<?php
// pages/Teacher_Assignments.php
session_start();
include '../model/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];

// --- FILTER LOGIC ---
$subjectFilter = $_GET['subject_id'] ?? null;
$whereClause = "a.TeacherID = ?";
$params = [$teacherID];

if ($subjectFilter) {
    $whereClause .= " AND a.SubjectID = ?";
    $params[] = $subjectFilter;
}

// 1. FETCH ASSIGNMENTS
$query = "
    SELECT 
        a.AssignmentID, a.Title, a.AssignmentType, a.MaxScore, a.DueDate,
        s.SubjectName, s.GradeLevel
    FROM Assignments a
    JOIN Subjects s ON a.SubjectID = s.SubjectID
    WHERE $whereClause
    ORDER BY a.DueDate DESC
";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. FETCH SUBJECTS FOR FILTER
$subStmt = $conn->prepare("
    SELECT DISTINCT s.SubjectID, s.SubjectName 
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
");
$subStmt->execute([$teacherID]);
$mySubjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Assignments</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: Arial, sans-serif; margin-top: 5px;}
    .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .header h1 { color: #007bff; margin: 0; }
    
    .controls { display: flex; gap: 15px; margin-bottom: 20px; }
    .controls select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    
    .panel { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; }
    .type-hw { background-color: #17a2b8; }
    .type-quiz { background-color: #ffc107; color: #333; }
    .type-project { background-color: #28a745; }
    .type-exam { background-color: #dc3545; }
    
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

    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #004080; color: #ffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}

  </style>
  <script>
    function filterBySubject() {
        const id = document.getElementById('subject_filter').value;
        window.location.href = 'Teacher_Assignments.php' + (id ? '?subject_id=' + id : '');
    }
    
    function confirmDelete(id, title) {
        if(confirm(`Are you sure you want to delete "${title}"? This will delete all student grades associated with it.`)) {
            window.location.href = `../model/assignment_delete.php?id=${id}`;
        }
    }
  </script>
</head>
<body>
  <div class="header">
    <h1>📚 Assignments Manager</h1>
    <a href="assignment_form.php" class="add-btn">Create New Assignment</a>
  </div>

  <div class="controls">
    <select id="subject_filter" onchange="filterBySubject()">
        <option value="">All Subjects</option>
        <?php foreach ($mySubjects as $s): ?>
            <option value="<?php echo $s['SubjectID']; ?>" <?php echo ($s['SubjectID'] == $subjectFilter) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($s['SubjectName']); ?>
            </option>
        <?php endforeach; ?>
    </select>
  </div>

    <table class="data-table">
      <thead>
        <tr>
          <th style="border-top-left-radius: 8px;">Title</th>
          <th>Subject</th>
          <th>Type</th>
          <th>Max Score</th>
          <th>Due Date</th>
          <th style="border-top-right-radius: 8px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($assignments)): ?>
            <tr><td colspan="6" style="text-align: center; padding: 20px;">No assignments found.</td></tr>
        <?php else: ?>
            <?php foreach ($assignments as $a): 
                $class = 'type-hw';
                if ($a['AssignmentType'] == 'Quiz') $class = 'type-quiz';
                if ($a['AssignmentType'] == 'Project') $class = 'type-project';
                if ($a['AssignmentType'] == 'Exam') $class = 'type-exam';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($a['Title']); ?></td>
              <td><?php echo htmlspecialchars($a['SubjectName']); ?> <small style="color:#6c757d;">(<?php echo $a['GradeLevel']; ?>)</small></td>
              <td><span class="badge <?php echo $class; ?>"><?php echo htmlspecialchars($a['AssignmentType']); ?></span></td>
              <td><?php echo $a['MaxScore']; ?></td>
              <td><?php echo date('M d, Y', strtotime($a['DueDate'])); ?></td>
              <td>
                <a href="assignment_form.php?id=<?php echo $a['AssignmentID']; ?>" class="btn btn-edit">✏️ Edit</a>
                <button onclick="confirmDelete(<?php echo $a['AssignmentID']; ?>, '<?php echo addslashes($a['Title']); ?>')" class="btn btn-delete">🗑️ Delete</button>
              </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
</body>
</html>