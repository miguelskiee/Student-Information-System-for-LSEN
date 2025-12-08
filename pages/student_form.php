<?php
// pages/student_form.php
include '../model/db.php'; 

$studentID = $_GET['id'] ?? null;
$student = [];
$action = 'Add New';

if ($studentID) {
    $action = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM Students WHERE StudentID = ?");
    $stmt->execute([$studentID]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student) { $studentID = null; $action = 'Add New'; }
}

$sexOptions = ['Male', 'Female', 'Other'];
$gradeLevels = ['Grade 6', 'Grade 7', 'Grade 8', 'Grade 9'];
$sections = ['SPED-A', 'SPED-B', 'SPED-C', 'Regular'];

// DEFINED LIST OF DISABILITIES FOR DROPDOWN (Based on sample data and common SPED categories)
$disabilityOptions = [
    'ADHD',
    'Dyslexia',
    'Autism Spectrum Disorder',
    'Dyscalculia',
    'Hearing Impairment',
    'Visual Impairment',
    'Speech Delay',
    'Other Learning Disability',
    'Emotional Disturbance',
    'None Specified'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo $action; ?> Student</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 25px;}
    .form-container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); max-width: 800px; margin: 0 auto; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; margin-bottom: 5px; color: #343a40; }
    .form-group input[type="text"], .form-group input[type="email"], .form-group input[type="date"], 
    .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .submit-btn { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; font-weight: bold; }
    .submit-btn:hover { background-color: #1e7e34; }
  </style>
</head>
<body>
  <div class="header"><h1>📝 <?php echo $action; ?> Student Record</h1></div>
  
  <div class="form-container">
    <form action="../model/data_submit.php" method="POST">
        <input type="hidden" name="form_type" value="student">
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentID); ?>">
        
        <h2>Personal Details</h2>
        <div class="form-grid">
            <div class="form-group"><label>First Name</label><input type="text" name="first_name" value="<?php echo htmlspecialchars($student['FirstName'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Last Name</label><input type="text" name="last_name" value="<?php echo htmlspecialchars($student['LastName'] ?? ''); ?>" required></div>
            <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" value="<?php echo htmlspecialchars($student['MiddleName'] ?? ''); ?>"></div>
            <div class="form-group"><label>Birth Date</label><input type="date" name="birth_date" value="<?php echo htmlspecialchars($student['BirthDate'] ?? ''); ?>"></div>
            <div class="form-group">
                <label>Sex</label>
                <select name="sex">
                    <?php foreach ($sexOptions as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (($student['Sex'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Contact Email</label><input type="email" name="contact_email" value="<?php echo htmlspecialchars($student['ContactEmail'] ?? ''); ?>"></div>
        </div>

        <h2>Academic & SPED Details</h2>
        <div class="form-grid">
            <div class="form-group">
                <label>Grade Level</label>
                <select name="grade_level">
                    <?php foreach ($gradeLevels as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (($student['GradeLevel'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Section</label>
                <select name="section">
                    <?php foreach ($sections as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (($student['Section'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- UPDATED TO DROPDOWN FOR CONSISTENCY -->
            <div class="form-group" style="grid-column: 1 / span 2;">
                <label>Disability</label>
                <select name="disability">
                    <?php foreach ($disabilityOptions as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php echo (($student['Disability'] ?? '') == $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column: 1 / span 2;">
                <label>Notes (SPED Interventions/Observations)</label>
                <textarea name="notes" rows="3"><?php echo htmlspecialchars($student['Notes'] ?? ''); ?></textarea>
            </div>
        </div>
        
        <button type="submit" class="submit-btn"><?php echo $action; ?> Student</button>
    </form>
  </div>
</body>
</html>