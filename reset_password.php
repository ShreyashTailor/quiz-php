<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

$db = db();
$step = $_GET['step'] ?? 1;
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "Password reset attempt", 'warning');
    } elseif ($step === 1) {
        // Step 1: Request password reset
        $email = sanitizeEmail($_POST['email'] ?? '');
        
        if (empty($email) || !validateEmail($email)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            $user = $db->fetchOne(
                "SELECT id, username, email FROM users WHERE email = ?",
                [$email],
                's'
            );
            
            if ($user) {
                // Generate secure reset token
                $reset_token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $reset_token);
                $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                
                // Store token (in a real app, use a separate password_resets table)
                // For now, we'll store in users table with additional columns
                $db->executeNonQuery(
                    "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?",
                    [$token_hash, $expires_at, $user['id']],
                    'ssi'
                );
                
                // In a real application, send email with reset link
                // For demo, we'll show the token on screen
                $reset_link = APP_URL . "/reset_password.php?step=2&token=" . $reset_token;
                
                $success_message = "Password reset link generated. In a real app, this would be emailed to you.";
                logSecurityEvent('password_reset_requested', "User: {$user['username']}, Email: $email");
                
                // For demo purposes only - show reset link
                $_SESSION['demo_reset_link'] = $reset_link;
            } else {
                // Don't reveal if email exists (security best practice)
                $success_message = "If the email exists in our system, a password reset link has been sent.";
                logSecurityEvent('password_reset_requested_unknown', "Email: $email", 'warning');
            }
        }
    } elseif ($step === 2) {
        // Step 2: Reset password with token
        $token = $_POST['token'] ?? ($_GET['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($token)) {
            $error_message = 'Invalid or missing reset token.';
        } elseif (empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (($errors = validatePassword($password)) !== []) {
            $error_message = implode(' ', $errors);
        } else {
            $token_hash = hash('sha256', $token);
            
            $user = $db->fetchOne(
                "SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()",
                [$token_hash],
                's'
            );
            
            if ($user) {
                $hashed_password = hashPassword($password);
                
                $result = $db->executeNonQuery(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?",
                    [$hashed_password, $user['id']],
                    'si'
                );
                
                if ($result > 0) {
                    $success_message = 'Password has been reset successfully! You can now login with your new password.';
                    logSecurityEvent('password_reset_success', "User: {$user['username']}");
                    
                    // Clear any demo reset link
                    unset($_SESSION['demo_reset_link']);
                    
                    // Redirect to login after 3 seconds
                    header("refresh:3;url=login.php");
                } else {
                    $error_message = 'Failed to reset password. Please try again.';
                    logSecurityEvent('password_reset_failed', "Error: " . $db->getLastError(), 'error');
                }
            } else {
                $error_message = 'Invalid or expired reset token.';
                logSecurityEvent('password_reset_invalid_token', "Token: " . substr($token_hash, 0, 8), 'warning');
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
  <title>Password Reset - QuizMaster</title>
  <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">QuizMaster</a>
      <div class="nav-links">
        <a href="login.php" class="nav-link">← Back to Login</a>
      </div>
    </div>
  </nav>

  <main class="main-content">
    <div class="container">
      <div class="grid grid-cols-1" style="max-width: 400px; margin: 0 auto;">
        <div class="card">
          <div class="card-header text-center">
            <?php if ($step === 1): ?>
              <h1 class="card-title text-2xl">Forgot Password?</h1>
              <p class="card-description">Enter your email to receive a password reset link</p>
            <?php else: ?>
              <h1 class="card-title text-2xl">Reset Password</h1>
              <p class="card-description">Enter your new password below</p>
            <?php endif; ?>
          </div>
          <div class="card-content">
            <?php if ($error_message): ?>
              <div class="alert alert-destructive mb-4">
                <p>❌ <?php echo htmlspecialchars($error_message); ?></p>
              </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
              <div class="alert alert-success mb-4">
                <p>✅ <?php echo htmlspecialchars($success_message); ?></p>
              </div>
            <?php endif; ?>
            
            <?php if ($step === 1): ?>
              <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="step" value="1">
                <div class="form-group">
                  <label class="label">Email Address</label>
                  <input type="email" name="email" class="input" placeholder="Enter your registered email" required autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary w-full">Send Reset Link</button>
              </form>
            <?php else: ?>
              <?php 
              $token = $_GET['token'] ?? $_POST['token'] ?? '';
              if (empty($token) && isset($_SESSION['demo_reset_link'])):
                  // Extract token from demo link
                  $parsed = parse_url($_SESSION['demo_reset_link']);
                  parse_str($parsed['query'] ?? '', $params);
                  $token = $params['token'] ?? '';
              endif;
              ?>
              
              <?php if (empty($token)): ?>
                <div class="alert alert-destructive">
                  <p>❌ Invalid or missing reset token. Please request a new password reset.</p>
                  <a href="reset_password.php" class="btn btn-primary w-full mt-4">Request New Reset</a>
                </div>
              <?php else: ?>
                <form method="post" class="space-y-4">
                  <?= csrfField() ?>
                  <input type="hidden" name="step" value="2">
                  <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                  <div class="form-group">
                    <label class="label">New Password</label>
                    <input type="password" name="password" class="input" placeholder="Enter new password" required 
                           autocomplete="new-password" minlength="8" maxlength="72">
                  </div>
                  <div class="form-group">
                    <label class="label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="input" placeholder="Confirm new password" required
                           autocomplete="new-password">
                  </div>
                  <button type="submit" class="btn btn-primary w-full">Reset Password</button>
                </form>
                
                <!-- Demo only - show reset link -->
                <?php if (isset($_SESSION['demo_reset_link'])): ?>
                  <div class="alert alert-default mt-6 p-4">
                    <p class="text-sm text-muted-foreground mb-2"><strong>Demo Mode:</strong> In production, this link would be sent via email.</p>
                    <p class="text-xs font-mono break-all"><?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?></p>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div class="card-footer text-center">
            <p class="text-sm text-muted-foreground">
              Remember your password? 
              <a href="login.php" class="text-primary hover:underline">Login here</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="dark-mode.js"></script>
</body>
</html>