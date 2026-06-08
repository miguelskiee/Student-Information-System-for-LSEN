<?php
// pages/grade_entry.php
session_start();
include '../models/db.php'; 
include '../models/deped_formulas.php'; // Include the new logic

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];
$studentID = $_GET['student_id'] ?? null;

if (!$studentID) { die("Error: No student selected."); }

// 2. FETCH STUDENT DETAILS
$studentStmt = $conn->prepare("SELECT FirstName, LastName, Section FROM Students WHERE StudentID = ?");
$studentStmt->execute([$studentID]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

// 3. FETCH TEACHER'S SUBJECTS
$subjectsStmt = $conn->prepare("
    SELECT s.SubjectID, s.SubjectName, s.Category 
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
");
$subjectsStmt->execute([$teacherID]); 
$subjectsList = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

// 4. HANDLE FILTERS
$selectedSubjectID = $_GET['subject_id'] ?? ($subjectsList[0]['SubjectID'] ?? null);
$selectedTerm = $_GET['term'] ?? '2024-Q1'; 
$termsList = ['2024-Q1', '2024-Q2', '2024-Q3', '2024-Q4'];

// Get Subject Category for Weights
$subjectCategory = 'Math/Science'; // Default
foreach ($subjectsList as $s) {
    if ($s['SubjectID'] == $selectedSubjectID) $subjectCategory = $s['Category'];
}

// 5. CHECK IF GRADE RECORD EXISTS
$recordExists = false;
$termGrade = [];
$assignments = [];
$computedGrade = 0;

if ($selectedSubjectID) {
    $gradeStmt = $conn->prepare("
        SELECT ar.AcademicID, s.SubjectName, ar.Score, ar.Comments, ar.MaxScore
        FROM AcademicRecords ar
        JOIN Subjects s ON ar.SubjectID = s.SubjectID
        WHERE ar.StudentID = ? AND ar.TeacherID = ? AND ar.Term = ? AND ar.SubjectID = ?
        LIMIT 1
    ");
    $gradeStmt->execute([$studentID, $teacherID, $selectedTerm, $selectedSubjectID]);
    $termGrade = $gradeStmt->fetch(PDO::FETCH_ASSOC);

    if ($termGrade) {
        $recordExists = true;
        // Fetch Assignments with Type
        $assignmentsStmt = $conn->prepare("
            SELECT a.AssignmentID, a.Title, a.MaxScore, a.AssignmentType, 
                   COALESCE(ag.Score, 0) AS StudentScore
            FROM Assignments a
            LEFT JOIN AssignmentGrades ag ON a.AssignmentID = ag.AssignmentID AND ag.StudentID = ?
            WHERE a.TeacherID = ? AND a.SubjectID = ?
            ORDER BY a.AssignmentType, a.DueDate ASC
        ");
        $assignmentsStmt->execute([$studentID, $teacherID, $selectedSubjectID]);
        $assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate Real-Time Grade for Display
        $computedGrade = DepEdGrading::calculateInitialGrade($assignments, $subjectCategory);
        $finalTransmuted = DepEdGrading::transmuteGrade($computedGrade);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>DepEd Grading Entry</title>
  <style>
    /* ... [Keep your existing styles] ... */
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: Arial, sans-serif; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 900px; margin: 0 auto; }
    
    .component-header { background: #e9ecef; padding: 10px; font-weight: bold; margin-top: 20px; border-left: 5px solid #007bff; }
    .grade-display { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: right; }
    .grade-value { font-size: 2rem; font-weight: bold; }
    
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
    th { background-color: #f8f9fa; }
    input[type="number"] { width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; }
    .btn-green { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
  </style>
  <script>
    function updateFilters() {
        const studentId = <?php echo json_encode($studentID); ?>;
        const subjectId = document.getElementById('subject_select').value;
        const term = document.getElementById('term_select').value;
        window.location.href = `grade_entry.php?student_id=${studentId}&subject_id=${subjectId}&term=${term}`;
    }
  </script>
</head>
<body>
  <div class="header">
    <h1>📝 DepEd Grading: <?php echo htmlspecialchars($student['FirstName']); ?></h1>
    <p>Subject: <strong><?php echo $subjectCategory; ?></strong> Weights Applied</p>
  </div>
  
  <div class="container">
    <div style="margin-bottom: 20px;">
        <select id="term_select" onchange="updateFilters()">
            <?php foreach ($termsList as $t): echo "<option value='$t' ".($t==$selectedTerm?'selected':'').">$t</option>"; endforeach; ?>
        </select>
        <select id="subject_select" onchange="updateFilters()">
            <?php foreach ($subjectsList as $s): echo "<option value='{$s['SubjectID']}' ".($s['SubjectID']==$selectedSubjectID?'selected':'').">{$s['SubjectName']}</option>"; endforeach; ?>
        </select>
    </div>

    <?php if (!$recordExists): ?>
        <p>No record found. Please <a href="#" onclick="document.forms['init'].submit()">Initialize Grading</a>.</p>
        <form name="init" action="../controllers/init_grade.php" method="POST" style="display:none;">
            <input type="hidden" name="student_id" value="<?php echo $studentID; ?>">
            <input type="hidden" name="subject_id" value="<?php echo $selectedSubjectID; ?>">
            <input type="hidden" name="term" value="<?php echo $selectedTerm; ?>">
            <input type="hidden" name="teacher_id" value="<?php echo $teacherID; ?>">
        </form>
    <?php else: ?>
        
        <form action="../controllers/grade_submit.php" method="POST">
            <input type="hidden" name="student_id" value="<?php echo $studentID; ?>">
            <input type="hidden" name="academic_id_term" value="<?php echo $termGrade['AcademicID']; ?>">
            <input type="hidden" name="subject_category" value="<?php echo $subjectCategory; ?>">

            <?php 
            // Group Assignments by Component
            $groups = ['WW' => [], 'PT' => [], 'QA' => []];
            foreach ($assignments as $a) {
                $comp = DepEdGrading::mapTypeToComponent($a['AssignmentType']);
                $groups[$comp][] = $a;
            }
            ?>

            <div class="component-header">Written Works (Homework, Quiz, Essay)</div>
            <table>
                <tr><th>Title</th><th>Score</th><th>Max</th></tr>
                <?php foreach ($groups['WW'] as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['Title']); ?></td>
                        <td><input type="number" name="assignment_score[<?php echo $a['AssignmentID']; ?>]" value="<?php echo $a['StudentScore']; ?>" max="<?php echo $a['MaxScore']; ?>"></td>
                        <td><?php echo $a['MaxScore']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="component-header">Performance Tasks (Projects, Activities)</div>
            <table>
                <tr><th>Title</th><th>Score</th><th>Max</th></tr>
                <?php foreach ($groups['PT'] as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['Title']); ?></td>
                        <td><input type="number" name="assignment_score[<?php echo $a['AssignmentID']; ?>]" value="<?php echo $a['StudentScore']; ?>" max="<?php echo $a['MaxScore']; ?>"></td>
                        <td><?php echo $a['MaxScore']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="component-header">Quarterly Assessment (Exam)</div>
            <table>
                <tr><th>Title</th><th>Score</th><th>Max</th></tr>
                <?php foreach ($groups['QA'] as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['Title']); ?></td>
                        <td><input type="number" name="assignment_score[<?php echo $a['AssignmentID']; ?>]" value="<?php echo $a['StudentScore']; ?>" max="<?php echo $a['MaxScore']; ?>"></td>
                        <td><?php echo $a['MaxScore']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="grade-display">
                <div>Initial Grade: <strong><?php echo number_format($computedGrade, 2); ?></strong></div>
                <div>Transmuted Grade (Report Card): <span class="grade-value"><?php echo $finalTransmuted; ?></span></div>
                <small><em>Note: Click Save to update this calculation.</em></small>
            </div>

            <button type="submit" class="btn-green" style="width:100%; margin-top:10px;">Save & Recalculate Grades</button>
        </form>

    <?php endif; ?>
  </div>
</body>
</html>