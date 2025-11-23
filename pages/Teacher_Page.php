<?php
// pages/Teacher_Page.php
include '../model/db.php'; // Corrected path to model
$teachers = $conn->query("SELECT * FROM Teachers ORDER BY UserRole DESC, LastName ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teachers Directory</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 15px; }
    .controls { margin-bottom: 20px; }
    .add-btn { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
    .table-container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .role-admin { font-weight: bold; color: #dc3545; }
  </style>
</head>
<body>
  <div class="header"><h1>👩‍🏫 Teacher Management</h1></div>
  
  <div class="controls">
      <a href="teacher_form.php" class="add-btn">➕ Add New Teacher</a>
  </div>
  
  <div class="table-container">
    <table class="data-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Role</th>
          <th>Email</th>
          <th>SPED Certified</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teachers as $t): ?>
          <tr>
            <td><?php echo htmlspecialchars($t['FirstName'] . ' ' . $t['LastName']); ?></td>
            <td class="<?php echo ($t['UserRole'] == 'Admin') ? 'role-admin' : ''; ?>"><?php echo htmlspecialchars($t['UserRole']); ?></td>
            <td><?php echo htmlspecialchars($t['Email']); ?></td>
            <td><?php echo $t['IsSpecialEdCertified'] ? '✅ Yes' : '❌ No'; ?></td>
            <td>
                <a href="teacher_detail.php?id=<?php echo $t['TeacherID']; ?>" style="color: #007bff;">View</a> |
                <a href="teacher_form.php?id=<?php echo $t['TeacherID']; ?>" style="color: #28a745;">Edit</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>