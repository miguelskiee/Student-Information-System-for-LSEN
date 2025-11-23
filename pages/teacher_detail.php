<?php
// pages/teacher_detail.php
include '../model/db.php'; 

$teacherID = $_GET['id'] ?? die("Error: Teacher ID required.");

// 1. Fetch Teacher's Core Profile
$stmt = $conn->prepare("SELECT * FROM Teachers WHERE TeacherID = ?");
$stmt->execute([$teacherID]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$teacher) {
    die("Error: Teacher not found.");
}

// 2. Fetch Teacher's Current Assignments
$assignmentsStmt = $conn->prepare("
    SELECT ta.Section, ta.Schedule, s.SubjectName, s.GradeLevel
    FROM TeacherAssignments ta
    JOIN Subjects s ON ta.SubjectID = s.SubjectID
    WHERE ta.TeacherID = ?
    ORDER BY s.GradeLevel, s.SubjectName
");
$assignmentsStmt->execute([$teacherID]);
$assignments = $assignmentsStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch count of High-Risk Students assigned to this teacher
$riskCountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT ar.StudentID) 
    FROM AcademicRecords ar
    JOIN AI_PerformanceAlerts apa ON ar.StudentID = apa.StudentID
    WHERE ar.TeacherID = ? AND apa.RiskLevel = 'High'
");
$riskCountStmt->execute([$teacherID]);
$riskCount = $riskCountStmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Profile: <?php echo htmlspecialchars($teacher['LastName']); ?></title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .panel { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
    .profile-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    h2 { color: #343a40; border-left: 5px solid #28a745; padding-left: 10px; margin-bottom: 15px; font-size: 1.4rem; }
    
    .data-row { margin-bottom: 15px; }
    .data-row strong { display: inline-block; width: 160px; color: #6c757d; font-weight: 600; }
    
    .status-alert { padding: 15px; border-radius: 6px; font-weight: bold; }
    .status-sped-certified { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-risk { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    
    /* Table Styling */
    .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
  </style>
</head>
<body>
  <div class="header">
    <h1>👩‍🏫 Teacher Profile: <?php echo htmlspecialchars($teacher['FirstName'] . ' ' . $teacher['LastName']); ?></h1>
    <a href="teacher_form.php?id=<?php echo $teacherID; ?>" style="float: right; color: #28a745; text-decoration: none;">[Edit Account Details]</a>
  </div>

  <div class="profile-grid">
    <!-- Left Column: Details & Qualifications -->
    <div>
        <div class="panel">
            <h2>Personal & Contact</h2>
            <div class="data-row"><strong>Role:</strong> <?php echo htmlspecialchars($teacher['UserRole']); ?></div>
            <div class="data-row"><strong>Email:</strong> <?php echo htmlspecialchars($teacher['Email']); ?></div>
            <div class="data-row"><strong>Phone:</strong> <?php echo htmlspecialchars($teacher['Phone']); ?></div>
        </div>

        <div class="panel">
            <h2>Qualifications</h2>
            <div class="data-row">
                <strong>SPED Certified:</strong> 
                <?php if ($teacher['IsSpecialEdCertified']): ?>
                    <span class="status-alert status-sped-certified">✅ YES, Certified</span>
                <?php else: ?>
                    ❌ No
                <?php endif; ?>
            </div>
            <div class="data-row"><strong>Specializations:</strong> <?php echo htmlspecialchars($teacher['Specializations']); ?></div>
            <div class="data-row"><strong>Training:</strong> <?php echo nl2br(htmlspecialchars($teacher['Training'])); ?></div>
            <div class="data-row"><strong>Certifications:</strong> <?php echo nl2br(htmlspecialchars($teacher['Certifications'])); ?></div>
        </div>
    </div>

    <!-- Right Column: Assignments & Metrics -->
    <div>
        <div class="panel">
            <h2>AI Metrics Summary</h2>
            <div class="data-row status-alert status-risk">
                <strong>High-Risk Students:</strong> 
                <?php echo $riskCount; ?>
            </div>
            <div class="data-row">
                <strong>Total Assignments:</strong> 
                <?php echo count($assignments); ?>
            </div>
        </div>

        <div class="panel">
            <h2>Current Class Assignments</h2>
            <?php if (count($assignments) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr><th>Subject</th><th>Grade</th><th>Section</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($assignment['SubjectName']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['GradeLevel']); ?></td>
                            <td><?php echo htmlspecialchars($assignment['Section']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="color: #dc3545;">No current teaching assignments recorded.</p>
            <?php endif; ?>
        </div>
    </div>
  </div>
</body>
</html>