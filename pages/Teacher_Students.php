<?php
// pages/Teacher_Students.php
session_start();
include '../model/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id']; 

// --- 1. FETCH TEACHER'S SECTIONS (FOR DROPDOWN) ---
$sectionsStmt = $conn->prepare("SELECT DISTINCT Section FROM TeacherAssignments WHERE TeacherID = ? ORDER BY Section");
$sectionsStmt->execute([$teacherID]);
$mySections = $sectionsStmt->fetchAll(PDO::FETCH_COLUMN);

// --- 2. FILTER LOGIC ---
$sectionFilter = $_GET['section'] ?? null;
$whereClause = "ta.TeacherID = ?";
$params = [$teacherID];

if ($sectionFilter) {
    $whereClause .= " AND ta.Section = ?";
    $params[] = $sectionFilter;
}

// --- 3. SORTING LOGIC ---
$allowed_columns = ['name' => 's.LastName', 'grade' => 's.GradeLevel', 'disability' => 's.Disability', 'risk' => 'apa.RiskLevel'];
$sort_by = $_GET['sort'] ?? 'name';
$sort_dir = $_GET['dir'] ?? 'ASC';
$order_column = $allowed_columns[$sort_by] ?? 's.LastName';
$order_direction = (strtoupper($sort_dir) === 'DESC') ? 'DESC' : 'ASC';

function get_next_dir($current_col, $req_col, $current_dir) {
    if ($current_col === $req_col) return ($current_dir === 'ASC') ? 'DESC' : 'ASC';
    return 'ASC';
}

function getRiskBadge($risk) {
    switch ($risk) {
        case 'High': return '<span style="color: white; background-color: #dc3545; padding: 4px 8px; border-radius: 4px; font-weight: bold;">HIGH 🚨</span>';
        case 'Medium': return '<span style="color: #333; background-color: #ffc107; padding: 4px 8px; border-radius: 4px; font-weight: bold;">MEDIUM ⚠️</span>';
        default: return '<span style="color: white; background-color: #28a745; padding: 4px 8px; border-radius: 4px;">Normal</span>';
    }
}

// --- 4. FETCH STUDENTS ---
$studentQuery = $conn->prepare("
    SELECT DISTINCT
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel
    FROM Students s
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID AND apa.AlertID = (
        SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID
    )
    WHERE $whereClause
    ORDER BY $order_column $order_direction
");
$studentQuery->execute($params);
$students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student List</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: Arial, sans-serif; }
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .header h1 { color: #007bff; margin: 0; margin-right: 20px; }
    
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    
    .action-link { color: #007bff; text-decoration: none; font-weight: 600; margin-right: 10px; }
    
    /* Filter Styles */
    .filter-group { display: flex; align-items: center; gap: 10px; }
    .filter-select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.95rem; }
    .btn-filter { background-color: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
    .btn-filter:hover { background-color: #0056b3; }
    .btn-back { background: #6c757d; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 0.9rem; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header">
    <div class="filter-group">
        <h1>🧑‍🎓 Student List</h1>
        
        <form method="GET" style="margin: 0; display: flex; gap: 5px;">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
            <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">
            
            <select name="section" class="filter-select">
                <option value="">All Sections</option>
                <?php foreach ($mySections as $sec): ?>
                    <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo ($sec == $sectionFilter) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($sec); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-filter">Filter</button>
        </form>
    </div>

    <?php if ($sectionFilter): ?>
        <a href="Teacher_Grades.php" class="btn-back">&larr; Back to Gradebook</a>
    <?php endif; ?>
  </div>

  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
            <th><a href="?sort=name&dir=<?php echo get_next_dir($sort_by, 'name', $sort_dir); ?>&section=<?php echo $sectionFilter; ?>">Name</a></th>
            <th>Grade/Section</th>
            <th>Disability</th>
            <th>Risk Status</th>
            <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($students)): ?>
            <tr><td colspan="5" style="text-align: center; padding: 20px; color: #6c757d;">No students found for this filter.</td></tr>
        <?php else: ?>
            <?php foreach ($students as $s): ?>
              <tr>
                <td><?php echo htmlspecialchars($s['LastName'] . ', ' . $s['FirstName']); ?></td>
                <td><?php echo htmlspecialchars($s['Section']); ?></td>
                <td><?php echo htmlspecialchars($s['Disability']); ?></td>
                <td><?php echo getRiskBadge($s['RiskLevel']); ?></td>
                <td>
                  <a href="grade_entry.php?student_id=<?php echo $s['StudentID']; ?>" class="action-link">📝 Grade</a>
                  <a href="student_profile.php?id=<?php echo $s['StudentID']; ?>" class="action-link" style="color: #6c757d;">👤 Profile</a>
                </td>
              </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</body>
</html>