<?php
/**
 * Database Migration Script
 * 
 * This script helps update the database schema with new columns and constraints.
 * Run this script ONCE after deploying updates.
 * 
 * Usage: php update_db.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "QuizMaster Database Update Script\n";
echo "=================================\n\n";

$db = db();
$errors = 0;
$success = 0;

try {
    // 1. Add missing columns to users table
    echo "1. Updating users table...\n";
    
    $userColumns = [
        'email' => "ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE DEFAULT NULL AFTER username",
        'status' => "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'",
        'reset_token' => "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL",
        'reset_expires' => "ALTER TABLE users ADD COLUMN reset_expires TIMESTAMP NULL DEFAULT NULL",
        'updated_at' => "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    
    foreach ($userColumns as $name => $query) {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = ?",
            [$name],
            's'
        );
        
        if (!$exists) {
            try {
                $db->executeNonQuery($query);
                echo "   ✓ Added $name column\n";
                $success++;
            } catch (Exception $e) {
                echo "   ✗ Failed to add $name: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "   ✓ $name column already exists\n";
            $success++;
        }
    }
    
    // 2. Add indexes to users table
    echo "\n2. Adding indexes to users table...\n";
    
    $userIndexes = [
        'idx_email' => "ALTER TABLE users ADD INDEX idx_email (email)",
        'idx_status' => "ALTER TABLE users ADD INDEX idx_status (status)",
        'idx_reset_token' => "ALTER TABLE users ADD INDEX idx_reset_token (reset_token)",
    ];
    
    foreach ($userIndexes as $name => $query) {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.statistics 
             WHERE table_schema = DATABASE() AND table_name = 'users' AND index_name = ?",
            [$name],
            's'
        );
        
        if (!$exists) {
            try {
                $db->executeNonQuery($query);
                echo "   ✓ Added index $name\n";
                $success++;
            } catch (Exception $e) {
                echo "   ✗ Failed to add index $name: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "   ✓ Index $name already exists\n";
            $success++;
        }
    }
    
    // 3. Add missing columns to quizzes table
    echo "\n3. Updating quizzes table...\n";
    
    $quizColumns = [
        'updated_at' => "ALTER TABLE quizzes ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    
    foreach ($quizColumns as $name => $query) {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() AND table_name = 'quizzes' AND column_name = ?",
            [$name],
            's'
        );
        
        if (!$exists) {
            try {
                $db->executeNonQuery($query);
                echo "   ✓ Added $name column\n";
                $success++;
            } catch (Exception $e) {
                echo "   ✗ Failed to add $name: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "   ✓ $name column already exists\n";
            $success++;
        }
    }
    
    // 4. Add foreign key constraints
    echo "\n4. Adding foreign key constraints...\n";
    
    $constraints = [
        'fk_quizzes_created_by' => "ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE",
        'fk_quizzes_approved_by' => "ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL",
        'fk_questions_quiz_id' => "ALTER TABLE questions ADD CONSTRAINT fk_questions_quiz_id FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE",
        'fk_leaderboard_user_id' => "ALTER TABLE leaderboard ADD CONSTRAINT fk_leaderboard_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        'fk_leaderboard_quiz_id' => "ALTER TABLE leaderboard ADD CONSTRAINT fk_leaderboard_quiz_id FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE",
    ];
    
    foreach ($constraints as $name => $query) {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.table_constraints 
             WHERE table_schema = DATABASE() AND constraint_name = ?",
            [$name],
            's'
        );
        
        if (!$exists) {
            try {
                $db->executeNonQuery($query);
                echo "   ✓ Added constraint $name\n";
                $success++;
            } catch (Exception $e) {
                echo "   ⚠ Could not add constraint $name: " . $e->getMessage() . "\n";
                // Don't count as error since it might already exist in data
            }
        } else {
            echo "   ✓ Constraint $name already exists\n";
            $success++;
        }
    }
    
    // 5. Verify table engines and charset
    echo "\n5. Verifying table engines and charset...\n";
    
    $tables = ['users', 'quizzes', 'questions', 'leaderboard'];
    foreach ($tables as $table) {
        $status = $db->fetchOne("SHOW TABLE STATUS WHERE Name = ?", [$table], 's');
        
        if ($status) {
            $engine = $status['Engine'] ?? 'Unknown';
            $collation = $status['Collation'] ?? 'Unknown';
            
            if ($engine !== 'InnoDB') {
                echo "   ⚠ Table $table uses $engine engine (should be InnoDB)\n";
            } else {
                echo "   ✓ Table $table uses InnoDB\n";
                $success++;
            }
            
            if (strpos($collation, 'utf8mb4') === false) {
                echo "   ⚠ Table $table uses $collation (should be utf8mb4_unicode_ci)\n";
            } else {
                echo "   ✓ Table $table uses utf8mb4\n";
                $success++;
            }
        }
    }
    
    // 6. Create default admin if none exists
    echo "\n6. Checking for admin user...\n";
    
    $adminCount = $db->fetchColumn("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    
    if ($adminCount == 0) {
        echo "   No admin user found. Creating default admin...\n";
        $hashedPassword = hashPassword('Admin@123');
        $result = $db->executeNonQuery(
            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')",
            ['admin', 'admin@quizmaster.local', $hashedPassword],
            'sss'
        );
        
        if ($result > 0) {
            echo "   ✓ Created default admin user\n";
            echo "   ⚠ IMPORTANT: Change the default password immediately!\n";
            echo "   Username: admin\n";
            echo "   Password: Admin@123\n";
            $success++;
        } else {
            echo "   ✗ Failed to create admin user: " . $db->getLastError() . "\n";
            $errors++;
        }
    } else {
        echo "   ✓ Admin user(s) exist: $adminCount\n";
        $success++;
    }
    
    echo "\n=================================\n";
    echo "Update completed!\n";
    echo "Success: $success\n";
    echo "Errors: $errors\n\n";
    
    if ($errors > 0) {
        echo "Some operations had issues. Please review the output above.\n";
        exit(1);
    }
    
    echo "Next steps:\n";
    echo "1. Change the default admin password immediately\n";
    echo "2. Test all functionality: login, registration, quiz creation, etc.\n";
    echo "3. Check logs/security.log for any security events\n";
    
} catch (Exception $e) {
    echo "\n❌ Update failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}