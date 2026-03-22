<?php
session_start();
include 'session_manager.php';
include 'connection.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['user_role'];

// Role-based access control - Only allow auditor role to access this dashboard
$allowed_roles = ['auditor']; // Auditor lang ang allowed
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
        header("Location: dashboard_auditor.php");
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

    // Get previous month data for comparison
    $prev_month = date('Y-m', strtotime($selected_year . '-' . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . ' -1 month'));
    
    $prev_month_revenue_sql = "SELECT SUM(credit_amount) as total FROM general_ledger WHERE account_type = 'Revenue' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$prev_month'";
    $prev_month_revenue_result = $conn->query($prev_month_revenue_sql);
    $prev_month_revenue = $prev_month_revenue_result->fetch_assoc()['total'] ?? 0;

    $prev_month_expenses_sql = "SELECT SUM(debit_amount) as total FROM general_ledger WHERE account_type = 'Expense' AND DATE_FORMAT(transaction_date, '%Y-%m') = '$prev_month'";
    $prev_month_expenses_result = $conn->query($prev_month_expenses_sql);
    $prev_month_expenses = $prev_month_expenses_result->fetch_assoc()['total'] ?? 0;

    // Calculate percentage changes
    $revenue_change = $prev_month_revenue > 0 ? (($total_revenue - $prev_month_revenue) / $prev_month_revenue) * 100 : ($total_revenue > 0 ? 100 : 0);
    $expenses_change = $prev_month_expenses > 0 ? (($total_expenses - $prev_month_expenses) / $prev_month_expenses) * 100 : ($total_expenses > 0 ? 100 : 0);

    // Get monthly data for charts - based on selected period and previous months (LIMIT to 5)
    $monthly_data_sql = "
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN account_type = 'Revenue' THEN credit_amount ELSE 0 END) as revenue,
            SUM(CASE WHEN account_type = 'Expense' THEN debit_amount ELSE 0 END) as expenses
        FROM general_ledger 
        WHERE DATE_FORMAT(transaction_date, '%Y-%m') <= '" . $selected_year . '-' . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "'
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 5
    ";
    $monthly_result = $conn->query($monthly_data_sql);
    $monthly_data = [];
    $monthly_labels = [];
    $monthly_revenue = [];
    $monthly_expenses = [];
    $monthly_profit = [];
    $monthly_loss = [];

    if ($monthly_result && $monthly_result->num_rows > 0) {
        while ($row = $monthly_result->fetch_assoc()) {
            $monthly_data[] = $row;
            $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
            $monthly_revenue[] = (float)$row['revenue'];
            $monthly_expenses[] = (float)$row['expenses'];
            
            $profit_loss = (float)($row['revenue'] - $row['expenses']);
            if ($profit_loss >= 0) {
                $monthly_profit[] = $profit_loss;
                $monthly_loss[] = 0;
            } else {
                $monthly_profit[] = 0;
                $monthly_loss[] = abs($profit_loss);
            }
        }
        $monthly_labels = array_reverse($monthly_labels);
        $monthly_revenue = array_reverse($monthly_revenue);
        $monthly_expenses = array_reverse($monthly_expenses);
        $monthly_profit = array_reverse($monthly_profit);
        $monthly_loss = array_reverse($monthly_loss);
    }

    // Get top expenses by category for selected period - Top 5
    $top_expenses_sql = "
        SELECT 
            gl_account_name as expense_categories,
            SUM(debit_amount) as total
        FROM general_ledger 
        WHERE account_type = 'Expense' AND $ledger_date_filter
        GROUP BY gl_account_name
        ORDER BY total DESC
        LIMIT 5
    ";
    $top_expenses_result = $conn->query($top_expenses_sql);
    $top_expenses_data = [];
    $top_expenses_labels = [];
    $top_expenses_amounts = [];

    if ($top_expenses_result && $top_expenses_result->num_rows > 0) {
        while ($row = $top_expenses_result->fetch_assoc()) {
            $top_expenses_data[] = $row;
            $top_expenses_labels[] = $row['expense_categories'] ?: 'Uncategorized';
            $top_expenses_amounts[] = (float)$row['total'];
        }
    }

    // Get net profit margin data for line chart for selected period and previous months (LIMIT to 5)
    $net_margin_sql = "
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            CASE 
                WHEN SUM(CASE WHEN account_type = 'Revenue' THEN credit_amount ELSE 0 END) > 0 
                THEN (SUM(CASE WHEN account_type = 'Revenue' THEN credit_amount ELSE 0 END) - 
                     SUM(CASE WHEN account_type = 'Expense' THEN debit_amount ELSE 0 END)) / 
                     SUM(CASE WHEN account_type = 'Revenue' THEN credit_amount ELSE 0 END) * 100
                ELSE 0 
            END as net_margin
        FROM general_ledger 
        WHERE DATE_FORMAT(transaction_date, '%Y-%m') <= '" . $selected_year . '-' . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "'
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 5
    ";
    $net_margin_result = $conn->query($net_margin_sql);
    $net_margin_data = [];
    $net_margin_labels = [];
    $net_margin_values = [];

    if ($net_margin_result && $net_margin_result->num_rows > 0) {
        while ($row = $net_margin_result->fetch_assoc()) {
            $net_margin_data[] = $row;
            $net_margin_labels[] = date('M Y', strtotime($row['month'] . '-01'));
            $net_margin_values[] = (float)$row['net_margin'];
        }
        $net_margin_labels = array_reverse($net_margin_labels);
        $net_margin_values = array_reverse($net_margin_values);
    }

    // FIXED: Get consistent target profit values and add next month projection for Profit & Loss Summary
    // First, get all available months with their revenue data
    $all_months_sql = "
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN account_type = 'Revenue' THEN credit_amount ELSE 0 END) as revenue
        FROM general_ledger 
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month ASC
    ";
    $all_months_result = $conn->query($all_months_sql);
    $all_revenue_data = [];

    if ($all_months_result && $all_months_result->num_rows > 0) {
        while ($row = $all_months_result->fetch_assoc()) {
            $all_revenue_data[$row['month']] = (float)$row['revenue'];
        }
    }

    // ADD NEXT MONTH PROJECTION FOR PROFIT & LOSS SUMMARY ONLY
    $next_month = date('Y-m', strtotime($selected_year . '-' . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . ' +1 month'));
    $next_month_label = date('M Y', strtotime($next_month . '-01'));

    // Add next month to labels for Profit & Loss Summary
    $profit_loss_labels = $monthly_labels;
    $profit_loss_labels[] = $next_month_label;

    // Calculate projected revenue for next month (5% growth from selected month)
    $projected_next_revenue = $total_revenue > 0 ? $total_revenue * 1.05 : (count($monthly_revenue) > 0 ? $monthly_revenue[count($monthly_revenue)-1] * 1.05 : 0);
    $profit_loss_revenue = $monthly_revenue;
    $profit_loss_revenue[] = $projected_next_revenue;

    // For expenses, use the same as current month (or calculate based on trend)
    $profit_loss_expenses = $monthly_expenses;
    $profit_loss_expenses[] = $total_expenses > 0 ? $total_expenses : (count($monthly_expenses) > 0 ? $monthly_expenses[count($monthly_expenses)-1] : 0);

    // Add zero values for profit/loss for the future month (since we don't have actual data)
    $profit_loss_profit = $monthly_profit;
    $profit_loss_loss = $monthly_loss;
    $profit_loss_profit[] = 0;
    $profit_loss_loss[] = 0;

    // FIXED: Calculate target profit based on a fixed percentage of revenue (20%)
    $target_profit_values = [];
    foreach ($profit_loss_labels as $index => $label) {
        // Extract the YYYY-MM format from the label
        $month_key = date('Y-m', strtotime($label));
        
        if (isset($all_revenue_data[$month_key])) {
            // Use 20% of the actual revenue for that month
            $target_profit_values[] = $all_revenue_data[$month_key] * 0.20;
        } else if (isset($profit_loss_revenue[$index]) && $profit_loss_revenue[$index] > 0) {
            // Use 20% of the displayed/projected revenue
            $target_profit_values[] = $profit_loss_revenue[$index] * 0.20;
        } else {
            // Fallback - use average of existing revenue data
            $average_revenue = count($profit_loss_revenue) > 0 ? array_sum($profit_loss_revenue) / count($profit_loss_revenue) : 0;
            $target_profit_values[] = $average_revenue * 0.20;
        }
    }

    // Make sure all arrays have the same length
    $profit_loss_profit = array_slice($profit_loss_profit, 0, count($profit_loss_labels));
    $profit_loss_loss = array_slice($profit_loss_loss, 0, count($profit_loss_labels));
}
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <title><?php echo $pin_verified ? 'Dashboard - Auditor' : 'PIN Verification'; ?></title>
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

        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
            }
            to { 
                opacity: 1; 
            }
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
                        <span class="text-blue-600">Auditor Dashboard</span>
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

            <!-- Profit & Loss and Net Margin Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Profit & Loss Summary Bar Graph -->
                <div class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Profit & Loss Summary</h3>
                    <div class="relative h-80 w-full">
                        <canvas id="profitLossChart"></canvas>
                    </div>
                </div>

                <!-- Net Profit Margin Line Chart -->
                <div class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Net Profit Margin Trend</h3>
                    <div class="relative h-80 w-full">
                        <canvas id="netMarginChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Expenses Section -->
            <div class="grid grid-cols-1 gap-6 mb-10">
                <!-- Top Expenses -->
                <div class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Expenses by Category</h3>
                    <div class="space-y-3" id="topExpensesList">
                        <?php if (count($top_expenses_data) > 0): ?>
                            <?php foreach ($top_expenses_data as $index => $expense): ?>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium"><?php echo $expense['expense_categories'] ?: 'Uncategorized'; ?></span>
                                    <span class="text-sm font-semibold">₱ <?php echo number_format($expense['total'], 2); ?></span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full bg-purple-600" style="width: <?php echo min(($expense['total'] / max($top_expenses_amounts)) * 100, 100); ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-4">No expense data found for <?php echo $period_text; ?></p>
                        <?php endif; ?>
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
                        <h1 class="text-2xl font-bold text-gray-900">Auditor Dashboard</h1>
                        <p class="text-sm text-gray-600 mt-1">Showing data for: <span class="font-semibold text-purple-600">Current Period</span></p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-500">
                            <a href="dashboard.php?page=dashboard" class="text-gray-900 hover:text-blue-800">Home</a>
                            <span class="mx-2">/</span>
                            <span class="text-blue-600">Auditor Dashboard</span>
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

                <!-- Blurred Financial Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 px-6 opacity-50">
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

                <!-- Blurred Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 px-6 opacity-50">
                    <!-- Profit & Loss Summary Bar Graph -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Profit & Loss Summary</h3>
                        <div class="relative h-80 w-full bg-gray-200 rounded-lg"></div>
                    </div>

                    <!-- Net Profit Margin Line Chart -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 hover:shadow-md transition-shadow border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Net Profit Margin Trend</h3>
                        <div class="relative h-80 w-full bg-gray-200 rounded-lg"></div>
                    </div>
                </div>

                <!-- Blurred Top Expenses Section -->
                <div class="grid grid-cols-1 gap-6 mb-10 px-6 opacity-50">
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Top 5 Expenses by Category</h3>
                        <div class="space-y-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-400">Category <?php echo $i; ?></span>
                                    <span class="text-sm font-semibold text-gray-400">₱ •••,•••.••</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
                                    <div class="h-full bg-gray-300" style="width: <?php echo rand(20, 90); ?>%"></div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PIN OVERLAY - SCROLLABLE -->
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
                            // Disable inputs if account is locked or in countdown
                            if ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) {
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
                            class="<?php echo ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="color: #8b5cf6; font-weight: 500; display: flex; align-items: center; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in countdown
                            if ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) {
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
                            class="<?php echo ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="flex: 1; background: #6b7280; color: white; font-weight: 500; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in countdown
                            if ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) {
                                echo 'disabled';
                            }
                            ?>
                        >
                            Clear
                        </button>
                        <button
                            type="submit"
                            id="verify-btn"
                            class="<?php echo ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) ? 'btn-disabled' : ''; ?>"
                            style="flex: 1; background: #8b5cf6; color: white; font-weight: 500; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem;"
                            <?php 
                            // Disable button if account is locked or in countdown
                            if ($_SESSION['failed_attempts'] >= 6 || $_SESSION['countdown_active']) {
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

                <?php if ($_SESSION['countdown_active']): ?>
                    <div class="text-center mt-4">
                        <p class="text-sm text-gray-600">Auto-logout in <span id="countdown">60</span> seconds</p>
                    </div>
                <?php endif; ?>
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
            if (!filterDropdown.contains(e.target) && !filterToggle.contains(e.target)) {
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
            
            let url = `dashboard_auditor.php?filter_type=${type}&year=${year}`;
            if (type === 'month') {
                url += `&month=${month}`;
            }
            
            window.location.href = url;
        });

        // Initialize charts
        // Profit & Loss Chart
        const profitLossCtx = document.getElementById('profitLossChart').getContext('2d');
        const profitLossChart = new Chart(profitLossCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($profit_loss_labels); ?>,
                datasets: [
                    {
                        label: 'Target Profit',
                        data: <?php echo json_encode($target_profit_values); ?>,
                        type: 'line',
                        borderColor: '#FF5733',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#FF5733',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    },
                    {
                        label: 'Profit',
                        data: <?php echo json_encode($profit_loss_profit); ?>,
                        backgroundColor: '#3498db',
                        borderColor: '#3498db',
                        borderWidth: 1
                    },
                    {
                        label: 'Loss',
                        data: <?php echo json_encode($profit_loss_loss); ?>,
                        backgroundColor: '#e74c3c',
                        borderColor: '#e74c3c',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'PHP'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Net Profit Margin Chart
        const netMarginCtx = document.getElementById('netMarginChart').getContext('2d');
        const netMarginChart = new Chart(netMarginCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($net_margin_labels); ?>,
                datasets: [{
                    label: 'Net Profit Margin (%)',
                    data: <?php echo json_encode($net_margin_values); ?>,
                    borderColor: '#9b59b6',
                    backgroundColor: 'rgba(155, 89, 182, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: '#9b59b6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
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
        const countdownElement = document.getElementById('countdown');
        
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
<?php
$conn->close();
?>