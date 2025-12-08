<?php
// pages/Students.php
include '../model/db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = "%$search%";

$limit = 10; // students per page (changed from 15 to 10)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$countQuery = $conn->prepare("
    SELECT COUNT(*) 
    FROM Students 
    WHERE FirstName LIKE ? OR LastName LIKE ? OR Section LIKE ?
");
$countQuery->execute([$searchParam, $searchParam, $searchParam]);
$totalStudents = $countQuery->fetchColumn();
$totalPages = ceil($totalStudents / $limit);

$studentQuery = $conn->prepare("
    SELECT 
        s.StudentID, s.FirstName, s.LastName, s.GradeLevel, s.Section, s.Disability,
        apa.RiskLevel, apa.PredictedIssue
    FROM Students s
    LEFT JOIN AI_PerformanceAlerts apa 
        ON s.StudentID = apa.StudentID 
        AND apa.AlertID = (
            SELECT MAX(AlertID) FROM AI_PerformanceAlerts WHERE StudentID = s.StudentID
        )
    WHERE s.FirstName LIKE ? OR s.LastName LIKE ? OR s.Section LIKE ?
    ORDER BY s.LastName ASC
    LIMIT $limit OFFSET $offset
");

$studentQuery->execute([$searchParam, $searchParam, $searchParam]);
$students = $studentQuery->fetchAll(PDO::FETCH_ASSOC);

function getRiskBadge($risk) {
    switch ($risk) {
        case 'High': return '<span style="color: white; background-color: #dc3545; padding: 8px 32px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; ">HIGH</span>';
        case 'Medium': return '<span style="color: #333; background-color: #ffc107; padding: 8px 20px; border-radius: 4px; font-weight: bold; font-size: 0.85rem">MEDIUM</span>';
        default: return '<span style="color: white; background-color: #28a745; padding: 8px 20px; border-radius: 4px; font-size: 0.85rem">NORMAL</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Directory & Status</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 10px;}

    .header {display: flex; justify-content: space-between; align-items: center;}
    .header h1 {color: #007bff;}

    /* Search Bar */
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

    .add-btn {
        background-color: #007bff;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: bold;
    }

    .add-btn:hover, .search-btn:hover { background-color: #0056b3; }

    .table-container {
        background-color: white; padding: 20px;
        border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #004080; color: #ffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}

    /* Enhanced Pagination */
    .pagination-wrapper {
        margin-top: 10px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
    }

    .pagination-info {
        text-align: center;
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }

    .page-btn {
        padding: 10px 16px;
        background: #ffffff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        text-decoration: none;
        color: #007bff;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        min-width: 42px;
        text-align: center;
    }

    .page-btn:hover {
        background: #007bff;
        color: white;
        border-color: #007bff;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
    }

    .page-btn.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
        box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
        cursor: default;
    }

    .page-btn.nav-btn {
        background: #007bff;
        color: white;
        border-color: #007bff;
        padding: 10px 20px;
        font-weight: 700;
    }

    .page-btn.nav-btn:hover {
        background: #0056b3;
        border-color: #0056b3;
    }

    .page-btn.disabled {
        background: #e9ecef;
        color: #6c757d;
        border-color: #dee2e6;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .page-btn.disabled:hover {
        background: #e9ecef;
        color: #6c757d;
        transform: none;
        box-shadow: none;
    }

    .page-ellipsis {
        padding: 10px 8px;
        color: #6c757d;
        font-weight: 600;
    }
    
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
    <h1>🧑‍🎓 Student Directory & AI Status</h1>

    <a href="student_form.php" class="add-btn">Add New Student</a>
</div>

<hr style="border: 0; height: 1px; background: #dee2e6; margin: 5px 0 20px 0;">

<!-- SEARCH BAR -->
<form class="search-box" method="GET">
    <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search); ?>">
    <button class="search-btn">Search</button>
</form>


<!-- STUDENT TABLE -->
    <table class="data-table" style="margin-top: 35px;">
      <thead>
        <tr>
          <th style="border-top-left-radius: 8px; width: 35%;">Name</th>
          <th style="width: 23%;">Grade/Section</th>
          <th style="width: 18%;">Disability</th>
          <th style="width: 14%; padding-left: 37px;">Status</th>
          <th style="border-top-right-radius: 8px; width: 10%; padding-left: 18px;">Actions</th>
        </tr>
      </thead>

      <tbody>
        <?php foreach ($students as $s): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['FirstName'] . ' ' . $s['LastName']); ?></td>
            <td><?php echo htmlspecialchars($s['GradeLevel'] . ' ' . $s['Section']); ?></td>
            <td><?php echo htmlspecialchars($s['Disability']); ?></td>
            <td><?php echo getRiskBadge($s['RiskLevel']); ?></td>

            <td>
                <a href="student_profile.php?id=<?php echo $s['StudentID']; ?>" class="view-btn">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>


<!-- ENHANCED PAGINATION -->
<div class="pagination-wrapper">
    <div class="pagination-info">
        <?php
            $start = $totalStudents > 0 ? $offset + 1 : 0;
            $end = min($offset + $limit, $totalStudents);
            echo "Showing $start - $end of $totalStudents students";
        ?>
    </div>

    <div class="pagination" style="margin-bottom: 30px;">
        <!-- Previous Button -->
        <?php if ($page > 1): ?>
            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="page-btn nav-btn">← Prev</a>
        <?php else: ?>
            <span class="page-btn nav-btn disabled">← Prev</span>
        <?php endif; ?>

        <?php
        // Smart pagination with ellipsis
        $range = 2; // Number of pages to show on each side of current page
        
        // Always show first page
        if ($page > $range + 2) {
            echo '<a href="?search=' . urlencode($search) . '&page=1" class="page-btn">1</a>';
            echo '<span class="page-ellipsis">...</span>';
        }
        
        // Show pages around current page
        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            echo '<a href="?search=' . urlencode($search) . '&page=' . $i . '" class="page-btn ' . $activeClass . '">' . $i . '</a>';
        }
        
        // Always show last page
        if ($page < $totalPages - $range - 1) {
            echo '<span class="page-ellipsis">...</span>';
            echo '<a href="?search=' . urlencode($search) . '&page=' . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
        }
        ?>

        <!-- Next Button -->
        <?php if ($page < $totalPages): ?>
            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="page-btn nav-btn">Next →</a>
        <?php else: ?>
            <span class="page-btn nav-btn disabled">Next →</span>
        <?php endif; ?>
    </div>
</div>

</body>
</html>