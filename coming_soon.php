<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Quiz Generator - Coming Soon | QuizMaster</title>
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
                <a href="create_quiz.php" class="btn btn-secondary">📝 Create Quiz</a>
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
        <div class="max-w-4xl mx-auto text-center">
            <!-- Hero Section -->
            <div class="mb-12">
                <div class="text-8xl mb-6">🤖</div>
                <h1 class="text-5xl font-bold mb-4">AI Quiz Generator</h1>
                <p class="text-xl text-muted-foreground mb-8">
                    Revolutionary AI-powered quiz creation is coming soon!
                </p>
                <div class="inline-flex items-center px-4 py-2 bg-primary/10 rounded-full">
                    <span class="w-2 h-2 bg-primary rounded-full animate-pulse mr-2"></span>
                    <span class="text-primary font-medium">Currently in Development</span>
                </div>
            </div>

            <!-- Features Preview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">📄</div>
                        <h3 class="font-semibold mb-2">PDF Upload</h3>
                        <p class="text-sm text-muted-foreground">
                            Upload any PDF document and our AI will analyze the content
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">🧠</div>
                        <h3 class="font-semibold mb-2">Smart Analysis</h3>
                        <p class="text-sm text-muted-foreground">
                            AI extracts key concepts and generates relevant questions
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">⚡</div>
                        <h3 class="font-semibold mb-2">Instant Creation</h3>
                        <p class="text-sm text-muted-foreground">
                            Generate complete quizzes in seconds, not hours
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">🎯</div>
                        <h3 class="font-semibold mb-2">Multiple Choice</h3>
                        <p class="text-sm text-muted-foreground">
                            Automatically generates questions with 4 answer options
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">✏️</div>
                        <h3 class="font-semibold mb-2">Easy Editing</h3>
                        <p class="text-sm text-muted-foreground">
                            Review and customize AI-generated questions before publishing
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-content text-center">
                        <div class="text-4xl mb-4">🔬</div>
                        <h3 class="font-semibold mb-2">Quality Control</h3>
                        <p class="text-sm text-muted-foreground">
                            Advanced algorithms ensure high-quality, relevant questions
                        </p>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card mb-12">
                <div class="card-header">
                    <h3 class="card-title">🚀 Development Timeline</h3>
                    <p class="card-description">Track our progress on the AI Quiz Generator</p>
                </div>
                <div class="card-content">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">Phase 1: Research & Planning</p>
                                <p class="text-sm text-muted-foreground">AI model selection and architecture design</p>
                            </div>
                            <span class="badge badge-default">Completed</span>
                        </div>
                        
                        <div class="flex items-center space-x-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="w-4 h-4 bg-blue-500 rounded-full animate-pulse"></div>
                            <div class="flex-1">
                                <p class="font-medium">Phase 2: AI Integration</p>
                                <p class="text-sm text-muted-foreground">Implementing PDF processing and question generation</p>
                            </div>
                            <span class="badge badge-secondary">In Progress</span>
                        </div>
                        
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                            <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">Phase 3: Testing & Optimization</p>
                                <p class="text-sm text-muted-foreground">Quality assurance and performance tuning</p>
                            </div>
                            <span class="badge badge-outline">Upcoming</span>
                        </div>
                        
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg">
                            <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                            <div class="flex-1">
                                <p class="font-medium">Phase 4: Launch</p>
                                <p class="text-sm text-muted-foreground">Public release and user feedback integration</p>
                            </div>
                            <span class="badge badge-outline">Q1 2026</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stay Updated -->
            <div class="card mb-8">
                <div class="card-header">
                    <h3 class="card-title">📧 Stay Updated</h3>
                    <p class="card-description">Be the first to know when AI Quiz Generator launches</p>
                </div>
                <div class="card-content">
                    <div class="max-w-md mx-auto">
                        <form class="flex space-x-2">
                            <input type="email" class="input flex-1" placeholder="Enter your email address" disabled>
                            <button type="submit" class="btn btn-primary" disabled>
                                Notify Me
                            </button>
                        </form>
                        <p class="text-xs text-muted-foreground mt-2">
                            Email notifications coming soon! For now, check back regularly for updates.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="space-y-4">
                <h3 class="text-xl font-semibold">In the meantime...</h3>
                <p class="text-muted-foreground mb-6">
                    Try our manual quiz creator to build amazing quizzes right now!
                </p>
                <div class="flex justify-center space-x-4">
                    <a href="create_quiz.php" class="btn btn-primary">
                        ✍️ Create Manual Quiz
                    </a>
                    <a href="index.php" class="btn btn-outline">
                        🏠 Back to Home
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Coming soon page loaded with Shadcn UI!');
            
            // Add some interactive animations
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.animation = 'fadeInUp 0.6s ease-out forwards';
                }, index * 100);
            });
        });
    </script>

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card {
            opacity: 0;
        }
    </style>
</body>
</html>
