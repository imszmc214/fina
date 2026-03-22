<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');
require_once 'includes/accounting_functions.php';

// Suppress errors for AJAX responses to prevent JSON corruption
if (isset($_GET['action']) || isset($_POST['action']) || isset($_GET['ajax'])) {
    // We'll use ob_start to handle any accidental output and clear it before JSON
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
}

// AJAX request handling for BULK APPROVE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_approve') {
    $response = ['success' => false, 'message' => '', 'approved_count' => 0];
    
    $employee_ids = isset($_POST['employee_ids']) ? json_decode($_POST['employee_ids'], true) : [];
    $pay_periods = isset($_POST['pay_periods']) ? json_decode($_POST['pay_periods'], true) : [];
    
    if (empty($employee_ids) || empty($pay_periods)) {
        $response['message'] = "No payrolls selected for approval";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Start transaction for bulk approval
    $conn->begin_transaction();
    
    try {
        $approved_count = 0;
        
        foreach ($employee_ids as $index => $employee_id) {
            $emp_id = $conn->real_escape_string($employee_id);
            $pay_period = $conn->real_escape_string($pay_periods[$index]);
            
            // Get payroll details
            $payroll_sql = "SELECT pr.*, e.full_name, e.department, e.position 
                           FROM payroll_records pr 
                           INNER JOIN employees e ON pr.employee_id = e.employee_id 
                           WHERE pr.employee_id = ? AND pr.pay_period_end = ? AND pr.status = 'pending'";
            
            $stmt_payroll = $conn->prepare($payroll_sql);
            $stmt_payroll->bind_param("ss", $emp_id, $pay_period);
            $stmt_payroll->execute();
            $payroll_result = $stmt_payroll->get_result();
            $payroll_data = $payroll_result->fetch_assoc();
            $stmt_payroll->close();
            
            if (!$payroll_data) {
                continue; // Skip if no pending payroll found
            }
            
            // Approve payroll
            $stmt = $conn->prepare("UPDATE payroll_records SET status = 'approved', approved_date = NOW() 
                                   WHERE employee_id = ? AND pay_period_end = ? AND status = 'pending'");
            
            if ($stmt) {
                $stmt->bind_param("ss", $emp_id, $pay_period);
                
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    // Create payout record in pa table
                    $reference_id = 'PA-' . strtoupper(substr(md5(uniqid() . $emp_id), 0, 8));
                    $account_name = $payroll_data['full_name'];
                    $requested_department = $payroll_data['department'];
                    $mode_of_payment = 'Bank'; // Default for payroll
                    $expense_categories = 'Payroll';
                    $amount = $payroll_data['net_salary'];
                    $description = "Payroll for " . $payroll_data['full_name'] . " - " . $payroll_data['position'] . 
                                  " (Period: " . date('M d', strtotime($payroll_data['pay_period_start'])) . 
                                  " - " . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . ")";
                    $payment_due = date('Y-m-d', strtotime('+3 days'));
                    $requested_at = date('Y-m-d H:i:s');
                    
                    // Check if payout already exists for this payroll
                    $check_sql = "SELECT COUNT(*) as count FROM pa WHERE description LIKE ? AND amount = ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $search_desc = "%Payroll for " . $payroll_data['full_name'] . "%" . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . "%";
                    $check_stmt->bind_param("sd", $search_desc, $amount);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $check_data = $check_result->fetch_assoc();
                    $check_stmt->close();
                    
                    if ($check_data['count'] == 0) {
                        // Safe ID Workaround
                        $next_pa_id = getNextAvailableId($conn, 'pa');
                        
                        // Insert into pa table (payout approvals)
                        $payout_sql = "INSERT INTO pa (
                            id, reference_id, account_name, employee_id, requested_department, mode_of_payment,
                            expense_categories, amount, description, document, payment_due, requested_at, 
                            submitted_date, approved_date, transaction_type, payout_type, source_module, status,
                            bank_name, bank_account_number, bank_account_name, 
                            ecash_provider, ecash_account_name, ecash_account_number
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Payroll', 'Payroll', 'Payroll', 'Pending Disbursement', '', '', '', '', '', '')";
                        
                        $stmt_payout = $conn->prepare($payout_sql);
                        $ref_id = 'PAY-' . $payroll_data['id'];
                        // Use NOW() for submitted_date if not specific requested_at
                        $sub_date = date('Y-m-d H:i:s');
                        $empty_doc = '';
                        
                        $stmt_payout->bind_param("issssssdssss", 
                            $next_pa_id,
                            $ref_id,
                            $account_name,
                            $emp_id,
                            $requested_department,
                            $mode_of_payment,
                            $expense_categories,
                            $amount,
                            $description,
                            $empty_doc,
                            $payment_due,
                            $requested_at
                        );
                        
                        if ($stmt_payout->execute()) {
                            $approved_count++;
                            
                            // Create journal entry and post to ledger
                            try {
                                $journal_number = createPayrollJournalEntry($conn, $payroll_data);
                                error_log("Journal entry created: $journal_number for payroll: " . $payroll_data['employee_id']);
                            } catch (Exception $e) {
                                error_log("WARNING: Failed to create journal entry for payroll " . $payroll_data['employee_id'] . ": " . $e->getMessage());
                            }
                        } else {
                            error_log("PA Insert failed: " . $stmt_payout->error);
                        }
                        $stmt_payout->close();
                    }
                }
                $stmt->close();
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['approved_count'] = $approved_count;
        $response['message'] = "Successfully approved $approved_count payroll(s) and created payout records!";
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for GET PENDING PAYROLLS FOR BULK APPROVE
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_pending_payrolls') {
    $response = ['success' => false, 'data' => []];
    
    $sql = "SELECT 
                e.employee_id,
                e.full_name,
                e.department,
                e.employee_type,
                pr.net_salary,
                pr.pay_period_end,
                pr.base_salary,
                e.position,
                pr.pay_period_start
            FROM employees e
            INNER JOIN payroll_records pr ON e.employee_id = pr.employee_id
            WHERE pr.status = 'pending'
            ORDER BY e.department, e.full_name";
    
    $result = $conn->query($sql);
    
    if ($result) {
        $payrolls = [];
        $totalNetPay = 0;
        
        while ($row = $result->fetch_assoc()) {
            $payrolls[] = $row;
            $totalNetPay += $row['net_salary'];
        }
        
        $response['success'] = true;
        $response['data']['payrolls'] = $payrolls;
        $response['data']['total'] = count($payrolls);
        $response['data']['totalNetPay'] = $totalNetPay;
    } else {
        $response['message'] = "Error fetching pending payrolls: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for APPROVE PAYROLL (single)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_payroll') {
    $response = ['success' => false, 'message' => ''];
    
    $employee_id = isset($_POST['employee_id']) ? $conn->real_escape_string($_POST['employee_id']) : '';
    
    if (empty($employee_id)) {
        $response['message'] = "No employee selected";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Get payroll details
        $payroll_sql = "SELECT pr.*, e.full_name, e.department, e.position 
                       FROM payroll_records pr 
                       INNER JOIN employees e ON pr.employee_id = e.employee_id 
                       WHERE pr.employee_id = ? AND pr.status = 'pending' 
                       ORDER BY pr.pay_period_end DESC LIMIT 1";
        
        $stmt_payroll = $conn->prepare($payroll_sql);
        $stmt_payroll->bind_param("s", $employee_id);
        $stmt_payroll->execute();
        $payroll_result = $stmt_payroll->get_result();
        $payroll_data = $payroll_result->fetch_assoc();
        $stmt_payroll->close();
        
        if (!$payroll_data) {
            throw new Exception("No pending payroll found for this employee");
        }
        
        // 2. Update payroll status to approved
        $stmt = $conn->prepare("UPDATE payroll_records SET status = 'approved', approved_date = NOW() 
                               WHERE employee_id = ? AND status = 'pending'");
        $stmt->bind_param("s", $employee_id);
        
        if (!$stmt->execute() || $stmt->affected_rows == 0) {
            throw new Exception("Failed to approve payroll");
        }
        $stmt->close();
        
        // 3. Create payout record in pa table
        $reference_id = 'PA-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $account_name = $payroll_data['full_name'];
        $requested_department = $payroll_data['department'];
        $mode_of_payment = 'Bank'; // Default, can be changed
        $expense_categories = 'Payroll';
        $amount = $payroll_data['net_salary'];
        $description = "Payroll for " . $payroll_data['full_name'] . " - " . $payroll_data['position'] . 
                      " (Period: " . date('M d', strtotime($payroll_data['pay_period_start'])) . 
                      " - " . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . ")";
        $payment_due = date('Y-m-d', strtotime('+3 days')); // 3 days from now
        $requested_at = date('Y-m-d H:i:s');
        
        // Check if payout already exists
        $check_sql = "SELECT COUNT(*) as count FROM pa WHERE description LIKE ? AND amount = ?";
        $check_stmt = $conn->prepare($check_sql);
        $search_desc = "%Payroll for " . $payroll_data['full_name'] . "%" . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . "%";
        $check_stmt->bind_param("sd", $search_desc, $amount);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_data['count'] > 0) {
            throw new Exception("Payout record already exists for this payroll");
        }
        
        // Safe ID Workaround
        $next_pa_id = getNextAvailableId($conn, 'pa');
        
        $payout_sql = "INSERT INTO pa (
            id, reference_id, account_name, employee_id, requested_department, mode_of_payment,
            expense_categories, amount, description, document, payment_due, requested_at,
            submitted_date, approved_date, transaction_type, payout_type, source_module, status,
            bank_name, bank_account_number, bank_account_name, 
            ecash_provider, ecash_account_name, ecash_account_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Payroll', 'Payroll', 'Payroll', 'Pending Disbursement', '', '', '', '', '', '')";
        
        $stmt_payout = $conn->prepare($payout_sql);
        $ref_id = 'PAY-' . $payroll_data['id'];
        $sub_date = date('Y-m-d H:i:s');
        $empty_doc = '';
        
        $stmt_payout->bind_param("issssssdssss", 
            $next_pa_id,
            $ref_id,
            $account_name,
            $employee_id,
            $requested_department,
            $mode_of_payment,
            $expense_categories,
            $amount,
            $description,
            $empty_doc,
            $payment_due,
            $requested_at
        );
        
        if (!$stmt_payout->execute()) {
            throw new Exception("Failed to create payout record: " . $stmt_payout->error);
        }
        $stmt_payout->close();
        
        // Create journal entry and post to ledger
        try {
            $journal_number = createPayrollJournalEntry($conn, $payroll_data);
            error_log("Journal entry created: $journal_number for payroll: " . $payroll_data['employee_id']);
        } catch (Exception $e) {
            error_log("WARNING: Failed to create journal entry for payroll " . $payroll_data['employee_id'] . ": " . $e->getMessage());
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Payroll approved and payout created successfully!";
        $response['payout_reference'] = $reference_id;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for REJECT PAYROLL (single)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_payroll') {
    $response = ['success' => false, 'message' => ''];
    
    $employee_id = isset($_POST['employee_id']) ? $conn->real_escape_string($_POST['employee_id']) : '';
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : '';
    
    if (empty($employee_id)) {
        $response['message'] = "No employee selected";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    if (empty($reason)) {
        $response['message'] = "Rejection reason is required";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE payroll_records SET status = 'rejected', rejection_reason = ?, rejected_date = NOW() 
                           WHERE employee_id = ? AND status = 'pending'");
    
    if ($stmt) {
        $stmt->bind_param("ss", $reason, $employee_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "Payroll rejected successfully!";
        } else {
            $response['message'] = "No pending payroll found for this employee";
        }
        $stmt->close();
    } else {
        $response['message'] = "Database error: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for EMPLOYEE DETAILS
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_employee_details') {
    // Clear any previous output (like BOM from parent files)
    if (ob_get_length()) ob_clean();
    
    $employee_id = isset($_GET['employee_id']) ? $conn->real_escape_string($_GET['employee_id']) : '';
    $response = ['success' => false, 'data' => [], 'error' => ''];
    
    try {
        if ($employee_id) {
            // Get employee details
            $employee_sql = "SELECT * FROM employees WHERE employee_id = '$employee_id'";
            $employee_result = $conn->query($employee_sql);
            
            if ($employee_result && $row = $employee_result->fetch_assoc()) {
                $response['success'] = true;
                $response['data']['employee'] = $row;
                
                // Get current pay period data (only pending payrolls)
                $payroll_sql = "SELECT * FROM payroll_records WHERE employee_id = '$employee_id' 
                            AND status = 'pending'
                            ORDER BY pay_period_end DESC LIMIT 1";
                $payroll_result = $conn->query($payroll_sql);
                
                if ($payroll_result && $payroll_data = $payroll_result->fetch_assoc()) {
                    $response['data']['payroll'] = $payroll_data;
                    
                    // Get attendance for current period
                    $attendance_sql = "SELECT 
                        COUNT(*) as total_days,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as days_present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as days_absent,
                        SUM(CASE WHEN status = 'holiday' THEN 1 ELSE 0 END) as working_holidays,
                        SUM(CASE WHEN status = 'pto' THEN 1 ELSE 0 END) as pto_days,
                        SUM(IFNULL(regular_hours, 0)) as total_regular_hours,
                        SUM(IFNULL(overtime_hours, 0)) as total_overtime_hours
                        FROM attendance 
                        WHERE employee_id = '$employee_id' 
                        AND attendance_date BETWEEN '{$payroll_data['pay_period_start']}' AND '{$payroll_data['pay_period_end']}'";
                    
                    $attendance_result = $conn->query($attendance_sql);
                    if ($attendance_result) {
                        $response['data']['attendance'] = $attendance_result->fetch_assoc();
                    }
                }
            } else {
                $response['error'] = 'Employee not found';
            }
        } else {
            $response['error'] = 'No employee ID provided';
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for GET BUDGET ALLOCATION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_budget') {
    $response = ['success' => false, 'message' => '', 'data' => null];
    $department = $_POST['department'] ?? '';
    
    try {
        // Handle department name cleaning if needed (e.g. removing suffixes like -1, -2)
        // But for now, let's try exact match first as per vendor.php
        $budget_sql = "SELECT * FROM budget_allocations WHERE department = ? AND status = 'active' ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($budget_sql);
        if ($stmt) {
            $stmt->bind_param("s", $department);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $budget = $result->fetch_assoc();
                $response['success'] = true;
                $response['data'] = $budget;
            } else {
                $response['message'] = "No budget allocation found for department: $department";
            }
            $stmt->close();
        } else {
            throw new Exception("Database prepare failed");
        }
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    // Clear any output buffers to ensure clean JSON
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for UPDATE PAYROLL (Adjustment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payroll') {
    $response = ['success' => false, 'message' => ''];
    
    $employee_id = $_POST['employee_id'] ?? '';
    $pay_period = $_POST['pay_period'] ?? '';
    
    if (empty($employee_id) || empty($pay_period)) {
        $response['message'] = "Missing required data";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Extract data
    $regular_pay = floatval($_POST['regular_pay'] ?? 0);
    $overtime_pay = floatval($_POST['overtime_pay'] ?? 0);
    $holiday_pay = floatval($_POST['holiday_pay'] ?? 0);
    $allowances = floatval($_POST['allowances'] ?? 0);
    $gross_salary = floatval($_POST['gross_salary'] ?? 0);
    
    $tax_amount = floatval($_POST['tax_amount'] ?? 0);
    $sss_amount = floatval($_POST['sss_amount'] ?? 0);
    $philhealth_amount = floatval($_POST['philhealth_amount'] ?? 0);
    $pagibig_amount = floatval($_POST['pagibig_amount'] ?? 0);
    $total_deductions = floatval($_POST['total_deductions'] ?? 0);
    
    $net_salary = floatval($_POST['net_salary'] ?? 0);
    
    try {
        $update_sql = "UPDATE payroll_records SET 
                       regular_pay = ?, overtime_pay = ?, holiday_pay = ?, allowances = ?, gross_salary = ?,
                       tax_amount = ?, sss_amount = ?, philhealth_amount = ?, pagibig_amount = ?, total_deductions = ?,
                       net_salary = ?
                       WHERE employee_id = ? AND pay_period_end = ? AND status = 'pending'";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("dddddddddddss", 
            $regular_pay, $overtime_pay, $holiday_pay, $allowances, $gross_salary,
            $tax_amount, $sss_amount, $philhealth_amount, $pagibig_amount, $total_deductions,
            $net_salary, $employee_id, $pay_period
        );
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows >= 0) { // >= 0 because maybe nothing changed but still success
                $response['success'] = true;
                $response['message'] = "Payroll adjustments saved successfully!";
            } else {
                $response['message'] = "Record not found or already approved.";
            }
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for APPROVE PAYROLL (with pay period)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve') {
    $response = ['success' => false, 'message' => ''];
    
    $employee_id = isset($_POST['employee_id']) ? $conn->real_escape_string($_POST['employee_id']) : '';
    $pay_period = isset($_POST['pay_period']) ? $conn->real_escape_string($_POST['pay_period']) : '';
    
    if (empty($employee_id) || empty($pay_period)) {
        $response['message'] = "Missing required data";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get payroll details
        $payroll_sql = "SELECT pr.*, e.full_name, e.department, e.position 
                       FROM payroll_records pr 
                       INNER JOIN employees e ON pr.employee_id = e.employee_id 
                       WHERE pr.employee_id = ? AND pr.pay_period_end = ? AND pr.status = 'pending'";
        
        $stmt_payroll = $conn->prepare($payroll_sql);
        $stmt_payroll->bind_param("ss", $employee_id, $pay_period);
        $stmt_payroll->execute();
        $payroll_result = $stmt_payroll->get_result();
        $payroll_data = $payroll_result->fetch_assoc();
        $stmt_payroll->close();
        
        if (!$payroll_data) {
            throw new Exception("No pending payroll found for this employee and pay period");
        }
        
        // Update payroll status to approved
        $stmt = $conn->prepare("UPDATE payroll_records SET status = 'approved', approved_date = NOW() 
                               WHERE employee_id = ? AND pay_period_end = ? AND status = 'pending'");
        $stmt->bind_param("ss", $employee_id, $pay_period);
        
        if (!$stmt->execute() || $stmt->affected_rows == 0) {
            throw new Exception("Failed to approve payroll");
        }
        $stmt->close();
        
        // Create payout record in pa table
        $reference_id = 'PA-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $account_name = $payroll_data['full_name'];
        $requested_department = $payroll_data['department'];
        $mode_of_payment = 'Bank';
        $expense_categories = 'Payroll';
        $amount = $payroll_data['net_salary'];
        $description = "Payroll for " . $payroll_data['full_name'] . " - " . $payroll_data['position'] . 
                      " (Period: " . date('M d', strtotime($payroll_data['pay_period_start'])) . 
                      " - " . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . ")";
        $payment_due = date('Y-m-d', strtotime('+3 days'));
        $requested_at = date('Y-m-d H:i:s');
        
        // Check if payout already exists
        $check_sql = "SELECT COUNT(*) as count FROM pa WHERE description LIKE ? AND amount = ?";
        $check_stmt = $conn->prepare($check_sql);
        $search_desc = "%Payroll for " . $payroll_data['full_name'] . "%" . date('M d, Y', strtotime($payroll_data['pay_period_end'])) . "%";
        $check_stmt->bind_param("sd", $search_desc, $amount);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_data['count'] > 0) {
            throw new Exception("Payout record already exists for this payroll");
        }
        
        // Safe ID Workaround
        $next_pa_id = getNextAvailableId($conn, 'pa');
        
        $payout_sql = "INSERT INTO pa (
            id, reference_id, account_name, employee_id, requested_department, mode_of_payment,
            expense_categories, amount, description, document, payment_due, requested_at, submitted_date, approved_date,
            transaction_type, payout_type, source_module, status,
            bank_name, bank_account_number, bank_account_name, 
            ecash_provider, ecash_account_name, ecash_account_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Payroll', 'Payroll', 'Payroll', 'Pending Disbursement', '', '', '', '', '', '')";
        
        $stmt_payout = $conn->prepare($payout_sql);
        $empty_doc = '';
        $stmt_payout->bind_param("issssssdssss", 
            $next_pa_id,
            $reference_id,
            $account_name,
            $employee_id,
            $requested_department,
            $mode_of_payment,
            $expense_categories,
            $amount,
            $description,
            $empty_doc,
            $payment_due,
            $requested_at
        );
        
        if (!$stmt_payout->execute()) {
            throw new Exception("Failed to create payout record: " . $stmt_payout->error);
        }
        $stmt_payout->close();
        
        // Create journal entry and post to ledger
        try {
            $journal_number = createPayrollJournalEntry($conn, $payroll_data);
            error_log("Journal entry created: $journal_number for payroll: " . $payroll_data['employee_id']);
        } catch (Exception $e) {
            error_log("WARNING: Failed to create journal entry for payroll " . $payroll_data['employee_id'] . ": " . $e->getMessage());
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = "Payroll approved and payout created successfully!";
        $response['payout_reference'] = $reference_id;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for REJECT PAYROLL (with pay period)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $response = ['success' => false, 'message' => ''];
    
    $employee_id = isset($_POST['employee_id']) ? $conn->real_escape_string($_POST['employee_id']) : '';
    $pay_period = isset($_POST['pay_period']) ? $conn->real_escape_string($_POST['pay_period']) : '';
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : '';
    
    if (empty($employee_id) || empty($pay_period)) {
        $response['message'] = "Missing required data";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    // Update payroll status to rejected
    $stmt = $conn->prepare("UPDATE payroll_records SET status = 'rejected', rejection_reason = ?, rejected_date = NOW() 
                           WHERE employee_id = ? AND pay_period_end = ? AND status = 'pending'");
    
    if ($stmt) {
        $stmt->bind_param("sss", $reason, $employee_id, $pay_period);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Payroll rejected successfully!";
        } else {
            $response['message'] = "Rejection failed: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Prepare failed: " . $conn->error;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for table data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $department_filter = isset($_GET['department_filter']) ? $conn->real_escape_string($_GET['department_filter']) : '';
    $status_filter = isset($_GET['status_filter']) ? $conn->real_escape_string($_GET['status_filter']) : 'pending';
    $type_filter = isset($_GET['type_filter']) ? $conn->real_escape_string($_GET['type_filter']) : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;

    $conditions = [];

    // Add filters if provided
    if ($department_filter != '') {
        $conditions[] = "e.department = '" . $conn->real_escape_string($department_filter) . "'";
    }
    
    if ($status_filter != '' && $status_filter != 'all') {
        $conditions[] = "pr.status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    
    if ($type_filter != '') {
        $conditions[] = "e.employee_type = '" . $conn->real_escape_string($type_filter) . "'";
    }

    // Count Total Records
    $count_sql = "SELECT COUNT(*) as total 
                  FROM employees e
                  INNER JOIN payroll_records pr ON e.employee_id = pr.employee_id
                  INNER JOIN (
                      SELECT employee_id, MAX(pay_period_end) as latest_period
                      FROM payroll_records
                      GROUP BY employee_id
                  ) latest ON pr.employee_id = latest.employee_id AND pr.pay_period_end = latest.latest_period";
    
    if (!empty($conditions)) {
        $count_sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $count_result = $conn->query($count_sql);
    $row_count = $count_result->fetch_assoc();
    $total_rows = $row_count['total'];
    $total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

    // Build Main Query
    $sql = "SELECT 
                e.employee_id,
                e.full_name,
                e.position,
                e.employee_type,
                e.department,
                pr.status,
                pr.base_salary,
                pr.net_salary,
                pr.pay_period_start,
                pr.pay_period_end,
                pr.days_present,
                pr.scheduled_days,
                pr.overtime_hours,
                pr.absent_days,
                pr.working_holidays,
                pr.pto_days
            FROM employees e
            INNER JOIN payroll_records pr ON e.employee_id = pr.employee_id
            INNER JOIN (
                SELECT employee_id, MAX(pay_period_end) as latest_period
                FROM payroll_records
                GROUP BY employee_id
            ) latest ON pr.employee_id = latest.employee_id AND pr.pay_period_end = latest.latest_period";
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY pr.status, e.department, e.full_name 
              LIMIT $records_per_page OFFSET $offset";

    $result = $conn->query($sql);
    
    // Calculate totals
    $totalEmployees = 0;
    $totalContractual = 0;
    $totalRegular = 0;
    $totalNetPay = 0;
    $totalDepartments = 0;
    $uniqueDepartments = [];
    $pendingCount = 0;
    $pendingNetPay = 0;
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $totalEmployees++;
        if ($row['employee_type'] === 'Contractual') {
            $totalContractual++;
        } else if ($row['employee_type'] === 'Regular') {
            $totalRegular++;
        }
        
        if ($row['status'] === 'pending') {
            $pendingCount++;
            $pendingNetPay += $row['net_salary'];
        }
        
        if (!in_array($row['department'], $uniqueDepartments)) {
            $uniqueDepartments[] = $row['department'];
        }
        
        $rows[] = $row;
    }
    
    $totalDepartments = count($uniqueDepartments);

    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'totalEmployees' => $totalEmployees,
        'totalContractual' => $totalContractual,
        'totalRegular' => $totalRegular,
        'totalNetPay' => $totalNetPay,
        'pendingCount' => $pendingCount,
        'pendingNetPay' => $pendingNetPay,
        'totalDepartments' => $totalDepartments,
        'total' => $total_rows,
        'page' => $page,
        'pages' => $total_pages,
        'offset' => $offset,
        'records_per_page' => $records_per_page
    ]);
    exit();
}

// Initial page load
$department_filter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'pending';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

$conditions = [];

if ($department_filter != '') {
    $conditions[] = "e.department = '" . $conn->real_escape_string($department_filter) . "'";
}

if ($status_filter != '' && $status_filter != 'all') {
    $conditions[] = "pr.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($type_filter != '') {
    $conditions[] = "e.employee_type = '" . $conn->real_escape_string($type_filter) . "'";
}

$count_sql = "SELECT COUNT(*) as total 
              FROM employees e
              INNER JOIN payroll_records pr ON e.employee_id = pr.employee_id
              INNER JOIN (
                  SELECT employee_id, MAX(pay_period_end) as latest_period
                  FROM payroll_records
                  GROUP BY employee_id
              ) latest ON pr.employee_id = latest.employee_id AND pr.pay_period_end = latest.latest_period";

if (!empty($conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $conditions);
}

$count_result = $conn->query($count_sql);
$row_count = $count_result->fetch_assoc();
$total_rows = $row_count['total'];
$total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

// Main Query
$sql = "SELECT 
            e.employee_id,
            e.full_name,
            e.position,
            e.employee_type,
            e.department,
            pr.status,
            pr.base_salary,
            pr.net_salary,
            pr.pay_period_start,
            pr.pay_period_end,
            pr.days_present,
            pr.scheduled_days,
            pr.overtime_hours,
            pr.absent_days,
            pr.working_holidays,
            pr.pto_days
        FROM employees e
        INNER JOIN payroll_records pr ON e.employee_id = pr.employee_id
        INNER JOIN (
            SELECT employee_id, MAX(pay_period_end) as latest_period
            FROM payroll_records
            GROUP BY employee_id
        ) latest ON pr.employee_id = latest.employee_id AND pr.pay_period_end = latest.latest_period";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY pr.status, e.department, e.full_name 
          LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);

// Calculate totals for initial load
$totalEmployees = 0;
$totalContractual = 0;
$totalRegular = 0;
$totalNetPay = 0;
$totalDepartments = 0;
$uniqueDepartments = [];
$pendingCount = 0;
$pendingNetPay = 0;

$rows_data = [];
while ($row = $result->fetch_assoc()) {
    $totalEmployees++;
    if ($row['employee_type'] === 'Contractual') {
        $totalContractual++;
    } else if ($row['employee_type'] === 'Regular') {
        $totalRegular++;
    }
    
    if ($row['status'] === 'pending') {
        $pendingCount++;
        $pendingNetPay += $row['net_salary'];
    }
    
    if (!in_array($row['department'], $uniqueDepartments)) {
        $uniqueDepartments[] = $row['department'];
    }
    
    $rows_data[] = $row;
}

$totalDepartments = count($uniqueDepartments);

// Department list based on your images
$departments = ['Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Core-1', 'Core-2', 'Logistic-1', 'Logistic-2', 'Administrative', 'Financial'];

// Position list
$positions = ['Delivery Assistant', 'Warehouse Officer', 'Logistics Manager', 
              'HR Assistant', 'HR Specialist', 'HR Manager', 
              'Accountant', 'Financial Analyst', 'IT Support', 
              'Operations Manager', 'Marketing Specialist'];
// AJAX response for table data
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'data' => $rows_data,
        'pages' => $total_pages,
        'totalEmployees' => $totalEmployees,
        'totalContractual' => $totalContractual,
        'totalRegular' => $totalRegular,
        'pendingNetPay' => $pendingNetPay,
        'pendingCount' => $pendingCount
    ]);
    exit();
}
?>

<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.0/jspdf.plugin.autotable.min.js"></script>
<?php endif; ?>
    <style>
        /* INCLUDE ALL THE SAME STYLES FROM BUDGET ALLOCATION */
        .bg-gray-50 { background-color: #f9fafb; }
        .px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .font-bold { font-weight: 700; }
        .text-gray-800 { color: #1f2937; }
        .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
        .text-gray-600 { color: #4b5563; }
        .text-blue-600 { color: #2563eb; }
        .hover\:text-blue-800:hover { color: #1e40af; }
        .mx-2 { margin-left: 0.5rem; margin-right: 0.5rem; }
        .text-gray-500 { color: #6b7280; }
        .text-gray-800 { color: #1f2937; }
        .font-medium { font-weight: 500; }
        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .md\:grid-cols-4 { @media (min-width: 768px) { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        .gap-6 { gap: 1.5rem; }
        .mb-8 { margin-bottom: 2rem; }
        .p-6 { padding: 1.5rem; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .flex-wrap { flex-wrap: wrap; }
        .gap-4 { gap: 1rem; }
        .gap-2 { gap: 0.5rem; }
        .mr-2 { margin-right: 0.5rem; }
        .ml-2 { margin-left: 0.5rem; }
        .block { display: block; }
        .inline-block { display: inline-block; }
        
        .border { border-width: 1px; }
        .border-gray-300 { border-color: #d1d5db; }
        .rounded-lg { border-radius: 0.5rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
        .focus\:ring-2:focus { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
        .focus\:ring-purple-500:focus { --tw-ring-opacity: 1; --tw-ring-color: rgb(139 92 246 / var(--tw-ring-opacity)); }
        .focus\:border-transparent:focus { border-color: transparent; }
        
        .overflow-x-auto { overflow-x: auto; }
        .w-full { width: 100%; }
        .border-collapse { border-collapse: collapse; }
        .text-left { text-align: left; }
        .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
        .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }
        .text-gray-300 { color: #d1d5db; }
        .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
        .pr-4 { padding-right: 1rem; }
        .text-purple-700 { color: #7c3aed; }
        .text-blue-700 { color: #1d4ed8; }
        .text-orange-700 { color: #c2410c; }
        .text-red-700 { color: #b91c1c; }
        .text-green-700 { color: #15803d; }
        .mt-6 { margin-top: 1.5rem; }
        .mt-8 { margin-top: 2rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        
        .fixed { position: fixed; }
        .top-0 { top: 0; }
        .left-0 { left: 0; }
        .right-0 { right: 0; }
        .bottom-0 { bottom: 0; }
        .bg-white { background-color: #ffffff; }
        .rounded-12px { border-radius: 12px; }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1); }
        .max-w-md { max-width: 28rem; }
        .space-y-4 > * + * { margin-top: 1rem; }
        .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .gap-3 { gap: 0.75rem; }
        .flex-1 { flex: 1 1 0%; }
        
        .theme-gradient-bg {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        
        .theme-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .theme-button {
            background: #7c3aed;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-button:hover {
            background: #6d28d9;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.2);
        }
        
        .theme-button-secondary {
            background: #f8fafc;
            color: #374151;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-button-secondary:hover {
            background: #f1f5f9;
            border-color: #d1d5db;
        }
        
        .theme-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .badge-contractual {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-regular {
            background: #fce7f3;
            color: #be185d;
        }
        
        .theme-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .theme-table th {
            background: #f8fafc;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .theme-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .theme-table tr:hover {
            background: #f9fafb;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-4px);
        }
        
        .card-icon {
            background: #7c3aed;
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .card-label {
            font-size: 14px;
            color: #6b7280;
        }
        
        .card-value {
            font-size: 24px;
            font-weight: bold;
            margin: 4px 0;
            color: #1f2937;
        }
        
        .card-change {
            font-size: 12px;
            color: #059669;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            z-index: 9999; /* Increased to cover sidebar and cards */
            display: none;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .modal-overlay.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            max-width: 2000px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .toast {
            background: #7c3aed;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            padding: 12px 16px;
            border-radius: 8px;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .toast.error {
            background: #ef4444;
        }
        
        .toast.success {
            background: #10b981;
        }
        
        .toast.info {
            background: #3b82f6;
        }
        
        /* PAYROLL DETAILS MODAL */
        .payroll-icon-large {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: white;
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .payroll-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .payroll-info {
            flex: 1;
            margin-left: 20px;
        }
        
        .payroll-name {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .payroll-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .payroll-period {
            font-size: 14px;
            color: #4f46e5;
            background: #eef2ff;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .attendance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 24px 0;
        }
        
        .attendance-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .attendance-card .label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .attendance-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin: 4px 0;
        }
        
        .attendance-card .subvalue {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .attendance-card.days {
            border-top: 4px solid #7c3aed;
        }
        
        .attendance-card.hours {
            border-top: 4px solid #3b82f6;
        }
        
        .attendance-card.absent {
            border-top: 4px solid #ef4444;
        }
        
        .attendance-card.holidays {
            border-top: 4px solid #10b981;
        }
        
        .salary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 24px 0;
        }
        
        .salary-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
        }
        
        .salary-card h4 {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }
        
        .salary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .salary-item:last-child {
            border-bottom: none;
        }
        
        .salary-item.total {
            font-weight: bold;
            color: #1f2937;
            font-size: 18px;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        
        .salary-label {
            color: #6b7280;
        }
        
        .salary-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-manage {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-manage:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-approve:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1b);
            transform: translateY(-1px);
        }
        
        .btn-print {
            background: #f8fafc;
            color: #374151;
            border: 1px solid #e5e7eb;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print:hover {
            background: #f1f5f9;
        }
        
        .modal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-top: 1px solid #e5e7eb;
            margin-top: 20px;
        }
        
        .footer-actions {
            display: flex;
            gap: 12px;
        }
        
        .footer-info {
            display: flex;
            gap: 20px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .text-purple-500 { color: #8b5cf6; }
        .text-orange-600 { color: #ea580c; }
        .text-red-600 { color: #dc2626; }
        .text-green-600 { color: #16a34a; }
        .bg-gray-50 { background-color: #f9fafb; }
        .text-purple-700 { color: #7c3aed; }
        .text-blue-700 { color: #1d4ed8; }
        .text-orange-700 { color: #c2410c; }
        .text-red-700 { color: #b91c1c; }
        .text-green-700 { color: #15803d; }
        .opacity-50 { opacity: 0.5; }
        .cursor-not-allowed { cursor: not-allowed; }
        
        .min-h-screen { min-height: 100vh; }
        .p-4 { padding: 1rem; }
        .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
        .text-gray-400 { color: #9ca3af; }
        .hover\:text-gray-600:hover { color: #4b5563; }
        .text-2xl { font-size: 1.5rem; line-height: 2rem; }
        .w-full { width: 100%; }
        .border { border-width: 1px; }
        .border-gray-300 { border-color: #d1d5db; }
        .rounded-lg { border-radius: 0.5rem; }
        .text-xs { font-size: 0.75rem; line-height: 1rem; }
        
        /* BULK APPROVE MODAL STYLES - UPDATED TO MATCH IMAGE */
        .bulk-approve-modal .modal-content {
            max-width: 900px;
            max-height: 80vh;
            background: #ffffff;
        }
        
        .modal-header-bulk {
            padding: 24px 24px 16px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .modal-title-bulk {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .modal-subtitle-bulk {
            font-size: 14px;
            color: #6b7280;
        }
        
        .modal-body-bulk {
            padding: 24px;
        }
        
        .filters-row-bulk {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            align-items: flex-end;
        }
        
        .filter-group-bulk {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label-bulk {
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .select-filter-bulk {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 14px;
            color: #374151;
            background: white;
            width: 200px;
            min-width: 150px;
        }
        
        .select-filter-bulk:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .payrolls-table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 24px;
            background: white;
        }
        
        .bulk-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .bulk-table thead {
            background: #f8fafc;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .bulk-table th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 14px;
        }
        
        .bulk-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .bulk-table tr:hover {
            background: #f9fafb;
        }
        
        .bulk-table tr.selected {
            background: #f0f9ff;
        }
        
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }
        
        .payroll-id-cell {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #374151;
        }
        
        .payroll-name-cell {
            font-weight: 500;
            color: #1f2937;
        }
        
        .payroll-dept-cell {
            color: #6b7280;
        }
        
        .payroll-salary-cell {
            font-weight: 600;
            color: #059669;
            text-align: right;
        }
        
        .summary-row-bulk {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .summary-text-bulk {
            font-size: 14px;
            color: #374151;
        }
        
        .summary-count-bulk {
            font-weight: 600;
            color: #7c3aed;
        }
        
        .summary-amount-bulk {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        
        .modal-actions-bulk {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 24px;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn-modal-cancel-bulk {
            background: #f8fafc;
            color: #374151;
            border: 1px solid #e5e7eb;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-modal-cancel-bulk:hover {
            background: #f1f5f9;
        }
        
        .btn-modal-approve-bulk {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-modal-approve-bulk:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
        }
        
        .btn-modal-approve-bulk:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .empty-state-bulk {
            text-align: center;
            padding: 48px 24px;
            color: #9ca3af;
        }
        
        .empty-icon-bulk {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-text-bulk {
            font-size: 16px;
        }
        
        /* CURRENCY STYLES */
        .currency {
            font-family: Arial, sans-serif;
        }
        
        /* CHECKBOX STYLES */
        input[type="checkbox"] {
            accent-color: #7c3aed;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .select-all-header {
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .select-all-label {
            font-size: 14px;
            color: #374151;
            font-weight: 500;
        }
        
        /* Table layout to match image */
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table-header-row {
            background: #f8fafc;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        /* Custom Confirmation Modal Styles */
        .confirm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 20000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .confirm-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .confirm-modal-box {
            background: white;
            padding: 30px;
            border-radius: 16px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            transform: scale(0.9);
            transition: all 0.3s ease;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .confirm-modal-overlay.active .confirm-modal-box {
            transform: scale(1);
        }

        .adjust-input {
            width: 100px;
            padding: 4px 8px;
            border: 2px solid #7c3aed;
            border-radius: 6px;
            font-size: 14px;
            text-align: right;
            font-weight: bold;
            color: #7c3aed;
            background: #f5f3ff;
            outline: none;
        }

        .adjust-input:focus {
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
        }

        .confirm-modal-icon {
            width: 60px;
            height: 60px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
        }

        .confirm-modal-icon i {
            font-size: 30px;
            color: #8b5cf6;
        }

        .confirm-modal-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .confirm-modal-message {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .confirm-modal-btns {
            display: flex;
            gap: 12px;
        }

        .confirm-modal-btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .confirm-modal-btn-cancel {
            background: #f3f4f6;
            color: #4b5563;
        }

        .confirm-modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .confirm-modal-btn-confirm {
            background: #8b5cf6;
            color: white;
        }

        .confirm-modal-btn-confirm:hover {
            background: #7c3aed;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
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
    
    <div class="overflow-y-auto h-full px-6">
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <!-- Breadcrumb -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Payroll Management</h1>
            <div class="text-sm text-gray-600">
                <a href="dashboard.php?page=dashboard" class="text-blue-600 hover:text-blue-800">Home</a>
                <span class="mx-2">/</span>
                <span class="text-gray-500">Human Resources</span>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Payroll Management</span>
            </div>
        </div>
<?php endif; ?>

<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="summary-card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Total Employees</div>
                    <div class="card-value" id="totalEmployees"><?= $totalEmployees ?></div>
                    <div class="card-change"><?= $totalContractual ?> contractual, <?= $totalRegular ?> regular</div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon bg-blue-500">
                    <i class="fas fa-file-contract"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Contractual Employees</div>
                    <div class="card-value" id="totalContractual"><?= $totalContractual ?></div>
                    <div class="card-change"><?= $totalEmployees > 0 ? round(($totalContractual/$totalEmployees)*100, 1) : 0 ?>% of workforce</div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon bg-red-500">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Regular Employees</div>
                    <div class="card-value" id="totalRegular"><?= $totalRegular ?></div>
                    <div class="card-change"><?= $totalEmployees > 0 ? round(($totalRegular/$totalEmployees)*100, 1) : 0 ?>% of workforce</div>
                </div>
            </div>
            
            <div class="summary-card">
                <div class="card-icon bg-green-500">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-content">
                    <div class="card-label">Pending Payroll</div>
                    <div class="card-value" id="totalNetPay">₱<?= number_format($pendingNetPay, 2) ?></div>
                    <div class="card-change"><?= $pendingCount ?> payroll(s) for approval</div>
                </div>
            </div>
        </div>
<?php endif; ?>

        <!-- Main Content -->
        <div class="<?= !defined('UNIFIED_DASHBOARD_MODE') ? 'theme-card p-6 mb-8 fade-in' : 'p-6 fade-in' ?>">
            <!-- Header with Filters -->
            <div class="flex flex-wrap justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <!-- Department Filter -->
                    <div class="flex items-center">
                        <select id="department_filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Departments</option>
                            <?php
                            foreach ($departments as $dept) {
                                $selected = ($department_filter === $dept) ? 'selected' : '';
                                echo "<option value=\"$dept\" $selected>$dept</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div class="flex items-center">
                        <select id="status_filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <!-- Type Filter -->
                    <div class="flex items-center">
                        <select id="type_filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">All Types</option>
                            <option value="Regular" <?= $type_filter === 'Regular' ? 'selected' : '' ?>>Regular</option>
                            <option value="Contractual" <?= $type_filter === 'Contractual' ? 'selected' : '' ?>>Contractual</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex items-center gap-4">
                    <!-- Export Dropdown -->
                    <div class="relative" id="exportDropdownContainer">
                        <button onclick="toggleExportDropdown()" class="bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-800 transition-all">
                            <i class="fas fa-download"></i>
                            Export
                        </button>
                        <div id="exportDropdown" class="absolute left-0 mt-2 w-56 <?= !defined('UNIFIED_DASHBOARD_MODE') ? 'bg-white rounded-lg shadow-lg border border-gray-200' : '' ?> hidden z-50">
                            <button onclick="exportPDF()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100">
                                <i class="fas fa-file-pdf text-red-500 text-lg"></i>
                                <span class="text-gray-700">Export as PDF</span>
                            </button>
                            <button onclick="exportExcel()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3 border-b border-gray-100">
                                <i class="fas fa-file-excel text-green-500 text-lg"></i>
                                <span class="text-gray-700">Export as Excel</span>
                            </button>
                            <button onclick="exportCSV()" class="w-full px-4 py-3 text-left hover:bg-gray-50 flex items-center gap-3">
                                <i class="fas fa-file-csv text-blue-500 text-lg"></i>
                                <span class="text-gray-700">Export as CSV</span>
                            </button>
                        </div>
                    </div>
                    <button class="theme-button flex items-center gap-2" onclick="openBulkApproveModal()">
                        <i class="fas fa-check-double"></i>
                        Bulk Approve
                    </button>
                </div>
            </div>

            <!-- Payroll Table -->
            <div class="overflow-x-auto">
                <table class="theme-table">
                    <thead>
                        <tr>
                            <th class="text-left">Employee ID</th>
                            <th class="text-left">Name</th>
                            <th class="text-left">Position</th>
                            <th class="text-left">Type</th>
                            <th class="text-left">Department</th>
                            <th class="text-left">Status</th>
                            <th class="text-left">Attendance</th>
                            <th class="text-left">Pay Period</th>
                            <th class="text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payrollTableBody">
                        <?php
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $statusClass = 'badge-' . $row['status'];
                                $typeClass = 'badge-' . strtolower($row['employee_type']);
                                $attendanceRate = $row['scheduled_days'] > 0 ? round(($row['days_present'] / $row['scheduled_days']) * 100, 1) : 0;
                        ?>
                        <tr class="fade-in">
                            <td class="font-mono font-semibold"><?= htmlspecialchars($row['employee_id']) ?></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-user text-purple-500"></i>
                                    <span><?= htmlspecialchars($row['full_name']) ?></span>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($row['position']) ?></td>
                            <td>
                                <span class="theme-badge <?= $typeClass ?>">
                                    <?= htmlspecialchars($row['employee_type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-building text-gray-400"></i>
                                    <?= htmlspecialchars($row['department']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="theme-badge <?= $statusClass ?>">
                                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="text-sm">
                                        <?= $row['days_present'] ?> / <?= $row['scheduled_days'] ?> days
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        (<?= $attendanceRate ?>%)
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm">
                                <?= date('M d', strtotime($row['pay_period_start'])) ?> - 
                                <?= date('M d, Y', strtotime($row['pay_period_end'])) ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-manage" 
                                            onclick="viewPayrollDetails('<?= htmlspecialchars($row['employee_id'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fas fa-eye mr-1"></i> Manage
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else { ?>
                            <tr>
                                <td colspan="11" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                                    No payroll records found
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-4 border-t border-gray-100">
                <div id="pageStatus" class="text-sm text-gray-600">
                    Showing <?= min($records_per_page, $total_rows) ?> of <?= $total_rows ?> entries
                </div>
                <div class="flex items-center gap-2">
                    <button id="prevPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" <?= $page <= 1 ? 'disabled' : '' ?>>
                        <i class="fas fa-chevron-left text-xs"></i> Previous
                    </button>
                    <button id="nextPage" class="px-4 py-2 border border-gray-300 rounded text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-1" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                        Next <i class="fas fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Approve Modal - UPDATED TO MATCH IMAGE -->
    <div id="bulkApproveModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content bulk-approve-modal">
                <!-- Modal Header -->
                <div class="modal-header-bulk">
                    <h2 class="modal-title-bulk">Bulk Approve Payrolls</h2>
                    <p class="modal-subtitle-bulk">Select the pending payrolls below you wish to approve and process.</p>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body-bulk">
                    <!-- Filters Row -->
                    <div class="filters-row-bulk">
                        <div class="filter-group-bulk">
                            <label class="filter-label-bulk">Filter by Department</label>
                            <select id="bulkDeptFilter" class="select-filter-bulk" onchange="filterBulkPayrolls()">
                                <option value="">All Departments</option>
                                <?php
                                foreach ($departments as $dept) {
                                    echo "<option value=\"$dept\">$dept</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="filter-group-bulk">
                            <label class="filter-label-bulk">Filter by Type</label>
                            <select id="bulkTypeFilter" class="select-filter-bulk" onchange="filterBulkPayrolls()">
                                <option value="">All Types</option>
                                <option value="Regular">Regular</option>
                                <option value="Contractual">Contractual</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Payrolls Table Container -->
                    <div class="payrolls-table-container">
                        <!-- Select All Row -->
                        <div class="select-all-header">
                            <input type="checkbox" id="selectAllBulk" class="payroll-checkbox" onchange="toggleSelectAllBulk()">
                            <span class="select-all-label">Select All</span>
                        </div>
                        
                        <!-- Payrolls Table -->
                        <table class="bulk-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell"></th>
                                    <th class="text-left">ID</th>
                                    <th class="text-left">NAME</th>
                                    <th class="text-left">DEPARTMENT</th>
                                    <th class="text-right">NET PAY</th>
                                </tr>
                            </thead>
                            <tbody id="bulkPayrollsTable">
                                <!-- Payroll items will be loaded here -->
                                <tr>
                                    <td colspan="5" class="empty-state-bulk">
                                        <div class="empty-icon-bulk">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </div>
                                        <div class="empty-text-bulk">Loading payrolls...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Summary Row -->
                    <div class="summary-row-bulk">
                        <div class="summary-text-bulk">
                            Showing <span id="showingCount" class="summary-count-bulk">0</span> pending payrolls ready for approval.
                        </div>
                        <div class="summary-amount-bulk" id="selectedTotalAmount">₱0.00</div>
                    </div>
                </div>
                
                <!-- Modal Actions -->
                <div class="modal-actions-bulk">
                    <button class="btn-modal-cancel-bulk" onclick="closeModal('bulkApproveModal')">
                        Cancel
                    </button>
                    <button id="btnBulkApprove" class="btn-modal-approve-bulk" onclick="processBulkApprove()" disabled>
                        <i class="fas fa-check mr-2"></i>
                        Approve Selected (<span id="selectedCount">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Details Modal -->
    <div id="payrollDetailsModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content">
                <div class="p-6">
                    <!-- MODAL HEADER -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-start gap-4">
                            <div class="payroll-icon-large">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="payroll-info">
                                <h2 class="payroll-name" id="detailEmployeeName">Employee Name</h2>
                                <div class="payroll-subtitle" id="detailEmployeePosition">Position</div>
                                <div class="flex gap-2 mt-2">
                                    <div class="payroll-period" id="detailEmployeeId">ID: 000000</div>
                                    <div class="payroll-period" id="detailEmployeeType">Type</div>
                                    <div class="payroll-period" id="detailEmployeeDept">Department</div>
                                </div>
                            </div>
                        </div>
                        <button onclick="closeModal('payrollDetailsModal')" class="text-gray-400 hover:text-gray-600 text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- ATTENDANCE OVERVIEW -->
                    <div class="attendance-grid">
                        <div class="attendance-card days">
                            <div class="label">Scheduled Days</div>
                            <div class="value" id="detailScheduledDays">0</div>
                            <div class="subvalue" id="detailPayPeriod">Period</div>
                        </div>
                        
                        <div class="attendance-card hours">
                            <div class="label">Hours Worked</div>
                            <div class="value" id="detailHoursWorked">0</div>
                            <div class="subvalue" id="detailOvertimeHours">0 OT hours</div>
                        </div>
                        
                        <div class="attendance-card absent">
                            <div class="label">Absent Days</div>
                            <div class="value" id="detailAbsentDays">0</div>
                            <div class="subvalue">No PTO used</div>
                        </div>
                        
                        <div class="attendance-card holidays">
                            <div class="label">Working Holidays</div>
                            <div class="value" id="detailWorkingHolidays">0</div>
                            <div class="subvalue">With holiday pay</div>
                        </div>
                    </div>

                    <!-- PAYROLL CALCULATION -->
                    <div class="salary-grid">
                        <div class="salary-card">
                            <h4>Earnings</h4>
                            <div class="salary-item">
                                <span class="salary-label" id="labelRegularPay">Regular Hours (80 hrs)</span>
                                <span class="salary-value" id="detailRegularPay">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label" id="labelOvertimePay">Overtime Hours (4 hrs)</span>
                                <span class="salary-value" id="detailOvertimePay">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label" id="labelHolidayPay">Holiday Pay (1 day)</span>
                                <span class="salary-value" id="detailHolidayPay">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label">Allowances</span>
                                <span class="salary-value" id="detailAllowances">₱0.00</span>
                            </div>
                            <div class="salary-item total">
                                <span class="salary-label">Gross Salary</span>
                                <span class="salary-value" id="detailGrossSalary">₱0.00</span>
                            </div>
                        </div>
                        
                        <div class="salary-card">
                            <h4>Deductions</h4>
                            <div class="salary-item">
                                <span class="salary-label">Tax (5%)</span>
                                <span class="salary-value" id="detailTax">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label">SSS Contribution</span>
                                <span class="salary-value" id="detailSSS">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label">PhilHealth</span>
                                <span class="salary-value" id="detailPhilHealth">₱0.00</span>
                            </div>
                            <div class="salary-item">
                                <span class="salary-label">Pag-IBIG</span>
                                <span class="salary-value" id="detailPagIBIG">₱0.00</span>
                            </div>
                            <div class="salary-item total">
                                <span class="salary-label">Total Deductions</span>
                                <span class="salary-value" id="detailTotalDeductions">₱0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- NET SALARY DISPLAY -->
                    <div class="theme-card p-6 mt-6" style="border-left: 4px solid #7c3aed;">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="text-sm text-gray-600">NET SALARY FOR PAY PERIOD</div>
                                <div class="text-3xl font-bold text-purple-700" id="detailNetSalary">₱0.00</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-600">ANNUAL BASE SALARY</div>
                                <div class="text-xl font-semibold" id="detailBaseSalary">₱0.00 / yr</div>
                            </div>
                        </div>
                    </div>

                    <!-- BUDGET IMPACT SECTION -->
                    <div id="budgetImpactSection" class="theme-card p-6 mt-6" style="border-left: 4px solid #f59e0b;">
                        <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <i class="fas fa-chart-line text-amber-500"></i>
                            Budget Impact
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Current Budget</span>
                                <span class="font-semibold text-gray-900" id="budgetCurrent">₱0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Amount (After Approval)</span>
                                <span class="font-semibold text-amber-600" id="budgetAfter">₱0.00</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 mt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-semibold text-gray-700">Remaining Budget</span>
                                    <span class="font-bold text-lg" id="budgetRemaining">₱0.00</span>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div id="budgetBar" class="bg-green-500 h-2 rounded-full transition-all" style="width: 100%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1" id="budgetStatus">Budget is sufficient</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex gap-4 mt-8">
                        <button class="btn-approve flex-1" onclick="approvePayroll()">
                            <i class="fas fa-check-circle mr-2"></i> Approve Payroll
                        </button>
                        <button class="btn-reject flex-1" onclick="showRejectModal()">
                            <i class="fas fa-times-circle mr-2"></i> Reject Payroll
                        </button>
                        <button class="btn-print" onclick="printPayrollSlip()">
                            <i class="fas fa-print mr-2"></i> Print
                        </button>
                    </div>

                    <!-- REJECT MODAL (hidden by default) -->
                    <div id="rejectModal" class="mt-6 p-4 border border-red-200 bg-red-50 rounded-lg" style="display: none;">
                        <h4 class="font-semibold text-red-700 mb-2">Reason for Rejection</h4>
                        <textarea id="rejectReason" class="w-full border border-red-300 rounded-lg p-3" rows="3" placeholder="Enter reason for rejecting this payroll..."></textarea>
                        <div class="flex gap-2 mt-3">
                            <button class="btn-reject" onclick="submitRejection()">
                                Submit Rejection
                            </button>
                            <button class="theme-button-secondary" onclick="hideRejectModal()">
                                Cancel
                            </button>
                        </div>
                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="modal-footer mt-8">
                        <div class="footer-actions">
                            <button class="theme-button-secondary" onclick="viewAttendanceHistory()">
                                <i class="fas fa-history mr-2"></i> View Attendance History
                            </button>
                            <button class="theme-button-secondary" onclick="adjustPayroll()">
                                <i class="fas fa-edit mr-2"></i> Adjust Calculation
                            </button>
                        </div>
                        <div class="footer-info">
                            <span id="lastUpdated">Last Updated: Loading...</span>
                            <span>Pay Period: <span id="detailPayPeriodFull">Loading...</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="customConfirm" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon">
                <i class="fas fa-question"></i>
            </div>
            <div id="customConfirmTitle" class="confirm-modal-title">Confirm Action</div>
            <div id="customConfirmMessage" class="confirm-modal-message">Are you sure you want to proceed?</div>
            <div class="confirm-modal-btns">
                <button id="customConfirmCancel" class="confirm-modal-btn confirm-modal-btn-cancel">Cancel</button>
                <button id="customConfirmBtn" class="confirm-modal-btn confirm-modal-btn-confirm">Yes, Proceed</button>
            </div>
        </div>
    </div>

    <script>
    // ========== JAVASCRIPT FUNCTIONALITY ==========
    // Declare ALL global variables at the very top of the script to avoid reference errors
    // Using window object to ensure global scope
    window.currentEmployeeId = '';
    window.currentPayPeriod = '';
    window.department_filter = "<?= $department_filter ?>";
    window.status_filter = "<?= $status_filter ?>";
    window.type_filter = "<?= $type_filter ?>";
    window.page = <?= $page ?>;
    window.pages = <?= $total_pages ?>;
    window.bulkPayrolls = []; // Initialize as empty array
    window.selectedBulkPayrolls = new Set();
    
    // Create local references for easier access
    var currentEmployeeId = window.currentEmployeeId;
    var currentPayPeriod = window.currentPayPeriod;
    var department_filter = window.department_filter;
    var status_filter = window.status_filter;
    var type_filter = window.type_filter;
    var page = window.page;
    var pages = window.pages;
    var bulkPayrolls = window.bulkPayrolls;
    var selectedBulkPayrolls = window.selectedBulkPayrolls;

    // Initialize status filter dropdown on page load
    window.addEventListener('load', function() {
        const statusFilterDropdown = document.getElementById('status_filter');
        if (statusFilterDropdown) {
            // If status_filter is 'pending' (default), make sure dropdown reflects this
            if (status_filter === 'pending') {
                statusFilterDropdown.value = 'pending';
            }
        }
    });

    // Custom Confirmation Function
    function showCustomConfirm(options) {
        const modal = document.getElementById('customConfirm');
        const title = document.getElementById('customConfirmTitle');
        const message = document.getElementById('customConfirmMessage');
        const confirmBtn = document.getElementById('customConfirmBtn');
        const cancelBtn = document.getElementById('customConfirmCancel');

        title.textContent = options.title || 'Confirm Action';
        message.textContent = options.message || 'Are you sure?';
        confirmBtn.textContent = options.confirmText || 'Yes, Proceed';
        
        // Remove existing classes from confirm button
        confirmBtn.className = 'confirm-modal-btn confirm-modal-btn-confirm';
        if (options.confirmClass) {
            confirmBtn.classList.add(options.confirmClass);
        }

        modal.classList.add('active');

        const close = () => {
            modal.classList.remove('active');
        };

        confirmBtn.onclick = () => {
            close();
            if (options.onConfirm) options.onConfirm();
        };

        cancelBtn.onclick = () => {
            close();
            if (options.onCancel) options.onCancel();
        };
    }

    // Format money function
    function formatMoney(amount) {
        if (!amount) return '0.00';
        return parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Format date
    function formatDate(dateString) {
        if (!dateString) return 'Invalid Date';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        return date.toLocaleDateString('en-PH', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Open Bulk Approve Modal
    function openBulkApproveModal() {
        openModal('bulkApproveModal');
        loadBulkPayrolls();
    }

    // Load bulk payrolls
    function loadBulkPayrolls() {
        const payrollsTable = document.getElementById('bulkPayrollsTable');
        payrollsTable.innerHTML = `
            <tr>
                <td colspan="5" class="empty-state-bulk">
                    <div class="empty-icon-bulk">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                    <div class="empty-text-bulk">Loading payrolls...</div>
                </td>
            </tr>
        `;
        
        fetch('payroll.php?action=get_pending_payrolls')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.payrolls) {
                    window.bulkPayrolls = bulkPayrolls = data.data.payrolls; // Assign to global variable
                    renderBulkPayrolls();
                } else {
                    payrollsTable.innerHTML = `
                        <tr>
                            <td colspan="5" class="empty-state-bulk">
                                <div class="empty-icon-bulk">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="empty-text-bulk">${data.message || 'No pending payrolls found'}</div>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading bulk payrolls:', error);
                payrollsTable.innerHTML = `
                    <tr>
                        <td colspan="5" class="empty-state-bulk">
                            <div class="empty-icon-bulk">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="empty-text-bulk">Error loading payrolls</div>
                        </td>
                    </tr>
                `;
            });
    }

    // Render bulk payrolls in table format like the image
    function renderBulkPayrolls() {
        // Defensive check to ensure variables are defined
        if (typeof window.selectedBulkPayrolls === 'undefined') {
            window.selectedBulkPayrolls = new Set();
        }
        if (typeof window.bulkPayrolls === 'undefined') {
            window.bulkPayrolls = [];
        }
        selectedBulkPayrolls = window.selectedBulkPayrolls;
        bulkPayrolls = window.bulkPayrolls;
        
        const payrollsTable = document.getElementById('bulkPayrollsTable');
        const deptFilter = document.getElementById('bulkDeptFilter').value;
        const typeFilter = document.getElementById('bulkTypeFilter').value;
        
        let filteredPayrolls = bulkPayrolls || [];
        
        // Apply filters
        if (deptFilter) {
            filteredPayrolls = filteredPayrolls.filter(p => p.department === deptFilter);
        }
        
        if (typeFilter) {
            filteredPayrolls = filteredPayrolls.filter(p => p.employee_type === typeFilter);
        }
        
        if (filteredPayrolls.length === 0) {
            payrollsTable.innerHTML = `
                <tr>
                    <td colspan="5" class="empty-state-bulk">
                        <div class="empty-icon-bulk">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <div class="empty-text-bulk">No pending payrolls match your filters</div>
                    </td>
                </tr>
            `;
            document.getElementById('showingCount').textContent = '0';
            document.getElementById('selectedTotalAmount').textContent = '₱0.00';
            updateBulkSummary();
            return;
        }
        
        let html = '';
        filteredPayrolls.forEach((payroll, index) => {
            const isSelected = selectedBulkPayrolls.has(payroll.employee_id + '|' + payroll.pay_period_end);
            const rowClass = isSelected ? 'selected' : '';
            
            html += `
                <tr class="${rowClass}" onclick="toggleBulkPayrollRow(this, ${index})">
                    <td class="checkbox-cell">
                        <input type="checkbox" 
                               id="payroll_${index}" 
                               class="payroll-checkbox" 
                               data-index="${index}"
                               data-employee-id="${payroll.employee_id}"
                               data-pay-period="${payroll.pay_period_end}"
                               data-net-salary="${payroll.net_salary}"
                               ${isSelected ? 'checked' : ''}
                               onclick="event.stopPropagation(); toggleBulkPayrollCheckbox(${index})">
                    </td>
                    <td class="payroll-id-cell">${payroll.employee_id}</td>
                    <td class="payroll-name-cell">${payroll.full_name}</td>
                    <td class="payroll-dept-cell">${payroll.department}</td>
                    <td class="payroll-salary-cell">₱${formatMoney(payroll.net_salary)}</td>
                </tr>
            `;
        });
        
        payrollsTable.innerHTML = html;
        document.getElementById('showingCount').textContent = filteredPayrolls.length;
        updateBulkSummary();
    }

    // Toggle bulk payroll row
    function toggleBulkPayrollRow(row, index) {
        const checkbox = row.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        toggleBulkPayrollCheckbox(index);
    }

    // Toggle bulk payroll checkbox
    function toggleBulkPayrollCheckbox(index) {
        const checkbox = document.getElementById(`payroll_${index}`);
        if (!checkbox) return;
        
        const employeeId = checkbox.getAttribute('data-employee-id');
        const payPeriod = checkbox.getAttribute('data-pay-period');
        const key = employeeId + '|' + payPeriod;
        
        if (checkbox.checked) {
            window.selectedBulkPayrolls.add(key);
            selectedBulkPayrolls = window.selectedBulkPayrolls;
            checkbox.closest('tr').classList.add('selected');
        } else {
            window.selectedBulkPayrolls.delete(key);
            selectedBulkPayrolls = window.selectedBulkPayrolls;
            checkbox.closest('tr').classList.remove('selected');
        }
        
        updateBulkSummary();
        updateSelectAllCheckbox();
    }

    // Filter bulk payrolls
    function filterBulkPayrolls() {
        renderBulkPayrolls();
    }

    // Toggle select all bulk payrolls
    function toggleSelectAllBulk() {
        const selectAllCheckbox = document.getElementById('selectAllBulk');
        const checkboxes = document.querySelectorAll('#bulkPayrollsTable .payroll-checkbox');
        
        checkboxes.forEach(checkbox => {
            const employeeId = checkbox.getAttribute('data-employee-id');
            const payPeriod = checkbox.getAttribute('data-pay-period');
            const key = employeeId + '|' + payPeriod;
            const row = checkbox.closest('tr');
            
            checkbox.checked = selectAllCheckbox.checked;
            
            if (selectAllCheckbox.checked) {
                window.selectedBulkPayrolls.add(key);
                if (row) row.classList.add('selected');
            } else {
                window.selectedBulkPayrolls.delete(key);
                if (row) row.classList.remove('selected');
            }
            selectedBulkPayrolls = window.selectedBulkPayrolls;
        });
        
        updateBulkSummary();
    }

    // Update select all checkbox state
    function updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('selectAllBulk');
        const checkboxes = document.querySelectorAll('#bulkPayrollsTable .payroll-checkbox');
        
        if (checkboxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            return;
        }
        
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        const someChecked = Array.from(checkboxes).some(cb => cb.checked);
        
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }

    // Update bulk summary
    function updateBulkSummary() {
        // Defensive check to ensure variables are defined
        if (typeof window.selectedBulkPayrolls === 'undefined') {
            window.selectedBulkPayrolls = new Set();
        }
        if (typeof window.bulkPayrolls === 'undefined') {
            window.bulkPayrolls = [];
        }
        selectedBulkPayrolls = window.selectedBulkPayrolls;
        bulkPayrolls = window.bulkPayrolls;
        
        const selectedCount = selectedBulkPayrolls.size;
        const btnBulkApprove = document.getElementById('btnBulkApprove');
        const selectedCountSpan = document.getElementById('selectedCount');
        
        // Calculate total amount
        let totalAmount = 0;
        selectedBulkPayrolls.forEach(key => {
            const [employeeId, payPeriod] = key.split('|');
            const payroll = bulkPayrolls.find(p => 
                p.employee_id === employeeId && p.pay_period_end === payPeriod
            );
            if (payroll) {
                totalAmount += parseFloat(payroll.net_salary) || 0;
            }
        });
        
        // Update UI
        selectedCountSpan.textContent = selectedCount;
        document.getElementById('selectedTotalAmount').textContent = `₱${formatMoney(totalAmount)}`;
        
        // Enable/disable approve button
        btnBulkApprove.disabled = selectedCount === 0;
    }

    // Process bulk approve
    function processBulkApprove() {
        if (selectedBulkPayrolls.size === 0) {
            showToast('No payrolls selected for approval', 'error');
            return;
        }
        
        showCustomConfirm({
            title: "Bulk Approval",
            message: `Are you sure you want to approve ${selectedBulkPayrolls.size} payroll(s)?`,
            confirmText: "Yes, Approve All",
            onConfirm: () => {
                const employeeIds = [];
                const payPeriods = [];
                
                selectedBulkPayrolls.forEach(key => {
                    const [employeeId, payPeriod] = key.split('|');
                    employeeIds.push(employeeId);
                    payPeriods.push(payPeriod);
                });
                
                const formData = new FormData();
                formData.append('action', 'bulk_approve');
                formData.append('employee_ids', JSON.stringify(employeeIds));
                formData.append('pay_periods', JSON.stringify(payPeriods));
                
                fetch('payroll.php', {
                    method: 'POST',
                    body: formData
                })
                .then(resp => resp.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeModal('bulkApproveModal');
                        loadTable();
                        // Reset selection
                        window.selectedBulkPayrolls.clear();
                        selectedBulkPayrolls = window.selectedBulkPayrolls;
                        // Reload bulk payrolls
                        loadBulkPayrolls();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error occurred', 'error');
                });
            }
        });

    }

    // Render table rows
    function renderRows(data) {
        let tbody = document.getElementById('payrollTableBody');
        tbody.innerHTML = "";
        
        // Update summary cards
        updateSummaryCards(data);
        
        if (data.rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                No payroll records found
            </td></tr>`;
        } else {
            data.rows.forEach(row => {
                const statusClass = 'badge-' + (row.status || 'pending');
                const typeClass = 'badge-' + (row.employee_type ? row.employee_type.toLowerCase() : 'regular');
                const attendanceRate = row.scheduled_days > 0 ? 
                    Math.round((row.days_present / row.scheduled_days) * 100) : 0;
                
                tbody.innerHTML += `
                    <tr class="fade-in">
                        <td class="font-mono font-semibold">${row.employee_id || ''}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-user text-purple-500"></i>
                                <span>${row.full_name || ''}</span>
                            </div>
                        </td>
                        <td>${row.position || ''}</td>
                        <td>
                            <span class="theme-badge ${typeClass}">
                                ${row.employee_type || 'Regular'}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-building text-gray-400"></i>
                                ${row.department || ''}
                            </div>
                        </td>
                        <td>
                            <span class="theme-badge ${statusClass}">
                                ${(row.status || 'pending').charAt(0).toUpperCase() + (row.status || 'pending').slice(1)}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="text-sm">
                                    ${row.days_present || 0} / ${row.scheduled_days || 0} days
                                </div>
                                <div class="text-xs text-gray-500">
                                    (${attendanceRate}%)
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">
                            ${formatDate(row.pay_period_start || new Date())} - 
                            ${formatDate(row.pay_period_end || new Date())}
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-manage" 
                                        onclick="viewPayrollDetails('${row.employee_id || ''}')">
                                    <i class="fas fa-eye mr-1"></i> Manage
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
    }

    // Update summary cards
    function updateSummaryCards(data) {
        if (document.getElementById('totalEmployees')) {
            document.getElementById('totalEmployees').textContent = data.totalEmployees || 0;
        }
        if (document.getElementById('totalContractual')) {
            document.getElementById('totalContractual').textContent = data.totalContractual || 0;
        }
        if (document.getElementById('totalRegular')) {
            document.getElementById('totalRegular').textContent = data.totalRegular || 0;
        }
        if (document.getElementById('totalNetPay')) {
            const pendingNetPay = data.pendingNetPay || 0;
            const pendingCount = data.pendingCount || 0;
            document.getElementById('totalNetPay').textContent = `₱${formatMoney(pendingNetPay)}`;
            document.querySelector('#totalNetPay').parentElement.querySelector('.card-change').textContent = 
                `${pendingCount} payroll(s) for approval`;
        }
    }

    // Load table data via AJAX
    function loadTable() {
        const url = new URL(window.location);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('department_filter', department_filter);
        url.searchParams.set('status_filter', status_filter);
        url.searchParams.set('type_filter', type_filter);
        url.searchParams.set('page', page);
        
        fetch(url)
            .then(resp => {
                if (!resp.ok) {
                    throw new Error('Network response was not ok');
                }
                return resp.json();
            })
            .then(data => {
                if (!data) {
                    throw new Error('No data received');
                }
                window.pages = pages = data.pages || 1;
                renderRows(data);
                if (document.getElementById("pageStatus")) {
                    const showing = Math.min(data.records_per_page || 10, data.total || 0);
                    document.getElementById("pageStatus").innerText = `Showing ${showing} of ${data.total || 0} entries`;
                }
                
                // Update pagination button states
                const prevPageBtn = document.getElementById('prevPage');
                const nextPageBtn = document.getElementById('nextPage');
                
                if (prevPageBtn) {
                    prevPageBtn.disabled = page <= 1;
                    if (prevPageBtn.disabled) {
                        prevPageBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        prevPageBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
                
                if (nextPageBtn) {
                    nextPageBtn.disabled = page >= pages;
                    if (nextPageBtn.disabled) {
                        nextPageBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        nextPageBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                }
            })
            .catch(error => {
                console.error('Error loading table data:', error);
                showToast('Failed to load payroll data', 'error');
            });
    }

    // Filters
    document.addEventListener('DOMContentLoaded', function() {
        const deptFilter = document.getElementById('department_filter');
        const statusFilter = document.getElementById('status_filter');
        const typeFilter = document.getElementById('type_filter');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        
        if (deptFilter) {
            deptFilter.addEventListener('change', function() {
                window.department_filter = department_filter = this.value;
                window.page = page = 1;
                loadTable();
                updateState();
            });
        }
        
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                window.status_filter = status_filter = this.value;
                window.page = page = 1;
                loadTable();
                updateState();
            });
        }
        
        if (typeFilter) {
            typeFilter.addEventListener('change', function() {
                window.type_filter = type_filter = this.value;
                window.page = page = 1;
                loadTable();
                updateState();
            });
        }
        
        // Pagination
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function() {
                if (page > 1) { 
                    window.page = page = page - 1; 
                    loadTable();
                    updateState();
                }
            });
        }
        
        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', function() {
                if (page < pages) { 
                    window.page = page = page + 1; 
                    loadTable();
                    updateState();
                }
            });
        }
        
        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const modalId = this.id;
                    closeModal(modalId);
                }
            });
        });
        
        // Initial table load
        loadTable();
    });

    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
            document.body.style.overflow = 'auto';
            
            // Reset bulk modal state when closing
            if (modalId === 'bulkApproveModal') {
                window.selectedBulkPayrolls.clear();
                selectedBulkPayrolls = window.selectedBulkPayrolls;
                document.getElementById('bulkDeptFilter').value = '';
                document.getElementById('bulkTypeFilter').value = '';
                document.getElementById('selectAllBulk').checked = false;
                document.getElementById('selectAllBulk').indeterminate = false;
            }
            
            if (modalId === 'payrollDetailsModal') {
                hideRejectModal();
            }
        }
    }

    // View Payroll Details
    function viewPayrollDetails(employeeId) {
        window.currentEmployeeId = currentEmployeeId = employeeId;
        
        // Show loading state
        if (document.getElementById('detailEmployeeName')) {
            document.getElementById('detailEmployeeName').textContent = 'Loading...';
            document.getElementById('detailEmployeePosition').textContent = 'Loading...';
            document.getElementById('detailEmployeeId').textContent = 'ID: ' + employeeId;
            document.getElementById('detailEmployeeType').textContent = 'Loading...';
            document.getElementById('detailEmployeeDept').textContent = 'Loading...';
        }
        
        // Open modal FIRST
        openModal('payrollDetailsModal');
        
        // Then fetch data
        fetchEmployeeData(employeeId);
    }

    // Fetch employee data
    function fetchEmployeeData(employeeId) {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_employee_details');
        url.searchParams.set('employee_id', encodeURIComponent(employeeId));
        
        fetch(url.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    displayEmployeeData(data.data);
                } else {
                    console.error('No data received:', data);
                    showToast('No data found for this employee', 'error');
                }
            })
            .catch(error => {
                console.error('Error fetching employee data:', error);
                showToast('Error loading employee details: ' + error.message, 'error');
            });
    }

    // Display employee data
    function displayEmployeeData(employeeData) {
        const employee = employeeData.employee || {};
        const payroll = employeeData.payroll || {};
        const attendance = employeeData.attendance || {};
        
        // Basic employee info
        if (document.getElementById('detailEmployeeName')) {
            document.getElementById('detailEmployeeName').textContent = employee.full_name || 'Unknown';
            document.getElementById('detailEmployeePosition').textContent = employee.position || 'Unknown Position';
            document.getElementById('detailEmployeeId').textContent = 'ID: ' + (employee.employee_id || '000000');
            document.getElementById('detailEmployeeType').textContent = employee.employee_type || 'Unknown';
            document.getElementById('detailEmployeeDept').textContent = employee.department || 'Unknown';
        }
        
        // Attendance data from payroll record (primary) or calculated attendance (secondary)
        if (document.getElementById('detailScheduledDays')) {
            document.getElementById('detailScheduledDays').textContent = payroll.scheduled_days ?? (attendance.total_days || 0);
            document.getElementById('detailHoursWorked').textContent = payroll.regular_hours ?? (attendance.total_regular_hours || 0);
            document.getElementById('detailOvertimeHours').textContent = (payroll.overtime_hours ?? (attendance.total_overtime_hours || 0)) + ' OT hours';
            document.getElementById('detailAbsentDays').textContent = payroll.absent_days ?? (attendance.days_absent || 0);
            document.getElementById('detailWorkingHolidays').textContent = payroll.working_holidays ?? (attendance.working_holidays || 0);
            
            // Update earning labels
            const regHrs = payroll.regular_hours ?? (attendance.total_regular_hours || 0);
            const otHrs = payroll.overtime_hours ?? (attendance.total_overtime_hours || 0);
            const holDays = payroll.working_holidays ?? (attendance.working_holidays || 0);
            
            if (document.getElementById('labelRegularPay')) document.getElementById('labelRegularPay').textContent = `Regular Hours (${regHrs} hrs)`;
            if (document.getElementById('labelOvertimePay')) document.getElementById('labelOvertimePay').textContent = `Overtime Hours (${otHrs} hrs)`;
            if (document.getElementById('labelHolidayPay')) document.getElementById('labelHolidayPay').textContent = `Holiday Pay (${holDays} day${holDays != 1 ? 's' : ''})`;
        }
        
        // Pay period
        const payPeriodStart = payroll.pay_period_start ? formatDate(payroll.pay_period_start) : 'Nov 1, 2025';
        const payPeriodEnd = payroll.pay_period_end ? formatDate(payroll.pay_period_end) : 'Nov 15, 2025';
        
        if (document.getElementById('detailPayPeriod')) {
            document.getElementById('detailPayPeriod').textContent = payPeriodStart + ' - ' + payPeriodEnd;
        }
        
        if (document.getElementById('detailPayPeriodFull')) {
            document.getElementById('detailPayPeriodFull').textContent = payPeriodStart + ' to ' + payPeriodEnd;
        }
        
        // Calculate payroll
        const calculatedNetSalary = calculatePayroll(payroll, attendance);
        
        // Fetch budget impact for the department (only for pending payroll)
        const budgetImpactSection = document.getElementById('budgetImpactSection');
        const payrollStatus = (payroll.status || '').toLowerCase();
        
        if (payrollStatus === 'pending' && employee.department) {
            if (budgetImpactSection) budgetImpactSection.style.display = 'block';
            fetchBudgetAllocation(employee.department, calculatedNetSalary);
        } else {
            if (budgetImpactSection) budgetImpactSection.style.display = 'none';
        }
        
        // Update timestamp
        const now = new Date();
        if (document.getElementById('lastUpdated')) {
            document.getElementById('lastUpdated').textContent = `Last Updated: ${now.toLocaleString()}`;
        }
        
        // Store current pay period for approval/rejection
        window.currentPayPeriod = currentPayPeriod = payroll.pay_period_end || '';
    }

    // Function to fetch budget allocation for a department
    function fetchBudgetAllocation(department, netSalary) {
        // Reset budget impact section to loading
        const budgetStatus = document.getElementById('budgetStatus');
        if (budgetStatus) budgetStatus.textContent = 'Calculating budget impact...';
        
        const formData = new FormData();
        formData.append('action', 'get_budget');
        formData.append('department', department);

        fetch('payroll.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data) {
                const b = data.data;
                const allocated = parseFloat(b.allocated_amount || 0);
                const spent = parseFloat(b.spent || 0);
                const amountAfter = spent + netSalary;
                const remaining = allocated - amountAfter;
                const isOver = remaining < 0;
                
                // Update UI elements
                document.getElementById('budgetCurrent').textContent = '₱' + formatMoney(allocated);
                document.getElementById('budgetAfter').textContent = '₱' + formatMoney(amountAfter);
                document.getElementById('budgetRemaining').textContent = '₱' + formatMoney(remaining);
                
                const bar = document.getElementById('budgetBar');
                const p = document.getElementById('budgetStatus');
                const approveBtn = document.querySelector('.btn-approve');
                
                const percent = allocated > 0 ? Math.min(100, Math.max(0, (amountAfter / allocated) * 100)) : 100;
                
                if (bar) {
                    bar.style.width = percent + '%';
                    bar.className = isOver ? 'bg-red-500 h-2 rounded-full transition-all' : 
                                    (percent > 80 ? 'bg-amber-500 h-2 rounded-full transition-all' : 'bg-green-500 h-2 rounded-full transition-all');
                }
                
                if (p) {
                    if (isOver) {
                        p.textContent = 'Warning: This approval will exceed the department budget!';
                        p.className = 'text-xs text-red-500 mt-1 font-bold';
                    } else {
                        p.textContent = 'Budget is sufficient for this payroll.';
                        p.className = 'text-xs text-gray-500 mt-1';
                    }
                }
                
                // Disable approve button if over budget
                if (approveBtn) {
                    if (isOver) {
                        approveBtn.disabled = true;
                        approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        approveBtn.title = 'Insufficient Budget';
                    } else {
                        approveBtn.disabled = false;
                        approveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                        approveBtn.title = 'Approve Payroll';
                    }
                }
            } else {
                if (budgetStatus) {
                    budgetStatus.textContent = data.message || 'No budget allocation found for this department.';
                    budgetStatus.className = 'text-xs text-amber-600 mt-1 italic';
                }
                // Disable approve button if no budget found
                const approveBtn = document.querySelector('.btn-approve');
                if (approveBtn) {
                    approveBtn.disabled = true;
                    approveBtn.classList.add('opacity-50', 'cursor-not-allowed');
                    approveBtn.title = 'No Budget Allocation Found';
                }

                // Reset values for better UX
                document.getElementById('budgetCurrent').textContent = '₱0.00';
                document.getElementById('budgetAfter').textContent = '₱' + formatMoney(netSalary);
                document.getElementById('budgetRemaining').textContent = '₱0.00';
                const bar = document.getElementById('budgetBar');
                if (bar) bar.style.width = '0%';
            }
        })
        .catch(err => {
            console.error("Budget Error:", err);
            if (budgetStatus) budgetStatus.textContent = 'Error loading budget data.';
        });
    }


    // Display payroll data from database (with fallback to calculation)
    function calculatePayroll(payroll, attendance) {
        // Try to use actual data from payroll_records table first
        let regularPay = parseFloat(payroll.regular_pay) || 0;
        let overtimePay = parseFloat(payroll.overtime_pay) || 0;
        let holidayPay = parseFloat(payroll.holiday_pay) || 0;
        let allowances = parseFloat(payroll.allowances) || 0;
        let grossSalary = parseFloat(payroll.gross_salary) || 0;
        
        let tax = parseFloat(payroll.tax_amount) || 0;
        let sss = parseFloat(payroll.sss_amount) || 0;
        let philhealth = parseFloat(payroll.philhealth_amount) || 0;
        let pagibig = parseFloat(payroll.pagibig_amount) || 0;
        let otherDeductions = parseFloat(payroll.other_deductions) || 0;
        let totalDeductions = parseFloat(payroll.total_deductions) || (tax + sss + philhealth + pagibig + otherDeductions);
        
        let netSalary = parseFloat(payroll.net_salary) || 0;
        
        // If database doesn't have detailed breakdown, calculate from attendance
        if (regularPay === 0 && attendance && attendance.total_regular_hours) {
            // Calculate based on base salary and attendance
            const baseSalary = parseFloat(payroll.base_salary) || 0;
            const monthlyRate = baseSalary / 12; // Convert annual to monthly
            const dailyRate = monthlyRate / 22; // Assuming 22 working days per month
            const hourlyRate = dailyRate / 8; // Assuming 8 hours per day
            
            const regularHours = parseFloat(attendance.total_regular_hours) || 0;
            const overtimeHours = parseFloat(attendance.total_overtime_hours) || 0;
            const holidayDays = parseFloat(attendance.working_holidays) || 0;
            
            regularPay = regularHours * hourlyRate;
            overtimePay = overtimeHours * (hourlyRate * 1.5); // 1.5x for OT
            holidayPay = holidayDays * dailyRate * 2; // 2x for holidays
            allowances = 0; // No allowances unless specified
            
            grossSalary = regularPay + overtimePay + holidayPay + allowances;
            
            // Calculate deductions
            tax = grossSalary * 0.05; // 5% tax
            sss = 800; // Standard SSS
            philhealth = 400; // Standard PhilHealth
            pagibig = 100; // Standard Pag-IBIG
            otherDeductions = 0;
            totalDeductions = tax + sss + philhealth + pagibig + otherDeductions;
            
            netSalary = grossSalary - totalDeductions;
        }
        
        // Update display with values (either from DB or calculated)
        if (document.getElementById('detailRegularPay')) {
            document.getElementById('detailRegularPay').textContent = `₱${formatMoney(regularPay)}`;
            document.getElementById('detailOvertimePay').textContent = `₱${formatMoney(overtimePay)}`;
            document.getElementById('detailHolidayPay').textContent = `₱${formatMoney(holidayPay)}`;
            document.getElementById('detailGrossSalary').textContent = `₱${formatMoney(grossSalary)}`;
            
            document.getElementById('detailTax').textContent = `₱${formatMoney(tax)}`;
            document.getElementById('detailTotalDeductions').textContent = `₱${formatMoney(totalDeductions)}`;
            
            document.getElementById('detailNetSalary').textContent = `₱${formatMoney(netSalary)}`;
            document.getElementById('detailBaseSalary').textContent = `₱${formatMoney(payroll.base_salary || 0)} / yr`;
        }
        
        // Update deduction details in the modal
        if (document.getElementById('detailSSS')) document.getElementById('detailSSS').textContent = `₱${formatMoney(sss)}`;
        if (document.getElementById('detailPhilHealth')) document.getElementById('detailPhilHealth').textContent = `₱${formatMoney(philhealth)}`;
        if (document.getElementById('detailPagIBIG')) document.getElementById('detailPagIBIG').textContent = `₱${formatMoney(pagibig)}`;
        if (document.getElementById('detailAllowances')) document.getElementById('detailAllowances').textContent = `₱${formatMoney(allowances)}`;

        return netSalary;
    }

    // Show reject modal
    function showRejectModal() {
        const rejectModal = document.getElementById('rejectModal');
        if (rejectModal) {
            rejectModal.style.display = 'block';
        }
    }

    function hideRejectModal() {
        const rejectModal = document.getElementById('rejectModal');
        const rejectReason = document.getElementById('rejectReason');
        
        if (rejectModal) {
            rejectModal.style.display = 'none';
        }
        if (rejectReason) {
            rejectReason.value = '';
        }
    }

    // Approve payroll (with pay period)
    function approvePayroll() {
        if (!currentEmployeeId || !currentPayPeriod) {
            showToast('Employee data not loaded properly', 'error');
            return;
        }
        
        showCustomConfirm({
            title: "Approve Payroll",
            message: "Are you sure you want to approve this payroll?",
            confirmText: "Yes, Approve",
            onConfirm: () => {
                const formData = new FormData();
                formData.append('action', 'approve');
                formData.append('employee_id', currentEmployeeId);
                formData.append('pay_period', currentPayPeriod);
                
                fetch('payroll.php', {
                    method: 'POST',
                    body: formData
                })
                .then(resp => resp.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        closeModal('payrollDetailsModal');
                        loadTable(); // Reload the table to remove approved payroll
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error occurred', 'error');
                });
            }
        });
    }

    // Submit rejection (with pay period)
    function submitRejection() {
        const rejectReason = document.getElementById('rejectReason');
        const reason = rejectReason ? rejectReason.value.trim() : '';
        
        if (!reason) {
            showToast('Please enter a reason for rejection', 'error');
            return;
        }
        
        if (!currentEmployeeId || !currentPayPeriod) {
            showToast('Employee data not loaded properly', 'error');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'reject');
        formData.append('employee_id', currentEmployeeId);
        formData.append('pay_period', currentPayPeriod);
        formData.append('reason', reason);
        
        fetch('payroll.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('payrollDetailsModal');
                loadTable(); // Reload the table to update status
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        });
    }

    // Toast notification
    function showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }
        
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            ${message}
        `;
        
        document.body.appendChild(toast);
        
        // Remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }

    // Export functions
    function getExportData() {
        const headers = ["Employee ID", "Name", "Position", "Type", "Department", "Status", "Base Salary", "Net Salary", "Attendance", "Pay Period"];
        const table = document.getElementById('payrollTableBody');
        const rows = table.querySelectorAll('tr');
        const data = [];
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 10 && !cells[0].textContent.includes('No payroll')) {
                data.push([
                    cells[0].textContent.trim(), // Employee ID
                    cells[1].textContent.trim(), // Name
                    cells[2].textContent.trim(), // Position
                    cells[3].textContent.trim(), // Type
                    cells[4].textContent.trim(), // Department
                    cells[5].textContent.trim(), // Status
                    cells[6].textContent.trim(), // Base Salary
                    cells[7].textContent.trim(), // Net Salary
                    cells[8].textContent.trim(), // Attendance
                    cells[9].textContent.trim()  // Pay Period
                ]);
            }
        });
        
        return {headers, data};
    }

    // Toggle Export Dropdown
    function toggleExportDropdown() {
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('exportDropdown');
        const container = document.getElementById('exportDropdownContainer');
        
        if (dropdown && container && !container.contains(event.target)) {
            dropdown.classList.add('hidden');
        }
    });

    function exportPDF() {
        // Close dropdown
        document.getElementById('exportDropdown').classList.add('hidden');
        const {headers, data} = getExportData();
        
        if (data.length === 0) {
            showToast('No payroll data to export', 'error');
            return;
        }
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4'); // Landscape orientation
        
        // Add title
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text('Payroll Management Report', 40, 40);
        
        // Add date
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Generated on: ' + new Date().toLocaleDateString(), 40, 60);
        
        // Create table
        doc.autoTable({
            head: [headers],
            body: data,
            startY: 80,
            theme: 'grid',
            headStyles: { 
                fillColor: [124, 58, 237], // Purple color
                textColor: 255, 
                fontStyle: 'bold',
                fontSize: 8
            },
            bodyStyles: { 
                fontSize: 7 
            },
            alternateRowStyles: {
                fillColor: [245, 245, 245]
            },
            margin: { left: 20, right: 20 },
            styles: {
                cellPadding: 2,
                overflow: 'linebreak',
                halign: 'left',
                fontSize: 7
            },
            columnStyles: {
                0: { cellWidth: 60 },  // Employee ID
                1: { cellWidth: 80 },  // Name
                2: { cellWidth: 70 },  // Position
                3: { cellWidth: 50 },  // Type
                4: { cellWidth: 60 },  // Department
                5: { cellWidth: 50 },  // Status
                6: { cellWidth: 70, halign: 'right' },  // Base Salary
                7: { cellWidth: 70, halign: 'right' },  // Net Salary
                8: { cellWidth: 60 },  // Attendance
                9: { cellWidth: 90 }   // Pay Period
            }
        });
        
        doc.save("payroll-report.pdf");
        showToast('PDF exported successfully!', 'success');
    }

    function exportCSV() {
        // Close dropdown
        document.getElementById('exportDropdown').classList.add('hidden');
        const {headers, data} = getExportData();
        
        if (data.length === 0) {
            showToast('No payroll data to export', 'error');
            return;
        }
        
        let csvContent = '';
        
        // Add headers
        csvContent += headers.map(header => `"${header}"`).join(',') + '\n';
        
        // Add data rows
        data.forEach(row => {
            csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
        });
        
        // Create and download file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'payroll-report.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('CSV exported successfully!', 'success');
    }

    function exportExcel() {
        // Close dropdown
        document.getElementById('exportDropdown').classList.add('hidden');
        const {headers, data} = getExportData();
        
        if (data.length === 0) {
            showToast('No payroll data to export', 'error');
            return;
        }
        
        // Create workbook and worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
        
        // Set column widths
        const colWidths = [
            { wch: 15 }, // Employee ID
            { wch: 25 }, // Name
            { wch: 20 }, // Position
            { wch: 12 }, // Type
            { wch: 15 }, // Department
            { wch: 12 }, // Status
            { wch: 15 }, // Base Salary
            { wch: 15 }, // Net Salary
            { wch: 15 }, // Attendance
            { wch: 25 }  // Pay Period
        ];
        ws['!cols'] = colWidths;
        
        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Payroll Report');
        
        // Generate and download file
        XLSX.writeFile(wb, 'payroll-report.xlsx');
        
        showToast('Excel exported successfully!', 'success');
    }

    function printPayrollSlip() {
        showToast('Print payroll slip feature coming soon!', 'info');
    }

    function viewAttendanceHistory() {
        showToast('Attendance history feature coming soon!', 'info');
    }

    function adjustPayroll() {
        const adjustBtn = document.querySelector('button[onclick="adjustPayroll()"]');
        
        if (adjustBtn.textContent.includes('Adjust')) {
            // Enter edit mode
            enterEditMode();
        } else {
            // Save adjustments
            savePayrollAdjustment();
        }
    }

    function enterEditMode() {
        const fields = [
            { id: 'detailRegularPay', key: 'regular' },
            { id: 'detailOvertimePay', key: 'overtime' },
            { id: 'detailHolidayPay', key: 'holiday' },
            { id: 'detailAllowances', key: 'allowances' },
            { id: 'detailTax', key: 'tax' },
            { id: 'detailSSS', key: 'sss' },
            { id: 'detailPhilHealth', key: 'ph' },
            { id: 'detailPagIBIG', key: 'pi' }
        ];

        fields.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) {
                const val = el.textContent.replace('₱', '').replace(/,/g, '').trim();
                el.innerHTML = `<input type="number" step="0.01" class="adjust-input" value="${val}" oninput="recalculateTotalsFromInputs()">`;
            }
        });

        const btn = document.querySelector('button[onclick="adjustPayroll()"]');
        btn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Changes';
        btn.classList.replace('theme-button-secondary', 'btn-approve');
        
        // Add Cancel
        const actions = document.querySelector('.footer-actions');
        if (!document.getElementById('cancelAdjust')) {
            const cancel = document.createElement('button');
            cancel.id = 'cancelAdjust';
            cancel.className = 'theme-button-secondary ml-3';
            cancel.innerHTML = '<i class="fas fa-times mr-2"></i> Cancel';
            cancel.onclick = () => window.location.reload();
            actions.appendChild(cancel);
        }
    }

    function recalculateTotalsFromInputs() {
        const getVal = (id) => {
            const input = document.querySelector(`#${id} input`);
            return input ? (parseFloat(input.value) || 0) : 0;
        };

        const reg = getVal('detailRegularPay');
        const ot = getVal('detailOvertimePay');
        const hol = getVal('detailHolidayPay');
        const alw = getVal('detailAllowances');
        
        const gross = reg + ot + hol + alw;
        document.getElementById('detailGrossSalary').textContent = `₱${formatMoney(gross)}`;
        
        const tax = getVal('detailTax');
        const sss = getVal('detailSSS');
        const ph = getVal('detailPhilHealth');
        const pi = getVal('detailPagIBIG');
        
        const ded = tax + sss + ph + pi;
        document.getElementById('detailTotalDeductions').textContent = `₱${formatMoney(ded)}`;
        
        const net = gross - ded;
        document.getElementById('detailNetSalary').textContent = `₱${formatMoney(net)}`;

        // Update budget impact
        const dept = document.getElementById('detailEmployeeDept').textContent;
        fetchBudgetAllocation(dept, net);
    }

    function savePayrollAdjustment() {
        const getV = (id) => document.querySelector(`#${id} input`).value;
        const getT = (id) => document.getElementById(id).textContent.replace('₱', '').replace(/,/g, '').trim();

        const fd = new FormData();
        fd.append('action', 'update_payroll');
        fd.append('employee_id', window.currentEmployeeId);
        fd.append('pay_period', window.currentPayPeriod);
        
        fd.append('regular_pay', getV('detailRegularPay'));
        fd.append('overtime_pay', getV('detailOvertimePay'));
        fd.append('holiday_pay', getV('detailHolidayPay'));
        fd.append('allowances', getV('detailAllowances'));
        fd.append('gross_salary', getT('detailGrossSalary'));
        
        fd.append('tax_amount', getV('detailTax'));
        fd.append('sss_amount', getV('detailSSS'));
        fd.append('philhealth_amount', getV('detailPhilHealth'));
        fd.append('pagibig_amount', getV('detailPagIBIG'));
        fd.append('total_deductions', getT('detailTotalDeductions'));
        
        fd.append('net_salary', getT('detailNetSalary'));

        const btn = document.querySelector('button[onclick="adjustPayroll()"]');
        const old = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        btn.disabled = true;

        fetch('payroll.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    showToast(data.message, 'error');
                    btn.innerHTML = old;
                    btn.disabled = false;
                }
            })
            .catch(e => {
                showToast('Save failed!', 'error');
                btn.innerHTML = old;
                btn.disabled = false;
            });
    }

    // State management
    function updateState() {
        const currentState = {
            department_filter: department_filter,
            status_filter: status_filter,
            type_filter: type_filter,
            page: page
        };
        sessionStorage.setItem('payrollManagementState', JSON.stringify(currentState));
        
        // Update URL
        const url = new URL(window.location);
        if (department_filter) {
            url.searchParams.set('department_filter', department_filter);
        } else {
            url.searchParams.delete('department_filter');
        }
        if (status_filter) {
            url.searchParams.set('status_filter', status_filter);
        } else {
            url.searchParams.delete('status_filter');
        }
        if (type_filter) {
            url.searchParams.set('type_filter', type_filter);
        } else {
            url.searchParams.delete('type_filter');
        }
        if (page > 1) {
            url.searchParams.set('page', page);
        } else {
            url.searchParams.delete('page');
        }
        
        window.history.replaceState({}, '', url);
    }

    // Initialize state from session storage if available
    document.addEventListener('DOMContentLoaded', function() {
        const savedState = sessionStorage.getItem('payrollManagementState');
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                window.department_filter = department_filter = state.department_filter || '';
                window.status_filter = status_filter = state.status_filter || '';
                window.type_filter = type_filter = state.type_filter || '';
                window.page = page = state.page || 1;
                
                // Update filter dropdowns
                const deptFilter = document.getElementById('department_filter');
                const statusFilter = document.getElementById('status_filter');
                const typeFilter = document.getElementById('type_filter');
                
                if (deptFilter && department_filter) {
                    deptFilter.value = department_filter;
                }
                
                if (statusFilter && status_filter) {
                    statusFilter.value = status_filter;
                }
                
                if (typeFilter && type_filter) {
                    typeFilter.value = type_filter;
                }
            } catch (e) {
                console.error('Error parsing saved state:', e);
            }
        }
    });
    </script>
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
</body>
</html>
<?php endif; ?>
<?php $conn->close(); ?>