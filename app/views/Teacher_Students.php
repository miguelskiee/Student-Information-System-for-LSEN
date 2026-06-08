<?php
// pages/Teacher_Students.php
session_start();
include '../models/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id']; 

// --- 1. FETCH TEACHER'S SECTIONS (FOR DROPDOWN) ---
$sectionsStmt = $conn->prepare("SELECT DISTINCT Section FROM TeacherAssignments WHERE TeacherID = ? ORDER BY Section");
$sectionsStmt->execute([$teacherID]);
$mySections = $sectionsStmt->fetchAll(PDO::FETCH_COLUMN);

// --- SEARCH LOGIC ---
$search = $_GET['search'] ?? '';
$searchParam = "%{$search}%";

// --- 2. FILTER LOGIC ---
$sectionFilter = $_GET['section'] ?? null;
$whereClause = "ta.TeacherID = ? AND (s.FirstName LIKE ? OR s.LastName LIKE ? OR s.Section LIKE ?)";
$params = [$teacherID, $searchParam, $searchParam, $searchParam];

if ($sectionFilter) {
    $whereClause .= " AND ta.Section = ?";
    $params[] = $sectionFilter;
}

// --- 3. SORTING LOGIC ---
$allowed_columns = [
    'name' => 's.LastName',
    'grade' => 's.GradeLevel',
    'disability' => 's.Disability',
    'risk' => 'apa.RiskLevel'
];

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
        case 'High': return '<span style="color: white; background-color: #dc3545; padding: 8px 32px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">HIGH</span>';
        case 'Medium': return '<span style="color: #333; background-color: #ffc107; padding: 8px 22px; border-radius: 4px; font-weight: bold; font-size: 0.85rem;">MEDIUM</span>';
        default: return '<span style="color: white; background-color: #28a745; padding: 8px 20px; border-radius: 4px; font-size: 0.85rem;">NORMAL</span>';
    }
}

// --- 4. FETCH STUDENTS ---
$studentQuery = $conn->prepare("
    SELECT DISTINCT
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel
    FROM Students s
    JOIN TeacherAssignments ta ON s.Section = ta.Section
    LEFT JOIN AI_PerformanceAlerts apa ON s.StudentID = apa.StudentID 
         AND apa.AlertID = (SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID)
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

    .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 15px; }
    .header h1 { color: #007bff; margin: 0; }

    /* Search Bar */
    .search-box { margin: 15px 0; display: flex; gap: 10px; }
    .search-box input {
        padding: 10px;
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .search-btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }
    .search-btn:hover { background-color: #0056b3; }

    /* Filter */
    .filter-select { padding: 8px; border-radius: 4px; border: 1px solid #ccc; }
    .btn-filter { background: #007bff; color: white; padding: 8px 14px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; }
    .btn-filter:hover { background: #0056b3; }

    .btn-back { background: #6c757d; color: white; padding: 8px 14px; border-radius: 4px; text-decoration: none; font-weight: bold; }

    /* Table */
    .table-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #004080; color: #ffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}


    .action-link { color: #007bff; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>

<div class="header">
    <h1>🧑‍🎓 Student List</h1>

    <?php if ($sectionFilter): ?>
        <a href="Teacher_Grades.php" class="btn-back">&larr; Back to Gradebook</a>
    <?php endif; ?>
</div>

<!-- SEARCH BAR -->
<form class="search-box" method="GET">
    <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">

    <?php if ($sectionFilter): ?>
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($sectionFilter); ?>">
    <?php endif; ?>

    <button class="search-btn">Search</button>
</form>

<!-- FILTER SECTION -->
<form method="GET" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <select name="section" class="filter-select">
        <option value="">All Sections</option>
        <?php foreach ($mySections as $sec): ?>
            <option value="<?php echo htmlspecialchars($sec); ?>" <?php echo ($sec == $sectionFilter) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($sec); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button class="btn-filter">Filter</button>
</form>

    <table class="data-table">
        <thead>
            <tr>
                <th style="border-top-left-radius: 8px; width: 35%;">Name</th>
                <th style="width: 20%;">Grade / Section</th>
                <th style="width: 20%;"">Disability</th>
                <th style="width: 15%;">Risk Status</th>
                <th style="border-top-right-radius: 8px; width: 10%;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="5" style="text-align:center; padding:20px; color:#6c757d;">No matching students found.</td></tr>
            <?php else: ?>
                <?php foreach ($students as $s): ?>
                <tr>
                    <td>
                        <a href="student_profile.php?id=<?php echo $s['StudentID']; ?>"
                           style="color:#007bff; font-weight:bold; text-decoration:none;">
                            <?php echo htmlspecialchars($s['LastName'] . ', ' . $s['FirstName']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($s['Section']); ?></td>
                    <td><?php echo htmlspecialchars($s['Disability']); ?></td>
                    <td><?php echo getRiskBadge($s['RiskLevel']); ?></td>
                    <td>
                        <a href="grade_entry.php?student_id=<?php echo $s['StudentID']; ?>" class="action-link">📝 Grade</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
