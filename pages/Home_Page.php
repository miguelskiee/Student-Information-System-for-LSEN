<?php
// pages/Home_Page.php
session_start();
include '../model/db.php'; 

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
</head>
<body>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-logo">
        <img src="../assets/school_pics/sagadl.png" alt="SIS Logo">
        <div class="navbar-title">Sagad High School SIS (Admin)</div>
      </div>
    </nav>

    <aside class="sidebar">
      <div class="sidebar-logo"><div class="profile-img"></div></div>
      <div class="username"><?php echo $username; ?></div>
      <div class="user-role"><?php echo $userRole; ?></div>

      <ul>
        <li><a href="#" onclick="loadPage('../pages/dashboard.php', event)">Dashboard</a></li>
        <li><a href="#" onclick="loadPage('../pages/Profile_Page.php', event)">Profile</a></li>
        <li><a href="#" onclick="loadPage('../pages/Teacher_Page.php', event)">Teachers</a></li>
        <li><a href="#" onclick="loadPage('../pages/Students.php', event)">Students</a></li>
        <li><a href="#" onclick="loadPage('../pages/Classes.php', event)">Classes</a></li>
        <li><a href="#" onclick="loadPage('../pages/analytics_detail.php', event)">Report Tools</a></li>
        <li><a href="#" onclick="loadPage('../pages/Settings.php', event)">Settings</a></li>
      </ul>

      <div class="navbar-links-container">
        <a href="logout.php" class="signout-text">Sign Out</a>
        <a href="logout.php" class="signout-btn"><img src="../assets/icon_pics/log-out.png" alt="Sign Out"></a>
      </div>
    </aside>

    <iframe id="dashboard-frame" src="../pages/dashboard.php"></iframe>
  </div>

  <script>
    function loadPage(pageUrl, event) {
      event.preventDefault();
      document.getElementById('dashboard-frame').src = pageUrl;
      document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));
      event.target.classList.add('active');
    }
  </script>
</body>
</html>