<?php
// Include database configuration
require_once 'config/db.php';

// Create a new admin user
$username = 'testadmin';
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$fullname = 'Test Administrator';
$email = 'testadmin@example.com';
$role = 'admin';

// Check if user already exists
$check_sql = "SELECT * FROM users WHERE username = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $username);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo "User already exists. Updating password...<br>";
    $update_sql = "UPDATE users SET password = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ss", $hashed_password, $username);
    
    if ($update_stmt->execute()) {
        echo "Password updated successfully!<br>";
    } else {
        echo "Error updating password: " . $conn->error . "<br>";
    }
    
    $update_stmt->close();
} else {
    echo "Creating new admin user...<br>";
    
    // Insert new user
    $insert_sql = "INSERT INTO users (username, password, fullname, email, role) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("sssss", $username, $hashed_password, $fullname, $email, $role);
    
    if ($insert_stmt->execute()) {
        echo "Admin user created successfully!<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
    
    $insert_stmt->close();
}

$check_stmt->close();

// Display login information
echo "<p>Login details:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> " . $username . "</li>";
echo "<li><strong>Password:</strong> " . $password . "</li>";
echo "</ul>";

echo "<p>The password hash is: " . $hashed_password . "</p>";
echo "<p><a href='admin/index.php'>Go to Login Page</a></p>";

// Close connection
$conn->close();
?> 