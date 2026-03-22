<?php
// request_portal.php - WITH VIEW ACTION
error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include connection
@include('connection.php');

if (!$conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => 'Database connection failed.']);
        exit;
    }
    die('Database connection failed.');
}

$requestor_name = $_SESSION['givenname'] . ' ' . $_SESSION['surname'];

$departments = ['Administrative', 'Core-1', 'Core-2', 'Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Logistic-1', 'Logistic-2', 'Financials'];

// Fetch Expense Accounts for Breakdown with real Hierarchy
$expense_accounts = [];
// Step 1: Get Categories (Level 2) under Expenses (Type/Level 1)
$hier_sql = "
    SELECT 
        child.id as gl_id,
        child.code as gl_code, 
        child.name as gl_name, 
        sub.name as subcategory_name, 
        cat.name as category_name
    FROM chart_of_accounts_hierarchy child
    JOIN chart_of_accounts_hierarchy sub ON child.parent_id = sub.id
    JOIN chart_of_accounts_hierarchy cat ON sub.parent_id = cat.id
    WHERE child.level = 4 
    AND child.status = 'active'
    AND EXISTS (
        SELECT 1 FROM chart_of_accounts_hierarchy p1 
        WHERE p1.id = cat.parent_id AND p1.level = 1 AND p1.type = 'Expense'
    )
    ORDER BY cat.name, sub.name, child.code ASC
";

$coa_result = $conn->query($hier_sql);
if ($coa_result) {
    while ($row = $coa_result->fetch_assoc()) {
        $expense_accounts[] = [
            'code' => $row['gl_code'],
            'name' => $row['gl_name'],
            'category' => $row['category_name'],
            'subcategory' => $row['subcategory_name']
        ];
    }
}

// Deprecated: tnvs_categories is now dynamically derived from expense_accounts in JS
$tnvs_categories = []; 

// Function to generate unique reference ID
function generateUniqueReferenceID($conn) {
    do {
        $prefix = 'BR-';
        $date = date('Ymd');
        $rand = mt_rand(1000, 9999);
        $reference_id = $prefix . $date . '-' . $rand;
        
        // Check if this reference_id already exists
        $check_sql = "SELECT COUNT(*) as count FROM budget_request WHERE reference_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $reference_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        $check_stmt->close();
        
        if ($check_row['count'] == 0) {
            return $reference_id;
        }
        // If it exists, generate another one
    } while (true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'])) {
    ob_start();
    
    $type = $_POST['request_type'];
    
    if ($type != 'budget') {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success'=>false, 'msg'=>'Invalid request type.']);
        exit;
    }
    
    try {
        // Get form data - generate NEW reference ID to ensure uniqueness
        $reference_id = generateUniqueReferenceID($conn);
        $account_name = $requestor_name; // From session
        $department = $_POST['department'] ?? '';
        
        // Use proposal_title for description
        $title = $_POST['proposal_title'] ?? '';
        $purpose = $_POST['project_objectives'] ?? '';
        $description = $title; // Description is just the title
        
        $category = $_POST['category'] ?? '';
        $sub_category = $_POST['sub_category'] ?? '';
        if (!empty($sub_category)) {
            $category .= " (" . $sub_category . ")";
        }
        
        $mode_of_payment = $_POST['mode_of_payment'] ?? 'Cash';
        $amount = $_POST['total_budget'] ?? 0;
        $payment_due = $_POST['start_date'] ?? ''; // Map start date to payment due
        $time_period = $_POST['time_period'] ?? 'weekly';
        
        // Initialize payment fields
        $bank_name = $bank_account_name = $bank_account_number = '';
        $ecash_provider = $ecash_account_name = $ecash_account_number = ''; 
        
        // Handle payment mode display
        $mode_of_payment_display = $mode_of_payment;
        if ($mode_of_payment === 'bank') $mode_of_payment_display = 'Bank Transfer';
        if ($mode_of_payment === 'ecash') $mode_of_payment_display = 'Ecash';
        
        // File upload handling
        $document = '';
        $uploadDir = 'uploads/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Handle file uploads
        if (!empty($_FILES['evidence_document']['name']) && $_FILES['evidence_document']['error'] == UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['evidence_document']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            
            if (in_array($fileExt, $allowedTypes)) {
                $safeName = 'doc_' . time() . '_' . preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
                $targetPath = $uploadDir . $safeName;
                
                if (move_uploaded_file($_FILES['evidence_document']['tmp_name'], $targetPath)) {
                    $document = $safeName;
                }
            }
        }
        
        // Process Cost Breakdown
        $breakdown_data = [];
        if (isset($_POST['breakdown_account']) && is_array($_POST['breakdown_account'])) {
            foreach ($_POST['breakdown_account'] as $index => $coa_code) {
                if (!empty($coa_code)) {
                    $breakdown_data[] = [
                        'account_code' => $coa_code,
                        'name' => $_POST['breakdown_name'][$index] ?? '',
                        'category' => $_POST['breakdown_category'][$index] ?? '',
                        'subcategory' => $_POST['breakdown_subcategory'][$index] ?? '',
                        'amount' => floatval($_POST['breakdown_amount'][$index] ?? 0)
                    ];
                }
            }
        }
        $detailed_breakdown_json = json_encode($breakdown_data);

        // Validate required fields
        $missing_fields = [];
        
        if (empty($reference_id)) $missing_fields[] = 'Reference ID';
        if (empty($department)) $missing_fields[] = 'Department';
        if (empty($category)) $missing_fields[] = 'Category';
        if (empty($description)) $missing_fields[] = 'Proposal Title';
        if (empty($amount) || $amount <= 0) $missing_fields[] = 'Amount';
        if (empty($payment_due)) $missing_fields[] = 'Start Date';
        if (empty($breakdown_data)) $missing_fields[] = 'Cost Breakdown (at least one item)';
        
        if (!empty($missing_fields)) {
            throw new Exception('Please fill all required fields: ' . implode(', ', $missing_fields));
        }
        
        // Convert amount to float
        $amount = floatval($amount);
        
        // Updated SQL to include detailed_breakdown
        $sql = "INSERT INTO budget_request (
            reference_id, account_name, requested_department, mode_of_payment,
            expense_categories, amount, description, detailed_breakdown, document, time_period,
            payment_due, bank_name, bank_account_name, bank_account_number,
            ecash_provider, ecash_account_name, ecash_account_number,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare SQL statement: ' . $conn->error);
        }
        
        // Bind parameters (17 parameters now)
        $stmt->bind_param(
            "sssssdsssssssssss",
            $reference_id,
            $account_name,
            $department,
            $mode_of_payment_display,
            $category,
            $amount,
            $description,
            $detailed_breakdown_json,
            $document,
            $time_period,
            $payment_due, 
            $bank_name,
            $bank_account_name,
            $bank_account_number,
            $ecash_provider, 
            $ecash_account_name, 
            $ecash_account_number 
        );
        
        if ($stmt->execute()) {
            $insert_id = $stmt->insert_id;
            // ====================================================================
            // NEW CODE: Insert into budget_proposals for Budget Planning
            // ====================================================================
            try {
                // Generate proposal code
                $proposal_code = 'PROP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                // Get dates
                $start_date = date('Y-m-d', strtotime($_POST['start_date'] ?? 'now'));
                $end_date = date('Y-m-d', strtotime($_POST['end_date'] ?? '+30 days'));
                $duration_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
                
                // Get fiscal year
                $fiscal_year = date('Y', strtotime($start_date));
                
                // Prepare supporting documents
                $supporting_docs = [];
                if (!empty($document)) {
                    $supporting_docs[] = $document;
                }
                $supporting_docs_json = json_encode($supporting_docs);
                
                // Get user info from session
                $created_by = $_SESSION['user_id'] ?? null;
                $created_by_name = $requestor_name;
                
                // Get category and subcategory
                $proposal_category = $_POST['category'] ?? ($breakdown_data[0]['category'] ?? 'General');
                $proposal_subcategory = $_POST['sub_category'] ?? ($breakdown_data[0]['subcategory'] ?? '');
                
                // Insert into budget_proposals
                $proposal_sql = "INSERT INTO budget_proposals (
                    proposal_code,
                    proposal_title,
                    project_objectives,
                    department,
                    category,
                    sub_category,
                    fiscal_year,
                    total_budget,
                    start_date,
                    end_date,
                    duration_days,
                    supporting_docs,
                    detailed_breakdown,
                    status,
                    submitted_by,
                    submitted_at,
                    created_at,
                    updated_at,
                    reference_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_review', ?, NOW(), NOW(), NOW(), ?)";
                
                $proposal_stmt = $conn->prepare($proposal_sql);
                
                if ($proposal_stmt) {
                    $proposal_stmt->bind_param(
                        "ssssssidssissss",
                        $proposal_code,
                        $title,
                        $purpose,
                        $department,
                        $proposal_category,
                        $proposal_subcategory,
                        $fiscal_year,
                        $amount,
                        $start_date,
                        $end_date,
                        $duration_days,
                        $supporting_docs_json,
                        $detailed_breakdown_json,
                        $created_by_name,
                        $reference_id
                    );
                    
                    if (!$proposal_stmt->execute()) {
                        error_log("Budget proposal INSERT failed: " . $proposal_stmt->error);
                        error_log("Purpose value: " . $purpose);
                    } else {
                        error_log("Budget proposal created successfully. Purpose: " . $purpose);
                    }
                    $proposal_id = $conn->insert_id;
                    $proposal_stmt->close();
                }
            } catch (Exception $proposal_error) {
                // Log error but don't fail the request
                error_log("Budget proposal creation failed: " . $proposal_error->getMessage());
            }
            // ====================================================================
            // END NEW CODE
            // ====================================================================
            
            // Insert notification if table exists
            $check_notif = $conn->query("SHOW TABLES LIKE 'requestor_notif'");
            if ($check_notif && $check_notif->num_rows > 0) {
                $notifMsg = "Your budget request has been submitted. Reference ID: $reference_id";
                $notifStmt = $conn->prepare("INSERT INTO requestor_notif (message, rejection_reason) VALUES (?, '')");
                if ($notifStmt) {
                    $notifStmt->bind_param("s", $notifMsg);
                    $notifStmt->execute();
                    $notifStmt->close();
                }
            }
            
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'msg' => 'Budget request submitted successfully! Reference ID: ' . $reference_id,
                'reference_id' => $reference_id,
                'insert_id' => $insert_id,
                'debug_purpose' => $purpose // Debug: show what purpose was received
            ]);
            
        } else {
            throw new Exception('Failed to execute SQL: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'msg' => 'Error: ' . $e->getMessage()
        ]);
    }
    
    if (isset($conn) && $conn) {
        $conn->close();
    }
    exit;
}

// For regular page load, just continue with HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request Portal</title>
    <meta charset="UTF-8">
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        /* All CSS styles remain the same */
        .tab-active { border-bottom: 4px solid #7c3aed; color: #7c3aed !important; background: #ede9fe; }
        .tab-btn:not(.tab-active):hover { background: #f3f4f6; }
        .fade-in { animation: fadein 0.3s ease-in-out; }
        @keyframes fadein { from { opacity:0; transform: translateY(-20px);} to { opacity: 1; transform: none; } }
        .modal-backdrop { background-color: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px); }
        .modal-content { max-height: 90vh; overflow-y: auto; transition: all 0.3s ease-in-out; }
        .required-field::after { content: " *"; color: #ef4444; }
        
        .form-input { width: 100%; padding: 0.625rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; transition: all 0.2s; background: white; }
        .form-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1); }
        .form-input.readonly { background-color: #f9fafb; color: #6b7280; border-color: #e5e7eb; cursor: not-allowed; }
        
        .payment-fields { background: #f8fafc; border-radius: 12px; padding: 1.25rem; margin-top: 1rem; border: 1px solid #e2e8f0; }
        .payment-fields.hidden { display: none; }
        .error-field { border-color: #ef4444 !important; box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important; }
        
        /* New styles for view modal */
        .view-modal { width: 700px; max-width: 95vw; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 0.75rem; color: #6b7280; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
        .info-value { font-size: 0.875rem; color: #374151; font-weight: 500; padding: 0.5rem 0; }
        .info-value.large { font-size: 1rem; }
        .info-value.amount { color: #059669; font-weight: 600; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #991b1b; }
        .document-link { color: #3b82f6; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem; }
        .document-link:hover { text-decoration: underline; }
        .section-divider { height: 1px; background-color: #e5e7eb; margin: 1.5rem 0; }
    </style>
</head>
<body class="bg-gray-50">
<?php include('sidebar.php'); ?>
<div class="flex h-screen">
    <div class="flex flex-1">
        <main class="flex-1 px-8 py-6 overflow-y-auto">
            <!-- Breadcrumb -->
            <div class="flex justify-between items-center px-6 py-6 font-poppins">
                <h1 class="text-2xl font-poppins text-gray-800">Request Portal</h1>
                <div class="text-sm text-gray-600">
                    <a href="dashboard.php?page=dashboard" class="text-gray-600 hover:text-blue-600 transition duration-200">Home</a>
                    /
                    <a href="user_management.php" class="text-blue-600 hover:text-blue-700 transition duration-200">Request Portal</a>
                </div>
            </div>
            
            
            
            <!-- Add New Request Card -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold mb-1">Request Budget</h2>
                        <p class="text-blue-100">Fill out the form below to request budget</p>
                    </div>
                    <button id="addRequestBtn" class="bg-white text-purple-600 px-6 py-2 rounded-lg font-medium hover:bg-gray-50 transition-colors flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Request
                    </button>
                </div>
            </div>
            
            <!-- Recent Requests Section -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Requests</h3>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <input id="searchBox" type="text" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Search requests...">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <div class="flex border border-gray-300 rounded-lg overflow-hidden">
                            <button id="recentTab" class="px-4 py-2 text-sm font-medium bg-blue-50 text-blue-600">Recent</button>
                            <button id="historyTab" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">History</button>
                        </div>
                    </div>
                </div>
                <div id="requestsTableContainer" class="fade-in">
                    <!-- Table will be loaded here -->
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modern Form Modal (Budget Proposal Style) -->
<div id="requestFormModal" class="fixed inset-0 z-50 hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0 modal-overlay" onclick="closeForm()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative z-10">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-center -mx-6 -mt-6 mb-6 p-6 rounded-t-lg bg-gradient-to-r from-purple-700 to-indigo-800 text-white sticky top-0 z-20">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-plus text-xl text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold" id="formTitle">New Budget Proposal</h3>
                            <p class="text-indigo-100 text-xs opacity-75">Enter essential details for your budget request</p>
                        </div>
                    </div>
                    <button onclick="closeForm()" class="text-white hover:text-indigo-200 transition-all bg-white bg-opacity-10 hover:bg-opacity-20 p-2 rounded-lg">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <form id="dynamicRequestForm" autocomplete="off">
                    <!-- Hidden inputs for backend compatibility -->
                    <input type="hidden" name="request_type" id="request_type" value="budget">
                    <input type="hidden" name="time_period" id="time_period" value="weekly">
                    <input type="hidden" name="mode_of_payment" id="mode_of_payment" value="Cash"> <!-- Default fallback -->

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Left Column -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proposal Title <span class="text-red-500">*</span></label>
                                <input type="text" name="proposal_title" id="description" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all" placeholder="e.g., 2025 Q1 Marketing Budget">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Departmental Source <span class="text-red-500">*</span></label>
                                <select name="department" id="department" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                    <option value="">Select a Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept ?>"><?= $dept ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget Start Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="start_date" id="target_release_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Budget End Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="end_date" id="end_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Justification / Purpose <span class="text-red-500">*</span></label>
                                <textarea name="project_objectives" id="purpose" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all resize-none" placeholder="Explain why this budget is needed..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Requested Total Amount Section -->
                    <div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-6 flex flex-col md:flex-row justify-between items-center mb-8">
                        <div class="mb-4 md:mb-0">
                            <h4 class="text-gray-700 font-bold mb-1">Requested Total Amount (₱)</h4>
                            <p class="text-[10px] text-gray-500 italic">This should closely match the sum of all GL account amounts below.</p>
                        </div>
                        <div class="relative w-full md:w-64">
                            <input type="number" name="total_budget" id="amount" required step="0.01" min="0" class="w-full px-4 py-3 border border-blue-200 bg-white rounded-lg font-bold text-gray-800 text-xl text-right outline-none focus:ring-2 focus:ring-blue-500" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Charts of Account Breakdown Section -->
                    <div class="mt-6 bg-gray-50/50 border border-gray-200 rounded-2xl overflow-hidden mb-8">
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800">Charts of Account Breakdown</h4>
                                    <p class="text-[11px] text-gray-500 mt-1">Search major groups, pick minor groups, then select GL accounts to include in this proposal.</p>
                                </div>
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-full">Step 2 - COA Selection</span>
                            </div>

                            <div class="space-y-5 mb-8">
                                <!-- Categories -->
                                <div class="relative">
                                    <label class="block text-xs font-black text-gray-600 uppercase tracking-widest mb-2">Account Categories</label>
                                    <input type="text" name="category" id="categorySearch" oninput="filterCategories()" onclick="filterCategories()" class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none bg-white transition-all shadow-sm cursor-text" placeholder="Search categories... (e.g., Vehicle Operations)" autocomplete="off">
                                    <div id="categoryResults" class="hidden absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-[60] max-h-60 overflow-y-auto divide-y divide-gray-50"></div>
                                </div>

                                <!-- Subcategories -->
                                <div class="relative">
                                    <label class="block text-xs font-black text-gray-600 uppercase tracking-widest mb-2">Account Subcategories</label>
                                    <select name="sub_category" id="subcategorySelect" onchange="filterGLAccounts()" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm">
                                        <option value="">Select a Category first to see Subcategories.</option>
                                    </select>
                                </div>

                                <!-- GL Accounts -->
                                <div class="relative">
                                    <label class="block text-xs font-black text-gray-600 uppercase tracking-widest mb-2">GL Accounts</label>
                                    <select id="glSelect" onchange="addAccountToList()" disabled class="w-full px-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm">
                                        <option value="">Select a Subcategory first to see GL accounts.</option>
                                    </select>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 border-dashed pt-8">
                                <h5 class="text-xs font-black text-gray-600 uppercase tracking-widest mb-4">Selected GL Accounts & Amount</h5>
                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <table class="w-full text-left border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-gray-50 text-gray-500 font-bold uppercase tracking-tight">
                                                <th class="px-4 py-4 border-b border-gray-200">GL CODE</th>
                                                <th class="px-4 py-4 border-b border-gray-200">NAME</th>
                                                <th class="px-4 py-4 border-b border-gray-200">CATEGORY</th>
                                                <th class="px-4 py-4 border-b border-gray-200">SUBCATEGORY</th>
                                                <th class="px-4 py-4 border-b border-gray-200 text-right">ASSIGNED COST</th>
                                                <th class="px-4 py-4 border-b border-gray-200 w-12 text-center"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="breakdownBody" class="divide-y divide-gray-100">
                                            <!-- Selected accounts will be added here -->
                                            <tr id="emptyBreakdownRow">
                                                <td colspan="6" class="px-4 py-10 text-center text-gray-400 italic font-medium">No GL accounts selected yet.</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-gray-50 font-black text-gray-900 border-t-2 border-gray-100">
                                                <td colspan="4" class="px-4 py-4 text-right text-sm">Total Amount:</td>
                                                <td class="px-4 py-4 text-right text-base text-blue-700" id="breakdownTotalDisplay">₱ 0.00</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Supporting Documents -->
                    <div class="mt-8">
                        <label class="block text-xs font-black text-gray-600 uppercase tracking-widest mb-3">Attach Full Justification Document (Required)</label>
                        <div class="border-2 border-dashed border-gray-200 rounded-xl p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50/50 transition-all group" id="evidenceUploadArea">
                            <input type="file" name="evidence_document" id="evidence_document" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                            <div class="w-14 h-14 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500 group-hover:scale-110 transition-transform bg-opacity-80">
                                <i class="fas fa-cloud-upload-alt text-2xl"></i>
                            </div>
                            <p id="evidenceUploadText" class="text-sm font-bold text-gray-500 group-hover:text-blue-700">Click to upload quotes or estimates</p>
                            <div id="evidenceFileName" class="hidden mt-4 p-3 bg-green-50 rounded-lg border border-green-200 flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-500 mr-2 text-lg"></i>
                                <span id="evidenceFileNameText" class="text-sm font-bold text-green-700 truncate max-w-[250px]"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Action Buttons -->
                    <div class="flex justify-end gap-3 mt-10 pt-8 border-t border-gray-100">
                        <button type="button" onclick="closeForm()" class="px-6 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-100 transition-all font-bold text-gray-700 text-xs shadow-sm flex items-center">
                            <i class="fas fa-save mr-2"></i> Save Draft
                        </button>
                        <button type="button" id="submitProposalBtn" onclick="submitForm()" class="px-8 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-700 text-white rounded-lg hover:from-blue-700 hover:to-indigo-800 transition-all font-bold text-xs shadow-lg flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Submit Proposal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Request Details Modal -->
<div id="viewRequestModal" class="fixed inset-0 z-50 hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0" onclick="closeViewModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-[95vw] max-h-[95vh] overflow-hidden flex flex-col relative z-30">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 rounded-t-xl bg-gradient-to-r from-indigo-700 to-purple-800 text-white shrink-0 shadow-lg">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center mr-4 backdrop-blur-sm border border-white border-opacity-30">
                        <i class="fas fa-search-dollar text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold">Request Details</h3>
                        <p class="text-indigo-100 text-xs opacity-80" id="viewReferenceId">REFERENCE ID: </p>
                    </div>
                </div>
                <button onclick="closeViewModal()" class="text-white hover:text-indigo-200 transition-all bg-white bg-opacity-10 hover:bg-opacity-20 p-2.5 rounded-xl">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-8 bg-gray-50/50">
                
                <div class="grid grid-cols-12 gap-8 mb-4">
                    <!-- Left Column: Request Information -->
                    <div class="col-span-12 lg:col-span-4 space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                <span class="w-8 h-px bg-gray-200 mr-3"></span> Basic Information
                            </h4>
                            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="info-item">
                                        <span class="info-label">Requestor</span>
                                        <span class="info-value font-bold text-gray-800" id="viewAccountName"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Department</span>
                                        <span class="info-value font-bold text-gray-700" id="viewDepartment"></span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 pt-3">
                                    <div class="info-item">
                                        <span class="info-label">Main Category</span>
                                        <span class="info-value font-bold text-gray-700" id="viewCategory"></span>
                                    </div>
                                </div>
                                <div class="border-t border-gray-100 pt-3">
                                    <span class="info-label">Description</span>
                                    <span class="info-value block mt-1 text-gray-700" id="viewDescription"></span>
                                </div>
                                <div class="border-t border-gray-100 pt-3">
                                    <span class="info-label">Purpose</span>
                                    <span class="info-value block mt-1 text-gray-700 italic" id="viewPurpose"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Request Status -->
                        <div>
                            <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                <span class="w-8 h-px bg-gray-200 mr-3"></span> Request Status
                            </h4>
                            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm flex items-center justify-between">
                                <div>
                                    <span class="info-label block mb-1">Current Status</span>
                                    <span id="viewStatusBadge" class="status-badge"></span>
                                </div>
                                <div class="text-right">
                                    <span class="info-label block mb-1">Created Date</span>
                                    <span class="text-sm font-bold text-gray-600" id="viewCreatedAt"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div>
                            <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                <span class="w-8 h-px bg-gray-200 mr-3"></span> Payment Details
                            </h4>
                            <div class="bg-indigo-50/50 rounded-xl p-5 border border-indigo-100 shadow-sm space-y-4">
                                <div class="flex justify-between items-center">
                                    <div class="info-item">
                                        <span class="info-label">Mode of Payment</span>
                                        <span class="info-value font-bold text-indigo-700" id="viewPaymentMode"></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="info-label">Amount</span>
                                        <span class="text-2xl font-black text-indigo-800 block" id="viewAmount"></span>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 border-t border-indigo-100 pt-3">
                                    <div class="info-item">
                                        <span class="info-label">Release Date</span>
                                        <span class="info-value font-bold text-gray-700" id="viewReleaseDate"></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">Time Period</span>
                                        <span class="info-value font-bold text-gray-700" id="viewTimePeriod"></span>
                                    </div>
                                </div>

                                <!-- Dynamic Payment Info -->
                                <div id="viewBankDetails" class="hidden border-t border-indigo-100 pt-3 bg-white bg-opacity-80 p-3 rounded-lg mt-2 shadow-sm">
                                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-tighter mb-2">Bank Account Details</p>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="info-item">
                                            <span class="info-label">Bank</span>
                                            <span class="text-xs font-bold" id="viewBankName"></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Number</span>
                                            <span class="text-xs font-bold" id="viewBankAccountNumber"></span>
                                        </div>
                                    </div>
                                    <div class="info-item mt-2">
                                        <span class="info-label">Name</span>
                                        <span class="text-xs font-bold" id="viewBankAccountName"></span>
                                    </div>
                                </div>

                                <div id="viewEcashDetails" class="hidden border-t border-indigo-100 pt-3 bg-white bg-opacity-80 p-3 rounded-lg mt-2 shadow-sm">
                                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-tighter mb-2">eCash Details</p>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="info-item">
                                            <span class="info-label">Provider</span>
                                            <span class="text-xs font-bold" id="viewEcashProvider"></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Number</span>
                                            <span class="text-xs font-bold" id="viewEcashAccountNumber"></span>
                                        </div>
                                    </div>
                                    <div class="info-item mt-2">
                                        <span class="info-label">Name</span>
                                        <span class="text-xs font-bold" id="viewEcashAccountName"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Evidence Details (Text) -->
                        <div>
                             <h4 class="text-sm font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                <span class="w-8 h-px bg-gray-200 mr-3"></span> Documentation
                            </h4>
                            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                                <div class="info-item">
                                    <span class="info-label pb-2 border-b border-gray-50 mb-2 block">Evidence/Justification</span>
                                    <span class="text-sm text-gray-600 block mt-1" id="viewEvidence"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Document Viewer -->
                    <div class="col-span-12 lg:col-span-8 space-y-4">
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-full flex flex-col">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-file-pdf text-indigo-600 mr-2"></i>
                                Attached Document
                            </h3>
                            <div id="documentViewerContainer" class="flex-1 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden min-h-[700px] relative">
                                <div class="text-center p-8">
                                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-3 block"></i>
                                    <p class="text-gray-500">No document preview available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown Display Section -->
                <div id="viewBreakdownSection" class="mt-8 pt-8 border-t border-gray-100 hidden">
                    <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6 flex items-center">
                        <i class="fas fa-list-ul text-blue-500 mr-2"></i> Charts of Account Breakdown History
                    </h4>
                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 font-bold uppercase tracking-tight">
                                    <th class="px-4 py-4 border-b border-gray-200 uppercase">GL CODE</th>
                                    <th class="px-4 py-4 border-b border-gray-200 uppercase">NAME</th>
                                    <th class="px-4 py-4 border-b border-gray-200 uppercase">CATEGORY</th>
                                    <th class="px-4 py-4 border-b border-gray-200 uppercase">SUBCATEGORY</th>
                                    <th class="px-4 py-4 border-b border-gray-200 text-right uppercase">ASSIGNED COST</th>
                                </tr>
                            </thead>
                            <tbody id="viewBreakdownBody" class="divide-y divide-gray-50">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t border-gray-100">
                    <button type="button" class="px-8 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition-all" onclick="closeViewModal()">Close View</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage"></span>
</div>

<script>
    // Generate Reference ID for display only (server will generate the final one)
    function generateReferenceID(type) {
        const prefix = type === 'budget' ? 'BR-' : 'PB-';
        const date = new Date().toISOString().slice(0,10).replace(/-/g,'');
        const rand = Math.floor(1000 + Math.random() * 9000);
        return `${prefix}${date}-${rand}`;
    }

    const requestorName = <?= json_encode($requestor_name) ?>;
    let selectedType = 'budget';
    const tnvsCategories = <?= json_encode($tnvs_categories) ?>;
    const expenseAccounts = <?= json_encode($expense_accounts) ?>;

    // Hierarchical COA Selection Logic (Category -> Subcategory -> GL Account)
    function filterCategories() {
        const query = document.getElementById('categorySearch').value.toLowerCase();
        const resultsDiv = document.getElementById('categoryResults');
        
        // Always get unique categories from expenseAccounts
        const categories = [...new Set(expenseAccounts.map(acc => acc.category))].filter(Boolean);
        const filtered = query.length >= 1 
            ? categories.filter(cat => cat.toLowerCase().includes(query))
            : categories;

        if (filtered.length > 0) {
            resultsDiv.innerHTML = filtered.map(cat => `
                <div onclick="selectCategory('${cat.replace(/'/g, "\\'")}')" class="px-4 py-3 hover:bg-blue-50 cursor-pointer text-sm font-semibold text-gray-700 transition-colors">
                    <i class="fas fa-folder text-blue-400 mr-2 text-xs"></i>${cat}
                </div>
            `).join('');
            resultsDiv.classList.remove('hidden');
        } else {
            resultsDiv.innerHTML = '<div class="px-4 py-3 text-xs text-gray-400 italic">No categories found</div>';
            resultsDiv.classList.remove('hidden');
        }
    }

    // Close category results on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#categorySearch') && !e.target.closest('#categoryResults')) {
            document.getElementById('categoryResults').classList.add('hidden');
        }
    });

    function selectCategory(category) {
        const searchInput = document.getElementById('categorySearch');
        searchInput.value = category;
        document.getElementById('categoryResults').classList.add('hidden');

        const subcategorySelect = document.getElementById('subcategorySelect');
        subcategorySelect.disabled = false;
        subcategorySelect.classList.remove('bg-gray-100', 'text-gray-400');
        subcategorySelect.innerHTML = '<option value="">Select a Subcategory</option>';

        // Get unique subcategories for this category
        const subcategories = [...new Set(expenseAccounts
            .filter(acc => acc.category === category)
            .map(acc => acc.subcategory))].filter(Boolean);

        subcategories.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub;
            option.textContent = sub;
            subcategorySelect.appendChild(option);
        });

        // Reset and disable GL select
        const glSelect = document.getElementById('glSelect');
        glSelect.disabled = true;
        glSelect.classList.add('bg-gray-100', 'text-gray-400');
        glSelect.innerHTML = '<option value="">Select a Subcategory first to see GL accounts.</option>';
    }

    function filterGLAccounts() {
        const subcategory = document.getElementById('subcategorySelect').value;
        const glSelect = document.getElementById('glSelect');

        if (!subcategory) {
            glSelect.disabled = true;
            glSelect.classList.add('bg-gray-100', 'text-gray-400');
            return;
        }

        glSelect.disabled = false;
        glSelect.classList.remove('bg-gray-100', 'text-gray-400');
        glSelect.innerHTML = '<option value="">Select a GL Account</option>';

        const filtered = expenseAccounts.filter(acc => acc.subcategory === subcategory);

        filtered.forEach(acc => {
            const option = document.createElement('option');
            option.value = acc.code;
            option.textContent = `${acc.code} - ${acc.name}`;
            glSelect.appendChild(option);
        });
    }

    function addAccountToList() {
        const glCode = document.getElementById('glSelect').value;
        if (!glCode) return;

        const acc = expenseAccounts.find(a => a.code == glCode);
        if (!acc) return;

        // Check if already in list
        const existingRow = document.querySelector(`tr[data-code="${glCode}"]`);
        if (existingRow) {
            showToast('This account is already in the list.', false);
            document.getElementById('glSelect').value = '';
            return;
        }

        const tbody = document.getElementById('breakdownBody');
        const emptyRow = document.getElementById('emptyBreakdownRow');
        if (emptyRow) emptyRow.remove();

        const row = document.createElement('tr');
        row.setAttribute('data-code', glCode);
        row.className = "hover:bg-blue-50/30 transition-colors group";
        row.innerHTML = `
            <td class="px-4 py-4 font-mono font-bold text-blue-600">${acc.code}</td>
            <td class="px-4 py-4 font-semibold text-gray-700">${acc.name}</td>
            <td class="px-4 py-4 text-gray-500 text-[11px] font-medium uppercase tracking-tight">${acc.category}</td>
            <td class="px-4 py-4 text-gray-500 text-[11px] font-medium uppercase tracking-tight">${acc.subcategory}</td>
            <td class="px-4 py-4">
                <div class="relative max-w-[150px] ml-auto">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-[10px]">₱</span>
                    <input type="number" name="breakdown_amount[]" required step="0.01" min="0" oninput="calculateBreakdownTotal()" class="w-full pl-6 pr-3 py-2 border border-blue-100 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-right font-black text-gray-800 bg-gray-50/50" placeholder="0.00">
                    <input type="hidden" name="breakdown_account[]" value="${acc.code}">
                    <input type="hidden" name="breakdown_name[]" value="${acc.name}">
                    <input type="hidden" name="breakdown_category[]" value="${acc.category}">
                    <input type="hidden" name="breakdown_subcategory[]" value="${acc.subcategory}">
                </div>
            </td>
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="removeAccountRow(this)" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Reset selection
        document.getElementById('glSelect').value = '';
    }

    function removeAccountRow(btn) {
        btn.closest('tr').remove();
        const tbody = document.getElementById('breakdownBody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyBreakdownRow">
                    <td colspan="6" class="px-4 py-10 text-center text-gray-400 italic font-medium">No GL accounts selected yet.</td>
                </tr>
            `;
        }
        calculateBreakdownTotal();
    }

    function calculateBreakdownTotal() {
        const amounts = document.getElementsByName('breakdown_amount[]');
        let total = 0;
        amounts.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
        });
        
        document.getElementById('breakdownTotalDisplay').textContent = '₱ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('amount').value = total.toFixed(2);
    }




    // View Request Details - UPDATED VERSION
    function viewRequest(referenceId) {
        // Show loading state
        showToast('Loading request details...', true);
        
        // Pass the selectedType parameter
        fetch(`request_portal_data.php?action=view&reference_id=${encodeURIComponent(referenceId)}&type=${encodeURIComponent(selectedType)}`)
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    populateViewModal(data.request);
                    openViewModal();
                } else {
                    showToast('Failed to load request details.', false);
                }
            })
            .catch(error => {
                console.error('Error loading request:', error);
                showToast('Error loading request details.', false);
            });
    }

    function populateViewModal(request) {
        // Basic Information
        document.getElementById('viewReferenceId').textContent = 'REFERENCE ID: ' + request.reference_id;
        document.getElementById('viewAccountName').textContent = request.account_name || 'N/A';
        document.getElementById('viewDepartment').textContent = request.requested_department || 'N/A';
        document.getElementById('viewCategory').textContent = request.expense_categories || 'N/A';
        document.getElementById('viewDescription').textContent = request.description || 'N/A';
        document.getElementById('viewPurpose').textContent = request.project_objectives || 'N/A';
        
        // Payment Information
        document.getElementById('viewPaymentMode').textContent = request.mode_of_payment || 'N/A';
        document.getElementById('viewAmount').textContent = '₱' + (parseFloat(request.amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        document.getElementById('viewReleaseDate').textContent = request.payment_due || 'N/A';
        document.getElementById('viewTimePeriod').textContent = request.time_period || 'N/A';
        
        document.getElementById('viewCreatedAt').textContent = request.created_at || 'N/A';
        
        // Hide all payment detail sections
        document.getElementById('viewBankDetails').classList.add('hidden');
        document.getElementById('viewEcashDetails').classList.add('hidden');
        
        // Show appropriate payment details
        if (request.mode_of_payment === 'Bank Transfer' && (request.bank_name || request.bank_account_number)) {
            document.getElementById('viewBankDetails').classList.remove('hidden');
            document.getElementById('viewBankName').textContent = request.bank_name || 'N/A';
            document.getElementById('viewBankAccountNumber').textContent = request.bank_account_number || 'N/A';
            document.getElementById('viewBankAccountName').textContent = request.bank_account_name || 'N/A';
        } else if (request.mode_of_payment === 'Ecash' && (request.ecash_provider || request.ecash_account_number)) {
            document.getElementById('viewEcashDetails').classList.remove('hidden');
            document.getElementById('viewEcashProvider').textContent = request.ecash_provider || 'N/A';
            document.getElementById('viewEcashAccountNumber').textContent = request.ecash_account_number || 'N/A';
            document.getElementById('viewEcashAccountName').textContent = request.ecash_account_name || 'N/A';
        }
        
        // Status badge
        const statusBadge = document.getElementById('viewStatusBadge');
        statusBadge.textContent = request.status || 'pending';
        statusBadge.className = 'status-badge ';
        switch((request.status || '').toLowerCase()) {
            case 'pending': statusBadge.classList.add('status-pending'); break;
            case 'approved': statusBadge.classList.add('status-approved'); break;
            case 'rejected': statusBadge.classList.add('status-rejected'); break;
            default: statusBadge.classList.add('status-pending');
        }

        // Populate Detailed Breakdown
        const breakdownSection = document.getElementById('viewBreakdownSection');
        const breakdownBody = document.getElementById('viewBreakdownBody');
        breakdownBody.innerHTML = '';
        
        if (request.detailed_breakdown && request.detailed_breakdown !== '[]') {
            try {
                const items = JSON.parse(request.detailed_breakdown);
                if (items && items.length > 0) {
                    breakdownSection.classList.remove('hidden');
                    items.forEach(item => {
                        const tr = document.createElement('tr');
                        tr.className = "hover:bg-gray-50 transition-colors";
                        tr.innerHTML = `
                            <td class="px-4 py-4 border-b border-gray-100 font-mono font-bold text-blue-600">${item.account_code}</td>
                            <td class="px-4 py-4 border-b border-gray-100 font-semibold text-gray-700">${item.name || item.description || 'N/A'}</td>
                            <td class="px-4 py-4 border-b border-gray-100 text-gray-500">${item.category || 'N/A'}</td>
                            <td class="px-4 py-4 border-b border-gray-100 text-gray-500">${item.subcategory || 'N/A'}</td>
                            <td class="px-4 py-4 border-b border-gray-100 text-right font-black text-gray-800">₱ ${(parseFloat(item.amount || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        `;
                        breakdownBody.appendChild(tr);
                    });
                } else {
                    breakdownSection.classList.add('hidden');
                }
            } catch (e) {
                console.error("Error parsing breakdown:", e);
                breakdownSection.classList.add('hidden');
            }
        } else {
            breakdownSection.classList.add('hidden');
        }
        
        // Document Preview Logic
        const documentContainer = document.getElementById('documentViewerContainer');
        if (request.document && request.document !== 'No document available') {
            const fileName = request.document;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const fileUrl = `uploads/${fileName}`;
            
            let documentsHtml = '';
            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                documentsHtml = `
                    <div class="flex flex-col items-center w-full h-full">
                        <div class="flex-1 flex items-center justify-center w-full overflow-auto p-4 bg-gray-200">
                            <img src="${fileUrl}" class="max-w-full rounded-lg shadow-lg border border-gray-300" alt="Document Preview">
                        </div>
                        <div class="w-full p-4 bg-gray-50 border-t flex items-center">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-widest"><i class="fas fa-image mr-2 text-indigo-500"></i> ${escapeHtml(fileName)}</span>
                        </div>
                    </div>`;
            } else if (fileExtension === 'pdf') {
                documentsHtml = `
                    <div class="flex flex-col w-full h-full">
                        <iframe src="${fileUrl}#toolbar=0" class="flex-1 w-full rounded-t-xl" style="min-height: 700px; border: none;"></iframe>
                        <div class="w-full p-4 bg-gray-50 border-t flex items-center">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-widest"><i class="fas fa-file-pdf mr-2 text-red-500"></i> ${escapeHtml(fileName)}</span>
                        </div>
                    </div>`;
            } else {
                // For other files (doc, xls, etc)
                documentsHtml = `
                    <div class="text-center p-12">
                        <div class="w-20 h-20 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                            ${getFileIcon(fileExtension)}
                        </div>
                        <h4 class="text-lg font-bold text-gray-800 mb-2">${escapeHtml(fileName)}</h4>
                        <p class="text-gray-500 text-sm mb-8">Preview not available for this file type.</p>
                        <a href="${fileUrl}" download="${fileName}" class="inline-flex items-center px-8 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-200">
                            <i class="fas fa-download mr-2"></i> Download Document
                        </a>
                    </div>`;
            }
            documentContainer.innerHTML = documentsHtml;
        } else {
            documentContainer.innerHTML = `
                <div class="text-center p-8">
                    <i class="fas fa-file-alt text-4xl text-gray-200 mb-3 block"></i>
                    <p class="text-gray-400">No document attached to this request</p>
                </div>`;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getFileIcon(extension) {
        const icons = {
            'pdf': '<i class="fas fa-file-pdf text-4xl text-red-500"></i>',
            'doc': '<i class="fas fa-file-word text-4xl text-blue-500"></i>',
            'docx': '<i class="fas fa-file-word text-4xl text-blue-500"></i>',
            'xls': '<i class="fas fa-file-excel text-4xl text-green-500"></i>',
            'xlsx': '<i class="fas fa-file-excel text-4xl text-green-500"></i>',
            'jpg': '<i class="fas fa-file-image text-4xl text-purple-500"></i>',
            'jpeg': '<i class="fas fa-file-image text-4xl text-purple-500"></i>',
            'png': '<i class="fas fa-file-image text-4xl text-purple-500"></i>',
            'gif': '<i class="fas fa-file-image text-4xl text-purple-500"></i>'
        };
        return icons[extension.toLowerCase()] || '<i class="fas fa-file text-4xl text-gray-400"></i>';
    }

    function openViewModal() {
        document.getElementById('viewRequestModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeViewModal() {
        document.getElementById('viewRequestModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Modal Functions
    const formModal = document.getElementById('requestFormModal');
    const viewModal = document.getElementById('viewRequestModal');
    
    document.getElementById('addRequestBtn').onclick = showForm;

    function showForm() {
        document.getElementById('formTitle').textContent = 'New Budget Proposal';
        document.getElementById('request_type').value = 'budget';
        
        // Reset form
        document.getElementById('dynamicRequestForm').reset();
        
        // Set today's date for start/end
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('target_release_date').value = today;
        document.getElementById('end_date').value = today;
        
        // Reset subcategories - Deprecated with new UI
        // document.getElementById('sub_category').innerHTML = '<option value="">Select Sub-category</option>';
        
        // Reset Hierarchical Selection
        document.getElementById('categorySearch').value = '';
        document.getElementById('subcategorySelect').innerHTML = '<option value="">Select a Category first to see Subcategories.</option>';
        document.getElementById('subcategorySelect').disabled = true;
        document.getElementById('glSelect').innerHTML = '<option value="">Select a Subcategory first to see GL accounts.</option>';
        document.getElementById('glSelect').disabled = true;

        // Reset Cost Breakdown Table
        document.getElementById('breakdownBody').innerHTML = `
            <tr id="emptyBreakdownRow">
                <td colspan="6" class="px-4 py-10 text-center text-gray-400 italic font-medium">No GL accounts selected yet.</td>
            </tr>
        `;
        document.getElementById('breakdownTotalDisplay').textContent = '₱ 0.00';
        
        // Reset file uploads
        resetFileUpload('evidence');
        
        // Clear error styles
        document.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        
        formModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeForm() {
        formModal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // File Upload Handling
    function setupFileUpload(type) {
        const area = document.getElementById(`${type}UploadArea`);
        const input = document.getElementById('evidence_document');
        const fileName = document.getElementById(`${type}FileName`);
        const fileNameText = document.getElementById(`${type}FileNameText`);
        const uploadText = document.getElementById(`${type}UploadText`);
        
        area.addEventListener('click', () => input.click());
        
        input.addEventListener('change', function(e) {
            if (this.files.length > 0) {
                const file = this.files[0];
                fileNameText.textContent = file.name;
                fileName.classList.remove('hidden');
                uploadText.textContent = 'File selected';
                area.classList.add('border-green-500', 'bg-green-50');
                area.classList.remove('border-gray-300', 'hover:border-purple-500');
            } else {
                resetFileUpload(type);
            }
        });
    }

    function resetFileUpload(type) {
        const fileName = document.getElementById(`${type}FileName`);
        const fileNameText = document.getElementById(`${type}FileNameText`);
        const uploadText = document.getElementById(`${type}UploadText`);
        const area = document.getElementById(`${type}UploadArea`);
        const input = document.getElementById('evidence_document');
        
        fileName.classList.add('hidden');
        fileNameText.textContent = '';
        input.value = '';
        uploadText.textContent = 'Click to upload quotes or estimates';
        area.classList.remove('border-green-500', 'bg-green-50');
        area.classList.add('border-gray-300', 'hover:border-purple-500');
    }

    // Initialize file uploads
    setupFileUpload('evidence');

    // Payment Fields Toggle
    function togglePaymentFields() {
        const mode = document.getElementById('mode_of_payment').value;
        
        document.getElementById('ecashFields').classList.add('hidden');
        document.getElementById('bankFields').classList.add('hidden');
        
        if (mode === 'ecash') {
            document.getElementById('ecashFields').classList.remove('hidden');
        } else if (mode === 'bank') {
            document.getElementById('bankFields').classList.remove('hidden');
        }
    }

    // Form Submission
    function submitForm() {
        const form = document.getElementById('dynamicRequestForm');
        const fd = new FormData(form);
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('submitProposalBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
        submitBtn.disabled = true;
        
        fetch('request_portal.php', {
            method: 'POST',
            body: fd
        })
        .then(async res => {
            const text = await res.text();
            console.log("Raw response:", text);
            
            try {
                const data = JSON.parse(text);
                return data;
            } catch (e) {
                console.error("Failed to parse JSON:", e);
                throw new Error("Server returned invalid JSON. Response: " + text.substring(0, 100));
            }
        })
        .then(res => {
            if (res.success) {
                showToast(res.msg, true);
                setTimeout(() => {
                    closeForm();
                    loadRequests('recent');
                }, 1500);
            } else {
                showToast(res.msg, false);
            }
        })
        .catch((error) => {
            console.error('Error:', error);
            showToast("Error: " + error.message, false);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    function validateForm() {
        const requiredFields = [
            'description', 'department', 'categorySearch', 'subcategorySelect', 
            'amount', 'target_release_date', 'end_date', 'purpose'
        ];
        
        let isValid = true;
        
        // Clear previous error styles
        document.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        
        // Check required fields
        requiredFields.forEach(field => {
            const element = document.getElementById(field);
            if (!element || !element.value.trim()) {
                if(element) element.classList.add('error-field');
                isValid = false;
            }
        });
        
        // Validate amount
        const amount = document.getElementById('amount');
        if (!amount.value || parseFloat(amount.value) <= 0) {
            amount.classList.add('error-field');
            showToast("Amount must be greater than 0 and calculated from breakdown.", false);
            isValid = false;
        }

        // Validate breakdown existence
        const breakdownItems = document.getElementsByName('breakdown_amount[]');
        if (breakdownItems.length === 0) {
            showToast("Please add at least one GL account to the cost breakdown.", false);
            isValid = false;
        }
        
        if (!isValid) {
            showToast("Please fill in all required fields marked with *", false);
        }
        
        return isValid;
    }

    // Toast Notification
    function showToast(message, success = true) {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        
        toastMessage.textContent = message;
        toast.className = 'toast show ' + (success ? 'toast-success' : 'toast-error');
        
        const icon = toast.querySelector('i');
        icon.className = success ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Table Functions
    document.getElementById('recentTab').onclick = function() {
        this.classList.add('bg-blue-50', 'text-blue-600');
        document.getElementById('historyTab').classList.remove('bg-blue-50', 'text-blue-600');
        loadRequests('recent');
    };
    
    document.getElementById('historyTab').onclick = function() {
        this.classList.add('bg-blue-50', 'text-blue-600');
        document.getElementById('recentTab').classList.remove('bg-blue-50', 'text-blue-600');
        loadRequests('history');
    };

    function loadRequests(tabType) {
        let search = document.getElementById('searchBox').value.trim();
        fetch(`request_portal_data.php?type=${encodeURIComponent(selectedType)}&tab=${tabType}&search=${encodeURIComponent(search)}`)
            .then(resp => {
                if (!resp.ok) {
                    throw new Error('Network response was not ok');
                }
                return resp.text();
            })
            .then(html => document.getElementById('requestsTableContainer').innerHTML = html)
            .catch(error => {
                console.error('Error loading requests:', error);
                document.getElementById('requestsTableContainer').innerHTML = '<p class="text-red-500">Error loading data. Please try again.</p>';
            });
    }
    
    document.getElementById('searchBox').addEventListener('input', () => {
        if (document.getElementById('recentTab').classList.contains('bg-blue-50')) {
            loadRequests('recent');
        } else {
            loadRequests('history');
        }
    });

    // Close modals on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (!formModal.classList.contains('hidden')) {
                closeForm();
            }
            if (!viewModal.classList.contains('hidden')) {
                closeViewModal();
            }
        }
    });

    // Close modals on outside click
    formModal.addEventListener('click', (e) => {
        if (e.target === formModal) {
            closeForm();
        }
    });
    
    viewModal.addEventListener('click', (e) => {
        if (e.target === viewModal) {
            closeViewModal();
        }
    });

    // Initialize
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('recentTab').click();
        loadRequests('recent');
    });
</script>
</body>
</html>