<?php
// pages/Profile_Page.php (REVISED WITH ERROR HANDLING)
include '../model/db.php'; 

// --- User Simulation ---
$adminID = 5; // Simulating the logged-in Admin's ID (expected to be the new user)

// --- Database Query ---
// Initialize $profile to an empty array for safety
$profile = []; 
try {
    $stmt = $conn->prepare("SELECT * FROM Teachers WHERE TeacherID = ?");
    $stmt->execute([$adminID]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error but continue to display a non-breaking page
    error_log("Profile Query Failed: " . $e->getMessage());
}

// --- Variable Fallbacks ---
// If the user was not found ($profile is false or empty), set safe defaults
if (!$profile) {
    $profile = [
        'FirstName' => 'User Not Found',
        'LastName' => '',
        'Email' => 'N/A',
        'Phone' => 'N/A',
        'UserRole' => 'UNKNOWN',
        'Specializations' => 'N/A',
        'Certifications' => 'N/A',
        'IsSpecialEdCertified' => 0
    ];
}

// Get the user role safely
$userRole = $profile['UserRole'] ?? 'UNKNOWN';

// Helper function to safely access array data
function display_profile_data($array, $key, $default = 'N/A') {
    return htmlspecialchars($array[$key] ?? $default);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Profile</title>
  <style>
    body { background-color: #f4f7f9; color: #333; padding: 20px; }
    .header h1 { color: #007bff; border-bottom: 2px solid #dee2ee; padding-bottom: 10px; margin-bottom: 25px; }
    .content-container { max-width: 800px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
    h2 { color: #343a40; margin-top: 20px; border-left: 5px solid #007bff; padding-left: 10px; }
    .profile-card p { margin: 10px 0; font-size: 1rem; }
    .profile-card strong { color: #6c757d; display: inline-block; width: 150px; }
    .edit-btn { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin-top: 20px; }
    .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="header"><h1>👤 <?php echo htmlspecialchars(strtoupper($userRole)); ?> Profile</h1></div>
  <div class="content-container">

    <?php if ($userRole == 'UNKNOWN'): ?>
        <div class="error-message">
            <strong>Database Error:</strong> User ID 5 could not be found. Please ensure the Admin user was inserted into the Teachers table.
        </div>
    <?php endif; ?>

    <h2>Account Information</h2>
    <div class="profile-card">
      <p><strong>Name:</strong> <?php echo display_profile_data($profile, 'FirstName') . ' ' . display_profile_data($profile, 'LastName'); ?></p>
      <p><strong>Role:</strong> <?php echo display_profile_data($profile, 'UserRole', 'UNKNOWN'); ?></p>
      <p><strong>Email:</strong> <?php echo display_profile_data($profile, 'Email'); ?></p>
      <p><strong>Phone:</strong> <?php echo display_profile_data($profile, 'Phone'); ?></p>
    </div>

    <h2>Professional Data</h2>
    <div class="profile-card">
      <p><strong>Specializations:</strong> <?php echo display_profile_data($profile, 'Specializations'); ?></p>
      <p><strong>SPED Certified:</strong> 
          <?php 
            echo ($profile['IsSpecialEdCertified'] ?? 0) ? '✅ Yes' : '❌ No'; 
          ?>
      </p>
      <p><strong>Training & Certifications:</strong> <?php echo nl2br(display_profile_data($profile, 'Certifications')); ?></p>
    </div>
    
    <button class="edit-btn">Edit Profile</button>
  </div>
</body>
</html>