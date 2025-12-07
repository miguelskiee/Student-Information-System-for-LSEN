<?php
// pages/log_behavior.php
include '../model/db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $behavior_type = $_POST['behavior_type'];
    $record_date = $_POST['record_date'];
    $description = $_POST['description'];
    
    // MAPPING: Define Category and Risk/Support Points
    $behavior_map = [
        // --- CHALLENGING BEHAVIORS (High Risk/Needs Support) ---
        'Aggression'       => ['Negative', 5], 
        'Self-Injury'      => ['Negative', 5], 
        'Elopement'        => ['Negative', 5], 
        'Property Damage'  => ['Negative', 4], 
        'Meltdown'         => ['Negative', 3], 
        'Non-Compliance'   => ['Negative', 2], 
        'Social Withdrawal'=> ['Negative', 2], 
        
        // --- ADAPTIVE BEHAVIORS (Reduces Risk/Positive Progress) ---
        'Self-Advocacy'    => ['Positive', -5], 
        'Self-Regulation'  => ['Positive', -4], 
        'Skill Mastery'    => ['Positive', -3], 
        'Social Initiation'=> ['Positive', -3], 
        'Task Completion'  => ['Positive', -2], 
        'Peer Support'     => ['Positive', -2]  
    ];

    if (isset($behavior_map[$behavior_type])) {
        $category = $behavior_map[$behavior_type][0];
        $points = $behavior_map[$behavior_type][1];

        try {
            // Note: We insert into the new table which uses snake_case column names
            $stmt = $conn->prepare("INSERT INTO behavior_records (student_id, record_date, behavior_type, category, description, points) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $record_date, $behavior_type, $category, $description, $points]);
            $message = "✅ Behavior logged successfully: <strong>$behavior_type</strong>";
        } catch (PDOException $e) {
            $message = "❌ Database Error: " . $e->getMessage();
        }
    } else {
        $message = "❌ Error: Invalid Behavior Type selected.";
    }
}

// Fetch Students - CORRECTED COLUMN NAMES based on your DESC output
$students = $conn->query("SELECT StudentID, FirstName, LastName FROM students ORDER BY LastName ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Log Behavior (PWD Context)</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f9; padding: 20px; }
        .form-container { max-width: 600px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        select, input, textarea, button { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #4e73df; color: white; border: none; cursor: pointer; font-weight: bold; font-size: 16px; }
        button:hover { background-color: #2e59d9; }
        .alert { padding: 15px; margin-bottom: 20px; background: #d4edda; color: #155724; border-radius: 4px; border: 1px solid #c3e6cb; }
        optgroup { font-weight: bold; color: #333; }
        option { padding: 5px; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>📋 Log Behavior (Inclusive)</h2>
    
    <?php if ($message): ?>
        <div class="alert"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Select Student:</label>
        <select name="student_id" required>
            <option value="">-- Choose Student --</option>
            <?php foreach ($students as $s): ?>
                <option value="<?php echo $s['StudentID']; ?>">
                    <?php echo htmlspecialchars($s['LastName'] . ', ' . $s['FirstName']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Date:</label>
        <input type="date" name="record_date" required value="<?php echo date('Y-m-d'); ?>">

        <label>Behavior Type:</label>
        <select name="behavior_type" required>
            <option value="">-- Select Observation --</option>
            
            <optgroup label="🔴 High Needs / Challenging">
                <option value="Aggression">Aggression (Physical/Verbal)</option>
                <option value="Self-Injury">Self-Injurious Behavior (SIB)</option>
                <option value="Elopement">Elopement (Wandering/Running)</option>
                <option value="Property Damage">Property Damage</option>
                <option value="Meltdown">Meltdown / Sensory Overload</option>
                <option value="Non-Compliance">Non-Compliance / Refusal</option>
                <option value="Social Withdrawal">Social Withdrawal / Shut Down</option>
            </optgroup>

            <optgroup label="🟢 Adaptive / Positive Progress">
                <option value="Self-Advocacy">Self-Advocacy (Communicated Needs)</option>
                <option value="Self-Regulation">Self-Regulation (Used Coping Skills)</option>
                <option value="Social Initiation">Positive Social Initiation</option>
                <option value="Task Completion">Task Completion (Goal Met)</option>
                <option value="Skill Mastery">New Skill Mastery</option>
                <option value="Peer Support">Peer Support / Empathy</option>
            </optgroup>
        </select>

        <label>Triggers / Antecedents / Context:</label>
        <textarea name="description" rows="4" placeholder="What happened before? What was the result? (e.g., 'Classroom was loud, student asked for headphones instead of yelling.')"></textarea>

        <button type="submit">Save Record</button>
    </form>
</div>

</body>
</html>