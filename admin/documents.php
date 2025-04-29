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

// Handle document status update
if (isset($_POST['update_status']) && isset($_POST['doc_id']) && isset($_POST['status'])) {
    $doc_id = $_POST['doc_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE documents SET status = ? WHERE doc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $doc_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Document status updated successfully.";
    } else {
        $_SESSION['error_msg'] = "Error updating document status: " . $conn->error;
    }
    
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: documents.php");
    exit();
}

// Handle document deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $doc_id = $_GET['delete'];
    
    // First, get the file path to delete the actual file
    $sql = "SELECT file_path FROM documents WHERE doc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $document = $result->fetch_assoc();
        
        // Delete the physical file if it exists
        if (!empty($document['file_path']) && file_exists('../' . $document['file_path'])) {
            unlink('../' . $document['file_path']);
        }
        
        // Now delete the database record
        $sql = "DELETE FROM documents WHERE doc_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $doc_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Document deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "Error deleting document: " . $conn->error;
        }
    }
    
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: documents.php");
    exit();
}

// Set up pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Filter by status if specified
$filter_clause = "";
$filter_params = [];
$filter_types = "";

if (isset($_GET['filter'])) {
    switch ($_GET['filter']) {
        case 'valid':
            $filter_clause = "WHERE status = ?";
            $filter_params = ['valid'];
            $filter_types = "s";
            break;
        case 'expired':
            $filter_clause = "WHERE status = ?";
            $filter_params = ['expired'];
            $filter_types = "s";
            break;
        case 'revoked':
            $filter_clause = "WHERE status = ?";
            $filter_params = ['revoked'];
            $filter_types = "s";
            break;
        case 'expiring':
            $filter_clause = "WHERE status = 'valid' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            break;
    }
}

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    
    if (empty($filter_clause)) {
        $filter_clause = "WHERE (doc_code LIKE ? OR issued_to LIKE ? OR purpose LIKE ?)";
        $filter_params = [$search_term, $search_term, $search_term];
        $filter_types = "sss";
    } else {
        $filter_clause .= " AND (doc_code LIKE ? OR issued_to LIKE ? OR purpose LIKE ?)";
        $filter_params = array_merge($filter_params, [$search_term, $search_term, $search_term]);
        $filter_types .= "sss";
    }
}

// Get total number of documents based on filters
$sql = "SELECT COUNT(*) as total FROM documents $filter_clause";

if (!empty($filter_params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($filter_types, ...$filter_params);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_records = $result->fetch_assoc()['total'];
    $stmt->close();
} else {
    $result = $conn->query($sql);
    $total_records = $result->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Get documents for the current page with filters
$sql = "SELECT * FROM documents $filter_clause ORDER BY doc_id DESC LIMIT $offset, $records_per_page";

if (!empty($filter_params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($filter_types, ...$filter_params);
    $stmt->execute();
    $documents = $stmt->get_result();
    $stmt->close();
} else {
    $documents = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
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
                    <h1>Manage Documents</h1>
                    <a href="add-document.php" class="btn btn-primary">
                        <i class="bi bi-file-earmark-plus"></i> Add New Document
                    </a>
                </div>
                
                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['success_msg'];
                        unset($_SESSION['success_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php 
                        echo $_SESSION['error_msg'];
                        unset($_SESSION['error_msg']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Document List</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filter and Search -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="btn-group" role="group">
                                    <a href="documents.php" class="btn btn-outline-secondary <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">All</a>
                                    <a href="documents.php?filter=valid" class="btn btn-outline-success <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'valid') ? 'active' : ''; ?>">Valid</a>
                                    <a href="documents.php?filter=expired" class="btn btn-outline-warning <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'expired') ? 'active' : ''; ?>">Expired</a>
                                    <a href="documents.php?filter=revoked" class="btn btn-outline-danger <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'revoked') ? 'active' : ''; ?>">Revoked</a>
                                    <a href="documents.php?filter=expiring" class="btn btn-outline-info <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'expiring') ? 'active' : ''; ?>">Expiring Soon</a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <form action="documents.php" method="GET" class="d-flex">
                                    <?php if (isset($_GET['filter'])): ?>
                                        <input type="hidden" name="filter" value="<?php echo $_GET['filter']; ?>">
                                    <?php endif; ?>
                                    <input type="text" name="search" class="form-control" placeholder="Search by code, name or purpose" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                                    <button type="submit" class="btn btn-primary ms-2"><i class="bi bi-search"></i></button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Documents Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Doc Code</th>
                                        <th>Issued To</th>
                                        <th>Purpose</th>
                                        <th>Issue Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($documents->num_rows > 0): ?>
                                        <?php while ($document = $documents->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $document['doc_code']; ?></td>
                                                <td><?php echo $document['issued_to']; ?></td>
                                                <td><?php echo strlen($document['purpose']) > 30 ? substr($document['purpose'], 0, 30) . '...' : $document['purpose']; ?></td>
                                                <td><?php echo formatDate($document['issue_date']); ?></td>
                                                <td><?php echo formatDate($document['expiry_date']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($document['status']) {
                                                        case 'valid':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'expired':
                                                            $status_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'revoked':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo ucfirst($document['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit-document.php?id=<?php echo $document['doc_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="../verify.php?code=<?php echo $document['doc_code']; ?>" target="_blank" class="btn btn-sm btn-success" title="Verify">
                                                            <i class="bi bi-check2-circle"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $document['doc_id']; ?>" title="Change Status">
                                                            <i class="bi bi-arrow-repeat"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $document['doc_id']; ?>" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Status Change Modal -->
                                                    <div class="modal fade" id="statusModal<?php echo $document['doc_id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Change Document Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Document Code: <strong><?php echo $document['doc_code']; ?></strong></p>
                                                                    <p>Current Status: <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($document['status']); ?></span></p>
                                                                    
                                                                    <form method="POST" action="documents.php">
                                                                        <input type="hidden" name="doc_id" value="<?php echo $document['doc_id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="status" class="form-label">New Status</label>
                                                                            <select class="form-select" name="status" id="status" required>
                                                                                <option value="">-- Select Status --</option>
                                                                                <option value="valid" <?php echo $document['status'] == 'valid' ? 'selected' : ''; ?>>Valid</option>
                                                                                <option value="expired" <?php echo $document['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                                                                <option value="revoked" <?php echo $document['status'] == 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="text-end">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Delete Confirmation Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $document['doc_id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete the document with code <strong><?php echo $document['doc_code']; ?></strong>?</p>
                                                                    <p class="text-danger">This action cannot be undone and will permanently delete the document and its file.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <a href="documents.php?delete=<?php echo $document['doc_id']; ?>" class="btn btn-danger">Delete</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No documents found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" tabindex="-1">Previous</a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 