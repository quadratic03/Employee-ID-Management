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

// Check if employee ID is provided
if (!isset($_GET['emp_id']) && !isset($_GET['id'])) {
    $_SESSION['error_msg'] = "Invalid employee ID.";
    header("Location: employees.php");
    exit();
}

// Use either emp_id or id parameter
$employee_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : $_GET['id'];
if (!is_numeric($employee_id)) {
    $_SESSION['error_msg'] = "Invalid employee ID format.";
    header("Location: employees.php");
    exit();
}

// Get employee details
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_msg'] = "Employee not found.";
    header("Location: employees.php");
    exit();
}

$employee = $result->fetch_assoc();
$stmt->close();

// Get ID template
$template_id = isset($_GET['template_id']) ? $_GET['template_id'] : null;

if ($template_id) {
    $sql = "SELECT * FROM id_templates WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template_result = $stmt->get_result();
    
    if ($template_result->num_rows > 0) {
        $template = $template_result->fetch_assoc();
    } else {
        // Fallback to default template
        $sql = "SELECT * FROM id_templates WHERE is_default = 1 LIMIT 1";
        $template_result = $conn->query($sql);
        $template = $template_result->fetch_assoc();
    }
    
    $stmt->close();
} else {
    // Get default template
    $sql = "SELECT * FROM id_templates WHERE is_default = 1 LIMIT 1";
    $template_result = $conn->query($sql);
    $template = $template_result->fetch_assoc();
}

// If no template found, use a hardcoded default
if (!$template) {
    $template = [
        'html_content' => '<div class="id-card">
            <div class="header">
                <img src="../assets/img/logo.png" class="logo">
                <div class="title">EMPLOYEE IDENTIFICATION</div>
            </div>
            <div class="content">
                <div class="photo-section">
                    <img src="{PHOTO_PATH}" alt="Employee Photo" class="photo">
                </div>
                <div class="info-section">
                    <div class="fullname">{FULL_NAME}</div>
                    <div class="position">rank {POSITION}</div>
                    <div class="department">{DEPARTMENT}</div>
                    <div class="id-number">{ID_NUMBER}</div>
                </div>
                <div class="qr-section">
                    <div class="qr-code">{QR_CODE}</div>
                </div>
            </div>
        </div>',
        'css_styles' => '.id-card {
            width: 3.375in;
            height: 2.125in;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            overflow: hidden;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
        }
        .header {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .logo {
            height: 24px;
            margin-bottom: 5px;
        }
        .title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }
        .content {
            display: flex;
            padding: 15px;
            position: relative;
            flex: 1;
        }
        .photo-section {
            width: 60px;
            margin-right: 15px;
        }
        .photo {
            width: 100%;
            height: auto;
            border: 1px solid #ddd;
        }
        .info-section {
            flex: 1;
        }
        .fullname {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
            color: #333;
        }
        .position, .department, .id-number {
            font-size: 12px;
            margin-bottom: 3px;
            color: #555;
        }
        .position {
            font-style: italic;
        }
        .qr-section {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 60px;
            height: 60px;
        }
        .qr-code img {
            width: 100%;
            height: 100%;
        }'
    ];
}

// Get all templates for selection
$sql = "SELECT id, name FROM id_templates ORDER BY is_default DESC, name ASC";
$all_templates = $conn->query($sql);

// Replace template placeholders with employee data
$template_content = $template['html_content'];
$css_styles = isset($template['css_styles']) ? $template['css_styles'] : '';

// Prepare employee data for template
$photo_path = !empty($employee['photo']) && file_exists('../' . $employee['photo'])
    ? '../' . $employee['photo']
    : '../assets/img/placeholder.jpg';

// Format dates
$issue_date = formatDate($employee['issue_date'], 'd M Y');
$expiry_date = !empty($employee['expiry_date']) ? formatDate($employee['expiry_date'], 'd M Y') : 'No Expiry';

// Generate QR code for verification
$qr_url = getIDQRContent($employee['id_number']);
$qr_code = '<img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($qr_url) . '&choe=UTF-8" alt="QR Code" style="width:100%; height:100%; display:block;">';

// Replace placeholders with actual data
$replacements = [
    '{{FULLNAME}}' => $employee['fullname'],
    '{FULL_NAME}' => $employee['fullname'],
    '{{POSITION}}' => $employee['position'],
    '{POSITION}' => $employee['position'],
    '{{DEPARTMENT}}' => $employee['department'],
    '{DEPARTMENT}' => $employee['department'],
    '{{ID_NUMBER}}' => $employee['id_number'],
    '{ID_NUMBER}' => $employee['id_number'],
    '{{ISSUE_DATE}}' => $issue_date,
    '{ISSUE_DATE}' => $issue_date,
    '{{EXPIRY_DATE}}' => $expiry_date,
    '{EXPIRY_DATE}' => $expiry_date,
    '{{PHOTO}}' => $photo_path,
    '{PHOTO_PATH}' => $photo_path,
    '{{QR_CODE}}' => $qr_code,
    '{QR_CODE}' => $qr_code,
];

foreach ($replacements as $placeholder => $value) {
    $template_content = str_replace($placeholder, $value, $template_content);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print ID Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
                background-color: #fff;
            }
            .print-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
                width: 100%;
                height: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .id-card {
                margin: 0 auto;
                transform: scale(1) !important;
                page-break-inside: avoid;
                box-shadow: none !important;
            }
            .id-preview {
                padding: 0;
                border: none;
                margin: 0;
                background: none;
                min-height: unset;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            @page {
                size: 3.375in 2.125in;
                margin: 0;
            }
        }
        
        .print-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .id-preview {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px dashed #ccc;
            min-height: 300px;
        }
        
        /* Allow proper scaling of ID card in preview */
        .id-card {
            transform: scale(1);
            transform-origin: center center;
            margin: 30px 0;
        }
        
        /* Include template CSS */
        <?php echo $css_styles; ?>
    </style>
</head>
<body>
    <div class="container-fluid no-print">
        <div class="d-flex justify-content-between align-items-center p-3 bg-dark text-white">
            <h1 class="h4 mb-0">ID Card Preview</h1>
            <div>
                <button onclick="window.print()" class="btn btn-light">
                    <i class="bi bi-printer"></i> Print ID Card
                </button>
                <a href="employees.php" class="btn btn-outline-light ms-2">
                    <i class="bi bi-arrow-left"></i> Back to Employees
                </a>
            </div>
        </div>
    </div>
    
    <div class="container no-print mt-3">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">Employee Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo $employee['fullname']; ?></p>
                                <p><strong>Position:</strong> <?php echo $employee['position']; ?></p>
                                <p><strong>Department:</strong> <?php echo $employee['department']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>ID Number:</strong> <?php echo $employee['id_number']; ?></p>
                                <p><strong>Issue Date:</strong> <?php echo $issue_date; ?></p>
                                <p><strong>Expiry Date:</strong> <?php echo $expiry_date; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Template Selection</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="get">
                            <input type="hidden" name="emp_id" value="<?php echo $employee['id']; ?>">
                            <div class="mb-3">
                                <label for="template_select" class="form-label">Select ID Card Template:</label>
                                <select name="template_id" id="template_select" class="form-select">
                                    <?php
                                    // Get all templates
                                    $sql = "SELECT id, name FROM id_templates ORDER BY is_default DESC, name ASC";
                                    $template_result = $conn->query($sql);
                                    while ($template_row = $template_result->fetch_assoc()) {
                                        $selected = (isset($_GET['template_id']) && $_GET['template_id'] == $template_row['id']) ? 'selected' : '';
                                        echo '<option value="' . $template_row['id'] . '" ' . $selected . '>' . $template_row['name'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Apply Template
                                </button>
                                <button type="button" onclick="window.print();" class="btn btn-success">
                                    <i class="bi bi-printer"></i> Print ID Card
                                </button>
                            </div>
                        </form>
                        <hr>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="employees.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Employees
                            </a>
                            <a href="edit-employee.php?id=<?php echo $employee_id; ?>" class="btn btn-info">
                                <i class="bi bi-pencil"></i> Edit Employee
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="print-container">
        <h4 class="text-center mb-3 no-print">
            <i class="bi bi-credit-card-2-front"></i> 
            ID Card Preview
        </h4>
        
        <div class="id-preview">
            <?php echo $template_content; ?>
        </div>
        
        <div class="alert alert-info no-print mt-4">
            <h5><i class="bi bi-info-circle"></i> Printing Instructions</h5>
            <p>For best results:</p>
            <ul>
                <li>Use card stock paper for printing</li>
                <li>Set printer to print at <strong>actual size (100% scale)</strong> - do not use "fit to page"</li>
                <li>Select <strong>landscape orientation</strong></li>
                <li>Turn off headers and footers in your browser's print settings</li>
                <li>Select "No Margins" or smallest possible margins in print options</li>
                <li>Verify the printed card measures exactly 3.375 × 2.125 inches (standard ID card size)</li>
            </ul>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 