<html>
    <head>
        <title>Sample Form</title>
</head>
<body>
<form action="" method="post">
    Name: <input type="text" name="name"><br>
    Email: <input type="text" name="email"><br>
    Password: <input type="password" name="password"><br>
    <input type="submit">
</form>
</body>
</html>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);

    //validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit;
    }
    //password atleast 6
    if (strlen($password) < 6) {
        echo "Password must be at least 6 characters long";
        exit;
    }
}