<?php
include 'db.php';

if ($_POST) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // simple hash for demo (use bcrypt in real apps)

    $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        echo "Username already exists!";
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username','$password','user')");
        echo "Registration successful! <a href='login.php'>Login here</a>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h2>User Registration</h2>
  <form method="post">
    <label>Username:</label>
    <input type="text" name="username" required>
    <label>Password:</label>
    <input type="password" name="password" required>
    <button type="submit">Register</button>
  </form>
</div>
</body>
</html>
