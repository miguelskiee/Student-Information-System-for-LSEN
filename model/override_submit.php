<?php
// model/override_submit.php
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$aiGradeID = $_POST['ai_grade_id'] ?? null;
$overrideScore = $_POST['override_score'] ?? null;
$teacherComments = $_POST['teacher_comments'] ?? '';

if (!$aiGradeID || $overrideScore === null) {
    die("Missing required grading data.");
}

$updateQuery = "
    UPDATE AI_GradingResults 
    SET TeacherOverrideScore = ?, TeacherComments = ?, ProcessedAt = NOW()
    WHERE AIGradeID = ?
";

try {
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$overrideScore, $teacherComments, $aiGradeID]);
    
    // NOTE: In a full system, you would also update the AcademicRecords here.
    
    $message = "Grade override saved successfully for AI Grade ID $aiGradeID.";
    header("Location: ../pages/grading_override.php?status=success&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    header("Location: ../pages/error_page.php?status=error&msg=" . urlencode($message));
    exit;
}
?>