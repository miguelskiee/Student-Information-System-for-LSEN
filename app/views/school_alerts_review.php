<?php
// pages/school_alerts_review.php
include '../models/db.php'; 

// Fetch all school-wide alerts
$alerts = $conn->query("
    SELECT * FROM AI_SchoolWideAlerts
    ORDER BY DateGenerated DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>School-Wide Alert Review</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 25px; }
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
    .data-table th { background-color: #f8d7da; color: #721c24; }
    .status-badge { padding: 5px 10px; border-radius: 15px; background-color: #007bff; color: white; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header"><h1>🚨 School-Wide Alert Review</h1></div>
  <p style="margin-bottom: 20px; color: #6c757d;">Aggregated findings from the AI model on cohort performance trends.</p>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Issue Detected</th>
          <th>Affected Count</th>
          <th>Model Recommendation</th>
          <th>Date Generated</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($alerts): ?>
        <?php foreach ($alerts as $a): ?>
          <tr>
            <td><?php echo htmlspecialchars($a['IssueDetected']); ?></td>
            <td><?php echo htmlspecialchars($a['AffectedCount']); ?> students</td>
            <td><?php echo htmlspecialchars($a['Recommendation']); ?></td>
            <td><?php echo date('M d, Y', strtotime($a['DateGenerated'])); ?></td>
            <td><span class="status-badge">New</span></td>
            <td><a href="#">View Students</a> | <a href="#">Acknowledge</a></td>
          </tr>
        <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="6" style="text-align: center;">No school-wide alerts found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
