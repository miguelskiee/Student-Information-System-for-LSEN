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
  <style>
    /* Basic Layout */
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: sans-serif; }
    .dashboard-header, .dashboard-title { padding: 0 0 20px 0; }
    .dashboard-welcome h1 { font-size: 1.8rem; color: #007bff; margin-bottom: 5px; }
    
    .dashboard-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
    .card { background-color: white; border-radius: 8px; padding: 20px; flex: 1; min-width: 200px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .card-icon { font-size: 2rem; margin-bottom: 10px; color: #28a745; }
    .card p { font-size: 2.2rem; font-weight: bold; color: #343a40; margin: 0; }

    /* Alert Section */
    .alert-section { margin-top: 30px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .alert-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .alert-table th, .alert-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .alert-table th { background-color: #f8f9fa; color: #555; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
    
    /* Risk Badges */
    .badge { padding: 5px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; color: white; display: inline-block; margin-right: 4px; }
    .badge-danger { background-color: #e74a3b; }   /* High Risk */
    .badge-warning { background-color: #f6c23e; color: #333; } /* Medium Risk */
    
    /* Issue Tags */
    .badge-info { background-color: #36b9cc; }     /* Grades */
    .badge-att { background-color: #f6c23e; color: #333; } /* Attendance */
    .badge-primary { background-color: #4e73df; }  /* Behavior */
    .badge-dark { background-color: #5a5c69; }     /* Anomaly */
    .badge-secondary { background-color: #858796; } /* Generic */

    .disability-tag { font-size: 0.75rem; color: #4e73df; background: #eaecf4; padding: 2px 6px; border-radius: 4px; display: block; width: fit-content; margin-top: 4px; }
    
    .btn-view { background-color: #4e73df; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 0.85rem; }
    .btn-view:hover { background-color: #2e59d9; }
  </style>
</head>
<body>

  <div class="dashboard-header">
    <img src="../assets/school_pics/sagadb.jpg" alt="Dashboard Icon" style="height: 50px; border-radius: 5px;">
  </div>

  <div class="dashboard-welcome">
    <h1>Sagad High School</h1>
    <h3 style="color: #666; font-weight: normal;">1EE Angeles Ext, Pasig, 1600 Metro Manila</h3>
  </div>
  
  <div class="dashboard-title">
    <h2>Dashboard Overview</h2>
  </div>

  <div class="dashboard-container">
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
      <h3 style="color: #e74a3b; margin-bottom: 5px;">⚠️ At-Risk Students (AI Analysis)</h3>
      <p style="color: #666; font-size: 0.9rem;">Students flagged by AI based on Grades, Attendance, and Behavioral patterns.</p>
      
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