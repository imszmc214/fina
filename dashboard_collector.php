<?php
session_start();
include 'session_manager.php';
include 'connection.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];

// Role-based access control - Only allow specific roles to access this dashboard
$allowed_roles = ['collector']; // Collector lang ang allowed
if (!in_array($role, $allowed_roles)) {
    header("Location: unauthorized.php");
    exit();
}

// Initialize variables for PIN verification
$pin_verified = isset($_SESSION['pin_verified']) && $_SESSION['pin_verified'] === true;
$pin_error = '';
$user_name = $_SESSION['givenname'] . ' ' . $_SESSION['surname'];
$user_role = $_SESSION['user_role'];

// Initialize session variables if not set
if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
}

if (!isset($_SESSION['lockout_until'])) {
    $_SESSION['lockout_until'] = null;
}

// Get user PIN from database
$user_id = $_SESSION['user_id'];
$pin_sql = "SELECT pin, account_status, failed_attempts FROM userss WHERE id = ?";
$pin_stmt = $conn->prepare($pin_sql);
$pin_stmt->bind_param("i", $user_id);
$pin_stmt->execute();
$pin_result = $pin_stmt->get_result();

if ($pin_result->num_rows > 0) {
    $user_data = $pin_result->fetch_assoc();
    $stored_pin = $user_data['pin'];
    $_SESSION['failed_attempts'] = $user_data['failed_attempts'] ?? 0;
    $account_status = $user_data['account_status'];
} else {
    $account_status = 'active';
}

// Check if account is locked in database
if ($account_status === 'locked') {
    $_SESSION['pin_error'] = "Your account is locked. Please contact administrator.";
    $_SESSION['failed_attempts'] = 6;
}

// Check if we're currently in a lockout period
$current_time = time();
if ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until']) {
    $remaining_time = $_SESSION['lockout_until'] - $current_time;
    $_SESSION['pin_error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
    $_SESSION['countdown_active'] = true;
} else {
    $_SESSION['countdown_active'] = false;
}

// Handle filter parameters - Default to current month
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';

// Generate year options (current year and previous 5 years)
$current_year = date('Y');
$years = [];
for ($i = 0; $i < 6; $i++) {
    $years[] = $current_year - $i;
}

// Month names
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Handle PIN verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_pin'])) {
    // Check if account is locked or suspended in database
    if ($account_status === 'locked') {
        $_SESSION['pin_error'] = "Your account is locked. Please contact administrator.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Check if we're in a lockout period
    if ($_SESSION['lockout_until'] && $current_time < $_SESSION['lockout_until']) {
        $remaining_time = $_SESSION['lockout_until'] - $current_time;
        $_SESSION['pin_error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
        $_SESSION['countdown_active'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Validate and sanitize PIN inputs
    $entered_pin = '';
    for ($i = 1; $i <= 6; $i++) {
        $pin_digit = $_POST['pin' . $i] ?? '';
        if (!preg_match('/^[0-9]$/', $pin_digit)) {
            $_SESSION['pin_error'] = "Invalid PIN format. Please enter digits only.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        $entered_pin .= $pin_digit;
    }

    // Verify the code with timing-safe comparison
    if (hash_equals($stored_pin, $entered_pin)) {
        // PIN verified successfully
        $_SESSION['pin_verified'] = true;
        $_SESSION['failed_attempts'] = 0;
        $_SESSION['lockout_until'] = null;
        $_SESSION['countdown_active'] = false;
        $pin_verified = true;
        
        // Reset failed attempts in database
        $reset_sql = "UPDATE userss SET failed_attempts = 0 WHERE id = ?";
        $reset_stmt = $conn->prepare($reset_sql);
        $reset_stmt->bind_param("i", $user_id);
        $reset_stmt->execute();
        $reset_stmt->close();
        
        // Refresh the page to show actual dashboard
        header("Location: dashboard_collector.php");
        exit();
    } else {
        // Wrong PIN
        $_SESSION['failed_attempts']++;
        
        // Update failed attempts in database
        $update_sql = "UPDATE userss SET failed_attempts = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $_SESSION['failed_attempts'], $user_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Check if we've reached the lockout threshold
        if ($_SESSION['failed_attempts'] >= 6) {
            // Permanently lock the account in database
            $lock_sql = "UPDATE userss SET account_status = 'locked' WHERE id = ?";
            $lock_stmt = $conn->prepare($lock_sql);
            $lock_stmt->bind_param("i", $user_id);
            $lock_stmt->execute();
            $lock_stmt->close();
            
            // Update local status
            $account_status = 'locked';
            
            $_SESSION['pin_error'] = "Too many failed attempts. Your account has been locked. Please contact administrator.";
            $_SESSION['countdown_active'] = false;
        } elseif ($_SESSION['failed_attempts'] >= 5) {
            // Set 60-second lockout for 5th failed attempt
            $_SESSION['lockout_until'] = $current_time + 60;
            $remaining_time = 60;
            $_SESSION['pin_error'] = "Too many failed attempts. Please try again in <span id='countdown-seconds'>$remaining_time</span> seconds.";
            $_SESSION['countdown_active'] = true;
        } else {
            // Regular failed attempt message
            $attempts_remaining = 5 - $_SESSION['failed_attempts'];
            $_SESSION['pin_error'] = "Incorrect PIN. $attempts_remaining attempts remaining.";
            $_SESSION['countdown_active'] = false;
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Only fetch dashboard data if PIN is verified
if ($pin_verified) {
    // Build date filters based on selected filter type
    if ($filter_type === 'year') {
        // Filter for entire year
        $ledger_date_filter = "YEAR(transaction_date) = $selected_year";
        $period_text = "Year $selected_year";
    } else {
        // Filter for specific month and year
        $ledger_date_filter = "YEAR(transaction_date) = $selected_year AND MONTH(transaction_date) = $selected_month";
        $period_text = $months[$selected_month] . " $selected_year";
    }

    // Fetch Total Revenue, Total Expenses, and Net Income with filters
    $sql_revenue = "SELECT SUM(credit_amount) AS total_revenue FROM general_ledger WHERE account_type = 'Revenue' AND $ledger_date_filter";
    $result_revenue = $conn->query($sql_revenue);
    $total_revenue = ($result_revenue->num_rows > 0) ? floatval($result_revenue->fetch_assoc()['total_revenue']) : 0;

    $sql_expenses = "SELECT SUM(debit_amount) AS total_expenses FROM general_ledger WHERE account_type = 'Expense' AND $ledger_date_filter";
    $result_expenses = $conn->query($sql_expenses);
    $total_expenses = ($result_expenses->num_rows > 0) ? floatval($result_expenses->fetch_assoc()['total_expenses']) : 0;

    $net_income = $total_revenue - $total_expenses;

    // ==================== RECEIVABLES DATA ====================
    // Receivables metrics
    $receivables_pending = $conn->query("SELECT COUNT(*) as total FROM account_receivable WHERE status = 'pending'")->fetch_assoc()['total'];
    $receivables_confirmed = $conn->query("SELECT COUNT(*) as total FROM account_receivable WHERE status = 'confirmed' AND amount_paid < amount")->fetch_assoc()['total'];
    $receivables_total_due = $conn->query("SELECT SUM(amount) as total FROM account_receivable WHERE status = 'confirmed' AND amount_paid < amount")->fetch_assoc()['total'];
    $receivables_remaining = $conn->query("SELECT SUM(amount - amount_paid) as total FROM account_receivable WHERE status = 'confirmed'")->fetch_assoc()['total'];

    // Receivables IA metrics
    $receivables_ia_pending = $conn->query("SELECT COUNT(*) as total FROM account_receivable WHERE status = 'pending'")->fetch_assoc()['total'];
    $receivables_ia_confirmed_month = $conn->query("
        SELECT COUNT(*) as total 
        FROM account_receivable 
        WHERE status = 'confirmed' 
        AND MONTH(approval_date) = MONTH(CURRENT_DATE)
        AND YEAR(approval_date) = YEAR(CURRENT_DATE)
    ")->fetch_assoc()['total'];
    $receivables_ia_total_due = $conn->query("SELECT SUM(amount) as total FROM account_receivable WHERE status = 'pending'")->fetch_assoc()['total'];

    // Payment Records metrics
    $payments_total = $conn->query("SELECT COUNT(*) as total FROM collections")->fetch_assoc()['total'];
    $payments_total_amount = $conn->query("SELECT SUM(amount) as total FROM collections")->fetch_assoc()['total'];
    $payments_today = $conn->query("SELECT COUNT(*) as total FROM collections WHERE DATE(payment_date) = CURDATE()")->fetch_assoc()['total'];
    $payments_today_amount = $conn->query("SELECT SUM(amount) as total FROM collections WHERE DATE(payment_date) = CURDATE()")->fetch_assoc()['total'];

    // Payment method distribution
    $payments_cash = $conn->query("SELECT COUNT(*) as total FROM collections WHERE LOWER(mode_of_payment) = 'cash'")->fetch_assoc()['total'];
    $payments_credit = $conn->query("SELECT COUNT(*) as total FROM collections WHERE LOWER(mode_of_payment) = 'credit' OR LOWER(mode_of_payment) LIKE '%bank%'")->fetch_assoc()['total'];
    $payments_ecash = $conn->query("SELECT COUNT(*) as total FROM collections WHERE LOWER(mode_of_payment) = 'ecash'")->fetch_assoc()['total'];

    // Recent receivables
    $receivables_sql = "SELECT * FROM account_receivable WHERE status = 'confirmed' AND amount_paid < amount ORDER BY approval_date DESC LIMIT 5";
    $receivables_result = $conn->query($receivables_sql);
    $receivables_rows = [];

    if ($receivables_result && $receivables_result->num_rows > 0) {
      while ($row = $receivables_result->fetch_assoc()) {
        $receivables_rows[] = $row;
      }
    }

    // Recent payments
    $payments_sql = "SELECT * FROM collections ORDER BY payment_date DESC LIMIT 5";
    $payments_result = $conn->query($payments_sql);
    $payments_rows = [];

    if ($payments_result && $payments_result->num_rows > 0) {
      while ($row = $payments_result->fetch_assoc()) {
        $payments_rows[] = $row;
      }
    }
}
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <title><?php echo $pin_verified ? 'Dashboard - Collector' : 'PIN Verification'; ?></title>
    <link rel="icon" href="logo.png" type="img">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            overflow: auto !important;
        }
        
        .dashboard-blur {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .main-content {
            overflow-y: auto;
            height: 100vh;
        }

        .pin-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: none !important;
            background: rgba(0, 0, 0, 0.7) !important;
            overflow-y: auto;
            padding: 20px 0;
        }

        .pin-container {
            border-radius: 20px;
            padding: 2rem;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            filter: none !important;
            backdrop-filter: none !important;
            background: white !important;
            margin: auto;
        }

        .pin-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            border-radius: 10px;
            margin: 0 5px;
            background: white !important;
            color: black !important;
            border: 2px solid #8b5cf6 !important;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .pin-input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
            outline: none;
        }

        .pin-input:disabled {
            background-color: #f3f4f6 !important;
            border-color: #d1d5db !important;
            color: #9ca3af !important;
            cursor: not-allowed;
        }
        
        .shake {
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }
        
        @keyframes shake {
            10%, 90% { transform: translateX(-2px); }
            20%, 80% { transform: translateX(4px); }
            30%, 50%, 70% { transform: translateX(-6px); }
            40%, 60% { transform: translateX(6px); }
        }

        .scrollable-content {
            overflow-y: auto;
            flex: 1;
        }

        /* Filter Styles */
        .filter-container {
            position: relative;
            display: inline-block;
        }
        
        .filter-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 20px;
            min-width: 280px;
            z-index: 100;
            margin-top: 8px;
        }
        
        .filter-dropdown.show {
            display: block;
        }
        
        .filter-option {
            margin-bottom: 15px;
        }
        
        .filter-option:last-child {
            margin-bottom: 0;
        }
        
        .filter-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .filter-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            font-size: 14px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        
        .filter-apply-btn {
            width: 100%;
            background: #7c3aed;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .filter-apply-btn:hover {
            background: #6d28d9;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 24px;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            color: white;
            border-radius: 16px;
            padding: 20px;
        }
        
        .stat-card-secondary {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
        }

        .btn-disabled {
            background-color: #9ca3af !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 0.6 !important;
        }

        .btn-disabled:hover {
            background-color: #9ca3af !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php if ($pin_verified): ?>
        <!-- ACTUAL DASHBOARD CONTENT -->
        <?php include('sidebar.php'); ?>
        
        <div class="flex-1 flex flex-col overflow-y-auto h-full px-6 py-6">
            <!-- Page Header with Filter -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-poppins text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Showing data for: <span class="font-semibold text-purple-600"><?php echo $period_text; ?></span></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm text-gray-500">
                        <a href="dashboard.php?page=dashboard" class="text-gray-900 hover:text-blue-800">Home</a>
                        <span class="mx-2">/</span>
                        <span class="text-blue-600">Dashboard</span>
                    </div>
                </div>
            </div>

            <!-- Filter Button Container -->
            <div class="flex justify-end">
              <div class="filter-container">
                <button id="filterToggle" class="flex items-center space-x-2 bg-white border border-gray-300 rounded-lg px-4 py-2 my-4 hover:bg-gray-50 transition-colors">
                  <i class="fas fa-filter text-gray-600"></i>
                  <span class="text-sm font-medium text-gray-700">Filter</span>
                  <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                </button>

                <!-- Filter Dropdown -->
                <div id="filterDropdown" class="filter-dropdown">
                  <div class="filter-option">
                    <label class="filter-label">Filter Type</label>
                    <select id="filterType" class="filter-select">
                      <option value="month" <?php echo $filter_type === 'month' ? 'selected' : ''; ?>>Monthly</option>
                      <option value="year" <?php echo $filter_type === 'year' ? 'selected' : ''; ?>>Yearly</option>
                    </select>
                  </div>

                  <div class="filter-option">
                    <label class="filter-label">Year</label>
                    <select id="filterYear" class="filter-select">
                      <?php foreach ($years as $year): ?>
                      <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div class="filter-option" id="monthFilterContainer" style="<?php echo $filter_type === 'year' ? 'display: none;' : ''; ?>">
                    <label class="filter-label">Month</label>
                    <select id="filterMonth" class="filter-select">
                      <?php foreach ($months as $key => $month): ?>
                      <option value="<?php echo $key; ?>" <?php echo $selected_month == $key ? 'selected' : ''; ?>>
                        <?php echo $month; ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <button id="applyFilter" class="filter-apply-btn">
                    Apply Filter
                  </button>
                </div>
              </div>
            </div>

            <!-- Main Balance Cards - TOTAL REVENUE, TOTAL EXPENSES, AND NET INCOME -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Revenue Card -->
                <div class="stat-card" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm opacity-80">Total Revenue</p>
                            <h2 class="text-2xl font-bold mt-1">₱ <?php echo number_format($total_revenue, 2); ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">All Income Sources</span>
                        <span class="text-sm font-semibold"><?php echo $period_text; ?></span>
                    </div>
                </div>
                
                <!-- Total Expenses Card -->
                <div class="stat-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm opacity-80">Total Expenses</p>
                            <h2 class="text-2xl font-bold mt-1">₱ <?php echo number_format($total_expenses, 2); ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                            <i class="fas fa-receipt text-xl"></i>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">All Expense Categories</span>
                        <span class="text-sm font-semibold"><?php echo $period_text; ?></span>
                    </div>
                </div>
                
                <!-- Net Income Card -->
                <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <p class="text-sm opacity-80">Net Income</p>
                            <h2 class="text-2xl font-bold mt-1">₱ <?php echo number_format($net_income, 2); ?></h2>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm">Revenue - Expenses</span>
                        <span class="text-sm font-semibold"><?php echo $period_text; ?></span>
                    </div>
                </div>
            </div>

            <!-- Three Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Receivables -->
                <div class="space-y-6">
                    <!-- Receivables Overview Card -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Receivables Overview</h2>
                            <a href="receivables.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-purple-600"><?php echo $receivables_confirmed; ?></div>
                                <div class="text-sm text-gray-600 mt-1">Confirmed Invoices</div>
                            </div>
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-green-600">₱ <?php echo number_format($receivables_total_due, 2); ?></div>
                                <div class="text-sm text-gray-600 mt-1">Total Amount Due</div>
                            </div>
                        </div>
                        
                        <!-- Status Summary -->
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Status Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Pending Approval</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium"><?php echo $receivables_ia_pending; ?> invoices</span>
                                    <span class="text-sm font-semibold text-blue-600">₱ <?php echo number_format($receivables_ia_total_due, 2); ?></span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Confirmed</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium"><?php echo $receivables_confirmed; ?> invoices</span>
                                    <span class="text-sm font-semibold text-purple-600">₱ <?php echo number_format($receivables_total_due, 2); ?></span>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Remaining Balance</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium"><?php echo $receivables_confirmed; ?> invoices</span>
                                    <span class="text-sm font-semibold text-red-600">₱ <?php echo number_format($receivables_remaining, 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <a href="receivables_ia.php" class="bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg p-4 text-center transition-colors">
                                <div class="text-blue-600 mb-2">
                                    <i class="fas fa-clipboard-list text-2xl"></i>
                                </div>
                                <p class="text-sm font-medium text-blue-800">Review Invoices</p>
                            </a>
                            
                            <a href="receivables.php" class="bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg p-4 text-center transition-colors">
                                <div class="text-purple-600 mb-2">
                                    <i class="fas fa-file-invoice-dollar text-2xl"></i>
                                </div>
                                <p class="text-sm font-medium text-purple-800">Manage Receivables</p>
                            </a>
                            
                            <a href="paymentrecords.php" class="bg-green-50 hover:bg-green-100 border border-green-200 rounded-lg p-4 text-center transition-colors">
                                <div class="text-green-600 mb-2">
                                    <i class="fas fa-money-bill-wave text-2xl"></i>
                                </div>
                                <p class="text-sm font-medium text-green-800">View Payments</p>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Middle Column: Receivables IA -->
                <div class="space-y-6">
                    <!-- Receivables IA Overview Card -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Invoice Approval Overview</h2>
                            <a href="receivables_ia.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-blue-600"><?php echo $receivables_ia_pending; ?></div>
                                <div class="text-sm text-gray-600 mt-1">Pending Invoices</div>
                            </div>
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo $receivables_ia_confirmed_month; ?></div>
                                <div class="text-sm text-gray-600 mt-1">Approved This Month</div>
                            </div>
                        </div>
                        
                        <!-- Amount Summary -->
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Amount Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Pending Amount</span>
                                <span class="text-sm font-semibold text-blue-600">₱ <?php echo number_format($receivables_ia_total_due, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Average per Invoice</span>
                                <span class="text-sm font-semibold text-purple-600">₱ <?php echo $receivables_ia_pending > 0 ? number_format($receivables_ia_total_due / $receivables_ia_pending, 2) : '0.00'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Payments -->
                <div class="space-y-6">
                    <!-- Payments Overview Card -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Payments Overview</h2>
                            <a href="paymentrecords.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-green-600"><?php echo $payments_total; ?></div>
                                <div class="text-sm text-gray-600 mt-1">Total Payments</div>
                            </div>
                            <div class="stat-card-secondary text-center">
                                <div class="text-2xl font-bold text-blue-600">₱ <?php echo number_format($payments_total_amount, 2); ?></div>
                                <div class="text-sm text-gray-600 mt-1">Total Amount</div>
                            </div>
                        </div>
                        
                        <!-- Payment Mode Distribution -->
                        <h3 class="text-md font-semibold text-gray-900 mb-4">Payment Distribution</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                    <span class="text-sm text-gray-600">Cash</span>
                                </div>
                                <span class="text-sm font-semibold"><?php echo $payments_cash; ?> payments</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                    <span class="text-sm text-gray-600">Credit/Bank</span>
                                </div>
                                <span class="text-sm font-semibold"><?php echo $payments_credit; ?> payments</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                                    <span class="text-sm text-gray-600">E-Cash</span>
                                </div>
                                <span class="text-sm font-semibold"><?php echo $payments_ecash; ?> payments</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <canvas id="pdf-viewer" width="600" height="400"></canvas>
            </div>
        </div>

    <?php else: ?>
        <!-- BLURRED DASHBOARD ONLY - PIN OVERLAY SEPARATE -->
        <div class="dashboard-blur">
            <?php include('sidebar.php'); ?>
            <div class="flex-1 flex flex-col h-screen">
                <!-- Page Header with Filter -->
                <div class="flex justify-between items-center px-6 py-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p class="text-sm text-gray-600 mt-1">Showing data for: <span class="font-semibold text-purple-600">Current Month</span></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-500">
                            <a href="dashboard.php?page=dashboard" class="text-gray-900 hover:text-blue-800">Home</a>
                            <span class="mx-2">/</span>
                            <span class="text-blue-600">Dashboard</span>
                        </div>
                    </div>
                </div>

                <!-- Filter Button Container -->
                <div class="flex justify-end px-6">
                  <div class="filter-container">
                    <button class="flex items-center space-x-2 bg-white border border-gray-300 rounded-lg px-4 py-2 my-4 hover:bg-gray-50 transition-colors opacity-50">
                      <i class="fas fa-filter text-gray-600"></i>
                      <span class="text-sm font-medium text-gray-700">Filter</span>
                      <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </button>
                  </div>
                </div>

                <!-- Blurred content -->
                <div class="flex-1 px-6">
                    <!-- Blurred balance cards -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 opacity-50">
                        <!-- Total Revenue Card -->
                        <div class="stat-card" style="background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 100%);">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-sm opacity-80">Total Revenue</p>
                                    <h2 class="text-2xl font-bold mt-1">₱ •••,•••.••</h2>
                                </div>
                                <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                                    <i class="fas fa-money-bill-wave text-xl"></i>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">All Income Sources</span>
                                <span class="text-sm font-semibold">Current Period</span>
                            </div>
                        </div>
                        
                        <!-- Total Expenses Card -->
                        <div class="stat-card" style="background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-sm opacity-80">Total Expenses</p>
                                    <h2 class="text-2xl font-bold mt-1">₱ •••,•••.••</h2>
                                </div>
                                <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                                    <i class="fas fa-receipt text-xl"></i>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">All Expense Categories</span>
                                <span class="text-sm font-semibold">Current Period</span>
                            </div>
                        </div>
                        
                        <!-- Net Income Card -->
                        <div class="stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="text-sm opacity-80">Net Income</p>
                                    <h2 class="text-2xl font-bold mt-1">₱ •••,•••.••</h2>
                                </div>
                                <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                                    <i class="fas fa-chart-line text-xl"></i>
                                </div>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Revenue - Expenses</span>
                                <span class="text-sm font-semibold">Current Period</span>
                            </div>
                        </div>
                    </div>

                    <!-- Blurred three column layout -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 opacity-50">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div class="card">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-semibold text-gray-900">Receivables Overview</h2>
                                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-purple-600">•••</div>
                                        <div class="text-sm text-gray-600 mt-1">Confirmed Invoices</div>
                                    </div>
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-green-600">₱ •••,•••.••</div>
                                        <div class="text-sm text-gray-600 mt-1">Total Amount Due</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Middle Column -->
                        <div class="space-y-6">
                            <div class="card">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-semibold text-gray-900">Invoice Approval Overview</h2>
                                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-blue-600">•••</div>
                                        <div class="text-sm text-gray-600 mt-1">Pending Invoices</div>
                                    </div>
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-green-600">•••</div>
                                        <div class="text-sm text-gray-600 mt-1">Approved This Month</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="space-y-6">
                            <div class="card">
                                <div class="flex justify-between items-center mb-6">
                                    <h2 class="text-lg font-semibold text-gray-900">Payments Overview</h2>
                                    <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All →</a>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-green-600">•••</div>
                                        <div class="text-sm text-gray-600 mt-1">Total Payments</div>
                                    </div>
                                    <div class="stat-card-secondary text-center">
                                        <div class="text-2xl font-bold text-blue-600">₱ •••,•••.••</div>
                                        <div class="text-sm text-gray-600 mt-1">Total Amount</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PIN VERIFICATION OVERLAY -->
        <div class="pin-overlay">
            <div class="pin-container">
                <!-- User Info -->
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                        <i class="fas fa-user text-white text-xl"></i>
                    </div>
                    <h3 style="font-weight: bold; font-size: 1.125rem;"><?php echo htmlspecialchars($user_name); ?></h3>
                    <p style="font-size: 0.875rem; opacity: 0.9;"><?php echo htmlspecialchars($user_role); ?></p>
                    <p style="font-size: 0.75rem; opacity: 0.8; margin-top: 4px;">PIN Verification Required</p>
                </div>

                <!-- Header -->
                <div style="text-align: center; margin-bottom: 24px;">
                    <div style="width: 64px; height: 64px; background: #ede9fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                        <i class="fas fa-lock text-violet-500 text-2xl"></i>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937;">Enter Your PIN</h2>
                    <p style="color: #6b7280; margin-top: 4px;">To access your dashboard</p>
                </div>

                <!-- Error Message -->
                <?php if (isset($_SESSION['pin_error'])): ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #fecaca; display: flex; align-items: start;">
                    <i class="fas fa-exclamation-circle mt-0.5 mr-2"></i>
                    <span><?php echo $_SESSION['pin_error']; ?></span>
                </div>
                <?php unset($_SESSION['pin_error']); ?>
                <?php else: ?>
                <div id="error-message" style="display: none; background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 16px; border: 1px solid #fecaca;">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>Incorrect PIN. Please try again.</span>
                </div>
                <?php endif; ?>

                <!-- PIN Inputs -->
                <form id="pin-form" method="POST" action="">
                    <input type="hidden" name="verify_pin" value="1">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 24px;" id="pin-container">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <input
                            type="password"
                            name="pin<?php echo $i; ?>"
                            class="pin-input <?php echo (isset($_SESSION['shake']) ? 'shake' : ''); ?>"
                            maxlength="1"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            id="pin-<?php echo $i; ?>"
                            autocomplete="off"
                            onpaste="return false;"
                            ondrop="return false;"
                            <?php 
                            // Disable inputs if account is locked or in lockout period
                            if ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) {
                                echo 'disabled';
                            }
                            ?>
                        />
                        <?php endfor; ?>
                        <?php unset($_SESSION['shake']); ?>
                    </div>

                    <!-- Show PIN Toggle -->
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                        <button
                            type="button"
                            id="toggle-pin"
                            class="<?php echo ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="color: #8b5cf6; font-weight: 500; display: flex; align-items: center; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in lockout period
                            if ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) {
                                echo 'disabled';
                            }
                            ?>
                        >
                            <i class="fas fa-eye mr-2"></i> Show PIN
                        </button>
                    </div>

                    <!-- Buttons -->
                    <div style="display: flex; gap: 12px;">
                        <button
                            type="button"
                            id="clear-btn"
                            class="<?php echo ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="flex: 1; background: #6b7280; color: white; font-weight: 500; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in lockout period
                            if ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) {
                                echo 'disabled';
                            }
                            ?>
                        >
                            Clear
                        </button>
                        <button
                            type="submit"
                            id="verify-btn"
                            class="<?php echo ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="flex: 1; background: #8b5cf6; color: white; font-weight: 500; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in lockout period
                            if ($_SESSION['failed_attempts'] >= 5 || $_SESSION['countdown_active']) {
                                echo 'disabled';
                            }
                            ?>
                        >
                            Verify PIN
                        </button>
                    </div>
                </form>

                <!-- Security Notice -->
                <div style="margin-top: 16px; text-align: center;">
                    <p style="font-size: 0.75rem; color: #6b7280;">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Your dashboard is secured with PIN verification
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        <?php if ($pin_verified): ?>
        // Filter functionality
        const filterToggle = document.getElementById('filterToggle');
        const filterDropdown = document.getElementById('filterDropdown');
        const filterType = document.getElementById('filterType');
        const filterYear = document.getElementById('filterYear');
        const filterMonth = document.getElementById('filterMonth');
        const monthFilterContainer = document.getElementById('monthFilterContainer');
        const applyFilter = document.getElementById('applyFilter');

        // Toggle filter dropdown
        filterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            filterDropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!filterToggle.contains(e.target) && !filterDropdown.contains(e.target)) {
                filterDropdown.classList.remove('show');
            }
        });

        // Show/hide month filter based on filter type
        filterType.addEventListener('change', function() {
            if (this.value === 'year') {
                monthFilterContainer.style.display = 'none';
            } else {
                monthFilterContainer.style.display = 'block';
            }
        });

        // Apply filter
        applyFilter.addEventListener('click', function() {
            const type = filterType.value;
            const year = filterYear.value;
            const month = filterMonth.value;
            
            let url = `dashboard_collector.php?filter_type=${type}&year=${year}`;
            if (type === 'month') {
                url += `&month=${month}`;
            }
            
            window.location.href = url;
        });

        <?php else: ?>
        // PIN Verification JavaScript
        const pinInputs = Array.from({ length: 6 }, (_, i) =>
            document.getElementById(`pin-${i + 1}`)
        );
        const pinContainer = document.getElementById("pin-container");
        const togglePinBtn = document.getElementById("toggle-pin");
        const clearBtn = document.getElementById("clear-btn");
        const verifyBtn = document.getElementById("verify-btn");
        const errorMessage = document.getElementById("error-message");
        const pinForm = document.getElementById("pin-form");
        let isPinVisible = false;

        // Check if inputs are disabled (max attempts reached or lockout active)
        const isDisabled = pinInputs[0].disabled;

        <?php if ($_SESSION['countdown_active']): ?>
        // Countdown timer for lockout period
        let countdownSeconds = <?php echo ($_SESSION['lockout_until'] - time()); ?>;
        const countdownElement = document.querySelector('#countdown-seconds');
        
        function updateCountdown() {
            if (countdownSeconds > 0) {
                countdownSeconds--;
                if (countdownElement) {
                    countdownElement.textContent = countdownSeconds;
                }
                setTimeout(updateCountdown, 1000);
            } else {
                // Reload page when countdown finishes
                location.reload();
            }
        }
        
        updateCountdown();
        <?php endif; ?>

        if (!isDisabled) {
            // Focus first input
            setTimeout(() => {
                pinInputs[0].focus();
            }, 100);

            // Enhanced keyboard handling for backspace
            document.addEventListener("keydown", function (e) {
                const currentFocused = document.activeElement;
                const currentIndex = pinInputs.indexOf(currentFocused);
                
                if (e.key >= "0" && e.key <= "9") {
                    // Number input
                    if (currentIndex !== -1) {
                        currentFocused.value = e.key;
                        setTimeout(() => {
                            if (currentIndex < 5) {
                                pinInputs[currentIndex + 1].focus();
                            } else {
                                // Auto-submit when last digit is entered
                                setTimeout(() => {
                                    pinForm.submit();
                                }, 100);
                            }
                        }, 10);
                    }
                    e.preventDefault();
                } else if (e.key === "Backspace") {
                    // Enhanced backspace handling
                    e.preventDefault();
                    if (currentIndex !== -1) {
                        if (currentFocused.value === "") {
                            // If current field is empty, move to previous field and clear it
                            if (currentIndex > 0) {
                                pinInputs[currentIndex - 1].focus();
                                pinInputs[currentIndex - 1].value = "";
                            }
                        } else {
                            // If current field has value, clear it but stay in the same field
                            currentFocused.value = "";
                        }
                    }
                } else if (e.key === "ArrowLeft" && currentIndex > 0) {
                    // Arrow left navigation
                    e.preventDefault();
                    pinInputs[currentIndex - 1].focus();
                } else if (e.key === "ArrowRight" && currentIndex < 5) {
                    // Arrow right navigation
                    e.preventDefault();
                    pinInputs[currentIndex + 1].focus();
                } else if (e.key === "Delete") {
                    // Delete key handling
                    e.preventDefault();
                    currentFocused.value = "";
                }
            });

            pinInputs.forEach((input, index) => {
                input.addEventListener("input", function () {
                    if (this.value.length === 1 && index < 5) {
                        pinInputs[index + 1].focus();
                    }
                });

                input.addEventListener("click", function () {
                    this.select();
                });

                input.addEventListener("paste", function (e) {
                    e.preventDefault();
                });
            });

            // Toggle PIN visibility
            togglePinBtn.addEventListener("click", function () {
                isPinVisible = !isPinVisible;
                pinInputs.forEach((input) => {
                    input.type = isPinVisible ? "text" : "password";
                });
                this.innerHTML = isPinVisible
                    ? '<i class="fas fa-eye-slash mr-2"></i> Hide PIN'
                    : '<i class="fas fa-eye mr-2"></i> Show PIN';
            });

            // Clear all PIN inputs
            clearBtn.addEventListener("click", function () {
                pinInputs.forEach((input) => {
                    input.value = "";
                });
                pinInputs[0].focus();
                if (errorMessage) errorMessage.style.display = "none";
                pinContainer.classList.remove("shake");
            });

            // Form submission validation
            pinForm.addEventListener("submit", function (e) {
                const pin = pinInputs.map((input) => input.value).join("");
                if (pin.length !== 6) {
                    e.preventDefault();
                    if (errorMessage) errorMessage.style.display = "flex";
                    pinContainer.classList.add("shake");
                    setTimeout(() => {
                        pinContainer.classList.remove("shake");
                    }, 500);
                }
            });

            // Auto-submit when all PIN digits are entered
            pinInputs.forEach((input) => {
                input.addEventListener("input", function () {
                    const allFilled = pinInputs.every((input) => input.value.length === 1);
                    if (allFilled) {
                        setTimeout(() => {
                            pinForm.submit();
                        }, 100);
                    }
                });
            });
        }

        <?php if ($_SESSION['failed_attempts'] >= 6): ?>
            // Auto logout after 2 seconds for permanently locked accounts
            setTimeout(function() {
                window.location.href = 'logout.php';
            }, 2000);
        <?php endif; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>