<?php
// pages/teacher_form.php
include '../models/db.php'; 

$teacherID = $_GET['id'] ?? null;
$teacher = [];
$action = 'Add New';

if ($teacherID) {
    $action = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM Teachers WHERE TeacherID = ?");
    $stmt->execute([$teacherID]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$teacher) { $teacherID = null; $action = 'Add New'; }
}

$roles = ['Admin', 'Teacher'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $action; ?> Teacher</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 700px; margin: 0 auto; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #343a40; }
    .form-group input[type="text"], .form-group input[type="email"], .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .checkbox-group { margin-top: 10px; }
    .submit-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
    .submit-btn:hover { background-color: #1e7e34; }
  </style>
</head>
<body>
  <div class="header"><h1>👩‍🏫 <?php echo $action; ?> Staff/Teacher Account</h1></div>
  
  <div class="form-container">
    <form action="../controllers/data_submit.php" method="POST">
        <input type="hidden" name="form_type" value="teacher">
        <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacherID); ?>">
        
        <h2>Contact & Role</h2>
        <div class="form-grid">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($teacher['FirstName'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($teacher['LastName'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($teacher['Email'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($teacher['Phone'] ?? ''); ?>"></div>
            
            <div class="form-group">
                <label>User Role</label>
                <select name="user_role">
                    <?php foreach ($roles as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (($teacher['UserRole'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group checkbox-group">
                <label>Certification</label>
                <input type="checkbox" name="is_sped_certified" value="1" id="sped_cert" <?php echo ($teacher['IsSpecialEdCertified'] ?? 0) ? 'checked' : ''; ?>>
                <label for="sped_cert" style="display: inline; font-weight: normal;">Is Special Ed Certified?</label>
            </div>
        </div>

        <h2>Qualifications</h2>
        <div class="form-group">
            <label>Specializations (Comma separated)</label>
            <input type="text" name="specializations" value="<?php echo htmlspecialchars($teacher['Specializations'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Training & Certifications</label>
            <textarea name="certifications" rows="3"><?php echo htmlspecialchars($teacher['Certifications'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="submit-btn"><?php echo $action; ?> Teacher</button>
    </form>
  </div>
</body>
</html>