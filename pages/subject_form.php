<?php
// pages/subject_form.php
include '../model/db.php'; 

$subjectID = $_GET['id'] ?? null;
$subject = [];
$action = 'Add New';

if ($subjectID) {
    $action = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM Subjects WHERE SubjectID = ?");
    $stmt->execute([$subjectID]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$subject) { $subjectID = null; $action = 'Add New'; }
}

$gradeLevels = ['Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $action; ?> Subject</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 600px; margin: 0 auto; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #343a40; }
    .form-group input[type="text"], .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .submit-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
    .submit-btn:hover { background-color: #1e7e34; }
  </style>
</head>
<body>
  <div class="header"><h1>📚 <?php echo $action; ?> Subject/Course</h1></div>
  
  <div class="form-container">
    <form action="../model/data_submit.php" method="POST">
        <input type="hidden" name="form_type" value="subject">
        <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subjectID); ?>">
        
        <div class="form-group">
            <label>Subject Name</label>
            <input type="text" name="subject_name" value="<?php echo htmlspecialchars($subject['SubjectName'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Course Code (e.g., ENG8, MATH9)</label>
            <input type="text" name="code" value="<?php echo htmlspecialchars($subject['Code'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>Grade Level</label>
            <select name="grade_level">
                <?php foreach ($gradeLevels as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo (($subject['GradeLevel'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="submit-btn"><?php echo $action; ?> Subject</button>
    </form>
  </div>
</body>
</html>