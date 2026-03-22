<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
include('connection.php');

// Handle report generation request
if (isset($_POST['generate_report'])) {
    $report_title = $_POST['report_title'];
    $report_period = $_POST['report_period'];
    $audit_team = $_POST['audit_team'];
    $site_section = $_POST['site_section'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Generate unique report number
    $report_number = 'AUD-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Analyze financial data for audit findings
    $audit_findings = analyzeFinancialData($conn, $start_date, $end_date);
    
    // Insert the new audit report
    $sql = "INSERT INTO audit_reports (report_number, report_title, report_period, audit_team, site_section, start_date, end_date, generated_date, audit_findings) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $findings_json = json_encode($audit_findings);
    $stmt->bind_param("ssssssss", $report_number, $report_title, $report_period, $audit_team, $site_section, $start_date, $end_date, $findings_json);
    
    if ($stmt->execute()) {
        $new_report_id = $stmt->insert_id;
        
        // Store report ID in session for immediate viewing with timestamp
        $_SESSION['last_generated_report'] = $new_report_id;
        $_SESSION['success_message_time'] = time();
        
        header("Location: audit_reports.php");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    
    $stmt->close();
}

// Function to analyze financial data and generate audit findings
function analyzeFinancialData($conn, $start_date, $end_date) {
    $findings = [];
    
    // 1. Analyze Journal Entries for inconsistencies
    $journal_analysis = analyzeJournalEntries($conn, $start_date, $end_date);
    $findings = array_merge($findings, $journal_analysis);
    
    // 2. Analyze Ledger for balance issues
    $ledger_analysis = analyzeLedgerEntries($conn, $start_date, $end_date);
    $findings = array_merge($findings, $ledger_analysis);
    
    // 3. Analyze Balance Sheet discrepancies
    $balance_sheet_analysis = analyzeBalanceSheet($conn, $start_date, $end_date);
    $findings = array_merge($findings, $balance_sheet_analysis);
    
    // 4. Analyze Income Statement trends
    $income_statement_analysis = analyzeIncomeStatement($conn, $start_date, $end_date);
    $findings = array_merge($findings, $income_statement_analysis);
    
    return $findings;
}

// ... (keep the existing analysis functions the same as before) ...
function analyzeJournalEntries($conn, $start_date, $end_date) {
    $findings = [];
    
    // Check for unbalanced journal entries by Grouping Reference Handle
    $sql = "SELECT original_reference as reference, SUM(debit_amount) as total_debit, SUM(credit_amount) as total_credit 
            FROM general_ledger 
            WHERE transaction_date BETWEEN '$start_date' AND '$end_date' 
            GROUP BY original_reference
            HAVING ABS(SUM(debit_amount) - SUM(credit_amount)) > 0.01";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $findings[] = [
            'element' => 'Journal Entries',
            'compliance' => 'Unbalanced journal entries detected (Debit != Credit in specific references)',
            'corrective_action' => 'Review references ' . implode(', ', array_column($result->fetch_all(MYSQLI_ASSOC), 'reference')) . ' and correct discrepancies.',
            'status' => 'pending'
        ];
    }
    
    return $findings;
}

function analyzeLedgerEntries($conn, $start_date, $end_date) {
    $findings = [];
    
    // Check for abnormal balances in Level 4 accounts
    $sql = "SELECT gl.gl_account_id, gl.gl_account_name as account_name, 
            SUM(gl.debit_amount - gl.credit_amount) as balance
            FROM general_ledger gl
            WHERE gl.transaction_date BETWEEN '$start_date' AND '$end_date'
            GROUP BY gl.gl_account_id, gl.gl_account_name
            HAVING ABS(balance) > 50000"; // Flagging very high movements
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $findings[] = [
                'element' => 'Ledger Accounts',
                'compliance' => "High volume transaction activity detected in {$row['account_name']}",
                'corrective_action' => "Audit detail of {$row['account_name']} to ensure all entries are valid business expenses.",
                'status' => 'pending'
            ];
        }
    }
    
    return $findings;
}

function analyzeBalanceSheet($conn, $start_date, $end_date) {
    $findings = [];
    
    // Basic Accounting Equation Audit: Assets = Liabilities + Equity
    // We use the 'type' column from hierarchy which directly maps to Asset, Liability, Equity
    $sql = "SELECT h.type, SUM(gl.debit_amount - gl.credit_amount) as net_val
            FROM general_ledger gl
            JOIN chart_of_accounts_hierarchy h ON gl.gl_account_id = h.id
            WHERE gl.transaction_date <= '$end_date'
            GROUP BY h.type";
    
    $result = $conn->query($sql);
    $data = [];
    if($result) {
        while($row = $result->fetch_assoc()){
            $data[$row['type']] = (float)$row['net_val'];
        }
    }

    $assets = abs($data['Asset'] ?? 0);
    $liabilities = abs($data['Liability'] ?? 0);
    $equity = abs($data['Equity'] ?? 0);
    
    if (abs($assets - ($liabilities + $equity)) > 10.00) {
        $findings[] = [
            'element' => 'Balance Sheet',
            'compliance' => 'Assets do not equal Liabilities + Equity (Equation Failure)',
            'corrective_action' => 'Check retained earnings calculation and ensure all accounts are mapped correctly in hierarchy.',
            'status' => 'pending'
        ];
    }
    
    return $findings;
}

function analyzeIncomeStatement($conn, $start_date, $end_date) {
    $findings = [];
    
    // Audit Profitability Trends
    // Use 'type' column for direct classification
    $sql = "SELECT h.type, SUM(gl.credit_amount - gl.debit_amount) as net_val
            FROM general_ledger gl
            JOIN chart_of_accounts_hierarchy h ON gl.gl_account_id = h.id
            WHERE gl.transaction_date BETWEEN '$start_date' AND '$end_date'
            GROUP BY h.type";
    
    $result = $conn->query($sql);
    $data = [];
    if($result) {
        while($row = $result->fetch_assoc()){
            $data[$row['type']] = (float)$row['net_val'];
        }
    }

    $revenue = $data['Revenue'] ?? 0;
    $expense = abs($data['Expense'] ?? 0);
    $net_income = $revenue - $expense;
    
    if ($net_income < 0 && $revenue > 0) {
        $findings[] = [
            'element' => 'Income Statement',
            'compliance' => 'Net loss detected despite having active revenue flows.',
            'corrective_action' => 'Perform vertical analysis of expenses to identify unusually high cost centers.',
            'status' => 'pending'
        ];
    }
    
    return $findings;
}

// Fetch audit reports with pagination
$limit = 10;
$p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($p < 1) $p = 1;
$offset = ($p - 1) * $limit;

$total_q = $conn->query("SELECT COUNT(*) as count FROM audit_reports");
$total_reports = $total_q->fetch_assoc()['count'];
$total_pages = ceil($total_reports / $limit);

$sql = "SELECT * FROM audit_reports ORDER BY generated_date DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch last generated report details if available
$last_report = null;
$show_success_message = false;
$show_update_success = false;

if (isset($_SESSION['last_generated_report']) && isset($_SESSION['success_message_time'])) {
    $message_time = $_SESSION['success_message_time'];
    $current_time = time();
    
    if (($current_time - $message_time) <= 5) {
        $show_success_message = true;
        $last_report_id = $_SESSION['last_generated_report'];
        $last_report_sql = "SELECT * FROM audit_reports WHERE id = $last_report_id";
        $last_report_result = $conn->query($last_report_sql);
        if ($last_report_result->num_rows > 0) {
            $last_report = $last_report_result->fetch_assoc();
            $last_report['audit_findings'] = json_decode($last_report['audit_findings'], true);
        }
    } else {
        unset($_SESSION['last_generated_report']);
        unset($_SESSION['success_message_time']);
    }
}

// Check for update success
if (isset($_SESSION['update_success']) && $_SESSION['update_success']) {
    $show_update_success = true;
    if (isset($_SESSION['updated_report_id'])) {
        $updated_report_id = $_SESSION['updated_report_id'];
        $updated_report_sql = "SELECT * FROM audit_reports WHERE id = $updated_report_id";
        $updated_report_result = $conn->query($updated_report_sql);
        if ($updated_report_result->num_rows > 0) {
            $last_report = $updated_report_result->fetch_assoc();
            $last_report['audit_findings'] = json_decode($last_report['audit_findings'], true);
        }
    }
    unset($_SESSION['update_success']);
    unset($_SESSION['updated_report_id']);
}

// Generate dynamic year options
$current_year = date('Y');
$year_options = [];
for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
    $year_options[] = $i;
}
?>

<html>

<head>
    <meta charset="UTF-8">
    <title>Audit Reports | ViaHale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.0/jspdf.plugin.autotable.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { z-index: 1000; }
        
        .reports-table { width: 100%; background: #fff; border-radius: 24px; border: 1px solid #cbd5e1; overflow: hidden; }
        .reports-table th { text-align: left; padding: 20px 24px; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
        .reports-table td { padding: 20px 24px; font-size: 13px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #f8fafc; }
        .report-name { color: #3f36bd; font-weight: 800; cursor: pointer; }
        .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #f1f5f9; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .action-icon:hover { background: #f8fafc; color: #3f36bd; border-color: #3f36bd; }

        /* Modal Redesign */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); z-index: 20002; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-card { background: #fff; border-radius: 32px; width: 480px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15); animation: modalIn 0.3s ease-out; position: relative; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 32px; }
        .input-group { margin-bottom: 24px; }
        .input-label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .modal-select, .modal-input { width: 100%; border: 2px solid #e2e8f0; border-radius: 14px; padding: 14px 20px; font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; transition: all 0.2s; outline: none; }
        .modal-select:focus, .modal-input:focus { border-color: #3f36bd; }
        .modal-actions { display: grid; grid-cols: 2; gap: 16px; margin-top: 40px; display: flex; }
        .btn-modal { flex: 1; padding: 16px; border-radius: 14px; font-size: 14px; font-weight: 800; letter-spacing: 1px; transition: all 0.2s; }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
        .btn-confirm { background: #1e1e2d; color: #fff; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2); }

        /* PIN Modal */
        .pin-input-container { display: flex; gap: 12px; justify-content: center; margin: 32px 0; }
        .pin-digit { width: 48px; height: 56px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; font-size: 24px; font-weight: 800; color: #0f172a; outline: none; }
        .pin-digit:focus { border-color: #3f36bd; }
        .pin-digit.error { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
        #pinErrorMessage { color: #ef4444; font-size: 13px; font-weight: 800; margin-top: 10px; display: none; text-align: center; text-transform: uppercase; letter-spacing: 1px; }

        /* Preview Modal Redesign */
        .preview-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 30000; display: none; align-items: center; justify-content: center; padding: 40px; }
        .preview-modal { background: #fff; border-radius: 24px; width: 95%; height: 90%; max-width: 1200px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalIn 0.3s ease-out; }
        .preview-nav { background: #1e1e2d; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; color: #fff; border-bottom: 1px solid #2d2d3f; }
        .preview-iframe { flex: 1; border: none; background: #fff; width: 100%; }

        /* Finding Badges */
        .badge { padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-resolved { background: #d1fae5; color: #065f46; }

        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        @media print {
            .no-print { display: none !important; }
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
</head>

<body class="bg-[#f8fafc]">

    <?php include('sidebar.php'); ?>
    
    <div class="flex-1 overflow-y-auto">
        <div class="px-10 py-8">
        <header class="mb-10">
            <h1 class="text-3xl font-black text-[#0f172a] flex items-center gap-3">
                <i class="fas fa-file-alt text-purple-600"></i> Audit Reports
            </h1>
            <p class="text-[11px] font-black text-purple-600/70 uppercase tracking-[3px] mt-1 ml-10">Module: Financial Oversight & Compliance</p>
        </header>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-white p-4 rounded-xl border border-gray-200">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" id="searchInput"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent w-80"
                        placeholder="Search audit reports...">
                </div>
            </div>

            <button onclick="openGenerateModal()" class="px-6 py-2 bg-[#1e1e2d] text-white rounded-lg font-bold text-sm hover:bg-black transition-all flex items-center gap-2">
                <i class="fas fa-plus"></i> GENERATE REPORT
            </button>
        </div>

        <?php if ($show_success_message && $last_report): ?>
        <!-- Success Notification -->
        <div id="successMessage" class="mb-6 bg-emerald-50 border border-emerald-200 p-4 rounded-xl flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white">
                    <i class="fas fa-check"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-emerald-900">Report Generated!</h4>
                    <p class="text-xs text-emerald-700">Audit report "<?php echo $last_report['report_title']; ?>" is now available.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="viewProfessionalReport(<?php echo htmlspecialchars(json_encode($last_report), ENT_QUOTES, 'UTF-8'); ?>)" class="px-4 py-2 bg-emerald-500 text-white rounded-lg text-xs font-bold hover:bg-emerald-600 transition-all">View Now</button>
                <button onclick="hideSuccessMessage()" class="p-2 text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($show_update_success && $last_report): ?>
        <!-- Update Notification -->
        <div id="updateSuccessMessage" class="mb-6 bg-blue-50 border border-blue-200 p-4 rounded-xl flex items-center justify-between animate-fade-in">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white">
                    <i class="fas fa-sync"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-blue-900">Report Updated!</h4>
                    <p class="text-xs text-blue-700">Changes to "<?php echo $last_report['report_title']; ?>" have been saved.</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="viewProfessionalReport(<?php echo htmlspecialchars(json_encode($last_report), ENT_QUOTES, 'UTF-8'); ?>)" class="px-4 py-2 bg-blue-500 text-white rounded-lg text-xs font-bold hover:bg-blue-600 transition-all">View Now</button>
                <button onclick="hideUpdateSuccessMessage()" class="p-2 text-blue-400 hover:text-blue-600"><i class="fas fa-times"></i></button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Audit Reports table -->
        <div class="reports-table">
            <table class="w-full">
                <thead>
                    <tr>
                        <th width="15%">Report No</th>
                        <th width="20%">Title</th>
                        <th width="15%">Audit Team</th>
                        <th width="15%">Date Range</th>
                        <th width="15%">Generated</th>
                        <th width="10%">Status</th>
                        <th width="10%" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="auditTableBody">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $findings_data = json_decode($row['audit_findings'], true);
                            $findings_count = is_array($findings_data) ? count($findings_data) : 0;
                            $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr id="report-row-<?php echo $row['id']; ?>" class="hover:bg-slate-50 transition border-b border-slate-100">
                            <td class="font-mono text-[11px] text-slate-400 font-black tracking-tighter"><?php echo $row['report_number']; ?></td>
                            <td>
                                <div class="report-name" onclick='viewProfessionalReport(<?php echo $jsonData; ?>)'>
                                    <?php echo $row['report_title']; ?>
                                </div>
                                <p class="text-[9px] font-black text-purple-600/60 uppercase tracking-[2px] mt-1"><?php echo $row['report_period']; ?></p>
                            </td>
                            <td class="text-slate-600 font-bold text-[13px]"><?php echo $row['audit_team']; ?></td>
                            <td class="text-slate-500 font-medium text-[12px]"><?php echo date('M d', strtotime($row['start_date'])); ?> - <?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                            <td class="text-slate-400 font-bold text-[11px] uppercase tracking-wider"><?php echo date('d M Y', strtotime($row['generated_date'])); ?></td>
                            <td>
                                <span class="badge <?php echo $findings_count > 0 ? 'badge-pending' : 'badge-resolved'; ?>">
                                    <?php echo $findings_count; ?> Issue<?php echo $findings_count != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick='viewProfessionalReport(<?php echo $jsonData; ?>)' class="action-icon hover:bg-purple-100 hover:text-purple-600" title="View"><i class="far fa-eye"></i></button>
                                    <button onclick='editProfessionalReport(<?php echo $jsonData; ?>)' class="action-icon hover:bg-amber-100 hover:text-amber-600" title="Edit"><i class="far fa-edit"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px]">No audit reports found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination UI -->
        <?php if ($total_reports > 0): ?>
        <div class="flex items-center justify-between mt-8 mb-12 px-2">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_reports); ?> of <?php echo $total_reports; ?> Reports
            </p>
            <div class="flex items-center gap-2">
                <!-- Prev Button -->
                <?php if ($p > 1): ?>
                    <a href="audit_reports.php?p=<?php echo $p - 1; ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider flex items-center">
                        <i class="fas fa-chevron-left mr-2 text-[10px]"></i> Prev
                    </a>
                <?php else: ?>
                    <span class="px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[11px] font-black text-slate-300 uppercase tracking-wider cursor-not-allowed flex items-center">
                        <i class="fas fa-chevron-left mr-2 text-[10px]"></i> Prev
                    </span>
                <?php endif; ?>

                <!-- Next Button -->
                <?php if ($p < $total_pages): ?>
                    <a href="audit_reports.php?p=<?php echo $p + 1; ?>" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider flex items-center">
                        Next <i class="fas fa-chevron-right ml-2 text-[10px]"></i>
                    </a>
                <?php else: ?>
                    <span class="px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[11px] font-black text-slate-300 uppercase tracking-wider cursor-not-allowed flex items-center">
                        Next <i class="fas fa-chevron-right ml-2 text-[10px]"></i>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Generate Report Modal -->
    <div id="generateModal" class="modal-overlay">
        <div class="modal-card !w-[560px]">
            <h3 class="modal-title flex items-center gap-3">
                <i class="fas fa-plus-circle text-purple-600"></i> Generate Audit Report
            </h3>
            <form id="generateReportForm" action="audit_reports.php" method="POST">
                <div class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="input-label">Report Title</label>
                        <input type="text" name="report_title" class="modal-input" placeholder="e.g. Q1 Compliance Review" required>
                    </div>
                    <div>
                        <label class="input-label">Report Period</label>
                        <select name="report_period" class="modal-select" required>
                            <?php foreach ($year_options as $year): ?>
                                <option value="Q1 <?php echo $year; ?>">Q1 <?php echo $year; ?></option>
                                <option value="Q2 <?php echo $year; ?>">Q2 <?php echo $year; ?></option>
                                <option value="Q3 <?php echo $year; ?>">Q3 <?php echo $year; ?></option>
                                <option value="Q4 <?php echo $year; ?>">Q4 <?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Audit Team</label>
                        <input type="text" name="audit_team" class="modal-input" value="Verification Team" required>
                    </div>
                    <div>
                        <label class="input-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="modal-input" required>
                    </div>
                    <div>
                        <label class="input-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="modal-input" required>
                    </div>
                    <div class="col-span-2">
                        <label class="input-label">Department / Site Audited</label>
                        <input type="text" name="site_section" class="modal-input" value="Finance HQ" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" onclick="closeGenerateModal()" class="btn-modal btn-cancel">CANCEL</button>
                    <button type="submit" name="generate_report" class="btn-modal btn-confirm">GENERATE</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Report Modal -->
    <div id="professionalReportModal" class="preview-overlay">
        <div class="preview-modal">
            <div class="preview-nav">
                <span class="text-sm font-black uppercase tracking-[2px] opacity-70"><i class="fas fa-file-invoice mr-3 text-purple-400"></i>Audit Preview System</span>
                <button onclick="closeProfessionalReportModal()" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg font-black text-xs hover:bg-red-500 hover:text-white transition uppercase">Close Viewer</button>
            </div>
            <div class="flex-1 overflow-y-auto bg-slate-50 p-10">
                <div id="professionalReportContent" class="max-w-4xl mx-auto">
                    <!-- Dynamic Content -->
                </div>
            </div>
            <div class="bg-white p-6 border-t border-slate-200 flex justify-center gap-4">
                <button onclick="printProfessionalReport()" class="px-6 py-2 bg-slate-100 text-slate-600 rounded-lg font-bold text-sm hover:bg-slate-200 transition-all flex items-center gap-2">
                    <i class="fas fa-print"></i> PRINT
                </button>
                <button onclick="exportCurrentReportToPDF()" class="px-6 py-2 bg-red-500 text-white rounded-lg font-bold text-sm hover:bg-red-600 transition-all flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i> EXPORT PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
    <div id="editReportModal" class="preview-overlay">
        <div class="preview-modal">
            <div class="preview-nav">
                <span class="text-sm font-black uppercase tracking-[2px] opacity-70"><i class="fas fa-edit mr-3 text-yellow-400"></i>Audit Editor</span>
                <button onclick="closeEditReportModal()" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg font-black text-xs hover:bg-red-500 hover:text-white transition uppercase">Discard Changes</button>
            </div>
            <div class="flex-1 overflow-y-auto bg-slate-50 p-10">
                <form id="editReportForm" action="audit_reports.php" method="POST" class="max-w-4xl mx-auto">
                    <input type="hidden" id="edit_report_id" name="report_id">
                    <div id="editReportContent">
                        <!-- Dynamic Editable Content -->
                    </div>
                </form>
            </div>
            <div class="bg-white p-6 border-t border-slate-200 flex justify-center gap-4">
                <button type="button" onclick="submitEditReport()" class="px-10 py-3 bg-[#1e1e2d] text-white rounded-xl font-bold text-sm hover:bg-black transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> SAVE CHANGES
                </button>
            </div>
        </div>
    </div>

    <!-- PIN Modal -->
    <div id="pinModal" class="modal-overlay">
        <div class="modal-card">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-purple-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Access Required</h3>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-2">Enter your secure PIN to proceed</p>
            </div>
            
            <div class="pin-input-container" id="pin-container">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="password" maxlength="1" class="pin-digit" inputmode="numeric">
            </div>

            <div id="pinErrorMessage">INCORRECT PIN. PLEASE TRY AGAIN.</div>

            <div class="modal-actions">
                <button type="button" onclick="closePinModal()" class="btn-modal btn-cancel">ABORT</button>
                <button type="button" onclick="verifyPinAndProceed()" class="btn-modal btn-confirm">VERIFY</button>
            </div>
        </div>
    </div>

    <!-- OTP Modal (Supervisor Authorization) -->
    <div id="otpModal" class="modal-overlay">
        <div class="modal-card">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-shield text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Auth Required</h3>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mt-2">Supervisor approval needed for export</p>
            </div>
            
            <div class="pin-input-container" id="otp-container">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
                <input type="text" maxlength="1" class="pin-digit" inputmode="numeric">
            </div>

            <div id="otp-error" class="hidden text-red-500 text-center font-bold text-[10px] uppercase tracking-widest mt-4">Invalid OTP Code</div>

            <div class="modal-actions !grid-cols-1">
                <button type="button" onclick="verifyOtpAndExport()" class="btn-modal btn-confirm !bg-red-600">AUTHORIZE EXPORT</button>
                <button type="button" onclick="closeOtpModal()" class="btn-modal btn-cancel">CANCEL</button>
            </div>
            <button onclick="resendOtp()" class="w-full text-center text-[10px] font-black text-purple-600 uppercase tracking-widest mt-6 hover:underline">Resend Authorization Code</button>
        </div>
    </div>

    <script>
        // Store current report data for PDF export and Modal handling
        let currentReportData = null;
        let pendingAction = null;
        let pendingActionData = null;

        // --- AUTHENTICATION SHIELD LOGIC ---
        // We override the primary actions to intercept with PIN/OTP protection
        function viewProfessionalReport(data) {
            pendingAction = 'view';
            pendingActionData = data;
            openPinModal();
        }

        function editProfessionalReport(data) {
            pendingAction = 'edit';
            pendingActionData = data;
            openPinModal();
        }

        // The actual UI opening functions (called after successful verification)
        function executeViewAction(data) {
            currentReportData = data;
            const reportContent = document.getElementById('professionalReportContent');
            const findings = typeof data.audit_findings === 'string' ? JSON.parse(data.audit_findings) : data.audit_findings;
            
            let findingsHtml = '';
            if (findings && findings.length > 0) {
                findings.forEach((finding, index) => {
                    findingsHtml += `
                        <tr class="border-b border-slate-200 hover:bg-slate-50 transition">
                            <td class="p-4 text-[13px] font-bold text-slate-700">${finding.element}</td>
                            <td class="p-4 text-[13px] text-slate-600">${finding.compliance}</td>
                            <td class="p-4 text-[13px] text-slate-600 italic font-medium">${finding.corrective_action}</td>
                            <td class="p-4 whitespace-nowrap">
                                <span class="badge ${getStatusBadgeClass(finding.status)}">${getStatusText(finding.status)}</span>
                            </td>
                        </tr>`;
                });
            } else {
                findingsHtml = `<tr><td colspan="4" class="p-10 text-center text-slate-400 font-bold uppercase text-[10px] tracking-widest">No critical findings registered</td></tr>`;
            }
            
            reportContent.innerHTML = `
                <div class="bg-white p-16 rounded-[32px] shadow-2xl border border-slate-200 relative overflow-hidden" id="pdfReportContent">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-slate-50 rounded-full -mr-32 -mt-32 opacity-50"></div>
                    
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-16 relative z-10">
                        <div>
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-12 h-12 bg-purple-600 rounded-xl flex items-center justify-center text-white shadow-xl">
                                    <i class="fas fa-shield-alt text-xl"></i>
                                </div>
                                <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">ViaHale <span class="text-purple-600">Audit</span></h1>
                            </div>
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[2px]">Ref Handle: ${data.report_number}</p>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[2px]">Batch ID: AUD-LVL-4</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h2 class="text-4xl font-black text-slate-900 mb-2">AUDIT REPORT</h2>
                            <p class="text-[11px] font-black text-purple-600 uppercase tracking-[3px] opacity-70">CONFIDENTIAL DOCUMENT</p>
                        </div>
                    </div>
                    
                    <!-- Metadata Grid -->
                    <div class="grid grid-cols-2 gap-8 mb-16 relative z-10">
                        <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">Engagement Details</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between border-b border-slate-200 pb-2">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase">Audit Team</span>
                                    <span class="text-[11px] font-black text-slate-900 uppercase">${data.audit_team}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200 pb-2">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase">Site/Section</span>
                                    <span class="text-[11px] font-black text-slate-900 uppercase">${data.site_section}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-200 pb-2">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase">Report Period</span>
                                    <span class="text-[11px] font-black text-slate-900 uppercase">${data.report_period}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-[11px] font-bold text-slate-500 uppercase">Generation Date</span>
                                    <span class="text-[11px] font-black text-slate-900 uppercase">${new Date(data.generated_date).toLocaleDateString()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-purple-600 p-6 rounded-2xl shadow-xl shadow-purple-100 flex flex-col justify-center">
                            <h3 class="text-[10px] font-black text-white/70 uppercase tracking-widest mb-4">System Status</h3>
                            <div class="flex items-end justify-between">
                                <div>
                                    <p class="text-4xl font-black text-white">${findings.length}</p>
                                    <p class="text-[10px] font-black text-white/70 uppercase tracking-widest">Findings Resolved: 0%</p>
                                </div>
                                <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center text-white">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Findings Table -->
                    <div class="mb-16 relative z-10">
                        <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[4px] mb-6 flex items-center gap-4">
                            Detailed Audit Findings <div class="flex-1 h-[1px] bg-slate-100"></div>
                        </h3>
                        <div class="border border-slate-200 rounded-2xl overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="p-4 text-[11px] font-black text-slate-400 uppercase text-left tracking-widest">Element</th>
                                        <th class="p-4 text-[11px] font-black text-slate-400 uppercase text-left tracking-widest">Compliance Issue</th>
                                        <th class="p-4 text-[11px] font-black text-slate-400 uppercase text-left tracking-widest">Corrective Action</th>
                                        <th class="p-4 text-[11px] font-black text-slate-400 uppercase text-left tracking-widest">Status</th>
                                    </tr>
                                </thead>
                                <tbody>${findingsHtml}</tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Authorization -->
                    <div class="flex justify-between items-end relative z-10">
                        <div>
                            <div class="mb-6">
                                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Lead Auditor</p>
                                <div class="w-48 h-[1px] bg-slate-300 mb-2"></div>
                                <p class="text-[13px] font-black text-slate-900">MARY D. BATUMBAKAL</p>
                                <p class="text-[10px] font-bold text-slate-400">Head of Financial Compliance</p>
                            </div>
                        </div>
                        <div class="text-right">
                             <p class="text-[10px] font-black text-slate-300 uppercase leading-relaxed">Generated safely by ViaHale Financial Ecosystem<br>Secure Document ID: ${Math.random().toString(36).substring(2, 10).toUpperCase()}</p>
                        </div>
                    </div>
                </div>`;
            document.getElementById('professionalReportModal').style.display = 'flex';
        }

        function executeEditAction(data) {
            const editContent = document.getElementById('editReportContent');
            const findings = typeof data.audit_findings === 'string' ? JSON.parse(data.audit_findings) : data.audit_findings;
            document.getElementById('edit_report_id').value = data.id;
            
            let findingsHtml = '';
            findings.forEach((finding, index) => {
                findingsHtml += `
                    <div class="bg-white p-6 rounded-2xl border border-slate-200 mb-4 shadow-sm">
                        <div class="flex justify-between items-center mb-6">
                            <h4 class="text-[11px] font-black text-slate-400 uppercase tracking-widest">Finding #${index + 1}</h4>
                            <select name="findings[${index}][status]" class="modal-select !w-auto !py-1 !px-4 !text-[11px]">
                                <option value="pending" ${finding.status === 'pending' ? 'selected' : ''}>Pending Review</option>
                                <option value="on_process" ${finding.status === 'on_process' ? 'selected' : ''}>Under Process</option>
                                <option value="done" ${finding.status === 'done' ? 'selected' : ''}>Resolved</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="input-label">Audit Element</label>
                                <input type="text" name="findings[${index}][element]" value="${finding.element}" class="modal-input" required>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="input-label">Compliance Issue</label>
                                    <textarea name="findings[${index}][compliance]" class="modal-input !h-32" required>${finding.compliance}</textarea>
                                </div>
                                <div>
                                    <label class="input-label">Recommended Corrective Action</label>
                                    <textarea name="findings[${index}][corrective_action]" class="modal-input !h-32" required>${finding.corrective_action}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });

            editContent.innerHTML = `
                <div class="mb-10 text-center">
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight mb-2">Edit Audit Findings</h2>
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Report Ref: ${data.report_number}</p>
                </div>
                <div class="grid grid-cols-2 gap-6 mb-10">
                    <div class="col-span-2">
                        <label class="input-label">Report Title</label>
                        <input type="text" name="report_title" value="${data.report_title}" class="modal-input" required>
                    </div>
                    <div>
                        <label class="input-label">Audit Team</label>
                        <input type="text" name="audit_team" value="${data.audit_team}" class="modal-input" required>
                    </div>
                     <div>
                        <label class="input-label">Site/Section</label>
                        <input type="text" name="site_section" value="${data.site_section}" class="modal-input" required>
                    </div>
                </div>
                <div class="mb-10">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[4px] mb-6 flex items-center gap-4">
                        Findings Editor <div class="flex-1 h-[1px] bg-slate-200"></div>
                    </h3>
                    ${findingsHtml}
                </div>`;
            document.getElementById('editReportModal').style.display = 'flex';
        }

        // --- HELPER LOGIC ---
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'done': return 'badge-resolved';
                case 'on_process': return 'bg-blue-100 text-blue-700';
                case 'pending': return 'badge-pending';
                default: return 'bg-slate-100 text-slate-600';
            }
        }

        function getStatusText(status) {
            switch(status) {
                case 'pending': return 'Pending Review';
                case 'on_process': return 'In Process';
                case 'done': return 'Resolved';
                default: return status.toUpperCase().replace('_', ' ');
            }
        }

        function getNextAuditDate(period) {
            const today = new Date();
            let nextDate = new Date(today);
            if (period.includes('Q1')) nextDate.setMonth(3);
            else if (period.includes('Q2')) nextDate.setMonth(6);
            else if (period.includes('Q3')) nextDate.setMonth(9);
            else if (period.includes('Q4')) { nextDate.setFullYear(today.getFullYear() + 1); nextDate.setMonth(0); }
            else nextDate.setMonth(today.getMonth() + 3);
            return nextDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }

        // --- FILTER & SEARCH LOGIC ---
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.toLowerCase();
                    const rows = document.querySelectorAll('#auditTableBody tr');
                    rows.forEach(row => {
                        const text = row.innerText.toLowerCase();
                        row.style.display = text.includes(term) ? '' : 'none';
                    });
                });
            }
        });

        // --- MODAL CONTROL LOGIC ---
        function openGenerateModal() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
            document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
            document.getElementById('generateModal').style.display = 'flex';
        }

        function closeGenerateModal() {
            document.getElementById('generateModal').style.display = 'none';
        }

        function closeProfessionalReportModal() {
            document.getElementById('professionalReportModal').style.display = 'none';
        }

        function closeEditReportModal() {
            document.getElementById('editReportModal').style.display = 'none';
        }

        // Success message control
        function hideSuccessMessage() {
            const el = document.getElementById('successMessage');
            if(el) {
                el.classList.add('fade-out');
                setTimeout(() => {
                    el.style.display = 'none';
                    fetch('clear_success_message.php')
                        .then(response => response.text())
                        .then(data => {
                            console.log('Success message cleared');
                        });
                }, 500);
            }
        }

        function hideUpdateSuccessMessage() {
            const el = document.getElementById('updateSuccessMessage');
            if(el) {
                el.classList.add('fade-out');
                setTimeout(() => {
                    el.style.display = 'none';
                }, 500);
            }
        }

        // Auto-hide messages
        <?php if ($show_success_message || $show_update_success): ?>
        setTimeout(() => { hideSuccessMessage(); hideUpdateSuccessMessage(); }, 5000);
        <?php endif; ?>

        // --- PIN & OTP SYSTEM ---
        function setupPinInputs(containerId, verifyCallback) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const digits = container.querySelectorAll('.pin-digit');
            
            digits.forEach((d, i) => {
                d.addEventListener('input', e => {
                    if(e.target.value && i < digits.length - 1) digits[i+1].focus();
                    else if(e.target.value && i === digits.length - 1) verifyCallback();
                });
                d.addEventListener('keydown', e => {
                    if(e.key === 'Backspace' && !e.target.value && i > 0) digits[i-1].focus();
                });
                d.addEventListener('paste', e => {
                    e.preventDefault();
                    const data = e.clipboardData.getData('text').replace(/\D/g, '').split('');
                    data.forEach((val, idx) => { if(digits[idx]) digits[idx].value = val; });
                    if(data.length >= digits.length) verifyCallback();
                    else if(digits[data.length]) digits[data.length].focus();
                });
            });
        }

        setupPinInputs('pin-container', verifyPinAndProceed);
        setupPinInputs('otp-container', verifyOtpAndExport);

        function openPinModal() {
            document.getElementById('pinModal').style.display = 'flex';
            resetPinInputs();
            setTimeout(() => document.querySelector('#pin-container .pin-digit').focus(), 100);
        }

        function closePinModal() {
            document.getElementById('pinModal').style.display = 'none';
            pendingAction = null;
            pendingActionData = null;
        }

        function resetPinInputs() {
            document.querySelectorAll('#pin-container .pin-digit').forEach(d => { d.value = ''; d.classList.remove('error'); });
            document.getElementById('pinErrorMessage').style.display = 'none';
        }

        async function verifyPinAndProceed() {
            const pin = Array.from(document.querySelectorAll('#pin-container .pin-digit')).map(d => d.value).join('');
            if(pin.length < 6) return;

            try {
                const response = await fetch('api/verify_pin.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ pin })
                });
                const res = await response.json();
                if(res.success) {
                    const action = pendingAction;
                    const data = pendingActionData;
                    closePinModal();
                    if(action === 'view') executeViewAction(data);
                    else if(action === 'edit') executeEditAction(data);
                } else {
                    document.getElementById('pinErrorMessage').style.display = 'block';
                    document.querySelector('#pinModal .modal-card').classList.add('shake');
                    setTimeout(() => document.querySelector('#pinModal .modal-card').classList.remove('shake'), 400);
                    resetPinInputs();
                }
            } catch(e) { console.error(e); }
        }

        function openOtpModal() {
            if (!currentReportData) return alert('Data missing');
            document.getElementById('otpModal').style.display = 'flex';
            resetOtpInputs();
            fetch('api/send_otp.php').then(r => r.json()).then(d => {
                if(d.success) setTimeout(() => document.querySelector('#otp-container .pin-digit').focus(), 100);
            });
        }

        function closeOtpModal() {
            document.getElementById('otpModal').style.display = 'none';
        }

        function resetOtpInputs() {
            document.querySelectorAll('#otp-container .pin-digit').forEach(d => { d.value = ''; });
            document.getElementById('otp-error').classList.add('hidden');
        }

        async function verifyOtpAndExport() {
            const otp_code = Array.from(document.querySelectorAll('#otp-container .pin-digit')).map(f => f.value).join('');
            if (otp_code.length < 6) return;

            try {
                const response = await fetch('api/verify_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ otp_code })
                });
                const data = await response.json();
                if (data.success) {
                    closeOtpModal();
                    executePDFExport();
                } else {
                    document.getElementById('otp-error').classList.remove('hidden');
                    document.querySelector('#otpModal .modal-card').classList.add('shake');
                    setTimeout(() => document.querySelector('#otpModal .modal-card').classList.remove('shake'), 400);
                    resetOtpInputs();
                }
            } catch (e) { console.error(e); }
        }

        function resendOtp() {
            fetch('api/send_otp.php').then(r => r.json()).then(d => {
                if(d.success) alert('A new authorization code has been dispatched.');
            });
        }

        // --- PDF EXPORT LOGIC ---
        function exportCurrentReportToPDF() {
            openOtpModal();
        }

        function executePDFExport() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'mm', 'a4');
            const pageWidth = doc.internal.pageSize.getWidth();
            const findings = typeof currentReportData.audit_findings === 'string' ? JSON.parse(currentReportData.audit_findings) : currentReportData.audit_findings;
            
            doc.setFontSize(22);
            doc.setTextColor(75, 0, 130);
            doc.text("VIAHALE FINANCIAL AUDIT REPORT", pageWidth/2, 20, {align: 'center'});
            
            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`Report Number: ${currentReportData.report_number}`, 20, 35);
            doc.text(`Audit Team: ${currentReportData.audit_team}`, 20, 42);
            doc.text(`Generated Date: ${currentReportData.generated_date}`, 20, 49);
            
            const tableData = findings.map(f => [f.element, f.compliance, f.corrective_action, getStatusText(f.status)]);
            doc.autoTable({
                startY: 60,
                head: [['Element', 'Compliance Issue', 'Action', 'Status']],
                body: tableData,
                theme: 'grid',
                headStyles: { fillColor: [75, 0, 130] }
            });
            
            doc.save(`Audit_${currentReportData.report_number}.pdf`);
        }

        function printProfessionalReport() {
            const reportContent = document.getElementById('professionalReportContent').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Professional Audit Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .professional-audit-report { max-width: 800px; margin: 0 auto; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .text-center { text-align: center; }
                        .mb-6 { margin-bottom: 24px; }
                    </style>
                </head>
                <body>
                    ${reportContent}
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        async function submitEditReport() {
            const form = document.getElementById('editReportForm');
            const formData = new FormData(form);
            const saveBtn = event.currentTarget;
            const originalBtnContent = saveBtn.innerHTML;
            
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SAVING...';
            saveBtn.disabled = true;

            try {
                const response = await fetch('api/update_audit_report.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    const data = res.data;
                    const findings = JSON.parse(data.audit_findings);
                    const findingsCount = findings.length;
                    
                    // Update table row content
                    const row = document.getElementById(`report-row-${data.id}`);
                    if (row) {
                        // Update title cell
                        const titleCell = row.querySelector('.report-name');
                        if(titleCell) {
                            titleCell.textContent = data.report_title;
                            titleCell.onclick = () => viewProfessionalReport(data);
                        }
                        
                        // Update team cell (assuming 3rd cell, let's be more specific if possible)
                        const cells = row.cells;
                        cells[2].textContent = data.audit_team;
                        
                        // Update status badge
                        const badge = row.querySelector('.badge');
                        if (badge) {
                            badge.className = `badge ${findingsCount > 0 ? 'badge-pending' : 'badge-resolved'}`;
                            badge.textContent = `${findingsCount} Issue${findingsCount !== 1 ? 's' : ''}`;
                        }
                        
                        // Update action buttons data
                        const editBtn = row.querySelector('button[title="Edit"]');
                        if (editBtn) editBtn.onclick = () => editProfessionalReport(data);
                        
                        const viewBtn = row.querySelector('button[title="View"]');
                        if (viewBtn) viewBtn.onclick = () => viewProfessionalReport(data);
                    }
                    
                    closeEditReportModal();
                    
                    // Show a temporary success toast if you want, or just let the UI change speak for itself
                    alert('Changes saved successfully!');
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (e) {
                console.error(e);
                alert('An error occurred while saving.');
            } finally {
                saveBtn.innerHTML = originalBtnContent;
                saveBtn.disabled = false;
            }
        }

        // --- PRIVACY PROTECTION ---
        (function() {
            window.addEventListener('blur', () => {
                document.querySelectorAll('.modal-overlay, .preview-overlay').forEach(el => {
                    if(el.style.display === 'flex') el.querySelector('.bg-white')?.classList.add('privacy-blur');
                });
            });
            window.addEventListener('focus', () => {
                document.querySelectorAll('.privacy-blur').forEach(el => el.classList.remove('privacy-blur'));
            });
        })();
    </script>
    </div>
    </main>
</body>
</html>