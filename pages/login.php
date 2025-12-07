<?php
// pages/login.php
session_start();
include '../model/db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? ''; 

    // 1. UPDATED QUERY: Added 'Password' to the SELECT list
    $stmt = $conn->prepare("SELECT TeacherID, FirstName, LastName, UserRole, Password FROM Teachers WHERE Email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. UPDATED CHECK: Verify the password matches
    // (This uses simple text comparison. For better security later, use password_verify)
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
        $error = "Invalid email or password.";
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
    .form-container {
      margin-top: 0; /* Override the margin from login.css to center it */
    }
    .error-msg {
      color: #dc3545;
      margin-bottom: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="form-container">
    <div class="form-box">
      <img src="../assets/school_pics/sagadl.png" alt="Logo" style="width: 80px; margin-bottom: 15px;">
      
      <h2>Welcome Back</h2>
      
      <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" style="background-color: #004080; color: white; padding: 10px; width: 100%; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem;">
          Login
        </button>
      </form>
      
      <p style="margin-top: 15px; font-size: 0.9rem;">
      </p>
    </div>
  </div>

</body>
</html>