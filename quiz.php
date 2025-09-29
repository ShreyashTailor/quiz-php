<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("Quiz ID not provided.");
}

$quiz_id = (int) $_GET['id'];  // typecast to int to avoid SQL injection

$result = mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($result);

if (!$quiz) {
    die("Quiz not found! Please create a quiz first.");
}

$questions = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id");
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo htmlspecialchars($quiz['title']); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <h2><?php echo htmlspecialchars($quiz['title']); ?></h2>
  <p><?php echo htmlspecialchars($quiz['description']); ?></p>
  
  <?php if (mysqli_num_rows($questions) == 0) { ?>
      <p>No questions added yet for this quiz.</p>
      <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>">Add Questions</a>
  <?php } else { ?>
      <form method="post" action="submit.php?id=<?php echo $quiz_id; ?>">
        <?php while ($q = mysqli_fetch_assoc($questions)) { ?>
          <div class="question">
            <p><b><?php echo htmlspecialchars($q['question']); ?></b></p>
            <label><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="A"> <?php echo htmlspecialchars($q['option_a']); ?></label><br>
            <label><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="B"> <?php echo htmlspecialchars($q['option_b']); ?></label><br>
            <label><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="C"> <?php echo htmlspecialchars($q['option_c']); ?></label><br>
            <label><input type="radio" name="ans[<?php echo $q['id']; ?>]" value="D"> <?php echo htmlspecialchars($q['option_d']); ?></label><br>
          </div>
        <?php } ?>
        <button type="submit">Submit Quiz</button>
      </form>
  <?php } ?>
</div>
</body>
</html>
