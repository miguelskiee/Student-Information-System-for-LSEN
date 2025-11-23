<?php
// pages/Students.php
include '../model/db.php'; // Corrected path to model

$studentQuery = "
    SELECT 
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel, apa.PredictedIssue
    FROM Students s
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID
    WHERE apa.AlertID = (
        SELECT MAX(AlertID) 
        FROM AI_PerformanceAlerts 
        WHERE StudentID = s.StudentID
    ) OR apa.AlertID IS NULL
    ORDER BY s.LastName ASC";

$students = $conn->query($studentQuery)->fetchAll(PDO::FETCH_ASSOC);

function getRiskBadge($risk) {
// ... (function remains the same) ...
    switch ($risk) {
        case 'High': return '<span style="color: white; background-color: #dc3545; padding: 4px 8px; border-radius: 4px; font-weight: bold;">HIGH 🚨</span>';
        case 'Medium': return '<span style="color: #333; background-color: #ffc107; padding: 4px 8px; border-radius: 4px; font-weight: bold;">MEDIUM ⚠️</span>';
        default: return '<span style="color: white; background-color: #28a745; padding: 4px 8px; border-radius: 4px;">Low / Normal</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Directory & Status</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
    .controls { margin-bottom: 20px; }
    .add-btn { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
  </style>
</head>
<body>
  <div class="header"><h1>🧑‍🎓 Student Directory & AI Status</h1></div>
  
  <div class="controls">
      <a href="student_form.php" class="add-btn">➕ Add New Student</a>
  </div>
  
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Grade/Section</th>
          <th>Disability</th>
          <th>AI Risk Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']); ?></td>
            <td><?php echo htmlspecialchars($s['GradeLevel'] . ' - ' . $s['Section']); ?></td>
            <td><?php echo htmlspecialchars($s['Disability']); ?></td>
            <td><?php echo getRiskBadge($s['RiskLevel']); ?></td>
            <td>
                <a href="student_profile.php?id=<?php echo $s['StudentID']; ?>" style="color: #007bff;">View</a> | 
                <a href="student_form.php?id=<?php echo $s['StudentID']; ?>" style="color: #28a745;">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>