<?php
// pages/Teacher_Home_Page.php
// The main application shell for the Teacher role.

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (file_exists('../model/db.php')) {
    include '../model/db.php'; 
} else {
    die("Error: Database connection file '../model/db.php' not found.");
}

// --- SIMULATED LOGIN ---
$userID = 1; // Assuming TeacherID 1 (Anna Soriano) is logged in.
$stmt = $conn->prepare("SELECT FirstName, LastName, UserRole, IsSpecialEdCertified FROM Teachers WHERE TeacherID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $user = ['FirstName' => 'Guest', 'LastName' => 'Teacher', 'UserRole' => 'TEACHER', 'IsSpecialEdCertified' => 0];
}

$username = htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']);
$userRole = strtoupper(htmlspecialchars($user['UserRole'] ?? 'Teacher'));

$navItems = [
    'Dashboard'     => 'Teacher_Dashboard.php',
    'Students'      => 'Teacher_Students.php',
    'Classes'       => 'Teacher_Classes.php',
    'Profile'       => 'Profile_Page.php', // Reuses the existing profile page
];
?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIS for Learners with Special Needs</title>
    <link rel="stylesheet" href="../styles/homepage.css">
  </head>
  <body>
    <div class="container">
      <!-- NAVBAR -->
      <nav class="navbar">
        <div class="navbar-logo">
          <img src="../assets/school_pics/sagadl.png" alt="SIS Logo">
          <div class="navbar-title">Sagad High School Student Information System for LSEN</div>
        </div>
        <div class="navbar-links"></div>
      </nav>

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-logo">  
          <div class="profile-img"></div>
        </div>
        <div class="username"><?php echo $username; ?></div>
        <div class="user-role"><?php echo $userRole; ?></div>

      <ul>
        <?php foreach ($navItems as $label => $file): ?>
            <li><a href="#" onclick="loadPage('../pages/<?php echo $file; ?>', event)"><?php echo $label; ?></a></li>
        <?php endforeach; ?>
      </ul>

        <div class="navbar-links-container">
          <a href="../utils/index.html" class="signout-text">Sign Out</a>
          <a href="../utils/index.html" class="signout-btn" aria-label="Sign Out">
            <img src="../assets/icon_pics/log-out.png" alt="Sign Out">
          </a>
        </div>
      </aside>

      <!-- MAIN IFRAME -->
      <iframe id="dashboard-frame" src="../pages/Teacher_Dashboard.php"></iframe>
    </div>

    <!-- SCRIPT TO SWITCH IFRAME PAGES -->
    <script>
      function loadPage(pageUrl, event) {
        event.preventDefault();
        const iframe = document.getElementById('dashboard-frame');
        iframe.src = pageUrl;

        // Highlight active link
        const links = document.querySelectorAll('.sidebar a');
        links.forEach(link => link.classList.remove('active'));
        event.target.classList.add('active');
      }
    </script>
  </body>
  </html>