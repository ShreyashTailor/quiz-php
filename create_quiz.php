<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_POST) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $desc = mysqli_real_escape_string($conn, $_POST['desc']);
    $created_by = $_SESSION['user_id'];
    $creator_name = mysqli_real_escape_string($conn, $_SESSION['username']);
    
    // Set status based on user role
    $status = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'approved' : 'pending';

    mysqli_query($conn, "INSERT INTO quizzes (title, description, created_by, creator_name, status) 
                        VALUES ('$title','$desc','$created_by','$creator_name','$status')");

    $quiz_id = mysqli_insert_id($conn);

    header("Location: add_questions.php?quiz_id=$quiz_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz - QuizMaster</title>
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
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">📝 Create a New Quiz</h1>
                <p class="text-muted-foreground text-lg">Choose how you'd like to create your quiz</p>
            </div>

            <!-- Quiz Creation Options -->
            <div class="grid grid-cols-2 gap-8 mb-8">
                <!-- Manual Quiz Creation -->
                <div class="card">
                    <div class="card-header text-center">
                        <div class="text-6xl mb-4">✍️</div>
                        <h3 class="card-title text-xl">Manual Quiz Creation</h3>
                        <p class="card-description">Create quiz questions manually with full control over content and structure</p>
                    </div>
                    <div class="card-content">
                        <form method="post" class="space-y-4">
                            <div class="form-group">
                                <label class="label">Quiz Title</label>
                                <input type="text" name="title" class="input" required 
                                       placeholder="Enter an engaging quiz title...">
                            </div>
                            
                            <div class="form-group">
                                <label class="label">Description</label>
                                <textarea name="desc" class="textarea" rows="4" 
                                          placeholder="Describe what your quiz covers and who it's for..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-full">
                                ✍️ Create Manual Quiz
                            </button>
                        </form>
                    </div>
                </div>

                <!-- AI Quiz Creation -->
                <div class="card">
                    <div class="card-header text-center">
                        <div class="text-6xl mb-4">🤖</div>
                        <h3 class="card-title text-xl">AI Quiz Generator</h3>
                        <p class="card-description">Upload a PDF and let AI generate quiz questions automatically</p>
                    </div>
                    <div class="card-content">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-green-500">✨</span>
                                <span>Automatically extract content from PDF</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-green-500">🧠</span>
                                <span>AI-powered question generation</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-green-500">⚡</span>
                                <span>Fast and intelligent quiz creation</span>
                            </div>
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="text-green-500">📚</span>
                                <span>Perfect for study materials</span>
                            </div>
                        </div>
                        
                        <a href="coming_soon.php" class="btn btn-secondary w-full">
                            🤖 Create AI Quiz from PDF
                        </a>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">💡 Quiz Creation Tips</h3>
                </div>
                <div class="card-content">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-2">Manual Creation</h4>
                            <ul class="text-sm text-muted-foreground space-y-1">
                                <li>• Write clear, concise questions</li>
                                <li>• Include diverse question types</li>
                                <li>• Test your quiz before publishing</li>
                                <li>• Add detailed explanations</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-2">AI Generation</h4>
                            <ul class="text-sm text-muted-foreground space-y-1">
                                <li>• Upload clear, well-formatted PDFs</li>
                                <li>• AI generates multiple choice questions</li>
                                <li>• Review and edit generated content</li>
                                <li>• Best for educational materials</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center">
                <a href="index.php" class="btn btn-outline">
                    🏠 Back to Home
                </a>
            </div>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Create quiz page loaded with Shadcn UI!');
        });
    </script>
</body>
</html>
