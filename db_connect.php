<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'digital_certificate_system';

// Create connection without specifying the database first
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database created or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// Import SQL file if tables don't exist
$check_tables = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_tables->num_rows == 0) {
    echo "Tables don't exist. Importing from SQL file...<br>";
    
    // Read SQL file
    $sql_contents = file_get_contents('database.sql');
    
    // Execute multi-query SQL
    if ($conn->multi_query($sql_contents)) {
        echo "Database imported successfully!<br>";
        
        // Need to clear result sets to continue
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    } else {
        echo "Error importing database: " . $conn->error . "<br>";
    }
}

echo "<p>Database is ready. <a href='admin/index.php'>Go to Login Page</a></p>";

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?> 