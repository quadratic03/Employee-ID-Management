<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Include database configuration
require_once '../config/db.php';
require_once '../includes/functions.php';

$error = '';

// Check if form is submitted
if (isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    // Check if fields are empty
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $login_success = false;
            
            // Try multiple password verification methods
            
            // 1. Try PHP's password_verify (for bcrypt hashes)
            if (password_verify($password, $user['password'])) {
                $login_success = true;
            }
            // 2. Try MD5 hash comparison
            else if (md5($password) === $user['password']) {
                $login_success = true;
            }
            // 3. Try SHA1 hash comparison
            else if (sha1($password) === $user['password']) {
                $login_success = true;
            }
            // 4. Try direct comparison (not secure, but for testing)
            else if ($password === $user['password']) {
                $login_success = true;
            }
            
            if ($login_success) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['full_name'] = $user['fullname'];
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .login-box {
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="text-center mb-4">
            <a href="../index.php">
                <img src="../assets/img/logo.png" alt="Company Logo" class="logo">
            </a>
            <h1>Admin Panel</h1>
            <p class="lead">Login to manage documents and ID cards</p>
        </div>
        
        <div class="login-box">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Login</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="login" class="btn btn-primary">Login</button>
                            <a href="../index.php" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p class="small text-muted">Try logging in with: admin4 / admin123</p>
                <p class="small text-muted mt-3">Developed by Namoc &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 