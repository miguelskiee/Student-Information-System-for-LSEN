<?php
// pages/student_profile.php
include '../model/db.php';


$studentID = $_GET['id'] ?? die("Error: Student ID required.");

$stmt = $conn->prepare("
    SELECT s.*,
        apa.RiskLevel, apa.PredictedIssue, apa.RiskProbability, apa.AnomalyScore,
        t.FirstName AS TeacherFirstName, t.LastName AS TeacherLastName,
        -- Fetch Current Academic Performance Data
        ar.Score AS CurrentGrade,
        ar.Term AS CurrentTerm,
        ar.AttendanceDays,
        ar.TotalPossibleDays
    FROM Students s
    -- We use AcademicRecords to find the current performance
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


// Calculate Current Attendance Rate for display
$currentAttendanceRate = 0;
if (isset($student['AttendanceDays']) && isset($student['TotalPossibleDays']) && $student['TotalPossibleDays'] > 0) {
    $currentAttendanceRate = ($student['AttendanceDays'] / $student['TotalPossibleDays']) * 100;
}


// --- 2. Fetch Detailed History (PAST Terms only) ---
$history_records_stmt = $conn->prepare("
    SELECT Term, Grade, AttendanceRate, BehaviorScore
    FROM StudentPerformanceHistory
    WHERE StudentID = ?
    ORDER BY RecordedAt DESC
    LIMIT 5
");
$history_records_stmt->execute([$studentID]);
$history_records = $history_records_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- 3. Behavioral Log ---
$behavior_logs_stmt = $conn->prepare("
    -- 1. Attendance Logs: Convert text to utf8 to prevent collation errors
    SELECT Date, CONVERT(Status USING utf8) as Status, CONVERT(Notes USING utf8) AS Detail
    FROM SchoolAttendanceLogs
    WHERE StudentID = ?
   
    UNION ALL
   
    -- 2. Behavioral Observations
    SELECT DateObserved as Date, 'Observation' as Status, CONVERT(BehaviorInClass USING utf8) AS Detail
    FROM BehavioralData
    WHERE StudentID = ?
   
    UNION ALL


    -- 3. AI/ML Behavior Records
    SELECT record_date as Date, 'Behavior Record' as Status, CONVERT(behavior_type USING utf8) AS Detail
    FROM behavior_records
    WHERE student_id = ?


    ORDER BY Date DESC
    LIMIT 10
");


// Execute the query passing the ID 3 times (once for each SELECT)
$behavior_logs_stmt->execute([$studentID, $studentID, $studentID]);
$behavior_logs = $behavior_logs_stmt->fetchAll(PDO::FETCH_ASSOC);




// --- 4. Fetch AI Recommendations ---
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
    $size = $is_large ? '0.85rem' : '0.85rem';
    return "<span class='badge {$class}' style='font-size: {$size};'>{$risk}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile: <?php echo htmlspecialchars($student['LastName']); ?></title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
 <style>
        body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    
    .profile-grid {
        display: block;
    }   
    .panel { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; height: 300px;}
    .panel h2 { color: #343a40; border-left: 5px solid #284ca7ff; padding-left: 20px; margin-bottom: 15px; font-size: 1.4rem; }
   
    .data-row { margin-bottom: 10px; font-size: 1rem; }
    .data-row strong { display: inline-block; width: 150px; color: #6c757d; font-weight: 600; }
   
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
    .data-table th { background-color: #004080; color: #ffffffff; }

    /* Highlight Current Term */
    .current-term-row { background-color: #fff3cd; font-weight: bold; border-left: 4px solid #ffc107; }
   
    .recs-list { list-style: none; padding-left: 0; }
    .recs-list li { border-bottom: 1px dotted #eee; padding: 8px 0; font-size: 0.95rem; }
    .panel { background-color: white; padding-left: 25px; padding-right: 25px; padding-top: 10px; padding-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }

    .two-columns {
        display: flex;
        gap: 20px;
    }

    .left-col, .right-col {
        flex: 1;
    }

</style>
</head>
<body>
<div class="header">
  <h1 style="display: flex; justify-content: space-between; align-items: center;">
    <span>
      🧑‍🎓 <?php echo htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']); ?>
      <span style="font-size: 1.2rem; font-weight: normal; color: #6c757d;">
        (Current Grade:
        <?php
            // Display Current Grade in Header
            echo isset($student['CurrentGrade'])
                ? htmlspecialchars($student['CurrentGrade'])
                : 'N/A';
        ?>)
      </span>
    </span>

    <a href="student_form.php?id=<?php echo $studentID; ?>" style="font-size: 2rem; color: #007bff; text-decoration: none;">
      <i class="fas fa-edit"></i>
    </a>
  </h1>
</div>


  <div class="profile-grid">
    <div class="two-columns">
        <!-- LEFT SIDE -->
        <div class="left-col">
            <div class="panel">
                <h2>General Information & SPED Needs</h2>
                <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
                <div class="data-row"><strong>Student ID:</strong> <?php echo htmlspecialchars($student['StudentID']); ?></div>
                <div class="data-row"><strong>Grade/Section:</strong> <?php echo htmlspecialchars($student['GradeLevel'] . ' - ' . $student['Section']); ?></div>
                <div class="data-row"><strong>Primary Disability:</strong> 
                    <span style="font-weight: bold; color: #007bff;">
                    <?php echo htmlspecialchars($student['Disability'] ?? 'N/A'); ?></span>
                </div>
                <div class="data-row"><strong>Case Manager:</strong> <?php echo htmlspecialchars($student['TeacherFirstName'] . ' ' . $student['TeacherLastName'] ?? 'N/A'); ?></div>

                <h3 style="font-size: 1.1rem; color: #343a40; margin-top: 20px;">Teacher Notes</h3>
                <p style="white-space: pre-wrap; font-size: 0.9rem; color: #6c757d;"><?php echo htmlspecialchars($student['Notes'] ?? 'No notes recorded.'); ?></p>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="right-col">
            <div class="panel">
                <h2>AI Teaching Recommendations</h2>
                <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
                <p style="color: #6c757d; margin-bottom: 10px;">
                    Strategies tailored to the student's disability and risk status.
                </p>

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
                        No recommendations yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div> 

    <div>
        <div class="panel" style="height: auto;">
            <h2>Latest AI Risk Status</h2>
            <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
            <div class="data-row">
                <strong>Risk Level:</strong>
                <?php echo getRiskBadge($student['RiskLevel'] ?? 'Low', true); ?>
            </div>
            <div class="data-row">
                <strong>Prediction:</strong>
                <span style="font-size: 1rem;"><?php echo htmlspecialchars($student['PredictedIssue'] ?? 'Normal'); ?></span>
            </div>
            <div class="data-row">
                <strong>Confidence:</strong>
            </div>
        </div>


        <div class="panel" style="height: auto;">
            <h2>Academic History</h2>
            <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
            <p style="color: #6c757d; margin-bottom: 10px;">Current Term vs. Historical Trends.</p>
            <table class="data-table">
                <thead>
                    <tr><th style="border-top-left-radius: 8px ;">Term</th><th>GPA/Score</th><th>Attendance Rate</th><th style="border-top-right-radius: 8px;">Behavior Score</th></tr>
                </thead>
                <tbody>
                    <?php if (isset($student['CurrentGrade'])): ?>
                    <tr class="current-term-row">
                        <td><?php echo htmlspecialchars($student['CurrentTerm']); ?> <span style="font-size:0.7em; background:#333; color:#fff; padding:2px 4px; border-radius:3px;">LIVE</span></td>
                        <td><?php echo htmlspecialchars($student['CurrentGrade']); ?></td>
                        <td><?php echo round($currentAttendanceRate, 1) . '%'; ?></td>
                        <td><small>See Logs</small></td>
                    </tr>
                    <?php endif; ?>


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
                        <?php if (!isset($student['CurrentGrade'])): ?>
                            <tr><td colspan="4">No academic data available.</td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
       
<div class="panel" style="height: auto;">
            <h2>Recent Behavior & Attendance Log</h2>
            <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="border-top-left-radius: 8px;">Date</th>
                        <th>Type</th>
                        <th style="border-top-right-radius: 8px;">Detail / Behavior Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($behavior_logs): ?>
                        <?php foreach ($behavior_logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($log['Date'])); ?></td>
                               
                                <td>
                                    <span style="color: <?php
                                        echo ($log['Status'] == 'Absent' || $log['Status'] == 'Late') ? '#dc3545' :
                                             ($log['Status'] == 'Observation' ? '#007bff' : '#28a745');
                                    ?>; font-weight: 600;">
                                        <?php echo htmlspecialchars($log['Status']); ?>
                                    </span>
                                </td>


                                <td>
                                    <span style="background-color: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; color: #495057;">
                                        <?php echo htmlspecialchars($log['Detail']); ?>
                                    </span>
                                </td>
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

