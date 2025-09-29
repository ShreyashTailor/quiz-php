<?php
include 'db.php';

echo "<h2>Setting up QuizMaster Database Tables...</h2>";

// Read the SQL file
$sql = file_get_contents('database_setup.sql');

// Split the SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if (mysqli_query($conn, $statement)) {
            $success_count++;
            echo "<p style='color: green;'>✅ Executed successfully: " . substr($statement, 0, 50) . "...</p>";
        } else {
            $error_count++;
            echo "<p style='color: red;'>❌ Error: " . mysqli_error($conn) . "</p>";
            echo "<p style='color: red;'>Statement: " . $statement . "</p>";
        }
    }
}

echo "<h3>Setup Complete!</h3>";
echo "<p>Successfully executed: $success_count statements</p>";
echo "<p>Errors: $error_count</p>";

if ($error_count == 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Database setup completed successfully!</p>";
    echo "<p><a href='index.php'>Go to QuizMaster Home</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some errors occurred during setup. Please check the error messages above.</p>";
}

mysqli_close($conn);
?>
