<?php
session_start();
include '../db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access denied!");
}

$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM quizzes WHERE id=$id");
header("Location: manage_quizzes.php");
exit;
?>
