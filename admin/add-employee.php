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
if (isset($_POST['add_employee'])) {
    // Get form data
    $full_name = sanitizeInput($_POST['full_name']);
    $position = sanitizeInput($_POST['position']);
    $department = sanitizeInput($_POST['department']);
    $issue_date = sanitizeInput($_POST['issue_date']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitizeInput($_POST['expiry_date']) : null;
    $status = sanitizeInput($_POST['status']);
    
    // Validate expiry date (cannot be less than current date)
    if (!empty($expiry_date) && $expiry_date < date('Y-m-d')) {
        $message = "Error: Expiry date cannot be earlier than the current date.";
        $message_type = 'danger';
    } else {
        // Generate a unique ID number based on department
        $dept_code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $department), 0, 2));
        if (empty($dept_code)) {
            $dept_code = 'EMP'; // Default code if department doesn't have letters
        }
        $id_number = generateIDNumber($dept_code, $conn);
        
        // Check if a photo was uploaded
        $photo_path = '';
        if (isset($_FILES['employee_photo']) && $_FILES['employee_photo']['error'] == 0) {
            $upload_result = uploadFile($_FILES['employee_photo'], '../uploads/photos/');
            
            if ($upload_result['success']) {
                $photo_path = $upload_result['file_path'];
            } else {
                $message = $upload_result['message'];
                $message_type = 'danger';
            }
        }
        
        // If no upload error, insert employee into database
        if (empty($message)) {
            $sql = "INSERT INTO employees (fullname, position, department, id_number, photo, issue_date, expiry_date, status, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $user_id = $_SESSION['user_id'];
            $stmt->bind_param("ssssssssi", $full_name, $position, $department, $id_number, $photo_path, $issue_date, $expiry_date, $status, $user_id);
            
            if ($stmt->execute()) {
                $employee_id = $conn->insert_id;
                $message = "Employee added successfully with ID number: $id_number";
                $message_type = 'success';
                
                // Reset the form
                $full_name = $position = $department = $issue_date = $expiry_date = '';
                
                // Optionally redirect to print ID
                if (isset($_POST['print_after_save']) && $_POST['print_after_save'] == 1) {
                    header("Location: print-id.php?id=$employee_id");
                    exit();
                }
            } else {
                $message = "Error adding employee: " . $conn->error;
                $message_type = 'danger';
            }
            
            $stmt->close();
        }
    }
}

// Get existing departments for dropdown
$sql = "SELECT DISTINCT department FROM employees ORDER BY department";
$departments = $conn->query($sql);

// Get department list as array for datalist
$department_list = [];
if ($departments->num_rows > 0) {
    while ($row = $departments->fetch_assoc()) {
        $department_list[] = $row['department'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .photo-preview {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border: 1px solid #ddd;
            margin-bottom: 15px;
            background-color: #f8f9fa;
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
                    <a href="employees.php" class="sidebar-link"><i class="bi bi-people"></i> Manage Employees</a>
                    <a href="add-employee.php" class="sidebar-link active"><i class="bi bi-person-plus"></i> Add Employee</a>
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
                    <h1>Add New Employee</h1>
                    <a href="employees.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Employees
                    </a>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Employee Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="add-employee.php" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="full_name" class="form-label">Full Name*</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo isset($full_name) ? $full_name : ''; ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="position" class="form-label">Position*</label>
                                            <input type="text" class="form-control" id="position" name="position" required value="<?php echo isset($position) ? $position : ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="department" class="form-label">Department*</label>
                                            <input type="text" class="form-control" id="department" name="department" list="departmentList" required value="<?php echo isset($department) ? $department : ''; ?>">
                                            <datalist id="departmentList">
                                                <?php foreach ($department_list as $dept): ?>
                                                    <option value="<?php echo $dept; ?>">
                                                <?php endforeach; ?>
                                            </datalist>
                                            <div class="form-text">Select an existing department or enter a new one</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="status" class="form-label">Status*</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" selected>Active</option>
                                                <option value="expired">Expired</option>
                                                <option value="revoked">Revoked</option>
                                                <option value="lost">Lost</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="issue_date" class="form-label">Issue Date*</label>
                                            <input type="date" class="form-control" id="issue_date" name="issue_date" required value="<?php echo isset($issue_date) ? $issue_date : date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="expiry_date" class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo isset($expiry_date) ? $expiry_date : ''; ?>">
                                            <div class="form-text">Leave blank if the ID does not expire</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-center">
                                    <label class="form-label">Employee Photo</label>
                                    <div class="d-flex justify-content-center">
                                        <img id="photoPreview" src="../assets/img/placeholder.jpg" alt="Photo Preview" class="photo-preview">
                                    </div>
                                    <input type="file" class="form-control mt-2" id="employee_photo" name="employee_photo" accept="image/*">
                                    <div class="form-text">Upload a photo for the ID card (JPG, PNG, max 5MB)</div>
                                </div>
                            </div>
                            
                            <div class="card bg-light mb-3 mt-3">
                                <div class="card-body">
                                    <h6 class="card-title">ID Number</h6>
                                    <p class="card-text">A unique ID number will be automatically generated based on the department when you submit this form.</p>
                                </div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="print_after_save" name="print_after_save">
                                <label class="form-check-label" for="print_after_save">
                                    Generate and print ID card after saving
                                </label>
                            </div>
                            
                            <div class="text-end">
                                <button type="reset" class="btn btn-secondary">Reset</button>
                                <button type="submit" name="add_employee" class="btn btn-success">
                                    <i class="bi bi-person-plus"></i> Add Employee
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set min date for expiry date input to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expiry_date').setAttribute('min', today);
        
        // Photo preview
        document.getElementById('employee_photo').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photoPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 