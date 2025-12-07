<?php
// model/attendance_submit.php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// 1. Get Form Data
$date = $_POST['date'];
$teacherID = $_POST['teacher_id'];
$statuses = $_POST['status'];      // Array: [student_id => status]
$notes = $_POST['notes'];          // Array: [student_id => note]
$behaviors = $_POST['behavior'];   // Array: [student_id => behavior_type]

// 2. Define Behavior Points Map (Same as used in log_behavior.php)
$behaviorMap = [
    // Negative
    'Aggression' => ['Negative', 5], 'Self-Injury' => ['Negative', 5], 'Elopement' => ['Negative', 5],
    'Property Damage' => ['Negative', 4], 'Meltdown' => ['Negative', 3], 
    'Non-Compliance' => ['Negative', 2], 'Social Withdrawal' => ['Negative', 2],
    // Positive
    'Self-Advocacy' => ['Positive', -5], 'Self-Regulation' => ['Positive', -4], 
    'Skill Mastery' => ['Positive', -3], 'Social Initiation' => ['Positive', -3],
    'Task Completion' => ['Positive', -2], 'Peer Support' => ['Positive', -2]
];

try {
    $conn->beginTransaction();

    // Prepare Attendance SQL (Insert or Update if exists)
    $stmtAttendance = $conn->prepare("
        INSERT INTO SchoolAttendanceLogs (StudentID, Date, Status, Notes, RecordedBy) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        Status = VALUES(Status), Notes = VALUES(Notes), RecordedBy = VALUES(RecordedBy)
    ");

    // Prepare Behavior SQL
    // First, delete any existing behavior for this student on this date to prevent duplicates/conflicts
    $stmtDeleteBehavior = $conn->prepare("DELETE FROM behavior_records WHERE student_id = ? AND record_date = ?");
    
    // Then insert the new one
    $stmtInsertBehavior = $conn->prepare("
        INSERT INTO behavior_records (student_id, record_date, behavior_type, category, points, description) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    foreach ($statuses as $studentID => $status) {
        $note = $notes[$studentID] ?? '';
        
        // --- A. PROCESS ATTENDANCE ---
        $stmtAttendance->execute([$studentID, $date, $status, $note, $teacherID]);

        // --- B. PROCESS BEHAVIOR ---
        // 1. Clear old entry for this day (allows updating from 'Aggression' to 'None' or 'Helping')
        $stmtDeleteBehavior->execute([$studentID, $date]);

        $bType = $behaviors[$studentID] ?? '';
        
        // 2. Insert new entry if a behavior was actually selected
        if (!empty($bType) && isset($behaviorMap[$bType])) {
            $category = $behaviorMap[$bType][0];
            $points = $behaviorMap[$bType][1];
            // We use the attendance note as the description for context, or you can leave it blank
            $description = "Logged during attendance. Note: " . $note;

            $stmtInsertBehavior->execute([$studentID, $date, $bType, $category, $points, $description]);
        }
    }

    $conn->commit();
    
    // Redirect back to the previous page (using Referer) or a success page
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php';
    header("Location: $redirectUrl");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    die("Error saving records: " . $e->getMessage());
}
?>