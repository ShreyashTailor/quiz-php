<?php
session_start();
include 'db.php';

$quiz_id = $_GET['id'];
$answers = $_POST['ans'];
$score = 0;

foreach ($answers as $qid => $ans) {
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT correct_option FROM questions WHERE id=$qid"));
    if ($q['correct_option'] == $ans) {
        $score++;
    }
}
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id"));

// Get quiz details
$quiz_result = mysqli_query($conn, "SELECT title FROM quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($quiz_result);

// Save score to leaderboard if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;
    $completion_time = date('Y-m-d H:i:s');
    
    // Insert score into leaderboard table
    $insert_query = "INSERT INTO leaderboard (user_id, username, quiz_id, quiz_title, score, total_questions, percentage, completion_time) 
                     VALUES ('$user_id', '$username', '$quiz_id', '" . mysqli_real_escape_string($conn, $quiz['title']) . "', '$score', '$total', '$percentage', '$completion_time')";
    mysqli_query($conn, $insert_query);
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Quiz Result</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
  <div class="result-card">
    <h2>🎉 Quiz Completed!</h2>
    <div class="score-display">
      <h3>Your Score: <span class="score"><?php echo $score; ?>/<?php echo $total; ?></span></h3>
      <div class="percentage">
        <?php 
        $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;
        echo $percentage . "%";
        ?>
      </div>
    </div>
    
    <div class="performance-message">
      <?php
      if ($percentage >= 90) {
          echo "🌟 Excellent! Outstanding performance!";
      } elseif ($percentage >= 75) {
          echo "👏 Great job! Well done!";
      } elseif ($percentage >= 60) {
          echo "👍 Good work! Keep it up!";
      } elseif ($percentage >= 40) {
          echo "📚 Not bad! Keep practicing!";
      } else {
          echo "💪 Keep trying! Practice makes perfect!";
      }
      ?>
    </div>
    
    <?php if (isset($_SESSION['user_id'])): ?>
      <p class="save-notice">✅ Your score has been saved to the leaderboard!</p>
    <?php else: ?>
      <p class="login-notice">💡 <a href="login.php">Login</a> to save your score to the leaderboard!</p>
    <?php endif; ?>
    
    <div class="action-buttons">
      <a href="index.php" class="btn-primary">🏠 Back to Home</a>
      <a href="leaderboard.php" class="btn-secondary">🏆 View Leaderboard</a>
      <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn-tertiary">🔄 Retake Quiz</a>
    </div>
  </div>
</div>
</body>
</html>
