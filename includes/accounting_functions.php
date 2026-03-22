<?php
/**
 * Accounting Helper Functions - FIXED VERSION
 * 
 * This file contains helper functions for the journal entry and ledger system.
 * Include this file in any script that needs to create journal entries or post to the ledger.
 * 
 * Usage: require_once 'includes/accounting_functions.php';
 */

/**
 * Get next available ID for a table (Domain-Safe Workaround for missing auto-increment)
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $id_col ID column name (default 'id')
 * @return int Next available ID
 */
function getNextAvailableId($conn, $table, $id_col = 'id') {
    $sql = "SELECT MAX(`$id_col`) as max_id FROM `$table` ";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return intval($row['max_id'] ?? 0) + 1;
    }
    return 1;
}

/**
 * Generate next Journal Entry number (JE-1, JE-2, JE-3, etc.)
 * 
 * @param mysqli $conn Database connection
 * @return string Next JE number
 */
function generateJENumber($conn) {
    $sql = "SELECT MAX(CAST(SUBSTRING(journal_number, 4) AS UNSIGNED)) as last_number 
            FROM journal_entries 
            WHERE journal_number LIKE 'JE-%'";
    
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $next_number = ($row['last_number'] ?? 0) + 1;
    
    return 'JE-' . $next_number;
}

/**
 * Auto-generate description based on transaction type and data
 * 
 * @param string $reference_type Type of transaction
 * @param array $data Transaction data
 * @return string Generated description
 */
function generateDescription($reference_type, $data) {
    switch ($reference_type) {
        case 'vendor_invoice':
            return sprintf(
                "Vendor invoice for %s - %s",
                strtolower($data['expense_category'] ?? 'expense'),
                $data['vendor_name'] ?? 'Supplier'
            );
            
        case 'reimbursement':
            return sprintf(
                "Reimbursement for %s purchased by %s",
                strtolower($data['expense_category'] ?? 'expense'),
                $data['employee_name'] ?? 'Employee'
            );
            
        case 'payroll':
            return sprintf(
                "Payroll for %s - %s",
                $data['period'] ?? date('F Y'),
                $data['employee_type'] ?? 'Staff'
            );
            
        case 'driver_payment':
            return sprintf(
                "Driver commission payment for boundary fees - Driver ID: %s",
                $data['driver_id'] ?? 'Unknown'
            );
            
        case 'receivable':
            return sprintf(
                "Corporate transportation service rendered to %s",
                $data['customer_name'] ?? 'Customer'
            );
            
        case 'payment':
            return sprintf(
                "Payment to %s for invoice %s",
                $data['payee_name'] ?? 'Supplier',
                $data['invoice_id'] ?? 'N/A'
            );
            
        default:
            return $data['description'] ?? 'Journal entry';
    }
}

/**
 * Get GL account details based on expense category
 * FIXED: Removed level constraint, added direct GL code support
 * 
 * @param mysqli $conn Database connection
 * @param string $expense_category Expense category name or GL code
 * @param string $description Optional description for additional context
 * @return array|null GL account details or null if not found
 */
function getExpenseGLAccount($conn, $expense_category, $description = '') {
    // Log what we're looking for
    error_log("getExpenseGLAccount called with: " . $expense_category);
    
    // Check if expense_category contains a 6-digit GL account code anywhere (e.g., "565001 - HR Systems")
    if (preg_match('/(\d{6})/', $expense_category, $matches)) {
        $gl_code = $matches[1];
        error_log("Extracted GL code from string: " . $gl_code);
        
        $sql = "SELECT id, code, name, type 
                FROM chart_of_accounts_hierarchy 
                WHERE code = ? AND status = 'active'
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $gl_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $gl_account = $result->fetch_assoc();
            error_log("Found GL account by extracted code: " . $gl_account['code'] . " - " . $gl_account['name']);
            return $gl_account;
        }
    }
    
    // Default to miscellaneous if not specified
    if (empty($expense_category)) {
        $expense_category = 'Other';
    }
    
    // Map expense categories to GL accounts
    $category_mapping = [
        // Fuel & Energy
        'Fuel' => '511001',
        'Fuel & Energy' => '511001',
        'Gasoline' => '511001',
        
        // Vehicle Maintenance
        'Vehicle Maintenance' => '512001',
        'Maintenance' => '512001',
        'Repair' => '512001',
        
        // Office Supplies
        'Office Supplies' => '554001',
        'Supplies' => '554001',
        
        // Utilities
        'Utilities' => '591002',
        'Electricity' => '591002',
        'Water' => '591002',
        
        // Insurance
        'Insurance' => '515001',
        'Vehicle Insurance' => '515001',
        
        // Payroll
        'Payroll' => '561001',
        'Salaries' => '561001',
        'Employee Salaries' => '561001',
        
        // Payroll Tax
        'Payroll Tax' => '584002',
        'Tax Withholding' => '584002',
        
        // Driver Payments
        'Driver Payment' => '521001',
        'Driver Commission' => '521001',
        
        // Technology & Systems
        'Hardware & Devices' => '534000',
        'Hardware' => '534000',
        'HR Systems' => '565001',
        'Software' => '565001',
        
        // Default
        'Other' => '431001',
        'Miscellaneous' => '431001'
    ];
    
    // Find matching GL code from mapping
    $gl_code = null;
    foreach ($category_mapping as $key => $code) {
        if (stripos($expense_category, $key) !== false) {
            $gl_code = $code;
            error_log("Found mapping for '$expense_category' using keyword '$key': $gl_code");
            break;
        }
    }
    
    if ($gl_code) {
        // Get full GL account details
        $sql = "SELECT id, code, name, type 
                FROM chart_of_accounts_hierarchy 
                WHERE code = ? AND status = 'active'
                LIMIT 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $gl_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
    }
    
    // Fallback: Search by NAME in the database
    // This handles "Hardware & Devices" or "HR Systems" directly
    error_log("Searching database for GL account by name: $expense_category");
    $search_pattern = "%" . $expense_category . "%";
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE (name LIKE ? OR ? LIKE CONCAT('%', name, '%'))
            AND status = 'active'
            AND type = 'Expense'
            ORDER BY LENGTH(name) ASC
            LIMIT 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_pattern, $expense_category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $gl_account = $result->fetch_assoc();
        error_log("Found GL account by name search: " . $gl_account['code'] . " - " . $gl_account['name']);
        return $gl_account;
    }
    
    // Log the issue for debugging
    error_log("WARNING: GL Account not found for category: $expense_category");
    
    // Try to find ANY active expense account as fallback
    $fallback_sql = "SELECT id, code, name, type 
                     FROM chart_of_accounts_hierarchy 
                     WHERE type = 'Expense' AND status = 'active'
                     LIMIT 1";
    
    $fallback_result = $conn->query($fallback_sql);
    if ($fallback_result && $fallback_result->num_rows > 0) {
        $fallback_account = $fallback_result->fetch_assoc();
        error_log("Using fallback expense account: " . $fallback_account['code'] . " - " . $fallback_account['name']);
        return $fallback_account;
    }
    
    error_log("CRITICAL: No expense GL accounts found in COA!");
    return null;
}

/**
 * Get Accounts Payable GL Account
 * FIXED: Removed level constraint
 * 
 * @param mysqli $conn Database connection
 * @return array|null GL account details
 */
function getAccountsPayableGL($conn) {
    // Try to find Accounts Payable - Suppliers (211001)
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '211001' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $gl_account = $result->fetch_assoc();
        error_log("Found Accounts Payable GL: " . $gl_account['code'] . " - " . $gl_account['name']);
        return $gl_account;
    }
    
    // Fallback: Try to find ANY Accounts Payable account
    $fallback_sql = "SELECT id, code, name, type 
                     FROM chart_of_accounts_hierarchy 
                     WHERE (name LIKE '%Accounts Payable%' OR name LIKE '%Payable%') 
                     AND type = 'Liability' 
                     AND status = 'active'
                     LIMIT 1";
    
    $fallback_result = $conn->query($fallback_sql);
    if ($fallback_result && $fallback_result->num_rows > 0) {
        $fallback_account = $fallback_result->fetch_assoc();
        error_log("Using fallback Accounts Payable: " . $fallback_account['code'] . " - " . $fallback_account['name']);
        return $fallback_account;
    }
    
    error_log("CRITICAL: Accounts Payable GL account not found!");
    return null;
}

/**
 * Update GL account balance in chart_of_accounts_hierarchy
 * 
 * @param mysqli $conn Database connection
 * @param int $gl_account_id GL account ID
 * @param float $amount Amount to add
 * @param string $type 'debit' or 'credit'
 */
function updateGLAccountBalance($conn, $gl_account_id, $amount, $type) {
    // 1. Get current account details (Type and current Balance)
    $sql = "SELECT type, balance FROM chart_of_accounts_hierarchy WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gl_account_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $account = $res->fetch_assoc();
    $stmt->close();

    if (!$account) return 0;

    $current_balance = floatval($account['balance']);
    $account_type = $account['type'];

    // 2. Calculate impact based on Normal Balance
    // Assets & Expenses: Normal Debit (Debit increases, Credit decreases)
    // Liabilities, Equity, Revenue: Normal Credit (Credit increases, Debit decreases)
    $is_normal_debit = in_array($account_type, ['Asset', 'Expense']);
    
    if ($type === 'debit') {
        $new_balance = $is_normal_debit ? ($current_balance + $amount) : ($current_balance - $amount);
    } else { // credit
        $new_balance = $is_normal_debit ? ($current_balance - $amount) : ($current_balance + $amount);
    }

    // 3. Update the Account table
    $upd = "UPDATE chart_of_accounts_hierarchy SET balance = ?, updated_at = NOW() WHERE id = ?";
    $upd_stmt = $conn->prepare($upd);
    $upd_stmt->bind_param("di", $new_balance, $gl_account_id);
    $upd_stmt->execute();
    $upd_stmt->close();

    return $new_balance;
}

/**
 * Recalculates and updates the running_balance column for all entries of an account.
 * This heals historical data where running_balance might be 0.
 * 
 * @param mysqli $conn Database connection
 * @param int $gl_account_id GL account ID
 */
function recalculateGLRunningBalances($conn, $gl_account_id) {
    // 1. Get Account Type
    $sql = "SELECT type FROM chart_of_accounts_hierarchy WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gl_account_id);
    $stmt->execute();
    $acc = $stmt->get_result()->fetch_assoc();
    if (!$acc) return;
    $type = $acc['type'];
    $is_normal_debit = in_array($type, ['Asset', 'Expense']);
    $is_income_statement = in_array($type, ['Revenue', 'Expense']);

    // 2. Fetch all entries in chronological order
    $sql = "SELECT id, debit_amount, credit_amount, transaction_date FROM general_ledger WHERE gl_account_id = ? ORDER BY transaction_date ASC, id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gl_account_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $running_bal = 0;
    $last_month = null;

    while ($row = $result->fetch_assoc()) {
        $debit = floatval($row['debit_amount']);
        $credit = floatval($row['credit_amount']);
        $current_month = date('Y-m', strtotime($row['transaction_date']));

        // Income Statement Reset Logic (Revenue/Expense reset per month)
        if ($is_income_statement && $last_month !== null && $last_month !== $current_month) {
            $running_bal = 0;
        }
        $last_month = $current_month;

        if ($is_normal_debit) {
            $running_bal += ($debit - $credit);
        } else {
            $running_bal += ($credit - $debit);
        }

        // 3. Update the record
        $upd = "UPDATE general_ledger SET running_balance = ? WHERE id = ?";
        $ustmt = $conn->prepare($upd);
        $ustmt->bind_param("di", $running_bal, $row['id']);
        $ustmt->execute();
    }
}

/**
 * Resolves the full account hierarchy for a specific GL account (Level 4).
 * Returns Level 1 (Type), Level 2 (Category), and Level 3 (Subcategory).
 */
function resolveGLAccountHierarchy($conn, $gl_account_id) {
    $res = [
        'level1' => null, // Type
        'level2' => null, // Category
        'level3' => null, // Subcategory
        'level4' => null  // The account itself
    ];

    // Current Account
    $sql = "SELECT id, parent_id, level, code, name, type FROM chart_of_accounts_hierarchy WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $gl_account_id);
    $stmt->execute();
    $acc = $stmt->get_result()->fetch_assoc();
    if (!$acc) return $res;
    
    $res['level4'] = $acc;
    $curr_parent = $acc['parent_id'];

    // Climb up the tree
    while ($curr_parent !== null) {
        $sql = "SELECT id, parent_id, level, code, name, type FROM chart_of_accounts_hierarchy WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $curr_parent);
        $stmt->execute();
        $parent = $stmt->get_result()->fetch_assoc();
        
        if (!$parent) break;

        if ($parent['level'] == 1) $res['level1'] = $parent;
        if ($parent['level'] == 2) $res['level2'] = $parent;
        if ($parent['level'] == 3) $res['level3'] = $parent;

        $curr_parent = $parent['parent_id'];
    }

    return $res;
}

/**
 * Create journal entry and post to general ledger for vendor invoice approval
 * 
 * @param mysqli $conn Database connection
 * @param array $invoice Invoice data
 * @return string Journal number
 * @throws Exception if journal entry creation fails
 */
function createVendorInvoiceJournalEntry($conn, $invoice) {
    // Log invoice data for debugging
    error_log("Creating journal entry for invoice: " . $invoice['invoice_id']);
    error_log("Expense category: " . ($invoice['expense_categories'] ?? 'NOT SET'));
    
    // Generate journal number (simplified: JE-1, JE-2, etc.)
    $journal_number = generateJENumber($conn);
    
    // Auto-generate description
    $description_data = [
        'expense_category' => $invoice['expense_categories'] ?? 'expense',
        'vendor_name' => $invoice['vendor_name'] ?? 'Supplier'
    ];
    $description = generateDescription('vendor_invoice', $description_data);
    
    // Get GL accounts
    // Priority: 
    // 1. Specific GL account from invoice data ('gl_account' field)
    // 2. Expense category mapping
    $gl_input = !empty($invoice['gl_account']) ? $invoice['gl_account'] : ($invoice['expense_categories'] ?? '');
    $expense_gl = getExpenseGLAccount($conn, $gl_input, $invoice['description']);
    $payable_gl = getAccountsPayableGL($conn);
    
    if (!$expense_gl || !$payable_gl) {
        $error_msg = "GL accounts not found in Chart of Accounts. ";
        if (!$expense_gl) $error_msg .= "Expense GL mapping failed for: " . $gl_input . ". ";
        if (!$payable_gl) $error_msg .= "Accounts Payable GL missing. ";
        error_log($error_msg);
        throw new Exception($error_msg);
    }
    
    $amount = $invoice['amount'];
    
    // 1. Create Journal Entry Header
    $next_je_id = getNextAvailableId($conn, 'journal_entries');
    
    $je_sql = "INSERT INTO journal_entries (
        id, journal_number, transaction_date, reference_type, reference_id, 
        description, total_debit, total_credit, status, created_by, posted_at
    ) VALUES (?, ?, NOW(), 'vendor_invoice', ?, ?, ?, ?, 'posted', 'System', NOW())";
    
    $je_stmt = $conn->prepare($je_sql);
    $je_stmt->bind_param("isssdd", 
        $next_je_id,
        $journal_number, 
        $invoice['invoice_id'], 
        $description, 
        $amount, 
        $amount
    );
    $je_stmt->execute();
    $journal_entry_id = $next_je_id;
    $je_stmt->close();
    
    // 2. Create Journal Entry Lines
    
    // Line 1: Debit Expense
    $next_line_id = getNextAvailableId($conn, 'journal_entry_lines');
    
    $line_sql_debit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)";
    
    $debit_stmt = $conn->prepare($line_sql_debit);
    $line_number = 1;
    $debit_stmt->bind_param("iiiisssdss",
        $next_line_id,
        $journal_entry_id,
        $line_number,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $amount,
        $description,
        $invoice['department']
    );
    $debit_stmt->execute();
    $debit_stmt->close();
    
    // Line 2: Credit Accounts Payable
    $next_line_id_2 = $next_line_id + 1;
    
    $line_sql_credit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
    
    $credit_stmt = $conn->prepare($line_sql_credit);
    $line_number = 2;
    $credit_stmt->bind_param("iiiisssdss",
        $next_line_id_2,
        $journal_entry_id,
        $line_number,
        $payable_gl['id'],
        $payable_gl['code'],
        $payable_gl['name'],
        $payable_gl['type'],
        $amount,
        $description,
        $invoice['department']
    );
    $credit_stmt->execute();
    $credit_stmt->close();
    
    // 3. Post to General Ledger
    
    // Update Balances first to get running balances
    $new_expense_bal = updateGLAccountBalance($conn, $expense_gl['id'], $amount, 'debit');
    $new_payable_bal = updateGLAccountBalance($conn, $payable_gl['id'], $amount, 'credit');

    // GL Entry 1: Debit Expense
    $next_gl_id = getNextAvailableId($conn, 'general_ledger');
    
    $gl_sql_debit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'vendor_invoice', ?, ?, ?, 0, ?, ?)";
    
    $gl_debit_stmt = $conn->prepare($gl_sql_debit);
    $gl_debit_stmt->bind_param("iisssisssdds",
        $next_gl_id,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $journal_entry_id,
        $journal_number,
        $invoice['invoice_id'],
        $description,
        $amount,
        $new_expense_bal,
        $invoice['department']
    );
    $gl_debit_stmt->execute();
    $gl_debit_stmt->close();
    
    // GL Entry 2: Credit Accounts Payable
    $next_gl_id_2 = $next_gl_id + 1;
    
    $gl_sql_credit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'vendor_invoice', ?, ?, 0, ?, ?, ?)";
    
    $gl_credit_stmt = $conn->prepare($gl_sql_credit);
    $gl_credit_stmt->bind_param("iisssisssdds",
        $next_gl_id_2,
        $payable_gl['id'],
        $payable_gl['code'],
        $payable_gl['name'],
        $payable_gl['type'],
        $journal_entry_id,
        $journal_number,
        $invoice['invoice_id'],
        $description,
        $amount,
        $new_payable_bal,
        $invoice['department']
    );
    $gl_credit_stmt->execute();
    $gl_credit_stmt->close();
    
    error_log("Journal entry created successfully: $journal_number");
    return $journal_number;
}

/**
 * Create journal entry and post to general ledger when reimbursement is approved
 * 
 * @param mysqli $conn Database connection
 * @param array $reimbursement Reimbursement data
 * @return string Journal number
 */
function createReimbursementJournalEntry($conn, $reimbursement) {
    error_log("Creating journal entry for reimbursement: " . $reimbursement['report_id']);
    
    // Generate journal number
    $journal_number = generateJENumber($conn);
    error_log("Generated JE number: $journal_number");
    
    // Parse reimbursement_type to get expense category
    $reimbursement_type = $reimbursement['reimbursement_type'] ?? '';
    $expense_category = explode(' - ', $reimbursement_type)[0] ?? 'Other';
    
    // Auto-generate description
    $description_data = [
        'expense_category' => $expense_category,
        'employee_name' => $reimbursement['employee_name'] ?? 'Employee'
    ];
    $description = generateDescription('reimbursement', $description_data);
    error_log("Generated description: $description");
    
    // Get GL accounts
    // Dynamic: Based on reimbursement type (e.g., "Hardware & Devices")
    // Credit: Employee Payables (223001)
    $expense_gl = getExpenseGLAccount($conn, $reimbursement['reimbursement_type'] ?? 'Other', $reimbursement['description']);
    $employee_payable_gl = getEmployeePayableGL($conn); // Employee Payables
    
    if (!$expense_gl) {
        throw new Exception("Expense GL account mapping failed for: " . ($reimbursement['reimbursement_type'] ?? 'Unknown'));
    }
    if (!$employee_payable_gl) {
        throw new Exception("Employee Payable GL account not found");
    }
    
    error_log("Expense GL: " . $expense_gl['code'] . " - " . $expense_gl['name']);
    error_log("Employee Payable GL: " . $employee_payable_gl['code'] . " - " . $employee_payable_gl['name']);
    
    $amount = $reimbursement['amount'];
    $department = $reimbursement['department'] ?? 'General';
    
    // 1. Create Journal Entry Header
    $next_je_id = getNextAvailableId($conn, 'journal_entries');
    
    $je_sql = "INSERT INTO journal_entries (
        id, journal_number, transaction_date, reference_type, reference_id, 
        description, total_debit, total_credit, status, created_by, posted_at
    ) VALUES (?, ?, NOW(), 'reimbursement', ?, ?, ?, ?, 'posted', 'System', NOW())";
    
    $je_stmt = $conn->prepare($je_sql);
    $je_stmt->bind_param("isssdd", 
        $next_je_id,
        $journal_number, 
        $reimbursement['report_id'], 
        $description, 
        $amount, 
        $amount
    );
    $je_stmt->execute();
    $journal_entry_id = $next_je_id;
    $je_stmt->close();
    
    error_log("Created journal entry header with ID: $journal_entry_id");
    
    // 2. Create Journal Entry Lines
    
    // Line 1: Debit Expense
    $next_line_id = getNextAvailableId($conn, 'journal_entry_lines');
    
    $line_sql_debit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)";
    
    $debit_stmt = $conn->prepare($line_sql_debit);
    $line_number = 1;
    $debit_stmt->bind_param("iiiisssdss",
        $next_line_id,
        $journal_entry_id,
        $line_number,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $amount,
        $description,
        $department
    );
    $debit_stmt->execute();
    $debit_stmt->close();
    
    // Line 2: Credit Employee Payables
    $next_line_id_2 = $next_line_id + 1;
    
    $line_sql_credit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
    
    $credit_stmt = $conn->prepare($line_sql_credit);
    $line_number = 2;
    $credit_stmt->bind_param("iiiisssdss",
        $next_line_id_2,
        $journal_entry_id,
        $line_number,
        $employee_payable_gl['id'],
        $employee_payable_gl['code'],
        $employee_payable_gl['name'],
        $employee_payable_gl['type'],
        $amount,
        $description,
        $department
    );
    $credit_stmt->execute();
    $credit_stmt->close();
    
    error_log("Created journal entry lines");
    
    // 3. Post to General Ledger
    
    // Update Balances first
    $new_expense_bal = updateGLAccountBalance($conn, $expense_gl['id'], $amount, 'debit');
    $new_payable_bal = updateGLAccountBalance($conn, $employee_payable_gl['id'], $amount, 'credit');

    // GL Entry 1: Debit Expense
    $next_gl_id = getNextAvailableId($conn, 'general_ledger');
    
    $gl_sql_debit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'reimbursement', ?, ?, ?, 0, ?, ?)";
    
    $gl_debit_stmt = $conn->prepare($gl_sql_debit);
    $gl_debit_stmt->bind_param("iisssisssdds",
        $next_gl_id,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $journal_entry_id,
        $journal_number,
        $reimbursement['report_id'],
        $description,
        $amount,
        $new_expense_bal,
        $department
    );
    $gl_debit_stmt->execute();
    $gl_debit_stmt->close();
    
    // GL Entry 2: Credit Employee Payables
    $next_gl_id_2 = $next_gl_id + 1;
    
    $gl_sql_credit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'reimbursement', ?, ?, 0, ?, ?, ?)";
    
    $gl_credit_stmt = $conn->prepare($gl_sql_credit);
    $gl_credit_stmt->bind_param("iisssisssdds",
        $next_gl_id_2,
        $employee_payable_gl['id'],
        $employee_payable_gl['code'],
        $employee_payable_gl['name'],
        $employee_payable_gl['type'],
        $journal_entry_id,
        $journal_number,
        $reimbursement['report_id'],
        $description,
        $amount,
        $new_payable_bal,
        $department
    );
    $gl_credit_stmt->execute();
    $gl_credit_stmt->close();
    
    error_log("Journal entry created successfully: $journal_number");
    return $journal_number;
}

/**
 * Get Employee Payable GL Account (223001)
 */
function getEmployeePayableGL($conn) {
    error_log("Looking for Employee Payable GL account (223001)");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '223001' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found Employee Payable GL: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    // Fallback: try to find any active liability account with "Employee" or "Payable" in name
    error_log("Employee Payable GL 223001 not found, trying fallback");
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE type = 'Liability' 
            AND (name LIKE '%Employee%' OR name LIKE '%Payable%')
            AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Fallback found: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    error_log("ERROR: No Employee Payable GL account found!");
    return null;
}

/**
 * Create journal entry and post to general ledger when payroll is approved
 * 
 * @param mysqli $conn Database connection
 * @param array $payroll Payroll data
 * @return string Journal number
 */
function createPayrollJournalEntry($conn, $payroll) {
    error_log("Creating journal entry for payroll: " . $payroll['employee_id'] . " - " . $payroll['pay_period_end']);
    
    // Generate journal number
    $journal_number = generateJENumber($conn);
    error_log("Generated JE number: $journal_number");
    
    // Auto-generate description
    $description_data = [
        'period' => date('F Y', strtotime($payroll['pay_period_end'])),
        'employee_type' => $payroll['position'] ?? 'Staff'
    ];
    $description = generateDescription('payroll', $description_data);
    error_log("Generated description: $description");
    
    // Get GL accounts
    $salary_expense_gl = getSalaryExpenseGL($conn);
    $salaries_payable_gl = getSalariesPayableGL($conn);
    
    if (!$salary_expense_gl) {
        throw new Exception("Salary Expense GL account (561001) not found");
    }
    if (!$salaries_payable_gl) {
        throw new Exception("Salaries Payable GL account (223001) not found");
    }
    
    error_log("Salary Expense GL: " . $salary_expense_gl['code'] . " - " . $salary_expense_gl['name']);
    error_log("Salaries Payable GL: " . $salaries_payable_gl['code'] . " - " . $salaries_payable_gl['name']);
    
    $amount = $payroll['net_salary'];
    $department = $payroll['department'] ?? 'General';
    $payroll_ref_id = 'PR-' . $payroll['employee_id'] . '-' . date('Ymd', strtotime($payroll['pay_period_end']));
    
    // 1. Create Journal Entry Header
    // 1. Create Journal Entry Header
    $next_je_id = getNextAvailableId($conn, 'journal_entries');
    
    $je_sql = "INSERT INTO journal_entries (
        id, journal_number, transaction_date, reference_type, reference_id, 
        description, total_debit, total_credit, status, created_by, posted_at
    ) VALUES (?, ?, NOW(), 'pr', ?, ?, ?, ?, 'posted', 'System', NOW())";
    
    $je_stmt = $conn->prepare($je_sql);
    $je_stmt->bind_param("isssdd", 
        $next_je_id,
        $journal_number, 
        $payroll_ref_id, 
        $description, 
        $amount, 
        $amount
    );
    $je_stmt->execute();
    $journal_entry_id = $next_je_id;
    $je_stmt->close();
    
    error_log("Created journal entry header with ID: $journal_entry_id");
    
    // 2. Create Journal Entry Lines
    
    // Line 1: Debit Salary Expense
    $next_line_id = getNextAvailableId($conn, 'journal_entry_lines');
    
    $line_sql_debit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)";
    
    $debit_stmt = $conn->prepare($line_sql_debit);
    $line_number = 1;
    $debit_stmt->bind_param("iiiisssdss",
        $next_line_id,
        $journal_entry_id,
        $line_number,
        $salary_expense_gl['id'],
        $salary_expense_gl['code'],
        $salary_expense_gl['name'],
        $salary_expense_gl['type'],
        $amount,
        $description,
        $department
    );
    $debit_stmt->execute();
    $debit_stmt->close();
    
    // Line 2: Credit Salaries Payable
    $next_line_id_2 = $next_line_id + 1;
    
    $line_sql_credit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
    
    $credit_stmt = $conn->prepare($line_sql_credit);
    $line_number = 2;
    $credit_stmt->bind_param("iiiisssdss",
        $next_line_id_2,
        $journal_entry_id,
        $line_number,
        $salaries_payable_gl['id'],
        $salaries_payable_gl['code'],
        $salaries_payable_gl['name'],
        $salaries_payable_gl['type'],
        $amount,
        $description,
        $department
    );
    $credit_stmt->execute();
    $credit_stmt->close();
    
    error_log("Created journal entry lines");
    
    // 3. Post to General Ledger (Update Balances First)
    $new_expense_bal = updateGLAccountBalance($conn, $salary_expense_gl['id'], $amount, 'debit');
    $new_payable_bal = updateGLAccountBalance($conn, $salaries_payable_gl['id'], $amount, 'credit');
    
    // GL Entry 1: Debit Salary Expense
    $next_gl_id = getNextAvailableId($conn, 'general_ledger');
    
    $gl_sql_debit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'pr', ?, ?, ?, 0, ?, ?)";
    
    $gl_debit_stmt = $conn->prepare($gl_sql_debit);
    $gl_debit_stmt->bind_param("iisssisssdds",
        $next_gl_id,
        $salary_expense_gl['id'],
        $salary_expense_gl['code'],
        $salary_expense_gl['name'],
        $salary_expense_gl['type'],
        $journal_entry_id,
        $journal_number,
        $payroll_ref_id,
        $description,
        $amount,
        $new_expense_bal,
        $department
    );
    $gl_debit_stmt->execute();
    $gl_debit_stmt->close();
    
    // GL Entry 2: Credit Salaries Payable
    $next_gl_id_2 = $next_gl_id + 1;
    
    $gl_sql_credit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'pr', ?, ?, 0, ?, ?, ?)";
    
    $gl_credit_stmt = $conn->prepare($gl_sql_credit);
    $gl_credit_stmt->bind_param("iisssisssdds",
        $next_gl_id_2,
        $salaries_payable_gl['id'],
        $salaries_payable_gl['code'],
        $salaries_payable_gl['name'],
        $salaries_payable_gl['type'],
        $journal_entry_id,
        $journal_number,
        $payroll_ref_id,
        $description,
        $amount,
        $new_payable_bal,
        $department
    );
    $gl_credit_stmt->execute();
    $gl_credit_stmt->close();
    
    error_log("Journal entry created successfully: $journal_number");
    return $journal_number;
}

/**
 * Get Salary Expense GL Account (561001)
 */
function getSalaryExpenseGL($conn) {
    error_log("Looking for Salary Expense GL account (561001)");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '561001' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found Salary Expense GL: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    error_log("ERROR: Salary Expense GL account 561001 not found!");
    return null;
}

/**
 * Get Salaries Payable GL Account (223001)
 */
function getSalariesPayableGL($conn) {
    error_log("Looking for Salaries Payable GL account (223001)");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '223001' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found Salaries Payable GL: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    error_log("ERROR: Salaries Payable GL account 223001 not found!");
    return null;
}

/**
 * Create journal entry and post to general ledger when driver payout is approved
 * 
 * @param mysqli $conn Database connection
 * @param array $driver_payout Driver payout data
 * @return string Journal number
 */
function createDriverJournalEntry($conn, $driver_payout) {
    error_log("Creating journal entry for driver payout: " . $driver_payout['payout_id']);
    
    // Generate journal number
    $journal_number = generateJENumber($conn);
    error_log("Generated JE number: $journal_number");
    
    // Auto-generate description
    $description = "Driver payout for " . ($driver_payout['driver_name'] ?? 'Driver') . 
                   " - Withdrawal request";
    error_log("Generated description: $description");
    
    // Get GL accounts
    // Debit: Driver Payment Expense (521001)
    // Credit: Driver Wallet Payable (213001)
    $expense_gl = getGLAccountByCode($conn, '521001'); // Driver Payment
    $driver_payable_gl = getGLAccountByCode($conn, '213001'); // Driver Wallet Payable
    
    if (!$expense_gl) {
        throw new Exception("Driver Payment GL account (521001) not found");
    }
    if (!$driver_payable_gl) {
        throw new Exception("Driver Wallet Payable GL account (213001) not found");
    }
    
    error_log("Expense GL: " . $expense_gl['code'] . " - " . $expense_gl['name']);
    error_log("Driver Wallet Payable GL: " . $driver_payable_gl['code'] . " - " . $driver_payable_gl['name']);
    
    $amount = $driver_payout['amount'];
    $department = 'Logistics'; // Drivers typically belong to Logistics
    
    // 1. Create Journal Entry Header
    $next_je_id = getNextAvailableId($conn, 'journal_entries');
    
    $je_sql = "INSERT INTO journal_entries (
        id, journal_number, transaction_date, reference_type, reference_id, 
        description, total_debit, total_credit, status, created_by, posted_at
    ) VALUES (?, ?, NOW(), 'D', ?, ?, ?, ?, 'posted', 'System', NOW())";
    
    $je_stmt = $conn->prepare($je_sql);
    $je_stmt->bind_param("isssdd", 
        $next_je_id,
        $journal_number, 
        $driver_payout['payout_id'], 
        $description, 
        $amount, 
        $amount
    );
    $je_stmt->execute();
    $journal_entry_id = $next_je_id;
    $je_stmt->close();
    
    error_log("Created journal entry header with ID: $journal_entry_id");
    
    // 2. Create Journal Entry Lines
    $next_line_id = getNextAvailableId($conn, 'journal_entry_lines');
    
    // Line 1: Debit Expense
    $line_sql_debit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)";
    
    $debit_stmt = $conn->prepare($line_sql_debit);
    $line_number = 1;
    $debit_stmt->bind_param("iiiisssdss",
        $next_line_id,
        $journal_entry_id,
        $line_number,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $amount,
        $description,
        $department
    );
    $debit_stmt->execute();
    $debit_stmt->close();
    
    // Line 2: Credit Driver Wallet Payable
    $next_line_id_2 = $next_line_id + 1;
    
    $line_sql_credit = "INSERT INTO journal_entry_lines (
        id, journal_entry_id, line_number, gl_account_id, gl_account_code, 
        gl_account_name, account_type, debit_amount, credit_amount, 
        description, department
    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?)";
    
    $credit_stmt = $conn->prepare($line_sql_credit);
    $line_number = 2;
    $credit_stmt->bind_param("iiiisssdss",
        $next_line_id_2,
        $journal_entry_id,
        $line_number,
        $driver_payable_gl['id'],
        $driver_payable_gl['code'],
        $driver_payable_gl['name'],
        $driver_payable_gl['type'],
        $amount,
        $description,
        $department
    );
    $credit_stmt->execute();
    $credit_stmt->close();
    
    error_log("Created journal entry lines");
    
    // 3. Post to General Ledger (Update Balances First)
    $new_expense_bal = updateGLAccountBalance($conn, $expense_gl['id'], $amount, 'debit');
    $new_payable_bal = updateGLAccountBalance($conn, $driver_payable_gl['id'], $amount, 'credit');

    // GL Entry 1: Debit Expense
    $next_gl_id = getNextAvailableId($conn, 'general_ledger');
    
    $gl_sql = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'D', ?, ?, ?, 0, ?, ?)";
    
    $gl_stmt = $conn->prepare($gl_sql);
    $gl_stmt->bind_param("iisssisssdds",
        $next_gl_id,
        $expense_gl['id'],
        $expense_gl['code'],
        $expense_gl['name'],
        $expense_gl['type'],
        $journal_entry_id,
        $journal_number,
        $driver_payout['payout_id'],
        $description,
        $amount,
        $new_expense_bal,
        $department
    );
    $gl_stmt->execute();
    $gl_stmt->close();
    
    // GL Entry 2: Credit Driver Wallet Payable
    $next_gl_id_2 = $next_gl_id + 1;
    
    $gl_sql_credit = "INSERT INTO general_ledger (
        id, gl_account_id, gl_account_code, gl_account_name, account_type,
        transaction_date, journal_entry_id, reference_id, reference_type,
        original_reference, description, debit_amount, credit_amount, running_balance, department
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'D', ?, ?, 0, ?, ?, ?)";
    
    $gl_stmt_credit = $conn->prepare($gl_sql_credit);
    $gl_stmt_credit->bind_param("iisssisssdds",
        $next_gl_id_2,
        $driver_payable_gl['id'],
        $driver_payable_gl['code'],
        $driver_payable_gl['name'],
        $driver_payable_gl['type'],
        $journal_entry_id,
        $journal_number,
        $driver_payout['payout_id'],
        $description,
        $amount,
        $new_payable_bal,
        $department
    );
    $gl_stmt_credit->execute();
    $gl_stmt_credit->close();
    
    error_log("Journal entry created successfully: $journal_number");
    return $journal_number;
}

/**
 * Get Transportation/Delivery Expense GL Account (540000)
 */
function getTransportationExpenseGL($conn) {
    error_log("Looking for Transportation Expense GL account (540000)");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '540000' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found Transportation Expense GL: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    // Fallback to general expense account
    error_log("Transportation Expense GL 540000 not found, using fallback");
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '500000' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    error_log("ERROR: No Transportation Expense GL account found!");
    return null;
}

/**
 * Get Driver Wallet Payable GL Account (213001)
 */
function getDriverWalletPayableGL($conn) {
    error_log("Looking for Driver Wallet Payable GL account (213001)");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '213001' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found Driver Wallet Payable GL: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    // Fallback: try to find Driver Payables parent account (213000)
    error_log("Driver Wallet Payable GL 213001 not found, trying parent 213000");
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = '213000' AND status = 'active'
            LIMIT 1";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Fallback found: " . $account['code'] . " - " . $account['name']);
        return $account;
    }
    
    error_log("ERROR: No Driver Wallet Payable GL account found!");
    return null;
}

/**
 * Get GL Account by Code
 * Simple helper to fetch GL account details by code
 */
function getGLAccountByCode($conn, $code) {
    error_log("Looking for GL account: $code");
    
    $sql = "SELECT id, code, name, type 
            FROM chart_of_accounts_hierarchy 
            WHERE code = ? AND status = 'active'
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        error_log("Found GL account: " . $account['code'] . " - " . $account['name'] . " (Type: " . $account['type'] . ")");
        return $account;
    }
    
    error_log("ERROR: GL account $code not found!");
    return null;
}

/**
 * Create journal entry for Accounts Receivable confirmation
 * Handles the 20% system revenue and 80% driver payout split
 */
function createARPaymentJournalEntry($conn, $data) {
    error_log("Creating AR journal entry for invoice: " . $data['invoice_id']);
    
    $journal_number = generateJENumber($conn);
    $invoice_id = $data['invoice_id'];
    $amount = floatval($data['amount']);
    $department = $data['department'] ?? 'Operations';
    $description = "Ride Confirmation - " . ($data['driver_name'] ?? 'Partner') . " (Invoice: $invoice_id)";

    // 1. Resolve GL Accounts
    $ar_gl = getGLAccountByCode($conn, '121001'); // Accounts Receivable
    $revenue_gl = getGLAccountByCode($conn, '411001'); // Service Revenue
    $driver_payable_gl = getGLAccountByCode($conn, '213001'); // Driver Wallet/Payable

    // Fallbacks if codes don't match exactly
    if (!$ar_gl) {
        $res = $conn->query("SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE name LIKE '%Receivable%' AND type='Asset' AND status='active' LIMIT 1");
        $ar_gl = $res->fetch_assoc();
    }
    if (!$revenue_gl) {
        $res = $conn->query("SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE (name LIKE '%Revenue%' OR name LIKE '%Income%') AND type='Revenue' AND status='active' LIMIT 1");
        $revenue_gl = $res->fetch_assoc();
    }
    if (!$driver_payable_gl) {
        $driver_payable_gl = getDriverWalletPayableGL($conn);
    }

    if (!$ar_gl || !$revenue_gl || !$driver_payable_gl) {
        throw new Exception("Accounting Error: Missing AR, Revenue, or Driver Payable GL accounts.");
    }

    $revenue_amt = $amount * 0.20;
    $driver_amt = $amount * 0.80;

    // 2. Create Journal Entry Header
    $je_id = getNextAvailableId($conn, 'journal_entries');
    $je_sql = "INSERT INTO journal_entries (id, journal_number, transaction_date, reference_type, reference_id, description, total_debit, total_credit, status, created_by, posted_at) VALUES (?, ?, NOW(), 'AR', ?, ?, ?, ?, 'posted', 'System', NOW())";
    $stmt = $conn->prepare($je_sql);
    $stmt->bind_param("isssdd", $je_id, $journal_number, $invoice_id, $description, $amount, $amount);
    $stmt->execute();

    // 3. Create Journal Entry Lines
    $line_id = getNextAvailableId($conn, 'journal_entry_lines');
    
    // Line 1: Debit AR (Full Amount)
    $line_sql = "INSERT INTO journal_entry_lines (id, journal_entry_id, line_number, gl_account_id, gl_account_code, gl_account_name, account_type, debit_amount, credit_amount, description, department) VALUES (?, ?, 1, ?, ?, ?, ?, ?, 0, ?, ?)";
    $stmt1 = $conn->prepare($line_sql);
    // Corrected type string from 10 to 9 chars: iiisssdss
    $stmt1->bind_param("iiisssdss", $line_id, $je_id, $ar_gl['id'], $ar_gl['code'], $ar_gl['name'], $ar_gl['type'], $amount, $description, $department);
    $stmt1->execute();

    // Line 2: Credit Revenue (20%)
    $line_id++;
    $line_sql2 = "INSERT INTO journal_entry_lines (id, journal_entry_id, line_number, gl_account_id, gl_account_code, gl_account_name, account_type, debit_amount, credit_amount, description, department) VALUES (?, ?, 2, ?, ?, ?, ?, 0, ?, ?, ?)";
    $stmt2 = $conn->prepare($line_sql2);
    // Corrected type string from 10 to 9 chars: iiisssdss
    $stmt2->bind_param("iiisssdss", $line_id, $je_id, $revenue_gl['id'], $revenue_gl['code'], $revenue_gl['name'], $revenue_gl['type'], $revenue_amt, $description, $department);
    $stmt2->execute();

    // Line 3: Credit Driver Payable (80%)
    $line_id++;
    $line_sql3 = "INSERT INTO journal_entry_lines (id, journal_entry_id, line_number, gl_account_id, gl_account_code, gl_account_name, account_type, debit_amount, credit_amount, description, department) VALUES (?, ?, 3, ?, ?, ?, ?, 0, ?, ?, ?)";
    $stmt3 = $conn->prepare($line_sql3);
    // Corrected type string from 10 to 9 chars: iiisssdss
    $stmt3->bind_param("iiisssdss", $line_id, $je_id, $driver_payable_gl['id'], $driver_payable_gl['code'], $driver_payable_gl['name'], $driver_payable_gl['type'], $driver_amt, $description, $department);
    $stmt3->execute();

    // 4. Update Balances & Post GL
    $new_ar_bal = updateGLAccountBalance($conn, $ar_gl['id'], $amount, 'debit');
    $new_rev_bal = updateGLAccountBalance($conn, $revenue_gl['id'], $revenue_amt, 'credit');
    $new_drv_bal = updateGLAccountBalance($conn, $driver_payable_gl['id'], $driver_amt, 'credit');

    $gl_id = getNextAvailableId($conn, 'general_ledger');
    $zero = 0;
    
    // Unified GL SQL (13 placeholders)
    $gl_sql = "INSERT INTO general_ledger (id, gl_account_id, gl_account_code, gl_account_name, account_type, transaction_date, journal_entry_id, reference_id, reference_type, original_reference, description, debit_amount, credit_amount, running_balance, department) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 'AR', ?, ?, ?, ?, ?, ?)";
    $gl_types = "iisssisssddds";

    // GL Entry 1: AR (Debit)
    $g1 = $conn->prepare($gl_sql);
    $g1->bind_param($gl_types, $gl_id, $ar_gl['id'], $ar_gl['code'], $ar_gl['name'], $ar_gl['type'], $je_id, $journal_number, $invoice_id, $description, $amount, $zero, $new_ar_bal, $department);
    $g1->execute();

    // GL Entry 2: Revenue (Credit)
    $gl_id++;
    $g2 = $conn->prepare($gl_sql);
    $g2->bind_param($gl_types, $gl_id, $revenue_gl['id'], $revenue_gl['code'], $revenue_gl['name'], $revenue_gl['type'], $je_id, $journal_number, $invoice_id, $description, $zero, $revenue_amt, $new_rev_bal, $department);
    $g2->execute();

    // GL Entry 3: Driver Payable (Credit)
    $gl_id++;
    $g3 = $conn->prepare($gl_sql);
    $g3->bind_param($gl_types, $gl_id, $driver_payable_gl['id'], $driver_payable_gl['code'], $driver_payable_gl['name'], $driver_payable_gl['type'], $je_id, $journal_number, $invoice_id, $description, $zero, $driver_amt, $new_drv_bal, $department);
    $g3->execute();

    return $journal_number;
}
?>
