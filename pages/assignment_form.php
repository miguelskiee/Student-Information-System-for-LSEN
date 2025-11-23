<?php
// pages/assignment_form.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID
$action = 'Create';

// Fetch teacher's assigned subjects for the dropdown
$subjectsStmt = $conn->prepare("
    SELECT s.SubjectID, s.SubjectName, ta.Section
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
");
$subjectsStmt->execute([$teacherID]);
$mySubjects = $subjectsStmt->fetchAll(PDO::FETCH_ASSOC);

$typeOptions = ['Homework', 'Quiz', 'Exam', 'Project', 'Essay'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $action; ?> Assignment</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 600px; margin: 0 auto; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #343a40; }
    .form-group input[type="text"], .form-group input[type="date"], .form-group textarea, .form-group select, .form-group input[type="number"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .submit-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header"><h1>➕ <?php echo $action; ?> New Assignment</h1></div>
  
  <div class="form-container">
    <form action="../model/assignment_submit.php" method="POST">
        <input type="hidden" name="teacher_id" value="<?php echo $teacherID; ?>">
        
        <h2>Assignment Details</h2>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required>
        </div>
        
        <div class="form-group">
            <label>Subject \& Section</label>
            <select name="subject_id" required>
                <?php foreach ($mySubjects as $s): ?>
                    <option value="<?php echo $s['SubjectID']; ?>"><?php echo htmlspecialchars($s['SubjectName'] . ' - ' . $s['Section']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Type</label>
            <select name="assignment_type" required>
                <?php foreach ($typeOptions as $opt): ?>
                    <option value="<?php echo $opt; ?>"><?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Maximum Score</label>
            <input type="number" name="max_score" value="100" min="1" required>
        </div>
        
        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3"></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Create Assignment</button>
    </form>
  </div>
</body>
</html>