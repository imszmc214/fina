<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');

// IMPORTANT: Move the AJAX request handling for DEPARTMENT DETAILS to the TOP
// This prevents output before headers when making AJAX calls
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_dept_details') {
    $dept_key = isset($_GET['dept_key']) ? $_GET['dept_key'] : '';
    $response = ['success' => false, 'data' => []];
    
    if ($dept_key) {
        // Get department summary (using MD5 hash for absolute name matching)
        $summary_stmt = $conn->prepare("SELECT 
            MAX(department) as actual_dept_name,
            SUM(annual_budget) as total_budget,
            SUM(committed_amount) as total_committed,
            SUM(spent) as total_spent,
            SUM(remaining_balance) as total_available
            FROM budget_allocations 
            WHERE MD5(department) = ? AND status = 'active'");
        $summary_stmt->bind_param("s", $dept_key);
        $summary_stmt->execute();
        $summary_result = $summary_stmt->get_result();
        
        if ($row = $summary_result->fetch_assoc()) {
            if ($row['total_budget'] === NULL) {
                $response['success'] = false;
                $response['message'] = 'No data found for this department ID';
            } else {
                $response['success'] = true;
                $dept_name = $row['actual_dept_name'];
                $total_used = ($row['total_committed'] ?? 0) + ($row['total_spent'] ?? 0);
                $total_budget = (float)($row['total_budget'] ?? 0);
                $utilization = $total_budget > 0 ? ($total_used / $total_budget) * 100 : 0;
                
                $response['data']['summary'] = [
                    'department' => $dept_name,
                    'total_budget' => $total_budget,
                    'total_committed' => (float)($row['total_committed'] ?? 0),
                    'total_spent' => (float)($row['total_spent'] ?? 0),
                    'total_available' => (float)($row['total_available'] ?? 0),
                    'utilization' => $utilization
                ];
                
                // Get breakdown by destination account (Accounts)
                $breakdown_stmt = $conn->prepare("SELECT 
                    MAX(id) as id,
                    COALESCE(to_account_name, category, 'Unknown Account') as category,
                    coa_id_to,
                    coa_id_from,
                    SUM(annual_budget) as annual_budget,
                    SUM(committed_amount) as committed,
                    SUM(spent) as spent,
                    SUM(remaining_balance) as available
                    FROM budget_allocations 
                    WHERE MD5(department) = ? AND status = 'active'
                    GROUP BY category, coa_id_to, coa_id_from
                    ORDER BY annual_budget DESC");
                $breakdown_stmt->bind_param("s", $dept_key);
                $breakdown_stmt->execute();
                $breakdown_result = $breakdown_stmt->get_result();
                
                $response['data']['breakdown'] = [];
                while ($cat = $breakdown_result->fetch_assoc()) {
                    $cat_budget = (float)($cat['annual_budget'] ?? 0);
                    $total_cat_used = ($cat['committed'] ?? 0) + ($cat['spent'] ?? 0);
                    $cat_utilization = $cat_budget > 0 ? ($total_cat_used / $cat_budget) * 100 : 0;
                    
                    $response['data']['breakdown'][] = [
                        'id' => $cat['id'],
                        'category' => $cat['category'],
                        'coa_id_to' => $cat['coa_id_to'],
                        'coa_id_from' => $cat['coa_id_from'],
                        'annual_budget' => $cat_budget,
                        'committed' => (float)($cat['committed'] ?? 0),
                        'spent' => (float)($cat['spent'] ?? 0),
                        'available' => (float)($cat['available'] ?? 0),
                        'utilization' => round($cat_utilization, 1),
                        'status' => $cat_utilization >= 90 ? 'Critical' : ($cat_utilization >= 80 ? 'Near Limit' : ($cat_utilization >= 70 ? 'Monitor' : ($cat_utilization <= 30 ? 'Underused' : 'Healthy')))
                    ];
                }
                $breakdown_stmt->close();
                
                // Get Recent Transactions
                $trans_stmt = $conn->prepare("SELECT jl.*, je.transaction_date, je.journal_number, je.reference_id, je.status as je_status
                             FROM journal_entry_lines jl
                             JOIN journal_entries je ON jl.journal_entry_id = je.id
                             WHERE MD5(jl.department) = ?
                             ORDER BY je.transaction_date DESC LIMIT 10");
                $trans_stmt->bind_param("s", $dept_key);
                $trans_stmt->execute();
                $trans_result = $trans_stmt->get_result();
                $response['data']['transactions'] = [];
                $total_approved_month = 0;
                $pending_approvals = 0;
                $current_month = date('m');
                $current_year = date('Y');
                
                while ($trans = $trans_result->fetch_assoc()) {
                    $amount = $trans['debit_amount'] > 0 ? $trans['debit_amount'] : $trans['credit_amount'];
                    $response['data']['transactions'][] = [
                        'id' => $trans['journal_number'],
                        'date' => date('M d, Y', strtotime($trans['transaction_date'])),
                        'description' => $trans['description'],
                        'category' => $trans['gl_account_name'],
                        'amount' => $amount,
                        'status' => $trans['je_status']
                    ];
                    if (date('m', strtotime($trans['transaction_date'])) == $current_month && date('Y', strtotime($trans['transaction_date'])) == $current_year && $trans['je_status'] == 'posted') {
                        $total_approved_month += $amount;
                    }
                    if ($trans['je_status'] == 'draft' || $trans['je_status'] == 'pending') {
                        $pending_approvals++;
                    }
                }
                $trans_stmt->close();
                
                $response['data']['transaction_stats'] = [
                    'total_approved_month' => $total_approved_month,
                    'pending_approvals' => $pending_approvals,
                    'avg_request_size' => count($response['data']['transactions']) > 0 ? $total_approved_month / count($response['data']['transactions']) : 0
                ];

                // Get Trends
                $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
                $trends_sql = "";
                $md5_match = "MD5(department) = ?";
                
                if ($period === 'quarterly') {
                    $trends_sql = "SELECT CONCAT('Q', QUARTER(je.transaction_date), ' ', YEAR(je.transaction_date)) as m, 
                                   SUM(jl.debit_amount + jl.credit_amount) as amt 
                                   FROM journal_entry_lines jl JOIN journal_entries je ON jl.journal_entry_id = je.id 
                                   WHERE $md5_match AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
                                   GROUP BY YEAR(je.transaction_date), QUARTER(je.transaction_date)
                                   ORDER BY je.transaction_date ASC";
                } else if ($period === 'yearly') {
                    $trends_sql = "SELECT YEAR(je.transaction_date) as m, 
                                   SUM(jl.debit_amount + jl.credit_amount) as amt 
                                   FROM journal_entry_lines jl JOIN journal_entries je ON jl.journal_entry_id = je.id 
                                   WHERE $md5_match AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
                                   GROUP BY m ORDER BY m ASC";
                } else {
                    $trends_sql = "SELECT DATE_FORMAT(transaction_date, '%b') as m, 
                                   SUM(jl.debit_amount+jl.credit_amount) as amt 
                                   FROM journal_entry_lines jl JOIN journal_entries je ON jl.journal_entry_id = je.id 
                                   WHERE $md5_match AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                   GROUP BY m, MONTH(transaction_date) ORDER BY transaction_date ASC";
                }
                
                $trends_stmt = $conn->prepare($trends_sql);
                $trends_stmt->bind_param("s", $dept_key);
                $trends_stmt->execute();
                $trends_res = $trends_stmt->get_result();
                $response['data']['trends'] = ['labels' => [], 'data' => []];
                while($tRow = $trends_res->fetch_assoc()) {
                    $response['data']['trends']['labels'][] = $tRow['m'];
                    $response['data']['trends']['data'][] = (float)$tRow['amt'];
                }
                $trends_stmt->close();
                
                // Alerts
                $response['data']['alerts'] = [];
                foreach ($response['data']['breakdown'] as $cat) {
                    if ($cat['utilization'] >= 80) {
                        $response['data']['alerts'][] = [
                            'type' => $cat['utilization'] >= 90 ? 'critical' : 'warning',
                            'message' => "Budget for {$cat['category']} is " . ($cat['utilization'] >= 90 ? "critically low" : "approaching limit") . " ({$cat['utilization']}% used)."
                        ];
                    }
                }
            }
        }
        $summary_stmt->close();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $response = ['success' => false, 'message' => ''];
    
    if (empty($_POST['id']) || empty($_POST['allocated_amount'])) {
        $response['message'] = "ID and Amount are required";
        echo json_encode($response);
        exit();
    }

    $id = (int)$_POST['id'];
    $new_amount = (float)$_POST['allocated_amount'];
    
    // Get current state to recalculate remaining and sync with COA
    $check = $conn->query("SELECT * FROM budget_allocations WHERE id = $id");
    if ($row = $check->fetch_assoc()) {
        $old_allocated = (float)$row['allocated_amount'];
        $diff = $new_amount - $old_allocated; // Positive if increasing budget, negative if decreasing
        
        // Preserve committed amount if not provided in the simplified form
        $committed = isset($_POST['committed_amount']) ? (float)$_POST['committed_amount'] : (float)$row['committed_amount'];
        $spent = (float)$row['spent'];
        $remaining = $new_amount - $spent - $committed;
        
        $stmt = $conn->prepare("UPDATE budget_allocations SET 
            annual_budget = ?, 
            allocated_amount = ?, 
            committed_amount = ?, 
            remaining_balance = ? 
            WHERE id = ?");
        $stmt->bind_param("ddddi", $new_amount, $new_amount, $committed, $remaining, $id);
        
        if ($stmt->execute()) {
            // SYNC WITH CHART OF ACCOUNTS if IDs are present and amount changed
            if (!empty($row['coa_id_from']) && !empty($row['coa_id_to']) && $diff != 0) {
                $from_id = (int)$row['coa_id_from'];
                $to_id = (int)$row['coa_id_to'];
                
                // Update Source Account (Level 3) - deduct the increase or return the decrease
                $conn->query("UPDATE chart_of_accounts_hierarchy SET balance = balance - $diff WHERE id = $from_id");
                
                // Update Destination Account (Level 4) - add the increase or deduct the decrease
                $conn->query("UPDATE chart_of_accounts_hierarchy 
                             SET balance = balance + $diff, 
                                 allocated_amount = allocated_amount + $diff 
                             WHERE id = $to_id");
            }
            $response['success'] = true;
            $response['message'] = "Budget updated successfully!";
        } else {
            $response['message'] = "Update failed!";
        }
        $stmt->close();
    } else {
        $response['message'] = "Record not found!";
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// EXPORT ALL handling via AJAX
if (isset($_GET['action']) && $_GET['action'] === 'export_all' && isset($_GET['ajax'])) {
    $department_filter = isset($_GET['department_filter']) ? $conn->real_escape_string($_GET['department_filter']) : '';
    $conditions = ["status = 'active'"];
    if ($department_filter != '') {
        $conditions[] = "department = '$department_filter'";
    }
    
    $where = implode(" AND ", $conditions);
    $sql = "SELECT department, annual_budget, category, from_allocated, to_allocated, 
                   allocated_amount, committed_amount, spent, remaining_balance 
            FROM budget_allocations WHERE $where ORDER BY department ASC";
    
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// GET ALL CATEGORIES for adjust budget
if (isset($_GET['action']) && $_GET['action'] === 'get_all_categories' && (isset($_GET['department']) || isset($_GET['dept_key']))) {
    $dept_key = isset($_GET['dept_key']) ? $_GET['dept_key'] : md5($_GET['department']);
    $stmt = $conn->prepare("SELECT id, category, allocated_amount, committed_amount, spent, remaining_balance 
            FROM budget_allocations 
            WHERE MD5(department) = ? AND status = 'active'
            ORDER BY category ASC");
    $stmt->bind_param("s", $dept_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

// GET EXISTING DEPARTMENTS
if (isset($_GET['action']) && $_GET['action'] === 'get_existing_departments' && isset($_GET['ajax'])) {
    $sql = "SELECT DISTINCT department FROM budget_allocations WHERE status = 'active' ORDER BY department ASC";
    $result = $conn->query($sql);
    $depts = [];
    while ($row = $result->fetch_assoc()) {
        $depts[] = $row['department'];
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'departments' => $depts]);
    exit();
}

// GET LEVEL 3 ACCOUNTS (Subcategories with GL code, name, balance)
if (isset($_GET['action']) && $_GET['action'] === 'get_level3_accounts' && isset($_GET['ajax'])) {
    $sql = "SELECT id, code, name, balance, type 
            FROM chart_of_accounts_hierarchy 
            WHERE level = 3 AND status = 'active' AND type = 'Expense'
            ORDER BY code ASC";
    $result = $conn->query($sql);
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'accounts' => $accounts]);
    exit();
}

// GET LEVEL 4 ACCOUNTS (Children of selected Level 3 subcategory)
if (isset($_GET['action']) && $_GET['action'] === 'get_level4_accounts' && isset($_GET['parent_id']) && isset($_GET['ajax'])) {
    $parent_id = (int)$_GET['parent_id'];
    $sql = "SELECT l4.id, l4.code, l4.name, l4.type, l4.balance, l4.allocated_amount, l4.parent_id,
                   l3.name as subcategory_name, l3.balance as parent_balance, l2.name as category_name
            FROM chart_of_accounts_hierarchy l4
            JOIN chart_of_accounts_hierarchy l3 ON l4.parent_id = l3.id
            JOIN chart_of_accounts_hierarchy l2 ON l3.parent_id = l2.id
            WHERE l4.level = 4 AND l4.parent_id = $parent_id AND l4.status = 'active'";
    $result = $conn->query($sql);
    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'accounts' => $accounts]);
    exit();
}

// AJAX request handling for ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $response = ['success' => false, 'message' => ''];
    $required = ['department', 'from_allocated', 'to_allocated'];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $response['message'] = "Error: Field '$field' is required!";
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    if (empty($_POST['from_account']) && empty($_POST['from_accounts'])) {
        $response['message'] = "Error: Source account is required!";
        echo json_encode($response);
        exit();
    }

    if (empty($_POST['to_accounts']) || !is_array($_POST['to_accounts'])) {
        $response['message'] = "Error: At least one destination account is required!";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    $department = trim(preg_replace('/\s+/', ' ', $_POST['department']));
    $to_accounts = $_POST['to_accounts'];
    $to_amounts = $_POST['to_amounts'];
    $from_accounts = isset($_POST['from_accounts']) ? $_POST['from_accounts'] : []; // Array of source IDs per destination
    
    // If from_accounts is not provided as array, fallback to single from_account
    if (empty($from_accounts) && isset($_POST['from_account'])) {
        $single_from_id = (int)$_POST['from_account'];
        $from_accounts = array_fill(0, count($to_accounts), $single_from_id);
    }

    $from_date = $_POST['from_allocated'];
    $to_date = $_POST['to_allocated'];

    // Pre-validation: Group by source to check total deduction vs balance
    $source_totals = [];
    foreach ($to_accounts as $idx => $to_id) {
        $src_id = (int)$from_accounts[$idx];
        $amt = (float)$to_amounts[$idx];
        if (!isset($source_totals[$src_id])) $source_totals[$src_id] = 0;
        $source_totals[$src_id] += $amt;
    }

    foreach ($source_totals as $src_id => $total) {
        if ($total <= 0) continue;
        $res = $conn->query("SELECT name, balance FROM chart_of_accounts_hierarchy WHERE id = $src_id");
        if (!$res || $res->num_rows === 0) {
            $response['message'] = "Error: Source account ID $src_id not found!";
            echo json_encode($response); exit();
        }
        $row = $res->fetch_assoc();
        if ($total > (float)$row['balance']) {
            $response['message'] = "Error: Total allocation for " . $row['name'] . " (₱" . number_format($total, 2) . ") exceeds balance (₱" . number_format($row['balance'], 2) . ")!";
            echo json_encode($response); exit();
        }
    }

    // Start Transaction
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO budget_allocations 
            (department, annual_budget, category, coa_id_from, coa_id_to, 
             from_allocated, to_allocated, from_account_name, to_account_name,
             allocated_amount, committed_amount, spent, remaining_balance, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, 'active')");

        foreach ($to_accounts as $index => $to_id) {
            $amt = (float)$to_amounts[$index];
            $to_id = (int)$to_id;
            $src_id = (int)$from_accounts[$index];
            
            if ($amt <= 0) continue;

            // Get names
            $src_res = $conn->query("SELECT name FROM chart_of_accounts_hierarchy WHERE id = $src_id");
            $src_row = $src_res->fetch_assoc();
            $src_name = $src_row['name'];

            $to_res = $conn->query("SELECT name FROM chart_of_accounts_hierarchy WHERE id = $to_id");
            $to_row = $to_res->fetch_assoc();
            $to_name = $to_row['name'];

            // CHECK FOR EXISTING ALLOCATION (To avoid duplicate rows in table for existing departments)
            $existing_check = $conn->prepare("SELECT id FROM budget_allocations WHERE department = ? AND coa_id_to = ? AND status = 'active' LIMIT 1");
            $existing_check->bind_param("si", $department, $to_id);
            $existing_check->execute();
            $check_res = $existing_check->get_result();
            
            if ($check_res && $check_res->num_rows > 0) {
                // UPDATE EXISTING RECORD
                $existing_row = $check_res->fetch_assoc();
                $allocation_id = $existing_row['id'];
                $update_alloc = $conn->query("UPDATE budget_allocations 
                                            SET annual_budget = annual_budget + $amt,
                                                allocated_amount = allocated_amount + $amt,
                                                remaining_balance = remaining_balance + $amt
                                            WHERE id = $allocation_id");
            } else {
                // INSERT NEW RECORD (For New Dept or New GL in Existing Dept)
                // Use Destination Name for 'category' to be consistent with UI
                $stmt->bind_param("sdsiissssdd", 
                    $department, $amt, $to_name, $src_id, $to_id,
                    $from_date, $to_date, $src_name, $to_name, 
                    $amt, $amt);
                
                if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
            }
            $existing_check->close();

            // Update TO account
            $conn->query("UPDATE chart_of_accounts_hierarchy SET balance = balance + $amt, allocated_amount = allocated_amount + $amt WHERE id = $to_id");
            
            // Deduct from FROM account
            $conn->query("UPDATE chart_of_accounts_hierarchy SET balance = balance - $amt WHERE id = $src_id");
        }

        $conn->commit();
        $response['success'] = true;
        $response['message'] = "Budget allocated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
    }

    $stmt->close();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// ARCHIVE handling via AJAX
if (isset($_GET['archive']) && isset($_GET['ajax'])) {
    $id = (int)$_GET['archive'];
    $response = ['success' => false, 'message' => ''];
    $stmt = $conn->prepare("UPDATE budget_allocations SET status = 'archived' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Budget allocation archived successfully!";
        } else {
            $response['message'] = "Archive failed!";
        }
        $stmt->close();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// AJAX request handling for table data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    $department_filter = isset($_GET['department_filter']) ? $conn->real_escape_string($_GET['department_filter']) : '';
    $pg = isset($_GET['pg']) ? max(1, intval($_GET['pg'])) : 1;
    $records_per_page = 10;
    $offset = ($pg - 1) * $records_per_page;

    $conditions = ["status = 'active'"]; 

    // Add the department filter if provided
    if ($department_filter != '') {
        $conditions[] = "department = '" . $conn->real_escape_string($department_filter) . "'";
    }

    // Count Total Unique Departments
    $count_sql = "SELECT COUNT(DISTINCT department) as total FROM budget_allocations";
    if (!empty($conditions)) {
        $count_sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $count_result = $conn->query($count_sql);
    $row_count = $count_result->fetch_assoc();
    $total_rows = $row_count['total'];
    $total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

    // Build Main Query - Grouped by Department
    $sql = "SELECT 
                department,
                SUM(annual_budget) as annual_budget,
                SUM(allocated_amount) as allocated_amount,
                SUM(committed_amount) as committed_amount,
                SUM(spent) as spent,
                SUM(remaining_balance) as remaining_balance,
                MD5(department) as dept_key,
                MAX(id) as id -- For actions
            FROM budget_allocations b";
            
    if (!empty($conditions)) {
        $sql .= " WHERE b." . implode(" AND b.", $conditions);
    }

    $sql .= " GROUP BY department ORDER BY department ASC LIMIT $records_per_page OFFSET $offset";

    $result = $conn->query($sql);
    
    $totalAllocated = 0;
    $totalCommitted = 0;
    $totalSpent = 0;
    $totalAvailable = 0;
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['utilization'] = $row['annual_budget'] > 0 ? ($row['spent'] / $row['annual_budget']) * 100 : 0;
        $totalAllocated += $row['annual_budget'];
        $totalCommitted += $row['committed_amount'];
        $totalSpent += $row['spent'];
        $totalAvailable += $row['remaining_balance'];
        $rows[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'totalAllocated' => $totalAllocated,
        'totalCommitted' => $totalCommitted,
        'totalSpent' => $totalSpent,
        'totalAvailable' => $totalAvailable,
        'total' => $total_rows,
        'page' => $pg,
        'pages' => $total_pages,
        'offset' => $offset,
        'records_per_page' => $records_per_page,
        'dept_keys' => array_map(function($r) { return md5($r['department']); }, $rows)
    ]);
    exit();
}

// --- For initial page load (non-AJAX) ---
$department_filter = isset($_GET['department_filter']) ? $_GET['department_filter'] : '';
$pg = isset($_GET['pg']) ? (int)$_GET['pg'] : 1;
if ($pg < 1) $pg = 1;
$records_per_page = 10;
$offset = ($pg - 1) * $records_per_page;

$conditions = ["status = 'active'"];

if ($department_filter != '') {
    $conditions[] = "department = '" . $conn->real_escape_string($department_filter) . "'";
}

// Initial Page Load - Count Total Unique Departments
$count_sql = "SELECT COUNT(DISTINCT department) as total FROM budget_allocations";
if (!empty($conditions)) {
    $count_sql .= " WHERE " . implode(" AND ", $conditions);
}

$count_result = $conn->query($count_sql);
$row_count = $count_result->fetch_assoc();
$total_rows = $row_count['total'] ?? 0;
$total_pages = ($total_rows > 0) ? ceil($total_rows / $records_per_page) : 1;

// Initial Page Load - Main Query Grouped by Department
$sql = "SELECT 
            department,
            SUM(annual_budget) as annual_budget,
            SUM(allocated_amount) as allocated_amount,
            SUM(committed_amount) as committed_amount,
            SUM(spent) as spent,
            SUM(remaining_balance) as remaining_balance,
            MD5(department) as dept_key,
            MAX(id) as id
        FROM budget_allocations b";
        
if (!empty($conditions)) {
    $sql .= " WHERE b." . implode(" AND b.", $conditions);
}

$sql .= " GROUP BY department ORDER BY department ASC LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($sql);

// Calculate totals for initial page load
$totalAllocated = 0;
$totalCommitted = 0;
$totalSpent = 0;
$totalAvailable = 0;

$rows_data = [];
while ($row = $result->fetch_assoc()) {
    $totalAllocated += $row['annual_budget']; // Total Budget set for the dept
    $totalCommitted += $row['committed_amount'];
    $totalSpent += $row['spent'];
    $totalAvailable += $row['remaining_balance'];
    $rows_data[] = $row;
}

// Reset result pointer for display
$result = $conn->query($sql);

// UPDATED DEPARTMENT LIST BASED ON IMAGE
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
    'Financials'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Allocation</title>
    <link rel="icon" href="logo.png" type="img">
    <!-- Remove CDN and use local Tailwind -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Tailwind CSS */
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
        
        /* Export Dropdown Styles */
    #exportMenu, #deptExportMenu { 
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); 
    }
    
    .modal-backdrop { 
        position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); 
        backdrop-filter: blur(4px); z-index: 100000; display: none;
    }
    .modal-backdrop.show { display: block; }
    
    #exportScopeModal.show { display: block; }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }

        /* Fix scrollbar layout - Remove outer scrollbar */
        html, body {
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* Ensure Sidebar's main container fills the space but doesn't overflow */
        main {
            height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
    </style>
    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>    
    <style>
        /* Flex utilities */
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
        
        /* Button styles */
        .border { border-width: 1px; }
        .border-gray-300 { border-color: #d1d5db; }
        .rounded-lg { border-radius: 0.5rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
        .focus\:ring-2:focus { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
        .focus\:ring-purple-500:focus { --tw-ring-opacity: 1; --tw-ring-color: rgb(139 92 246 / var(--tw-ring-opacity)); }
        .focus\:border-transparent:focus { border-color: transparent; }
        
        /* Table styles */
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
        
        /* Modal styles */
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
        
        /* Your existing custom styles */
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
        
        .badge-good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
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
        
        .progress-bar-container {
            width: 100px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            display: inline-block;
            margin-right: 8px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(to right, #7c3aed, #6d28d9);
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
            z-index: 1000;
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
            max-width: 1200px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
            overscroll-behavior: contain;
            scroll-behavior: smooth;
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
        
        /* DETAILS MODAL STYLES */
        .dept-icon-large {
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
        
        .dept-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .dept-info {
            flex: 1;
            margin-left: 20px;
        }
        
        .dept-name {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .dept-subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        
        .dept-period {
            font-size: 14px;
            color: #4f46e5;
            background: #eef2ff;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .budget-overview-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 24px 0;
        }
        
        .overview-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .overview-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        
        .overview-card .label {
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .overview-card .value {
            font-size: 28px;
            font-weight: bold;
            color: #1f2937;
            margin: 8px 0;
        }
        
        .overview-card .subvalue {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .overview-card.total-budget {
            border-top: 4px solid #7c3aed;
        }
        
        .overview-card.spent {
            border-top: 4px solid #ef4444;
        }
        
        .overview-card.available {
            border-top: 4px solid #10b981;
        }
        
        .overview-card.utilization {
            border-top: 4px solid #3b82f6;
        }
        
        .detail-tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tab-btn:hover {
            color: #7c3aed;
        }
        
        .tab-btn.active {
            color: #7c3aed;
            border-bottom-color: #7c3aed;
            background: #f5f3ff;
        }
        
        .tab-content-container {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content-container.active {
            display: block;
        }
        
        .breakdown-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 40px;
            margin-top: 20px;
        }
        
        .category-table-container {
            overflow-x: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: white;
        }
        
        .category-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .category-table th {
            background: #f8fafc;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        .category-row:hover {
            background: #f9fafb;
        }
        
        .category-name {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .category-budget, .category-spent, .category-available {
            font-weight: 600;
            color: #1f2937;
        }
        
        .category-usage {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .usage-bar {
            width: 100px;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            display: inline-block;
        }
        
        .usage-fill {
            height: 100%;
            border-radius: 4px;
        }
        
        .usage-percent {
            font-size: 13px;
            font-weight: 600;
            min-width: 40px;
        }
        
        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            display: inline-block;
            min-width: 80px;
        }
        
        .status-badge.good {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.danger {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-badge.success {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .chart-container-wrapper {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .chart-container-wrapper h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1f2937;
        }
        
        .chart-container {
            width: 100%;
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .status-filter {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            background: white;
        }
        
        .date-filter {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 12px;
            background: white;
            transition: all 0.2s ease;
        }
        
        .transaction-item:hover {
            border-color: #7c3aed;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.1);
        }
        
        .transaction-main {
            flex: 1;
        }
        
        .transaction-id {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .transaction-id strong {
            font-size: 15px;
            color: #1f2937;
            font-weight: 600;
        }
        
        .transaction-date {
            font-size: 13px;
            color: #9ca3af;
            background: #f3f4f6;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .transaction-desc {
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .transaction-category .cat-badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .transaction-side {
            text-align: right;
            margin-left: 20px;
        }
        
        .transaction-amount {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .transaction-status .status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status.approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status.rejected {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .transaction-actions button {
            background: none;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 8px;
        }
        
        .transaction-actions button:hover {
            background: #7c3aed;
            color: white;
            border-color: #7c3aed;
        }
        
        .transactions-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-item span {
            display: block;
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .summary-item strong {
            font-size: 18px;
            color: #1f2937;
            font-weight: 700;
        }
        
        .trends-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        
        .time-selector {
            display: flex;
            gap: 8px;
        }
        
        .time-btn {
            padding: 8px 20px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .time-btn:hover {
            border-color: #7c3aed;
            color: #7c3aed;
        }
        
        .time-btn.active {
            background: #7c3aed;
            color: white;
            border-color: #7c3aed;
        }
        
        .comparison-selector label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
        }
        
        .trends-charts {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .trends-charts {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-wrapper {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
        }

        .chart-container-trends {
            height: 250px;
            width: 100%;
            position: relative;
        }
        
        .chart-wrapper h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .trends-insights {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
        }
        
        .trends-insights h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1f2937;
        }
        
        .insight-list {
            list-style: none;
            padding: 0;
        }
        
        .insight-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .insight-item:last-child {
            border-bottom: none;
        }
        
        .insight-item i {
            margin-top: 2px;
        }
        
        .insight-item span {
            font-size: 14px;
            color: #374151;
            line-height: 1.4;
        }
        
        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 16px;
            background: white;
            border: 1px solid #e5e7eb;
        }
        
        .alert-item.warning {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
        }
        
        .alert-item.info {
            border-left: 4px solid #3b82f6;
            background: #eff6ff;
        }
        
        .alert-item.success {
            border-left: 4px solid #10b981;
            background: #f0fdf4;
        }
        
        .alert-icon {
            font-size: 24px;
            margin-top: 4px;
        }
        
        .alert-item.warning .alert-icon {
            color: #f59e0b;
        }
        
        .alert-item.info .alert-icon {
            color: #3b82f6;
        }
        
        .alert-item.success .alert-icon {
            color: #10b981;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 16px;
            color: #1f2937;
        }
        
        .alert-desc {
            color: #6b7280;
            margin-bottom: 12px;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .alert-meta {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .alert-category {
            background: #e5e7eb;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .alert-actions {
            display: flex;
            gap: 8px;
        }
        
        .alert-settings {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        
        .alert-settings h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1f2937;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .setting-item {
            display: flex;
            align-items: center;
        }
        
        .setting-item label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
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
        
        .btn-secondary {
            padding: 8px 16px;
            background: #f8fafc;
            color: #374151;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-secondary:hover {
            background: #f1f5f9;
        }
        
        /* ACTION BUTTON STYLES */
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-details {
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
        
        .btn-details:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-1px);
        }
        
        .btn-archive {
            background: linear-gradient(135deg, #ef4444, #dc2626);
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
        
        .btn-archive:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1b);
            transform: translateY(-1px);
        }
        
        .btn-action {
            padding: 8px 16px;
            background: #7c3aed;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            background: #6d28d9;
        }
        
        .btn-action.secondary {
            background: #f8fafc;
            color: #374151;
            border: 1px solid #e5e7eb;
        }
        
        .btn-action.secondary:hover {
            background: #f1f5f9;
        }
        
        /* Color utilities */
        .bg-purple-500 { background-color: #8b5cf6; }
        .bg-blue-500 { background-color: #3b82f6; }
        .bg-red-500 { background-color: #ef4444; }
        .bg-green-500 { background-color: #10b981; }
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
        
        /* Print Styles */
        @media print {
            body * {
                visibility: hidden;
            }
            #deptDetailsModal, #deptDetailsModal * {
                visibility: visible;
            }
            #deptDetailsModal {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .modal-overlay {
                background: white !important;
                backdrop-filter: none !important;
            }
            .modal-content {
                box-shadow: none !important;
                max-height: none !important;
                overflow: visible !important;
                width: 100% !important;
                max-width: none !important;
            }
            .modal-footer, .detail-tabs, .time-selector, .comparison-selector, .filter-controls {
                display: none !important;
            }
            .tab-btn {
                display: none !important;
            }
            .tab-content-container {
                display: block !important;
                opacity: 1 !important;
                visibility: visible !important;
            }
            .btn-secondary, .theme-button, .action-buttons, .footer-actions {
                display: none !important;
            }
        }
        
        /* More utilities */
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
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .animate-shimmer {
            animation: shimmer 1.5s infinite linear;
        }
        
        /* SEARCHABLE SELECT STYLES */
        .custom-select-container {
            position: relative;
            width: 100%;
        }
        
        .custom-select-trigger {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #1f2937;
            transition: all 0.2s;
        }
        
        .custom-select-trigger:focus-within {
            ring: 2px;
            ring-color: #7c3aed;
            border-color: transparent;
            box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.5);
        }
        
        .custom-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            margin-top: 4px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
            overflow: hidden;
            animation: slideDown 0.2s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .custom-select-search {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .custom-select-search input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            font-size: 13px;
            outline: none;
        }
        
        .custom-select-options {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .custom-select-option {
            padding: 10px 16px;
            font-size: 13px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .custom-select-option:hover {
            background: #f5f3ff;
            color: #7c3aed;
        }
        
        .custom-select-option.selected {
            background: #7c3aed;
            color: white;
        }
        
        .custom-select-option.hidden {
            display: none;
        }

        /* ALLOCATION ROWS STYLES */
        .allocation-rows-header {
            display: grid;
            grid-template-columns: 1fr 140px 40px;
            gap: 12px;
            margin-bottom: 8px;
            padding: 0 4px;
        }
        
        .allocation-row {
            display: grid;
            grid-template-columns: 1fr 140px 40px;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .allocation-row:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }
        
        .remove-row-btn {
            height: 38px;
            width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .remove-row-btn:hover {
            color: #ef4444;
            border-color: #fecaca;
            background: #fef2f2;
        }
        
        .add-account-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f1f5f9;
            color: #475569;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            width: 100%;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 8px;
        }
        
        .add-account-btn:hover {
            background: #eef2ff;
            color: #4f46e5;
            border-color: #c7d2fe;
            border-style: solid;
        }

        .shadow-inner {
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <?php include('sidebar.php'); ?>
    
    <div class="flex-1 overflow-y-auto px-6">
            <!-- Breadcrumb -->
            <div class="flex justify-between items-center mb-6 mt-4">
                <h1 class="text-2xl font-bold text-gray-800">Budget Allocation</h1>
                <div class="text-sm text-gray-600">
                    <a href="dashboard.php?page=dashboard" class="text-blue-600 hover:text-blue-800">Home</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-500">Budget Management</span>
                    <span class="mx-2">/</span>
                    <span class="text-gray-800 font-medium">Budget Allocation</span>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="summary-card">
                    <div class="card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-label">Total Allocated</div>
                        <div class="card-value" id="totalAnnualSummary">₱<?= number_format($totalAllocated, 2) ?></div>
                        <div class="card-change" id="totalAnnualChange"><?= ($totalAllocated > 0 && $totalCommitted > 0) ? round(($totalCommitted / $totalAllocated) * 100, 1) : 0 ?>% committed</div>
                    </div>
                </div>
                
                
                <div class="summary-card">
                    <div class="card-icon bg-red-500">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-label">Total Spent</div>
                        <div class="card-value" id="totalSpentSummary">₱<?= number_format($totalSpent, 2) ?></div>
                        <div class="card-change" id="totalSpentChange"><?= ($totalAllocated > 0 && $totalSpent > 0) ? round(($totalSpent / $totalAllocated) * 100, 1) : 0 ?>% of budget</div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-icon bg-green-500">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="card-content">
                        <div class="card-label">Total Available</div>
                        <div class="card-value" id="totalAvailableSummary">₱<?= number_format($totalAvailable, 2) ?></div>
                        <div class="card-change" id="totalAvailableChange"><?= ($totalAllocated > 0 && $totalAvailable > 0) ? round(($totalAvailable / $totalAllocated) * 100, 1) : 100 ?>% remaining</div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="theme-card p-6 mb-8 fade-in">
                <!-- Header with Filters -->
                <div class="flex flex-wrap justify-between items-center mb-6">
                    <div class="flex items-center gap-6">
                        <!-- Department Filter -->
                        <div class="flex items-center">
                            <select id="department_filter" class="border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">All Departments</option>
                                <?php
                                // UPDATED DEPARTMENT LIST
                                foreach ($departments as $dept) {
                                    $selected = ($department_filter === $dept) ? 'selected' : '';
                                    echo "<option value=\"$dept\" $selected>$dept</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Search Filter -->
                        <div class="relative flex-1 sm:flex-none sm:w-72">
                            <input type="text" 
                                   id="searchInput" 
                                   placeholder="Search department, category..." 
                                   class="w-full px-4 py-2.5 pl-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                   onkeyup="filterBudgetTable()">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400 text-sm"></i>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <button id="exportDropdownBtn" class="px-5 py-2.5 bg-white border border-gray-200 rounded-2xl text-sm font-bold text-gray-700 hover:bg-gray-50 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-50 flex items-center gap-3 transition-all active:scale-95 group" onclick="toggleExportMenu(event)">
                                <div class="w-8 h-8 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition-all shadow-sm">
                                    <i class="fas fa-file-export text-xs"></i>
                                </div>
                                <span>Export Data</span>
                            </button>
                            <div id="exportMenu" class="absolute right-0 mt-4 w-72 bg-white rounded-[32px] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-gray-100 p-4 z-50 hidden transition-all animate-fade-in">
                                <p class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase tracking-[2px] mb-2">Select Format</p>
                                <button onclick="openExportScopeModal('pdf')" class="w-full px-4 py-4 text-left text-sm font-bold text-gray-700 hover:bg-rose-50 hover:text-rose-600 rounded-2xl transition-all flex items-center gap-4 group/item">
                                    <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center group-hover/item:bg-rose-500 group-hover/item:text-white transition-all shadow-sm"><i class="fas fa-file-pdf"></i></div>
                                    <div>
                                        <div class="font-black">PDF Document</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Traditional Report</div>
                                    </div>
                                </button>
                                <button onclick="openExportScopeModal('excel')" class="w-full px-4 py-4 text-left text-sm font-bold text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 rounded-2xl transition-all flex items-center gap-4 group/item">
                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center group-hover/item:bg-emerald-500 group-hover/item:text-white transition-all shadow-sm"><i class="fas fa-file-excel"></i></div>
                                    <div>
                                        <div class="font-black">Excel Sheet</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Data Analysis</div>
                                    </div>
                                </button>
                                <div class="my-2 border-t border-gray-50"></div>
                                <button onclick="exportDataCSV()" class="w-full px-4 py-4 text-left text-sm font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 rounded-2xl transition-all flex items-center gap-4 group/item">
                                    <div class="w-10 h-10 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center group-hover/item:bg-blue-500 group-hover/item:text-white transition-all shadow-sm"><i class="fas fa-file-csv"></i></div>
                                    <div>
                                        <div class="font-black">CSV File</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Raw Spreadsheet</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <button class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-2xl text-sm font-bold hover:shadow-xl hover:shadow-purple-100 hover:-translate-y-0.5 transition-all flex items-center gap-3 active:scale-95" onclick="openAllocateModal()">
                            <i class="fas fa-plus-circle"></i>
                            Allocate Budget
                        </button>
                    </div>
                </div>

                <!-- Budget Table -->
                <div class="overflow-x-auto">
                    <table class="theme-table">
                        <thead>
                            <tr>
                                <th class="text-left">Department</th>
                                <th class="text-left">Allocated</th>
                                <th class="text-left">Spent</th>
                                <th class="text-left">Available</th>
                                <th class="text-left">Utilization</th>
                                <th class="text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="budgetTableBody">
                            <?php
                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $allocated = $row['allocated_amount'];
                                    $committed = $row['committed_amount'];
                                    $spent = $row['spent'];
                                    $available = $row['remaining_balance'];
                                    $total_used = $spent;
                                    $utilization = $allocated > 0 ? ($total_used / $allocated) * 100 : 0;
                                    
                                    // Determine status badge based on utilization
                                    if ($utilization >= 90) {
                                        $statusClass = 'badge-danger';
                                        $statusText = 'High';
                                    } elseif ($utilization >= 70) {
                                        $statusClass = 'badge-warning';
                                        $statusText = 'Medium';
                                    } else {
                                        $statusClass = 'badge-good';
                                        $statusText = 'Good';
                                    }
                                ?>
                                <tr class="fade-in">
                                    <td class="flex items-center gap-3">
                                        <i class="fas fa-building text-purple-500"></i>
                                        <span><?= htmlspecialchars($row['department']) ?></span>
                                    </td>
                                    <td class="font-semibold">₱<?= number_format($allocated, 2) ?></td>
                                    <td class="font-semibold">₱<?= number_format($allocated, 2) ?></td>
                                    <td class="font-semibold text-red-600">₱<?= number_format($spent, 2) ?></td>
                                    <td class="font-semibold text-green-600">₱<?= number_format($available, 2) ?></td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="progress-bar-container">
                                                <div class="progress-fill" style="width: <?= min($utilization, 100) ?>%"></div>
                                            </div>
                                            <span class="text-sm font-medium"><?= number_format($utilization, 1) ?>%</span>
                                            <span class="theme-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-details" 
                                                    onclick="viewDepartmentDetails('<?= htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') ?>', '<?= $row['dept_key'] ?>')">
                                                <i class="fas fa-chart-bar mr-1"></i> Details
                                            </button>
                                            <button class="btn-archive" 
                                                    onclick="archiveBudget(<?= $row['id'] ?>)">
                                                <i class="fas fa-archive mr-1"></i> Archive
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                }
                            } else { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-500">
                                        <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                                        No budget allocations found
                                    </td>
                                </tr>
                            <?php } ?>
                            
                            <!-- Totals Row -->
                            <?php if ($totalAllocated > 0) { ?>
                            <tr class="bg-gray-50 font-bold">
                                <td class="text-right py-4 pr-4 uppercase tracking-wider text-gray-500">Total Dashboard Summary</td>
                                <td class="text-purple-700 py-4">₱<?= number_format($totalAllocated, 2) ?></td>
                                <td class="text-blue-700 py-4">₱<?= number_format($totalAllocated, 2) ?></td>
                                <td class="text-red-700 py-4">₱<?= number_format($totalSpent, 2) ?></td>
                                <td class="text-green-700 py-4">₱<?= number_format($totalAvailable, 2) ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-8 mb-12 px-2">
                    <div id="pageStatus" class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_rows); ?> of <?php echo $total_rows; ?> Records
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Prev Button -->
                        <button id="prevPage" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider flex items-center <?= $pg <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $pg <= 1 ? 'disabled' : '' ?>>
                            <i class="fas fa-chevron-left mr-2 text-[10px]"></i> Prev
                        </button>

                        <!-- Next Button -->
                        <button id="nextPage" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider flex items-center <?= $pg >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= $pg >= $total_pages ? 'disabled' : '' ?>>
                            Next <i class="fas fa-chevron-right ml-2 text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
    </div>

    <div id="addModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content w-full max-w-4xl">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-xl font-bold text-gray-800">Allocate Budget</h3>
                        <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="addForm">
                        <!-- Top Options: Method & Department -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Allocation Method</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative flex cursor-pointer">
                                        <input type="radio" name="allocation_type" value="new" checked class="peer sr-only" onchange="toggleAllocationType()">
                                        <div class="w-full py-2 px-4 text-center rounded-lg border-2 border-gray-100 bg-white peer-checked:border-purple-600 peer-checked:bg-purple-50 transition-all">
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-purple-700">New Dept</span>
                                        </div>
                                    </label>
                                    <label class="relative flex cursor-pointer">
                                        <input type="radio" name="allocation_type" value="existing" class="peer sr-only" onchange="toggleAllocationType()">
                                        <div class="w-full py-2 px-4 text-center rounded-lg border-2 border-gray-100 bg-white peer-checked:border-purple-600 peer-checked:bg-purple-50 transition-all">
                                            <span class="text-xs font-bold text-gray-600 peer-checked:text-purple-700">Existing Dept</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Department Selection</label>
                                <select name="department" id="alloc_department" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-500 bg-white">
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept) echo "<option value=\"$dept\">$dept</option>"; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Step Selection (Image Style) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                            <!-- 2. SUBCATEGORY -->
                            <div class="step-selection-item">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">1. SUBCATEGORY</label>
                                <div class="custom-select-container" id="from_account_container">
                                    <select name="from_account" id="from_account" class="hidden" onchange="updateFromBalance()">
                                        <option value="">Select a Subcategory</option>
                                    </select>
                                </div>
                                <div id="from_account_info" class="mt-2 text-xs font-semibold text-green-600 hidden">
                                    Available: <span id="from_balance">₱0.00</span> 
                                    <span class="text-gray-400 ml-2">GL: <span id="from_gl_code">-</span></span>
                                </div>
                            </div>
                            <!-- 3. GL ACCOUNT -->
                            <div class="step-selection-item">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">2. GL ACCOUNT</label>
                                <div class="custom-select-container" id="gl_account_selection_container">
                                    <select id="gl_account_selector" class="hidden" onchange="handleAddGLAccount(this.value)">
                                        <option value="">Select a GL Account</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SELECTED TABLE -->
                        <div class="mb-10">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-list-ul text-purple-600 text-xs"></i>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">SELECTED GL ACCOUNTS & BUDGETS</span>
                            </div>
                            <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                <table class="w-full text-left border-collapse">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">GL CODE</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">NAME</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">CATEGORY</th>
                                            <th class="px-4 py-3 text-[10px] font-bold text-gray-500 uppercase tracking-wider">SUBCATEGORY</th>
                                            <th class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-wider">BUDGET ALLOC.</th>
                                            <th class="px-4 py-3 w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="allocationTableBody" class="divide-y divide-gray-100">
                                        <!-- Dynamic rows -->
                                    </tbody>
                                    <tfoot class="bg-gray-50 border-t border-gray-200">
                                        <tr>
                                            <td colspan="4" class="px-4 py-5 text-center">
                                                <span class="text-xs font-bold text-purple-700 uppercase tracking-widest">CALCULATED TOTAL:</span>
                                            </td>
                                            <td class="px-4 py-5 text-right">
                                                <span class="text-lg font-black text-purple-800" id="modal_total_display">₱ 0.00</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Hidden fields and Summary -->
                        <input type="hidden" name="category" id="category_hidden" value="">
                        <!-- Allocation Summary & Visualization (Rich Version Restored) -->
                        <div id="allocation_percentage" class="hidden mb-10">
                            <!-- Percentage Badge -->
                            <div class="flex items-center justify-between mb-4 p-5 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-2xl border border-purple-100 shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-full bg-white shadow-md flex items-center justify-center border-2 border-purple-100">
                                        <i class="fas fa-chart-pie text-purple-600 text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1" id="percentage_label_text">Current Item Distribution</p>
                                        <p class="text-3xl font-black text-purple-600" id="percentage_value">0%</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mb-1">System Status</p>
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold shadow-sm" id="allocation_status_badge">
                                        <i class="fas fa-circle text-[6px] mr-2"></i>
                                        <span id="allocation_status_text">Normal</span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="mb-5 px-1">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest" id="progress_bar_label">Item Allocation Progress</span>
                                    <span class="text-[10px] font-bold text-gray-700" id="percentage_fraction">₱0 / ₱0</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden shadow-inner border border-gray-100">
                                    <div id="percentage_bar" class="bg-purple-600 h-3 rounded-full transition-all duration-500 ease-out relative" style="width: 0%">
                                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-30 animate-shimmer"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Breakdown Details -->
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-blue-50/50 rounded-xl p-3 border border-blue-100/50">
                                    <p class="text-[9px] text-blue-500 font-bold uppercase tracking-wider mb-1" id="allocating_label">Item Amount</p>
                                    <p class="text-sm font-black text-blue-700" id="allocating_amount">₱0.00</p>
                                </div>
                                <div class="bg-purple-50/50 rounded-xl p-3 border border-purple-100/50">
                                    <p class="text-[9px] text-purple-500 font-bold uppercase tracking-wider mb-1">Item Percent</p>
                                    <p class="text-sm font-black text-purple-700" id="percentage_display">0%</p>
                                </div>
                                <div class="bg-green-50/50 rounded-xl p-3 border border-green-100/50">
                                    <p class="text-[9px] text-green-500 font-bold uppercase tracking-wider mb-1">Total Remaining</p>
                                    <p class="text-sm font-black text-green-700" id="remaining_amount">₱0.00</p>
                                </div>
                            </div>
                            
                            <!-- Warning Message -->
                            <div id="allocation_warning" class="mt-4 p-3 bg-red-50 border border-red-100 rounded-xl hidden flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-red-700 font-bold mt-1">Total distribution exceeds the available source balance!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4 mb-8">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Period From</label>
                                <input type="date" name="from_allocated" id="from_allocated" required 
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500" 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Period To</label>
                                <input type="date" name="to_allocated" id="to_allocated" required 
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-500" 
                                       value="2026-12-21"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="flex gap-4">
                            <button type="submit" id="submitAllocationBtn" class="theme-button flex-1 py-3 text-sm font-bold uppercase tracking-widest">
                                Allocate Budget
                            </button>
                            <button type="button" onclick="closeModal('addModal')" class="theme-button-secondary flex-1 py-3 text-sm font-bold uppercase tracking-widest">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Details Modal -->
    <div id="deptDetailsModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content">
                <div class="p-6">
                    <!-- MODAL HEADER -->
                    <div class="flex justify-between items-start mb-6">
                        <div class="flex items-start gap-4">
                            <div class="dept-icon-large">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="dept-info">
                                <h2 class="dept-name" id="detailDeptName">Department Name</h2>
                                <div class="dept-subtitle">Fiscal Year 2024</div>
                                <div class="dept-period" id="detailFiscalYear">Jan 1 - Dec 31, 2024</div>
                            </div>
                        </div>
                        <button onclick="closeModal('deptDetailsModal')" class="text-gray-400 hover:text-gray-600 text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- BUDGET OVERVIEW GRID -->
                    <div class="budget-overview-grid">
                        <div class="overview-card total-budget">
                            <div class="label">Total Budget</div>
                            <div class="value" id="detailTotalBudget">₱0</div>
                            <div class="subvalue" id="detailBudgetPeriod">Annual Allocation</div>
                        </div>
                        
                        <div class="overview-card spent">
                            <div class="label">Spent</div>
                            <div class="value" id="detailTotalSpent">₱0</div>
                            <div class="subvalue" id="detailSpentPercent">0% of budget</div>
                        </div>
                        
                        <div class="overview-card available">
                            <div class="label">Available</div>
                            <div class="value" id="detailTotalAvailable">₱0</div>
                            <div class="subvalue" id="detailAvailablePercent">0% remaining</div>
                        </div>
                        
                        <div class="overview-card utilization">
                            <div class="label">Utilization</div>
                            <div class="value" id="detailUtilization">0%</div>
                            <div class="subvalue" id="detailUtilizationStatus">Within target</div>
                        </div>
                    </div>

                    <!-- TAB NAVIGATION -->
                    <div class="detail-tabs mt-8 mb-6">
                        <button class="tab-btn active" data-tab="breakdownTab" onclick="switchDetailTab('breakdownTab')">
                            <i class="fas fa-chart-pie mr-2"></i> Budget Breakdown
                        </button>
                        <button class="tab-btn" data-tab="transactionsTab" onclick="switchDetailTab('transactionsTab')">
                            <i class="fas fa-list mr-2"></i> Recent Transactions
                        </button>
                        <button class="tab-btn" data-tab="trendsTab" onclick="switchDetailTab('trendsTab')">
                            <i class="fas fa-chart-line mr-2"></i> Spending Trends
                        </button>
                        <button class="tab-btn" data-tab="alertsTab" onclick="switchDetailTab('alertsTab')">
                            <i class="fas fa-bell mr-2"></i> Alerts & Notices
                        </button>
                    </div>

                    <!-- TAB 1: BUDGET BREAKDOWN -->
                    <div id="breakdownTab" class="tab-content-container active">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold">Budget by Expense Accounts</h3>
                            <div class="text-sm text-gray-500" id="breakdownUpdateTime">Updated: Today</div>
                        </div>
                        
                        <div class="breakdown-container">
                            <!-- PIE CHART -->
                            <div class="chart-container-wrapper">
                                <h4>Account Distribution</h4>
                                <div class="chart-container">
                                    <canvas id="budgetPieChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                            
                            <!-- CATEGORY TABLE -->
                            <div class="category-table-container">
                                <table class="category-table">
                                    <thead>
                                        <tr>
                                            <th>Accounts</th>
                                            <th>Budget</th>
                                            <th>Spent</th>
                                            <th>Available</th>
                                            <th>% Used</th>
                                            <th>Status</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="categoryTableBody">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: RECENT TRANSACTIONS -->
                    <div id="transactionsTab" class="tab-content-container">
                        <div class="transactions-header">
                            <h3 class="text-lg font-semibold">Recent Transactions</h3>
                            <div class="filter-controls">
                                <input type="date" class="date-filter" id="transactionDate">
                            </div>
                        </div>
                        
                        <div class="transactions-list" id="transactionsList">
                            <!-- Transactions will be loaded here -->
                        </div>
                        
                        <!-- TRANSACTIONS SUMMARY -->
                        <div class="transactions-summary">
                            <div class="summary-item">
                                <span>Total Approved this Month</span>
                                <strong id="totalApproved">₱0</strong>
                            </div>
                            <div class="summary-item">
                                <span>Pending Approvals</span>
                                <strong id="totalPending">₱0</strong>
                            </div>
                            <div class="summary-item">
                                <span>Average Request Size</span>
                                <strong id="avgRequest">₱0</strong>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: SPENDING TRENDS -->
                    <div id="trendsTab" class="tab-content-container">
                        <div class="trends-controls">
                            <div class="time-selector">
                                <button class="time-btn active" onclick="changeTrendPeriod('monthly')">Monthly</button>
                                <button class="time-btn" onclick="changeTrendPeriod('quarterly')">Quarterly</button>
                                <button class="time-btn" onclick="changeTrendPeriod('yearly')">Yearly</button>
                            </div>
                            <div class="comparison-selector">
                                <label>
                                    <input type="checkbox" id="comparePrevious" onchange="toggleComparison()">
                                    Compare with Previous Year
                                </label>
                            </div>
                        </div>
                        
                        <div class="trends-charts">
                            <div class="chart-wrapper">
                                <h4>Monthly Spending</h4>
                                <div class="chart-container-trends">
                                    <canvas id="monthlyTrendChart"></canvas>
                                </div>
                            </div>
                            
                            <div class="chart-wrapper">
                                <h4>Category Comparison</h4>
                                <div class="chart-container-trends">
                                    <canvas id="categoryTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="trends-insights">
                            <h4>Key Insights</h4>
                            <ul class="insight-list" id="keyInsightsList">
                                <li class="insight-item">
                                    <i class="fas fa-arrow-up text-red-500"></i>
                                    <span>Fuel expenses increased by 15% this month</span>
                                </li>
                                <li class="insight-item">
                                    <i class="fas fa-arrow-down text-green-500"></i>
                                    <span>Maintenance costs decreased by 10% vs last quarter</span>
                                </li>
                                <li class="insight-item">
                                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                                    <span>Office supplies budget will be exhausted in 2 months at current rate</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- TAB 4: ALERTS & NOTICES -->
                    <div id="alertsTab" class="tab-content-container">
                        <h3 class="text-lg font-semibold mb-6">Budget Alerts & Notices</h3>
                        
                        <div class="alerts-container" id="alertsContainer">
                            <!-- Alerts will be populated here -->
                        </div>
                        
                        <!-- ALERT SETTINGS -->
                        <div class="alert-settings">
                            <h4>Alert Settings</h4>
                            <div class="settings-grid">
                                <div class="setting-item">
                                    <label>
                                        <input type="checkbox" checked>
                                        Send email when category exceeds 80%
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>
                                        <input type="checkbox" checked>
                                        Notify for unusual spending patterns
                                    </label>
                                </div>
                                <div class="setting-item">
                                    <label>
                                        <input type="checkbox">
                                        Daily budget summary report
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL FOOTER -->
                    <div class="modal-footer mt-8">
                        <div class="footer-actions">
                            <button class="btn-secondary" onclick="printDepartmentReport()">
                                <i class="fas fa-print mr-2"></i> Print Report
                            </button>
                            <div class="relative inline-block text-left">
                                <button class="btn-secondary flex items-center gap-2" onclick="toggleDeptExportMenu(event)">
                                    <i class="fas fa-download mr-1"></i> Export Data
                                </button>
                                <div id="deptExportMenu" class="absolute right-0 bottom-full mb-3 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 p-2 z-50 hidden transition-all animate-fade-in">
                                    <button onclick="exportDepartmentPDF()" class="w-full px-4 py-3 text-left text-sm font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                        <div class="w-8 h-8 bg-rose-50 text-rose-500 rounded-lg flex items-center justify-center"><i class="fas fa-file-pdf text-xs"></i></div> PDF Document
                                    </button>
                                    <button onclick="exportDepartmentExcel()" class="w-full px-4 py-3 text-left text-sm font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                        <div class="w-8 h-8 bg-emerald-50 text-emerald-500 rounded-lg flex items-center justify-center"><i class="fas fa-file-excel text-xs"></i></div> Excel Sheet
                                    </button>
                                    <button onclick="exportDepartmentData()" class="w-full px-4 py-3 text-left text-sm font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-50 text-blue-500 rounded-lg flex items-center justify-center"><i class="fas fa-file-csv text-xs"></i></div> CSV File
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="footer-info">
                            <span id="lastUpdated">Last Updated: Loading...</span>
                            <span>Data Source: Budget Management System</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Export Scope Modal -->
    <div id="exportScopeBackdrop" class="modal-backdrop" onclick="closeExportScopeModal()"></div>
    <div id="exportScopeModal" class="modal-box p-10 hidden fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[100001] bg-white rounded-[32px] shadow-2xl w-[90%] max-w-lg">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fas fa-file-export"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900 leading-tight">Export Context</h3>
                    <p class="text-sm font-bold text-gray-500">Choose the scope of your budget report</p>
                </div>
            </div>
            <button onclick="closeExportScopeModal()" class="w-12 h-12 flex items-center justify-center rounded-2xl hover:bg-gray-100 transition-all text-gray-400 hover:text-gray-900">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <button onclick="executeExport('filtered')" class="p-6 bg-white border border-gray-200 rounded-[32px] text-left hover:border-violet-500 hover:shadow-2xl hover:shadow-violet-100 transition-all group">
                <div class="w-10 h-10 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-violet-600 group-hover:text-white transition-all">
                    <i class="fas fa-filter"></i>
                </div>
                <div class="text-sm font-black text-gray-900 mb-1">Current Filter</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Respect Active Selection</div>
            </button>

            <button onclick="executeExport('all')" class="p-6 bg-white border border-gray-200 rounded-[32px] text-left hover:border-blue-500 hover:shadow-2xl hover:shadow-blue-100 transition-all group md:col-span-2">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="text-sm font-black text-gray-900 mb-1">Full Summary</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">All Departments & Allocations</div>
            </button>
        </div>

        <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-2xl border border-amber-100">
            <i class="fas fa-info-circle text-amber-500"></i>
            <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Export will include all relevant budget metrics.</p>
        </div>
    </div>
    <div id="adjustBudgetModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content w-full max-w-lg overflow-hidden !rounded-[24px]">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-700 p-6 text-white relative">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center border border-white/30">
                            <i class="fas fa-edit text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Adjust Account Budget</h3>
                            <p class="text-purple-100 text-sm opacity-80">Modify individual account ceilings</p>
                        </div>
                    </div>
                    <button onclick="closeModal('adjustBudgetModal')" class="absolute top-6 right-6 text-white/60 hover:text-white transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div class="p-8">
                    <form id="adjustForm">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="space-y-6">
                            <!-- Account Information (Read-only) -->
                            <div class="group">
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">Account to Adjust</label>
                                <div class="relative">
                                    <i class="fas fa-file-invoice-dollar absolute left-4 top-1/2 -translate-y-1/2 text-purple-400"></i>
                                    <input type="hidden" name="id" id="adjust_account_id">
                                    <div id="adjust_account_name_display" 
                                         class="w-full bg-gray-50 border-2 border-gray-100 rounded-2xl pl-12 pr-4 py-3.5 text-sm font-bold text-gray-700">
                                        Loading account...
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Adjustment Fields -->
                            <div id="adjustFields" class="space-y-6 hidden animate-fadeIn">
                                <div class="group">
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest mb-2 ml-1">New Annual Budget Ceiling</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-lg">₱</span>
                                        <input type="number" name="allocated_amount" id="adjust_allocated_amount" required step="0.01" min="0" 
                                               class="w-full bg-white border-2 border-purple-100 rounded-2xl pl-10 pr-4 py-4 text-xl font-black text-purple-700 focus:border-purple-500 focus:ring-4 focus:ring-purple-50 outline-none transition-all"
                                               placeholder="0.00">
                                    </div>
                                    <p class="mt-2 ml-1 text-[10px] text-gray-400 font-medium italic">* This will update the total budget for the entire department.</p>
                                </div>

                                <!-- Current Spent Info -->
                                <div class="bg-red-50/50 border border-red-100 rounded-2xl p-5 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-red-800 uppercase tracking-wider">Current Spending</p>
                                            <p class="text-[10px] text-red-600 font-medium">Actual posted transactions</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span id="adjust_spent_display" class="text-xl font-black text-red-600">₱0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer Actions -->
                        <div class="flex gap-4 mt-10">
                            <button type="submit" class="flex-[2] bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold py-4 rounded-2xl shadow-lg shadow-purple-200 hover:shadow-purple-300 hover:-translate-y-0.5 transition-all">
                                Save Changes
                            </button>
                            <button type="button" onclick="closeModal('adjustBudgetModal')" class="flex-1 bg-gray-100 text-gray-600 font-bold py-4 rounded-2xl hover:bg-gray-200 transition-all">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ========== JAVASCRIPT FUNCTIONALITY ==========
    // Initialize global variables at the top
    var deptChart = null;
    var monthlyChart = null;
    var categoryChart = null;
    var currentDepartment = '', currentDeptKey = '';
    var pg = <?= json_encode($pg) ?>;
    var pages = <?= json_encode($total_pages) ?>;
    var department_filter = <?= json_encode($department_filter) ?>;
    var staticDepartments = <?= json_encode($departments) ?>;

    // Format money function
    function formatMoney(amount) {
        if (!amount) return '0.00';
        return parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Get fiscal year
    function getFiscalYear() {
        const currentYear = new Date().getFullYear();
        return `Jan 1 - Dec 31, ${currentYear}`;
    }

    // Get icon for category
    function getCategoryIcon(category) {
        if (!category) return 'fa-folder text-gray-500';
        
        const icons = {
            'fuel': 'fa-gas-pump text-blue-500',
            'maintenance': 'fa-tools text-green-500',
            'training': 'fa-graduation-cap text-purple-500',
            'office supplies': 'fa-box text-yellow-500',
            'software': 'fa-laptop-code text-indigo-500',
            'travel': 'fa-plane text-red-500',
            'utilities': 'fa-bolt text-orange-500',
            'salaries': 'fa-users text-pink-500',
            'tax': 'fa-file-invoice-dollar text-teal-500',
            'management': 'fa-chart-line text-purple-500',
            'vehicle': 'fa-car text-blue-500',
            'equipments': 'fa-wrench text-gray-500',
            'assets': 'fa-building text-indigo-500',
            'compensation': 'fa-money-check-alt text-green-500',
            'benefits': 'fa-gift text-pink-500',
            'health': 'fa-heartbeat text-red-500',
            'safety': 'fa-shield-alt text-yellow-500',
            'employees': 'fa-users text-blue-500',
            'facility': 'fa-building text-gray-500',
            'expenses': 'fa-file-invoice text-orange-500',
            'petty cash': 'fa-money-bill-wave text-green-500',
            'accounts': 'fa-book text-blue-500',
            'payables': 'fa-file-invoice-dollar text-purple-500',
            'disbursement': 'fa-share-square text-red-500',
            'general': 'fa-chart-bar text-indigo-500',
            'ledger': 'fa-book text-teal-500'
        };
        
        // Find matching icon
        const catLower = category.toLowerCase();
        for (const [key, icon] of Object.entries(icons)) {
            if (catLower.includes(key)) {
                return icon;
            }
        }
        
        return 'fa-folder text-gray-500';
    }

    // Helper function to escape HTML for JavaScript
    function escapeHtmlForJs(text) {
        if (!text) return '';
        return text
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'")
            .replace(/"/g, '\\"')
            .replace(/\n/g, '\\n')
            .replace(/\r/g, '\\r');
    }

    // Render table rows
    function renderRows(data) {
        let tbody = document.getElementById('budgetTableBody');
        tbody.innerHTML = "";
        
        // Update summary cards
        updateSummaryCards(data);
        
        if (data.rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                No budget allocations found
            </td></tr>`;
        } else {
            let totalAllocated = 0;
            let totalCommitted = 0;
            let totalSpent = 0;
            let totalAvailable = 0;
            
            data.rows.forEach(row => {
                const allocated = parseFloat(row.allocated_amount) || 0;
                const committed = parseFloat(row.committed_amount) || 0;
                const spent = parseFloat(row.spent) || 0;
                const available = parseFloat(row.remaining_balance) || 0;
                const total_used = spent;
                const utilization = allocated > 0 ? (total_used / allocated) * 100 : 0;
                
                totalAllocated += allocated;
                totalCommitted += committed;
                totalSpent += spent;
                totalAvailable += available;
                
                // Determine status
                let statusClass, statusText;
                if (utilization >= 90) {
                    statusClass = 'badge-danger';
                    statusText = 'High';
                } else if (utilization >= 70) {
                    statusClass = 'badge-warning';
                    statusText = 'Medium';
                } else {
                    statusClass = 'badge-good';
                    statusText = 'Good';
                }
                
                tbody.innerHTML += `
                    <tr class="fade-in">
                        <td class="flex items-center gap-3">
                            <i class="fas fa-building text-purple-500"></i>
                            <span>${row.department || ''}</span>
                        </td>
                        <td class="font-semibold">₱${formatMoney(allocated)}</td>
                        <td class="font-semibold">₱${formatMoney(allocated)}</td>
                        <td class="font-semibold text-red-600">₱${formatMoney(spent)}</td>
                        <td class="font-semibold text-green-600">₱${formatMoney(available)}</td>
                        <td class="font-semibold text-blue-600">₱${formatMoney(row.coa_balance || 0)}</td>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="progress-bar-container">
                                    <div class="progress-fill" style="width: ${Math.min(utilization, 100)}%"></div>
                                </div>
                                <span class="text-sm font-medium">${utilization.toFixed(1)}%</span>
                                <span class="theme-badge ${statusClass}">${statusText}</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-details" 
                                        onclick="viewDepartmentDetails('${escapeHtmlForJs(row.department || '')}', '${row.dept_key}')">
                                    <i class="fas fa-chart-bar mr-1"></i> Details
                                </button>
                                <button class="btn-archive" 
                                        onclick="archiveBudget(${row.id})">
                                    <i class="fas fa-archive mr-1"></i> Archive
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            // Add totals row
            if (totalAllocated > 0) {
                tbody.innerHTML += `
                    <tr class="bg-gray-50 font-bold">
                        <td class="text-right py-4 pr-4 uppercase tracking-wider text-gray-500">Total Dashboard Summary</td>
                        <td class="text-blue-700 py-4">₱${formatMoney(totalAllocated)}</td>
                        <td class="text-red-700 py-4">₱${formatMoney(totalSpent)}</td>
                        <td class="text-green-700 py-4">₱${formatMoney(totalAvailable)}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                `;
            }
        }
    }

    // Update summary cards
    function updateSummaryCards(data) {
        const totalAnnual = data.totalAllocated || 0;
        const totalCommitted = data.totalCommitted || 0;
        const totalSpent = data.totalSpent || 0;
        const totalAvailable = data.totalAvailable || 0;
        const totalUsed = totalSpent;
        const totalUtilization = totalAnnual > 0 ? (totalUsed / totalAnnual) * 100 : 0;
        
        // Update card values
        document.getElementById('totalAnnualSummary').textContent = `₱${formatMoney(totalAnnual)}`;
        document.getElementById('totalSpentSummary').textContent = `₱${formatMoney(totalSpent)}`;
        document.getElementById('totalAvailableSummary').textContent = `₱${formatMoney(totalAvailable)}`;
        
        // Update percentage changes
        document.getElementById('totalAnnualChange').textContent = `${totalUtilization.toFixed(1)}% utilized`;
        document.getElementById('totalSpentChange').textContent = totalAnnual > 0 ? `${((totalSpent/totalAnnual)*100).toFixed(1)}% of budget` : '0%';
        document.getElementById('totalAvailableChange').textContent = totalAnnual > 0 ? `${((totalAvailable/totalAnnual)*100).toFixed(1)}% remaining` : '100%';
    }

    // Load table data via AJAX
    function loadTable() {
        const url = new URL(window.location);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('department_filter', department_filter);
        url.searchParams.set('pg', pg);
        
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
                pg = data.page || 1;
                pages = data.pages || 1;
                renderRows(data);
                
                const startRecord = data.total > 0 ? data.offset + 1 : 0;
                const endRecord = Math.min(data.offset + data.records_per_page, data.total);
                document.getElementById("pageStatus").innerText = `Showing ${startRecord} to ${endRecord} of ${data.total} Records`;
                
                // Update pagination button states
                document.getElementById('prevPage').disabled = pg <= 1;
                document.getElementById('nextPage').disabled = pg >= pages;
                
                // Update button styles
                ['prevPage', 'nextPage'].forEach(btnId => {
                    const btn = document.getElementById(btnId);
                    if (btn.disabled) {
                        btn.classList.add('opacity-50', 'cursor-not-allowed');
                    } else {
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                    }
                });
            })
            .catch(error => {
                console.error('Error loading table data:', error);
                showToast('Failed to load data', 'error');
            });
    }

    // Department filter
    document.getElementById('department_filter').addEventListener('change', function() {
        department_filter = this.value;
        pg = 1;
        loadTable();
        updateState();
    });

    // Search filter function
    function filterBudgetTable() {
        const searchInput = document.getElementById('searchInput');
        const searchValue = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const tableBody = document.getElementById('budgetTableBody');
        const rows = tableBody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            
            // Skip the totals row
            if (row.classList.contains('bg-gray-50') && row.classList.contains('font-bold')) {
                continue;
            }

            const cells = row.getElementsByTagName('td');
            if (cells.length === 0) continue;

            // Get department name (first cell)
            const department = cells[0] ? cells[0].textContent.toLowerCase() : '';
            
            // Check if search matches department
            const matchesSearch = department.includes(searchValue);

            // Show/hide row based on search
            if (matchesSearch || searchValue === '') {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    }

    // Pagination
    document.getElementById('prevPage').addEventListener('click', function() {
        if (pg > 1) { 
            pg--; 
            loadTable();
            updateState();
        }
    });

    document.getElementById('nextPage').addEventListener('click', function() {
        if (pg < pages) { 
            pg++; 
            loadTable();
            updateState();
        }
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
            if (modalId === 'addModal') {
                document.getElementById('addForm').reset();
            }
            if (modalId === 'deptDetailsModal') {
                // Safely destroy charts
                if (deptChart !== null && deptChart !== undefined) {
                    try {
                        deptChart.destroy();
                    } catch(e) {
                        console.log('Error destroying deptChart:', e);
                    }
                    deptChart = null;
                }
                if (monthlyChart !== null && monthlyChart !== undefined) {
                    try {
                        monthlyChart.destroy();
                    } catch(e) {
                        console.log('Error destroying monthlyChart:', e);
                    }
                    monthlyChart = null;
                }
                if (categoryChart !== null && categoryChart !== undefined) {
                    try {
                        categoryChart.destroy();
                    } catch(e) {
                        console.log('Error destroying categoryChart:', e);
                    }
                    categoryChart = null;
                }
            }
        }
    }

    // Archive Budget function
    function archiveBudget(id) {
        if (confirm("Are you sure you want to archive this budget allocation?")) {
            const url = new URL(window.location);
            url.searchParams.set('archive', id);
            url.searchParams.set('ajax', '1');
            
            fetch(url)
                .then(resp => {
                    if (!resp.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return resp.json();
                })
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadTable();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Network error occurred', 'error');
                });
        }
    }

    // View Department Details
    function viewDepartmentDetails(department, deptKey) {
        if (!deptKey) return;
        currentDepartment = department;
        currentDeptKey = deptKey;
        console.log('Opening details for:', department, 'with key:', deptKey);
        
        // Set basic department info immediately
        document.getElementById('detailDeptName').textContent = department;
        document.getElementById('detailFiscalYear').textContent = getFiscalYear();
        
        // Show loading state
        document.getElementById('categoryTableBody').innerHTML = '<tr><td colspan="6" class="text-center py-8">Loading department data...</td></tr>';
        document.getElementById('detailTotalBudget').textContent = '₱0';
        document.getElementById('detailTotalSpent').textContent = '₱0';
        document.getElementById('detailTotalAvailable').textContent = '₱0';
        document.getElementById('detailUtilization').textContent = '0%';
        
        // Open modal FIRST
        openModal('deptDetailsModal');
        
        // Then fetch data
        fetchDepartmentData(deptKey);
    }

    // Fetch department data
    function fetchDepartmentData(deptKey, period = 'monthly') {
        const url = new URL(window.location.href);
        url.searchParams.set('action', 'get_dept_details');
        url.searchParams.set('dept_key', deptKey);
        url.searchParams.set('period', period);
        
        console.log('Fetching from:', url.toString());
        
        fetch(url.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                if (data.success && data.data) {
                    displayDepartmentData(data.data);
                } else {
                    console.error('No data received:', data);
                    showToast(data.message || 'No data found for this department', 'error');
                    displayEmptyDepartmentData(currentDepartment);
                }
            })
            .catch(error => {
                console.error('Error fetching department data:', error);
                showToast('Error loading department details: ' + error.message, 'error');
                displayEmptyDepartmentData(currentDepartment);
            });
    }

    let currentDeptData = null; // Global store for details
    
    // Display department data
    function displayDepartmentData(deptData) {
        currentDeptData = deptData; // Store for other tabs
        const summary = deptData.summary || {};
        const breakdown = deptData.breakdown || [];
        
        // Update summary info
        document.getElementById('detailTotalBudget').textContent = `₱${formatMoney(summary.total_budget || 0)}`;
        document.getElementById('detailTotalSpent').textContent = `₱${formatMoney(summary.total_spent || 0)}`;
        document.getElementById('detailTotalAvailable').textContent = `₱${formatMoney(summary.total_available || 0)}`;
        document.getElementById('detailUtilization').textContent = `${(summary.utilization || 0).toFixed(1)}%`;
        
        const spentPercent = summary.total_budget > 0 ? ((summary.total_spent / summary.total_budget) * 100).toFixed(1) : 0;
        const availablePercent = summary.total_budget > 0 ? ((summary.total_available / summary.total_budget) * 100).toFixed(1) : 0;
        
        document.getElementById('detailSpentPercent').textContent = `${spentPercent}% of budget`;
        document.getElementById('detailAvailablePercent').textContent = `${availablePercent}% remaining`;
        
        // Set utilization status
        let utilizationStatus = 'Within target';
        const utilization = summary.utilization || 0;
        if (utilization >= 90) {
            utilizationStatus = 'High usage';
        } else if (utilization >= 70) {
            utilizationStatus = 'Monitor closely';
        } else if (utilization <= 30) {
            utilizationStatus = 'Underutilized';
        }
        document.getElementById('detailUtilizationStatus').textContent = utilizationStatus;
        
        // Display breakdown
        displayBreakdownTable(breakdown);
        
        // Display Transactions
        displayTransactions(deptData.transactions || [], deptData.transaction_stats || {});
        
        // Display alerts
        displayAlerts(deptData.alerts || []);
        
        // Generate Insights
        generateKeyInsights(deptData);
        
        // Update timestamp
        const now = new Date();
        document.getElementById('lastUpdated').textContent = `Last Updated: ${now.toLocaleString()}`;
        document.getElementById('breakdownUpdateTime').textContent = `Updated: ${now.toLocaleDateString()}`;
    }

    // Display empty department data
    function displayEmptyDepartmentData(department) {
        document.getElementById('categoryTableBody').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500">No budget data available for this department</td></tr>';
        
        // Create empty pie chart
        createEmptyPieChart();
        
        // Reset transactions and alerts
        displayTransactions([], {});
        displayAlerts([]);
        
        // Clear insights
        const insightContainer = document.getElementById('keyInsightsList');
        if (insightContainer) insightContainer.innerHTML = '<li class="insight-item"><span>No data available to generate insights</span></li>';
        
        const now = new Date();
        document.getElementById('lastUpdated').textContent = `Last Updated: ${now.toLocaleString()}`;
    }

    // Display breakdown table
    function displayBreakdownTable(breakdown) {
        const tbody = document.getElementById('categoryTableBody');
        tbody.innerHTML = '';
        
        if (breakdown.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500">No categories found</td></tr>';
            createEmptyPieChart();
            return;
        }
        
        breakdown.forEach(cat => {
            const usagePercent = cat.utilization || 0;
            let status = 'good';
            let statusText = 'Healthy';
            let usageColor = '#10b981';
            
            if (usagePercent >= 90) {
                status = 'danger';
                statusText = 'Critical';
                usageColor = '#ef4444';
            } else if (usagePercent >= 80) {
                status = 'warning';
                statusText = 'Near Limit';
                usageColor = '#f59e0b';
            } else if (usagePercent >= 70) {
                status = 'warning';
                statusText = 'Monitor';
                usageColor = '#f59e0b';
            } else if (usagePercent <= 30) {
                status = 'success';
                statusText = 'Underused';
                usageColor = '#3b82f6';
            }
            
            // Get icon based on category
            const icon = getCategoryIcon(cat.category);
            
            tbody.innerHTML += `
                <tr class="category-row">
                    <td class="category-name">
                        <i class="fas ${icon}"></i>
                        ${cat.category}
                    </td>
                    <td class="category-budget font-bold">₱${formatMoney(cat.annual_budget || 0)}</td>
                    <td class="category-spent text-red-600 font-semibold">₱${formatMoney(cat.spent || 0)}</td>
                    <td class="category-available text-green-600 font-semibold">₱${formatMoney(cat.available || 0)}</td>
                    <td class="category-usage">
                        <div class="usage-bar">
                            <div class="usage-fill" style="width: ${Math.min(usagePercent, 100)}%; background: ${usageColor};"></div>
                        </div>
                        <span class="usage-percent">${usagePercent.toFixed(1)}%</span>
                    </td>
                    <td class="category-status">
                        <span class="status-badge ${status}">${statusText}</span>
                    </td>
                    <td class="text-center">
                        <button class="bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded-lg transition-all text-xs font-bold flex items-center gap-1 mx-auto" 
                                onclick="adjustDepartmentBudget(${cat.id})"
                                title="Adjust Budget for this Account">
                            <i class="fas fa-edit"></i> Adjust
                        </button>
                    </td>
                </tr>
            `;
        });
        
        // Create pie chart
        createPieChart(breakdown);
    }

    // Create pie chart
    function createPieChart(breakdown) {
        const ctx = document.getElementById('budgetPieChart');
        if (!ctx) return;
        
        // Destroy existing chart if it exists
        if (deptChart !== null && deptChart !== undefined) {
            try {
                deptChart.destroy();
            } catch(e) {
                console.log('Error destroying previous chart:', e);
            }
        }
        
        const labels = breakdown.map(cat => cat.category);
        const data = breakdown.map(cat => cat.annual_budget || 0);
        
        // Vibrant, multi-color palette for categories
        const palette = [
            '#9b59b6', '#3498db', '#2ecc71', '#e67e22', '#34495e',
            '#f1c40f', '#e74c3c', '#1abc9c', '#d35400', '#2980b9',
            '#27ae60', '#8e44ad', '#16a085', '#bdc3c7', '#7f8c8d'
        ];
        
        const backgroundColors = labels.map((_, i) => palette[i % palette.length]);
        
        try {
            deptChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating pie chart:', error);
            createEmptyPieChart();
        }
    }

    // Create empty pie chart
    function createEmptyPieChart() {
        const ctx = document.getElementById('budgetPieChart');
        if (!ctx) return;
        
        if (deptChart !== null && deptChart !== undefined) {
            try {
                deptChart.destroy();
            } catch(e) {
                console.log('Error destroying previous chart:', e);
            }
        }
        
        try {
            deptChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        data: [100],
                        backgroundColor: ['#e5e7eb']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating empty pie chart:', error);
        }
    }

    // Display transactions
    function displayTransactions(transactions, stats) {
        const container = document.getElementById('transactionsList');
        if (!container) return;
        
        // Update stats - Matching HTML IDs: totalApproved, totalPending, avgRequest
        if (stats) {
            const approvedEl = document.getElementById('totalApproved');
            const pendingEl = document.getElementById('totalPending');
            const avgEl = document.getElementById('avgRequest');
            
            if (approvedEl) approvedEl.textContent = `₱${formatMoney(stats.total_approved_month || 0)}`;
            if (pendingEl) pendingEl.textContent = stats.pending_approvals || 0;
            if (avgEl) avgEl.textContent = `₱${formatMoney(stats.avg_request_size || 0)}`;
        }

        container.innerHTML = '';
        
        if (transactions.length === 0) {
            container.innerHTML = '<div class="py-12 text-center text-gray-500">No recent transactions found for this department</div>';
            return;
        }
        
        transactions.forEach(trans => {
            const statusClass = trans.status === 'posted' ? 'approved' : 
                               (trans.status === 'draft' || trans.status === 'pending') ? 'pending' : 'rejected';
            
            container.innerHTML += `
                <div class="transaction-item">
                    <div class="transaction-main">
                        <div class="transaction-id">
                            <strong>${trans.id}</strong>
                            <span class="transaction-date">${trans.date}</span>
                        </div>
                        <div class="transaction-desc">
                            ${trans.description}
                        </div>
                        <div class="transaction-category">
                            <span class="cat-badge">${trans.category}</span>
                        </div>
                    </div>
                    <div class="transaction-side">
                        <div class="transaction-amount">₱${formatMoney(trans.amount)}</div>
                        <div class="transaction-status">
                            <span class="status ${statusClass}">${trans.status.charAt(0).toUpperCase() + trans.status.slice(1)}</span>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    // Display alerts
    function displayAlerts(alerts) {
        const container = document.getElementById('alertsContainer');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (alerts.length === 0) {
            container.innerHTML = `
                <div class="bg-green-50 border border-green-100 rounded-2xl p-6 text-center">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <h4 class="font-bold text-green-800">No Budget Alerts</h4>
                    <p class="text-sm text-green-600">All categories for this department are within healthy budget limits.</p>
                </div>
            `;
            return;
        }
        
        alerts.forEach(alert => {
            const isCritical = alert.type === 'critical';
            container.innerHTML += `
                <div class="p-4 rounded-xl border ${isCritical ? 'bg-red-50 border-red-100' : 'bg-amber-50 border-amber-100'} mb-3 flex items-start gap-4">
                    <div class="w-10 h-10 rounded-lg ${isCritical ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'} flex items-center justify-center shrink-0">
                        <i class="fas ${isCritical ? 'fa-exclamation-circle' : 'fa-exclamation-triangle'}"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold ${isCritical ? 'text-red-800' : 'text-amber-800'}">${alert.message}</p>
                        <p class="text-xs ${isCritical ? 'text-red-600' : 'text-amber-600'} mt-0.5">Automated Budget Guard Alert</p>
                    </div>
                </div>
            `;
        });
    }

    // Tab switching
    function switchDetailTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content-container').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.detail-tabs .tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // Activate clicked tab button
        const tabButtons = document.querySelectorAll('.detail-tabs .tab-btn');
        tabButtons.forEach(btn => {
            if (btn.getAttribute('data-tab') === tabName) {
                btn.classList.add('active');
            }
        });
        
        // Scroll modal content to top when switching tabs to prevent jumpiness
        const modalContent = document.querySelector('#deptDetailsModal .modal-content');
        if (modalContent) {
            modalContent.scrollTop = 0;
        }
        
        // Load data for trends tab
        if (tabName === 'trendsTab') {
            setTimeout(loadTrendsCharts, 100);
        }
    }

    // Load trends charts
    function loadTrendsCharts() {
        const monthlyCanvas = document.getElementById('monthlyTrendChart');
        const categoryCanvas = document.getElementById('categoryTrendChart');
        
        if (!currentDeptData || !currentDeptData.breakdown) return;

        if (monthlyChart !== null && monthlyChart !== undefined) {
            try { monthlyChart.destroy(); } catch(e) {}
        }
        
        if (categoryChart !== null && categoryChart !== undefined) {
            try { categoryChart.destroy(); } catch(e) {}
        }
        
        // Use real Trends from backend
        const trends = currentDeptData.trends || { labels: ['No Data'], data: [0] };
        const monthlyData = {
            labels: trends.labels.length > 0 ? trends.labels : ['No Data'],
            datasets: [{
                label: 'Actual Spending',
                data: trends.data.length > 0 ? trends.data : [0],
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.1)',
                fill: true,
                tension: 0.4
            }]
        };
        
        if (monthlyCanvas) {
            monthlyChart = new Chart(monthlyCanvas.getContext('2d'), {
                type: 'line',
                data: monthlyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        }
        
        // Category Comparison (Budget vs Spent)
        const categories = currentDeptData.breakdown.slice(0, 5); // Top 5
        if (categoryCanvas) {
            categoryChart = new Chart(categoryCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: categories.map(c => c.category),
                    datasets: [
                        {
                            label: 'Budget',
                            data: categories.map(c => c.annual_budget),
                            backgroundColor: '#3b82f6'
                        },
                        {
                            label: 'Spent',
                            data: categories.map(c => c.spent),
                            backgroundColor: '#ef4444'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
    }

    // Trend period change
    function changeTrendPeriod(period) {
        console.log('Changed trend period to:', period);
        
        // Update UI active state
        document.querySelectorAll('.time-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.toLowerCase() === period) {
                btn.classList.add('active');
            }
        });

        // Re-fetch data with period
        fetchDepartmentData(currentDeptKey, period);
    }

    // Toggle comparison
    function toggleComparison() {
        console.log('Comparison toggled');
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

    // Other utility functions
    function printDepartmentReport() {
        window.print();
    }

    function downloadCSV(data, filename) {
        if (data.length === 0) return;
        
        const headers = Object.keys(data[0]);
        const csvRows = [];
        csvRows.push(headers.join(','));
        
        for (const row of data) {
            const values = headers.map(header => {
                const escaped = ('' + row[header]).replace(/"/g, '\\"');
                return `"${escaped}"`;
            });
            csvRows.push(values.join(','));
        }
        
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', filename + '.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    let activeExportFormat = 'pdf';

    function toggleExportMenu(e) {
        e.stopPropagation();
        const me = document.getElementById('exportMenu');
        me.classList.toggle('hidden');
    }

    function toggleDeptExportMenu(e) {
        e.stopPropagation();
        const me = document.getElementById('deptExportMenu');
        me.classList.toggle('hidden');
    }

    function openExportScopeModal(format) {
        activeExportFormat = format;
        document.getElementById('exportMenu').classList.add('hidden');
        document.getElementById('exportScopeBackdrop').classList.add('show');
        document.getElementById('exportScopeModal').classList.remove('hidden');
        document.getElementById('exportScopeModal').classList.add('show');
    }

    function closeExportScopeModal() {
        document.getElementById('exportScopeBackdrop').classList.remove('show');
        document.getElementById('exportScopeModal').classList.add('hidden');
        document.getElementById('exportScopeModal').classList.remove('show');
    }

    async function executeExport(scope) {
        closeExportScopeModal();
        showToast(`Preparing ${activeExportFormat.toUpperCase()} report...`, 'info');
        
        try {
            let dataToExport = [];
            if (scope === 'filtered') {
                const resp = await fetch(`budget_allocation.php?ajax=1&action=export_all&department_filter=${encodeURIComponent(department_filter)}`);
                const data = await resp.json();
                dataToExport = data.data;
            } else {
                const resp = await fetch(`budget_allocation.php?ajax=1&action=export_all`);
                const data = await resp.json();
                dataToExport = data.data;
            }

            if (dataToExport.length === 0) {
                showToast('No data found to export', 'error');
                return;
            }

            if (activeExportFormat === 'pdf') {
                generateBudgetPDF(dataToExport, scope);
            } else if (activeExportFormat === 'excel') {
                generateBudgetExcel(dataToExport, scope);
            } else {
                exportDataCSV(); // Fallback to existing CSV
            }
        } catch (e) {
            console.error(e);
            showToast('Export failed', 'error');
        }
    }

    function generateBudgetPDF(data, scope) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4'); // Landscape
        const pageWidth = doc.internal.pageSize.getWidth();
        
        doc.setFont("helvetica", "bold");
        doc.setFontSize(20);
        doc.text("BUDGET ALLOCATION SUMMARY", pageWidth/2, 50, { align: 'center' });
        
        doc.setFontSize(10);
        doc.setFont("helvetica", "normal");
        doc.text(`Scope: ${scope === 'filtered' ? 'Filtered View' : 'Full Portfolio'}`, 40, 80);
        doc.text(`Generated: ${new Date().toLocaleString()}`, pageWidth - 40, 80, { align: 'right' });

        const tableData = data.map(r => [
            r.department,
            formatMoney(r.annual_budget),
            r.category || 'N/A',
            formatMoney(r.allocated_amount),
            formatMoney(r.spent),
            formatMoney(r.remaining_balance),
            ((parseFloat(r.spent) / parseFloat(r.allocated_amount)) * 100).toFixed(1) + '%'
        ]);

        doc.autoTable({
            startY: 100,
            head: [['Department', 'Annual Budget', 'Category', 'Allocated', 'Spent', 'Available', 'Util. %']],
            body: tableData,
            theme: 'grid',
            headStyles: { fillColor: [124, 58, 237], textColor: 255 },
            styles: { fontSize: 8 },
            margin: { left: 40, right: 40 }
        });

        doc.save(`Budget_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        showToast('PDF Export successful!');
    }

    function generateBudgetExcel(data, scope) {
        const wb = XLSX.utils.book_new();
        const wsData = [
            ["BUDGET ALLOCATION SUMMARY"],
            [`Scope: ${scope === 'filtered' ? 'Filtered View' : 'Full Portfolio'}`],
            [`Generated: ${new Date().toLocaleString()}`],
            [],
            ["Department", "Annual Budget", "Category", "Allocated", "Spent", "Available", "Utilization %"]
        ];

        data.forEach(r => {
            wsData.push([
                r.department,
                parseFloat(r.annual_budget),
                r.category,
                parseFloat(r.allocated_amount),
                parseFloat(r.spent),
                parseFloat(r.remaining_balance),
                ((parseFloat(r.spent) / parseFloat(r.allocated_amount)) * 100).toFixed(1) + '%'
            ]);
        });

        const ws = XLSX.utils.aoa_to_sheet(wsData);
        XLSX.utils.book_append_sheet(wb, ws, "Budget Summary");
        XLSX.writeFile(wb, `Budget_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        showToast('Excel Export successful!');
    }

    function exportDataCSV() {
        const url = `budget_allocation.php?ajax=1&action=export_all&department_filter=${encodeURIComponent(department_filter)}`;
        fetch(url)
            .then(resp => resp.json())
            .then(resp => {
                if (resp.success) {
                    downloadCSV(resp.data, 'All_Budget_Allocations');
                    showToast('CSV Export successful!', 'success');
                }
            });
    }

    // --- Department Specific Exports ---
    async function exportDepartmentPDF() {
        if (!currentDeptKey) return;
        document.getElementById('deptExportMenu').classList.add('hidden');
        showToast('Generating Department PDF...', 'info');
        
        try {
            const resp = await fetch(`budget_allocation.php?ajax=1&action=get_dept_details&dept_key=${currentDeptKey}`);
            const data = await resp.json();
            
            if (!data.success) throw new Error('Failed to fetch data');

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            
            doc.setFont("helvetica", "bold");
            doc.setFontSize(18);
            doc.text(`DEPARTMENT BUDGET REPORT: ${currentDepartment.toUpperCase()}`, pageWidth/2, 60, { align: 'center' });
            
            // Summary Info
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Report Date: ${new Date().toLocaleDateString()}`, 40, 90);
            
            const summary = data.data.summary;
            const summaryData = [
                ['Total Budget', '₱' + formatMoney(summary.total_budget)],
                ['Total Committed', '₱' + formatMoney(summary.total_committed)],
                ['Total Spent', '₱' + formatMoney(summary.total_spent)],
                ['Total Available', '₱' + formatMoney(summary.total_available)],
                ['Overall Utilization', summary.utilization.toFixed(1) + '%']
            ];

            doc.autoTable({
                startY: 110,
                body: summaryData,
                theme: 'plain',
                styles: { fontSize: 10, cellPadding: 5 },
                columnStyles: { 0: { fontStyle: 'bold', width: 150 } }
            });

            // Breakdown Table
            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.setTextColor(0);
            doc.text("Budget Breakdown by Account", 40, doc.lastAutoTable.finalY + 40);

            const breakdownData = data.data.breakdown.map(b => [
                b.category,
                formatMoney(b.annual_budget),
                formatMoney(b.spent),
                formatMoney(b.available),
                b.utilization + '%',
                b.status
            ]);

            doc.autoTable({
                startY: doc.lastAutoTable.finalY + 60,
                head: [['Account/Category', 'Budget', 'Spent', 'Available', 'Util.', 'Status']],
                body: breakdownData,
                theme: 'grid',
                headStyles: { fillColor: [124, 58, 237] },
                styles: { fontSize: 8 }
            });

            doc.save(`Budget_Report_${currentDepartment.replace(/\s+/g, '_')}.pdf`);
            showToast('PDF Export successful!');
        } catch (e) {
            console.error(e);
            showToast('PDF Export failed', 'error');
        }
    }

    async function exportDepartmentExcel() {
        if (!currentDeptKey) return;
        document.getElementById('deptExportMenu').classList.add('hidden');
        showToast('Generating Department Excel...', 'info');
        
        try {
            const resp = await fetch(`budget_allocation.php?ajax=1&action=get_dept_details&dept_key=${currentDeptKey}`);
            const data = await resp.json();
            
            if (!data.success) throw new Error('Failed to fetch data');

            const wb = XLSX.utils.book_new();
            
            // Summary Sheet
            const summary = data.data.summary;
            const wsSummaryData = [
                ["DEPARTMENT BUDGET SUMMARY"],
                ["Department", currentDepartment],
                ["Report Date", new Date().toLocaleDateString()],
                [],
                ["Metric", "Amount"],
                ["Total Budget", summary.total_budget],
                ["Total Committed", summary.total_committed],
                ["Total Spent", summary.total_spent],
                ["Total Available", summary.total_available],
                ["Utilization %", summary.utilization]
            ];
            const wsSummary = XLSX.utils.aoa_to_sheet(wsSummaryData);
            XLSX.utils.book_append_sheet(wb, wsSummary, "Summary");

            // Breakdown Sheet
            const wsBreakdownData = [
                ["Account/Category", "Budget", "Committed", "Spent", "Available", "Utilization %", "Status"]
            ];
            data.data.breakdown.forEach(b => {
                wsBreakdownData.push([
                    b.category,
                    b.annual_budget,
                    b.committed,
                    b.spent,
                    b.available,
                    b.utilization,
                    b.status
                ]);
            });
            const wsBreakdown = XLSX.utils.aoa_to_sheet(wsBreakdownData);
            XLSX.utils.book_append_sheet(wb, wsBreakdown, "Breakdown");

            XLSX.writeFile(wb, `Budget_Report_${currentDepartment.replace(/\s+/g, '_')}.xlsx`);
            showToast('Excel Export successful!');
        } catch (e) {
            console.error(e);
            showToast('Excel Export failed', 'error');
        }
    }

    function exportDepartmentData() {
        if (!currentDeptKey) return;
        document.getElementById('deptExportMenu').classList.add('hidden');
        
        fetch(`budget_allocation.php?ajax=1&action=get_dept_details&dept_key=${currentDeptKey}`)
            .then(resp => resp.json())
            .then(resp => {
                if (resp.success && resp.data.breakdown) {
                    downloadCSV(resp.data.breakdown, `Budget_Report_${currentDepartment.replace(/\s+/g, '_')}`);
                    showToast('CSV Export successful!', 'success');
                }
            });
    }

    // ADJUST BUDGET LOGIC
    var adjustCategoriesData = [];

    function adjustDepartmentBudget(accountId) {
        if (!currentDeptKey || !accountId) return;
        
        // Find account data from currently loaded department breakdown
        const accountData = currentDeptData.breakdown.find(a => a.id == accountId);
        if (accountData) {
            loadCategoryForAdjust(accountData);
            openModal('adjustBudgetModal');
        } else {
            // Fallback for fresh fetch if not in breakdown
            fetch(`budget_allocation.php?ajax=1&action=get_all_categories&dept_key=${currentDeptKey}`)
                .then(resp => resp.json())
                .then(resp => {
                    if (resp.success) {
                        const directData = resp.data.find(a => a.id == accountId);
                        if (directData) {
                            loadCategoryForAdjust(directData);
                            openModal('adjustBudgetModal');
                        }
                    }
                });
        }
    }

    function loadCategoryForAdjust(cat) {
        const fields = document.getElementById('adjustFields');
        if (!cat) return;
        
        document.getElementById('adjust_account_id').value = cat.id;
        document.getElementById('adjust_account_name_display').textContent = cat.category || 'Unknown Account';
        document.getElementById('adjust_allocated_amount').value = cat.annual_budget || cat.allocated_amount;
        document.getElementById('adjust_spent_display').textContent = '₱' + formatMoney(cat.spent || 0);
        
        fields.classList.remove('hidden');
    }

    // Generate Key Insights
    function generateKeyInsights(deptData) {
        const container = document.getElementById('keyInsightsList');
        if (!container) return;
        
        const breakdown = deptData.breakdown || [];
        const trends = deptData.trends || { labels: [], data: [] };
        const summary = deptData.summary || {};
        
        container.innerHTML = '';
        const insights = [];
        
        // 1. Spending Trend Insight
        if (trends.data.length >= 2) {
            const last = trends.data[trends.data.length - 1];
            const prev = trends.data[trends.data.length - 2];
            const diff = last - prev;
            const percent = prev > 0 ? (Math.abs(diff) / prev) * 100 : 0;
            
            if (diff > 0) {
                insights.push({
                    icon: 'fa-arrow-up',
                    color: 'text-red-500',
                    text: `Spending increased by ${percent.toFixed(1)}% compared to last month.`
                });
            } else if (diff < 0) {
                insights.push({
                    icon: 'fa-arrow-down',
                    color: 'text-green-500',
                    text: `Spending decreased by ${percent.toFixed(1)}% compared to last month.`
                });
            }
        }
        
        // 2. High Utilization Insight
        const highUsage = breakdown.filter(cat => cat.utilization >= 70).sort((a, b) => b.utilization - a.utilization);
        if (highUsage.length > 0) {
            insights.push({
                icon: 'fa-exclamation-triangle',
                color: 'text-amber-500',
                text: `${highUsage[0].category} has reached ${highUsage[0].utilization}% of its budget.`
            });
        }
        
        // 3. Runway Insight (Simple math: balance / avg month spend)
        const avgSpend = trends.data.length > 0 ? trends.data.reduce((a, b) => a + b, 0) / trends.data.length : 0;
        if (avgSpend > 0 && summary.total_available > 0) {
            const monthsLeft = summary.total_available / avgSpend;
            if (monthsLeft < 3) {
                insights.push({
                    icon: 'fa-clock',
                    color: 'text-purple-500',
                    text: `At current spending rate, budget will last for approximately ${monthsLeft.toFixed(1)} months.`
                });
            } else {
                insights.push({
                    icon: 'fa-check-circle',
                    color: 'text-blue-500',
                    text: `Current budget health is steady for the next ${Math.floor(monthsLeft)} months.`
                });
            }
        }
        
        // Fallback
        if (insights.length === 0) {
            insights.push({
                icon: 'fa-info-circle',
                color: 'text-gray-500',
                text: 'Maintain current budget controls to ensure fiscal stability.'
            });
        }
        
        insights.forEach(insight => {
            container.innerHTML += `
                <li class="insight-item">
                    <i class="fas ${insight.icon} ${insight.color}"></i>
                    <span>${insight.text}</span>
                </li>
            `;
        });
    }


    document.getElementById('adjustForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
        
        const formData = new FormData(this);
        
        fetch('budget_allocation.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('adjustBudgetModal');
                // Refresh both the main dashboard and the currently open details modal content
                loadTable();
                if (typeof currentDeptKey !== 'undefined' && currentDeptKey) {
                    fetchDepartmentData(currentDeptKey);
                }
            } else {
                showToast(data.message, 'error');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // ===== ALLOCATE BUDGET FUNCTIONS =====
    let fromAccountsData = [];
    let toAccountsData = []; // Store L4 accounts for reuse in rows
    let selectedFromBalance = 0;

    // Custom Searchable Select Implementation
    function initCustomSelect(container, onSelectCallback) {
        const originalSelect = container.querySelector('select');
        if (!originalSelect) return;

        // Create UI
        const trigger = document.createElement('div');
        trigger.className = 'custom-select-trigger';
        trigger.innerHTML = `<span class="placeholder">${originalSelect.options[0].text}</span><i class="fas fa-chevron-down text-xs text-gray-400"></i>`;
        
        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';
        
        const searchBox = document.createElement('div');
        searchBox.className = 'custom-select-search';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search accounts...';
        searchBox.appendChild(searchInput);
        
        const optionsList = document.createElement('div');
        optionsList.className = 'custom-select-options';
        
        dropdown.appendChild(searchBox);
        dropdown.appendChild(optionsList);
        container.appendChild(trigger);
        container.appendChild(dropdown);

        // Toggle dropdown
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.style.display === 'block';
            closeAllCustomSelects();
            if (!isOpen) dropdown.style.display = 'block';
            searchInput.focus();
        });

        // Search logic
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const options = optionsList.querySelectorAll('.custom-select-option');
            options.forEach(opt => {
                const text = opt.textContent.toLowerCase();
                opt.classList.toggle('hidden', !text.includes(term));
            });
        });

        // Click outside to close
        document.addEventListener('click', closeAllCustomSelects);

        // Function to rebuild options
        container.rebuildOptions = () => {
            optionsList.innerHTML = '';
            Array.from(originalSelect.options).forEach((opt, idx) => {
                if (!opt.value && idx === 0) return; // Skip placeholder
                const div = document.createElement('div');
                div.className = 'custom-select-option';
                if (opt.selected) div.classList.add('selected');
                div.textContent = opt.text;
                div.addEventListener('click', () => {
                    originalSelect.value = opt.value;
                    trigger.querySelector('span').textContent = opt.text;
                    trigger.querySelector('span').classList.remove('placeholder');
                    dropdown.style.display = 'none';
                    searchInput.value = '';
                    
                    // Update active state in UI
                    optionsList.querySelectorAll('.custom-select-option').forEach(o => o.classList.remove('selected'));
                    div.classList.add('selected');

                    // Trigger original change event
                    originalSelect.dispatchEvent(new Event('change'));
                    if (onSelectCallback) onSelectCallback(opt.value);
                });
                optionsList.appendChild(div);
            });
            
            // Sync current selection
            const selectedOpt = originalSelect.options[originalSelect.selectedIndex];
            if (selectedOpt && selectedOpt.value) {
                trigger.querySelector('span').textContent = selectedOpt.text;
                trigger.querySelector('span').classList.remove('placeholder');
            } else {
                trigger.querySelector('span').textContent = originalSelect.options[0].text;
                trigger.querySelector('span').classList.add('placeholder');
            }
        };

        container.rebuildOptions();
    }

    function closeAllCustomSelects() {
        document.querySelectorAll('.custom-select-dropdown').forEach(d => d.style.display = 'none');
    }

    // Helper for comma formatting
    function formatAmountInput(e) {
        let input = e.target;
        let value = input.value.replace(/[^0-9.]/g, '');
        let parts = value.split('.');
        
        if (parts.length > 2) parts = [parts[0], parts.slice(1).join('')];
        
        let formatted = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        if (parts.length === 2) formatted += "." + parts[1];
        
        input.value = formatted;
        calculatePercentage(input);
    }

    let activeInputRef = null;

    // Allocation Table Management
    let selectedGLAccounts = [];

    function handleAddGLAccount(id) {
        if (!id) return;
        
        const account = toAccountsData.find(a => a.id == id);
        if (!account) return;
        
        // Check if already added
        if (selectedGLAccounts.some(a => a.id == id)) {
            showToast('Account already added to the list', 'info');
            return;
        }
        
        selectedGLAccounts.push({
            ...account,
            alloc_amount: 0,
            parent_id: account.parent_id,
            parent_balance: parseFloat(account.parent_balance) || 0
        });
        
        renderAllocationTable();
        
        // RESET SELECTORS
        // 1. Reset GL Account Selection
        document.getElementById('gl_account_selector').value = '';
        const glContainer = document.getElementById('gl_account_selection_container');
        if (glContainer.rebuildOptions) glContainer.rebuildOptions();
        
        // 2. Reset Subcategory (from_account) Selector per user request
        const fromSelect = document.getElementById('from_account');
        fromSelect.value = '';
        const fromContainer = document.getElementById('from_account_container');
        if (fromContainer.rebuildOptions) fromContainer.rebuildOptions();
        updateFromBalance(); // Clears balance display
    }

    function renderAllocationTable() {
        const tbody = document.getElementById('allocationTableBody');
        tbody.innerHTML = '';
        
        selectedGLAccounts.forEach((acc, index) => {
            const row = document.createElement('tr');
            row.className = 'fade-in';
            const formattedVal = acc.alloc_amount ? acc.alloc_amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
            
            row.innerHTML = `
                <td class="px-4 py-3 text-xs font-bold text-blue-600">${acc.code}</td>
                <td class="px-4 py-3 text-xs font-semibold text-gray-700">${acc.name}</td>
                <td class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">${acc.category_name || '-'}</td>
                <td class="px-4 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">${acc.subcategory_name || '-'}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <span class="text-xs text-gray-400">₱</span>
                        <input type="text" name="to_amounts[]" value="${formattedVal}" 
                               class="allocation-amount-input w-32 border border-gray-200 rounded-lg px-3 py-2 text-right text-xs font-bold focus:ring-2 focus:ring-purple-500 outline-none transition-all"
                               placeholder="0.00">
                    </div>
                    <input type="hidden" name="to_accounts[]" value="${acc.id}">
                    <input type="hidden" name="from_accounts[]" value="${acc.parent_id}">
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" onclick="removeGLAccount(${index})" class="text-gray-300 hover:text-red-500 transition-colors">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </td>
            `;
            
            const input = row.querySelector('.allocation-amount-input');
            input.addEventListener('input', (e) => {
                formatAmountInput(e);
                selectedGLAccounts[index].alloc_amount = parseFloat(e.target.value.replace(/,/g, '')) || 0;
            });
            input.addEventListener('focus', () => {
                activeInputRef = input;
                calculatePercentage(input);
            });
            
            tbody.appendChild(row);
        });
        
        calculatePercentage();
    }

    function updateRowAmount(index, value) {
        selectedGLAccounts[index].alloc_amount = parseFloat(value) || 0;
        calculatePercentage();
    }

    function removeGLAccount(index) {
        selectedGLAccounts.splice(index, 1);
        renderAllocationTable();
    }

    // Pre-fill modal for adding account to existing category (from details modal)
    function addAccountToExistingCategory(deptName, categoryName, coaIdFrom) {
        selectedGLAccounts = [];
        renderAllocationTable();
        
        openModal('addModal');
        
        // Set allocation type to 'existing'
        const existingRadio = document.querySelector('input[name="allocation_type"][value="existing"]');
        if (existingRadio) existingRadio.checked = true;
        
        // Set department (one-off option)
        const deptSelect = document.getElementById('alloc_department');
        deptSelect.innerHTML = `<option value="${deptName}" selected>${deptName}</option>`;
        
        // Initialize main FROM search
        const fromContainer = document.getElementById('from_account_container');
        if (!fromContainer.rebuildOptions) {
            initCustomSelect(fromContainer);
        }
        
        // Fetch source accounts and select the specific one
        const fromSelect = document.getElementById('from_account');
        fromSelect.innerHTML = '<option value="">Loading...</option>';
        fromContainer.rebuildOptions();

        fetch('budget_allocation.php?action=get_level3_accounts&ajax=1')
            .then(resp => resp.json())
            .then(data => {
                fromSelect.innerHTML = '<option value="">Select a Subcategory</option>';
                if (data.success && data.accounts) {
                    fromAccountsData = data.accounts;
                    data.accounts.forEach(account => {
                        const option = document.createElement('option');
                        option.value = account.id;
                        option.textContent = `${account.code} - ${account.name} (Balance: ₱${formatMoney(account.balance)})`;
                        option.dataset.code = account.code;
                        option.dataset.name = account.name;
                        option.dataset.balance = account.balance;
                        if (account.id == coaIdFrom) option.selected = true;
                        fromSelect.appendChild(option);
                    });
                    fromContainer.rebuildOptions();
                    updateFromBalance();
                }
            });

        // Initialize GL selector search
        const glContainer = document.getElementById('gl_account_selection_container');
        if (!glContainer.rebuildOptions) {
            initCustomSelect(glContainer);
        }
    }

    // Open fresh Allocate Budget modal
    function openAllocateModal() {
        const modal = document.getElementById('addModal');
        if (modal) {
            // Default to 'New' allocation
            const newRadio = document.querySelector('input[name="allocation_type"][value="new"]');
            if (newRadio) newRadio.checked = true;
            
            selectedGLAccounts = [];
            renderAllocationTable();
            
            openModal('addModal');
            toggleAllocationType();
            
            // Initialize main FROM search after modal is visible
            const fromContainer = document.getElementById('from_account_container');
            if (!fromContainer.rebuildOptions) {
                initCustomSelect(fromContainer);
            }
            
            // Initialize GL selector search
            const glContainer = document.getElementById('gl_account_selection_container');
            if (!glContainer.rebuildOptions) {
                initCustomSelect(glContainer);
            }
        }
    }

    // Toggle Allocation Type (New vs Existing)
    function toggleAllocationType() {
        const type = document.querySelector('input[name="allocation_type"]:checked').value;
        const deptSelect = document.getElementById('alloc_department');
        
        // Save current selection to restore if possible
        const currentVal = deptSelect.value;
        
        // Reset dependent fields
        deptSelect.innerHTML = '<option value="">Select Department</option>';
        document.getElementById('from_account').innerHTML = '<option value="">Select Source Account (Level 3 - Subcategory)</option>';
        
        // Refresh custom select if initialized
        const fromContainer = document.getElementById('from_account_container');
        if (fromContainer.rebuildOptions) fromContainer.rebuildOptions();

        document.getElementById('from_account_info').classList.add('hidden');
        document.getElementById('allocation_percentage').classList.add('hidden');
        
        if (type === 'new') {
            staticDepartments.forEach(dept => {
                const opt = document.createElement('option');
                opt.value = dept;
                opt.textContent = dept;
                if (dept === currentVal) opt.selected = true;
                deptSelect.appendChild(opt);
            });
        } else {
            loadExistingDepartments(currentVal);
        }
    }

    // Load Existing Departments from Database
    function loadExistingDepartments(preselect = null) {
        const deptSelect = document.getElementById('alloc_department');
        deptSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch('budget_allocation.php?action=get_existing_departments&ajax=1')
            .then(resp => resp.json())
            .then(data => {
                deptSelect.innerHTML = '<option value="">Select Existing Department</option>';
                if (data.success && data.departments) {
                    data.departments.forEach(dept => {
                        const opt = document.createElement('option');
                        opt.value = dept;
                        opt.textContent = dept;
                        if (dept === preselect) opt.selected = true;
                        deptSelect.appendChild(opt);
                    });
                }
            });
    }

    // Load Level 3 subcategories when modal opens
    window.addEventListener('DOMContentLoaded', function() {
        const allocDeptSelect = document.getElementById('alloc_department');
        if (allocDeptSelect) {
            allocDeptSelect.addEventListener('change', loadFromAccounts);
        }
    });
    
    // Load FROM accounts
    function loadFromAccounts() {
        const fromSelect = document.getElementById('from_account');
        fromSelect.innerHTML = '<option value="">Loading accounts...</option>';
        
        fetch('budget_allocation.php?action=get_level3_accounts&ajax=1')
            .then(resp => resp.json())
            .then(data => {
                fromSelect.innerHTML = '<option value="">Select Source Account (Level 3 - Subcategory)</option>';
                if (data.success && data.accounts) {
                    fromAccountsData = data.accounts;
                    data.accounts.forEach(account => {
                        const option = document.createElement('option');
                        option.value = account.id;
                        option.textContent = `${account.code} - ${account.name} (Balance: ₱${formatMoney(account.balance)})`;
                        option.dataset.code = account.code;
                        option.dataset.name = account.name;
                        option.dataset.balance = account.balance;
                        fromSelect.appendChild(option);
                    });
                }
                const fromContainer = document.getElementById('from_account_container');
                if (fromContainer.rebuildOptions) fromContainer.rebuildOptions();
            });
    }
    
    // Update FROM balance and load TO accounts
    function updateFromBalance() {
        const fromSelect = document.getElementById('from_account');
        const fromInfo = document.getElementById('from_account_info');
        
        if (!fromSelect.value) {
            fromInfo.classList.add('hidden');
            document.getElementById('allocation_percentage').classList.add('hidden');
            return;
        }
        
        const selectedOption = fromSelect.options[fromSelect.selectedIndex];
        const glCode = selectedOption.dataset.code;
        const accountName = selectedOption.dataset.name;
        const balance = parseFloat(selectedOption.dataset.balance) || 0;
        
        selectedFromBalance = balance;
        document.getElementById('from_gl_code').textContent = glCode;
        document.getElementById('from_balance').textContent = `₱${formatMoney(balance)}`;
        fromInfo.classList.remove('hidden');
        document.getElementById('category_hidden').value = accountName;
        
        loadToAccounts(fromSelect.value);
        calculatePercentage();
    }
    
    // Load TO accounts for selector
    function loadToAccounts(parentId) {
        toAccountsData = [];
        const glSelector = document.getElementById('gl_account_selector');
        glSelector.innerHTML = '<option value="">Loading accounts...</option>';
        
        fetch(`budget_allocation.php?action=get_level4_accounts&parent_id=${parentId}&ajax=1`)
            .then(resp => resp.json())
            .then(data => {
                glSelector.innerHTML = '<option value="">Select a GL Account</option>';
                if (data.success && data.accounts) {
                    toAccountsData = data.accounts;
                    data.accounts.forEach(acc => {
                        const opt = document.createElement('option');
                        opt.value = acc.id;
                        opt.textContent = `${acc.code} - ${acc.name} (Bal: ₱${formatMoney(acc.balance)})`;
                        glSelector.appendChild(opt);
                    });
                }
                const container = document.getElementById('gl_account_selection_container');
                if (container.rebuildOptions) container.rebuildOptions();
            });
    }
    
    // Multi-Row Percentage Calculation & Overbudget Validation (Row-Specific/Pot-Based)
    function calculatePercentage(currentInput = null) {
        const amountInputs = document.querySelectorAll('input[name="to_amounts[]"]');
        const percentageDiv = document.getElementById('allocation_percentage');
        const percentageValue = document.getElementById('percentage_value');
        const percentageBar = document.getElementById('percentage_bar');
        const percentageDisplay = document.getElementById('percentage_display');
        const allocationWarning = document.getElementById('allocation_warning');
        const statusText = document.getElementById('allocation_status_text');
        const statusBadge = document.getElementById('allocation_status_badge');
        const modalTotalDisplay = document.getElementById('modal_total_display');
        const submitBtn = document.getElementById('submitAllocationBtn');
        const percentageFraction = document.getElementById('percentage_fraction');
        const allocatingAmountDisp = document.getElementById('allocating_amount');
        const remainingAmountDisp = document.getElementById('remaining_amount');
        const labelText = document.getElementById('percentage_label_text');
        
        let totalAllocatingGlobal = 0;
        let parentTotals = {}; // Tracks total allocation per parent account
        let globalHasError = false;

        // 1. First Pass: Calculate totals per parent/pot (Read directly from DOM for REAL-TIME accuracy)
        amountInputs.forEach((input, idx) => {
            const val = parseFloat(input.value.replace(/,/g, '')) || 0;
            const acc = selectedGLAccounts[idx];
            
            // Sync the internal state immediately
            acc.alloc_amount = val;
            
            totalAllocatingGlobal += val;
            if (!parentTotals[acc.parent_id]) parentTotals[acc.parent_id] = 0;
            parentTotals[acc.parent_id] += val;
        });
        
        // 2. Second Pass: Update individual borders & Check errors vs parent pots
        amountInputs.forEach((input, idx) => {
            const acc = selectedGLAccounts[idx];
            const potTotal = parentTotals[acc.parent_id];
            const isOverPot = potTotal > acc.parent_balance;
            
            const currentVal = parseFloat(input.value.replace(/,/g, '')) || 0;
            if (isOverPot && currentVal > 0) {
                // Highlight if this specific account is part of a pot that is over
                input.classList.add('border-red-500', 'ring-2', 'ring-red-100', 'bg-red-50');
                input.classList.remove('border-gray-200');
                globalHasError = true;
            } else {
                input.classList.remove('border-red-500', 'ring-2', 'ring-red-100', 'bg-red-50');
                input.classList.add('border-gray-200');
            }
        });
        
        modalTotalDisplay.textContent = `₱ ${formatMoney(totalAllocatingGlobal)}`;

        if (totalAllocatingGlobal > 0) {
            percentageDiv.classList.remove('hidden');
            allocationWarning.classList.toggle('hidden', !globalHasError);

            // ITEM-SPECIFIC UI SYNC (Based on focused item's own parent pot)
            const activeInput = currentInput || activeInputRef || amountInputs[0];
            const activeIdx = Array.from(amountInputs).indexOf(activeInput);
            
            if (activeIdx !== -1) {
                const item = selectedGLAccounts[activeIdx];
                labelText.textContent = `${item.name} Distribution`;
                
                const itemVal = item.alloc_amount || 0;
                const parentPotBalance = item.parent_balance;
                const itemPercent = (itemVal / parentPotBalance) * 100;
                
                // Progress Bar & circular UI reflect active item relative to its specific parent
                percentageValue.textContent = `${itemPercent.toFixed(1)}%`;
                percentageDisplay.textContent = `${itemPercent.toFixed(1)}%`;
                percentageBar.style.width = `${Math.min(itemPercent, 100)}%`;
                percentageBar.style.backgroundColor = (parentTotals[item.parent_id] > parentPotBalance) ? '#ef4444' : '#7c3aed';
                
                percentageFraction.textContent = `₱${formatMoney(itemVal)} / ₱${formatMoney(parentPotBalance)}`;
                allocatingAmountDisp.textContent = `₱${formatMoney(itemVal)}`;
                
                // ITEM-CENTRIC REMAINING: Show remaining balance for THIS item relative to the source balance
                // User requested: "dapat insync to sa percentage, progress, item na ginagalaw ko"
                const itemRemaining = Math.max(0, parentPotBalance - itemVal);
                remainingAmountDisp.textContent = `₱${formatMoney(itemRemaining)}`;
                
                // Status Badge logic
                if (parentTotals[item.parent_id] > parentPotBalance) {
                    statusText.textContent = 'Pot Overbudget';
                    statusBadge.className = 'inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-red-100 text-red-700 shadow-sm';
                    percentageValue.className = 'text-3xl font-black text-red-600';
                } else {
                    statusText.textContent = 'Item Healthy';
                    statusBadge.className = 'inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-green-100 text-green-700 shadow-sm';
                    percentageValue.className = 'text-3xl font-black text-purple-600';
                }
            }

            submitBtn.disabled = globalHasError;
            if (globalHasError) submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            else submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            percentageDiv.classList.add('hidden');
            submitBtn.disabled = totalAllocatingGlobal <= 0;
        }
    }

    // Add Form Submission
    document.getElementById('addForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';
        
        // Prepare data - clean commas
        const formData = new FormData(this);
        formData.append('action', 'add');
        
        // Clean values in FormData
        const toAmounts = formData.getAll('to_amounts[]');
        formData.delete('to_amounts[]');
        toAmounts.forEach(amt => {
            formData.append('to_amounts[]', amt.replace(/,/g, ''));
        });
        
        fetch('budget_allocation.php', {
            method: 'POST',
            body: formData
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('addModal');
                loadTable();
                this.reset();
            } else {
                showToast(data.message, 'error');
            }
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // State management
    function updateState() {
        const url = new URL(window.location);
        
        // Always preserve the 'page' parameter for sidebar highlight
        url.searchParams.set('page', 'budgetallocation');
        
        if (department_filter) {
            url.searchParams.set('department_filter', department_filter);
        } else {
            url.searchParams.delete('department_filter');
        }
        
        if (pg > 1) {
            url.searchParams.set('pg', pg);
        } else {
            url.searchParams.delete('pg');
        }
        
        window.history.replaceState({}, '', url);
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Close modals on overlay click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    const modalId = this.id;
                    closeModal(modalId);
                }
            });
        });

        // Close export menus on outside click
        document.addEventListener('click', function() {
            document.getElementById('exportMenu')?.classList.add('hidden');
            document.getElementById('deptExportMenu')?.classList.add('hidden');
        });
        
        // Initialize tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                if (tabName) {
                    switchDetailTab(tabName);
                }
            });
        });
    });
    </script>
    </div>
    </main>
</body>
</html>
<?php $conn->close(); ?>