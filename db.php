<?php
$host = "db.fr-pari1.bengt.wasmernet.com";
$port = 10272; 
$username = "a3f228567afe80000576f53a820d";
$pass = "068da3f2-2856-7c5a-8000-cff47e0a369f";
$db = "dbGFjXeeTfzoBSkj45iZMzKb";

$conn = mysqli_connect($host, $username, $pass, $db, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
