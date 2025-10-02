<?php
session_start();
include '../db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle quiz approval/rejection
if ($_POST) {
    if (isset($_POST['approve_quiz'])) {
        $quiz_id = (int)$_POST['quiz_id'];
        $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
        
        mysqli_query($conn, "UPDATE quizzes SET 
                            status = 'approved', 
                            admin_notes = '$admin_notes',
                            approved_by = {$_SESSION['user_id']},
                            approved_at = NOW()
                            WHERE id = $quiz_id");
        
        $success_message = "Quiz approved successfully!";
    }
    
    if (isset($_POST['reject_quiz'])) {
        $quiz_id = (int)$_POST['quiz_id'];
        $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
        
        mysqli_query($conn, "UPDATE quizzes SET 
                            status = 'rejected', 
                            admin_notes = '$admin_notes',
                            approved_by = {$_SESSION['user_id']},
                            approved_at = NOW()
                            WHERE id = $quiz_id");
        
        $success_message = "Quiz rejected.";
    }
}

// Get pending quizzes
$pending_quizzes = mysqli_query($conn, "SELECT q.*, COUNT(qs.id) as question_count 
                                       FROM quizzes q 
                                       LEFT JOIN questions qs ON q.id = qs.quiz_id 
                                       WHERE q.status = 'pending' 
                                       GROUP BY q.id 
                                       ORDER BY q.created_at ASC");

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM quizzes WHERE status = 'pending') as pending_count,
    (SELECT COUNT(*) FROM quizzes WHERE status = 'approved') as approved_count,
    (SELECT COUNT(*) FROM quizzes WHERE status = 'rejected') as rejected_count,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM leaderboard) as total_attempts";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get recent activity
$recent_activity = mysqli_query($conn, "SELECT q.*, u.username as approver_name 
                                       FROM quizzes q 
                                       LEFT JOIN users u ON q.approved_by = u.id 
                                       WHERE q.status IN ('approved', 'rejected') 
                                       ORDER BY q.approved_at DESC 
                                       LIMIT 10");
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
                <p class="text-muted-foreground">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Manage quiz submissions and monitor platform activity.</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success mb-6">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">✅</span>
                        <span><?php echo $success_message; ?></span>
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
                    <?php if (mysqli_num_rows($pending_quizzes) > 0): ?>
                        <div class="space-y-6">
                            <?php while ($quiz = mysqli_fetch_assoc($pending_quizzes)): ?>
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
                                                <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                                
                                                <div class="form-group">
                                                    <label class="label">Admin Notes:</label>
                                                    <textarea name="admin_notes" class="textarea" rows="3" 
                                                              placeholder="Add notes about your decision (optional)..."></textarea>
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
                            <?php endwhile; ?>
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
                    <?php if (mysqli_num_rows($recent_activity) > 0): ?>
                        <div class="space-y-4">
                            <?php while ($activity = mysqli_fetch_assoc($recent_activity)): ?>
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
                                        <?php if ($activity['admin_notes']): ?>
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
                            <?php endwhile; ?>
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
