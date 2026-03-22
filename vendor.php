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

// Suppress errors for AJAX responses to prevent JSON corruption
if (isset($_GET['action']) || isset($_POST['action'])) {
    // We'll use ob_start to handle any accidental output and clear it before JSON
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
}


// Include accounting helper functions
require_once 'includes/accounting_functions.php';


// Handle tab switching via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_tab']) && !isset($_POST['action'])) {
    $_SESSION['current_tab'] = $_POST['current_tab'];
    exit();
}

// Set current tab from GET parameter or session
if (isset($_GET['tab'])) {
    $_SESSION['current_tab'] = $_GET['tab'];
} elseif (!isset($_SESSION['current_tab'])) {
    // Only default to 'all' if no session tab exists
    $_SESSION['current_tab'] = 'all';
}

$currentTab = $_SESSION['current_tab'] ?? 'all';

// =================================================================
// AJAX REQUEST HANDLERS - MOVED TO TOP FOR PERFORMANCE OPTIMIZATION
// =================================================================
// These handlers skip the heavy metric/category queries below


// AJAX request handling for APPROVE (moved from confirm_payable.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    $response = ['success' => false, 'message' => ''];
    
    $invoice_id = $_POST['invoice_id'];
    
    $conn->begin_transaction();
    try {
        // Get invoice details first and lock the row to prevent race conditions
        $get_sql = "SELECT * FROM accounts_payable WHERE invoice_id = ? FOR UPDATE";
        $get_stmt = $conn->prepare($get_sql);
        $get_stmt->bind_param("s", $invoice_id);
        $get_stmt->execute();
        $invoice = $get_stmt->get_result()->fetch_assoc();
        $get_stmt->close();
        
        if (!$invoice) {
            throw new Exception("Invoice not found");
        }
        
        // Update status to approved and set approval date - ONLY IF PENDING
        $update_sql = "UPDATE accounts_payable SET status = 'approved', approval_date = NOW() WHERE invoice_id = ? AND status = 'pending'";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $invoice_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error approving invoice: " . $conn->error);
        }
        
        // If no rows were affected, it might already be approved
        if ($stmt->affected_rows === 0) {
            // Check if it's already approved to avoid double processing
            if ($invoice['status'] === 'approved') {
                $response['success'] = true;
                $response['message'] = "Invoice was already approved.";
                $conn->commit();
                echo json_encode($response);
                exit();
            }
            throw new Exception("Invoice is not in pending status or not found.");
        }
        $stmt->close();
        
        // Create payout record in 'pa' table - ONLY IF NOT EXISTS
        $reference_id = "VEN-" . $invoice_id; // Vendor invoice prefix
        
        // Double check PA table to prevent duplicates
        $check_pa = $conn->prepare("SELECT id FROM pa WHERE reference_id = ?");
        $check_pa->bind_param("s", $reference_id);
        $check_pa->execute();
        if ($check_pa->get_result()->num_rows > 0) {
            $check_pa->close();
            // Refresh the approval date to bring it to the top of the payout list
            $update_pa = $conn->prepare("UPDATE pa SET approved_date = NOW() WHERE reference_id = ?");
            $update_pa->bind_param("s", $reference_id);
            $update_pa->execute();
            $update_pa->close();
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Invoice approved (payout record updated).";
            echo json_encode($response);
            exit();
        }
        $check_pa->close();

        $expense_categories = "Vendor Payment";
        $description = "Payment for vendor invoice " . $invoice_id;
        $from_payable = 1;
        $payment_due_date = $invoice['payment_due'];
        
        // Safe ID Workaround
        $next_pa_id = getNextAvailableId($conn, 'pa');
        
        $insert_sql = "INSERT INTO pa (
            id, reference_id, account_name, vendor_address, requested_department, mode_of_payment, 
            expense_categories, amount, description, document, payment_due, 
            from_payable, bank_name, bank_account_number, bank_account_name, 
            ecash_provider, ecash_account_name, ecash_account_number, 
            submitted_date, approved_date, transaction_type, payout_type, 
            source_module, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, 
            ?, NOW(), 'Vendor', 'Vendor', 
            'Vendor', 'Pending Disbursement'
        )";
        
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Ensure values are not null
        $vendor_name = $invoice['vendor_name'] ?? 'N/A';
        $vendor_address = $invoice['vendor_address'] ?? 'N/A';
        $department = $invoice['department'] ?? 'N/A';
        $payment_method = $invoice['payment_method'] ?? 'N/A';
        $amount_val = floatval($invoice['amount'] ?? 0);
        $doc_path = $invoice['document'] ?? '';
        $bank_n = $invoice['bank_name'] ?? '';
        $bank_acc_n = $invoice['bank_account_number'] ?? '';
        $bank_acc_name = $invoice['bank_account_name'] ?? '';
        $ecash_p = $invoice['ecash_provider'] ?? '';
        $ecash_name = $invoice['ecash_account_name'] ?? '';
        $ecash_num = $invoice['ecash_account_number'] ?? '';
        $s_date = $invoice['invoice_date'] ?? date('Y-m-d');
        
        $insert_stmt->bind_param(
            "issssssdsssisssssss",
            $next_pa_id,
            $reference_id,
            $vendor_name,
            $vendor_address,
            $department,
            $payment_method,
            $expense_categories,
            $amount_val,
            $description,
            $doc_path,
            $payment_due_date,
            $from_payable,
            $bank_n,
            $bank_acc_n,
            $bank_acc_name,
            $ecash_p,
            $ecash_name,
            $ecash_num,
            $s_date
        );
        if (!$insert_stmt->execute()) {
            throw new Exception("Error creating payout record: " . $insert_stmt->error);
        }
        $insert_stmt->close();
        
        // Create journal entry and post to ledger
        try {
            if (function_exists('createVendorInvoiceJournalEntry')) {
                $journal_number = createVendorInvoiceJournalEntry($conn, $invoice);
                error_log("Journal entry created: $journal_number for invoice: " . $invoice['invoice_id']);
            }
        } catch (Exception $e) {
            // Log error but don't stop the process if the payout record is already created
            error_log("Failed to create journal entry: " . $e->getMessage());
        }
        
        // Trigger Webhook Update if exists
        try {
            if (file_exists('api/webhook_helper.php')) {
                require_once 'api/webhook_helper.php';
                if (function_exists('sendWebhookUpdate')) {
                    sendWebhookUpdate($conn, $department, 'vendor_invoice', $invoice['invoice_id'], 'Approved');
                }
            }
        } catch (Exception $e) { error_log("Webhook failed: " . $e->getMessage()); }
        
        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Invoice approved successfully!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = "Approval failed: " . $e->getMessage();
    }
    
    // Clear any output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    echo json_encode($response);
    exit();
}

// AJAX request handling for REJECT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $response = ['success' => false, 'message' => ''];
    
    $invoice_id = $_POST['invoice_id'];
    $reason   = $conn->real_escape_string($_POST['reason']);
 
    $conn->begin_transaction();
    try {
        // Safe ID Workaround
        $next_rej_id = getNextAvailableId($conn, 'rejected_payables');
        
        // Insert into rejected_payables table
        $insert_sql = "INSERT INTO rejected_payables (id, invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, bank_name, bank_account_name, bank_account_number, ecash_provider, ecash_account_name, ecash_account_number, rejected_reason)
                        SELECT ?, invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, bank_name, bank_account_name, bank_account_number, ecash_provider, ecash_account_name, ecash_account_number, ?
                        FROM accounts_payable WHERE invoice_id = ?";
        $stmt_insert = $conn->prepare($insert_sql);
        $stmt_insert->bind_param("iss", $next_rej_id, $reason, $invoice_id);
 
        if ($stmt_insert->execute()) {
            // Delete from accounts_payable
            $delete_sql = "DELETE FROM accounts_payable WHERE invoice_id = ?";
            $stmt_delete = $conn->prepare($delete_sql);
            $stmt_delete->bind_param("s", $invoice_id);
 
            if ($stmt_delete->execute()) {
                // Trigger Webhook Update
                // We need to know the department of the invoice ID
                $check_dept_sql = "SELECT department FROM rejected_payables WHERE invoice_id = ? ORDER BY id DESC LIMIT 1";
                $cd_stmt = $conn->prepare($check_dept_sql);
                $cd_stmt->bind_param("s", $invoice_id);
                $cd_stmt->execute();
                $cd_res = $cd_stmt->get_result()->fetch_assoc();
                $cd_stmt->close();
                
                if ($cd_res) {
                    require_once 'api/webhook_helper.php';
                    sendWebhookUpdate($conn, $cd_res['department'], 'vendor_invoice', $invoice_id, 'Rejected', $reason);
                }

                $conn->commit();
                $response['success'] = true;
                $response['message'] = "Invoice rejected and moved to Rejected Invoices!";
            } else {
                throw new Exception("Error deleting record from accounts_payable: " . $stmt_delete->error);
            }
        } else {
            throw new Exception("Error inserting record into rejected_payables: " . $stmt_insert->error);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Transaction failed: " . $e->getMessage();
    }
    
    // Clear any previous output
    // Clear all output buffers to ensure clean JSON
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for GET BUDGET ALLOCATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_budget') {
    error_reporting(0);
    ob_start();
    
    $response = ['success' => false, 'message' => '', 'data' => null];
    $department = $_POST['department'] ?? '';
    
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
            // Check if department exists at all (for debugging)
            $check_sql = "SELECT COUNT(*) as count FROM budget_allocations WHERE department = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $department);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $check_row = $check_result->fetch_assoc();
            
            if ($check_row['count'] > 0) {
                $response['message'] = "Budget found but not active for $department";
            } else {
                $response['message'] = "No budget allocation found for department: $department";
            }
            $check_stmt->close();
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for ARCHIVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archive') {
    $response = ['success' => false, 'message' => ''];
    
    $archiveIds = $_POST['archive_ids'] ?? [];
    
    if (!empty($archiveIds)) {
        $conn->begin_transaction();
        try {
            foreach ($archiveIds as $archiveId) {
                // Safe ID Workaround
                $next_arch_id = getNextAvailableId($conn, 'archive_payables');
                
                // Insert into archive_payables table
                $insert_sql = "INSERT INTO archive_payables (
                    id, invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, 
                    document, invoice_date, payment_due, bank_name, bank_account_name, bank_account_number, 
                    ecash_provider, ecash_account_name, ecash_account_number, rejected_reason
                )
                SELECT 
                    ?, invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, 
                    document, invoice_date, payment_due, bank_name, bank_account_name, bank_account_number, 
                    ecash_provider, ecash_account_name, ecash_account_number, rejected_reason
                FROM rejected_payables WHERE id = ?";
                
                $stmt_insert = $conn->prepare($insert_sql);
                $stmt_insert->bind_param("ii", $next_arch_id, $archiveId);
                
                if (!$stmt_insert->execute()) {
                    throw new Exception("Error archiving record ID $archiveId: " . $stmt_insert->error);
                }
                $stmt_insert->close();
                
                // Delete from rejected_payables
                $delete_sql = "DELETE FROM rejected_payables WHERE id = ?";
                $stmt_delete = $conn->prepare($delete_sql);
                $stmt_delete->bind_param("i", $archiveId);
                
                if (!$stmt_delete->execute()) {
                    throw new Exception("Error deleting record ID $archiveId from rejected_payables: " . $stmt_delete->error);
                }
                $stmt_delete->close();
            }
            $conn->commit();
            $response['success'] = true;
            $response['message'] = count($archiveIds) . " invoice(s) archived successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = "Archive transaction failed: " . $e->getMessage();
        }
    } else {
        $response['message'] = "No invoices selected for archiving";
    }
    // Clear any output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    echo json_encode($response);
    exit();
}

// AJAX: Get all pending invoices (for bulk approve modal)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_bulk') {
    $response = ['success' => false, 'data' => []];
    
    $sql = "SELECT invoice_id, vendor_name, department, vendor_type, amount, invoice_date 
            FROM accounts_payable 
            WHERE status = 'pending' 
            ORDER BY invoice_date DESC";
    
    $result = $conn->query($sql);
    if ($result) {
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
    
    // Clear any output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX: Bulk Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_approve') {
    $response = ['success' => false, 'message' => ''];
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    
    if (empty($invoice_ids)) {
        $response['message'] = "No invoices selected for approval.";
    } else {
        $success_count = 0;
        $error_count = 0;
        
        foreach ($invoice_ids as $invoice_id) {
            $invoice_id = $conn->real_escape_string($invoice_id);
            
            $conn->begin_transaction();
            try {
                // Get invoice details and lock row
                $get_sql = "SELECT * FROM accounts_payable WHERE invoice_id = ? FOR UPDATE";
                $get_stmt = $conn->prepare($get_sql);
                $get_stmt->bind_param("s", $invoice_id);
                $get_stmt->execute();
                $invoice = $get_stmt->get_result()->fetch_assoc();
                $get_stmt->close();
                
                if (!$invoice) {
                    throw new Exception("Invoice not found");
                }
                
                // Update status to approved - ONLY IF PENDING
                $update_sql = "UPDATE accounts_payable SET status = 'approved', approval_date = NOW() WHERE invoice_id = ? AND status = 'pending'";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("s", $invoice_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error approving invoice");
                }
                
                // If no rows were affected, check if it was already handled
                if ($stmt->affected_rows === 0) {
                    $stmt->close();
                    // Double check if it already exists in PA
                    $check_p = $conn->prepare("SELECT id FROM pa WHERE reference_id = ?");
                    $pref = "VEN-" . $invoice_id;
                    $check_p->bind_param("s", $pref);
                    $check_p->execute();
                    if ($check_p->get_result()->num_rows > 0) {
                        $check_p->close();
                        // Refresh approval date
                        $upd_p = $conn->prepare("UPDATE pa SET approved_date = NOW() WHERE reference_id = ?");
                        $upd_p->bind_param("s", $pref);
                        $upd_p->execute();
                        $upd_p->close();
                        
                        $success_count++;
                        $conn->commit();
                        continue;
                    }
                    $check_p->close();
                    
                    if ($invoice['status'] === 'approved') {
                        $success_count++;
                        $conn->commit();
                        continue;
                    }
                    throw new Exception("Invoice is not in pending status or not found.");
                }
                $stmt->close();
                
                // Create payout record in 'pa' table
                $reference_id = "VEN-" . $invoice_id;
                $expense_categories = "Vendor Payment";
                $description = "Payment for vendor invoice " . $invoice_id;
                $from_payable = 1;
                $payment_due_date = $invoice['payment_due'];
                
                // Safe ID Workaround
                $next_pa_id = getNextAvailableId($conn, 'pa');
                
                $insert_sql = "INSERT INTO pa (
                    id, reference_id, account_name, vendor_address, requested_department, mode_of_payment, 
                    expense_categories, amount, description, document, payment_due, 
                    from_payable, bank_name, bank_account_number, bank_account_name, 
                    ecash_provider, ecash_account_name, ecash_account_number, 
                    submitted_date, approved_date, transaction_type, payout_type, 
                    source_module, status
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, 
                    ?, NOW(), 'Vendor', 'Vendor', 
                    'Vendor', 'Pending Disbursement'
                )";
                
                $insert_stmt = $conn->prepare($insert_sql);
                $s_date = $invoice['invoice_date'] ?? date('Y-m-d');
                $vendor_address = $invoice['vendor_address'] ?? 'N/A';
                $insert_stmt->bind_param(
                    "issssssdsssisssssss",
                    $next_pa_id,
                    $reference_id,
                    $invoice['vendor_name'],
                    $vendor_address,
                    $invoice['department'],
                    $invoice['payment_method'],
                    $expense_categories,
                    $invoice['amount'],
                    $description,
                    $invoice['document'],
                    $payment_due_date,
                    $from_payable,
                    $invoice['bank_name'],
                    $invoice['bank_account_number'],
                    $invoice['bank_account_name'],
                    $invoice['ecash_provider'],
                    $invoice['ecash_account_name'],
                    $invoice['ecash_account_number'],
                    $s_date
                );
                
                if (!$insert_stmt->execute()) {
                    throw new Exception("Error creating payout record: " . $insert_stmt->error);
                }
                $insert_stmt->close();
                
                $conn->commit();
                $success_count++;
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_count++;
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Successfully approved $success_count invoice(s).";
        if ($error_count > 0) {
            $response['message'] .= " Failed to approve $error_count invoice(s).";
        }
    }
    
    // Clear any output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}


// AJAX: Fetch Latest Data (Auto Refresh)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_latest_data') {
    $response = ['success' => false, 'html' => '', 'counts' => []];
    $tab = $_GET['tab'] ?? 'all';
    
    // 1. Get Counts
    $response['counts'] = [
        'all' => $conn->query("SELECT (SELECT COUNT(*) FROM accounts_payable) + (SELECT COUNT(*) FROM rejected_payables) + (SELECT COUNT(*) FROM archive_payables) as total")->fetch_assoc()['total'] ?? 0,
        'pending' => $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0,
        'approved' => $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0,
        'paid' => $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0,
        'rejected' => $conn->query("SELECT COUNT(*) as total FROM rejected_payables")->fetch_assoc()['total'] ?? 0,
        'archived' => $conn->query("SELECT COUNT(*) as total FROM archive_payables")->fetch_assoc()['total'] ?? 0
    ];

    // 2. Get Rows & Generate HTML
    $rows = [];
    $sql = "";
    
    if ($tab === 'all') {
        $sql = "SELECT * FROM (
            SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, paid_date, created_at, status, NULL as rejected_reason FROM accounts_payable
            UNION ALL
            SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, NULL as paid_date, created_at, 'rejected' as status, rejected_reason FROM rejected_payables
            UNION ALL
            SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, NULL as paid_date, created_at, 'archived' as status, rejected_reason FROM archive_payables
        ) as combined ORDER BY created_at DESC";
    } elseif ($tab === 'pending') {
        $sql = "SELECT * FROM accounts_payable WHERE status = 'pending' ORDER BY created_at DESC";
    } elseif ($tab === 'approved') {
        $sql = "SELECT * FROM accounts_payable WHERE status = 'approved' ORDER BY approval_date DESC";
    } elseif ($tab === 'paid') {
        $sql = "SELECT * FROM accounts_payable WHERE status = 'paid' ORDER BY paid_date DESC";
    } elseif ($tab === 'rejected') {
        $sql = "SELECT * FROM rejected_payables ORDER BY id DESC";
    } elseif ($tab === 'archived') {
        $sql = "SELECT * FROM archive_payables ORDER BY id DESC";
    }

    ob_start();
    if ($sql) {
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                $paidDateFull = $row['paid_date'] ?? null ? date('Y-m-d H:i', strtotime($row['paid_date'])) : '-';
                $paidDateData = $row['paid_date'] ?? null ? date('Y-m-d', strtotime($row['paid_date'])) : '';
                $jsonData = htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                
                // Common classes/styles
                $rowClass = "hover:bg-gray-50 border-b border-gray-100 transition-colors";
                $deptClass = "inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800";
                $typeClass = "inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800";
                $modeClass = "inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800";
                $vendorNameHtml = '<div class="flex items-center gap-2"><div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center"><i class="fas fa-building text-purple-600 text-xs"></i></div><span class="font-medium text-gray-900">' . htmlspecialchars($row['vendor_name']) . '</span></div>';
                
                if ($tab === 'all') {
                    $statusClass = '';
                    switch(strtolower($row['status'])) {
                        case 'pending': $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                        case 'approved': $statusClass = 'bg-blue-100 text-blue-800'; break;
                        case 'paid': $statusClass = 'bg-green-100 text-green-800'; break;
                        case 'rejected': $statusClass = 'bg-red-100 text-red-800'; break;
                        case 'archived': $statusClass = 'bg-gray-100 text-gray-800'; break;
                        default: $statusClass = 'bg-gray-100 text-gray-800';
                    }
                    echo "<tr class='$rowClass' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "' data-due='$paymentDue' data-paid='$paidDateData'>";
                    echo "<td class='px-6 py-3 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                    echo "<td class='px-6 py-3'><span class='$deptClass'>{$row['department']}</span></td>";
                    echo "<td class='px-6 py-3'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                    echo "<td class='px-6 py-3'><span class='font-medium text-gray-900'>{$row['vendor_name']}</span></td>";
                    echo "<td class='px-6 py-3 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                    echo "<td class='px-6 py-3 text-gray-600'>$paymentDue</td>";
                    echo "<td class='px-6 py-3'><span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold $statusClass'>" . strtoupper($row['status']) . "</span></td>";
                    echo "<td class='px-6 py-3 text-center'>";
                    if (strtolower($row['status']) === 'pending') {
                        echo "<button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> Review</button>";
                    } elseif (strtolower($row['status']) === 'rejected') {
                         echo "<button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-red-50 text-red-700 text-xs font-bold rounded-md hover:bg-red-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> Details</button>";
                    } else {
                         echo "<button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-md hover:bg-green-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> Details</button>";
                    }
                    echo "</td></tr>";
                
                } elseif ($tab === 'pending') {
                    echo "<tr class='$rowClass' data-mode='" . strtolower($row['payment_method']) . "' data-invoiceid='" . htmlspecialchars($row['invoice_id']) . "' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "' data-due='$paymentDue'>";
                    echo "<td class='pl-10 pr-6 py-3 checkbox-cell'><input type='checkbox' name='pending_ids[]' value='{$row['invoice_id']}' class='pending-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500'></td>";
                    echo "<td class='px-6 py-3 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                    echo "<td class='px-6 py-3'><span class='$deptClass'>{$row['department']}</span></td>";
                    echo "<td class='px-6 py-3'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                    echo "<td class='px-6 py-3'>$vendorNameHtml</td>";
                    echo "<td class='px-6 py-3'><span class='$modeClass'>{$row['payment_method']}</span></td>";
                    echo "<td class='px-6 py-3 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                    echo "<td class='px-6 py-3 text-gray-600'>$paymentDue</td>";
                    echo "<td class='px-6 py-3 text-center'><button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> Review</button></td>";
                    echo "</tr>";
                
                } elseif ($tab === 'approved') {
                    echo "<tr class='$rowClass' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "' data-due='$paymentDue'>";
                    echo "<td class='px-6 py-3 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                    echo "<td class='px-6 py-3'><span class='$deptClass'>{$row['department']}</span></td>";
                    echo "<td class='px-6 py-3'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                    echo "<td class='px-6 py-3'>$vendorNameHtml</td>";
                    echo "<td class='px-6 py-3'><span class='$modeClass'>{$row['payment_method']}</span></td>";
                    echo "<td class='px-6 py-3 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                    echo "<td class='px-6 py-3 text-yellow-600 font-bold italic text-xs'><i class='fas fa-clock mr-1'></i>Awaiting Disbursement</td>";
                    echo "<td class='px-6 py-3 text-center'><button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> View</button></td>";
                    echo "</tr>";

                } elseif ($tab === 'paid') {
                     echo "<tr class='$rowClass' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "' data-due='$paymentDue' data-paid='$paidDateData'>";
                     echo "<td class='px-6 py-3 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                     echo "<td class='px-6 py-3'><span class='$deptClass'>{$row['department']}</span></td>";
                     echo "<td class='px-6 py-3'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                     echo "<td class='px-6 py-3'>$vendorNameHtml</td>";
                     echo "<td class='px-6 py-3 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                     echo "<td class='px-6 py-3 text-green-600 font-bold'>$paidDateFull</td>";
                     echo "<td class='px-6 py-3 text-center'><button onclick='openReviewModal($jsonData)' class='px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-md hover:bg-green-100 transition-all flex items-center gap-1 mx-auto'><i class='fas fa-eye text-xs'></i> Details</button></td>";
                     echo "</tr>";
                } elseif ($tab === 'rejected') {
                    $jsonRejected = htmlspecialchars(json_encode(array_merge($row, ['status' => 'Rejected']), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    echo "<tr class='$rowClass' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "' data-due='$paymentDue'>";
                    echo "<td class='py-3 px-4 checkbox-cell'><input type='checkbox' name='rejected_ids[]' value='{$row['id']}' class='rejected-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500'></td>";
                    echo "<td class='py-3 px-4 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                    echo "<td class='py-3 px-4'><span class='$deptClass'>{$row['department']}</span></td>";
                    echo "<td class='py-3 px-4'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                    echo "<td class='py-3 px-4'>$vendorNameHtml</td>";
                    echo "<td class='py-3 px-4'><span class='$modeClass'>{$row['payment_method']}</span></td>";
                    echo "<td class='py-3 px-4 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                    echo "<td class='py-3 px-4 text-red-600 truncate max-w-[200px]' title='" . htmlspecialchars($row['rejected_reason']) . "'>" . htmlspecialchars($row['rejected_reason']) . "</td>";
                    echo "<td class='px-4 py-3 text-center'><div class='flex items-center justify-center gap-2'>";
                    echo "<button onclick='openReviewModal($jsonRejected)' class='p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all' title='View Details'><i class='fas fa-eye'></i></button>";
                    echo "<button onclick='archiveSingleRejected({$row['id']})' class='p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all' title='Archive'><i class='fas fa-archive'></i></button>";
                    echo "</div></td></tr>";

                } elseif ($tab === 'archived') {
                    $jsonArchived = htmlspecialchars(json_encode(array_merge($row, ['status' => 'Archived']), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                    echo "<tr class='$rowClass' data-dept='{$row['department']}' data-type='" . ($row['vendor_type'] ?? 'Vendor') . "'>";
                    echo "<td class='py-3 px-4 font-medium text-gray-900'>{$row['invoice_id']}</td>";
                    echo "<td class='py-3 px-4'><span class='$deptClass'>{$row['department']}</span></td>";
                    echo "<td class='py-3 px-4'><span class='$typeClass'>" . ($row['vendor_type'] ?? 'Vendor') . "</span></td>";
                    echo "<td class='py-3 px-4'>$vendorNameHtml</td>";
                    echo "<td class='py-3 px-4'><span class='$modeClass'>{$row['payment_method']}</span></td>";
                    echo "<td class='py-3 px-4 font-bold text-gray-900'>&#8369;" . number_format($row['amount'], 2) . "</td>";
                    echo "<td class='py-3 px-4 text-red-600 truncate max-w-[200px]' title='" . htmlspecialchars($row['rejected_reason']) . "'>" . htmlspecialchars($row['rejected_reason']) . "</td>";
                    echo "<td class='px-4 py-3 text-center'><div class='flex items-center justify-center gap-2'>";
                    echo "<button onclick='openReviewModal($jsonArchived)' class='p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all' title='View Details'><i class='fas fa-eye'></i></button>";
                    echo "</div></td></tr>";
                }
            }
            if (count($rows) === 0) {
                // Return empty state row
                $msg = "No " . ($tab === 'all' ? '' : $tab . " ") . "vendor invoices found";
                echo "<tr><td colspan='100%' class='text-center py-12 text-gray-400'><i class='fas fa-inbox text-4xl mb-3'></i><p class='text-sm font-medium'>$msg</p></td></tr>";
            }
        }
    }
    
    $response['html'] = ob_get_clean();
    $response['success'] = true;
    
    // Clean buffer
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// --- BACKGROUND AUTO-REJECT PROCESSOR REMOVED BY USER REQUEST ---

// Departments list - consistent with reimbursement.php
$departments = [
    'Human Resource 1', 
    'Human Resource 2',
    'Human Resource 3', 
    'Human Resource 4',
    'Core 1',
    'Core 2',
    'Logistic 1', 
    'Logistic 2',
    'Administrative',
    'Financials',
];

// Fetch expense categories and subcategories from chart of accounts hierarchy
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
        $expense_subcategories[$category][] = $subcategory;
    }
}

// Fetch GL Accounts (Level 4)
$gl_accounts = [];
$gl_query = "SELECT code, name FROM chart_of_accounts_hierarchy WHERE level = 4 AND status = 'active' ORDER BY code";
$gl_result = $conn->query($gl_query);
if ($gl_result) {
    while ($gl_row = $gl_result->fetch_assoc()) {
        $gl_accounts[] = $gl_row;
    }
}

// Overview Metrics
// 1. Overdue Invoices
$overdueRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status IN ('pending', 'approved') AND payment_due < CURRENT_DATE()");
$overdueData = $overdueRes ? $overdueRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$overdueCount = $overdueData['total'] ?? 0;
$overdueAmount = $overdueData['total_amt'] ?? 0;

// 2. Pending Approval
$pendingRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status = 'pending'");
$pendingData = $pendingRes ? $pendingRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$pendingCount = $pendingData['total'] ?? 0;
$pendingAmount = $pendingData['total_amt'] ?? 0;

// 3. For Payment (Approved)
$forPaymentRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status = 'approved'");
$forPaymentData = $forPaymentRes ? $forPaymentRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$forPaymentCount = $forPaymentData['total'] ?? 0;
$forPaymentAmount = $forPaymentData['total_amt'] ?? 0;

// 4. Processed This Month (Paid)
$processedRes = $conn->query("
    SELECT COUNT(*) as total, SUM(amount) as total_amt 
    FROM accounts_payable 
    WHERE status = 'paid' 
      AND MONTH(paid_date) = MONTH(CURRENT_DATE())
      AND YEAR(paid_date) = YEAR(CURRENT_DATE())
");
$processedData = $processedRes ? $processedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$processedCount = $processedData['total'] ?? 0;
$processedAmount = $processedData['total_amt'] ?? 0;

// Build queries for different tabs
$allSql = "
    SELECT * FROM (
        SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, paid_date, created_at, status, NULL as rejected_reason FROM accounts_payable
        UNION ALL
        SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, NULL as paid_date, created_at, 'rejected' as status, rejected_reason FROM rejected_payables
        UNION ALL
        SELECT invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, payment_method, amount, description, document, invoice_date, payment_due, NULL as paid_date, created_at, 'archived' as status, rejected_reason FROM archive_payables
    ) as combined ORDER BY created_at DESC";

$pendingSql = "SELECT * FROM accounts_payable WHERE status = 'pending' ORDER BY created_at DESC";
$approvedSql = "SELECT * FROM accounts_payable WHERE status = 'approved' ORDER BY approval_date DESC";
$paidSql = "SELECT * FROM accounts_payable WHERE status = 'paid' ORDER BY paid_date DESC";
$rejectedSql = "SELECT * FROM rejected_payables ORDER BY id DESC";
$archivedSql = "SELECT * FROM archive_payables ORDER BY id DESC";

// count total rows for all tabs (efficiently)
$countAll = $conn->query("SELECT (SELECT COUNT(*) FROM accounts_payable) + (SELECT COUNT(*) FROM rejected_payables) + (SELECT COUNT(*) FROM archive_payables) as total")->fetch_assoc()['total'] ?? 0;
$countPending = $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0;
$countApproved = $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'approved'")->fetch_assoc()['total'] ?? 0;
$countPaid = $conn->query("SELECT COUNT(*) as total FROM accounts_payable WHERE status = 'paid'")->fetch_assoc()['total'] ?? 0;
$countRejected = $conn->query("SELECT COUNT(*) as total FROM rejected_payables")->fetch_assoc()['total'] ?? 0;
$countArchived = $conn->query("SELECT COUNT(*) as total FROM archive_payables")->fetch_assoc()['total'] ?? 0;

// Initialize all row arrays as empty
$allRows = [];
$pendingRows = [];
$approvedRows = [];
$paidRows = [];
$rejectedRows = [];
$archivedRows = [];

// FETCH DATA ONLY FOR THE ACTIVE TAB - PERFORMANCE OPTIMIZATION
if ($currentTab === 'all') {
    $res = $conn->query($allSql);
    while($r = $res->fetch_assoc()) $allRows[] = $r;
} elseif ($currentTab === 'pending') {
    $res = $conn->query($pendingSql);
    while($r = $res->fetch_assoc()) $pendingRows[] = $r;
} elseif ($currentTab === 'approved') {
    $res = $conn->query($approvedSql);
    while($r = $res->fetch_assoc()) $approvedRows[] = $r;
} elseif ($currentTab === 'paid') {
    $res = $conn->query($paidSql);
    while($r = $res->fetch_assoc()) $paidRows[] = $r;
} elseif ($currentTab === 'rejected') {
    $res = $conn->query($rejectedSql);
    while($r = $res->fetch_assoc()) $rejectedRows[] = $r;
} elseif ($currentTab === 'archived') {
    $res = $conn->query($archivedSql);
    while($r = $res->fetch_assoc()) $archivedRows[] = $r;
}

// Count new rejected items (items added since last view)
// Initialize last seen ID if not set
if (!isset($_SESSION['rejected_last_seen_id'])) {
    // Get current max ID as the baseline
    $maxIdResult = $conn->query("SELECT MAX(id) as max_id FROM rejected_payables");
    $maxId = $maxIdResult->fetch_assoc()['max_id'] ?? 0;
    $_SESSION['rejected_last_seen_id'] = $maxId;
}

// If current tab is rejected, reset the baseline to latest and set count to 0
if ($currentTab === 'rejected') {
    $maxIdResult = $conn->query("SELECT MAX(id) as max_id FROM rejected_payables");
    $_SESSION['rejected_last_seen_id'] = $maxIdResult->fetch_assoc()['max_id'] ?? 0;
    $newRejectedCount = 0;
} else {
    // Otherwise count items newer than the baseline
    $newRejectedCountSql = "SELECT COUNT(*) as count FROM rejected_payables WHERE id > ?";
    $stmt = $conn->prepare($newRejectedCountSql);
    $stmt->bind_param("i", $_SESSION['rejected_last_seen_id']);
    $stmt->execute();
    $newRejectedCount = $stmt->get_result()->fetch_assoc()['count'];
    $stmt->close();
}


?>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Invoices Dashboard</title>
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
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .active-receipt-tab {
            background-color: rgba(139, 92, 246, 0.2) !important;
            border-color: #8b5cf6 !important;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.5) !important;
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
            max-height: 600px;
            overflow-y: auto;
        }

        /* PDF Viewer Styles */
        .pdf-viewer-container {
            width: 100%;
            height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .pdf-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Tab Pane visibility */
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block !important;
        }

        /* Modal active state */
        .modal.active {
            display: block !important;
        }

        .checkbox-cell {
            width: 40px;
            text-align: center;
            display: none;
        }
        .selection-mode .checkbox-cell {
            display: table-cell;
        }
        .pending-checkbox, .rejected-checkbox {
            display: none;
        }
        .selection-mode .pending-checkbox,
        .selection-mode .rejected-checkbox {
            display: inline-block;
        }
    </style>
    <script>
        var nextInvoiceId = <?= json_encode($latestInvoiceCode) ?>;
    </script>
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
    <?php if (isset($_SESSION['toast_message'])): ?>
    <div class="toast <?php echo $_SESSION['toast_type']; ?>" id="autoToast">
        <div class="flex items-center">
            <i class="toast-icon <?php echo $_SESSION['toast_type'] == 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($_SESSION['toast_message']); ?></span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <?php 
        unset($_SESSION['toast_message']);
        unset($_SESSION['toast_type']);
    endif; ?>
</div>

<!-- Legacy toast handling if needed (though we're moving to the new one) -->
<?php if (isset($_SESSION['success'])): ?>
    <div id="toast-success" class="fixed top-6 right-6 z-50 flex items-center w-full max-w-xs p-4 mb-4 text-green-800 bg-green-100 rounded-lg shadow transition-opacity duration-500" role="alert" style="opacity:1;">
        <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
        <div class="ml-3 text-sm font-medium"><?= htmlspecialchars($_SESSION['success']) ?></div>
    </div>
    <script>
        setTimeout(function() {
            var toast = document.getElementById('toast-success');
            if(toast) toast.style.opacity = '0';
        }, 1800);
        setTimeout(function() {
            var toast = document.getElementById('toast-success');
            if(toast) toast.style.display = 'none';
        }, 2200);
    </script>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="overflow-y-auto h-full px-6">
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl">Vendor Invoices Dashboard</h1>
        <div class="text-sm">
            <a href="dashboard.php" class="text-black hover:text-blue-600">Home</a>
            /
            <a class="text-black">Accounts Payable</a>
            /
            <a href="vendor.php" class="text-blue-600 hover:text-blue-600">Vendor Invoices</a>
        </div>
    </div>
<?php endif; ?>

<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 px-4">
        <!-- Card 1: Overdue Invoices -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($overdueCount) ?></div>
                <div class="text-base text-gray-500 font-medium">Overdue Invoices</div>
                <div class="text-base font-bold text-red-500">₱<?= number_format($overdueAmount, 2) ?></div>
            </div>
        </div>

        <!-- Card 2: Pending Approval -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($pendingCount) ?></div>
                <div class="text-base text-gray-500 font-medium">Pending Approval</div>
                <div class="text-base font-bold text-amber-500">₱<?= number_format($pendingAmount, 2) ?></div>
            </div>
        </div>
        
        <!-- Card 3: For Payment -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($forPaymentCount) ?></div>
                <div class="text-base text-gray-500 font-medium">For Payment</div>
                <div class="text-base font-bold text-blue-500">₱<?= number_format($forPaymentAmount, 2) ?></div>
            </div>
        </div>
        
        <!-- Card 4: Processed This Month -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($processedCount) ?></div>
                <div class="text-base text-gray-500 font-medium">Processed This Month</div>
                <div class="text-base font-bold text-green-500">₱<?= number_format($processedAmount, 2) ?></div>
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
                    <button type="button" data-tab="rejected" class="px-4 py-2 rounded-t-lg hover-tab request-type-tab relative <?= $currentTab === 'rejected' ? 'active-tab' : 'text-gray-900' ?>">
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
                
                <!-- Refined Filter Button -->
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
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Department</label>
                                <select id="department_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterTable()">
                                    <option value="">All Departments</option>
                                    <?php
                                    $dept_query = "SELECT DISTINCT department FROM accounts_payable WHERE department IS NOT NULL AND department != '' UNION SELECT DISTINCT department FROM rejected_payables WHERE department IS NOT NULL AND department != '' ORDER BY department";
                                    $dept_result = $conn->query($dept_query);
                                    if ($dept_result) {
                                        while ($dept_row = $dept_result->fetch_assoc()) {
                                            echo "<option value=\"" . htmlspecialchars($dept_row['department']) . "\">" . htmlspecialchars($dept_row['department']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Type</label>
                                <select id="type_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterTable()">
                                    <option value="">All Types</option>
                                    <option value="Vendor">Vendor</option>
                                    <option value="Supplier">Supplier</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Due Date</label>
                                <input type="date" id="due_date_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterTable()">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Paid Date</label>
                                <input type="date" id="paid_date_filter" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500" onchange="filterTable()">
                            </div>
                            
                            <div class="pt-2 border-t border-gray-100 flex justify-between">
                                <button onclick="resetFilters()" class="text-xs font-semibold text-purple-600 hover:text-purple-700">Reset All</button>
                                <button onclick="toggleFilterDropdown()" class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-md hover:bg-purple-700">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button id="bulkActionBtn" onclick="openBulkApproveModal()" 
                        class="px-4 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 flex items-center gap-2">
                    <i class="fas fa-check-double text-xs"></i>
                    Bulk Action
                </button>
            </div>
        </div>

            <!-- Tab Content -->
            <div id="tabContent">
                <div id="allTab" class="tab-pane <?php echo ($currentTab === 'all' || !$currentTab) ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="allTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-6 py-3">INVOICE ID</th>
                                    <th class="px-6 py-3">DEPARTMENT</th>
                                    <th class="px-6 py-3">TYPE</th>
                                    <th class="px-6 py-3">VENDOR NAME</th>
                                    <th class="px-6 py-3">AMOUNT</th>
                                    <th class="px-6 py-3">DUE DATE</th>
                                    <th class="px-6 py-3">STATUS</th>
                                    <th class="px-6 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="allTableBody">
                                <?php foreach ($allRows as $row): 
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                    $paidDateData = ($row['paid_date']) ? date('Y-m-d', strtotime($row['paid_date'])) : '';
                                    $statusClass = '';
                                    switch(strtolower($row['status'])) {
                                        case 'pending': $statusClass = 'bg-yellow-100 text-yellow-800'; break;
                                        case 'approved': $statusClass = 'bg-blue-100 text-blue-800'; break;
                                        case 'paid': $statusClass = 'bg-green-100 text-green-800'; break;
                                        case 'rejected': $statusClass = 'bg-red-100 text-red-800'; break;
                                        case 'archived': $statusClass = 'bg-gray-100 text-gray-800'; break;
                                        default: $statusClass = 'bg-gray-100 text-gray-800';
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>"
                                        data-due="<?php echo $paymentDue; ?>"
                                        data-paid="<?php echo $paidDateData; ?>">
                                        <td class='px-6 py-3 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                        </td>
                                        <td class='px-6 py-3 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='px-6 py-3 text-gray-600'><?php echo $paymentDue;?></td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold <?php echo $statusClass; ?>">
                                                <?php echo strtoupper($row['status']);?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3 text-center'>
                                            <?php 
                                            $jsonData = htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); 
                                            $itemStatus = strtolower($row['status'] ?? '');
                                            
                                            if ($itemStatus === 'pending'): ?>
                                                <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'>
                                                    <i class="fas fa-eye text-xs"></i>
                                                    Review
                                                </button>
                                            <?php elseif ($itemStatus === 'rejected'): ?>
                                                <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-red-50 text-red-700 text-xs font-bold rounded-md hover:bg-red-100 transition-all flex items-center gap-1 mx-auto'>
                                                    <i class="fas fa-eye text-xs"></i>
                                                    Details
                                                </button>
                                            <?php else: ?>
                                                <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-md hover:bg-green-100 transition-all flex items-center gap-1 mx-auto'>
                                                    <i class="fas fa-eye text-xs"></i>
                                                    Details
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination for All -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="allPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="allPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('all')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="allNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('all')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="pendingTab" class="tab-pane <?php echo $currentTab === 'pending' ? 'active' : ''; ?>">
                    <!-- Batch Action Buttons for Pending -->
                    <div id="pendingBatchActions" class="flex justify-between items-center mb-4 hidden">
                        <div class="flex items-center space-x-4">
                            <input type="checkbox" id="selectAllPending" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500">
                            <label for="selectAllPending" class="text-sm font-medium text-gray-700">Select All</label>
                            
                            <button id="batchApproveBtn" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-all font-bold text-sm">
                                <i class="fas fa-check-circle mr-2"></i>Approve Selected
                            </button>
                            
                            <button id="batchRejectBtn" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-all font-bold text-sm">
                                <i class="fas fa-times-circle mr-2"></i>Reject Selected
                            </button>

                            <button id="cancelSelectionPending" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-all font-bold text-sm">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="pendingTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="pl-10 pr-6 py-3 checkbox-cell">Select</th>
                                    <th class="px-6 py-3">INVOICE ID</th>
                                    <th class="px-6 py-3">DEPARTMENT</th>
                                    <th class="px-6 py-3">TYPE</th>
                                    <th class="px-6 py-3">VENDOR NAME</th>
                                    <th class="px-6 py-3">MODE</th>
                                    <th class="px-6 py-3">AMOUNT</th>
                                    <th class="px-6 py-3">DUE DATE</th>
                                    <th class="px-6 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="pendingTableBody">
                                <?php foreach ($pendingRows as $row): 
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors" 
                                        data-mode="<?php echo strtolower($row['payment_method']); ?>"
                                        data-invoiceid="<?php echo htmlspecialchars($row['invoice_id']); ?>"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>"
                                        data-due="<?php echo $paymentDue; ?>">
                                        <td class='pl-10 pr-6 py-3 checkbox-cell'>
                                            <input type="checkbox" name="pending_ids[]" value="<?php echo $row['invoice_id']; ?>" class="pending-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500">
                                        </td>
                                        <td class='px-6 py-3 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-building text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                            </div>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                                <?php echo $row['payment_method'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='px-6 py-3 text-gray-600'><?php echo $paymentDue;?></td>
                                        <td class='px-6 py-3 text-center'>
                                            <?php $jsonData = htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                            <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'>
                                                <i class="fas fa-eye text-xs"></i>
                                                Review
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($pendingRows) === 0): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-inbox text-4xl mb-3"></i>
                                <p class="text-sm font-medium">No pending vendor invoices found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="pendingPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="pendingPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('pending')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="pendingNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('pending')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="approvedTab" class="tab-pane <?php echo $currentTab === 'approved' ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="approvedTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-6 py-3">INVOICE ID</th>
                                    <th class="px-6 py-3">DEPARTMENT</th>
                                    <th class="px-6 py-3">TYPE</th>
                                    <th class="px-6 py-3">VENDOR NAME</th>
                                    <th class="px-6 py-3">MODE</th>
                                    <th class="px-6 py-3">AMOUNT</th>
                                    <th class="px-6 py-3">STATUS</th>
                                    <th class="px-6 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="approvedTableBody">
                                <?php foreach ($approvedRows as $row): 
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>"
                                        data-due="<?php echo $paymentDue; ?>">
                                        <td class='px-6 py-3 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-building text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                            </div>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                                <?php echo $row['payment_method'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='px-6 py-3 text-yellow-600 font-bold italic text-xs'>
                                            <i class="fas fa-clock mr-1"></i>Awaiting Disbursement
                                        </td>
                                        <td class='px-6 py-3 text-center'>
                                            <?php $jsonData = htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                            <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-purple-50 text-purple-700 text-xs font-bold rounded-md hover:bg-purple-100 transition-all flex items-center gap-1 mx-auto'>
                                                <i class="fas fa-eye text-xs"></i>
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($approvedRows) === 0): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-check-circle text-4xl mb-3"></i>
                                <p class="text-sm font-medium">No approved vendor invoices found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="approvedPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="approvedPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('approved')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="approvedNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('approved')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="paidTab" class="tab-pane <?php echo $currentTab === 'paid' ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="paidTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-6 py-3">INVOICE ID</th>
                                    <th class="px-6 py-3">DEPARTMENT</th>
                                    <th class="px-6 py-3">TYPE</th>
                                    <th class="px-6 py-3">VENDOR NAME</th>
                                    <th class="px-6 py-3">AMOUNT</th>
                                    <th class="px-6 py-3">PAID DATE</th>
                                    <th class="px-6 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="paidTableBody">
                                <?php foreach ($paidRows as $row): 
                                    $paidDateFull = $row['paid_date'] ? date('Y-m-d H:i', strtotime($row['paid_date'])) : '-';
                                    $paidDateData = $row['paid_date'] ? date('Y-m-d', strtotime($row['paid_date'])) : '';
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>"
                                        data-due="<?php echo $paymentDue; ?>"
                                        data-paid="<?php echo $paidDateData; ?>">
                                        <td class='px-6 py-3 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='px-6 py-3'>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-building text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                            </div>
                                        </td>
                                        <td class='px-6 py-3 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='px-6 py-3 text-green-600 font-bold'><?php echo $paidDateFull;?></td>
                                        <td class='px-6 py-3 text-center'>
                                            <?php $jsonData = htmlspecialchars(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                            <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='px-3 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-md hover:bg-green-100 transition-all flex items-center gap-1 mx-auto'>
                                                <i class="fas fa-eye text-xs"></i>
                                                Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($paidRows) === 0): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-money-check-alt text-4xl mb-3"></i>
                                <p class="text-sm font-medium">No paid vendor invoices found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="paidPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="paidPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('paid')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="paidNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('paid')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="rejectedTab" class="tab-pane <?php echo $currentTab === 'rejected' ? 'active' : ''; ?>">
                    <!-- Batch Archive Button for Rejected -->
                    <div id="rejectedBatchActions" class="flex justify-between items-center mb-4 hidden">
                        <div class="flex items-center space-x-4">
                            <input type="checkbox" id="selectAllRejected" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500">
                            <label for="selectAllRejected" class="text-sm font-medium text-gray-700">Select All</label>
                            
                            <button id="batchArchiveBtn" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition-all font-bold text-sm">
                                <i class="fas fa-archive mr-2"></i>Archive Selected
                            </button>

                            <button id="cancelSelectionRejected" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-all font-bold text-sm">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="rejectedTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-4 py-3 checkbox-cell">Select</th>
                                    <th class="px-4 py-3">INVOICE ID</th>
                                    <th class="px-4 py-3">DEPARTMENT</th>
                                    <th class="px-4 py-3">TYPE</th>
                                    <th class="px-4 py-3">VENDOR NAME</th>
                                    <th class="px-4 py-3">MODE</th>
                                    <th class="px-4 py-3">AMOUNT</th>
                                    <th class="px-4 py-3">REASON</th>
                                    <th class="px-4 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="rejectedTableBody">
                                <?php foreach ($rejectedRows as $row): 
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>"
                                        data-due="<?php echo $paymentDue; ?>">
                                        <td class='py-3 px-4 checkbox-cell'>
                                            <input type="checkbox" name="rejected_ids[]" value="<?php echo $row['id']; ?>" class="rejected-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500">
                                        </td>
                                        <td class='py-3 px-4 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='py-3 px-4'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='py-3 px-4'>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-building text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                            </div>
                                        </td>
                                        <td class='py-3 px-4'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                                <?php echo $row['payment_method'];?>
                                            </span>
                                        </td>
                                        <td class='py-3 px-4 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='py-3 px-4 text-red-600 truncate max-w-[200px]' title="<?php echo htmlspecialchars($row['rejected_reason']); ?>">
                                            <?php echo htmlspecialchars($row['rejected_reason']); ?>
                                        </td>
                                        <td class='px-4 py-3 text-center'>
                                            <div class="flex items-center justify-center gap-2">
                                                <?php $jsonData = htmlspecialchars(json_encode(array_merge($row, ['status' => 'Rejected']), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                                <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all' title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="archiveSingleRejected(<?php echo $row['id']; ?>)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-all" title="Archive">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($rejectedRows) === 0): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-times-circle text-4xl mb-3"></i>
                                <p class="text-sm font-medium">No rejected vendor invoices found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="rejectedPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="rejectedPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('rejected')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="rejectedNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('rejected')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Archived Invoices Tab -->
                <div id="archivedTab" class="tab-pane <?php echo $currentTab === 'archived' ? 'active' : ''; ?>">
                    <div class="table-container">
                        <table class="w-full table-auto bg-white" id="archivedTable">
                            <thead>
                                <tr class="text-purple-800 uppercase text-xs leading-normal text-left sticky top-0 bg-white shadow-sm">
                                    <th class="px-4 py-3">INVOICE ID</th>
                                    <th class="px-4 py-3">DEPARTMENT</th>
                                    <th class="px-4 py-3">TYPE</th>
                                    <th class="px-4 py-3">VENDOR NAME</th>
                                    <th class="px-4 py-3">MODE</th>
                                    <th class="px-4 py-3">AMOUNT</th>
                                    <th class="px-4 py-3">REASON</th>
                                    <th class="px-4 py-3 text-center">ACTION</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-900 text-sm font-light" id="archivedTableBody">
                                <?php foreach ($archivedRows as $row): 
                                    $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                                ?>
                                    <tr class="hover:bg-gray-50 border-b border-gray-100 transition-colors"
                                        data-dept="<?php echo htmlspecialchars($row['department']); ?>"
                                        data-type="<?php echo htmlspecialchars($row['vendor_type'] ?? 'Vendor'); ?>">
                                        <td class='py-3 px-4 font-medium text-gray-900'><?php echo $row['invoice_id'];?></td>
                                        <td class='py-3 px-4'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800">
                                                <?php echo $row['department'];?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                                <?php echo $row['vendor_type'] ?? 'Vendor';?>
                                            </span>
                                        </td>
                                        <td class='py-3 px-4'>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-building text-purple-600 text-xs"></i>
                                                </div>
                                                <span class="font-medium text-gray-900"><?php echo $row['vendor_name'];?></span>
                                            </div>
                                        </td>
                                        <td class='py-3 px-4'>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                                <?php echo $row['payment_method'];?>
                                            </span>
                                        </td>
                                        <td class='py-3 px-4 font-bold text-gray-900'>&#8369;<?= number_format($row['amount'], 2);?></td>
                                        <td class='py-3 px-4 text-red-600 truncate max-w-[200px]' title="<?php echo htmlspecialchars($row['rejected_reason']); ?>">
                                            <?php echo htmlspecialchars($row['rejected_reason']); ?>
                                        </td>
                                        <td class='px-4 py-3 text-center'>
                                            <div class="flex items-center justify-center gap-2">
                                                <?php $jsonData = htmlspecialchars(json_encode(array_merge($row, ['status' => 'Archived']), JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>
                                                <button onclick='openReviewModal(<?php echo $jsonData; ?>)' class='p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-all' title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($archivedRows) === 0): ?>
                            <div class="text-center py-12 text-gray-400">
                                <i class="fas fa-archive text-4xl mb-3"></i>
                                <p class="text-sm font-medium">No archived vendor invoices found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination for Archived Tab -->
                    <!-- Pagination for Archived Tab -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                        <div id="archivedPageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex items-center gap-2">
                            <button id="archivedPrevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="prevPage('archived')">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </button>
                            <button id="archivedNextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" onclick="nextPage('archived')">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <canvas id="pdf-viewer" width="600" height="400"></canvas>
        </div>
    </div>
</div>

<!-- Side-by-Side Review Modal (View Modal) -->
<!-- Review Invoice Modal (Premium Design) -->
<div id="viewModal" class="modal">
    <div class="modal-content !max-w-[1500px] !p-8 relative">
        <button onclick="closeModal('viewModal')" class="absolute top-4 right-4 text-gray-500 hover:text-purple-700 transition-colors">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <h2 class="text-2xl font-bold text-gray-800 mb-4 text-center border-b pb-4">Review Vendor Invoice</h2>
        
        <div class="grid grid-cols-[400px_1fr] gap-8">
            <!-- Left Column: Details & Budget -->
            <div>
                <!-- ID is now the header, styled purple -->
                <h3 class="text-lg font-semibold text-purple-700 mb-4" id="modalInvoiceId">INV-000000-0000</h3>
                
                <div id="reviewModalContent" class="space-y-3 text-sm">
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Vendor Name:</span>
                        <span class="font-bold text-gray-900" id="modalAccountName">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Department:</span>
                        <span class="text-gray-800" id="modalDepartment">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Type:</span>
                        <span class="text-gray-800" id="modalVendorType">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Address:</span>
                        <span class="text-gray-800" id="modalVendorAddress">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">GL Account:</span>
                        <span class="text-gray-800" id="modalGlAccount">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Invoice Date:</span>
                        <span class="text-gray-800" id="modalInvoiceDate">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Payment Due:</span>
                        <span class="text-gray-800" id="modalPaymentDue">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Status:</span>
                        <span id="modalStatus" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium">-</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="font-semibold text-gray-600">Payment Method:</span>
                        <span class="text-gray-800" id="modalPaymentMethod"></span>
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
                    <button onclick="approveInvoice()" class="flex-1 bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 inline-flex items-center justify-center font-bold text-sm">
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

<!-- Confirmation Modal for Approve -->
<div id="approveConfirmModal" class="modal">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
        <button onclick="closeModal('approveConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-green-50">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Confirm Approval</h2>
            <p class="text-gray-500 text-sm mt-1">Are you sure you want to approve this invoice?</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-4 mb-6 space-y-2 text-sm border border-gray-100">
            <div class="flex justify-between">
                <span class="text-gray-500 font-medium">Invoice ID:</span>
                <span class="font-bold text-gray-800" id="confirmApproveRefId">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 font-medium">Vendor:</span>
                <span class="font-bold text-gray-800" id="confirmApproveVendor">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 font-medium">Amount:</span>
                <span class="font-bold text-purple-700" id="confirmApproveAmount">-</span>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button id="confirmApproveBtn" class="flex-1 bg-green-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-green-700 transition-all shadow-md">
                Confirm Approve
            </button>
            <button onclick="closeModal('approveConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
                Cancel
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Return (Reject) -->
<div id="returnConfirmModal" class="modal">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 overflow-hidden">
        <button onclick="closeModal('returnConfirmModal')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-amber-50">
                <i class="fas fa-undo text-2xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900">Return for Revision</h2>
            <p class="text-gray-500 text-sm mt-1">Please provide a reason for the return.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2 text-sm border border-gray-100">
            <div class="flex justify-between">
                <span class="text-gray-500 font-medium">Invoice ID:</span>
                <span class="font-bold text-gray-800" id="confirmReturnRefId">-</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 font-medium">Vendor:</span>
                <span class="font-bold text-gray-800" id="confirmReturnVendor">-</span>
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
            <button id="confirmReturnBtn" class="flex-1 bg-amber-600 text-white px-4 py-3 rounded-xl font-bold hover:bg-amber-700 transition-all shadow-md">
                Confirm Return
            </button>
            <button onclick="closeModal('returnConfirmModal')" class="flex-1 bg-gray-100 text-gray-600 px-4 py-3 rounded-xl font-bold hover:bg-gray-200 transition-all">
                Cancel
            </button>
        </div>
    </div>
</div>



<!-- Toast Notification -->
<div id="toastContainer" class="toast-container"></div>

<script>
// ========== UTILITIES & STATE ==========
let vendorDashboardState = { all: 1, pending: 1, approved: 1, paid: 1, rejected: 1, archived: 1 };
const VENDOR_PAGE_SIZE = 10;
let currentInvoiceData = null;

function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div class="toast ${type}" id="${toastId}">
            <div class="flex items-center">
                <i class="toast-icon ${type === 'success' ? 'fas fa-check-circle' : type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-info-circle'} mr-3"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close ml-4" onclick="removeToast('${toastId}')">&times;</button>
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



// ========== NEW INVOICE MODAL & FILE HANDLING ==========


// ========== MODAL CONTROLS ==========
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'block'; // Fallback
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none'; // Fallback
        if (!document.querySelector('.modal.active[style*="display: block"]')) {
            document.body.style.overflow = '';
        }
    }
}

// ========== TAB SYSTEM ==========
function handleTabChoice(tabChoice) {
    console.log('Switching to tab:', tabChoice);
    const formData = new FormData();
    formData.append('current_tab', tabChoice);
    fetch('vendor.php', { method: 'POST', body: formData })
        .then(() => {
            window.location.reload();
        })
        .catch(err => {
            window.location.reload(); 
        });
}

// ========== REVIEW MODAL LOGIC ==========
function openReviewModal(data) {
    currentInvoiceData = data;
    openModal('viewModal');

    // Populate fields
    document.getElementById('modalInvoiceId').textContent = data.invoice_id || '-';
    document.getElementById('modalAccountName').textContent = data.vendor_name || '-';
    document.getElementById('modalDepartment').textContent = data.department || '-';
    document.getElementById('modalVendorType').textContent = data.vendor_type || '-';
    document.getElementById('modalVendorAddress').textContent = data.vendor_address || '-';
    document.getElementById('modalGlAccount').textContent = data.gl_account || '-';
    document.getElementById('modalInvoiceDate').textContent = data.invoice_date ? data.invoice_date.split(' ')[0] : '-';
    document.getElementById('modalPaymentDue').textContent = data.payment_due ? data.payment_due.split(' ')[0] : '-';
    document.getElementById('modalPaymentMethod').textContent = data.payment_method || '-';
    document.getElementById('modalAmount').textContent = '₱' + (parseFloat(data.amount) || 0).toLocaleString('en-PH', {minimumFractionDigits:2});
    document.getElementById('modalDescription').textContent = data.description || 'No description provided';
    
    const statusBadge = document.getElementById('modalStatus');
    let status = data.status || 'Pending';
    statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
    
    let statusClass = '';
    const statusLower = status.toLowerCase();
    
    switch(statusLower) {
        case 'pending': statusClass = 'bg-yellow-100 text-yellow-800'; break;
        case 'approved': statusClass = 'bg-green-100 text-green-800'; break;
        case 'rejected': statusClass = 'bg-red-100 text-red-800'; break;
        case 'archived': statusClass = 'bg-gray-100 text-gray-800'; break;
        default: statusClass = 'bg-gray-100 text-gray-800';
    }
    statusBadge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ' + statusClass;

    // Show/Hide Action Buttons based on status
    const actionButtons = document.getElementById('actionButtons');
    const returnedNotesSection = document.getElementById('returnedNotesSection');
    
    if (statusLower === 'pending') {
        actionButtons.style.display = 'flex';
        returnedNotesSection.style.display = 'none';
        // Auto-fetch budget for pending items
        fetchBudgetAllocation(data.department, data.amount);
    } else if (statusLower === 'rejected') {
        actionButtons.style.display = 'none';
        returnedNotesSection.style.display = 'block';
        document.getElementById('modalReturnNotes').textContent = data.rejected_reason || data.reason || 'No return notes provided.';
        
        // Clear budget section for non-pending
        document.getElementById('budgetAllocationContent').innerHTML = '<div class="text-center text-xs text-gray-500 py-4 italic">No budget info needed for rejected items</div>';
    } else {
        actionButtons.style.display = 'none';
        returnedNotesSection.style.display = 'none';
        
        // Clear budget section for non-pending
        document.getElementById('budgetAllocationContent').innerHTML = '<div class="text-center text-xs text-gray-500 py-4 italic">No budget info needed for approved/paid items</div>';
    }

    // Handle Documents
    const pdfIframe = document.getElementById('pdfFrame');
    const pdfPlaceholder = document.getElementById('pdfViewerPlaceholder');
    const trayContainer = document.getElementById('receiptsTray');
    const listContainer = document.getElementById('receiptsList');
    const docCount = document.getElementById('receiptsCount');
    
    let documents = [];
    try {
        if (data.document) {
            if (data.document.trim().startsWith('[') || data.document.trim().startsWith('{')) {
                documents = JSON.parse(data.document);
            } else {
                documents = [data.document];
            }
        }
    } catch (e) {
        console.error("Error parsing documents:", e);
        documents = data.document ? [data.document] : [];
    }

    if (documents.length > 0) {
        trayContainer.classList.remove('hidden');
        if (docCount) docCount.textContent = documents.length + ' FILES ATTACHED';
        
        // Auto-open first viewable file
        const viewableFile = documents.find(doc => ['pdf', 'jpg', 'jpeg', 'png'].includes(doc.split('.').pop().toLowerCase()));
        
        let trayHtml = '';
        documents.forEach((doc, idx) => {
            const ext = doc.split('.').pop().toLowerCase();
            const isPdf = ext === 'pdf';
            const icon = isPdf ? 'fa-file-pdf text-red-500' : 'fa-file-image text-purple-500';
            const name = doc.split('/').pop();
            const shortName = name.length > 15 ? name.substring(0, 12) + '...' : name;

            trayHtml += `
                 <div class="receipt-tab-item px-2 py-1 border border-gray-700 rounded bg-gray-800/50 cursor-pointer hover:bg-gray-700 hover:border-gray-500 transition-all flex items-center gap-2 group max-w-[140px]" 
                     onclick="switchDocument('${doc}', this)"
                     title="${name}">
                    <i class="fas ${icon} text-xs group-hover:scale-110 transition-transform"></i>
                    <span class="text-[10px] text-gray-400 group-hover:text-gray-200 truncate font-medium select-none">${shortName}</span>
                </div>
            `;
        });
        listContainer.innerHTML = trayHtml;
        
        // Open the viewable file, or just the first one
        const initialDoc = viewableFile || documents[0];
        // find the tab element
        const tabs = listContainer.getElementsByClassName('receipt-tab-item');
        // Simple search for the tab that matches
        for(let tab of tabs) {
            if(tab.getAttribute('title') === initialDoc.split('/').pop()) {
                switchDocument(initialDoc, tab);
                break;
            }
        }
        
    } else {
        pdfIframe.classList.add('hidden');
        pdfPlaceholder.classList.remove('hidden');
        trayContainer.classList.add('hidden');
    }
    
    // Documents handled below
}

function switchDocument(doc, tabElement) {
    const pdfIframe = document.getElementById('pdfFrame');
    const pdfPlaceholder = document.getElementById('pdfViewerPlaceholder');
    
    if (!doc) return;

    // Check if it's a full URL
    if (doc.startsWith('http://') || doc.startsWith('https://')) {
        pdfIframe.src = doc;
    } else {
        // Extract filename for view_pdf.php
        const fileName = doc.split('/').pop();
        pdfIframe.src = `view_pdf.php?file=${encodeURIComponent(fileName)}`;
    }
    
    pdfIframe.classList.remove('hidden');
    pdfPlaceholder.classList.add('hidden');
    
    // Update active tab style
    document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('bg-gray-700', 'border-gray-500', 'ring-1', 'ring-purple-500'));
    if (tabElement) {
        tabElement.classList.add('bg-gray-700', 'border-gray-500', 'ring-1', 'ring-purple-500');
    }
}

// switchDocument is now replaced above.

function fetchBudgetAllocation(department, amount) {
    const container = document.getElementById('budgetAllocationContent');
    container.innerHTML = `<div class="flex justify-center py-4"><i class="fas fa-spinner fa-spin text-purple-600 text-xl"></i></div>`;
    
    console.log("Budget: Fetching starting for", department, amount);
    
    const formData = new FormData();
    formData.append('action', 'get_budget');
    formData.append('department', department);

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // Increased to 15s

    fetch('vendor.php', { method: 'POST', body: formData, signal: controller.signal })
        .then(res => {
            clearTimeout(timeoutId);
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(text => {
            console.log("Budget: Raw Response ->", text.substring(0, 100) + (text.length > 100 ? '...' : ''));
            try {
                const data = JSON.parse(text);
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
                        html += `<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-600 text-xs font-bold mb-4 w-full justify-center"><i class="fas fa-exclamation-triangle"></i> Insufficient Budget!</div>`;
                    } else {
                        html += `<div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-600 text-xs font-bold mb-4 w-full justify-center"><i class="fas fa-check-circle"></i> Sufficient Budget</div>`;
                    }

                    html += `
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm"><span>Allocated:</span><span class="font-bold">₱${allocated.toLocaleString(undefined, {minimumFractionDigits:2})}</span></div>
                            <div class="flex justify-between text-sm"><span>Current Spent:</span><span class="font-bold">₱${spent.toLocaleString(undefined, {minimumFractionDigits:2})} (${percentSpent.toFixed(1)}%)</span></div>
                            <div class="h-px bg-gray-200 my-2"></div>
                            <div class="flex justify-between text-sm"><span>After Approval:</span>
                                <div class="text-right">
                                    <div class="font-bold ${isOver ? 'text-red-600' : 'text-green-600'}">\u20B1${newSpent.toLocaleString(undefined, {minimumFractionDigits:2})} (${newPercentSpent.toFixed(1)}%)</div>
                                    <div class="text-xs ${isOver ? 'text-red-500 font-bold' : 'text-gray-500'}">Rem: \u20B1${newRemaining.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                                </div>
                            </div>
                        </div>`;
                    container.innerHTML = html;
                    
                    const approveBtn = document.querySelector('#actionButtons button[onclick^="approveInvoice"]');
                    if (approveBtn) {
                        if (isOver) {
                            approveBtn.disabled = true;
                            approveBtn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                            approveBtn.title = "Insufficient Budget";
                        } else {
                            approveBtn.disabled = false;
                            approveBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-400');
                            approveBtn.title = "Approve Invoice";
                        }
                    }
                } else {
                    container.innerHTML = `<div class="text-center text-xs text-gray-500 py-4 italic"><i class="fas fa-info-circle mr-1"></i> ${data.message || 'No budget found'}</div>`;
                }
            } catch (e) {
                console.error("Budget: Parse Error ->", e, text);
                throw new Error('Invalid server response (check console)');
            }
        })
        .catch(err => {
            clearTimeout(timeoutId);
            console.error("Budget: Fetch Error ->", err);
            container.innerHTML = `<div class="text-center text-xs text-red-500 py-4 italic font-bold">
                <i class="fas fa-exclamation-circle mr-1"></i> ${err.name === 'AbortError' ? 'Timeout reaching server' : 'Error loading budget'}
            </div>`;
        });
}

// ========== CORE ACTIONS ==========
function approveInvoice() {
    if (!currentInvoiceData) return;
    
    document.getElementById('confirmApproveRefId').textContent = currentInvoiceData.invoice_id;
    document.getElementById('confirmApproveVendor').textContent = currentInvoiceData.vendor_name;
    document.getElementById('confirmApproveAmount').textContent = '\u20B1' + parseFloat(currentInvoiceData.amount).toLocaleString('en-PH', {minimumFractionDigits:2});
    
    openModal('approveConfirmModal');
    
    // Bind click
    document.getElementById('confirmApproveBtn').onclick = function() {
        submitStatusUpdate('approve', currentInvoiceData.invoice_id);
    };
}

function openReturnConfirmation() {
    if (!currentInvoiceData) return;
    
    document.getElementById('confirmReturnRefId').textContent = currentInvoiceData.invoice_id;
    document.getElementById('confirmReturnVendor').textContent = currentInvoiceData.vendor_name;
    document.getElementById('confirmReturnAmount').textContent = '\u20B1' + parseFloat(currentInvoiceData.amount).toLocaleString('en-PH', {minimumFractionDigits:2});
    
    openModal('returnConfirmModal');
    
    // Bind click
    document.getElementById('confirmReturnBtn').onclick = function() {
        const reason = document.getElementById('returnNotes').value.trim();
        if (!reason) {
            showToast('Please provide a reason for return', 'error');
            return;
        }
        submitStatusUpdate('reject', currentInvoiceData.invoice_id, reason);
    };
}

function archiveSingleRejected(id) {
    if (confirm('Move this to archives?')) {
        submitStatusUpdate('archive', null, null, [id]);
    }
}

function submitStatusUpdate(action, invoiceId, reason = null, bulkIds = null) {
    const formData = new FormData();
    formData.append('action', action);
    if (invoiceId) formData.append('invoice_id', invoiceId);
    if (reason) formData.append('reason', reason);
    if (bulkIds) {
        bulkIds.forEach(id => formData.append('archive_ids[]', id));
    }

    const btn = event?.target?.closest('button') || document.activeElement;
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch('vendor.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Operation successful!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Operation failed!', 'error');
                btn.innerHTML = orig;
                btn.disabled = false;
            }
        });
}

// ========== FILTERING & PAGINATION ==========
function filterTable() {
    const tabPane = document.querySelector('.tab-pane.active');
    if (!tabPane) return;
    const tab = tabPane.id.replace('Tab', '');
    if (vendorDashboardState) vendorDashboardState[tab] = 1;
    renderTable(tab);
}

function renderTable(tab) {
    const searchInput = document.getElementById('searchInput');
    const search = searchInput ? searchInput.value.toLowerCase() : '';
    const dept = document.getElementById('department_filter').value;
    const type = document.getElementById('type_filter').value;
    const due = document.getElementById('due_date_filter').value;
    const paid = document.getElementById('paid_date_filter').value;
    
    const tableBody = document.getElementById(tab + 'TableBody');
    if (!tableBody) return;
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    
    const filteredRows = allRows.filter(row => {
        const text = row.innerText.toLowerCase();
        const rDept = row.getAttribute('data-dept');
        const rType = row.getAttribute('data-type');
        const rDue = row.getAttribute('data-due');
        const rPaid = row.getAttribute('data-paid');

        const mSearch = !search || text.includes(search);
        const mDept = !dept || rDept === dept;
        const mType = !type || rType === type;
        const mDue = !due || rDue === due;
        const mPaid = !paid || rPaid === paid;

        return mSearch && mDept && mType && mDue && mPaid;
    });

    const total = filteredRows.length;
    const rowsPerPg = VENDOR_PAGE_SIZE || 10;
    let currPg = parseInt(vendorDashboardState[tab]) || 1;
    
    const pages = Math.ceil(total / rowsPerPg);
    if (currPg > pages && pages > 0) currPg = pages;
    if (currPg < 1) currPg = 1;
    vendorDashboardState[tab] = currPg;

    const start = (currPg - 1) * rowsPerPg;
    const end = start + rowsPerPg;

    allRows.forEach(row => row.style.display = 'none');
    filteredRows.slice(start, end).forEach(row => row.style.display = '');

    const statusElement = document.getElementById(tab + 'PageStatus');
    if (statusElement) {
        if (total > 0) {
            statusElement.textContent = `Showing ${start + 1}-${Math.min(end, total)} of ${total} entries`;
        } else {
            statusElement.textContent = 'No results found';
        }
    }
    
    const prev = document.getElementById(tab + 'PrevPage');
    const next = document.getElementById(tab + 'NextPage');
    if (prev) prev.disabled = currPg <= 1;
    if (next) next.disabled = currPg >= pages || pages === 0;
}

function prevPage(tab) {
    if (vendorDashboardState[tab] > 1) {
        vendorDashboardState[tab]--;
        renderTable(tab);
    }
}

function nextPage(tab) {
    const tableBody = document.getElementById(tab + 'TableBody');
    if (!tableBody) return;
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const total = allRows.length; // Simplified for bounds check
    const pages = Math.ceil(total / VENDOR_PAGE_SIZE);
    if (vendorDashboardState[tab] < pages) {
        vendorDashboardState[tab]++;
        renderTable(tab);
    }
}

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    // Tab Listeners
    document.querySelectorAll('.request-type-tab').forEach(btn => {
        btn.onclick = () => handleTabChoice(btn.getAttribute('data-tab'));
    });

    // Close modals on background click
    window.onclick = (e) => {
        if (e.target.classList.contains('modal')) closeModal(e.target.id);
    };

    // Bulk Mode Toggles
    const setupBulkToggle = (btnId, cancelId, tabId) => {
        const btn = document.getElementById(btnId);
        const cancel = document.getElementById(cancelId);
        const tab = document.getElementById(tabId + 'Tab');
        const actions = document.getElementById(tabId + 'BatchActions');
        const table = document.getElementById(tabId + 'Table');

        if (btn) btn.onclick = () => {
            table.classList.add('selection-mode');
            actions.classList.remove('hidden');
            btn.classList.add('hidden');
            if (cancel) cancel.classList.remove('hidden');
        };

        if (cancel) cancel.onclick = () => {
            table.classList.remove('selection-mode');
            actions.classList.add('hidden');
            btn.classList.remove('hidden');
            // Uncheck all
            table.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        };
    };

    setupBulkToggle('enableSelectionPending', 'cancelSelectionPending', 'pending');
    setupBulkToggle('enableSelectionRejected', 'cancelSelectionRejected', 'rejected');

    // Select All Listeners
    const selectAllPending = document.getElementById('selectAllPending');
    if (selectAllPending) selectAllPending.onchange = (e) => {
        document.querySelectorAll('.pending-checkbox').forEach(cb => cb.checked = e.target.checked);
    };

    const selectAllRejected = document.getElementById('selectAllRejected');
    if (selectAllRejected) selectAllRejected.onchange = (e) => {
        document.querySelectorAll('.rejected-checkbox').forEach(cb => cb.checked = e.target.checked);
    };

    // Batch Action Listeners
    const batchApproveBtn = document.getElementById('batchApproveBtn');
    if (batchApproveBtn) batchApproveBtn.onclick = () => {
        const ids = Array.from(document.querySelectorAll('.pending-checkbox:checked')).map(cb => cb.value);
        if (ids.length && confirm(`Approve ${ids.length} invoices?`)) {
            // Processing bulk approval via individual or bulk action if backend supports it.
            // Current backend seems to support individual? No, I saw batch_approve in the old logic.
            // Let's use bulk logic.
            const formData = new FormData();
            formData.append('action', 'batch_approve');
            ids.forEach(id => formData.append('invoice_ids[]', id));
            
            fetch('vendor.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(data => data.success && location.reload());
        }
    };

    const batchRejectBtn = document.getElementById('batchRejectBtn');
    if (batchRejectBtn) batchRejectBtn.onclick = () => {
        const ids = Array.from(document.querySelectorAll('.pending-checkbox:checked')).map(cb => cb.value);
        if (!ids.length) return;
        const reason = prompt('Reason for batch rejection:');
        if (reason) {
            const formData = new FormData();
            formData.append('action', 'batch_reject');
            formData.append('reason', reason);
            ids.forEach(id => formData.append('invoice_ids[]', id));
            fetch('vendor.php', { method: 'POST', body: formData })
                .then(res => res.json()).then(data => data.success && location.reload());
        }
    };

    const batchArchiveBtn = document.getElementById('batchArchiveBtn');
    if (batchArchiveBtn) batchArchiveBtn.onclick = () => {
        const ids = Array.from(document.querySelectorAll('.rejected-checkbox:checked')).map(cb => cb.value);
        if (ids.length && confirm(`Archive ${ids.length} records?`)) {
            submitStatusUpdate('archive', null, null, ids);
        }
    };

    // Initial Render
    ['all', 'pending', 'approved', 'paid', 'rejected', 'archived'].forEach(renderTable);
});

function toggleFilterDropdown() {
    const dropdown = document.getElementById('filterDropdown');
    dropdown.classList.toggle('hidden');
}

function resetFilters() {
    document.getElementById('department_filter').value = '';
    document.getElementById('type_filter').value = '';
    document.getElementById('due_date_filter').value = '';
    document.getElementById('paid_date_filter').value = '';
    document.getElementById('searchInput').value = '';
    filterTable();
}

// Close dropdown when clicking outside
window.addEventListener('click', function(e) {
    const dropdown = document.getElementById('filterDropdown');
    const filterBtn = document.getElementById('filterBtn');
    if (dropdown && filterBtn && !filterBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

function toggleFullscreen() {
    const viewer = document.getElementById('pdfIframe');
    if (viewer.requestFullscreen) viewer.requestFullscreen();
    else if (viewer.webkitRequestFullscreen) viewer.webkitRequestFullscreen();
    else if (viewer.msRequestFullscreen) viewer.msRequestFullscreen();
}

/* AUTO-REFRESH LOGIC */
let autoRefreshInterval;

function startAutoRefresh() {
    // Refresh every 5 seconds
    autoRefreshInterval = setInterval(refreshTableData, 5000);
}

async function refreshTableData() {
    // 1. Checks to prevent refresh during active interaction
    if (document.hidden) return; // Tab not active
    
    // Check for open modals
    const openModals = document.querySelectorAll('.modal[style*="block"]');
    // Also check for 'active' class used in CSS
    const activeModals = document.querySelectorAll('.modal.active');
    if (openModals.length > 0 || activeModals.length > 0) return;
    
    // Check for bulk selection mode
    const selectionMode = document.querySelector('.selection-mode');
    if (selectionMode) return;
    
    // Check if any checkboxes are checked manually
    const checked = document.querySelectorAll('input[type="checkbox"]:checked');
    if (checked.length > 0) return;
    
    // 2. Identify Current Tab
    const activeTabPane = document.querySelector('.tab-pane.active');
    if (!activeTabPane) return;
    const currentTab = activeTabPane.id.replace('Tab', '');
    
    try {
        const response = await fetch(`vendor.php?action=fetch_latest_data&tab=${currentTab}`);
        const data = await response.json();
        
        if (data.success) {
            // 3. Update Table Body
            const tbodyId = currentTab + 'TableBody';
            const tbody = document.getElementById(tbodyId);
            if (tbody) {
                // Only update if content changed to avoid flicker/reflow if possible
                if (tbody.innerHTML !== data.html) {
                    tbody.innerHTML = data.html;
                    // Re-apply client-side pagination/filtering
                    renderTable(currentTab);
                }
            }
            
            // 4. Update Tab Counts
            updateTabCounts(data.counts);
        }
    } catch (e) {
        console.error("Auto-refresh failed:", e);
    }
}

function updateTabCounts(counts) {
    if (!counts) return;
    
    const map = {
        'all': 'all',
        'pending': 'pending',
        'approved': 'approved',
        'paid': 'paid',
        'rejected': 'rejected',
        'archived': 'archived'
    };
    
    for (const [key, tabName] of Object.entries(map)) {
        const count = counts[key];
        // Find the tab button (handling both active and inactive states)
        const btn = document.querySelector(`button[data-tab="${tabName}"] span`);
        if (btn) {
             btn.textContent = `(${count})`;
        }
    }
}

// Start auto-refresh on load
document.addEventListener('DOMContentLoaded', () => {
    startAutoRefresh();
});
</script>

<!-- Bulk Approve Modal -->
<div id="bulkApproveModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(5px);">
    <div class="modal-content" style="background-color:white; border-radius:12px; box-shadow:0 10px 40px rgba(0,0,0,0.3); max-height:90vh; overflow-y:auto; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); width:95%; max-width:900px;">
        <button onclick="closeModal('bulkApproveModal')" style="position:absolute; top:16px; right:16px; background:none; border:none; color:#9ca3af; cursor:pointer; z-index:10;">
            <i class="fas fa-times text-2xl"></i>
        </button>
        
        <div style="padding:32px;">
            <div style="margin-bottom:24px;">
                <h2 style="font-size:24px; font-weight:bold; color:#111827; display:flex; align-items:center;">
                    <i class="fas fa-check-double" style="color:#10b981; margin-right:12px;"></i>
                    Bulk Approve Invoices
                </h2>
                <p style="color:#6b7280; font-size:14px; margin-top:4px;">Select the invoices you want to approve as a batch.</p>
            </div>

            <div style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-bottom:24px;">
                <div style="max-height:400px; overflow-y:auto;">
                    <table style="width:100%; font-size:14px; text-align:left;">
                        <thead style="background-color:#f9fafb; color:#6b7280; text-transform:uppercase; font-size:12px; position:sticky; top:0;">
                            <tr>
                                <th style="padding:12px 24px;">
                                    <input type="checkbox" id="selectAllBulk" onchange="toggleAllBulk(this)" style="border-radius:4px;">
                                </th>
                                <th style="padding:12px 16px;">Invoice ID</th>
                                <th style="padding:12px 16px;">Vendor</th>
                                <th style="padding:12px 16px;">Department</th>
                                <th style="padding:12px 16px; text-align:right;">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="bulkApproveList" style="border-top:1px solid #e5e7eb;">
                            <!-- Loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="background-color:#f9fafb; padding:24px; border-radius:12px; border:1px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
                <div style="color:#374151;">
                    <div style="font-size:14px; font-weight:500;">Total Amount Selected:</div>
                    <div id="bulkTotalAmount" style="font-size:24px; font-weight:bold; color:#10b981;">\u20B10.00</div>
                    <div style="font-size:12px; color:#6b7280; margin-top:4px;"><span id="bulkSelectedCount">0</span> items selected</div>
                </div>
                <div style="display:flex; gap:12px;">
                    <button onclick="submitBulkApproval()" id="bulkApproveSubmitBtn" disabled style="padding:12px 32px; background-color:#10b981; color:white; font-weight:bold; border-radius:12px; border:none; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.1); display:flex; align-items:center; gap:8px;">
                        <i class="fas fa-check-circle"></i>
                        Approve Selected
                    </button>
                    <button onclick="closeModal('bulkApproveModal')" style="padding:12px 24px; background-color:#e5e7eb; color:#374151; font-weight:bold; border-radius:12px; border:none; cursor:pointer;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Approve Confirmation Modal -->
<div id="bulkApproveConfirmModal" class="modal" style="display:none; position:fixed; z-index:1001; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(5px);">
    <div style="background-color:white; padding:32px; border-radius:16px; box-shadow:0 20px 60px rgba(0,0,0,0.3); width:450px; position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); overflow:hidden;">
        <button onclick="closeModal('bulkApproveConfirmModal')" style="position:absolute; top:16px; right:16px; background:none; border:none; color:#9ca3af; cursor:pointer;">
            <i class="fas fa-times text-xl"></i>
        </button>
        <div style="text-align:center; margin-bottom:24px;">
            <div style="width:64px; height:64px; background-color:#d1fae5; color:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                <i class="fas fa-check-double text-2xl"></i>
            </div>
            <h2 style="font-size:20px; font-weight:bold; color:#111827;">Confirm Bulk Approval</h2>
            <p style="color:#6b7280; font-size:14px; margin-top:4px;">Review the batch before confirming.</p>
        </div>
        
        <div style="background-color:#f9fafb; border-radius:12px; padding:16px; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:8px;">
                <span style="color:#6b7280; font-weight:500;">Selected Items:</span>
                <span id="confirmBulkCount" style="font-weight:bold; color:#374151;">0</span>
            </div>
            <div style="display:flex; justify-content:space-between; border-top:1px solid #f3f4f6; padding-top:8px;">
                <span style="color:#6b7280; font-weight:500; font-size:14px;">Total Amount:</span>
                <span id="confirmBulkTotal" style="font-weight:bold; color:#10b981; font-size:18px;">\u20B10.00</span>
            </div>
        </div>
        
        <div style="display:flex; gap:12px;">
            <button onclick="bulkApproveConfirmed()" id="bulkConfirmSubmitBtn" style="flex:1; background-color:#10b981; color:white; padding:12px 16px; border-radius:12px; font-weight:bold; border:none; cursor:pointer; box-shadow:0 4px 6px rgba(0,0,0,0.1); font-size:14px;">
                Confirm Batch Approval
            </button>
            <button onclick="closeModal('bulkApproveConfirmModal')" style="flex:1; background-color:#f3f4f6; color:#6b7280; padding:12px 16px; border-radius:12px; font-weight:bold; border:none; cursor:pointer; font-size:14px;">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
// Bulk approve functions
function openBulkApproveModal() {
    const bulkList = document.getElementById('bulkApproveList');
    bulkList.innerHTML = '<tr><td colspan="5" style="padding:48px 24px; text-align:center;"><i class="fas fa-spinner fa-spin" style="color:#10b981; font-size:24px; margin-bottom:8px;"></i><p style="font-size:12px; color:#6b7280;">Fetching pending invoices...</p></td></tr>';
    
    document.getElementById('bulkTotalAmount').textContent = '\u20B10.00';
    document.getElementById('bulkSelectedCount').textContent = '0';
    document.getElementById('bulkApproveSubmitBtn').disabled = true;
    
    openModal('bulkApproveModal');
    
    fetch('vendor.php?action=get_pending_bulk')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    html += `
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 24px;">
                                <input type="checkbox" name="bulk_ids[]" value="${item.invoice_id}" data-amount="${item.amount}" onchange="updateBulkTotal()" style="border-radius:4px;">
                            </td>
                            <td style="padding:12px 16px; font-family:monospace; font-size:12px;">${item.invoice_id}</td>
                            <td style="padding:12px 16px; font-weight:500;">${item.vendor_name}</td>
                            <td style="padding:12px 16px; color:#6b7280;">${item.department}</td>
                            <td style="padding:12px 16px; text-align:right; font-weight:bold;">\u20B1${parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });
                bulkList.innerHTML = html;
            } else {
                bulkList.innerHTML = '<tr><td colspan="5" style="padding:48px 24px; text-align:center; color:#6b7280; font-weight:500; font-style:italic;">No pending invoices found.</td></tr>';
            }
        })
        .catch(error => {
            bulkList.innerHTML = '<tr><td colspan="5" style="padding:48px 24px; text-align:center; color:#ef4444;">Error loading data</td></tr>';
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
    
    document.getElementById('bulkTotalAmount').textContent = '\u20B1' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('bulkSelectedCount').textContent = count;
    document.getElementById('bulkApproveSubmitBtn').disabled = count === 0;
}

function submitBulkApproval() {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one invoice to approve');
        return;
    }
    
    // Calculate total
    let total = 0;
    checkboxes.forEach(cb => {
        total += parseFloat(cb.getAttribute('data-amount'));
    });
    
    // Update confirmation modal
    document.getElementById('confirmBulkCount').textContent = checkboxes.length;
    document.getElementById('confirmBulkTotal').textContent = '\u20B1' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
    
    // Show confirmation modal
    openModal('bulkApproveConfirmModal');
}

function bulkApproveConfirmed() {
    const checkboxes = document.querySelectorAll('input[name="bulk_ids[]"]:checked');
    const invoice_ids = Array.from(checkboxes).map(cb => cb.value);
    const formData = new FormData();
    formData.append('action', 'bulk_approve');
    invoice_ids.forEach(id => formData.append('invoice_ids[]', id));
    
    fetch('vendor.php', {
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

function showToast(message, type) {
    // Create toast container if it doesn't exist
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; display:flex; flex-direction:column; gap:10px;';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? '#10b981' : '#ef4444';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    toast.style.cssText = `
        background-color: ${bgColor};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 400px;
        animation: slideIn 0.3s ease-out;
    `;
    
    toast.innerHTML = `
        <i class="fas ${icon}" style="font-size:20px;"></i>
        <span style="flex:1; font-size:14px; font-weight:500;">${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none; border:none; color:white; cursor:pointer; padding:0; font-size:18px;">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}

// Add CSS animation
if (!document.getElementById('toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

function openModal(id) {
    document.getElementById(id).style.display = 'block';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Modal auto-close when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('bulkApproveModal');
    if (modal && e.target === modal) {
        closeModal('bulkApproveModal');
    }
});
</script>


<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
</main> <!-- Closing main from sidebar.php -->
</div> <!-- Closing outer div from sidebar.php -->

</body>
</html>
<?php endif; ?>