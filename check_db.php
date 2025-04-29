<?php
// Include database configuration
require_once 'config/db.php';

echo "<h1>Database Diagnostic</h1>";

// Check database connection
echo "<h2>Database Connection</h2>";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connected successfully: " . $database . "<br>";
}

// Check if users table exists
echo "<h2>Tables Check</h2>";
$tables = ["users", "documents", "employees", "id_templates", "verification_logs"];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "Table '$table' exists.<br>";
        
        // Show structure of users table
        if ($table === "users") {
            echo "<h3>Structure of 'users' table:</h3>";
            $columns = $conn->query("SHOW COLUMNS FROM users");
            echo "<pre>";
            while ($column = $columns->fetch_assoc()) {
                print_r($column);
            }
            echo "</pre>";
            
            echo "<h3>First 10 users:</h3>";
            $users = $conn->query("SELECT * FROM users LIMIT 10");
            if ($users->num_rows > 0) {
                echo "<ul>";
                while ($user = $users->fetch_assoc()) {
                    echo "<li>ID: " . $user['id'] . " | Username: " . $user['username'] . " | Role: " . $user['role'] . "</li>";
                }
                echo "</ul>";
            } else {
                echo "No users found in the database!<br>";
                
                // If no users, create a default one
                echo "<h3>Creating default admin user...</h3>";
                $username = 'admin';
                $password = 'admin123';
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, fullname, email, role) 
                        VALUES (?, ?, 'System Administrator', 'admin@example.com', 'admin')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $username, $hashed_password);
                
                if ($stmt->execute()) {
                    echo "Default admin user created!<br>";
                    echo "Username: admin<br>";
                    echo "Password: admin123<br>";
                    echo "Password hash: $hashed_password<br>";
                } else {
                    echo "Error creating default user: " . $stmt->error;
                }
            }
        }
    } else {
        echo "Table '$table' does not exist!<br>";
    }
}

// Close connection
$conn->close();
?> 