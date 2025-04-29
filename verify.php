<?php
// Start session
session_start();

// Include necessary files
require_once 'config/db.php';
require_once 'includes/functions.php';

$message = '';
$document = null;
$verification_status = '';

// Check if form is submitted or code is in URL
if (isset($_POST['verify']) || isset($_GET['code'])) {
    $doc_code = isset($_POST['doc_code']) ? sanitizeInput($_POST['doc_code']) : sanitizeInput($_GET['code']);
    
    // Check if document exists
    $sql = "SELECT * FROM documents WHERE doc_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $doc_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $document = $result->fetch_assoc();
        
        // Check document status
        if ($document['status'] == 'valid') {
            // Check if document is expired
            if (!empty($document['expiry_date']) && isDocumentExpired($document['expiry_date'])) {
                $verification_status = 'expired';
                logVerification($doc_code, null, 'expired');
            } else {
                $verification_status = 'valid';
                logVerification($doc_code, null, 'valid');
            }
        } else {
            $verification_status = $document['status'];
            logVerification($doc_code, null, $document['status']);
        }
    } else {
        $message = "No document found with the provided code.";
        $verification_status = 'not_found';
        logVerification($doc_code, null, 'not_found');
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Verification</title>
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
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="text-center mb-4">
            <a href="index.php">
                <img src="assets/img/logo.png" alt="Company Logo" class="logo">
            </a>
            <h1>Document Verification System</h1>
            <p class="lead">Verify the authenticity of your documents</p>
        </div>
        
        <div class="verification-box">
            <?php if ($verification_status === 'valid'): ?>
                <div class="verification-success">
                    <h3><i class="bi bi-check-circle-fill"></i> Valid Document</h3>
                    <p>This document is authentic and currently valid.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        Document Details
                    </div>
                    <div class="card-body">
                        <p><strong>Document Code:</strong> <?php echo $document['doc_code']; ?></p>
                        <p><strong>Issued To:</strong> <?php echo $document['issued_to']; ?></p>
                        <p><strong>Purpose:</strong> <?php echo $document['purpose']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($document['issue_date']); ?></p>
                        <p><strong>Expiry Date:</strong> <?php echo formatDate($document['expiry_date']); ?></p>
                        
                        <?php if (!empty($document['file_path']) && file_exists($document['file_path'])): ?>
                            <p><a href="<?php echo $document['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View Document</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($verification_status === 'expired'): ?>
                <div class="verification-expired">
                    <h3><i class="bi bi-exclamation-triangle-fill"></i> Expired Document</h3>
                    <p>This document was valid but has expired.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        Document Details
                    </div>
                    <div class="card-body">
                        <p><strong>Document Code:</strong> <?php echo $document['doc_code']; ?></p>
                        <p><strong>Issued To:</strong> <?php echo $document['issued_to']; ?></p>
                        <p><strong>Purpose:</strong> <?php echo $document['purpose']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($document['issue_date']); ?></p>
                        <p><strong>Expiry Date:</strong> <?php echo formatDate($document['expiry_date']); ?></p>
                    </div>
                </div>
            <?php elseif ($verification_status === 'revoked'): ?>
                <div class="verification-invalid">
                    <h3><i class="bi bi-x-circle-fill"></i> Revoked Document</h3>
                    <p>This document has been revoked and is no longer valid.</p>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        Document Details
                    </div>
                    <div class="card-body">
                        <p><strong>Document Code:</strong> <?php echo $document['doc_code']; ?></p>
                        <p><strong>Issued To:</strong> <?php echo $document['issued_to']; ?></p>
                        <p><strong>Issue Date:</strong> <?php echo formatDate($document['issue_date']); ?></p>
                        <p><strong>Status:</strong> Revoked</p>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Enter Document Verification Code
                    </div>
                    <div class="card-body">
                        <form method="POST" action="verify.php">
                            <div class="mb-3">
                                <label for="doc_code" class="form-label">Document Code</label>
                                <input type="text" class="form-control" id="doc_code" name="doc_code" required>
                                <div class="form-text">Enter the verification code provided on the document</div>
                            </div>
                            <button type="submit" name="verify" class="btn btn-primary">Verify Document</button>
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