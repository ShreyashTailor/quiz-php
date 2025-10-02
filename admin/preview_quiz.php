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

// Handle quiz actions
if ($_POST) {
    if (isset($_POST['delete_quiz'])) {
        // Delete questions first (foreign key constraint)
        mysqli_query($conn, "DELETE FROM questions WHERE quiz_id = $quiz_id");
        // Delete leaderboard entries
        mysqli_query($conn, "DELETE FROM leaderboard WHERE quiz_id = $quiz_id");
        // Delete quiz
        mysqli_query($conn, "DELETE FROM quizzes WHERE id = $quiz_id");
        
        header("Location: manage_quizzes.php?deleted=1");
        exit;
    }
    
    if (isset($_POST['approve_quiz']) || isset($_POST['reject_quiz'])) {
        $status = isset($_POST['approve_quiz']) ? 'approved' : 'rejected';
        $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
        
        mysqli_query($conn, "UPDATE quizzes SET 
                            status = '$status', 
                            admin_notes = '$admin_notes',
                            approved_by = {$_SESSION['user_id']},
                            approved_at = NOW()
                            WHERE id = $quiz_id");
        
        $success_message = "Quiz " . $status . " successfully!";
    }
}

$result = mysqli_query($conn, "SELECT * FROM quizzes WHERE id=$quiz_id");
$quiz = mysqli_fetch_assoc($result);

if (!$quiz) {
    die("Quiz not found!");
}

$questions = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id=$quiz_id");
$question_count = mysqli_num_rows($questions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($quiz['title']); ?> - Admin</title>
    <link rel="stylesheet" href="../shadcn-style.css">
</head>
<body class="font-sans bg-background text-foreground">
    <nav class="navigation">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>🎯 QuizMaster</h1>
            </div>
            <div class="nav-links">
                <span class="badge badge-secondary">👑 Admin Preview</span>
                <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                <a href="../logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold mb-2">📋 Quiz Preview</h1>
                <p class="text-muted-foreground text-lg">Review and manage this quiz submission</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success mb-6">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">✅</span>
                        <span><?php echo $success_message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quiz Details -->
            <div class="card mb-8">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <h3 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h3>
                        <?php 
                        $status_class = '';
                        switch($quiz['status']) {
                            case 'approved': $status_class = 'badge-default'; break;
                            case 'pending': $status_class = 'badge-secondary'; break;
                            case 'rejected': $status_class = 'badge-destructive'; break;
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>">
                            <?php 
                            switch($quiz['status']) {
                                case 'pending': echo '⏳ Pending Approval'; break;
                                case 'approved': echo '✅ Approved'; break;
                                case 'rejected': echo '❌ Rejected'; break;
                            }
                            ?>
                        </span>
                    </div>
                    <p class="card-description"><?php echo htmlspecialchars($quiz['description']); ?></p>
                </div>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center space-x-2">
                                <span>👤</span>
                                <span class="font-medium">Created by:</span>
                            </div>
                            <span><?php echo htmlspecialchars($quiz['creator_name']); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center space-x-2">
                                <span>📅</span>
                                <span class="font-medium">Submitted:</span>
                            </div>
                            <span><?php echo date('M j, Y g:i A', strtotime($quiz['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                            <div class="flex items-center space-x-2">
                                <span>❓</span>
                                <span class="font-medium">Total Questions:</span>
                            </div>
                            <span class="font-bold"><?php echo $question_count; ?></span>
                        </div>
                        <?php if ($quiz['admin_notes']): ?>
                            <div class="flex items-center justify-between p-3 bg-muted rounded-lg">
                                <div class="flex items-center space-x-2">
                                    <span>📝</span>
                                    <span class="font-medium">Admin Notes:</span>
                                </div>
                                <span><?php echo htmlspecialchars($quiz['admin_notes']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Admin Actions -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">🛠️ Admin Actions</h3>
                    <p class="card-description">Manage this quiz</p>
                </div>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Edit Quiz -->
                        <a href="../add_questions.php?quiz_id=<?php echo $quiz_id; ?>" 
                           class="btn btn-primary w-full">
                            ✏️ Edit Questions
                        </a>
                        
                        <!-- Delete Quiz -->
                        <form method="post" style="display: inline;" class="w-full"
                              onsubmit="return confirm('Are you sure you want to delete this quiz? This action cannot be undone and will remove all questions and user attempts.')">
                            <button type="submit" name="delete_quiz" 
                                    class="btn btn-destructive w-full">
                                🗑️ Delete Quiz
                            </button>
                        </form>
                        
                        <!-- Take Quiz (if approved) -->
                        <?php if ($quiz['status'] === 'approved'): ?>
                            <a href="../quiz.php?id=<?php echo $quiz_id; ?>" 
                               class="btn btn-secondary w-full" target="_blank">
                                🎯 Take Quiz
                            </a>
                        <?php endif; ?>
                        
                        <!-- Manage All Quizzes -->
                        <a href="manage_quizzes.php" class="btn btn-outline w-full">
                            📝 Manage All Quizzes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Questions Preview -->
            <?php if ($question_count == 0): ?>
                <div class="card mb-8">
                    <div class="card-content text-center p-8">
                        <div class="text-6xl mb-4">⚠️</div>
                        <h3 class="text-lg font-semibold mb-2">No Questions Available</h3>
                        <p class="text-muted-foreground mb-4">This quiz has no questions yet. Questions are required before the quiz can be approved.</p>
                        <a href="../add_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                            ➕ Add Questions
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">📝 Questions Preview</h3>
                        <p class="card-description"><?php echo $question_count; ?> question(s) in this quiz</p>
                    </div>
                    <div class="card-content">
                        <div class="space-y-6">
                            <?php 
                            $q_num = 1;
                            mysqli_data_seek($questions, 0);
                            while ($q = mysqli_fetch_assoc($questions)): 
                            ?>
                                <div class="border rounded-lg p-6">
                                    <h4 class="text-primary font-semibold mb-3">Question <?php echo $q_num; ?></h4>
                                    <p class="font-medium mb-4"><?php echo htmlspecialchars($q['question']); ?></p>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="p-3 rounded-lg <?php echo ($q['correct_option'] === 'A') ? 'bg-green-100 dark:bg-green-900/30 border-green-200 dark:border-green-800' : 'bg-muted'; ?>">
                                            <div class="flex items-center justify-between">
                                                <span><strong>A)</strong> <?php echo htmlspecialchars($q['option_a']); ?></span>
                                                <?php if ($q['correct_option'] === 'A'): ?>
                                                    <span class="text-green-600 font-medium">✅ Correct</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="p-3 rounded-lg <?php echo ($q['correct_option'] === 'B') ? 'bg-green-100 dark:bg-green-900/30 border-green-200 dark:border-green-800' : 'bg-muted'; ?>">
                                            <div class="flex items-center justify-between">
                                                <span><strong>B)</strong> <?php echo htmlspecialchars($q['option_b']); ?></span>
                                                <?php if ($q['correct_option'] === 'B'): ?>
                                                    <span class="text-green-600 font-medium">✅ Correct</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="p-3 rounded-lg <?php echo ($q['correct_option'] === 'C') ? 'bg-green-100 dark:bg-green-900/30 border-green-200 dark:border-green-800' : 'bg-muted'; ?>">
                                            <div class="flex items-center justify-between">
                                                <span><strong>C)</strong> <?php echo htmlspecialchars($q['option_c']); ?></span>
                                                <?php if ($q['correct_option'] === 'C'): ?>
                                                    <span class="text-green-600 font-medium">✅ Correct</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="p-3 rounded-lg <?php echo ($q['correct_option'] === 'D') ? 'bg-green-100 dark:bg-green-900/30 border-green-200 dark:border-green-800' : 'bg-muted'; ?>">
                                            <div class="flex items-center justify-between">
                                                <span><strong>D)</strong> <?php echo htmlspecialchars($q['option_d']); ?></span>
                                                <?php if ($q['correct_option'] === 'D'): ?>
                                                    <span class="text-green-600 font-medium">✅ Correct</span>
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
                </div>
            <?php endif; ?>

            <!-- Status Management (for pending quizzes) -->
            <?php if ($quiz['status'] === 'pending'): ?>
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">✅ Approval Actions</h3>
                        <p class="card-description">Review and change quiz status</p>
                    </div>
                    <div class="card-content">
                        <form method="post" class="space-y-4">
                            <div class="form-group">
                                <label class="label">Admin Notes (Optional):</label>
                                <textarea name="admin_notes" class="textarea" rows="3" 
                                          placeholder="Add notes about your decision..."></textarea>
                            </div>
                            
                            <div class="flex space-x-4">
                                <button type="submit" name="approve_quiz" class="btn btn-primary flex-1"
                                        onclick="return confirm('Are you sure you want to approve this quiz?')">
                                    ✅ Approve Quiz
                                </button>
                                <button type="submit" name="reject_quiz" class="btn btn-destructive flex-1"
                                        onclick="return confirm('Are you sure you want to reject this quiz?')">
                                    ❌ Reject Quiz
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation -->
            <div class="text-center">
                <a href="dashboard.php" class="btn btn-outline">
                    🛠️ Back to Dashboard
                </a>
            </div>
        </div>
    </main>

    <script src="../dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin preview page loaded with Shadcn UI!');
        });
    </script>
</body>
</html>

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

        <!-- Admin Action Buttons (Modern Shadcn UI) -->
        <?php if ($quiz['status'] === 'pending'): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">✅ Approval Actions</h2>
                    <p class="text-sm text-muted-foreground">Review and change quiz status</p>
                </div>
                <div class="card-content">
                    <form method="post" class="space-y-4">
                        <div class="form-group">
                            <label class="label">Admin Notes (Optional):</label>
                            <textarea name="admin_notes" class="textarea" rows="3" 
                                      placeholder="Add notes about your decision..."></textarea>
                        </div>
                        
                        <div class="flex space-x-4">
                            <button type="submit" name="approve_quiz" class="btn btn-primary flex-1"
                                    onclick="return confirm('Are you sure you want to approve this quiz?')">
                                ✅ Approve Quiz
                            </button>
                            <button type="submit" name="reject_quiz" class="btn btn-destructive flex-1"
                                    onclick="return confirm('Are you sure you want to reject this quiz?')">
                                ❌ Reject Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Actions -->
        <div class="text-center">
            <a href="dashboard.php" class="btn btn-outline">🛠️ Back to Dashboard</a>
            <?php if ($quiz['status'] === 'approved'): ?>
                <a href="../quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary ml-4">🎯 Take Quiz</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
