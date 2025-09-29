<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$database = "student_db";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $age    = $_POST['age'];
    $course = $_POST['course'];

    $sql = "INSERT INTO students (name, email, age, course) 
            VALUES ('$name', '$email', '$age', '$course')";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>Student registered successfully!</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Registration Form</title>
</head>
<body style="font-family: Arial; margin: 50px;">
    <h2>Student Registration Form</h2>
    <form method="POST" action="">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Age:</label><br>
        <input type="number" name="age" required><br><br>

        <label>Course:</label><br>
        <input type="text" name="course" required><br><br>

        <input type="submit" value="Register">
    </form>
</body>
</html>
