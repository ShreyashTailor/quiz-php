<?php
session_start();
include 'db.php';

// Set timezone to GMT+5:30
date_default_timezone_set('Asia/Kolkata');

// Get filter parameters
$quiz_filter = isset($_GET['quiz']) ? (int)$_GET['quiz'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'percentage';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Build query based on filters
$where_clause = "";
if ($quiz_filter !== 'all') {
    $where_clause = "WHERE quiz_id = $quiz_filter";
}

// Build ORDER BY clause
$valid_sorts = ['percentage', 'score', 'completion_time', 'username'];
$sort_by = in_array($sort_by, $valid_sorts) ? $sort_by : 'percentage';
$order = ($order === 'ASC') ? 'ASC' : 'DESC';

// Get leaderboard data
$leaderboard_query = "SELECT * FROM leaderboard $where_clause ORDER BY $sort_by $order, completion_time DESC";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);

// Get quiz statistics
$stats_query = "SELECT 
    COUNT(*) as total_attempts,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT quiz_id) as unique_quizzes,
    AVG(percentage) as avg_score,
    MAX(percentage) as highest_score
    FROM leaderboard $where_clause";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Get available quizzes for filter
$quizzes_query = "SELECT DISTINCT q.id, q.title FROM quizzes q 
                  INNER JOIN leaderboard l ON q.id = l.quiz_id 
                  ORDER BY q.title";
$quizzes_result = mysqli_query($conn, $quizzes_query);

// Get top performers
$top_performers_query = "SELECT username, AVG(percentage) as avg_percentage, COUNT(*) as quiz_count
                        FROM leaderboard 
                        GROUP BY username 
                        ORDER BY avg_percentage DESC, quiz_count DESC 
                        LIMIT 5";
$top_performers_result = mysqli_query($conn, $top_performers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - QuizMaster</title>
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
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">🏆 Leaderboard</h1>
                <p class="text-muted-foreground text-lg">See how you stack up against other quiz masters!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-3 gap-6 mb-6">
                <div class="card text-center">
                    <div class="card-content">
                        <div class="text-3xl font-bold text-primary"><?php echo number_format($stats['total_attempts']); ?></div>
                        <p class="text-sm text-muted-foreground">Total Attempts</p>
                    </div>
                </div>
                <div class="card text-center">
                    <div class="card-content">
                        <div class="text-3xl font-bold text-primary"><?php echo number_format($stats['unique_users']); ?></div>
                        <p class="text-sm text-muted-foreground">Active Users</p>
                    </div>
                </div>
                <div class="card text-center">
                    <div class="card-content">
                        <div class="text-3xl font-bold text-primary"><?php echo number_format($stats['avg_score'], 1); ?>%</div>
                        <p class="text-sm text-muted-foreground">Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">🔍 Filters & Controls</h3>
                    <p class="card-description">Customize your leaderboard view</p>
                </div>
                <div class="card-content">
                    <form method="get" class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">Filter by Quiz:</label>
                            <select name="quiz" class="select">
                                <option value="all" <?php echo ($quiz_filter === 'all') ? 'selected' : ''; ?>>All Quizzes</option>
                                <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
                                    <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz_filter == $quiz['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($quiz['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Sort by:</label>
                            <select name="sort" class="select">
                                <option value="percentage" <?php echo ($sort_by === 'percentage') ? 'selected' : ''; ?>>Score %</option>
                                <option value="completion_time" <?php echo ($sort_by === 'completion_time') ? 'selected' : ''; ?>>Completion Time</option>
                                <option value="username" <?php echo ($sort_by === 'username') ? 'selected' : ''; ?>>Username</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Order:</label>
                            <select name="order" class="select">
                                <option value="DESC" <?php echo ($order === 'DESC') ? 'selected' : ''; ?>>Highest First</option>
                                <option value="ASC" <?php echo ($order === 'ASC') ? 'selected' : ''; ?>>Lowest First</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-full">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📊 Leaderboard Rankings</h3>
                </div>
                <div class="card-content">
                    <?php if (mysqli_num_rows($leaderboard_result) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-4">Rank</th>
                                        <th class="text-left p-4">User</th>
                                        <th class="text-left p-4">Quiz</th>
                                        <th class="text-left p-4">Score</th>
                                        <th class="text-left p-4">Percentage</th>
                                        <th class="text-left p-4">Completed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $rank = 1;
                                    while ($entry = mysqli_fetch_assoc($leaderboard_result)): 
                                        $percentage = $entry['percentage'];
                                        $badge_class = '';
                                        if ($percentage >= 90) $badge_class = 'badge-default';
                                        elseif ($percentage >= 70) $badge_class = 'badge-secondary';
                                        else $badge_class = 'badge-outline';
                                    ?>
                                        <tr class="border-b hover:bg-muted/50">
                                            <td class="p-4">
                                                <div class="flex items-center">
                                                    <?php if ($rank <= 3): ?>
                                                        <span class="text-2xl mr-2">
                                                            <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="font-semibold">#<?php echo $rank; ?></span>
                                                </div>
                                            </td>
                                            <td class="p-4 font-medium"><?php echo htmlspecialchars($entry['username']); ?></td>
                                            <td class="p-4"><?php echo htmlspecialchars($entry['quiz_title']); ?></td>
                                            <td class="p-4"><?php echo $entry['score']; ?>/<?php echo $entry['total_questions']; ?></td>
                                            <td class="p-4">
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </span>
                                            </td>
                                            <td class="p-4 text-sm text-muted-foreground">
                                                <?php echo date('M j, Y g:i A', strtotime($entry['completion_time'])); ?>
                                            </td>
                                        </tr>
                                    <?php 
                                    $rank++;
                                    endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-6">
                            <div class="text-6xl mb-4">📈</div>
                            <h3 class="text-lg font-semibold mb-2">No Results Found</h3>
                            <p class="text-muted-foreground">No quiz attempts match your current filters.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="dark-mode.js"></script>
    <script>
        // Add sorting functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Leaderboard loaded with Shadcn UI!');
        });
    </script>
</body>
</html>
