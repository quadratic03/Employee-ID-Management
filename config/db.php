<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'digital_certificate_system';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");
?> 