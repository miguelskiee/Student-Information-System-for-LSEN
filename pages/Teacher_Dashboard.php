<?php
// pages/Teacher_Dashboard.php
session_start();
include '../model/db.php'; 

// 1. SECURITY & USER CHECK
if (!isset($_SESSION['user_id'])) { die("Access Denied. Please log in."); }
$teacherID = $_SESSION['user_id'];

// Get User Name for Welcome Message
$user = $conn->query("SELECT FirstName FROM Teachers WHERE TeacherID = $teacherID")->fetch(PDO::FETCH_ASSOC);

// ============================================
// AI MODEL EXECUTION LOGIC 
// ============================================
$python_script_path = '../model/model.py'; 
$min_run_interval_seconds = 60; 

try {
    $stmt = $conn->prepare("
        SELECT UNIX_TIMESTAMP(CompletedAt) AS LastRunTime 
        FROM SystemJobs 
        WHERE JobName = 'ML_Inference' AND Status = 'Completed' 
        ORDER BY CompletedAt DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $last_run_time = $stmt->fetchColumn();
    $last_run_time = $last_run_time ?: 0; 
    $current_time = time();
    $time_since_last_run = $current_time - $last_run_time;

    if ($time_since_last_run >= $min_run_interval_seconds) {
        $command = "python3 " . escapeshellarg($python_script_path) . " > /dev/null 2>&1 &";
        exec($command);
    } 
} catch (PDOException $e) {
    error_log("ML SystemJobs check failed: " . $e->getMessage());
}

// ============================================
// DASHBOARD DATA FETCHING
// ============================================

// 2. FETCH COUNTS (Linked via Section)
$studentCount = $conn->query("
    SELECT COUNT(DISTINCT s.StudentID) FROM Students s
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    WHERE ta.TeacherID = $teacherID
")->fetchColumn();

$subjectCount = $conn->query("SELECT COUNT(*) FROM TeacherAssignments WHERE TeacherID = $teacherID")->fetchColumn();

$alertsCount = $conn->query("
    SELECT COUNT(DISTINCT apa.StudentID) FROM AI_PerformanceAlerts apa
    JOIN Students s ON apa.StudentID = s.StudentID
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    WHERE ta.TeacherID = $teacherID AND apa.RiskLevel = 'High'
")->fetchColumn();

// 3. FETCH GRADING QUEUE
$queueQuery = "
    SELECT s.SubmissionID, ass.Title, stud.FirstName, stud.LastName, aigr.SuggestedScore, aigr.ConfidenceLevel
    FROM StudentSubmissions s
    JOIN Assignments ass ON s.AssignmentID = ass.AssignmentID
    JOIN AI_GradingResults aigr ON s.SubmissionID = aigr.SubmissionID
    JOIN Students stud ON s.StudentID = stud.StudentID
    WHERE ass.TeacherID = $teacherID AND aigr.ConfidenceLevel < 0.80 
    LIMIT 3";
$gradingQueue = $conn->query($queueQuery)->fetchAll(PDO::FETCH_ASSOC);

// 4. FETCH STUDENT ALERTS (Linked via Section)
$alertsQuery = "
    SELECT s.StudentID, s.FirstName, s.LastName, a.RiskLevel, a.PredictedIssue
    FROM AI_PerformanceAlerts a
    JOIN Students s ON a.StudentID = s.StudentID
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    WHERE ta.TeacherID = $teacherID AND a.RiskLevel IN ('High', 'Medium')
    GROUP BY s.StudentID LIMIT 5";
$studentAlerts = $conn->query($alertsQuery)->fetchAll(PDO::FETCH_ASSOC);

// 5. FETCH TEACHING RECOMMENDATIONS (MISSING PART FIXED HERE)
// Updated to link via TeacherAssignments (Section) instead of Gradebook
$recsQuery = "
    SELECT 
        s.StudentID, s.FirstName, s.LastName, atr.RecommendedStrategy, s.Disability 
    FROM AI_TeachingRecommendations atr
    JOIN Students s ON atr.StudentID = s.StudentID
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    WHERE ta.TeacherID = $teacherID
    LIMIT 5";
$teachingRecs = $conn->query($recsQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Dashboard</title>
  <style>
    /* Teacher Dashboard Styling */
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .dashboard-welcome h1 { font-size: 1.8rem; color: #28a745; }
    .dashboard-title h1 { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; }
    .dashboard-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
    .card { background-color: white; border-radius: 8px; padding: 20px; flex: 1; min-width: 180px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .card-icon { font-size: 2rem; margin-bottom: 10px; color: #007bff; }
    .card p { font-size: 2.0rem; font-weight: bold; color: #343a40; }

    /* Action Panels (AI driven) */
    .action-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .panel { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
    .panel h2 { color: #dc3545; border-bottom: 2px solid #ffc107; padding-bottom: 5px; margin-bottom: 15px; font-size: 1.2rem; }

    /* Table Styling */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; }
    .bg-high { background-color: #dc3545; } 
    .bg-medium { background-color: #ffc107; color: #333; } 
  </style>
</head>
<body>
  <div class="dashboard-welcome">
    <h1>Welcome back, <?php echo htmlspecialchars($user['FirstName'] ?? 'Teacher'); ?>!</h1>
    <h3>Focus on students needing immediate intervention.</h3>
  </div>
  
  <div class="dashboard-title">
    <h1>My Overview</h1>
  </div>

  <div class="dashboard-container">
    <div class="card">
      <div class="card-icon">👥</div>
      <h3>My Students</h3>
      <p><?php echo $studentCount; ?></p>
    </div>
    <div class="card">
      <div class="card-icon">📚</div>
      <h3>My Subjects</h3>
      <p><?php echo $subjectCount; ?></p>
    </div>
    <div class="card" style="background-color: #f8d7da; border-left: 5px solid #dc3545;">
      <div class="card-icon" style="color: #dc3545;">🚨</div>
      <h3>High Risk Alerts</h3>
      <p><?php echo $alertsCount; ?></p>
    </div>
  </div>

  <div class="action-grid">
    <!-- Left Panel: Alerts and Recommendations -->
    <div>
      <div class="panel">
        <h2 style="color: #007bff; border-bottom-color: #007bff;">🔔 Urgent Student Alerts</h2>
        <table class="data-table">
            <thead>
                <tr><th>Student</th><th>Risk</th><th>Prediction</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if ($studentAlerts): ?>
                    <?php foreach ($studentAlerts as $alert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alert['FirstName'] . ' ' . $alert['LastName']); ?></td>
                        <td><span class="badge <?php echo ($alert['RiskLevel'] == 'High') ? 'bg-high' : 'bg-medium'; ?>"><?php echo htmlspecialchars($alert['RiskLevel']); ?></span></td>
                        <td><?php echo htmlspecialchars($alert['PredictedIssue']); ?></td>
                        <td><a href="student_profile.php?id=<?php echo $alert['StudentID']; ?>" style="color: #007bff;">View Recs</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No high-priority student alerts.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
      </div>

<div class="panel">
    <h2 style="color: #28a745; border-bottom-color: #28a745;">💡 Smart Lesson Recommendations</h2>
    <p style="color: #6c757d; margin-bottom: 15px;">Suggested strategies for your highest-risk students.</p>
    
    <?php if ($teachingRecs): ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($teachingRecs as $rec): ?>
                <div style="background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 12px; border-radius: 4px;">
                    <div style="font-weight: bold; color: #343a40; margin-bottom: 5px; font-size: 1.05rem;">
                        <?php echo htmlspecialchars($rec['FirstName'] . ' ' . $rec['LastName']); ?>
                        <span style="font-size: 0.85rem; color: #6c757d; font-weight: normal;">
                            (<?php echo htmlspecialchars($rec['Disability'] ?: 'General'); ?>)
                        </span>
                    </div>
                    
                    <div style="font-size: 0.95rem; color: #495057; padding-left: 5px; line-height: 1.5;">
                        <?php 
                        // nl2br converts the Python \n to HTML <br> tags so bullets stack vertically
                        echo nl2br(htmlspecialchars($rec['RecommendedStrategy'])); 
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #6c757d; font-style: italic;">No recent AI recommendations for your students.</p>
    <?php endif; ?>
</div>

</div>
</body>
</html>