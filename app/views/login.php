<?php
// pages/login.php
session_start();
include '../models/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? ''; 

    $stmt = $conn->prepare("SELECT TeacherID, FirstName, LastName, UserRole, Password FROM Teachers WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['Password'] === $password) {
        
        // LOGIN SUCCESS
        $_SESSION['user_id'] = $user['TeacherID'];
        $_SESSION['user_name'] = $user['FirstName'] . ' ' . $user['LastName'];
        $_SESSION['role'] = $user['UserRole'];

        if ($user['UserRole'] === 'Admin') {
            header("Location: Home_Page.php");
        } else {
            header("Location: Teacher_Home_Page.php");
        }
        exit;
    } else {
        $error = "Incorrect Email or Password";  
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sagad High School - Login</title>
  <link rel="stylesheet" href="../styles/index.css">
  <link rel="stylesheet" href="../styles/login.css">
  <style>
    /* Slight overrides to center the box perfectly on a full page */
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }
    .error-line {
      height: 1.5px;
      background-color: #353ddcff;
      margin-bottom: 20px;
    }

    .error-text {
      display: none; 
      color: #dc3545;
      font-weight: 600;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

    <div class="form-box">
      <img src="../assets/school_pics/sagadl.png" alt="Logo" style="width: 18vh; margin-bottom: 15px;">
      
      <h2>Sagad High School</h2>
      <!-- Line under the heading -->
      <div class="error-line"></div>
      
      <!-- Dynamic error message area -->
      <div id="error-message" class="error-text">
        <?php echo $error; ?>
      </div>
      
      <form method="POST" action="">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" style="margin-top: 10px;background-color: #004080; color: white; padding: 10px; width: 100%; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem;">
          Login
        </button>
      </form>
      
      <p style="margin-top: 15px; font-size: 0.9rem;">
      </p>
    </div>

    <script>
      <?php if ($error): ?>
        // If there's an error, display the error message and remove it after 5 seconds
        const errorMessage = document.getElementById('error-message');
        errorMessage.style.display = 'block';
        setTimeout(() => {
          errorMessage.style.display = 'none';
        }, 5000);  // Hide error after 5 seconds
      <?php endif; ?>
    </script>

</body>
</html>
