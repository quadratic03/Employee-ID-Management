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
$document = null;

// Check if document ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = "Invalid document ID.";
    header("Location: documents.php");
    exit();
}

$doc_id = $_GET['id'];

// Get document details
$sql = "SELECT * FROM documents WHERE doc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_msg'] = "Document not found.";
    header("Location: documents.php");
    exit();
}

$document = $result->fetch_assoc();
$stmt->close();

// Check if form is submitted
if (isset($_POST['update_document'])) {
    // Get form data
    $issued_to = sanitizeInput($_POST['issued_to']);
    $purpose = sanitizeInput($_POST['purpose']);
    $issue_date = sanitizeInput($_POST['issue_date']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
    $status = sanitizeInput($_POST['status']);
    
    // Check if a new file was uploaded
    $file_path = $document['file_path'];
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $upload_result = uploadFile($_FILES['document_file'], '../uploads/documents/');
        
        if ($upload_result['success']) {
            // Delete the old file if it exists
            if (!empty($document['file_path']) && file_exists('../' . $document['file_path'])) {
                unlink('../' . $document['file_path']);
            }
            
            $file_path = $upload_result['file_path'];
        } else {
            $message = $upload_result['message'];
            $message_type = 'danger';
        }
    }
    
    // Check if remove file checkbox is checked
    if (isset($_POST['remove_file']) && $_POST['remove_file'] == 1) {
        // Delete the file if it exists
        if (!empty($document['file_path']) && file_exists('../' . $document['file_path'])) {
            unlink('../' . $document['file_path']);
        }
        
        $file_path = '';
    }
    
    // If no upload error, update document in database
    if (empty($message)) {
        $sql = "UPDATE documents SET issued_to = ?, purpose = ?, issue_date = ?, expiry_date = ?, status = ?, file_path = ? WHERE doc_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $issued_to, $purpose, $issue_date, $expiry_date, $status, $file_path, $doc_id);
        
        if ($stmt->execute()) {
            $message = "Document updated successfully.";
            $message_type = 'success';
            
            // Refresh document data
            $sql = "SELECT * FROM documents WHERE doc_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $doc_id);
            $stmt->execute();
            $document = $stmt->get_result()->fetch_assoc();
        } else {
            $message = "Error updating document: " . $conn->error;
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
    <title>Edit Document</title>
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
                    <a href="documents.php" class="sidebar-link active"><i class="bi bi-file-earmark-text"></i> Manage Documents</a>
                    <a href="add-document.php" class="sidebar-link"><i class="bi bi-file-earmark-plus"></i> Add Document</a>
                    
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
                    <h1>Edit Document</h1>
                    <div>
                        <a href="../verify.php?code=<?php echo $document['doc_code']; ?>" target="_blank" class="btn btn-success me-2">
                            <i class="bi bi-check2-circle"></i> Verify
                        </a>
                        <a href="documents.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Documents
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Edit Document: <?php echo $document['doc_code']; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="edit-document.php?id=<?php echo $doc_id; ?>" enctype="multipart/form-data">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="doc_code" class="form-label">Document Code</label>
                                    <input type="text" class="form-control" id="doc_code" value="<?php echo $document['doc_code']; ?>" readonly>
                                    <div class="form-text">This is the unique verification code for this document</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Status*</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="valid" <?php echo $document['status'] == 'valid' ? 'selected' : ''; ?>>Valid</option>
                                        <option value="expired" <?php echo $document['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                        <option value="revoked" <?php echo $document['status'] == 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="issued_to" class="form-label">Issued To*</label>
                                    <input type="text" class="form-control" id="issued_to" name="issued_to" required value="<?php echo $document['issued_to']; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="issue_date" class="form-label">Issue Date*</label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" required value="<?php echo $document['issue_date']; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="expiry_date" class="form-label">Expiry Date</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo $document['expiry_date']; ?>">
                                    <div class="form-text">Leave blank if the document does not expire</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="created_at" class="form-label">Created At</label>
                                    <input type="text" class="form-control" id="created_at" value="<?php echo formatDate($document['created_at'], 'd M Y, H:i'); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose*</label>
                                <textarea class="form-control" id="purpose" name="purpose" rows="3" required><?php echo $document['purpose']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Document File</label>
                                
                                <?php if (!empty($document['file_path'])): ?>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <p class="mb-0">Current file: <strong><?php echo basename($document['file_path']); ?></strong></p>
                                            </div>
                                            <div>
                                                <a href="../<?php echo $document['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">View File</a>
                                            </div>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="remove_file" name="remove_file" value="1">
                                            <label class="form-check-label" for="remove_file">
                                                Remove current file
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No file currently attached to this document.</p>
                                <?php endif; ?>
                                
                                <input type="file" class="form-control mt-2" id="document_file" name="document_file">
                                <div class="form-text">Upload a new file to replace the current one (optional, max 5MB)</div>
                            </div>
                            
                            <div class="text-end">
                                <a href="documents.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="update_document" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">QR Code</h5>
                    </div>
                    <div class="card-body text-center">
                        <p>The following QR code can be used to verify this document:</p>
                        <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo urlencode(getDocumentQRContent($document['doc_code'])); ?>&choe=UTF-8" class="img-thumbnail" alt="QR Code">
                        <div class="mt-3">
                            <a href="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?php echo urlencode(getDocumentQRContent($document['doc_code'])); ?>&choe=UTF-8" download="doc-<?php echo $document['doc_code']; ?>-qr.png" class="btn btn-sm btn-outline-primary">Download QR Code</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 