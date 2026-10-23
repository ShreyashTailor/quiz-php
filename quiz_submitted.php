<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

requireLogin();

$db = db();

$quiz_id = sanitizeInt($_GET['quiz_id'] ?? 0, 1);

// Get quiz details using prepared statement
$quiz = $db->fetchOne(
    "SELECT * FROM quizzes WHERE id = ?",
    [$quiz_id],
    'i'
);

if (!$quiz) {
    header("Location: index.php");
    exit;
}

// Check if user owns this quiz
if ($quiz['created_by'] != getCurrentUserId() && !isAdmin()) {
    header("Location: index.php");
    exit;
}

// Get question count using prepared statement
$question_count = $db->fetchColumn(
    "SELECT COUNT(*) FROM questions WHERE quiz_id = ?",
    [$quiz_id],
    'i'
) ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Submitted - QuizMaster</title>
    <link rel="stylesheet" href="shadcn-style.css">
</head>
<body class="font-sans bg-background text-foreground">
    <nav class="navigation">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>🎯 QuizMaster</h1>
            </div>
            <div class="nav-links">
                <a href="index.php" class="btn btn-outline">🏠 Home</a>
                <span class="text-muted-foreground">Hello, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</span>
                <a href="logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-4xl mx-auto">
            <?php if (isAdmin()): ?>
                <!-- Admin created quiz - auto approved -->
                <div class="text-center mb-8">
                    <div class="text-8xl mb-6">🎉</div>
                    <h1 class="text-4xl font-bold mb-4">Quiz Created Successfully!</h1>
                    <p class="text-xl text-muted-foreground">
                        Congratulations! Your quiz "<strong><?php echo htmlspecialchars($quiz['title']); ?></strong>" 
                        has been created and is now live on the platform.
                    </p>
                </div>

                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">📊 Quiz Details</h3>
                    </div>
                    <div class="card-content">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center space-x-2">
                                <span>📚</span>
                                <span class="font-medium">Quiz Title:</span>
                                <span><?php echo htmlspecialchars($quiz['title']); ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span>❓</span>
                                <span class="font-medium">Questions Added:</span>
                                <span><?php echo $question_count; ?></span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span>📊</span>
                                <span class="font-medium">Status:</span>
                                <span class="badge badge-default">✅ Live & Available</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                        🎯 Take Your Quiz
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        🏠 Back to Home
                    </a>
                    <a href="create_quiz.php" class="btn btn-outline">
                        ➕ Create Another Quiz
                    </a>
                </div>

            <?php else: ?>
                <!-- Regular user created quiz - needs approval -->
                <div class="text-center mb-8">
                    <div class="text-8xl mb-6">🙏</div>
                    <h1 class="text-4xl font-bold mb-4">Thanks for Adding the Quiz!</h1>
                    <p class="text-xl text-muted-foreground">
                        Your quiz "<strong><?php echo htmlspecialchars($quiz['title']); ?></strong>" has been submitted successfully. 
                        An admin will review and approve your quiz, and it will then appear on the home page for everyone to enjoy!
                    </p>
                </div>

                <!-- Quiz Details -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">📊 Quiz Summary</h3>
                    </div>
                    <div class="card-content">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>📚</span>
                                    <span class="font-medium">Quiz Title:</span>
                                </div>
                                <span><?php echo htmlspecialchars($quiz['title']); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>📝</span>
                                    <span class="font-medium">Description:</span>
                                </div>
                                <span><?php echo htmlspecialchars($quiz['description']); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>❓</span>
                                    <span class="font-medium">Questions Added:</span>
                                </div>
                                <span class="font-bold"><?php echo $question_count; ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>📊</span>
                                    <span class="font-medium">Status:</span>
                                </div>
                                <span class="badge badge-secondary">⏳ Pending Admin Approval</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>⏰</span>
                                    <span class="font-medium">Submitted:</span>
                                </div>
                                <span><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What Happens Next -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">📋 What Happens Next?</h3>
                    </div>
                    <div class="card-content">
                        <div class="space-y-6">
                            <!-- Step 1 - Completed -->
                            <div class="flex items-start space-x-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-semibold text-green-700 dark:text-green-300">Quiz Submitted</h4>
                                    <p class="text-sm text-green-600 dark:text-green-400">
                                        Your quiz has been successfully submitted with <?php echo $question_count; ?> questions.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 2 - In Progress -->
                            <div class="flex items-start space-x-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white">
                                    ⏳
                                </div>
                                <div>
                                    <h4 class="font-semibold text-blue-700 dark:text-blue-300">Admin Review</h4>
                                    <p class="text-sm text-blue-600 dark:text-blue-400">
                                        An administrator will review your quiz content and questions for quality and appropriateness.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 3 - Future -->
                            <div class="flex items-start space-x-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                                <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white">
                                    🎯
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700 dark:text-gray-300">Go Live</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        Once approved, your quiz will appear on the home page for all users to take and enjoy!
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-center space-x-4 mb-8">
                    <a href="index.php" class="btn btn-primary">
                        🏠 Back to Home
                    </a>
                    <a href="create_quiz.php" class="btn btn-secondary">
                        ➕ Create Another Quiz
                    </a>
                    <a href="leaderboard.php" class="btn btn-outline">
                        🏆 View Leaderboard
                    </a>
                </div>

                <!-- Tips for Approval -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">💡 Tips for Approval</h3>
                        <p class="card-description">Follow these guidelines to increase your chances of approval</p>
                    </div>
                    <div class="card-content">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-green-500">✅</span>
                                <span class="text-sm">Ensure questions are clear and well-written</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-green-500">✅</span>
                                <span class="text-sm">Make sure correct answers are accurate</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-green-500">✅</span>
                                <span class="text-sm">Keep content appropriate and educational</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="text-green-500">✅</span>
                                <span class="text-sm">Add variety in question difficulty</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Quiz submitted page loaded with Shadcn UI!');
            
            // Add celebration animation for admin users
            <?php if (isAdmin()): ?>
            setTimeout(() => {
                const emoji = document.querySelector('.text-8xl');
                emoji.style.animation = 'bounce 2s ease-in-out infinite';
            }, 500);
            <?php endif; ?>
        });
    </script>

    <style>
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        .line-clamp-2 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
    </style>
</body>
</html>