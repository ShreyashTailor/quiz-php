<?php
session_start();
include 'db.php';

// Check if user is logged in - mandatory for taking quizzes
if (!isset($_SESSION['user_id'])) {
    // Redirect to login with return URL
    $current_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: login.php?redirect=" . $current_url);
    exit;
}

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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($quiz['title']); ?> - QuizMaster</title>
  <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">QuizMaster</a>
      <div class="nav-links">
        <a href="index.php" class="nav-link">← Back to Home</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <!-- Quiz Header -->
      <div class="card mb-6">
        <div class="card-header text-center">
          <h1 class="card-title text-2xl"><?php echo htmlspecialchars($quiz['title']); ?></h1>
          <p class="card-description"><?php echo htmlspecialchars($quiz['description'] ?? 'Test your knowledge with this quiz!'); ?></p>
        </div>
      </div>

      <?php if (mysqli_num_rows($questions) == 0) { ?>
        <div class="card text-center">
          <div class="card-content p-6">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-lg font-semibold mb-2">No Questions Available</h3>
            <p class="text-muted-foreground mb-4">This quiz doesn't have any questions yet.</p>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
          </div>
        </div>
      <?php } else { ?>
        <!-- Quiz Questions -->
        <form method="post" action="submit.php?id=<?php echo $quiz_id; ?>" id="quizForm">
          <?php 
          $question_number = 1;
          while ($q = mysqli_fetch_assoc($questions)) { ?>
            <div class="card question-card mb-6">
              <div class="card-header">
                <h3 class="card-title">Question <?php echo $question_number; ?></h3>
                <p class="text-lg"><?php echo htmlspecialchars($q['question']); ?></p>
              </div>
              <div class="card-content">
                <div class="space-y-3">
                  <label class="option-button" for="q<?php echo $q['id']; ?>_a">
                    <input type="radio" name="ans[<?php echo $q['id']; ?>]" value="A" id="q<?php echo $q['id']; ?>_a" class="mr-3">
                    <span>A) <?php echo htmlspecialchars($q['option_a']); ?></span>
                  </label>
                  <label class="option-button" for="q<?php echo $q['id']; ?>_b">
                    <input type="radio" name="ans[<?php echo $q['id']; ?>]" value="B" id="q<?php echo $q['id']; ?>_b" class="mr-3">
                    <span>B) <?php echo htmlspecialchars($q['option_b']); ?></span>
                  </label>
                  <label class="option-button" for="q<?php echo $q['id']; ?>_c">
                    <input type="radio" name="ans[<?php echo $q['id']; ?>]" value="C" id="q<?php echo $q['id']; ?>_c" class="mr-3">
                    <span>C) <?php echo htmlspecialchars($q['option_c']); ?></span>
                  </label>
                  <label class="option-button" for="q<?php echo $q['id']; ?>_d">
                    <input type="radio" name="ans[<?php echo $q['id']; ?>]" value="D" id="q<?php echo $q['id']; ?>_d" class="mr-3">
                    <span>D) <?php echo htmlspecialchars($q['option_d']); ?></span>
                  </label>
                </div>
              </div>
            </div>
          <?php 
          $question_number++;
          } ?>
          
          <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">Submit Quiz</button>
            <p class="text-sm text-muted-foreground mt-2">Make sure to answer all questions before submitting</p>
          </div>
        </form>
      <?php } ?>
    </div>
  </main>

  <script src="dark-mode.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add interactive option selection
      const optionButtons = document.querySelectorAll('.option-button');
      
      optionButtons.forEach(button => {
        button.addEventListener('click', function() {
          const radio = this.querySelector('input[type="radio"]');
          const questionName = radio.name;
          
          // Remove selected class from all options in this question
          document.querySelectorAll(`input[name="${questionName}"]`).forEach(r => {
            r.closest('.option-button').classList.remove('selected');
          });
          
          // Add selected class to clicked option
          this.classList.add('selected');
        });
      });
      
      // Form validation
      document.getElementById('quizForm').addEventListener('submit', function(e) {
        const questions = document.querySelectorAll('.question-card');
        let allAnswered = true;
        
        questions.forEach(question => {
          const radios = question.querySelectorAll('input[type="radio"]');
          const answered = Array.from(radios).some(radio => radio.checked);
          
          if (!answered) {
            allAnswered = false;
            question.style.borderColor = 'hsl(var(--destructive))';
            question.style.backgroundColor = 'hsl(var(--destructive) / 0.1)';
          } else {
            question.style.borderColor = '';
            question.style.backgroundColor = '';
          }
        });
        
        if (!allAnswered) {
          e.preventDefault();
          alert('Please answer all questions before submitting the quiz.');
        }
      });
    });
  </script>
</body>
</html>
