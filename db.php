<?php
$host="db.fr-pari1.bengt.wasmernet.com";
$port = 10272
$username="a3f228567afe80000576f53a820d";
$pass = $_ENV['pass'];
$db = "dbGFjXeeTfzoBSkj45iZMzKb";

$conn = mysqli_connect($host, $username, $pass, $db, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
