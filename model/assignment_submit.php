<?php
// model/assignment_submit.php
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$teacherID = $_POST['teacher_id'];
$subjectID = $_POST['subject_id'];
$title = $_POST['title'];
$type = $_POST['assignment_type'];
$maxScore = $_POST['max_score'];
$dueDate = $_POST['due_date'];
$description = $_POST['description'];

$sql = "
    INSERT INTO Assignments 
    (SubjectID, TeacherID, Title, Description, AssignmentType, MaxScore, DueDate) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute([$subjectID, $teacherID, $title, $description, $type, $maxScore, $dueDate]);
    $message = "Assignment '{$title}' created successfully.";
    header("Location: ../pages/Teacher_Classes.php?status=success&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    header("Location: ../pages/error_page.php?status=error&msg=" . urlencode($message));
    exit;
}
?>