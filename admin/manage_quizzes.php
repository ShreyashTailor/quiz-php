<?php
session_start();
include '../db.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    die("Access denied!");
}

$quizzes = mysqli_query($conn, "SELECT * FROM quizzes ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Quizzes</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<div class="container">
  <h2>Manage Quizzes</h2>
  <table border="1" width="100%" cellpadding="10">
    <tr>
      <th>ID</th>
      <th>Title</th>
      <th>Description</th>
      <th>Actions</th>
    </tr>
    <?php while ($q = mysqli_fetch_assoc($quizzes)) { ?>
      <tr>
        <td><?php echo $q['id']; ?></td>
        <td><?php echo htmlspecialchars($q['title']); ?></td>
        <td><?php echo htmlspecialchars($q['description']); ?></td>
        <td>
          <a href="edit_quiz.php?id=<?php echo $q['id']; ?>">Edit</a> | 
          <a href="delete_quiz.php?id=<?php echo $q['id']; ?>" onclick="return confirm('Delete quiz?')">Delete</a>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>
</body>
</html>
