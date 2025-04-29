<?php
// Start session
session_start();

// Include necessary files
require_once '../config/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

// Check if form is submitted
if (isset($_POST['add_document'])) {
    // Get form data
    $issued_to = sanitizeInput($_POST['issued_to']);
    $purpose = sanitizeInput($_POST['purpose']);
    $issue_date = sanitizeInput($_POST['issue_date']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
    $status = sanitizeInput($_POST['status']);
    
    // Generate a unique document code
    $doc_code = generateDocCode();
    
    // Check if a file was uploaded
    $file_path = '';
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $upload_result = uploadFile($_FILES['document_file'], '../uploads/documents/');
        
        if ($upload_result['success']) {
            $file_path = $upload_result['file_path'];
        } else {
            $message = $upload_result['message'];
            $message_type = 'danger';
        }
    }
    
    // If no upload error, insert document into database
    if (empty($message)) {
        $sql = "INSERT INTO documents (doc_code, issued_to, purpose, issue_date, expiry_date, status, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $doc_code, $issued_to, $purpose, $issue_date, $expiry_date, $status, $file_path);
        
        if ($stmt->execute()) {
            $message = "Document added successfully with code: $doc_code";
            $message_type = 'success';
            
            // Reset the form
            $issued_to = $purpose = $issue_date = $expiry_date = '';
        } else {
            $message = "Error adding document: " . $conn->error;
            $message_type = 'danger';
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
    <title>Add Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
            padding: 10px 15px;
            border-radius: 5px;
            display: block;
            text-decoration: none;
            margin-bottom: 5px;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .sidebar-heading {
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <div class="sidebar-heading">Document System</div>
                    <a href="dashboard.php" class="sidebar-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="documents.php" class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Manage Documents</a>
                    <a href="add-document.php" class="sidebar-link active"><i class="bi bi-file-earmark-plus"></i> Add Document</a>
                    
                    <div class="sidebar-heading">ID System</div>
                    <a href="employees.php" class="sidebar-link"><i class="bi bi-people"></i> Manage Employees</a>
                    <a href="add-employee.php" class="sidebar-link"><i class="bi bi-person-plus"></i> Add Employee</a>
                    <a href="id-templates.php" class="sidebar-link"><i class="bi bi-card-heading"></i> ID Templates</a>
                    
                    <div class="sidebar-heading">System</div>
                    <a href="logs.php" class="sidebar-link"><i class="bi bi-list-check"></i> Verification Logs</a>
                    <a href="settings.php" class="sidebar-link"><i class="bi bi-gear"></i> Settings</a>
                    <a href="users.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Manage Users</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Add New Document</h1>
                    <a href="documents.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Documents
                    </a>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Document Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="add-document.php" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="issued_to" class="form-label">Issued To*</label>
                                    <input type="text" class="form-control" id="issued_to" name="issued_to" required value="<?php echo isset($issued_to) ? $issued_to : ''; ?>">
                                    <div class="form-text">Full name of the person or organization this document is issued to</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label">Issue Date*</label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" required value="<?php echo isset($issue_date) ? $issue_date : date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo isset($expiry_date) ? $expiry_date : ''; ?>">
                                    <div class="form-text">Leave blank if the document does not expire</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status*</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="valid" selected>Valid</option>
                                        <option value="expired">Expired</option>
                                        <option value="revoked">Revoked</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose*</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required><?php echo isset($purpose) ? $purpose : ''; ?></textarea>
                                <div class="form-text">Describe the purpose of this document</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="document_file" class="form-label">Document File</label>
                                <input type="file" class="form-control" id="document_file" name="document_file">
                                <div class="form-text">Upload a PDF, DOC, or image of the document (optional, max 5MB)</div>
                            </div>
                            
                            <div class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Document Code</h6>
                                    <p class="card-text">A unique verification code will be automatically generated when you submit this form.</p>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="submit" name="add_document" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-plus"></i> Add Document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 