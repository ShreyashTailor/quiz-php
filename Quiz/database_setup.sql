-- Create leaderboard table for storing quiz results
CREATE TABLE IF NOT EXISTS leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    quiz_id INT NOT NULL,
    quiz_title VARCHAR(255) NOT NULL,
    score INT NOT NULL,
    total_questions INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    completion_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_quiz_id (quiz_id),
    INDEX idx_percentage (percentage),
    INDEX idx_completion_time (completion_time)
);

-- Create users table if it doesn't exist
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create quizzes table if it doesn't exist
CREATE TABLE IF NOT EXISTS quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT DEFAULT NULL,
    creator_name VARCHAR(100) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    admin_notes TEXT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
);

-- Create questions table if it doesn't exist
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option ENUM('A', 'B', 'C', 'D') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Update existing quizzes table structure
-- Check and add columns one by one to avoid errors

-- Add created_by column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'created_by';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN created_by INT DEFAULT NULL',
    'SELECT "Column created_by already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add creator_name column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'creator_name';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN creator_name VARCHAR(100) DEFAULT NULL',
    'SELECT "Column creator_name already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add status column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'status';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN status ENUM(''pending'', ''approved'', ''rejected'') DEFAULT ''approved''',
    'SELECT "Column status already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add admin_notes column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'admin_notes';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN admin_notes TEXT DEFAULT NULL',
    'SELECT "Column admin_notes already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add approved_by column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'approved_by';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN approved_by INT DEFAULT NULL',
    'SELECT "Column approved_by already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add approved_at column
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND column_name = 'approved_at';

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE quizzes ADD COLUMN approved_at TIMESTAMP NULL',
    'SELECT "Column approved_at already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes if they don't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND index_name = 'idx_status';

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE quizzes ADD INDEX idx_status (status)',
    'SELECT "Index idx_status already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.statistics 
WHERE table_schema = DATABASE() 
AND table_name = 'quizzes' 
AND index_name = 'idx_created_by';

SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE quizzes ADD INDEX idx_created_by (created_by)',
    'SELECT "Index idx_created_by already exists" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
