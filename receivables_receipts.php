<?php
// Start output buffering to prevent any accidental output before JSON responses
ob_start();

// Suppress errors for AJAX requests to prevent HTML error output
if (isset($_GET['action']) || isset($_POST['action'])) {
    error_reporting(0);
    ini_set('display_errors', 0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    if (isset($_GET['action']) || isset($_POST['action'])) {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit();
    }
    header("Location: login.php");
    exit();
}

include('connection.php');

// Ensure page parameter is set for sidebar highlighting
if (!isset($_GET['page']) && !isset($_GET['action']) && !isset($_POST['action'])) {
    header("Location: receivables_receipts.php?page=arreceipts");
    exit();
}

// AJAX: Get pending receipts for bulk modal
if (isset($_GET['action']) && $_GET['action'] === 'get_pending') {
    $sql = "SELECT ar.*, ar.description as receipt_description, ar.created_at, 
                   account_receivable.description as invoice_description, 
                   account_receivable.driver_name,
                   account_receivable.amount as total_invoice_amount
            FROM ar 
            LEFT JOIN account_receivable ON ar.invoice_reference = account_receivable.invoice_id
            WHERE ar.from_receivable = 1 AND (ar.status IS NULL OR ar.status != 'collected')
            ORDER BY ar.created_at DESC";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $items]);
    exit();
}

// AJAX: Bulk Collect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_collect') {
    $receipt_ids = $_POST['receipt_ids'] ?? [];
    $response = ['success' => false, 'message' => '', 'collected_count' => 0];
    
    if (empty($receipt_ids)) {
        $response['message'] = "No receipts selected";
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    $conn->begin_transaction();
    try {
        $collected_count = 0;
        foreach ($receipt_ids as $data) {
            // data is "receipt_id|invoice_id|amount"
            list($rcpt_id, $inv_id, $amt) = explode('|', $data);
            $rcpt_id = $conn->real_escape_string($rcpt_id);
            $inv_id = $conn->real_escape_string($inv_id);
            $amt = floatval($amt);
            
            // 1. Update amount_paid in account_receivable
            $conn->query("UPDATE account_receivable SET amount_paid = amount_paid + $amt, updated_at = NOW() WHERE invoice_id = '$inv_id'");
            
            // 2. Check for fully paid
            $res = $conn->query("SELECT amount, amount_paid FROM account_receivable WHERE invoice_id = '$inv_id'");
            $invoice = $res->fetch_assoc();
            if ($invoice && ($invoice['amount_paid'] >= $invoice['amount'])) {
                $conn->query("UPDATE account_receivable SET status = 'paid' WHERE invoice_id = '$inv_id'");
            }
            
            // 3. Mark specific receipt as collected
            $res = $conn->query("UPDATE ar SET status = 'collected', collected_at = NOW() WHERE receipt_id = '$rcpt_id' AND invoice_reference = '$inv_id'");
            if ($conn->affected_rows > 0) {
                $collected_count++;
            }
        }
        $conn->commit();
        $response['success'] = true;
        $response['collected_count'] = $collected_count;
        $response['message'] = "Successfully collected $collected_count receipt(s)!";
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = "Error: " . $e->getMessage();
    }
    
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Initial page data fetch
$sql = "SELECT ar.*, ar.description as receipt_description, 
               ar.created_at, 
               account_receivable.description as invoice_description,
               account_receivable.amount_paid,
               account_receivable.amount as total_invoice_amount,
               account_receivable.driver_name
        FROM ar 
        LEFT JOIN account_receivable ON ar.invoice_reference = account_receivable.invoice_id
        WHERE ar.from_receivable = 1
        ORDER BY ar.created_at DESC";
$result = $conn->query($sql);

$receipts = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) { 
        if (empty($row['receipt_id']) || $row['receipt_id'] === $row['invoice_reference']) {
            $new_receipt_id = "RCPT-" . time() . '-' . rand(1000, 9999);
            $conn->query("UPDATE ar SET receipt_id = '$new_receipt_id' WHERE id = " . $row['id']);
            $row['receipt_id'] = $new_receipt_id;
        }
        
        if (empty($row['receipt_description']) && !empty($row['invoice_description'])) {
            $formatted_description = "Payment for INV " . $row['invoice_reference'] . " - " . $row['invoice_description'];
            $conn->query("UPDATE ar SET description = '" . $conn->real_escape_string($formatted_description) . "' WHERE id = " . $row['id']);
            $row['receipt_description'] = $formatted_description;
        }
        
        $row['is_collected'] = ($row['status'] === 'collected');
        $receipts[] = $row; 
    }
}
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <!-- jsPDF and autotable for PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Receipts</title>
    <link rel="icon" href="logo.png" type="img">
    <style>
        body { font-family: 'Outfit', sans-serif; font-size: 15px; }
        .theme-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; }
        .theme-badge { padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 500; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-collected { background: #d1fae5; color: #065f46; }
        
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 50; }
        .modal-content { background: white; border-radius: 16px; width: 100%; max-width: 900px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        
        .bulk-table-container { max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
        .bulk-table { width: 100%; border-collapse: collapse; }
        .bulk-table th { position: sticky; top: 0; background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #64748b; border-bottom: 1px solid #e5e7eb; }
        .bulk-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .bulk-table tr:hover { background: #f8fafc; }
        .bulk-table tr.selected { background: #f0f9ff; }
        
        .action-btn { transition: all 0.2s; }
        .action-btn:hover { transform: translateY(-1px); }
        .action-btn:active { transform: translateY(0); }
        
        .search-container { position: relative; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { padding-left: 36px; border-radius: 8px; border: 1px solid #e2e8f0; width: 300px; transition: all 0.2s; }
        .search-input:focus { outline: none; border-color: #6366f1; ring: 2px solid #6366f1; }
        
        .export-btn { background: #1e293b; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .export-btn:hover { background: #0f172a; }
        
        .bulk-btn { background: #10b981; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .bulk-btn:hover { background: #059669; }

        .toast { position: fixed; top: 20px; right: 20px; padding: 16px 24px; border-radius: 8px; color: white; z-index: 1000; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); animation: slideIn 0.3s ease-out; }
        .toast.success { background: #10b981; }
        .toast.error { background: #ef4444; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        .tab-btn { padding: 12px 24px; font-weight: 600; font-size: 14px; position: relative; transition: all 0.2s; color: #64748b; }
        .tab-btn:hover { color: #4f46e5; }
        .tab-btn.active { color: #4f46e5; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: #4f46e5; border-radius: 9999px; }

        input[type="checkbox"] { accent-color: #6366f1; width: 16px; height: 16px; }

        /* Custom Confirmation Modal Styling */
        .confirm-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; }
        .confirm-modal-card { background: white; border-radius: 20px; width: 400px; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: center; transform: scale(0.9); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); opacity: 0; }
        .confirm-modal-card.show { transform: scale(1); opacity: 1; }
        .confirm-icon { width: 64px; height: 64px; background: #eef2ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px; }
    </style>
</head>

<body class="bg-gray-50">
    <?php include('sidebar.php'); ?>

    <div class="overflow-y-auto h-full px-6">
        <!-- Breadcrumb -->
        <div class="flex justify-between items-center py-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pending Receipts</h1>
                <p class="text-gray-500 text-sm mt-1">Manage and track your accounts receivable collection</p>
            </div>
            <div class="text-sm flex items-center gap-2">
                <a href="dashboard.php" class="text-gray-600 hover:text-indigo-600">Home</a>
                <span class="text-gray-400">/</span>
                <span class="text-gray-600">Collection</span>
                <span class="text-gray-400">/</span>
                <span class="text-indigo-600 font-medium">Receipts</span>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <div class="flex items-center gap-4">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input py-2" placeholder="Search receipts, names..." onkeyup="filterTable()">
                </div>
                <input type="date" id="dateFilter" class="border border-gray-300 rounded-lg px-4 py-1.5 focus:outline-none focus:border-indigo-500" onchange="filterTable()">
            </div>
            <div class="flex items-center gap-3">
                <!-- Export Dropdown -->
                <div class="relative">
                    <button onclick="toggleExportDropdown(event)" class="export-btn py-1.5 focus:outline-none">
                        <i class="fas fa-download"></i>
                        Export
                        <i class="fas fa-chevron-down text-xs ml-1"></i>
                    </button>
                    <div id="exportDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 hidden z-50 overflow-hidden">
                        <button onclick="exportPDF()" class="w-full px-4 py-3 text-left hover:bg-indigo-50 flex items-center gap-3 border-b border-gray-50 transition-colors">
                            <i class="fas fa-file-pdf text-red-500"></i> <span class="text-gray-700 font-medium">PDF Document</span>
                        </button>
                        <button onclick="exportCSV()" class="w-full px-4 py-3 text-left hover:bg-indigo-50 flex items-center gap-3 border-b border-gray-50 transition-colors">
                            <i class="fas fa-file-csv text-green-500"></i> <span class="text-gray-700 font-medium">CSV Spreadsheet</span>
                        </button>
                        <button onclick="exportExcel()" class="w-full px-4 py-3 text-left hover:bg-indigo-50 flex items-center gap-3 transition-colors">
                            <i class="fas fa-file-excel text-blue-500"></i> <span class="text-gray-700 font-medium">Excel Workbook</span>
                        </button>
                    </div>
                </div>
                <button id="bulkCollectBtn" class="bulk-btn py-1.5" onclick="openBulkCollectModal()">
                    <i class="fas fa-check-double"></i>
                    Bulk Collect
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex items-center border-b border-gray-200 mb-6 bg-white rounded-t-xl px-4">
            <button onclick="switchTab('pending')" id="tabPending" class="tab-btn active">Pending Receipts</button>
            <button onclick="switchTab('collected')" id="tabCollected" class="tab-btn">Collected History</button>
        </div>

        <!-- Main Table -->
        <div class="theme-card mb-8 overflow-hidden rounded-t-none">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-bottom border-gray-200">
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Receipt ID</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Driver Name</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Description</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Date Received</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 font-semibold text-gray-600 text-sm uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="receiptsTableBody" class="divide-y divide-gray-100">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-between border-t border-gray-100">
                <p id="pageStatus" class="text-sm text-gray-500 font-medium">Showing 0 to 0 of 0 entries</p>
                <div class="flex gap-2">
                    <button id="prevPage" class="px-4 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" onclick="prevPage()">
                        <i class="fas fa-chevron-left mr-1.5 text-xs"></i> Previous
                    </button>
                    <button id="nextPage" class="px-4 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50" onclick="nextPage()">
                        Next <i class="fas fa-chevron-right ml-1.5 text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Collect Modal -->
    <div id="bulkCollectModal" class="modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="modal-content">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Bulk Collection Processing</h2>
                            <p class="text-gray-500 text-sm mt-1">Select multiple receipts to mark as collected across your ledger</p>
                        </div>
                        <button onclick="closeModal('bulkCollectModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="bulkSearchInput" class="search-input w-full py-2.5" placeholder="Search by Receipt ID or Driver Name..." onkeyup="filterBulkModal()">
                        </div>
                    </div>

                    <div class="bulk-table-container mb-6 bg-gray-50">
                        <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-white">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" id="selectAllBulk" onchange="toggleSelectAllBulk()">
                                <label for="selectAllBulk" class="text-sm font-bold text-gray-700 cursor-pointer">Select All Pending</label>
                            </div>
                            <span id="bulkSummaryText" class="text-sm text-gray-500">0 items selected</span>
                        </div>
                        <table class="bulk-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px"></th>
                                    <th>RECEIPT ID</th>
                                    <th>DRIVER NAME</th>
                                    <th>INVOICE REF</th>
                                    <th class="text-right">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody id="bulkReceiptsTable">
                                <!-- Populated by AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-indigo-50 p-6 rounded-xl border border-indigo-100 mb-8 flex items-center justify-between">
                        <div>
                            <p class="text-indigo-600 text-sm font-semibold uppercase tracking-wider mb-1">Total Collection Amount</p>
                            <h3 class="text-3xl font-bold text-indigo-900" id="selectedTotalAmount">₱0.00</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-500 text-sm mb-1">Batch Count</p>
                            <p class="text-2xl font-bold text-gray-800" id="selectedCountValue">0 Receipts</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <button onclick="closeModal('bulkCollectModal')" class="px-6 py-2.5 text-sm font-bold text-gray-600 hover:text-gray-800">Cancel</button>
                        <button id="btnProcessBulk" class="px-8 py-2.5 bg-indigo-600 text-white rounded-lg font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all" onclick="processBulkCollect()" disabled>
                            Process Selected Payments
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // State management
    const receipts = <?php echo json_encode($receipts); ?>;
    let filtered = receipts.slice();
    let currentPage = 1;
    let currentTab = 'pending';
    const rowsPerPage = 10;
    
    // State for bulk modal
    window.bulkReceipts = [];
    window.selectedBulkIds = new Set();

    document.addEventListener('DOMContentLoaded', () => {
        filterTable(); // Initial filter will handle tab and search
    });

    // Dropdown Logic
    function toggleExportDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('exportDropdown');
        dropdown.classList.toggle('hidden');
    }

    // Close dropdowns when clicking outside
    window.addEventListener('click', () => {
        const dropdown = document.getElementById('exportDropdown');
        if (dropdown) dropdown.classList.add('hidden');
    });

    function showCustomConfirm(title, message, callback) {
        const overlay = document.getElementById('confirmModalOverlay');
        const card = document.getElementById('confirmModalCard');
        const proceedBtn = document.getElementById('confirmProceedBtn');
        
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        
        overlay.style.display = 'flex';
        setTimeout(() => card.classList.add('show'), 10);
        
        proceedBtn.onclick = () => {
            closeConfirmModal();
            callback();
        };
    }

    function closeConfirmModal() {
        const overlay = document.getElementById('confirmModalOverlay');
        const card = document.getElementById('confirmModalCard');
        card.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    }

    function switchTab(tab) {
        currentTab = tab;
        
        // Update UI
        document.getElementById('tabPending').classList.toggle('active', tab === 'pending');
        document.getElementById('tabCollected').classList.toggle('active', tab === 'collected');
        
        // Toggle bulk button visibility
        document.getElementById('bulkCollectBtn').style.display = tab === 'pending' ? 'flex' : 'none';
        
        // Update data
        currentPage = 1;
        filterTable();
    }

    function formatMoney(amount) {
        return Number(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    function renderTable() {
        const tbody = document.getElementById('receiptsTableBody');
        tbody.innerHTML = '';
        
        const start = (currentPage - 1) * rowsPerPage;
        const end = Math.min(start + rowsPerPage, filtered.length);
        const paginated = filtered.slice(start, end);
        
        if (paginated.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-12 text-center text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3 block text-gray-200"></i> No receipts found
            </td></tr>`;
        } else {
            paginated.forEach(row => {
                const statusClass = row.is_collected ? 'badge-collected' : 'badge-pending';
                const statusText = row.is_collected ? 'Collected' : 'Pending';
                
                tbody.innerHTML += `
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono font-semibold text-gray-900 block text-sm">${row.receipt_id}</span>
                            <span class="text-[12px] text-gray-500 font-bold uppercase tracking-wider block mt-1">Ref: ${row.invoice_reference}</span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-900">${row.driver_name || 'N/A'}</td>
                        <td class="px-6 py-4 text-gray-700 max-w-xs truncate">${row.receipt_description}</td>
                        <td class="px-6 py-4 font-bold text-gray-900 text-xl">₱${formatMoney(row.amount_received)}</td>
                        <td class="px-6 py-4 text-gray-600 font-medium">${formatDate(row.created_at)}</td>
                        <td class="px-6 py-4">
                            <span class="theme-badge ${statusClass}">${statusText}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            ${!row.is_collected ? `
                                <button onclick="confirmSingleCollect('${row.receipt_id}', '${row.invoice_reference}', ${row.amount_received})" 
                                        class="px-5 py-2 bg-indigo-600 text-white hover:bg-indigo-700 rounded-xl text-sm font-bold transition-all shadow-md shadow-indigo-100">
                                    Collect
                                </button>
                            ` : `
                                <span class="text-gray-400 text-xs font-bold uppercase tracking-wider">Processed</span>
                            `}
                        </td>
                    </tr>
                `;
            });
        }
        
        // Update pagination status
        const totalEntries = filtered.length;
        document.getElementById('pageStatus').textContent = `Showing ${totalEntries > 0 ? start + 1 : 0} to ${end} of ${totalEntries} entries`;
        document.getElementById('prevPage').disabled = currentPage === 1;
        document.getElementById('nextPage').disabled = end >= filtered.length;
    }

    function filterTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilter') ? document.getElementById('dateFilter').value : '';
        
        filtered = receipts.filter(row => {
            const matchesSearch = (row.receipt_id || '').toLowerCase().includes(searchInput) ||
                                (row.driver_name || '').toLowerCase().includes(searchInput) ||
                                (row.receipt_description || '').toLowerCase().includes(searchInput);
            
            const matchesDate = !dateFilter || (row.created_at && row.created_at.startsWith(dateFilter));
            
            const matchesTab = currentTab === 'collected' ? row.is_collected : !row.is_collected;
            
            return matchesSearch && matchesDate && matchesTab;
        });
        
        currentPage = 1;
        renderTable();
    }

    function prevPage() { if (currentPage > 1) { currentPage--; renderTable(); } }
    function nextPage() { if (currentPage * rowsPerPage < filtered.length) { currentPage++; renderTable(); } }

    // MODAL HELPERS
    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    // BULK COLLECT LOGIC
    function openBulkCollectModal() {
        const tbody = document.getElementById('bulkReceiptsTable');
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading pending receipts...</td></tr>';
        
        const searchInput = document.getElementById('bulkSearchInput');
        if (searchInput) searchInput.value = '';

        window.selectedBulkIds = new Set();
        updateBulkSummary();
        openModal('bulkCollectModal');
        
        fetch('receivables_receipts.php?action=get_pending')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.bulkReceipts = data.data;
                    renderBulkReceipts();
                } else {
                    showToast(data.message || 'Failed to load data', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Error connecting to server', 'error');
            });
    }

    function filterBulkModal() {
        renderBulkReceipts();
    }

    function renderBulkReceipts() {
        const tbody = document.getElementById('bulkReceiptsTable');
        const searchTerm = document.getElementById('bulkSearchInput').value.toLowerCase();
        
        const filteredList = window.bulkReceipts.filter(row => {
            return (row.receipt_id || '').toLowerCase().includes(searchTerm) ||
                   (row.driver_name || '').toLowerCase().includes(searchTerm);
        });

        if (filteredList.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">
                ${window.bulkReceipts.length === 0 ? 'No pending receipts found' : 'No matches found for your search'}
            </td></tr>`;
            return;
        }
        
        let html = '';
        filteredList.forEach((row, idx) => {
            const key = `${row.receipt_id}|${row.invoice_reference}|${row.amount_received}`;
            const isSelected = window.selectedBulkIds.has(key);
            html += `
                <tr class="${isSelected ? 'selected' : ''}" onclick="toggleBulkRow(this, '${key}')">
                    <td class="text-center"><input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleBulkRow(this.closest('tr'), '${key}')"></td>
                    <td class="font-mono text-gray-900">${row.receipt_id}</td>
                    <td>${row.driver_name || 'N/A'}</td>
                    <td>${row.invoice_reference}</td>
                    <td class="text-right font-bold text-gray-900">₱${formatMoney(row.amount_received)}</td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        updateSelectAllState();
    }

    function toggleBulkRow(row, key) {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (window.selectedBulkIds.has(key)) {
            window.selectedBulkIds.delete(key);
            checkbox.checked = false;
            row.classList.remove('selected');
        } else {
            window.selectedBulkIds.add(key);
            checkbox.checked = true;
            row.classList.add('selected');
        }
        updateBulkSummary();
        updateSelectAllState();
    }

    function toggleSelectAllBulk() {
        const checked = document.getElementById('selectAllBulk').checked;
        const searchTerm = document.getElementById('bulkSearchInput').value.toLowerCase();
        
        const filteredList = window.bulkReceipts.filter(row => {
            return (row.receipt_id || '').toLowerCase().includes(searchTerm) ||
                   (row.driver_name || '').toLowerCase().includes(searchTerm);
        });

        filteredList.forEach(row => {
            const key = `${row.receipt_id}|${row.invoice_reference}|${row.amount_received}`;
            if (checked) {
                window.selectedBulkIds.add(key);
            } else {
                window.selectedBulkIds.delete(key);
            }
        });
        
        renderBulkReceipts();
        updateBulkSummary();
    }

    function updateSelectAllState() {
        const selectAll = document.getElementById('selectAllBulk');
        if (!selectAll) return;
        
        const searchTerm = document.getElementById('bulkSearchInput').value.toLowerCase();
        const filteredList = window.bulkReceipts.filter(row => {
            return (row.receipt_id || '').toLowerCase().includes(searchTerm) ||
                   (row.driver_name || '').toLowerCase().includes(searchTerm);
        });

        const count = filteredList.length;
        let selectedCount = 0;
        filteredList.forEach(row => {
            const key = `${row.receipt_id}|${row.invoice_reference}|${row.amount_received}`;
            if (window.selectedBulkIds.has(key)) selectedCount++;
        });

        selectAll.checked = count > 0 && selectedCount === count;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < count;
    }

    function updateBulkSummary() {
        const count = window.selectedBulkIds.size;
        let total = 0;
        window.selectedBulkIds.forEach(key => {
            const parts = key.split('|');
            total += parseFloat(parts[2]);
        });
        
        document.getElementById('selectedCountValue').textContent = `${count} Receipt${count === 1 ? '' : 's'}`;
        document.getElementById('selectedTotalAmount').textContent = `₱${formatMoney(total)}`;
        document.getElementById('bulkSummaryText').textContent = `${count} item${count === 1 ? '' : 's'} selected`;
        document.getElementById('btnProcessBulk').disabled = count === 0;
    }

    function processBulkCollect() {
        const ids = Array.from(window.selectedBulkIds);
        if (ids.length === 0) return;
        
        showCustomConfirm(
            'Bulk Collection',
            `Are you sure you want to collect ${ids.length} selected payments?`,
            () => {
                const formData = new FormData();
                formData.append('action', 'bulk_collect');
                ids.forEach(id => formData.append('receipt_ids[]', id));
                
                document.getElementById('btnProcessBulk').disabled = true;
                document.getElementById('btnProcessBulk').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
                
                fetch('receivables_receipts.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000); 
                    } else {
                        showToast(data.message, 'error');
                        document.getElementById('btnProcessBulk').disabled = false;
                        document.getElementById('btnProcessBulk').textContent = 'Process Selected Payments';
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Network error occurred', 'error');
                    document.getElementById('btnProcessBulk').disabled = false;
                    document.getElementById('btnProcessBulk').textContent = 'Process Selected Payments';
                });
            }
        );
    }

    function confirmSingleCollect(rcptId, invId, amt) {
        showCustomConfirm(
            'Confirm Collection',
            `Mark receipt ${rcptId} as collected for ₱${formatMoney(amt)}?`,
            () => {
                const formData = new FormData();
                formData.append('action', 'bulk_collect');
                formData.append('receipt_ids[]', `${rcptId}|${invId}|${amt}`);
                
                fetch('receivables_receipts.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast('Network error occurred', 'error');
                });
            }
        );
    }

    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    // EXPORT FUNCTIONS
    function getExportData() {
        const headers = ["Receipt ID", "Driver Name", "Description", "Amount", "Date", "Status"];
        const data = filtered.map(row => [
            row.receipt_id,
            row.driver_name || 'N/A',
            row.receipt_description,
            `₱${formatMoney(row.amount_received)}`,
            formatDate(row.created_at),
            row.is_collected ? 'Collected' : 'Pending'
        ]);
        return { headers, data };
    }

    function exportPDF() {
        const { headers, data } = getExportData();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'pt', 'a4');
        
        doc.setFontSize(18);
        doc.text("Receivables Receipts Report", 40, 40);
        doc.setFontSize(10);
        doc.text(`Generated on: ${new Date().toLocaleString()}`, 40, 60);
        
        doc.autoTable({
            head: [headers],
            body: data,
            startY: 80,
            theme: 'striped',
            headStyles: { fillColor: [79, 70, 229] } // indigo-600
        });
        
        doc.save(`Receipts_Report_${new Date().toISOString().slice(0,10)}.pdf`);
        showToast('PDF Exported Successfully!', 'success');
    }

    function exportCSV() {
        const { headers, data } = getExportData();
        let csv = headers.join(',') + '\n';
        data.forEach(row => {
            csv += row.map(cell => `"${(cell+'').replace(/"/g, '""')}"`).join(',') + '\n';
        });
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Receipts_Report_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        showToast('CSV Exported Successfully!', 'success');
    }

    function exportExcel() {
        const { headers, data } = getExportData();
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
        XLSX.utils.book_append_sheet(wb, ws, "Receipts");
        XLSX.writeFile(wb, `Receipts_Report_${new Date().toISOString().slice(0,10)}.xlsx`);
        showToast('Excel Exported Successfully!', 'success');
    }
</script>

<!-- Custom Confirmation Modal -->
<div id="confirmModalOverlay" class="confirm-modal-overlay">
    <div id="confirmModalCard" class="confirm-modal-card">
        <div class="confirm-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2" id="confirmTitle">Confirm Action</h3>
        <p class="text-gray-600 mb-8 leading-relaxed" id="confirmMessage">Are you sure you want to proceed with this collection?</p>
        <div class="flex gap-3">
            <button onclick="closeConfirmModal()" class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 font-bold rounded-xl hover:bg-gray-200 transition-colors">
                No, Cancel
            </button>
            <button id="confirmProceedBtn" class="flex-1 px-4 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition-all">
                Yes, Proceed
            </button>
        </div>
    </div>
</div>
</body>
</html>