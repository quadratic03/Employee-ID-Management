<?php
// Include database configuration
include 'db.php';

// SQL to create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($database);

// SQL to create documents table
$sql = "CREATE TABLE IF NOT EXISTS documents (
    doc_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doc_code VARCHAR(50) UNIQUE NOT NULL,
    issued_to VARCHAR(255) NOT NULL,
    purpose TEXT,
    issue_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('valid', 'expired', 'revoked') NOT NULL DEFAULT 'valid',
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Documents table created successfully<br>";
} else {
    echo "Error creating documents table: " . $conn->error . "<br>";
}

// SQL to create employees table for ID system
$sql = "CREATE TABLE IF NOT EXISTS employees (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    id_number VARCHAR(100) UNIQUE NOT NULL,
    photo_path VARCHAR(255),
    issue_date DATE NOT NULL,
    expiry_date DATE,
    status ENUM('active', 'expired', 'lost', 'revoked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Employees table created successfully<br>";
} else {
    echo "Error creating employees table: " . $conn->error . "<br>";
}

// SQL to create id_templates table
$sql = "CREATE TABLE IF NOT EXISTS id_templates (
    template_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    html_content TEXT NOT NULL,
    css_content TEXT,
    is_default BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "ID Templates table created successfully<br>";
} else {
    echo "Error creating ID Templates table: " . $conn->error . "<br>";
}

// SQL to create users table for admin access
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// SQL to create verification_logs table
$sql = "CREATE TABLE IF NOT EXISTS verification_logs (
    log_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    doc_code VARCHAR(50),
    id_number VARCHAR(100),
    verification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT
)";

if ($conn->query($sql) === TRUE) {
    echo "Verification logs table created successfully<br>";
} else {
    echo "Error creating verification logs table: " . $conn->error . "<br>";
}

// Insert a default admin user (password: admin123)
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, password, email, full_name, role) 
        VALUES ('admin', '$hashed_password', 'admin@example.com', 'System Administrator', 'admin')
        ON DUPLICATE KEY UPDATE email = VALUES(email)";

if ($conn->query($sql) === TRUE) {
    echo "Default admin user created successfully<br>";
} else {
    echo "Error creating default admin user: " . $conn->error . "<br>";
}

// Insert a default ID template
$default_html = '<div class="id-card">
    <div class="header">
        <img src="../assets/img/logo.png" class="logo">
        <h1>COMPANY NAME</h1>
    </div>
    <div class="photo">
        <img src="{PHOTO_PATH}" alt="Employee Photo">
    </div>
    <div class="details">
        <h2>{FULL_NAME}</h2>
        <p class="position">{POSITION}</p>
        <p class="department">{DEPARTMENT}</p>
        <p class="id-number">ID: {ID_NUMBER}</p>
        <p class="issue-date">Issue Date: {ISSUE_DATE}</p>
        <p class="expiry-date">Valid Until: {EXPIRY_DATE}</p>
    </div>
    <div class="qr-code">
        {QR_CODE}
    </div>
</div>';

$default_css = '.id-card {
    width: 3.375in;
    height: 2.125in;
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    position: relative;
    font-family: Arial, sans-serif;
}
.header {
    background-color: #003366;
    color: white;
    padding: 10px;
    text-align: center;
}
.logo {
    height: 30px;
    margin-bottom: 5px;
}
.header h1 {
    margin: 0;
    font-size: 14px;
}
.photo {
    position: absolute;
    top: 60px;
    left: 20px;
    width: 1in;
    height: 1.25in;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    overflow: hidden;
}
.photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.details {
    position: absolute;
    top: 60px;
    left: 1.3in;
    width: 1.8in;
}
.details h2 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #003366;
}
.details p {
    margin: 0 0 3px 0;
    font-size: 10px;
    color: #333;
}
.position {
    font-weight: bold;
    color: #003366 !important;
}
.qr-code {
    position: absolute;
    bottom: 10px;
    left: 20px;
    width: 0.75in;
    height: 0.75in;
}';

$sql = "INSERT INTO id_templates (name, html_content, css_content, is_default) 
        VALUES ('Default Template', '$default_html', '$default_css', 1)
        ON DUPLICATE KEY UPDATE html_content = VALUES(html_content), css_content = VALUES(css_content)";

if ($conn->query($sql) === TRUE) {
    echo "Default ID template created successfully<br>";
} else {
    echo "Error creating default ID template: " . $conn->error . "<br>";
}

echo "<p>Database initialization completed. <a href='../index.php'>Go to Home</a></p>";

// Close connection
$conn->close();
?> 