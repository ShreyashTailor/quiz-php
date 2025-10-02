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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QuizMaster - Interactive Learning Platform</title>
  <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">QuizMaster</a>
      <div class="nav-links">
        <a href="leaderboard.php" class="nav-link">🏆 Leaderboard</a>
        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
          <span class="text-sm">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>! <?php echo getTimeBasedGreeting(); ?>!</span>
          <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
        <?php else: ?>
          <a href="register.php" class="btn btn-outline btn-sm">Register</a>
          <a href="login.php" class="btn btn-primary btn-sm">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <!-- Hero Section -->
      <div class="card mb-6">
        <div class="card-content text-center p-6">
          <h2 class="text-2xl font-bold mb-4">Welcome to QuizMaster</h2>
          <p class="text-lg mb-6 text-muted-foreground">Test your knowledge with our interactive quizzes. Create, share, and take quizzes on various topics!</p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <div class="grid grid-cols-2 gap-4" style="max-width: 400px; margin: 0 auto;">
              <a href="create_quiz.php" class="btn btn-primary">Create Quiz</a>
              <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
            </div>
          <?php else: ?>
            <div class="grid grid-cols-2 gap-4" style="max-width: 300px; margin: 0 auto;">
              <a href="register.php" class="btn btn-primary">Get Started</a>
              <a href="login.php" class="btn btn-outline">Sign In</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Quiz Listing Section -->
      <div class="mb-6">
        <h3 class="text-xl font-semibold mb-4">🎯 Available Quizzes</h3>
        <?php if (mysqli_num_rows($quizzes) > 0) { ?>
          <div class="grid grid-cols-3 gap-6">
            <?php while ($q = mysqli_fetch_assoc($quizzes)) { ?>
              <div class="card quiz-card">
                <div class="card-header">
                  <h4 class="card-title"><?php echo htmlspecialchars($q['title']); ?></h4>
                  <p class="card-description">
                    <?php echo isset($q['description']) ? htmlspecialchars($q['description']) : 'Test your knowledge with this exciting quiz!'; ?>
                  </p>
                </div>
                <div class="card-content">
                  <div class="flex items-center justify-between text-sm text-muted-foreground mb-4">
                    <span>📝 By: <?php echo htmlspecialchars($q['creator_name'] ?? 'Admin'); ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                      <span class="badge <?php echo $q['status'] === 'approved' ? 'badge-default' : 'badge-secondary'; ?>">
                        <?php echo ucfirst($q['status']); ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <a href="quiz.php?id=<?php echo $q['id']; ?>" class="btn btn-primary w-full">Take Quiz</a>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <div class="card text-center">
            <div class="card-content p-6">
              <div class="text-6xl mb-4">🎲</div>
              <h4 class="text-lg font-semibold mb-2">No quizzes available yet!</h4>
              <p class="text-muted-foreground mb-4">Be the first to create an amazing quiz for others to enjoy.</p>
              <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_quiz.php" class="btn btn-primary">Create First Quiz</a>
              <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login to Create Quiz</a>
              <?php endif; ?>
            </div>
          </div>
        <?php } ?>
      </div>

      <!-- Features Section -->
      <div class="mb-6">
        <h3 class="text-xl font-semibold mb-4 text-center">✨ Why Choose QuizMaster?</h3>
        <div class="grid grid-cols-3 gap-6">
          <div class="card text-center">
            <div class="card-content">
              <div class="text-4xl mb-4">🎯</div>
              <h4 class="font-semibold mb-2">Easy to Create</h4>
              <p class="text-sm text-muted-foreground">Create custom quizzes in minutes with our intuitive interface.</p>
            </div>
          </div>
          <div class="card text-center">
            <div class="card-content">
              <div class="text-4xl mb-4">📊</div>
              <h4 class="font-semibold mb-2">Track Progress</h4>
              <p class="text-sm text-muted-foreground">Monitor your learning progress with detailed analytics and leaderboards.</p>
            </div>
          </div>
          <div class="card text-center">
            <div class="card-content">
              <div class="text-4xl mb-4">🤖</div>
              <h4 class="font-semibold mb-2">AI-Powered</h4>
              <p class="text-sm text-muted-foreground">Generate quizzes automatically from PDF documents using AI technology.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dark-mode.js"></script>
  <script>
    // Add any JavaScript for interactions here
    document.addEventListener('DOMContentLoaded', function() {
      // Quiz card hover effects are handled by CSS
      console.log('QuizMaster Shadcn UI with Dark Mode loaded successfully!');
      
      // Add subtle animations to quiz cards
      const quizCards = document.querySelectorAll('.quiz-card');
      quizCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    });
  </script>
</body>
</html>
