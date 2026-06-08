<?php
// pages/Home_Page.php
session_start();
include '../models/db.php'; 

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT FirstName, LastName, UserRole FROM Teachers WHERE TeacherID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$username = htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']);
$userRole = strtoupper(htmlspecialchars($user['UserRole']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIS - Admin Portal</title>
  <link rel="stylesheet" href="../styles/homepage.css">
  <!-- Include Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-logo">
        <img src="../assets/school_pics/sagadl.png" alt="SIS Logo">
        <div class="navbar-title">Sagad High School SIS </div>
      </div>

      <div class="nav-center-title">Admin Dashboard</div>

      <!-- Profile and Settings Icons -->
      <div class="profile-settings-links">
        <a href="#" onclick="loadPage('../views/Settings.php', event)">
          <i class="fa-solid fa-gear" style="font-size: 25px; color: white;"></i>
        </a>
      <!-- Profile Icon -->
        <a href="#" onclick="loadPage('../views/Profile_Page.php', event)">
          <i class="fa-solid fa-user" style="font-size: 25px; margin-left: 10px; margin-right: 5px; color: white;"></i>
        </a>
        <!-- Settings Icon -->

      </div>
    </nav>

    <aside class="sidebar">
      <div class="sidebar-logo"><div class="profile-img"></div></div>
      <div class="username"><?php echo $username; ?></div>
      <div class="user-role"><?php echo $userRole; ?></div>

      <ul>
        <li><a href="#" onclick="loadPage('../views/dashboard.php', event)" id="dashboard-link">Dashboard</a></li>
        <li><a href="#" onclick="loadPage('../views/Teacher_Page.php', event)">Teachers</a></li>
        <li><a href="#" onclick="loadPage('../views/Students.php', event)">Students</a></li>
        <li><a href="#" onclick="loadPage('../views/Classes.php', event)">Classes</a></li>
        <li><a href="#" onclick="loadPage('../views/analytics_detail.php', event)">Report Tools</a></li>
        <li><a href="logout.php" class="signout-text" style="margin-top:20px;">Sign Out</a>

      </ul>

    </aside>

    <iframe id="dashboard-frame" src="../views/dashboard.php"></iframe>
  </div>

  <script>
    function loadPage(pageUrl, event) {
      event.preventDefault();
      document.getElementById('dashboard-frame').src = pageUrl;
      
      // Remove active class from all links
      document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));
      
      // Add active class to the clicked link
      event.target.classList.add('active');
    }

    // Set default active page (Dashboard)
    window.onload = () => {
      document.getElementById('dashboard-link').classList.add('active');
    };
  </script>
</body>
</html>
