<?php
// Start session
session_start();

// Include necessary files
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$employee = null;
$verification_status = '';

// Check if form is submitted or ID is in URL
if (isset($_POST['verify']) || isset($_GET['id'])) {
    $id_number = isset($_POST['id_number']) ? sanitizeInput($_POST['id_number']) : sanitizeInput($_GET['id']);
    
    // Check if employee ID exists
    $sql = "SELECT * FROM employees WHERE id_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        
        // Check ID status
        if ($employee['status'] == 'active') {
            // Check if ID is expired
            if (!empty($employee['expiry_date']) && isDocumentExpired($employee['expiry_date'])) {
                $verification_status = 'expired';
                logVerification(null, $id_number, 'expired');
            } else {
                $verification_status = 'active';
                logVerification(null, $id_number, 'active');
            }
        } else {
            $verification_status = $employee['status'];
            logVerification(null, $id_number, $employee['status']);
        }
    } else {
        $message = "No employee found with the provided ID number.";
        $verification_status = 'not_found';
        logVerification(null, $id_number, 'not_found');
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee ID Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .verification-box {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .verification-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .verification-expired {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .verification-invalid {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 20px;
        }
        .employee-photo {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="text-center mb-4">
            <a href="index.php">
                <img src="assets/img/logo.png" alt="Company Logo" class="logo">
            </a>
            <h1>Employee ID Verification System</h1>
            <p class="lead">Verify the authenticity of employee identification cards</p>
        </div>
        
        <div class="verification-box">
            <?php if ($verification_status === 'active'): ?>
                <div class="verification-success">
                    <h3><i class="bi bi-check-circle-fill"></i> Valid ID Card</h3>
                    <p>This employee ID is authentic and currently active.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        Employee Details
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php if (!empty($employee['photo']) && file_exists($employee['photo'])): ?>
                                <img src="<?php echo $employee['photo']; ?>" alt="Employee Photo" class="employee-photo">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No Photo Available" class="employee-photo">
                            <?php endif; ?>
                        </div>
                        <p><strong>ID Number:</strong> <?php echo $employee['id_number']; ?></p>
                        <p><strong>Name:</strong> <?php echo $employee['fullname']; ?></p>
                        <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
                        <p><strong>Department:</strong> <?php echo $employee['department']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($employee['issue_date']); ?></p>
                        <p><strong>Expiry Date:</strong> <?php echo formatDate($employee['expiry_date']); ?></p>
                    </div>
                </div>
            <?php elseif ($verification_status === 'expired'): ?>
                <div class="verification-expired">
                    <h3><i class="bi bi-exclamation-triangle-fill"></i> Expired ID Card</h3>
                    <p>This employee ID was valid but has expired.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        Employee Details
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <?php if (!empty($employee['photo']) && file_exists($employee['photo'])): ?>
                                <img src="<?php echo $employee['photo']; ?>" alt="Employee Photo" class="employee-photo">
                            <?php else: ?>
                                <img src="assets/img/placeholder.jpg" alt="No Photo Available" class="employee-photo">
                            <?php endif; ?>
                        </div>
                        <p><strong>ID Number:</strong> <?php echo $employee['id_number']; ?></p>
                        <p><strong>Name:</strong> <?php echo $employee['fullname']; ?></p>
                        <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
                        <p><strong>Department:</strong> <?php echo $employee['department']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($employee['issue_date']); ?></p>
                        <p><strong>Expiry Date:</strong> <?php echo formatDate($employee['expiry_date']); ?></p>
                    </div>
                </div>
            <?php elseif ($verification_status === 'revoked' || $verification_status === 'lost'): ?>
                <div class="verification-invalid">
                    <h3><i class="bi bi-x-circle-fill"></i> <?php echo ucfirst($verification_status); ?> ID Card</h3>
                    <p>This employee ID has been <?php echo $verification_status; ?> and is no longer valid.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        Employee Details
                    </div>
                    <div class="card-body">
                        <p><strong>ID Number:</strong> <?php echo $employee['id_number']; ?></p>
                        <p><strong>Name:</strong> <?php echo $employee['fullname']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($employee['issue_date']); ?></p>
                        <p><strong>Status:</strong> <?php echo ucfirst($employee['status']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-success text-white">
                        Enter Employee ID Number
                    </div>
                    <div class="card-body">
                        <form method="POST" action="id-verify.php">
                            <div class="mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                                <div class="form-text">Enter the ID number displayed on the employee ID card</div>
                            </div>
                            <button type="submit" name="verify" class="btn btn-success">Verify ID</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"></script>
</body>
</html> 