<?php
// pages/Teacher_Grades.php
session_start();
include '../model/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];
$currentTerm = '2024-Q1'; // Default term for display

// 1. FETCH CLASSES & GRADING PROGRESS
// This query calculates:
// - Total Students per Section
// - Count of Graded Students (AcademicRecords) for this Term/Subject
$classesQuery = "
    SELECT 
        s.SubjectName, 
        s.SubjectID,
        ta.Section,
        COUNT(DISTINCT stud.StudentID) as TotalStudents,
        COUNT(DISTINCT ar.AcademicID) as GradedCount
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    LEFT JOIN Students stud ON ta.Section = stud.Section
    LEFT JOIN AcademicRecords ar ON stud.StudentID = ar.StudentID 
        AND ar.SubjectID = ta.SubjectID 
        AND ar.Term = '$currentTerm'
    WHERE ta.TeacherID = ?
    GROUP BY ta.Section, s.SubjectID
";
$stmt = $conn->prepare($classesQuery);
$stmt->execute([$teacherID]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. FETCH PENDING REVIEWS (AI Queue)
$queueQuery = "
    SELECT s.SubmissionID, ass.Title, stud.FirstName, stud.LastName, aigr.SuggestedScore, aigr.ConfidenceLevel
    FROM StudentSubmissions s
    JOIN Assignments ass ON s.AssignmentID = ass.AssignmentID
    JOIN AI_GradingResults aigr ON s.SubmissionID = aigr.SubmissionID
    JOIN Students stud ON s.StudentID = stud.StudentID
    WHERE ass.TeacherID = ? AND aigr.ConfidenceLevel < 0.80 
    LIMIT 5";
$stmtQueue = $conn->prepare($queueQuery);
$stmtQueue->execute([$teacherID]);
$gradingQueue = $stmtQueue->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Grades</title>
  <style>
        body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    
    .grid-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    
    /* CLASS CARDS */
    .class-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 25px; border-left: 5px solid #007bff; }
    .class-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .class-title { font-size: 1.2rem; font-weight: bold; color: #343a40; }
    .class-meta { color: #6c757d; font-size: 0.9rem; }
    
    /* PROGRESS BAR */
    .progress-wrapper { background-color: #e9ecef; border-radius: 5px; height: 10px; width: 100%; margin: 10px 0; overflow: hidden; }
    .progress-bar { height: 100%; background-color: #28a745; transition: width 0.5s; }
    
    .btn { text-decoration: none; padding: 8px 15px; border-radius: 4px; font-weight: bold; font-size: 0.9rem; display: inline-block; }
    .btn-primary { background-color: #007bff; color: white; }
    .btn-primary:hover { background-color: #0056b3; }
    
    /* QUEUE TABLE */
    .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .queue-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .queue-table th, .queue-table td { text-align: left; padding: 8px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    .queue-table th { background-color: #fff3cd; color: #856404; }
  </style>
</head>
<body>
  <div class="header"><h1>📊 Gradebook Dashboard (<?php echo $currentTerm; ?>)</h1></div>

  <div class="grid-container">
    
    <div>
        <h3 style="color: #6c757d; margin-top: 0;">My Classes & Progress</h3>
        
        <?php if (empty($classes)): ?>
            <div class="class-card">No classes assigned yet.</div>
        <?php else: ?>
            <?php foreach ($classes as $cls): ?>
                <?php 
                    $percent = ($cls['TotalStudents'] > 0) ? round(($cls['GradedCount'] / $cls['TotalStudents']) * 100) : 0;
                ?>
                <div class="class-card">
                    <div class="class-header">
                        <div class="class-title"><?php echo htmlspecialchars($cls['SubjectName']); ?></div>
                        <div class="class-meta">Section: <strong><?php echo htmlspecialchars($cls['Section']); ?></strong></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px;">
                        <span>Grading Progress: <?php echo $cls['GradedCount']; ?> / <?php echo $cls['TotalStudents']; ?> Students</span>
                        <span><?php echo $percent; ?>%</span>
                    </div>
                    
                    <div class="progress-wrapper">
                        <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                    </div>
                    
                    <div style="margin-top: 15px; text-align: right;">
                        <a href="Teacher_Students.php?section=<?php echo urlencode($cls['Section']); ?>" class="btn btn-primary">
                            📂 Open Gradebook
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div>
        <div class="panel" style="margin-top: 40px;">
            <h3 style="color: #dc3545; margin-top: 0;">⚠️ AI Review Queue</h3>
            <p style="font-size: 0.85rem; color: #666;">Low confidence grades needing manual override.</p>
            
            <table class="queue-table">
                <thead>
                    <tr><th>Student</th><th>AI Score</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if ($gradingQueue): ?>
                        <?php foreach ($gradingQueue as $q): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($q['FirstName'] . ' ' . $q['LastName']); ?></td>
                            <td><?php echo round($q['SuggestedScore']); ?> <small>(<?php echo round($q['ConfidenceLevel']*100); ?>%)</small></td>
                            <td><a href="grading_override.php?id=<?php echo $q['SubmissionID']; ?>" style="color: #007bff;">Review</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; color: #28a745;">All caught up!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 10px; text-align: center;">
                <a href="grading_override.php" style="font-size: 0.9rem; text-decoration: none;">View All Pending &rarr;</a>
            </div>
        </div>
    </div>

  </div>
</body>
</html>