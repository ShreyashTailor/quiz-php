<?php
session_start();
include '../db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access denied!");
}

$id = $_GET['id'];
$quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$id"));

if ($_POST) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    mysqli_query($conn, "UPDATE quizzes SET title='$title', description='$desc' WHERE id=$id");
    header("Location: manage_quizzes.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Quiz</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
  <h2>Edit Quiz</h2>
  <form method="post">
    <label>Title:</label>
    <input type="text" name="title" value="<?php echo $quiz['title']; ?>" required>
    <label>Description:</label>
    <textarea name="desc"><?php echo $quiz['description']; ?></textarea>
    <button type="submit">Update</button>
  </form>
</div>
</body>
</html>
