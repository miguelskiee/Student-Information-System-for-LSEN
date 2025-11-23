<?php
// model/grade_submit.php
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$studentID = $_POST['student_id'] ?? null;
$academicIDTerm = $_POST['academic_id_term'] ?? null; // Final Term AcademicID
$finalScore = $_POST['final_score'] ?? null;
$finalComments = $_POST['final_comments'] ?? '';
$assignmentScores = $_POST['assignment_score'] ?? []; // Array of assignment scores

$updateCount = 0;

if (!$studentID || !$academicIDTerm || $finalScore === null) {
    die("Missing required final term score data.");
}

try {
    $conn->beginTransaction();
    
    // --- 1. Update Final Academic Record (Term Score) ---
    $updateTermQuery = "
        UPDATE AcademicRecords 
        SET Score = ?, Comments = ?, UpdatedAt = NOW() 
        WHERE AcademicID = ? AND StudentID = ?
    ";
    $stmtTerm = $conn->prepare($updateTermQuery);
    
    $finalScore = max(0, min(100, floatval($finalScore))); // Sanitize score
    $stmtTerm->execute([$finalScore, $finalComments, $academicIDTerm, $studentID]);
    $updateCount += $stmtTerm->rowCount();


    // --- 2. Insert/Update Granular Assignment Scores ---
    $insertAssignmentQuery = "
        INSERT INTO AssignmentGrades (AssignmentID, StudentID, Score, GradedAt) 
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE Score = VALUES(Score), GradedAt = NOW()
    ";
    $stmtAssignment = $conn->prepare($insertAssignmentQuery);
    
    foreach ($assignmentScores as $assignmentID => $score) {
        $score = max(0, min(100, floatval($score)));
        $stmtAssignment->execute([$assignmentID, $studentID, $score]);
        $updateCount++;
    }

    $conn->commit();
    $message = "Grades and assignments saved successfully ($updateCount updates).";
    header("Location: ../pages/Teacher_Students.php?status=updated&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $message = "Database Error: " . $e->getMessage();
    header("Location: ../pages/error_page.php?status=error&msg=" . urlencode($message));
    exit;
}
?>