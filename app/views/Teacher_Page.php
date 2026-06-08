<?php
// pages/Teacher_Page.php
include '../models/db.php';

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%$search%";

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Count total matching teachers
$countQuery = $conn->prepare("SELECT COUNT(*) FROM Teachers WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?");
$countQuery->execute([$searchParam, $searchParam, $searchParam]);
$totalTeachers = $countQuery->fetchColumn();
$totalPages = ceil($totalTeachers / $limit);

// Fetch teachers with search and pagination
$teacherQuery = $conn->prepare("
    SELECT * 
    FROM Teachers 
    WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?
    ORDER BY UserRole DESC, LastName ASC
    LIMIT $limit OFFSET $offset
");
$teacherQuery->execute([$searchParam, $searchParam, $searchParam]);
$teachers = $teacherQuery->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teachers Directory</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 10px;}
    
    .header {display: flex; justify-content: space-between; align-items: center;}
    .header h1 {color: #007bff;}

    .add-btn {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: bold;
    }

    .add-btn:hover { background-color: #0056b3; }

    .search-box {margin: 10px 0 20px 0; display: flex; gap: 10px;}
    .search-box input {padding: 10px; width: 100%; border: 1px solid #ccc; border-radius: 5px;}
    .search-btn {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: bold;
    }
    .search-btn:hover { background-color: #0056b3; }

    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #004080; color: #ffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}
    .role-admin { font-weight: bold; color: #dc3545; }

    /* Pagination */
    .pagination { display: flex; justify-content: center; gap: 6px; margin-top: 20px; flex-wrap: wrap; }
    .page-btn { padding: 8px 14px; border: 1px solid #dee2e6; border-radius: 6px; text-decoration: none; color: #007bff; font-weight: 600; }
    .page-btn.active { background: #007bff; color: white; border-color: #007bff; cursor: default; }
    .page-btn:hover:not(.active) { background: #007bff; color: white; border-color: #007bff; }
    .page-btn.disabled { background: #e9ecef; color: #6c757d; cursor: not-allowed; }

        .view-btn {
    background-color: #007bff;
    color: white;
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s ease;
}

.view-btn:hover {
    background-color: #0056b3;
    transform: translateY(-1px);
}
  </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <h1>👩‍🏫 Teacher Management</h1>
    <a href="teacher_form.php" class="add-btn">Add New Teacher</a>
</div>

<hr style="border: 0; height: 1px; background: #dee2e6; margin: 5px 0 20px 0;">

<!-- SEARCH -->
<form class="search-box" method="GET">
    <input type="text" name="search" placeholder="Search teachers..." value="<?php echo htmlspecialchars($search); ?>">
    <button class="search-btn">Search</button>
</form>

<!-- TEACHER TABLE -->
    <table class="data-table" style="margin-top: 35px;">
      <thead>
        <tr>
          <th style="border-top-left-radius: 8px; width: 35%;">Name</th>
          <th style="width: 18%;">Role</th>
          <th style="width: 20%;">Email</th>
          <th style="width: 17%;">SPED Certified</th>
          <th style="border-top-right-radius: 8px; width: 10%;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($teachers) > 0): ?>
            <?php foreach ($teachers as $t): ?>
            <tr>
                <td><?php echo htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']); ?></td>
                <td class="<?php echo ($t['UserRole'] == 'Admin') ? 'role-admin' : ''; ?>"><?php echo htmlspecialchars($t['UserRole']); ?></td>
                <td><?php echo htmlspecialchars($t['Email']); ?></td>
                <td><?php echo $t['IsSpecialEdCertified'] ? '✅ Yes' : '❌ No'; ?></td>
                <td>
                    <a href="teacher_detail.php?id=<?php echo $t['TeacherID']; ?>" class="view-btn">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No teachers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

<!-- PAGINATION -->
<div class="pagination">
<?php
// Previous button
if ($page > 1) {
    echo '<a href="?search=' . urlencode($search) . '&page=' . ($page-1) . '" class="page-btn">Prev</a>';
} else {
    echo '<span class="page-btn disabled">Prev</span>';
}

// Pages
for ($i=1; $i<=$totalPages; $i++) {
    $active = ($i == $page) ? 'active' : '';
    echo '<a href="?search=' . urlencode($search) . '&page=' . $i . '" class="page-btn ' . $active . '">' . $i . '</a>';
}

// Next button
if ($page < $totalPages) {
    echo '<a href="?search=' . urlencode($search) . '&page=' . ($page+1) . '" class="page-btn">Next</a>';
} else {
    echo '<span class="page-btn disabled">Next</span>';
}
?>
</div>

</body>
</html>
