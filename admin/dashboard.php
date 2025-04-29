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

// Get some statistics
// Count of valid documents
$sql = "SELECT COUNT(*) AS doc_count FROM documents WHERE status = 'valid'";
$result = $conn->query($sql);
$valid_docs = $result->fetch_assoc()['doc_count'];

// Count of active employees
$sql = "SELECT COUNT(*) AS emp_count FROM employees WHERE status = 'active'";
$result = $conn->query($sql);
$active_employees = $result->fetch_assoc()['emp_count'];

// Count of verification logs in the last 7 days
$sql = "SELECT COUNT(*) AS log_count FROM verification_logs WHERE verified_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$result = $conn->query($sql);
$recent_verifications = $result->fetch_assoc()['log_count'];

// Count of documents expiring in the next 30 days
$sql = "SELECT COUNT(*) AS exp_count FROM documents WHERE status = 'valid' AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$result = $conn->query($sql);
$expiring_docs = $result->fetch_assoc()['exp_count'];

// Recent verification activity
$sql = "SELECT *, DATE_FORMAT(verified_at, '%d %b %Y, %H:%i') AS formatted_time FROM verification_logs ORDER BY verified_at DESC LIMIT 10";
$recent_activity = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
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
                    <a href="dashboard.php" class="sidebar-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="documents.php" class="sidebar-link"><i class="bi bi-file-earmark-text"></i> Manage Documents</a>
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
                    <h1>Dashboard</h1>
                    <div>
                        <a href="../index.php" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-eye"></i> View Public Site
                        </a>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-primary text-white stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Active Documents</h5>
                                        <h2 class="mb-0"><?php echo $valid_docs; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-file-earmark-check"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="documents.php" class="text-white text-decoration-none small">View Details</a>
                                <div><i class="bi bi-arrow-right text-white"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-success text-white stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Active Employees</h5>
                                        <h2 class="mb-0"><?php echo $active_employees; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="employees.php" class="text-white text-decoration-none small">View Details</a>
                                <div><i class="bi bi-arrow-right text-white"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-info text-white stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Recent Verifications</h5>
                                        <h2 class="mb-0"><?php echo $recent_verifications; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="logs.php" class="text-white text-decoration-none small">View Logs</a>
                                <div><i class="bi bi-arrow-right text-white"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card bg-warning text-dark stat-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">Expiring Soon</h5>
                                        <h2 class="mb-0"><?php echo $expiring_docs; ?></h2>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a href="documents.php?filter=expiring" class="text-dark text-decoration-none small">View Details</a>
                                <div><i class="bi bi-arrow-right text-dark"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity Table -->
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">Recent Verification Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Document/ID</th>
                                        <th>Status</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_activity->num_rows > 0): ?>
                                        <?php while ($log = $recent_activity->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $log['formatted_time']; ?></td>
                                                <td>
                                                    <?php if (!empty($log['document_id'])): ?>
                                                        <span class="badge bg-primary">DOC</span> <?php echo $log['document_id']; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">ID</span> <?php echo $log['employee_id']; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $verification_type = $log['verification_type'];
                                                    
                                                    if ($verification_type == 'document') {
                                                        echo '<span class="badge bg-primary">Document</span>';
                                                    } else if ($verification_type == 'id_card') {
                                                        echo '<span class="badge bg-success">ID Card</span>';
                                                    } else {
                                                        echo '<span class="badge bg-secondary">Unknown</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $log['ip_address']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No recent activity</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <a href="logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">Document System Quick Links</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="add-document.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-file-earmark-plus"></i> Register New Document
                                    </a>
                                    <a href="documents.php?filter=expired" class="list-group-item list-group-item-action">
                                        <i class="bi bi-calendar-x"></i> View Expired Documents
                                    </a>
                                    <a href="documents.php?filter=revoked" class="list-group-item list-group-item-action">
                                        <i class="bi bi-file-earmark-x"></i> View Revoked Documents
                                    </a>
                                    <a href="../verify.php" target="_blank" class="list-group-item list-group-item-action">
                                        <i class="bi bi-search"></i> Verify a Document
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">ID System Quick Links</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="add-employee.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-person-plus"></i> Add New Employee
                                    </a>
                                    <a href="id-templates.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-card-heading"></i> Manage ID Templates
                                    </a>
                                    <a href="batch-id.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-collection"></i> Batch Generate IDs
                                    </a>
                                    <a href="../id-verify.php" target="_blank" class="list-group-item list-group-item-action">
                                        <i class="bi bi-search"></i> Verify an ID
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
</body>
</html> 