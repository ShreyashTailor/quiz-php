<?php
session_start();
include '../db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Quiz ID not provided.");
}

$quiz_id = (int) $_GET['id'];

$result = mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($result);

if (!$quiz) {
    die("Quiz not found!");
}

$questions = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id");
$question_count = mysqli_num_rows($questions);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview: <?php echo htmlspecialchars($quiz['title']); ?> - Admin</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="nav-left">
            <h1><a href="../index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
        </div>
        <div class="nav-center">
            <span class="admin-badge">👑 Admin Preview</span>
        </div>
        <div class="nav-right">
            <a href="dashboard.php" class="nav-btn">🛠️ Dashboard</a>
            <a href="../logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Quiz Header -->
        <div class="quiz-preview-header">
            <div class="quiz-status-info">
                <h2>📋 Quiz Preview</h2>
                <div class="status-display">
                    <span class="status-badge <?php echo $quiz['status']; ?>">
                        <?php 
                        switch($quiz['status']) {
                            case 'pending': echo '⏳ Pending Approval'; break;
                            case 'approved': echo '✅ Approved'; break;
                            case 'rejected': echo '❌ Rejected'; break;
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <div class="quiz-details-card">
                <h3><?php echo htmlspecialchars($quiz['title']); ?></h3>
                <p class="quiz-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                
                <div class="quiz-meta-info">
                    <div class="meta-row">
                        <span class="meta-label">👤 Created by:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($quiz['creator_name']); ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">📅 Submitted:</span>
                        <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></span>
                    </div>
                    <div class="meta-row">
                        <span class="meta-label">❓ Total Questions:</span>
                        <span class="meta-value"><?php echo $question_count; ?></span>
                    </div>
                    <?php if ($quiz['admin_notes']): ?>
                        <div class="meta-row">
                            <span class="meta-label">📝 Admin Notes:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($quiz['admin_notes']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Questions Preview -->
        <?php if ($question_count == 0): ?>
            <div class="no-questions">
                <p>⚠️ This quiz has no questions yet.</p>
                <p>Questions are required before the quiz can be approved.</p>
            </div>
        <?php else: ?>
            <div class="questions-section">
                <h3>📝 Questions Preview</h3>
                <div class="questions-container">
                    <?php 
                    $q_num = 1;
                    while ($q = mysqli_fetch_assoc($questions)): 
                    ?>
                        <div class="question-preview-card">
                            <div class="question-header">
                                <h4>Question <?php echo $q_num; ?></h4>
                            </div>
                            
                            <div class="question-content">
                                <p class="question-text"><?php echo htmlspecialchars($q['question']); ?></p>
                                
                                <div class="options-grid">
                                    <div class="option-item <?php echo ($q['correct_option'] === 'A') ? 'correct' : ''; ?>">
                                        <span class="option-label">A)</span>
                                        <span class="option-text"><?php echo htmlspecialchars($q['option_a']); ?></span>
                                        <?php if ($q['correct_option'] === 'A'): ?>
                                            <span class="correct-indicator">✅ Correct</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="option-item <?php echo ($q['correct_option'] === 'B') ? 'correct' : ''; ?>">
                                        <span class="option-label">B)</span>
                                        <span class="option-text"><?php echo htmlspecialchars($q['option_b']); ?></span>
                                        <?php if ($q['correct_option'] === 'B'): ?>
                                            <span class="correct-indicator">✅ Correct</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="option-item <?php echo ($q['correct_option'] === 'C') ? 'correct' : ''; ?>">
                                        <span class="option-label">C)</span>
                                        <span class="option-text"><?php echo htmlspecialchars($q['option_c']); ?></span>
                                        <?php if ($q['correct_option'] === 'C'): ?>
                                            <span class="correct-indicator">✅ Correct</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="option-item <?php echo ($q['correct_option'] === 'D') ? 'correct' : ''; ?>">
                                        <span class="option-label">D)</span>
                                        <span class="option-text"><?php echo htmlspecialchars($q['option_d']); ?></span>
                                        <?php if ($q['correct_option'] === 'D'): ?>
                                            <span class="correct-indicator">✅ Correct</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                    $q_num++;
                    endwhile; 
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin Action Buttons -->
        <?php if ($quiz['status'] === 'pending'): ?>
            <div class="admin-actions-section">
                <h3>🛠️ Admin Actions</h3>
                <div class="approval-card">
                    <form method="post" action="dashboard.php" class="approval-form">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">
                        
                        <div class="form-group">
                            <label>Admin Notes (Optional):</label>
                            <textarea name="admin_notes" placeholder="Add notes about your decision..."></textarea>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" name="approve_quiz" class="btn-approve" 
                                    onclick="return confirm('Are you sure you want to approve this quiz?')">
                                ✅ Approve Quiz
                            </button>
                            <button type="submit" name="reject_quiz" class="btn-reject"
                                    onclick="return confirm('Are you sure you want to reject this quiz?')">
                                ❌ Reject Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Actions -->
        <div class="navigation-actions">
            <a href="dashboard.php" class="btn-primary">🛠️ Back to Dashboard</a>
            <?php if ($quiz['status'] === 'approved'): ?>
                <a href="../quiz.php?id=<?php echo $quiz_id; ?>" class="btn-secondary">🎯 Take Quiz</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
