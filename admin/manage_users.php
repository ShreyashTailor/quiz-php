<?php
require_once __DIR__ . '/../security.php';

initSecureSession();
setSecurityHeaders();

requireAdmin('../login.php');

$db = db();
$success_message = '';
$error_message = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        logSecurityEvent('csrf_validation_failed', "Admin user action", 'warning');
    } elseif (isset($_POST['action'])) {
        $user_id = sanitizeInt($_POST['user_id'] ?? 0, 1);
        
        // Prevent deleting yourself
        if ($user_id === getCurrentUserId()) {
            $error_message = "You cannot delete your own account.";
        } else {
            switch ($_POST['action']) {
                case 'delete_user':
                    // Check if user is admin (protect admin accounts)
                    $target_user = $db->fetchOne(
                        "SELECT role FROM users WHERE id = ?",
                        [$user_id],
                        'i'
                    );
                    
                    if ($target_user && $target_user['role'] === 'admin') {
                        $error_message = "Cannot delete admin users.";
                    } else {
                        try {
                            $db->beginTransaction();
                            
                            // Delete user's quiz attempts
                            $db->executeNonQuery(
                                "DELETE FROM leaderboard WHERE user_id = ?",
                                [$user_id],
                                'i'
                            );
                            
                            // Delete user's quizzes (will cascade to questions via FK)
                            $db->executeNonQuery(
                                "DELETE FROM quizzes WHERE created_by = ?",
                                [$user_id],
                                'i'
                            );
                            
                            // Finally delete the user
                            $result = $db->executeNonQuery(
                                "DELETE FROM users WHERE id = ? AND role != 'admin'",
                                [$user_id],
                                'i'
                            );
                            
                            if ($result > 0) {
                                $db->commit();
                                $success_message = "User deleted successfully!";
                                logSecurityEvent('user_deleted', "User ID: $user_id, Admin: " . getCurrentUsername());
                            } else {
                                $db->rollback();
                                $error_message = "Error deleting user! User may not exist or is protected.";
                            }
                        } catch (Exception $e) {
                            $db->rollback();
                            $error_message = "Error deleting user: " . $e->getMessage();
                            logSecurityEvent('user_delete_error', "Error: " . $e->getMessage(), 'error');
                        }
                    }
                    break;
            }
        }
    }
}

// Get users with statistics using prepared statement
$users = $db->fetchAll(
    "SELECT 
        u.id,
        u.username,
        u.role,
        COUNT(DISTINCT q.id) as quiz_count,
        COUNT(DISTINCT l.id) as attempt_count,
        AVG(l.score) as avg_score,
        MAX(l.score) as best_score
     FROM users u
     LEFT JOIN quizzes q ON u.id = q.created_by
     LEFT JOIN leaderboard l ON u.id = l.user_id
     GROUP BY u.id, u.username, u.role
     ORDER BY u.id DESC"
);

// Get total statistics
$stats = $db->fetchOne(
    "SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_count,
        COUNT(CASE WHEN role = 'user' THEN 1 END) as user_count
     FROM users"
) ?: [
    'total_users' => 0,
    'admin_count' => 0,
    'user_count' => 0
];
$stats['active_count'] = $stats['total_users'];
$stats['inactive_count'] = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../shadcn-style.css">
</head>
<body>
    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle" class="fixed top-4 right-4 z-50 p-2 rounded-lg bg-secondary hover:bg-secondary/80 transition-colors">
        <span class="dark-toggle-icon">🌙</span>
    </button>

    <div class="min-h-screen bg-background">
        <nav class="bg-card shadow-sm border-b">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-xl font-bold text-primary">QuizMaster Admin</h1>
                        <span class="text-sm text-muted-foreground">User Management</span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="dashboard.php" class="btn btn-outline btn-sm">
                            🏠 Dashboard
                        </a>
                        <a href="../logout.php" class="btn btn-destructive btn-sm">
                            🚪 Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container mx-auto px-4 py-8">
            <?php if ($success_message): ?>
                <div class="alert alert-success mb-6">
                    <span class="font-medium">Success!</span> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-destructive mb-6">
                    <span class="font-medium">Error!</span> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div class="card">
                    <div class="card-content">
                        <div class="text-2xl font-bold text-primary"><?php echo $stats['total_users']; ?></div>
                        <p class="text-xs text-muted-foreground">Total Users</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <div class="text-2xl font-bold text-purple-600"><?php echo $stats['admin_count']; ?></div>
                        <p class="text-xs text-muted-foreground">Admins</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-content">
                        <div class="text-2xl font-bold text-blue-600"><?php echo $stats['user_count']; ?></div>
                        <p class="text-xs text-muted-foreground">Regular Users</p>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">All Users</h2>
                    <p class="text-sm text-muted-foreground">Manage user accounts and view statistics</p>
                </div>
                <div class="card-content p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="border-b bg-muted/50">
                                <tr>
                                    <th class="text-left p-4 font-medium">User</th>
                                    <th class="text-left p-4 font-medium">Role</th>
                                    <th class="text-left p-4 font-medium">Quizzes Created</th>
                                    <th class="text-left p-4 font-medium">Quiz Attempts</th>
                                    <th class="text-left p-4 font-medium">Best Score</th>
                                    <th class="text-left p-4 font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr class="border-b hover:bg-muted/30 transition-colors">
                                    <td class="p-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                <span class="text-primary font-medium">
                                                    <?php echo strtoupper(substr(htmlspecialchars($user['username']), 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-medium"><?php echo htmlspecialchars($user['username']); ?></div>
                                                <div class="text-sm text-muted-foreground">ID: <?php echo $user['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-center">
                                            <span class="font-medium text-lg"><?php echo $user['quiz_count']; ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-center">
                                            <span class="font-medium text-lg"><?php echo $user['attempt_count']; ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="text-center">
                                            <?php if ($user['best_score'] !== null): ?>
                                                <span class="font-medium text-lg <?php echo $user['best_score'] >= 80 ? 'text-green-600' : ($user['best_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                    <?php echo round($user['best_score'], 1); ?>%
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted-foreground">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <?php if ($user['role'] !== 'admin' && $user['id'] !== getCurrentUserId()): ?>
                                            <div class="flex space-x-2">
                                                <!-- Delete User -->
                                                <form method="POST" style="display: inline;">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-destructive btn-sm" 
                                                            onclick="return confirm('Are you sure you want to delete this user? This will also delete all their quizzes and attempts.')">
                                                        🗑️ Delete
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($user['role'] === 'admin'): ?>
                                            <span class="text-muted-foreground text-sm">Protected (Admin)</span>
                                        <?php else: ?>
                                            <span class="text-muted-foreground text-sm">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../dark-mode.js"></script>
</body>
</html>