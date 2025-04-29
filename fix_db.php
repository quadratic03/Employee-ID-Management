<?php
// First, check if the existing db.php has the right database name
$db_content = file_get_contents('config/db.php');
$wrong_db_name = 'certificate_id_system';
$correct_db_name = 'digital_certificate_system';

if (strpos($db_content, $wrong_db_name) !== false) {
    // Need to update the database name
    $db_content = str_replace($wrong_db_name, $correct_db_name, $db_content);
    file_put_contents('config/db.php', $db_content);
    echo "Database configuration updated from '$wrong_db_name' to '$correct_db_name'.<br>";
} else {
    echo "Database configuration is already correct.<br>";
}

// Now set up the database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = $correct_db_name;

// Create connection without specifying database
try {
    $conn = new mysqli($host, $username, $password);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS `$database`";
    if ($conn->query($sql) === TRUE) {
        echo "Database created or already exists.<br>";
    } else {
        echo "Error creating database: " . $conn->error . "<br>";
    }
    
    // Select the database
    $conn->select_db($database);
    
    // Check if tables exist
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        echo "Creating database tables...<br>";
        
        // Read SQL file
        $sql_content = file_get_contents('database.sql');
        
        // Remove the database creation part to avoid errors
        $sql_content = preg_replace('/CREATE DATABASE.*?;/s', '', $sql_content);
        $sql_content = preg_replace('/USE.*?;/s', '', $sql_content);
        
        // Execute each statement separately
        $queries = explode(';', $sql_content);
        $success = true;
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                if (!$conn->query($query)) {
                    echo "Error executing query: " . $conn->error . "<br>Query: " . $query . "<br>";
                    $success = false;
                }
            }
        }
        
        if ($success) {
            echo "Database tables created successfully!<br>";
        } else {
            echo "There were errors creating some tables.<br>";
        }
    } else {
        echo "Database tables already exist.<br>";
    }
    
    echo "Database setup is complete!<br>";
    echo "<p><a href='admin/index.php'>Go to login page</a></p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "Database setup error: " . $e->getMessage() . "<br>";
}
?> 