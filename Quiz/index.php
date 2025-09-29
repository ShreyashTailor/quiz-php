<?php
session_start();
include 'db.php';

// Set timezone to GMT+5:30 (India Standard Time)
date_default_timezone_set('Asia/Kolkata');

// Function to get greeting based on current time
function getTimeBasedGreeting() {
    $hour = date('H');
    
    if ($hour >= 5 && $hour < 12) {
        return "Good Morning";
    } elseif ($hour >= 12 && $hour < 17) {
        return "Good Afternoon";
    } else {
        return "Good Evening";
    }
}

// Only show approved quizzes to regular users, show all to admins
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $quizzes = mysqli_query($conn, "SELECT * FROM quizzes ORDER BY id DESC");
} else {
    $quizzes = mysqli_query($conn, "SELECT * FROM quizzes WHERE status = 'approved' ORDER BY id DESC");
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Online Quiz Website</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- Top Navigation -->
  <div class="navbar">
    <div class="nav-left">
      <h1>QuizMaster</h1>
    </div>
    <div class="nav-center">
      <a href="leaderboard.php" class="nav-link">🏆 Leaderboard</a>
    </div>
    <div class="nav-right">
      <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
        <div class="user-greeting">
          <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>! <?php echo getTimeBasedGreeting(); ?>!</span>
          <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
      <?php else: ?>
        <a href="register.php" class="nav-btn">Register</a>
        <a href="login.php" class="nav-btn">Login</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Hero Section -->
  <div class="hero">
    <div class="hero-content">
      <h2>Welcome to QuizMaster</h2>
      <p>Test your knowledge with our interactive quizzes. Create, share, and take quizzes on various topics!</p>
      <a href="create_quiz.php" class="hero-btn">Create Your First Quiz</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container">
    <div class="section">
      <h3>🎯 Featured Quizzes</h3>
      <?php if (mysqli_num_rows($quizzes) > 0) { ?>
        <div class="quiz-grid">
          <?php while ($q = mysqli_fetch_assoc($quizzes)) { ?>
            <div class="quiz-card">
              <h4><?php echo htmlspecialchars($q['title']); ?></h4>
              <p>Test your knowledge with this exciting quiz!</p>
              <a href="quiz.php?id=<?php echo $q['id']; ?>" class="quiz-btn">Take Quiz</a>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="no-quizzes">
          <p>🎲 No quizzes available yet!</p>
          <p>Be the first to create an amazing quiz for others to enjoy.</p>
          <a href="create_quiz.php" class="create-btn">Create First Quiz</a>
        </div>
      <?php } ?>
    </div>

    <!-- Features Section -->
    <div class="section">
      <h3>✨ Why Choose QuizMaster?</h3>
      <div class="features">
        <div class="feature">
          <div class="feature-icon">🎯</div>
          <h4>Easy to Create</h4>
          <p>Create custom quizzes in minutes with our intuitive interface.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">📊</div>
          <h4>Track Progress</h4>
          <p>Monitor your performance and see detailed results.</p>
        </div>
        <div class="feature">
          <div class="feature-icon">🌟</div>
          <h4>Share & Compete</h4>
          <p>Share quizzes with friends and compete for the best scores.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
