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

// AJAX handler for journal entries
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_entries'])) {
    // Clear any previous output to ensure JSON only
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $entryDate = isset($_GET['entryDate']) ? $conn->real_escape_string($_GET['entryDate']) : '';
    $dateFrom = isset($_GET['dateFrom']) ? $conn->real_escape_string($_GET['dateFrom']) : '';
    $dateTo = isset($_GET['dateTo']) ? $conn->real_escape_string($_GET['dateTo']) : '';
    $filterMonth = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : '';
    $filterYear = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';
    $refType = isset($_GET['refType']) ? $conn->real_escape_string($_GET['refType']) : 'all';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $rowsPerPage = 10;
    $offset = ($page - 1) * $rowsPerPage;

    $where = "WHERE 1=1";
    if ($search) {
        $where .= " AND (
            journal_number LIKE '%$search%' OR 
            reference_id LIKE '%$search%' OR
            description LIKE '%$search%' OR
            total_debit LIKE '%$search%' OR
            total_credit LIKE '%$search%'
        )";
    }
    if ($entryDate) {
        $where .= " AND DATE(transaction_date) = '$entryDate'";
    } else {
        if ($dateFrom) {
            $where .= " AND DATE(transaction_date) >= '$dateFrom'";
        }
        if ($dateTo) {
            $where .= " AND DATE(transaction_date) <= '$dateTo'";
        }
    }
    
    if ($filterMonth && !$dateFrom && !$dateTo) {
        $where .= " AND MONTH(transaction_date) = '$filterMonth'";
    }
    if ($filterYear && !$dateFrom && !$dateTo) {
        $where .= " AND YEAR(transaction_date) = '$filterYear'";
    }
    if ($refType !== 'all') {
        $where .= " AND reference_type = '$refType'";
    }
    
    $count_sql = "SELECT COUNT(*) as total FROM journal_entries $where";
    $result_count = $conn->query($count_sql);
    $total = $result_count->fetch_assoc()['total'];
    
    $limit_sql = isset($_GET['all']) ? "" : "LIMIT $offset, $rowsPerPage";
    $sql = "SELECT * FROM journal_entries $where ORDER BY transaction_date DESC, id DESC $limit_sql";
    $result = $conn->query($sql);
    $rows = [];
    while($row = $result->fetch_assoc()) {
        // Fetch lines for this entry
        $je_id = $row['id'];
        $line_sql = "SELECT * FROM journal_entry_lines WHERE journal_entry_id = $je_id ORDER BY line_number ASC";
        $line_res = $conn->query($line_sql);
        $row['lines'] = [];
        while($line = $line_res->fetch_assoc()) {
            $row['lines'][] = $line;
        }
        $rows[] = $row;
    }

    echo json_encode([
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $rowsPerPage)
    ]);
    exit();
}

// AJAX handler for journal entry lines (details)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_lines'])) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    $je_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($je_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit();
    }
    
    try {
        $sql = "SELECT * FROM journal_entry_lines WHERE journal_entry_id = ? ORDER BY line_number ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $je_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $lines = [];
        while($row = $result->fetch_assoc()) $lines[] = $row;
        
        echo json_encode(['success' => true, 'data' => $lines]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// AJAX handler for Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import_journal') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit();
    }

    $file = $_FILES['csv']['tmp_name'];
    $handle = fopen($file, "r");
    
    // Skip header
    fgetcsv($handle);
    
    $imported = 0;
    $errors = 0;
    $currentJE = null;
    $conn->begin_transaction();

    try {
        require_once 'includes/accounting_functions.php';
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Expected columns: Date (0), RefType (1), RefID (2), Desc (3), Account (4), Debit (5), Credit (6), Dept (7)
            if (count($data) < 7) continue;

            $date = $data[0];
            $refType = $data[1];
            $refID = $data[2];
            $desc = $data[3];
            $account = $data[4];
            $debit = floatval($data[5]);
            $credit = floatval($data[6]);
            $dept = $data[7] ?? 'General';

            // Check if this line belongs to the same JE as the previous line
            $jeKey = $date . $refType . $refID;
            
            if (!$currentJE || $currentJE['key'] !== $jeKey) {
                // Create new JE Header
                $journalNumber = generateJENumber($conn);
                $sql = "INSERT INTO journal_entries (journal_number, transaction_date, reference_type, reference_id, description, status, created_by, posted_at) 
                        VALUES (?, ?, ?, ?, ?, 'posted', 'System', NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $journalNumber, $date, $refType, $refID, $desc);
                $stmt->execute();
                
                $jeId = $conn->insert_id;
                $currentJE = [
                    'key' => $jeKey,
                    'id' => $jeId,
                    'number' => $journalNumber,
                    'totalDebit' => 0,
                    'totalCredit' => 0,
                    'lines' => 0
                ];
                $imported++;
            }

            // Get GL Account
            $gl = getExpenseGLAccount($conn, $account);
            if (!$gl) {
                // Fallback attempt to get any account by code or name if not expense
                $sql_gl = "SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE code = ? OR name = ? LIMIT 1";
                $stmt_gl = $conn->prepare($sql_gl);
                $stmt_gl->bind_param("ss", $account, $account);
                $stmt_gl->execute();
                $res_gl = $stmt_gl->get_result();
                $gl = $res_gl->fetch_assoc();
            }

            if ($gl) {
                // Create Line
                $lineNum = ++$currentJE['lines'];
                $sqlLine = "INSERT INTO journal_entry_lines (journal_entry_id, line_number, gl_account_id, gl_account_code, gl_account_name, account_type, debit_amount, credit_amount, description, department) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtLine = $conn->prepare($sqlLine);
                $stmtLine->bind_param("iiisssddss", $currentJE['id'], $lineNum, $gl['id'], $gl['code'], $gl['name'], $gl['type'], $debit, $credit, $desc, $dept);
                $stmtLine->execute();

                // Post to General Ledger
                $sqlGL = "INSERT INTO general_ledger (gl_account_id, gl_account_code, gl_account_name, account_type, transaction_date, journal_entry_id, reference_id, reference_type, original_reference, description, debit_amount, credit_amount, department) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmtGL = $conn->prepare($sqlGL);
                $stmtGL->bind_param("issssissssdds", $gl['id'], $gl['code'], $gl['name'], $gl['type'], $date, $currentJE['id'], $refID, $refType, $currentJE['number'], $desc, $debit, $credit, $dept);
                $stmtGL->execute();

                $currentJE['totalDebit'] += $debit;
                $currentJE['totalCredit'] += $credit;

                // Update Header totals after each line
                $sqlUpd = "UPDATE journal_entries SET total_debit = ?, total_credit = ? WHERE id = ?";
                $stmtUpd = $conn->prepare($sqlUpd);
                $stmtUpd->bind_param("ddi", $currentJE['totalDebit'], $currentJE['totalCredit'], $currentJE['id']);
                $stmtUpd->execute();
                
                // Update Account Balances
                updateGLAccountBalance($conn, $gl['id'], $debit > 0 ? $debit : $credit, $debit > 0 ? 'debit' : 'credit');
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Successfully imported $imported journal entries."]);
    } catch (Throwable $e) {
        if ($conn->connect_errno == 0 && $conn->ping()) {
            $conn->rollback();
        }
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Import Error: ' . $e->getMessage()]);
    }
    
    fclose($handle);
    exit();
}
?>
<?php
// Define this to prevent sidebar.php from outputting full HTML structure if we want more control,
// but actually most pages here expect sidebar.php to handle it.
// To keep it consistent with the project's "double body" style but make it less broken, 
// we will just remove the outer wrapping tags in THIS file since sidebar.php provides them.
?>
$pageTitle = 'Journal Entries';
$pageIcon = 'logo.png';
include('sidebar.php'); 
?>
    <!-- External Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .gradient-violet { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .status-badge { padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em; }
        .status-posted { background-color: #ecfdf5; color: #059669; border: 1px solid #10b981; }
        .status-draft { background-color: #fffbeb; color: #d97706; border: 1px solid #f59e0b; }
        .status-reversed { background-color: #fef2f2; color: #dc2626; border: 1px solid #ef4444; }
        
        /* Modal Backdrop */
        .modal-backdrop {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px);
            z-index: 20000; opacity: 0; pointer-events: none; transition: all 0.3s ease;
        }
        .modal-backdrop.show { opacity: 1; pointer-events: auto; }
        
        /* Modal Box */
        .modal-box {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -45%);
            z-index: 20001; opacity: 0; pointer-events: none; width: 95%; max-width: 800px;
            background: white; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            max-height: 85vh; overflow-y: auto; transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .modal-box.show { opacity: 1; pointer-events: auto; transform: translate(-50%, -50%); }
        
        .table-header-custom { background-color: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .hover-row:hover { background-color: #f5f3ff; transition: all 0.2s ease; transform: scale(1.002); }
        .premium-card { border: 1px solid #ddd6fe; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; }
        .premium-card:hover { border-color: #8b5cf6; box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.1); }
        .premium-input { border: 1px solid #e2e8f0; transition: all 0.2s ease; }
        .premium-input:focus { border-color: #8b5cf6; box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.1); }
        
        @underline { text-decoration: underline; }
        .je-group-border { border-top: 2px solid #e2e8f0 !important; }
        .account-line { display: flex; align-items: center; padding: 4px 0; }
        .credit-indent { padding-left: 2.5rem; }
        .desc-bar { 
            background-color: #f1f5f9; 
            border-left: 4px solid #8b5cf6; 
            padding: 10px 18px; 
            font-style: italic; 
            color: #475569; 
            font-size: 13px;
        }
        .je-meta { font-size: 12px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

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
    <div class="px-6 py-6">
        <!-- Breadcrumb & Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Journal Entries</h1>
                <p class="text-gray-600 mt-1">Manage and audit your generalized ledger recordings.</p>
            </div>
            <div class="text-sm text-gray-500">
                <a href="dashboard_admin.php" class="text-gray-500 hover:text-purple-600">Home</a>
                /
                <a class="text-gray-500">General Ledger</a>
                /
                <a href="journal_entry.php" class="text-purple-600 hover:text-purple-600 font-medium">Journal Entries</a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="px-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Entries</p>
                        <p class="text-2xl font-bold text-gray-800" id="totalEntriesStat">0</p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-100">
                        <i class="fas fa-file-invoice text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Posted Entries</p>
                        <p class="text-2xl font-bold text-green-600" id="postedEntriesStat">0</p>
                    </div>
                    <div class="p-3 rounded-full bg-green-100">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Draft Entries</p>
                        <p class="text-2xl font-bold text-amber-600" id="draftEntriesStat">0</p>
                    </div>
                    <div class="p-3 rounded-full bg-amber-100">
                        <i class="fas fa-edit text-amber-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Unbalanced</p>
                        <p class="text-2xl font-bold text-red-600" id="unbalancedStat">0</p>
                    </div>
                    <div class="p-3 rounded-full bg-red-100">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="flex-1 bg-white p-6 h-full w-full">
            <div class="w-full">
    <div class="px-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <!-- Header Section -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center space-x-4">
                        <div class="p-2 rounded-lg bg-purple-100">
                            <i class="fas fa-book text-purple-600 font-bold"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Journal Records</h2>
                            <p class="text-sm text-gray-600">Audit and manage ledger entries</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <input type="text" id="searchInput" class="pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm w-64 shadow-sm" placeholder="Search entries...">
                            <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                        </div>
                        
                        <!-- Unified Filters Button -->
                        <div class="relative">
                            <button id="filterButton" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 flex items-center gap-2 shadow-sm transition-all active:scale-95">
                                <i class="fas fa-filter text-purple-500"></i>
                                Filters
                            </button>
                            <!-- Filter Menu Dropdown -->
                            <div id="filterMenu" class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 p-6 z-50 hidden">
                                <div class="space-y-5">
                                    <!-- Filter Mode Toggle -->
                                    <div class="flex p-1 bg-gray-100 rounded-xl">
                                        <button onclick="setFilterMode('standard')" id="modeStandard" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all bg-white shadow-sm text-purple-600">Standard</button>
                                        <button onclick="setFilterMode('custom')" id="modeCustom" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest rounded-lg transition-all text-gray-500 hover:text-gray-700">Custom Range</button>
                                    </div>

                                    <!-- Standard Mode (Month/Year) -->
                                    <div id="standardFilters" class="space-y-4">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Month</label>
                                                <select id="filterMonth" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                                    <option value="">All Months</option>
                                                    <?php
                                                    $m_list = [1=>"Jan", 2=>"Feb", 3=>"Mar", 4=>"Apr", 5=>"May", 6=>"Jun", 7=>"Jul", 8=>"Aug", 9=>"Sep", 10=>"Oct", 11=>"Nov", 12=>"Dec"];
                                                    foreach ($m_list as $n => $m) echo "<option value='$n'>$m</option>";
                                                    ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Year</label>
                                                <select id="filterYear" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                                    <option value="">All Years</option>
                                                    <?php
                                                    $curr = date('Y');
                                                    for ($y = $curr; $y >= $curr - 5; $y--) echo "<option value='$y'>$y</option>";
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Custom Mode (Date Range) -->
                                    <div id="customFilters" class="space-y-4 hidden">
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Start Date</label>
                                                <input type="date" id="filterDateFrom" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">End Date</label>
                                                <input type="date" id="filterDateTo" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Common Reference Filter -->
                                    <div>
                                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Source Type</label>
                                        <select id="refTypeFilter" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-purple-500 outline-none">
                                            <option value="all">Reference: All</option>
                                            <option value="vendor_invoice">Vendor</option>
                                            <option value="reimbursement">Reimbursement</option>
                                            <option value="pr">Payroll</option>
                                            <option value="D">Driver</option>
                                        </select>
                                    </div>

                                    <div class="flex gap-2 pt-2">
                                        <button onclick="resetFilters()" class="flex-1 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600 transition-all">Reset All</button>
                                        <button onclick="applyFilters()" class="flex-[2] py-3 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-gray-200 hover:bg-slate-800 transition-all active:scale-95">Apply Filters</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Dropdown -->
                        <div class="relative">
                            <button id="exportDropdownBtn" class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 flex items-center gap-2 shadow-sm transition-all active:scale-95">
                                <i class="fas fa-download text-emerald-500"></i>
                                Export
                            </button>
                            <div id="exportMenu" class="absolute right-0 mt-3 w-48 bg-white rounded-2xl shadow-2xl border border-gray-100 p-2 z-50 hidden">
                                <button onclick="exportData('pdf')" class="w-full px-4 py-3 text-left text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                    <i class="fas fa-file-pdf text-rose-500 text-lg"></i> Export as PDF
                                </button>
                                <button onclick="exportData('excel')" class="w-full px-4 py-3 text-left text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                    <i class="fas fa-file-excel text-emerald-500 text-lg"></i> Export as Excel
                                </button>
                                <button onclick="exportData('csv')" class="w-full px-4 py-3 text-left text-xs font-bold text-gray-600 hover:bg-gray-50 rounded-xl transition-all flex items-center gap-3">
                                    <i class="fas fa-file-csv text-blue-500 text-lg"></i> Export as CSV
                                </button>
                            </div>
                        </div>

                        <button onclick="openImportModal()" class="gradient-bg text-white px-6 py-2.5 rounded-xl flex items-center gap-2 hover:opacity-90 transition-all shadow-lg shadow-purple-200 font-bold text-sm active:scale-95">
                            <i class="fas fa-upload text-xs"></i> Import
                        </button>
                    </div>
                </div>
                


            <!-- Table Section -->
            <div class="p-6">
                <div class="overflow-hidden border border-gray-200 rounded-xl">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[12px] font-black text-black uppercase tracking-widest">Entry Info</th>
                                    <th class="px-6 py-4 text-left text-[12px] font-black text-black uppercase tracking-widest">Reference</th>
                                    <th class="px-6 py-4 text-left text-[12px] font-black text-black uppercase tracking-widest">Particulars / Accounts</th>
                                    <th class="px-6 py-4 text-right text-[12px] font-black text-black uppercase tracking-widest">Debit</th>
                                    <th class="px-6 py-4 text-right text-[12px] font-black text-black uppercase tracking-widest">Credit</th>
                                    <th class="px-6 py-4 text-left text-[12px] font-black text-black uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-center text-[12px] font-black text-black uppercase tracking-widest w-24">Action</th>
                                </tr>
                            </thead>
                            <tbody id="journalTableBody" class="bg-white divide-y divide-gray-100 text-sm">
                                <!-- JS Populated -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="emptyState" class="hidden py-16 text-center">
                        <div class="text-gray-300 text-4xl mb-4"><i class="fas fa-search"></i></div>
                        <h3 class="text-gray-600 font-medium text-lg">No records found</h3>
                        <p class="text-gray-400">Adjust filters to see results.</p>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <div class="text-sm text-gray-500" id="pageStatus">Showing 0 to 0 of 0 entries</div>
                        <div class="flex items-center gap-2">
                            <button id="prevPage" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition-all">Previous</button>
                            <button id="nextPage" class="px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50 transition-all">Next</button>
                        </div>
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
</main>
</div>
</div>

    <!-- Detail Modal -->
    <div id="detailBackdrop" class="modal-backdrop" onclick="closeDetailModal()"></div>
    <div id="detailModal" class="modal-box">
        <div class="px-8 py-10 gradient-violet text-white relative">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <h3 id="modalTitle" class="text-3xl font-black tracking-tight">Journal Entry Detail</h3>
                    <p id="modalSubtitle" class="text-violet-100 font-medium mt-1 uppercase tracking-widest text-sm"></p>
                </div>
                <button onclick="closeDetailModal()" class="w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 rounded-full transition-all focus:outline-none backdrop-blur-md">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full blur-2xl"></div>
        </div>
        <div class="p-6">
            <div class="mb-6 flex gap-8 items-start">
                <div class="flex-1">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Transaction Description</h4>
                    <p id="modalDescription" class="text-gray-700 text-lg font-medium leading-relaxed"></p>
                </div>
                <div class="w-1/3 bg-violet-50 p-5 rounded-2xl border border-violet-100">
                    <h4 class="text-[10px] font-black text-violet-400 uppercase tracking-widest mb-1 text-center">Reference Source</h4>
                    <div id="modalRefSource" class="text-center font-bold text-violet-700">-</div>
                </div>
            </div>
            
            <div class="overflow-hidden rounded-2xl border border-gray-100 shadow-xl shadow-gray-100">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 font-black text-[11px] uppercase tracking-widest">
                            <th class="px-6 py-4">Account Details</th>
                            <th class="px-6 py-4">Department</th>
                            <th class="px-6 py-4 text-right">Debit</th>
                            <th class="px-6 py-4 text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody id="modalLinesBody" class="divide-y divide-gray-100 font-medium">
                        <!-- JS populated -->
                    </tbody>
                    <tfoot>
                        <tr class="bg-violet-900 text-white font-black">
                            <td colspan="2" class="px-6 py-5 text-right uppercase text-[11px] tracking-widest opacity-80">Total Balance</td>
                            <td id="modalTotalDebit" class="px-6 py-5 text-right text-xl">₱0.00</td>
                            <td id="modalTotalCredit" class="px-6 py-5 text-right text-xl">₱0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="p-6 bg-gray-50 flex justify-center border-t border-gray-100">
            <button onclick="closeDetailModal()" class="px-12 py-4 bg-gray-900 text-white rounded-2xl hover:bg-slate-800 transition-all font-black text-sm uppercase tracking-widest shadow-lg active:scale-95">
                Dismiss Record
            </button>
        </div>
    </div>

    <!-- Import Modal -->
    <div id="importBackdrop" class="modal-backdrop" onclick="closeImportModal()"></div>
    <div id="importModal" class="modal-box !max-w-md">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Import Journal Entries</h3>
            <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-8">
            <form id="importForm" onsubmit="handleImport(event)">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
                    <div class="border-2 border-dashed border-gray-200 rounded-2xl p-8 text-center hover:border-violet-400 transition-all cursor-pointer bg-gray-50" 
                         onclick="document.getElementById('csvFile').click()">
                        <i class="fas fa-cloud-upload-alt text-4xl text-violet-300 mb-3"></i>
                        <p class="text-gray-500 text-sm">Click or drag CSV file here</p>
                        <p class="text-gray-400 text-xs mt-1">Format: Date, Ref Type, Ref ID, Desc, Account, Debit, Credit, Dept</p>
                        <input type="file" id="csvFile" class="hidden" accept=".csv" required onchange="updateFileName(this)">
                        <div id="selectedFileName" class="mt-4 text-violet-600 font-bold text-sm hidden"></div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" id="importSubmitBtn" class="flex-1 py-3 bg-violet-600 text-white font-bold rounded-xl hover:bg-violet-700 transition-all shadow-md">
                        Start Import
                    </button>
                    <button type="button" onclick="closeImportModal()" class="flex-1 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-8 right-8 z-[100000] flex flex-col gap-3 pointer-events-none"></div>

    <script>
        let page = 1, pages = 1;
        const urlParams = new URLSearchParams(window.location.search);
        let search = urlParams.get('search') || "";
        let entryDate = "", refType = "all";
        let filterMonth = "", filterYear = "";
        let dateFrom = "", dateTo = "";
        let filterMode = 'standard';
        let currentRows = [];

        async function loadTable() {
            try {
                const query = new URLSearchParams({
                    ajax_get_entries: 1,
                    search: search,
                    month: filterMonth,
                    year: filterYear,
                    dateFrom: dateFrom,
                    dateTo: dateTo,
                    refType: refType,
                    page: page
                });
                const response = await fetch(`journal_entry.php?${query.toString()}`);
                const data = await response.json();
                
                currentRows = data.rows;
                pages = data.pages;
                
                renderRows(currentRows);
                
                // Update stats and pagination
                document.getElementById('totalEntriesStat').innerText = data.total;
                document.getElementById('pageStatus').innerText = `Showing ${(page-1)*10+1} to ${Math.min(page*10, data.total)} of ${data.total} entries`;
                
                document.getElementById('prevPage').disabled = page <= 1;
                document.getElementById('nextPage').disabled = page >= pages;
                
                // Fetch extra stats if needed (simplified here for now)
                document.getElementById('postedEntriesStat').innerText = currentRows.filter(r => r.status === 'posted').length; 
                // Note: These local stats only reflect current page, for real system you'd fetch global stats from DB
                
            } catch (error) {
                console.error('Error loading table data:', error);
            }
        }

        function renderRows(rows) {
            const tbody = document.getElementById('journalTableBody');
            const emptyState = document.getElementById('emptyState');
            tbody.innerHTML = "";
            
            if (rows.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            }
            
            emptyState.classList.add('hidden');
            rows.forEach(row => {
                const lines = row.lines || [];
                const debits = lines.filter(l => parseFloat(l.debit_amount) > 0);
                const credits = lines.filter(l => parseFloat(l.credit_amount) > 0);
                
                // 1. Transaction Header Row (The first line of the transaction)
                const firstLine = debits[0] || credits[0] || {};
                const trHeader = document.createElement('tr');
                trHeader.className = 'bg-white je-group-border';
                
                const formatAmt = (val) => '₱' + parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});

                trHeader.innerHTML = `
                    <td class="px-6 py-4 align-top" rowspan="${lines.length + 1}">
                        <div class="flex flex-col">
                            <span class="font-black text-gray-900 text-base">${row.journal_number}</span>
                            <span class="text-xs font-bold text-slate-400 mt-1">${row.transaction_date.substring(0,10)}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 align-top" rowspan="${lines.length + 1}">
                        <span class="text-gray-700 font-bold text-sm bg-slate-100 px-3 py-1 rounded-full">${row.reference_id || 'Manual'}</span>
                    </td>
                    <td class="px-6 py-3">
                        <div class="flex flex-col">
                            <span class="text-gray-900 font-bold">${firstLine.gl_account_name || '---'}</span>
                            <span class="text-[10px] font-black text-violet-400 uppercase tracking-widest">${firstLine.gl_account_code || ''} • ${firstLine.account_type || ''}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-right font-black text-emerald-600 text-sm">
                        ${formatAmt(firstLine.debit_amount)}
                    </td>
                    <td class="px-6 py-3 text-right font-black text-rose-600 text-sm">
                        ${formatAmt(firstLine.credit_amount)}
                    </td>
                    <td class="px-6 py-4 align-top" rowspan="${lines.length + 1}">
                        <span class="status-badge status-${row.status || 'posted'}">${row.status || 'posted'}</span>
                    </td>
                    <td class="px-6 py-4 align-top text-center" rowspan="${lines.length + 1}">
                        <button onclick="viewDetail(${row.id}, '${row.journal_number}', '${row.reference_id || 'Manual'}', 'ENTRY')" 
                                class="w-10 h-10 mx-auto flex items-center justify-center text-violet-600 hover:bg-violet-600 hover:text-white rounded-xl transition-all border border-violet-100 shadow-sm" title="Audit Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(trHeader);

                // 2. Remaining Lines (Other debits then credits)
                const remainingLines = lines.slice(1);
                remainingLines.forEach(line => {
                    const trLine = document.createElement('tr');
                    trLine.className = 'bg-white';
                    const isCredit = parseFloat(line.credit_amount) > 0;
                    
                    trLine.innerHTML = `
                        <td class="px-6 py-2 ${isCredit ? 'credit-indent' : ''}">
                            <div class="flex flex-col">
                                <span class="text-gray-900 font-medium ${isCredit ? 'italic text-slate-600' : 'font-bold'}">${line.gl_account_name}</span>
                                <span class="text-[10px] font-black text-violet-300 uppercase tracking-widest">${line.gl_account_code} • ${line.account_type}</span>
                            </div>
                        </td>
                        <td class="px-6 py-2 text-right font-black text-emerald-500 text-sm opacity-80">
                            ${formatAmt(line.debit_amount)}
                        </td>
                        <td class="px-6 py-2 text-right font-black text-rose-500 text-sm opacity-80">
                            ${formatAmt(line.credit_amount)}
                        </td>
                    `;
                    tbody.appendChild(trLine);
                });

                // 3. Description Layer
                const trDesc = document.createElement('tr');
                trDesc.className = 'bg-white';
                trDesc.innerHTML = `
                    <td colspan="3" class="px-10 py-3 pb-6">
                        <div class="desc-bar rounded-lg">
                            <i class="fas fa-info-circle mr-2 opacity-50"></i>
                            ${row.description}
                        </div>
                    </td>
                `;
                tbody.appendChild(trDesc);
            });
        }

        async function viewDetail(id, number, ref, type) {
            try {
                const response = await fetch(`journal_entry.php?ajax_get_lines=1&id=${id}`);
                const res = await response.json();
                
                if (res.success) {
                    const lines = res.data;
                    const modalBody = document.getElementById('modalLinesBody');
                    modalBody.innerHTML = "";
                    
                    document.getElementById('modalSubtitle').innerText = "Voucher #" + number;
                    document.getElementById('modalDescription').innerText = lines[0]?.description || 'No description available.';
                    document.getElementById('modalRefSource').innerText = type + ": " + ref;
                    
                    let tDebit = 0, tCredit = 0;
                    
                    lines.forEach(line => {
                        const d = parseFloat(line.debit_amount);
                        const c = parseFloat(line.credit_amount);
                        tDebit += d;
                        tCredit += c;
                        
                        modalBody.innerHTML += `
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-6 py-5">
                                    <div class="flex flex-col">
                                        <span class="text-[14px] text-gray-900 font-bold">${line.gl_account_name}</span>
                                        <span class="text-[14px] font-black font-mono text-violet-400 tracking-tighter uppercase">${line.gl_account_code} • ${line.account_type}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-gray-600 font-black text-[11px] tracking-widest uppercase">${line.department || 'GENERAL'}</td>
                                <td class="px-6 py-5 text-right font-black text-emerald-600 text-base border-l border-gray-50">₱${d.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                                <td class="px-6 py-5 text-right font-black text-rose-600 text-base border-l border-gray-50">₱${c.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                            </tr>
                        `;
                    });
                    
                    document.getElementById('modalTotalDebit').innerText = '₱' + tDebit.toLocaleString(undefined, {minimumFractionDigits:2});
                    document.getElementById('modalTotalCredit').innerText = '₱' + tCredit.toLocaleString(undefined, {minimumFractionDigits:2});
                    
                    // Show modal
                    document.getElementById('detailBackdrop').classList.add('show');
                    document.getElementById('detailModal').classList.add('show');
                }
            } catch (error) {
                console.error('Error fetching details:', error);
            }
        }

        function closeDetailModal() {
            document.getElementById('detailBackdrop').classList.remove('show');
            document.getElementById('detailModal').classList.remove('show');
        }



        // Import Modal Functions
        function openImportModal() {
            document.getElementById('importBackdrop').classList.add('show');
            document.getElementById('importModal').classList.add('show');
        }

        function closeImportModal() {
            document.getElementById('importBackdrop').classList.remove('show');
            document.getElementById('importModal').classList.remove('show');
            document.getElementById('importForm').reset();
            document.getElementById('selectedFileName').classList.add('hidden');
        }

        function updateFileName(input) {
            const fileNameDiv = document.getElementById('selectedFileName');
            if (input.files.length > 0) {
                fileNameDiv.innerText = input.files[0].name;
                fileNameDiv.classList.remove('hidden');
            } else {
                fileNameDiv.classList.add('hidden');
            }
        }

        async function handleImport(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('importSubmitBtn');
            const fileInput = document.getElementById('csvFile');
            
            if (fileInput.files.length === 0) return;

            const originalText = submitBtn.innerText;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';
            submitBtn.disabled = true;

            const formData = new FormData();
            formData.append('csv', fileInput.files[0]);
            formData.append('action', 'import_journal');

            try {
                const response = await fetch('journal_entry.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.success) {
                    showToast(res.message, 'success');
                    closeImportModal();
                    loadTable();
                } else {
                    showToast(res.message || 'Import failed', 'error');
                }
            } catch (error) {
                console.error('Import error:', error);
                showToast('A network error occurred during import', 'error');
            } finally {
                submitBtn.innerText = originalText;
                submitBtn.disabled = false;
            }
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
            const color = type === 'success' ? 'bg-emerald-500' : (type === 'error' ? 'bg-rose-500' : 'bg-blue-500');
            
            toast.className = `${color} text-white px-6 py-4 rounded-2xl shadow-xl flex items-center gap-3 animate-fade-in min-w-[300px] pointer-events-auto`;
            toast.innerHTML = `<i class="fas ${icon} text-xl"></i> <span class="font-medium">${message}</span>`;
            
            container.appendChild(toast);
            setTimeout(() => {
                toast.classList.remove('animate-fade-in');
                toast.classList.add('opacity-0', 'translate-y-[-20px]', 'transition-all', 'duration-500');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        // Filter Functions
        function toggleFilterMenu(e) {
            e.stopPropagation();
            const menu = document.getElementById('filterMenu');
            menu.classList.toggle('hidden');
            document.getElementById('exportMenu').classList.add('hidden');
        }

        function setFilterMode(mode) {
            filterMode = mode;
            const standardBtn = document.getElementById('modeStandard');
            const customBtn = document.getElementById('modeCustom');
            const standardFilters = document.getElementById('standardFilters');
            const customFilters = document.getElementById('customFilters');

            if (mode === 'standard') {
                standardBtn.classList.add('bg-white', 'shadow-sm', 'text-purple-600');
                standardBtn.classList.remove('text-gray-500');
                customBtn.classList.remove('bg-white', 'shadow-sm', 'text-purple-600');
                customBtn.classList.add('text-gray-500');
                standardFilters.classList.remove('hidden');
                customFilters.classList.add('hidden');
            } else {
                customBtn.classList.add('bg-white', 'shadow-sm', 'text-purple-600');
                customBtn.classList.remove('text-gray-500');
                standardBtn.classList.remove('bg-white', 'shadow-sm', 'text-purple-600');
                standardBtn.classList.add('text-gray-500');
                customFilters.classList.remove('hidden');
                standardFilters.classList.add('hidden');
            }
        }

        function applyFilters() {
            if (filterMode === 'standard') {
                filterMonth = document.getElementById('filterMonth').value;
                filterYear = document.getElementById('filterYear').value;
                dateFrom = "";
                dateTo = "";
            } else {
                dateFrom = document.getElementById('filterDateFrom').value;
                dateTo = document.getElementById('filterDateTo').value;
                filterMonth = "";
                filterYear = "";
            }
            refType = document.getElementById('refTypeFilter').value;
            page = 1;
            loadTable();
            document.getElementById('filterMenu').classList.add('hidden');
        }

        function resetFilters() {
            document.getElementById('filterMonth').value = "";
            document.getElementById('filterYear').value = "";
            document.getElementById('filterDateFrom').value = "";
            document.getElementById('filterDateTo').value = "";
            document.getElementById('refTypeFilter').value = "all";
            
            filterMonth = "";
            filterYear = "";
            dateFrom = "";
            dateTo = "";
            refType = "all";
            page = 1;
            loadTable();
            document.getElementById('filterMenu').classList.add('hidden');
        }

        // Export Functions
        function toggleExportMenu(e) {
            e.stopPropagation();
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('hidden');
            document.getElementById('filterMenu').classList.add('hidden');
        }

        async function fetchAllFilteredData() {
            // Fetch all data matching current filters (without pagination)
            const query = new URLSearchParams({
                ajax_get_entries: 1,
                search: search,
                month: filterMonth,
                year: filterYear,
                dateFrom: dateFrom,
                dateTo: dateTo,
                refType: refType,
                page: 1,
                limit: 999999 // Large limit to get all
            });
            
            // To support the big limit, we might need a minor backend tweak, 
            // but for now let's just fetch the current filters.
            // If the backend doesn't support 'limit', we'll get the first page.
            // Let's assume for now currentRows is enough for the user or we'll update backend if needed.
            // Actually, let's just fetch all by omitting pagination on backend if limit is high.
            
            const response = await fetch(`journal_entry.php?${query.toString()}&all=1`);
            const data = await response.json();
            return data.rows;
        }

        async function exportData(format) {
            document.getElementById('exportMenu').classList.add('hidden');
            showToast(`Preparing ${format.toUpperCase()} export...`, 'info');
            
            const rows = await fetchAllFilteredData();
            const headers = ["JE Number", "Date", "Type", "Ref ID", "Debit", "Credit", "Status"];
            const data = rows.map(row => [
                row.journal_number,
                row.transaction_date ? row.transaction_date.substring(0,10) : '',
                row.reference_type,
                row.reference_id || '',
                parseFloat(row.total_debit),
                parseFloat(row.total_credit),
                row.status
            ]);

            if (format === 'pdf') {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'pt', 'a4');
                doc.setFontSize(18);
                doc.text("Journal Entry Registry", 40, 40);
                doc.autoTable({
                    head: [headers],
                    body: data,
                    startY: 60,
                    theme: 'grid',
                    headStyles: { fillColor: [124, 58, 237] }
                });
                doc.save(`Journal_Entries_${new Date().toISOString().split('T')[0]}.pdf`);
            } else if (format === 'excel') {
                let ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
                let wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Journal Entries");
                XLSX.writeFile(wb, `Journal_Entries_${new Date().toISOString().split('T')[0]}.xlsx`);
            } else if (format === 'csv') {
                let csvContent = [headers, ...data].map(e => e.join(",")).join("\n");
                let blob = new Blob([csvContent], { type: 'text/csv' });
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = `Journal_Entries_${new Date().toISOString().split('T')[0]}.csv`;
                a.click();
            }
        }



        // Event Listeners
        document.getElementById('searchInput').addEventListener('input', (e) => { 
            clearTimeout(window.searchTimer);
            window.searchTimer = setTimeout(() => {
                search = e.target.value; 
                page = 1; 
                loadTable(); 
            }, 500);
        });

        document.getElementById('filterButton').addEventListener('click', toggleFilterMenu);
        document.getElementById('exportDropdownBtn').addEventListener('click', toggleExportMenu);

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#filterMenu') && !e.target.closest('#filterButton')) {
                document.getElementById('filterMenu').classList.add('hidden');
            }
            if (!e.target.closest('#exportMenu') && !e.target.closest('#exportDropdownBtn')) {
                document.getElementById('exportMenu').classList.add('hidden');
            }
        });

        document.getElementById('prevPage').addEventListener('click', () => { if (page > 1) { page--; loadTable(); } });
        document.getElementById('nextPage').addEventListener('click', () => { if (page < pages) { page++; loadTable(); } });

        window.onload = () => {
            if (search) document.getElementById('searchInput').value = search;
            loadTable();
        };
    </script>
</body>
</html>