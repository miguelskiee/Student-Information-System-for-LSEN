<?php
// model/init_grade.php
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") { die("Invalid request."); }

$studentID = $_POST['student_id'];
$subjectID = $_POST['subject_id'];
$term = $_POST['term'];
$teacherID = $_POST['teacher_id'];

// Check if it already exists to avoid duplicates
$check = $conn->prepare("SELECT AcademicID FROM AcademicRecords WHERE StudentID=? AND SubjectID=? AND Term=?");
$check->execute([$studentID, $subjectID, $term]);

if (!$check->fetch()) {
    // Insert new blank record
    $sql = "INSERT INTO AcademicRecords (StudentID, SubjectID, TeacherID, Term, Score, MaxScore, AttendanceDays, TotalPossibleDays) 
            VALUES (?, ?, ?, ?, 0, 100, 0, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$studentID, $subjectID, $teacherID, $term]);
}

// Redirect back to grade entry
header("Location: ../views/grade_entry.php?student_id=$studentID&subject_id=$subjectID&term=$term");
exit;
?>