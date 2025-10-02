<?php
include 'db.php';

$success_message = '';
$error_message = '';

if ($_POST) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = md5($_POST['password']); // simple hash for demo (use bcrypt in real apps)

    $check = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error_message = "Username already exists! Please choose a different username.";
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, role) VALUES ('$username','$password','user')");
        $success_message = "Registration successful! You can now login to your account.";
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
                <div class="form-group">
                  <label class="label">Username</label>
                  <input type="text" name="username" class="input" placeholder="Choose a username" required 
                         value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                  <label class="label">Password</label>
                  <input type="password" name="password" class="input" placeholder="Create a password" required>
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
  </form>
</div>
</body>
</html>
