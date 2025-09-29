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
<html>
<head>
    <title>Admin Dashboard - QuizMaster</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="nav-left">
            <h1><a href="../index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
        </div>
        <div class="nav-center">
            <span class="admin-badge">👑 Admin Dashboard</span>
        </div>
        <div class="nav-right">
            <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="../logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Page Header -->
        <div class="admin-header">
            <h2>🛠️ Admin Dashboard</h2>
            <p>Manage quiz submissions and monitor platform activity</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <p>✅ <?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            <div class="stat-card approved">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['approved_count']; ?></div>
                <div class="stat-label">Approved Quizzes</div>
            </div>
            <div class="stat-card rejected">
                <div class="stat-icon">❌</div>
                <div class="stat-value"><?php echo $stats['rejected_count']; ?></div>
                <div class="stat-label">Rejected Quizzes</div>
            </div>
            <div class="stat-card users">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card attempts">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?php echo $stats['total_attempts']; ?></div>
                <div class="stat-label">Quiz Attempts</div>
            </div>
        </div>

        <!-- Pending Quizzes Section -->
        <div class="section">
            <h3>⏳ Pending Quiz Approvals (<?php echo $stats['pending_count']; ?>)</h3>
            
            <?php if (mysqli_num_rows($pending_quizzes) > 0): ?>
                <div class="pending-quizzes">
                    <?php while ($quiz = mysqli_fetch_assoc($pending_quizzes)): ?>
                        <div class="quiz-review-card">
                            <div class="quiz-info">
                                <h4><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                                
                                <div class="quiz-meta">
                                    <span class="meta-item">👤 Created by: <strong><?php echo htmlspecialchars($quiz['creator_name']); ?></strong></span>
                                    <span class="meta-item">❓ Questions: <strong><?php echo $quiz['question_count']; ?></strong></span>
                                    <span class="meta-item">📅 Submitted: <strong><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></strong></span>
                                </div>
                                
                                <div class="quiz-actions">
                                    <a href="preview_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-secondary" target="_blank">👀 Preview Quiz</a>
                                </div>
                            </div>
                            
                            <div class="approval-actions">
                                <form method="post" class="approval-form">
                                    <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label>Admin Notes:</label>
                                        <textarea name="admin_notes" placeholder="Add notes about your decision (optional)..."></textarea>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button type="submit" name="approve_quiz" class="btn-approve" 
                                                onclick="return confirm('Are you sure you want to approve this quiz?')">
                                            ✅ Approve Quiz
                                        </button>
                                        <button type="submit" name="reject_quiz" class="btn-reject"
                                                onclick="return confirm('Are you sure you want to reject this quiz? This action cannot be undone.')">
                                            ❌ Reject Quiz
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-pending">
                    <div class="no-data">
                        <p>🎉 Great! No quizzes pending approval.</p>
                        <p>All submitted quizzes have been reviewed.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="section">
            <h3>📊 Recent Admin Activity</h3>
            
            <?php if (mysqli_num_rows($recent_activity) > 0): ?>
                <div class="activity-list">
                    <?php while ($activity = mysqli_fetch_assoc($recent_activity)): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php echo ($activity['status'] === 'approved') ? '✅' : '❌'; ?>
                            </div>
                            <div class="activity-content">
                                <h4><?php echo htmlspecialchars($activity['title']); ?></h4>
                                <p>
                                    <?php echo ucfirst($activity['status']); ?> by 
                                    <strong><?php echo htmlspecialchars($activity['approver_name'] ?? 'Unknown'); ?></strong>
                                    on <?php echo date('M j, Y g:i A', strtotime($activity['approved_at'])); ?>
                                </p>
                                <?php if ($activity['admin_notes']): ?>
                                    <p class="admin-notes">📝 Notes: <?php echo htmlspecialchars($activity['admin_notes']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="activity-meta">
                                <span class="status-badge <?php echo $activity['status']; ?>">
                                    <?php echo ucfirst($activity['status']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>No recent admin activity.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="section">
            <h3>⚡ Quick Actions</h3>
            <div class="quick-actions">
                <a href="../create_quiz.php" class="action-card">
                    <div class="action-icon">➕</div>
                    <h4>Create Quiz</h4>
                    <p>Create a new quiz as admin</p>
                </a>
                <a href="../leaderboard.php" class="action-card">
                    <div class="action-icon">🏆</div>
                    <h4>View Leaderboard</h4>
                    <p>Check quiz performance</p>
                </a>
                <a href="../index.php" class="action-card">
                    <div class="action-icon">🏠</div>
                    <h4>Home Page</h4>
                    <p>Go to main site</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
