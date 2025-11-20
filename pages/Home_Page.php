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
        <div class="username">Admin</div>
        <div class="user-role">ADMINISTRATOR</div>

        <ul>
  <ul>
    <li><a href="#" onclick="loadPage('../pages/dashboard.php', event)">Dashboard</a></li>
    <li><a href="#" onclick="loadPage('../pages/Profile_Page.php', event)">Profile</a></li>
    <li><a href="#" onclick="loadPage('../pages/Teacher_Page.php', event)">Teachers</a></li>
    <li><a href="#" onclick="loadPage('../pages/Students.php', event)">Students</a></li>
    <li><a href="#" onclick="loadPage('../pages/Classes.php', event)">Classes</a></li>
    <li><a href="#" onclick="loadPage('../pages/analytics_detail.php', event)">Report Tools</a></li>
    <li><a href="#" onclick="loadPage('../pages/Settings.php', event)">Settings</a></li>
  </ul>
        </ul>

        <div class="navbar-links-container">
          <a href="../utils/index.html" class="signout-text">Sign Out</a>
          <a href="../utils/index.html" class="signout-btn" aria-label="Sign Out">
            <img src="../assets/icon_pics/log-out.png" alt="Sign Out">
          </a>
        </div>
      </aside>

      <!-- MAIN IFRAME -->
      <iframe id="dashboard-frame" src="../pages/dashboard.php"></iframe>
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
