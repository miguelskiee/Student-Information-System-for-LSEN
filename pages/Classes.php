<?php
// pages/Classes.php
include '../model/db.php'; 
$subjects = $conn->query("SELECT * FROM Subjects ORDER BY GradeLevel, SubjectName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Classes & Subjects</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    h2 { color: #343a40; margin-top: 20px; border-left: 5px solid #28a745; padding-left: 10px; margin-bottom: 15px; }
    .section-subjects, .section-assignments { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .add-btn { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class="header"><h1>📚 Subjects & Assignments</h1></div>

  <div class="section-subjects">
    <h2>Subject List</h2>
    <div class="controls"><button class="add-btn">➕ Add New Subject</button></div>
    <table class="data-table">
      <thead>
        <tr><th>Code</th><th>Name</th><th>Grade Level</th><th>Assignments</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subjects as $sub): ?>
          <tr>
            <td><?php echo htmlspecialchars($sub['Code']); ?></td>
            <td><?php echo htmlspecialchars($sub['SubjectName']); ?></td>
            <td><?php echo htmlspecialchars($sub['GradeLevel']); ?></td>
            <td><a href="assignments.php?sub_id=<?php echo $sub['SubjectID']; ?>" style="color: #007bff;">View (Placeholder)</a></td>
            <td><a href="#" style="color: #007bff;">Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section-assignments">
    <h2>🤖 AI Grading Review Queue</h2>
    <p>Submissions graded by AI with low confidence or a pending Teacher Override Score.</p>
    <table class="data-table">
      <thead>
        <tr><th>Submission ID</th><th>Assignment</th><th>Student</th><th>AI Score</th><th>Confidence</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>101</td>
          <td>Essay Writing</td>
          <td>Jacob Lopez</td>
          <td style="color: #dc3545;">45/50</td>
          <td>55% (Low)</td>
          <td><a href="grading_tool.php?sub_id=101" style="color: #007bff; font-weight: bold;">Review & Override</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</body>
</html>