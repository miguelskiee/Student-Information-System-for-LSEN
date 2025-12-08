<?php
// pages/Teacher_Home_Page.php
session_start();
include '../model/db.php'; 

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teacher') {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['user_id'];

// 2. FETCH REAL USER DETAILS
$stmt = $conn->prepare("SELECT FirstName, LastName, UserRole FROM Teachers WHERE TeacherID = ?");
$stmt->execute([$userID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Fallback if user deleted but session exists
    session_destroy();
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($user['FirstName'] . ' ' . $user['LastName']);
$userRole = strtoupper(htmlspecialchars($user['UserRole']));

$navItems = [
    'Dashboard'     => 'Teacher_Dashboard.php',
    'Assignments'   => 'Teacher_Assignments.php',
    'Grades'        => 'Teacher_Grades.php',  
    'Students'      => 'Teacher_Students.php',
    'Classes'       => 'Teacher_Classes.php',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIS - Teacher Portal</title>
  <link rel="stylesheet" href="../styles/homepage.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
  <div class="container">
    <nav class="navbar">
      <div class="navbar-logo">
        <img src="../assets/school_pics/sagadl.png" alt="SIS Logo">
        <div class="navbar-title">Sagad High School SIS</div>
      </div>
     <div class="nav-center-title">Teacher Dashboard</div>

        <a href="#" onclick="loadPage('../pages/Profile_Page.php', event)">
          <i class="fa-solid fa-user" style="font-size: 25px; margin-left: 10px; margin-right: 5px; color: white;"></i>
        </a>
      </div>
    </nav>

    <aside class="sidebar">
      <div class="sidebar-logo">  
        <div class="profile-img"></div>
      </div>
      <div class="username"><?php echo $username; ?></div>
      <div class="user-role"><?php echo $userRole; ?></div>

      <ul>
        <?php foreach ($navItems as $label => $file): ?>
            <li>
              <a href="#" 
                id="<?php echo ($label === 'Dashboard') ? 'dashboard-link' : ''; ?>" 
                onclick="loadPage('../pages/<?php echo $file; ?>', event)">
                <?php echo $label; ?>
              </a>
            </li>
        <?php endforeach; ?>
      </ul>

      <div class="navbar-links-container">
        <a href="logout.php" class="signout-text">Sign Out</a>
        <a href="logout.php" class="signout-btn" aria-label="Sign Out">
          <img src="../assets/icon_pics/log-out.png" alt="Sign Out">
        </a>
      </div>
    </aside>

    <iframe id="dashboard-frame" src="../pages/Teacher_Dashboard.php"></iframe>
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