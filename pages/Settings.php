<?php
// pages/Settings.php
include '../model/db.php'; 
// Placeholder for fetching latest job status
$jobStatus = $conn->query("
    SELECT JobName, Status, CompletedAt 
    FROM SystemJobs 
    ORDER BY CompletedAt DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>System Settings</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .settings-group { background-color: white; padding-left: 25px; padding-right: 25px; padding-top: 10px; padding-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
    h2 { color: #343a40; margin-bottom: 15px; border-bottom: 1px dotted #ccc; padding-bottom: 5px; }
    .setting-item { margin-bottom: 10px; font-size: 1rem; }
    .setting-item strong { color: #007bff; }
    
    form label { display: block; margin-top: 15px; font-weight: 600; color: #6c757d; }
    form input[type="number"] { padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100px; margin-top: 5px; }
    
    .action-btn, .save-btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px; margin-right: 10px; }
    .action-btn { background-color: #007bff; }
    .action-btn:last-child { background-color: #dc3545; }
  </style>
</head>
<body>
  <div class="header"><h1><i class="fa-solid fa-gear" style="font-size: 32px; margin-left: 10px; margin-right: 5px; color: #2f2178ff;"></i>
System Settings</h1></div>

  <div class="settings-group">
    <h2>Machine Learning Model Management</h2>
    <div class="setting-item">
        <strong>Last Job:</strong> 
        <?php echo $jobStatus ? htmlspecialchars($jobStatus['JobName']) : 'Inference'; ?>
    </div>
    <div class="setting-item">
        <strong>Status:</strong> 
        <?php echo $jobStatus ? htmlspecialchars($jobStatus['Status']) : 'Completed'; ?>
    </div>
    <div class="setting-item">
        <strong>Completed At:</strong> 
        <?php echo $jobStatus ? htmlspecialchars($jobStatus['CompletedAt']) : 'N/A'; ?>
    </div>
    <button class="action-btn">Run ML Inference Now</button>
    <button class="action-btn">Retrain Model</button>
  </div>

  <div class="settings-group">
    <h2>Configuration Thresholds</h2>
    <form>
        <label for="anomaly_threshold">Isolation Forest Anomaly Score Threshold:</label>
        <input type="number" id="anomaly_threshold" value="0.65" step="0.01">
        
        <label for="gpa_threshold">At-Risk GPA Threshold (Used for training label):</label>
        <input type="number" id="gpa_threshold" value="75" step="1">
        
        <button type="submit" class="save-btn">Save Thresholds</button>
    </form>
  </div>

  <div class="settings-group">
    <h2>User & Role Management</h2>
    <p>Manage Admin accounts and system access permissions.</p>
    <button class="action-btn" style="background-color: #6c757d;">Go to User List</button>
  </div>
</body>
</html>