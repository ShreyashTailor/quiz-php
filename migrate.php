<?php
/**
 * Migration Script: Upgrade from Legacy Schema to Secure Schema
 * 
 * This script helps migrate existing databases to the new secure schema
 * with proper constraints, indexes, and bcrypt password hashing.
 * 
 * Run this script ONCE after deploying the new code.
 * 
 * Usage: php migrate.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

echo "QuizMaster Database Migration Script\n";
echo "====================================\n\n";

$db = db();

try {
    // 1. Add email column to users if not exists
    echo "1. Adding email column to users table...\n";
    $colExists = $db->fetchColumn(
        "SELECT COUNT(*) FROM information_schema.columns 
         WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'email'"
    );
    
    if (!$colExists) {
        $db->executeNonQuery("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE NOT NULL AFTER username");
        echo "   ✓ Added email column\n";
    } else {
        echo "   ✓ Email column already exists\n";
    }
    
    // 2. Add updated_at columns
    echo "\n2. Adding updated_at columns...\n";
    
    foreach (['users', 'quizzes'] as $table) {
        $colExists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.columns 
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'updated_at'",
            [$table],
            's'
        );
        
        if (!$colExists) {
            $db->executeNonQuery("ALTER TABLE `$table` ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            echo "   ✓ Added updated_at to $table\n";
        } else {
            echo "   ✓ updated_at already exists in $table\n";
        }
    }
    
    // 3. Add foreign key constraints if not exist
    echo "\n3. Adding foreign key constraints...\n";
    
    $constraints = [
        'fk_quizzes_created_by' => "ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE",
        'fk_quizzes_approved_by' => "ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL",
        'fk_questions_quiz_id' => "ALTER TABLE questions ADD CONSTRAINT fk_questions_quiz_id FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE",
        'fk_leaderboard_user_id' => "ALTER TABLE leaderboard ADD CONSTRAINT fk_leaderboard_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE",
        'fk_leaderboard_quiz_id' => "ALTER TABLE leaderboard ADD CONSTRAINT fk_leaderboard_quiz_id FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE",
    ];
    
    foreach ($constraints as $name => $sql) {
        $exists = $db->fetchColumn(
            "SELECT COUNT(*) FROM information_schema.table_constraints 
             WHERE table_schema = DATABASE() AND constraint_name = ?",
            [$name],
            's'
        );
        
        if (!$exists) {
            try {
                $db->executeNonQuery($sql);
                echo "   ✓ Added constraint $name\n";
            } catch (Exception $e) {
                echo "   ⚠ Could not add $name: " . $e->getMessage() . "\n";
            }
        } else {
            echo "   ✓ Constraint $name already exists\n";
        }
    }
    
    // 4. Migrate passwords from MD5 to bcrypt
    echo "\n4. Migrating passwords from MD5 to bcrypt...\n";
    
    // Find users with MD5 hashes (32 character hex strings)
    $md5Users = $db->fetchAll(
        "SELECT id, username, password FROM users WHERE LENGTH(password) = 32 AND password NOT LIKE ?",
        ['$2y$%'],
        's'
    );
    
    if (!empty($md5Users)) {
        echo "   Found " . count($md5Users) . " users with MD5 hashes.\n";
        echo "   NOTE: Cannot automatically migrate MD5 to bcrypt without original passwords.\n";
        echo "   Users will need to use 'Forgot Password' or you must reset their passwords manually.\n";
        
        foreach ($md5Users as $user) {
            echo "   - User: {$user['username']} (ID: {$user['id']}) needs password reset\n";
        }
    } else {
        echo "   ✓ No MD5 hashes found\n";
    }
    
    // 5. Update default status for quizzes from 'approved' to 'pending' for non-admin created
    echo "\n5. Checking quiz statuses...\n";
    
    $pendingQuizzes = $db->fetchAll(
        "SELECT q.id, q.title, u.username, u.role 
         FROM quizzes q 
         JOIN users u ON q.created_by = u.id 
         WHERE q.status = 'approved' AND u.role != 'admin'"
    );
    
    if (!empty($pendingQuizzes)) {
        echo "   Found " . count($pendingQuizzes) . " quizzes created by non-admins that are approved.\n";
        echo "   These should ideally be 'pending' for review. Update manually if needed.\n";
    } else {
        echo "   ✓ Quiz statuses look correct\n";
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
        }
    } else {
        echo "   ✓ Admin user(s) exist: $adminCount\n";
    }
    
    // 7. Verify table engines and charset
    echo "\n7. Verifying table engines and charset...\n";
    
    $tables = $db->fetchAll("SHOW TABLES");
    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        $status = $db->fetchOne("SHOW TABLE STATUS WHERE Name = ?", [$tableName], 's');
        
        if ($status) {
            $engine = $status['Engine'] ?? 'Unknown';
            $collation = $status['Collation'] ?? 'Unknown';
            
            if ($engine !== 'InnoDB') {
                echo "   ⚠ Table $tableName uses $engine engine (should be InnoDB)\n";
            }
            
            if (strpos($collation, 'utf8mb4') === false) {
                echo "   ⚠ Table $tableName uses $collation (should be utf8mb4_unicode_ci)\n";
            }
        }
    }
    
    echo "\n====================================\n";
    echo "Migration completed!\n\n";
    echo "Next steps:\n";
    echo "1. Update your .env file with database credentials\n";
    echo "2. Change the default admin password immediately\n";
    echo "3. Reset passwords for any users that had MD5 hashes\n";
    echo "4. Test all functionality: login, registration, quiz creation, etc.\n";
    echo "5. Check logs/security.log for any security events\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}