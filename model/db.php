<?php
// utils/db.php
$servername = "localhost";
$username = "root"; // Default XAMPP/WAMP user
$password = "";     // Default XAMPP/WAMP password
$dbname = "sagad_sis";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?>