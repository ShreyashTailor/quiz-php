<?php
include 'db.php';

echo "<h2>🔄 Updating QuizMaster Database Structure...</h2>";

// List of columns to add to quizzes table
$columns_to_add = [
    'created_by' => 'INT DEFAULT NULL',
    'creator_name' => 'VARCHAR(100) DEFAULT NULL',
    'status' => 'ENUM(\'pending\', \'approved\', \'rejected\') DEFAULT \'approved\'',
    'admin_notes' => 'TEXT DEFAULT NULL',
    'approved_by' => 'INT DEFAULT NULL',
    'approved_at' => 'TIMESTAMP NULL'
];

$success_count = 0;
$error_count = 0;

// Check and add each column
foreach ($columns_to_add as $column_name => $column_definition) {
    // Check if column exists
    $check_query = "SELECT COUNT(*) as count FROM information_schema.columns 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'quizzes' 
                    AND column_name = '$column_name'";
    
    $result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE quizzes ADD COLUMN $column_name $column_definition";
        
        if (mysqli_query($conn, $alter_query)) {
            echo "<p style='color: green;'>✅ Added column: $column_name</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Error adding column $column_name: " . mysqli_error($conn) . "</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>ℹ️ Column $column_name already exists</p>";
    }
}

// Add indexes
$indexes_to_add = [
    'idx_status' => 'status',
    'idx_created_by' => 'created_by'
];

foreach ($indexes_to_add as $index_name => $column_name) {
    // Check if index exists
    $check_query = "SELECT COUNT(*) as count FROM information_schema.statistics 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'quizzes' 
                    AND index_name = '$index_name'";
    
    $result = mysqli_query($conn, $check_query);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        // Index doesn't exist, add it
        $alter_query = "ALTER TABLE quizzes ADD INDEX $index_name ($column_name)";
        
        if (mysqli_query($conn, $alter_query)) {
            echo "<p style='color: green;'>✅ Added index: $index_name</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>❌ Error adding index $index_name: " . mysqli_error($conn) . "</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: orange;'>ℹ️ Index $index_name already exists</p>";
    }
}

echo "<h3>Migration Complete!</h3>";
echo "<p>Successfully updated: $success_count items</p>";
echo "<p>Errors: $error_count</p>";

if ($error_count == 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Database migration completed successfully!</p>";
    echo "<p><strong>You can now use the quiz approval system!</strong></p>";
    echo "<p><a href='index.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to QuizMaster Home</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some errors occurred during migration. Please check the error messages above.</p>";
}

mysqli_close($conn);
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}

h2, h3 {
    color: #333;
}

p {
    padding: 8px 15px;
    margin: 5px 0;
    border-radius: 5px;
    background: white;
    border-left: 4px solid #ccc;
}

p[style*="green"] {
    border-left-color: #28a745;
    background: #d4edda;
}

p[style*="red"] {
    border-left-color: #dc3545;
    background: #f8d7da;
}

p[style*="orange"] {
    border-left-color: #ffc107;
    background: #fff3cd;
}
</style>
