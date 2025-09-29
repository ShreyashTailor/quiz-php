<?php
$servername = "localhost";
$username = "root";  // change if needed
$password = "";      // change if needed
$dbname = "quiz_app";

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
