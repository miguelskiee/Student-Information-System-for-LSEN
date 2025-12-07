<?php
// pages/assignment_form.php
session_start();
include '../model/db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];

// CHECK IF EDITING
$assignmentID = $_GET['id'] ?? null;
$assignment = [];
$action = 'Create';

if ($assignmentID) {
    $action = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM Assignments WHERE AssignmentID = ? AND TeacherID = ?");
    $stmt->execute([$assignmentID, $teacherID]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) { die("Assignment not found or access denied."); }
}

// Fetch subjects for dropdown
$subjectsStmt = $conn->prepare("
    SELECT s.SubjectID, s.SubjectName, ta.Section
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
    GROUP BY s.SubjectID
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
    body { background-color: #f4f7f9; color: #333; padding: 20px; font-family: Arial, sans-serif; }
    .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 600px; margin: 0 auto; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #343a40; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    
    .submit-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; width: 100%; }
    .submit-btn:hover { background-color: #1e7e34; }
    
    .back-link { display: block; text-align: center; margin-top: 15px; color: #6c757d; text-decoration: none; }
  </style>
</head>
<body>
  <div class="form-container">
    <div class="header"><h1>📝 <?php echo $action; ?> Assignment</h1></div>
    
    <form action="../model/assignment_submit.php" method="POST">
        <input type="hidden" name="assignment_id" value="<?php echo htmlspecialchars($assignmentID); ?>">
        
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($assignment['Title'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Subject</label>
            <select name="subject_id" required>
                <?php foreach ($mySubjects as $s): ?>
                    <option value="<?php echo $s['SubjectID']; ?>" <?php echo (($assignment['SubjectID'] ?? '') == $s['SubjectID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['SubjectName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Type</label>
            <select name="assignment_type" required>
                <?php foreach ($typeOptions as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo (($assignment['AssignmentType'] ?? '') == $opt) ? 'selected' : ''; ?>>
                        <?php echo $opt; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Maximum Score</label>
            <input type="number" name="max_score" value="<?php echo htmlspecialchars($assignment['MaxScore'] ?? '100'); ?>" min="1" required>
        </div>
        
        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" value="<?php echo htmlspecialchars($assignment['DueDate'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?php echo htmlspecialchars($assignment['Description'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="submit-btn">Save Assignment</button>
        <a href="Teacher_Assignments.php" class="back-link">Cancel & Return</a>
    </form>
  </div>
</body>
</html>