<?php
// pages/Teacher_Students.php
// CORRECTED PATH: pages -> ../ -> model/db.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID (e.g., Anna Soriano)

// --- SORTING LOGIC ---
$allowed_columns = [
    'name' => 's.LastName',
    'grade' => 's.GradeLevel',
    'disability' => 's.Disability',
    'risk' => 'apa.RiskLevel'
];

$sort_by = $_GET['sort'] ?? 'risk'; // Default sort is by risk
$sort_dir = $_GET['dir'] ?? 'DESC';  // Default direction for risk is DESC (highest first)

// Validate inputs to prevent SQL injection
$order_column = $allowed_columns[$sort_by] ?? 'apa.RiskLevel';
$order_direction = (strtoupper($sort_dir) === 'ASC') ? 'ASC' : 'DESC';

// Determine the next sort direction for the clicked column
function get_next_dir($current_column, $requested_column, $current_dir) {
    if ($current_column === $requested_column) {
        return (strtoupper($current_dir) === 'ASC') ? 'DESC' : 'ASC';
    }
    // For Risk level, always default to DESC first. For others, default to ASC.
    return ($requested_column === 'risk') ? 'DESC' : 'ASC';
}
// ---------------------

// The query below ensures ONLY students who have AcademicRecords linked 
// to this specific teacher ID are displayed.

$studentQuery = $conn->prepare("
    SELECT DISTINCT
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel
    FROM Students s
    -- JOIN ensures only students with a record linked to this teacher are included
    JOIN AcademicRecords ar ON s.StudentID = ar.StudentID
    -- LEFT JOIN fetches the latest AI alert status for display
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID AND apa.AlertID = (
        SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID
    )
    WHERE ar.TeacherID = ?
    ORDER BY $order_column $order_direction, s.LastName ASC
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
    .sort-link { color: inherit; text-decoration: none; display: block; }
    .sort-indicator { margin-left: 5px; font-size: 0.8em; }
    .action-link { color: #28a745; text-decoration: none; font-weight: 600; margin-right: 15px; }
    .action-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="header"><h1>🧑‍🎓 My Students (for intervention)</h1></div>
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
            <!-- Sortable Header: Name -->
            <th>
                <?php $next_dir = get_next_dir($sort_by, 'name', $sort_dir); ?>
                <a href="?sort=name&dir=<?php echo $next_dir; ?>" class="sort-link">
                    Name
                    <?php if ($sort_by === 'name') echo '<span class="sort-indicator">' . ($order_direction === 'ASC' ? '▲' : '▼') . '</span>'; ?>
                </a>
            </th>
            <!-- Sortable Header: Grade/Section -->
            <th>
                <?php $next_dir = get_next_dir($sort_by, 'grade', $sort_dir); ?>
                <a href="?sort=grade&dir=<?php echo $next_dir; ?>" class="sort-link">
                    Grade/Section
                    <?php if ($sort_by === 'grade') echo '<span class="sort-indicator">' . ($order_direction === 'ASC' ? '▲' : '▼') . '</span>'; ?>
                </a>
            </th>
            <!-- Sortable Header: Disability -->
            <th>
                <?php $next_dir = get_next_dir($sort_by, 'disability', $sort_dir); ?>
                <a href="?sort=disability&dir=<?php echo $next_dir; ?>" class="sort-link">
                    Disability
                    <?php if ($sort_by === 'disability') echo '<span class="sort-indicator">' . ($order_direction === 'ASC' ? '▲' : '▼') . '</span>'; ?>
                </a>
            </th>
            <!-- Sortable Header: AI Risk Status -->
            <th>
                <?php $next_dir = get_next_dir($sort_by, 'risk', $sort_dir); ?>
                <a href="?sort=risk&dir=<?php echo $next_dir; ?>" class="sort-link">
                    AI Risk Status
                    <?php if ($sort_by === 'risk') echo '<span class="sort-indicator">' . ($order_direction === 'ASC' ? '▲' : '▼') . '</span>'; ?>
                </a>
            </th>
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
              <a href="grade_entry.php?student_id=<?php echo $s['StudentID']; ?>" class="action-link" style="color: #007bff;">Enter Grades</a>
              <a href="student_profile.php?id=<?php echo $s['StudentID']; ?>" class="action-link" style="color: #6c757d;">View Profile & Recs</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>