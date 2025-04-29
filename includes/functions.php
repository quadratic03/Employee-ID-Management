<?php
// Generate a unique document code
function generateDocCode($length = 10) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Generate a unique ID number for employees
function generateIDNumber($dept_code, $conn) {
    // Format: DEPT-YEAR-XXXX (e.g., HR-2023-0001)
    $year = date('Y');
    
    // Get the last ID number for this department and year
    $sql = "SELECT id_number FROM employees 
            WHERE id_number LIKE '$dept_code-$year-%' 
            ORDER BY id_number DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $last_number = intval(substr($row['id_number'], -4));
        $new_number = $last_number + 1;
    } else {
        $new_number = 1;
    }
    
    // Format with leading zeros
    $formatted_number = sprintf('%04d', $new_number);
    return "$dept_code-$year-$formatted_number";
}

// Check if a document is expired
function isDocumentExpired($expiry_date) {
    if (empty($expiry_date)) {
        return false; // No expiry date set
    }
    
    $today = date('Y-m-d');
    return $expiry_date < $today;
}

// Generate QR code content for document verification
function getDocumentQRContent($doc_code, $base_url = '') {
    if (empty($base_url)) {
        $base_url = getBaseURL();
    }
    
    return $base_url . '/verify.php?code=' . $doc_code;
}

// Generate QR code content for ID verification
function getIDQRContent($id_number, $base_url = '') {
    if (empty($base_url)) {
        $base_url = getBaseURL();
    }
    
    return $base_url . '/id-verify.php?id=' . $id_number;
}

// Get the base URL of the application
function getBaseURL() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    
    // Remove '/includes' from path if this function is called from an include file
    $path = str_replace('/includes', '', $path);
    $path = str_replace('/admin', '', $path);
    
    return $protocol . $domain . $path;
}

// Log verification attempts
function logVerification($doc_code = null, $id_number = null, $status) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $verification_type = $doc_code ? 'document' : 'id_card';
    
    $sql = "INSERT INTO verification_logs (document_id, employee_id, verification_type, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $doc_code, $id_number, $verification_type, $ip_address, $user_agent);
    $stmt->execute();
    $stmt->close();
}

// Format date for display
function formatDate($date, $format = 'd M Y') {
    if (empty($date)) {
        return 'N/A';
    }
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user has admin role
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Get current user details
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

// Upload file and return file path
function uploadFile($file, $target_dir = 'uploads/') {
    // Check if directory exists, if not create it
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Generate a unique filename
    $unique_name = uniqid() . '.' . $file_type;
    $target_file = $target_dir . $unique_name;
    
    // Check if file already exists
    if (file_exists($target_file)) {
        return [
            'success' => false,
            'message' => 'File already exists.'
        ];
    }
    
    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return [
            'success' => false,
            'message' => 'File is too large. Maximum size is 5MB.'
        ];
    }
    
    // Allow certain file formats for documents
    if ($target_dir == 'uploads/documents/') {
        $allowed_types = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
        if (!in_array($file_type, $allowed_types)) {
            return [
                'success' => false,
                'message' => 'Only PDF, DOC, DOCX, JPG, JPEG & PNG files are allowed for documents.'
            ];
        }
    }
    
    // Allow certain file formats for photos
    if ($target_dir == 'uploads/photos/') {
        $allowed_types = ["jpg", "jpeg", "png"];
        if (!in_array($file_type, $allowed_types)) {
            return [
                'success' => false,
                'message' => 'Only JPG, JPEG & PNG files are allowed for photos.'
            ];
        }
    }
    
    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true,
            'file_path' => $target_file
        ];
    } else {
        return [
            'success' => false,
            'message' => 'There was an error uploading your file.'
        ];
    }
}
?> 