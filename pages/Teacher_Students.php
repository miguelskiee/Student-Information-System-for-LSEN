<?php
// pages/Teacher_Students.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID

// Fetch students assigned to this teacher (by having a record with their ID)
$studentQuery = $conn->prepare("
    SELECT DISTINCT
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel
    FROM Students s
    JOIN AcademicRecords ar ON s.StudentID = ar.StudentID
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID AND apa.AlertID = (
        SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID
    )
    WHERE ar.TeacherID = ?
    ORDER BY apa.RiskLevel DESC, s.LastName ASC
");
$studentQuery->execute([$teacherID]);
$students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);

function getRiskBadge($risk) {
    switch ($risk) {
        case 'High': return '<span style="color: white; background-color: #dc3545; padding: 4px 8px; border-radius: 4px; font-weight: bold;">HIGH 🚨</span>';
        case 'Medium': return '<span style="color: #333; background-color: #ffc107; padding: 4px 8px; border-radius: 4px; font-weight: bold;">MEDIUM ⚠️</span>';
        default: return '<span style="color: white; background-color: #28a745; padding: 4px 8px; border-radius: 4px;">Normal</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Students</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
  </style>
</head>
<body>
  <div class="header"><h1>🧑‍🎓 My Students (for intervention)</h1></div>
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
            <td><a href="student_profile.php?id=<?php echo $s['StudentID']; ?>" style="color: #007bff;">View Profile & Recs</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>