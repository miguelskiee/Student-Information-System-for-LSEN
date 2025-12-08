<?php
// pages/analytics_detail.php
include '../model/db.php'; 

// Example Query to fetch student data grouped by Disability for comparison
$disabilityStats = $conn->query("
    SELECT 
        s.Disability,
        COUNT(s.StudentID) AS TotalStudents,
        AVG(sph.Grade) AS AvgGPA,
        AVG(sph.AttendanceRate) AS AvgAttendanceRate
    FROM Students s
    JOIN StudentPerformanceHistory sph ON s.StudentID = sph.StudentID
    GROUP BY s.Disability
    ORDER BY TotalStudents DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Placeholder for ML Feature Importance data (should be stored in SystemJobs or a dedicated table)
$featureImportance = [
    ['Feature' => 'Grade Trend', 'Score' => 0.35],
    ['Feature' => 'Attendance Rate', 'Score' => 0.28],
    ['Feature' => 'Behavior Score', 'Score' => 0.15],
    ['Feature' => 'Grade Volatility', 'Score' => 0.10],
];
$featureLabels = json_encode(array_column($featureImportance, 'Feature'));
$featureScores = json_encode(array_column($featureImportance, 'Score'));


// 2. Disability Comparison Data (Line Chart - Avg GPA)
// This comes from the $disabilityStats query (StudentPerformanceHistory aggregated by Disability).
$disabilityLabels = json_encode(array_column($disabilityStats, 'Disability'));
$avgGPA = json_encode(array_column($disabilityStats, 'AvgGPA'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Detailed AI Analytics</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* ... (CSS styling remains the same as previous response) ... */
    body { background-color: #f4f7f9; color: #333; padding: 0 20px; font-family: sans-serif; margin-top: 10px;}
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; margin-bottom: 25px; }
    .panel { background-color: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; height: auto;}
    .panel h2 { color: #343a40; border-left: 5px solid #284ca7ff; padding-left: 20px; margin-bottom: 15px; font-size: 1.4rem; }
    .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .data-table th, .data-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
    .data-table th { background-color: #f8f9fa; color: #495057; }
    .chart-container { height: 350px; margin-bottom: 30px; }
    .panel { background-color: white; padding-left: 25px; padding-right: 25px; padding-top: 10px; padding-bottom: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }

  </style>
</head>
<body>
  <div class="header"><h1>📊 Detailed AI Analytics & Reporting</h1></div>

  <div class="panel">
    <h2>🤖 Risk Factor Importance (Random Forest Output)</h2>
    <p style="color: #6c757d;">Identifies which features are the biggest predictors of student risk.</p>
    
    <div class="chart-container">
        <canvas id="featureImportanceChart"></canvas>
    </div>
  </div>
  
  <div class="panel">
    <h2>♿ Disability Comparison: Average GPA</h2>
    <p style="color: #6c757d;">Compares average performance metrics across different LSEN groups using historical data.</p>
    
    <div class="chart-container">
        <canvas id="disabilityComparisonChart"></canvas>
    </div>

    <h3 style="margin-top: 15px; font-size: 1.1rem; color: #343a40;">Disability Metrics Table</h3>
    <table class="data-table">
        <thead>
            <tr><th>Disability</th><th>Total Students</th><th>Avg GPA (Previous Term)</th><th>Avg Attendance Rate</th></tr>
        </thead>
        <tbody>
            <?php foreach ($disabilityStats as $d): ?>
                <tr>
                    <td><?php echo htmlspecialchars($d['Disability']); ?></td>
                    <td><?php echo htmlspecialchars($d['TotalStudents']); ?></td>
                    <td><?php echo round($d['AvgGPA'], 2); ?></td>
                    <td><?php echo round($d['AvgAttendanceRate'] * 100, 1) . '%'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
  </div>

<script>
    // --------------------------------------------------------
    // CHART 1: Feature Importance (Bar Chart)
    // Data comes from Random Forest output
    // --------------------------------------------------------
    const featureLabels = <?php echo $featureLabels; ?>;
    const featureScores = <?php echo $featureScores; ?>;

    new Chart(document.getElementById('featureImportanceChart'), {
        type: 'bar',
        data: {
            labels: featureLabels,
            datasets: [{
                label: 'Prediction Weight (%)',
                data: featureScores.map(score => score * 100), // Convert to %
                backgroundColor: 'rgba(0, 123, 255, 0.8)',
                borderColor: 'rgba(0, 123, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Importance (%)' }
                },
                x: {
                    title: { display: true, text: 'ML Feature' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // --------------------------------------------------------
    // CHART 2: Disability Comparison (Line Chart)
    // Data comes from aggregated historical performance
    // --------------------------------------------------------
    const disabilityLabels = <?php echo $disabilityLabels; ?>;
    const avgGPA = <?php echo $avgGPA; ?>;

    new Chart(document.getElementById('disabilityComparisonChart'), {
        type: 'line',
        data: {
            labels: disabilityLabels,
            datasets: [{
                label: 'Average GPA',
                data: avgGPA,
                backgroundColor: 'rgba(40, 167, 69, 0.2)', // Green fill
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    max: 100,
                    title: { display: true, text: 'Average Score' }
                }
            },
            plugins: {
                title: { display: true, text: 'Avg GPA by Disability Group' }
            }
        }
    });
</script>
</body>
</html>
