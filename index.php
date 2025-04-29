<?php
// Start session to check if user is logged in
session_start();
$is_admin_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document & ID Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Document & ID Management System</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Document Verification System</h3>
                    </div>
                    <div class="card-body">
                        <p>Verify the authenticity of documents issued by our company.</p>
                        <div class="d-grid gap-2">
                            <a href="verify.php" class="btn btn-primary btn-lg">Verify a Document</a>
                            <?php if ($is_admin_logged_in): ?>
                                <a href="admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            <?php else: ?>
                                <a href="admin/index.php" class="btn btn-outline-secondary">Admin Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title">ID Maker System</h3>
                    </div>
                    <div class="card-body">
                        <p>Create and manage employee identification cards.</p>
                        <div class="d-grid gap-2">
                            <a href="id-verify.php" class="btn btn-success btn-lg">Verify an ID</a>
                            <?php if ($is_admin_logged_in): ?>
                                <a href="admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            <?php else: ?>
                                <a href="admin/index.php" class="btn btn-outline-secondary">Admin Login</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 