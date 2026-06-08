<?php
// pages/Classes.php
include '../models/db.php'; // Corrected path to model
$subjects = $conn->query("SELECT * FROM Subjects ORDER BY GradeLevel, SubjectName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Classes & Subjects</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}

    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }

    .section-subjects { 
        background-color: white; 
        padding: 20px; 
        border-radius: 8px; 
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); 
        margin-bottom: 30px; 
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .section-header h2 {
        color: #343a40; 
        margin: 0; 
        border-left: 5px solid #007bff; 
        padding-left: 10px; 
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

    .add-btn:hover { background-color: #0056b3; }

    .data-table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 10px;
    }

    .data-table th, .data-table td { 
        text-align: left; 
        padding: 12px; 
        border-bottom: 1px solid #eee; 
    }

    .data-table td { 
        background-color: white; 
        padding: 22px 15px;
    }

    .data-table th { 
        background-color: #004080; 
        color: #ffffff; 
    }

    .data-table td a { text-decoration: none; }

    .data-table td a:hover { text-decoration: underline; }

    .view-btn, .edit-btn {
        padding: 6px 14px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.9rem;
        color: white;
        display: inline-block;
        text-align: center;
        transition: all 0.2s ease;
    }

    .view-btn {
        background-color: #007bff;
    }

    .view-btn:hover {
        background-color: #0056b3;
    }

    .edit-btn {
        background-color: #28a745;
    }

    .edit-btn:hover {
        background-color: #218838;
    }

  </style>
</head>
<body>

<div class="header"><h1>📚 Subjects & Assignments</h1></div>

<div class="section-subjects">
    <div class="section-header">
        <h2>Subject List</h2>
        <a href="subject_form.php" class="add-btn">Add New Subject</a>
    </div>

    <hr style="border: 0; height: 1px; background: #dee2e6; margin: 10px 0 20px 0;">

 <table class="data-table">
  <thead>
    <tr>
      <th style="border-top-left-radius: 8px;">Code</th>
      <th>Name</th>
      <th>Grade Level</th>
      <th>Assignments</th>
      <th style="border-top-right-radius: 8px;">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($subjects as $sub): ?>
      <tr>
        <td><?php echo htmlspecialchars($sub['Code']); ?></td>
        <td><?php echo htmlspecialchars($sub['SubjectName']); ?></td>
        <td><?php echo htmlspecialchars($sub['GradeLevel']); ?></td>
        <td>
            <a href="assignments.php?sub_id=<?php echo $sub['SubjectID']; ?>" class="view-btn">View (12)</a>
        </td>
        <td>
            <a href="subject_form.php?id=<?php echo $sub['SubjectID']; ?>" class="edit-btn">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</div>

</body>
</html>
