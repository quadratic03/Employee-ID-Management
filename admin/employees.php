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

// Handle employee status update
if (isset($_POST['update_status']) && isset($_POST['employee_id']) && isset($_POST['status'])) {
    $employee_id = $_POST['employee_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE employees SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $employee_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Employee status updated successfully.";
    } else {
        $_SESSION['error_msg'] = "Error updating employee status: " . $conn->error;
    }
    
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: employees.php");
    exit();
}

// Handle employee deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $employee_id = $_GET['delete'];
    
    // First, get the photo path to delete the actual photo
    $sql = "SELECT photo FROM employees WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        
        // Delete the physical photo if it exists
        if (!empty($employee['photo']) && file_exists('../' . $employee['photo'])) {
            unlink('../' . $employee['photo']);
        }
        
        // Now delete the database record
        $sql = "DELETE FROM employees WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $employee_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Employee deleted successfully.";
        } else {
            $_SESSION['error_msg'] = "Error deleting employee: " . $conn->error;
        }
    }
    
    $stmt->close();
    
    // Redirect to prevent form resubmission
    header("Location: employees.php");
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
        case 'active':
            $filter_clause = "WHERE status = ?";
            $filter_params = ['active'];
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
        case 'lost':
            $filter_clause = "WHERE status = ?";
            $filter_params = ['lost'];
            $filter_types = "s";
            break;
    }
}

// Search functionality
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    
    if (empty($filter_clause)) {
        $filter_clause = "WHERE (id_number LIKE ? OR fullname LIKE ? OR position LIKE ? OR department LIKE ?)";
        $filter_params = [$search_term, $search_term, $search_term, $search_term];
        $filter_types = "ssss";
    } else {
        $filter_clause .= " AND (id_number LIKE ? OR fullname LIKE ? OR position LIKE ? OR department LIKE ?)";
        $filter_params = array_merge($filter_params, [$search_term, $search_term, $search_term, $search_term]);
        $filter_types .= "ssss";
    }
}

// Department filter
if (isset($_GET['department']) && !empty($_GET['department'])) {
    $dept = $_GET['department'];
    
    if (empty($filter_clause)) {
        $filter_clause = "WHERE department = ?";
        $filter_params = [$dept];
        $filter_types = "s";
    } else {
        $filter_clause .= " AND department = ?";
        $filter_params = array_merge($filter_params, [$dept]);
        $filter_types .= "s";
    }
}

// Get total number of employees based on filters
$sql = "SELECT COUNT(*) as total FROM employees $filter_clause";

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

// Get employees for the current page with filters
$sql = "SELECT * FROM employees $filter_clause ORDER BY id DESC LIMIT $offset, $records_per_page";

if (!empty($filter_params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($filter_types, ...$filter_params);
    $stmt->execute();
    $employees = $stmt->get_result();
    $stmt->close();
} else {
    $employees = $conn->query($sql);
}

// Get unique departments for filter dropdown
$sql = "SELECT DISTINCT department FROM employees ORDER BY department";
$departments = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .employee-thumb {
            width: 40px;
            height: 50px;
            object-fit: cover;
            border-radius: 3px;
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
                    <a href="add-document.php" class="sidebar-link"><i class="bi bi-file-earmark-plus"></i> Add Document</a>
                    
                    <div class="sidebar-heading">ID System</div>
                    <a href="employees.php" class="sidebar-link active"><i class="bi bi-people"></i> Manage Employees</a>
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
                    <h1>Manage Employees</h1>
                    <a href="add-employee.php" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Add New Employee
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
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Employee List</h5>
                    </div>
                    <div class="card-body">
                        <!-- Filter and Search -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="btn-group" role="group">
                                    <a href="employees.php" class="btn btn-outline-secondary <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">All</a>
                                    <a href="employees.php?filter=active" class="btn btn-outline-success <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'active') ? 'active' : ''; ?>">Active</a>
                                    <a href="employees.php?filter=expired" class="btn btn-outline-warning <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'expired') ? 'active' : ''; ?>">Expired</a>
                                    <a href="employees.php?filter=revoked" class="btn btn-outline-danger <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'revoked') ? 'active' : ''; ?>">Revoked</a>
                                    <a href="employees.php?filter=lost" class="btn btn-outline-secondary <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'lost') ? 'active' : ''; ?>">Lost</a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="department-filter" onchange="filterByDepartment(this.value)">
                                    <option value="">All Departments</option>
                                    <?php while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['department']; ?>" <?php echo (isset($_GET['department']) && $_GET['department'] == $dept['department']) ? 'selected' : ''; ?>>
                                            <?php echo $dept['department']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <form action="employees.php" method="GET" class="d-flex">
                                    <?php if (isset($_GET['filter'])): ?>
                                        <input type="hidden" name="filter" value="<?php echo $_GET['filter']; ?>">
                                    <?php endif; ?>
                                    <?php if (isset($_GET['department'])): ?>
                                        <input type="hidden" name="department" value="<?php echo $_GET['department']; ?>">
                                    <?php endif; ?>
                                    <input type="text" name="search" class="form-control" placeholder="Search by ID, name, position or department" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                                    <button type="submit" class="btn btn-primary ms-2"><i class="bi bi-search"></i></button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Employees Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>ID Number</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Issue Date</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($employees->num_rows > 0): ?>
                                        <?php while ($employee = $employees->fetch_assoc()): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if (!empty($employee['photo']) && file_exists('../' . $employee['photo'])): ?>
                                                        <img src="../<?php echo $employee['photo']; ?>" alt="Employee Photo" class="employee-thumb">
                                                    <?php else: ?>
                                                        <img src="../assets/img/placeholder.jpg" alt="No Photo" class="employee-thumb">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $employee['id_number']; ?></td>
                                                <td><?php echo $employee['fullname']; ?></td>
                                                <td><?php echo $employee['position']; ?></td>
                                                <td><?php echo $employee['department']; ?></td>
                                                <td><?php echo formatDate($employee['issue_date']); ?></td>
                                                <td><?php echo formatDate($employee['expiry_date']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch ($employee['status']) {
                                                        case 'active':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        case 'expired':
                                                            $status_class = 'bg-warning text-dark';
                                                            break;
                                                        case 'revoked':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                        case 'lost':
                                                            $status_class = 'bg-secondary';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo ucfirst($employee['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit-employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="print-id.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-info" title="Print ID">
                                                            <i class="bi bi-printer"></i>
                                                        </a>
                                                        <a href="../id-verify.php?id=<?php echo $employee['id_number']; ?>" target="_blank" class="btn btn-sm btn-success" title="Verify">
                                                            <i class="bi bi-check2-circle"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $employee['id']; ?>" title="Change Status">
                                                            <i class="bi bi-arrow-repeat"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $employee['id']; ?>" title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Status Change Modal -->
                                                    <div class="modal fade" id="statusModal<?php echo $employee['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Change Employee ID Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Employee: <strong><?php echo $employee['fullname']; ?></strong></p>
                                                                    <p>ID Number: <strong><?php echo $employee['id_number']; ?></strong></p>
                                                                    <p>Current Status: <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($employee['status']); ?></span></p>
                                                                    
                                                                    <form method="POST" action="employees.php">
                                                                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="status" class="form-label">New Status</label>
                                                                            <select class="form-select" name="status" id="status" required>
                                                                                <option value="">-- Select Status --</option>
                                                                                <option value="active" <?php echo $employee['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                                <option value="expired" <?php echo $employee['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                                                                <option value="revoked" <?php echo $employee['status'] == 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                                                                                <option value="lost" <?php echo $employee['status'] == 'lost' ? 'selected' : ''; ?>>Lost</option>
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
                                                    <div class="modal fade" id="deleteModal<?php echo $employee['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirm Delete</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to delete the employee record for <strong><?php echo $employee['fullname']; ?></strong> with ID <strong><?php echo $employee['id_number']; ?></strong>?</p>
                                                                    <p class="text-danger">This action cannot be undone and will permanently delete the employee record and photo.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <a href="employees.php?delete=<?php echo $employee['id']; ?>" class="btn btn-danger">Delete</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No employees found</td>
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
                
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <a href="add-employee.php" class="btn btn-outline-success">
                                        <i class="bi bi-person-plus"></i> Add New Employee
                                    </a>
                                    <a href="batch-id.php" class="btn btn-outline-primary">
                                        <i class="bi bi-collection"></i> Batch Generate IDs
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-grid gap-2">
                                    <a href="id-templates.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-card-heading"></i> Manage ID Templates
                                    </a>
                                    <a href="print-all.php" class="btn btn-outline-dark">
                                        <i class="bi bi-printer"></i> Print All Active IDs
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterByDepartment(department) {
            const currentUrl = new URL(window.location.href);
            const params = currentUrl.searchParams;
            
            if (department) {
                params.set('department', department);
            } else {
                params.delete('department');
            }
            
            // Keep other existing parameters
            const newUrl = currentUrl.pathname + '?' + params.toString();
            window.location.href = newUrl;
        }
    </script>
</body>
</html> 