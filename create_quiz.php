<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_POST) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $created_by = $_SESSION['user_id'];
    $creator_name = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Set status based on user role
    $status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'approved' : 'pending';

    mysqli_query($conn, "INSERT INTO quizzes (title, description, created_by, creator_name, status) 
                        VALUES ('$title','$desc','$created_by','$creator_name','$status')");

    $quiz_id = mysqli_insert_id($conn);

    header("Location: add_questions.php?quiz_id=$quiz_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Create Quiz - QuizMaster</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Navigation -->
  <div class="navbar">
    <div class="nav-left">
      <h1><a href="index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
    </div>
    <div class="nav-right">
      <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
      <a href="logout.php" class="nav-btn logout-btn">Logout</a>
    </div>
  </div>

  <div class="container">
    <div class="create-quiz-header">
      <h2>📝 Create a New Quiz</h2>
      <p>Choose how you'd like to create your quiz</p>
    </div>

    <div class="quiz-creation-options">
      <!-- Manual Quiz Creation -->
      <div class="creation-option">
        <div class="option-header">
          <div class="option-icon">✍️</div>
          <h3>Manual Quiz Creation</h3>
          <p>Create quiz questions manually with full control</p>
        </div>
        
        <form method="post" class="manual-form">
          <div class="form-group">
            <label>Quiz Title:</label>
            <input type="text" name="title" required placeholder="Enter your quiz title...">
          </div>
          
          <div class="form-group">
            <label>Description:</label>
            <textarea name="desc" placeholder="Describe what your quiz is about..."></textarea>
          </div>
          
          <button type="submit" class="btn-primary">✍️ Create Manual Quiz</button>
        </form>
      </div>

      <!-- AI Quiz Creation -->
      <div class="creation-option ai-option">
        <div class="option-header">
          <div class="option-icon">🤖</div>
          <h3>Gemini AI Quiz Creator</h3>
          <p>Upload a PDF and let AI generate quiz questions automatically</p>
        </div>
        
        <div class="ai-features">
          <ul>
            <li>✨ Automatically extract content from PDF</li>
            <li>🧠 AI-powered question generation</li>
            <li>⚡ Fast and intelligent quiz creation</li>
            <li>📚 Perfect for study materials and documents</li>
          </ul>
        </div>
        
        <a href="ai_quiz_creator.php" class="btn-ai">
          🤖 Create AI Quiz from PDF
        </a>
      </div>
    </div>

    <div class="back-action">
      <a href="index.php" class="btn-tertiary">🏠 Back to Home</a>
    </div>
  </div>
</body>
</html>
