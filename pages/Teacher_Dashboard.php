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
    $stmt = $conn->prepare("SELECT UNIX_TIMESTAMP(CompletedAt) AS LastRunTime 
                            FROM SystemJobs 
                            WHERE JobName = 'ML_Inference' AND Status = 'Completed' 
                            ORDER BY CompletedAt DESC 
                            LIMIT 1");
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
$studentCount = $conn->query("SELECT COUNT(DISTINCT s.StudentID) FROM Students s
                                JOIN TeacherAssignments ta ON s.Section = ta.Section
                                WHERE ta.TeacherID = $teacherID")->fetchColumn();

$subjectCount = $conn->query("SELECT COUNT(*) FROM TeacherAssignments WHERE TeacherID = $teacherID")->fetchColumn();

$alertsCount = $conn->query("SELECT COUNT(DISTINCT apa.StudentID) FROM AI_PerformanceAlerts apa
                              JOIN Students s ON apa.StudentID = s.StudentID
                              JOIN TeacherAssignments ta ON s.Section = ta.Section
                              WHERE ta.TeacherID = $teacherID AND apa.RiskLevel = 'High'")->fetchColumn();

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
    * {
        box-sizing: border-box;
    }
    body { 
        background-color: #f4f7f9; 
        color: #333; 
        padding: 20px; 
        font-family: Arial, sans-serif;
        height: 100%;
        margin: 0;
    }

    .card {
    flex: 1 1 200px;
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: 0.3s;
    }

    .card:hover {
    transform: scale(1.05);
    }

    .card-icon {
    font-size: 40px;
    color: #004080;
    margin-bottom: 10px;
    }

    .card h3 {
    margin: 10px 0 5px;
    }

    .card p {
    font-size: 20px;
    font-weight: bold;
    }

    .dashboard-welcome h1 { 
        font-size: 1.8rem; 
        color: #286aa7ff; 
        margin-bottom: 10px;
    }
    .dashboard-title h1 { 
        border-bottom: 2px solid #dee2e6; 
        padding: 10px 20px; 
        margin-bottom: 20px; 
        font-size: 1.6rem;
    }
    .dashboard-container { 
        display: flex; 
        gap: 20px; 
        flex-wrap: wrap; 
        margin-bottom: 30px; 
        padding: 0 20px;
    }

    /* Action Panels (AI driven) */
    .action-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 0 20px;
    }

    .panel {
        flex: 1;
        min-width: 300px; /* Prevent panels from getting too narrow */
        width: 100%;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .panel h2 { color: #343a40; border-left: 5px solid #284ca7ff; padding-left: 20px; margin-bottom: 15px; font-size: 1.4rem; }

    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed;}
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
    .data-table th { background-color: #004080; color: #ffffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}

.badge { 
    padding: 10px 15px; 
    border-radius: 12px; 
    font-size: 0.85rem; 
    font-weight: bold; 
    color: white; 
    display: inline-block; /* Make the badge an inline block element */
    width: 80px; /* Fixed width for both High and Medium badges */
    text-align: center; /* Center the text inside the badge */
}

    .bg-high { 
        background-color: #dc3545; 
    } 
    .bg-medium { 
        background-color: #ffc107; 
        color: #333; 
    } 

    .action-btn { 
        color: #007bff; 
        font-weight: 600; 
        text-decoration: none; 
    }
    .action-btn:hover { 
        text-decoration: underline; 
    }

    .panel { background-color: white; padding-left: 25px; padding-right: 25px; padding-top: 10px; padding-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }

    .view-btn {
        background-color: #007bff;
        color: white;
        padding: 8px 16px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .view-btn:hover {
        background-color: #0056b3;
        transform: translateY(-1px);
    }

  </style>
</head>
<body>

  <div class="dashboard-welcome">
    <center><h1>Welcome back, <?php echo htmlspecialchars($user['FirstName'] ?? 'Teacher'); ?>!</h1>
    <h3>Focus on students needing immediate intervention.</h3></center>
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
        <h2>Urgent Student Alerts</h2>
        <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">

        <table class="data-table">
            <thead>
                <tr><th style="width: 35%; border-top-left-radius: 8px;">Student</th><th style="width: 15%; padding-left: 38px;">Risk</th><th style="width: 30%;"">Prediction</th><th style="width: 10%; border-top-right-radius: 8px; padding-left: 24px;">Action</th></tr>
            </thead>
            <tbody>
                <?php if ($studentAlerts): ?>
                    <?php foreach ($studentAlerts as $alert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alert['FirstName'] . ' ' . $alert['LastName']); ?></td>
                        <td><span class="badge <?php echo ($alert['RiskLevel'] == 'High') ? 'bg-high' : 'bg-medium'; ?>"><?php echo htmlspecialchars($alert['RiskLevel']); ?></span></td>
                        <td><?php echo htmlspecialchars($alert['PredictedIssue']); ?></td>
                        <td><a href="student_profile.php?id=<?php echo $alert['StudentID']; ?>" class="view-btn">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No high-priority student alerts.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
      </div>

      <div class="panel">
        <h2>Smart Lesson Recommendations</h2>
        <p style="color: #6c757d; margin-bottom: 15px;">Suggested strategies for your highest-risk students.</p>
        <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">
        
        <?php if ($teachingRecs): ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($teachingRecs as $rec): ?>
                    <div style="background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 12px; border-radius: 4px; margin-bottom: 10px;">
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
  </div>

</body>
</html>
