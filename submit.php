<?php
require_once __DIR__ . '/security.php';

initSecureSession();
setSecurityHeaders();

$db = db();

$quiz_id = sanitizeInt($_GET['id'] ?? 0, 1);
$answers = $_POST['ans'] ?? [];
$score = 0;

// Validate quiz exists and is approved (or admin)
$quiz = $db->fetchOne(
    "SELECT id, title FROM quizzes WHERE id = ? AND (status = 'approved' OR ? = 1)",
    [$quiz_id, isAdmin() ? 1 : 0],
    'ii'
);

if (!$quiz) {
    die("Quiz not found or not accessible!");
}

// Fetch correct answers using prepared statement
if (!empty($answers)) {
    $questionIds = array_keys($answers);
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    
    $correctAnswers = $db->fetchAll(
        "SELECT id, correct_option FROM questions WHERE id IN ($placeholders)",
        $questionIds,
        str_repeat('i', count($questionIds))
    );
    
    $correctMap = [];
    foreach ($correctAnswers as $ca) {
        $correctMap[$ca['id']] = $ca['correct_option'];
    }
    
    foreach ($answers as $qid => $ans) {
        if (isset($correctMap[$qid]) && $correctMap[$qid] === $ans) {
            $score++;
        }
    }
}

$total = $db->fetchColumn(
    "SELECT COUNT(*) FROM questions WHERE quiz_id = ?",
    [$quiz_id],
    'i'
) ?: 0;

$percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;

// Save score to leaderboard if user is logged in
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    $username = getCurrentUsername();
    $completion_time = date('Y-m-d H:i:s');
    
    // Insert score into leaderboard table using prepared statement
    $db->executeNonQuery(
        "INSERT INTO leaderboard (user_id, username, quiz_id, quiz_title, score, total_questions, percentage, completion_time) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$user_id, $username, $quiz_id, $quiz['title'], $score, $total, $percentage, $completion_time],
        'isssiisd'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Result - QuizMaster</title>
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
                <?php if (isLoggedIn()): ?>
                    <span class="text-muted-foreground">Hello, <?php echo htmlspecialchars(getCurrentUsername()); ?>!</span>
                    <a href="logout.php" class="btn btn-outline">🚪 Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">🔑 Login</a>
                <?php endif; ?>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Results Card -->
            <div class="card">
                <div class="card-header text-center">
                    <div class="text-6xl mb-4">🎉</div>
                    <h1 class="text-3xl font-bold mb-2">Quiz Completed!</h1>
                    <p class="text-muted-foreground">Great job on finishing the quiz!</p>
                </div>
                
                <div class="card-content">
                    <!-- Score Display -->
                    <div class="text-center mb-8">
                        <div class="mb-4">
                            <span class="text-lg text-muted-foreground">Your Score:</span>
                            <h2 class="text-4xl font-bold text-primary mt-1">
                                <?php echo $score; ?>/<?php echo $total; ?>
                            </h2>
                        </div>
                        
                        <div class="text-6xl font-bold mb-4 <?php 
                            if ($percentage >= 90) echo 'text-green-500';
                            elseif ($percentage >= 70) echo 'text-blue-500';
                            elseif ($percentage >= 50) echo 'text-yellow-500';
                            else echo 'text-red-500';
                        ?>">
                            <?php echo $percentage; ?>%
                        </div>
                        
                        <!-- Performance Badge -->
                        <div class="mb-6">
                            <?php
                            if ($percentage >= 90) {
                                $badge_class = 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300';
                                $message = "🌟 Excellent! Outstanding performance!";
                            } elseif ($percentage >= 75) {
                                $badge_class = 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
                                $message = "👏 Great job! Well done!";
                            } elseif ($percentage >= 60) {
                                $badge_class = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300';
                                $message = "👍 Good work! Keep it up!";
                            } elseif ($percentage >= 40) {
                                $badge_class = 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300';
                                $message = "📚 Not bad! Keep practicing!";
                            } else {
                                $badge_class = 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300';
                                $message = "💪 Keep trying! Practice makes perfect!";
                            }
                            ?>
                            <div class="inline-flex items-center px-4 py-2 rounded-full <?php echo $badge_class; ?>">
                                <?php echo $message; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-8">
                        <div class="flex justify-between text-sm text-muted-foreground mb-2">
                            <span>Progress</span>
                            <span><?php echo $score; ?> out of <?php echo $total; ?> correct</span>
                        </div>
                        <div class="w-full bg-muted rounded-full h-3">
                            <div class="h-3 rounded-full transition-all duration-300 <?php 
                                if ($percentage >= 90) echo 'bg-green-500';
                                elseif ($percentage >= 70) echo 'bg-blue-500';
                                elseif ($percentage >= 50) echo 'bg-yellow-500';
                                else echo 'bg-red-500';
                            ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>

                    <!-- Save Status -->
                    <div class="mb-8">
                        <?php if (isLoggedIn()): ?>
                            <div class="alert alert-success">
                                <div class="flex items-center">
                                    <span class="text-lg mr-2">✅</span>
                                    <span>Your score has been saved to the leaderboard!</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <div class="flex items-center">
                                    <span class="text-lg mr-2">💡</span>
                                    <span>
                                        <a href="login.php" class="text-primary hover:underline font-medium">Login</a> 
                                        to save your score to the leaderboard!
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-3 gap-4">
                        <a href="index.php" class="btn btn-primary text-center">
                            🏠 Back to Home
                        </a>
                        <a href="leaderboard.php" class="btn btn-secondary text-center">
                            🏆 View Leaderboard
                        </a>
                        <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-outline text-center">
                            🔄 Retake Quiz
                        </a>
                    </div>
                </div>
            </div>

            <!-- Performance Breakdown -->
            <div class="card mt-8">
                <div class="card-header">
                    <h3 class="card-title">📊 Performance Breakdown</h3>
                </div>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-500"><?php echo $score; ?></div>
                            <p class="text-sm text-muted-foreground">Correct Answers</p>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-500"><?php echo ($total - $score); ?></div>
                            <p class="text-sm text-muted-foreground">Incorrect Answers</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t">
                        <div class="flex justify-between items-center">
                            <span class="font-medium">Accuracy Rate:</span>
                            <span class="text-xl font-bold"><?php echo $percentage; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Motivational Message -->
            <div class="card mt-8">
                <div class="card-content text-center">
                    <?php if ($percentage >= 90): ?>
                        <div class="text-4xl mb-3">🏆</div>
                        <h3 class="text-xl font-semibold mb-2">Outstanding Achievement!</h3>
                        <p class="text-muted-foreground">You've mastered this topic! Consider challenging yourself with more quizzes.</p>
                    <?php elseif ($percentage >= 70): ?>
                        <div class="text-4xl mb-3">🎯</div>
                        <h3 class="text-xl font-semibold mb-2">Great Performance!</h3>
                        <p class="text-muted-foreground">You're doing really well! Keep up the excellent work.</p>
                    <?php elseif ($percentage >= 50): ?>
                        <div class="text-4xl mb-3">📈</div>
                        <h3 class="text-xl font-semibold mb-2">Good Effort!</h3>
                        <p class="text-muted-foreground">You're on the right track! A bit more practice and you'll excel.</p>
                    <?php else: ?>
                        <div class="text-4xl mb-3">💪</div>
                        <h3 class="text-xl font-semibold mb-2">Keep Going!</h3>
                        <p class="text-muted-foreground">Every expert was once a beginner. Keep practicing and you'll improve!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Quiz results page loaded with Shadcn UI!');
            
            // Add celebration effect for high scores
            const percentage = <?php echo $percentage; ?>;
            if (percentage >= 90) {
                const emoji = document.querySelector('.text-6xl');
                if (emoji) {
                    emoji.style.animation = 'bounce 2s ease-in-out infinite';
                }
            }
            
            // Animate progress bar
            setTimeout(() => {
                const progressBar = document.querySelector('.h-3.rounded-full');
                if (progressBar) {
                    progressBar.style.width = '0%';
                    setTimeout(() => {
                        progressBar.style.width = '<?php echo $percentage; ?>%';
                    }, 100);
                }
            }, 500);
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
    </style>
</body>
</html>