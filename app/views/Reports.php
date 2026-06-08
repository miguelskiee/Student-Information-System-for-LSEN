<?php
// pages/Reports.php
include '../models/db.php'; 

// Fetch total AI alerts grouped by risk level (Placeholder data)
$riskStats = $conn->query("
    SELECT RiskLevel, COUNT(*) AS Total
    FROM AI_PerformanceAlerts
    GROUP BY RiskLevel
")->fetchAll(PDO::FETCH_ASSOC);

$schoolAlertCount = $conn->query("SELECT COUNT(*) FROM AI_SchoolWideAlerts")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI & Report Tools</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    h2 { color: #343a40; margin-top: 20px; border-left: 5px solid #ffc107; padding-left: 10px; margin-bottom: 15px; }
    .card-container { display: flex; gap: 20px; margin-bottom: 30px; }
    .card { background-color: white; border-radius: 8px; padding: 20px; flex: 1; min-width: 200px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .card h3 { font-size: 1rem; color: #6c757d; margin-bottom: 5px; }
    .card p { font-size: 2.0rem; font-weight: bold; color: #343a40; }
    .report-section { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
  </style>
</head>
<body>
  <div class="header"><h1>📈 AI & Report Tools</h1></div>

  <div class="card-container">
    <div class="card">
      <div style="font-size: 1.8rem; color: #dc3545; margin-bottom: 10px;">🚨</div>
      <h3>High Risk Students</h3>
      <p><?php echo $riskStats[0]['Total'] ?? 0; ?> (Placeholder)</p>
    </div>
    <div class="card">
      <div style="font-size: 1.8rem; color: #007bff; margin-bottom: 10px;">🏫</div>
      <h3>School-Wide Alerts</h3>
      <p><?php echo $schoolAlertCount; ?></p>
    </div>
    <div class="card">
      <div style="font-size: 1.8rem; color: #28a745; margin-bottom: 10px;">💬</div>
      <h3>Parent Comms Log</h3>
      <p>Analyze</p>
    </div>
  </div>

  <div class="report-section">
    <h2>🤖 AI Performance Analytics</h2>
    <p>Visual representation of student risk distribution and feature importance.</p>
    
    <div style="height: 250px; border: 1px solid #ccc; background: #fafafa; padding: 20px; text-align: center;">
        [Placeholder for Chart: Risk Distribution (High, Medium, Low)]
    </div>

    <h3 style="margin-top: 20px; color: #007bff;">Top Risk Factors (Feature Importance)</h3>
    <div style="height: 150px; border: 1px solid #ccc; background: #fafafa; padding: 20px; text-align: center;">
        [Placeholder for Bar Chart: Grade Trend, Attendance Rate, Volatility]
    </div>
  </div>
  
  <div class="report-section">
      <h2>📣 AI School-Wide Alerts Log</h2>
      <table class="data-table">
          <thead>
              <tr><th>Issue Detected</th><th>Affected Count</th><th>Date Generated</th><th>Recommendation</th></tr>
          </thead>
          <tbody>
              <tr>
                  <td>Sudden drop in Grade 9 Science scores</td>
                  <td>15</td>
                  <td>2025-11-15</td>
                  <td>Review curriculum pacing and teacher training needs.</td>
              </tr>
          </tbody>
      </table>
  </div>
</body>
</html>