<?php
session_start();
include 'db.php';

$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_POST) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']);

    $res = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    $user = mysqli_fetch_assoc($res);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            // Redirect to the originally requested page or index
            header("Location: " . urldecode($redirect_url));
        }
        exit;
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - QuizMaster</title>
  <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">QuizMaster</a>
      <div class="nav-links">
        <a href="register.php" class="nav-link">Don't have an account? Register</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <div class="grid grid-cols-1" style="max-width: 400px; margin: 0 auto;">
        <div class="card">
          <div class="card-header text-center">
            <h1 class="card-title text-2xl">Welcome Back</h1>
            <p class="card-description">Sign in to your QuizMaster account</p>
            <?php if (isset($_GET['redirect'])): ?>
              <div class="alert alert-default mt-4">
                <p>🔒 Please login to take the quiz</p>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-content">
            <?php if (isset($error_message)): ?>
              <div class="alert alert-destructive mb-4">
                <p>❌ <?php echo htmlspecialchars($error_message); ?></p>
              </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
              <div class="form-group">
                <label class="label">Username</label>
                <input type="text" name="username" class="input" placeholder="Enter your username" required>
              </div>
              <div class="form-group">
                <label class="label">Password</label>
                <input type="password" name="password" class="input" placeholder="Enter your password" required>
              </div>
              <button type="submit" class="btn btn-primary w-full">Sign In</button>
            </form>
          </div>
          <div class="card-footer text-center">
            <p class="text-sm text-muted-foreground">
              Don't have an account? 
              <a href="register.php" class="text-primary hover:underline">Register here</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dark-mode.js"></script>
  <?php if (isset($_POST['username']) && !$user): ?>
    <script>
      // Show error message
      document.addEventListener('DOMContentLoaded', function() {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-destructive';
        errorDiv.innerHTML = '<p>❌ Invalid username or password. Please try again.</p>';
        document.querySelector('.card-content').prepend(errorDiv);
      });
    </script>
  <?php endif; ?>
</body>
</html>
