<?php
// model/assignment_delete.php
session_start();
include 'db.php'; 

if (!isset($_SESSION['user_id'])) { die("Access Denied."); }
$teacherID = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $conn->prepare("DELETE FROM Assignments WHERE AssignmentID = ? AND TeacherID = ?");
        $stmt->execute([$id, $teacherID]);
        header("Location: ../pages/Teacher_Assignments.php?msg=Deleted");
    } catch (PDOException $e) {
        die("Error deleting assignment: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/Teacher_Assignments.php");
}
?>