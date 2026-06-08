<?php
session_start();
include '../models/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];

// Fetch assignments for THIS teacher
$assignmentsStmt = $conn->prepare("
    SELECT ta.Section, s.SubjectName, s.SubjectID 
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
");
$assignmentsStmt->execute([$teacherID]);
$myClasses = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// REMOVED: Pending review count query
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Classes & Grading</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    /* Changed grid to single column since right panel is removed */
    .panel-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
    .panel { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    h2 { color: #343a40; margin-bottom: 15px; border-left: 5px solid #28a745; padding-left: 10px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #004080; color: #ffffff; }
    .data-table td { background-color: white; padding: 22px 15px;}

    .action-link { text-decoration: none; margin: 0 5px; }
  </style>
</head>
<body>
  <div class="header"><h1>📝 My Classes</h1></div>
      <table class="data-table">
        <thead>
          <tr><th style="border-top-left-radius: 8px;">Subject</th><th>Section</th><th style="border-top-right-radius: 8px;">Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($myClasses as $class): ?>
            <tr>
              <td><?php echo htmlspecialchars($class['SubjectName']); ?></td>
              <td><?php echo htmlspecialchars($class['Section']); ?></td>
              <td>
                <a href="attendance_logger.php?subject_id=<?php echo $class['SubjectID']; ?>&section=<?php echo $class['Section']; ?>" 
                   class="action-link" style="color: #ffc107; font-weight: 600;">Log Attendance</a> |
                
                <a href="Teacher_Students.php" class="action-link" style="color: #28a745;">Enter Grades</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

</body>
</html>