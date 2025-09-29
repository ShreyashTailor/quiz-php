<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$quiz_id = $_GET['quiz_id'];

// Get quiz details
$quiz_result = mysqli_query($conn, "SELECT * FROM quizzes WHERE id = $quiz_id");
$quiz = mysqli_fetch_assoc($quiz_result);

// Check if user owns this quiz or is admin
if ($quiz['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['question'])) {
        // Adding a question
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $d = mysqli_real_escape_string($conn, $_POST['option_d']);
        $correct = $_POST['correct'];

        mysqli_query($conn, "INSERT INTO questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) 
            VALUES ('$quiz_id','$question','$a','$b','$c','$d','$correct')");
            
        $success_message = "Question added successfully!";
    }
    
    if (isset($_POST['finish_quiz'])) {
        // Finishing the quiz - redirect to success page
        header("Location: quiz_submitted.php?quiz_id=$quiz_id");
        exit;
    }
}

// Get existing questions for this quiz
$questions_result = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id");
$question_count = mysqli_num_rows($questions_result);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Add Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Navigation -->
  <div class="navbar">
    <div class="nav-left">
      <h1><a href="index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
    </div>
    <div class="nav-right">
      <?php if (isset($_SESSION['user_id'])): ?>
        <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
        <a href="logout.php" class="nav-btn logout-btn">Logout</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="container">
    <div class="quiz-creation-header">
      <h2>📝 Adding Questions to: <span class="quiz-title"><?php echo htmlspecialchars($quiz['title']); ?></span></h2>
      <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
      <div class="question-counter">
        <span class="counter-badge">Questions Added: <?php echo $question_count; ?></span>
        <?php if ($quiz['status'] === 'pending'): ?>
          <span class="status-badge pending">⏳ Pending Approval</span>
        <?php elseif ($quiz['status'] === 'approved'): ?>
          <span class="status-badge approved">✅ Approved</span>
        <?php endif; ?>
      </div>
    </div>

    <?php if (isset($success_message)): ?>
      <div class="success-message">
        <p>✅ <?php echo $success_message; ?></p>
      </div>
    <?php endif; ?>

    <!-- Add Question Form -->
    <div class="question-form-section">
      <h3>➕ Add New Question</h3>
      <form method="post" class="question-form">
        <div class="form-group">
          <label>Question:</label>
          <textarea name="question" required placeholder="Enter your question here..."></textarea>
        </div>
        
        <div class="options-grid">
          <div class="option-group">
            <label>Option A:</label>
            <input type="text" name="option_a" required placeholder="Enter option A">
          </div>
          <div class="option-group">
            <label>Option B:</label>
            <input type="text" name="option_b" required placeholder="Enter option B">
          </div>
          <div class="option-group">
            <label>Option C:</label>
            <input type="text" name="option_c" required placeholder="Enter option C">
          </div>
          <div class="option-group">
            <label>Option D:</label>
            <input type="text" name="option_d" required placeholder="Enter option D">
          </div>
        </div>
        
        <div class="form-group">
          <label>Correct Option:</label>
          <select name="correct" required>
            <option value="">Select correct answer</option>
            <option value="A">A</option>
            <option value="B">B</option>
            <option value="C">C</option>
            <option value="D">D</option>
          </select>
        </div>
        
        <button type="submit" class="btn-primary">➕ Add Question</button>
      </form>
    </div>

    <!-- Existing Questions Preview -->
    <?php if ($question_count > 0): ?>
      <div class="questions-preview">
        <h3>📋 Questions Added (<?php echo $question_count; ?>)</h3>
        <div class="questions-list">
          <?php 
          $q_num = 1;
          mysqli_data_seek($questions_result, 0);
          while ($q = mysqli_fetch_assoc($questions_result)): 
          ?>
            <div class="question-preview">
              <h4>Q<?php echo $q_num; ?>: <?php echo htmlspecialchars($q['question']); ?></h4>
              <div class="options-preview">
                <span class="option <?php echo ($q['correct_option'] === 'A') ? 'correct' : ''; ?>">A) <?php echo htmlspecialchars($q['option_a']); ?></span>
                <span class="option <?php echo ($q['correct_option'] === 'B') ? 'correct' : ''; ?>">B) <?php echo htmlspecialchars($q['option_b']); ?></span>
                <span class="option <?php echo ($q['correct_option'] === 'C') ? 'correct' : ''; ?>">C) <?php echo htmlspecialchars($q['option_c']); ?></span>
                <span class="option <?php echo ($q['correct_option'] === 'D') ? 'correct' : ''; ?>">D) <?php echo htmlspecialchars($q['option_d']); ?></span>
              </div>
            </div>
          <?php 
          $q_num++;
          endwhile; 
          ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-section">
      <?php if ($question_count >= 1): ?>
        <form method="post" style="display: inline;">
          <button type="submit" name="finish_quiz" class="btn-success">✅ Finish Quiz Creation</button>
        </form>
      <?php else: ?>
        <p class="requirement-note">⚠️ Please add at least 1 question before finishing the quiz.</p>
      <?php endif; ?>
      
      <?php if ($quiz['status'] === 'approved'): ?>
        <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn-secondary">👀 Preview Quiz</a>
      <?php endif; ?>
      
      <a href="index.php" class="btn-tertiary">🏠 Back to Home</a>
    </div>
  </div>
</body>
</html>
