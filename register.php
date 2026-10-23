<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

$db = db();
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting for registration
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit("register_$ip", 3, 3600)) {
        $error_message = 'Too many registration attempts. Please try again in 1 hour.';
        logSecurityEvent('register_rate_limit_exceeded', "IP: $ip", 'warning');
    } else {
        $username = sanitizeString($_POST['username'] ?? '', 100);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate CSRF token
        if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
            $error_message = 'Invalid security token. Please refresh the page and try again.';
            logSecurityEvent('csrf_validation_failed', "Registration attempt", 'warning');
        } elseif (empty($username) || empty($password)) {
            $error_message = 'Please fill in all fields.';
        } elseif (!validateUsername($username)) {
            $error_message = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
        } elseif (($errors = validatePassword($password)) !== []) {
            $error_message = implode(' ', $errors);
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } else {
            // Check if username exists using prepared statement
            $existing = $db->fetchOne(
                "SELECT id FROM users WHERE username = ?",
                [$username],
                's'
            );
            
            if ($existing) {
                $error_message = 'Username already exists! Please choose a different username.';
            } else {
                // Hash password with bcrypt
                $hashed_password = hashPassword($password);
                
                // Insert new user with prepared statement
                $result = $db->executeNonQuery(
                    "INSERT INTO users (username, password, role) VALUES (?, ?, 'user')",
                    [$username, $hashed_password],
                    'ss'
                );
                
                if ($result > 0) {
                    $success_message = 'Registration successful! You can now login to your account.';
                    logSecurityEvent('registration_success', "Username: $username");
                    
                    // Clear rate limit on successful registration
                    clearRateLimit("register_$ip");
                } else {
                    $error_message = 'Registration failed. Please try again.';
                    logSecurityEvent('registration_failed', "Username: $username | Error: " . $db->getLastError(), 'error');
                }
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
  <title>Register - QuizMaster</title>
  <link rel="stylesheet" href="shadcn-style.css">
</head>
<body>
  <!-- Navigation -->
  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="nav-brand">QuizMaster</a>
      <div class="nav-links">
        <a href="login.php" class="nav-link">Already have an account? Login</a>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="main-content">
    <div class="container">
      <div class="grid grid-cols-1" style="max-width: 400px; margin: 0 auto;">
        <div class="card">
          <div class="card-header text-center">
            <h1 class="card-title text-2xl">Create Account</h1>
            <p class="card-description">Join QuizMaster and start your learning journey</p>
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
                <div class="mt-4">
                  <a href="login.php" class="btn btn-primary w-full">Login Now</a>
                </div>
              </div>
            <?php else: ?>
              <form method="post" class="space-y-4">
                <?= csrfField() ?>
                <div class="form-group">
                  <label class="label">Username</label>
                  <input type="text" name="username" class="input" placeholder="Choose a username" required 
                         value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                         autocomplete="username" minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+"
                         title="3-30 characters, letters, numbers, and underscores only">
                </div>
                <div class="form-group">
                  <label class="label">Password</label>
                  <input type="password" name="password" class="input" placeholder="Create a password" required
                         autocomplete="new-password" minlength="8" maxlength="72"
                         title="At least 8 characters with uppercase, lowercase, number, and special character">
                </div>
                <div class="form-group">
                  <label class="label">Confirm Password</label>
                  <input type="password" name="confirm_password" class="input" placeholder="Confirm your password" required
                         autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-full">Create Account</button>
              </form>
            <?php endif; ?>
          </div>
          <div class="card-footer text-center">
            <p class="text-sm text-muted-foreground">
              Already have an account? 
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