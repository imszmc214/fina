<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ============================================================================
// AI PAYOUT VALIDATION - JAVASCRIPT VERSION
// ============================================================================
$ai_service_online = true; // JavaScript AI is always available
$ai_checker = null;

// Include accounting helper functions
require_once 'includes/accounting_functions.php';

try {
    // Load JavaScript-based AI checker
    $ai_checker_file = 'ai_payout_check_js.php';
    if (file_exists($ai_checker_file)) {
        require_once $ai_checker_file;
        
        // Check if class exists
        if (class_exists('JavaScriptAIPayoutChecker')) {
            $ai_checker = new JavaScriptAIPayoutChecker();
            $ai_service_online = $ai_checker->checkHealth();
        } else {
            error_log("AI Checker: JavaScriptAIPayoutChecker class not found.");
        }
    } else {
        error_log("AI Checker: File 'ai_payout_check_js.php' not found.");
    }
} catch (Exception $e) {
    error_log("AI Checker Initialization Error: " . $e->getMessage());
    $ai_service_online = true; // Still true because JS AI runs client-side
}
// ============================================================================

// AJAX: Get pending payouts for bulk approval (segregated by tab)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_bulk_payout') {
    include('connection.php');
    $type = $_GET['type'] ?? '';
    $response = ['success' => false, 'data' => []];
    
    if ($type === 'vendors') {
        $sql = "SELECT pa.id, pa.reference_id, pa.account_name, pa.requested_department, pa.amount, pa.expense_categories, pa.approved_date,                COALESCE(NULLIF(pa.vendor_address, ''), ap.vendor_address) as vendor_address 
                FROM pa 
                LEFT JOIN accounts_payable ap ON pa.reference_id = CONCAT('VEN-', ap.invoice_id)
                WHERE (pa.transaction_type NOT IN ('Payroll', 'Reimbursement', 'Driver Payout') OR pa.transaction_type IS NULL OR pa.transaction_type = '')
                AND (pa.expense_categories NOT IN ('Payroll', 'Reimbursement', 'Driver Payout') OR pa.expense_categories IS NULL OR pa.expense_categories = '')
                AND (pa.status IS NULL OR pa.status = 'Pending Disbursement' OR pa.status = '')
                GROUP BY pa.id
                ORDER BY pa.approved_date DESC, pa.id DESC";
        $stmt = $conn->prepare($sql);
    } elseif ($type === 'driver') {
        $sql = "SELECT id, reference_id, account_name, wallet_id, amount, expense_categories, approved_date                FROM pa 
                WHERE (transaction_type = 'Driver Payout' OR payout_type = 'Driver' OR source_module = 'Driver Payable')
                AND (status IS NULL OR status = 'Pending Disbursement' OR status = '')
                ORDER BY approved_date DESC, id DESC";
        $stmt = $conn->prepare($sql);
    } else {
        $transaction_type = ($type === 'reimbursement') ? 'Reimbursement' : 'Payroll';
        $sql = "SELECT id, reference_id, account_name, amount, expense_categories, employee_id, requested_department, approved_date                FROM pa 
                WHERE (transaction_type = ? OR expense_categories = ?)
                AND (status IS NULL OR status = 'Pending Disbursement' OR status = '')
                ORDER BY approved_date DESC, id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $transaction_type, $transaction_type);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    }
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX: Bulk Disburse Payouts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_disburse_payout') {
    include('connection.php');
    $ids = $_POST['ids'] ?? [];
    $response = ['success' => false, 'message' => ''];
    
    if (empty($ids)) {
        $response['message'] = "No items selected.";
    } else {
        $conn->begin_transaction();
        try {
            $approver_name = ($_SESSION['givenname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '');
            $approver_name = trim($approver_name) ?: 'Authorized Approver';
            $success_count = 0;
            
            foreach ($ids as $approveId) {
                $approveId = intval($approveId);
                
                // Fetch internal data for each ID
                $select_sql = "SELECT * FROM pa WHERE id = ?";
                $stmt_select = $conn->prepare($select_sql);
                $stmt_select->bind_param("i", $approveId);
                $stmt_select->execute();
                $payout_data = $stmt_select->get_result()->fetch_assoc();
                $stmt_select->close();
                
                if (!$payout_data) continue;
                
                $mode_of_payment = $payout_data['mode_of_payment'];
                $reference_id = $payout_data['reference_id'];
                $amount = $payout_data['amount'];
                $account_name = $payout_data['account_name'];
                $requested_department = $payout_data['requested_department'];
                $expense_categories = $payout_data['expense_categories'];
                $description = $payout_data['description'];
                $document = $payout_data['document'];
                $payment_due = $payout_data['payment_due'];
                $requested_at = $payout_data['requested_at'] ?? date('Y-m-d H:i:s');
                $bank_name = $payout_data['bank_name'] ?? '';
                $bank_account_number = $payout_data['bank_account_number'] ?? '';
                $bank_account_name = $payout_data['bank_account_name'] ?? '';
                $ecash_provider = $payout_data['ecash_provider'] ?? '';
                $ecash_account_name = $payout_data['ecash_account_name'] ?? '';
                $ecash_account_number = $payout_data['ecash_account_number'] ?? '';
                $from_payable = $payout_data['from_payable'] ?? 0;
                
                // ============================================================================
                // AI VALIDATION NOW HANDLED BY JAVASCRIPT (Client-Side)
                // ============================================================================
                // AI validation is now performed in the browser using TensorFlow.js
                // The JavaScript AI will block payouts before they reach this point
                // ============================================================================
                
                $new_reference_id = $reference_id;

                $status = 'disbursed';
                
                // Safe ID Workaround
                $next_dr_id = getNextAvailableId($conn, 'dr');
                
                // 1. Insert into dr
                $disbursed_sql = "INSERT INTO dr (
                    id, reference_id, account_name, requested_department, mode_of_payment,
                    expense_categories, amount, description, document, payment_due,
                    bank_name, bank_account_number, bank_account_name,
                    ecash_provider, ecash_account_name, ecash_account_number,
                    status, archived, source_type, approved_by, approved_at, approval_source
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'payout', ?, NOW(), 'Payout Bulk Disburse')";
                
                $stmt_disbursed = $conn->prepare($disbursed_sql);
                $stmt_disbursed->bind_param("isssssdsssssssssss", 
                    $next_dr_id,
                    $new_reference_id, $account_name, $requested_department, $mode_of_payment,
                    $expense_categories, $amount, $description, $document, $payment_due,
                    $bank_name, $bank_account_number, $bank_account_name,
                    $ecash_provider, $ecash_account_name, $ecash_account_number,
                    $status, $approver_name
                );
                
                if (!$stmt_disbursed->execute()) throw new Exception("DR Insert failed for ID $approveId");
                $stmt_disbursed->close();
                
                // 2. Payables Receipt
                if ($from_payable == 1) {
                    $next_receipt_id = getNextAvailableId($conn, 'payables_receipts');
                    
                    $receipt_sql = "INSERT INTO payables_receipts (
                        id, reference_id, account_name, requested_department, expense_categories,
                        mode_of_payment, amount, description, document, payment_due,
                        requested_at, bank_name, bank_account_name, bank_account_number,
                        ecash_provider, ecash_account_name, ecash_account_number,
                        disbursed_date, status, original_reference_id, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())";
                    
                    $stmt_receipt = $conn->prepare($receipt_sql);
                    $stmt_receipt->bind_param("isssssdssssssssssss",
                        $next_receipt_id,
                        $new_reference_id, $account_name, $requested_department, $expense_categories,
                        $mode_of_payment, $amount, $description, $document, $payment_due,
                        $requested_at, $bank_name, $bank_account_name, $bank_account_number,
                        $ecash_provider, $ecash_account_name, $ecash_account_number,
                        $status, $reference_id
                    );
                    $stmt_receipt->execute();
                    $stmt_receipt->close();
                }
                
                // 3. Delete from pa
                $conn->query("DELETE FROM pa WHERE id = $approveId");
                
                // 4. Update Source Tables
                if (strpos($reference_id, 'VEN-') === 0) {
                    $orig_id = str_replace('VEN-', '', $reference_id);
                    $conn->query("UPDATE accounts_payable SET status = 'Paid', paid_date = NOW() WHERE invoice_id = '$orig_id'");
                } elseif (strpos($reference_id, 'REIMB-') === 0) {
                    $orig_id = str_replace('REIMB-', '', $reference_id);
                    $conn->query("UPDATE reimbursements SET status = 'Paid', processed_date = NOW() WHERE report_id = '$orig_id' OR report_id = 'REIMB-$orig_id'");
                } elseif (strpos($reference_id, 'PAY-') === 0) {
                    $orig_id = str_replace('PAY-', '', $reference_id);
                    $conn->query("UPDATE payroll_records SET status = 'Paid' WHERE id = '$orig_id'");
                } elseif (strpos($reference_id, 'DRV-') === 0) {
                    $orig_id = str_replace('DRV-', '', $reference_id);
                    $conn->query("UPDATE driver_payouts SET status = 'Paid', paid_date = NOW() WHERE payout_id = '$orig_id'");
                }
                
                // 5. Update TR if exists
                $conn->query("UPDATE tr SET status = 'disbursed' WHERE reference_id = '$reference_id' AND status = 'approved'");
                
                $success_count++;
            }
            
            $conn->commit();
            $response['success'] = true;
            $response['message'] = "Successfully disbursed $success_count records.";
        } catch (Exception $e) {
            $conn->rollback();
            $response['message'] = $e->getMessage();
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Toast message handling
$toast_message = '';
$toast_type = ''; // success, error

// Approve Handler (PHP POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    include('connection.php');

    $approveId = intval($_POST['approve_id']);

    // Kunin ang lahat ng data mula sa pa table
    $select_sql = "SELECT * FROM pa WHERE id = ?";
    $stmt_select = $conn->prepare($select_sql);
    $stmt_select->bind_param("i", $approveId);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $payout_data = $result->fetch_assoc();
    $stmt_select->close();

    if (!$payout_data) {
        $toast_message = "Error: Payout record not found for ID $approveId.";
        $toast_type = 'error';
    } else {
        $mode_of_payment = $payout_data['mode_of_payment'];
        $reference_id = $payout_data['reference_id'];
        $amount = $payout_data['amount'];
        $account_name = $payout_data['account_name'];
        $requested_department = $payout_data['requested_department'];
        $expense_categories = $payout_data['expense_categories'];
        $description = $payout_data['description'] ?? '';
        $document = $payout_data['document'] ?? '';
        $payment_due = $payout_data['payment_due'] ?? '';
        $requested_at = $payout_data['requested_at'] ?? date('Y-m-d H:i:s');
        $bank_name = $payout_data['bank_name'] ?? '';
        $bank_account_number = $payout_data['bank_account_number'] ?? '';
        $bank_account_name = $payout_data['bank_account_name'] ?? '';
        $ecash_provider = $payout_data['ecash_provider'] ?? '';
        $ecash_account_name = $payout_data['ecash_account_name'] ?? '';
        $ecash_account_number = $payout_data['ecash_account_number'] ?? '';
        $from_payable = $payout_data['from_payable'] ?? 0;
        
        // ============================================================================
        // AI VALIDATION NOW HANDLED BY JAVASCRIPT (Client-Side)
        // ============================================================================
        // AI validation is now performed in the browser using TensorFlow.js
        // The JavaScript AI will block payouts before they reach this point
        // ============================================================================
                
        $new_reference_id = $reference_id;

        $conn->begin_transaction();
        
        try {
            $current_date = date('Y-m-d H:i:s');
            $status = 'disbursed';
            
            // 1. Ilagay sa disbursedrecords (dr) table
            $next_dr_id = getNextAvailableId($conn, 'dr');
            
            $disbursed_sql = "
            INSERT INTO dr (
                id, reference_id, account_name, requested_department, mode_of_payment,
                expense_categories, amount, description, document, payment_due,
                bank_name, bank_account_number, bank_account_name,
                ecash_provider, ecash_account_name, ecash_account_number,
                status, archived, source_type, approved_by, approved_at, approval_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'payout', ?, NOW(), 'Payout Single Disburse')
            ";
            
            $approver_name = ($_SESSION['givenname'] ?? '') . ' ' . ($_SESSION['surname'] ?? '');
            $approver_name = trim($approver_name) ?: 'Authorized Approver';

            $stmt_disbursed = $conn->prepare($disbursed_sql);
            $stmt_disbursed->bind_param("isssssdsssssssssss", 
                $next_dr_id,
                $new_reference_id,
                $account_name,
                $requested_department,
                $mode_of_payment,
                $expense_categories,
                $amount,
                $description,
                $document,
                $payment_due,
                $bank_name,
                $bank_account_number,
                $bank_account_name,
                $ecash_provider,
                $ecash_account_name,
                $ecash_account_number,
                $status,
                $approver_name
            );
            
            if (!$stmt_disbursed->execute()) {
                throw new Exception("Error inserting into dr table: " . $stmt_disbursed->error);
            }
            $dr_id = $conn->insert_id;
            $stmt_disbursed->close();
            
            // 2. Ilagay sa payables_receipts table - KUNG ITO AY PAYABLE (from_payable = 1)
            if ($from_payable == 1) {
                // Check if payables_receipts table exists
                $table_check = $conn->query("SHOW TABLES LIKE 'payables_receipts'");
                
                if ($table_check->num_rows == 0) {
                    // Create table with requested_at column
                    $create_table = "CREATE TABLE payables_receipts (
                        id INT(11) NOT NULL AUTO_INCREMENT,
                        reference_id VARCHAR(255) NOT NULL,
                        account_name VARCHAR(30) NOT NULL,
                        requested_department VARCHAR(255) NOT NULL,
                        expense_categories VARCHAR(255) NOT NULL,
                        mode_of_payment VARCHAR(255) NOT NULL,
                        amount BIGINT(24) NOT NULL,
                        description TEXT,
                        document VARCHAR(255),
                        payment_due DATE,
                        requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        bank_name VARCHAR(255),
                        bank_account_name VARCHAR(255),
                        bank_account_number VARCHAR(20),
                        ecash_provider VARCHAR(100),
                        ecash_account_name VARCHAR(100),
                        ecash_account_number VARCHAR(20),
                        disbursed_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                        status ENUM('disbursed','cancelled','reversed') DEFAULT 'disbursed',
                        original_reference_id VARCHAR(255) NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
                    
                    if (!$conn->query($create_table)) {
                        throw new Exception("Failed to create payables_receipts table: " . $conn->error);
                    }
                }
                
                // Insert into payables_receipts
                // Handle document - convert BLOB to filename if needed
                $doc_filename = '';
                if (!empty($document)) {
                    if (is_string($document) && strlen($document) < 255) {
                        $doc_filename = $document;
                    } else {
                        // Generate filename
                        $doc_filename = 'doc_' . $approveId . '_' . time() . '.pdf';
                    }
                }
                
                // Insert into payables_receipts
                $next_receipt_id = getNextAvailableId($conn, 'payables_receipts');
                $receipt_sql = "
                INSERT INTO payables_receipts (
                    id, reference_id, account_name, requested_department, expense_categories,
                    mode_of_payment, amount, description, document, payment_due,
                    requested_at, bank_name, bank_account_name, bank_account_number,
                    ecash_provider, ecash_account_name, ecash_account_number,
                    disbursed_date, status, original_reference_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())
                ";
                
                $stmt_receipt = $conn->prepare($receipt_sql);
                $stmt_receipt->bind_param("isssssdssssssssssss",
                    $next_receipt_id,
                    $new_reference_id,
                    $account_name,
                    $requested_department,
                    $expense_categories,
                    $mode_of_payment,
                    $amount,
                    $description,
                    $doc_filename,
                    $payment_due,
                    $requested_at,
                    $bank_name,
                    $bank_account_name,
                    $bank_account_number,
                    $ecash_provider,
                    $ecash_account_name,
                    $ecash_account_number,
                    $status,
                    $reference_id
                );
                
                if (!$stmt_receipt->execute()) {
                    throw new Exception("Error inserting into payables_receipts: " . $stmt_receipt->error);
                }
                
                $receipt_id = $conn->insert_id;
                $stmt_receipt->close();
                
                // Debug log
                error_log("PAYABLES_RECEIPT: Inserted record ID $receipt_id for PA ID $approveId");
            }
            
            // 3. Tanggalin mula sa pa table
            $delete_sql = "DELETE FROM pa WHERE id = ?";
            $stmt_delete = $conn->prepare($delete_sql);
            $stmt_delete->bind_param("i", $approveId);
            
            if (!$stmt_delete->execute()) {
                throw new Exception("Error deleting record from pa: " . $stmt_delete->error);
            }
            $stmt_delete->close();

            // 4. Update Source Tables
            if (strpos($reference_id, 'VEN-') === 0) {
                $orig_id = str_replace('VEN-', '', $reference_id);
                $conn->query("UPDATE accounts_payable SET status = 'Paid', paid_date = NOW() WHERE invoice_id = '$orig_id'");
            } elseif (strpos($reference_id, 'REIMB-') === 0) {
                $orig_id = str_replace('REIMB-', '', $reference_id);
                $conn->query("UPDATE reimbursements SET status = 'Paid', processed_date = NOW() WHERE report_id = '$orig_id' OR report_id = 'REIMB-$orig_id'");
            } elseif (strpos($reference_id, 'PAY-') === 0) {
                $orig_id = str_replace('PAY-', '', $reference_id);
                $conn->query("UPDATE payroll_records SET status = 'Paid' WHERE id = '$orig_id'");
            } elseif (strpos($reference_id, 'DRV-') === 0) {
                $orig_id = str_replace('DRV-', '', $reference_id);
                $conn->query("UPDATE driver_payouts SET status = 'Paid', paid_date = NOW() WHERE payout_id = '$orig_id'");
            }
            
            // 5. Update ang tr table status kung may matching reference_id
            $update_tr_sql = "UPDATE tr SET status = 'disbursed' WHERE reference_id = ? AND status = 'approved'";
            $stmt_update_tr = $conn->prepare($update_tr_sql);
            $stmt_update_tr->bind_param("s", $reference_id);
            
            if (!$stmt_update_tr->execute()) {
                // It's okay if this fails - not all payouts come from tr table
                error_log("Note: No matching tr record found for reference_id: $reference_id");
            }
            $stmt_update_tr->close();
            
            // Commit lahat ng transaction
            $conn->commit();
            
            $toast_message = "Payout Disbursed Successfully! ";
            $toast_type = 'success';
            
        } catch (Exception $e) {
            $conn->rollback();
            $toast_message = "Transaction failed: " . $e->getMessage();
            $toast_type = 'error';
            error_log("DISBURSEMENT_ERROR: " . $e->getMessage());
        }
    }
    
    // Store toast message in session for display after redirect
    $_SESSION['toast_message'] = $toast_message;
    $_SESSION['toast_type'] = $toast_type;
    
    // Redirect to avoid form resubmission
    // Redirect to avoid form resubmission
    $tab = $_POST['tab'] ?? 'vendors';
    header("Location: payout.php?tab=" . urlencode($tab));
    exit();
}

// Bulk Approve/Disburse Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_disburse']) && isset($_POST['disburse_ids']) && is_array($_POST['disburse_ids'])) {
    include('connection.php');
    $ids = $_POST['disburse_ids'];
    $success_count = 0;
    
    $conn->begin_transaction();
    try {
        foreach ($ids as $approveId) {
            $approveId = intval($approveId);
            
            // Fetch payout data
            $stmt = $conn->prepare("SELECT * FROM pa WHERE id = ?");
            $stmt->bind_param("i", $approveId);
            $stmt->execute();
            $payout_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$payout_data) continue;
            
            $mode_of_payment = $payout_data['mode_of_payment'];
            $reference_id = $payout_data['reference_id'];
            $amount = $payout_data['amount'];
            $account_name = $payout_data['account_name'];
            $requested_department = $payout_data['requested_department'];
            $expense_categories = $payout_data['expense_categories'];
            $description = $payout_data['description'] ?? '';
            $document = $payout_data['document'] ?? '';
            $payment_due = $payout_data['payment_due'] ?? '';
            $requested_at = $payout_data['requested_at'] ?? date('Y-m-d H:i:s');
            $bank_name = $payout_data['bank_name'] ?? '';
            $bank_account_number = $payout_data['bank_account_number'] ?? '';
            $bank_account_name = $payout_data['bank_account_name'] ?? '';
            $ecash_provider = $payout_data['ecash_provider'] ?? '';
            $ecash_account_name = $payout_data['ecash_account_name'] ?? '';
            $ecash_account_number = $payout_data['ecash_account_number'] ?? '';
            $from_payable = $payout_data['from_payable'] ?? 0;
            
            // New Reference ID logic
            if (strtolower($mode_of_payment) == 'bank' || strtolower($mode_of_payment) == 'bank transfer') {
                $new_reference_id = 'BNK-' . preg_replace('/^PA-/', '', $reference_id);
            } elseif (strtolower($mode_of_payment) == 'cash') {
                $new_reference_id = 'C-' . preg_replace('/^PA-/', '', $reference_id);
            } elseif (strtolower($mode_of_payment) == 'ecash') {
                $new_reference_id = 'EC-' . preg_replace('/^PA-/', '', $reference_id);
            } else {
                $new_reference_id = 'DIS-' . preg_replace('/^PA-/', '', $reference_id);
            }
            
            // 1. Insert into dr
            $status_val = 'disbursed';
            $next_dr_id = getNextAvailableId($conn, 'dr');
            $stmt = $conn->prepare("INSERT INTO dr (id, reference_id, account_name, requested_department, mode_of_payment, expense_categories, amount, description, document, payment_due, bank_name, bank_account_number, bank_account_name, ecash_provider, ecash_account_name, ecash_account_number, status, archived, source_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'payout')");
            $stmt->bind_param("isssssdssssssssss", $next_dr_id, $new_reference_id, $account_name, $requested_department, $mode_of_payment, $expense_categories, $amount, $description, $document, $payment_due, $bank_name, $bank_account_number, $bank_account_name, $ecash_provider, $ecash_account_name, $ecash_account_number, $status_val);
            $stmt->execute();
            $stmt->close();
            
            // 2. Insert into payables_receipts if needed
            if ($from_payable == 1) {
                $status_val = 'disbursed';
                $next_receipt_id = getNextAvailableId($conn, 'payables_receipts');
                $stmt = $conn->prepare("INSERT INTO payables_receipts (id, reference_id, account_name, requested_department, expense_categories, mode_of_payment, amount, description, document, payment_due, requested_at, bank_name, bank_account_name, bank_account_number, ecash_provider, ecash_account_name, ecash_account_number, disbursed_date, status, original_reference_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())");
                $stmt->bind_param("isssssdssssssssssss", $next_receipt_id, $new_reference_id, $account_name, $requested_department, $expense_categories, $mode_of_payment, $amount, $description, $document, $payment_due, $requested_at, $bank_name, $bank_account_name, $bank_account_number, $ecash_provider, $ecash_account_name, $ecash_account_number, $status_val, $reference_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // 3. Delete from pa
            $stmt = $conn->prepare("DELETE FROM pa WHERE id = ?");
            $stmt->bind_param("i", $approveId);
            $stmt->execute();
            $stmt->close();

            // 4. Update Source Tables
            if (strpos($reference_id, 'VEN-') === 0) {
                $orig_id = str_replace('VEN-', '', $reference_id);
                $conn->query("UPDATE accounts_payable SET status = 'Paid', paid_date = NOW() WHERE invoice_id = '$orig_id'");
            } elseif (strpos($reference_id, 'REIMB-') === 0) {
                $orig_id = str_replace('REIMB-', '', $reference_id);
                $conn->query("UPDATE reimbursements SET status = 'Paid', processed_date = NOW() WHERE report_id = '$orig_id' OR report_id = 'REIMB-$orig_id'");
            } elseif (strpos($reference_id, 'PAY-') === 0) {
                $orig_id = str_replace('PAY-', '', $reference_id);
                $conn->query("UPDATE payroll_records SET status = 'Paid' WHERE id = '$orig_id'");
            } elseif (strpos($reference_id, 'DRV-') === 0) {
                $orig_id = str_replace('DRV-', '', $reference_id);
                $conn->query("UPDATE driver_payouts SET status = 'Paid', paid_date = NOW() WHERE payout_id = '$orig_id'");
            }
            
            // 5. Update tr status
            $stmt = $conn->prepare("UPDATE tr SET status = 'disbursed' WHERE reference_id = ? AND status = 'approved'");
            $stmt->bind_param("s", $reference_id);
            $stmt->execute();
            $stmt->close();
            
            $success_count++;
        }
        $conn->commit();
        $_SESSION['toast_message'] = "Successfully disbursed $success_count payouts.";
        $_SESSION['toast_type'] = 'success';
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['toast_message'] = "Bulk disburse failed: " . $e->getMessage();
        $_SESSION['toast_type'] = 'error';
    }
    $tab = $_POST['tab'] ?? 'vendors';
    header("Location: payout.php?tab=" . urlencode($tab));
    exit();
}

// Check for toast message from session
if (isset($_SESSION['toast_message'])) {
    $toast_message = $_SESSION['toast_message'];
    $toast_type = $_SESSION['toast_type'];
    unset($_SESSION['toast_message']);
    unset($_SESSION['toast_type']);
}

// Fetch PENDING payouts (Exclude dummy data EM- and test records)
include('connection.php');

$sql = "SELECT pa.*, COALESCE(NULLIF(pa.vendor_address, ''), ap.vendor_address) as vendor_address 
        FROM pa 
        LEFT JOIN accounts_payable ap ON pa.reference_id = CONCAT('VEN-', ap.invoice_id)
        WHERE pa.reference_id NOT LIKE 'EM-%' 
        AND pa.account_name NOT LIKE '%test%' 
        AND (pa.status IS NULL OR pa.status = 'Pending Disbursement' OR pa.status = '')
        GROUP BY pa.id
        ORDER BY pa.approved_date DESC, pa.id DESC";
$result = $conn->query($sql);
$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Overview metrics for PENDING payouts only
$total_pending = count($rows);
$total_amount_due = 0;
$pending_cash = 0;
$pending_bank = 0;
foreach ($rows as $r) {
    $total_amount_due += floatval($r['amount']);
    $mode = strtolower($r['mode_of_payment']);
    if ($mode === 'cash') $pending_cash++;
    elseif ($mode === 'bank' || $mode === 'bank transfer') $pending_bank++;
}
?>
<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <!-- TensorFlow.js for AI Processing -->
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js"></script>
  
  <!-- JavaScript AI Payout Checker -->
  <script src="ai_payout_checker.js?v=<?php echo time(); ?>"></script>
  <script src="ai_table_highlighter.js?v=<?php echo time(); ?>"></script>
  <?php include 'ai_recommendation_cards.php'; ?>
  
  <title>Payout Approval</title>
  <link rel="icon" href="logo.png" type="img">
  <style>
    @media (max-width: 1024px) {
      .overview-flex { flex-direction: column !important; }
      .overview-left, .overview-right { width: 100% !important; }
      .overview-cards { flex-direction: column !important; }
      .overview-right { min-width: 0 !important; }
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
      background-color: white;
      margin: 5% auto;
      padding: 30px;
      border-radius: 10px;
      width: 50%;
      max-width: 600px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .confirmation-modal-content {
      background-color: white;
      margin: 10% auto;
      padding: 30px;
      border-radius: 12px;
      width: 45%;
      max-width: 500px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.25);
    }
    .actions-container {
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .table-container {
      max-height: 500px;
      overflow-y: auto;
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
    
    /* Confirmation modal specific styles */
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
  </style>
</head>
<body class="bg-white">



    <?php include('sidebar.php'); ?>
    
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
        <div class="flex justify-between items-center px-6 py-6 font-poppins">
            <h1 class="text-2xl">Payout</h1>
            <div class="text-sm">
                <a href="dashboard.php?page=dashboard" class="text-black hover:text-blue-600">Home</a>
                /
                <a class="text-black">Disbursement</a>
                /
                <a href="payout_approval.php" class="text-blue-600 hover:text-blue-600">Disbursement Request</a>
            </div>
        </div>

      <div class="flex-1 bg-white p-6 h-full w-full">
        <!-- Stats Overview Cards (Premium Look) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 px-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Pending</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($total_pending); ?></p>
                        <p class="text-xs text-purple-600 font-semibold mt-2 flex items-center">
                            <i class="fas fa-clock mr-1"></i> Awaiting disbursement
                        </p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl">
                        <i class="fas fa-file-invoice-dollar text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Amount Due</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2">₱<?php echo number_format($total_amount_due, 2); ?></p>
                        <p class="text-xs text-green-600 font-semibold mt-2 flex items-center">
                            <i class="fas fa-coins mr-1"></i> Total liabilities
                        </p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-xl">
                        <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Bank Transfers</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $pending_bank; ?></p>
                        <p class="text-xs text-blue-600 font-semibold mt-2 flex items-center">
                            <i class="fas fa-university mr-1"></i> Pending bank
                        </p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl">
                        <i class="fas fa-exchange-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Cash Payments</p>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $pending_cash; ?></p>
                        <p class="text-xs text-yellow-600 font-semibold mt-2 flex items-center">
                            <i class="fas fa-wallet mr-1"></i> Ready for release
                        </p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-xl">
                        <i class="fas fa-hand-holding-usd text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full">
            <div class="flex justify-between items-center mb-6 mx-6 flex-wrap gap-4 border-b border-gray-100 pb-4">
            <!-- Left: Tabs + Filters -->
            <div class="flex items-center gap-6 flex-wrap">
                <!-- Main Tabs (Updated to Budget Planning Style) -->
                <div class="flex gap-1 font-poppins text-sm font-medium p-1 bg-gray-100 rounded-xl">
                    <button type="button" class="tab-button px-6 py-2.5 rounded-lg bg-white shadow-sm text-purple-600 font-bold transition-all duration-200" data-tab="vendors" onclick="switchTab('vendors', this)">
                        <i class="fas fa-store mr-2"></i>Vendors/Supplier
                    </button>
                    <button type="button" class="tab-button px-6 py-2.5 rounded-lg text-gray-500 hover:text-purple-500 transition-all duration-200" data-tab="reimbursement" onclick="switchTab('reimbursement', this)">
                        <i class="fas fa-user-tie mr-2"></i>Reimbursement
                    </button>
                    <button type="button" class="tab-button px-6 py-2.5 rounded-lg text-gray-500 hover:text-purple-500 transition-all duration-200" data-tab="payroll" onclick="switchTab('payroll', this)">
                        <i class="fas fa-users-cog mr-2"></i>Payroll
                    </button>
                    <button type="button" class="tab-button px-6 py-2.5 rounded-lg text-gray-500 hover:text-purple-500 transition-all duration-200" data-tab="driver" onclick="switchTab('driver', this)">
                        <i class="fas fa-wallet mr-2"></i>Driver Payable
                    </button>
                </div>
                
                <!-- Payment Method Filter Buttons -->
                <div class="flex items-center gap-2 flex-wrap bg-gray-50 p-1 rounded-full border border-gray-200">
                    <button type="button" class="filter-btn px-4 py-1.5 rounded-full bg-indigo-600 text-white font-medium text-xs hover:bg-indigo-700 transition" data-mode="all">ALL</button>
                    <button type="button" class="filter-btn px-4 py-1.5 rounded-full text-gray-600 font-medium text-xs hover:bg-gray-200 transition" data-mode="cash">CASH</button>
                    <button type="button" class="filter-btn px-4 py-1.5 rounded-full text-gray-600 font-medium text-xs hover:bg-gray-200 transition" data-mode="bank">BANK</button>
                </div>
            </div>

            <!-- Right: Search and Date -->
            <div class="flex items-center gap-3 flex-wrap">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" id="searchInput" class="pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-400 w-64 transition-all" placeholder="Search payout request..." />
                </div>
                <div class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-200">
                    <i class="far fa-calendar-alt text-gray-400 text-sm"></i>
                    <input type="date" id="dueDate" class="bg-transparent text-sm focus:outline-none text-gray-600" />
                </div>
            </div>
          </div>

          <!-- Tab Sections -->
          <div id="vendorsSection" class="tab-section animate-fade-in px-6">
              <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                  <div class="flex items-center justify-between p-6 border-b border-gray-50 bg-gray-50/30">
                      <div class="flex items-center">
                          <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
                              <i class="fas fa-store text-purple-600"></i>
                          </div>
                          <div>
                              <h2 class="text-xl font-bold text-gray-800">Vendors & Suppliers</h2>
                              <p class="text-xs text-gray-500">Manage disbursement for external vendors</p>
                          </div>
                      </div>
                      <button type="button" onclick="openBulkApproveModal('vendors')" class="bg-indigo-600 text-white px-5 py-2 rounded-xl font-poppins font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-sm active:scale-95 text-xs">
                          <i class="fas fa-check-circle"></i> Bulk Disburse Vendors
                      </button>
                  </div>
                  <div class="p-0">
                      <div class="table-container">
                          <table class="w-full table-auto" id="vendorsTable">
                            <thead>
                              <tr class="bg-gray-50/50 text-gray-500 uppercase text-[10px] font-bold tracking-wider text-left border-b border-gray-100">
                                <th class="pl-6 py-4">Invoice No.</th>
                                <th class="px-4 py-4">Vendor</th>
                                <th class="px-4 py-4">Address</th>
                                <th class="px-4 py-4">Department</th>
                                <th class="px-4 py-4">Amount</th>
                                <th class="px-4 py-4 text-center">Actions</th>
                              </tr>
                            </thead>
                            <tbody class="text-gray-700 text-sm divide-y divide-gray-50" id="vendorsTableBody">
                               <!-- Paginated Vendors rows -->
                            </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>

          <div id="reimbursementSection" class="tab-section hidden animate-fade-in px-6">
              <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                  <div class="flex items-center justify-between p-6 border-b border-gray-50 bg-gray-50/30">
                      <div class="flex items-center">
                          <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                              <i class="fas fa-user-tie text-blue-600"></i>
                          </div>
                          <div>
                              <h2 class="text-xl font-bold text-gray-800">Reimbursement Requests</h2>
                              <p class="text-xs text-gray-500">Employee expense reimbursements</p>
                          </div>
                      </div>
                      <button type="button" onclick="openBulkApproveModal('reimbursement')" class="bg-indigo-600 text-white px-5 py-2 rounded-xl font-poppins font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-sm active:scale-95 text-xs">
                          <i class="fas fa-check-circle"></i> Bulk Disburse Reimbursements
                      </button>
                  </div>
                  <div class="table-container">
                      <table class="w-full table-auto" id="reimbursementTable">
                        <thead>
                          <tr class="bg-gray-50/50 text-gray-500 uppercase text-[10px] font-bold tracking-wider text-left border-b border-gray-100">
                            <th class="pl-6 py-4">Ticket No.</th>
                            <th class="px-4 py-4">Employee ID</th>
                            <th class="px-4 py-4">Department</th>
                            <th class="px-4 py-4">Submitted Date</th>
                            <th class="px-4 py-4">Approved Date</th>
                            <th class="px-4 py-4">Amount</th>
                            <th class="px-4 py-4 text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50" id="reimbursementTableBody">
                           <!-- Paginated Reimbursement rows -->
                        </tbody>
                      </table>
                  </div>
              </div>
          </div>

          <div id="payrollSection" class="tab-section hidden animate-fade-in px-6">
              <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                  <div class="flex items-center justify-between p-6 border-b border-gray-50 bg-gray-50/30">
                      <div class="flex items-center">
                          <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                              <i class="fas fa-users-cog text-green-600"></i>
                          </div>
                          <div>
                              <h2 class="text-xl font-bold text-gray-800">Payroll Disbursement</h2>
                              <p class="text-xs text-gray-500">Employee salary and benefits payout</p>
                          </div>
                      </div>
                      <button type="button" onclick="openBulkApproveModal('payroll')" class="bg-indigo-600 text-white px-5 py-2 rounded-xl font-poppins font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-sm active:scale-95 text-xs">
                          <i class="fas fa-check-circle"></i> Bulk Disburse Payroll
                      </button>
                  </div>
                  <div class="table-container">
                      <table class="w-full table-auto" id="payrollTable">
                        <thead>
                          <tr class="bg-gray-50/50 text-gray-500 uppercase text-[10px] font-bold tracking-wider text-left border-b border-gray-100">
                            <th class="pl-6 py-4">Payroll ID</th>
                            <th class="px-4 py-4">Employee ID</th>
                            <th class="px-4 py-4">Department</th>
                            <th class="px-4 py-4">Approved Date</th>
                            <th class="px-4 py-4">Amount</th>
                            <th class="px-4 py-4 text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50" id="payrollTableBody">
                           <!-- Paginated Payroll rows -->
                        </tbody>
                      </table>
                  </div>
              </div>
          </div>

          <div id="driverSection" class="tab-section hidden animate-fade-in px-6">
              <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                  <div class="flex items-center justify-between p-6 border-b border-gray-50 bg-gray-50/30">
                      <div class="flex items-center">
                          <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center mr-4">
                              <i class="fas fa-wallet text-amber-600"></i>
                          </div>
                          <div>
                              <h2 class="text-xl font-bold text-gray-800">Driver Payouts</h2>
                              <p class="text-xs text-gray-500">Scheduled payouts for drivers</p>
                          </div>
                      </div>
                      <button type="button" onclick="openBulkApproveModal('driver')" class="bg-indigo-600 text-white px-5 py-2 rounded-xl font-poppins font-bold hover:bg-indigo-700 transition-all flex items-center gap-2 shadow-sm active:scale-95 text-xs">
                          <i class="fas fa-check-circle"></i> Bulk Disburse Drivers
                      </button>
                  </div>
                  <div class="table-container">
                      <table class="w-full table-auto" id="driverTable">
                        <thead>
                          <tr class="bg-gray-50/50 text-gray-500 uppercase text-[10px] font-bold tracking-wider text-left border-b border-gray-100">
                            <th class="pl-6 py-4">Payout ID</th>
                            <th class="px-4 py-4">Wallet ID</th>
                            <th class="px-4 py-4">Driver Name</th>
                            <th class="px-4 py-4">Amount</th>
                            <th class="px-4 py-4 text-center">Approved Date</th>
                            <th class="px-4 py-4 text-center">Actions</th>
                          </tr>
                        </thead>
                        <tbody class="text-gray-700 text-sm divide-y divide-gray-50" id="driverTableBody">
                           <!-- Paginated Driver rows -->
                        </tbody>
                      </table>
                  </div>
              </div>
          </div>

          <!-- Hidden Main Table Body (for data source) -->
          <table class="hidden">
            <tbody id="payoutTableBody">
              <?php
              if (!empty($rows)):
              foreach ($rows as $row):
                  $paymentDue = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                  ?>
                  <tr class="hover:bg-indigo-50/30 transition-colors" data-mode="<?php echo strtolower($row['mode_of_payment']); ?>"
                      data-refid="<?php echo htmlspecialchars($row['reference_id']); ?>"
                      data-acct="<?php echo htmlspecialchars($row['account_name']); ?>"
                      data-dept="<?php echo htmlspecialchars($row['requested_department']); ?>"
                      data-category="<?php echo htmlspecialchars($row['expense_categories']); ?>"
                      data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                      data-duedate="<?php echo $paymentDue; ?>"
                      data-requestedat="<?php echo $row['requested_at'] ? date('Y-m-d', strtotime($row['requested_at'])) : ''; ?>"
                      data-bankname="<?php echo htmlspecialchars($row['bank_name'] ?? ''); ?>"
                      data-bankaccountname="<?php echo htmlspecialchars($row['bank_account_name'] ?? ''); ?>"
                      data-bankaccountnumber="<?php echo htmlspecialchars($row['bank_account_number'] ?? ''); ?>"
                      data-ecashprovider="<?php echo htmlspecialchars($row['ecash_provider'] ?? ''); ?>"
                      data-ecashaccountname="<?php echo htmlspecialchars($row['ecash_account_name'] ?? ''); ?>"
                      data-ecashaccountnumber="<?php echo htmlspecialchars($row['ecash_account_number'] ?? ''); ?>"
                      data-amount="<?php echo htmlspecialchars($row['amount']); ?>"
                      data-payouttype="<?php echo htmlspecialchars($row['payout_type'] ?? ''); ?>"
                      data-transtype="<?php echo htmlspecialchars($row['transaction_type'] ?? ''); ?>"
                      data-empid="<?php echo htmlspecialchars($row['employee_id'] ?? ''); ?>"
                      data-walletid="<?php echo htmlspecialchars($row['wallet_id'] ?? ''); ?>"
                      data-address="<?php echo htmlspecialchars($row['vendor_address'] ?? ''); ?>"
                      data-submitted="<?php echo ($row['submitted_date'] ?? null) ? date('Y-m-d', strtotime($row['submitted_date'])) : ''; ?>"
                      data-approved="<?php echo ($row['approved_date'] ?? null) ? date('Y-m-d', strtotime($row['approved_date'])) : ''; ?>"
                      data-approved-full="<?php echo $row['approved_date'] ?? ''; ?>"
                      data-id="<?php echo $row['id']; ?>">
                      <td class='pl-6 py-4 font-mono text-xs font-bold text-gray-600'><?php echo $row['reference_id'];?></td>
                      <td class='px-4 py-4 font-semibold text-gray-800'><?php echo $row['account_name'];?></td>
                      <td class='px-4 py-4 text-gray-500'><?php echo $row['requested_department'];?></td>
                      <td class='px-4 py-4'>
                          <?php 
                          $m = strtolower($row['mode_of_payment']);
                          if($m == 'bank' || $m == 'bank transfer') echo '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-bold uppercase tracking-tight">BANK</span>';
                          elseif($m == 'ecash') echo '<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-[10px] font-bold uppercase tracking-tight">E-CASH</span>';
                          else echo '<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-[10px] font-bold uppercase tracking-tight">CASH</span>';
                          ?>
                      </td>
                      <td class='px-4 py-4 font-black text-gray-900'>₱<?php echo number_format($row['amount'], 2);?></td>
                      <td class='px-4 py-4 text-xs font-medium text-gray-500'>
                          <?php 
                          echo htmlspecialchars($row['expense_categories']); 
                          // Validation Rule: If it's a reimbursement but appearing here (e.g. category is wrong but payout_type is set)
                          if (isset($row['payout_type']) && $row['payout_type'] === 'Reimbursement' && strtolower($row['expense_categories']) !== 'reimbursement') {
                              echo '<br><span class="text-[9px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full font-bold mt-1 inline-block"><i class="fas fa-exclamation-triangle mr-1"></i>Invalid Payout Routing</span>';
                          }
                          ?>
                      </td>
                      <td class='px-4 py-4 priority-cell'></td>
                      <td class='px-4 py-4'>
                          <div class="flex justify-center gap-2">
                              <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all view-btn" 
                                      data-id="<?php echo $row['id'];?>" title="View Details">
                                  <i class="far fa-eye text-xs"></i>
                              </button>
                              <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all disburse-btn" 
                                      data-id="<?php echo $row['id'];?>" title="Disburse Payment">
                                  <i class="fas fa-paper-plane text-xs"></i>
                              </button>
                          </div>
                      </td>
                  </tr>
              <?php endforeach;
              endif; ?>
            </tbody>
          </table>

          <div class="mt-4 flex justify-between items-center">
              <div id="pageStatus" class="text-gray-700 font-bold"></div>
              <div class="flex">
                  <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500">Previous</button>
                  <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500">Next</button>
              </div>
          </div>
        </div> 
      </div>
  </div>

  <!-- View Details Modal -->
  <div id="viewModal" class="modal">
    <div class="modal-content">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-purple-700">Payment Details</h2>
        <button id="closeModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
      </div>
      
      <div id="modalContent">
        <!-- Details will be populated by JavaScript -->
      </div>
      
      <div class="mt-6 text-right">
        <button id="closeModalBtn" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-700">Close</button>
      </div>
    </div>
  </div>

  <!-- Premium Confirmation Modal -->
  <div id="customConfirmModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden z-[1001] backdrop-blur-sm">
      <div class="bg-white p-8 rounded-2xl shadow-2xl w-[450px] transform transition-all scale-95 opacity-0 duration-300 border border-gray-100" id="confirmModalCard">
          <div id="confirmIconContainer" class="flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-6 mx-auto">
              <i id="confirmIcon" class="fas fa-receipt text-purple-600 text-3xl"></i>
          </div>
          <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center" id="confirmTitle">Disbursement Confirmation</h3>
          <p class="text-gray-600 mb-6 text-center leading-relaxed font-poppins" id="confirmMessage">Are you sure you want to disburse this payout? Action cannot be undone.</p>
          
          <div id="confirmDetailsBox" class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100 text-sm font-poppins hidden">
             <!-- Populated dynamically -->
          </div>

          <div class="flex gap-4">
              <button id="customCancelBtn" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 font-bold transition-all active:scale-95">Cancel</button>
              <button id="customProceedBtn" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 font-bold shadow-lg shadow-indigo-200 transition-all active:scale-95">Confirm Disburse</button>
          </div>
      </div>
  </div>

  </div>

  <!-- Bulk Approve Modal -->
  <div id="bulkApproveModal" class="modal">
      <div class="modal-content max-w-3xl">
          <div class="flex justify-between items-center mb-6">
              <div>
                  <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-3" id="bulkModalTitle">
                      <i class="fas fa-check-double text-indigo-600"></i>
                      Bulk Disburse Payouts
                  </h2>
                  <p class="text-gray-500 text-sm mt-1">Select the requests you want to disburse as a batch.</p>
              </div>
              <button onclick="closeBulkModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                  <i class="fas fa-times text-xl text-gray-400"></i>
              </button>
          </div>

          <div class="border border-gray-100 rounded-2xl overflow-hidden mb-6 shadow-sm">
              <div class="max-h-[400px] overflow-y-auto custom-scrollbar">
                  <table class="w-full text-sm text-left">
                      <thead class="bg-gray-50 text-gray-500 uppercase text-[10px] font-bold tracking-wider sticky top-0 z-10">
                          <tr>
                              <th class="px-6 py-4">
                                  <input type="checkbox" id="selectAllBulk" onchange="toggleAllBulk(this)" class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
                              </th>
                              <th class="px-4 py-4" id="bulkColRef">Reference ID</th>
                              <th class="px-4 py-4" id="bulkColName">Account Name</th>
                              <th class="px-4 py-4" id="bulkColExtra">Category</th>
                              <th class="px-4 py-4 text-right">Amount</th>
                          </tr>
                      </thead>
                      <tbody id="bulkApproveList" class="divide-y divide-gray-50 bg-white">
                          <!-- Loaded via AJAX -->
                      </tbody>
                  </table>
              </div>
          </div>

          <div class="flex justify-between items-center bg-gray-50 p-6 rounded-2xl border border-gray-100">
              <div>
                  <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Total Amount Selected:</p>
                  <p class="text-2xl font-black text-indigo-600" id="bulkTotalAmount">₱0.00</p>
                  <p class="text-[10px] font-bold text-gray-500 mt-1 uppercase" id="bulkSelectedCount">0 items selected</p>
              </div>
              <div class="flex gap-3">
                  <button type="button" onclick="closeBulkModal()" class="px-6 py-3 bg-white border border-gray-200 text-gray-600 rounded-xl font-bold hover:bg-gray-50 transition-all active:scale-95 shadow-sm">
                      Cancel
                  </button>
                  <button type="button" id="bulkApproveSubmitBtn" onclick="submitBulkApproval()" disabled class="px-8 py-3 bg-emerald-500 text-white rounded-xl font-bold hover:bg-emerald-600 transition-all active:scale-95 shadow-lg shadow-emerald-100 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                      <i class="fas fa-check-circle"></i> Disburse Selected
                  </button>
              </div>
          </div>
      </div>
  </div>

<script>
// ============================================================================
// AI PAYOUT CHECKER - USES GLOBAL aiPayoutChecker from ai_payout_checker.js
// ============================================================================
// NOTE: aiPayoutChecker is initialized automatically in ai_payout_checker.js
// We just need to wait for it to be ready

// Analyze payout with AI before submission
async function analyzePayoutWithAI(payoutData) {
    if (typeof window.aiPayoutChecker === 'undefined' || !window.aiPayoutChecker.isInitialized) {
        console.warn('⚠️ AI not initialized yet, skipping validation');
        return { recommendation: 'ALLOW_PAYOUT', risk_level: 'LOW', risk_score: 0, issues: ['AI initializing...'] };
    }
    
    try {
        const result = await window.aiPayoutChecker.analyzePayout(payoutData);
        console.log('🔍 AI Analysis Result:', result);
        
        return result;
    } catch (error) {
        console.error('❌ AI analysis error:', error);
        return { recommendation: 'REQUIRE_MANUAL_REVIEW', risk_level: 'MEDIUM', risk_score: 50, issues: ['AI error occurred'] };
    }
}

// Show AI analysis modal
function showAIAnalysisModal(result, payoutData, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    const riskColor = result.risk_level === 'HIGH' ? 'red' : 
                      result.risk_level === 'MEDIUM' ? 'yellow' : 'green';
    
    const riskIcon = result.risk_level === 'HIGH' ? '🚨' : 
                     result.risk_level === 'MEDIUM' ? '⚠️' : '✅';
    
    modal.innerHTML = `
        <div class="confirmation-modal-content">
            <div class="text-center">
                <div style="font-size: 60px; margin-bottom: 20px;">${riskIcon}</div>
                <h2 class="confirm-title">AI Security Analysis</h2>
                
                <div class="confirm-details">
                    <div class="detail-item">
                        <span class="detail-label">Payout ID:</span>
                        <span class="detail-value">${payoutData.payout_id}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Amount:</span>
                        <span class="detail-value">₱${parseFloat(payoutData.amount).toLocaleString()}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Risk Level:</span>
                        <span class="detail-value" style="color: ${riskColor}; font-weight: bold;">${result.risk_level}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Risk Score:</span>
                        <span class="detail-value">${result.risk_score}/100</span>
                    </div>
                </div>
                
                <div style="background: #f9fafb; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: left;">
                    <h4 style="font-weight: bold; margin-bottom: 10px; color: #4B5563;">⚠️ Issues Detected:</h4>
                    ${result.issues.map(issue => `
                        <div style="padding: 8px; margin: 5px 0; background: white; border-left: 3px solid ${riskColor}; border-radius: 4px; font-size: 14px;">
                            ${issue}
                        </div>
                    `).join('')}
                </div>
                
                ${result.recommendation === 'BLOCK_PAYOUT' ? `
                    <div style="background: #fee2e2; border: 2px solid #ef4444; border-radius: 8px; padding: 15px; margin: 20px 0;">
                        <p style="color: #991b1b; font-weight: bold;">🔒 This payout has been blocked by AI security.</p>
                        <p style="color: #991b1b; font-size: 14px; margin-top: 5px;">Please review the issues and contact your supervisor.</p>
                    </div>
                ` : ''}
                
                <div class="confirm-buttons">
                    <button onclick="this.closest('.modal').remove()" 
                            class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-300 transition-all">
                        Cancel
                    </button>
                    ${result.recommendation !== 'BLOCK_PAYOUT' ? `
                        <button onclick="
                            this.closest('.modal').remove(); 
                            if (window.aiPayoutChecker && window.aiPayoutChecker.confirmTransaction) {
                                window.aiPayoutChecker.confirmTransaction(${JSON.stringify(payoutData)}, ${JSON.stringify(result)});
                            }
                            (${onConfirm.toString()})();
                        " 
                                class="px-8 py-3 ${result.risk_level === 'MEDIUM' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600'} text-white rounded-xl font-bold transition-all shadow-lg">
                            ${result.risk_level === 'MEDIUM' ? '⚠️ Proceed with Caution' : '✅ Confirm Disburse'}
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Priority logic
function getPriorityLevel(payment_due) {
    if (!payment_due) return 4;
    const today = new Date();
    const due = new Date(payment_due);
    today.setHours(0,0,0,0); due.setHours(0,0,0,0);
    const diff = Math.ceil((due - today) / (1000 * 60 * 60 * 24));
    if (diff < 0) return 1;
    if (diff <= 3) return 2;
    if (diff <= 7) return 3;
    return 4;
}

function priorityLabel(level) {
    switch(level) {
        case 1: return '<span class="font-bold text-red-500">1 (Overdue)</span>';
        case 2: return '<span class="font-bold text-yellow-500">2 (Within 3 days)</span>';
        case 3: return '<span class="font-bold text-blue-500">3 (Within 7 days)</span>';
        default: return '<span class="font-bold text-green-600">4 (Low)</span>';
    }
}


const urlParams = new URLSearchParams(window.location.search);
let currentTab = urlParams.get('tab') || 'vendors'; // Track current main tab
let modeOfPayment = 'all';
let currentPage = 1;
const rowsPerPage = 10;

// Initialize tab on load
window.addEventListener('DOMContentLoaded', () => {
    // Get tab from URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'vendors';
    
    // Find the button for this tab
    const tabBtn = document.querySelector(`.tab-button[data-tab="${initialTab}"]`);
    if (tabBtn) {
        switchTab(initialTab, tabBtn);
    } else {
        // Fallback to vendors if invalid tab
        const defaultBtn = document.querySelector('.tab-button[data-tab="vendors"]');
        if (defaultBtn) switchTab('vendors', defaultBtn);
    }
});

function switchTab(tabId, btn) {
    // Hide all sections
    document.querySelectorAll('.tab-section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Show target section
    const targetSection = document.getElementById(tabId + 'Section');
    if (targetSection) targetSection.classList.remove('hidden');
    
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('bg-white', 'shadow-sm', 'text-purple-600', 'font-bold');
        button.classList.add('text-gray-500');
    });
    
    btn.classList.add('bg-white', 'shadow-sm', 'text-purple-600', 'font-bold');
    btn.classList.remove('text-gray-500');
    
    // Reset state
    currentTab = tabId;
    currentPage = 1;
    
    // Update URL without reloading
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.pushState({}, '', url);
    
    renderTable(currentPage);
}

// Event listeners for filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        // Update filter button styling
        document.querySelectorAll('.filter-btn').forEach(filterBtn => {
            filterBtn.classList.remove('bg-indigo-600', 'text-white');
            filterBtn.classList.add('text-gray-600');
        });
        this.classList.remove('text-gray-600');
        this.classList.add('bg-indigo-600', 'text-white');
        
        // Update mode of payment filter
        modeOfPayment = this.getAttribute('data-mode');
        currentPage = 1;
        renderTable(currentPage);
    });
});

// Toast notification functions
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHtml = `
        <div class="toast ${type}" id="${toastId}">
            <div class="flex items-center">
                <i class="toast-icon ${type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'}"></i>
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

// Auto remove the PHP-generated toast after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const autoToast = document.getElementById('autoToast');
    if (autoToast) {
        setTimeout(() => {
            autoToast.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                autoToast.remove();
            }, 300);
        }, 5000);
    }
    
    // Initialize priority labels
    updatePriorityLabels();
});

// Update all priority labels
function updatePriorityLabels() {
    document.querySelectorAll('#payoutTableBody tr').forEach(row => {
        let cell = row.querySelector('.priority-cell');
        if (cell) {
            let paymentDue = row.getAttribute('data-duedate');
            let p = getPriorityLevel(paymentDue);
            cell.innerHTML = priorityLabel(p);
        }
    });
}

// Modal functionality
const viewModal = document.getElementById('viewModal');
const modalContent = document.getElementById('modalContent');
const closeModal = document.getElementById('closeModal');
const closeModalBtn = document.getElementById('closeModalBtn');

// Confirmation modal elements
const confirmationModal = document.getElementById('confirmationModal');
const confirmRefId = document.getElementById('confirmRefId');
const confirmAccount = document.getElementById('confirmAccount');
const confirmMode = document.getElementById('confirmMode');
const confirmAmount = document.getElementById('confirmAmount');
const confirmDueDate = document.getElementById('confirmDueDate');
const confirmApproveId = document.getElementById('confirmApproveId');
const cancelConfirm = document.getElementById('cancelConfirm');
const disburseForm = document.getElementById('disburseForm');

// View and Disburse button click handler (with event delegation and closest support)
document.addEventListener('click', function(e) {
    const viewBtn = e.target.closest('.view-btn');
    const disburseBtn = e.target.closest('.disburse-btn');

    if (viewBtn) {
        const row = viewBtn.closest('tr');
        const mode = row.getAttribute('data-mode');
        const refId = row.getAttribute('data-refid');
        const accountName = row.getAttribute('data-acct');
        const department = row.getAttribute('data-dept');
        const amount = row.getAttribute('data-amount');
        const paymentDue = row.getAttribute('data-duedate');
        const description = row.getAttribute('data-desc');
        const category = row.getAttribute('data-category');
        const bankName = row.getAttribute('data-bankname');
        const bankAccountName = row.getAttribute('data-bankaccountname');
        const bankAccountNumber = row.getAttribute('data-bankaccountnumber');
        const ecashProvider = row.getAttribute('data-ecashprovider');
        const ecashAccountName = row.getAttribute('data-ecashaccountname');
        const ecashAccountNumber = row.getAttribute('data-ecashaccountnumber');
        const vendorAddress = row.getAttribute('data-address');
        
        let detailsHtml = `
            <div class="space-y-4 font-poppins text-gray-800">
                <div class="grid grid-cols-2 gap-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reference ID</p>
                        <p class="text-lg font-mono font-bold text-purple-700">${refId}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Account Name</p>
                        <p class="text-lg font-bold">${accountName}</p>
                    </div>
                </div>
                
                <div class="px-4">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Vendor Address</p>
                    <p class="text-md font-medium text-gray-700">${vendorAddress || 'N/A'}</p>
                </div>
                
                <div class="grid grid-cols-2 gap-6 px-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Department</p>
                        <p class="text-md font-semibold text-gray-600">${department}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Payment Mode</p>
                        <p class="text-md font-bold text-blue-600 uppercase">${mode}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-6 px-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Amount</p>
                        <p class="text-xl font-black text-green-600">₱${parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Payment Due</p>
                        <p class="text-md font-bold ${new Date(paymentDue) < new Date() ? 'text-red-500' : 'text-gray-700'}">${paymentDue || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="px-4">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expense Category</p>
                    <p class="text-md font-medium">${category}</p>
                </div>
                
                <div class="px-4">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Description</p>
                    <p class="text-md bg-gray-50 p-3 rounded-lg border border-gray-100 italic text-gray-600 mt-1">${description || 'No description provided.'}</p>
                </div>
        `;
        
        // Add payment-specific details
        if (mode === 'bank' || mode === 'bank transfer') {
            detailsHtml += `
                <div class="mt-4 p-5 bg-blue-50/50 rounded-2xl border border-blue-100 mx-2">
                    <h3 class="font-bold text-blue-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-university"></i> Bank Account Information
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Bank Name</p>
                            <p class="font-bold text-blue-900">${bankName || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Account Name</p>
                            <p class="font-bold text-blue-900">${bankAccountName || 'N/A'}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Account Number</p>
                        <p class="text-lg font-mono font-bold text-blue-900">${bankAccountNumber || 'N/A'}</p>
                    </div>
                </div>
            `;
        } else if (mode === 'cash') {
            detailsHtml += `
                <div class="mt-4 p-5 bg-green-50/50 rounded-2xl border border-green-100 mx-2">
                    <h3 class="font-bold text-green-800 mb-2 flex items-center gap-2 text-lg">
                        <i class="fas fa-hand-holding-usd"></i> Cash Disbursement
                    </h3>
                    <p class="text-sm text-green-900 opacity-80">This is a hand-to-hand cash payment. Ensure the recipient signs the acknowledgment receipt upon release.</p>
                </div>
            `;
        }
        
        detailsHtml += `</div>`;
        modalContent.innerHTML = detailsHtml;
        viewModal.style.display = 'block';
    }
});

// AI Validation Function - UPDATED TO USE JAVASCRIPT AI
async function runAIValidation(transactions) {
    try {
        if (typeof window.aiPayoutChecker === 'undefined' || !window.aiPayoutChecker.isInitialized) {
            console.warn('⚠️ AI not initialized yet');
            return {
                status: 'offline',
                message: 'AI service is initializing...',
                analysis: []
            };
        }
        
        // Prepare payout data for AI analysis
        const payoutData = {
            payout_id: transactions[0].reference_id,
            amount: parseFloat(transactions[0].amount),
            payee_type: transactions[0].expense_categories === 'Payroll' ? 'Employee' : 
                       (transactions[0].expense_categories === 'Reimbursement' ? 'Employee' : 'Vendor'),
            payee_id: transactions[0].account_name,
            reference_type: transactions[0].expense_categories || 'Invoice',
            reference_id: transactions[0].reference_id,
            approver_id: '<?php echo $_SESSION["user_id"] ?? "SYSTEM"; ?>',
            releaser_id: '<?php echo $_SESSION["user_id"] ?? "SYSTEM"; ?>',
            approval_status: 'Approved',
            payment_method: transactions[0].mode_of_payment || 'CASH',
            department: transactions[0].requested_department || 'OPERATIONS',
            timestamp: new Date().toISOString()
        };
        
        // Run AI analysis
        const aiResult = await window.aiPayoutChecker.analyzePayout(payoutData);
        
        // Transform to expected format
        return {
            status: 'success',
            message: aiResult.issues.join('; '),
            analysis: [{
                reference_id: transactions[0].reference_id,
                risk_level: aiResult.risk_level === 'LOW' ? 'Low' : 
                           aiResult.risk_level === 'MEDIUM' ? 'Medium' : 'High',
                risk_score: aiResult.risk_score,
                issues_detected: aiResult.issues,
                recommendation: aiResult.recommendation
            }]
        };
        
    } catch (error) {
        console.error('❌ AI validation error:', error);
        return {
            status: 'error',
            message: 'AI validation failed: ' + error.message,
            analysis: []
        };
    }
}


function getAIRiskBadge(level) {
    const badges = {
        'Low': '<span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-[10px] font-bold uppercase border border-green-200"><i class="fas fa-check-circle mr-1"></i>Low Risk</span>',
        'Medium': '<span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-[10px] font-bold uppercase border border-orange-200"><i class="fas fa-exclamation-triangle mr-1"></i>Medium Risk</span>',
        'High': '<span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold uppercase border border-red-200"><i class="fas fa-times-circle mr-1"></i>High Risk</span>'
    };
    return badges[level] || level;
}

// Disburse button click handler - SHOW PREMIUM MODAL
document.addEventListener('click', async function(e) {
    const disburseBtn = e.target.closest('.disburse-btn');
    
    if (disburseBtn) {
        const row = disburseBtn.closest('tr');
        const payoutData = {
            payout_id: row.getAttribute('data-refid'),
            reference_id: row.getAttribute('data-refid'),
            amount: parseFloat(row.getAttribute('data-amount')),
            reference_amount: parseFloat(row.getAttribute('data-amount')),
            payee_id: row.getAttribute('data-acct'),
            payee_type: row.getAttribute('data-payouttype') || 'Vendor',
            reference_type: row.getAttribute('data-transtype') || 'Invoice',
            approver_id: 'USER',
            releaser_id: '<?php echo $_SESSION["user_id"] ?? "CURRENT"; ?>',
            payment_method: row.getAttribute('data-mode') || 'CASH',
            department: row.getAttribute('data-dept') || 'OPERATIONS'
        };
        
        showToast('🤖 AI analyzing...', 'info');
        
        const aiResult = await window.aiPayoutChecker.analyzePayout(payoutData);
        if (window.aiTableHighlighter) {
            window.aiTableHighlighter.highlightRow(row, aiResult.risk_level, aiResult.risk_score);
        }
        showDisbursementModalWithAI(payoutData, aiResult);
    }
});

function showDisbursementModalWithAI(payoutData, aiResult) {
    const aiHTML = `
        <div class="mt-4 p-4 rounded-lg border-2 ${
            aiResult.risk_level === 'LOW' ? 'bg-green-50 border-green-200' :
            aiResult.risk_level === 'MEDIUM' ? 'bg-yellow-50 border-yellow-200' :
            'bg-red-50 border-red-200'
        }">
            <div class="flex justify-between mb-3">
                <h4 class="font-bold text-sm">🤖 AI Analysis</h4>
                <span class="px-3 py-1 rounded-full text-xs font-bold ${
                    aiResult.risk_level === 'LOW' ? 'bg-green-500 text-white' :
                    aiResult.risk_level === 'MEDIUM' ? 'bg-yellow-500 text-white' :
                    'bg-red-500 text-white'
                }">
                    ${aiResult.risk_level} RISK
                </span>
            </div>
            <div class="text-sm">
                <div>Risk Score: <strong>${aiResult.risk_score}/100</strong></div>
                <div class="mt-2 text-xs font-bold text-gray-400 uppercase tracking-widest">Issues Detected:</div>
                <ul class="text-xs space-y-1 mt-1">
                    ${aiResult.issues.slice(0, 3).map(i => `<li class="flex items-start gap-2"><i class="fas fa-info-circle mt-0.5 ${aiResult.risk_level === 'HIGH' ? 'text-red-500' : 'text-orange-500'}"></i> <span>${i}</span></li>`).join('')}
                    ${aiResult.issues.length > 3 ? `<li class="text-gray-400 font-bold ml-5">...and ${aiResult.issues.length - 3} more</li>` : ''}
                </ul>
            </div>
        </div>
    `;
    
    const content = `
        <div class="space-y-2 mt-4">
            <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-400 uppercase text-[10px] font-bold">Ref ID</span> <span class="font-mono font-bold text-purple-700">${payoutData.reference_id}</span></div>
            <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-400 uppercase text-[10px] font-bold">Account</span> <span class="font-bold text-gray-800">${payoutData.payee_id}</span></div>
            <div class="flex justify-between py-2 border-b border-gray-100"><span class="text-gray-400 uppercase text-[10px] font-bold">Total Amount</span> <span class="font-black text-green-600 text-lg">₱${payoutData.amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</span></div>
        </div>
        ${aiHTML}
    `;
    
    const blocked = aiResult.risk_level === 'HIGH';
    
    showCustomConfirm(
        'Verify Disbursement',
        blocked ? 'AI has blocked this disbursement due to critical risk factors.' : 'Review the AI analysis and transaction details before releasing funds.',
        blocked ? null : () => {
            window.aiPayoutChecker.confirmTransaction(payoutData, aiResult);
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'approve_id';
            // Need the actual database ID which might be different from ref_id
            // Find it from the page or pass it through
            const row = document.querySelector(`tr[data-refid="${payoutData.reference_id}"]`);
            input.value = row ? row.getAttribute('data-id') : payoutData.payout_id;
            form.appendChild(input);
            
            // Add current tab to form
            const tabInput = document.createElement('input');
            tabInput.type = 'hidden';
            tabInput.name = 'tab';
            tabInput.value = currentTab;
            form.appendChild(tabInput);
            
            document.body.appendChild(form);
            form.submit();
        },
        content
    );
}

function bulkDisburse() {
    const activeSection = document.getElementById(currentTab + 'Section');
    const selected = activeSection.querySelectorAll('.payout-checkbox:checked');
    if (selected.length === 0) {
        showCustomAlert('Selection Required', 'Please select at least one payout request from the active list to disburse.', 'warning');
        return;
    }

    showToast('AI Analyzing selected transactions...', 'info');

    const transactions = Array.from(selected).map(cb => {
        const row = cb.closest('tr');
        return {
            id: cb.value,
            reference_id: row.cells[1].innerText.trim(),
            account_name: row.cells[2].innerText.trim(),
            amount: row.cells[5].innerText.replace(/[^0-9.]/g, ''),
            approved_by: 'Authorized User'
        };
    });

    runAIValidation(transactions).then(aiResult => {
        let aiSummaryHtml = '';
        let highRiskCount = 0;

        if (aiResult.status === 'success') {
            const highRiskItems = aiResult.analysis.filter(a => a.risk_level === 'High');
            highRiskCount = highRiskItems.length;
            
            aiSummaryHtml = `
                <div class="mt-4 p-4 rounded-xl border ${highRiskCount > 0 ? 'bg-red-50 border-red-100' : 'bg-green-50 border-green-100'}">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">AI Bulk Analysis</h4>
                        <span class="text-[10px] font-bold">${aiResult.analysis.length} Transactions Scanned</span>
                    </div>
                    ${highRiskCount > 0 
                        ? `<p class="text-xs text-red-600 font-bold flex items-center gap-2"><i class="fas fa-times-circle"></i> ${highRiskCount} critical risk(s) detected. Bulk execution restricted.</p>`
                        : `<p class="text-xs text-green-600 font-bold flex items-center gap-2"><i class="fas fa-check-circle"></i> All selected transactions cleared by AI.</p>`
                    }
                </div>
            `;
        }

        showCustomConfirm(
            highRiskCount > 0 ? 'Bulk Disbursement Blocked' : 'Bulk Disburse Verify',
            highRiskCount > 0 
                ? 'Critial risks detected in selected items. Please review high-risk items individually.'
                : `You are about to process ${selected.length} payout(s) simultaneously. Proceed?`,
            highRiskCount > 0 ? null : () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const hiddenAction = document.createElement('input');
                hiddenAction.type = 'hidden';
                hiddenAction.name = 'bulk_disburse';
                hiddenAction.value = '1';
                form.appendChild(hiddenAction);
                
                selected.forEach(cb => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'disburse_ids[]';
                    hiddenInput.value = cb.value;
                    form.appendChild(hiddenInput);
                });
                
                // Add current tab to form
                const tabInput = document.createElement('input');
                tabInput.type = 'hidden';
                tabInput.name = 'tab';
                tabInput.value = currentTab;
                form.appendChild(tabInput);
                
                document.body.appendChild(form);
                form.submit();
                showToast('Processing bulk disbursement...', 'success');
            },
            aiSummaryHtml
        );
    });
}

function toggleSelectAll(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;
    
    const selectAllBox = section.querySelector('.selectAll');
    const checkboxes = section.querySelectorAll('.payout-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAllBox.checked;
    });
}

// Global Custom Modal Helpers
function showCustomConfirm(title, message, onConfirm, detailsHtml = '') {
    const modal = document.getElementById('customConfirmModal');
    const card = document.getElementById('confirmModalCard');
    const proceedBtn = document.getElementById('customProceedBtn');
    const cancelBtn = document.getElementById('customCancelBtn');
    const detailsBox = document.getElementById('confirmDetailsBox');
    
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    
    if(detailsHtml) {
        detailsBox.innerHTML = detailsHtml;
        detailsBox.classList.remove('hidden');
    } else {
        detailsBox.classList.add('hidden');
    }

    // AI Safety: Disable proceed if onConfirm is null
    if (onConfirm === null) {
        proceedBtn.classList.add('hidden');
        cancelBtn.textContent = 'Close';
    } else {
        proceedBtn.classList.remove('hidden');
        cancelBtn.textContent = 'Cancel';
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    const singleHandle = () => {
        if (!onConfirm) return;
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
        onConfirm();
        cleanup();
    };
    
    const singleCancel = () => {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 300);
        cleanup();
    };

    function cleanup() {
        proceedBtn.removeEventListener('click', singleHandle);
        cancelBtn.removeEventListener('click', singleCancel);
    }
    
    proceedBtn.addEventListener('click', singleHandle);
    cancelBtn.addEventListener('click', singleCancel);
}

function showCustomAlert(title, message, type = 'info') {
    const modal = document.getElementById('customConfirmModal');
    const card = document.getElementById('confirmModalCard');
    const proceedBtn = document.getElementById('customProceedBtn');
    const cancelBtn = document.getElementById('customCancelBtn');
    const detailsBox = document.getElementById('confirmDetailsBox');
    
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    cancelBtn.style.display = 'none';
    detailsBox.classList.add('hidden');
    
    proceedBtn.textContent = 'OK';
    modal.classList.remove('hidden');
    setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    const closeAlert = () => {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            proceedBtn.removeEventListener('click', closeAlert);
            cancelBtn.style.display = 'block';
            proceedBtn.textContent = 'Confirm Disburse';
        }, 300);
    };
    
    proceedBtn.addEventListener('click', closeAlert);
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    if (event.target === viewModal) {
        viewModal.style.display = 'none';
    }
    if (event.target === document.getElementById('bulkApproveModal')) {
        closeBulkModal();
    }
    if (event.target === document.getElementById('customConfirmModal')) {
        document.getElementById('customConfirmModal').classList.add('hidden');
    }
});

// Bulk Approve Modal Logic
async function openBulkApproveModal(type) {
    const modal = document.getElementById('bulkApproveModal');
    const bulkList = document.getElementById('bulkApproveList');
    const title = document.getElementById('bulkModalTitle');
    const selectAll = document.getElementById('selectAllBulk');
    const colRef = document.getElementById('bulkColRef');
    const colName = document.getElementById('bulkColName');
    const colExtra = document.getElementById('bulkColExtra');
    
    // Reset modal
    bulkList.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center"><i class="fas fa-spinner fa-spin text-indigo-600 text-3xl mb-4"></i><p class="text-sm text-gray-400 font-medium">Fetching pending requests...</p></td></tr>';
    selectAll.checked = false;
    document.getElementById('bulkTotalAmount').textContent = '₱0.00';
    document.getElementById('bulkSelectedCount').textContent = '0 items selected';
    document.getElementById('bulkApproveSubmitBtn').disabled = true;
    
    // Set labels based on type
    if (type === 'vendors') {
        title.innerHTML = '<i class="fas fa-store text-purple-600"></i> Bulk Disburse Vendor Payments';
        colRef.textContent = 'Invoice No.';
        colName.textContent = 'Vendor';
        colExtra.textContent = 'Department';
    } else if (type === 'reimbursement') {
        title.innerHTML = '<i class="fas fa-user-tie text-blue-600"></i> Bulk Disburse Reimbursements';
        colRef.textContent = 'Ticket No.';
        colName.textContent = 'Employee ID';
        colExtra.textContent = 'Department';
    } else if (type === 'payroll') {
        title.innerHTML = '<i class="fas fa-users-cog text-green-600"></i> Bulk Disburse Payroll';
        colRef.textContent = 'Payroll ID';
        colName.textContent = 'Employee ID';
        colExtra.textContent = 'Department';
    } else if (type === 'driver') {
        title.innerHTML = '<i class="fas fa-wallet text-amber-600"></i> Bulk Disburse Driver Payouts';
        colRef.textContent = 'Payout ID';
        colName.textContent = 'Wallet ID';
        colExtra.textContent = 'Driver Name';
    }
    
    modal.style.display = 'block';
    
    try {
        const response = await fetch(`payout.php?action=get_pending_bulk_payout&type=${type}`);
        const data = await response.json();
        
        if (data.success) {
            if (data.data.length === 0) {
                bulkList.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic font-medium">No pending ${transactionTypeLabel(type)} found matching approval criteria.</td></tr>`;
            } else {
                bulkList.innerHTML = '';
                const bulkAnalyses = [];
                
                for (const item of data.data) {
                    let col2 = item.account_name;
                    let col3 = item.expense_categories;
                    
                    if (type === 'reimbursement' || type === 'payroll') {
                        col2 = item.employee_id || 'N/A';
                        col3 = item.requested_department || 'N/A';
                    } else if (type === 'driver') {
                        col2 = item.wallet_id || 'N/A';
                        col3 = item.account_name;
                    } else if (type === 'vendors') {
                        col2 = item.account_name;
                        col3 = item.requested_department || 'N/A';
                    }
                    
                    const extra = col3;
                    // AI Analysis for bulk item
                    const payoutData = {
                        payout_id: item.reference_id,
                        reference_id: item.reference_id,
                        amount: parseFloat(item.amount),
                        reference_amount: parseFloat(item.amount),
                        payee_id: item.account_name,
                        payee_type: (type === 'payroll' || type === 'reimbursement') ? 'Employee' : (type === 'driver' ? 'Contractor' : 'Vendor'),
                        reference_type: (type === 'payroll') ? 'Payroll' : (type === 'reimbursement' ? 'Reimbursement' : (type === 'driver' ? 'Driver Payout' : 'Invoice')),
                        payment_method: 'CASH', // Default for bulk view
                        department: item.requested_department || 'OPERATIONS'
                    };
                    
                    let riskBadge = '<span class="text-[8px] text-gray-400">AI Evaluating...</span>';
                    let riskClass = '';
                    
                    try {
                        if (window.aiPayoutChecker && window.aiPayoutChecker.isInitialized) {
                            const aiResult = await window.aiPayoutChecker.analyzePayout(payoutData);
                            bulkAnalyses.push(aiResult);
                            riskBadge = `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold ${
                                aiResult.risk_level === 'LOW' ? 'bg-green-100 text-green-700' :
                                aiResult.risk_level === 'MEDIUM' ? 'bg-yellow-100 text-yellow-700' :
                                'bg-red-100 text-red-700'
                            }">${aiResult.risk_level}</span>`;
                            
                            if (aiResult.risk_level === 'LOW') riskClass = 'bg-green-50/50';
                            else if (aiResult.risk_level === 'HIGH') riskClass = 'bg-red-50/50';
                        }
                    } catch (e) { console.error(e); }

                    const row = document.createElement('tr');
                    row.className = `hover:bg-gray-50/50 transition-colors ${riskClass}`;
                    const addrHtml = (type === 'vendors' && item.vendor_address) ? `<div class="text-[10px] text-gray-400 mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i>${item.vendor_address}</div>` : '';
                    row.innerHTML = `
                        <td class="px-6 py-4">
                            <input type="checkbox" class="bulk-item-checkbox w-4 h-4 text-emerald-600 rounded border-gray-300 focus:ring-emerald-500 cursor-pointer" 
                                value="${item.id}" data-amount="${item.amount}" onchange="updateBulkHighlights()">
                        </td>
                        <td class="px-4 py-4 font-mono text-xs font-bold text-gray-600">${item.reference_id} ${riskBadge}</td>
                        <td class="px-4 py-4">
                            <div class="font-semibold text-gray-800">${col2}</div>
                            ${addrHtml}
                        </td>
                        <td class="px-4 py-4 text-gray-500 text-xs">${extra}</td>
                        <td class="px-4 py-4 text-right font-black text-gray-900">₱${parseFloat(item.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    `;
                    bulkList.appendChild(row);
                }

                // Show AI summary if highlighter is available
                if (window.aiTableHighlighter && bulkAnalyses.length > 0) {
                    window.lastBulkAnalysis = bulkAnalyses;
                    window.aiTableHighlighter.showBulkSummary(data.data, bulkAnalyses);
                }
            }
        } else {
            bulkList.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-500 font-medium">Error: ${data.message}</td></tr>`;
        }
    } catch (error) {
        bulkList.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-500 font-medium">Error connecting to server: ${error.message}</td></tr>`;
    }
}

function transactionTypeLabel(type) {
    if (type === 'vendors') return 'vendor payments';
    if (type === 'reimbursement') return 'reimbursements';
    if (type === 'payroll') return 'payroll entries';
    return 'items';
}

function closeBulkModal() {
    document.getElementById('bulkApproveModal').style.display = 'none';
}

function toggleAllBulk(master) {
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateBulkHighlights();
}

function updateBulkHighlights() {
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox');
    let total = 0;
    let count = 0;
    
    checkboxes.forEach(cb => {
        if (cb.checked) {
            total += parseFloat(cb.getAttribute('data-amount')) || 0;
            count++;
        }
    });
    
    document.getElementById('bulkTotalAmount').textContent = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('bulkSelectedCount').textContent = count + ' items selected';
    document.getElementById('bulkApproveSubmitBtn').disabled = count === 0;
}

async function submitBulkApproval() {
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    const btn = document.getElementById('bulkApproveSubmitBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'bulk_disburse_payout');
        ids.forEach(id => formData.append('ids[]', id));
        
        const response = await fetch('payout.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        showToast('Error: ' + error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function filterAndPaginateRows() {
    let allRows = Array.from(document.querySelectorAll('#payoutTableBody > tr'));
    
    // Explicitly sort rows
    // BULLETPROOF SORTING
    allRows.sort((a, b) => {
        // Use string comparison for approved_date (format is YYYY-MM-DD HH:MM:SS)
        const dateA = a.getAttribute('data-approved-full') || '';
        const dateB = b.getAttribute('data-approved-full') || '';
        
        if (dateB !== dateA) {
            return dateB > dateA ? 1 : -1;
        }
        
        // Secondary sort: ID descending (Numeric)
        const idA = parseInt(a.getAttribute('data-id') || 0);
        const idB = parseInt(b.getAttribute('data-id') || 0);
        return idB - idA;
    });

    const search = document.getElementById('searchInput').value.toLowerCase();
    const dueDate = document.getElementById('dueDate').value;
    
    return allRows.filter(row => {
        // Filter by tab (expense category + transaction/payout type)
        let category = (row.getAttribute('data-category') || '').toLowerCase();
        let payoutType = (row.getAttribute('data-payouttype') || '').toLowerCase();
        let transType = (row.getAttribute('data-transtype') || '').toLowerCase();
        
        const isPayroll = category === 'payroll' || payoutType === 'payroll' || transType === 'payroll';
        const isReimbursement = category === 'reimbursement' || payoutType === 'reimbursement' || transType === 'reimbursement';
        const isDriver = category === 'driver payout' || payoutType === 'driver' || transType === 'driver payout';

        if (currentTab === 'vendors') {
            // Vendors/Supplier: exclude ANY payroll, reimbursement, or driver record
            if (isPayroll || isReimbursement || isDriver) return false;
        } else if (currentTab === 'reimbursement') {
            // Reimbursement: only show reimbursement
            if (!isReimbursement) return false;
        } else if (currentTab === 'payroll') {
            // Payroll: only show payroll
            if (!isPayroll) return false;
        } else if (currentTab === 'driver') {
            // Driver: only show driver
            const isDriver = category === 'driver payout' || payoutType === 'driver' || transType === 'driver payout';
            if (!isDriver) return false;
        }
        
        // Filter by mode of payment
        if (modeOfPayment !== 'all') {
            let rowMode = row.getAttribute('data-mode');
            if (modeOfPayment === 'bank') {
                // Match both 'bank' and 'bank transfer'
                if (!(rowMode === 'bank' || rowMode === 'bank transfer')) return false;
            } else {
                if (rowMode !== modeOfPayment) return false;
            }
        }
        
        // Filter by search and date
        let refid = row.getAttribute('data-refid').toLowerCase();
        let acct = row.getAttribute('data-acct').toLowerCase();
        let dept = row.getAttribute('data-dept').toLowerCase();
        let desc = row.getAttribute('data-desc').toLowerCase();
        let rowDue = row.getAttribute('data-duedate');
        let matchSearch = refid.includes(search) || acct.includes(search) || dept.includes(search) || desc.includes(search) || category.includes(search) || (rowDue && rowDue.includes(search));
        let matchDate = (!dueDate || rowDue === dueDate);
        
        return matchSearch && matchDate;
    });
}

function renderTable(page) {
    // Determine target table based on currentTab
    const targetBodyId = currentTab + 'TableBody';
    const targetBody = document.getElementById(targetBodyId);
    if (!targetBody) return;

    // Clear the visible table body
    targetBody.innerHTML = '';
    
    // Get all filtered rows from the source (hidden)
    let rows = filterAndPaginateRows();
    let start = (page - 1) * rowsPerPage;
    let end = start + rowsPerPage;
    let paginated = rows.slice(start, end);
    
    if (paginated.length === 0) {
        targetBody.innerHTML = `<tr><td colspan="12" class="text-center py-10 text-gray-400">
            <i class="fas fa-folder-open text-4xl mb-3 block opacity-20"></i>
            No records found matching your filters.
        </td></tr>`;
    } else {
        paginated.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-indigo-50/30 transition-colors';
            
            // Sync all data attributes for consistency
            Array.from(row.attributes).forEach(attr => {
                tr.setAttribute(attr.name, attr.value);
            });

            const data = {
                refid: row.getAttribute('data-refid'),
                acct: row.getAttribute('data-acct'),
                dept: row.getAttribute('data-dept'),
                mode: row.getAttribute('data-mode'),
                amount: row.getAttribute('data-amount'),
                empid: row.getAttribute('data-empid'),
                walletid: row.getAttribute('data-walletid'),
                address: row.getAttribute('data-address'),
                submitted: row.getAttribute('data-submitted'),
                approved: row.getAttribute('data-approved'),
                requestedAt: row.getAttribute('data-requestedat'),
                paymentDue: row.getAttribute('data-duedate'),
                id: row.getAttribute('data-id')
            };

            const actionsHtml = `
                <div class="flex justify-center gap-2">
                    <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition-all view-btn" data-id="${data.id}" title="View Details"><i class="far fa-eye text-xs"></i></button>
                    <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center bg-green-50 text-green-600 hover:bg-green-600 hover:text-white transition-all disburse-btn" data-id="${data.id}" title="Disburse Payment"><i class="fas fa-paper-plane text-xs"></i></button>
                </div>
            `;

            if (currentTab === 'vendors') {
                tr.innerHTML = `
                    <td class='pl-6 py-4 font-mono text-xs font-bold text-gray-600'>${data.refid}</td>
                    <td class='px-4 py-4 font-semibold text-gray-800'>
                        ${data.acct}
                        <div class="text-[9px] text-gray-400 mt-1 font-normal opacity-50">Approve: ${row.getAttribute('data-approved-full') || 'N/A'} | ID: ${data.id}</div>
                    </td>
                    <td class='px-4 py-4 text-xs text-gray-500'>${data.address || 'N/A'}</td>
                    <td class='px-4 py-4 text-gray-500'>${data.dept}</td>
                    <td class='px-4 py-4 font-black text-gray-900'>₱${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class='px-4 py-4 text-center'>${actionsHtml}</td>
                `;
            } else if (currentTab === 'reimbursement') {
                tr.innerHTML = `
                    <td class='pl-6 py-4 font-mono text-xs font-bold text-gray-600'>${data.refid}</td>
                    <td class='px-4 py-4 font-semibold text-gray-800'>${data.empid}</td>
                    <td class='px-4 py-4 text-gray-500'>${data.dept}</td>
                    <td class='px-4 py-4 text-xs text-gray-500'>${data.submitted || 'N/A'}</td>
                    <td class='px-4 py-4 text-xs text-gray-500'>${data.approved || 'N/A'}</td>
                    <td class='px-4 py-4 font-black text-gray-900'>₱${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class='px-4 py-4 text-center'>${actionsHtml}</td>
                `;
            } else if (currentTab === 'payroll') {
                tr.innerHTML = `
                    <td class='pl-6 py-4 font-mono text-xs font-bold text-gray-600'>${data.refid}</td>
                    <td class='px-4 py-4 font-semibold text-gray-800'>${data.empid}</td>
                    <td class='px-4 py-4 text-gray-500'>${data.dept}</td>
                    <td class='px-4 py-4 text-xs text-gray-500'>${data.approved || 'N/A'}</td>
                    <td class='px-4 py-4 font-black text-gray-900'>₱${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class='px-4 py-4 text-center'>${actionsHtml}</td>
                `;
            } else if (currentTab === 'driver') {
                tr.innerHTML = `
                    <td class='pl-6 py-4 font-mono text-xs font-bold text-gray-600'>${data.refid}</td>
                    <td class='px-4 py-4 font-semibold text-gray-800'>${data.walletid || 'N/A'}</td>
                    <td class='px-4 py-4 font-semibold text-gray-800'>${data.acct}</td>
                    <td class='px-4 py-4 font-black text-gray-900'>₱${parseFloat(data.amount).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    <td class='px-4 py-4 text-center text-xs text-gray-500'>${data.approved || 'N/A'}</td>
                    <td class='px-4 py-4 text-center'>${actionsHtml}</td>
                `;
            }
            
            targetBody.appendChild(tr);
        });
    }
    
    // Update pagination buttons
    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = end >= rows.length;
    
    // Update page status
    const pageStatus = document.getElementById("pageStatus");
    const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));
    pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;

    // Trigger AI analysis for visible rows
    if (typeof analyzeTableRows === 'function') {
        analyzeTableRows();
    }
}


// Event listeners for search and date filters

document.getElementById('searchInput').addEventListener('input', () => { 
    currentPage = 1; 
    renderTable(currentPage); 
});

document.getElementById('dueDate').addEventListener('change', () => { 
    currentPage = 1; 
    renderTable(currentPage); 
});

document.getElementById('prevPage').addEventListener('click', () => { 
    if(currentPage > 1) {
        currentPage--; 
        renderTable(currentPage);
    }
});

document.getElementById('nextPage').addEventListener('click', () => { 
    let rows = filterAndPaginateRows();
    if(currentPage * rowsPerPage < rows.length) {
        currentPage++; 
        renderTable(currentPage); 
    }
});

// Close modal logic
const closeModalFunc = () => {
    viewModal.style.display = 'none';
};

closeModal.addEventListener('click', closeModalFunc);
closeModalBtn.addEventListener('click', closeModalFunc);

// Background AI analysis for all visible rows
async function analyzeTableRows() {
    if (typeof window.aiPayoutChecker === 'undefined' || !window.aiPayoutChecker.isInitialized) {
        setTimeout(analyzeTableRows, 1000); // Retry if not ready
        return;
    }

    const rows = document.querySelectorAll('#vendorsTableBody tr, #reimbursementTableBody tr, #payrollTableBody tr, #driverTableBody tr');
    const analyses = [];

    for (const row of rows) {
        if (row.cells.length < 2 || row.textContent.includes('No records found')) continue;
        
        const payoutData = {
            payout_id: row.getAttribute('data-refid'),
            reference_id: row.getAttribute('data-refid'),
            amount: parseFloat(row.getAttribute('data-amount')),
            reference_amount: parseFloat(row.getAttribute('data-amount')),
            payee_id: row.getAttribute('data-acct'),
            payee_type: row.getAttribute('data-payouttype') || 'Vendor',
            reference_type: row.getAttribute('data-transtype') || 'Invoice',
            approver_id: 'SYSTEM_SCAN',
            releaser_id: '<?php echo $_SESSION["user_id"] ?? "SYSTEM"; ?>',
            payment_method: row.getAttribute('data-mode') || 'CASH',
            department: row.getAttribute('data-dept') || 'OPERATIONS'
        };

        try {
            const result = await window.aiPayoutChecker.analyzePayout(payoutData);
            if (window.aiTableHighlighter) {
                // Apply highlight with detailed feedback
                window.aiTableHighlighter.highlightRow(row, result.risk_level, result.risk_score);
            }
            analyses.push(result);
        } catch (e) {
            console.warn('Analysis failed for row', payoutData.payout_id, e);
        }
    }

    // Update the AI Recommendations dashboard if it exists
    if (typeof updateAIResults === 'function' && analyses.length > 0) {
        updateAIResults(analyses); 
        
        // Update counts on the main page if there are counters
        const safeCount = analyses.filter(a => a.risk_level === 'LOW').length;
        const mediumCount = analyses.filter(a => a.risk_level === 'MEDIUM').length;
        const highCount = analyses.filter(a => a.risk_level === 'HIGH').length;
        console.log(`📊 AI Scan: ${analyses.length} items. ${safeCount} Safe, ${mediumCount} Review, ${highCount} High Risk.`);
    }
}

// Initialize
window.onload = () => { 
    renderTable(currentPage);
    
    // Start background analysis once AI is ready
    const checkAI = setInterval(() => {
        if (window.aiPayoutChecker && window.aiPayoutChecker.isInitialized) {
            clearInterval(checkAI);
            console.log('✅ AI Ready! Starting background scan...');
            analyzeTableRows();
        }
    }, 1000);
    
    console.log('✅ Page loaded! AI will initialize automatically.');
};
</script>
</body>
</html>