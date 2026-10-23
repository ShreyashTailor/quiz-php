<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

$db = db();
$redirect_url = isset($_GET['redirect']) ? urldecode($_GET['redirect']) : 'index.php';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting for login attempts
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit("login_$ip", 5, 300)) {
        $error_message = 'Too many login attempts. Please try again in 5 minutes.';
        logSecurityEvent('login_rate_limit_exceeded', "IP: $ip", 'warning');
    } else {
        $username = sanitizeString($_POST['username'] ?? '', 100);
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } elseif (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $error_message = 'Invalid security token. Please refresh the page and try again.';
            logSecurityEvent('csrf_validation_failed', "Login attempt", 'warning');
        } else {
            // Use prepared statement to prevent SQL injection
            $user = $db->fetchOne(
                "SELECT id, username, password, role FROM users WHERE username = ?",
                [$username],
                's'
            );
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Check if password needs rehashing (e.g., after cost increase)
                if (needsRehash($user['password'])) {
                    $newHash = hashPassword($password);
                    $db->executeNonQuery(
                        "UPDATE users SET password = ? WHERE id = ?",
                        [$newHash, $user['id']],
                        'si'
                    );
                }
                
                // Regenerate session ID on successful login
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                // Clear rate limit on successful login
                clearRateLimit("login_$ip");
                
                logSecurityEvent('login_success', "User: {$user['username']}");
                
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: " . $redirect_url);
                }
                exit;
            } else {
                $error_message = 'Invalid username or password.';
                logSecurityEvent('login_failed', "Username: $username", 'warning');
            }
        }
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
            <?php if ($error_message): ?>
              <div class="alert alert-destructive mb-4">
                <p>❌ <?php echo htmlspecialchars($error_message); ?></p>
              </div>
            <?php endif; ?>
            <form method="post" class="space-y-4">
              <?= csrfField() ?>
              <div class="form-group">
                <label class="label">Username</label>
                <input type="text" name="username" class="input" placeholder="Enter your username" required autocomplete="username">
              </div>
              <div class="form-group">
                <label class="label">Password</label>
                <input type="password" name="password" class="input" placeholder="Enter your password" required autocomplete="current-password">
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
  <?php if (isset($_POST['username']) && $error_message): ?>
    <script>
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