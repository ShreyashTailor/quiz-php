<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$quiz_id = $_GET['quiz_id'];

// Get quiz details
$quiz_result = mysqli_query($conn, "SELECT * FROM quizzes WHERE id = $quiz_id");
$quiz = mysqli_fetch_assoc($quiz_result);

// Check if user owns this quiz
if ($quiz['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Get question count
$question_count = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id = $quiz_id"));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quiz Submitted - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="nav-left">
            <h1><a href="index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
        </div>
        <div class="nav-right">
            <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <a href="logout.php" class="nav-btn logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="submission-success">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <!-- Admin created quiz - auto approved -->
                <div class="success-card admin-success">
                    <div class="success-icon">🎉</div>
                    <h2>Quiz Created Successfully!</h2>
                    <p class="success-message">
                        Congratulations! Your quiz "<strong><?php echo htmlspecialchars($quiz['title']); ?></strong>" 
                        has been created and is now live on the platform.
                    </p>
                    <div class="quiz-details">
                        <div class="detail-item">
                            <span class="detail-label">📚 Quiz Title:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($quiz['title']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">❓ Questions Added:</span>
                            <span class="detail-value"><?php echo $question_count; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">📊 Status:</span>
                            <span class="status-badge approved">✅ Live & Available</span>
                        </div>
                    </div>
                    <div class="admin-actions">
                        <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn-primary">🎯 Take Your Quiz</a>
                        <a href="index.php" class="btn-secondary">🏠 Back to Home</a>
                        <a href="create_quiz.php" class="btn-tertiary">➕ Create Another Quiz</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Regular user created quiz - needs approval -->
                <div class="success-card user-success">
                    <div class="success-icon">🙏</div>
                    <h2>Thanks for Adding the Quiz!</h2>
                    <p class="success-message">
                        Your quiz "<strong><?php echo htmlspecialchars($quiz['title']); ?></strong>" has been submitted successfully. 
                        An admin will review and approve your quiz, and it will then appear on the home page for everyone to enjoy!
                    </p>
                    
                    <div class="quiz-details">
                        <div class="detail-item">
                            <span class="detail-label">📚 Quiz Title:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($quiz['title']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">📝 Description:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($quiz['description']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">❓ Questions Added:</span>
                            <span class="detail-value"><?php echo $question_count; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">📊 Status:</span>
                            <span class="status-badge pending">⏳ Pending Admin Approval</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">⏰ Submitted:</span>
                            <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></span>
                        </div>
                    </div>

                    <div class="approval-process">
                        <h3>📋 What Happens Next?</h3>
                        <div class="process-steps">
                            <div class="step completed">
                                <div class="step-icon">✅</div>
                                <div class="step-content">
                                    <h4>Quiz Submitted</h4>
                                    <p>Your quiz has been successfully submitted with <?php echo $question_count; ?> questions.</p>
                                </div>
                            </div>
                            <div class="step pending">
                                <div class="step-icon">⏳</div>
                                <div class="step-content">
                                    <h4>Admin Review</h4>
                                    <p>An administrator will review your quiz content and questions for quality and appropriateness.</p>
                                </div>
                            </div>
                            <div class="step future">
                                <div class="step-icon">🎯</div>
                                <div class="step-content">
                                    <h4>Go Live</h4>
                                    <p>Once approved, your quiz will appear on the home page for all users to take and enjoy!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="user-actions">
                        <a href="index.php" class="btn-primary">🏠 Back to Home</a>
                        <a href="create_quiz.php" class="btn-secondary">➕ Create Another Quiz</a>
                        <a href="leaderboard.php" class="btn-tertiary">🏆 View Leaderboard</a>
                    </div>

                    <div class="helpful-tips">
                        <h3>💡 Tips for Approval</h3>
                        <ul>
                            <li>✅ Ensure questions are clear and well-written</li>
                            <li>✅ Make sure correct answers are accurate</li>
                            <li>✅ Keep content appropriate and educational</li>
                            <li>✅ Add variety in question difficulty</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
