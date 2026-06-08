<?php
// model/grade_submit.php
include 'db.php'; 
include 'deped_formulas.php'; // Include calculation logic

if ($_SERVER["REQUEST_METHOD"] !== "POST") { die("Invalid request method."); }

$studentID = $_POST['student_id'] ?? null;
$academicIDTerm = $_POST['academic_id_term'] ?? null;
$assignmentScores = $_POST['assignment_score'] ?? [];
$subjectCategory = $_POST['subject_category'] ?? 'Math/Science';

try {
    $conn->beginTransaction();

    // 1. Save all granular assignment grades FIRST
    $insertAssignmentQuery = "
        INSERT INTO AssignmentGrades (AssignmentID, StudentID, Score, GradedAt) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE Score = VALUES(Score), GradedAt = NOW()
    ";
    $stmtAssignment = $conn->prepare($insertAssignmentQuery);
    
    foreach ($assignmentScores as $assignmentID => $score) {
        $score = max(0, floatval($score)); // Sanitize
        $stmtAssignment->execute([$assignmentID, $studentID, $score]);
    }

    // 2. Re-Fetch all scores to calculate the final DepEd Grade
    // We fetch again to ensure we have MaxScore data and correct types
    $calcQuery = "
        SELECT a.AssignmentType, a.MaxScore, ag.Score AS StudentScore
        FROM Assignments a
        JOIN AssignmentGrades ag ON a.AssignmentID = ag.AssignmentID
        WHERE ag.StudentID = ? AND a.AssignmentID IN (" . implode(',', array_keys($assignmentScores)) . ")
    ";
    // Note: In production, better to fetch by Term/Subject, but this works for the active form context
    // A more robust way is to fetch ALL assignments for this subject/student to cover unchanged ones:
    
    $fullCalcQuery = "
        SELECT a.AssignmentType, a.MaxScore, COALESCE(ag.Score, 0) AS StudentScore
        FROM Assignments a
        LEFT JOIN AssignmentGrades ag ON a.AssignmentID = ag.AssignmentID AND ag.StudentID = ?
        WHERE a.SubjectID = (SELECT SubjectID FROM AcademicRecords WHERE AcademicID = ?)
    ";
    
    $stmtCalc = $conn->prepare($fullCalcQuery);
    $stmtCalc->execute([$studentID, $academicIDTerm]);
    $gradeData = $stmtCalc->fetchAll(PDO::FETCH_ASSOC);

    // 3. Compute DepEd Grades
    $initialGrade = DepEdGrading::calculateInitialGrade($gradeData, $subjectCategory);
    $transmutedGrade = DepEdGrading::transmuteGrade($initialGrade);

    // 4. Update the Final Term Record
    $updateTermQuery = "
        UPDATE AcademicRecords 
        SET Score = ?, Comments = CONCAT('Initial Grade: ', ?), UpdatedAt = NOW() 
        WHERE AcademicID = ?
    ";
    $stmtTerm = $conn->prepare($updateTermQuery);
    $stmtTerm->execute([$transmutedGrade, number_format($initialGrade, 2), $academicIDTerm]);

    $conn->commit();
    
    // Redirect back
    $referrer = $_SERVER['HTTP_REFERER'];
    header("Location: $referrer");
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    die("Database Error: " . $e->getMessage());
}
?>