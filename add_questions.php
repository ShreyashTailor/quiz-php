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

// Check if user owns this quiz or is admin
if ($quiz['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['question'])) {
        // Adding a question
        $question = mysqli_real_escape_string($conn, $_POST['question']);
        $a = mysqli_real_escape_string($conn, $_POST['option_a']);
        $b = mysqli_real_escape_string($conn, $_POST['option_b']);
        $c = mysqli_real_escape_string($conn, $_POST['option_c']);
        $d = mysqli_real_escape_string($conn, $_POST['option_d']);
        $correct = $_POST['correct'];

        mysqli_query($conn, "INSERT INTO questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_option) 
            VALUES ('$quiz_id','$question','$a','$b','$c','$d','$correct')");
            
        $success_message = "Question added successfully!";
    }
    
    if (isset($_POST['finish_quiz'])) {
        // Finishing the quiz - redirect to success page
        header("Location: quiz_submitted.php?quiz_id=$quiz_id");
        exit;
    }
}

// Get existing questions for this quiz
$questions_result = mysqli_query($conn, "SELECT * FROM questions WHERE quiz_id = $quiz_id ORDER BY id");
$question_count = mysqli_num_rows($questions_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Questions - <?php echo htmlspecialchars($quiz['title']); ?></title>
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
                <span class="text-muted-foreground">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-outline">🚪 Logout</a>
                <button id="theme-toggle" class="btn btn-outline" aria-label="Toggle theme">🌙</button>
            </div>
        </div>
    </nav>

    <main class="container py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Quiz Header -->
            <div class="card mb-8">
                <div class="card-header text-center">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <span class="text-2xl">📝</span>
                        <h1 class="text-2xl font-bold">Adding Questions to:</h1>
                    </div>
                    <h2 class="text-xl text-primary font-semibold"><?php echo htmlspecialchars($quiz['title']); ?></h2>
                    <p class="text-muted-foreground mt-2"><?php echo htmlspecialchars($quiz['description']); ?></p>
                </div>
                <div class="card-content">
                    <div class="flex justify-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <span class="badge badge-default">Questions Added: <?php echo $question_count; ?></span>
                        </div>
                        <?php if ($quiz['status'] === 'pending'): ?>
                            <span class="badge badge-secondary">⏳ Pending Approval</span>
                        <?php elseif ($quiz['status'] === 'approved'): ?>
                            <span class="badge badge-default">✅ Approved</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success mb-6">
                    <div class="flex items-center">
                        <span class="text-lg mr-2">✅</span>
                        <span><?php echo $success_message; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add Question Form -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">➕ Add New Question</h3>
                    <p class="card-description">Create a multiple choice question with 4 options</p>
                </div>
                <div class="card-content">
                    <form method="post" class="space-y-6">
                        <div class="form-group">
                            <label class="label">Question</label>
                            <textarea name="question" class="textarea" rows="3" required 
                                      placeholder="Enter your question here..."></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Option A</label>
                                <input type="text" name="option_a" class="input" required 
                                       placeholder="Enter option A">
                            </div>
                            <div class="form-group">
                                <label class="label">Option B</label>
                                <input type="text" name="option_b" class="input" required 
                                       placeholder="Enter option B">
                            </div>
                            <div class="form-group">
                                <label class="label">Option C</label>
                                <input type="text" name="option_c" class="input" required 
                                       placeholder="Enter option C">
                            </div>
                            <div class="form-group">
                                <label class="label">Option D</label>
                                <input type="text" name="option_d" class="input" required 
                                       placeholder="Enter option D">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="label">Correct Answer</label>
                            <select name="correct" class="select" required>
                                <option value="">Select correct answer</option>
                                <option value="A">Option A</option>
                                <option value="B">Option B</option>
                                <option value="C">Option C</option>
                                <option value="D">Option D</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full">
                            ➕ Add Question
                        </button>
                    </form>
                </div>
            </div>

            <!-- Existing Questions Preview -->
            <?php if ($question_count > 0): ?>
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">📋 Questions Added</h3>
                        <p class="card-description"><?php echo $question_count; ?> question(s) created so far</p>
                    </div>
                    <div class="card-content">
                        <div class="space-y-6">
                            <?php 
                            $q_num = 1;
                            mysqli_data_seek($questions_result, 0);
                            while ($q = mysqli_fetch_assoc($questions_result)): 
                            ?>
                                <div class="border rounded-lg p-4">
                                    <h4 class="font-semibold mb-3">Q<?php echo $q_num; ?>: <?php echo htmlspecialchars($q['question']); ?></h4>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="p-2 rounded <?php echo ($q['correct_option'] === 'A') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-muted'; ?>">
                                            <span class="font-medium">A)</span> <?php echo htmlspecialchars($q['option_a']); ?>
                                            <?php if ($q['correct_option'] === 'A'): ?><span class="text-green-600 ml-2">✓</span><?php endif; ?>
                                        </div>
                                        <div class="p-2 rounded <?php echo ($q['correct_option'] === 'B') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-muted'; ?>">
                                            <span class="font-medium">B)</span> <?php echo htmlspecialchars($q['option_b']); ?>
                                            <?php if ($q['correct_option'] === 'B'): ?><span class="text-green-600 ml-2">✓</span><?php endif; ?>
                                        </div>
                                        <div class="p-2 rounded <?php echo ($q['correct_option'] === 'C') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-muted'; ?>">
                                            <span class="font-medium">C)</span> <?php echo htmlspecialchars($q['option_c']); ?>
                                            <?php if ($q['correct_option'] === 'C'): ?><span class="text-green-600 ml-2">✓</span><?php endif; ?>
                                        </div>
                                        <div class="p-2 rounded <?php echo ($q['correct_option'] === 'D') ? 'bg-green-100 dark:bg-green-900/30' : 'bg-muted'; ?>">
                                            <span class="font-medium">D)</span> <?php echo htmlspecialchars($q['option_d']); ?>
                                            <?php if ($q['correct_option'] === 'D'): ?><span class="text-green-600 ml-2">✓</span><?php endif; ?>
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

            <!-- Action Buttons -->
            <div class="card">
                <div class="card-content">
                    <div class="flex flex-col space-y-4">
                        <?php if ($question_count >= 1): ?>
                            <form method="post">
                                <button type="submit" name="finish_quiz" class="btn btn-primary w-full">
                                    ✅ Finish Quiz Creation
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <p>⚠️ Please add at least 1 question before finishing the quiz.</p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex space-x-4">
                            <?php if ($quiz['status'] === 'approved'): ?>
                                <a href="quiz.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary flex-1">
                                    👀 Preview Quiz
                                </a>
                            <?php endif; ?>
                            
                            <a href="index.php" class="btn btn-outline flex-1">
                                🏠 Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Add questions page loaded with Shadcn UI!');
        });
    </script>
</body>
</html>
