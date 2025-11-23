<?php
// pages/Teacher_Classes.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID

// Fetch subjects and sections assigned to this teacher
$assignments = $conn->prepare("
    SELECT ta.Section, s.SubjectName, s.SubjectID 
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
");
$assignments->execute([$teacherID]);
$myClasses = $assignments->fetchAll(PDO::FETCH_ASSOC);

// Count pending submissions needing review for visual feedback
$pendingReviewCount = $conn->query("
    SELECT COUNT(s.SubmissionID) 
    FROM StudentSubmissions s
    JOIN Assignments a ON s.AssignmentID = a.AssignmentID
    JOIN AI_GradingResults aigr ON s.SubmissionID = aigr.SubmissionID
    WHERE a.TeacherID = $teacherID AND aigr.ConfidenceLevel < 0.80
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Classes & Grading</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .panel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .panel { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    h2 { color: #343a40; margin-bottom: 15px; border-left: 5px solid #28a745; padding-left: 10px; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .action-btn { background-color: #007bff; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px; }
    .warning { color: #dc3545; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header"><h1>📝 My Classes & Gradebook</h1></div>

  <div class="panel-grid">
    <!-- Left Panel: My Classes & Data Entry -->
    <div class="panel">
      <h2>My Current Assignments</h2>
      <p style="color: #6c757d; margin-bottom: 15px;">Sections where you are the primary instructor.</p>
      
      <table class="data-table">
        <thead>
          <tr><th>Subject</th><th>Section</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($myClasses as $class): ?>
            <tr>
              <td><?php echo htmlspecialchars($class['SubjectName']); ?></td>
              <td><?php echo htmlspecialchars($class['Section']); ?></td>
              <td>
                <a href="#" style="color: #28a745;">Enter Grades</a> | 
                <a href="#" style="color: #ffc107;">Log Attendance</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button class="action-btn" style="margin-top: 20px; background-color: #28a745;">➕ Create New Assignment</button>
    </div>

    <!-- Right Panel: AI Grading -->
    <div class="panel">
      <h2 style="border-left-color: #ffc107;">🤖 AI Grading Review (<?php echo $pendingReviewCount; ?> Pending)</h2>
      <p style="color: #6c757d; margin-bottom: 15px;">Submissions requiring manual inspection or score override.</p>
      
      <?php if ($pendingReviewCount > 0): ?>
          <p class="warning">You have **<?php echo $pendingReviewCount; ?>** submissions needing review.</p>
          <a href="grading_override.php" class="action-btn" style="background-color: #dc3545;">Go to Review Queue</a>
      <?php else: ?>
          <p style="color: #28a745; font-weight: bold;">✅ Queue is clear! All submissions processed.</p>
      <?php endif; ?>
      
      <h3 style="margin-top: 30px; font-size: 1rem; color: #343a40;">Quick Actions</h3>
      <a href="#" class="action-btn" style="background-color: #007bff;">View All Submissions</a>
    </div>
  </div>
</body>
</html>