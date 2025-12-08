<?php
// pages/dashboard.php

// 1. Error Reporting (Useful for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Database Connection
include '../model/db.php'; 

// ============================================
// AI MODEL EXECUTION LOGIC
// ============================================

$python_script_path = '../model/model.py'; 
$min_run_interval_seconds = 60; 

try {
    // Check for the most recent successful inference job
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

    // Trigger Python script if enough time has passed
    if ($time_since_last_run >= $min_run_interval_seconds) {
        // Run asynchronously
        $command = "python3 " . escapeshellarg($python_script_path) . " > /dev/null 2>&1 &";
        exec($command);
        error_log("ML script triggered (Time since last run: {$time_since_last_run}s)");
    } 

} catch (PDOException $e) {
    error_log("ML SystemJobs check failed: " . $e->getMessage());
}

// ============================================
// DASHBOARD DATA FETCH
// ============================================

// 1. Fetch Basic Counts
$studentCount = $conn->query("SELECT COUNT(*) FROM Students")->fetchColumn();
$teacherCount = $conn->query("SELECT COUNT(*) FROM Teachers")->fetchColumn();
$subjectCount = $conn->query("SELECT COUNT(*) FROM Subjects")->fetchColumn();
$termCount = 4; 

// 2. Fetch AI Alerts (Updated for detailed tags)
$alertQuery = "
    SELECT 
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        a.RiskLevel, a.PredictedIssue, a.RiskProbability, a.DateGenerated
    FROM AI_PerformanceAlerts a
    JOIN Students s ON a.StudentID = s.StudentID
    WHERE a.RiskLevel IN ('High', 'Medium') OR a.PredictedIssue LIKE '%Anomaly%'
    ORDER BY a.DateGenerated DESC
    LIMIT 10"; // Increased limit to see more recent alerts
$alerts = $conn->query($alertQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="../styles/dashboard.css">
</head>
<body>

  <div class="dashboard-header">
    <img src="../assets/school_pics/sagadb.jpg" class="header-picture" alt="Dashboard Icon" >
  </div>

  <div class="dashboard-welcome">
    <h1 style="font-size: 2.15rem; margin-bottom: -8px;">Sagad High School</h1>
    <h3 style="font-size: 1.5rem;">1EE Angeles Ext, Pasig, 1600 Metro Manila</h3>
  </div>

  <div class="dashboard-container-icon">
    <div class="card">
      <div class="card-icon">📘</div>
      <h3>Academic Terms</h3>
      <p><?php echo $termCount; ?></p>
    </div>

    <div class="card">
      <div class="card-icon">👩‍🏫</div>
      <h3>Teachers</h3>
      <p><?php echo $teacherCount; ?></p>
    </div>

    <div class="card">
      <div class="card-icon">👨‍🎓</div>
      <h3>LSEN Students</h3>
      <p><?php echo $studentCount; ?></p>
    </div>

    <div class="card">
      <div class="card-icon">📖</div>
      <h3>Subjects</h3>
      <p><?php echo $subjectCount; ?></p>
    </div>
  </div>

  <div class="dashboard-container" style="display: block;">
    <div class="alert-section">
      <h3 style="font-size: 1.25rem;margin-left: 15px; margin-top: 28px; color: #212121ff; margin-bottom: 5px; font-weight: 600;">At-Risk Students (AI Analysis)</h3>
      <p style="margin-left: 15px; margin-bottom: 15px; color: #666; font-size: 0.9rem;">Students flagged by AI based on Grades, Attendance, and Behavioral patterns.</p>
      
      <table class="alert-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Risk Level</th>
            <th>Detected Issues</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($alerts) > 0): ?>
            <?php foreach ($alerts as $row): ?>
              <?php
                // 1. Determine Risk Badge Color
                $riskBadge = ($row['RiskLevel'] == 'High') ? 'badge-danger' : 'badge-warning';

                // 2. Process Predicted Issues (String Manipulation)
                // Remove prefix if exists
                $cleanIssues = str_replace("At Risk: ", "", $row['PredictedIssue']);
                // Convert string to array
                $issuesArray = explode(", ", $cleanIssues);
              ?>
              <tr>
                <td>
                    <strong><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></strong>
                    <br>
                    <span class="disability-tag"><?php echo htmlspecialchars($row['Disability']); ?></span>
                    <small style="color: #888;"><?php echo htmlspecialchars($row['GradeLevel'] . '-' . $row['Section']); ?></small>
                </td>
                <td>
                  <span class="badge <?php echo $riskBadge; ?>">
                    <?php echo htmlspecialchars($row['RiskLevel']); ?>
                  </span>
                </td>
                <td>
                    <?php foreach($issuesArray as $issue): ?>
                        <?php 
                            $issue = trim($issue);
                            $tagClass = 'badge-secondary'; // Default
                            if($issue == 'Grades') $tagClass = 'badge-info';
                            if($issue == 'Attendance') $tagClass = 'badge-att';
                            if($issue == 'Behavior') $tagClass = 'badge-primary';
                            if(strpos($issue, 'Anomaly') !== false) $tagClass = 'badge-dark';
                        ?>
                        <span class="badge <?php echo $tagClass; ?>">
                            <?php echo htmlspecialchars($issue); ?>
                        </span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <a href="student_profile.php?id=<?php echo $row['StudentID']; ?>" class="btn-view">
                       View Strategies
                    </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 20px; color: #28a745;">
                <strong>No immediate risks detected.</strong> The system is monitoring student performance.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>