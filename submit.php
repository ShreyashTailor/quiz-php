<?php
session_start();
include 'db.php';

$quiz_id = $_GET['id'];
$answers = $_POST['ans'];
$score = 0;

foreach ($answers as $qid => $ans) {
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT correct_option FROM questions WHERE id=$qid"));
    if ($q['correct_option'] == $ans) {
        $score++;
    }
}
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id"));

// Get quiz details
$quiz_result = mysqli_query($conn, "SELECT title FROM quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($quiz_result);

// Save score to leaderboard if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $percentage = ($total > 0) ? round(($score / $total) * 100, 2) : 0;
    $completion_time = date('Y-m-d H:i:s');
    
    // Insert score into leaderboard table
    $insert_query = "INSERT INTO leaderboard (user_id, username, quiz_id, quiz_title, score, total_questions, percentage, completion_time) 
                     VALUES ('$user_id', '$username', '$quiz_id', '" . mysqli_real_escape_string($conn, $quiz['title']) . "', '$score', '$total', '$percentage', '$completion_time')";
    mysqli_query($conn, $insert_query);
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
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="text-muted-foreground">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
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
                            $badge_class = '';
                            $message = '';
                            if ($percentage >= 90) {
                                $badge_class = 'badge-default';
                                $message = "🌟 Excellent! Outstanding performance!";
                            } elseif ($percentage >= 75) {
                                $badge_class = 'badge-secondary';
                                $message = "👏 Great job! Well done!";
                            } elseif ($percentage >= 60) {
                                $badge_class = 'badge-secondary';
                                $message = "👍 Good work! Keep it up!";
                            } elseif ($percentage >= 40) {
                                $badge_class = 'badge-outline';
                                $message = "📚 Not bad! Keep practicing!";
                            } else {
                                $badge_class = 'badge-outline';
                                $message = "💪 Keep trying! Practice makes perfect!";
                            }
                            ?>
                            <div class="inline-flex items-center px-4 py-2 rounded-full <?php 
                                if ($percentage >= 90) echo 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300';
                                elseif ($percentage >= 70) echo 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
                                elseif ($percentage >= 50) echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300';
                                else echo 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300';
                            ?>">
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
                        <?php if (isset($_SESSION['user_id'])): ?>
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
                        <div class="text-4xl mb-3">�</div>
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
                // Add confetti effect or celebration animation
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
