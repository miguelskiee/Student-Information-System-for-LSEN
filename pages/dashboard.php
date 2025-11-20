<?php
// pages/dashboard.php
// Add these 3 lines to see errors!
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assuming the correct path is '../utils/db.php' based on previous structure
include '../model/db.php'; 

// 1. FETCH COUNTS
$studentCount = $conn->query("SELECT COUNT(*) FROM Students")->fetchColumn();
$teacherCount = $conn->query("SELECT COUNT(*) FROM Teachers")->fetchColumn();
$subjectCount = $conn->query("SELECT COUNT(*) FROM Subjects")->fetchColumn();
$termCount = 4;

// 2. FETCH AI ALERTS (The "Missing" Feature)
// Query is UPDATED to fetch StudentID and Disability for actionable insight
$alertQuery = "
    SELECT 
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        a.RiskLevel, a.PredictedIssue, a.AnomalyScore, a.DateGenerated
    FROM AI_PerformanceAlerts a
    JOIN Students s ON a.StudentID = s.StudentID
    WHERE a.RiskLevel IN ('High', 'Medium') OR a.PredictedIssue LIKE '%Anomaly%'
    ORDER BY a.DateGenerated DESC
    LIMIT 5";
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
    /* Styling is kept inline for this response but should ideally move to dashboard.css */
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .dashboard-header, .dashboard-title { padding: 0 0 20px 0; }
    .dashboard-welcome h1 { font-size: 1.8rem; color: #007bff; }
    
    .dashboard-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
    .card { background-color: white; border-radius: 8px; padding: 20px; flex: 1; min-width: 200px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .card-icon { font-size: 2rem; margin-bottom: 10px; color: #28a745; }
    .card p { font-size: 2.2rem; font-weight: bold; color: #343a40; }

    /* Alert Section */
    .alert-section { margin-top: 30px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .alert-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .alert-table th, .alert-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .alert-table th { background-color: #f8f9fa; color: #333; }
    
    /* Status Badges */
    .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.85rem; font-weight: bold; color: white; display: inline-block; }
    .bg-high { background-color: #dc3545; } /* Red for High Risk */
    .bg-medium { background-color: #ffc107; color: #333; } /* Yellow for Medium */
    .disability-tag { font-size: 0.8rem; color: #007bff; background: #e6f0ff; padding: 3px 6px; border-radius: 4px; margin-left: 5px; }
  </style>
</head>
<body>
  <div class="dashboard-header">
    <img src="../assets/school_pics/sagadb.jpg" alt="Dashboard Icon" class="dashboard-icon">
  </div>

  <div class="dashboard-welcome">
    <h1>Sagad High School</h1>
    <h3>1EE Angeles Ext, Pasig, 1600 Metro Manila</h3>
  </div>
  
  <div class="dashboard-title">
    <h1>Dashboard Overview</h1>
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
      <h2 style="color: #dc3545;">⚠️ Early Intervention Alerts (AI Detected)</h2>
      <p>Students flagged by Random Forest & Isolation Forest models requiring immediate attention.</p>
      
      <table class="alert-table">
        <thead>
          <tr>
            <th>Student Name & Disability</th>
            <th>Grade/Section</th>
            <th>Risk Level</th>
            <th>AI Prediction / Issue</th>
            <th>Date Detected</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($alerts) > 0): ?>
            <?php foreach ($alerts as $row): ?>
              <tr>
                <td>
                    <?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?>
                    <span class="disability-tag"><?php echo htmlspecialchars($row['Disability']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($row['GradeLevel'] . ' - ' . $row['Section']); ?></td>
                <td>
                  <span class="badge <?php echo ($row['RiskLevel'] == 'High') ? 'bg-high' : 'bg-medium'; ?>">
                    <?php echo htmlspecialchars($row['RiskLevel']); ?>
                  </span>
                </td>
                <td>
                    <?php echo htmlspecialchars($row['PredictedIssue']); ?>
                    <?php if($row['AnomalyScore'] > 0.6) echo " <small>(Anomaly Score: ".round($row['AnomalyScore'], 2).")</small>"; ?>
                </td>
                <td><?php echo date('M d, Y', strtotime($row['DateGenerated'])); ?></td>
                <td>
                    <a href="student_profile.php?id=<?php echo $row['StudentID']; ?>" 
                       style="color: #007bff; text-decoration: underline; font-weight: 600;">
                        Review Profile
                    </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center;">No alerts detected at this time. Good job!</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>