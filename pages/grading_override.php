<?php
// pages/grading_override.php
include '../model/db.php'; 

$teacherID = 1; // Simulated logged-in TeacherID

// Fetch submissions requiring review for the teacher
$reviewQueueStmt = $conn->prepare("
    SELECT 
        s.SubmissionID, s.SubmissionText, s.SubmittedAt,
        stud.FirstName, stud.LastName, stud.Disability,
        ass.Title, ass.MaxScore, 
        aigr.AIGradeID, aigr.SuggestedScore, aigr.AIComments, aigr.ConfidenceLevel
    FROM StudentSubmissions s
    JOIN Assignments ass ON s.AssignmentID = ass.AssignmentID
    JOIN AI_GradingResults aigr ON s.SubmissionID = aigr.SubmissionID
    JOIN Students stud ON s.StudentID = stud.StudentID
    WHERE ass.TeacherID = ? AND aigr.ConfidenceLevel < 0.80 AND aigr.TeacherOverrideScore IS NULL
    ORDER BY s.SubmittedAt ASC
    LIMIT 10
");
$reviewQueueStmt->execute([$teacherID]);
$submissions = $reviewQueueStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI Grading Review Queue</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px; margin-bottom: 25px; }
    .queue-panel { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }
    .submission-card { border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 6px; }
    .submission-card h3 { color: #007bff; margin-top: 0; }
    .ai-score-box { background-color: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
    .override-form input[type="number"], .override-form textarea { padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; margin-top: 5px; }
    .submit-btn { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 15px; }
    .confidence-low { color: #dc3545; font-weight: bold; }
  </style>
</head>
<body>
  <div class="header"><h1>🤖 AI Grading Review Queue</h1></div>
  <p style="margin-bottom: 20px; color: #6c757d;">Showing submissions where the AI Confidence Level is below 80\%. Please review and apply an override score if necessary.</p>

  <div class="queue-panel">
    <?php if ($submissions): ?>
        <?php foreach ($submissions as $sub): ?>
            <div class="submission-card">
                <h3><?php echo htmlspecialchars($sub['Title']); ?> by <?php echo htmlspecialchars($sub['FirstName'] . ' ' . $sub['LastName']); ?> (Disability: <?php echo htmlspecialchars($sub['Disability']); ?>)</h3>
                
                <div class="ai-score-box">
                    <strong>AI Suggested Score:</strong> <?php echo htmlspecialchars($sub['SuggestedScore']); ?> / <?php echo htmlspecialchars($sub['MaxScore']); ?> <br>
                    <strong class="confidence-low">AI Confidence:</strong> <?php echo round($sub['ConfidenceLevel'] * 100); ?>\% <br>
                    <strong>AI Feedback:</strong> <?php echo nl2br(htmlspecialchars($sub['AIComments'])); ?>
                </div>

                <p style="font-weight: bold;">Student Submission Text:</p>
                <div style="border: 1px solid #eee; padding: 10px; background: #fff; max-height: 150px; overflow-y: auto; margin-bottom: 15px;">
                    <?php echo nl2br(htmlspecialchars($sub['SubmissionText'])); ?>
                </div>

                <form action="../model/override_submit.php" method="POST" class="override-form">
                    <input type="hidden" name="ai_grade_id" value="<?php echo $sub['AIGradeID']; ?>">
                    
                    <label>Teacher Override Score (<?php echo $sub['MaxScore']; ?> Max)</label>
                    <input type="number" name="override_score" min="0" max="<?php echo $sub['MaxScore']; ?>" step="0.1" required>
                    
                    <label>Teacher Comments / Justification</label>
                    <textarea name="teacher_comments" rows="2" required></textarea>
                    
                    <button type="submit" class="submit-btn">Apply Override Score</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #28a745; font-size: 1.1rem; font-weight: bold;">✅ The AI Review Queue is currently empty! All low-confidence submissions have been handled.</p>
    <?php endif; ?>
  </div>
</body>
</html>