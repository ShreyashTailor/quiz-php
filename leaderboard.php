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
<html>
<head>
    <title>Leaderboard - QuizMaster</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Navigation -->
    <div class="navbar">
        <div class="nav-left">
            <h1><a href="index.php" style="color: #333; text-decoration: none;">QuizMaster</a></h1>
        </div>
        <div class="nav-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <span class="greeting-text">Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="nav-btn logout-btn">Logout</a>
            <?php else: ?>
                <a href="register.php" class="nav-btn">Register</a>
                <a href="login.php" class="nav-btn">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- Page Header -->
        <div class="leaderboard-header">
            <h2>🏆 Leaderboard</h2>
            <p>Track top performers and quiz statistics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo number_format($stats['total_attempts']); ?></div>
                <div class="stat-label">Total Attempts</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['unique_users']); ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo number_format($stats['unique_quizzes']); ?></div>
                <div class="stat-label">Quizzes Played</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-value"><?php echo round($stats['avg_score'], 1); ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>

        <!-- Filters and Controls -->
        <div class="leaderboard-controls">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="quiz">Filter by Quiz:</label>
                    <select name="quiz" id="quiz">
                        <option value="all" <?php echo ($quiz_filter === 'all') ? 'selected' : ''; ?>>All Quizzes</option>
                        <?php while ($quiz = mysqli_fetch_assoc($quizzes_result)): ?>
                            <option value="<?php echo $quiz['id']; ?>" <?php echo ($quiz_filter == $quiz['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($quiz['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort">
                        <option value="percentage" <?php echo ($sort_by === 'percentage') ? 'selected' : ''; ?>>Score %</option>
                        <option value="score" <?php echo ($sort_by === 'score') ? 'selected' : ''; ?>>Raw Score</option>
                        <option value="completion_time" <?php echo ($sort_by === 'completion_time') ? 'selected' : ''; ?>>Date</option>
                        <option value="username" <?php echo ($sort_by === 'username') ? 'selected' : ''; ?>>Username</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="order">Order:</label>
                    <select name="order" id="order">
                        <option value="DESC" <?php echo ($order === 'DESC') ? 'selected' : ''; ?>>High to Low</option>
                        <option value="ASC" <?php echo ($order === 'ASC') ? 'selected' : ''; ?>>Low to High</option>
                    </select>
                </div>
                
                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </div>

        <!-- Top Performers Section -->
        <div class="section">
            <h3>🌟 Top Performers (Overall)</h3>
            <div class="top-performers">
                <?php 
                $rank = 1;
                mysqli_data_seek($top_performers_result, 0);
                while ($performer = mysqli_fetch_assoc($top_performers_result)): 
                ?>
                    <div class="performer-card rank-<?php echo $rank; ?>">
                        <div class="rank-badge"><?php echo $rank; ?></div>
                        <div class="performer-info">
                            <h4><?php echo htmlspecialchars($performer['username']); ?></h4>
                            <p><?php echo round($performer['avg_percentage'], 1); ?>% avg • <?php echo $performer['quiz_count']; ?> quizzes</p>
                        </div>
                    </div>
                <?php 
                $rank++;
                endwhile; 
                ?>
            </div>
        </div>

        <!-- Leaderboard Table -->
        <div class="section">
            <h3>📋 Detailed Results</h3>
            
            <?php if (mysqli_num_rows($leaderboard_result) > 0): ?>
                <div class="leaderboard-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>User</th>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Date</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            while ($entry = mysqli_fetch_assoc($leaderboard_result)): 
                                $badge_class = '';
                                if ($entry['percentage'] >= 90) $badge_class = 'excellent';
                                elseif ($entry['percentage'] >= 75) $badge_class = 'great';
                                elseif ($entry['percentage'] >= 60) $badge_class = 'good';
                                elseif ($entry['percentage'] >= 40) $badge_class = 'fair';
                                else $badge_class = 'needs-improvement';
                            ?>
                                <tr>
                                    <td>
                                        <span class="rank-number">
                                            <?php 
                                            if ($rank <= 3) {
                                                $medals = ['🥇', '🥈', '🥉'];
                                                echo $medals[$rank - 1];
                                            } else {
                                                echo $rank;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="username"><?php echo htmlspecialchars($entry['username']); ?></td>
                                    <td class="quiz-title"><?php echo htmlspecialchars($entry['quiz_title']); ?></td>
                                    <td class="score"><?php echo $entry['score']; ?>/<?php echo $entry['total_questions']; ?></td>
                                    <td class="percentage">
                                        <span class="percentage-value"><?php echo $entry['percentage']; ?>%</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $entry['percentage']; ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="date"><?php echo date('M j, Y g:i A', strtotime($entry['completion_time'])); ?></td>
                                    <td>
                                        <span class="performance-badge <?php echo $badge_class; ?>">
                                            <?php
                                            if ($entry['percentage'] >= 90) echo 'Excellent';
                                            elseif ($entry['percentage'] >= 75) echo 'Great';
                                            elseif ($entry['percentage'] >= 60) echo 'Good';
                                            elseif ($entry['percentage'] >= 40) echo 'Fair';
                                            else echo 'Needs Work';
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>📊 No quiz results found!</p>
                    <p>Be the first to take a quiz and appear on the leaderboard.</p>
                    <a href="index.php" class="btn-primary">Take a Quiz</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
