<?php
// model/attendance_submit.php
include 'db.php'; 

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request method.");
}

$date = $_POST['date'];
$teacherID = $_POST['teacher_id'];
$statuses = $_POST['status'] ?? [];
$notes = $_POST['notes'] ?? [];

$insertOrUpdateQuery = "
    INSERT INTO SchoolAttendanceLogs (StudentID, Date, Status, RecordedBy, Notes) 
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE Status = VALUES(Status), Notes = VALUES(Notes)
";
$stmt = $conn->prepare($insertOrUpdateQuery);
$count = 0;

try {
    $conn->beginTransaction();
    
    foreach ($statuses as $studentID => $status) {
        $note = $notes[$studentID] ?? '';
        
        $stmt->execute([
            $studentID,
            $date,
            $status,
            $teacherID,
            $note
        ]);
        $count++;
    }
    
    $conn->commit();
    $message = "Attendance for $date saved successfully for $count students.";
    header("Location: ../pages/Teacher_Classes.php?status=success&msg=" . urlencode($message));
    exit;

} catch (PDOException $e) {
    $conn->rollBack();
    $message = "Database Error: " . $e->getMessage();
    header("Location: ../pages/error_page.php?status=error&msg=" . urlencode($message));
    exit;
}
?>