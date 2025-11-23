<?php
// pages/Teacher_Dashboard.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID

// 1. FETCH COUNTS & ASSIGNMENTS
$studentCount = $conn->query("SELECT COUNT(DISTINCT StudentID) FROM AcademicRecords WHERE TeacherID = $teacherID")->fetchColumn();
$subjectCount = $conn->query("SELECT COUNT(*) FROM TeacherAssignments WHERE TeacherID = $teacherID")->fetchColumn();
$alertsCount = $conn->query("SELECT COUNT(*) FROM AI_PerformanceAlerts apa JOIN AcademicRecords ar ON apa.StudentID = ar.StudentID WHERE ar.TeacherID = $teacherID AND apa.RiskLevel = 'High'")->fetchColumn();

// 2. FETCH GRADING QUEUE (AI-Assisted Grading & Feedback)
// Find submissions related to this teacher's assignments that need review/override
$queueQuery = "
    SELECT 
        s.SubmissionID, ass.Title, stud.FirstName, stud.LastName, aigr.SuggestedScore, aigr.ConfidenceLevel
    FROM StudentSubmissions s
    JOIN Assignments ass ON s.AssignmentID = ass.AssignmentID
    JOIN AI_GradingResults aigr ON s.SubmissionID = aigr.SubmissionID
    JOIN Students stud ON s.StudentID = stud.StudentID
    WHERE ass.TeacherID = $teacherID AND aigr.ConfidenceLevel < 0.80 
    LIMIT 3";
$gradingQueue = $conn->query($queueQuery)->fetchAll(PDO::FETCH_ASSOC);

// 3. FETCH STUDENT ALERTS (Progress & Performance Prediction)
$alertsQuery = "
    SELECT 
        s.StudentID, s.FirstName, s.LastName, a.RiskLevel, a.PredictedIssue
    FROM AI_PerformanceAlerts a
    JOIN AcademicRecords ar ON a.StudentID = ar.StudentID
    JOIN Students s ON a.StudentID = s.StudentID
    WHERE ar.TeacherID = $teacherID AND a.RiskLevel IN ('High', 'Medium')
    GROUP BY s.StudentID -- Distinct alerts per student
    LIMIT 5";
$studentAlerts = $conn->query($alertsQuery)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teacher Dashboard</title>
  <style>
    /* Teacher Dashboard Styling */
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .dashboard-welcome h1 { font-size: 1.8rem; color: #28a745; }
    .dashboard-title h1 { border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 20px; }
    .dashboard-container { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
    .card { background-color: white; border-radius: 8px; padding: 20px; flex: 1; min-width: 180px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    .card-icon { font-size: 2rem; margin-bottom: 10px; color: #007bff; }
    .card p { font-size: 2.0rem; font-weight: bold; color: #343a40; }

    /* Action Panels (AI driven) */
    .action-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .panel { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); margin-bottom: 20px; }
    .panel h2 { color: #dc3545; border-bottom: 2px solid #ffc107; padding-bottom: 5px; margin-bottom: 15px; font-size: 1.2rem; }

    /* Table Styling */
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; font-size: 0.95rem; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; }
    .bg-high { background-color: #dc3545; } 
    .bg-medium { background-color: #ffc107; color: #333; } 
  </style>
</head>
<body>
  <div class="dashboard-welcome">
    <h1>Welcome back, <?php echo htmlspecialchars($user['FirstName'] ?? 'Teacher'); ?>!</h1>
    <h3>Focus on students needing immediate intervention.</h3>
  </div>
  
  <div class="dashboard-title">
    <h1>My Overview</h1>
  </div>

  <div class="dashboard-container">
    <div class="card">
      <div class="card-icon">👥</div>
      <h3>My Students</h3>
      <p><?php echo $studentCount; ?></p>
    </div>
    <div class="card">
      <div class="card-icon">📚</div>
      <h3>My Subjects</h3>
      <p><?php echo $subjectCount; ?></p>
    </div>
    <div class="card" style="background-color: #f8d7da; border-left: 5px solid #dc3545;">
      <div class="card-icon" style="color: #dc3545;">🚨</div>
      <h3>High Risk Alerts</h3>
      <p><?php echo $alertsCount; ?></p>
    </div>
  </div>

  <div class="action-grid">
    <!-- Left Panel: Alerts and Recommendations -->
    <div>
      <div class="panel">
        <h2 style="color: #007bff; border-bottom-color: #007bff;">🔔 Urgent Student Alerts</h2>
        <table class="data-table">
            <thead>
                <tr><th>Student</th><th>Risk</th><th>Prediction</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if ($studentAlerts): ?>
                    <?php foreach ($studentAlerts as $alert): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($alert['FirstName'] . ' ' . $alert['LastName']); ?></td>
                        <td><span class="badge <?php echo ($alert['RiskLevel'] == 'High') ? 'bg-high' : 'bg-medium'; ?>"><?php echo htmlspecialchars($alert['RiskLevel']); ?></span></td>
                        <td><?php echo htmlspecialchars($alert['PredictedIssue']); ?></td>
                        <td><a href="student_profile.php?id=<?php echo $alert['StudentID']; ?>" style="color: #007bff;">View Recs</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No high-priority student alerts.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
      </div>

      <div class="panel">
        <h2 style="color: #28a745; border-bottom-color: #28a745;">💡 Smart Lesson Recommendations</h2>
        <p style="color: #6c757d;">Suggested strategies for your current highest-risk students (based on `AI_TeachingRecommendations`).</p>
        <ul style="list-style: disc; padding-left: 20px; margin-top: 15px; color: #343a40;">
            <!-- Placeholder for fetching actual recommendations for high-risk students -->
            <li>Jacob Lopez (ADHD): Provide movement breaks.</li>
            <li>Jasper Reyes (Visual Imp.): Prepare large print materials.</li>
            <li>Hannah De Leon (Dyslexia): Use audio materials for readings.</li>
        </ul>
      </div>
    </div>

    <!-- Right Panel: Grading Queue -->
    <div>
      <div class="panel">
        <h2 style="color: #ffc107; border-bottom-color: #ffc107;">🤖 AI Grading Review Queue</h2>
        <p style="color: #6c757d;">Submissions where AI confidence is low or override is needed.</p>
        <table class="data-table" style="margin-top: 10px;">
            <thead>
                <tr><th>Submission</th><th>Student</th><th>Score</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php if ($gradingQueue): ?>
                    <?php foreach ($gradingQueue as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['Title']); ?></td>
                        <td><?php echo htmlspecialchars($item['FirstName'] . ' ' . $item['LastName']); ?></td>
                        <td style="color: #dc3545;"><?php echo round($item['SuggestedScore'], 0); ?> (<?php echo round($item['ConfidenceLevel']*100); ?>%)</td>
                        <td><a href="grading_override.php?id=<?php echo $item['SubmissionID']; ?>">Review</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No submissions requiring manual review.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>