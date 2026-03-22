<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');

// AJAX: Get pending invoices for bulk modal
if (isset($_GET['action']) && $_GET['action'] === 'get_pending') {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $sql = "SELECT invoice_id, driver_name, amount, amount_paid, description, approval_date 
            FROM account_receivable 
            WHERE status = 'confirmed' AND amount_paid < amount
            ORDER BY approval_date DESC";
    $res = $conn->query($sql);
    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit();
}

// AJAX: Process bulk collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_collect') {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    if (empty($invoice_ids)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        exit();
    }

    $conn->begin_transaction();
    try {
        foreach ($invoice_ids as $id_str) {
            // Format: invoice_id|remaining
            list($invoice_id, $remaining) = explode('|', $id_str);
            $remaining = (float)$remaining;

            // 1. Get current data
            $stmt = $conn->prepare("SELECT * FROM account_receivable WHERE invoice_id = ?");
            $stmt->bind_param("s", $invoice_id);
            $stmt->execute();
            $ar = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($ar) {
                $new_paid = $ar['amount_paid'] + $remaining;
                
                // 2. Update account_receivable
                $stmt = $conn->prepare("UPDATE account_receivable SET amount_paid = ?, updated_at = NOW() WHERE invoice_id = ?");
                $stmt->bind_param("ds", $new_paid, $invoice_id);
                $stmt->execute();
                $stmt->close();

                // 3. Insert into ar table
                $payment_date = date('Y-m-d');
                $receipt_id = $invoice_id . "-B" . date("His");
                $from_receivable = 1;
                
                $insert_sql = "INSERT INTO ar (
                    receipt_id, driver_name, payment_method, 
                    amount_received, payment_date, invoice_reference, from_receivable, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param(
                    "sssdssis",
                    $receipt_id,
                    $ar['driver_name'],
                    $ar['payment_method'],
                    $remaining,
                    $payment_date,
                    $invoice_id,
                    $from_receivable,
                    $ar['description']
                );
                $insert_stmt->execute();
                $insert_stmt->close();

                // 4. Mark as 'paid' if fully paid
                if ($new_paid >= $ar['amount'] - 0.00001) {
                    $stmt = $conn->prepare("UPDATE account_receivable SET status = 'paid' WHERE invoice_id = ?");
                    $stmt->bind_param("s", $invoice_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Bulk collection processed successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Payment logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invoice_id'], $_POST['amount_pay'])) {
    $invoice_id = trim($_POST['invoice_id']);
    $amount_pay = (float)$_POST['amount_pay'];

    // Get current paid and amount
    $stmt = $conn->prepare("SELECT * FROM account_receivable WHERE invoice_id = ? AND status = 'confirmed'");
    $stmt->bind_param("s", $invoice_id);
    $stmt->execute();
    $ar = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($ar) {
        $new_paid = $ar['amount_paid'] + $amount_pay;
        $amount = $ar['amount'];

        // Update amount_paid
        $stmt = $conn->prepare("UPDATE account_receivable SET amount_paid = ?, updated_at = NOW() WHERE invoice_id = ? AND status = 'confirmed'");
        $stmt->bind_param("ds", $new_paid, $ar['invoice_id']);
        $stmt->execute();
        $stmt->close();

        // Prepare data for AR table
        $receipt_id = $invoice_id;
        $from_receivable = 1;
        $payment_date = date('Y-m-d');

        // Insert into ar table - FIXED: Added description field
        $insert_sql = "INSERT INTO ar (
            receipt_id, driver_name, payment_method, 
            amount_received, payment_date, invoice_reference, from_receivable, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param(
            "sssdssis",
            $receipt_id,
            $ar['driver_name'],
            $ar['payment_method'],
            $amount_pay,
            $payment_date,
            $invoice_id,
            $from_receivable,
            $ar['description'] // Added this line to provide the description
        );
        $insert_stmt->execute();
        $insert_stmt->close();

        // Mark as 'paid' if fully paid
        if ($new_paid >= $amount - 0.00001) {
            $stmt = $conn->prepare("UPDATE account_receivable SET status = 'paid' WHERE invoice_id = ?");
            $stmt->bind_param("s", $invoice_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Store success message in session and redirect to same page
    $_SESSION['success_message'] = "Payment received successfully!";
    header("Location: receivables.php?page=receivables");
    exit();
}

// Overview metrics
$approvedCount = $conn->query("SELECT COUNT(*) as total FROM account_receivable WHERE status = 'confirmed' AND amount_paid < amount")->fetch_assoc()['total'];
$totalDue = $conn->query("SELECT SUM(amount) as total FROM account_receivable WHERE status = 'confirmed' AND amount_paid < amount")->fetch_assoc()['total'];
$totalRemaining = $conn->query("SELECT SUM(amount - amount_paid) as total FROM account_receivable WHERE status = 'confirmed'")->fetch_assoc()['total'];

// Main Query
$sql = "SELECT * FROM account_receivable WHERE status = 'confirmed' ORDER BY approval_date DESC";
$result = $conn->query($sql);

// Aging Summary
$aging_brackets = [
    'Current' => 'DATEDIFF(fully_paid_date, CURRENT_DATE) >= 0',
    '1-30 Days Past Due' => 'DATEDIFF(CURRENT_DATE, fully_paid_date) BETWEEN 1 AND 30',
    '31-60 Days Past Due' => 'DATEDIFF(CURRENT_DATE, fully_paid_date) BETWEEN 31 AND 60',
    '61-90 Days Past Due' => 'DATEDIFF(CURRENT_DATE, fully_paid_date) BETWEEN 61 AND 90',
    'Over 90 Days Past Due' => 'DATEDIFF(CURRENT_DATE, fully_paid_date) > 90'
];
$aging_data = [];
foreach ($aging_brackets as $label => $where) {
    $q = $conn->query("SELECT SUM(amount - amount_paid) as total, COUNT(*) as count FROM account_receivable WHERE status='confirmed' AND amount > amount_paid AND $where");
    $row = $q->fetch_assoc();
    $aging_data[$label] = [
        'count' => $row['count'] ?? 0,
        'total' => $row['total'] ?? 0,
    ];
}

// Outstanding Summary
$outstanding_sql = "
    SELECT driver_name, SUM(amount - amount_paid) as outstanding, COUNT(*) as count
    FROM account_receivable
    WHERE status='confirmed' AND amount > amount_paid
    GROUP BY driver_name
    ORDER BY outstanding DESC
";
$outstanding_result = $conn->query($outstanding_sql);
$outstanding_data = [];
while ($row = $outstanding_result->fetch_assoc()) {
    $outstanding_data[] = $row;
}
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <title>Receivables</title>
    <link rel="icon" href="logo.png" type="img">
    <style>
    .tab-active {
      border-bottom: 2px solid #7c3aed;
      color: #7c3aed !important;
      font-weight: bold;
    }
    
    @media (max-width: 1024px) {
      .overview-flex { flex-direction: column !important; }
      .overview-left, .overview-right { width: 100% !important; }
      .overview-cards { flex-direction: column !important; }
      .overview-right { min-width: 0 !important; }
    }

    .toast { position: fixed; top: 20px; right: 20px; padding: 12px 20px; border-radius: 8px; color: white; font-weight: bold; z-index: 1000; opacity: 0; transform: translateX(100%); transition: all 0.3s ease-in-out; }
    .toast.show { opacity: 1; transform: translateX(0); }
    .toast.success { background-color: #10B981; }
    .toast.error { background-color: #EF4444; }

    /* Modal Styles */
    .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 50; }
    .modal-content { background: white; border-radius: 16px; width: 100%; max-width: 900px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .bulk-table-container { max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; }
    .bulk-table { width: 100%; border-collapse: collapse; }
    .bulk-table th { position: sticky; top: 0; background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #64748b; border-bottom: 1px solid #e5e7eb; }
    .bulk-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    .bulk-table tr:hover { background: #f8fafc; cursor: pointer; }
    .bulk-table tr.selected { background: #f0f9ff; }
    .search-container { position: relative; }
    .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .search-input { padding-left: 36px; border-radius: 8px; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .search-input:focus { outline: none; border-color: #6366f1; ring: 2px solid #6366f1; }

    /* Custom Confirmation Modal Styling */
    .confirm-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 100; align-items: center; justify-content: center; }
    .confirm-modal-card { background: white; border-radius: 20px; width: 400px; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); text-align: center; transform: scale(0.9); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); opacity: 0; }
    .confirm-modal-card.show { transform: scale(1); opacity: 1; }
    .confirm-icon { width: 64px; height: 64px; background: #eef2ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 20px; }

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: bold;
        z-index: 1000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease-in-out;
    }
    
    .toast.show {
        opacity: 1;
        transform: translateX(0);
    }
    
    .toast.success {
        background-color: #10B981;
    }
    
    .toast.error {
        background-color: #EF4444;
    }
    </style>
</head>

<body class="bg-white">
    <?php include('sidebar.php'); ?>

    <!-- Success Message Toast -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div id="successToast" class="toast success">
            <?php echo $_SESSION['success_message']; ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <div class="overflow-y-auto h-full px-6">
        <div class="flex justify-between items-center px-6 py-6 font-poppins">
            <h1 class="text-2xl">Receivables</h1>
            <div class="text-sm">
                <a href="dashboard.php?page=dashboard" class="text-black hover:text-blue-600">Home</a>
                /
                <a class="text-black">Accounts Receivable</a>
                /
                <a href="receivables.php?page=receivables" class="text-blue-600 hover:text-blue-600">Receivables</a>
            </div>
        </div>
      
        
        <div class="flex-1 bg-white p-6 h-full w-full">
            <div class="w-full">
                <!-- Tabs + Filters -->
                <div class="mb-6 flex items-center gap-6 flex-wrap border-b border-gray-200">
                    <!-- Tabs -->
                    <div class="flex space-x-4 font-poppins text-sm font-medium">
                        <button class="tab-btn px-4 py-2 text-gray-700 tab-active" onclick="showTab('invoices')">Approved Invoices</button>
                        <button class="tab-btn px-4 py-2 text-gray-700" onclick="showTab('aging')">Aging Summary</button>
                        <button class="tab-btn px-4 py-2 text-gray-700" onclick="showTab('outstanding')">Outstanding Summary</button>
                    </div>
                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-4 mb-4 flex-grow justify-end">
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input border px-3 py-2 rounded-full text-m font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400 w-64" 
                            placeholder="Search invoices..." 
                            onkeyup="filterTable()" />
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <label for="dueDate" class="font-semibold text-gray-600 text-sm">Due Date:</label>
                            <input 
                            type="date" 
                            id="dueDate" 
                            class="border border-gray-300 rounded-lg px-2 py-1 shadow-sm text-sm" 
                            onchange="filterTable()" />
                        </div>

                        <button id="bulkCollectBtn" onclick="openBulkCollectModal()" class="flex items-center gap-2 px-5 py-2 bg-emerald-600 text-white rounded-full font-bold text-sm hover:bg-emerald-700 transition-all shadow-md shadow-emerald-100">
                            <i class="fas fa-layer-group"></i>
                            <span>Bulk Collect</span>
                        </button>
                    </div>
                </div>

                <!-- Approved Invoices Tab -->
                <div id="tab-invoices">
                    <!-- Main content area -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
                        <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                            <i class="far fa-file-alt text-xl"></i>
                            <h2 class="text-2xl font-poppins text-black">Approved Invoices</h2>
                        </div>

                        <div class="overflow-x-auto w-full">
                        <table class="w-full table-auto bg-white mt-4">
                            <thead>
                                <tr class="text-blue-800 uppercase text-xs leading-normal text-left">
                                    <th class="pl-10 pr-6 py-4">Invoice ID</th>
                                    <th class="px-6 py-4">Driver Name</th>
                                    <th class="px-6 py-4">Description</th>
                                    <th class="px-6 py-4 text-right">Amount</th>
                                    <th class="px-6 py-4 text-center">Age</th>
                                    <th class="px-6 py-4 text-right">Remaining Balance</th>
                                    <th class="px-6 py-4 text-center">Approved Date</th>
                                    <th class="px-6 py-4 text-center">Due Date</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="invoiceTable" class="text-gray-900 text-sm font-light">
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $remaining = $row['amount'] - $row['amount_paid'];
                                        if ($remaining > 0.00001) {
                                            $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                                            echo "<tr class='hover:bg-violet-50 border-b border-gray-100 transition-colors'>";
                                            echo "<td class='pl-10 pr-6 py-4 font-mono font-bold text-gray-900'>{$row['invoice_id']}</td>";
                                            echo "<td class='px-6 py-4 font-semibold text-gray-800'>{$row['driver_name']}</td>";
                                            echo "<td class='px-6 py-4 text-gray-600 italic max-w-xs truncate'>{$row['description']}</td>";
                                            echo "<td class='px-6 py-4 text-indigo-700 font-bold text-right'>₱ " . number_format($row['amount'], 2) . "</td>";
                                            
                                            // Calculate Age
                                            $approval_date = new DateTime($row['approval_date']);
                                            $today = new DateTime();
                                            $age = $approval_date->diff($today)->days;
                                            $age_class = ($age <= 30) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                                            $age_label = $age . "d";
                                            
                                            echo "<td class='px-6 py-4 text-center'><span class='px-4 py-1 rounded-full font-bold text-xs inline-block min-w-[50px] text-center $age_class'>$age_label</span></td>";
                                            
                                            $rbClass = ($remaining > 0) ? "text-red-700 font-black" : "text-green-700 font-black";
                                            echo "<td class='px-6 py-4 $rbClass text-lg text-right'>₱ " . number_format($remaining, 2) . "</td>";
                                            
                                            echo "<td class='px-6 py-4 text-center font-medium text-gray-500'>" . date('Y-m-d', strtotime($row['approval_date'])) . "</td>";
                                            echo "<td class='px-6 py-4 text-center font-bold text-indigo-900'>" . date('Y-m-d', strtotime($row['fully_paid_date'])) . "</td>";
                                            
                                            echo "<td class='py-4 text-right'>";
                                            echo "<button onclick='openPaymentModal($jsonData)' class='px-4 py-1.5 bg-indigo-600 text-white hover:bg-indigo-700 rounded-lg font-bold transition-all shadow-sm'>Collect</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    }
                                } else {
                                    echo "<tr><td colspan='8' class='text-center py-4'>No records found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <div id="pageStatus" class="text-gray-700 font-bold"></div>
                        <div class="flex">
                            <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500" onclick="prevPage()">Previous</button>
                            <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500" onclick="nextPage()">Next</button>
                        </div>
                    </div>
                    <div class="mt-6">
                        <canvas id="pdf-viewer" width="600" height="400"></canvas>
                    </div>
                </div>

                <!-- Aging Summary Tab -->
                <div id="tab-aging" class="hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-4 space-x-3 text-purple-700">
                            <i class="far fa-clock text-xl"></i>
                            <h2 class="text-2xl font-poppins text-black">Aging Summary Report</h2>
                        </div>
                        <table class="min-w-full bg-white mt-4">
                            <thead>
                                <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                    <th class="px-4 py-2">Bracket</th>
                                    <th class="px-4 py-2">Number of Invoices</th>
                                    <th class="px-4 py-2">Outstanding Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aging_data as $label => $ag): ?>
                                <tr>
                                    <td class="px-4 py-2"><?= $label ?></td>
                                    <td class="px-4 py-2"><?= $ag['count'] ?></td>
                                    <td class="px-4 py-2">₱ <?= number_format($ag['total'], 2) ?></td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Outstanding Summary Tab -->
                <div id="tab-outstanding" class="hidden">
                    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-4 space-x-3 text-purple-700">
                            <i class="far fa-list-alt text-xl"></i>
                            <h2 class="text-2xl font-poppins text-black">Outstanding Summary Report</h2>
                        </div>
                        <table class="min-w-full bg-white mt-4">
                            <thead>
                                <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                    <th class="px-4 py-2">Driver Name</th>
                                    <th class="px-4 py-2">Count</th>
                                    <th class="px-4 py-2">Outstanding Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($outstanding_data as $out): ?>
                                <tr>
                                    <td class="px-4 py-2"><?= htmlspecialchars($out['driver_name']) ?></td>
                                    <td class="px-4 py-2"><?= $out['count'] ?></td>
                                    <td class="px-4 py-2">₱ <?= number_format($out['outstanding'], 2) ?></td>
                                </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
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
                                <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-emerald-500 pl-4">Bulk Collection Processing</h2>
                                <p class="text-gray-500 text-sm mt-1 ml-4">Select multiple confirmed invoices to process payments in batch</p>
                            </div>
                            <button onclick="closeBulkModal('bulkCollectModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <div class="mb-6">
                            <div class="search-container">
                                <i class="fas fa-search search-icon"></i>
                                <input type="text" id="bulkSearchInput" class="search-input w-full py-2.5" placeholder="Search by Invoice ID or Driver Name..." onkeyup="filterBulkModal()">
                            </div>
                        </div>

                        <div class="bulk-table-container mb-6 bg-gray-50">
                            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-white">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" id="selectAllBulk" onchange="toggleSelectAllBulk()">
                                    <label for="selectAllBulk" class="text-sm font-bold text-gray-700 cursor-pointer">Select All Pending</label>
                                </div>
                                <span id="bulkSummaryText" class="text-sm text-emerald-600 font-bold">0 items selected</span>
                            </div>
                            <table class="bulk-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px" class="text-center"></th>
                                        <th>INVOICE ID</th>
                                        <th>DRIVER NAME</th>
                                        <th class="text-right">REMAINING BALANCE</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkInvoicesTable">
                                    <!-- Populated by AJAX -->
                                </tbody>
                            </table>
                        </div>

                        <div class="bg-emerald-50 p-6 rounded-xl border border-emerald-100 mb-8 flex items-center justify-between">
                            <div>
                                <p class="text-emerald-600 text-sm font-semibold uppercase tracking-wider mb-1">Total Batch Collection</p>
                                <h3 class="text-3xl font-bold text-emerald-900" id="selectedTotalAmount">₱0.00</h3>
                            </div>
                            <div class="text-right">
                                <p class="text-gray-500 text-sm mb-1">Batch Count</p>
                                <p class="text-2xl font-bold text-gray-800" id="selectedCountValue">0 Invoices</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-4">
                            <button onclick="closeBulkModal('bulkCollectModal')" class="px-6 py-2.5 text-sm font-bold text-gray-600 hover:text-gray-800 transition-colors">No, Cancel</button>
                            <button id="btnProcessBulk" class="px-8 py-2.5 bg-emerald-600 text-white rounded-lg font-bold shadow-lg shadow-emerald-200 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all" onclick="processBulkCollect()" disabled>
                                Process Batch Payments
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div id="paymentModal" class="modal-overlay">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="modal-content max-w-md">
                    <div class="p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-indigo-500 pl-4">Payment Details</h2>
                                <p class="text-gray-500 text-sm mt-1 ml-4" id="singlePaymentTitle">Process collection for this invoice</p>
                            </div>
                            <button onclick="closeBulkModal('paymentModal')" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <form method="POST" action="receivables.php?page=receivables">
                            <input type="hidden" name="invoice_id" id="invoice_id">
                            <div class="space-y-4 mb-8">
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                                    <p class="text-xs text-gray-500 font-bold uppercase mb-1">Driver Name</p>
                                    <p class="font-bold text-gray-900" id="displayDriverName">---</p>
                                </div>
                                <div>
                                    <label for="amount_pay" class="block text-sm font-bold text-gray-700 mb-2">Amount to Receive</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-gray-400">₱</span>
                                        <input type="number" step="0.01" name="amount_pay" id="amount_pay" class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-bold text-indigo-700" required>
                                    </div>
                                    <p class="text-[11px] text-gray-400 mt-2 font-medium" id="remainingBalanceHint"></p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3">
                                <button type="button" onclick="closeBulkModal('paymentModal')" class="px-6 py-2.5 text-sm font-bold text-gray-600">Cancel</button>
                                <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white rounded-lg font-bold shadow-lg shadow-indigo-100 hover:bg-indigo-700 transition-all">
                                    Process Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

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

<script>
    // State management
    window.bulkInvoices = [];
    window.selectedBulkIds = new Set();
    const table = document.querySelector("#invoiceTable");
    let allRows = Array.from(table.querySelectorAll("tr"));
    let currentPage = 1;
    const rowsPerPage = 10;

    // Toast and Initialization
    document.addEventListener('DOMContentLoaded', () => {
        const successToast = document.getElementById('successToast');
        if (successToast) {
            setTimeout(() => successToast.classList.add('show'), 100);
            setTimeout(() => {
                successToast.classList.remove('show');
                setTimeout(() => successToast?.remove(), 300);
            }, 3000);
        }
        displayData(1);
        showTab('invoices');
    });

    // Modal Generic
    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeBulkModal(id) { document.getElementById(id).style.display = 'none'; }

    // Custom Confirmation
    function showCustomConfirm(title, message, callback) {
        const overlay = document.getElementById('confirmModalOverlay');
        const card = document.getElementById('confirmModalCard');
        const proceedBtn = document.getElementById('confirmProceedBtn');
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        overlay.style.display = 'flex';
        setTimeout(() => card.classList.add('show'), 10);
        proceedBtn.onclick = () => { closeConfirmModal(); callback(); };
    }

    function closeConfirmModal() {
        const overlay = document.getElementById('confirmModalOverlay');
        const card = document.getElementById('confirmModalCard');
        card.classList.remove('show');
        setTimeout(() => overlay.style.display = 'none', 300);
    }

    // Tabs
    function showTab(tab) {
        ['invoices', 'aging', 'outstanding'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden'));
        document.getElementById('tab-' + tab).classList.remove('hidden');
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('tab-active'));
        const tabIdx = tab === 'invoices' ? 0 : tab === 'aging' ? 1 : 2;
        document.querySelectorAll('.tab-btn')[tabIdx].classList.add('tab-active');
        document.getElementById('bulkCollectBtn').style.display = tab === 'invoices' ? 'flex' : 'none';
    }

    // Formatting
    function formatMoney(amount) {
        return Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Main Table Display
    function displayData(page) {
        currentPage = page;
        const filteredRows = filterRows();
        const start = (currentPage - 1) * rowsPerPage;
        const paginatedRows = filteredRows.slice(start, start + rowsPerPage);

        table.innerHTML = "";
        if (paginatedRows.length === 0) {
            table.innerHTML = "<tr><td colspan='9' class='text-center py-12 text-gray-400'><i class='fas fa-inbox text-4xl mb-3 block opacity-20'></i> No records found</td></tr>";
        } else {
            paginatedRows.forEach(row => table.appendChild(row));
        }

        document.getElementById("prevPage").disabled = currentPage === 1;
        document.getElementById("nextPage").disabled = (start + rowsPerPage) >= filteredRows.length;
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        document.getElementById("pageStatus").textContent = `Page ${currentPage} of ${totalPages}`;
    }

    function filterRows() {
        const searchInput = document.getElementById("searchInput").value.toLowerCase();
        const dueDate = document.getElementById("dueDate").value;
        return allRows.filter(row => {
            const cells = row.children;
            if (cells.length < 8) return false;
            const invoiceId = cells[0].textContent.toLowerCase();
            const driverName = cells[1].textContent.toLowerCase();
            const description = cells[2].textContent.toLowerCase();
            const rowDate = cells[7].textContent.trim();
            const matchesSearch = invoiceId.includes(searchInput) || driverName.includes(searchInput) || description.includes(searchInput);
            const matchesDate = !dueDate || rowDate === dueDate;
            return matchesSearch && matchesDate;
        });
    }

    function filterTable() { displayData(1); }
    function prevPage() { if (currentPage > 1) displayData(currentPage - 1); }
    function nextPage() { if ((currentPage * rowsPerPage) < filterRows().length) displayData(currentPage + 1); }

    // BULK COLLECT LOGIC
    function openBulkCollectModal() {
        const tbody = document.getElementById('bulkInvoicesTable');
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-12 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Loading approved invoices...</td></tr>';
        document.getElementById('bulkSearchInput').value = '';
        window.selectedBulkIds = new Set();
        updateBulkSummary();
        openModal('bulkCollectModal');

        fetch('receivables.php?action=get_pending')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.bulkInvoices = data.data;
                    renderBulkInvoices();
                } else {
                    showToast(data.message || 'Error loading invoices', 'error');
                    tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-12 text-center text-red-500">Failed to load data</td></tr>';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Network error occurred', 'error');
                tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-12 text-center text-red-500">Network error</td></tr>';
            });
    }

    function filterBulkModal() { renderBulkInvoices(); }

    function renderBulkInvoices() {
        const tbody = document.getElementById('bulkInvoicesTable');
        const searchTerm = document.getElementById('bulkSearchInput').value.toLowerCase();
        const filtered = window.bulkInvoices.filter(row => 
            row.invoice_id.toLowerCase().includes(searchTerm) || 
            row.driver_name.toLowerCase().includes(searchTerm)
        );

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No matching invoices found</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(row => {
            const remaining = row.amount - row.amount_paid;
            const key = `${row.invoice_id}|${remaining}`;
            const isSelected = window.selectedBulkIds.has(key);
            return `
                <tr class="${isSelected ? 'selected' : ''}" onclick="toggleBulkRow(this, '${key}')">
                    <td class="text-center"><input type="checkbox" ${isSelected ? 'checked' : ''} onclick="event.stopPropagation(); toggleBulkRow(this.closest('tr'), '${key}')"></td>
                    <td class="font-mono font-bold text-gray-900">${row.invoice_id}</td>
                    <td class="font-semibold text-gray-700">${row.driver_name}</td>
                    <td class="text-right font-black text-emerald-700">₱${formatMoney(remaining)}</td>
                </tr>
            `;
        }).join('');
        updateSelectAllState();
    }

    function toggleBulkRow(row, key) {
        const checkbox = row.querySelector('input[type="checkbox"]');
        if (window.selectedBulkIds.has(key)) {
            window.selectedBulkIds.delete(key);
            row.classList.remove('selected');
            checkbox.checked = false;
        } else {
            window.selectedBulkIds.add(key);
            row.classList.add('selected');
            checkbox.checked = true;
        }
        updateBulkSummary();
        updateSelectAllState();
    }

    function toggleSelectAllBulk() {
        const selectAll = document.getElementById('selectAllBulk');
        const searchTerm = document.getElementById('bulkSearchInput').value.toLowerCase();
        const filtered = window.bulkInvoices.filter(row => 
            row.invoice_id.toLowerCase().includes(searchTerm) || 
            row.driver_name.toLowerCase().includes(searchTerm)
        );

        filtered.forEach(row => {
            const remaining = row.amount - row.amount_paid;
            const key = `${row.invoice_id}|${remaining}`;
            if (selectAll.checked) window.selectedBulkIds.add(key);
            else window.selectedBulkIds.delete(key);
        });
        renderBulkInvoices();
        updateBulkSummary();
    }

    function updateSelectAllState() {
        const selectAll = document.getElementById('selectAllBulk');
        const checkboxes = document.querySelectorAll('#bulkInvoicesTable input[type="checkbox"]');
        if (checkboxes.length === 0) {
            selectAll.checked = false;
            return;
        }
        selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
    }

    function updateBulkSummary() {
        let total = 0;
        window.selectedBulkIds.forEach(key => {
            total += parseFloat(key.split('|')[1]);
        });
        document.getElementById('selectedTotalAmount').textContent = '₱' + formatMoney(total);
        document.getElementById('selectedCountValue').textContent = `${window.selectedBulkIds.size} Invoices`;
        document.getElementById('bulkSummaryText').textContent = `${window.selectedBulkIds.size} items selected`;
        document.getElementById('btnProcessBulk').disabled = window.selectedBulkIds.size === 0;
    }

    function processBulkCollect() {
        const ids = Array.from(window.selectedBulkIds);
        showCustomConfirm(
            'Process Batch Payments',
            `Are you sure you want to collect payments for ${ids.length} selected invoices?`,
            () => {
                const btn = document.getElementById('btnProcessBulk');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';

                const formData = new FormData();
                formData.append('action', 'bulk_collect');
                ids.forEach(id => formData.append('invoice_ids[]', id));

                fetch('receivables.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(data.message, 'error');
                        btn.disabled = false;
                        btn.textContent = 'Process Batch Payments';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    btn.disabled = false;
                    btn.textContent = 'Process Batch Payments';
                });
            }
        );
    }

    function showToast(msg, type) {
        const toast = document.createElement('div');
        toast.className = `toast show ${type}`;
        toast.textContent = msg;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function openPaymentModal(data) {
        const remaining = data.amount - data.amount_paid;
        document.getElementById("invoice_id").value = data.invoice_id;
        document.getElementById("displayDriverName").textContent = data.driver_name;
        document.getElementById("amount_pay").value = remaining.toFixed(2);
        document.getElementById("amount_pay").max = remaining;
        document.getElementById("remainingBalanceHint").textContent = `* Maximum remaining balance: ₱${formatMoney(remaining)}`;
        openModal('paymentModal');
    }
</script>
</body>
</html>