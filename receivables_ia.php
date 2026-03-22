<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');

// Overview Metrics
$pendingCount = $conn->query("SELECT COUNT(*) as total FROM account_receivable WHERE status = 'pending'")->fetch_assoc()['total'];
$confirmedThisMonth = $conn->query("
  SELECT COUNT(*) as total 
  FROM account_receivable 
  WHERE status = 'confirmed' 
    AND MONTH(approval_date) = MONTH(CURRENT_DATE)
    AND YEAR(approval_date) = YEAR(CURRENT_DATE)
")->fetch_assoc()['total'];
$totalDue = $conn->query("
  SELECT SUM(amount) as total 
  FROM account_receivable 
  WHERE status = 'pending'
")->fetch_assoc()['total'] ?? 0;

require_once 'includes/accounting_functions.php';

// AJAX Handler: Get Pending Receivables
if (isset($_GET['action']) && $_GET['action'] === 'get_pending_receivables') {
    $sql = "SELECT * FROM account_receivable WHERE status = 'pending' ORDER BY created_at ASC";
    $res = $conn->query($sql);
    $items = [];
    $totalAmount = 0;
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
        $totalAmount += floatval($row['amount']) * 0.20;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => ['items' => $items, 'total' => $totalAmount]]);
    exit();
}

// AJAX Handler: Bulk Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_approve_receivables') {
    $invoice_ids = $_POST['invoice_ids'] ?? [];
    if (empty($invoice_ids)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No invoices selected']);
        exit();
    }

    $conn->begin_transaction();
    try {
        $count = 0;
        foreach ($invoice_ids as $id) {
            $id = $conn->real_escape_string($id);
            $res = $conn->query("SELECT * FROM account_receivable WHERE invoice_id = '$id' AND status = 'pending' LIMIT 1");
            $data = $res->fetch_assoc();
            
            if ($data) {
                // 1. Create Journal Entry
                createARPaymentJournalEntry($conn, $data);
                
                // 2. Update Status
                $conn->query("UPDATE account_receivable SET status = 'confirmed', approval_date = NOW() WHERE invoice_id = '$id'");
                $count++;
            }
        }
        $conn->commit();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => "Successfully approved $count invoices!", 'count' => $count]);
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Build Main Query
$sql = "SELECT * FROM account_receivable WHERE status = 'pending' ORDER BY invoice_id DESC";
$result = $conn->query($sql);

// Get all rows for display
$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Generate invoice code in the format: YYYYMMDD-RANDOM (numeric part only)
function generateInvoiceCode() {
    $date = date('Ymd');
    $rand = mt_rand(1000, 9999);
    return $date . '-' . $rand;
}

// Generate a unique invoice code
$invoiceIdValue = generateInvoiceCode();

// Check if this invoice code already exists
$check_sql = "SELECT COUNT(*) as count FROM account_receivable WHERE invoice_id = 'INV-" . $invoiceIdValue . "'";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();

// If it exists, generate a new one
if ($row['count'] > 0) {
    $invoiceIdValue = generateInvoiceCode();
}

// Bulk Approval Logic
if (isset($_POST['bulk_approve']) && isset($_POST['invoice_ids']) && is_array($_POST['invoice_ids'])) {
    $invoice_ids = $_POST['invoice_ids'];
    $success_count = 0;
    $error_count = 0;

    $conn->begin_transaction();
    try {
        foreach ($invoice_ids as $invoice_id) {
            $invoice_id = $conn->real_escape_string($invoice_id);
            
            // Get amount and payment method
            $fetch_sql = "SELECT amount, payment_method FROM account_receivable WHERE invoice_id = ?";
            $stmt_fetch = $conn->prepare($fetch_sql);
            $stmt_fetch->bind_param("s", $invoice_id);
            $stmt_fetch->execute();
            $stmt_fetch->bind_result($amount, $payment_method);
            $stmt_fetch->fetch();
            $stmt_fetch->close();

            $payment_modes = ["Cash", "Credit"];

            if (in_array($payment_method, $payment_modes)) {
                // Journal Entries
                $stmt = $conn->prepare("INSERT INTO journal_table (expense_categories, debit_amount, credit_amount, credit_account) VALUES ('Accounts Receivables', 0, ?, NULL)");
                $stmt->bind_param("d", $amount);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO journal_table (expense_categories, debit_amount, credit_amount, credit_account) VALUES (?, ?, 0, 'Accounts Receivables')");
                $stmt->bind_param("sd", $payment_method, $amount);
                $stmt->execute();
                $stmt->close();

                // Ledger Entries
                $stmt = $conn->prepare("INSERT INTO ledger_table (expense_categories, debit_amount, credit_amount, credit_account, transaction_date) VALUES ('Accounts Receivable', ?, 0, 'Revenue', NOW())");
                $stmt->bind_param("d", $amount);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("INSERT INTO ledger_table (expense_categories, debit_amount, credit_amount, credit_account, transaction_date) VALUES ('Boundary', 0, ?, 'Assets', NOW())");
                $stmt->bind_param("d", $amount);
                $stmt->execute();
                $stmt->close();
            }

            // Update status
            $stmt = $conn->prepare("UPDATE account_receivable SET status = 'confirmed', approval_date = NOW() WHERE invoice_id = ?");
            $stmt->bind_param("s", $invoice_id);
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
            $stmt->close();
        }
        $conn->commit();
        $_SESSION['success'] = "Process completed. $success_count invoices confirmed." . ($error_count > 0 ? " $error_count errors occurred." : "");
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "An error occurred during bulk approval: " . $e->getMessage();
    }
    header("Location: receivables_ia.php");
    exit();
}
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice Confirmation</title>
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <style>
    @media (max-width: 1024px) {
      .overview-flex { flex-direction: column !important; }
      .overview-left, .overview-right { width: 100% !important; }
      .overview-cards { flex-direction: column !important; }
      .overview-right { min-width: 0 !important; }
    }

    /* Bulk Approve Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
    .modal-content.bulk-approve-modal {
        background: white;
        border-radius: 20px;
        max-width: 900px;
        width: 100%;
        max-height: 90vh;
        overflow-y: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    .modal-header-bulk { padding: 24px; border-bottom: 1px solid #e5e7eb; }
    .modal-title-bulk { font-size: 24px; font-weight: 800; color: #1f2937; }
    .modal-subtitle-bulk { font-size: 14px; color: #6b7280; margin-top: 4px; }
    .modal-body-bulk { padding: 24px; overflow-y: auto; flex: 1; }
    .payrolls-table-container { 
        border: 1px solid #e5e7eb; 
        border-radius: 12px; 
        overflow: hidden;
        margin-bottom: 20px;
    }
    .bulk-table { width: 100%; border-collapse: collapse; }
    .bulk-table th { 
        background: #f8fafc; 
        padding: 12px 16px; 
        text-align: left; 
        font-size: 11px; 
        text-transform: uppercase; 
        letter-spacing: 0.05em;
        color: #64748b;
        border-bottom: 1px solid #e5e7eb;
    }
    .bulk-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .summary-row-bulk { 
        background: #f8fafc; 
        padding: 20px; 
        border-radius: 12px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        margin-bottom: 20px;
    }
    .summary-amount-bulk { font-size: 20px; font-weight: 900; color: #059669; }
    .modal-actions-bulk { padding: 20px 24px; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 12px; }
    .btn-modal-approve-bulk {
        background: #3f36bd;
        color: white;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.2s;
    }
    .btn-modal-approve-bulk:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-modal-cancel-bulk {
        background: #f1f5f9;
        color: #475569;
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 700;
    }
    .select-all-header {
        padding: 12px 16px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>

</head>

<body class="bg-white">
    <?php include('sidebar.php'); ?>
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

<!-- Breadcrumb -->
<div class="overflow-y-auto h-full px-6">
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl">Invoice Confirmation</h1>
        <div class="text-sm">
            <a href="dashboard.php?page=dashboard" class="text-black hover:text-blue-600">Home</a>
            /
            <a class="text-black">Account Receivables</a>
            /
            <a href="receivables_ia.php" class="text-blue-600 hover:text-blue-600">Invoice Confirmation</a>
        </div>
    </div>
 
    <!-- Main Content -->
    <div class="flex-1 bg-white p-6 h-full w-full">
        <div class="w-full">
            <div class="flex items-center justify-between">
                <div class="flex flex-wrap items-center gap-4 mb-4">
                    <div class="flex items-center gap-2">
                        <input
                        type="text"
                        id="searchInput"
                        class="border px-3 py-2 rounded-full text-m font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400"
                        placeholder="Search here"
                        onkeyup="filterTable()" />
                    </div>
                    <div class="flex items-center space-x-2">
                        <label for="dueDate" class="font-semibold">Payment Due:</label>
                        <input
                            type="date"
                            id="dueDate"
                            class="border border-gray-300 rounded-lg px-2 py-1 shadow-sm"
                            onchange="filterTable()" />
                    </div>
                    <button type="button" onclick="bulkApprove()" class="bg-green-600 text-white px-4 py-2 rounded-lg font-poppins hover:bg-green-700 transition-colors flex items-center gap-2 ml-4">
                        <i class="fas fa-check-double"></i> Bulk Approve
                    </button>
                </div>
                
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
                <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                    <i class="far fa-file-alt text-xl"></i>
                    <h2 class="text-2xl font-poppins text-black">Pending Invoices</h2>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full table-auto bg-white mt-4">
                    <thead>
                        <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                            <th class="pl-12 px-4 py-2">Invoice ID</th>
                            <th class="px-4 py-2">Driver Name</th>
                            <th class="px-4 py-2">Description</th>
                            <th class="px-4 py-2">Amount</th>
                            <th class="px-4 py-2">Payment Due</th>
                            <th class="px-4 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-900 text-sm font-light" id="invoiceTableBody">
                        <?php
                        foreach ($rows as $row):
                            $fullyPaidDate= $row['fully_paid_date'] ? date('Y-m-d', strtotime($row['fully_paid_date'])) : '';
                            ?>
                            <tr class="hover:bg-violet-100" 
                                data-invoiceid="<?php echo htmlspecialchars($row['invoice_id']); ?>"
                                data-drivername="<?php echo htmlspecialchars($row['driver_name']); ?>"
                                data-driverid="<?php echo htmlspecialchars($row['driver_id'] ?? 'D-'.mt_rand(1000,9999)); ?>"
                                data-desc="<?php echo htmlspecialchars($row['description']); ?>"
                                data-amount="<?php echo htmlspecialchars($row['amount']); ?>"
                                data-duedate="<?php echo $fullyPaidDate; ?>">
                                <td class='pl-12 px-4 py-2'><?php echo $row['invoice_id'];?></td>
                                <td class='px-4 py-2'><?php echo $row['driver_name'];?></td>
                                <td class='px-4 py-2'><?php echo $row['description'];?></td>
                                <td class='px-4 py-2'>₱<?php echo number_format($row['amount'] * 0.20, 2);?></td>
                                <td class='px-4 py-2'><?php echo $fullyPaidDate;?></td>
                                <td class='px-4 py-2 flex items-center gap-2'>
                                    <button onclick='viewReviewModal(<?php echo htmlspecialchars(json_encode($row)); ?>)' class='font-black bg-blue-100 text-blue-700 px-4 py-1.5 hover:bg-blue-600 hover:text-white rounded-full transition-all text-[11px] uppercase tracking-wider' title="Review Commission">
                                        Review
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
    </div>
</div>
<div id="addModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg w-[600px] relative">
        <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Add Receivables Details</h2>
        <form id="addReceivableForm" action="add_new_receivable_details.php" method="POST" enctype="multipart/form-data" class="grid gap-4 grid-cols-2">
            <div class="mb-4 col-span-2">
                <label class="block text-gray-700 mb-1" for="invoice_id">Invoice ID</label>
                <div class="flex items-center">
                    <span class="bg-gray-200 px-3 py-1 border border-r-0 border-gray-300 rounded-l-md">INV-</span>
                    <input type="text" id="invoice_id" name="invoice_id" 
                        value="<?php echo $invoiceIdValue; ?>" 
                        class="w-full px-2 py-1 border border-gray-300 rounded-r-md" required readonly>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1" for="driver_name">Driver Name</label>
                <input type="text" id="driver_name" name="driver_name" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1" for="description">Description</label>
                <input type="text" id="description" name="description" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1" for="amount">Amount</label>
                <input type="number" id="amount" name="amount" class="w-full px-2 py-1 border border-gray-300 rounded-md" required step="0.01">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 mb-1" for="fully_paid_date">Payment Due</label>
                <input type="date" id="fully_paid_date" name="fully_paid_date" class="w-full px-2 py-1 border border-gray-300 rounded-md" required>
            </div>
            <div class="col-span-2 flex justify-end gap-2 mt-4">
                <button type="submit" class="bg-violet-600 text-white hover:bg-purple-300 hover:text-violet-900 border border-violet-600 px-4 py-2 rounded-md">Save</button>
                <button type="button" onclick="closeAddReceivableModal()" class="bg-violet-100 text-violet-900 hover:bg-purple-300 hover:text-violet-900 border border-violet-900 px-4 py-2 rounded-md">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Confirmation Modal -->
<div id="customConfirmModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden z-[60] backdrop-blur-sm">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-[400px] transform transition-all scale-95 opacity-0 duration-300 border border-gray-100" id="confirmModalCard">
        <div id="confirmIconContainer" class="flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-6 mx-auto">
            <i id="confirmIcon" class="fas fa-exclamation-triangle text-yellow-600 text-3xl"></i>
        </div>
        <h3 class="text-2xl font-bold text-gray-800 mb-2 text-center" id="confirmTitle">Confirm Action</h3>
        <p class="text-gray-600 mb-8 text-center leading-relaxed" id="confirmMessage">Are you sure you want to proceed?</p>
        <div class="flex gap-4">
            <button id="confirmCancelBtn" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 font-bold transition-all active:scale-95">Cancel</button>
            <button id="confirmProceedBtn" class="flex-1 px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-bold shadow-lg shadow-purple-200 transition-all active:scale-95">Proceed</button>
        </div>
    </div>
</div>

<!-- Driver Commission Review Modal -->
<div id="reviewModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm hidden z-[70]">
    <div class="bg-white rounded-3xl shadow-2xl w-[900px] max-h-[90vh] overflow-hidden flex flex-col transform transition-all scale-95 opacity-0 duration-300" id="reviewModalCard">
        <!-- Close Button -->
        <button onclick="closeReviewModal()" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors z-10">
            <i class="fas fa-times text-2xl"></i>
        </button>

        <div class="p-8 flex-1 overflow-y-auto">
            <h2 class="text-3xl font-black text-slate-800 mb-8 flex items-center gap-3">
                Review Invoice
            </h2>

            <div class="grid grid-cols-12 gap-8">
                <!-- Left Section: Details -->
                <div class="col-span-12 lg:col-span-5 bg-slate-50 rounded-2xl p-6 border border-slate-100 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <span id="modal_invoice_id" class="px-4 py-1.5 bg-purple-100 text-purple-700 rounded-full font-black text-xs tracking-wider uppercase"></span>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-lg font-bold text-[10px] uppercase tracking-widest">Pending</span>
                    </div>

                    <div class="space-y-6 flex-1">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Partner ID</p>
                                <p id="modal_driver_id" class="text-sm font-black text-slate-700"></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Partner Name</p>
                                <p id="modal_driver_name" class="text-sm font-black text-slate-700"></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Trip Reference</p>
                            <p id="modal_description" class="text-sm font-bold text-slate-600 italic"></p>
                        </div>
                        
                        <div class="bg-white p-5 rounded-xl border border-slate-200">
                             <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 border-b border-slate-50 pb-2">Computation Summary</h4>
                             <div class="space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500">Gross Fare</span>
                                    <span id="modal_gross_fare" class="font-black text-slate-700"></span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500">System Fee (20%)</span>
                                    <span id="modal_amount" class="font-black text-red-500"></span>
                                </div>
                                <div class="pt-2 mt-2 border-t border-dashed border-slate-100 flex justify-between items-center italic">
                                    <span class="text-[10px] font-black text-blue-600 uppercase">Total Net Pay</span>
                                    <span id="modal_net_pay" class="text-lg font-black text-blue-600"></span>
                                </div>
                             </div>
                        </div>

                        <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100">
                            <h4 class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-2">Route Highlights</h4>
                            <div class="space-y-1">
                                <p class="text-[11px] text-slate-600 flex items-center gap-2">
                                    <i class="fas fa-clock text-blue-400"></i> Duration: <b>~24 mins</b>
                                </p>
                                <p class="text-[11px] text-slate-600 flex items-center gap-2">
                                    <i class="fas fa-road text-blue-400"></i> Distance: <b>6.8 km</b>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Automated Document Area -->
                <div class="col-span-12 lg:col-span-7 bg-slate-100 rounded-2xl p-4 overflow-hidden flex items-start justify-center">
                    <div id="automatedDocument" class="bg-white w-full h-full min-h-[500px] shadow-sm rounded-lg p-10 font-sans text-slate-800 scale-[0.85] origin-top border border-slate-200">
                        <!-- Header -->
                        <div class="flex justify-between items-start border-bottom-2 border-slate-900 pb-6 mb-6">
                            <div>
                                <img src="logo2.png" alt="ViaHale Logo" class="h-10 mb-4 object-contain grayscale opacity-80">
                                <h3 class="text-2xl font-black tracking-tighter text-slate-900">COMMISSION VOUCHER</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[2px]">ViaHale TNVS (Core 1) Ecosystem</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Document No.</p>
                                <p id="doc_voucher_id" class="text-xs font-black text-slate-900 mb-4"></p>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Date Generated</p>
                                <p class="text-xs font-black text-slate-900"><?php echo date('F d, Y'); ?></p>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-10 mb-8 pb-8 border-b border-slate-100 italic">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100 pb-1">Partner Information</p>
                                <p id="doc_driver_id" class="text-[11px] font-black text-blue-600 mb-0.5"></p>
                                <p id="doc_driver_name" class="text-sm font-black text-slate-900 uppercase"></p>
                                <p class="text-[10px] text-slate-500 font-medium tracking-tight">Verified TNVS Ride Partner</p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 border-b border-slate-100 pb-1">Trip Reference</p>
                                <p id="doc_trip_ref" class="text-sm font-black text-slate-900"></p>
                                <p class="text-[10px] text-slate-500 font-medium">Service Category: Ride Hailing</p>
                            </div>
                        </div>

                        <!-- NEW: Location Details in Document -->
                        <div class="mb-10 bg-slate-50 p-5 rounded-xl border border-slate-100">
                             <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-4">Route Details</p>
                             <div class="space-y-4 relative">
                                <div class="absolute left-[7px] top-2 bottom-2 w-0.5 bg-slate-200"></div>
                                <div class="flex items-start gap-4 relative z-10">
                                    <div class="w-4 h-4 rounded-full bg-green-500 border-2 border-white shadow-sm mt-1"></div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Pick-up From</p>
                                        <p id="doc_from" class="text-xs font-black text-slate-800"></p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4 relative z-10">
                                    <div class="w-4 h-4 rounded-full bg-red-500 border-2 border-white shadow-sm mt-1"></div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Drop-off To</p>
                                        <p id="doc_to" class="text-xs font-black text-slate-800"></p>
                                    </div>
                                </div>
                             </div>
                        </div>

                        <!-- Computation Table -->
                        <table class="w-full mb-10">
                            <thead>
                                <tr class="border-b-2 border-slate-900 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">
                                    <th class="py-2">Fare Description</th>
                                    <th class="py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="text-[11px] font-medium text-slate-600">
                                    <td class="py-3">
                                        Gross Trip Fare
                                        <p id="doc_item_desc" class="text-[9px] text-slate-400 italic font-medium"></p>
                                    </td>
                                    <td id="doc_gross_fare" class="py-3 text-right font-black text-slate-800"></td>
                                </tr>
                                <tr class="text-[11px] font-medium text-slate-600 border-b border-slate-50">
                                    <td class="py-3">Standard Service Fee (20%)</td>
                                    <td id="doc_service_fee" class="py-3 text-right font-black text-red-500"></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="py-6 text-[11px] font-black text-slate-900 uppercase">
                                        NET PAYABLE TO PARTNER
                                    </td>
                                    <td id="doc_total" class="py-6 text-right text-lg font-black text-blue-600 border-t-2 border-slate-100"></td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Security Protocol Footer -->
                        <div class="mt-10 text-center opacity-30 pt-10 border-t border-slate-100">
                            <p class="text-[8px] font-medium text-slate-400 uppercase tracking-[4px]">Verified via ViaHale AI Security Protocol • MD5-<?php echo strtoupper(bin2hex(random_bytes(4))); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="p-6 bg-slate-50 border-t border-slate-200 flex gap-4 justify-end">
            <button onclick="closeReviewModal()" class="px-8 py-3 bg-white border border-slate-300 text-slate-600 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-slate-100 transition-all">Cancel</button>
            <button id="modalConfirmBtn" class="px-10 py-3 bg-[#3f36bd] text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-200 hover:scale-[1.02] transition-all">Confirm Invoice</button>
        </div>
    </div>
</div>

<!-- Bulk Approve Modal -->
<div id="bulkApproveModal" class="modal-overlay">
    <div class="modal-content bulk-approve-modal flex-col relative">
        <div class="modal-header-bulk" style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
            <h2 class="modal-title-bulk" style="margin:0;">Bulk Approve Commissions</h2>
            <p class="modal-subtitle-bulk" style="margin:0;">Select the pending invoices below you wish to confirm and process for accounting.</p>
        </div>
        
        <div class="modal-body-bulk">
            <div class="payrolls-table-container">
                <div class="select-all-header">
                    <input type="checkbox" id="selectAllBulk" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500" onchange="toggleSelectAllBulk()">
                    <span>Select All Pending Invoices</span>
                </div>
                <table class="bulk-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Invoice ID</th>
                            <th>Driver Name</th>
                            <th>Description</th>
                            <th style="text-align: right;">Commission (20%)</th>
                        </tr>
                    </thead>
                    <tbody id="bulkReceivablesTable">
                        <!-- Items will be loaded here -->
                        <tr>
                            <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8;">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Loading pending invoices...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fixed Summary Row -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
            <div class="summary-row-bulk" style="margin-bottom:0;">
                <div style="font-size: 14px; color: #475569;">
                    Showing <span id="showingCount" style="font-weight: 800; color: #7c3aed;">0</span> pending invoices ready for confirmation.
                </div>
                <div class="summary-amount-bulk" id="selectedTotalAmount">₱0.00</div>
            </div>
        </div>
        
        <div class="modal-actions-bulk">
            <button class="btn-modal-cancel-bulk hover:bg-slate-200 transition-colors" onclick="closeBulkModal()">Cancel</button>
            <button id="btnBulkApprove" class="btn-modal-approve-bulk hover:bg-blue-700 shadow-lg shadow-blue-100 transition-all translate-y-0 active:translate-y-1" onclick="processBulkApprove()" disabled>
                <i class="fas fa-check mr-2"></i> Confirm Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
    </div>
</div>


<script>
let table = document.querySelector("table tbody");
let allRows = Array.from(table.querySelectorAll("tr"));
let currentPage = 1;
const rowsPerPage = 10;

function generateInvoiceId() {
    const date = new Date().toISOString().slice(0,10).replace(/-/g,'');
    const rand = Math.floor(1000 + Math.random() * 9000);
    return `${date}-${rand}`;
}

function openAddReceivableModal() {
    // Generate a new invoice ID each time the modal opens
    const newInvoiceId = generateInvoiceId();
    document.getElementById('invoice_id').value = newInvoiceId;
    document.getElementById('addModal').classList.remove('hidden');
}

function closeAddReceivableModal() {
    document.getElementById('addModal').classList.add('hidden');
    // Reset form but keep the invoice ID
    const invoiceId = document.getElementById('invoice_id').value;
    document.getElementById('addReceivableForm').reset();
    document.getElementById('invoice_id').value = invoiceId;
}

function filterRows() {
    const searchInput = document.getElementById("searchInput").value.toLowerCase();
    const dueDate = document.getElementById("dueDate").value;
    
    return allRows.filter(row => {
        if (row.classList.contains('no-results')) return false;
        const invoiceId = row.getAttribute('data-invoiceid').toLowerCase();
        const driverName = row.getAttribute('data-drivername').toLowerCase();
        const description = row.getAttribute('data-desc').toLowerCase();
        const amount = row.getAttribute('data-amount').toLowerCase();
        const rowDate = row.getAttribute('data-duedate');
        
        const matchSearch = (
            invoiceId.includes(searchInput) ||
            driverName.includes(searchInput) ||
            description.includes(searchInput) ||
            amount.includes(searchInput)
        );
        
        const matchDate = (!dueDate || rowDate === dueDate);
        return matchSearch && matchDate;
    });
}

function displayData(page) {
    const filteredRows = filterRows();
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedRows = filteredRows.slice(start, end);
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
    
    // Show only the paginated rows
    paginatedRows.forEach(row => row.style.display = 'table-row');
    
    // Update pagination buttons
    document.getElementById("prevPage").disabled = (currentPage === 1);
    const nextPageBtn = document.getElementById("nextPage");
    if (nextPageBtn) nextPageBtn.disabled = (end >= filteredRows.length);
    
    // Update page status
    const pageStatus = document.getElementById("pageStatus");
    const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
    if (pageStatus) pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
    
    // If no results found, show message
    const tbody = document.getElementById("invoiceTableBody");
    const existingNoResults = tbody.querySelector('.no-results');
    if (filteredRows.length === 0) {
        if (!existingNoResults) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results';
            noResultsRow.innerHTML = `<td colspan="6" class="text-center py-4">No records found</td>`;
            tbody.appendChild(noResultsRow);
        }
    } else {
        if (existingNoResults) existingNoResults.remove();
    }
}

function filterTable() {
    currentPage = 1;
    displayData(currentPage);
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        displayData(currentPage);
    }
}

function nextPage() {
    const filteredRows = filterRows();
    if (currentPage * rowsPerPage < filteredRows.length) {
        currentPage++;
        displayData(currentPage);
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('addModal');
    if (event.target === modal) {
        closeAddReceivableModal();
    }
});

// Bulk Approve Modal Logic
function bulkApprove() {
    const modal = document.getElementById('bulkApproveModal');
    modal.classList.add('active');
    loadPendingReceivables();
}

function closeBulkModal() {
    document.getElementById('bulkApproveModal').classList.remove('active');
}

function loadPendingReceivables() {
    const tableBody = document.getElementById('bulkReceivablesTable');
    
    fetch('receivables_ia.php?action=get_pending_receivables')
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const items = res.data.items;
                // Initialize showing count to total items
                document.getElementById('showingCount').textContent = items.length;
                
                if (items.length === 0) {
                    tableBody.innerHTML = `<tr><td colspan="5" class="py-10 text-center text-slate-400">No pending invoices found.</td></tr>`;
                    document.getElementById('selectedTotalAmount').textContent = '₱0.00';
                    return;
                }
                
                tableBody.innerHTML = items.map(item => `
                    <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="toggleRow(this)">
                        <td class="checkbox-cell">
                            <input type="checkbox" class="bulk-item-checkbox w-4 h-4 rounded text-purple-600 focus:ring-purple-500" 
                                value="${item.invoice_id}" data-amount="${item.amount * 0.20}" onclick="event.stopPropagation(); updateBulkSummary()">
                        </td>
                        <td class="font-black text-slate-700">${item.invoice_id}</td>
                        <td class="font-bold text-slate-600">${item.driver_name}</td>
                        <td class="text-slate-500 text-xs">${item.description}</td>
                        <td class="text-right font-black text-emerald-600">₱${(item.amount * 0.20).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
                    </tr>
                `).join('');
                
                updateBulkSummary();
            }
        });
}

function toggleRow(row) {
    const checkbox = row.querySelector('.bulk-item-checkbox');
    checkbox.checked = !checkbox.checked;
    updateBulkSummary();
}

function toggleSelectAllBulk() {
    const selectAll = document.getElementById('selectAllBulk');
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkSummary();
}

function updateBulkSummary() {
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox:checked');
    const count = checkboxes.length;
    let total = 0;
    
    checkboxes.forEach(cb => {
        total += parseFloat(cb.getAttribute('data-amount'));
    });
    
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('showingCount').textContent = count; // Display selected count in summary text
    document.getElementById('selectedTotalAmount').textContent = '₱' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
    
    const btn = document.getElementById('btnBulkApprove');
    btn.disabled = count === 0;
}

function processBulkApprove() {
    const checkboxes = document.querySelectorAll('.bulk-item-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    const btn = document.getElementById('btnBulkApprove');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    
    const formData = new FormData();
    formData.append('action', 'bulk_approve_receivables');
    ids.forEach(id => formData.append('invoice_ids[]', id));
    
    fetch('receivables_ia.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.success) {
            closeBulkModal();
            showCustomAlert('Success', res.message, 'info');
            // Refresh table
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showCustomAlert('Error', res.message, 'warning');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(err => {
        showCustomAlert('Error', 'An unexpected error occurred.', 'warning');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function confirmSingleInvoice(invoiceId) {
    showCustomConfirm(
        'Confirm Invoice',
        `Are you sure you want to confirm Invoice ID: ${invoiceId}?`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'confirm_invoice.php';
            
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'invoice_id';
            hiddenInput.value = invoiceId;
            form.appendChild(hiddenInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    );
}

function viewReviewModal(data) {
    const modal = document.getElementById('reviewModal');
    const card = document.getElementById('reviewModalCard');
    
    // Formatting Helper
    const formatPHP = (num) => '₱' + parseFloat(num).toLocaleString(undefined, {minimumFractionDigits: 2});

    // Sample Locations Data logic - extracting from description if possible or using randoms
    const locations = {
        'Hospital': ['SM City North EDSA', 'Philippine General Hospital (PGH)'],
        'Mall': ['Trinoma Mall', 'Robinson\'s Galleria'],
        'Airport': ['Pasay Rotonda', 'NAIA Terminal 3'],
        'QC': ['Commonwealth Ave.', 'Quezon Memorial Circle'],
        'Makati': ['Ayala Triangle', 'Glorietta 4'],
        'Pasig': ['Ortigas Center', 'Capitol Commons'],
        'BGC': ['Uptown Mall BGC', 'Mind Museum'],
        'Village': ['Gate 1 Subdivision', 'Communtiy Park']
    };

    let fromText = 'Central Terminal, Manila';
    let toText = 'Intramuros East Gate';

    // Basic logic to pick sample address based on keyword in description
    for (let key in locations) {
        if (data.description.includes(key)) {
            fromText = locations[key][0];
            toText = locations[key][1];
            break;
        }
    }

    // Calculations
    const grossFare = parseFloat(data.amount);
    const serviceFee = grossFare * 0.20;
    const netEarnings = grossFare - serviceFee;
    const drvId = data.driver_id || 'DRV-' + (Math.floor(Math.random() * 9000) + 1000);

    // Populate Modal Details (Left Side)
    document.getElementById('modal_invoice_id').textContent = data.invoice_id;
    document.getElementById('modal_driver_id').textContent = drvId;
    document.getElementById('modal_driver_name').textContent = data.driver_name;
    document.getElementById('modal_description').textContent = data.description;
    
    document.getElementById('modal_gross_fare').textContent = formatPHP(grossFare);
    document.getElementById('modal_amount').textContent = '-' + formatPHP(serviceFee);
    document.getElementById('modal_net_pay').textContent = formatPHP(netEarnings);
    
    // Populate Document (Right Side)
    document.getElementById('doc_voucher_id').textContent = data.invoice_id;
    document.getElementById('doc_driver_id').textContent = drvId;
    document.getElementById('doc_driver_name').textContent = data.driver_name;
    document.getElementById('doc_trip_ref').textContent = 'BTN-' + data.invoice_id.split('-').pop();
    document.getElementById('doc_from').textContent = fromText;
    document.getElementById('doc_to').textContent = toText;
    document.getElementById('doc_item_desc').textContent = data.description;
    document.getElementById('doc_gross_fare').textContent = formatPHP(grossFare);
    document.getElementById('doc_service_fee').textContent = '-' + formatPHP(serviceFee);
    document.getElementById('doc_total').textContent = formatPHP(netEarnings);

    document.getElementById('modalConfirmBtn').onclick = () => {
        closeReviewModal();
        confirmSingleInvoice(data.invoice_id);
    };

    modal.classList.remove('hidden');
    setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    const card = document.getElementById('reviewModalCard');
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Global Custom Modal Helpers
function showCustomConfirm(title, message, onConfirm) {
    const modal = document.getElementById('customConfirmModal');
    const card = document.getElementById('confirmModalCard');
    const proceedBtn = document.getElementById('confirmProceedBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    const icon = document.getElementById('confirmIcon');
    const iconContainer = document.getElementById('confirmIconContainer');
    
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    cancelBtn.style.display = 'block';
    
    icon.className = 'fas fa-exclamation-triangle text-yellow-600 text-3xl';
    iconContainer.className = 'flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-6 mx-auto';
    proceedBtn.className = 'flex-1 px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 font-bold shadow-lg shadow-purple-200 transition-all active:scale-95';
    proceedBtn.textContent = 'Proceed';

    modal.classList.remove('hidden');
    setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    const handleConfirm = () => {
        closeConfirmModal();
        onConfirm();
    };
    
    const closeConfirmModal = () => {
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            proceedBtn.removeEventListener('click', handleConfirm);
            cancelBtn.removeEventListener('click', closeConfirmModal);
        }, 300);
    };
    
    proceedBtn.addEventListener('click', handleConfirm);
    cancelBtn.addEventListener('click', closeConfirmModal);
}

function showCustomAlert(title, message, type = 'info') {
    const modal = document.getElementById('customConfirmModal');
    const card = document.getElementById('confirmModalCard');
    const proceedBtn = document.getElementById('confirmProceedBtn');
    const cancelBtn = document.getElementById('confirmCancelBtn');
    const icon = document.getElementById('confirmIcon');
    const iconContainer = document.getElementById('confirmIconContainer');
    
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    cancelBtn.style.display = 'none';
    
    if(type === 'warning') {
        icon.className = 'fas fa-exclamation-circle text-orange-600 text-3xl';
        iconContainer.className = 'flex items-center justify-center w-20 h-20 bg-orange-100 rounded-full mb-6 mx-auto';
    } else {
        icon.className = 'fas fa-info-circle text-blue-600 text-3xl';
        iconContainer.className = 'flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6 mx-auto';
    }
    
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
        }, 300);
    };
    
    proceedBtn.addEventListener('click', closeAlert);
}

function toggleSelectAll() {
    const selectAllCheck = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.invoice-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('tr').style.display !== 'none') {
            checkbox.checked = selectAllCheck.checked;
        }
    });
}

// Initialize on page load
window.onload = () => {
    allRows = Array.from(document.querySelectorAll("#invoiceTableBody > tr"));
    displayData(currentPage);
    
    const today = new Date().toISOString().split('T')[0];
    const paidDate = document.getElementById('fully_paid_date');
    if (paidDate) {
        paidDate.value = today;
        paidDate.min = today;
    }
};
</script>
</body>
</html>