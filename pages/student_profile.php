<?php
// pages/student_profile.php
// CORRECTED PATH: pages -> ../ -> model/db.php
include '../model/db.php'; 

$studentID = $_GET['id'] ?? die("Error: Student ID required."); 

// --- 1. Fetch Student Core Data & Latest Alert ---
$stmt = $conn->prepare("
    SELECT s.*, 
        apa.RiskLevel, apa.PredictedIssue, apa.RiskProbability, apa.AnomalyScore,
        t.FirstName AS TeacherFirstName, t.LastName AS TeacherLastName
    FROM Students s
    -- We use AcademicRecords to find the last teacher who recorded data for them
    LEFT JOIN AcademicRecords ar ON s.StudentID = ar.StudentID
    LEFT JOIN Teachers t ON ar.TeacherID = t.TeacherID
    -- Pull the latest AI alert record for the status
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID AND apa.AlertID = (
        SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID
    )
    WHERE s.StudentID = ?
    LIMIT 1
");
$stmt->execute([$studentID]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student record not found for ID: " . htmlspecialchars($studentID));
}

// --- 2. Fetch Detailed History (Academic and Behavioral/Attendance Logs) ---
// Academic History (Past terms, for trend visualization)
$history_records_stmt = $conn->prepare("
    SELECT Term, Grade, AttendanceRate, BehaviorScore 
    FROM StudentPerformanceHistory 
    WHERE StudentID = ? 
    ORDER BY RecordedAt DESC
    LIMIT 5
");
$history_records_stmt->execute([$studentID]);
$history_records = $history_records_stmt->fetchAll(PDO::FETCH_ASSOC);

// Behavioral Log (Combined Attendance and Observations)
// Using UNION ALL to mix records from two tables (Attendance and Behavior Observations)
$behavior_logs_stmt = $conn->prepare("
    SELECT Date, Status, Notes FROM SchoolAttendanceLogs WHERE StudentID = ?
    UNION ALL
    SELECT DateObserved as Date, 'Observation' as Status, Notes FROM BehavioralData WHERE StudentID = ?
    ORDER BY Date DESC
    LIMIT 10
");
$behavior_logs_stmt->execute([$studentID, $studentID]);
$behavior_logs = $behavior_logs_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 3. Fetch AI Recommendations ---
$recs_stmt = $conn->prepare("
    SELECT RecommendedStrategy, Source, DateGenerated 
    FROM AI_TeachingRecommendations 
    WHERE StudentID = ? 
    ORDER BY DateGenerated DESC
    LIMIT 5
");
$recs_stmt->execute([$studentID]);
$recommendations = $recs_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Helper Functions ---
function getRiskBadge($risk, $is_large = false) {
    $class = '';
    switch ($risk) {
        case 'High': $class = 'bg-high'; break;
        case 'Medium': $class = 'bg-medium'; break;
        default: $class = 'bg-low'; break;
    }
    $size = $is_large ? '1.5rem' : '1rem';
    return "<span class='badge {$class}' style='font-size: {$size};'>{$risk}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile: <?php echo htmlspecialchars($student['LastName']); ?></title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    
    .profile-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    
    .panel { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
    .panel h2 { color: #343a40; border-left: 5px solid #28a745; padding-left: 10px; margin-bottom: 15px; font-size: 1.4rem; }
    
    .data-row { margin-bottom: 10px; font-size: 1rem; }
    .data-row strong { display: inline-block; width: 140px; color: #6c757d; font-weight: 600; }
    
    /* Risk Status Panel */
    .risk-panel { border-left: 5px solid #dc3545; }
    .risk-panel h2 { border-left-color: #dc3545; color: #dc3545; }
    
    .badge { padding: 5px 10px; border-radius: 4px; font-weight: bold; color: white; }
    .bg-high { background-color: #dc3545; } 
    .bg-medium { background-color: #ffc107; color: #333; }
    .bg-low { background-color: #28a745; }

    /* Tables */
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    
    .recs-list { list-style: none; padding-left: 0; }
    .recs-list li { border-bottom: 1px dotted #eee; padding: 8px 0; font-size: 0.95rem; }
  </style>
</head>
<body>
  <div class="header">
    <h1>🧑‍🎓 <?php echo htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']); ?> 
      <span style="font-size: 1.2rem; font-weight: normal; color: #6c757d;">(Grade: <?php echo htmlspecialchars($student['GradeLevel'] ?? 'N/A'); ?>)</span>
    </h1>
    <a href="student_form.php?id=<?php echo $studentID; ?>" style="float: right; color: #28a745; text-decoration: none; margin-top: -30px;">[Edit Student Profile]</a>
  </div>

  <div class="profile-grid">
    <!-- LEFT COLUMN: CORE INFO, NOTES, RECOMMENDATIONS -->
    <div>
        <div class="panel">
            <h2>General Information \& SPED Needs</h2>
            <div class="data-row"><strong>Student ID:</strong> <?php echo htmlspecialchars($student['StudentID']); ?></div>
            <div class="data-row"><strong>Grade/Section:</strong> <?php echo htmlspecialchars($student['GradeLevel'] . ' - ' . $student['Section']); ?></div>
            <div class="data-row"><strong>Primary Disability:</strong> <span style="font-weight: bold; color: #007bff;"><?php echo htmlspecialchars($student['Disability'] ?? 'N/A'); ?></span></div>
            <div class="data-row"><strong>Case Manager:</strong> <?php echo htmlspecialchars($student['TeacherFirstName'] . ' ' . $student['TeacherLastName'] ?? 'N/A'); ?></div>
            
            <h3 style="font-size: 1.1rem; color: #343a40; margin-top: 20px;">Teacher Notes</h3>
            <p style="white-space: pre-wrap; font-size: 0.9rem; color: #6c757d;"><?php echo htmlspecialchars($student['Notes'] ?? 'No notes recorded.'); ?></p>
        </div>

<div class="panel">
            <h2>🧠 AI Teaching Recommendations</h2>
            <p style="color: #6c757d; margin-bottom: 10px;">Strategies tailored to the student's disability and risk status.</p>
            
            <?php if ($recommendations): ?>
            <ul class="recs-list">
                <?php foreach ($recommendations as $rec): ?>
                    <li style="padding: 15px 0; border-bottom: 1px solid #eee;">
                        <div style="margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: #007bff; font-size: 0.9rem;">
                                [<?php echo htmlspecialchars($rec['Source']); ?>]
                            </span>
                            <small style="color: #adb5bd;">
                                <?php echo date('M d, Y', strtotime($rec['DateGenerated'])); ?>
                            </small>
                        </div>
                        
                        <div style="color: #495057; line-height: 1.6; padding-left: 5px; font-size: 0.95rem;">
                            <?php echo nl2br(htmlspecialchars($rec['RecommendedStrategy'])); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
                <p style="color: #6c757d; font-style: italic; padding: 10px 0;">
                    No specific teaching recommendations generated yet. (Run ML script)
                </p>
            <?php endif; ?>
        </div>
    
    <!-- RIGHT COLUMN: AI STATUS, HISTORY, LOGS -->
    <div>
        <div class="panel risk-panel">
            <h2>🚨 Latest AI Risk Status</h2>
            <div class="data-row">
                <strong style="width: 80px;">Risk Level:</strong> 
                <?php echo getRiskBadge($student['RiskLevel'] ?? 'Low', true); ?>
            </div>
            <div class="data-row">
                <strong style="width: 80px;">Prediction:</strong> 
                <span style="font-size: 1rem;"><?php echo htmlspecialchars($student['PredictedIssue'] ?? 'Normal'); ?></span>
            </div>
            <div class="data-row" style="font-size: 0.9rem; color: #6c757d;">
            </div>
        </div>

        <div class="panel">
            <h2>📈 Academic History</h2>
            <p style="color: #6c757d; margin-bottom: 10px;">Last 5 term records (used for trend calculation).</p>
            <table class="data-table">
                <thead>
                    <tr><th>Term</th><th>GPA</th><th>Attendance Rate</th><th>Behavior Score</th></tr>
                </thead>
                <tbody>
                    <?php if ($history_records): ?>
                        <?php foreach ($history_records as $hr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($hr['Term']); ?></td>
                                <td><?php echo htmlspecialchars($hr['Grade']); ?></td>
                                <td><?php echo round(($hr['AttendanceRate'] ?? 0) * 100, 1) . '%'; ?></td>
                                <td><?php echo htmlspecialchars($hr['BehaviorScore']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No historical performance data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="panel">
            <h2>📜 Recent Behavior \& Attendance Log</h2>
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    <?php if ($behavior_logs): ?>
                        <?php foreach ($behavior_logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($log['Date'])); ?></td>
                                <td><span style="color: <?php echo ($log['Status'] == 'Absent' || $log['Status'] == 'Late') ? '#dc3545' : ($log['Status'] == 'Observation' ? '#007bff' : '#28a745'); ?>; font-weight: 600;"><?php echo htmlspecialchars($log['Status']); ?></span></td>
                                <td><?php echo htmlspecialchars(substr($log['Notes'], 0, 40)) . (strlen($log['Notes']) > 40 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No recent logs recorded.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</body>
</html>