<?php
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
include('connection.php');

// Account type mapping for Tabs
$account_type = isset($_GET['type']) ? $_GET['type'] : 'Asset';
$valid_types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
if (!in_array($account_type, $valid_types)) {
    $account_type = 'Asset';
}

// AJAX handler for Overview Stats
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_overview_stats'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
    $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $dateFrom = isset($_GET['dateFrom']) ? $conn->real_escape_string($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? $conn->real_escape_string($_GET['dateTo']) : '';
    $filterMode = isset($_GET['filterMode']) ? $_GET['filterMode'] : 'standard';

    $where = "WHERE 1=1";
    $endDate = null;
    if ($filterMode === 'standard') {
        if ($month > 0) $where .= " AND MONTH(transaction_date) = $month";
        if ($year > 0) {
            $where .= " AND YEAR(transaction_date) = $year";
            $endDate = ($month > 0) ? date('Y-m-t', strtotime("$year-$month-01")) : "$year-12-31";
        }
    } else {
        if ($dateFrom) $where .= " AND transaction_date >= '$dateFrom'";
        if ($dateTo) {
            $where .= " AND transaction_date <= '$dateTo'";
            $endDate = $dateTo;
        }
    }

    $whereCumulative = "WHERE 1=1";
    if ($endDate) $whereCumulative .= " AND transaction_date <= '$endDate'";

    // Cumulative stats for Balance Sheet items (Asset, Liability, Equity)
    $sqlCum = "SELECT account_type, SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit FROM general_ledger $whereCumulative GROUP BY account_type";
    $resultCum = $conn->query($sqlCum);
    $stats = [];
    while($row = $resultCum->fetch_assoc()) {
        $type = $row['account_type'];
        $is_debit = in_array($type, ['Asset', 'Expense']);
        $bal = $is_debit ? ($row['total_debit'] - $row['total_credit']) : ($row['total_credit'] - $row['total_debit']);
        $stats[$type] = $bal;
    }

    // Period stats for P&L items (Revenue, Expense) to calculate Net Income for the period
    $sqlPeriod = "SELECT account_type, SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit FROM general_ledger $where GROUP BY account_type";
    $resultPeriod = $conn->query($sqlPeriod);
    $periodStats = [];
    while($row = $resultPeriod->fetch_assoc()) {
        $type = $row['account_type'];
        $is_debit = in_array($type, ['Asset', 'Expense']);
        $periodBal = $is_debit ? ($row['total_debit'] - $row['total_credit']) : ($row['total_credit'] - $row['total_debit']);
        $periodStats[$type] = $periodBal;
    }

    $revenue = $periodStats['Revenue'] ?? 0;
    $expense = $periodStats['Expense'] ?? 0;
    $net_income = $revenue - $expense;

    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'net_income' => $net_income
    ]);
    exit();
}

// AJAX handler for Accounts by Type
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_accounts_by_type'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : 'Asset';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $rowsPerPage = 10;
    $offset = ($page - 1) * $rowsPerPage;
    
    $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
    $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $dateFrom = isset($_GET['dateFrom']) ? $conn->real_escape_string($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? $conn->real_escape_string($_GET['dateTo']) : '';
    $filterMode = isset($_GET['filterMode']) ? $_GET['filterMode'] : 'standard';

    $filterGL = "AND 1=1";
    $endDate = null;
    if ($filterMode === 'standard') {
        if ($month > 0) $filterGL .= " AND MONTH(transaction_date) = $month";
        if ($year > 0) {
            $filterGL .= " AND YEAR(transaction_date) = $year";
            $endDate = ($month > 0) ? date('Y-m-t', strtotime("$year-$month-01")) : "$year-12-31";
        }
    } else {
        if ($dateFrom) $filterGL .= " AND transaction_date >= '$dateFrom'";
        if ($dateTo) {
            $filterGL .= " AND transaction_date <= '$dateTo'";
            $endDate = $dateTo;
        }
    }

    $filterCumulative = "AND 1=1";
    if ($endDate) $filterCumulative .= " AND transaction_date <= '$endDate'";

    // Get total count first
    $count_sql = "SELECT COUNT(*) as total FROM chart_of_accounts_hierarchy WHERE type = '$type' AND status = 'active' AND level = 4";
    $count_res = $conn->query($count_sql);
    $total = $count_res->fetch_assoc()['total'];

    $sql = "SELECT c.*, 
            (SELECT COUNT(*) FROM general_ledger g WHERE g.gl_account_id = c.id $filterGL AND MONTH(g.transaction_date) = MONTH(CURRENT_DATE())) as month_txn_count,
            (SELECT SUM(CASE 
                WHEN c.type IN ('Asset', 'Expense') THEN (g.debit_amount - g.credit_amount)
                ELSE (g.credit_amount - g.debit_amount) 
            END) FROM general_ledger g WHERE g.gl_account_id = c.id $filterCumulative) as period_balance
            FROM chart_of_accounts_hierarchy c 
            WHERE c.type = '$type' AND c.status = 'active' AND c.level = 4
            LIMIT $offset, $rowsPerPage";
    
    $result = $conn->query($sql);
    $accounts = [];
    while($row = $result->fetch_assoc()) {
        $row['balance'] = $row['period_balance'] ?? 0;
        $accounts[] = $row;
    }

    echo json_encode(['success' => true, 'type' => $type, 'accounts' => $accounts, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $rowsPerPage)]);
    exit();
}

// AJAX handler for Ledger records
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_ledger'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : 'Asset';
    $accountId = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $month = isset($_GET['month']) ? intval($_GET['month']) : 0;
    $year = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $dateFrom = isset($_GET['dateFrom']) ? $conn->real_escape_string($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? $conn->real_escape_string($_GET['dateTo']) : '';
    $filterMode = isset($_GET['filterMode']) ? $_GET['filterMode'] : 'standard';

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $rowsPerPage = 10;
    $offset = ($page - 1) * $rowsPerPage;

    $where = "WHERE 1=1";
    if ($accountId > 0) {
        $where .= " AND gl_account_id = $accountId";
    } else if ($type) {
        $where .= " AND account_type = '$type'";
    }

    if ($filterMode === 'standard') {
        if ($month > 0) $where .= " AND MONTH(transaction_date) = $month";
        if ($year > 0) $where .= " AND YEAR(transaction_date) = $year";
    } else {
        if ($dateFrom) $where .= " AND transaction_date >= '$dateFrom'";
        if ($dateTo) $where .= " AND transaction_date <= '$dateTo'";
    }

    if ($search) {
        $where .= " AND (description LIKE '%$search%' OR reference_id LIKE '%$search%' OR gl_account_name LIKE '%$search%')";
    }
    
    $count_sql = "SELECT COUNT(*) as total FROM general_ledger $where";
    $result_count = $conn->query($count_sql);
    $total = $result_count->fetch_assoc()['total'];
    
    // Fetch records
    require_once 'includes/accounting_functions.php';
    if ($accountId > 0) {
        recalculateGLRunningBalances($conn, $accountId);
    }

    $limit_sql = isset($_GET['all']) ? "" : "LIMIT $offset, $rowsPerPage";
    $sql = "SELECT * FROM general_ledger $where ORDER BY transaction_date ASC, id ASC $limit_sql";
    $result = $conn->query($sql);
    $rows = [];
    while($row = $result->fetch_assoc()) $rows[] = $row;

    $acc_info = null;
    if ($accountId > 0) {
        $acc_sql = "SELECT name, code, balance, type FROM chart_of_accounts_hierarchy WHERE id = $accountId";
        $acc_res = $conn->query($acc_sql);
        $acc_info = $acc_res->fetch_assoc();
    }

    echo json_encode([
        'success' => true, 'rows' => $rows, 'total' => $total, 
        'page' => $page, 'pages' => ceil($total / $rowsPerPage), 'account' => $acc_info
    ]);
    exit();
}

// AJAX handler for Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_ledger') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        exit();
    }
    $file = $_FILES['csv']['tmp_name'];
    $handle = fopen($file, "r");
    fgetcsv($handle); 
    $imported = 0; $currentJE = null;
    $conn->begin_transaction();
    try {
        require_once 'includes/accounting_functions.php';
        $accountsToSync = []; 
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($data) < 6) continue;
            $date = $data[0]; $accountCode = $data[1]; $reference = $data[2]; $desc = $data[3];
            $debit = floatval($data[4]); $credit = floatval($data[5]); $dept = $data[6] ?? 'General';

            $jeKey = $date . $reference;
            if (!$currentJE || $currentJE['key'] !== $jeKey) {
                $journalNumber = generateJENumber($conn);
                $sqlJE = "INSERT INTO journal_entries (journal_number, transaction_date, reference_type, reference_id, description, status, created_by, posted_at) VALUES (?, ?, 'IMPORT', ?, ?, 'posted', 'System', NOW())";
                $stmtJE = $conn->prepare($sqlJE);
                $stmtJE->bind_param("ssss", $journalNumber, $date, $reference, $desc);
                $stmtJE->execute();
                $currentJE = ['key' => $jeKey, 'id' => $conn->insert_id, 'number' => $journalNumber];
                $imported++;
            }
            $acc_sql = "SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE code = ? LIMIT 1";
            $acc_stmt = $conn->prepare($acc_sql);
            $acc_stmt->bind_param("s", $accountCode);
            $acc_stmt->execute();
            $gl = $acc_stmt->get_result()->fetch_assoc();
            if ($gl) {
                $sqlGL = "INSERT INTO general_ledger (gl_account_id, gl_account_code, gl_account_name, account_type, transaction_date, journal_entry_id, reference_id, reference_type, original_reference, description, debit_amount, credit_amount, department, running_balance) VALUES (?, ?, ?, ?, ?, ?, ?, 'IMPORT', ?, ?, ?, ?, ?, ?)";
                
                // Note: Balance is recalculated after the full import for efficiency
                $dummy_bal = 0; 
                $stmtGL = $conn->prepare($sqlGL);
                $stmtGL->bind_param("issssisssddsd", $gl['id'], $gl['code'], $gl['name'], $gl['type'], $date, $currentJE['id'], $reference, $currentJE['number'], $desc, $debit, $credit, $dept, $dummy_bal);
                $stmtGL->execute();
                
                // Update the COA balance immediately
                updateGLAccountBalance($conn, $gl['id'], $debit > 0 ? $debit : $credit, $debit > 0 ? 'debit' : 'credit');
                
                // Store account ID to recalculate later
                $accountsToSync[$gl['id']] = true;
            }
        }
        
        // Final sync for all affected accounts
        foreach(array_keys($accountsToSync) as $acc_id) {
            recalculateGLRunningBalances($conn, $acc_id);
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Imported $imported entries."]);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    fclose($handle); exit();
}

$pageTitle = 'ledger';
$pageIcon = 'logo.png';
include('sidebar.php'); 
?>
<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; }
        :root {
            --primary: #7c3aed;
            --primary-light: #f5f3ff;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        .premium-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .glass-header { background: white; border-bottom: 1px solid #e2e8f0; }
        
        .stat-widget { border-radius: 12px; border: 1px solid #e2e8f0; background: white; padding: 20px; display: flex; align-items: center; justify-content: space-between; }
        .stat-icon { width: 48px; height: 48px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        
        .tab-btn { position: relative; padding: 12px 24px; font-weight: 700; color: #64748b; transition: all 0.2s; font-size: 0.875rem; }
        .tab-btn.active { color: var(--primary); }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -1px; left: 0; width: 100%; height: 2px; background: var(--primary); }
        
        .view-content { display: none; }
        .view-content.active { display: block; }

        .btn-premium { @apply px-5 py-2.5 rounded-xl font-bold transition-all active:scale-95 flex items-center gap-2 text-sm; }
        .btn-primary { @apply bg-violet-600 text-white hover:bg-violet-700 shadow-sm; }
        .btn-ghost { @apply bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 shadow-sm; }

        /* Centered Modal Fix */
        .modal-box { 
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            z-index: 100001; background: white; border-radius: 24px; 
            width: 90%; max-width: 500px; display: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .modal-backdrop { 
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); 
            backdrop-filter: blur(4px); z-index: 100000; display: none;
        }
        .modal-box.show, .modal-backdrop.show { display: block; }

        /* Typography improvements */
        h1, h2, h3 { color: #000 !important; }
        th { color: #000 !important; font-size: 0.875rem !important; font-weight: 800 !important; }
        td { color: #1e293b !important; font-size: 0.875rem !important; font-weight: 500 !important; }
        .text-gray-400, .text-gray-500 { color: #4b5563 !important; } /* Darken gray text */
        
        /* Tabs & Navigation (Precision Match) */
        .tabs-container { 
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px;
            background-color: #f3f4f6;
            border-radius: 9999px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            overflow: visible;
            max-width: 100%;
            position: relative;
        }
        .tab-indicator {
            position: absolute;
            height: calc(100% - 12px);
            background: #3f36bd;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(63, 54, 189, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            top: 6px;
            left: 6px;
        }
        .module-tab { 
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            border-radius: 9999px;
            font-size: 15px;
            font-weight: 600;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            background: transparent;
            border: none;
            position: relative;
            z-index: 2;
        }
        .module-tab:hover { 
            color: #1f2937;
        }
        .module-tab.active { 
            color: white !important;
        }
        .module-tab.active i { color: white !important; opacity: 1; }
        .module-tab i { font-size: 14px; opacity: 0.7; }
        
        /* Filter Menu Styles */
        #filterMenu { box-shadow: 0 20px 50px -12px rgba(124, 58, 237, 0.15); }
        .filter-btn { @apply px-5 py-3 bg-white border border-gray-200 rounded-2xl text-sm font-bold text-gray-700 hover:bg-gray-50 flex items-center gap-3 shadow-sm transition-all active:scale-95; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Account Card Polish */
        .account-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .account-card:hover { transform: translateY(-4px) scale(1.01); border-color: #7c3aed; }

        /* View Toggle Styles */
        .view-toggle-container { 
            display: flex; 
            background: #f1f5f9; 
            padding: 4px; 
            border-radius: 12px; 
            position: relative; 
            width: fit-content;
        }
        .view-toggle-indicator {
            position: absolute;
            height: calc(100% - 8px);
            width: calc(50% - 4px);
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
        }
        .view-toggle-btn { 
            position: relative;
            z-index: 2;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
        }
        .view-toggle-btn.active { color: #7c3aed; }

        /* Animations */
        .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

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

    <div class="flex-1 overflow-y-auto">
        <!-- Header Section (Matched with journal_entry.php) -->
        <div class="bg-white px-8 py-6 border-b border-gray-200 shadow-sm">
            <div class="flex items-center gap-5">
                <div id="breadcrumbIcon" class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-2xl">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="flex flex-col md:flex-row md:items-center justify-between flex-1 gap-4">
                    <div>
                        <h1 id="viewTitle" class="text-2xl font-black text-black">General Ledger</h1>
                        <p id="viewDescription" class="text-gray-600 mt-1 font-medium italic">Detailed audit and tracking of financial balances across all accounts.</p>
                    </div>
                    <div class="text-sm font-medium">
                        <a href="dashboard_admin.php" class="text-gray-500 hover:text-purple-600">Home</a>
                        <span class="mx-1 text-gray-300">/</span>
                        <span id="breadcrumbParent" class="text-gray-500">General Ledger</span>
                        <span class="mx-1 text-gray-300">/</span>
                        <a href="ledger.php" id="breadcrumbCurrent" class="text-purple-600 font-bold uppercase tracking-wider text-[11px]">Ledger Explorer</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-8 pb-32">
            <!-- VIEW: OVERVIEW -->
            <div id="view-overview" class="view-content active">
                <!-- Statistics Grid (Original Style) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="stat-widget shadow-sm">
                        <div>
                            <p class="text-sm font-bold text-gray-500 mb-1 leading-none">Total Assets</p>
                            <p id="stat-Asset" class="text-2xl font-black text-black leading-tight">₱0.00</p>
                        </div>
                        <div class="stat-icon bg-blue-100 text-blue-600">
                            <i class="fas fa-vault"></i>
                        </div>
                    </div>
                    
                    <div class="stat-widget shadow-sm">
                        <div>
                            <p class="text-sm font-bold text-gray-500 mb-1 leading-none">Total Liabilities</p>
                            <p id="stat-Liability" class="text-2xl font-black text-rose-600 leading-tight">₱0.00</p>
                        </div>
                        <div class="stat-icon bg-rose-100 text-rose-600">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>

                    <div class="stat-widget shadow-sm">
                        <div>
                            <p class="text-sm font-bold text-gray-500 mb-1 leading-none">Net Income (MTD)</p>
                            <p id="stat-NetIncome" class="text-2xl font-black text-emerald-600 leading-tight">₱0.00</p>
                        </div>
                        <div class="stat-icon bg-emerald-100 text-emerald-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>

                    <div class="stat-widget shadow-sm">
                        <div>
                            <p class="text-sm font-bold text-gray-500 mb-1 leading-none">Total Equity</p>
                            <p id="stat-Equity" class="text-2xl font-black text-violet-600 leading-tight">₱0.00</p>
                        </div>
                        <div class="stat-icon bg-violet-100 text-violet-600">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>

                <!-- Unified Controls Row -->
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4 mb-6">
                    <!-- Tabs (Left) -->
                    <div class="tabs-container no-scrollbar">
                        <div id="tab-indicator" class="tab-indicator"></div>
                        <?php 
                        $categories = [
                            ['type' => 'Asset', 'icon' => 'fa-briefcase'],
                            ['type' => 'Liability', 'icon' => 'fa-credit-card'],
                            ['type' => 'Equity', 'icon' => 'fa-users-cog'],
                            ['type' => 'Revenue', 'icon' => 'fa-arrow-trend-up'],
                            ['type' => 'Expense', 'icon' => 'fa-receipt']
                        ];
                        foreach($categories as $cat): ?>
                        <button onclick="loadCategory('<?php echo $cat['type']; ?>')" id="tab-<?php echo $cat['type']; ?>"
                                class="module-tab transition-all whitespace-nowrap">
                            <i class="fas <?php echo $cat['icon']; ?>"></i>
                            <?php echo $cat['type']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Actions (Right) -->
                    <div class="flex flex-wrap items-center justify-end gap-3 flex-1 w-full lg:w-auto">
                        <!-- Search Box -->
                        <div class="relative w-full md:w-64">
                            <input type="text" id="globalSearch" onkeyup="handleGlobalSearch(this)" 
                                   class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all shadow-sm" 
                                   placeholder="Search accounts...">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        </div>
                        
                        <!-- Audit Filters -->
                        <div class="relative">
                            <button id="filterButton" onclick="toggleFilterMenu(event)" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2 shadow-sm transition-all active:scale-95">
                                <i class="fas fa-filter text-purple-500 text-xs"></i>
                                Filters
                            </button>
                            <!-- Filter Menu Dropdown -->
                            <div id="filterMenu" class="absolute right-0 mt-4 w-80 bg-white rounded-[32px] border border-gray-100 p-8 z-[60] hidden animate-fade-in shadow-2xl">
                                <div class="space-y-6">
                                    <div class="flex p-1.5 bg-gray-100 rounded-2xl">
                                        <button onclick="setFilterMode('standard')" id="modeStandard" class="flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all bg-white shadow-sm text-purple-600">Standard</button>
                                        <button onclick="setFilterMode('custom')" id="modeCustom" class="flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all text-gray-500 hover:text-gray-700">Custom Range</button>
                                    </div>

                                    <div id="standardFilters" class="space-y-5">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Month</label>
                                                <select id="filterMonth" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                                    <option value="">All Months</option>
                                                    <?php
                                                    $m_list = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"May", 6=>"Jun", 7=>"Jul", 8=>"Aug", 9=>"Sep", 10=>"Oct", 11=>"Nov", 12=>"Dec"];
                                                    foreach ($m_list as $n => $m) echo "<option value='$n'>$m</option>";
                                                    ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Year</label>
                                                <select id="filterYear" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                                    <option value="">All Years</option>
                                                    <?php
                                                    $curr = date('Y');
                                                    for ($y = $curr; $y >= $curr - 5; $y--) {
                                                        $selected = ($y == $curr) ? 'selected' : '';
                                                        echo "<option value='$y' $selected>$y</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="customFilters" class="space-y-5 hidden">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">From</label>
                                                <input type="date" id="filterDateFrom" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">To</label>
                                                <input type="date" id="filterDateTo" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 pt-2">
                                        <button onclick="resetFilters()" class="flex-1 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-all">Reset</button>
                                        <button onclick="applyFilters()" class="flex-[2] py-4 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-2xl shadow-xl shadow-gray-200 hover:bg-black transition-all active:scale-95">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export -->
                        <div class="relative">
                            <button id="exportDropdownBtn" class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 flex items-center gap-2 shadow-sm transition-all active:scale-95" onclick="toggleExportMenu(event)">
                                <i class="fas fa-download text-emerald-500 text-xs"></i> Export
                            </button>
                            <div id="exportMenu" class="absolute right-0 mt-3 w-64 bg-white rounded-3xl shadow-2xl border border-gray-100 p-3 z-50 hidden transition-all">
                                <button onclick="openExportScopeModal('pdf')" class="w-full px-5 py-4 text-left text-sm font-bold text-gray-600 hover:bg-gray-50 rounded-2xl transition-all flex items-center gap-4">
                                    <div class="w-10 h-10 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center"><i class="fas fa-file-pdf"></i></div> PDF Document
                                </button>
                                <button onclick="openExportScopeModal('excel')" class="w-full px-5 py-4 text-left text-sm font-bold text-gray-600 hover:bg-gray-50 rounded-2xl transition-all flex items-center gap-4">
                                    <div class="w-10 h-10 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center"><i class="fas fa-file-excel"></i></div> Excel Sheet
                                </button>
                            </div>
                        </div>

                        <!-- Import -->
                        <button onclick="openImportModal()" class="px-4 py-2 bg-violet-600 text-white rounded-xl text-sm font-semibold hover:bg-violet-700 flex items-center gap-2 shadow-sm transition-all active:scale-95">
                            <i class="fas fa-upload text-xs"></i> Import
                        </button>

                        <div class="w-[1px] h-8 bg-gray-200 mx-1"></div>

                        <!-- Grid/Table Toggle (Animated) -->
                        <div class="view-toggle-container">
                            <div id="view-indicator" class="view-toggle-indicator" style="left: calc(50% + 0px);"></div>
                            <button onclick="setLayout('grid')" id="btn-grid" class="view-toggle-btn">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button onclick="setLayout('table')" id="btn-table" class="view-toggle-btn active">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Account List Content (Integrated from category view) -->
                <div id="explorer-content" class="min-h-[400px]">
                    <!-- Grid View -->
                    <div id="accountCardsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 hidden">
                        <!-- Cards injected by JS -->
                    </div>

                    <!-- Table View -->
                    <div id="accountTableContainer" class="bg-white rounded-3xl border border-gray-200 shadow-sm overflow-hidden animate-slide-up">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="pl-8 py-6">Code</th>
                                    <th class="px-6 py-6">Account Name</th>
                                    <th class="px-6 py-6">Monthly Activity</th>
                                    <th class="pr-8 py-6 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody id="accountTableBody" class="divide-y divide-gray-100">
                                <!-- Rows injected by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Overview Pagination -->
                    <div id="overviewPagination" class="mt-8 flex items-center justify-between">
                        <div id="overviewPageStatus" class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Showing 0-0 of 0 entries</div>
                        <div class="flex gap-2">
                            <button id="prevOverviewPage" class="btn-premium btn-ghost !py-2 !px-4 text-[11px] font-black uppercase tracking-widest disabled:opacity-30">Previous</button>
                            <button id="nextOverviewPage" class="btn-premium btn-ghost !py-2 !px-4 text-[11px] font-black uppercase tracking-widest disabled:opacity-30">Next</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- VIEW: DETAILED LEDGER -->
            <div id="view-ledger" class="view-content">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <button onclick="goBackFromLedger()" class="w-12 h-12 bg-white border border-gray-100 rounded-2xl flex items-center justify-center text-gray-400 hover:text-gray-900 transition-all shadow-sm">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div>
                            <h2 id="ledgerAccountName" class="text-2xl font-black text-gray-900">Petty Cash Fund</h2>
                            <p id="ledgerAccountCode" class="text-violet-500 font-bold font-mono text-sm tracking-widest">111001 • ASSET</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <button onclick="toggleFilterMenu(event, 'ledger')" class="px-5 py-3 bg-white border border-gray-100 rounded-2xl text-sm font-bold text-gray-700 hover:bg-gray-50 flex items-center gap-3 shadow-sm transition-all active:scale-95">
                                <i class="fas fa-filter text-violet-500 text-xs"></i>
                                Filters
                            </button>
                            <!-- Filter Menu for Ledger View (reusing the same menu ID or separate one) -->
                            <!-- To keep it simple, we'll keep the menu in one place and move it with JS or just use a shared one -->
                        </div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white rounded-[24px] border border-gray-200 shadow-sm overflow-hidden mb-12">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="pl-8 pr-4 py-6">Date</th>
                                <th class="px-4 py-6">Reference</th>
                                <th class="px-4 py-6">Description</th>
                                <th class="px-4 py-6 text-right">Debit</th>
                                <th class="px-4 py-6 text-right">Credit</th>
                                <th class="pl-4 pr-8 py-6 text-right">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerTableBody" class="divide-y divide-gray-100">
                            <!-- Injected by JS -->
                        </tbody>
                        <tfoot id="ledgerTableFooter" class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="3" class="pl-8 py-6 font-black text-black">TOTAL BALANCE</td>
                                <td id="totalDebit" class="px-4 py-6 text-right font-black text-emerald-600">₱0.00</td>
                                <td id="totalCredit" class="px-4 py-6 text-right font-black text-rose-600">₱0.00</td>
                                <td id="totalNetBalance" class="pl-4 pr-8 py-6 text-right font-black text-black bg-gray-100/50">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <!-- Ledger Pagination -->
                    <div class="p-8 bg-gray-50/30 flex items-center justify-between border-t border-gray-50">
                        <div id="pageStatus" class="text-[11px] font-black text-gray-400 uppercase tracking-widest">Showing 1-10 of 45 entries</div>
                        <div class="flex gap-2">
                            <button id="prevPage" class="btn-premium btn-ghost !py-2 !px-4 text-[11px] uppercase tracking-widest disabled:opacity-30">Previous</button>
                            <button id="nextPage" class="btn-premium btn-ghost !py-2 !px-4 text-[11px] uppercase tracking-widest disabled:opacity-30">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importBackdrop" class="modal-backdrop" onclick="closeImportModal()"></div>
    <div id="importModal" class="modal-box !max-w-md">
        <div class="p-8 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-2xl font-black text-gray-900">Import Ledger</h3>
            <button onclick="closeImportModal()" class="w-10 h-10 flex items-center justify-center text-gray-400 hover:bg-gray-50 rounded-xl transition-all">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-10">
            <form id="importForm" onsubmit="handleImport(event)">
                <div class="mb-8">
                    <div class="border-2 border-dashed border-gray-100 rounded-3xl p-10 text-center hover:border-violet-400 transition-all cursor-pointer bg-gray-50/50" 
                         onclick="document.getElementById('csvFile').click()">
                        <div class="w-16 h-16 bg-violet-100 text-violet-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                            <i class="fas fa-file-csv"></i>
                        </div>
                        <p class="text-gray-900 font-bold mb-1">Upload CSV Template</p>
                        <p class="text-gray-400 text-xs px-4">Ensure dates follow YYYY-MM-DD format and account codes match COA.</p>
                        <input type="file" id="csvFile" class="hidden" accept=".csv" required onchange="updateFileName(this)">
                        <div id="selectedFileName" class="mt-4 px-4 py-2 bg-violet-600 text-white rounded-xl font-bold text-xs inline-block hidden"></div>
                    </div>
                </div>
                <div class="flex gap-4">
                    <button type="submit" id="importSubmitBtn" class="flex-1 btn-premium btn-primary py-4 justify-center">
                        Execute Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-8 right-8 z-[100000] flex flex-col gap-3 pointer-events-none"></div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-8 right-8 z-[100000] flex flex-col gap-3 pointer-events-none"></div>

    <style>
        .modal-backdrop.show { display: block; }
        .animate-bounce-in { animation: bounceIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); }
        }
    </style>

    <!-- Export Scope Modal -->
    <div id="exportScopeBackdrop" class="modal-backdrop" onclick="closeExportScopeModal()"></div>
    <div id="exportScopeModal" class="modal-box p-10">
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fas fa-file-export"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900 leading-tight">Export Context</h3>
                    <p class="text-sm font-bold text-gray-500">Choose the scope of your financial report</p>
                </div>
            </div>
            <button onclick="closeExportScopeModal()" class="w-12 h-12 flex items-center justify-center rounded-2xl hover:bg-gray-100 transition-all text-gray-400 hover:text-gray-900">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <button onclick="executeExport('single')" class="p-6 bg-white border border-gray-200 rounded-[32px] text-left hover:border-violet-500 hover:shadow-2xl hover:shadow-violet-100 transition-all group">
                <div class="w-10 h-10 bg-violet-50 text-violet-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-violet-600 group-hover:text-white transition-all">
                    <i class="fas fa-bullseye"></i>
                </div>
                <div class="text-sm font-black text-gray-900 mb-1">Current Focus</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Selected Account Only</div>
            </button>

            <button onclick="executeExport('category')" class="p-6 bg-white border border-gray-200 rounded-[32px] text-left hover:border-emerald-500 hover:shadow-2xl hover:shadow-emerald-100 transition-all group">
                <div class="w-10 h-10 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-all">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="text-sm font-black text-gray-900 mb-1">Active Category</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest" id="scopeCategoryLabel">Assets Accounts</div>
            </button>

            <button onclick="executeExport('all')" class="p-6 bg-white border border-gray-200 rounded-[32px] text-left hover:border-blue-500 hover:shadow-2xl hover:shadow-blue-100 transition-all group md:col-span-2">
                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="text-sm font-black text-gray-900 mb-1">Full Ledger Export</div>
                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">All Categories & Accounts</div>
            </button>
        </div>

        <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-2xl border border-amber-100">
            <i class="fas fa-info-circle text-amber-500"></i>
            <p class="text-[10px] font-black text-amber-700 uppercase tracking-widest">Export will automatically respect your active audit filters.</p>
        </div>
    </div>

    <script>
        let currentView = 'overview'; // overview, ledger
        let currentType = 'Asset';
        let currentAccountId = 0;
        let currentPage = 1; // Used for Detailed Ledger
        let totalPages = 1;
        let overviewPage = 1; // Used for Category Overview
        let overviewTotalPages = 1;
        let layoutPreference = 'table'; // grid or table
        
        // Filter Global State
        let filterMode = 'standard';
        let activeExportFormat = 'pdf';

        function toggleFilterMenu(e, source = 'overview') {
            e.stopPropagation();
            const menu = document.getElementById('filterMenu');
            const isHidden = menu.classList.contains('hidden');
            
            if (isHidden) {
                // If opening from ledger view, we might want to reposition it
                const btn = e.currentTarget;
                const rect = btn.getBoundingClientRect();
                
                // If in ledger view, the overview container might be hidden, so we need to move the menu
                if (source === 'ledger') {
                    document.body.appendChild(menu); // Move to body to avoid clipping
                    menu.style.position = 'fixed';
                    menu.style.top = (rect.bottom + 10) + 'px';
                    menu.style.left = (rect.right - 320) + 'px';
                } else {
                    // Back to its original parent for overview
                    const parent = btn.parentElement;
                    parent.appendChild(menu);
                    menu.style.position = 'absolute';
                    menu.style.top = '';
                    menu.style.left = '';
                }
                menu.classList.remove('hidden');
            } else {
                menu.classList.add('hidden');
            }
        }

        function setFilterMode(mode) {
            filterMode = mode;
            document.getElementById('modeStandard').className = mode === 'standard' ? 'flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all bg-white shadow-sm text-purple-600' : 'flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all text-gray-500 hover:text-gray-700';
            document.getElementById('modeCustom').className = mode === 'custom' ? 'flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all bg-white shadow-sm text-purple-600' : 'flex-1 py-2.5 text-[10px] font-black uppercase tracking-widest rounded-xl transition-all text-gray-500 hover:text-gray-700';
            
            document.getElementById('standardFilters').classList.toggle('hidden', mode !== 'standard');
            document.getElementById('customFilters').classList.toggle('hidden', mode !== 'custom');
        }

        function applyFilters() {
            document.getElementById('filterMenu').classList.add('hidden');
            if (currentView === 'ledger') {
                loadLedger(currentAccountId);
            } else {
                loadCategory(currentType);
                loadDashboard();
            }
        }

        function resetFilters() {
            document.getElementById('filterMonth').value = '';
            document.getElementById('filterYear').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            applyFilters();
        }

        // Export Scope Modal
        function openExportScopeModal(format) {
            activeExportFormat = format;
            document.getElementById('scopeCategoryLabel').innerText = `${currentType} Accounts`;
            document.getElementById('exportMenu').classList.add('hidden');
            document.getElementById('exportScopeBackdrop').classList.add('show');
            document.getElementById('exportScopeModal').classList.add('show');
        }

        function closeExportScopeModal() {
            document.getElementById('exportScopeBackdrop').classList.remove('show');
            document.getElementById('exportScopeModal').classList.remove('show');
        }

        // View State Manager
        function switchView(view) {
            currentView = view;
            
            // Toggle Views
            document.querySelectorAll('.view-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`view-${view}`).classList.add('active');

            // Update Header & Breadcrumbs
            const title = document.getElementById('viewTitle');
            const desc = document.getElementById('viewDescription');
            const breadParent = document.getElementById('breadcrumbParent');
            const breadCurrent = document.getElementById('breadcrumbCurrent');
            const icon = document.getElementById('breadcrumbIcon');

            if (view === 'overview') {
                title.innerText = 'General Ledger';
                desc.innerText = 'Detailed audit and tracking of financial balances across all accounts.';
                breadParent.innerText = 'Accounting';
                breadCurrent.innerText = 'Ledger Explorer';
                icon.innerHTML = '<i class="fas fa-book-open"></i>';
                loadDashboard();
            } else if (view === 'ledger') {
                title.innerText = 'Account Audit';
                desc.innerText = 'Detailed transaction history and running balance audit.';
                breadParent.innerText = currentType;
                breadCurrent.innerText = 'Ledger Detail';
                icon.innerHTML = '<i class="fas fa-search-dollar"></i>';
            }
        }

        let cachedAccounts = [];

        // 1. Dashboard Logic (Search & Sync Inspired)
        function handleGlobalSearch(input) {
            const query = input.value.toLowerCase().trim();
            const filtered = cachedAccounts.filter(acc => {
                const nameMatch = (acc.name || '').toLowerCase().includes(query);
                const codeMatch = (acc.code || '').toString().toLowerCase().includes(query);
                return nameMatch || codeMatch;
            });
            renderCategoryData(filtered);
        }

        async function loadDashboard() {
            try {
                const month = document.getElementById('filterMonth').value;
                const year = document.getElementById('filterYear').value;
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;
                
                const query = new URLSearchParams({
                    ajax_get_overview_stats: 1,
                    month, year, dateFrom, dateTo, filterMode
                });
                
                const response = await fetch(`ledger.php?${query.toString()}`);
                const data = await response.json();
                if (data.success) {
                    const stats = data.stats;
                    ['Asset', 'Liability', 'Equity'].forEach(type => {
                        const val = parseFloat(stats[type] || 0);
                        document.getElementById(`stat-${type}`).innerText = '₱' + val.toLocaleString(undefined, {minimumFractionDigits:2});
                    });
                    document.getElementById('stat-NetIncome').innerText = '₱' + parseFloat(data.net_income || 0).toLocaleString(undefined, {minimumFractionDigits:2});
                }
            } catch (error) {
                console.error('Overview error:', error);
            }
        }

        // 2. Category Browser Logic
        function setLayout(layout) {
            layoutPreference = layout;
            const indicator = document.getElementById('view-indicator');
            const btnGrid = document.getElementById('btn-grid');
            const btnTable = document.getElementById('btn-table');
            
            if (layout === 'grid') {
                indicator.style.left = '4px';
                btnGrid.classList.add('active');
                btnTable.classList.remove('active');
            } else {
                indicator.style.left = 'calc(50% + 0px)';
                btnGrid.classList.remove('active');
                btnTable.classList.add('active');
            }
            
            document.getElementById('accountCardsGrid').classList.toggle('hidden', layout !== 'grid');
            document.getElementById('accountTableContainer').classList.toggle('hidden', layout !== 'table');
            
            // Re-render cached data to update view
            renderCategoryData(cachedAccounts);
        }

        async function loadCategory(type, pageNum = 1) {
            currentType = type;
            overviewPage = pageNum;
            // Update Tab UI
            document.querySelectorAll('.module-tab').forEach(btn => btn.classList.remove('active'));
            const activeTab = document.getElementById(`tab-${type}`);
            const indicator = document.getElementById('tab-indicator');
            
            if (activeTab && indicator) {
                activeTab.classList.add('active');
                indicator.style.width = `${activeTab.offsetWidth}px`;
                indicator.style.left = `${activeTab.offsetLeft}px`;
            }
            
            const grid = document.getElementById('accountCardsGrid');
            const tbody = document.getElementById('accountTableBody');
            
            const loadingHtml = '<div class="col-span-full py-20 text-center"><i class="fas fa-circle-notch fa-spin text-4xl text-violet-200"></i></div>';
            grid.innerHTML = loadingHtml;
            tbody.innerHTML = '<tr><td colspan="4" class="py-20 text-center text-gray-400 italic">Loading accounts...</td></tr>';

            try {
                const month = document.getElementById('filterMonth').value;
                const year = document.getElementById('filterYear').value;
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;

                const query = new URLSearchParams({
                    ajax_get_accounts_by_type: 1,
                    type: type,
                    page: pageNum,
                    month, year, dateFrom, dateTo, filterMode
                });
                
                const response = await fetch(`ledger.php?${query.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    cachedAccounts = data.accounts;
                    overviewTotalPages = data.pages;
                    renderCategoryData(data.accounts);
                    
                    document.getElementById('overviewPageStatus').innerText = `Showing ${(overviewPage-1)*10+1}-${Math.min(overviewPage*10, data.total)} of ${data.total} entries`;
                    document.getElementById('prevOverviewPage').disabled = overviewPage <= 1;
                    document.getElementById('nextOverviewPage').disabled = overviewPage >= overviewTotalPages;
                }
            } catch (error) {
                console.error('Category error:', error);
            }
        }

        function renderCategoryData(accounts) {
            const grid = document.getElementById('accountCardsGrid');
            const tbody = document.getElementById('accountTableBody');
            
            grid.innerHTML = "";
            tbody.innerHTML = "";

            if (accounts.length === 0) {
                const emptyMsg = `<div class="col-span-full py-20 text-center text-gray-400 font-bold uppercase tracking-[0.2em] opacity-30">No matches found</div>`;
                grid.innerHTML = emptyMsg;
                tbody.innerHTML = `<tr><td colspan="4" class="py-20 text-center text-gray-400">${emptyMsg}</td></tr>`;
                return;
            }

            accounts.forEach(acc => {
                const bal = parseFloat(acc.balance);
                const balColor = bal >= 0 ? 'text-emerald-600' : 'text-rose-600';
                
                // Render Card
                const card = document.createElement('div');
                card.className = 'account-card p-6 bg-white border border-gray-100 rounded-[32px] premium-shadow animate-fade-in group flex flex-col justify-between';
                card.onclick = () => loadLedger(acc.id);
                card.innerHTML = `
                    <div>
                        <div class="flex justify-between items-start mb-4">
                            <div class="px-3 py-1 bg-gray-50 text-gray-500 rounded-lg text-[11px] font-black uppercase tracking-widest">
                                ${acc.type}
                            </div>
                            <div class="px-3 py-1 bg-violet-50 text-violet-600 text-[11px] font-black uppercase tracking-widest rounded-lg">
                                ${acc.month_txn_count} movements
                            </div>
                        </div>
                        <div class="text-[16px] font-black text-gray-400 font-mono tracking-tight leading-none mb-2">${acc.code}</div>
                        <h3 class="text-[16px] font-bold text-gray-900 line-clamp-1 mb-2">${acc.name}</h3>
                    </div>
                    
                    <div class="pt-4 border-t border-gray-50">
                        <div class="flex justify-between items-end">
                            <div>
                                <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Period Balance</div>
                                <div class="text-xl font-black ${balColor}">₱${bal.toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-violet-600 transition-all">
                                <i class="fas fa-chevron-right text-[10px] text-gray-300 group-hover:text-white"></i>
                            </div>
                        </div>
                    </div>
                `;
                grid.appendChild(card);

                // Render Table Row
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-violet-50 cursor-pointer transition-all group animate-fade-in';
                tr.onclick = () => loadLedger(acc.id);
                tr.innerHTML = `
                    <td class="pl-8 py-6 font-mono text-xs font-black text-violet-500 group-hover:translate-x-1 transition-all">${acc.code}</td>
                    <td class="px-6 py-6 font-black text-gray-900">${acc.name}</td>
                    <td class="px-6 py-6">
                        <span class="px-4 py-1.5 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-black uppercase">
                            ${acc.month_txn_count} recorded movements
                        </span>
                    </td>
                    <td class="pr-8 py-6 text-right font-black text-gray-900 text-xl">
                        ₱${bal.toLocaleString(undefined, {minimumFractionDigits:2})}
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        // 3. Detailed Ledger Logic
        async function loadLedger(accountId, pageNum = 1) {
            currentAccountId = accountId;
            currentPage = pageNum;
            switchView('ledger');

            const tbody = document.getElementById('ledgerTableBody');

            try {
                const month = document.getElementById('filterMonth').value;
                const year = document.getElementById('filterYear').value;
                const filterDateFrom = document.getElementById('filterDateFrom').value;
                const filterDateTo = document.getElementById('filterDateTo').value;

                const query = new URLSearchParams({
                    ajax_get_ledger: 1,
                    account_id: accountId,
                    type: currentType,
                    page: pageNum,
                    month, year, filterDateFrom, filterDateTo, filterMode
                });
                const response = await fetch(`ledger.php?${query.toString()}`);
                const data = await response.json();

                if (data.success) {
                    // Update UI info
                    document.getElementById('ledgerAccountName').innerText = data.account.name;
                    document.getElementById('ledgerAccountCode').innerText = `${data.account.code} • ${data.account.type.toUpperCase()}`;
                    
                    totalPages = data.pages;
                    renderLedgerRows(data.rows);
                    
                    document.getElementById('pageStatus').innerText = `Showing ${(currentPage-1)*10+1}-${Math.min(currentPage*10, data.total)} of ${data.total} entries`;
                    document.getElementById('prevPage').disabled = currentPage <= 1;
                    document.getElementById('nextPage').disabled = currentPage >= totalPages;
                }
            } catch (error) {
                console.error('Ledger error:', error);
            }
        }

        function renderLedgerRows(rows) {
            const tbody = document.getElementById('ledgerTableBody');
            tbody.innerHTML = "";

            let totalDebit = 0;
            let totalCredit = 0;

            if (rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="py-20 text-center text-gray-300 italic">No transaction history found for this period.</td></tr>`;
                document.getElementById('totalDebit').innerText = '₱0.00';
                document.getElementById('totalCredit').innerText = '₱0.00';
                document.getElementById('totalNetBalance').innerText = '₱0.00';
                return;
            }

            rows.forEach(row => {
                const debit = parseFloat(row.debit_amount || 0);
                const credit = parseFloat(row.credit_amount || 0);
                const bal = parseFloat(row.running_balance || 0);
                
                totalDebit += debit;
                totalCredit += credit;

                const tr = document.createElement('tr');
                tr.className = 'hover:bg-violet-50/50 transition-all group';
                tr.innerHTML = `
                    <td class="pl-8 pr-4 py-5 font-bold text-gray-900">${row.transaction_date.substring(0,10)}</td>
                    <td class="px-4 py-5">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-lg text-[11px] font-black group-hover:bg-white transition-all border border-gray-200">
                            ${row.original_reference || row.reference_id}
                        </span>
                    </td>
                    <td class="px-4 py-5 font-bold text-gray-700 max-w-[300px] truncate" title="${row.description}">${row.description}</td>
                    <td class="px-4 py-5 text-right font-black text-emerald-600">${debit > 0 ? '₱'+debit.toLocaleString(undefined, {minimumFractionDigits:2}) : '-'}</td>
                    <td class="px-4 py-5 text-right font-black text-rose-600">${credit > 0 ? '₱'+credit.toLocaleString(undefined, {minimumFractionDigits:2}) : '-'}</td>
                    <td class="pl-4 pr-8 py-5 text-right font-black text-black bg-gray-50/50 group-hover:bg-violet-100/50 transition-all">
                        ₱${bal.toLocaleString(undefined, {minimumFractionDigits:2})}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Update Footer Totals
            document.getElementById('totalDebit').innerText = '₱' + totalDebit.toLocaleString(undefined, {minimumFractionDigits:2});
            document.getElementById('totalCredit').innerText = '₱' + totalCredit.toLocaleString(undefined, {minimumFractionDigits:2});
            
            // Total Net reflects the last row's running balance if viewing a single account
            if (rows.length > 0) {
                const lastRowBal = parseFloat(rows[rows.length - 1].running_balance || 0); 
                document.getElementById('totalNetBalance').innerText = '₱' + lastRowBal.toLocaleString(undefined, {minimumFractionDigits:2});
            } else {
                document.getElementById('totalNetBalance').innerText = '₱0.00';
            }
        }

        function applyLedgerFilters() {
            loadLedger(currentAccountId, 1);
        }

        function goBackFromLedger() {
            switchView('overview');
            loadCategory(currentType || 'Asset');
        }


        // Global Helpers
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const colors = { success: 'bg-emerald-500', error: 'bg-rose-500', info: 'bg-blue-500' };
            toast.className = `${colors[type]} text-white px-8 py-5 rounded-3xl shadow-2xl flex items-center gap-4 animate-slide-up pointer-events-auto min-w-[320px]`;
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle text-2xl"></i> <span class="font-black">${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => { toast.classList.add('opacity-0'); setTimeout(() => toast.remove(), 500); }, 4000);
        }

        function toggleExportMenu(e) {
            e.stopPropagation();
            const me = document.getElementById('exportMenu');
            me.classList.toggle('hidden');
        }

        // Modal Helpers
        function openImportModal() {
            document.getElementById('importBackdrop').classList.add('show');
            document.getElementById('importModal').classList.add('show');
        }
        function closeImportModal() {
            document.getElementById('importBackdrop').classList.remove('show');
            document.getElementById('importModal').classList.remove('show');
        }
        function updateFileName(input) {
            const div = document.getElementById('selectedFileName');
            if (input.files.length) { div.innerText = input.files[0].name; div.classList.remove('hidden'); }
        }

        async function handleImport(event) {
            event.preventDefault();
            const btn = document.getElementById('importSubmitBtn');
            const file = document.getElementById('csvFile').files[0];
            if (!file) return;

            btn.disabled = true;
            btn.innerText = 'Processing...';

            const fd = new FormData();
            fd.append('csv', file);
            fd.append('action', 'import_ledger');

            try {
                const res = await fetch('ledger.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message);
                    closeImportModal();
                    loadDashboard();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) { showToast('Import failed', 'error'); }
            finally { btn.disabled = false; btn.innerText = 'Execute Import'; }
        }

        // Advanced Export Execution
        async function executeExport(scope) {
            closeExportScopeModal();
            showToast(`Preparing ${activeExportFormat.toUpperCase()} for ${scope} scope...`, 'info');
            
            try {
                const month = document.getElementById('filterMonth').value;
                const year = document.getElementById('filterYear').value;
                const dateFrom = document.getElementById('filterDateFrom').value;
                const dateTo = document.getElementById('filterDateTo').value;
                
                let accountsToExport = [];
                
                if (scope === 'single') {
                    if (currentAccountId === 0) {
                        showToast('Please select an account first', 'error');
                        return;
                    }
                    accountsToExport.push({ id: currentAccountId });
                } else if (scope === 'category') {
                    const res = await fetch(`ledger.php?ajax_get_accounts_by_type=1&type=${currentType}&month=${month}&year=${year}&dateFrom=${dateFrom}&dateTo=${dateTo}&filterMode=${filterMode}`);
                    const data = await res.json();
                    accountsToExport = data.accounts;
                } else if (scope === 'all') {
                    const types = ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense'];
                    for (const t of types) {
                        const res = await fetch(`ledger.php?ajax_get_accounts_by_type=1&type=${t}&month=${month}&year=${year}&dateFrom=${dateFrom}&dateTo=${dateTo}&filterMode=${filterMode}`);
                        const data = await res.json();
                        accountsToExport = accountsToExport.concat(data.accounts);
                    }
                }

                if (accountsToExport.length === 0) {
                    showToast('No data found for the selected scope', 'error');
                    return;
                }

                if (activeExportFormat === 'pdf') {
                    await generateTraditionalPDF(accountsToExport, { month, year, dateFrom, dateTo, filterMode });
                } else {
                    await generateTraditionalExcel(accountsToExport, { month, year, dateFrom, dateTo, filterMode });
                }
                
                showToast('Report generated successfully!');
            } catch (e) {
                console.error(e);
                showToast('Export failed', 'error');
            }
        }

        async function generateTraditionalPDF(accounts, filters) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            
            // Header
            doc.setFont("helvetica", "bold");
            doc.setFontSize(22);
            doc.setTextColor(20, 20, 20);
            doc.text("GENERAL LEDGER", pageWidth/2, 60, { align: 'center' });
            
            let currentY = 100;

            for (let i = 0; i < accounts.length; i++) {
                const acc = accounts[i];
                
                // Fetch Ledger for this account
                const q = new URLSearchParams({ 
                    ajax_get_ledger: 1, account_id: acc.id, all: 1, 
                    ...filters 
                });
                const res = await fetch(`ledger.php?${q.toString()}`);
                const data = await res.json();
                const rows = data.rows;

                if (currentY > 700) { doc.addPage(); currentY = 60; }
                
                // Account Title Bar
                doc.setDrawColor(220, 220, 220);
                doc.setFillColor(250, 250, 250);
                doc.rect(40, currentY, pageWidth - 80, 40, 'F');
                
                doc.setFontSize(14);
                doc.setTextColor(31, 41, 55);
                doc.text(acc.name, 55, currentY + 25);
                
                doc.setFontSize(10);
                doc.setTextColor(107, 114, 128);
                doc.text(`Account No. ${acc.code}`, pageWidth - 55, currentY + 25, { align: 'right' });
                
                currentY += 40;

                const tableData = rows.map(r => {
                    const bal = parseFloat(r.running_balance);
                    const isNormalDebit = (acc.normal_balance || 'debit') === 'debit';
                    
                    return [
                        r.transaction_date.substring(0,10),
                        r.description,
                        r.reference_id,
                        r.debit_amount > 0 ? parseFloat(r.debit_amount).toLocaleString(undefined, {minimumFractionDigits:2}) : '-',
                        r.credit_amount > 0 ? parseFloat(r.credit_amount).toLocaleString(undefined, {minimumFractionDigits:2}) : '-',
                        bal >= 0 ? bal.toLocaleString(undefined, {minimumFractionDigits:2}) : '-',
                        bal < 0 ? Math.abs(bal).toLocaleString(undefined, {minimumFractionDigits:2}) : '-'
                    ];
                });

                doc.autoTable({
                    startY: currentY,
                    head: [[
                        { content: 'Date', styles: { halign: 'center' } },
                        'Explanation',
                        { content: 'P.R.', styles: { halign: 'center' } },
                        { content: 'Debit', styles: { halign: 'right' } },
                        { content: 'Credit', styles: { halign: 'right' } },
                        { content: 'Balance Debit', styles: { halign: 'right' } },
                        { content: 'Balance Credit', styles: { halign: 'right' } }
                    ]],
                    body: tableData,
                    margin: { left: 40, right: 40 },
                    theme: 'grid',
                    headStyles: { fillColor: [75, 85, 99], textColor: 255, fontSize: 8, fontStyle: 'bold' },
                    styles: { fontSize: 7, cellPadding: 5 },
                    columnStyles: {
                        0: { width: 60 },
                        2: { width: 50 },
                        3: { halign: 'right', width: 60 },
                        4: { halign: 'right', width: 60 },
                        5: { halign: 'right', width: 60 },
                        6: { halign: 'right', width: 60 }
                    },
                    didDrawPage: (d) => { currentY = d.cursor.y; }
                });

                currentY += 40; // Space between accounts
            }

            doc.save(`Traditional_Ledger_${new Date().toISOString().split('T')[0]}.pdf`);
        }

        async function generateTraditionalExcel(accounts, filters) {
            const wb = XLSX.utils.book_new();
            
            for (const acc of accounts) {
                const q = new URLSearchParams({ ajax_get_ledger: 1, account_id: acc.id, all: 1, ...filters });
                const res = await fetch(`ledger.php?${q.toString()}`);
                const data = await res.json();
                
                const wsData = [
                    ["GENERAL LEDGER"],
                    [`Account: ${acc.name}`, "", "", "", "", `Account No. ${acc.code}`],
                    [],
                    ["Date", "Explanation", "P.R.", "Debit", "Credit", "Balance Debit", "Balance Credit"]
                ];

                data.rows.forEach(r => {
                    const bal = parseFloat(r.running_balance);
                    wsData.push([
                        r.transaction_date.substring(0,10),
                        r.description,
                        r.reference_id,
                        parseFloat(r.debit_amount || 0),
                        parseFloat(r.credit_amount || 0),
                        bal >= 0 ? bal : 0,
                        bal < 0 ? Math.abs(bal) : 0
                    ]);
                });

                const ws = XLSX.utils.aoa_to_sheet(wsData);
                XLSX.utils.book_append_sheet(wb, ws, acc.name.substring(0,31));
            }

            XLSX.writeFile(wb, `Traditional_Ledger_${new Date().toISOString().split('T')[0]}.xlsx`);
        }

        // Event Listeners
        window.addEventListener('resize', () => {
            if (currentType) {
                const activeTab = document.getElementById(`tab-${currentType}`);
                const indicator = document.getElementById('tab-indicator');
                if (activeTab && indicator) {
                    indicator.style.transition = 'none';
                    indicator.style.width = `${activeTab.offsetWidth}px`;
                    indicator.style.left = `${activeTab.offsetLeft}px`;
                    setTimeout(() => indicator.style.transition = '', 10);
                }
            }
        });

        document.getElementById('prevPage').onclick = () => { if (currentPage > 1) loadLedger(currentAccountId, currentPage - 1); };
        document.getElementById('nextPage').onclick = () => { if (currentPage < totalPages) loadLedger(currentAccountId, currentPage + 1); };
        
        document.getElementById('prevOverviewPage').onclick = () => { if (overviewPage > 1) loadCategory(currentType, overviewPage - 1); };
        document.getElementById('nextOverviewPage').onclick = () => { if (overviewPage < overviewTotalPages) loadCategory(currentType, overviewPage + 1); };
        document.addEventListener('click', () => document.getElementById('exportMenu')?.classList.add('hidden'));

        window.onload = () => {
            switchView('overview');
            // Ensure the year is set to current year value from PHP default
            loadCategory('Asset');
        };
    </script>
    </main>
</div>
</div>
</body>
</html>