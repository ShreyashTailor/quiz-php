<?php
require_once __DIR__ . '/../security.php';

initSecureSession();
setSecurityHeaders();

requireAdmin('../login.php');

$db = db();
$success_message = '';
$error_message = '';

// Handle quiz approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "Admin quiz action", 'warning');
    } elseif (isset($_POST['approve_quiz'])) {
        $quiz_id = sanitizeInt($_POST['quiz_id'] ?? 0, 1);
        $admin_notes = sanitizeString($_POST['admin_notes'] ?? '', 2000);
        
        $result = $db->executeNonQuery(
            "UPDATE quizzes SET 
             status = 'approved', 
             admin_notes = ?,
             approved_by = ?,
             approved_at = NOW()
             WHERE id = ?",
            [$admin_notes, getCurrentUserId(), $quiz_id],
            'sii'
        );
        
        if ($result > 0) {
            $success_message = "Quiz approved successfully!";
            logSecurityEvent('quiz_approved', "Quiz ID: $quiz_id, Admin: " . getCurrentUsername());
        } else {
            $error_message = "Failed to approve quiz.";
            logSecurityEvent('quiz_approve_failed', "Error: " . $db->getLastError(), 'error');
        }
    } elseif (isset($_POST['reject_quiz'])) {
        $quiz_id = sanitizeInt($_POST['quiz_id'] ?? 0, 1);
        $admin_notes = sanitizeString($_POST['admin_notes'] ?? '', 2000);
        
        $result = $db->executeNonQuery(
            "UPDATE quizzes SET 
             status = 'rejected', 
             admin_notes = ?,
             approved_by = ?,
             approved_at = NOW()
             WHERE id = ?",
            [$admin_notes, getCurrentUserId(), $quiz_id],
            'sii'
        );
        
        if ($result > 0) {
            $success_message = "Quiz rejected.";
            logSecurityEvent('quiz_rejected', "Quiz ID: $quiz_id, Admin: " . getCurrentUsername());
        } else {
            $error_message = "Failed to reject quiz.";
            logSecurityEvent('quiz_reject_failed', "Error: " . $db->getLastError(), 'error');
        }
    }
}

// Get pending quizzes using prepared statement
$pending_quizzes = $db->fetchAll(
    "SELECT q.*, COUNT(qs.id) as question_count 
     FROM quizzes q 
     LEFT JOIN questions qs ON q.id = qs.quiz_id 
     WHERE q.status = 'pending' 
     GROUP BY q.id 
     ORDER BY q.created_at ASC"
);

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM quizzes WHERE status = 'pending') as pending_count,
    (SELECT COUNT(*) FROM quizzes WHERE status = 'approved') as approved_count,
    (SELECT COUNT(*) FROM quizzes WHERE status = 'rejected') as rejected_count,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM leaderboard) as total_attempts";
$stats_result = $db->fetchOne($stats_query);
$stats = $stats_result ?: [
    'pending_count' => 0,
    'approved_count' => 0,
    'rejected_count' => 0,
    'total_users' => 0,
    'total_attempts' => 0
];

// Get recent activity
$recent_activity = $db->fetchAll(
    "SELECT q.*, u.username as approver_name 
     FROM quizzes q 
     LEFT JOIN users u ON q.approved_by = u.id 
     WHERE q.status IN ('approved', 'rejected') 
     ORDER BY q.approved_at DESC 
     LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QuizMaster</title>
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
                <a href="../leaderboard.php" class="btn btn-secondary">🏆 Leaderboard</a>
                <a href="../logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Welcome Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold mb-2">Admin Dashboard</h1>
                <p class="text-muted-foreground">Welcome back, <?php echo htmlspecialchars(getCurrentUsername()); ?>! Manage quiz submissions and monitor platform activity.</p>
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-5 gap-6 mb-8">
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">⏳</div>
                            <div>
                                <p class="text-sm text-muted-foreground">Pending</p>
                                <p class="text-2xl font-bold"><?php echo $stats['pending_count']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">✅</div>
                            <div>
                                <p class="text-sm text-muted-foreground">Approved</p>
                                <p class="text-2xl font-bold"><?php echo $stats['approved_count']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">❌</div>
                            <div>
                                <p class="text-sm text-muted-foreground">Rejected</p>
                                <p class="text-2xl font-bold"><?php echo $stats['rejected_count']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">👥</div>
                            <div>
                                <p class="text-sm text-muted-foreground">Users</p>
                                <p class="text-2xl font-bold"><?php echo $stats['total_users']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-content">
                        <div class="flex items-center space-x-3">
                            <div class="text-3xl">🎯</div>
                            <div>
                                <p class="text-sm text-muted-foreground">Attempts</p>
                                <p class="text-2xl font-bold"><?php echo $stats['total_attempts']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <a href="../create_quiz.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">➕</div>
                        <h3 class="font-semibold mb-2">Create Quiz</h3>
                        <p class="text-sm text-muted-foreground">Create a new quiz as admin</p>
                    </div>
                </a>
                
                <a href="manage_quizzes.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">📝</div>
                        <h3 class="font-semibold mb-2">Manage Quizzes</h3>
                        <p class="text-sm text-muted-foreground">Edit, delete and manage quizzes</p>
                    </div>
                </a>
                
                <a href="../leaderboard.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">🏆</div>
                        <h3 class="font-semibold mb-2">Leaderboard</h3>
                        <p class="text-sm text-muted-foreground">Check quiz performance</p>
                    </div>
                </a>
                
                <a href="manage_users.php" class="card hover:scale-105 transition-transform">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-3">👥</div>
                        <h3 class="font-semibold mb-2">Manage Users</h3>
                        <p class="text-sm text-muted-foreground">View and manage user accounts</p>
                    </div>
                </a>
            </div>

            <!-- Pending Quizzes Section -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">⏳ Pending Quiz Approvals</h3>
                    <p class="card-description"><?php echo $stats['pending_count']; ?> quiz(es) awaiting review</p>
                </div>
                <div class="card-content">
                    <?php if (!empty($pending_quizzes)): ?>
                        <div class="space-y-6">
                            <?php foreach ($pending_quizzes as $quiz): ?>
                                <div class="border rounded-lg p-6">
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <h4 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                            <p class="text-muted-foreground mb-4"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                            
                                            <div class="space-y-2 text-sm">
                                                <div class="flex items-center space-x-2">
                                                    <span>👤</span>
                                                    <span>Created by: <strong><?php echo htmlspecialchars($quiz['creator_name']); ?></strong></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span>❓</span>
                                                    <span>Questions: <strong><?php echo $quiz['question_count']; ?></strong></span>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span>📅</span>
                                                    <span>Submitted: <strong><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></strong></span>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4">
                                                <a href="preview_quiz.php?id=<?php echo $quiz['id']; ?>" 
                                                   class="btn btn-outline" target="_blank">👀 Preview Quiz</a>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <form method="post" class="space-y-4">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                
                                                <div class="form-group">
                                                    <label class="label">Admin Notes:</label>
                                                    <textarea name="admin_notes" class="textarea" rows="3" 
                                                              placeholder="Add notes about your decision (optional)..."
                                                              maxlength="2000"></textarea>
                                                </div>
                                                
                                                <div class="flex space-x-3">
                                                    <button type="submit" name="approve_quiz" class="btn btn-primary flex-1"
                                                            onclick="return confirm('Are you sure you want to approve this quiz?')">
                                                        ✅ Approve
                                                    </button>
                                                    <button type="submit" name="reject_quiz" class="btn btn-destructive flex-1"
                                                            onclick="return confirm('Are you sure you want to reject this quiz?')">
                                                        ❌ Reject
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-6">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold mb-2">All Caught Up!</h3>
                            <p class="text-muted-foreground">No quizzes pending approval. All submitted quizzes have been reviewed.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Recent Admin Activity</h3>
                    <p class="card-description">Latest review actions and decisions</p>
                </div>
                <div class="card-content">
                    <?php if (!empty($recent_activity)): ?>
                        <div class="space-y-4">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="flex items-start space-x-4 p-4 border rounded-lg">
                                    <div class="text-2xl">
                                        <?php echo ($activity['status'] === 'approved') ? '✅' : '❌'; ?>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold"><?php echo htmlspecialchars($activity['title']); ?></h4>
                                        <p class="text-sm text-muted-foreground">
                                            <?php echo ucfirst($activity['status']); ?> by 
                                            <strong><?php echo htmlspecialchars($activity['approver_name'] ?? 'Unknown'); ?></strong>
                                            on <?php echo date('M j, Y g:i A', strtotime($activity['approved_at'])); ?>
                                        </p>
                                        <?php if (!empty($activity['admin_notes'])): ?>
                                            <p class="text-sm mt-2 p-2 bg-muted rounded">
                                                📝 Notes: <?php echo htmlspecialchars($activity['admin_notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge <?php echo $activity['status'] === 'approved' ? 'badge-default' : 'badge-destructive'; ?>">
                                            <?php echo ucfirst($activity['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-6">
                            <div class="text-6xl mb-4">📊</div>
                            <h3 class="text-lg font-semibold mb-2">No Recent Activity</h3>
                            <p class="text-muted-foreground">Admin review activity will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="../dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin dashboard loaded with Shadcn UI!');
        });
    </script>
</body>
</html>