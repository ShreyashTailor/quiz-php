<?php
require_once __DIR__ . '/../security.php';

initSecureSession();
setSecurityHeaders();

requireAdmin('../login.php');

$db = db();

$quiz_id = sanitizeInt($_GET['id'] ?? 0, 1);
$success_message = '';
$error_message = '';

// Get quiz details
$quiz = $db->fetchOne(
    "SELECT * FROM quizzes WHERE id = ?",
    [$quiz_id],
    'i'
);

if (!$quiz) {
    header("Location: manage_quizzes.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "Edit quiz attempt", 'warning');
    } else {
        $title = sanitizeString($_POST['title'] ?? '', 255);
        $desc = sanitizeString($_POST['desc'] ?? '', 2000);
        
        if (empty($title)) {
            $error_message = 'Quiz title is required.';
        } elseif (strlen($title) < 3) {
            $error_message = 'Quiz title must be at least 3 characters.';
        } else {
            $result = $db->executeNonQuery(
                "UPDATE quizzes SET title = ?, description = ?, updated_at = NOW() WHERE id = ?",
                [$title, $desc, $quiz_id],
                'ssi'
            );
            
            if ($result > 0) {
                $success_message = "Quiz updated successfully!";
                logSecurityEvent('quiz_updated', "Quiz ID: $quiz_id, Admin: " . getCurrentUsername());
                // Refresh quiz data
                $quiz = $db->fetchOne(
                    "SELECT * FROM quizzes WHERE id = ?",
                    [$quiz_id],
                    'i'
                );
            } else {
                $error_message = "Failed to update quiz. No changes made.";
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
    <title>Edit Quiz - Admin | QuizMaster</title>
    <link rel="stylesheet" href="../shadcn-style.css">
</head>
<body class="font-sans bg-background text-foreground">
    <nav class="navigation">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>🎯 QuizMaster Admin</h1>
            </div>
            <div class="nav-links">
                <a href="../index.php" class="btn btn-outline">🏠 Home</a>
                <a href="manage_quizzes.php" class="btn btn-secondary">📝 Manage Quizzes</a>
                <a href="../logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">✏️ Edit Quiz</h1>
                <p class="text-muted-foreground">Update quiz title and description</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success mb-6">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">✅</span>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-destructive mb-6">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">❌</span>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quiz Details</h3>
                </div>
                <div class="card-content">
                    <form method="post" class="space-y-4">
                        <?= csrfField() ?>
                        <div class="form-group">
                            <label class="label">Quiz Title</label>
                            <input type="text" name="title" class="input" required 
                                   value="<?php echo htmlspecialchars($quiz['title']); ?>"
                                   maxlength="255" minlength="3">
                        </div>
                        
                        <div class="form-group">
                            <label class="label">Description</label>
                            <textarea name="desc" class="textarea" rows="5"
                                      placeholder="Describe what your quiz covers..."><?php echo htmlspecialchars($quiz['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" class="btn btn-primary flex-1">Update Quiz</button>
                            <a href="manage_quizzes.php" class="btn btn-outline flex-1">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="manage_quizzes.php" class="btn btn-secondary">← Back to Manage Quizzes</a>
            </div>
        </div>
    </main>

    <script src="../dark-mode.js"></script>
</body>
</html>