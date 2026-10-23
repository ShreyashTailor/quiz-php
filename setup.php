<?php
/**
 * Database Setup Script
 * 
 * This script sets up the QuizMaster database tables.
 * Run this script ONCE during initial installation.
 * 
 * Usage: php setup.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

echo "QuizMaster Database Setup\n";
echo "========================\n\n";

$db = db();
$success_count = 0;
$error_count = 0;

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/database_setup.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read database_setup.sql');
    }
    
    // Split the SQL into individual statements
    // Handle multi-line statements and comments properly
    $statements = [];
    $current = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip empty lines and comments
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        
        $current .= $line . "\n";
        
        // Check if statement ends with semicolon (and not inside quotes)
        if (preg_match('/;$/', $trimmed)) {
            $statements[] = trim($current);
            $current = '';
        }
    }
    
    // Add any remaining
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->executeNonQuery($statement);
                $success_count++;
                echo "✅ Executed: " . substr($statement, 0, 80) . "...\n";
            } catch (Exception $e) {
                $error_count++;
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\n================================\n";
    echo "Setup Complete!\n";
    echo "Successfully executed: $success_count statements\n";
    echo "Errors: $error_count\n";
    
    if ($error_count == 0) {
        echo "\n🎉 Database setup completed successfully!\n";
        echo "You can now access QuizMaster at the home page.\n";
        exit(0);
    } else {
        echo "\n⚠️ Some errors occurred during setup. Please check the error messages above.\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n❌ Setup failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}