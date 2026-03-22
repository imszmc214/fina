<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? $pageTitle : 'ViaHale Dashboard'; ?></title>
  <link rel="icon" href="<?php echo isset($pageIcon) ? $pageIcon : 'logo2.png'; ?>" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&family=Quicksand:wght@300;400;600&display=swap" rel="stylesheet">
  <style>
    .font-poppins {
      font-family: 'Poppins', sans-serif;
    }
    .font-quicksand {
      font-family: 'Quicksand', sans-serif;
    }
    .font-bricolage {
      font-family: 'Bricolage Grotesque', sans-serif;
    }
  </style>
<?php endif; ?>

<?php
// Set the current page from the URL parameter
$page = $_GET['page'] ?? 'sidebar';

// Auto-detect page based on filename if 'page' parameter is missing or default
$currentPageFile = basename($_SERVER['PHP_SELF']);
$fileToPageMap = [
    'payout.php' => 'payout',
    'disbursedrecords.php' => 'disbursedrecords',
    'budget_planning.php' => 'budgetplanning',
    'budget_allocation.php' => 'budgetallocation',
    'budget_estimation.php' => 'budgetestimation',
    'pettycash.php' => 'pettycash',
    'archive.php' => 'archive',
    'paymentrecords.php' => 'paymentrecords',
    'receivables_receipts.php' => 'arreceipts',
    'reimbursement.php' => 'reimbursement',
    'vendor.php' => 'vendor',
    'payroll.php' => 'payroll',
    'driver_payable.php' => 'driverpayable',
    'accounts_payable.php' => 'accountspayable',
    'payables_records.php' => 'payablesrecords',
    'receivables_ia.php' => 'iareceivables',
    'receivables.php' => 'receivables',
    'receivables_records.php' => 'receivablesrecords',
    'charts_of_accounts.php' => 'chartsofaccounts',
    'journal_entry.php' => 'journalentry',
    'ledger.php' => 'ledger',
    'financial_statement.php' => 'financialstatement',
    'audit_reports.php' => 'auditreports',
    'user_management.php' => 'usermanagement',
    'legal_management.php' => 'legalmanagement'
];

if (($page == 'sidebar' || $page == '') && isset($fileToPageMap[$currentPageFile])) {
    $page = $fileToPageMap[$currentPageFile];
}

$dropdowns = [
    'budget' => ['budgetplanning', 'budgetallocation', 'budgetestimation', 'archive', 'pettycash'],
    'disburse' => ['payout', 'disbursedrecords'],
    'collect' => ['paymentrecords', 'arreceipts', 'collected'],
    'ap' => ['reimbursement', 'vendor', 'payables', 'payablesrecords', 'payroll', 'driverpayable', 'accountspayable'],
    'ar' => ['iareceivables', 'receivables', 'receivablesrecords'],
    'gl' => ['chartsofaccounts', 'journalentry', 'ledger', 'financialstatement', 'auditreports'],
    'legal' => ['legalmanagement'],
];

$activeDropdown = null;

// Check kung aling dropdown ang dapat bukas
foreach ($dropdowns as $dropdown => $pages) {
    if (in_array($page, $pages)) {
        $activeDropdown = $dropdown;
        break;
    }
}

include 'connection.php';

// Define user roles
$userRole = $_SESSION['user_role'] ?? '';

// I-add ito sa simula ng sidebar.php pagkatapos ng database connection
$user_id = $_SESSION["user_id"] ?? 0;

// Kunin ang profile picture mula sa database
$profilePictureQuery = "SELECT profile_picture FROM userss WHERE id = ?";
$stmt = $conn->prepare($profilePictureQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profileResult = $stmt->get_result();
$profileData = $profileResult->fetch_assoc();

// Set default kung walang profile picture
$userProfilePicture = !empty($profileData['profile_picture']) && file_exists($profileData['profile_picture']) 
    ? $profileData['profile_picture'] 
    : 'default_profile.png';

$stmt->close();
?>

<?php
// Modules data
$modules = [
    'addap' => 'Add Budget Request',
    'sidebar' => 'Dashboard',
    'budgetplanning' => 'Budget Planning',
    'budgetallocation' => 'Budget Allocation',
    'budgetestimation' => 'Budget Estimation',
    'pettycash' => 'Petty Cash Allowance',
    'payout' => 'Payout',
    'disbursedrecords' => 'Disbursed Records',
    'paymentrecords' => 'Payment Records',
    'collected' => 'Collected Receipts',
    'arreceipts' => 'Receivables Receipts',
    'reimbursement' => 'Reimbursement',
    'vendor' => 'Vendor Management',
    'payables' => 'Payables',
    'payablesrecords' => 'Payables Records',
    'iareceivables' => 'Account Receivables',
    'receivables' => 'Receivables',
    'receivablesrecords' => 'Receivables Records',
    'chartsofaccounts' => 'Charts of Accounts',
    'journalentry' => 'Journal Entry',
    'ledger' => 'Ledger',
    'financialstatement' => 'Financial Statement',
    'auditreports' => 'Audit Reports',
    'archive' => 'Archive',
    'requestportal' => 'Request Portal',
    'usermanagement' => 'User Management',
    'compliancecontrol' => 'Compliance Control',
    'payroll' => 'Payroll Management',
    'driverpayable' => 'Driver Payable',
    'legalmanagement' => 'Legal Management'
];

// Handle search query
$searchQuery = $_GET['search'] ?? '';
$searchResults = [];

if (!empty($searchQuery)) {
    // Search through modules for matching terms
    foreach ($modules as $key => $value) {
        if (stripos($value, $searchQuery) !== false) {
            $searchResults[] = ['key' => $key, 'name' => $value];
        }
    }
}
?>

<!-- Display search results -->
<!-- <?php if (!empty($searchQuery)): ?>
    <div>
        <h3>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h3>
        <ul>
            <?php if (empty($searchResults)): ?>
                <li>No results found.</li>
            <?php else: ?>
                <?php foreach ($searchResults as $result): ?>
                    <li>
                        <a href="?page=<?php echo $result['key']; ?>">
                            <?php echo $result['name']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    
<?php endif; ?> -->
<?php if (!defined('UNIFIED_DASHBOARD_MODE')): ?>
                </head>
                <body>
<?php endif; ?>
<div class="flex min-h-screen h-full bg-white font-poppins">
    <!-- Sidebar -->
<aside id="sidebar" class="bg-[#181c2f] text-white w-64 space-y-6 transition-all duration-300 flex-shrink-0 min-h-screen overflow-y-auto flex flex-col">
    <!-- Scrollable Content Area -->
    <div class="flex-grow overflow-y-auto">
        <div class="flex items-center pr-4 pt-4">
            <img src="logo2.png" class="w-50 h-50 my-6" alt="logo">
        </div>
        <div>
            <a class="font-bold flex items-center -ml-8 space-x-1 p-2 pl-12 cursor-pointer text-gray-500">
                <span>MAIN MENU</span>
            </a>
        </div>
        <nav>
            <ul>
                <?php
                // Determine dashboard link based on user role
                $dashboard_link = '';
                switch($userRole) {
                    case 'financial admin':
                        $dashboard_link = 'dashboard_admin.php?page=sidebar';
                        break;
                    case 'auditor':
                        $dashboard_link = 'dashboard_auditor.php?page=sidebar';
                        break;
                    case 'budget manager':
                        $dashboard_link = 'dashboard_budget_manager.php?page=sidebar';
                        break;
                    case 'collector':
                        $dashboard_link = 'dashboard_collector.php?page=sidebar';
                        break;
                    case 'disburse officer':
                        $dashboard_link = 'dashboard_disburse_officer.php?page=sidebar';
                        break;
                    default:
                        $dashboard_link = 'dashboard_admin.php?page=sidebar'; // fallback to admin dashboard
                }
                ?>

                <li>
                    <div>
                        <a href="<?php echo $dashboard_link; ?>" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4 <?php echo ($page == 'sidebar' ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' : ''); ?>">
                            <i class="fas fa-th-large"></i>
                            <span>Dashboard</span>
                        </a>
                    </div>
                </li>
                
                <!-- User Management - Financial Admin Only -->
                <?php if ($userRole == 'financial admin'): ?>
                <li>
                    <div>
                        <a href="user_management.php?page=usermanagement" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4 <?php echo ($page == 'usermanagement' ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' : ''); ?>">
                            <i class="fas fa-address-card mr-1"></i>
                            <span>User Management</span>
                        </a>
                    </div>
                </li>
                <?php endif; ?>
                
                <div>
                    <a class="font-bold flex items-center mt-6 -ml-8 space-x-1 p-2 pl-12 cursor-pointer text-gray-500">
                        <span>OPERATIONS</span>
                    </a>
                </div>
            <!-- Budget Management - Financial Admin & Budget Manager -->
            <?php if ($userRole == 'financial admin' || $userRole == 'budget manager'): ?>
            <li>
                <div>
                    <a href="#" onclick="toggleDropdown('budgetDropdown', 'budget')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                        <i class="fas fa-chart-pie mr-1"></i>
                        <span>Budget Management</span>
                        <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'budget' ? 'rotate-90' : ''); ?>"></i>
                    </a>
                </div>
                <ul id="budgetDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'budget' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                        <li class="mb-2">
                            <a href="budget_planning.php?page=budgetplanning" class="flex items-center
                                <?php echo ($page == 'budgetplanning' 
                                ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-8 cursor-pointer">
                                <i class="fas fa-chart-line"></i>
                                <span>Budget Planning</span>
                            </a>
                        </li> 
            
                        <li class="mb-2">
                            <a href="budget_allocation.php?page=budgetallocation" class="flex items-center
                                <?php echo ($page == 'budgetallocation' 
                                ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-8 cursor-pointer">
                                <i class="fas fa-chart-pie"></i>
                                <span>Budget Allocation</span>
                            </a>
                        </li>
            

                    </ul>
                </li>
                <?php endif; ?>
                
                <!-- Account Payables - Financial Admin & Disburse Officer -->
                <?php if ($userRole == 'financial admin' || $userRole == 'disburse officer'): ?>
                <li>
                    <div>
                        <a href="#" onclick="toggleDropdown('apDropdown', 'ap')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                            <i class="fas fa-file-invoice-dollar mr-1"></i>
                            <span>Accounts Payables</span>
                            <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'ap' ? 'rotate-90' : ''); ?>"></i>
                        </a>
                        <ul id="apDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'ap' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                            <li class="mb-2">
                                <a href="accounts_payable.php?module=vendor&page=accountspayable" class="flex items-center 
                                    <?php echo (in_array($page, ['accountspayable', 'vendor', 'reimbursement', 'payroll', 'driverpayable'])
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <span>Accounts Payable</span>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="payables_records.php?page=payablesrecords" class="flex items-center 
                                    <?php echo ($page == 'payablesrecords' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-archive"></i>
                                    <span>AP Records</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Disbursement - Financial Admin & Disburse Officer -->
                <?php if ($userRole == 'financial admin' || $userRole == 'disburse officer'): ?>
                <li>
                    <div>
                        <a href="#" onclick="toggleDropdown('disburseDropdown', 'disburse')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                            <i class="fas fa-money-check-alt mr-1"></i>
                            <span>Disbursement</span>
                            <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'disburse' ? 'rotate-90' : ''); ?>"></i>
                        </a>
                        
                        <ul id="disburseDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'disburse' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                            <li class="mb-2">
                                <a href="payout.php?page=payout" class="flex items-center mt-4
                                    <?php echo ($page == 'payout' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-hand-holding-usd"></i>
                                    <span>Payout</span>
                                </a>
                            </li>
                            
                            <li class="mb-2">
                                <a href="disbursedrecords.php?page=disbursedrecords" class="flex items-center 
                                    <?php echo ($page == 'disbursedrecords' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-history"></i>
                                    <span>Disbursed Records</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Collection - Financial Admin & Collector -->
                <?php if ($userRole == 'financial admin' || $userRole == 'collector'): ?>
                <li>
                    <div>
                        <a href="#" onclick="toggleDropdown('collectDropdown', 'collect')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                            <i class="fas fa-hand-holding-usd mr-1"></i>
                            <span>Collection</span>
                            <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'collect' ? 'rotate-90' : ''); ?>"></i>
                        </a>
                        <ul id="collectDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'collect' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                            <li class="mb-2">
                                <a href="paymentrecords.php?page=paymentrecords" class="flex items-center 
                                    <?php echo ($page == 'paymentrecords' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Payments</span>
                                </a>
                            </li>
                          <li class="mb-2">
                                <a href="receivables_receipts.php?page=arreceipts" class="flex items-center
                                    <?php echo ($page == 'arreceipts'
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">                      
                                    <i class="fas fa-receipt"></i>
                                    <span>Receipts</span>
                                </a>
                            </li>
                                                      
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Account Receivables - Financial Admin & Collector -->
                <?php if ($userRole == 'financial admin' || $userRole == 'collector'): ?>
                <li>
                    <div>
                        <a href="#" onclick="toggleDropdown('arDropdown', 'ar')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                            <i class="fas fa-file-invoice mr-1"></i>
                            <span>Account Receivables</span>
                            <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'ar' ? 'rotate-90' : ''); ?>"></i>
                        </a>
                        <ul id="arDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'ar' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                            <li class="mb-2">
                                <a href="receivables_ia.php?page=iareceivables" class="flex items-center 
                                    <?php echo ($page == 'iareceivables' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Invoice Confirmation</span>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="receivables.php?page=receivables" class="flex items-center 
                                    <?php echo ($page == 'receivables' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Receivables</span>
                                </a>
                            </li>
                        
                            <li class="mb-2">
                                <a href="receivables_records.php?page=receivablesrecords" class="flex items-center 
                                    <?php echo ($page == 'receivablesrecords' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-archive"></i>
                                    <span>Receivables Records</span>
                                </a>
                            </li>
                        </ul>
                    </div>      
                </li>
                <?php endif; ?>
                
                <!-- General Ledger - Financial Admin & Auditor -->
                <?php if ($userRole == 'financial admin' || $userRole == 'auditor'): ?>
                <li>
                    <div>
                        <a href="#" onclick="toggleDropdown('glDropdown', 'gl')" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4">
                            <i class="fas fa-book mr-1"></i>
                            <span>General Ledger</span>
                            <i class="fas fa-chevron-right ml-auto transition-transform duration-500 <?php echo ($activeDropdown == 'gl' ? 'rotate-90' : ''); ?>"></i>
                        </a>
                        <ul id="glDropdown" class="pl-4 text-sm overflow-hidden transition-all duration-500 ease-in-out mt-4 max-h-0 opacity-0 bg-purple-500/10 <?php echo ($activeDropdown == 'gl' ? 'max-h-[500px] opacity-100' : 'max-h-0 opacity-0'); ?>">
                            <li class="mb-2">
                                <a href="charts_of_accounts.php?page=chartsofaccounts" class="flex items-center mt-4
                                    <?php echo ($page == 'chartsofaccounts' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Charts of Accounts</span>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="journal_entry.php?page=journalentry" class="flex items-center 
                                    <?php echo ($page == 'journalentry' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-pen-fancy"></i>
                                    <span>Journal Entry</span>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="ledger.php?page=ledger" class="flex items-center 
                                    <?php echo ($page == 'ledger' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-book-open"></i>
                                    <span>Ledger</span>
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="financial_statement.php?page=financialstatement" class="flex items-center 
                                    <?php echo ($page == 'financialstatement' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-file-contract"></i>
                                    <span>Financial Report</span>
                                </a>
                            </li>
            
                            <li class="mb-2">
                                <a href="audit_reports.php?page=auditreports" class="flex items-center 
                                    <?php echo ($page == 'auditreports' 
                                    ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' 
                                    : 'text-[#bfc7d1] hover:text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4'); ?> space-x-2 p-2 pl-12 cursor-pointer">
                                    <i class="fas fa-clipboard-check"></i>
                                    <span>Audit Reports</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <?php endif; ?>
                
                <!-- Legal Management - Financial Admin Only -->
                <?php if ($userRole == 'financial admin'): ?>
                <li>
                    <div>
                        <a href="legal_and_document/index.php?page=legalmanagement" class="flex items-center -ml-4 space-x-1 p-2 pl-12 cursor-pointer text-[#bfc7d1] hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:text-white hover:border-l-4 <?php echo ($page == 'legalmanagement' ? 'text-white bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] border-l-4 border-purple-500' : ''); ?>">
                            <i class="fas fa-gavel mr-1"></i>
                            <span>Legal Management</span>
                        </a>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>

    <!-- Fixed Bottom Section -->
    <div class="mt-auto flex-shrink-0">
        <!-- Gray Line -->
        <div class="h-px bg-gray-600 mx-6 my-4"></div>
        
        <!-- Profile and Logout Links -->
        <?php if ($userRole == 'financial admin' || $userRole == 'auditor' || $userRole == 'budget manager' || $userRole == 'collector' || $userRole == 'disburse officer'): ?>
        <ul>
            <li>
                <div>
                    <a href="profile.php" class="flex items-center space-x-1 p-2 pl-8 text-lg text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4 space-x-2 p-2 pl-12 cursor-pointer">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </div>
            </li>
            <li>
                <div>
                    <a href="logout.php" class="flex items-center space-x-1 p-2 pl-8 mb-12 text-lg text-white hover:bg-[linear-gradient(to_right,_#9A66FF,_#6532C9,_#4311A5)] hover:border-l-4 space-x-2 p-2 pl-12 cursor-pointer">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</aside>

     <!-- Main Content -->
    <main class="flex-1 overflow-hidden">
        <!-- Header Bar -->
        <header class="flex justify-between items-center px-6 py-3 bg-white border border-gray-200 font-poppins">
            <div class="flex items-center space-x-12">
                <button id="menu-toggle" class="cursor-pointer"><i class="fas fa-bars text-2xl"></i></button>
                <a href="<?php echo $dashboard_link; ?>" class="text-lg text-black hover:text-blue-500">Home</a>
            </div>

            <!-- Notification and Profile -->
            <div class="flex items-center space-x-4">

                <!-- Notification Dropdown -->
                <div class="relative cursor-pointer">
                    <button class="flex items-center" onclick="toggleSimpleDropdown('notificationDropdown', event)">
                        <i class="fas fa-envelope text-2xl pr-4"></i>
                        <?php if (!empty($notifications) && count($notifications) > 0): ?>
                            <span id="notificationCount" class="absolute top-0 right-0 bg-purple-700 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">
                                <?php echo count($notifications); ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <div id="notificationDropdown" class="absolute right-0 mt-2 w-56 bg-white overflow-y-auto h-52 rounded-lg shadow-lg py-2 hidden">
                        <?php if (empty($notifications)): ?>
                            <p class="block px-4 py-2 text-gray-700 text-sm">No notifications</p>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="px-4 py-2 border-b border-gray-200 notification-item" 
                                    data-id="<?php echo $notification['id']; ?>" 
                                    onclick="markAsRead(this)">
                                    <p class="text-gray-700 text-sm"><?php echo $notification['message']; ?></p>
                                    <p class="text-gray-500 text-xs"><?php echo date("F j, Y, g:i a", strtotime($notification['timestamp'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                 <!-- Profile Dropdown -->
                <div class="flex items-center space-x-2 relative">
                    <!-- Profile Picture with fallback -->
                    <?php if (!empty($profileData['profile_picture']) && file_exists($profileData['profile_picture'])): ?>
                        <img src="<?php echo $profileData['profile_picture']; ?>" class="rounded-full w-12 h-12 object-cover border border-violet-500 border-4" alt="Profile">
                    <?php else: ?>
                        <!-- Fallback: Show initial letter -->
                        <div class="rounded-full w-10 h-10 bg-purple-600 flex items-center justify-center text-white font-bold text-lg">
                            <span><?php echo strtoupper($_SESSION['givenname'][0] ?? 'U'); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="font-poppins pr-8 pl-2">
                        <div class="font-poppins">
                            <?php
                            $initial = strtoupper($_SESSION['givenname'][0] ?? 'U'); // Get first letter and capitalize
                            $surname = $_SESSION['surname'] ?? 'User'; // Get full surname
                            echo $initial . '. ' . $surname;
                            ?>
                        </div>
                        <div class="text-sm text-gray-500"><?php echo $userRole; ?></div>
                    </div>
                    <div id="userDropdown" class="absolute right-0 w-48 bg-white rounded-md shadow-lg py-2 hidden" style="top: 100%; z-index: 10;">
                        <a class="block px-4 py-2 text-gray-700 font-bold hover:bg-purple-700 hover:text-white" href="profile.php">Profile</a>
                        <a class="block px-4 py-2 text-gray-700 font-bold hover:bg-purple-700 hover:text-white" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

  <script>
// Sidebar toggle
document.getElementById('menu-toggle').addEventListener('click', () => {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-ml-64');
});

// Store the currently open dropdown
let currentOpenDropdown = null;

// Improved toggleDropdown function for sidebar
function toggleDropdown(dropdownId, dropdownType) {
    const dropdown = document.getElementById(dropdownId);
    const icon = dropdown.previousElementSibling.querySelector('i.fa-chevron-right');
    
    // If clicking the same dropdown that's already open, close it
    if (currentOpenDropdown === dropdownId) {
        dropdown.classList.remove("max-h-[500px]", "opacity-100");
        dropdown.classList.add("max-h-0", "opacity-0");
        icon.classList.remove("rotate-90");
        currentOpenDropdown = null;
    } else {
        // Close previously opened dropdown
        if (currentOpenDropdown) {
            const prevDropdown = document.getElementById(currentOpenDropdown);
            const prevIcon = prevDropdown.previousElementSibling.querySelector('i.fa-chevron-right');
            prevDropdown.classList.remove("max-h-[500px]", "opacity-100");
            prevDropdown.classList.add("max-h-0", "opacity-0");
            prevIcon.classList.remove("rotate-90");
        }
        
        // Open new dropdown
        dropdown.classList.remove("max-h-0", "opacity-0");
        dropdown.classList.add("max-h-[500px]", "opacity-100");
        icon.classList.add("rotate-90");
        currentOpenDropdown = dropdownId;
    }
}

// Initialize dropdown states on page load
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($activeDropdown): ?>
        // If there's an active dropdown from PHP, set it as current open dropdown
        const activeDropdownId = '<?php echo $activeDropdown; ?>Dropdown';
        const activeDropdown = document.getElementById(activeDropdownId);
        const activeIcon = activeDropdown.previousElementSibling.querySelector('i.fa-chevron-right');
        
        if (activeDropdown && activeIcon) {
            activeDropdown.classList.remove("max-h-0", "opacity-0");
            activeDropdown.classList.add("max-h-[500px]", "opacity-100");
            activeIcon.classList.add("rotate-90");
            currentOpenDropdown = activeDropdownId;
        }
    <?php endif; ?>
});

// Toggle function for notifications and user dropdowns
function toggleSimpleDropdown(dropdownId, event) {
    event.stopPropagation(); // Prevent the click event from bubbling up
    const dropdown = document.getElementById(dropdownId);
    dropdown.classList.toggle('hidden'); // Toggle the visibility of the dropdown
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    // Close notification dropdown if clicked outside
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown && !event.target.closest('.relative.cursor-pointer')) {
        notificationDropdown.classList.add('hidden');
    }
    // Close user dropdown if clicked outside
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown && !event.target.closest('.flex.items-center.space-x-2')) {
        userDropdown.classList.add('hidden');
    }
});


// Function to mark notification as read
function markAsRead(element) {
    const notificationId = element.getAttribute('data-id');
    
    // Add visual feedback that it's read
    element.style.backgroundColor = '#f0f0f0'; // Optional: Change background color
    element.style.opacity = '0.7'; // Optional: Change opacity

    // Update the notification count
    const countElement = document.getElementById('notificationCount');
    let currentCount = parseInt(countElement.textContent);

    // Only decrease count if it hasn't been read yet
    if (currentCount > 0 && !element.classList.contains('read')) {
        currentCount--;
        countElement.textContent = currentCount;

        // Hide the count badge if no unread notifications left
        if (currentCount === 0) {
            countElement.style.display = 'none';
        }

        // Mark as read in the DOM
        element.classList.add('read');

        // Send AJAX request to server to mark as read
        markNotificationAsReadOnServer(notificationId);
    }
}

// AJAX function to update server
function markNotificationAsReadOnServer(notificationId) {
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to mark notification as read');
            // Optionally revert the UI changes if the server update failed
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
  

// Define the suggestions and their corresponding pages
    const suggestionsData = {
        "Admin Dashboard": "dashboard_admin.php?page=sidebar",
        "Auditor Dashboard": "dashboard_auditor.php?page=sidebar",
        "Budget Manager Dashboard": "dashboard_budget_manager.php?page=sidebar",
        "Collector Dashboard": "dashboard_collector.php?page=sidebar",
        "Disburse Officer Dashboard": "dashboard_disburse_officer.php?page=sidebar",
        "Budget Requests": "budget_request.php",
        "Rejected Requests": "rejected_request.php",
        "Budget Allocation": "budget_allocation.php",
        "Budget Estimation": "budget_estimation.php",
        "Payout": "payout.php",
        "Disbursed Records": "disbursedrecords.php",
        "Payment Records": "paymentrecords.php",
        "Payables Receipts": "payables_receipts.php",
        "Receivables Receipts": "receivables_receipts.php",
        "Reimbursement": "reimbursement.php?page=reimbursement",
        "Vendor Management": "vendor.php?page=vendor",
        "Payables": "payables.php",
        "Payables Records": "payables_records.php",
        "Payroll Management": "payroll.php",
        "Invoice Approval (Receivables)": "receivables_ia.php",
        "Receivables": "receivables.php",
        "Receivables Records": "receivables_records.php",
        "Charts of Accounts": "charts_of_accounts.php",
        "Journal Entry": "journal_entry.php",
        "Ledger": "ledger.php",
        "Trial Balance": "trial_balance.php",
        "Financial Statement": "financial_statement.php",
        "Audit Reports": "audit_reports.php",
        "Request Portal": "request_portal.php?page=requestportal",
        "User Management": "user_management.php?page=usermanagement",
        "Legal Management": "vendor/phpmailer/legal_and_document/index.php?page=legalmanagement"
    };

    function showSuggestions() {
        const input = document.getElementById("searchInputnavbar");
        const suggestionsBox = document.getElementById("suggestions");
        const query = input.value.toLowerCase().trim();

        // Clear previous suggestions
        suggestionsBox.innerHTML = "";
        suggestionsBox.classList.add("hidden");

        if (query === "") return; // Do not show suggestions for an empty input

        // Filter suggestions based on the query
        const filteredSuggestions = Object.keys(suggestionsData).filter(name =>
            name.toLowerCase().includes(query)
        );

        // Display suggestions
        if (filteredSuggestions.length > 0) {
            suggestionsBox.classList.remove("hidden");
            filteredSuggestions.forEach(name => {
                const suggestionItem = document.createElement("div");
                suggestionItem.textContent = name;
                suggestionItem.classList.add("py-2", "px-4", "hover:bg-gray-100", "cursor-pointer");

                // Add click event to redirect to the corresponding page
                suggestionItem.onclick = () => {
                    window.location.href = suggestionsData[name]; // Navigate to the page
                };

                suggestionsBox.appendChild(suggestionItem);
            });
        }
    }
</script>