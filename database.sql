-- Create the database
CREATE DATABASE IF NOT EXISTS digital_certificate_system;
USE digital_certificate_system;

-- Drop tables if they exist to avoid errors
DROP TABLE IF EXISTS verification_logs;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS id_templates;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert admin users with various password hash formats
-- First admin with simple MD5 hash
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`) 
VALUES ('admin1', MD5('admin123'), 'System Administrator 1', 'admin1@example.com', 'admin');

-- Second admin with SHA1 hash
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`) 
VALUES ('admin2', SHA1('admin123'), 'System Administrator 2', 'admin2@example.com', 'admin');

-- Third admin with bcrypt hash
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`) 
VALUES ('admin3', '$2y$10$V3o1P7L.T1IlM2ATPQRICev1Uc2WU0raxvNYqAjyoVtF0LMSUoOgC', 'System Administrator 3', 'admin3@example.com', 'admin');

-- Fourth admin with simple text password (TEMPORARY FOR TESTING - REMOVE IN PRODUCTION!)
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`) 
VALUES ('admin4', 'admin123', 'System Administrator 4', 'admin4@example.com', 'admin');

-- Fifth admin with another bcrypt hash
INSERT INTO `users` (`username`, `password`, `fullname`, `email`, `role`) 
VALUES ('admin5', '$2y$10$eNDzG.nCBxHaQ/TySdm3J.IJ38Dq0jVdvNzXkNq6oI6SUcKg3N1ES', 'System Administrator 5', 'admin5@example.com', 'admin');

-- Create documents table
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `doc_id` varchar(20) NOT NULL,
  `title` varchar(100) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `description` text,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('valid','expired','revoked') NOT NULL DEFAULT 'valid',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `doc_id` (`doc_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create employees table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_number` varchar(20) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `department` varchar(50) NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create id_templates table
CREATE TABLE IF NOT EXISTS `id_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `html_content` text NOT NULL,
  `css_styles` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `id_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default ID template
INSERT INTO `id_templates` (`name`, `html_content`, `css_styles`, `is_default`, `created_by`) 
VALUES (
  'Default Template', 
  '<div class="id-card">
    <div class="header">
      <img src="../assets/img/logo.png" alt="Company Logo" class="logo">
      <h1>EMPLOYEE IDENTIFICATION</h1>
    </div>
    <div class="photo-section">
      <img src="{{PHOTO}}" alt="Employee Photo" class="photo">
    </div>
    <div class="details">
      <p class="fullname">{{FULLNAME}}</p>
      <p class="position">{{POSITION}}</p>
      <p class="department">{{DEPARTMENT}}</p>
      <p class="id-number">ID: {{ID_NUMBER}}</p>
      <div class="dates">
        <p>Issue Date: {{ISSUE_DATE}}</p>
        <p>Expiry Date: {{EXPIRY_DATE}}</p>
      </div>
      <div class="qr-code">
        {{QR_CODE}}
      </div>
    </div>
  </div>', 
  '.id-card {
    width: 3.375in;
    height: 2.125in;
    background-color: #fff;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  
  .header {
    text-align: center;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
    margin-bottom: 10px;
  }
  
  .header .logo {
    height: 30px;
    margin-bottom: 5px;
  }
  
  .header h1 {
    font-size: 14px;
    margin: 0;
    color: #333;
  }
  
  .photo-section {
    float: left;
    width: 30%;
  }
  
  .photo {
    width: 1in;
    height: 1.25in;
    object-fit: cover;
    border: 1px solid #ddd;
  }
  
  .details {
    float: right;
    width: 65%;
    padding-left: 5px;
  }
  
  .fullname {
    font-size: 14px;
    font-weight: bold;
    margin: 5px 0;
  }
  
  .position, .department, .id-number {
    font-size: 12px;
    margin: 3px 0;
  }
  
  .dates {
    font-size: 9px;
    margin-top: 5px;
  }
  
  .qr-code {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 0.75in;
    height: 0.75in;
  }
  
  .qr-code img {
    width: 100%;
    height: 100%;
  }', 
  1, 
  1
);

-- Create verification_logs table
CREATE TABLE IF NOT EXISTS `verification_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_id` varchar(20) DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `verification_type` enum('document','id_card') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `verified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_id` (`document_id`),
  KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delete any testing scripts that might cause confusion
-- (You can run this after testing is complete)
-- DELETE FROM check_db.php;
-- DELETE FROM create_admin.php;
-- DELETE FROM fix_admin.sql; 