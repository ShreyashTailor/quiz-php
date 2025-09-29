<?php
// Database configuration
$servername = "127.0.0.1";
$username = "root";
$password = "";  // no password
$database = "user_db";
$port = 3306;

// Create connection
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = $_POST['username'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash password

    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $user, $email, $pass);

    if ($stmt->execute()) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <style>
        body { font-family: Arial; background: #f2f2f2; }
        .container { width: 400px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0px 0px 10px #aaa; }
        input[type=text], input[type=email], input[type=password] { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        input[type=submit] { padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; }
        input[type=submit]:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <form action="register.php" method="post">
            <label>Username:</label>
            <input type="text" name="username" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>

