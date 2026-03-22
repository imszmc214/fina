<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
include('connection.php');
require_once 'includes/accounting_functions.php';

// Handle tab switching via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_tab']) && !isset($_POST['action'])) {
    $_SESSION['driver_payable_tab'] = $_POST['current_tab'];
    exit();
}

// Force default to 'all' if it's a fresh visit
$isTabRequest = isset($_GET['tab']) || isset($_POST['current_tab']);
if (!$isTabRequest && (!isset($_SESSION['driver_payable_tab']) || (isset($_SERVER['HTTP_REFERER']) && !strpos($_SERVER['HTTP_REFERER'], 'driver_payable.php')))) {
    $_SESSION['driver_payable_tab'] = 'all';
} elseif (isset($_GET['tab'])) {
    $_SESSION['driver_payable_tab'] = $_GET['tab'];
}

$currentTab = $_SESSION['driver_payable_tab'] ?? 'all';

// =================================================================
// AJAX REQUEST HANDLERS
// =================================================================

// AJAX: Get payout details
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_payout_details') {
    header('Content-Type: application/json');
    ob_start();
    $payout_id = isset($_GET['payout_id']) ? $conn->real_escape_string($_GET['payout_id']) : '';
    $response = ['success' => false, 'data' => []];
    
    if ($payout_id) {
        $sql = "SELECT * FROM driver_payouts WHERE payout_id = '$payout_id'";
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $response['success'] = true;
            $response['data'] = $row;
        }
    }
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Get budget allocation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_budget') {
    header('Content-Type: application/json');
    ob_start();
    $response = ['success' => false, 'message' => '', 'data' => null];
    $department = $_POST['department'] ?? 'Logistic-1';
    try {
        $budget_sql = "SELECT * FROM budget_allocations WHERE department = ? AND status = 'active' ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($budget_sql);
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $budget = $result->fetch_assoc();
            $response['success'] = true;
            $response['data'] = $budget;
        } else {
            $response['message'] = "No active budget allocation found for department: $department";
        }
    } catch (Exception $e) { $response['message'] = "Error: " . $e->getMessage(); }
    ob_end_clean();
    echo json_encode($response);
    exit();
}


// AJAX: Approve payout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    // Set JSON header and start output buffering to prevent any HTML output
    header('Content-Type: application/json');
    ob_start();
    
    $response = ['success' => false, 'message' => ''];
    
    $payout_id = $_POST['payout_id'];
    $approver_notes = $conn->real_escape_string($_POST['approver_notes'] ?? '');
    
    $conn->begin_transaction();
    try {
        // Get payout details first
        $get_sql = "SELECT * FROM driver_payouts WHERE payout_id = ?";
        $get_stmt = $conn->prepare($get_sql);
        $get_stmt->bind_param("s", $payout_id);
        $get_stmt->execute();
        $payout = $get_stmt->get_result()->fetch_assoc();
        $get_stmt->close();
        
        if (!$payout) {
            throw new Exception("Payout not found");
        }
        
        // Update status to approved
        $approver_name = ($_SESSION['givenname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '');
        $approver_name = trim($approver_name) ?: 'Authorized Approver';
        
        // Update status to Approved - ONLY IF PENDING
        $update_sql = "UPDATE driver_payouts SET status = 'Approved', approved_date = NOW(), approver_notes = ?, approved_by = ? WHERE payout_id = ? AND status = 'Pending'";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sss", $approver_notes, $approver_name, $payout_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error approving payout: " . $conn->error);
        }

        // If no rows affected, it might already be approved
        if ($stmt->affected_rows === 0) {
            $stmt->close();
            // Check if it already exists in PA table
            $reference_id = "DRV-" . $payout_id;
            $check_pa = $conn->prepare("SELECT id FROM pa WHERE reference_id = ?");
            $check_pa->bind_param("s", $reference_id);
            $check_pa->execute();
            if ($check_pa->get_result()->num_rows > 0) {
                // Already approved and in PA, count as success to avoid error message but don't re-insert
                $check_pa->close();
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Payout approved (already exists).";
                ob_end_clean();
                echo json_encode($response);
                exit();
            }
            $check_pa->close();
            
            // Check current status
            if ($payout['status'] === 'Approved') {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Payout was already approved.";
                ob_end_clean();
                echo json_encode($response);
                exit();
            }
            throw new Exception("Payout is not in pending status or not found.");
        }
        $stmt->close();

        // Create payout record in 'pa' table for the Payout module
        $reference_id = "DRV-" . $payout_id;
        $account_name = $payout['driver_name'];
        $department = $payout['department'] ?? 'Logistic';
        $mode_of_payment = 'Bank'; // Default
        $expense_category = 'Driver Payout';
        $amount = $payout['amount'];
        $description = "Driver Payout for " . $payout['driver_name'] . " (ID: " . $payout['driver_id'] . ")";
        $payment_due = date('Y-m-d');
        
        // Safe ID Workaround
        $next_pa_id = getNextAvailableId($conn, 'pa');
        
        $payout_sql = "INSERT INTO pa (
            id, reference_id, account_name, employee_id, wallet_id, requested_department, mode_of_payment, 
            expense_categories, amount, description, payment_due, requested_at, from_payable,
            transaction_type, payout_type, source_module, status,
            approved_by, submitted_date, approved_date, approval_source
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Driver Payout', 'Driver', 'Driver Payable', 'Pending Disbursement', ?, ?, NOW(), 'Driver Module')";
        
        $payout_stmt = $conn->prepare($payout_sql);
        if (!$payout_stmt) {
            throw new Exception("Payout prepare failed: " . $conn->error);
        }
        
        // Handle wallet_id - use NULL if empty to avoid N/A display
        $wallet_id_value = !empty($payout['wallet_id']) ? $payout['wallet_id'] : NULL;
        
        $payout_stmt->bind_param(
            "isssssssdsssss",
            $next_pa_id,
            $reference_id,
            $account_name,
            $payout['driver_id'],
            $wallet_id_value,
            $department,
            $mode_of_payment,
            $expense_category,
            $amount,
            $description,
            $payment_due,
            $payout['created_at'],
            $approver_name,
            $payout['created_at']
        );
        
        if (!$payout_stmt->execute()) {
            throw new Exception("Error creating payout record: " . $payout_stmt->error);
        }
        $payout_stmt->close();
        
        // Create journal entry and post to ledger
        try {
            $journal_number = createDriverJournalEntry($conn, $payout);
            error_log("Journal entry created: $journal_number for driver payout: " . $payout['payout_id']);
        } catch (Exception $e) {
            error_log("WARNING: Failed to create journal entry for driver payout " . $payout['payout_id'] . ": " . $e->getMessage());
        }
        
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Payout approved successfully!";
        
        // Trigger Webhook Update (wrapped in try-catch to prevent breaking JSON response)
        try {
            require_once 'api/webhook_helper.php';
            $p_dept = $payout['department'] ?? 'Logistic'; // Default to Logistic if column missing
            sendWebhookUpdate($conn, $p_dept, 'driver_payout', $payout_id, 'Approved');
        } catch (Exception $webhook_error) {
            // Log webhook error but don't fail the approval
            error_log("Webhook error for driver payout $payout_id: " . $webhook_error->getMessage());
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Transaction failed: " . $e->getMessage();
    }
    
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Reject payout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    header('Content-Type: application/json');
    ob_start();
    $response = ['success' => false, 'message' => ''];
    
    $payout_id = $_POST['payout_id'];
    $reason = $conn->real_escape_string($_POST['reason']);
    
    $update_sql = "UPDATE driver_payouts SET status = 'Rejected', rejected_reason = ? WHERE payout_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ss", $reason, $payout_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Payout rejected successfully!";
        
        // Trigger Webhook Update (wrapped in try-catch to prevent breaking JSON response)
        try {
            $get_dept = $conn->query("SELECT department FROM driver_payouts WHERE payout_id = '$payout_id'")->fetch_assoc();
            $p_dept = $get_dept['department'] ?? 'Logistic';
            require_once 'api/webhook_helper.php';
            sendWebhookUpdate($conn, $p_dept, 'driver_payout', $payout_id, 'Rejected', $reason);
        } catch (Exception $webhook_error) {
            error_log("Webhook error for driver payout $payout_id (reject): " . $webhook_error->getMessage());
        }
    } else {
        $response['message'] = "Error rejecting payout: " . $conn->error;
    }
    $stmt->close();
    
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Archive rejected payout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    header('Content-Type: application/json');
    ob_start();
    $response = ['success' => false, 'message' => ''];
    
    $payout_id = $_POST['payout_id'];
    
    $update_sql = "UPDATE driver_payouts SET status = 'Archived' WHERE payout_id = ? AND status = 'Rejected'";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("s", $payout_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Payout archived successfully!";
    } else {
        $response['message'] = "Error archiving payout: " . $conn->error;
    }
    $stmt->close();
    
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Get all pending payouts (for bulk approve modal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_bulk') {
    header('Content-Type: application/json');
    ob_start();
    $response = ['success' => false, 'data' => []];
    
    $sql = "SELECT payout_id, driver_name, driver_id, amount, created_at 
            FROM driver_payouts 
            WHERE status = 'Pending' 
            ORDER BY created_at DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
    
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Bulk Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_approve') {
    header('Content-Type: application/json');
    ob_start();
    $response = ['success' => false, 'message' => ''];
    $payout_ids = $_POST['payout_ids'] ?? [];
    $notes = $_POST['notes'] ?? 'Approved via bulk action';
    
    if (empty($payout_ids)) {
        $response['message'] = "No items selected for approval.";
    } else {
        $success_count = 0;
        $error_count = 0;
        $approver_name = ($_SESSION['givenname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '');
        $approver_name = trim($approver_name) ?: 'Authorized Approver';
        
        foreach ($payout_ids as $payout_id) {
            $payout_id = $conn->real_escape_string($payout_id);
            $update_sql = "UPDATE driver_payouts SET status = 'Approved', approved_date = NOW(), approver_notes = ?, approved_by = ? WHERE payout_id = ? AND status = 'Pending'";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sss", $notes, $approver_name, $payout_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $success_count++;
                
                // Get payout details for journal entry and Payout module
                $get_sql = "SELECT * FROM driver_payouts WHERE payout_id = ?";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("s", $payout_id);
                $get_stmt->execute();
                $payout_data = $get_stmt->get_result()->fetch_assoc();
                $get_stmt->close();
                
                // Create journal entry and Payout record
                if ($payout_data) {
                    // 1. Create payout record in 'pa' table
                    try {
                        $payout_ref_id = "DRV-" . $payout_id;
                        $payout_desc = "Driver Payout for " . $payout_data['driver_name'] . " (ID: " . $payout_data['driver_id'] . ")";
                        $payout_due = date('Y-m-d');
                        
                        $next_bulk_pa_id = getNextAvailableId($conn, 'pa');
                        
                        $payout_sql = "INSERT INTO pa (
                            id, reference_id, account_name, employee_id, wallet_id, requested_department, mode_of_payment, 
                            expense_categories, amount, description, payment_due, requested_at, from_payable,
                            transaction_type, payout_type, source_module, status,
                            approved_by, submitted_date, approved_date, approval_source
                        ) VALUES (?, ?, ?, ?, ?, ?, 'Bank', 'Driver Payout', ?, ?, ?, ?, 1, 'Driver Payout', 'Driver', 'Driver Payable', 'Pending Disbursement', ?, ?, NOW(), 'Driver Module')";
                        
                        // Handle wallet_id - use NULL if empty to avoid N/A display
                        $bulk_wallet_id = !empty($payout_data['wallet_id']) ? $payout_data['wallet_id'] : NULL;
                        
                        $p_stmt = $conn->prepare($payout_sql);
                        $p_stmt->bind_param("isssssdsssss", 
                            $next_bulk_pa_id,
                            $payout_ref_id, 
                            $payout_data['driver_name'], 
                            $payout_data['driver_id'],
                            $bulk_wallet_id,
                            $payout_data['department'], 
                            $payout_data['amount'], 
                            $payout_desc, 
                            $payout_due,
                            $payout_data['created_at'],
                            $approver_name,
                            $payout_data['created_at']
                        );
                        $p_stmt->execute();
                        $p_stmt->close();
                    } catch (Exception $e) {
                        error_log("Error creating payout record for driver $payout_id: " . $e->getMessage());
                    }

                    // 2. Create journal entry
                    try {
                        $journal_number = createDriverJournalEntry($conn, $payout_data);
                        error_log("Journal entry created: $journal_number for driver payout: " . $payout_id);
                    } catch (Exception $e) {
                        error_log("WARNING: Failed to create journal entry for driver payout $payout_id: " . $e->getMessage());
                    }
                    
                    // 3. Trigger Webhook Update (wrapped in try-catch)
                    try {
                        $p_dept = $payout_data['department'] ?? 'Logistic';
                        require_once 'api/webhook_helper.php';
                        sendWebhookUpdate($conn, $p_dept, 'driver_payout', $payout_id, 'Approved');
                    } catch (Exception $webhook_error) {
                        error_log("Webhook error for driver payout $payout_id (bulk): " . $webhook_error->getMessage());
                    }
                }
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        
        $response['success'] = true;
        $response['message'] = "Successfully approved $success_count item(s).";
        if ($error_count > 0) {
            $response['message'] .= " Failed to approve $error_count item(s).";
        }
    }
    
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// =================================================================
// PAGE LOAD DATA QUERIES
// =================================================================

// Calculate totals for widgets
$totalPending = $conn->query("SELECT COUNT(*) as count FROM driver_payouts WHERE status = 'Pending'")->fetch_assoc()['count'] ?? 0;
$totalApproved = $conn->query("SELECT COUNT(*) as count FROM driver_payouts WHERE status = 'Approved'")->fetch_assoc()['count'] ?? 0;
$totalPaid = $conn->query("SELECT COUNT(*) as count FROM driver_payouts WHERE status = 'Paid'")->fetch_assoc()['count'] ?? 0;
$totalRejected = $conn->query("SELECT COUNT(*) as count FROM driver_payouts WHERE status = 'Rejected'")->fetch_assoc()['count'] ?? 0;
$totalArchived = $conn->query("SELECT COUNT(*) as count FROM driver_payouts WHERE status = 'Archived'")->fetch_assoc()['count'] ?? 0;

$totalAmountPending = $conn->query("SELECT SUM(amount) as total FROM driver_payouts WHERE status = 'Pending'")->fetch_assoc()['total'] ?? 0;
$totalAmountApproved = $conn->query("SELECT SUM(amount) as total FROM driver_payouts WHERE status = 'Approved'")->fetch_assoc()['total'] ?? 0;
$totalAmountPaidThisMonth = $conn->query("SELECT SUM(amount) as total FROM driver_payouts WHERE status = 'Paid' AND MONTH(paid_date) = MONTH(NOW()) AND YEAR(paid_date) = YEAR(NOW())")->fetch_assoc()['total'] ?? 0;

// Tab counts
$countAll = $conn->query("SELECT COUNT(*) as count FROM driver_payouts")->fetch_assoc()['count'] ?? 0;
$countPending = $totalPending;
$countApproved = $totalApproved;
$countPaid = $totalPaid;
$countRejected = $totalRejected;
$countArchived = $totalArchived;

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Build condition based on tab
$where_clause = "";
if ($currentTab !== 'all') {
    $where_clause = " WHERE status = '" . $conn->real_escape_string(ucfirst($currentTab)) . "'";
}

// Count total records for the CURRENT TAB
$count_sql = "SELECT COUNT(*) as total FROM driver_payouts" . $where_clause;
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'] ?? 0;
$total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

// Fetch rows for the CURRENT TAB ONLY with pagination
$currentRows = [];
$sql = "SELECT * FROM driver_payouts" . $where_clause . " ORDER BY created_at DESC LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $currentRows[] = $row;
    }
}
?>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Payable - Withdrawal Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="icon" href="logo.png" type="img">
<?php endif; ?>
    <style>
        .font-poppins { font-family: 'Poppins', sans-serif; }
        
        /* Modal styles */
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
        }
        
        .modal.active { display: block !important; }
        
        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-height: 90vh;
            overflow-y: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95%;
            max-width: 600px;
        }
        
        /* Toast styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
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
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast.success { background-color: #10b981; border-left: 4px solid #059669; }
        .toast.error { background-color: #ef4444; border-left: 4px solid #dc2626; }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
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
        
        .table-container {
            width: 100%;
        }
        
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block !important;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #1f2937;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #8b5cf6;
            border-radius: 10px;
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
    
    <div id="toast-container" class="toast-container"></div>
    
    <div class="overflow-y-auto h-full px-6">
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-6 font-poppins">
            <h1 class="text-2xl">Driver Payout Requests</h1>
            <div class="text-sm">
                <a href="dashboard.php" class="text-black hover:text-blue-600">Home</a>
                /
                <a class="text-black">Accounts Payable</a>
                /
                <a href="driver_payable.php" class="text-blue-600 hover:text-blue-600">Driver Payout Requests</a>
            </div>
        </div>
<?php endif; ?>

<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <!-- Widgets -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 px-4">
            <!-- Card 1: Pending Withdrawals -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
                <div class="space-y-1">
                    <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($totalPending) ?></div>
                    <div class="text-base text-gray-500 font-medium">Pending Withdrawals</div>
                    <div class="text-base font-bold text-amber-500">₱<?= number_format($totalAmountPending, 2) ?></div>
                </div>
            </div>

            <!-- Card 2: Approved -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
                <div class="space-y-1">
                    <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($totalApproved) ?></div>
                    <div class="text-base text-gray-500 font-medium">Approved</div>
                    <div class="text-base font-bold text-green-500">₱<?= number_format($totalAmountApproved, 2) ?></div>
                </div>
            </div>
            
            <!-- Card 3: Paid This Month -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
                <div class="space-y-1">
                    <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($totalPaid) ?></div>
                    <div class="text-base text-gray-500 font-medium">Paid This Month</div>
                    <div class="text-base font-bold text-blue-500">₱<?= number_format($totalAmountPaidThisMonth, 2) ?></div>
                </div>
            </div>
            
            <!-- Card 4: Total Pending Amount -->
            <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
                <div class="space-y-1">
                    <div class="text-4xl font-bold text-[#001f3f]">₱<?= number_format($totalAmountPending, 2) ?></div>
                    <div class="text-base text-gray-500 font-medium">Total Pending Amount</div>
                </div>
            </div>
        </div>
<?php endif; ?>
        
        <div class="<?= !defined('UNIFIED_DASHBOARD_MODE') ? 'bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6' : 'p-4' ?>">
            <!-- Header with Tabs and Search -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-6">
                <!-- Status Tabs -->
                <div class="w-full lg:w-auto overflow-x-auto">
                    <div class="flex gap-2 font-poppins text-sm font-medium border-b border-gray-300 min-w-max" id="statusTabs">
                        <button type="button" data-tab="all" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= ($currentTab === 'all' || !$currentTab) ? 'active-tab' : 'text-gray-900' ?>">
                            ALL <span class="text-xs opacity-80 ml-1">(<?= $countAll ?>)</span>
                        </button>
                        <button type="button" data-tab="pending" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= $currentTab === 'pending' ? 'active-tab' : 'text-gray-900' ?>">
                            PENDING <span class="text-xs opacity-80 ml-1">(<?= $countPending ?>)</span>
                        </button>
                        <button type="button" data-tab="approved" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= $currentTab === 'approved' ? 'active-tab' : 'text-gray-900' ?>">
                            APPROVED <span class="text-xs opacity-80 ml-1">(<?= $countApproved ?>)</span>
                        </button>
                        <button type="button" data-tab="paid" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= $currentTab === 'paid' ? 'active-tab' : 'text-gray-900' ?>">
                            PAID <span class="text-xs opacity-80 ml-1">(<?= $countPaid ?>)</span>
                        </button>
                        <button type="button" data-tab="rejected" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= $currentTab === 'rejected' ? 'active-tab' : 'text-gray-900' ?>">
                            REJECTED <span class="text-xs opacity-80 ml-1">(<?= $countRejected ?>)</span>
                        </button>
                        <button type="button" data-tab="archived" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab <?= $currentTab === 'archived' ? 'active-tab' : 'text-gray-900' ?>">
                            ARCHIVED <span class="text-xs opacity-80 ml-1">(<?= $countArchived ?>)</span>
                        </button>
                    </div>
                </div>
                
                <!-- Search and Actions -->
                <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                    <!-- Search -->
                    <div class="relative flex-1 sm:flex-none sm:w-64">
                        <input type="text" 
                               id="searchInput" 
                               placeholder="Search here" 
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               onkeyup="filterTable()">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400 text-sm"></i>
                    </div>
                    
                    <!-- Filter Button -->
                    <div class="relative inline-block text-left">
                        <button id="filterBtn" onclick="toggleFilterDropdown()" class="px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none flex items-center gap-2">
                            <i class="fas fa-filter text-gray-400 text-xs"></i>
                            Filter
                            <i class="fas fa-chevron-down text-gray-400 text-[10px] ml-1"></i>
                        </button>
                        
                        <!-- Filter Dropdown -->
                        <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-[1001] p-5">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Driver</label>
                                    <input type="text" id="driver_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" placeholder="Driver name">
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Date Range</label>
                                    <input type="date" id="date_from_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div class="pt-2 border-t border-gray-100 flex justify-between">
                                    <button onclick="resetFilters()" class="text-xs font-semibold text-purple-600 hover:text-purple-700">Reset All</button>
                                    <button onclick="toggleFilterDropdown()" class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-md hover:bg-purple-700">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Import Button -->
                    <button onclick="document.getElementById('importFile').click()" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 flex items-center gap-2">
                        <i class="fas fa-file-import text-xs"></i>
                        Import
                    </button>
                    <input type="file" id="importFile" accept=".csv,.xlsx,.xls" style="display:none" onchange="handleImport(this)">
                    
                    <!-- Bulk Action Button (only show on pending tab) -->
                    <button id="bulkActionBtn" onclick="openBulkApproveModal()" class="px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-2" style="display:none">
                        <i class="fas fa-check-double text-xs"></i>
                        Bulk Action
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <div class="tab-pane active">
                    <div class="table-container">
                        <table class="w-full table-auto bg-white">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-6 py-3">PAYOUT ID</th>
                                    <th class="px-6 py-3">DRIVER NAME</th>
                                    <th class="px-6 py-3">AMOUNT</th>
                                    <th class="px-6 py-3">STATUS</th>
                                    <th class="px-6 py-3">CREATED DATE</th>
                                    <th class="px-6 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light">
                                <?php if (empty($currentRows)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>No withdrawal requests found in this category</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($currentRows as $row): 
                                    $statusClass = '';
                                    switch($row['status']) {
                                        case 'Pending': $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                                        case 'Approved': $statusClass = 'bg-blue-100 text-blue-800'; break;
                                        case 'Paid': $statusClass = 'bg-green-100 text-green-800'; break;
                                        case 'Rejected': $statusClass = 'bg-red-100 text-red-800'; break;
                                        case 'Archived': $statusClass = 'bg-gray-100 text-gray-800'; break;
                                        default: $statusClass = 'bg-gray-100 text-gray-800';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                    <td class='px-6 py-3 font-medium text-gray-900'><?= htmlspecialchars($row['payout_id']) ?></td>
                                    <td class='px-6 py-3'>
                                        <span class="font-medium text-gray-900"><?= htmlspecialchars($row['driver_name']) ?></span>
                                    </td>
                                    <td class='px-6 py-3 font-bold text-gray-900'>₱<?= number_format($row['amount'], 2) ?></td>
                                    <td class='px-6 py-3'>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $statusClass ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class='px-6 py-3 text-gray-600'><?= date('Y-m-d', strtotime($row['created_at'])) ?></td>
                                    <td class='px-6 py-3 text-center'>
                                        <button onclick="viewDetails('<?= htmlspecialchars($row['payout_id']) ?>')" class="px-4 py-2 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition-all">
                                            <i class="fas fa-eye mr-1"></i> <?= $row['status'] === 'Pending' ? 'Review' : 'View' ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div class="text-sm text-gray-600">
                            <?php 
                            $start = ($total_rows > 0) ? ($offset + 1) : 0;
                            $end = min($offset + $records_per_page, $total_rows);
                            ?>
                            Showing <?= $start ?> to <?= $end ?> of <?= $total_rows ?> entries
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="changePage(<?= $page - 1 ?>)" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1 <?= $page <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page <= 1 ? 'disabled' : '' ?>>
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button onclick="changePage(<?= $page + 1 ?>)" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1 <?= $page >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content !max-w-[1500px] !p-8 relative">
            <button onclick="closeModal('reviewModal')" class="absolute top-4 right-4 text-gray-500 hover:text-purple-700 transition-colors z-10">
                <i class="fas fa-times text-2xl"></i>
            </button>
            
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center border-b pb-4 flex items-center justify-center gap-3">
                <i class="fas fa-file-invoice text-purple-600"></i>
                Withdrawal Request Details
            </h2>
            
            <div class="grid grid-cols-[400px_1fr] gap-8">
                <!-- Left Column: Details & Budget -->
                <div id="modalDetailsColumn">
                    <div id="reviewContentBasic" class="space-y-4 text-sm">
                        <!-- Content loaded via AJAX -->
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
                    <div id="modalActionButtons" class="mt-8 pt-6 border-t border-gray-100 flex gap-3">
                        <!-- Buttons will be injected here by JavaScript -->
                    </div>
                </div>

                <!-- Right Column: Document Viewer -->
                <div id="modalDocumentColumn">
                    <h3 class="text-lg font-semibold text-purple-700 mb-3">Supporting Document</h3>
                    <div id="documentViewerContainer" class="border-2 border-gray-200 rounded-xl p-0 bg-gray-50 min-h-[700px] flex flex-col relative overflow-hidden shadow-inner">
                        <div id="pdfViewerPlaceholder" class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-3">
                            <i class="fas fa-file-contract text-5xl opacity-20"></i>
                            <p class="font-medium">No document available</p>
                        </div>
                        
                        <iframe id="pdfFrame" class="flex-1 w-full border-0 rounded hidden" frameborder="0"></iframe>

                        <!-- Receipts Tray -->
                        <div id="receiptsTray" class="bg-gray-900/90 backdrop-blur text-white p-3 border-t border-gray-700 hidden">
                             <div class="flex items-center justify-between mb-2 pb-1 border-b border-gray-800">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500" id="receiptsCount">0 Files ATTACHED</span>
                            </div>
                            <div id="receiptsList" class="flex flex-wrap gap-2 max-h-[120px] overflow-y-auto">
                                 <!-- List of files -->
                            </div>
                        </div>
                    </div>
                </div>
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
                        Bulk Approve Withdrawals
                    </h2>
                    <p class="text-gray-500 text-sm mt-1">Select the withdrawal requests you want to approve as a batch.</p>
                </div>

                <div class="border rounded-xl overflow-hidden mb-6">
                    <div class="max-h-[400px] overflow-y-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-600 uppercase text-xs sticky top-0">
                                <tr>
                                    <th class="px-6 py-3">
                                        <input type="checkbox" id="selectAllBulk" onchange="toggleAllBulk(this)" class="rounded text-green-600 focus:ring-green-500">
                                    </th>
                                    <th class="px-4 py-3">Payout ID</th>
                                    <th class="px-4 py-3">Driver</th>
                                    <th class="px-4 py-3">Driver ID</th>
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
    
    <!-- Bulk Approve Confirmation Modal -->
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
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content !max-w-md">
            <button onclick="closeModal('rejectModal')" class="absolute top-4 right-4 text-gray-500 hover:text-red-700 transition-colors z-10">
                <i class="fas fa-times text-2xl"></i>
            </button>
            <div class="p-8">
                <h2 class="text-2xl font-bold text-red-600 mb-6 flex items-center gap-3">
                    <i class="fas fa-times-circle"></i>
                    Reject Withdrawal
                </h2>
                <form id="rejectForm" onsubmit="submitReject(event)">
                    <input type="hidden" id="rejectPayoutId" name="payout_id">
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea name="reason" required rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeModal('rejectModal')" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all">
                            <i class="fas fa-times-circle mr-2"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Approve Confirmation Modal -->
    <div id="approveConfirmModal" class="modal">
        <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
            <button onclick="closeModal('approveConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900">Approve Withdrawal</h2>
                <p class="text-gray-500 text-sm mt-1">Are you sure you want to approve this driver withdrawal request?</p>
            </div>
            
            <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Payout ID:</span>
                    <span class="font-bold text-gray-800" id="confirmPayoutId">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Driver:</span>
                    <span class="font-bold text-gray-800" id="confirmDriverName">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-medium">Amount:</span>
                    <span class="font-bold text-green-700" id="confirmAmount">-</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="approveConfirmed()" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-md text-sm">
                    Confirm Approval
                </button>
                <button onclick="closeModal('approveConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching (sync with URL for pagination)
        document.querySelectorAll('.request-type-tab').forEach(button => {
            button.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                url.searchParams.set('page', 1); // Reset to page 1 on tab switch
                window.location.href = url.toString();
            });
        });
        
        function toggleFilterDropdown() {
            const dropdown = document.getElementById('filterDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        function resetFilters() {
            document.getElementById('driver_filter').value = '';
            document.getElementById('date_from_filter').value = '';
            filterTable();
        }
        
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const driver = document.getElementById('driver_filter')?.value.toLowerCase() || '';
            
            document.querySelectorAll('.tab-pane.active tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                const visible = text.includes(search) && (driver === '' || text.includes(driver));
                row.style.display = visible ? '' : 'none';
            });
        }
        
        function viewDetails(payoutId) {
            fetch(`driver_payable.php?action=get_payout_details&payout_id=${payoutId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const payout = data.data;
                        const statusLower = (payout.status || 'Pending').toLowerCase();
                        const color = statusLower === 'pending' ? 'yellow' : (statusLower === 'approved' ? 'green' : (statusLower === 'paid' ? 'blue' : 'gray'));
                        
                        // Basic Details HTML
                        const content = `
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Payout ID</p>
                                    <p class="font-bold text-gray-900">${payout.payout_id}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Driver ID</p>
                                    <p class="font-bold text-gray-900">${payout.driver_id}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Driver Name</p>
                                    <p class="font-medium text-gray-900">${payout.driver_name}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Wallet ID</p>
                                    <p class="font-medium text-gray-900">${payout.wallet_id}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Amount</p>
                                    <p class="text-lg font-bold text-purple-600">₱${parseFloat(payout.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-${color}-100 text-${color}-800">${payout.status}</span>
                                </div>
                                <div class="col-span-2 border-t pt-3">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">GL Account</p>
                                    <p class="text-gray-700">${payout.gl_account || 'Liability - Driver Wallet'}</p>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Description</p>
                                    <p class="text-gray-700 italic">"${payout.description || 'Weekly earnings withdrawal request'}"</p>
                                </div>
                                ${payout.approver_notes ? `<div class="col-span-2 bg-green-50 p-3 rounded-lg border border-green-100"><p class="text-[10px] font-bold text-green-700 uppercase mb-1">Approver Notes</p><p class="text-sm text-green-900">${payout.approver_notes}</p></div>` : ''}
                                ${payout.rejected_reason ? `<div class="col-span-2 bg-red-50 p-3 rounded-lg border border-red-100"><p class="text-[10px] font-bold text-red-700 uppercase mb-1">Rejection Reason</p><p class="text-sm text-red-900">${payout.rejected_reason}</p></div>` : ''}
                            </div>
                        `;
                        
                        document.getElementById('reviewContentBasic').innerHTML = content;
                        
                        // Action Buttons
                        let actionButtonsHtml = '';
                        if (statusLower === 'pending') {
                            actionButtonsHtml = `
                                <button onclick="approvePayout('${payout.payout_id}', '${payout.driver_name}', '${payout.amount}')" id="approveBtn" class="flex-1 px-6 py-2.5 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition-all shadow-sm flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button onclick="openRejectModal('${payout.payout_id}')" class="flex-1 px-6 py-2.5 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition-all shadow-sm flex items-center justify-center gap-2">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            `;
                            // Fetch budget impact
                            const dept = payout.requested_department || 'Logistic-1';
                            fetchBudgetAllocation(dept, payout.amount);
                        } else {
                             document.getElementById('budgetAllocationContent').innerHTML = `<div class="text-center text-xs text-gray-500 py-4 italic"><i class="fas fa-info-circle mr-1"></i> Budget impact only available for pending requests</div>`;
                             actionButtonsHtml = `<p class="text-center w-full text-gray-500 text-xs italic">No actions available for ${statusLower} requests</p>`;
                        }
                        document.getElementById('modalActionButtons').innerHTML = actionButtonsHtml;

                        // Documents
                        handleDocuments(payout);
                        
                        openModal('reviewModal');
                    } else {
                        showToast('Failed to load payout details', 'error');
                    }
                })
                .catch(err => {
                    console.error("Error fetching details:", err);
                    showToast('An error occurred while loading details', 'error');
                });
        }

        function handleDocuments(payout) {
            const pdfIframe = document.getElementById('pdfFrame');
            const pdfPlaceholder = document.getElementById('pdfViewerPlaceholder');
            const trayContainer = document.getElementById('receiptsTray');
            const listContainer = document.getElementById('receiptsList');
            const docCount = document.getElementById('receiptsCount');
            
            let documents = [];
            try {
                if (payout.document) {
                    if (payout.document.trim().startsWith('[') || payout.document.trim().startsWith('{')) {
                        documents = JSON.parse(payout.document);
                    } else {
                        documents = [payout.document];
                    }
                }
            } catch (e) {
                documents = payout.document ? [payout.document] : [];
            }

            if (documents.length > 0) {
                trayContainer.classList.remove('hidden');
                docCount.textContent = documents.length + ' FILES ATTACHED';
                
                let trayHtml = '';
                documents.forEach((doc, idx) => {
                    const name = doc.split('/').pop();
                    const shortName = name.length > 20 ? name.substring(0, 17) + '...' : name;
                    trayHtml += `
                         <div class="px-3 py-1.5 border border-gray-700 rounded bg-gray-800/50 cursor-pointer hover:bg-gray-700 transition-all text-[10px] text-gray-300 flex items-center gap-2" 
                              onclick="switchDocument('${doc}', this)" title="${name}">
                            <i class="fas fa-file"></i>
                            <span class="truncate max-w-[150px] font-medium">${shortName}</span>
                        </div>
                    `;
                });
                listContainer.innerHTML = trayHtml;
                switchDocument(documents[0], listContainer.firstElementChild);
            } else {
                pdfIframe.classList.add('hidden');
                pdfPlaceholder.classList.remove('hidden');
                trayContainer.classList.add('hidden');
            }
        }

        function switchDocument(doc, element) {
            const pdfIframe = document.getElementById('pdfFrame');
            const pdfPlaceholder = document.getElementById('pdfViewerPlaceholder');
            
            if (!doc) return;
            
            // Handle both full URLs and relative paths
            const fileUrl = doc.includes('http') ? doc : `view_pdf.php?file=${encodeURIComponent(doc.split('/').pop())}`;
            pdfIframe.src = fileUrl;
            pdfIframe.classList.remove('hidden');
            pdfPlaceholder.classList.add('hidden');
            
            // Update active tab style
            document.querySelectorAll('#receiptsList > div').forEach(el => el.classList.remove('border-purple-500', 'bg-purple-900/40', 'ring-1', 'ring-purple-500'));
            if (element) {
                element.classList.add('border-purple-500', 'bg-purple-900/40', 'ring-1', 'ring-purple-500');
            }
        }

        function fetchBudgetAllocation(department, amount) {
            const container = document.getElementById('budgetAllocationContent');
            container.innerHTML = `<div class="flex justify-center py-4"><i class="fas fa-spinner fa-spin text-purple-600 text-xl"></i></div>`;
            
            const formData = new FormData();
            formData.append('action', 'get_budget');
            formData.append('department', department);

            fetch('driver_payable.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        const b = data.data;
                        const allocated = parseFloat(b.allocated_amount || 0);
                        const spent = parseFloat(b.spent || 0);
                        const invoiceAmt = parseFloat(amount || 0);
                        const newSpent = spent + invoiceAmt;
                        const newRemaining = allocated - newSpent;
                        const isOver = newRemaining < 0;
                        
                        const percentSpent = allocated > 0 ? (spent / allocated * 100) : 0;
                        const newPercentSpent = allocated > 0 ? (newSpent / allocated * 100) : 0;

                        let html = '';
                        if (isOver) {
                            html += `<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-600 text-[10px] font-bold mb-3 w-full justify-center"><i class="fas fa-exclamation-triangle"></i> Insufficient Budget</div>`;
                        } else {
                            html += `<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-600 text-[10px] font-bold mb-3 w-full justify-center"><i class="fas fa-check-circle"></i> Sufficient Budget</div>`;
                        }

                        html += `
                            <div class="space-y-2">
                                <div class="flex justify-between text-[11px]"><span>Allocated:</span><span class="font-bold font-mono text-gray-900">₱${allocated.toLocaleString(undefined, {minimumFractionDigits:2})}</span></div>
                                <div class="flex justify-between text-[11px]"><span>Current Spent:</span><span class="font-bold font-mono text-gray-900">₱${spent.toLocaleString(undefined, {minimumFractionDigits:2})} (${percentSpent.toFixed(1)}%)</span></div>
                                <div class="h-px bg-gray-100 my-1"></div>
                                <div class="flex justify-between text-[11px]"><span>After Approval:</span>
                                    <div class="text-right">
                                        <div class="font-bold font-mono ${isOver ? 'text-red-600' : 'text-green-600'}">₱${newSpent.toLocaleString(undefined, {minimumFractionDigits:2})} (${newPercentSpent.toFixed(1)}%)</div>
                                        <div class="text-[10px] ${isOver ? 'text-red-500 font-bold' : 'text-gray-400'}">Rem: ₱${newRemaining.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                                    </div>
                                </div>
                            </div>`;
                        container.innerHTML = html;
                        
                        const approveBtn = document.getElementById('approveBtn');
                        if (approveBtn) {
                            approveBtn.disabled = isOver;
                            if (isOver) {
                                approveBtn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                                approveBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                                approveBtn.title = "Insufficient Budget Blocked";
                            }
                        }
                    } else {
                        container.innerHTML = `<div class="text-center text-[10px] text-gray-500 py-4 italic"><i class="fas fa-info-circle mr-1"></i> ${data.message || 'No active budget found for this department'}</div>`;
                    }
                })
                .catch(err => {
                    console.error("Budget Error:", err);
                    container.innerHTML = `<div class="text-center text-[10px] text-red-500 py-4 font-bold"><i class="fas fa-exclamation-circle mr-1"></i> Error loading budget info</div>`;
                });
        }
        
        function approvePayout(payoutId, driverName, amount) {
            // Store data for confirmation
            window.pendingApproval = {
                payoutId: payoutId,
                driverName: driverName,
                amount: amount
            };
            
            // Update confirmation modal
            document.getElementById('confirmPayoutId').textContent = payoutId;
            document.getElementById('confirmDriverName').textContent = driverName;
            document.getElementById('confirmAmount').textContent = '₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Close review modal and show confirmation
            closeModal('reviewModal');
            openModal('approveConfirmModal');
        }
        
        function approveConfirmed() {
            if (!window.pendingApproval) return;
            
            const formData = new FormData();
            formData.append('action', 'approve');
            formData.append('payout_id', window.pendingApproval.payoutId);
            formData.append('approver_notes', 'Approved');
            
            fetch('driver_payable.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeModal('approveConfirmModal');
                    showToast(data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        function openRejectModal(payoutId) {
            document.getElementById('rejectPayoutId').value = payoutId;
            closeModal('reviewModal');
            openModal('rejectModal');
        }
        
        function submitReject(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('action', 'reject');
            
            fetch('driver_payable.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal('rejectModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
        
        function showToast(message, type) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon} mr-3"></i><span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5000);
        }
        
        // Show/hide bulk action button based on active tab
        function updateBulkActionButton() {
            const activeTab = document.querySelector('.request-type-tab.active-tab');
            const bulkBtn = document.getElementById('bulkActionBtn');
            if (activeTab && activeTab.getAttribute('data-tab') === 'pending') {
                bulkBtn.style.display = 'flex';
            } else {
                bulkBtn.style.display = 'none';
            }
        }
        
        // Call on page load and tab switch
        document.addEventListener('DOMContentLoaded', updateBulkActionButton);
        document.querySelectorAll('.request-type-tab').forEach(button => {
            button.addEventListener('click', function() {
                setTimeout(updateBulkActionButton, 100);
            });
        });
        
        // Bulk approve functions
        function openBulkApproveModal() {
            const bulkList = document.getElementById('bulkApproveList');
            bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><i class="fas fa-spinner fa-spin text-green-500 text-2xl mb-2"></i><p class="text-xs text-gray-500">Fetching pending withdrawals...</p></td></tr>';
            
            document.getElementById('bulkTotalAmount').textContent = '₱0.00';
            document.getElementById('bulkSelectedCount').textContent = '0';
            document.getElementById('bulkApproveSubmitBtn').disabled = true;
            
            openModal('bulkApproveModal');
            
            fetch('driver_payable.php?action=get_pending_bulk')
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(item => {
                            html += `
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3">
                                        <input type="checkbox" name="bulk_ids[]" value="${item.payout_id}" data-amount="${item.amount}" onchange="updateBulkTotal()" class="rounded text-green-600 focus:ring-green-500">
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">${item.payout_id}</td>
                                    <td class="px-4 py-3 font-medium">${item.driver_name}</td>
                                    <td class="px-4 py-3 text-gray-600">${item.driver_id}</td>
                                    <td class="px-4 py-3 text-right font-bold">₱${parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                </tr>
                            `;
                        });
                        bulkList.innerHTML = html;
                    } else {
                        bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 font-medium italic">No pending withdrawals found.</td></tr>';
                    }
                })
                .catch(error => {
                    bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-red-500">Error loading data</td></tr>';
                });
        }
        
        function toggleAllBulk(checkbox) {
            const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]');
            checkboxes.forEach(cb => {
                cb.checked = checkbox.checked;
            });
            updateBulkTotal();
        }
        
        function updateBulkTotal() {
            const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]');
            let total = 0;
            let count = 0;
            
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.getAttribute('data-amount'));
                    count++;
                }
            });
            
            document.getElementById('bulkTotalAmount').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('bulkSelectedCount').textContent = count;
            document.getElementById('bulkApproveSubmitBtn').disabled = count === 0;
        }
        
        function submitBulkApproval() {
            const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
            if (checkboxes.length === 0) {
                showToast('Please select at least one withdrawal to approve', 'error');
                return;
            }
            
            // Calculate total
            let total = 0;
            checkboxes.forEach(cb => {
                total += parseFloat(cb.getAttribute('data-amount'));
            });
            
            // Update confirmation modal
            document.getElementById('confirmBulkCount').textContent = checkboxes.length;
            document.getElementById('confirmBulkTotal').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Show confirmation modal
            openModal('bulkApproveConfirmModal');
        }
        
        function bulkApproveConfirmed() {
            const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
            const payout_ids = Array.from(checkboxes).map(cb => cb.value);
            const formData = new FormData();
            formData.append('action', 'bulk_approve');
            payout_ids.forEach(id => formData.append('payout_ids[]', id));
            
            fetch('driver_payable.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    closeModal('bulkApproveConfirmModal');
                    closeModal('bulkApproveModal');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
        
        // Import handler
        function handleImport(input) {
            if (input.files && input.files[0]) {
                showToast('Import functionality coming soon!', 'info');
                input.value = '';
            }
        }
        
        
        // Pagination function
        function changePage(page) {
            if (page < 1 || page > <?= $total_pages ?>) return;
            const url = new URL(window.location);
            url.searchParams.set('page', page);
            // Tab is already in URL if navigated via tab buttons
            window.location.href = url.toString();
        }
        
        // Modal auto-close when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
    </script>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
</body>
</html>
<?php endif; ?>
