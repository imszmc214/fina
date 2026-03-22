<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');
require_once 'includes/accounting_functions.php';

// Toast message handling
$toast_message = '';
$toast_type = '';

// AJAX: Get reimbursement details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_reimbursement_details') {
    $report_id = isset($_GET['report_id']) ? $conn->real_escape_string($_GET['report_id']) : '';
    $response = ['success' => false, 'data' => []];
    
    if ($report_id) {
        $sql = "SELECT * FROM reimbursements WHERE report_id = '$report_id'";
        $result = $conn->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = $row;
            
            // Get receipts
            $receipts_sql = "SELECT * FROM reimbursement_receipts WHERE report_id = '$report_id'";
            $receipts_result = $conn->query($receipts_sql);
            $response['data']['receipts'] = [];
            if ($receipts_result) {
                while ($receipt = $receipts_result->fetch_assoc()) {
                    $response['data']['receipts'][] = $receipt;
                }
            }
            
            // Get timeline
            $timeline_sql = "SELECT * FROM reimbursement_timeline WHERE report_id = '$report_id' ORDER BY created_at";
            $timeline_result = $conn->query($timeline_sql);
            $response['data']['timeline'] = [];
            if ($timeline_result) {
                while ($timeline = $timeline_result->fetch_assoc()) {
                    $response['data']['timeline'][] = $timeline;
                }
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX: Get budget allocation (for modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_budget') {
    $response = ['success' => false, 'message' => '', 'data' => null];
    $department = $_POST['department'] ?? '';
    
    try {
        $budget_sql = "SELECT * FROM budget_allocations 
                      WHERE department = ? 
                      AND status = 'active' 
                      ORDER BY id DESC 
                      LIMIT 1";
        
        $stmt = $conn->prepare($budget_sql);
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $budget = $result->fetch_assoc();
            $response['success'] = true;
            $response['data'] = $budget;
        } else {
            $response['success'] = false;
            $response['message'] = "No active budget allocation found for this department";
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = "Error fetching budget: " . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX: Update reimbursement status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $report_id = isset($_POST['report_id']) ? $conn->real_escape_string($_POST['report_id']) : '';
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';
    $approver_notes = isset($_POST['approver_notes']) ? $conn->real_escape_string($_POST['approver_notes']) : '';
    
    $result = processApproval($conn, $report_id, $status, $approver_notes);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit();
}

// AJAX: Get all pending reimbursements (for bulk approve modal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_bulk') {
    $response = ['success' => false, 'data' => []];
    
    $sql = "SELECT report_id, employee_name, department, reimbursement_type, amount, submitted_date 
            FROM reimbursements 
            WHERE status = 'Pending' 
            ORDER BY submitted_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX: Bulk Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_approve') {
    $response = ['success' => false, 'message' => ''];
    $report_ids = $_POST['report_ids'] ?? [];
    $notes = $_POST['notes'] ?? 'Approved via bulk action';
    
    if (empty($report_ids)) {
        $response['message'] = "No items selected for approval.";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($report_ids as $report_id) {
            $report_id = $conn->real_escape_string($report_id);
            $res = processApproval($conn, $report_id, 'Approved', $notes);
            if ($res['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Successfully approved $success_count item(s).";
        if ($error_count > 0) {
            $response['message'] .= " Failed to approve $error_count item(s).";
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

/**
 * Common function to process reimbursement approval/rejection/return
 */
function processApproval($conn, $report_id, $status, $approver_notes) {
    if (!$report_id || !$status) return ['success' => false, 'message' => 'Missing parameters'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update reimbursement
        $stmt = $conn->prepare("UPDATE reimbursements SET status = ?, approver_notes = ?, approved_date = NOW() WHERE report_id = ?");
        $stmt->bind_param("sss", $status, $approver_notes, $report_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        $stmt->close();
        
        // Add to timeline
        $action = $status === 'Approved' ? 'approved' : ($status === 'Rejected' ? 'rejected' : 'returned');
        
        // Safe ID Workaround
        $next_timeline_id = getNextAvailableId($conn, 'reimbursement_timeline');
        
        $timeline_sql = "INSERT INTO reimbursement_timeline (id, report_id, action, notes, created_at) VALUES (?, ?, ?, ?, NOW())";
        $timeline_stmt = $conn->prepare($timeline_sql);
        if (!$timeline_stmt) {
            throw new Exception("Timeline prepare failed: " . $conn->error);
        }
        $timeline_stmt->bind_param("isss", $next_timeline_id, $report_id, $action, $approver_notes);
        if (!$timeline_stmt->execute()) {
            throw new Exception("Timeline update failed: " . $timeline_stmt->error);
        }
        $timeline_stmt->close();
        
        // Update budget if approved
        if ($status === 'Approved') {
            updateBudgetAllocation($conn, $report_id);
            
            // Get reimbursement details to create payout record
            $reimb_sql = "SELECT * FROM reimbursements WHERE report_id = ?";
            $reimb_stmt = $conn->prepare($reimb_sql);
            $reimb_stmt->bind_param("s", $report_id);
            $reimb_stmt->execute();
            $reimb_result = $reimb_stmt->get_result();
            $reimb_data = $reimb_result->fetch_assoc();
            $reimb_stmt->close();
            
            if ($reimb_data) {
                // Create payout record in 'pa' table
                // FIX: Use original ID to avoid REIMB-REIMB duplication
                $payout_ref_id = $report_id;
                
                // FIX: Check if payout already exists to prevent duplicates
                $check_pa = $conn->prepare("SELECT id FROM pa WHERE reference_id = ?");
                $check_pa->bind_param("s", $payout_ref_id);
                $check_pa->execute();
                $pa_exists = $check_pa->get_result()->num_rows > 0;
                $check_pa->close();
                
                if (!$pa_exists) {
                    $account_name = $reimb_data['employee_name'];
                    $department = $reimb_data['department'];
                    $mode_of_payment = 'Cash'; 
                    $expense_category = 'Reimbursement'; 
                    $amount = $reimb_data['amount'];
                    $description = "Reimbursement: " . $reimb_data['description'];
                    $payment_due = date('Y-m-d', strtotime('+7 days')); 
                    $from_payable = 1; 
                    
                    // Audit Trail Info
                    $approver_name = ($_SESSION['givenname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '');
                    $approver_name = trim($approver_name) ?: 'Authorized Approver';
                    
                    // Safe ID Workaround
                    $next_pa_id = getNextAvailableId($conn, 'pa');
                    
                    $payout_sql = "INSERT INTO pa (
                        id, reference_id, account_name, employee_id, requested_department, mode_of_payment, 
                        expense_categories, amount, description, document, payment_due, submitted_date, from_payable,
                        transaction_type, payout_type, source_module, status,
                        approved_by, approved_at, approved_date, approval_source,
                        bank_name, bank_account_number, bank_account_name,
                        ecash_provider, ecash_account_name, ecash_account_number
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, 'Pending Disbursement', ?, NOW(), NOW(), 'Reimbursement Module', ?, ?, ?, ?, ?, ?)";
                    
                    $payout_stmt = $conn->prepare($payout_sql);
                    if (!$payout_stmt) {
                        throw new Exception("Payout prepare failed: " . $conn->error);
                    }
                    
                    $transaction_type = "Reimbursement";
                    $payout_type = "Reimbursement";
                    $source_module = "Reimbursement";
                    
                    // Initialize empty banking fields for Cash payment
                    $bank_name = '';
                    $bank_account_number = '';
                    $bank_account_name = '';
                    $ecash_provider = '';
                    $ecash_account_name = '';
                    $ecash_account_number = '';
                    
                    $payout_stmt->bind_param(
                        "issssssdsssissssssssss",
                        $next_pa_id,
                        $payout_ref_id,
                        $account_name,
                        $reimb_data['employee_id'],
                        $department,
                        $mode_of_payment,
                        $expense_category,
                        $amount,
                        $description,
                        $payment_due,
                        $reimb_data['submitted_date'],
                        $from_payable,
                        $transaction_type,
                        $payout_type,
                        $source_module,
                        $approver_name,
                        $bank_name,
                        $bank_account_number,
                        $bank_account_name,
                        $ecash_provider,
                        $ecash_account_name,
                        $ecash_account_number
                    );
                    
                    if (!$payout_stmt->execute()) {
                        throw new Exception("Error creating payout record: " . $payout_stmt->error);
                    }
                    $payout_stmt->close();
                }
                
                // Create journal entry and post to ledger
                try {
                    $journal_number = createReimbursementJournalEntry($conn, $reimb_data);
                    error_log("Journal entry created: $journal_number for reimbursement: " . $report_id);
                } catch (Exception $e) {
                    // Log warning but don't fail the entire transaction
                    error_log("WARNING: Failed to create journal entry for reimbursement $report_id: " . $e->getMessage());
                }
            }
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Status updated successfully!'];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// AJAX: Submit new reimbursement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_reimbursement') {
    $response = ['success' => false, 'message' => ''];
    
    // Generate unique report ID
    $report_id = 'REIMB-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Get form data
    $employee_name = $conn->real_escape_string($_POST['employee_name']);
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    $department = $conn->real_escape_string($_POST['department']);
    $expense_category = $conn->real_escape_string($_POST['expense_category']);
    $expense_subcategory = $conn->real_escape_string($_POST['expense_subcategory']);
    $amount = floatval($_POST['amount']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Safe ID Workaround
    $next_reimb_id = getNextAvailableId($conn, 'reimbursements');
    
    // Insert reimbursement
    $stmt = $conn->prepare("INSERT INTO reimbursements (id, report_id, employee_name, employee_id, department, reimbursement_type, amount, description, status, submitted_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
    $reimbursement_type = $expense_category . ' - ' . $expense_subcategory; // Combine for display
    $stmt->bind_param("isssssds", $next_reimb_id, $report_id, $employee_name, $employee_id, $department, $reimbursement_type, $amount, $description);
    
    if ($stmt->execute()) {
        // Add to timeline
        $next_tl_id = getNextAvailableId($conn, 'reimbursement_timeline');
        $timeline_sql = "INSERT INTO reimbursement_timeline (id, report_id, action, notes, created_at) VALUES (?, ?, ?, ?, NOW())";
        $timeline_stmt = $conn->prepare($timeline_sql);
        if ($timeline_stmt) {
            $action = 'submitted';
            $notes = 'Reimbursement submitted for approval';
            $timeline_stmt->bind_param("isss", $next_tl_id, $report_id, $action, $notes);
            $timeline_stmt->execute();
            $timeline_stmt->close();
        }
        
        // Handle file uploads
        if (isset($_FILES['receipts']) && !empty($_FILES['receipts']['name'][0])) {
            $upload_dir = UPLOAD_PATH;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['receipts']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['receipts']['error'][$key] === 0) {
                    $file_name = basename($_FILES['receipts']['name'][$key]);
                    $saved_name = time() . '_' . $file_name;
                    $file_full_path = $upload_dir . $saved_name;
                    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $file_size = $_FILES['receipts']['size'][$key];
                    
                    if (move_uploaded_file($tmp_name, $file_full_path)) {
                        // Safe ID Workaround
                        $next_receipt_id = getNextAvailableId($conn, 'reimbursement_receipts');
                        
                        // Store only the saved_name in file_path to work with view_pdf.php?file=...
                        $receipt_sql = "INSERT INTO reimbursement_receipts (id, report_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)";
                        $receipt_stmt = $conn->prepare($receipt_sql);
                        if ($receipt_stmt) {
                            $receipt_stmt->bind_param("issssi", $next_receipt_id, $report_id, $file_name, $saved_name, $file_type, $file_size);
                            $receipt_stmt->execute();
                            $receipt_stmt->close();
                        }
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Reimbursement submitted successfully!";
        $response['report_id'] = $report_id;
        
        // Store toast message
        $_SESSION['toast_message'] = "Reimbursement submitted successfully!";
        $_SESSION['toast_type'] = 'success';
    } else {
        $response['message'] = "Submission failed: " . $stmt->error;
        $_SESSION['toast_message'] = "Submission failed!";
        $_SESSION['toast_type'] = 'error';
    }
    
    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Export functionality
if (isset($_GET['export'])) {
    $format = $_GET['format'] ?? 'excel';
    $status_filter = $_GET['status_filter'] ?? '';
    $department_filter = $_GET['department_filter'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    $conditions = ["1=1"];
    
    if ($status_filter && $status_filter !== 'All') {
        $conditions[] = "status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    
    if ($department_filter && $department_filter !== 'All') {
        $conditions[] = "department = '" . $conn->real_escape_string($department_filter) . "'";
    }
    
    if ($date_from) {
        $conditions[] = "submitted_date >= '" . $conn->real_escape_string($date_from) . "'";
    }
    
    if ($date_to) {
        $conditions[] = "submitted_date <= '" . $conn->real_escape_string($date_to) . " 23:59:59'";
    }
    
    $sql = "SELECT * FROM reimbursements";
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY submitted_date DESC";
    
    $result = $conn->query($sql);
    
    // Generate export data
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'Report ID' => $row['report_id'],
                'Employee Name' => $row['employee_name'],
                'Employee ID' => $row['employee_id'],
                'Department' => $row['department'],
                'Type' => $row['reimbursement_type'],
                'Amount' => '₱' . number_format($row['amount'], 2),
                'Status' => $row['status'],
                'Submitted Date' => $row['submitted_date'],
                'Approved Date' => $row['approved_date'] ?? 'N/A',
                'Description' => $row['description']
            ];
        }
    }
    
    // Generate filename
    $filename = 'reimbursements_' . date('Y-m-d_H-i-s');
    
    if ($format === 'pdf') {
        // Redundant, handled client-side
    } elseif ($format === 'csv') {
        exportCSV($data, $filename);
    } elseif ($format === 'json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $data]);
        exit();
    } else {
        exportExcel($data, $filename);
    }
    
    exit();
}

function updateBudgetAllocation($conn, $report_id) {
    $sql = "SELECT amount, reimbursement_type, department FROM reimbursements WHERE report_id = '$report_id'";
    $result = $conn->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $amount = $row['amount'];
        $category = $row['reimbursement_type'];
        $department = $row['department'];
        
        $update_sql = "UPDATE budget_allocations 
                      SET spent = spent + $amount, 
                          remaining_balance = remaining_balance - $amount 
                      WHERE department = '$department' 
                      AND category LIKE '%$category%' 
                      AND status = 'active'";
        $conn->query($update_sql);
    }
}


function exportExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            table { border-collapse: collapse; width: 100%; }
            th { background-color: #f2f2f2; font-weight: bold; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            tr:nth-child(even) { background-color: #f9f9f9; }
        </style>
    </head>
    <body>';
    
    echo '<h2>Reimbursement Report</h2>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    
    echo '<table>';
    // Headers
    if (!empty($data)) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        // Data
        foreach ($data as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</table>';
    
    echo '</body></html>';
}

function exportCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    if (!empty($data)) {
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
}

// Check for toast messages
if (isset($_SESSION['toast_message'])) {
    $toast_message = $_SESSION['toast_message'];
    $toast_type = $_SESSION['toast_type'];
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
}

// For initial page load
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'Pending'; // Default to Pending
$department_filter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : '';
$date_from_filter = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to_filter = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$conditions = ["1=1"];

if ($status_filter && $status_filter !== 'All') {
    $conditions[] = "status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($department_filter && $department_filter !== 'All') {
    $conditions[] = "department = '" . $conn->real_escape_string($department_filter) . "'";
}

if ($type_filter && $type_filter !== 'All') {
    $conditions[] = "reimbursement_type = '" . $conn->real_escape_string($type_filter) . "'";
}

if ($date_from_filter) {
    $conditions[] = "DATE(submitted_date) >= '" . $conn->real_escape_string($date_from_filter) . "'";
}

if ($date_to_filter) {
    $conditions[] = "DATE(submitted_date) <= '" . $conn->real_escape_string($date_to_filter) . "'";
}

if ($search) {
    $conditions[] = "(employee_name LIKE '%" . $conn->real_escape_string($search) . "%' OR report_id LIKE '%" . $conn->real_escape_string($search) . "%' OR reimbursement_type LIKE '%" . $conn->real_escape_string($search) . "%' OR employee_id LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$count_sql = "SELECT COUNT(*) as total FROM reimbursements";
if (!empty($conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $conditions);
}

$count_result = $conn->query($count_sql);
$row_count = $count_result ? $count_result->fetch_assoc() : ['total' => 0];
$total_rows = $row_count['total'];
$total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

$sql = "SELECT * FROM reimbursements";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY submitted_date DESC LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);

// Calculate totals
$totalPending = $conn->query("SELECT COUNT(*) as count FROM reimbursements WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$totalApproved = $conn->query("SELECT COUNT(*) as count FROM reimbursements WHERE status = 'Approved'")->fetch_assoc()['count'] ?? 0;
$totalProcessing = $conn->query("SELECT COUNT(*) as count FROM reimbursements WHERE status = 'Processing'")->fetch_assoc()['count'] ?? 0;
$totalRejected = $conn->query("SELECT COUNT(*) as count FROM reimbursements WHERE status = 'Rejected'")->fetch_assoc()['count'] ?? 0;

$totalAmountPending = $conn->query("SELECT SUM(amount) as total FROM reimbursements WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
$totalAmount7Days = $conn->query("SELECT SUM(amount) as total FROM reimbursements WHERE status = 'Approved' AND approved_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;

$rows_data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows_data[] = $row;
    }
}

// Departments list
$departments = [
    'Human Resource-1', 
    'Human Resource-2',
    'Human Resource-3', 
    'Human Resource-4',
    'Core-1',
    'Core-2',
    'Logistic-1', 
    'Logistic-2',
    'Administrative',
    'Financials',
];

// Fetch expense categories and subcategories from chart of accounts
$expense_categories = [];
$expense_subcategories = [];
$category_query = "SELECT DISTINCT 
                        cat.name as category, 
                        sub.name as subcategory 
                    FROM chart_of_accounts_hierarchy sub
                    JOIN chart_of_accounts_hierarchy cat ON sub.parent_id = cat.id
                    WHERE sub.level = 3 
                    AND cat.level = 2
                    AND sub.status = 'active'
                    AND sub.type = 'Expense'
                    ORDER BY category, subcategory";
$category_result = $conn->query($category_query);
if ($category_result) {
    while ($cat_row = $category_result->fetch_assoc()) {
        $category = $cat_row['category'];
        $subcategory = $cat_row['subcategory'];
        
        if (!in_array($category, $expense_categories)) {
            $expense_categories[] = $category;
        }
        
        if (!isset($expense_subcategories[$category])) {
            $expense_subcategories[$category] = [];
        }
        if (!in_array($subcategory, $expense_subcategories[$category])) {
            $expense_subcategories[$category][] = $subcategory;
        }
    }
}
?>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Reimbursement Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="logo.png" type="img">
    <!-- jsPDF and autotable for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
<?php endif; ?>
    <style>
        /* Modal backdrop with blur */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999; /* Higher z-index to cover sidebar/cards */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 5vh auto;
            width: 95%;
            scrollbar-width: thin;
            scrollbar-color: #8b5cf6 #f3f4f6;
        }
        
        .modal-content::-webkit-scrollbar {
            width: 6px;
        }
        
        .modal-content::-webkit-scrollbar-track {
            background: #f3f4f6;
        }
        
        .modal-content::-webkit-scrollbar-thumb {
            background-color: #8b5cf6;
            border-radius: 20px;
        }
        
        .confirm-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .confirm-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
        }
        
        .confirm-title {
            font-size: 24px;
            font-weight: bold;
            color: #4F46E5;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirm-details {
            background-color: #F8FAFC;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .detail-label {
            font-weight: 600;
            color: #4B5563;
        }
        
        .detail-value {
            font-weight: 500;
            color: #111827;
        }
        
        /* Toast Notification Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }
        
        .toast {
            padding: 16px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast.success {
            background-color: #10b981;
            border-left: 4px solid #059669;
        }
        
        .toast.error {
            background-color: #ef4444;
            border-left: 4px solid #dc2626;
        }
        
        .toast.info {
            background-color: #3b82f6;
            border-left: 4px solid #1d4ed8;
        }
        
        .toast-icon {
            margin-right: 12px;
            font-size: 20px;
        }
        
        .toast-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            margin-left: 15px;
            opacity: 0.8;
        }
        
        .toast-close:hover {
            opacity: 1;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .drag-drop-area {
            border: 2px dashed #d1d5db;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
        }
        
        .drag-drop-area:hover {
            border-color: #8b5cf6;
            background-color: #faf5ff;
        }

        .active-receipt-tab {
            background-color: rgba(139, 92, 246, 0.2) !important;
            border-color: #8b5cf6 !important;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.5) !important;
        }
        
        .active-receipt-tab i {
            color: #a78bfa !important;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Active tab style */
        .active-tab {
            border-bottom: 4px solid #8b5cf6;
            color: #8b5cf6;
            font-weight: bold;
        }
        
        .hover-tab:hover {
            border-bottom: 2px solid #8b5cf6;
            color: #8b5cf6;
        }
        
        /* PDF Viewer Styles */
        .pdf-viewer-container {
            width: 100%;
            height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .pdf-toolbar {
            background-color: #f3f4f6;
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pdf-frame {
            width: 100%;
            height: calc(100% - 50px);
            border: none;
        }
        
        /* Returned notes styling */
        .returned-notes {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        
        .returned-notes h4 {
            color: #92400e;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
</head>
<body class="bg-gray-50">
    <?php include('sidebar.php'); ?>
<?php else: ?>
</head>
<body>
<?php endif; ?>
    
    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer">
        <?php if ($toast_message): ?>
        <div class="toast <?php echo $toast_type; ?>" id="autoToast">
            <div class="flex items-center">
                <i class="toast-icon <?php echo $toast_type == 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($toast_message); ?></span>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="overflow-y-auto h-full px-6">
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <div class="flex justify-between items-center px-6 py-6 font-poppins">
            <h1 class="text-2xl">Employee Reimbursement Dashboard</h1>
            <div class="text-sm">
                <a href="dashboard.php" class="text-black hover:text-blue-600">Home</a>
                /
                <a class="text-black">Accounts Payable</a>
                /
                <a href="reimbursement.php" class="text-blue-600 hover:text-blue-600">Reimbursement</a>
            </div>
        </div>
<?php endif; ?>

<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 px-4">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-yellow-500 flex items-center justify-center text-white">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-medium">Awaiting Approval</div>
                        <div class="text-2xl font-bold text-gray-900"><?= $totalPending ?></div>
                        <div class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full mt-1 inline-block">Needs attention</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-blue-500 flex items-center justify-center text-white">
                        <i class="fas fa-file-invoice-dollar text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-medium">Total Pending Amount</div>
                        <div class="text-2xl font-bold text-gray-900">₱<?= number_format($totalAmountPending, 2) ?></div>
                        <div class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full mt-1 inline-block">This month</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-green-500 flex items-center justify-center text-white">
                        <i class="fas fa-sync-alt text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-medium">Processing Payment</div>
                        <div class="text-2xl font-bold text-gray-900"><?= $totalProcessing ?></div>
                        <div class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full mt-1 inline-block">Ready for disbursement</div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-purple-500 flex items-center justify-center text-white">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase font-medium">7-Day Reimbursed</div>
                        <div class="text-2xl font-bold text-gray-900">₱<?= number_format($totalAmount7Days, 2) ?></div>
                        <div class="text-xs bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full mt-1 inline-block">Paid this week</div>
                    </div>
                </div>
            </div>
        </div>
<?php endif; ?>

        <!-- Main Content -->
        <div class="<?= !defined('UNIFIED_DASHBOARD_MODE') ? 'bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6' : 'p-4' ?>">
            <!-- Header with Tabs and Search -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                <!-- Status Tabs -->
                <div class="w-full lg:w-auto">
                    <div class="flex gap-2 font-poppins text-sm font-medium border-b border-gray-300" id="statusTabs">
                        <button type="button" data-status="Pending" class="px-4 py-2 rounded-t-lg hover-tab <?= $status_filter === 'Pending' ? 'active-tab' : 'text-gray-900' ?>">
                            PENDING <span class="text-xs opacity-80 ml-1">(<?= $totalPending ?>)</span>
                        </button>
                        <button type="button" data-status="Approved" class="px-4 py-2 rounded-t-lg hover-tab <?= $status_filter === 'Approved' ? 'active-tab' : 'text-gray-900' ?>">
                            APPROVED <span class="text-xs opacity-80 ml-1">(<?= $totalApproved ?>)</span>
                        </button>
                        <button type="button" data-status="Rejected" class="px-4 py-2 rounded-t-lg hover-tab <?= $status_filter === 'Rejected' ? 'active-tab' : 'text-gray-900' ?>">
                            RETURNED <span class="text-xs opacity-80 ml-1">(<?= $totalRejected ?>)</span>
                        </button>
                    </div>
                </div>
                
                <!-- Search and Actions -->
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-1 sm:flex-none sm:w-64">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search by Employee, Report ID, or Category..." 
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400 text-sm"></i>
                    </div>
                    
                    <!-- Filter and Export Buttons -->
                    <div class="flex gap-2">
                        <!-- Filter Dropdown -->
                        <div class="relative">
                            <button id="filterButton" class="px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-filter"></i>
                                Filters
                            </button>
                            <div id="filterMenu" class="absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 p-4 z-50 hidden">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Department</label>
                                        <select id="filterDepartment" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                            <option value="All">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept ?>" <?= $department_filter === $dept ? 'selected' : '' ?>><?= $dept ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Expense Category</label>
                                        <select id="filterType" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                            <option value="All">All Categories</option>
                                            <?php foreach ($expense_categories as $category): ?>
                                                <option value="<?= htmlspecialchars($category) ?>" <?= $type_filter === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Submitted Date From</label>
                                        <input type="date" id="filterDateFrom" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" value="<?= htmlspecialchars($date_from_filter) ?>">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Submitted Date To</label>
                                        <input type="date" id="filterDateTo" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" value="<?= htmlspecialchars($date_to_filter) ?>">
                                    </div>
                                    
                                    <div class="flex gap-2 pt-2">
                                        <button type="button" onclick="applyFilters()" class="flex-1 px-3 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700">
                                            Apply
                                        </button>
                                        <button type="button" onclick="clearFilters()" class="flex-1 px-3 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Export Dropdown -->
                        <div class="relative">
                            <button id="exportButton" class="px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="fas fa-download"></i>
                                Export
                            </button>
                            <div id="exportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 p-2 z-50 hidden">
                                <button onclick="exportData('pdf')" class="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center gap-2">
                                    <i class="fas fa-file-pdf text-red-500"></i>
                                    Export as PDF
                                </button>
                                <button onclick="exportData('excel')" class="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center gap-2">
                                    <i class="fas fa-file-excel text-green-500"></i>
                                    Export as Excel
                                </button>
                                <button onclick="exportData('csv')" class="w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md flex items-center gap-2">
                                    <i class="fas fa-file-csv text-blue-500"></i>
                                    Export as CSV
                                </button>
                            </div>
                        </div>
                        
                        <!-- Bulk Approve Button -->
                        <button onclick="openBulkApproveModal()" class="px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-2">
                            <i class="fas fa-check-double"></i>
                            Bulk Approve
                        </button>
                        
                        <!-- New Reimbursement Button -->
                        <button onclick="openNewModal()" class="px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            New
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <table class="w-full table-auto bg-white" id="reimbursementTable">
                    <thead>
                        <tr class="text-purple-800 uppercase text-sm leading-normal text-left sticky top-0 bg-white">
                            <th class="pl-6 pr-4 py-3">REPORT ID</th>
                            <th class="px-4 py-3">EMPLOYEE</th>
                            <th class="px-4 py-3">DEPARTMENT</th>
                            <th class="px-4 py-3">SUBMITTED DATE</th>
                            <th class="px-4 py-3">AGE</th>
                            <th class="px-4 py-3">STATUS</th>
                            <th class="px-4 py-3">TYPE</th>
                            <th class="px-4 py-3">AMOUNT</th>
                            <th class="px-4 py-3">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900 text-sm font-light" id="tableBody">
                        <?php if (count($rows_data) > 0): ?>
                            <?php foreach ($rows_data as $row): ?>
                                <?php
                                $age = floor((time() - strtotime($row['submitted_date'])) / (60 * 60 * 24));
                                $ageClass = 'bg-green-100 text-green-800';
                                if ($age > 3) $ageClass = 'bg-yellow-100 text-yellow-800';
                                if ($age > 7) $ageClass = 'bg-red-100 text-red-800';
                                
                                $statusClass = '';
                                switch($row['status']) {
                                    case 'Pending': $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                                    case 'Approved': $statusClass = 'bg-green-100 text-green-800'; break;
                                    case 'Rejected': $statusClass = 'bg-red-100 text-red-800'; break;
                                }
                                ?>
                                <tr class="hover:bg-gray-50 border-b border-gray-200">
                                    <td class="pl-6 pr-4 py-3 font-medium text-gray-900"><?= $row['report_id'] ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-user text-purple-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($row['employee_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><?= htmlspecialchars($row['department']) ?></td>
                                    <td class="px-4 py-3"><?= date('Y-m-d', strtotime($row['submitted_date'])) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $ageClass ?>">
                                            <?= $age ?>d
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= $row['reimbursement_type'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-900">₱<?= number_format($row['amount'], 2) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button onclick="viewReimbursement('<?= $row['report_id'] ?>')" class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-100 flex items-center gap-1">
                                                <i class="fas fa-eye text-xs"></i>
                                                Review
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-inbox text-3xl mb-2"></i>
                                        <div class="text-sm font-medium text-gray-500 mb-1">No reimbursement requests found</div>
                                        <p class="text-xs text-gray-400">Try adjusting your filters or submit a new request.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                <div class="text-sm text-gray-600" id="pageInfo">
                    Showing <?= min($records_per_page, count($rows_data)) ?> of <?= $total_rows ?> entries
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($total_pages > 1): ?>
                        <button id="prevBtn" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1 <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page <= 1 ? 'disabled' : '' ?> onclick="changePage(<?= $page - 1 ?>)">
                            <i class="fas fa-chevron-left text-xs"></i> Previous
                        </button>
                        <button id="nextBtn" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1 <?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page >= $total_pages ? 'disabled' : '' ?> onclick="changePage(<?= $page + 1 ?>)">
                            Next <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- View Reimbursement Modal -->
    <!-- View Reimbursement Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content !max-w-[1500px] !p-8 relative">
            <button onclick="closeModal('viewModal')" class="absolute top-4 right-4 text-gray-500 hover:text-purple-700 transition-colors">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center border-b pb-4">Review Reimbursement Request</h2>
            
            <div class="grid grid-cols-[400px_1fr] gap-8">
                <!-- Left Column: Details & Budget -->
                <div>
                    <h3 class="text-lg font-semibold text-purple-700 mb-4" id="modalReportId">Invoice Information</h3>
                    
                    <div id="reviewModalContent" class="space-y-3 text-sm">
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Employee Name:</span>
                            <span class="font-bold text-gray-900" id="modalEmployeeName">-</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Employee ID:</span>
                            <span class="text-gray-800" id="modalEmployeeId">-</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Department:</span>
                            <span class="text-gray-800" id="modalDepartment">-</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Submitted Date:</span>
                            <span class="text-gray-800" id="modalSubmittedDate">-</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Status:</span>
                            <span id="modalStatus" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">-</span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Type:</span>
                            <span class="text-gray-800" id="modalType"></span>
                        </div>
                        <div class="flex justify-between border-b pb-2">
                            <span class="font-semibold text-gray-600">Amount:</span>
                            <span class="font-bold text-green-600" id="modalAmount">₱0.00</span>
                        </div>
                        <div class="border-b pb-2">
                            <span class="font-semibold text-gray-600">Description:</span>
                            <p class="mt-1 text-gray-700" id="modalDescription">No description provided.</p>
                        </div>
                    </div>

                    <!-- Budget Allocation Section -->
                    <div id="budgetAllocationSection" class="mt-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-700 mb-3 flex items-center">
                            <i class="fas fa-chart-pie mr-2"></i>
                            Budget Allocation
                        </h3>
                        <div id="budgetAllocationContent" class="space-y-2 text-sm">
                            <div class="flex items-center justify-center py-4">
                                <i class="fas fa-spinner fa-spin text-blue-500 mr-2"></i>
                                <span class="text-gray-600">Loading budget information...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 pt-4 border-t flex gap-3" id="actionButtons">
                        <button onclick="openApproveConfirmation()" class="flex-1 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 inline-flex items-center justify-center font-bold text-sm">
                            <i class="fas fa-check-circle mr-2"></i>
                            Approve
                        </button>
                        <button onclick="openReturnConfirmation()" class="flex-1 bg-amber-500 text-white px-4 py-2 rounded-lg hover:bg-amber-600 inline-flex items-center justify-center font-bold text-sm">
                            <i class="fas fa-undo mr-2"></i>
                            Return
                        </button>
                    </div>

                    <!-- Return Notes Section (for Rejected) -->
                    <div id="returnedNotesSection" style="display: none;" class="mt-4 p-4 border-2 border-yellow-200 bg-yellow-50 rounded-lg">
                        <h4 class="font-semibold text-yellow-700 mb-2">Return Reason</h4>
                        <div class="text-sm text-gray-700" id="modalReturnNotes">No notes provided.</div>
                    </div>
                </div>

                <!-- Right Column: Document Viewer -->
                <div>
                    <h3 class="text-lg font-semibold text-purple-700 mb-2">Invoice Document</h3>
                    <div id="documentViewerContainer" class="border-2 border-gray-300 rounded-lg p-0 bg-gray-50 min-h-[750px] flex flex-col relative overflow-hidden">
                        <div id="pdfViewerPlaceholder" class="flex-1 flex items-center justify-center text-gray-500">No document available</div>
                        
                        <!-- Keep iframe hidden initially -->
                        <iframe id="pdfFrame" class="flex-1 w-full border-0 rounded hidden" frameborder="0"></iframe>
                        
                        <!-- Receipts Tray for multiple files -->
                        <div id="receiptsTray" class="bg-gray-900/90 backdrop-blur text-white p-2 border-t border-gray-700 hidden">
                            <div class="flex items-center justify-between mb-1.5 border-b border-gray-800 pb-1 px-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500" id="receiptsCount">0 Files</span>
                                <span class="text-[9px] text-gray-600 italic">Click to switch views</span>
                            </div>
                            <div id="receiptsList" class="flex flex-wrap gap-1.5">
                                <!-- Tabs here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Reimbursement Modal -->
    <div id="newModal" class="modal">
        <div class="modal-content !max-w-xl !p-0 relative">
            <button onclick="closeModal('newModal')" class="absolute top-4 right-4 text-gray-500 hover:text-purple-700 transition-colors z-10">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="p-8">
                <!-- Header -->
                <div class="mb-8 pr-8">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                        New Reimbursement Request
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Submit an expense report for management review.</p>
                </div>
                
                <!-- Form -->
                <form id="reimbursementForm" enctype="multipart/form-data" onsubmit="submitReimbursement(event)">
                    <div class="space-y-6">
                        <!-- Employee Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Employee Name *</label>
                                <input type="text" name="employee_name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="Enter name">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Employee ID *</label>
                                <input type="text" name="employee_id" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="Enter ID">
                            </div>
                        </div>
                        
                        <!-- Department & Category -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Department *</label>
                                <select name="department" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept ?>"><?= $dept ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Expense Category *</label>
                                <select name="expense_category" id="expenseCategory" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer" onchange="updateSubcategories()">
                                    <option value="">Select Category</option>
                                    <?php foreach ($expense_categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Subcategory -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Expense Subcategory *</label>
                            <select name="expense_subcategory" id="expenseSubcategory" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                        
                        <!-- Amount -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Amount *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-2.5 text-gray-400 text-sm font-bold">₱</span>
                                <input type="number" name="amount" required step="0.01" min="0" class="w-full pl-9 pr-4 py-2.5 bg-purple-50 border border-purple-200 rounded-lg text-sm font-bold text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Description *</label>
                            <textarea name="description" required rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none" placeholder="What was this expense for?"></textarea>
                        </div>
                        
                        <!-- Receipt Upload -->
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Receipts (Physical or Digital)</label>
                            
                            <!-- Drag Drop Area (Initial) -->
                            <div class="drag-drop-area border-2 !border-dashed border-gray-300 hover:border-purple-400 hover:bg-purple-50 transition-all rounded-xl p-8 text-center cursor-pointer" id="dragDropArea" ondragover="handleDragOver(event)" ondrop="handleDrop(event)" ondragleave="handleDragLeave(event)" onclick="document.getElementById('fileInput').click()">
                                <div class="bg-purple-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-file-invoice text-2xl text-purple-500"></i>
                                </div>
                                <div class="text-gray-900 text-base mb-1 font-bold">Drag & drop files here</div>
                                <div class="text-xs text-gray-500 mb-6 font-medium">Support PDF, JPG, PNG (Max 5MB each)</div>
                                <div class="px-6 py-2.5 bg-purple-600 text-white text-sm font-bold rounded-xl hover:bg-purple-700 transition-all shadow-sm inline-flex items-center gap-2">
                                    <i class="fas fa-search"></i> Browse Files
                                </div>
                            </div>

                            <!-- Hidden File Input -->
                            <input type="file" id="fileInput" name="receipts[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="handleFileSelect(event)">
                            
                            <!-- Add File Button (Hidden initially) -->
                            <div id="addFileContainer" class="hidden mt-2">
                                <button type="button" onclick="document.getElementById('fileInput').click()" class="w-full py-4 border-2 border-dashed border-purple-200 rounded-xl text-purple-600 font-bold text-sm hover:bg-purple-50 transition-all flex items-center justify-center gap-2 bg-purple-50/30">
                                    <i class="fas fa-plus-circle"></i> Add Another File
                                </button>
                            </div>

                            <!-- File List -->
                            <div class="mt-4 space-y-3" id="fileList"></div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex gap-4 mt-8 pt-6 border-t border-gray-100">
                        <button type="submit" class="flex-1 px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                            <i class="fas fa-paper-plane"></i>
                            Submit Request
                        </button>
                        <button type="button" onclick="closeModal('newModal')" class="flex-1 px-6 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Approve -->
    <div id="approveConfirmModal" class="modal">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
            <button onclick="closeModal('approveConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Confirm Approval</h2>
                <p class="text-gray-500 text-sm mt-1">Are you sure you want to approve this request?</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Report ID:</span>
                    <span class="font-bold text-gray-800" id="confirmApproveRefId">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Employee:</span>
                    <span class="font-bold text-gray-800" id="confirmApproveEmployee">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="font-bold text-purple-700" id="confirmApproveAmount">-</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="updateStatus('Approved')" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-md">
                    Confirm Approve
                </button>
                <button onclick="closeModal('approveConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Return -->
    <div id="returnConfirmModal" class="modal">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
            <button onclick="closeModal('returnConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-undo text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Return for Revision</h2>
                <p class="text-gray-500 text-sm mt-1">Please provide a reason for the return.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Report ID:</span>
                    <span class="font-bold text-gray-800" id="confirmReturnRefId">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Employee:</span>
                    <span class="font-bold text-gray-800" id="confirmReturnEmployee">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="font-bold text-purple-700" id="confirmReturnAmount">-</span>
                </div>
            </div>

            <div class="mb-6 text-left">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Return Reason *</label>
                <textarea id="returnNotes" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all resize-none" placeholder="Explain what needs to be corrected..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button onclick="updateStatus('Rejected')" class="flex-1 bg-amber-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-amber-700 transition-all shadow-md">
                    Confirm Return
                </button>
                <button onclick="closeModal('returnConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal for Quick Approve -->
    <div id="quickApproveConfirmModal" class="modal">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
            <button onclick="closeModal('quickApproveConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Quick Approval</h2>
                <p class="text-gray-500 text-sm mt-1">Are you sure you want to approve this request immediately?</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Report ID:</span>
                    <span class="font-bold text-gray-800" id="confirmQuickRefId">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Employee:</span>
                    <span class="font-bold text-gray-800" id="confirmQuickEmployee">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="font-bold text-purple-700" id="confirmQuickAmount">-</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="quickApproveConfirmed()" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-md text-sm">
                    Confirm Quick Approve
                </button>
                <button onclick="closeModal('quickApproveConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Approve Modal -->
    <div id="bulkApproveModal" class="modal">
        <div class="modal-content !max-w-[900px] !p-0 overflow-hidden relative">
            <button onclick="closeModal('bulkApproveModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-10">
                <i class="fas fa-times text-2xl"></i>
            </button>
            
            <div class="p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-check-double text-green-600 mr-3"></i>
                        Bulk Approve Reimbursements
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Select the requests you want to approve as a batch.</p>
                </div>

                <div class="border rounded-xl overflow-hidden mb-6">
                    <div class="max-h-[400px] overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs sticky top-0">
                                <tr>
                                    <th class="px-6 py-3">
                                        <input type="checkbox" id="selectAllBulk" onchange="toggleAllBulk(this)" class="rounded text-green-600 focus:ring-green-500">
                                    </th>
                                    <th class="px-4 py-3">Report ID</th>
                                    <th class="px-4 py-3">Employee</th>
                                    <th class="px-4 py-3">Category</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="bulkApproveList" class="divide-y divide-gray-200">
                                <!-- Loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl border border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-gray-700">
                        <div class="text-sm font-medium">Total Amount Selected:</div>
                        <div class="text-2xl font-bold text-green-600" id="bulkTotalAmount">₱0.00</div>
                        <div class="text-xs text-gray-500 mt-1"><span id="bulkSelectedCount">0</span> items selected</div>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button onclick="submitBulkApproval()" id="bulkApproveSubmitBtn" disabled class="flex-1 sm:flex-none px-8 py-3 bg-green-600 text-white font-bold rounded-xl hover:bg-green-700 shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            Approve Selected
                        </button>
                        <button onclick="closeModal('bulkApproveModal')" class="flex-1 sm:flex-none px-6 py-3 bg-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-300 transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bulk Approve Confirm Modal -->
    <div id="bulkApproveConfirmModal" class="modal">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
            <button onclick="closeModal('bulkApproveConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-double text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Confirm Bulk Approval</h2>
                <p class="text-gray-500 text-sm mt-1">Review the batch before confirming.</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Selected Items:</span>
                    <span class="font-bold text-gray-800" id="confirmBulkCount">0</span>
                </div>
                <div class="flex justify-between border-t border-gray-100 pt-2">
                    <span class="text-gray-500 font-medium">Total Amount:</span>
                    <span class="font-bold text-green-600 text-lg" id="confirmBulkTotal">₱0.00</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="bulkApproveConfirmed()" id="bulkConfirmSubmitBtn" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-md text-sm">
                    Confirm Batch Approval
                </button>
                <button onclick="closeModal('bulkApproveConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</body>
</html>

<script>
// ========== GLOBAL VARIABLES ==========
let currentReportId = '';
let currentReportData = null;
let filesToUpload = [];
let searchTimeout = null;

// ========== UTILITY FUNCTIONS ==========
function formatMoney(amount) {
    return parseFloat(amount || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div class="toast ${type}" id="${toastId}">
            <div class="flex items-center">
                <i class="toast-icon ${type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close" onclick="removeToast('${toastId}')">&times;</button>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('afterbegin', toastHtml);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        removeToast(toastId);
    }, 5000);
}

function removeToast(toastId) {
    const toast = document.getElementById(toastId);
    if (toast) {
        toast.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ========== MODAL FUNCTIONS ==========
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        if (modalId === 'newModal') {
            document.getElementById('reimbursementForm').reset();
            filesToUpload = [];
            updateFileList();
        }
        if (modalId === 'viewModal') {
            closePdfViewer();
        }
        document.body.style.overflow = 'auto';
    }
}

function openNewModal() {
    openModal('newModal');
}

// PDF Viewer Functions
let currentPdfPath = ''; // Store current PDF path for new tab and download

function openPdfViewer(filePath, fileName) {
    currentPdfPath = filePath; // Store for later use
    document.getElementById('receiptsList').style.display = 'none';
    document.getElementById('pdfViewerContainer').style.display = 'block';
    document.getElementById('pdfFrame').src = filePath;
    document.getElementById('currentFileName').textContent = fileName;
}

function closePdfViewer() {
    currentPdfPath = '';
    document.getElementById('pdfViewerContainer').style.display = 'none';
    document.getElementById('receiptsList').style.display = 'grid';
    document.getElementById('pdfFrame').src = '';
}

function openPdfInNewTab() {
    if (currentPdfPath) {
        window.open(currentPdfPath, '_blank');
    }
}

function downloadCurrentPdf() {
    if (currentPdfPath) {
        const link = document.createElement('a');
        link.href = currentPdfPath;
        link.download = document.getElementById('currentFileName').textContent || 'document.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// ========== FILTER FUNCTIONS ==========
function toggleFilterMenu() {
    const menu = document.getElementById('filterMenu');
    menu.classList.toggle('hidden');
}

function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    menu.classList.toggle('hidden');
}

function applyFilters() {
    const department = document.getElementById('filterDepartment').value;
    const type = document.getElementById('filterType').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    
    const url = new URL(window.location);
    
    if (department === 'All') {
        url.searchParams.delete('department_filter');
    } else {
        url.searchParams.set('department_filter', department);
    }
    
    if (type === 'All') {
        url.searchParams.delete('type_filter');
    } else {
        url.searchParams.set('type_filter', type);
    }
    
    if (dateFrom) {
        url.searchParams.set('date_from', dateFrom);
    } else {
        url.searchParams.delete('date_from');
    }
    
    if (dateTo) {
        url.searchParams.set('date_to', dateTo);
    } else {
        url.searchParams.delete('date_to');
    }
    
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('department_filter');
    url.searchParams.delete('type_filter');
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// ========== SEARCH FUNCTION ==========
function handleSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const searchInput = document.getElementById('searchInput');
        const searchValue = searchInput.value.trim();
        
        const url = new URL(window.location);
        if (searchValue) {
            url.searchParams.set('search', searchValue);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }, 500); // 500ms delay
}

// ========== EXPORT FUNCTIONS ==========
function exportData(format) {
    if (format === 'pdf') {
        exportToPDF();
        return;
    }
    
    let url = new URL(window.location.href);
    url.searchParams.set('export', '1');
    url.searchParams.set('format', format);
    
    window.open(url.toString(), '_blank');
    showToast('Export started...', 'info');
    
    // Close export menu
    document.getElementById('exportMenu').classList.add('hidden');
}

async function exportToPDF() {
    const exportBtn = document.querySelector('[onclick="exportData(\'pdf\')"]');
    const originalContent = exportBtn.innerHTML;
    
    
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>';
    exportBtn.disabled = true;
    
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('export', '1');
        url.searchParams.set('format', 'json');
        
        // Include current filters if any
        const department = document.getElementById('filterDepartment')?.value;
        const type = document.getElementById('filterType')?.value;
        const dateFrom = document.getElementById('filterDateFrom')?.value;
        const dateTo = document.getElementById('filterDateTo')?.value;
        
        if (department && department !== 'All') url.searchParams.set('department_filter', department);
        if (type && type !== 'All') url.searchParams.set('type_filter', type);
        if (dateFrom) url.searchParams.set('date_from', dateFrom);
        if (dateTo) url.searchParams.set('date_to', dateTo);
        
        const response = await fetch(url.toString());
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4');
            
            const headers = [["ID", "Employee", "Dept", "Expense Type", "Amount", "Status", "Date", "Description"]];
            const data = result.data.map(item => [
                item['Report ID'],
                item['Employee Name'],
                item['Department'],
                item['Type'],
                item['Amount'],
                item['Status'],
                item['Submitted Date'].split(' ')[0],
                item['Description']
            ]);
            
            // Header
            doc.setFontSize(22);
            doc.setTextColor(79, 70, 229); // Purple color
            doc.text("REIMBURSEMENT REPORT", 40, 50);
            
            doc.setFontSize(10);
            doc.setTextColor(100, 116, 139);
            doc.text("Generated on: " + new Date().toLocaleString(), 40, 70);
            
            doc.autoTable({
                head: headers,
                body: data,
                startY: 90,
                theme: 'striped',
                headStyles: { fillColor: [79, 70, 229], textColor: [255, 255, 255], fontStyle: 'bold' },
                footStyles: { fillColor: [248, 250, 252] },
                alternateRowStyles: { fillColor: [249, 250, 251] },
                margin: { top: 90, bottom: 40, left: 40, right: 40 },
                styles: { fontSize: 8, cellPadding: 8 },
                columnStyles: {
                    0: { cellWidth: 60 },
                    4: { halign: 'right', fontStyle: 'bold' },
                    5: { halign: 'center' }
                }
            });
            
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
            doc.save(`reimbursement_report_${timestamp}.pdf`);
            showToast('PDF Exported Successfully', 'success');
        } else {
            showToast('No data found to export', 'error');
        }
    } catch (error) {
        console.error('PDF Export Error:', error);
        showToast('Failed to generate PDF', 'error');
    } finally {
        exportBtn.innerHTML = originalContent;
        exportBtn.disabled = false;
        document.getElementById('exportMenu').classList.add('hidden');
    }
}

// ========== REIMBURSEMENT DETAILS ==========
async function viewReimbursement(reportId) {
    currentReportId = reportId;
    
    // Show loading state
    document.getElementById('modalReportId').textContent = reportId;
    document.getElementById('modalEmployeeName').textContent = 'Loading...';
    document.getElementById('modalAmount').textContent = '₱0.00';
    
    openModal('viewModal');
    
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_reimbursement_details');
        url.searchParams.set('report_id', reportId);
        
        const response = await fetch(url.toString());
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        
        if (data.success) {
            currentReportData = data.data;
            displayReimbursementDetails(data.data);
            
            // NEW: Fetch budget allocation like vendor.php
            fetchBudgetAllocation(data.data.department, data.data.amount);
        } else {
            showToast('Failed to load details', 'error');
            closeModal('viewModal');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
        closeModal('viewModal');
    }
}

function displayReimbursementDetails(data) {
    // Basic info
    document.getElementById('modalEmployeeName').textContent = data.employee_name || '-';
    document.getElementById('modalEmployeeId').textContent = data.employee_id || '-';
    document.getElementById('modalDepartment').textContent = data.department || '-';
    document.getElementById('modalSubmittedDate').textContent = data.submitted_date ? data.submitted_date.split(' ')[0] : '-';
    document.getElementById('modalAmount').textContent = '₱' + formatMoney(data.amount || 0);
    document.getElementById('modalType').textContent = (data.reimbursement_type || '-');
    document.getElementById('modalDescription').textContent = data.description || 'No description provided.';
    
    // Status
    const statusBadge = document.getElementById('modalStatus');
    const status = data.status || 'Pending';
    statusBadge.textContent = status;
    
    // Set status color
    let statusClass = '';
    switch(status) {
        case 'Pending': statusClass = 'bg-yellow-100 text-yellow-800'; break;
        case 'Approved': statusClass = 'bg-green-100 text-green-800'; break;
        case 'Rejected': statusClass = 'bg-red-100 text-red-800'; break;
        default: statusClass = 'bg-gray-100 text-gray-800';
    }
    statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ' + statusClass;
    
    // Show/hide action buttons based on status
    const actionButtons = document.getElementById('actionButtons');
    if (status === 'Rejected') {
        actionButtons.style.display = 'none';
        
        // Show returned notes
        const returnedNotesSection = document.getElementById('returnedNotesSection');
        returnedNotesSection.style.display = 'block';
        
        // Get return notes from timeline or approver_notes
        let returnNotes = data.approver_notes || '';
        if (!returnNotes && data.timeline) {
            const rejectedTimeline = data.timeline.find(item => item.action === 'rejected');
            if (rejectedTimeline) {
                returnNotes = rejectedTimeline.notes || '';
            }
        }
        document.getElementById('modalReturnNotes').textContent = returnNotes || 'No return notes provided.';
    } else {
        actionButtons.style.display = 'flex';
        document.getElementById('returnedNotesSection').style.display = 'none';
    }
    
    // Receipts
    displayReceipts(data.receipts || []);
}

function fetchBudgetAllocation(department, invoiceAmount) {
    const budgetContent = document.getElementById('budgetAllocationContent');
    
    // Show loading state
    budgetContent.innerHTML = `
        <div class="flex items-center justify-center py-4">
            <i class="fas fa-spinner fa-spin text-blue-500 mr-2"></i>
            <span class="text-gray-600">Loading budget information...</span>
        </div>
    `;
    
    const formData = new FormData();
    formData.append('action', 'get_budget');
    formData.append('department', department);
    
    fetch('reimbursement.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const budget = data.data;
            const allocatedAmount = parseFloat(budget.allocated_amount);
            const spent = parseFloat(budget.spent);
            const remaining = allocatedAmount - spent;
            const invoiceAmt = parseFloat(invoiceAmount);
            
            const percentUsed = (spent / allocatedAmount * 100).toFixed(1);
            const newSpent = spent + invoiceAmt;
            const newRemaining = remaining - invoiceAmt;
            const newPercentUsed = (newSpent / allocatedAmount * 100).toFixed(1);
            
            let statusClass = 'text-green-600';
            let statusIcon = 'fa-check-circle';
            let statusText = 'Sufficient Budget';
            
            if (newRemaining < 0) {
                statusClass = 'text-red-600';
                statusIcon = 'fa-exclamation-triangle';
                statusText = 'Insufficient Budget!';
            } else if (newPercentUsed > 90) {
                statusClass = 'text-orange-600';
                statusIcon = 'fa-exclamation-circle';
                statusText = 'Budget Almost Depleted';
            }
            
            budgetContent.innerHTML = `
                <div class="flex items-center mb-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-white border border-gray-200 ${statusClass}">
                        <i class="fas ${statusIcon} mr-1"></i> ${statusText}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-y-2 text-xs">
                    <div class="text-gray-500">Allocated Budget:</div>
                    <div class="text-right font-bold text-gray-900">₱${allocatedAmount.toLocaleString()}</div>
                    <div class="text-gray-500">Current Spent:</div>
                    <div class="text-right font-bold text-gray-900">₱${spent.toLocaleString()} (${percentUsed}%)</div>
                    <div class="text-gray-500 border-t pt-1">After Approval:</div>
                    <div class="text-right font-bold ${statusClass} border-t pt-1">
                        Spent: ₱${newSpent.toLocaleString()} (${newPercentUsed}%)<br>
                        Rem: ₱${newRemaining.toLocaleString()}
                    </div>
                </div>
            `;
        } else {
            budgetContent.innerHTML = `
                <div class="p-3 bg-red-50 text-red-700 text-xs rounded border border-red-100 italic">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    ${data.message || 'No active budget found for this department'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error fetching budget:', error);
        budgetContent.innerHTML = '<div class="text-red-500 text-xs">Error loading budget data</div>';
    });
}

function displayReceipts(receipts) {
    const listContainer = document.getElementById('receiptsList');
    const trayContainer = document.getElementById('receiptsTray');
    const countLabel = document.getElementById('receiptsCount');
    
    if (countLabel) countLabel.textContent = receipts.length + ' file' + (receipts.length !== 1 ? 's' : '') + ' attached';

    // Auto-show the first PDF or Image in the iframe
    const viewableFile = receipts.find(r => ['pdf', 'jpg', 'jpeg', 'png'].includes(r.file_type.toLowerCase()));
    
    if (viewableFile) {
        openPdfViewer(viewableFile.file_path);
    } else {
        closePdfViewer();
    }

    // Thumbnails for switching between files
    if (receipts.length > 0) {
        trayContainer.classList.remove('hidden');
        let html = '';
        receipts.forEach((receipt, index) => {
            const isViewable = ['pdf', 'jpg', 'jpeg', 'png'].includes(receipt.file_type.toLowerCase());
            const fileExt = receipt.file_type.toLowerCase();
            const iconClass = fileExt === 'pdf' ? 'fa-file-pdf text-red-400' : 
                             (['jpg', 'jpeg', 'png'].includes(fileExt) ? 'fa-file-image text-purple-400' : 'fa-file text-gray-400');
            
            // Truncate filename
            const displayName = receipt.file_name.length > 15 ? receipt.file_name.substring(0, 12) + '...' : receipt.file_name;
            
            html += `
                <div class="receipt-tab-item px-2 py-1 border border-gray-700 rounded bg-gray-800/50 cursor-pointer hover:bg-gray-700 hover:border-gray-500 transition-all flex items-center gap-2 group max-w-[140px]" 
                     data-path="${receipt.file_path}"
                     onclick="${isViewable ? `openPdfViewer('${receipt.file_path}', this)` : `window.open('view_pdf.php?file=' + encodeURIComponent('${receipt.file_path.split('/').pop()}'), '_blank')`}"
                     title="${receipt.file_name}">
                    <i class="fas ${iconClass} text-xs group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] text-gray-400 group-hover:text-gray-200 truncate font-medium select-none">${displayName}</span>
                </div>
            `;
        });
        listContainer.innerHTML = html;
        
        // Highlight the first one if it's the one we opened
        if (viewableFile) {
            const firstTab = listContainer.querySelector(`[data-path="${viewableFile.file_path}"]`);
            if (firstTab) firstTab.classList.add('active-receipt-tab');
        }
    } else {
        trayContainer.classList.add('hidden');
    }
}

function openPdfViewer(filePath, element = null) {
    const frame = document.getElementById('pdfFrame');
    const placeholder = document.getElementById('pdfViewerPlaceholder');
    
    // Highlight active tab
    if (element) {
        document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
        element.classList.add('active-receipt-tab');
    }

    // Extract filename for view_pdf.php
    const fileName = filePath.split('/').pop();
    frame.src = 'view_pdf.php?file=' + encodeURIComponent(fileName); 
    
    frame.classList.remove('hidden');
    placeholder.classList.add('hidden');
}

function closePdfViewer() {
    document.getElementById('pdfFrame').classList.add('hidden');
    document.getElementById('pdfViewerPlaceholder').classList.remove('hidden');
    document.getElementById('pdfFrame').src = '';
}

// ========== TAB HANDLING ==========
function handleTabClick(status) {
    // Update active tab
    document.querySelectorAll('#statusTabs button').forEach(btn => {
        btn.classList.remove('active-tab');
        if (btn.getAttribute('data-status') === status) {
            btn.classList.add('active-tab');
        }
    });
    
    // Update URL without reloading the whole page
    const url = new URL(window.location);
    url.searchParams.set('status_filter', status);
    url.searchParams.delete('page');
    
    // Update browser URL without reload
    window.history.pushState({}, '', url.toString());
    
    // Reload the table only
    loadTableData();
}

// ========== TABLE FILTERING ==========
function filterTableRows() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('#tableBody tr');
    
    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchValue)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ========== TABLE DATA LOADING ==========
async function loadTableData() {
    const url = new URL(window.location);
    url.searchParams.set('ajax', '1');
    
    try {
        const response = await fetch(url.toString());
        const html = await response.text();
        
        // Parse the HTML to extract table data
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newTableBody = doc.querySelector('#tableBody');
        const newPagination = doc.querySelector('.flex.flex-col.sm\\:flex-row.justify-between.items-center.gap-4.mt-6.pt-4.border-t.border-gray-200');
        
        // Update table body
        if (newTableBody) {
            document.getElementById('tableBody').innerHTML = newTableBody.innerHTML;
        }
        
        // Update pagination
        if (newPagination) {
            document.querySelector('.flex.flex-col.sm\\:flex-row.justify-between.items-center.gap-4.mt-6.pt-4.border-t.border-gray-200').innerHTML = newPagination.innerHTML;
        }
        
        // Update page info
        const pageInfo = doc.querySelector('#pageInfo');
        if (pageInfo) {
            document.getElementById('pageInfo').innerHTML = pageInfo.innerHTML;
        }
        
        // Reattach event listeners
        attachRowEventListeners();
        
    } catch (error) {
        console.error('Error loading table data:', error);
        showToast('Error loading data', 'error');
    }
}

function attachRowEventListeners() {
    // Attach click event to review buttons
    document.querySelectorAll('#tableBody button[onclick^="viewReimbursement"]').forEach(btn => {
        const onclick = btn.getAttribute('onclick');
        const reportId = onclick.match(/'([^']+)'/)[1];
        btn.onclick = () => viewReimbursement(reportId);
    });
}

// ========== APPROVAL FUNCTIONS ==========
function quickAction(reportId, action) {
    if (action === 'Approved') {
        document.getElementById('confirmQuickRefId').textContent = reportId;
        // You might want to fetch additional data here
        openModal('quickApproveConfirmModal');
        currentReportId = reportId;
    }
}

function openApproveConfirmation() {
    if (!currentReportData) return;
    
    document.getElementById('confirmApproveRefId').textContent = currentReportData.report_id;
    document.getElementById('confirmApproveEmployee').textContent = currentReportData.employee_name;
    document.getElementById('confirmApproveAmount').textContent = '₱' + formatMoney(currentReportData.amount);
    
    openModal('approveConfirmModal');
}

function openReturnConfirmation() {
    if (!currentReportData) return;
    
    document.getElementById('confirmReturnRefId').textContent = currentReportData.report_id;
    document.getElementById('confirmReturnEmployee').textContent = currentReportData.employee_name;
    document.getElementById('confirmReturnAmount').textContent = '₱' + formatMoney(currentReportData.amount);
    
    openModal('returnConfirmModal');
}

async function updateStatus(status) {
    let notes = '';
    
    if (status === 'Rejected') {
        notes = document.getElementById('returnNotes').value.trim();
        if (!notes) {
            showToast('Please provide return notes', 'error');
            return;
        }
    } else {
        // For approve, you might want to get notes from a textarea if you add one
        notes = 'Approved';
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('report_id', currentReportId);
        formData.append('status', status);
        formData.append('approver_notes', notes);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            
            // Close all modals
            closeModal('viewModal');
            closeModal('approveConfirmModal');
            closeModal('returnConfirmModal');
            closeModal('quickApproveConfirmModal');
            
            // Reload table data
            loadTableData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    }
}

async function quickApproveConfirmed() {
    try {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('report_id', currentReportId);
        formData.append('status', 'Approved');
        formData.append('approver_notes', 'Approved via quick action');
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('quickApproveConfirmModal');
            
            // Reload table data
            loadTableData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    }
}

// ========== BULK APPROVE FUNCTIONS ==========
async function openBulkApproveModal() {
    const bulkList = document.getElementById('bulkApproveList');
    bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><i class="fas fa-spinner fa-spin text-green-500 text-2xl mb-2"></i><p class="text-xs text-gray-500">Fetching pending reimbursements...</p></td></tr>';
    
    document.getElementById('bulkTotalAmount').textContent = '₱0.00';
    document.getElementById('bulkSelectedCount').textContent = '0';
    document.getElementById('selectAllBulk').checked = false;
    document.getElementById('bulkApproveSubmitBtn').disabled = true;
    
    openModal('bulkApproveModal');
    
    try {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_pending_bulk');
        
        const response = await fetch(url.toString());
        const data = await response.json();
        
        if (data.success) {
            if (data.data.length === 0) {
                bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium italic">No pending reimbursements found.</td></tr>';
                return;
            }
            
            let html = '';
            data.data.forEach(item => {
                html += `
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleBulkItem('${item.report_id}')">
                        <td class="px-6 py-4" onclick="event.stopPropagation()">
                            <input type="checkbox" name="bulk_ids[]" value="${item.report_id}" data-amount="${item.amount}" onchange="updateBulkTotal()" class="rounded text-green-600 focus:ring-green-500">
                        </td>
                        <td class="px-4 py-4 font-medium text-gray-900">${item.report_id}</td>
                        <td class="px-4 py-4 text-gray-700">${item.employee_name}</td>
                        <td class="px-4 py-4 text-gray-600 truncate max-w-[150px]">${item.reimbursement_type}</td>
                        <td class="px-4 py-4 text-right font-bold text-gray-900">₱${formatMoney(item.amount)}</td>
                    </tr>
                `;
            });
            bulkList.innerHTML = html;
        } else {
            showToast('Failed to load pending reimbursements', 'error');
            closeModal('bulkApproveModal');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
        closeModal('bulkApproveModal');
    }
}

function toggleBulkItem(reportId) {
    const checkbox = document.querySelector(`input[value="${reportId}"]`);
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        updateBulkTotal();
    }
}

function toggleAllBulk(master) {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBulkTotal();
}

function updateBulkTotal() {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]');
    let total = 0;
    let count = 0;
    
    checkboxes.forEach(cb => {
        if (cb.checked) {
            total += parseFloat(cb.getAttribute('data-amount') || 0);
            count++;
        }
    });
    
    document.getElementById('bulkTotalAmount').textContent = '₱' + formatMoney(total);
    document.getElementById('bulkSelectedCount').textContent = count;
    document.getElementById('bulkApproveSubmitBtn').disabled = count === 0;
    
    // Update select all checkbox state
    const selectAll = document.getElementById('selectAllBulk');
    if (checkboxes.length > 0) {
        selectAll.checked = count === checkboxes.length;
        selectAll.indeterminate = count > 0 && count < checkboxes.length;
    }
}

async function submitBulkApproval() {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
    const reportIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (reportIds.length === 0) return;
    
    const totalAmount = document.getElementById('bulkTotalAmount').textContent;
    const count = reportIds.length;
    
    document.getElementById('confirmBulkCount').textContent = count;
    document.getElementById('confirmBulkTotal').textContent = totalAmount;
    
    openModal('bulkApproveConfirmModal');
}

async function bulkApproveConfirmed() {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
    const reportIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (reportIds.length === 0) return;
    
    const submitBtn = document.getElementById('bulkConfirmSubmitBtn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'bulk_approve');
        reportIds.forEach(id => formData.append('report_ids[]', id));
        
        const response = await fetch('reimbursement.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('bulkApproveConfirmModal');
            closeModal('bulkApproveModal');
            loadTableData(); // Reload the main dashboard table
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// ========== NEW REIMBURSEMENT ==========
// ========== NEW REIMBURSEMENT FILE HANDLING ==========
function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    addFilesToList(files);
    event.target.value = '';
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    document.getElementById('dragDropArea').style.borderColor = '#8b5cf6';
    document.getElementById('dragDropArea').style.backgroundColor = '#faf5ff';
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    document.getElementById('dragDropArea').style.borderColor = '#d1d5db';
    document.getElementById('dragDropArea').style.backgroundColor = '';
}

function handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    document.getElementById('dragDropArea').style.borderColor = '#d1d5db';
    document.getElementById('dragDropArea').style.backgroundColor = '';
    
    const files = Array.from(event.dataTransfer.files);
    addFilesToList(files);
}

function addFilesToList(files) {
    const MAX_SIZE = 5 * 1024 * 1024; // 5MB
    const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
    
    files.forEach(file => {
        if (file.size > MAX_SIZE) {
            showToast(`File ${file.name} exceeds 5MB limit.`, 'error');
            return;
        }
        
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
            showToast(`File ${file.name} is invalid. Only PDF, JPG, PNG allowed.`, 'error');
            return;
        }
        
        filesToUpload.push(file);
    });
    
    updateFileList();
    updateUploadUI();
}

function updateUploadUI() {
    const dragDropArea = document.getElementById('dragDropArea');
    const addFileContainer = document.getElementById('addFileContainer');
    
    if (filesToUpload.length > 0) {
        dragDropArea.classList.add('hidden');
        addFileContainer.classList.remove('hidden');
    } else {
        dragDropArea.classList.remove('hidden');
        addFileContainer.classList.add('hidden');
    }
}

function updateFileList() {
    const fileList = document.getElementById('fileList');
    
    if (filesToUpload.length === 0) {
        fileList.innerHTML = '';
        return;
    }
    
    let html = '';
    filesToUpload.forEach((file, index) => {
        const isPdf = file.name.toLowerCase().endsWith('.pdf');
        const iconClass = isPdf ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500';
        
        html += `
            <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm hover:border-purple-300 transition-all group">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas ${iconClass} text-xl"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-sm font-bold text-gray-900 truncate">${file.name}</div>
                        <div class="text-xs text-gray-500">${formatFileSize(file.size)}</div>
                    </div>
                </div>
                <button type="button" onclick="removeFile(${index})" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });
    
    fileList.innerHTML = html;
}

function removeFile(index) {
    filesToUpload.splice(index, 1);
    updateFileList();
    updateUploadUI();
}

async function submitReimbursement(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'submit_reimbursement');
        
        // Remove the default file input field's file if any (we use filesToUpload)
        formData.delete('receipts[]');
        filesToUpload.forEach(file => {
            formData.append('receipts[]', file);
        });
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('newModal');
            
            // Cleanup
            filesToUpload = [];
            form.reset();
            updateFileList();
            updateUploadUI();
            
            loadTableData();
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// ========== PAGINATION ==========
function changePage(page) {
    const url = new URL(window.location);
    url.searchParams.set('page', page);
    
    // Update browser URL without reload
    window.history.pushState({}, '', url.toString());
    
    // Load table data for the new page
    loadTableData();
}

// ========== EVENT LISTENERS ==========
document.addEventListener('DOMContentLoaded', function() {
    // Auto remove the PHP-generated toast after 5 seconds
    const autoToast = document.getElementById('autoToast');
    if (autoToast) {
        setTimeout(() => {
            autoToast.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                autoToast.remove();
            }, 300);
        }, 5000);
    }
    
    // Search input with real-time filtering
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                handleSearchInput();
            }, 500);
        });
    }
    
    // Tab click handlers
    document.querySelectorAll('#statusTabs button').forEach(btn => {
        btn.addEventListener('click', function() {
            const status = this.getAttribute('data-status');
            handleTabClick(status);
        });
    });
    
    // Filter button
    const filterButton = document.getElementById('filterButton');
    if (filterButton) {
        filterButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('filterMenu');
            menu.classList.toggle('hidden');
            document.getElementById('exportMenu').classList.add('hidden');
        });
    }
    
    // Export button
    const exportButton = document.getElementById('exportButton');
    if (exportButton) {
        exportButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('hidden');
            document.getElementById('filterMenu').classList.add('hidden');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            document.getElementById('filterMenu').classList.add('hidden');
            document.getElementById('exportMenu').classList.add('hidden');
        }
    });
    
    // Close modals
    const closeButtons = [
        document.getElementById('cancelApprove'),
        document.getElementById('cancelReturn'),
        document.getElementById('cancelQuick')
    ];
    
    closeButtons.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) modal.style.display = 'none';
            });
        }
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const modals = ['viewModal', 'newModal', 'approveConfirmModal', 'returnConfirmModal', 'quickApproveConfirmModal', 'bulkApproveModal', 'bulkApproveConfirmModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Browser back/forward navigation
    window.addEventListener('popstate', function() {
        loadTableData();
    });
});

// ========== CASCADING DROPDOWN FOR EXPENSE CATEGORIES ==========
const expenseSubcategories = <?php echo json_encode($expense_subcategories); ?>;

function updateSubcategories() {
    const categorySelect = document.getElementById('expenseCategory');
    const subcategorySelect = document.getElementById('expenseSubcategory');
    const selectedCategory = categorySelect.value;
    
    // Clear existing options
    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
    
    if (selectedCategory && expenseSubcategories[selectedCategory]) {
        expenseSubcategories[selectedCategory].forEach(subcategory => {
            const option = document.createElement('option');
            option.value = subcategory;
            option.textContent = subcategory;
            subcategorySelect.appendChild(option);
        });
    } else {
        subcategorySelect.innerHTML = '<option value="">Select Category First</option>';
    }
}

// Close view modal with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = ['viewModal', 'newModal', 'approveConfirmModal', 'returnConfirmModal', 'quickApproveConfirmModal', 'bulkApproveModal', 'bulkApproveConfirmModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});
</script>
<?php $conn->close(); ?>