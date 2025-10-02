<?php
require_once 'db.php';

// Add missing columns to users table
$queries = [
    "ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'",
    "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
];

foreach ($queries as $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        echo "Success: " . $query . "\n";
    } else {
        echo "Error or already exists: " . $query . " - " . mysqli_error($conn) . "\n";
    }
}

echo "Database update completed!\n";
?>
