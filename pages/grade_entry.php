<?php
// pages/grade_entry.php
include '../model/db.php'; 

// --- Setup Variables ---
$teacherID = 1; // Simulated logged-in TeacherID
$studentID = $_GET['student_id'] ?? 1; // Use StudentID 1 as fallback

// --- Fetch all possible Terms and Subjects for the dropdowns (unchanged) ---
$subjectsStmt = $conn->prepare("
    SELECT s.SubjectID, s.SubjectName
    FROM AcademicRecords ar
    JOIN Subjects s ON ar.SubjectID = s.SubjectID
    WHERE ar.TeacherID = ? AND ar.StudentID = ?
    GROUP BY s.SubjectID, s.SubjectName
");
$subjectsStmt->execute([$teacherID, $studentID]);
$subjectsList = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

$termsStmt = $conn->prepare("
    SELECT DISTINCT Term FROM AcademicRecords 
    WHERE TeacherID = ? AND StudentID = ?
    ORDER BY Term DESC
");
$termsStmt->execute([$teacherID, $studentID]);
$termsList = $termsStmt->fetchAll(PDO::FETCH_ASSOC);


// --- Filter Logic ---
$selectedSubjectID = $_GET['subject_id'] ?? ($subjectsList[0]['SubjectID'] ?? null);
$selectedTerm = $_GET['term'] ?? ($termsList[0]['Term'] ?? 'N/A');


// --- Fetch Final Academic Record (for term score update) ---
$grades = [];
$student = ['FirstName' => 'Student', 'LastName' => 'Not Found'];

if ($selectedSubjectID && $selectedTerm) {
    // 1. Student Details
    $studentStmt = $conn->prepare("SELECT FirstName, LastName FROM Students WHERE StudentID = ?");
    $studentStmt->execute([$studentID]);
    $student = $studentStmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Final Term Grade Record
    $gradeStmt = $conn->prepare("
        SELECT ar.AcademicID, s.SubjectName, ar.Score, ar.Comments, ar.MaxScore
        FROM AcademicRecords ar
        JOIN Subjects s ON ar.SubjectID = s.SubjectID
        WHERE ar.StudentID = ? AND ar.TeacherID = ? AND ar.Term = ? AND ar.SubjectID = ?
        LIMIT 1
    ");
    $gradeStmt->execute([$studentID, $teacherID, $selectedTerm, $selectedSubjectID]);
    $grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Granular Assignments for this subject/teacher/term (NEW QUERY)
    $assignmentsStmt = $conn->prepare("
        SELECT a.AssignmentID, a.Title, a.MaxScore, ag.Score AS StudentScore
        FROM Assignments a
        LEFT JOIN AssignmentGrades ag ON a.AssignmentID = ag.AssignmentID AND ag.StudentID = ?
        WHERE a.TeacherID = ? AND a.SubjectID = ?
        ORDER BY a.DueDate ASC
    ");
    $assignmentsStmt->execute([$studentID, $teacherID, $selectedSubjectID]);
    $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enter Grades</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .grade-form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 800px; margin: 0 auto; }
    
    .filter-controls { display: flex; gap: 20px; margin-bottom: 20px; }
    .filter-controls label { font-weight: bold; color: #6c757d; margin-right: 5px; }
    .filter-controls select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    
    .grade-form-container h2 { margin-bottom: 20px; color: #343a40; border-bottom: 1px solid #eee; padding-bottom: 5px;}
    .grade-entry-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .grade-entry-table th, .grade-entry-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
    .grade-entry-table th { background-color: #f8f9fa; color: #495057; }
    input[type="number"], textarea { border: 1px solid #ccc; border-radius: 4px; padding: 8px; width: 100%; box-sizing: border-box; }
    .submit-btn { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
    .submit-btn:hover { background-color: #1e7e34; }
  </style>
  <script>
    function updateFilters() {
        const studentId = <?php echo json_encode($studentID); ?>;
        const subjectId = document.getElementById('subject_select').value;
        const term = document.getElementById('term_select').value;
        
        // Reload the page with the new filters
        window.location.href = `grade_entry.php?student_id=${studentId}&subject_id=${subjectId}&term=${term}`;
    }
  </script>
</head>
<body>
  <div class="header">
    <h1>📝 Grade Entry for <?php echo htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']); ?></h1>
  </div>
  
  <div class="grade-form-container">
    
    <div class="filter-controls">
        <label for="term_select">Term:</label>
        <select id="term_select" onchange="updateFilters()">
            <?php foreach ($termsList as $t): ?>
                <option value="<?php echo htmlspecialchars($t['Term']); ?>" <?php echo ($t['Term'] == $selectedTerm) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($t['Term']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="subject_select">Subject:</label>
        <select id="subject_select" onchange="updateFilters()">
            <?php foreach ($subjectsList as $s): ?>
                <option value="<?php echo htmlspecialchars($s['SubjectID']); ?>" <?php echo ($s['SubjectID'] == $selectedSubjectID) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s['SubjectName']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <!-- Check if a record was found -->
    <?php if (count($grades) == 0): ?>
        <p style="color: #dc3545; font-weight: bold;">
            No final term record found for the selected Subject and Term. 
            The academic record must be created first (AcademicID).
        </p>
    <?php else: 
        $termGrade = $grades[0];
    ?>
    
    <form action="../model/grade_submit.php" method="POST">
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentID); ?>">
        <input type="hidden" name="academic_id_term" value="<?php echo $termGrade['AcademicID']; ?>">
        
        <!-- ============================================== -->
        <!-- ASSIGNMENTS GRADING SECTION (NEW) -->
        <!-- ============================================== -->
        <h2 style="border-left: 5px solid #007bff; color: #007bff; margin-top: 25px;">1. Individual Assignments (Granular Grades)</h2>
        
        <table class="grade-entry-table">
            <thead>
                <tr>
                    <th>Assignment Title</th>
                    <th>Max Score</th>
                    <th>Student Score</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($assignments): ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['Title']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['MaxScore']); ?></td>
                            <td>
                                <input type="number" name="assignment_score[<?php echo $assignment['AssignmentID']; ?>]" 
                                    value="<?php echo htmlspecialchars($assignment['StudentScore']); ?>" 
                                    min="0" max="<?php echo $assignment['MaxScore']; ?>" step="0.1" required>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                     <tr><td colspan="3" style="color: #6c757d;">No assignments found for this subject/teacher.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- ============================================== -->
        <!-- FINAL TERM GRADE SECTION -->
        <!-- ============================================== -->
        <h2 style="border-left: 5px solid #28a745; color: #28a745; margin-top: 40px;">2. Final Term Grade & Comments (Term Score)</h2>
        <p style="color: #dc3545;">WARNING: This score is used for AI prediction!</p>

        <table class="grade-entry-table">
            <thead>
                <tr>
                    <th>Term Subject: <?php echo htmlspecialchars($termGrade['SubjectName']); ?></th>
                    <th>Final Score (0-<?php echo htmlspecialchars($termGrade['MaxScore']); ?>)</th>
                    <th>Overall Comments</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>\(\text{Academic ID}:\) <?php echo $termGrade['AcademicID']; ?></td>
                    <td>
                        <input type="number" name="final_score" 
                            value="<?php echo htmlspecialchars($termGrade['Score']); ?>" 
                            min="0" max="<?php echo htmlspecialchars($termGrade['MaxScore']); ?>" step="0.01" required>
                    </td>
                    <td>
                        <textarea name="final_comments" rows="2"><?php echo htmlspecialchars($termGrade['Comments']); ?></textarea>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <button type="submit" class="submit-btn">Save All Grades \& Update Term Record</button>
    </form>
    
    <?php endif; ?>
  </div>
</body>
</html>