<?php
require_once __DIR__ . '/../security.php';

initSecureSession();
setSecurityHeaders();

requireAdmin('../login.php');

$db = db();
$success_message = '';
$error_message = '';

// Handle quiz actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "Admin quiz action", 'warning');
    } elseif (isset($_POST['delete_quiz'])) {
        $quiz_id = sanitizeInt($_POST['quiz_id'] ?? 0, 1);
        
        try {
            $db->beginTransaction();
            
            // Delete questions first (foreign key constraint)
            $db->executeNonQuery(
                "DELETE FROM questions WHERE quiz_id = ?",
                [$quiz_id],
                'i'
            );
            
            // Delete leaderboard entries
            $db->executeNonQuery(
                "DELETE FROM leaderboard WHERE quiz_id = ?",
                [$quiz_id],
                'i'
            );
            
            // Delete quiz
            $result = $db->executeNonQuery(
                "DELETE FROM quizzes WHERE id = ?",
                [$quiz_id],
                'i'
            );
            
            if ($result > 0) {
                $db->commit();
                $success_message = "Quiz deleted successfully!";
                logSecurityEvent('quiz_deleted', "Quiz ID: $quiz_id, Admin: " . getCurrentUsername());
            } else {
                $db->rollback();
                $error_message = "Quiz not found or already deleted.";
            }
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error deleting quiz: " . $e->getMessage();
            logSecurityEvent('quiz_delete_error', "Error: " . $e->getMessage(), 'error');
        }
    } elseif (isset($_POST['change_status'])) {
        $quiz_id = sanitizeInt($_POST['quiz_id'] ?? 0, 1);
        $new_status = sanitizeString($_POST['new_status'] ?? '', 20);
        
        $valid_statuses = ['pending', 'approved', 'rejected'];
        if (!in_array($new_status, $valid_statuses)) {
            $error_message = "Invalid status.";
        } else {
            $result = $db->executeNonQuery(
                "UPDATE quizzes SET 
                 status = ?,
                 approved_by = ?,
                 approved_at = NOW()
                 WHERE id = ?",
                [$new_status, getCurrentUserId(), $quiz_id],
                'sii'
            );
            
            if ($result > 0) {
                $success_message = "Quiz status updated to " . ucfirst($new_status) . "!";
                logSecurityEvent('quiz_status_changed', "Quiz ID: $quiz_id, New Status: $new_status, Admin: " . getCurrentUsername());
            } else {
                $error_message = "Failed to update quiz status.";
            }
        }
    }
}

// Get all quizzes with question count using prepared statement
$quizzes = $db->fetchAll(
    "SELECT q.*, COUNT(qs.id) as question_count,
     (SELECT COUNT(*) FROM leaderboard l WHERE l.quiz_id = q.id) as attempt_count
     FROM quizzes q 
     LEFT JOIN questions qs ON q.id = qs.quiz_id 
     GROUP BY q.id 
     ORDER BY q.created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - Admin | QuizMaster</title>
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
                <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                <a href="../logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold mb-2">📝 Manage Quizzes</h1>
                <p class="text-muted-foreground text-lg">Edit, delete, and manage all quizzes in the system</p>
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

            <!-- Quick Actions -->
            <div class="grid grid-cols-3 gap-6 mb-8">
                <a href="../create_quiz.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">➕</div>
                        <h3 class="font-semibold mb-2">Create New Quiz</h3>
                        <p class="text-sm text-muted-foreground">Start creating a new quiz</p>
                    </div>
                </a>
                
                <a href="../leaderboard.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">🏆</div>
                        <h3 class="font-semibold mb-2">View Leaderboard</h3>
                        <p class="text-sm text-muted-foreground">Check quiz performance</p>
                    </div>
                </a>
                
                <a href="dashboard.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">📊</div>
                        <h3 class="font-semibold mb-2">Admin Dashboard</h3>
                        <p class="text-sm text-muted-foreground">View admin overview</p>
                    </div>
                </a>
            </div>

            <!-- Quizzes Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🗂️ All Quizzes</h3>
                    <p class="card-description">Complete list of quizzes with management options</p>
                </div>
                <div class="card-content">
                    <?php if (!empty($quizzes)): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-4">Quiz Details</th>
                                        <th class="text-left p-4">Creator</th>
                                        <th class="text-left p-4">Status</th>
                                        <th class="text-left p-4">Questions</th>
                                        <th class="text-left p-4">Attempts</th>
                                        <th class="text-left p-4">Created</th>
                                        <th class="text-left p-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quizzes as $quiz): ?>
                                        <tr class="border-b hover:bg-muted/50">
                                            <td class="p-4">
                                                <div>
                                                    <h4 class="font-semibold"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                                    <p class="text-sm text-muted-foreground line-clamp-2">
                                                        <?php echo htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)); ?>...
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="p-4">
                                                <span class="font-medium"><?php echo htmlspecialchars($quiz['creator_name']); ?></span>
                                            </td>
                                            <td class="p-4">
                                                <?php 
                                                $status_class = '';
                                                switch($quiz['status']) {
                                                    case 'approved': $status_class = 'badge-default'; break;
                                                    case 'pending': $status_class = 'badge-secondary'; break;
                                                    case 'rejected': $status_class = 'badge-destructive'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($quiz['status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-4">
                                                <span class="font-medium"><?php echo $quiz['question_count']; ?></span>
                                            </td>
                                            <td class="p-4">
                                                <span class="font-medium"><?php echo $quiz['attempt_count']; ?></span>
                                            </td>
                                            <td class="p-4 text-sm text-muted-foreground">
                                                <?php echo date('M j, Y', strtotime($quiz['created_at'])); ?>
                                            </td>
                                            <td class="p-4">
                                                <div class="flex space-x-2">
                                                    <!-- Edit Quiz -->
                                                    <a href="../add_questions.php?quiz_id=<?php echo $quiz['id']; ?>" 
                                                       class="btn btn-outline btn-sm" title="Edit Questions">
                                                        ✏️
                                                    </a>
                                                    
                                                    <!-- Preview Quiz -->
                                                    <?php if ($quiz['question_count'] > 0): ?>
                                                        <a href="../quiz.php?id=<?php echo $quiz['id']; ?>" 
                                                           class="btn btn-secondary btn-sm" title="Preview Quiz" target="_blank">
                                                            👀
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Status Change -->
                                                    <div class="relative">
                                                        <button onclick="toggleStatusMenu(<?php echo $quiz['id']; ?>)" 
                                                                class="btn btn-outline btn-sm" title="Change Status">
                                                            🔄
                                                        </button>
                                                        <div id="status-menu-<?php echo $quiz['id']; ?>" 
                                                             class="absolute right-0 mt-1 w-32 bg-background border rounded-lg shadow-lg hidden z-10">
                                                            <form method="post">
                                                                <?= csrfField() ?>
                                                                <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                                <button type="submit" name="change_status" value="approved" 
                                                                        class="w-full text-left px-3 py-2 hover:bg-muted text-sm"
                                                                        onclick="return confirm('Approve this quiz?')">
                                                                    ✅ Approve
                                                                </button>
                                                                <button type="submit" name="change_status" value="pending" 
                                                                        class="w-full text-left px-3 py-2 hover:bg-muted text-sm"
                                                                        onclick="return confirm('Set this quiz to pending?')">
                                                                    ⏳ Pending
                                                                </button>
                                                                <button type="submit" name="change_status" value="rejected" 
                                                                        class="w-full text-left px-3 py-2 hover:bg-muted text-sm"
                                                                        onclick="return confirm('Reject this quiz?')">
                                                                    ❌ Reject
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Delete Quiz -->
                                                    <form method="post" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this quiz? This action cannot be undone.')">
                                                        <?= csrfField() ?>
                                                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                        <button type="submit" name="delete_quiz" 
                                                                class="btn btn-destructive btn-sm" title="Delete Quiz">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-8">
                            <div class="text-6xl mb-4">📝</div>
                            <h3 class="text-lg font-semibold mb-2">No Quizzes Found</h3>
                            <p class="text-muted-foreground mb-4">No quizzes have been created yet.</p>
                            <a href="../create_quiz.php" class="btn btn-primary">Create First Quiz</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../dark-mode.js"></script>
    <script>
        function toggleStatusMenu(quizId) {
            const menu = document.getElementById('status-menu-' + quizId);
            const allMenus = document.querySelectorAll('[id^="status-menu-"]');
            
            // Close all other menus
            allMenus.forEach(m => {
                if (m.id !== 'status-menu-' + quizId) {
                    m.classList.add('hidden');
                }
            });
            
            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[onclick^="toggleStatusMenu"]') && !e.target.closest('[id^="status-menu-"]')) {
                document.querySelectorAll('[id^="status-menu-"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Manage quizzes page loaded with Shadcn UI!');
        });
    </script>
</body>
</html>