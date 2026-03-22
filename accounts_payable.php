<?php
ob_start();
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Set flag to indicate we're in unified dashboard mode
// Included files can check this to skip their header/sidebar/cards
define('UNIFIED_DASHBOARD_MODE', true);

// Database connection
include('connection.php');

// Handle module tab switching
if (!isset($_SESSION['ap_module'])) {
    $_SESSION['ap_module'] = 'vendor'; // Default to vendor
}

if (isset($_GET['module'])) {
    $_SESSION['ap_module'] = $_GET['module'];
}

$currentModule = $_SESSION['ap_module'];

// Handle AJAX/POST requests from modules before any output
// This prevents dashboard HTML from leaking into JSON responses
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['ajax']) && $_GET['ajax'] == '1') || (isset($_GET['action']))) {
    $moduleFile = $currentModule . '.php';
    if ($currentModule === 'driver') $moduleFile = 'driver_payable.php';
    if (file_exists($moduleFile)) {
        include($moduleFile);
        exit(); // Stop execution here for AJAX/POST
    }
}

// Query metrics for all 4 modules
// VENDOR METRICS
$vendorOverdueRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status IN ('pending', 'approved') AND payment_due < CURRENT_DATE()");
$vendorOverdueData = $vendorOverdueRes ? $vendorOverdueRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$vendorOverdueCount = $vendorOverdueData['total'] ?? 0;
$vendorOverdueAmount = $vendorOverdueData['total_amt'] ?? 0;

$vendorPendingRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status = 'pending'");
$vendorPendingData = $vendorPendingRes ? $vendorPendingRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$vendorPendingCount = $vendorPendingData['total'] ?? 0;
$vendorPendingAmount = $vendorPendingData['total_amt'] ?? 0;

$vendorForPaymentRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status = 'approved'");
$vendorForPaymentData = $vendorForPaymentRes ? $vendorForPaymentRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$vendorForPaymentCount = $vendorForPaymentData['total'] ?? 0;
$vendorForPaymentAmount = $vendorForPaymentData['total_amt'] ?? 0;

$vendorProcessedRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM accounts_payable WHERE status = 'paid' OR status = 'Paid'");
$vendorProcessedData = $vendorProcessedRes ? $vendorProcessedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$vendorProcessedCount = $vendorProcessedData['total'] ?? 0;
$vendorProcessedAmount = $vendorProcessedData['total_amt'] ?? 0;

// REIMBURSEMENT METRICS
$reimbPendingRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM reimbursements WHERE status = 'pending' OR status = 'Pending'");
$reimbPendingData = $reimbPendingRes ? $reimbPendingRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$reimbPendingCount = $reimbPendingData['total'] ?? 0;
$reimbPendingAmount = $reimbPendingData['total_amt'] ?? 0;

$reimbApprovedRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM reimbursements WHERE status = 'approved' OR status = 'Approved'");
$reimbApprovedData = $reimbApprovedRes ? $reimbApprovedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$reimbApprovedCount = $reimbApprovedData['total'] ?? 0;
$reimbApprovedAmount = $reimbApprovedData['total_amt'] ?? 0;

$reimbPaidRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM reimbursements WHERE status = 'paid' OR status = 'Paid'");
$reimbPaidData = $reimbPaidRes ? $reimbPaidRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$reimbPaidCount = $reimbPaidData['total'] ?? 0;
$reimbPaidAmount = $reimbPaidData['total_amt'] ?? 0;

$reimbProcessedRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM reimbursements WHERE status = 'paid' OR status = 'Paid'");
$reimbProcessedData = $reimbProcessedRes ? $reimbProcessedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$reimbProcessedCount = $reimbProcessedData['total'] ?? 0;
$reimbProcessedAmount = $reimbProcessedData['total_amt'] ?? 0;

// PAYROLL METRICS
$payrollPendingRes = $conn->query("SELECT COUNT(*) as total, SUM(net_salary) as total_amt FROM payroll_records WHERE status = 'pending'");
$payrollPendingData = $payrollPendingRes ? $payrollPendingRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$payrollPendingCount = $payrollPendingData['total'] ?? 0;
$payrollPendingAmount = $payrollPendingData['total_amt'] ?? 0;

$payrollApprovedRes = $conn->query("SELECT COUNT(*) as total, SUM(net_salary) as total_amt FROM payroll_records WHERE status = 'approved'");
$payrollApprovedData = $payrollApprovedRes ? $payrollApprovedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$payrollApprovedCount = $payrollApprovedData['total'] ?? 0;
$payrollApprovedAmount = $payrollApprovedData['total_amt'] ?? 0;

$payrollPaidRes = $conn->query("SELECT COUNT(*) as total, SUM(net_salary) as total_amt FROM payroll_records WHERE status = 'paid'");
$payrollPaidData = $payrollPaidRes ? $payrollPaidRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$payrollPaidCount = $payrollPaidData['total'] ?? 0;
$payrollPaidAmount = $payrollPaidData['total_amt'] ?? 0;

$payrollProcessedRes = $conn->query("SELECT COUNT(*) as total, SUM(net_salary) as total_amt FROM payroll_records WHERE status = 'paid' OR status = 'Paid'");
$payrollProcessedData = $payrollProcessedRes ? $payrollProcessedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$payrollProcessedCount = $payrollProcessedData['total'] ?? 0;
$payrollProcessedAmount = $payrollProcessedData['total_amt'] ?? 0;

// DRIVER PAYABLE METRICS
$driverPendingRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM driver_payouts WHERE status = 'Pending'");
$driverPendingData = $driverPendingRes ? $driverPendingRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$driverPendingCount = $driverPendingData['total'] ?? 0;
$driverPendingAmount = $driverPendingData['total_amt'] ?? 0;

$driverApprovedRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM driver_payouts WHERE status = 'Approved'");
$driverApprovedData = $driverApprovedRes ? $driverApprovedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$driverApprovedCount = $driverApprovedData['total'] ?? 0;
$driverApprovedAmount = $driverApprovedData['total_amt'] ?? 0;

$driverPaidRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM driver_payouts WHERE status = 'Paid'");
$driverPaidData = $driverPaidRes ? $driverPaidRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$driverPaidCount = $driverPaidData['total'] ?? 0;
$driverPaidAmount = $driverPaidData['total_amt'] ?? 0;

$driverProcessedRes = $conn->query("SELECT COUNT(*) as total, SUM(amount) as total_amt FROM driver_payouts WHERE status = 'Paid' OR status = 'paid'");
$driverProcessedData = $driverProcessedRes ? $driverProcessedRes->fetch_assoc() : ['total' => 0, 'total_amt' => 0];
$driverProcessedCount = $driverProcessedData['total'] ?? 0;
$driverProcessedAmount = $driverProcessedData['total_amt'] ?? 0;

// Set current module's metrics
$card1Count = 0;
$card1Amount = 0;
$card1Label = '';
$card1Color = 'red';

$card2Count = 0;
$card2Amount = 0;
$card2Label = '';
$card2Color = 'amber';

$card3Count = 0;
$card3Amount = 0;
$card3Label = '';
$card3Color = 'blue';

$card4Count = 0;
$card4Amount = 0;
$card4Label = '';
$card4Color = 'green';

switch ($currentModule) {
    case 'vendor':
        $card1Count = $vendorOverdueCount;
        $card1Amount = $vendorOverdueAmount;
        $card1Label = 'Overdue Invoices';
        $card1Color = 'red';
        
        $card2Count = $vendorPendingCount;
        $card2Amount = $vendorPendingAmount;
        $card2Label = 'Pending Approval';
        $card2Color = 'amber';
        
        $card3Count = $vendorForPaymentCount;
        $card3Amount = $vendorForPaymentAmount;
        $card3Label = 'For Payment';
        $card3Color = 'blue';
        
        $card4Count = $vendorProcessedCount;
        $card4Amount = $vendorProcessedAmount;
        $card4Label = 'Processed This Month';
        $card4Color = 'green';
        break;
        
    case 'reimbursement':
        $card1Count = $reimbPendingCount;
        $card1Amount = $reimbPendingAmount;
        $card1Label = 'Pending Reports';
        $card1Color = 'amber';
        
        $card2Count = $reimbApprovedCount;
        $card2Amount = $reimbApprovedAmount;
        $card2Label = 'Approved';
        $card2Color = 'blue';
        
        $card3Count = $reimbPaidCount;
        $card3Amount = $reimbPaidAmount;
        $card3Label = 'Paid';
        $card3Color = 'green';
        
        $card4Count = $reimbProcessedCount;
        $card4Amount = $reimbProcessedAmount;
        $card4Label = 'Processed This Month';
        $card4Color = 'purple';
        break;
        
    case 'payroll':
        $card1Count = $payrollPendingCount;
        $card1Amount = $payrollPendingAmount;
        $card1Label = 'Pending Payroll';
        $card1Color = 'amber';
        
        $card2Count = $payrollApprovedCount;
        $card2Amount = $payrollApprovedAmount;
        $card2Label = 'Approved';
        $card2Color = 'blue';
        
        $card3Count = $payrollPaidCount;
        $card3Amount = $payrollPaidAmount;
        $card3Label = 'Paid';
        $card3Color = 'green';
        
        $card4Count = $payrollProcessedCount;
        $card4Amount = $payrollProcessedAmount;
        $card4Label = 'Processed This Month';
        $card4Color = 'purple';
        break;
        
    case 'driver':
        $card1Count = $driverPendingCount;
        $card1Amount = $driverPendingAmount;
        $card1Label = 'Pending Payouts';
        $card1Color = 'amber';
        
        $card2Count = $driverApprovedCount;
        $card2Amount = $driverApprovedAmount;
        $card2Label = 'Approved';
        $card2Color = 'blue';
        
        $card3Count = $driverPaidCount;
        $card3Amount = $driverPaidAmount;
        $card3Label = 'Paid';
        $card3Color = 'green';
        
        $card4Count = $driverProcessedCount;
        $card4Amount = $driverProcessedAmount;
        $card4Label = 'Processed This Month';
        $card4Color = 'purple';
        break;
}

// Module titles
$moduleTitles = [
    'vendor' => 'Vendor Invoices',
    'reimbursement' => 'Reimbursements',
    'payroll' => 'Payroll',
    'driver' => 'Driver Payable'
];

$currentModuleTitle = $moduleTitles[$currentModule] ?? 'Accounts Payable';

// Map current module to sidebar page identifier for proper dropdown/active state
$sidebarPageMap = [
    'vendor' => 'vendor',
    'reimbursement' => 'reimbursement',
    'payroll' => 'payroll',
    'driver' => 'driverpayable'
];

// Set the page parameter so sidebar knows which dropdown to open and which item to highlight
$_GET['page'] = $sidebarPageMap[$currentModule] ?? 'vendor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Accounts Payable Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="logo.png" type="img">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .tabs-container { 
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px;
            background-color: #f3f4f6;
            border-radius: 9999px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: visible;
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
            pointer-events: none;
        }

        .module-tab {
            padding: 10px 24px;
            border-radius: 9999px;
            font-size: 15px;
            font-weight: 600;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .tab-badge {
            background: #374151;
            color: white;
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 9999px;
            min-width: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .module-tab.active .tab-badge {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .module-content {
            display: none;
        }
        
        .module-content.active {
            display: block;
        }
        
        .card-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .card-container {
                grid-template-columns: 1fr;
            }
        }
        
        /* Global Modal Fix to ensure it covers the sidebar */
        .modal {
            z-index: 10050 !important; /* HIgher than sidebar's 10000 */
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
        
        /* Hide scrollbar for main content area only */
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        .hide-scrollbar {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include('sidebar.php'); ?>

<div class="overflow-y-auto h-full px-6 hide-scrollbar">
    <!-- Header -->
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl font-semibold">Accounts Payable Dashboard</h1>
        <div class="text-sm">
            <a href="dashboard.php" class="text-black hover:text-blue-600">Home</a>
            /
            <a class="text-blue-600">Accounts Payable</a>
            /
            <a class="text-blue-600"><?= $currentModuleTitle ?></a>
        </div>
    </div>
    
    <!-- Dynamic Summary Cards -->
    <div class="card-container px-4">
        <!-- Card 1 -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($card1Count) ?></div>
                <div class="text-base text-gray-500 font-medium"><?= $card1Label ?></div>
                <div class="text-base font-bold text-<?= $card1Color ?>-500">₱<?= number_format($card1Amount, 2) ?></div>
            </div>
        </div>
        
        <!-- Card 2 -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($card2Count) ?></div>
                <div class="text-base text-gray-500 font-medium"><?= $card2Label ?></div>
                <div class="text-base font-bold text-<?= $card2Color ?>-500">₱<?= number_format($card2Amount, 2) ?></div>
            </div>
        </div>
        
        <!-- Card 3 -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($card3Count) ?></div>
                <div class="text-base text-gray-500 font-medium"><?= $card3Label ?></div>
                <div class="text-base font-bold text-<?= $card3Color ?>-500">₱<?= number_format($card3Amount, 2) ?></div>
            </div>
        </div>
        
        <!-- Card 4 -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 hover:shadow-lg transition-all">
            <div class="space-y-1">
                <div class="text-4xl font-bold text-[#001f3f]"><?= number_format($card4Count) ?></div>
                <div class="text-base text-gray-500 font-medium"><?= $card4Label ?></div>
                <div class="text-base font-bold text-<?= $card4Color ?>-500">₱<?= number_format($card4Amount, 2) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="flex-1 bg-white p-6 h-full w-full">
<div class="w-full">
    <!-- Module Tabs (Below Cards) -->
    <div class="flex justify-start mb-6 px-4">
        <div class="tabs-container no-scrollbar">
            <div id="tab-indicator" class="tab-indicator"></div>
            <button class="module-tab <?= $currentModule === 'vendor' ? 'active' : '' ?>" onclick="switchModule('vendor')">
                <i class="fas fa-file-invoice"></i>
                <span>Vendor Invoices</span>
                <?php if ($vendorPendingCount > 0): ?>
                    <span class="tab-badge"><?= $vendorPendingCount ?></span>
                <?php endif; ?>
            </button>
            <button class="module-tab <?= $currentModule === 'reimbursement' ? 'active' : '' ?>" onclick="switchModule('reimbursement')">
                <i class="fas fa-receipt"></i>
                <span>Reimbursements</span>
                <?php if ($reimbPendingCount > 0): ?>
                    <span class="tab-badge"><?= $reimbPendingCount ?></span>
                <?php endif; ?>
            </button>
            <button class="module-tab <?= $currentModule === 'payroll' ? 'active' : '' ?>" onclick="switchModule('payroll')">
                <i class="fas fa-money-check-alt"></i>
                <span>Payroll</span>
                <?php if ($payrollPendingCount > 0): ?>
                    <span class="tab-badge"><?= $payrollPendingCount ?></span>
                <?php endif; ?>
            </button>
            <button class="module-tab <?= $currentModule === 'driver' ? 'active' : '' ?>" onclick="switchModule('driver')">
                <i class="fas fa-wallet"></i>
                <span>Driver Payable</span>
                <?php if ($driverPendingCount > 0): ?>
                    <span class="tab-badge"><?= $driverPendingCount ?></span>
                <?php endif; ?>
            </button>
        </div>
    </div>
    
    <!-- Module Content -->
    <div class="bg-white rounded-b-xl" style="min-height: 600px;">
        <!-- Vendor Module -->
        <div id="vendor-content" class="module-content <?= $currentModule === 'vendor' ? 'active' : '' ?>">
            <?php if ($currentModule === 'vendor') {
                // Capture and include vendor.php content
                ob_start();
                include('vendor.php');
                $vendorContent = ob_get_clean();
                
                // Extract only the main content (skip header, sidebar, cards)
                // For now, include everything and let vendor.php handle it
                echo $vendorContent;
            } ?>
        </div>
        
        <!-- Reimbursement Module -->
        <div id="reimbursement-content" class="module-content <?= $currentModule === 'reimbursement' ? 'active' : '' ?>">
            <?php if ($currentModule === 'reimbursement') {
                ob_start();
                include('reimbursement.php');
                $reimbContent = ob_get_clean();
                echo $reimbContent;
            } ?>
        </div>
        
        <!-- Payroll Module -->
        <div id="payroll-content" class="module-content <?= $currentModule === 'payroll' ? 'active' : '' ?>">
            <?php if ($currentModule === 'payroll') {
                ob_start();
                include('payroll.php');
                $reimbContent = ob_get_clean();
                echo $reimbContent;
            } ?>
        </div>
        
        <!-- Driver Payable Module -->
        <div id="driver-content" class="module-content <?= $currentModule === 'driver' ? 'active' : '' ?>">
            <?php if ($currentModule === 'driver') {
                ob_start();
                include('driver_payable.php');
                $driverContent = ob_get_clean();
                echo $driverContent;
            } ?>
        </div>
    </div>
</div>

<script>
let currentModule = '<?= $currentModule ?>';

function switchModule(module) {
    if (module === currentModule) return;
    
    const targetTab = document.querySelector(`.module-tab[onclick*="'${module}'"]`);
    const indicator = document.getElementById('tab-indicator');
    
    if (targetTab && indicator) {
        indicator.style.width = `${targetTab.offsetWidth}px`;
        indicator.style.left = `${targetTab.offsetLeft}px`;
        
        // Short delay for animation before redirect
        setTimeout(() => {
            window.location.href = 'accounts_payable.php?module=' + module;
        }, 300);
    } else {
        window.location.href = 'accounts_payable.php?module=' + module;
    }
}

// Initialize indicator on load
document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.querySelector('.module-tab.active');
    const indicator = document.getElementById('tab-indicator');
    if (activeTab && indicator) {
        // Position immediately without animation
        indicator.style.transition = 'none';
        indicator.style.width = `${activeTab.offsetWidth}px`;
        indicator.style.left = `${activeTab.offsetLeft}px`;
        // Restore transition after first frame
        setTimeout(() => indicator.style.transition = '', 50);
    }
});

// Update on resize
window.addEventListener('resize', () => {
    const activeTab = document.querySelector('.module-tab.active');
    const indicator = document.getElementById('tab-indicator');
    if (activeTab && indicator) {
        indicator.style.transition = 'none';
        indicator.style.width = `${activeTab.offsetWidth}px`;
        indicator.style.left = `${activeTab.offsetLeft}px`;
        setTimeout(() => indicator.style.transition = '', 10);
    }
});
</script>

</main>
</body>
</html>
