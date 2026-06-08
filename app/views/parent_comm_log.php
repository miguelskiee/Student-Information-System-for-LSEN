<?php
// pages/parent_comm_log.php
include '../models/db.php'; 

// Fetch communication logs for the latest 20 entries
$logs = $conn->query("
    SELECT 
        s.FirstName, s.LastName, p.FullName AS ParentName,
        l.Message, l.MessageType, l.SentByAI, l.SentAt, l.Status
    FROM ParentContactLogs l
    JOIN Students s ON l.StudentID = s.StudentID
    LEFT JOIN Parents p ON l.ParentID = p.ParentID
    ORDER BY l.SentAt DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Parent Communication Log</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .ai-flag { background-color: #e6f7ff; color: #007bff; padding: 3px 6px; border-radius: 4px; font-size: 0.8rem; }
    .type-concern { color: #dc3545; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header"><h1>✉️ Parent Communication Manager</h1></div>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student</th>
          <th>Parent</th>
          <th>Type</th>
          <th>Message Snippet</th>
          <th>Source</th>
          <th>Sent At</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
          <tr>
            <td><?php echo htmlspecialchars($log['FirstName'] . ' ' . $log['LastName']); ?></td>
            <td><?php echo htmlspecialchars($log['ParentName'] ?? 'N/A'); ?></td>
            <td class="<?php echo ($log['MessageType'] == 'Concern') ? 'type-concern' : ''; ?>"><?php echo htmlspecialchars($log['MessageType']); ?></td>
            <td><?php echo htmlspecialchars(substr($log['Message'], 0, 50)) . '...'; ?></td>
            <td><?php echo $log['SentByAI'] ? '<span class="ai-flag">🤖 AI Draft</span>' : 'Teacher'; ?></td>
            <td><?php echo date('M d, H:i', strtotime($log['SentAt'])); ?></td>
            <td><?php echo htmlspecialchars($log['Status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
