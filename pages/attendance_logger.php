<?php
// pages/attendance_logger.php
session_start();
include '../model/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];
$subjectID = $_GET['subject_id'] ?? null;
$section = $_GET['section'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');
$statusOptions = ['Present', 'Absent', 'Late', 'Excused'];

// --- 1. DEFINE BEHAVIOR OPTIONS ---
$behaviorOptions = [
    'Challenging' => [
        'Aggression', 'Self-Injury', 'Elopement', 'Property Damage', 
        'Meltdown', 'Non-Compliance', 'Social Withdrawal'
    ],
    'Adaptive/Positive' => [
        'Self-Advocacy', 'Self-Regulation', 'Social Initiation', 
        'Task Completion', 'Peer Support', 'Skill Mastery'
    ]
];

if (!$subjectID || !$section) {
    die("Error: Subject ID and Section are required to log attendance.");
}

// Fetch subject name for display
$subjectName = $conn->prepare("SELECT SubjectName FROM Subjects WHERE SubjectID = ?");
$subjectName->execute([$subjectID]);
$subject = $subjectName->fetchColumn();

// Fetch students in this specific class section
$studentsStmt = $conn->prepare("
    SELECT StudentID, FirstName, LastName, Disability
    FROM Students
    WHERE GradeLevel = (
        SELECT GradeLevel FROM Subjects WHERE SubjectID = ?
    ) AND Section = ?
    ORDER BY LastName
");
$studentsStmt->execute([$subjectID, $section]);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing attendance records
$existingRecords = $conn->prepare("
    SELECT StudentID, Status, Notes
    FROM SchoolAttendanceLogs
    WHERE Date = ? AND RecordedBy = ?
");
$existingRecords->execute([$date, $teacherID]);
$records = $existingRecords->fetchAll(PDO::FETCH_KEY_PAIR | PDO::FETCH_GROUP);

// --- 2. FETCH EXISTING BEHAVIORS FOR THIS DATE ---
$behaviorStmt = $conn->prepare("
    SELECT student_id, behavior_type 
    FROM behavior_records 
    WHERE record_date = ?
");
$behaviorStmt->execute([$date]);
// Creates array: [student_id => behavior_type]
$existingBehaviors = $behaviorStmt->fetchAll(PDO::FETCH_KEY_PAIR); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log Attendance & Behavior: <?php echo htmlspecialchars($subject); ?></title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: sans-serif; }
    .header h1 { color: #ffc107; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .logger-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 900px; margin: 0 auto; }
    .controls { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center; }
    .controls label { font-weight: 600; color: #6c757d; margin-right: 10px; }
    .controls input[type="date"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    select, textarea { border: 1px solid #ccc; border-radius: 4px; padding: 6px; width: 100%; box-sizing: border-box; }
    .submit-btn { background-color: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; width: 100%; }
    .submit-btn:hover { background-color: #0056b3; }
    /* Visual distinction for behavior dropdown */
    .behavior-select { border-color: #17a2b8; }
  </style>
  <script>
    function updateDate() {
        const date = document.getElementById('date_select').value;
        const url = new URL(window.location.href);
        url.searchParams.set('date', date);
        window.location.href = url.toString();
    }
  </script>
</head>
<body>
  <div class="header"><h1>📅 Attendance & Behavior: <?php echo htmlspecialchars($subject); ?></h1></div>
  
  <div class="logger-container">
    <div class="controls">
        <div>
            <label for="date_select">Date:</label>
            <input type="date" id="date_select" value="<?php echo htmlspecialchars($date); ?>" onchange="updateDate()">
        </div>
        <p style="color: #dc3545; font-weight: 600;">Students: <?php echo count($students); ?></p>
    </div>
    
    <form action="../model/attendance_submit.php" method="POST">
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
        <input type="hidden" name="teacher_id" value="<?php echo $teacherID; ?>">

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Student Name</th>
                    <th style="width: 15%;">Disability</th>
                    <th style="width: 15%;">Attendance</th>
                    <th style="width: 25%;">Daily Behavior</th> <th style="width: 25%;">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): 
                    $sid = $student['StudentID'];
                    $currentStatus = $records[$sid][0]['Status'] ?? 'Present';
                    $currentNotes = $records[$sid][0]['Notes'] ?? '';
                    // Get existing behavior for this student on this date (if any)
                    $currentBehavior = $existingBehaviors[$sid] ?? '';
                ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($student['LastName'] . ', ' . $student['FirstName']); ?></strong>
                        </td>
                        <td><small style="color: #007bff;"><?php echo htmlspecialchars($student['Disability']); ?></small></td>
                        
                        <td>
                            <select name="status[<?php echo $sid; ?>]">
                                <?php foreach ($statusOptions as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo ($opt == $currentStatus) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <select name="behavior[<?php echo $sid; ?>]" class="behavior-select">
                                <option value="">-- No Record --</option>
                                <?php foreach ($behaviorOptions as $category => $behaviors): ?>
                                    <optgroup label="<?php echo $category; ?>">
                                        <?php foreach ($behaviors as $b): ?>
                                            <option value="<?php echo $b; ?>" <?php echo ($b === $currentBehavior) ? 'selected' : ''; ?>>
                                                <?php echo $b; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <textarea name="notes[<?php echo $sid; ?>]" rows="1" placeholder="Details..."><?php echo htmlspecialchars($currentNotes); ?></textarea>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <button type="submit" class="submit-btn">Save Records for <?php echo date('M d, Y', strtotime($date)); ?></button>
    </form>
  </div>
</body>
</html>