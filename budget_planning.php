<?php
// budget_planning.php - COMPLETE FIXED VERSION WITH ERROR HANDLING
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display to prevent HTML errors in JSON
ini_set('log_errors', 1); // Log errors instead

// Start output buffering to capture any stray output
ob_start();

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');

if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
}

// Constants
$current_year = date('Y');
$years = range($current_year - 1, $current_year + 1);
$departments = ['Administrative', 'Core-1', 'Core-2', 'Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Logistic-1', 'Logistic-2', 'Financials'];

$tnvs_categories = [
    'Accounts Payable' => ['Supplier Payables', 'Service Payables'],
    'Accrued Liabilities' => ['Platform Payables', 'Driver Payables', 'Employee Payables', 'Tax Payables'],
    'Long-term Liabilities' => ['Loans Payable'],
    'Vehicle Operations' => ['Fuel Expenses', 'Vehicle Maintenance', 'Tire Replacement', 'Car Wash & Detailing', 'Insurance Premiums', 'Vehicle Registration', 'Parking Fees', 'Toll Fees'],
    'Driver Costs' => ['Driver Commissions', 'Driver Incentives', 'Driver Training', 'Driver Safety Gear', 'Health Insurance for Drivers'],
    'Technology & Platform' => ['App Platform Fees', 'GPS & Navigation Subscriptions', 'In-car Wi-Fi & Connectivity', 'Mobile Device Expenses', 'Software Licenses'],
    'Marketing & Acquisition' => ['Rider Acquisition Marketing', 'Driver Sign-up Bonuses', 'Promotional Campaigns', 'Referral Programs', 'Social Media Advertising'],
    'Back Office & Support' => ['Office Rent & Utilities', 'Professional Services', 'Legal & Compliance', 'Office Supplies', 'Support Staff Compensation'],
    'Personnel & Workforce' => ['Employee Salaries & Benefits', 'Payroll Administration', 'Recruitment & Hiring', 'Staff Development Programs', 'HR Systems', 'Benefits Administration'],
    'Contingency' => ['Emergency Repairs', 'Accident Reserves', 'Regulatory Changes Reserve', 'Market Fluctuation Buffer'],
    'Other Expenses' => ['Depreciation Expense', 'Bank Charges', 'Interest Expense', 'Business Taxes', 'Permit & License Fees']
];

$proposal_statuses = [
    'draft' => 'Draft',
    'submitted' => 'Submitted',
    'pending_review' => 'Pending Review',
    'rejected' => 'Rejected',
    'approved' => 'Approved',
    'archived' => 'Archived'
];

$cost_categories = [
    'direct' => 'Direct Costs',
    'indirect' => 'Indirect Costs',
    'equipment' => 'Equipment & Supplies',
    'travel' => 'Travel & Expenses',
    'contingency' => 'Contingency',
    'other' => 'Other Expenses'
];

// Get user's actual name from session or database
$user_name = $_SESSION['name'] ?? ($_SESSION['username'] ?? 'User');
$user_id = $_SESSION['user_id'] ?? null;

// Get GL Accounts - CATEGORY (L2), SUBCATEGORY (L3), AND ACCOUNT (L4)
$gl_categories = [];
$gl_subcategories = [];
$gl_accounts_by_sub = [];

try {
    // 1. Fetch Categories (Level 2) - liabilities and expenses only for budgeting
    $cat_res = $conn->query("SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE level = 2 AND status = 'active' AND type IN ('Liability', 'Expense') ORDER BY name ASC");
    if ($cat_res) {
        while ($row = $cat_res->fetch_assoc()) {
            $gl_categories[] = $row;
        }
    }

    // 2. Fetch Subcategories (Level 3)
    $sub_res = $conn->query("SELECT id, code, name, parent_id, type FROM chart_of_accounts_hierarchy WHERE level = 3 AND status = 'active' ORDER BY name ASC");
    if ($sub_res) {
        while ($row = $sub_res->fetch_assoc()) {
            $gl_subcategories[$row['parent_id']][] = $row;
        }
    }

    // 3. Fetch Accounts (Level 4)
    $acc_res = $conn->query("SELECT id, code, name, parent_id, type FROM chart_of_accounts_hierarchy WHERE level = 4 AND status = 'active' ORDER BY name ASC");
    if ($acc_res) {
        while ($row = $acc_res->fetch_assoc()) {
            $gl_accounts_by_sub[$row['parent_id']][] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Hierarchical GL Accounts query failed: " . $e->getMessage());
}

// For backward compatibility with existing Strategic Plan logic (replacing major/minor names)
$major_gl_accounts = $gl_categories;
$minor_gl_accounts = $gl_subcategories;


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clear ALL output buffers to prevent HTML errors in JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    try {
        if (!$conn) {
            throw new Exception("Database connection lost");
        }
        
        switch ($_POST['action']) {
            case 'add_proposal':
                // Prepare data
                foreach ($_POST as $key => $value) {
                    if (is_string($value)) {
                        $_POST[$key] = trim($value);
                    }
                }
                
                // Updated required fields for simplified form (removed department)
                $required = ['proposal_title', 'project_objectives', 'category', 
                            'sub_category', 'fiscal_year', 'total_budget', 'start_date', 'end_date'];
                $missing_fields = [];
                foreach ($required as $field) {
                    if (empty($_POST[$field])) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (!empty($missing_fields)) {
                    throw new Exception("Please fill all required fields: " . implode(', ', $missing_fields));
                }
                
                $proposal_code = 'PROP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                // Handle multiple file uploads
                $supporting_docs = [];
                if (isset($_FILES['supporting_docs']) && is_array($_FILES['supporting_docs']['name'])) {
                    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'];
                    $upload_dir = 'uploads/proposals/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    for ($i = 0; $i < count($_FILES['supporting_docs']['name']); $i++) {
                        if ($_FILES['supporting_docs']['error'][$i] === 0) {
                            $ext = strtolower(pathinfo($_FILES['supporting_docs']['name'][$i], PATHINFO_EXTENSION));
                            
                            if (!in_array($ext, $allowed)) {
                                throw new Exception("Invalid file type: " . $_FILES['supporting_docs']['name'][$i] . ". Allowed: " . implode(', ', $allowed));
                            }
                            
                            if ($_FILES['supporting_docs']['size'][$i] > 10485760) {
                                throw new Exception("File size must be less than 10MB: " . $_FILES['supporting_docs']['name'][$i]);
                            }
                            
                            $file_name = time() . '_' . $i . '_' . basename($_FILES['supporting_docs']['name'][$i]);
                            if (move_uploaded_file($_FILES['supporting_docs']['tmp_name'][$i], $upload_dir . $file_name)) {
                                $supporting_docs[] = $file_name;
                            }
                        }
                    }
                }
                
                // Calculate duration
                $start_date = date('Y-m-d', strtotime($_POST['start_date']));
                $end_date = date('Y-m-d', strtotime($_POST['end_date']));
                $duration_days = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24);
                
                // Prepare proposal data
                $proposal_title = $conn->real_escape_string($_POST['proposal_title']);
                $project_objectives = $conn->real_escape_string($_POST['project_objectives']);
                $project_scope = $conn->real_escape_string($_POST['project_scope'] ?? $_POST['project_objectives']);
                $project_deliverables = $conn->real_escape_string($_POST['project_deliverables'] ?? 'As per budget description');
                $implementation_timeline = $conn->real_escape_string($_POST['implementation_timeline'] ?? 'As per project dates');
                $project_type = $conn->real_escape_string($_POST['project_type'] ?? 'operational');
                $proposal_type = $conn->real_escape_string($_POST['proposal_type'] ?? 'new');
                $department = !empty($_POST['department']) ? $conn->real_escape_string($_POST['department']) : 'General';
                $category_id = (int)$_POST['category'];
                $subcategory_id = (int)$_POST['sub_category'];

                // Lookup Category and Subcategory Names (since IDs are sent from form)
                $cat_res = $conn->query("SELECT name FROM chart_of_accounts_hierarchy WHERE id = $category_id LIMIT 1");
                $category = ($cat_res && $row = $cat_res->fetch_assoc()) ? $row['name'] : 'Uncategorized';
                
                $sub_res = $conn->query("SELECT name FROM chart_of_accounts_hierarchy WHERE id = $subcategory_id LIMIT 1");
                $sub_category = ($sub_res && $row = $sub_res->fetch_assoc()) ? $row['name'] : 'Uncategorized';
                
                $category = $conn->real_escape_string($category);
                $sub_category = $conn->real_escape_string($sub_category);

                $gl_account_code = !empty($_POST['gl_account_code']) ? $conn->real_escape_string($_POST['gl_account_code']) : null;
                $fiscal_year = (int)$_POST['fiscal_year'];
                $quarter = !empty($_POST['quarter']) ? (int)$_POST['quarter'] : null;
                $month = !empty($_POST['month']) ? (int)$_POST['month'] : null;
                $total_budget = (float)$_POST['total_budget'];
                $direct_costs = (float)($_POST['direct_costs'] ?? 0);
                $indirect_costs = (float)($_POST['indirect_costs'] ?? 0);
                $equipment_costs = (float)($_POST['equipment_costs'] ?? 0);
                $travel_costs = (float)($_POST['travel_costs'] ?? 0);
                $contingency_percentage = (float)($_POST['contingency_percentage'] ?? 5.0);
                $contingency_amount = (float)($_POST['contingency_amount'] ?? ($total_budget * $contingency_percentage / 100));
                $previous_budget = (float)($_POST['previous_budget'] ?? 0);
                $justification = $conn->real_escape_string($_POST['justification'] ?? $_POST['project_objectives']);
                $business_case = $conn->real_escape_string($_POST['business_case'] ?? '');
                $expected_roi = !empty($_POST['expected_roi']) ? (float)$_POST['expected_roi'] : null;
                $priority_level = $conn->real_escape_string($_POST['priority_level'] ?? 'medium');
                $submitted_by = $user_name;
                $status = 'pending_review';
                $submitted_at = date('Y-m-d H:i:s');
                $supporting_docs_json = !empty($supporting_docs) ? json_encode($supporting_docs) : null;
                $funding_sources = $conn->real_escape_string($_POST['funding_sources'] ?? '');
                $cost_sharing_details = $conn->real_escape_string($_POST['cost_sharing_details'] ?? '');
                $team_members = $conn->real_escape_string($_POST['team_members'] ?? '');
                $executive_summary = $conn->real_escape_string($_POST['executive_summary'] ?? '');
                $approval_required = isset($_POST['approval_required']) ? 1 : 0;
                $description = $conn->real_escape_string($_POST['description'] ?? '');
                
                // Check if proposal already exists
                $check_sql = "SELECT id FROM budget_proposals WHERE proposal_title = ? AND fiscal_year = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("si", $proposal_title, $fiscal_year);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    throw new Exception("A proposal with the same title already exists for this year");
                }
                $check_stmt->close();
                
                // Insert proposal
                $sql = "INSERT INTO budget_proposals (
                    proposal_code, proposal_title, project_objectives, project_scope, project_deliverables, 
                    implementation_timeline, project_type, proposal_type, department, category, sub_category, 
                    gl_account_code, fiscal_year, quarter, month, total_budget, direct_costs, indirect_costs, 
                    equipment_costs, travel_costs, contingency_percentage, contingency_amount, previous_budget, 
                    justification, business_case, expected_roi, priority_level, submitted_by, submitted_at, 
                    status, supporting_docs, funding_sources, cost_sharing_details, team_members, 
                    executive_summary, approval_required, start_date, end_date, duration_days, description
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param(
                    "ssssssssssssiiiddddddddssdsssssssssissis",
                    $proposal_code,
                    $proposal_title,
                    $project_objectives,
                    $project_scope,
                    $project_deliverables,
                    $implementation_timeline,
                    $project_type,
                    $proposal_type,
                    $department,
                    $category,
                    $sub_category,
                    $gl_account_code,
                    $fiscal_year,
                    $quarter,
                    $month,
                    $total_budget,
                    $direct_costs,
                    $indirect_costs,
                    $equipment_costs,
                    $travel_costs,
                    $contingency_percentage,
                    $contingency_amount,
                    $previous_budget,
                    $justification,
                    $business_case,
                    $expected_roi,
                    $priority_level,
                    $submitted_by,
                    $submitted_at,
                    $status,
                    $supporting_docs_json,
                    $funding_sources,
                    $cost_sharing_details,
                    $team_members,
                    $executive_summary,
                    $approval_required,
                    $start_date,
                    $end_date,
                    $duration_days,
                    $description
                );
                
                if ($stmt->execute()) {
                    $proposal_id = $conn->insert_id;
                    
                    // Insert proposal items if provided
                    if (isset($_POST['proposal_items']) && is_array($_POST['proposal_items'])) {
                        $items_sql = "INSERT INTO budget_proposal_items (proposal_id, item_type, category, description, quantity, unit_cost, total_cost, timeline_month, justification, vendor_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $items_stmt = $conn->prepare($items_sql);
                        if (!$items_stmt) {
                            throw new Exception("Prepare items failed: " . $conn->error);
                        }
                        
                        foreach ($_POST['proposal_items'] as $item) {
                            if (!empty($item['description'])) {
                                $item_type = $conn->real_escape_string($item['type'] ?? 'direct');
                                $item_category = $conn->real_escape_string($item['category'] ?? 'direct');
                                $item_description = $conn->real_escape_string($item['description']);
                                $item_quantity = (int)($item['quantity'] ?? 1);
                                $item_unit_cost = (float)($item['unit_cost'] ?? 0);
                                $item_total_cost = (float)($item['total_cost'] ?? 0);
                                $item_timeline_month = !empty($item['timeline_month']) ? (int)$item['timeline_month'] : null;
                                $item_justification = $conn->real_escape_string($item['justification'] ?? '');
                                $item_vendor_info = $conn->real_escape_string($item['vendor_info'] ?? '');
                                
                                $items_stmt->bind_param(
                                    "isssiddiss",
                                    $proposal_id,
                                    $item_type,
                                    $item_category,
                                    $item_description,
                                    $item_quantity,
                                    $item_unit_cost,
                                    $item_total_cost,
                                    $item_timeline_month,
                                    $item_justification,
                                    $item_vendor_info
                                );
                                $items_stmt->execute();
                            }
                        }
                        $items_stmt->close();
                    }
                    
                    $response['success'] = true;
                    $response['message'] = "Budget proposal submitted for review successfully";
                    $response['proposal_id'] = $proposal_id;
                    $response['proposal_code'] = $proposal_code;
                } else {
                    throw new Exception("Failed to save proposal: " . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'save_proposal_draft':
                // Similar to add_proposal but with status = 'draft'
                foreach ($_POST as $key => $value) {
                    if (is_string($value)) {
                        $_POST[$key] = trim($value);
                    }
                }
                
                $proposal_code = 'PROP-DRAFT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                
                // Handle file uploads
                $supporting_docs = [];
                if (isset($_FILES['supporting_docs']) && is_array($_FILES['supporting_docs']['name'])) {
                    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'];
                    $upload_dir = 'uploads/proposals/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    for ($i = 0; $i < count($_FILES['supporting_docs']['name']); $i++) {
                        if ($_FILES['supporting_docs']['error'][$i] === 0) {
                            $ext = strtolower(pathinfo($_FILES['supporting_docs']['name'][$i], PATHINFO_EXTENSION));
                            
                            if (!in_array($ext, $allowed)) {
                                continue; // Skip invalid files for draft
                            }
                            
                            if ($_FILES['supporting_docs']['size'][$i] > 10485760) {
                                continue; // Skip large files for draft
                            }
                            
                            $file_name = time() . '_' . $i . '_' . basename($_FILES['supporting_docs']['name'][$i]);
                            if (move_uploaded_file($_FILES['supporting_docs']['tmp_name'][$i], $upload_dir . $file_name)) {
                                $supporting_docs[] = $file_name;
                            }
                        }
                    }
                }
                
                // Prepare data with defaults for draft
                $proposal_title = $conn->real_escape_string($_POST['proposal_title'] ?? 'Draft Proposal');
                $project_objectives = $conn->real_escape_string($_POST['project_objectives'] ?? '');
                $project_scope = $conn->real_escape_string($_POST['project_scope'] ?? '');
                $project_deliverables = $conn->real_escape_string($_POST['project_deliverables'] ?? '');
                $implementation_timeline = $conn->real_escape_string($_POST['implementation_timeline'] ?? '');
                $project_type = $conn->real_escape_string($_POST['project_type'] ?? 'operational');
                $proposal_type = $conn->real_escape_string($_POST['proposal_type'] ?? 'new');
                $department = $conn->real_escape_string($_POST['department'] ?? '');
                $category = $conn->real_escape_string($_POST['category'] ?? '');
                $sub_category = $conn->real_escape_string($_POST['sub_category'] ?? '');
                $fiscal_year = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : $current_year;
                $total_budget = isset($_POST['total_budget']) ? (float)$_POST['total_budget'] : 0;
                $justification = $conn->real_escape_string($_POST['justification'] ?? '');
                $status = 'draft';
                
                $sql = "INSERT INTO budget_proposals (
                    proposal_code, proposal_title, project_objectives, project_scope, project_deliverables, 
                    implementation_timeline, project_type, proposal_type, department, category, sub_category, 
                    fiscal_year, total_budget, justification, submitted_by, status, supporting_docs
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                $stmt->bind_param(
                    "sssssssssssidsiss",
                    $proposal_code,
                    $proposal_title,
                    $project_objectives,
                    $project_scope,
                    $project_deliverables,
                    $implementation_timeline,
                    $project_type,
                    $proposal_type,
                    $department,
                    $category,
                    $sub_category,
                    $fiscal_year,
                    $total_budget,
                    $justification,
                    $user_name,
                    $status,
                    json_encode($supporting_docs)
                );
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = "Proposal saved as draft";
                    $response['proposal_id'] = $conn->insert_id;
                } else {
                    throw new Exception("Failed to save draft: " . $stmt->error);
                }
                $stmt->close();
                break;
                
            case 'update_proposal_status':
                if (empty($_POST['proposal_id']) || empty($_POST['status'])) {
                    throw new Exception("Proposal ID and status are required");
                }
                
                $proposal_id = (int)$_POST['proposal_id'];
                $status = $conn->real_escape_string($_POST['status']);
                $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
                $reviewed_by = $user_name;
                $adjusted_amount = !empty($_POST['adjusted_amount']) ? (float)$_POST['adjusted_amount'] : null;
                $rejection_reason = !empty($_POST['rejection_reason']) ? $conn->real_escape_string($_POST['rejection_reason']) : null;
                
                $update_fields = "status = '$status', updated_at = NOW()";
                
                if ($status === 'approved') {
                    // Mark as approved and create budget plan immediately
                    $update_fields = "status = 'approved', approved_by = '$reviewed_by', approved_at = NOW(), updated_at = NOW()";
                    
                    // Create a budget plan from approved proposal
                    $proposal_result = $conn->query("SELECT * FROM budget_proposals WHERE id = $proposal_id");
                    if ($proposal_result && $proposal_result->num_rows > 0) {
                        $proposal = $proposal_result->fetch_assoc();
                        
                        // Create budget plan
                        $plan_code = !empty($proposal['plan_code']) ? $proposal['plan_code'] : ('PLAN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
                        $plan_name = $proposal['proposal_title'];
                        $department = $proposal['department'];
                        $category = $proposal['category'];
                        $sub_category = $proposal['sub_category'];
                        $plan_type = $proposal['project_type'] ?? 'operational';
                        $plan_year = $proposal['fiscal_year'];
                        $plan_month = $proposal['month'];
                        $planned_amount = $adjusted_amount !== null ? $adjusted_amount : $proposal['total_budget'];
                        $gl_account_code = $proposal['gl_account_code'];
                        $description = $proposal['description'] ?? $proposal['justification'];
                        $created_by = $proposal['submitted_by'];
                        
                        // Check if plan already exists
                        $check_plan = $conn->query("SELECT id FROM budget_plans WHERE plan_name = '$plan_name' AND plan_year = $plan_year AND department = '$department'");
                        
                        if ($check_plan->num_rows == 0) {
                            // Build dynamic SQL based on whether gl_account_code exists
                            if (!empty($gl_account_code)) {
                                $plan_sql = "INSERT INTO budget_plans (plan_code, plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, gl_account_code, description, status, created_by) 
                                            VALUES ('$plan_code', '$plan_name', '$department', '$category', '$sub_category', '$plan_type', $plan_year, " . 
                                            ($plan_month ? "$plan_month" : "NULL") . ", $planned_amount, '$gl_account_code', '$description', 'approved', '$created_by')";
                            } else {
                                $plan_sql = "INSERT INTO budget_plans (plan_code, plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, description, status, created_by) 
                                            VALUES ('$plan_code', '$plan_name', '$department', '$category', '$sub_category', '$plan_type', $plan_year, " . 
                                            ($plan_month ? "$plan_month" : "NULL") . ", $planned_amount, '$description', 'approved', '$created_by')";
                            }
                            
                            if ($conn->query($plan_sql)) {
                                $plan_id = $conn->insert_id;
                                $conn->query("INSERT INTO budget_plan_snapshots (plan_id, snapshot_date, planned_amount, snapshot_type) 
                                            VALUES ($plan_id, CURDATE(), $planned_amount, 'initial')");
                            }
                        }
                        $check_plan->free();
                    }
                    $proposal_result->free();
                } elseif ($status === 'rejected') {
                    $update_fields .= ", reviewed_by = '$reviewed_by', reviewed_at = NOW(), rejection_reason = '$rejection_reason'";
                } elseif ($status === 'pending_review') {
                    $update_fields .= ", reviewed_by = '$reviewed_by', reviewed_at = NOW()";
                }
                
                if ($adjusted_amount !== null) {
                    $update_fields .= ", adjusted_amount = $adjusted_amount";
                }
                
                $sql = "UPDATE budget_proposals SET $update_fields WHERE id = $proposal_id";
                
                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = "Proposal status updated successfully";
                } else {
                    throw new Exception("Failed to update proposal: " . $conn->error);
                }
                break;
                
            case 'add_proposal_comment':
                if (empty($_POST['proposal_id']) || empty($_POST['comment'])) {
                    throw new Exception("Proposal ID and comment are required");
                }
                
                $proposal_id = (int)$_POST['proposal_id'];
                $comment = $conn->real_escape_string(trim($_POST['comment']));
                $user_name = $user_name;
                $user_id = $_SESSION['user_id'] ?? null;
                $is_internal = isset($_POST['is_internal']) ? (int)$_POST['is_internal'] : 1;
                
                $attachment = null;
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
                    $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowed)) {
                        throw new Exception("Invalid file type");
                    }
                    
                    if ($_FILES['attachment']['size'] > 5242880) {
                        throw new Exception("Attachment size must be less than 5MB");
                    }
                    
                    $upload_dir = 'uploads/proposal_comments/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $attachment = time() . '_' . basename($_FILES['attachment']['name']);
                    if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $attachment)) {
                        throw new Exception("Failed to upload attachment");
                    }
                }
                
                $sql = "INSERT INTO proposal_comments (proposal_id, user_id, user_name, comment, attachment, is_internal) 
                        VALUES ($proposal_id, " . ($user_id ? "$user_id" : "NULL") . ", '$user_name', '$comment', " .
                        ($attachment ? "'$attachment'" : "NULL") . ", $is_internal)";
                
                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = "Comment added successfully";
                } else {
                    throw new Exception("Failed to add comment: " . $conn->error);
                }
                break;
                
            case 'save_forecast':
                if (empty($_POST['department']) || empty($_POST['category']) || empty($_POST['forecast_period']) || empty($_POST['forecasted_amount'])) {
                    throw new Exception("All required fields must be filled");
                }
                
                $department = $conn->real_escape_string(trim($_POST['department']));
                $category = $conn->real_escape_string(trim($_POST['category']));
                $forecast_type = $conn->real_escape_string($_POST['forecast_type'] ?? 'monthly');
                $forecast_period = $conn->real_escape_string($_POST['forecast_period']);
                $forecasted_amount = (float)$_POST['forecasted_amount'];
                $actual_amount = (float)($_POST['actual_amount'] ?? 0);
                $confidence_level = (int)($_POST['confidence_level'] ?? 80);
                $assumptions = $conn->real_escape_string(trim($_POST['assumptions'] ?? ''));
                $forecasted_by = $user_name;
                
                $check_sql = "SELECT id FROM budget_forecasts WHERE department = '$department' AND category = '$category' 
                              AND forecast_period = '$forecast_period'";
                $check_result = $conn->query($check_sql);
                
                if ($check_result && $check_result->num_rows > 0) {
                    $row = $check_result->fetch_assoc();
                    $sql = "UPDATE budget_forecasts SET forecasted_amount = $forecasted_amount, 
                            actual_amount = $actual_amount, confidence_level = $confidence_level, 
                            assumptions = '$assumptions', forecasted_by = '$forecasted_by', updated_at = NOW() 
                            WHERE id = {$row['id']}";
                    $check_result->free();
                } else {
                    $sql = "INSERT INTO budget_forecasts (department, category, forecast_type, forecast_period, 
                            forecasted_amount, actual_amount, confidence_level, assumptions, forecasted_by) 
                            VALUES ('$department', '$category', '$forecast_type', '$forecast_period', 
                            $forecasted_amount, $actual_amount, $confidence_level, '$assumptions', '$forecasted_by')";
                }
                
                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = "Forecast saved successfully";
                } else {
                    throw new Exception("Failed to save forecast: " . $conn->error);
                }
                break;
                
            case 'review_plan':
                if (empty($_POST['plan_id'])) {
                    throw new Exception("Plan ID is required");
                }
                
                $plan_id = (int)$_POST['plan_id'];
                $review_notes = $conn->real_escape_string(trim($_POST['review_notes'] ?? ''));
                $reviewed_by = $user_name;
                $new_amount = !empty($_POST['planned_amount']) ? (float)$_POST['planned_amount'] : null;
                
                $conn->begin_transaction();
                
                try {
                    $current_result = $conn->query("SELECT planned_amount FROM budget_plans WHERE id = $plan_id");
                    if (!$current_result || $current_result->num_rows === 0) {
                        throw new Exception("Plan not found");
                    }
                    $current = $current_result->fetch_assoc();
                    $current_amount = $current['planned_amount'];
                    $current_result->free();
                    
                    $update_sql = "UPDATE budget_plans SET 
                        status = 'pending_review', 
                        reviewed_by = '$reviewed_by', 
                        reviewed_at = NOW(), 
                        review_notes = '$review_notes',
                        updated_at = NOW()";
                    
                    if ($new_amount !== null) {
                        $update_sql .= ", planned_amount = $new_amount";
                    }
                    
                    $update_sql .= " WHERE id = $plan_id";
                    
                    if (!$conn->query($update_sql)) {
                        throw new Exception("Review update failed: " . $conn->error);
                    }
                    
                    if ($new_amount !== null && $new_amount != $current_amount) {
                        $conn->query("INSERT INTO budget_plan_snapshots (plan_id, snapshot_date, planned_amount, snapshot_type) 
                                     VALUES ($plan_id, CURDATE(), $new_amount, 'reviewed')");
                    }
                    
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = "Plan marked for review";
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                break;
                
            case 'archive_plan':
                if (empty($_POST['plan_id'])) {
                    throw new Exception("Plan ID is required");
                }
                
                $plan_id = (int)$_POST['plan_id'];
                $archive_reason = $conn->real_escape_string(trim($_POST['archive_reason'] ?? ''));
                $archive_notes = $conn->real_escape_string(trim($_POST['archive_notes'] ?? ''));
                $archived_by = $user_name;
                
                // First, get the details of the selected plan to find related rows
                $result = $conn->query("SELECT plan_name, department, plan_year FROM budget_plans WHERE id = $plan_id");
                if (!$result || $result->num_rows === 0) {
                    throw new Exception("Plan not found");
                }
                $base_plan = $result->fetch_assoc();
                $result->free();
                
                $safe_title = $conn->real_escape_string($base_plan['plan_name']);
                $safe_dept = $conn->real_escape_string($base_plan['department']);
                $safe_year = (int)$base_plan['plan_year'];

                // Get all rows belonging to this plan
                $result = $conn->query("SELECT * FROM budget_plans 
                                       WHERE plan_name = '$safe_title' 
                                       AND department = '$safe_dept' 
                                       AND plan_year = $safe_year
                                       AND status != 'archived'");
                
                $archive_count = 0;
                while ($plan = $result->fetch_assoc()) {
                    $curr_id = $plan['id'];
                    $sql = "INSERT INTO budget_plan_archive (original_plan_id, plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, gl_account_code, description, created_by, archived_by, archive_reason, archive_notes) 
                            VALUES ($curr_id, '{$plan['plan_name']}', '{$plan['department']}', '{$plan['category']}', '{$plan['sub_category']}', '{$plan['plan_type']}', {$plan['plan_year']}, " . 
                            ($plan['plan_month'] ? $plan['plan_month'] : "NULL") . ", {$plan['planned_amount']}, " . 
                            ($plan['gl_account_code'] ? "'{$plan['gl_account_code']}'" : "NULL") . ", '{$plan['description']}', '{$plan['created_by']}', '$archived_by', '$archive_reason', '$archive_notes')";
                    
                    if ($conn->query($sql)) {
                        $conn->query("UPDATE budget_plans SET status = 'archived', updated_at = NOW() WHERE id = $curr_id");
                        $archive_count++;
                    }
                }
                
                if ($archive_count > 0) {
                    $response['success'] = true;
                    $response['message'] = "Plan archived successfully";
                } else {
                    throw new Exception("No records were archived. They might already be archived.");
                }
                break;
                
            case 'restore_plan':
                if (empty($_POST['archive_id'])) {
                    throw new Exception("Archive ID is required");
                }
                
                $archive_id = (int)$_POST['archive_id'];
                $restore_reason = $conn->real_escape_string(trim($_POST['restore_reason'] ?? ''));
                $restored_by = $user_name;
                
                $result = $conn->query("SELECT * FROM budget_plan_archive WHERE id = $archive_id");
                if (!$result || $result->num_rows === 0) {
                    throw new Exception("Archived plan not found");
                }
                $archived_plan = $result->fetch_assoc();
                $result->free();
                
                $conn->begin_transaction();
                
                try {
                    if ($archived_plan['original_plan_id']) {
                        $original_result = $conn->query("SELECT id FROM budget_plans WHERE id = {$archived_plan['original_plan_id']}");
                        if ($original_result && $original_result->num_rows > 0) {
                            $update_sql = "UPDATE budget_plans SET 
                                status = 'draft',
                                updated_at = NOW(),
                                restored_from = $archive_id
                                WHERE id = {$archived_plan['original_plan_id']}";
                            
                            if (!$conn->query($update_sql)) {
                                throw new Exception("Failed to restore plan: " . $conn->error);
                            }
                            $original_result->free();
                        } else {
                            $insert_sql = "INSERT INTO budget_plans (plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, gl_account_code, description, status, created_by, restored_from) 
                                          VALUES ('{$archived_plan['plan_name']}', '{$archived_plan['department']}', '{$archived_plan['category']}', '{$archived_plan['sub_category']}', '{$archived_plan['plan_type']}', {$archived_plan['plan_year']}, " . 
                                          ($archived_plan['plan_month'] ? $archived_plan['plan_month'] : "NULL") . ", {$archived_plan['planned_amount']}, " . 
                                          ($archived_plan['gl_account_code'] ? "'{$archived_plan['gl_account_code']}'" : "NULL") . ", '{$archived_plan['description']}', 'draft', '{$archived_plan['created_by']}', $archive_id)";
                            
                            if (!$conn->query($insert_sql)) {
                                throw new Exception("Failed to restore plan: " . $conn->error);
                            }
                        }
                    } else {
                        $insert_sql = "INSERT INTO budget_plans (plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, gl_account_code, description, status, created_by, restored_from) 
                                      VALUES ('{$archived_plan['plan_name']}', '{$archived_plan['department']}', '{$archived_plan['category']}', '{$archived_plan['sub_category']}', '{$archived_plan['plan_type']}', {$archived_plan['plan_year']}, " . 
                                      ($archived_plan['plan_month'] ? $archived_plan['plan_month'] : "NULL") . ", {$archived_plan['planned_amount']}, " . 
                                      ($archived_plan['gl_account_code'] ? "'{$archived_plan['gl_account_code']}'" : "NULL") . ", '{$archived_plan['description']}', 'draft', '{$archived_plan['created_by']}', $archive_id)";
                        
                        if (!$conn->query($insert_sql)) {
                            throw new Exception("Failed to restore plan: " . $conn->error);
                        }
                    }
                    
                    $update_archive = "UPDATE budget_plan_archive SET 
                        restored_at = NOW(),
                        restored_by = '$restored_by',
                        restored = 1,
                        restore_reason = '$restore_reason'
                        WHERE id = $archive_id";
                    
                    if (!$conn->query($update_archive)) {
                        throw new Exception("Failed to update archive record: " . $conn->error);
                    }
                    
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = "Plan restored successfully";
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                break;
                
            case 'get_proposal_items':
                $proposal_id = (int)$_POST['proposal_id'];
                
                $result = $conn->query("SELECT * FROM budget_proposal_items WHERE proposal_id = $proposal_id ORDER BY item_type, id");
                $items = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $items[] = $row;
                    }
                    $result->free();
                }
                
                $response['success'] = true;
                $response['items'] = $items;
                break;
                
            case 'create_plan':
                // Safely trim only string values in $_POST
                foreach ($_POST as $key => $value) {
                    if (is_string($value)) {
                        $_POST[$key] = trim($value);
                    }
                }
                
                try {
                    $conn->begin_transaction();
                    
                    // File Upload Handler
                    $plan_file_name = null;
                    if (!isset($_FILES['plan_file']) || $_FILES['plan_file']['error'] === UPLOAD_ERR_NO_FILE) {
                        throw new Exception("An attachment is required.");
                    }
                    if ($_FILES['plan_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception("File upload failed with error code: " . $_FILES['plan_file']['error']);
                    }
                    
                    $upload_dir = 'uploads/budget_proposals/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    
                    $plan_file_ext = pathinfo($_FILES['plan_file']['name'], PATHINFO_EXTENSION);
                    $plan_file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $plan_file_ext;
                    
                    if (!move_uploaded_file($_FILES['plan_file']['tmp_name'], $upload_dir . $plan_file_name)) {
                        throw new Exception("Failed to save the uploaded file to server.");
                    }
                    
                    // Read file into memory and then DELETE the physical file immediately for privacy
                    $full_upload_path = $upload_dir . $plan_file_name;
                    $plan_file_blob = file_get_contents($full_upload_path);
                    if ($plan_file_blob === false) throw new Exception("Failed to read file contents.");
                    unlink($full_upload_path); // Physically remove from server

                    // Data Prep
                    if (empty($_POST['budget_title'])) {
                        throw new Exception("Budget Title is required.");
                    }
                    $title = $_POST['budget_title'];
                    $user_dept = $conn->real_escape_string($_POST['department'] ?? ''); 
                    $p_type = $_POST['plan_type'];
                    $total_budget = (float)$_POST['total_budget'];
                    $year = (int)($_POST['fiscal_year'] ?? date('Y'));
                    $start = $_POST['start_date'];
                    $end = $_POST['end_date'];
                    $justification = $_POST['justification'];
                    
                    // Privacy: Obfuscate the filename in the database as well
                    $obfuscated_name = "Attached_Document." . $plan_file_ext;
                    $docs = json_encode([$obfuscated_name]);
                    
                    $user_name = $_SESSION['username'] ?? 'User';
                    $batch_time = date('Y-m-d H:i:s'); // Consistent time for grouping

                    // New financial fields
                    $project_revenue = (float)str_replace(',', '', $_POST['project_revenue'] ?? 0);
                    $impact_percentage = (float)str_replace(',', '', $_POST['impact_percentage'] ?? 0);
                    $taxation_adj = (float)str_replace(',', '', $_POST['taxation_adj'] ?? 0);

                    if (!isset($_POST['gl_allocation']) || !is_array($_POST['gl_allocation'])) {
                        throw new Exception("No budget allocations found. Please fill in the grid.");
                    }

                    $total_allocation_amount = 0;
                    foreach ($_POST['gl_allocation'] as $gl_code => $amount) {
                        $amount = (float)str_replace(',', '', $amount);
                        if ($amount > 0) $total_allocation_amount += $amount;
                    }
                    
                    if ($total_allocation_amount <= 0) {
                         throw new Exception("Please enter at least one budget amount in the grid.");
                    }

                    // -- Revenue Limit Check --
                    // Get total Revenue from GL for the target year
                    // Get total Revenue from General Ledger for the target year
                    $revenue_sql = "SELECT SUM(credit_amount) - SUM(debit_amount) as total_revenue
                                    FROM general_ledger 
                                    WHERE account_type = 'Revenue'
                                    AND YEAR(transaction_date) = ?";
                    $rev_stmt = $conn->prepare($revenue_sql);
                    $rev_stmt->bind_param("i", $year);
                    $rev_stmt->execute();
                    $rev_res = $rev_stmt->get_result();
                    $rev_row = $rev_res->fetch_assoc();
                    $total_revenue = (float)($rev_row['total_revenue'] ?? 0);

                    // Get currently allocated (Approved + Pending) budget for the target year
                    $allocated_sql = "SELECT SUM(planned_amount) as total_allocated 
                                     FROM budget_plans 
                                     WHERE plan_year = ? 
                                     AND status IN ('approved', 'pending_review')";
                    $alloc_stmt = $conn->prepare($allocated_sql);
                    $alloc_stmt->bind_param("i", $year);
                    $alloc_stmt->execute();
                    $alloc_res = $alloc_stmt->get_result();
                    $alloc_row = $alloc_res->fetch_assoc();
                    $current_allocated = (float)($alloc_row['total_allocated'] ?? 0);

                    // Compare
                    if (($current_allocated + $total_allocation_amount) > $total_revenue) {
                        $remaining_cap = max(0, $total_revenue - $current_allocated);
                        throw new Exception("Budget limit reached! Total Revenue for $year is ₱" . number_format($total_revenue, 2) . 
                                          ". Remaining capacity is ₱" . number_format($remaining_cap, 2) . 
                                          ". You are trying to allocate ₱" . number_format($total_allocation_amount, 2));
                    }
                    // -- End Revenue Check --

                    $allocations_saved = 0;
                    $batch_id = 'BATCH-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -4));

                    // -- Auto-fix Database Schema if needed --
                    $required_columns = [
                        'justification_doc' => "VARCHAR(255) NULL AFTER status",
                        'justification_blob' => "LONGBLOB NULL AFTER justification_doc",
                        'project_revenue' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER description",
                        'impact_percentage' => "DECIMAL(5, 2) DEFAULT 0.00 AFTER project_revenue",
                        'taxation_adj' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER impact_percentage"
                    ];
                    
                    foreach ($required_columns as $col => $def) {
                        $check_col = $conn->query("SHOW COLUMNS FROM budget_plans LIKE '$col'");
                        if ($check_col && $check_col->num_rows == 0) {
                            $conn->query("ALTER TABLE budget_plans ADD $col $def");
                        }
                    }

                    $plan_sql = "INSERT INTO budget_plans (
                        plan_code, plan_name, department, category, sub_category, 
                        plan_type, plan_year, planned_amount, gl_account_code, description, 
                        status, created_by, justification_doc, justification_blob, project_revenue, impact_percentage, taxation_adj, start_date, end_date, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_review', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $plan_stmt = $conn->prepare($plan_sql);
                    if (!$plan_stmt) throw new Exception("Prepare statement failed: " . $conn->error);

                    foreach ($_POST['gl_allocation'] as $gl_code => $amount) {
                        $amount = (float)$amount;
                        if ($amount <= 0) continue;

                        // Get Category/Sub-category Names AND GL Account Name
                        $gl_info_sql = "SELECT 
                                            acc.name as gl_name,
                                            cat.name as cat_name, 
                                            sub.name as sub_name 
                                        FROM chart_of_accounts_hierarchy acc
                                        LEFT JOIN chart_of_accounts_hierarchy sub ON acc.parent_id = sub.id AND sub.level = 3
                                        LEFT JOIN chart_of_accounts_hierarchy cat ON sub.parent_id = cat.id AND cat.level = 2
                                        WHERE acc.code = ? AND acc.level = 4 LIMIT 1";
                        
                        $gl_info_stmt = $conn->prepare($gl_info_sql);
                        $gl_info_stmt->bind_param("s", $gl_code);
                        $gl_info_stmt->execute();
                        $gl_res = $gl_info_stmt->get_result();
                        $gl_row = $gl_res->fetch_assoc();
                        
                        $row_cat = $gl_row['cat_name'] ?? 'Miscellaneous';
                        $row_sub = $gl_row['sub_name'] ?? 'Uncategorized';
                        
                        $null_blob = null;
                        $plan_stmt->bind_param("ssssssidssssbdddssss", 
                            $batch_id, $title, $user_dept, $row_cat, $row_sub, $p_type, $year, $amount, $gl_code, $justification, 
                            $user_name, $docs, $null_blob, $project_revenue, $impact_percentage, $taxation_adj, $start, $end, $batch_time, $batch_time
                        );
                        
                        // Send binary data
                        $plan_stmt->send_long_data(12, $plan_file_blob); // 12 is the 13th parameter (justification_blob)
                        
                        if (!$plan_stmt->execute()) throw new Exception("Execute plan failed: " . $plan_stmt->error);
                        $allocations_saved++;
                    }

                    if ($allocations_saved === 0) {
                        throw new Exception("Please enter at least one budget amount in the grid.");
                    }


                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = "Budget Plan created successfully!";
                } catch (Exception $e) {
                    if ($conn->in_transaction) $conn->rollback();
                    $response['success'] = false;
                    $response['message'] = "Error: " . $e->getMessage();
                }
                break;
                
            case 'get_plan_details':
                if (empty($_GET['plan_id'])) {
                    throw new Exception("Plan ID is required");
                }
                $plan_id = (int)$_GET['plan_id'];

                // Auto-fix: ensure live server has the same schema
                $live_required_cols = [
                    'justification_doc'  => "VARCHAR(255) NULL",
                    'justification_blob' => "LONGBLOB NULL",
                    'project_revenue'    => "DECIMAL(15,2) DEFAULT 0.00",
                    'impact_percentage'  => "DECIMAL(5,2) DEFAULT 0.00",
                    'taxation_adj'       => "DECIMAL(15,2) DEFAULT 0.00",
                    'start_date'         => "DATE NULL",
                    'end_date'           => "DATE NULL"
                ];
                foreach ($live_required_cols as $col => $def) {
                    $chk = $conn->query("SHOW COLUMNS FROM budget_plans LIKE '$col'");
                    if ($chk && $chk->num_rows === 0) {
                        $conn->query("ALTER TABLE budget_plans ADD $col $def");
                    }
                }
                
                // Get the main plan record (explicit columns — never SELECT * with BLOBs; breaks JSON)
                $sql = "SELECT bp.id, bp.plan_code, bp.plan_name, bp.department, bp.category,
                        bp.sub_category, bp.plan_type, bp.plan_year, bp.plan_month,
                        bp.planned_amount, bp.gl_account_code, bp.description,
                        bp.project_revenue, bp.impact_percentage, bp.taxation_adj,
                        bp.start_date, bp.end_date, bp.created_by, bp.approved_by,
                        bp.status, bp.justification_doc, bp.created_at, bp.updated_at,
                        COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id = bp.id), 0) as actual_spent
                        FROM budget_plans bp WHERE bp.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $plan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $plan = $result->fetch_assoc();
                    $breakdown = [];
                    $total_sum = 0;

                    // 1. Precise Batch Matching using EXACT created_at
                    $p_name = $plan['plan_name'];
                    $created_at = $plan['created_at'];

                    $breakdown_sql = "SELECT 
                                        bp.planned_amount as amount, 
                                        acc.name as account_name,
                                        bp.category,
                                        bp.sub_category as subcategory,
                                        bp.gl_account_code
                                    FROM budget_plans bp
                                    LEFT JOIN chart_of_accounts_hierarchy acc ON bp.gl_account_code = acc.code AND acc.level = 4
                                    WHERE bp.plan_code = ? 
                                    ORDER BY bp.category ASC, bp.sub_category ASC";
                    
                    if ($b_stmt = $conn->prepare($breakdown_sql)) {
                        $p_batch_id = $plan['plan_code'];
                        $b_stmt->bind_param("s", $p_batch_id);
                        $b_stmt->execute();
                        $b_res = $b_stmt->get_result();
                        
                        while($b_row = $b_res->fetch_assoc()) {
                            // Accurate account name retrieval
                            $accName = $b_row['account_name'];
                            if (empty($accName)) {
                                $accName = 'GL Account: ' . $b_row['gl_account_code'];
                            }
                            
                            $breakdown[] = [
                                'name' => $accName,
                                'category' => $b_row['category'] ?? 'General',
                                'subcategory' => $b_row['subcategory'] ?? 'Miscellaneous',
                                'amount' => (float)$b_row['amount']
                            ];
                            $total_sum += (float)$b_row['amount'];
                        }
                    }

                    // 2. Guaranteed Fallback: If sibling query failed, use the primary record's own data
                    if (empty($breakdown)) {
                        // Re-fetch category/subcategory name for the primary record just in case
                        $breakdown[] = [
                            'name' => $plan['plan_name'] . ' (Direct Entry)', 
                            'category' => $plan['category'] ?? 'General',
                            'subcategory' => $plan['sub_category'] ?? 'Miscellaneous',
                            'amount' => (float)$plan['planned_amount']
                        ];
                        $total_sum = (float)$plan['planned_amount'];
                    }

                    // 3. Sync plan object for modal population
                    $plan['planned_amount'] = $total_sum;
                    $plan['breakdown'] = $breakdown;
                    
                    $unique_cats = array_unique(array_filter(array_column($breakdown, 'category')));
                    if (count($unique_cats) > 1) {
                        $plan['category'] = 'Multi-category (' . count($unique_cats) . ')';
                    } else if (!empty($unique_cats)) {
                        $plan['category'] = reset($unique_cats);
                    }
                    
                    if (ob_get_length()) ob_clean();
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true, 'plan' => $plan], JSON_UNESCAPED_UNICODE);
                    exit();
                } else {
                    if (ob_get_length()) ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Plan not found']);
                    exit();
                }
                break;
                
            case 'delete_proposal_item':
                $item_id = (int)$_POST['item_id'];
                
                $sql = "DELETE FROM budget_proposal_items WHERE id = $item_id";
                
                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = "Item deleted successfully";
                } else {
                    throw new Exception("Failed to delete item: " . $conn->error);
                }
                break;
                
            case 'add_proposal_item':
                if (empty($_POST['proposal_id']) || empty($_POST['item_type']) || empty($_POST['description']) || empty($_POST['total_cost'])) {
                    throw new Exception("Required fields are missing");
                }
                
                $proposal_id = (int)$_POST['proposal_id'];
                $item_type = $conn->real_escape_string($_POST['item_type']);
                $category = $conn->real_escape_string($_POST['category'] ?? '');
                $description = $conn->real_escape_string($_POST['description']);
                $quantity = (int)($_POST['quantity'] ?? 1);
                $unit_cost = (float)($_POST['unit_cost'] ?? 0);
                $total_cost = (float)$_POST['total_cost'];
                $timeline_month = !empty($_POST['timeline_month']) ? (int)$_POST['timeline_month'] : null;
                $justification = $conn->real_escape_string($_POST['justification'] ?? '');
                $vendor_info = $conn->real_escape_string($_POST['vendor_info'] ?? '');
                
                $sql = "INSERT INTO budget_proposal_items (proposal_id, item_type, category, description, quantity, unit_cost, total_cost, timeline_month, justification, vendor_info) 
                        VALUES ($proposal_id, '$item_type', '$category', '$description', $quantity, $unit_cost, $total_cost, " . 
                        ($timeline_month ? "$timeline_month" : "NULL") . ", '$justification', '$vendor_info')";
                
                if ($conn->query($sql)) {
                    $response['success'] = true;
                    $response['message'] = "Item added successfully";
                    $response['item_id'] = $conn->insert_id;
                } else {
                    throw new Exception("Failed to add item: " . $conn->error);
                }
                break;
                
            default:
                $response['message'] = "Unknown action";
                break;
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Budget Planning Error: " . $e->getMessage());
    }
    
    // Clean any output buffer before sending JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle AJAX GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax'])) {
    // Clear ALL output buffers to prevent HTML errors in JSON
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        if (!$conn) {
            throw new Exception("Database connection lost");
        }
        
        switch ($_GET['ajax']) {
            case 'load_plans':
                $search = !empty($_GET['search']) ? $conn->real_escape_string(trim($_GET['search'])) : '';
                $department = !empty($_GET['department']) ? $conn->real_escape_string(trim($_GET['department'])) : '';
                $year = !empty($_GET['year']) && $_GET['year'] != '0' ? (int)$_GET['year'] : 0;
                
                // Build WHERE clause
                $where_conditions = ["status IN ('approved', 'pending_review')"];
                
                if (!empty($search)) {
                    $where_conditions[] = "(plan_name LIKE '%$search%' OR category LIKE '%$search%' OR description LIKE '%$search%')";
                }
                
                if (!empty($department)) {
                    $where_conditions[] = "department = '$department'";
                }
                
                if ($year > 0) {
                    $where_conditions[] = "plan_year = $year";
                }
                
                $where = "WHERE " . implode(" AND ", $where_conditions);
                
                // Get total count (Corrected to count distinct plans)
                $count_sql = "SELECT COUNT(DISTINCT plan_code) as total FROM budget_plans $where";
                $count_result = $conn->query($count_sql);
                $total = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                
                // Pagination
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per_page = 10;
                $offset = ($page - 1) * $per_page;
                $total_pages = $total > 0 ? ceil($total / $per_page) : 0;
                
                // Get plans with actual spent
                $sql = "SELECT 
                        MAX(bp.id) as id, bp.plan_code, bp.plan_name, bp.plan_year, bp.plan_type,
                        CASE 
                            WHEN COUNT(DISTINCT bp.category) > 1 THEN 'Multi-category'
                            ELSE MAX(bp.category)
                        END as category,
                        SUM(bp.planned_amount) as planned_amount,
                        bp.status, MAX(bp.created_at) as created_at, bp.start_date, bp.end_date,
                        COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id = MAX(bp.id)), 0) as actual_spent
                        FROM budget_plans bp 
                        $where 
                        GROUP BY bp.plan_code, bp.plan_name, bp.plan_year, bp.status, bp.plan_type, bp.start_date, bp.end_date
                        ORDER BY 
                            id DESC
                        LIMIT $offset, $per_page";
                
                $result = $conn->query($sql);
                
                $plans = [];
                $total_planned = 0;
                $total_actual = 0;
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $row['variance'] = $row['planned_amount'] - $row['actual_spent'];
                        $total_planned += $row['planned_amount'];
                        $total_actual += $row['actual_spent'];
                        $plans[] = $row;
                    }
                    $result->free();
                }
                
                echo json_encode([
                    'success' => true,
                    'plans' => $plans,
                    'total' => $total,
                    'page' => $page,
                    'pages' => $total_pages,
                    'total_planned' => $total_planned,
                    'total_actual' => $total_actual,
                    'total_variance' => $total_planned - $total_actual
                ]);
                exit;
                
                
            case 'load_proposals':
                $search = !empty($_GET['search']) ? $_GET['search'] : '';
                $filters = [];
                $params = [];
                $types = '';
                
                $global_filters = ["(plan_code IS NULL OR plan_code = '')"];
                $params = [];
                $types = '';
                
                if (!empty($search)) {
                    $global_filters[] = "(proposal_title LIKE ? OR proposal_code LIKE ? OR department LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $types .= 'sss';
                }
                
                if (!empty($_GET['department'])) {
                    $global_filters[] = "department = ?";
                    $params[] = $_GET['department'];
                    $types .= 's';
                }
                if (!empty($_GET['year']) && $_GET['year'] != '0') {
                    $global_filters[] = "fiscal_year = ?";
                    $params[] = (int)$_GET['year'];
                    $types .= 'i';
                }
                
                if (isset($_GET['my_proposals']) && $_GET['my_proposals'] == 'true' && isset($_SESSION['username'])) {
                    $global_filters[] = "submitted_by = ?";
                    $params[] = $_SESSION['username'];
                    $types .= 's';
                }

                $status_filters = [];
                $proposed_type = !empty($_GET['proposal_type']) ? strtolower($_GET['proposal_type']) : 'pending';
                
                switch ($proposed_type) {
                    case 'pending':
                        $status_filters[] = "status IN ('pending_review', 'submitted')";
                        break;
                    case 'approved':
                        $status_filters[] = "status IN ('approved', 'pending_executive', 'executive_approved')";
                        break;
                    case 'rejected':
                        $status_filters[] = "status = 'rejected'";
                        break;
                    default:
                        $status_filters[] = "status NOT IN ('archived', 'draft')";
                        break;
                }
                
                $where = (array_merge($global_filters, $status_filters)) ? "WHERE " . implode(" AND ", array_merge($global_filters, $status_filters)) : "";
                $stats_where = $global_filters ? "WHERE " . implode(" AND ", $global_filters) : "";
                
                // Get total count for pagination
                $count_sql = "SELECT COUNT(*) as total FROM budget_proposals $where";
                $stmt = $conn->prepare($count_sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $count_result = $stmt->get_result();
                $total = $count_result ? $count_result->fetch_assoc()['total'] : 0;
                $stmt->close();
                
                // Pagination
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per_page = 10;
                $offset = ($page - 1) * $per_page;
                $total_pages = $total > 0 ? ceil($total / $per_page) : 0;
                
                // Get proposals with pagination
                $sql = "SELECT * FROM budget_proposals $where 
                        ORDER BY 
                            FIELD(status, 'pending_review', 'submitted', 'approved', 'rejected'),
                            created_at DESC
                        LIMIT $offset, $per_page";
                
                $stmt = $conn->prepare($sql);
                if ($params) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $proposals = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        // Ensure all required fields exist with defaults
                        $row['proposal_title'] = $row['proposal_title'] ?? 'Untitled Proposal';
                        $row['proposal_code'] = $row['proposal_code'] ?? 'PROP-' . $row['id'];
                        $row['priority_level'] = $row['priority_level'] ?? 'medium';
                        $row['status'] = $row['status'] ?? 'draft';
                        $row['total_budget'] = floatval($row['total_budget'] ?? 0);
                        $row['requested_amount'] = $row['total_budget'];
                        
                        $proposals[] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
                
                // Get statistics
                $stats_sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN LOWER(status) IN ('pending_review', 'submitted') THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN LOWER(status) = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN LOWER(status) IN ('approved', 'pending_executive', 'executive_approved') THEN 1 ELSE 0 END) as approved,
                    SUM(total_budget) as total_requested
                    FROM budget_proposals $stats_where";
                
                $stmt_stats = $conn->prepare($stats_sql);
                if ($params) {
                    $stmt_stats->bind_param($types, ...$params);
                }
                $stmt_stats->execute();
                $stats_result = $stmt_stats->get_result();
                $stats = $stats_result ? $stats_result->fetch_assoc() : [];
                $stmt_stats->close();
                
                echo json_encode([
                    'success' => true,
                    'proposals' => $proposals,
                    'stats' => $stats,
                    'total' => $total,
                    'page' => $page,
                    'pages' => $total_pages
                ]);
                break;
                
            case 'get_proposal_details':
                if (empty($_GET['proposal_id'])) {
                    throw new Exception("Proposal ID is required");
                }
                
                $proposal_id = (int)$_GET['proposal_id'];
                
                $sql = "SELECT bp.*, 
                               gl.name as gl_account_name,
                               gl.code as gl_account_code_display
                        FROM budget_proposals bp 
                        LEFT JOIN chart_of_accounts_hierarchy gl ON bp.gl_account_code = gl.code AND gl.level = 4 
                        WHERE bp.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $proposal = $result->fetch_assoc();
                    $result->free();
                    
                    // Parse supporting docs JSON
                    if (!empty($proposal['supporting_docs'])) {
                        try {
                            $proposal['supporting_docs_array'] = json_decode($proposal['supporting_docs'], true);
                            if (!is_array($proposal['supporting_docs_array'])) {
                                $proposal['supporting_docs_array'] = [];
                            }
                        } catch (Exception $e) {
                            $proposal['supporting_docs_array'] = [];
                        }
                    } else {
                        $proposal['supporting_docs_array'] = [];
                    }
                    
                    // Parse detailed_breakdown JSON (from request_portal.php)
                    if (!empty($proposal['detailed_breakdown'])) {
                        try {
                            $proposal['detailed_breakdown_array'] = json_decode($proposal['detailed_breakdown'], true);
                            if (!is_array($proposal['detailed_breakdown_array'])) {
                                $proposal['detailed_breakdown_array'] = [];
                            } else {
                                // If we have a JSON breakdown, ensure total_budget matches the sum
                                $proposal['total_budget'] = array_sum(array_column($proposal['detailed_breakdown_array'], 'amount'));
                            }
                        } catch (Exception $e) {
                            $proposal['detailed_breakdown_array'] = [];
                        }
                    } else {
                        $proposal['detailed_breakdown_array'] = [];
                    }

                    // NEW: Link to Planning Breakdown if plan_code exists and no breakdown yet 
                    // OR try to auto-find a plan if one isn't specified but department has one
                    if (empty($proposal['detailed_breakdown_array'])) {
                        $p_batch_id = $proposal['plan_code'];
                        
                        // If no plan_code, try to find the one and only approved plan for this year
                        if (empty($p_batch_id)) {
                            $auto_plan_sql = "SELECT plan_code FROM budget_plans 
                                            WHERE status = 'approved' AND plan_year = ?
                                            GROUP BY plan_code";
                            $auto_stmt = $conn->prepare($auto_plan_sql);
                            $auto_stmt->bind_param("i", $proposal['fiscal_year']);
                            $auto_stmt->execute();
                            $auto_res = $auto_stmt->get_result();
                            if ($auto_res->num_rows === 1) {
                                $auto_p = $auto_res->fetch_assoc();
                                $p_batch_id = $auto_p['plan_code'];
                            }
                            $auto_stmt->close();
                        }

                        if (!empty($p_batch_id)) {
                            $breakdown_sql = "SELECT 
                                                bp.planned_amount as amount, 
                                                acc.name as account_name,
                                                bp.category,
                                                bp.sub_category as subcategory,
                                                bp.gl_account_code as account_code
                                            FROM budget_plans bp
                                            LEFT JOIN chart_of_accounts_hierarchy acc ON bp.gl_account_code = acc.code AND acc.level = 4
                                            WHERE bp.plan_code = ? 
                                            ORDER BY bp.category ASC, bp.sub_category ASC";
                            
                            if ($b_stmt = $conn->prepare($breakdown_sql)) {
                                $b_stmt->bind_param("s", $p_batch_id);
                                $b_stmt->execute();
                                $b_res = $b_stmt->get_result();
                                $breakdown = [];
                                while($b_row = $b_res->fetch_assoc()) {
                                    $breakdown[] = [
                                        'account_code' => $b_row['account_code'],
                                        'name' => $b_row['account_name'] ?: ('GL Account: ' . $b_row['account_code']),
                                        'category' => $b_row['category'],
                                        'subcategory' => $b_row['subcategory'],
                                        'amount' => (float)$b_row['amount']
                                    ];
                                }
                                $proposal['detailed_breakdown_array'] = $breakdown;
                                // If this was empty, update total budget to match plan if needed (or keep proposal total)
                                if (!empty($breakdown) && empty($proposal['detailed_breakdown'])) {
                                     // We keep the proposal's original total_budget but allow the UI to map it
                                }
                                $b_stmt->close();
                            }
                        }
                    }

                    // Get proposal items (fallback or for request_portal types)
                    $items_result = $conn->query("SELECT * FROM budget_proposal_items WHERE proposal_id = $proposal_id ORDER BY item_type, id");
                    $items = [];
                    if ($items_result) {
                        while ($item = $items_result->fetch_assoc()) {
                            $items[] = $item;
                        }
                        $items_result->free();
                    }
                    if (empty($proposal['detailed_breakdown_array'])) {
                        $proposal['items'] = $items;
                    }
                    
                    // Get comments
                    $comments_result = $conn->query("SELECT * FROM proposal_comments WHERE proposal_id = $proposal_id ORDER BY created_at DESC");
                    $comments = [];
                    if ($comments_result) {
                        while ($comment = $comments_result->fetch_assoc()) {
                            $comments[] = $comment;
                        }
                        $comments_result->free();
                    }
                    $proposal['comments'] = $comments;
                    
                    echo json_encode(['success' => true, 'proposal' => $proposal]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Proposal not found']);
                }
                $stmt->close();
                break;
                
            case 'get_plan_details':
                if (empty($_GET['plan_id'])) {
                    throw new Exception("Plan ID is required");
                }
                
                $plan_id = (int)$_GET['plan_id'];

                // Auto-fix: ensure live server has the same schema as local
                $required_live_cols = [
                    'justification_doc'  => "VARCHAR(255) NULL",
                    'justification_blob' => "LONGBLOB NULL",
                    'project_revenue'    => "DECIMAL(15,2) DEFAULT 0.00",
                    'impact_percentage'  => "DECIMAL(5,2) DEFAULT 0.00",
                    'taxation_adj'       => "DECIMAL(15,2) DEFAULT 0.00",
                    'start_date'         => "DATE NULL",
                    'end_date'           => "DATE NULL"
                ];
                foreach ($required_live_cols as $col => $def) {
                    $chk = $conn->query("SHOW COLUMNS FROM budget_plans LIKE '$col'");
                    if ($chk && $chk->num_rows === 0) {
                        $conn->query("ALTER TABLE budget_plans ADD $col $def");
                    }
                }
                
                // IMPORTANT: Never use SELECT * when table has LONGBLOB columns.
                // BLOB data in the result set corrupts the JSON response entirely.
                $sql = "SELECT id, plan_code, plan_name, department, category, sub_category,
                               plan_type, plan_year, plan_month, planned_amount, gl_account_code,
                               description, project_revenue, impact_percentage, taxation_adj,
                               start_date, end_date, created_by, approved_by, status,
                               justification_doc, created_at, updated_at
                        FROM budget_plans WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $plan_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $plan = $result->fetch_assoc();
                    $result->free();
                    
                    // Now get ALL records in this batch using plan_code
                    if (!empty($plan['plan_code'])) {
                        $p_batch_id = $plan['plan_code'];
                        $breakdown_sql = "SELECT 
                                            bp.planned_amount as amount, 
                                            acc.name as account_name,
                                            bp.category,
                                            bp.sub_category as subcategory,
                                            bp.gl_account_code as account_code
                                        FROM budget_plans bp
                                        LEFT JOIN chart_of_accounts_hierarchy acc ON bp.gl_account_code = acc.code AND acc.level = 4
                                        WHERE bp.plan_code = ? 
                                        ORDER BY bp.category ASC, bp.sub_category ASC";
                        
                        $b_stmt = $conn->prepare($breakdown_sql);
                        $b_stmt->bind_param("s", $p_batch_id);
                        $b_stmt->execute();
                        $b_res = $b_stmt->get_result();
                        $breakdown = [];
                        while($b_row = $b_res->fetch_assoc()) {
                            $breakdown[] = [
                                'account_code' => $b_row['account_code'],
                                'name' => $b_row['account_name'] ?: ('GL Account: ' . $b_row['account_code']),
                                'category' => $b_row['category'],
                                'subcategory' => $b_row['subcategory'],
                                'amount' => (float)$b_row['amount']
                            ];
                        }
                        $plan['breakdown'] = $breakdown;
                        // Recalculate total planned amount for the batch
                        $plan['planned_amount'] = array_sum(array_column($breakdown, 'amount'));
                        $b_stmt->close();
                    }
                    
                    if (ob_get_length()) ob_clean();
                    echo json_encode(['success' => true, 'plan' => $plan]);
                    exit;
                } else {
                    if (isset($stmt)) $stmt->close();
                    if (ob_get_length()) ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Plan not found']);
                    exit;
                }
                break;
                
            case 'get_gl_accounts_by_category':
                $category = $_GET['category'] ?? '';
                $sub_category = $_GET['sub_category'] ?? '';
                
                if (empty($category)) {
                    echo json_encode(['success' => false, 'message' => 'Category is required']);
                    exit();
                }
                
                $category_mapping = [
                    'Vehicle Operations' => 'Vehicle Expenses',
                    'Driver Costs' => 'Driver Expenses',
                    'Technology & Platform' => 'Technology Expenses',
                    'Marketing & Acquisition' => 'Marketing Expenses',
                    'Back Office & Support' => 'Office Expenses',
                    'Personnel & Workforce' => 'Personnel Expenses',
                    'Contingency' => 'Contingency Reserve',
                    'Accounts Payable' => 'Accounts Payable',
                    'Accrued Liabilities' => 'Accrued Liabilities',
                    'Long-term Liabilities' => 'Long-term Liabilities',
                    'Other Expenses' => 'Other Expenses'
                ];
                
                $gl_category = $category_mapping[$category] ?? $category;
                
                $sql = "SELECT a.code, a.name FROM chart_of_accounts_hierarchy a
                        LEFT JOIN chart_of_accounts_hierarchy sub ON a.parent_id = sub.id AND a.level = 4
                        LEFT JOIN chart_of_accounts_hierarchy cat ON sub.parent_id = cat.id AND sub.level = 3
                        WHERE a.level = 4 AND a.status = 'active' 
                        AND (a.type = 'Expense' OR a.type = 'Liability')";
                
                if (in_array($category, ['Vehicle Operations', 'Driver Costs', 'Technology & Platform', 
                    'Marketing & Acquisition', 'Back Office & Support', 'Personnel & Workforce', 'Contingency'])) {
                    $sql .= " AND cat.name LIKE ? ORDER BY a.code";
                    $search_term = "%$gl_category%";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $search_term);
                } else {
                    $sql .= " AND cat.name LIKE ? ORDER BY a.code";
                    $search_term = "%$gl_category%";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $search_term);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $accounts = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $accounts[] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
                
                echo json_encode(['success' => true, 'accounts' => $accounts]);
                break;
                
            case 'load_monitoring_data':
                $year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
                $department = !empty($_GET['department']) ? $_GET['department'] : null;
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per_page = 10;
                $offset = ($page - 1) * $per_page;
                
                $sql_base_where = " WHERE bp.plan_year = ? AND bp.status = 'approved'";
                $params = [$year];
                $types = "i";
                
                if ($department) {
                    $sql_base_where .= " AND bp.department = ?";
                    $params[] = $department;
                    $types .= "s";
                }
                
                // Get total count for pagination
                $count_sql = "SELECT COUNT(*) as total FROM (SELECT 1 FROM budget_plans bp $sql_base_where GROUP BY bp.category, bp.sub_category) as t";
                $count_stmt = $conn->prepare($count_sql);
                $count_stmt->bind_param($types, ...$params);
                $count_stmt->execute();
                $total_items = $count_stmt->get_result()->fetch_assoc()['total'];
                $count_stmt->close();

                $total_pages = ceil($total_items / $per_page);

                $sql = "SELECT 
                    COALESCE(bp.category, 'Uncategorized') as category,
                    COALESCE(bp.sub_category, 'Uncategorized') as gl_category,
                    SUM(bp.planned_amount) as planned,
                    COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id IN (SELECT id FROM budget_plans WHERE category = bp.category AND sub_category = bp.sub_category AND plan_year = ?)), 0) as actual,
                    COUNT(DISTINCT bp.department) as dept_count
                    FROM budget_plans bp
                    $sql_base_where
                    GROUP BY bp.category, bp.sub_category 
                    ORDER BY planned DESC
                    LIMIT ?, ?";
                
                $stmt = $conn->prepare($sql);
                $final_params = array_merge([$year], $params, [$offset, $per_page]);
                $final_types = "i" . $types . "ii";
                
                $stmt->bind_param($final_types, ...$final_params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $monitoring_data = [];
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $variance = $row['planned'] - $row['actual'];
                        $variance_percentage = $row['planned'] > 0 ? ($variance / $row['planned'] * 100) : 0;
                        $utilization = $row['planned'] > 0 ? ($row['actual'] / $row['planned'] * 100) : 0;
                        
                        $row['variance'] = $variance;
                        $row['variance_percentage'] = $variance_percentage;
                        $row['utilization'] = $utilization;
                        
                        $monitoring_data[] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
                
                // Get monitoring data by department
                $dept_sql = "SELECT 
                    bp.department,
                    SUM(bp.planned_amount) as planned,
                    COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id IN (SELECT id FROM budget_plans WHERE department = bp.department AND plan_year = ? AND status = 'approved')), 0) as actual,
                    COUNT(DISTINCT bp.category) as category_count
                    FROM budget_plans bp
                    WHERE bp.plan_year = ? AND bp.status = 'approved'
                    GROUP BY bp.department ORDER BY planned DESC";

                $dept_params = [$year, $year];
                $dept_types = "ii";
                
                if ($department) {
                    $dept_sql = "SELECT 
                        bp.department,
                        SUM(bp.planned_amount) as planned,
                        COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id IN (SELECT id FROM budget_plans WHERE department = bp.department AND plan_year = ? AND status = 'approved')), 0) as actual,
                        COUNT(DISTINCT bp.category) as category_count
                        FROM budget_plans bp
                        WHERE bp.plan_year = ? AND bp.status = 'approved' AND bp.department = ?
                        GROUP BY bp.department ORDER BY planned DESC";
                    $dept_params[] = $department;
                    $dept_types .= "s";
                }
                
                $dept_stmt = $conn->prepare($dept_sql);
                $dept_stmt->bind_param($dept_types, ...$dept_params);
                $dept_stmt->execute();
                $dept_result = $dept_stmt->get_result();
                
                $dept_monitoring_data = [];
                
                if ($dept_result) {
                    while ($row = $dept_result->fetch_assoc()) {
                        $variance = $row['planned'] - $row['actual'];
                        $variance_percentage = $row['planned'] > 0 ? ($variance / $row['planned'] * 100) : 0;
                        $utilization = $row['planned'] > 0 ? ($row['actual'] / $row['planned'] * 100) : 0;
                        
                        $row['variance'] = $variance;
                        $row['variance_percentage'] = $variance_percentage;
                        $row['utilization'] = $utilization;
                        
                        $dept_monitoring_data[] = $row;
                    }
                    $dept_result->free();
                }
                $dept_stmt->close();
                
                // Get data for chart (top 10 categories overall, not paginated)
                $chart_sql = "SELECT 
                    bp.category,
                    SUM(bp.planned_amount) as planned,
                    COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id IN (SELECT id FROM budget_plans WHERE category = bp.category AND plan_year = ?)), 0) as actual
                    FROM budget_plans bp
                    $sql_base_where
                    GROUP BY bp.category 
                    ORDER BY planned DESC
                    LIMIT 10";
                
                $chart_stmt = $conn->prepare($chart_sql);
                // Bind year for subquery and original params for where clause
                $chart_final_params = array_merge([$year], $params);
                $chart_final_types = "i" . $types;
                $chart_stmt->bind_param($chart_final_types, ...$chart_final_params);
                $chart_stmt->execute();
                $chart_result = $chart_stmt->get_result();
                $chart_data = [];
                while ($c_row = $chart_result->fetch_assoc()) {
                    $chart_data[] = $c_row;
                }
                $chart_stmt->close();

                // Get alerts
                $alerts_sql = "SELECT * FROM budget_alerts WHERE status = 'active' ORDER BY created_at DESC LIMIT 10";
                $alerts_result = $conn->query($alerts_sql);
                $alerts = [];
                if ($alerts_result) {
                    while ($alert = $alerts_result->fetch_assoc()) {
                        $alerts[] = $alert;
                    }
                    $alerts_result->free();
                }
                
                // Get forecasts
                $forecasts_sql = "SELECT * FROM budget_forecasts WHERE YEAR(forecast_period) = ? ORDER BY forecast_period DESC LIMIT 5";
                $forecast_stmt = $conn->prepare($forecasts_sql);
                $forecast_stmt->bind_param("i", $year);
                $forecast_stmt->execute();
                $forecast_result = $forecast_stmt->get_result();
                
                $forecasts = [];
                if ($forecast_result) {
                    while ($forecast = $forecast_result->fetch_assoc()) {
                        $forecast['variance'] = $forecast['forecasted_amount'] - $forecast['actual_amount'];
                        $forecast['variance_percentage'] = $forecast['forecasted_amount'] > 0 ? 
                            ($forecast['variance'] / $forecast['forecasted_amount'] * 100) : 0;
                        $forecasts[] = $forecast;
                    }
                    $forecast_result->free();
                }
                $forecast_stmt->close();
                
                echo json_encode([
                    'success' => true,
                    'monitoring_data' => $monitoring_data,
                    'chart_data' => $chart_data,
                    'dept_monitoring_data' => $dept_monitoring_data,
                    'alerts' => $alerts,
                    'forecasts' => $forecasts,
                    'pagination' => [
                        'total' => $total_items,
                        'page' => $page,
                        'pages' => $total_pages,
                        'per_page' => $per_page
                    ]
                ]);
                break;
                
            case 'get_department_stats':
                $year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
                
                $sql = "SELECT 
                    department,
                    COALESCE(SUM(planned_amount), 0) as budget,
                    COALESCE((SELECT SUM(actual_amount) FROM budget_plan_snapshots WHERE plan_id IN (SELECT id FROM budget_plans WHERE department = bp.department AND plan_year = ? AND status = 'approved')), 0) as spent
                    FROM budget_plans bp
                    WHERE bp.plan_year = ? AND bp.status = 'approved'
                    GROUP BY department
                    ORDER BY budget DESC";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $year, $year);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $departments = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $departments[] = $row;
                    }
                    $result->free();
                }
                $stmt->close();
                
                echo json_encode([
                    'success' => true,
                    'departments' => $departments,
                    'year' => $year
                ]);
                break;
                
            case 'get_stats':
                $stats = [
                    'total_plans' => 0,
                    'total_planned' => 0,
                    'approved_plans' => 0,
                    'draft_plans' => 0,
                    'pending_plans' => 0,
                    'total_actual' => 0,
                    'total_variance' => 0,
                    'remaining_budget' => 0,
                    'pending_proposals_review' => 0
                ];
                
                // Get budget stats (Only count APPROVED plans for the total budget)
                $result = $conn->query("SELECT 
                    COALESCE(SUM(CASE WHEN status = 'approved' THEN planned_amount ELSE 0 END), 0) as total_planned,
                    COUNT(DISTINCT CASE WHEN status = 'approved' THEN plan_code END) as approved_plans,
                    COUNT(DISTINCT CASE WHEN status = 'draft' THEN plan_code END) as draft_plans,
                    COUNT(DISTINCT CASE WHEN status = 'pending_review' THEN plan_code END) as pending_plans
                    FROM budget_plans WHERE status != 'archived'");
                
                if ($result) {
                    $data = $result->fetch_assoc();
                    $stats['total_planned'] = $data['total_planned'] ?? 0;
                    $stats['approved_plans'] = $data['approved_plans'] ?? 0;
                    $stats['draft_plans'] = $data['draft_plans'] ?? 0;
                    $stats['pending_plans'] = $data['pending_plans'] ?? 0;
                    $result->free();
                }
                
                // Get Actual Spending from General Ledger (Expenses) for Current Year
                $cur_year = date('Y');
                $actual_sql = "SELECT SUM(debit_amount) - SUM(credit_amount) as total_actual
                               FROM general_ledger
                               WHERE account_type = 'Expense'
                               AND YEAR(transaction_date) = $cur_year";

                $actual_result = $conn->query($actual_sql);
                if ($actual_result) {
                    $actual_data = $actual_result->fetch_assoc();
                    $stats['total_actual'] = (float)($actual_data['total_actual'] ?? 0);
                    $actual_result->free();
                }

                // Get Total Revenue from General Ledger for Current Year (for remaining/limit calc)
                $revenue_sql = "SELECT SUM(credit_amount) - SUM(debit_amount) as total_revenue
                                FROM general_ledger
                                WHERE account_type = 'Revenue'
                                AND YEAR(transaction_date) = $cur_year";
                
                $revenue_result = $conn->query($revenue_sql);
                $total_revenue = 0;
                if ($revenue_result) {
                    $rev_data = $revenue_result->fetch_assoc();
                    $total_revenue = (float)($rev_data['total_revenue'] ?? 0);
                    $revenue_result->free();
                }
                
                // Logic update: User has Requested:
                // 1. Total Revenue card shows 'Available Revenue' (Total Revenue - Approved Budget)
                // 2. Allocated Budget card shows 'Total Approved Plan Amount'
                // 3. When a plan is approved, Revenue card should decrease.
                
                $stats['total_revenue'] = $total_revenue - $stats['total_planned'];
                $stats['remaining_budget'] = $stats['total_revenue']; // Identical for now
                
                // Keep true revenue for internal limit tracking if needed in future stats
                $stats['raw_revenue'] = $total_revenue;
                
                // Get proposal stats
                $proposal_result = $conn->query("SELECT 
                    COUNT(*) as total_proposals,
                    SUM(CASE WHEN status IN ('pending_review', 'submitted') THEN 1 ELSE 0 END) as pending_review,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_proposals
                    FROM budget_proposals WHERE status != 'approved'");
                
                if ($proposal_result) {
                    $proposal_data = $proposal_result->fetch_assoc();
                    $stats['total_proposals'] = $proposal_data['total_proposals'] ?? 0;
                    $stats['pending_proposals_review'] = $proposal_data['pending_review'] ?? 0;
                    $stats['rejected_proposals'] = $proposal_data['rejected_proposals'] ?? 0;
                    $proposal_result->free();
                }
                
                echo json_encode(['success' => true, 'stats' => $stats]);
                break;
                
            case 'load_archived':
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                $per_page = 10;
                $offset = ($page - 1) * $per_page;
                
                $result = $conn->query("SELECT COUNT(*) as total FROM budget_plan_archive WHERE restored_at IS NULL OR restored = 0");
                $total = $result ? $result->fetch_assoc()['total'] : 0;
                $total_pages = $total > 0 ? ceil($total / $per_page) : 0;
                if ($result) $result->free();
                
                $result = $conn->query("SELECT a.id, a.plan_name, a.plan_type, MAX(a.archived_at) as archived_at, 
                                              a.archive_reason, SUM(a.planned_amount) as planned_amount, 
                                              gl.name as gl_account_name 
                                       FROM budget_plan_archive a 
                                       LEFT JOIN chart_of_accounts_hierarchy gl ON a.gl_account_code = gl.code AND gl.level = 4
                                       WHERE (a.restored_at IS NULL OR a.restored = 0)
                                       GROUP BY a.plan_name, a.archived_at
                                       ORDER BY archived_at DESC
                                       LIMIT $offset, $per_page");
                
                $archived = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $archived[] = $row;
                    }
                    $result->free();
                }
                
                echo json_encode([
                    'success' => true, 
                    'data' => $archived,
                    'total' => $total,
                    'page' => $page,
                    'pages' => $total_pages
                ]);
                break;

            case 'get_budget':
                $department = trim($_GET['department'] ?? $_POST['department'] ?? '');
                $year = intval($_GET['year'] ?? $_POST['year'] ?? date('Y'));
                
                if (empty($department)) {
                    echo json_encode(['success' => false, 'message' => 'Department is required']);
                    exit;
                }
                
                // Create robust search patterns
                $dept_normalized = preg_replace('/[^a-zA-Z0-9]/', '', $department);
                
                $response = ['success' => false, 'data' => null, 'gl_budgets' => [], 'gl_actuals' => []];
                
                // 1. Get Overall Budget stats (Global for the year)
                $budget_sql = "SELECT 
                                     SUM(planned_amount) as allocated,
                                     0 as spent,
                                     SUM(planned_amount) as remaining
                              FROM budget_plans 
                              WHERE status = 'approved'
                              AND plan_year = ?
                              LIMIT 1";
                
                $stmt = $conn->prepare($budget_sql);
                $stmt->bind_param("i", $year);
                $stmt->execute();
                $budget_result = $stmt->get_result();
                
                if ($budget_result->num_rows > 0) {
                    $budget = $budget_result->fetch_assoc();
                    $budget_result->free();
                    
                    // 2. Get GL-level budgets
                    $gl_stmt = $conn->prepare("SELECT gl_account_code, SUM(planned_amount) as allocated 
                                             FROM budget_plans 
                                             WHERE status = 'approved' AND plan_year = ? 
                                             GROUP BY gl_account_code");
                    $gl_stmt->bind_param("i", $year);
                    $gl_stmt->execute();
                    $gl_res = $gl_stmt->get_result();
                    $gl_mapping = [];
                    while ($row = $gl_res->fetch_assoc()) {
                        $gl_mapping[$row['gl_account_code']] = (float)$row['allocated'];
                    }
                    $gl_stmt->close();
                    
                    // 3. Get Last Year Actuals
                    $last_year = $year - 1;
                    $actuals_stmt = $conn->prepare("SELECT bp.gl_account_code, SUM(bps.actual_amount) as actual 
                                                  FROM budget_plan_snapshots bps
                                                  JOIN budget_plans bp ON bps.plan_id = bp.id
                                                  WHERE bp.plan_year = ? 
                                                  GROUP BY bp.gl_account_code");
                    $actuals_stmt->bind_param("i", $last_year);
                    $actuals_stmt->execute();
                    $actuals_res = $actuals_stmt->get_result();
                    $gl_actuals = [];
                    while ($row = $actuals_res->fetch_assoc()) {
                        $gl_actuals[$row['gl_account_code']] = (float)$row['actual'];
                    }
                    $actuals_stmt->close();
                    
                    // 4. Get spent amount from approved proposals (Global for the year)
                    $spent_stmt = $conn->prepare("SELECT SUM(COALESCE(adjusted_amount, total_budget)) as spent
                                                FROM budget_proposals
                                                WHERE status = 'approved' AND fiscal_year = ?");
                    $spent_stmt->bind_param("i", $year);
                    $spent_stmt->execute();
                    $spent_result = $spent_stmt->get_result();
                    
                    if ($spent_data = $spent_result->fetch_assoc()) {
                        $budget['spent'] = (float)($spent_data['spent'] ?? 0);
                        $budget['remaining'] = (float)$budget['allocated'] - $budget['spent'];
                    }
                    $spent_stmt->close();
                    
                    $response['success'] = true;
                    $response['data'] = $budget;
                    $response['gl_budgets'] = $gl_mapping;
                    $response['gl_actuals'] = $gl_actuals;
                } else {
                    $response['success'] = false;
                    $response['message'] = "No approved budget plan found for FY $year";
                    $response['gl_budgets'] = []; 
                    $response['gl_actuals'] = [];
                }
                $stmt->close();
                echo json_encode($response);
                exit;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid AJAX request']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Clean output buffer before HTML output
ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planning | Financial Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        .toast-container {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
        }
        
        .toast {
            animation: slideInRight 0.3s ease-out;
            margin-bottom: 0.75rem;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        
        .modal-content {
            max-height: 90vh;
            overflow-y: auto;
            z-index: 60;
            position: relative;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
            text-transform: capitalize;
        }

        /* Modern Additions */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .animate-fade-scale {
            animation: fadeInScale 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .amount-input-wrapper {
            display: flex;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            color: #4f46e5;
        }

        .amount-input-wrapper:focus-within {
            background-color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .amount-input-wrapper.is-error {
            background-color: #fef2f2 !important;
            border-color: #f87171 !important;
            color: #ef4444 !important;
        }

        .amount-input-wrapper.is-error:focus-within {
            background-color: #fff !important;
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15) !important;
        }

        .input-integer-only {
            flex: 1;
            min-width: 0;
            width: 100%;
            background: transparent;
            border: none;
            text-align: right;
            font-weight: 900;
            color: inherit;
            outline: none;
            padding: 0;
            font-size: 0.875rem;
            text-overflow: ellipsis;
        }

        .static-decimal {
            color: inherit;
            font-weight: 900;
            font-size: 0.875rem;
            margin-left: 1px;
            user-select: none;
        }

        .btn-premium {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            font-weight: 900;
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.3);
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        }

        .btn-premium:active {
            transform: scale(0.95);
        }

        .btn-premium:disabled {
            opacity: 0.5;
            filter: grayscale(1);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .modal-blur-bg {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        
        .badge-approved { 
            background-color: #d1fae5; 
            color: #065f46;
            border: 1px solid #10b981;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-archived { 
            background-color: #fef3c7; 
            color: #92400e;
            border: 1px solid #fbbf24;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-draft { 
            background-color: #e5e7eb; 
            color: #374151;
            border: 1px solid #9ca3af;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-submitted { 
            background-color: #dbeafe; 
            color: #1e40af;
            border: 1px solid #60a5fa;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-pending_review { 
            background-color: #fef3c7; 
            color: #92400e;
            border: 1px solid #fbbf24;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-rejected { 
            background-color: #fee2e2; 
            color: #991b1b;
            border: 1px solid #ef4444;
            border-radius: 15px;
            padding: 2px 9px;
        }
        
        .badge-priority-low { 
            background-color: #d1fae5; 
            color: #065f46;
            border: 1px solid #10b981;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-priority-medium { 
            background-color: #fef3c7; 
            color: #92400e;
            border: 1px solid #fbbf24;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-priority-high { 
            background-color: #ffedd5; 
            color: #9a3412;
            border: 1px solid #f97316;
            border-radius: 15px;
            padding: 2px 9px;
        }
        .badge-priority-critical { 
            background-color: #fee2e2; 
            color: #991b1b;
            border: 1px solid #ef4444;
            border-radius: 15px;
            padding: 2px 9px;
        }
        
        .metric-positive { color: #10B981; }
        .metric-negative { color: #EF4444; }
        .metric-neutral { color: #6B7280; }
        
        .file-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        
        .file-preview i {
            color: #9ca3af;
            margin-right: 8px;
        }
        
        .remove-file {
            color: #ef4444;
            cursor: pointer;
        }
        
        .remove-file:hover {
            color: #dc2626;
        }
        
        .proposal-tab {
            padding: 10px 20px;
            margin-right: 12px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 700;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 2px solid transparent;
            color: #f8fafc;
            background: #475569;
        }

        .proposal-tab:hover {
            background: #334155;
            color: white;
        }

        .proposal-tab.active {
            background-color: #f8fafc;
            border-color: #1e1b4b;
            color: #1e1b4b;
            box-shadow: 0 4px 12px rgba(30, 27, 75, 0.2);
        }

        .tab-button {
            position: relative;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 0.875rem;
            color: #334155;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            border: none;
        }

        .tab-button:hover {
            color: #312e81;
            background: rgba(49, 46, 129, 0.1);
        }

        .tab-button.active {
            color: white;
            background: linear-gradient(135deg, #312e81 0%, #4338ca 100%);
            box-shadow: 0 10px 20px -5px rgba(49, 46, 129, 0.5);
        }

        .tab-button i {
            font-size: 1rem;
            opacity: 0.8;
        }

        .tab-button.active i {
            opacity: 1;
        }

        .tab-button .counter {
            background: #334155;
            color: white;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 99px;
            transition: all 0.3s ease;
        }

        .tab-button.active .counter {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }
        
        .document-viewer {
            width: 100%;
            height: 500px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .document-embed {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .modal-backdrop {
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-body-container {
            max-height: calc(95vh - 120px);
            overflow-y: auto;
            padding: 2.5rem;
            background: white;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.75rem;
        }

        .section-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .input-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.75rem;
            letter-spacing: 0.05em;
        }

        .input-field {
            width: 100%;
            padding: 0.875rem 1.25rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 0.875rem;
            color: #1e293b;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
        }

        .input-field:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .metric-card {
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .metric-card.blue { background: #f0f7ff; border-color: #e0f2fe; }
        .metric-card.green { background: #f0fdf4; border-color: #dcfce7; }

        .upload-zone {
            border: 2px dashed #e2e8f0;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #ffffff;
        }

        .upload-zone:hover { border-color: #6366f1; background: #f8fafc; }

        .gl-mapping-container {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .gl-item {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }

        .gl-item:hover { background: #f8fafc; }

        .modal-footer {
            padding: 1.5rem 2.5rem;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 1.5rem;
            align-items: center;
        }

        .btn-ghost {
            color: #94a3b8;
            font-weight: 800;
            font-size: 0.875rem;
            text-transform: uppercase;
            padding: 0.875rem 1.5rem;
            letter-spacing: 0.05em;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            font-weight: 800;
            font-size: 0.875rem;
            text-transform: uppercase;
            padding: 0.875rem 2.5rem;
            border-radius: 14px;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            letter-spacing: 0.05em;
        }

        .btn-primary-gradient:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4); }
        .btn-primary-gradient:disabled { opacity: 0.5; transform: none; box-shadow: none; cursor: not-allowed; }
        
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto;
        }
        
        .step-active {
            background-color: #7c3aed;
            color: white;
        }
        
        .step-completed {
            background-color: #10b981;
            color: white;
        }
        
        .step-pending {
            background-color: #e5e7eb;
            color: #6b7280;
            border: 2px solid #9ca3af;
        }
        
        .cost-breakdown-chart {
            height: 300px;
            width: 100%;
        }
        
        .budget-timeline {
            background: linear-gradient(90deg, #7c3aed 0%, #a78bfa 100%);
            border-radius: 10px;
            padding: 20px;
            color: white;
        }
        
        .required-field::after {
            content: " *";
            color: #ef4444;
        }
        .active-receipt-tab {
            background-color: rgba(139, 92, 246, 0.2) !important;
            border-color: #8b5cf6 !important;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.5) !important;
        }
        
        .active-receipt-tab i {
            color: #a78bfa !important;
        }
        
        .receipt-tab-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        /* Filter Menu Styles (Matched with journal_entry.php) */
        .filter-menu {
            position: absolute;
            right: 0;
            margin-top: 0.75rem;
            width: 320px;
            background: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid #f1f5f9;
            padding: 1.5rem;
            z-index: 50;
        }
        
        .filter-btn-sleek {
            padding: 0.625rem 1rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
        }
        
        .filter-btn-sleek:hover {
            background: #f8fafc;
        }
        
        .filter-btn-sleek:active {
            transform: scale(0.95);
        }

        .filter-label {
            display: block;
            text-align: left;
            font-size: 10px;
            font-weight: 900;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 0.5rem;
            margin-left: 0.25rem;
        }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden">
    <?php if (file_exists('sidebar.php')) include('sidebar.php'); ?>
    
    <div class="<?php echo file_exists('sidebar.php') ? 'ml-6' : ''; ?> h-screen overflow-y-auto bg-gray-50 pb-20">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b top-0 z-10">
            <div class="px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Budget Planning</h1>
                        <p class="text-gray-600 mt-1">Manage budgets, proposals, and monitoring</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="badge" style="background-color: #e9d5ff; color: #6d28d9; border: 1px solid #a78bfa;">
                            FY <?php echo $current_year; ?>-<?php echo $current_year + 1; ?>
                        </span>
                        <div class="text-sm text-gray-600">
                            <?php echo $user_name; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="p-6">
            <!-- Stats Cards -->
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <!-- ID: totalRevenue -->
                <div class="bg-white rounded-lg shadow p-5 card-hover transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Available Revenue</p>
                            <p class="text-xl font-bold text-emerald-600 mt-2" id="totalRevenue">₱0.00</p>
                            <p class="text-[10px] text-gray-400 mt-1">
                                FYI <?php echo $current_year; ?> (Budget Limit)
                            </p>
                        </div>
                        <div class="p-2 bg-emerald-50 rounded-lg">
                            <i class="fas fa-hand-holding-usd text-emerald-500 text-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- ID: totalBudget -->
                <div class="bg-white rounded-lg shadow p-5 card-hover transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Allocated Budget</p>
                            <p class="text-xl font-bold text-gray-900 mt-2" id="totalBudget">₱0.00</p>
                            <p class="text-[10px] mt-1" id="totalBudgetMetric">
                                <span class="metric-neutral">vs Actual: ₱0.00</span>
                            </p>
                        </div>
                        <div class="p-2 bg-purple-50 rounded-lg">
                            <i class="fas fa-coins text-purple-500 text-lg"></i>
                        </div>
                    </div>
                </div>
                
                <!-- ID: remainingBudget -->
                <div class="bg-white rounded-lg shadow p-5 card-hover transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Remaining Budget</p>
                            <p class="text-xl font-bold text-gray-900 mt-2" id="remainingBudget">₱0.00</p>
                            <p class="text-[10px] mt-1" id="remainingBudgetMetric">
                                <span class="metric-neutral">0% of Revenue</span>
                            </p>
                        </div>
                        <div class="p-2 bg-blue-50 rounded-lg">
                            <i class="fas fa-wallet text-blue-500 text-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- ID: approvedPlans -->
                <div class="bg-white rounded-lg shadow p-5 card-hover transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Approved Plans</p>
                            <p class="text-xl font-bold text-gray-900 mt-2" id="approvedPlans">0</p>
                            <p class="text-[10px] mt-1" id="approvedPlansMetric">
                                <span class="metric-neutral">active plans</span>
                            </p>
                        </div>
                        <div class="p-2 bg-green-50 rounded-lg">
                            <i class="fas fa-check-circle text-green-500 text-lg"></i>
                        </div>
                    </div>
                </div>
                
                <!-- ID: pendingPlans -->
                <div class="bg-white rounded-lg shadow p-5 card-hover transition-all duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wide">Pending Review</p>
                            <p class="text-xl font-bold text-gray-900 mt-2" id="pendingPlans">0</p>
                            <p class="text-[10px] mt-1" id="pendingPlansMetric">
                                <span class="metric-neutral">needs action</span>
                            </p>
                        </div>
                        <div class="p-2 bg-yellow-50 rounded-lg">
                            <i class="fas fa-clock text-yellow-500 text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-3 p-1.5 bg-slate-200 rounded-2xl w-fit border border-slate-300">
                        <button onclick="switchTab('plans')" class="tab-button active">
                            <i class="fas fa-clipboard-list"></i>Budget Plans
                        </button>
                        <button onclick="switchTab('proposals')" class="tab-button">
                            <i class="fas fa-file-alt"></i>Proposals
                            <span class="counter" id="proposalCount">0</span>
                        </button>
                        <button onclick="switchTab('monitoring')" class="tab-button">
                            <i class="fas fa-chart-line"></i>Monitoring
                            <span class="counter" id="alertCount">0</span>
                        </button>
                        <button onclick="switchTab('archived')" class="tab-button">
                            <i class="fas fa-archive"></i>Archived
                        </button>
                    </nav>
                </div>
            </div>
            
            <!-- Budget Plans Tab -->
            <div id="plansTab" class="tab-content">
                <!-- Filter and Search -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Active Budget Plans</h3>
                            <div class="flex space-x-3">
                                <div class="relative">
                                    <button onclick="toggleFilterMenu(event, 'planFilterMenu')" class="filter-btn-sleek">
                                        <i class="fas fa-filter text-purple-600"></i>Filter
                                    </button>
                                    <!-- Plan Filter Menu -->
                                    <div id="planFilterMenu" class="filter-menu hidden">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="filter-label">Year</label>
                                                <select id="filterYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold">
                                                    <option value="0">All Years</option>
                                                    <?php foreach ($years as $year): ?>
                                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="filter-label">Status</label>
                                                <select id="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold" disabled>
                                                    <option value="approved" selected>Approved</option>
                                                </select>
                                            </div>
                                            <div class="flex gap-2 pt-2">
                                                <button onclick="resetFilters('plans')" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600">Reset</button>
                                                <button onclick="applyFilters('plans')" class="flex-[2] py-2 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-black">Apply</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="openModal('createPlanModal'); updateBudgetDates();" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 shadow-md text-sm font-bold flex items-center">
                                    <i class="fas fa-plus mr-2"></i>Create Plan
                                </button>
                            </div>
                        </div>
                        
                        <div class="relative mb-4">
                            <input type="text" id="searchInput" placeholder="Search plans..." 
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                   onkeyup="debounceSearch()">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Plans Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Budget</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="plansTableBody" class="divide-y divide-gray-200">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="plansLoading" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2 text-gray-600">Loading plans...</p>
                    </div>
                    
                    <div id="plansEmpty" class="hidden p-8 text-center">
                        <i class="fas fa-clipboard-list text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No budget plans found</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-white">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700" id="plansPaginationInfo">
                                Showing 0 to 0 of 0 entries
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="previousPage('plans')" id="plansPrevBtn" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </button>
                                <button onclick="nextPage('plans')" id="plansNextBtn"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Proposals Tab -->
            <div id="proposalsTab" class="tab-content hidden">
                <!-- Proposal Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Total Proposas</p>
                        <p class="text-xl font-bold" id="totalProposals">0</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-xl font-bold" id="pendingProposals">0</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4 border-l-4 border-emerald-500">
                        <p class="text-sm text-gray-600">Approved</p>
                        <p class="text-xl font-bold text-emerald-600" id="approvedStatCount">0</p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Rejected</p>
                        <p class="text-xl font-bold" id="rejectedProposals">0</p>
                    </div>
                </div>
                
                <!-- Proposal Actions -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Budget Proposals</h3>
                                <p class="text-gray-600 mt-1">Create and manage budget proposals</p>
                            </div>
                            <div class="flex space-x-3">
                                <div class="relative">
                                    <button onclick="toggleFilterMenu(event, 'proposalFilterMenu')" class="filter-btn-sleek">
                                        <i class="fas fa-filter text-purple-600"></i>Filter
                                    </button>
                                    <!-- Proposal Filter Menu -->
                                    <div id="proposalFilterMenu" class="filter-menu hidden">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="filter-label">Department</label>
                                                <select id="proposalFilterDepartment" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold">
                                                    <option value="">All Departments</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="filter-label">Year</label>
                                                <select id="proposalFilterYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold">
                                                    <option value="0">All Years</option>
                                                    <?php foreach ($years as $year): ?>
                                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="flex gap-2 pt-2">
                                                <button onclick="resetFilters('proposals')" class="flex-1 py-2 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-gray-600">Reset</button>
                                                <button onclick="applyFilters('proposals')" class="flex-[2] py-2 bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest rounded-xl hover:bg-black">Apply</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button onclick="toggleMyProposals()" id="myProposalsBtn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all font-bold text-sm flex items-center">
                                    <i class="fas fa-user mr-2 text-blue-500"></i>My Proposals
                                </button>
                                <button onclick="resetProposalForm(); openModal('newProposalModal')" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all font-bold text-sm flex items-center">
                                    <i class="fas fa-plus mr-2"></i>New Proposal
                                </button>
                            </div>
                        </div>
                        
                        <!-- Proposal Type Tabs -->
                        <div class="flex mb-4 border-b border-gray-200">
                            <div class="proposal-tab active" onclick="switchProposalType('pending')" id="tabPending">Pending Proposals</div>
                            <div class="proposal-tab" onclick="switchProposalType('approved')" id="tabApproved">Approved Proposals</div>
                            <div class="proposal-tab" onclick="switchProposalType('rejected')" id="tabRejected">Rejected Proposals</div>
                        </div>
                        
                        <div class="relative mb-4">
                            <input type="text" id="proposalSearchInput" placeholder="Search proposals..." 
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                   onkeyup="debounceProposalSearch()">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Proposals List -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="proposalsTableBody" class="divide-y divide-gray-200">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="proposalsLoading" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2 text-gray-600">Loading proposals...</p>
                    </div>
                    
                    <div id="proposalsEmpty" class="hidden p-8 text-center">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-600">No proposals found</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-white">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700" id="proposalsPaginationInfo">
                                Showing 0 to 0 of 0 entries
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="previousPage('proposals')" id="proposalsPrevBtn" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </button>
                                <button onclick="nextPage('proposals')" id="proposalsNextBtn"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Monitoring Tab -->
            <div id="monitoringTab" class="tab-content hidden">
                <!-- View Toggle -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <label class="text-sm font-medium text-gray-700">View By:</label>
                            <div class="flex space-x-2">
                                <button id="categoryViewBtn" onclick="toggleMonitoringView('category')" 
                                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-tags mr-2"></i>Category
                                </button>
                                <button id="departmentViewBtn" onclick="toggleMonitoringView('department')" 
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                    <i class="fas fa-building mr-2"></i>Department
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Monitoring Overview -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <div class="lg:col-span-2">
                        <!-- Budget Utilization by Category -->
                        <div id="categoryChartSection" class="bg-white rounded-lg shadow p-6 mb-6">
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Budget Utilization by Category</h4>
                                <div class="flex space-x-3">
                                    <select id="monitoringYear" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select id="monitoringDepartment" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas id="monitoringChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Departmental Budget vs Actual Spent -->
                        <div id="departmentChartSection" class="bg-white rounded-lg shadow p-6 mb-6 hidden">
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Departmental Budget vs Actual Spent</h4>
                                <div class="flex space-x-3">
                                    <select id="departmentGraphYear" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas id="departmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Alerts</h4>
                                <span class="badge" style="background-color: #fee2e2; color: #991b1b; border: 1px solid #ef4444;" id="alertsCount">0</span>
                            </div>
                            <div id="alertsList" class="space-y-4 max-h-64 overflow-y-auto">
                                <!-- Alerts loaded via AJAX -->
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h4 class="text-lg font-semibold text-gray-900">Forecasts</h4>
                                <button onclick="openModal('forecastModal')" class="px-3 py-1 bg-purple-100 text-purple-800 rounded-lg text-sm hover:bg-purple-200 transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Add
                                </button>
                            </div>
                            <div id="forecastsList" class="space-y-4">
                                <!-- Forecasts loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Monitoring by GL Category -->
                <!-- Budget Monitoring by Category Table -->
                <div id="categoryTableSection" class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Budget Monitoring by Category</h4>
                            <p class="text-gray-600 text-sm mt-1">Planned vs Actual spending across categories</p>
                        </div>
                        <div class="flex space-x-3">
                            <select id="monitoringTableYear" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="monitoringTableDepartment" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">GL Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departments</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planned</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Spent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                                </tr>
                            </thead>
                            <tbody id="monitoringTableBody" class="divide-y divide-gray-200">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="monitoringPagination" class="flex items-center justify-between mt-6 px-2">
                        <div class="text-sm text-gray-500" id="monitoringPaginationInfo">
                            Showing 0 to 0 of 0 entries
                        </div>
                        <div class="flex space-x-2">
                            <button id="monitoringPrevBtn" onclick="changeMonitoringPage(-1)" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                                <i class="fas fa-chevron-left mr-2"></i>Prev
                            </button>
                            <button id="monitoringNextBtn" onclick="changeMonitoringPage(1)" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                                Next<i class="fas fa-chevron-right ml-2"></i>
                            </button>
                        </div>
                    </div>

                    <div id="monitoringLoading" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2 text-gray-600">Loading monitoring data...</p>
                    </div>
                </div>
                
                <!-- Budget Monitoring by Department Table -->
                <div id="departmentTableSection" class="bg-white rounded-lg shadow overflow-hidden mb-6 hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900">Budget Monitoring by Department</h4>
                                <p class="text-gray-600 text-sm mt-1">Planned vs Actual spending by department</p>
                            </div>
                            <div class="flex space-x-3">
                                <select id="deptMonitoringYear" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <?php foreach ($years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select id="deptMonitoringDepartment" class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categories</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Planned</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Spent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilization</th>
                                </tr>
                            </thead>
                            <tbody id="deptMonitoringTableBody" class="divide-y divide-gray-200">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="deptMonitoringLoading" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2 text-gray-600">Loading department monitoring data...</p>
                    </div>
                </div>
            </div>
            
            <!-- Archived Tab -->
            <div id="archivedTab" class="tab-content hidden">
                <!-- Archived Actions -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Archived Plans</h3>
                                <p class="text-gray-600 mt-1">Historical budget plans</p>
                            </div>
                            <div class="flex space-x-3">
                                <button onclick="toggleFilterPanel('archivedFilterPanel')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-filter mr-2"></i>Filter
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filter Panel -->
                        <div id="archivedFilterPanel" class="hidden">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                    <select id="archivedFilterDepartment" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                                    <select id="archivedFilterYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="0">All Years</option>
                                        <?php foreach ($years as $year): ?>
                                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Archive Reason</label>
                                    <select id="archivedFilterReason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                        <option value="">All Reasons</option>
                                        <option value="completed">Project Completed</option>
                                        <option value="cancelled">Project Cancelled</option>
                                        <option value="superseded">Superseded by New Plan</option>
                                        <option value="obsolete">No Longer Relevant</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Archived Plans Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archived Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="archivedTableBody" class="divide-y divide-gray-200">
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="archivedLoading" class="p-8 text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        <p class="mt-2 text-gray-600">Loading archived data...</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 bg-white">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700" id="archivedPaginationInfo">
                                Showing 0 to 0 of 0 entries
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="previousPage('archived')" id="archivedPrevBtn" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </button>
                                <button onclick="nextPage('archived')" id="archivedNextBtn"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Plan Modal (Review Modal) -->
    <div id="viewPlanModal" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-6xl overflow-hidden transform transition-all animate-fade-scale shadow-indigo-100/50 flex flex-col relative z-30" style="max-height: 95vh;">
                
                <!-- Modal Header -->
                <div class="flex justify-between items-center px-8 py-6 bg-gradient-to-r from-purple-700 to-indigo-800 text-white shrink-0">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                            <i class="fas fa-eye text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 id="viewPlanTitle" class="text-xl font-bold tracking-tight text-white">Plan Details</h3>
                            <p id="viewPlanCode" class="text-[10px] font-bold text-indigo-200 uppercase tracking-widest"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div id="viewPlanStatusBadge" class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-white/20 text-white border border-white/30 backdrop-blur-sm"></div>
                        <button onclick="closeModal('viewPlanModal')" class="w-10 h-10 flex items-center justify-center rounded-xl text-indigo-200 hover:bg-white/10 hover:text-white transition-all">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="bg-white border-b border-gray-100 px-8 shrink-0">
                    <div class="flex gap-2">
                        <button onclick="switchViewPlanTab('details')" id="viewPlanTab-details" class="px-6 py-4 font-bold text-sm border-b-2 border-purple-600 text-purple-700 bg-purple-50/50 transition-all">
                            <i class="fas fa-chart-pie mr-1.5 opacity-70"></i> Details & Allocation
                        </button>
                        <button onclick="switchViewPlanTab('document')" id="viewPlanTab-document" class="hidden px-6 py-4 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all">
                            <i class="fas fa-paperclip mr-1.5 opacity-70"></i> Attached Document
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden relative bg-gray-50 min-h-[70vh]">
                    <!-- DETAILS TAB -->
                    <div id="viewPlanContent-details" class="absolute inset-0 overflow-y-auto custom-scrollbar p-8">
                        <div class="grid grid-cols-12 gap-12">
                            <!-- LEFT COLUMN: SUMMARY DETAILS -->
                            <div class="col-span-12 lg:col-span-5 space-y-8 bg-gradient-to-br from-indigo-50/80 to-purple-50/50 p-8 rounded-[2rem] border border-indigo-100/60 shadow-inner">
                                <div class="section-header">
                                    <div class="section-dot bg-purple-500"></div>
                                    <h4 class="section-title text-indigo-900">Plan Summary</h4>
                                </div>

                                <!-- Highlights -->
                                <div class="bg-white/80 backdrop-blur-sm p-6 rounded-2xl border border-indigo-100/50 space-y-4 shadow-sm">
                                    <div>
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">Total Planned Budget</p>
                                        <p id="viewPlanAmount" class="text-3xl font-black text-slate-800 tracking-tighter"></p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-indigo-100">
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Effective Year</p>
                                            <p id="viewPlanYear" class="text-sm font-black text-slate-800"></p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Planning Type</p>
                                            <p id="viewPlanType" class="text-sm font-black text-slate-800 uppercase"></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-6 bg-white/60 p-5 rounded-2xl border border-white">
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Start Date</p>
                                        <p id="viewPlanStartDate" class="text-sm font-bold text-indigo-700"></p>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">End Date</p>
                                        <p id="viewPlanEndDate" class="text-sm font-bold text-indigo-700"></p>
                                    </div>
                                </div>

                                <!-- Financial Metrics -->
                                <div class="grid grid-cols-3 gap-4 p-4 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl text-white shadow-md">
                                    <div class="text-center space-y-1">
                                        <p class="text-[8px] font-black text-indigo-200 uppercase tracking-widest">Revenue (₱)</p>
                                        <p id="viewPlanRevenue" class="text-xs font-black"></p>
                                    </div>
                                    <div class="text-center space-y-1 border-x border-indigo-400/30">
                                        <p class="text-[8px] font-black text-indigo-200 uppercase tracking-widest">Impact (%)</p>
                                        <p id="viewPlanImpact" class="text-xs font-black"></p>
                                    </div>
                                    <div class="text-center space-y-1">
                                        <p class="text-[8px] font-black text-indigo-200 uppercase tracking-widest">Taxation (₱)</p>
                                        <p id="viewPlanTaxation" class="text-xs font-black"></p>
                                    </div>
                                </div>

                                <!-- Rationale -->
                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-indigo-400 uppercase tracking-widest block">Strategic Rationale</label>
                                    <div class="bg-white/80 p-5 rounded-2xl border border-indigo-100/50 min-h-[120px] shadow-sm">
                                        <p id="viewPlanRationale" class="text-sm text-slate-700 italic leading-relaxed"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN: ALLOCATION BREAKDOWN -->
                            <div class="col-span-12 lg:col-span-7 space-y-8 flex flex-col h-[calc(100vh-280px)]">
                                <div class="flex items-center justify-between border-b border-gray-200 pb-4 shrink-0">
                                    <div class="section-header mb-0">
                                        <div class="section-dot bg-emerald-500"></div>
                                        <h4 class="section-title text-gray-800">Account Allocation Summary</h4>
                                    </div>
                                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-full uppercase tracking-widest border border-emerald-200">Verified Breakdown</span>
                                </div>

                                <div class="border border-gray-200 rounded-2xl overflow-hidden shadow-sm bg-white flex-1 flex flex-col min-h-0">
                                    <div class="overflow-y-auto custom-scrollbar flex-1">
                                        <table class="w-full text-left">
                                            <thead class="bg-gray-50/80 backdrop-blur-sm sticky top-0 z-10 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-6 py-4 text-[10px] uppercase font-black text-gray-500 tracking-widest">Account Breakdown & Details</th>
                                                    <th class="px-6 py-4 text-right text-[10px] uppercase font-black text-gray-500 tracking-widest">Allocated Value</th>
                                                </tr>
                                            </thead>
                                            <tbody id="viewPlanBreakdownBody" class="divide-y divide-gray-100 bg-white">
                                                <!-- Rows will be injected here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Breakdown Summary -->
                                <div class="bg-gradient-to-r from-purple-700 to-indigo-800 p-6 rounded-2xl text-white flex items-center justify-between shadow-xl shadow-indigo-200/50 shrink-0">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                            <i class="fas fa-calculator text-lg text-white"></i>
                                        </div>
                                        <div>
                                            <p class="text-[9px] font-black text-indigo-200 uppercase tracking-widest">Consolidated Final Total</p>
                                            <p id="viewPlanFooterAmount" class="text-3xl font-black tracking-tighter text-white"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOCUMENT TAB -->
                    <div id="viewPlanContent-document" class="hidden absolute inset-0 bg-gray-900 flex flex-col">
                        <div class="flex-1 flex items-center justify-center relative w-full h-full">
                            <iframe id="viewPlanPdfFrame" class="absolute inset-0 w-full h-full border-0 bg-white" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-8 py-6 bg-white border-t border-gray-100 flex justify-end gap-3 shrink-0 rounded-b-[2.5rem]">
                    <button onclick="window.print()" class="px-6 py-2 bg-white text-gray-600 border border-gray-200 rounded-xl font-bold text-sm hover:bg-gray-50 transition-all flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Summary
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Create Plan Modal -->
    <div id="createPlanModal" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px);">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-6xl overflow-hidden transform transition-all animate-fade-scale shadow-indigo-100/50">
                
                <!-- Modal Header -->
                <div class="bg-white px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-100">
                            <i class="fas fa-file-invoice-dollar text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Create New Budget Plan</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Strategic Financial Roadmap</p>
                        </div>
                    </div>
                    <button onclick="closeModal('createPlanModal')" class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-400 hover:bg-slate-50 hover:text-slate-600 transition-all">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <form id="createPlanForm" action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create_plan">
                    <input type="hidden" name="plan_type" id="plan_type_input" value="yearly">
                    <input type="hidden" name="total_budget" value="0">

                    <div class="modal-body-container custom-scrollbar">
                        <div class="grid grid-cols-12 gap-12">
                            
                            <!-- LEFT COLUMN: BUDGET DETAILS -->
                            <div class="col-span-12 lg:col-span-5 space-y-8 bg-gradient-to-br from-indigo-100 via-purple-50 to-pink-50 p-8 rounded-[2rem] border border-indigo-200/60 shadow-inner">
                                <div class="section-header">
                                    <div class="section-dot bg-indigo-500"></div>
                                    <h4 class="section-title text-indigo-900">Budget Details</h4>
                                </div>

                                <div class="input-group">
                                    <label>Budget Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="budget_title" required class="input-field" placeholder="e.g. FY 2025 Strategic Operations" oninput="validateField(this)">
                                    <p class="text-[10px] text-red-500 mt-1 hidden validation-msg">Budget Title is required.</p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="input-group">
                                        <label>Planning Type<span class="text-red-500">*</span></label>
                                        <select class="input-field" onchange="setBudgetType(this, this.value.toLowerCase().includes('yearly') ? 'yearly' : 'monthly')">
                                            <option value="yearly">Yearly Plan</option>
                                            <option value="monthly">Monthly Plan</option>
                                        </select>
                                    </div>
                                    <div class="input-group">
                                        <label>Effective Year<span class="text-red-500">*</span></label>
                                        <select name="fiscal_year" id="modal_fiscal_year" class="input-field" onchange="updateBudgetDates()">
                                            <?php 
                                            $current_year = (int)date('Y');
                                            foreach ($years as $year): 
                                                if ($year < $current_year) continue; // Disable past years
                                            ?>
                                            <option value="<?php echo $year; ?>" <?php echo $year == $current_year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="input-group">
                                        <label>Start Date<span class="text-red-500">*</span></label>
                                        <input type="date" id="modal_start_date" name="start_date" required class="input-field" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" oninput="validateField(this)">
                                    </div>
                                    <div class="input-group">
                                        <label>End Date<span class="text-red-500">*</span></label>
                                        <input type="date" id="modal_end_date" name="end_date" required class="input-field" min="<?php echo date('Y-m-d'); ?>" oninput="validateField(this)">
                                    </div>
                                </div>

                                <input type="hidden" name="total_budget" id="total_budget_display" value="0">

                                <!-- Attachment -->
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="input-group">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Attachment<span class="text-red-500">*</span></label>
                                        <div class="upload-zone group p-3 border-2 border-dashed border-slate-300 rounded-xl hover:border-indigo-400 hover:bg-indigo-50 transition-all cursor-pointer" onclick="document.getElementById('planFile').click()">
                                            <input type="file" id="planFile" name="plan_file" class="hidden" required onchange="previewFile(this); validateField(this)">
                                            <div class="text-center">
                                                <div id="file-preview-icon" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-2 group-hover:bg-indigo-100 group-hover:text-indigo-500 transition-all">
                                                    <i class="fas fa-cloud-upload-alt text-sm"></i>
                                                </div>
                                                <p class="text-[9px] font-bold text-indigo-600 mb-0.5 truncate px-1" id="file-preview-name">Upload Justification</p>
                                                <p class="text-[7px] text-slate-400" id="file-preview-size">PDF, XL, IMG</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="input-group">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Project Revenue (₱)</label>
                                    <div class="amount-input-wrapper">
                                        <span class="text-slate-400 font-bold text-[10px] mr-1">₱</span>
                                        <input type="text" name="project_revenue" class="input-integer-only" placeholder="0" value="0" 
                                               oninput="formatIntegerInput(this); validateField(this)"
                                               onblur="syncIntegerDisplay(this)">
                                        <span class="static-decimal">.00</span>
                                    </div>
                                    <p class="text-[9px] text-emerald-600 mt-1"><i class="fas fa-lightbulb"></i> Auto-suggested at 115% of total budget (editable)</p>
                                </div>

                                <!-- Impact Percentage Mix -->
                                <div class="input-group">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Impact Percentage Mix (%)</label>
                                    <div class="amount-input-wrapper">
                                        <span class="text-slate-400 font-bold text-[10px] mr-1" style="visibility: hidden;">₱</span>
                                        <input type="text" name="impact_percentage" class="input-integer-only" placeholder="0" value="0"
                                               oninput="formatIntegerInput(this); validateField(this)"
                                               onblur="syncIntegerDisplay(this)">
                                        <span class="static-decimal">%</span>
                                    </div>
                                    <p class="text-[9px] text-emerald-600 mt-1"><i class="fas fa-lightbulb"></i> Auto-suggested based on budget size (editable)</p>
                                </div>

                                <!-- Taxation Adjustment -->
                                <div class="input-group">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Taxation Adjustment (₱)</label>
                                    <div class="amount-input-wrapper">
                                        <span class="text-slate-400 font-bold text-[10px] mr-1">₱</span>
                                        <input type="text" name="taxation_adj" class="input-integer-only" placeholder="0" value="0"
                                               oninput="formatIntegerInput(this); validateField(this)"
                                               onblur="syncIntegerDisplay(this)">
                                        <span class="static-decimal">.00</span>
                                    </div>
                                    <p class="text-[9px] text-emerald-600 mt-1"><i class="fas fa-lightbulb"></i> Auto-suggested at 12% VAT (editable)</p>
                                </div>
                            </div>
                            <!-- RIGHT COLUMN: ALLOCATION -->
                            <div class="col-span-12 lg:col-span-7 flex flex-col h-full bg-white p-2 rounded-3xl">
                                <div class="section-header mb-6 px-6">
                                    <div class="section-dot bg-emerald-500"></div>
                                    <div class="flex-1 flex justify-between items-center">
                                        <h4 class="section-title">Budget Allocation Grid</h4>
                                        <span class="text-[10px] font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full uppercase tracking-widest flex items-center gap-2">
                                            <span>Total: <span id="current-mapping-total">₱0.00</span></span>
                                            <span class="w-1 h-1 rounded-full bg-emerald-200"></span>
                                            <span>Available: <span id="modal-available-budget">₱0.00</span></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- GL Account Mapping Grid -->
                                <div class="input-group mb-4">
                                    <div class="relative">
                                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                        <input type="text" id="glAccountSearch" placeholder="Search accounts or codes..." 
                                               class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:bg-white focus:border-indigo-500 transition-all outline-none"
                                               oninput="filterGLAllocationList(this.value)">
                                    </div>
                                </div>
                                
                                <div class="gl-allocations-wrapper bg-slate-50 border border-slate-200 rounded-xl overflow-hidden flex flex-col" style="height: 520px;">
                                    <div id="glAllocationList" class="flex-1 overflow-y-auto p-4 space-y-6 custom-scrollbar">
                                        <?php if (empty($gl_categories)): ?>
                                            <div class="p-12 text-center">
                                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-slate-300 mx-auto mb-4">
                                                    <i class="fas fa-database text-2xl"></i>
                                                </div>
                                                <p class="text-slate-400 italic text-sm">No GL accounts found. Please check COA setup.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($gl_categories as $cat): ?>
                                                <div class="category-block" data-cat-id="<?php echo $cat['id']; ?>">
                                                    <div class="flex items-center space-x-2 mb-3 py-1">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                                                        <span class="text-[11px] font-black text-slate-700 uppercase tracking-widest"><?php echo htmlspecialchars($cat['name']); ?></span>
                                                    </div>
                                                    
                                                    <div class="space-y-4 pl-3 border-l-2 border-slate-200 ml-1">
                                                    <?php if (isset($gl_subcategories[$cat['id']])): ?>
                                                        <?php foreach ($gl_subcategories[$cat['id']] as $sub): ?>
                                                            <div class="subcategory-block mb-3" data-sub-id="<?php echo $sub['id']; ?>" data-searchable="<?php echo strtolower($sub['name']); ?>">
                                                                <div class="flex items-center justify-between p-3 bg-white border border-slate-200 rounded-xl hover:border-indigo-400 hover:shadow-md transition-all group">
                                                                    <div class="flex items-center">
                                                                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-500 mr-3 group-hover:bg-indigo-500 group-hover:text-white transition-all">
                                                                            <i class="fas fa-layer-group text-xs"></i>
                                                                        </div>
                                                                        <div class="flex flex-col">
                                                                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Subcategory</span>
                                                                            <span class="text-xs font-black text-slate-700"><?php echo htmlspecialchars($sub['name']); ?></span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="amount-input-wrapper flex-1 max-w-[240px] min-w-[160px]">
                                                                        <span class="text-slate-400 font-bold text-[10px] mr-1">₱</span>
                                                                        <input type="text" placeholder="0"
                                                                               class="input-integer-only"
                                                                               oninput="formatIntegerInput(this); distributeSubcategoryBudget('<?php echo $sub['id']; ?>', this.value)"
                                                                               onblur="syncIntegerDisplay(this)">
                                                                        <span class="static-decimal">.00</span>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="space-y-1.5">
                                                                <?php if (isset($gl_accounts_by_sub[$sub['id']])): ?>
                                                                    <?php foreach ($gl_accounts_by_sub[$sub['id']] as $acc): ?>
                                                                        <div class="gl-account-row hidden" 
                                                                             data-searchable="<?php echo strtolower($acc['code'] . ' ' . $acc['name']); ?>">
                                                                            <input type="number" 
                                                                                   name="gl_allocation[<?php echo $acc['code']; ?>]" 
                                                                                   step="0.01" 
                                                                                   class="gl-input-actual">
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="bg-indigo-50 p-3 border-t border-indigo-100 flex justify-between items-center">
                                        <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest">Calculated Total Budget</span>
                                        <span id="calculated-total-summary" class="text-sm font-black text-indigo-800">₱0.00</span>
                                    </div>
                                </div>
                                
                                <!-- Strategic Rationale (moved below) -->
                                <div class="input-group mt-6">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 block">Strategic Rationale & Objectives<span class="text-red-500">*</span></label>
                                    <textarea name="justification" rows="4" required class="input-field resize-none" placeholder="Describe the core objectives of this plan..." oninput="validateField(this)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="modal-footer px-8 py-6 bg-slate-50 border-t border-slate-100 mt-8 flex justify-end gap-3">
                        <button type="button" onclick="closeModal('createPlanModal')" class="px-6 py-3 rounded-2xl text-slate-500 font-black uppercase tracking-widest text-[10px] hover:bg-slate-200 transition-all">Cancel</button>
                        <button type="submit" id="submit-btn" class="btn-premium" disabled>
                            Allocate Amounts <i class="fas fa-lock ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .animate-fade-in-up { animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    .animate-shake { animation: shake 0.2s ease-in-out 0s 2; }

    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

    .wizard-pane.active { display: block; }
    .wizard-pane { display: none; }

    .step-circle.active div {
        background: #4f46e5;
        border-color: #4f46e5;
        color: white;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }
    .step-circle.completed div {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    .step-circle.completed span { color: #10b981; }

    #revenue-circle-progress, #equity-circle-progress, #assets-circle-progress {
        transition: stroke-dashoffset 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    }
    </style>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .premium-input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .premium-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }
        .section-container {
            border-radius: 12px;
            padding: 24px;
        }
        .section-yellow { background: #fffcf0; border-color: #fef3c7; }
        .section-green { background: #f0fdf4; border-color: #dcfce7; }
        .section-blue { background: #f8fbff; border-color: #dbeafe; }
        .section-gray { background: #f9fafb; border-color: #f3f4f6; }
        
        .budget-type-btn.active {
            background: #4f46e5 !important;
            color: white !important;
            border-color: #4f46e5 !important;
        }
        .major-account-item {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 16px;
            align-items: center;
        }
        .drag-drop-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #fdfdfd;
            transition: all 0.3s;
            cursor: pointer;
        }
        .drag-drop-area:hover {
            border-color: #4f46e5;
            background: #f5f3ff;
        }
    </style>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>
    
    <!-- Proposal Review Modal -->
    
    <!-- Modals -->
    
    <!-- New Proposal Modal (Consolidated Single Page) -->
    <div id="newProposalModal" class="fixed inset-0 z-50 hidden overflow-y-auto modal-backdrop">
        <div class="fixed inset-0 modal-overlay" onclick="closeModal('newProposalModal')"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[95vh] overflow-y-auto relative z-10 transition-all">
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-center -mx-6 -mt-6 mb-6 p-6 rounded-t-lg bg-gradient-to-r from-slate-900 to-indigo-900 text-white sticky top-0 z-20 shadow-lg border-b border-indigo-800">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-indigo-500/20 rounded-2xl flex items-center justify-center mr-4 backdrop-blur-md border border-indigo-400/30">
                                <i class="fas fa-plus text-xl text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white">Create Budget Proposal</h3>
                                <p class="text-indigo-200/70 text-xs font-medium">Draft a new budget request for review and approval</p>
                            </div>
                        </div>
                        <button onclick="closeModal('newProposalModal')" class="text-indigo-200 hover:text-white transition-all bg-white/10 hover:bg-white/20 p-2 rounded-lg">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form id="proposalForm" autocomplete="off" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add_proposal">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Left Column -->
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Proposal Title <span class="text-red-500">*</span></label>
                                    <input type="text" name="proposal_title" id="description" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all shadow-sm" placeholder="e.g., 2025 Q1 Vehicle Fleet Maintenance">
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span></label>
                                        <select name="department" id="department" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all shadow-sm">
                                            <option value="">Select a Department</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal Year <span class="text-red-500">*</span></label>
                                        <select name="fiscal_year" id="fiscal_year" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all shadow-sm" onchange="updateProposalDates()">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>><?= $year ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date <span class="text-red-500">*</span></label>
                                        <input type="date" name="start_date" id="start_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all shadow-sm" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date <span class="text-red-500">*</span></label>
                                        <input type="date" name="end_date" id="end_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all shadow-sm" value="<?php echo date('Y-12-31'); ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Justification / Purpose <span class="text-red-500">*</span></label>
                                    <textarea name="project_objectives" id="purpose" required rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all resize-none shadow-sm" placeholder="Explain why this budget is needed and what it will accomplish..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Requested Total Amount Section -->
                        <div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-6 flex flex-col md:flex-row justify-between items-center mb-8 shadow-inner">
                            <div class="mb-4 md:mb-0">
                                <h4 class="text-gray-700 font-bold mb-1">Requested Total Amount (₱)</h4>
                                <p class="text-[10px] text-gray-500 italic font-medium">This should closely match the sum of all GL account amounts below.</p>
                            </div>
                            <div class="relative w-full md:w-80">
                                <input type="number" name="total_budget" id="totalBudgetInput" required step="0.01" min="0" class="w-full px-4 py-4 border-2 border-blue-200 bg-white rounded-xl font-black text-gray-800 text-2xl text-right outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-400 transition-all shadow-lg" placeholder="0.00">
                            </div>
                        </div>

                        <!-- Charts of Account Breakdown Section -->
                        <div class="mt-6 bg-gray-50/50 border border-gray-200 rounded-2xl overflow-hidden mb-8 shadow-sm">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-6 border-b border-gray-200 border-dashed pb-4">
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-800">Charts of Account Breakdown</h4>
                                        <p class="text-[11px] text-gray-500 mt-1">Select category, pick subcategories, then select GL accounts to include in this plan.</p>
                                    </div>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-full">Step 2 - COA Selection</span>
                                </div>

                                <div class="space-y-5 mb-8">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- Categories -->
                                        <div class="relative">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">1. Account Category</label>
                                            <select name="category" id="proposal_category_select" onchange="updateSubcategoriesProp()" required class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white transition-all shadow-sm">
                                                <option value="">Select Category...</option>
                                                <?php foreach ($gl_categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Subcategories -->
                                        <div class="relative">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">2. Subcategory</label>
                                            <select name="sub_category" id="subcategorySelect" onchange="filterGLAccountsProp()" disabled class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm appearance-none">
                                                <option value="">Select Category first...</option>
                                            </select>
                                            <p id="subcategory-error" class="text-[9px] text-red-500 mt-1 hidden font-bold">Selected subcategory does not match the chosen category.</p>
                                        </div>

                                        <!-- GL Accounts -->
                                        <div class="relative">
                                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">3. GL Account</label>
                                            <select id="glSelect" onchange="addAccountToPropBreakdown()" disabled class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white disabled:bg-gray-100 disabled:text-gray-400 transition-all shadow-sm appearance-none">
                                                <option value="">Select Subcategory first...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200/50 pt-8">
                                    <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center">
                                        <i class="fas fa-list-ul mr-2 text-indigo-400"></i> Selected GL Accounts & Budgets
                                    </h5>
                                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                        <table class="w-full text-left border-collapse text-xs">
                                            <thead>
                                                <tr class="bg-gray-50/80 text-gray-500 font-bold uppercase tracking-tight">
                                                    <th class="px-4 py-4 border-b border-gray-200">GL CODE</th>
                                                    <th class="px-4 py-4 border-b border-gray-200">NAME</th>
                                                    <th class="px-4 py-4 border-b border-gray-200">CATEGORY</th>
                                                    <th class="px-4 py-4 border-b border-gray-200">SUBCATEGORY</th>
                                                    <th class="px-4 py-4 border-b border-gray-200 text-right">BUDGET ALLOC.</th>
                                                    <th class="px-4 py-4 border-b border-gray-200 w-12 text-center"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="breakdownBody" class="divide-y divide-gray-100 text-gray-700 font-medium">
                                                <tr id="emptyBreakdownRow">
                                                    <td colspan="6" class="px-4 py-12 text-center text-gray-400 italic font-medium bg-gray-50/30">No GL accounts selected yet.</td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr class="bg-indigo-50/50 font-black text-gray-900 border-t-2 border-gray-100">
                                                    <td colspan="4" class="px-4 py-5 text-right text-sm text-indigo-600 uppercase tracking-widest">Calculated Total:</td>
                                                    <td class="px-4 py-5 text-right text-xl text-indigo-700" id="breakdownTotalDisplay">₱ 0.00</td>
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
                            <label class="block text-xs font-black text-gray-600 uppercase tracking-widest mb-3">Supporting Documentation (Required)</label>
                            <div class="border-2 border-dashed border-gray-200 rounded-2xl p-10 text-center cursor-pointer hover:border-indigo-500 hover:bg-indigo-50/50 transition-all group relative bg-gray-50/30" onclick="document.getElementById('supportingDocs').click()">
                                <input type="file" name="supporting_docs[]" id="supportingDocs" class="hidden" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" onchange="handleFileSelect(this)">
                                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center mx-auto mb-4 text-indigo-500 group-hover:scale-110 transition-transform group-hover:shadow-indigo-100 border border-gray-100">
                                    <i class="fas fa-cloud-upload-alt text-2xl"></i>
                                </div>
                                <p class="text-sm font-bold text-gray-500 group-hover:text-indigo-700">Click to upload quotes, estimates or project plans</p>
                                <p class="text-[10px] text-gray-400 mt-2 uppercase tracking-tighter">PDF, DOC, XLS, and Images are supported (Max 10MB per file)</p>
                                <div id="fileList" class="mt-6 flex flex-wrap gap-2 justify-center"></div>
                            </div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="flex justify-end gap-3 mt-10 pt-8 border-t border-gray-100">
                            <button type="button" onclick="saveDraft()" class="px-8 py-3.5 border-2 border-gray-200 rounded-xl hover:bg-gray-50 transition-all font-bold text-gray-600 text-xs shadow-sm flex items-center">
                                <i class="fas fa-save mr-2"></i> Save Draft
                            </button>
                            <button type="submit" id="submitProposalBtn" class="px-10 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-700 text-white rounded-xl hover:from-purple-700 hover:to-indigo-800 transition-all font-black text-xs shadow-xl shadow-indigo-100 flex items-center tracking-widest uppercase">
                                <i class="fas fa-paper-plane mr-2"></i> Submit Proposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>    <input type="hidden" name="project_type" value="operational">
                        <input type="hidden" name="proposal_type" value="new">
                        <input type="hidden" name="justification" value="As described in objectives">
                        <input type="hidden" name="project_scope" value="As described in objectives">
                        <input type="hidden" name="project_deliverables" value="As described in objectives">
                        <input type="hidden" name="implementation_timeline" value="As per start and end dates">
                        <input type="hidden" name="direct_costs" value="0">
                        <input type="hidden" name="indirect_costs" value="0">
                        <input type="hidden" name="equipment_costs" value="0">
                        <input type="hidden" name="travel_costs" value="0">
                        <input type="hidden" name="contingency_percentage" value="0">
                        <input type="hidden" name="contingency_amount" value="0">
                        <input type="hidden" name="priority_level" value="medium">
                        <input type="hidden" name="expected_roi" value="0">
                        <input type="hidden" name="funding_sources" value="Internal">
                        <input type="hidden" name="cost_sharing_details" value="N/A">
                        <input type="hidden" name="team_members" value="As assigned">
                        <input type="hidden" name="business_case" value="As described in justification">
                        <input type="hidden" name="executive_summary" value="As described in objectives">
                        <input type="hidden" name="previous_budget" value="0">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    
    
    <!-- Proposal Review Modal -->
<!-- REPLACEMENT MODAL - Review Budget Proposal (Request Portal Style) -->
<div id="proposalReviewModal" class="fixed inset-0 z-50 hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal('proposalReviewModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[1400px] max-h-[95vh] overflow-hidden flex flex-col relative z-30">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-5 bg-gradient-to-r from-purple-700 to-indigo-800 text-white shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-white bg-opacity-20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-file-invoice-dollar text-xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold">Review Budget Proposal</h3>
                        <p class="text-indigo-100 text-xs opacity-90" id="viewProposalCode">PROPOSAL CODE</p>
                    </div>
                </div>
                <button onclick="closeModal('proposalReviewModal')" class="text-white hover:text-indigo-200 transition-all bg-white bg-opacity-10 hover:bg-opacity-20 p-2 rounded-lg">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <!-- Tab Navigation -->
            <div class="bg-white border-b border-gray-200 px-6 shrink-0">
                <div class="flex gap-1">
                    <button onclick="switchReviewTab('details')" id="detailsTab" class="px-6 py-3 font-bold text-sm border-b-2 border-purple-600 text-purple-700 bg-purple-50/50 transition-all">
                        Details
                    </button>
                    <button onclick="switchReviewTab('documents')" id="documentsTab" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all">
                        <i class="fas fa-paperclip mr-1.5"></i>Supporting Documents
                    </button>
                    <button onclick="switchReviewTab('past_transactions')" id="pastTransactionsTab" class="px-6 py-3 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all">
                        <i class="fas fa-history mr-1.5"></i>Past Transactions
                    </button>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div class="flex-1 overflow-y-auto bg-gray-50">
                
                <!-- DETAILS TAB -->
                <div id="detailsTabContent" class="p-6">
                    <div class="grid grid-cols-12 gap-6">
                        <!-- Left: Proposal Info -->
                        <div class="col-span-12 lg:col-span-4 space-y-5">
                            <!-- Proposal Information Card -->
                            <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 pb-2 border-b border-gray-100">
                                    Proposal Information
                                </h4>
                                <div id="reviewModalContent" class="space-y-3 text-sm">
                                    <!-- Content populated via JS -->
                                </div>
                            </div>

                            <!-- Final Review Amount -->
                            <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-xl p-5 text-white shadow-lg">
                                <p class="text-xs font-black uppercase tracking-widest opacity-80 mb-2">Final Review Amount</p>
                                <p id="finalReviewAmount" class="text-3xl font-black">₱ 0.00</p>
                                <p class="text-xs opacity-70 mt-1">Adjusted if partial approval is required</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="grid grid-cols-2 gap-3">
                                <button onclick="approveFromReview()" class="bg-emerald-600 text-white px-5 py-3 rounded-xl hover:bg-emerald-700 transition-all font-bold shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <i class="fas fa-check-circle"></i> Approve
                                </button>
                                <button onclick="showRejectFromReview()" class="bg-red-500 text-white px-5 py-3 rounded-xl hover:bg-red-600 transition-all font-bold shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                                    <i class="fas fa-times-circle"></i> Reject
                                </button>
                            </div>
                            
                            <!-- Rejection Form -->
                            <div id="rejectFormInReview" class="p-4 bg-red-50 border border-red-200 rounded-xl hidden">
                                <h4 class="font-bold text-red-800 mb-2 text-sm">Reason for Rejection</h4>
                                <textarea id="rejectReasonInReview" rows="3" class="w-full px-3 py-2 text-sm border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Describe why this proposal is being rejected..."></textarea>
                                <div class="flex gap-2 mt-3">
                                    <button onclick="confirmRejectFromReview()" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 font-bold text-sm transition-colors">Confirm Rejection</button>
                                    <button onclick="cancelRejectFromReview()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 font-bold text-sm transition-colors">Cancel</button>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Account Breakdown Table -->
                        <div class="col-span-12 lg:col-span-8">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="px-5 py-4 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                                    <h3 class="text-sm font-black text-gray-700 uppercase tracking-wide flex items-center">
                                        <i class="fas fa-list-ul text-indigo-600 mr-2"></i> Account Breakdown & Details
                                    </h3>
                                </div>
                                
                                <div id="costBreakdownSection" class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="bg-gray-50 text-gray-600 text-xs font-bold uppercase tracking-tight">
                                                <th class="px-5 py-3.5 border-b border-gray-200">Expense Account</th>
                                                <th class="px-5 py-3.5 border-b border-gray-200 text-right">Requested Amount</th>
                                                <th class="px-5 py-3.5 border-b border-gray-200 text-right">Last Year Actual</th>
                                                <th class="px-5 py-3.5 border-b border-gray-200 text-right">Finance Recommendation</th>
                                                <th class="px-5 py-3.5 border-b border-gray-200 text-right">Variance</th>
                                                <th class="px-5 py-3.5 border-b border-gray-200 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="costBreakdownContent" class="divide-y divide-gray-100 text-sm">
                                            <!-- Populated via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUPPORTING DOCUMENTS TAB -->
                <div id="documentsTabContent" class="hidden h-full flex flex-col bg-gray-900 overflow-hidden relative">
                    <!-- Top area: fill with the active view -->
                    <div class="flex-1 overflow-hidden relative flex flex-col">
                        <!-- Automated Proposal View (shown when Official Proposal tab is clicked) -->
                        <div id="automatedProposalView" class="hidden flex-1 w-full bg-gray-100 overflow-y-auto p-10">
                            <!-- Content generated by JS -->
                        </div>

                        <!-- Document Viewer (shown when a supporting doc tab is clicked) -->
                        <div id="documentViewerContainer" class="hidden flex-1 overflow-hidden relative flex flex-col">
                            <div class="px-6 py-3 border-b border-gray-700 bg-gray-800 flex justify-between items-center shrink-0">
                                <h3 class="text-sm font-bold text-gray-200 flex items-center">
                                    <i class="fas fa-file-import text-purple-400 mr-2"></i>
                                    Attached Supporting Document
                                </h3>
                                <div class="flex gap-2">
                                    <button onclick="openCurrentDocInNewTab()" class="px-3 py-1.5 text-xs text-gray-300 hover:text-white bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors flex items-center gap-1" title="Open in New Tab">
                                        <i class="fas fa-external-link-alt"></i> Open in Tab
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-hidden relative flex flex-col">
                                <div id="pdfViewerPlaceholder" class="flex-1 flex items-center justify-center bg-gray-800">
                                    <div class="text-center p-8">
                                        <i class="fas fa-file-alt text-5xl text-gray-600 mb-4 block"></i>
                                        <p class="text-gray-400 font-medium">No document selected</p>
                                        <p class="text-gray-500 text-sm mt-1">Select a document from the tray below</p>
                                    </div>
                                </div>
                                
                                <!-- Main Document Iframe -->
                                <iframe id="pdfFrame" class="flex-1 w-full border-0 hidden" frameborder="0"></iframe>
                                
                                <!-- Image Viewer (Fallback) -->
                                <div id="imageViewerContainer" class="flex-1 w-full hidden relative bg-gray-50 overflow-auto">
                                    <img id="docImage" src="" class="max-w-full h-auto mx-auto p-4" alt="Document">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipts Tray - always visible at bottom when documents tab is shown -->
                    <div id="receiptsTray" class="bg-gray-900 border-t border-gray-700 text-white p-3 hidden shrink-0">
                        <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-800 px-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400" id="receiptsCount">0 Files</span>
                            <span class="text-[9px] text-gray-500 italic">Click to switch views</span>
                        </div>
                        <div id="receiptsList" class="flex flex-wrap gap-2">
                            <!-- Tabs will be rendered here via JS -->
                        </div>
                    </div>
                </div>

                <!-- PAST TRANSACTIONS TAB -->
                <div id="pastTransactionsTabContent" class="hidden h-full flex flex-col bg-white overflow-hidden p-6">
                    <div class="flex items-center justify-between mb-6 shrink-0">
                        <div>
                            <h4 class="text-xl font-bold text-gray-900 tracking-tight">Department Spending History</h4>
                            <p class="text-sm text-gray-500">Historical data for related GL accounts</p>
                        </div>
                        <div class="bg-indigo-50 px-4 py-2 rounded-xl text-indigo-700 font-bold text-sm border border-indigo-100 flex items-center gap-2">
                            <i class="fas fa-chart-line text-indigo-400"></i>
                            24-Month Trend Analysis
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 shrink-0">
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Avg. Monthly Spend</p>
                            <p id="avgMonthlySpend" class="text-lg font-bold text-gray-900">₱0.00</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Peak Spend (Last 24m)</p>
                            <p id="peakSpend" class="text-lg font-bold text-gray-900">₱0.00</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Trend Status</p>
                            <div class="flex items-center gap-2" id="trendStatusContainer">
                                <span id="trendStatusBadge" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tighter bg-emerald-100 text-emerald-700">Stable</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto border border-gray-100 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead class="sticky top-0 bg-white z-10 shadow-sm">
                                <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">
                                    <th class="px-5 py-4">Transaction Period</th>
                                    <th class="px-5 py-4">Reference</th>
                                    <th class="px-5 py-4">Account/GL</th>
                                    <th class="px-5 py-4 text-right">Amount Spent</th>
                                    <th class="px-5 py-4 text-center">Utilization</th>
                                </tr>
                            </thead>
                            <tbody id="pastTransactionsBody" class="divide-y divide-gray-50">
                                <!-- Generated by JS -->
                            </tbody>
                        </table>
                    </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end shrink-0">
                <button type="button" class="px-8 py-2.5 bg-gray-600 text-white rounded-xl font-bold hover:bg-gray-700 transition-all shadow-md" onclick="closeModal('proposalReviewModal')">
                    Close Review
                </button>
            </div>
        </div>
</div>

<!-- Add Comment Modal -->
<div id="addCommentModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal('addCommentModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-30">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-5 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-comment-alt text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Add Comment</h3>
                        <p class="text-xs text-gray-500" id="commentCategoryLabel">Expense Account: -</p>
                    </div>
                </div>
                <button onclick="closeModal('addCommentModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Comment Type -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Comment Type</label>
                    <select id="commentType" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="internal_note">Internal Note (Finance Only)</option>
                        <option value="revision_request">Revision Request</option>
                        <option value="clarification_needed">Clarification Needed</option>
                    </select>
                </div>

                <!-- Your Comment -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Your Comment <span class="text-red-500">*</span></label>
                    <textarea id="commentText" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Enter your comment here..."></textarea>
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Priority</label>
                    <select id="commentPriority" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeModal('addCommentModal')" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button onclick="submitComment()" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 transition-colors shadow-md">
                    Post Comment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Line Item Modal -->
<div id="editLineItemModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal('editLineItemModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-30">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-5 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-edit text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Edit Line Item</h3>
                </div>
                <button onclick="closeModal('editLineItemModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Category (Read-only) -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Expense Account</label>
                    <input type="text" id="editCategory" readonly class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-700 cursor-not-allowed">
                </div>

                <!-- Amounts Row -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Department Requested Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-500 font-bold">₱</span>
                            <input type="number" id="editRequestedAmount" step="0.01" oninput="updateEditVariance()" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-bold">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Finance Recommended Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-3.5 text-gray-500 font-bold">₱</span>
                            <input type="number" id="editRecommendedAmount" step="0.01" oninput="updateEditVariance()" class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-bold">
                        </div>
                    </div>
                </div>

                <!-- Adjustment Reason -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Adjustment Reason</label>
                    <textarea id="editAdjustmentReason" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm" placeholder="Explain the reason for adjustment..."></textarea>
                </div>

                <!-- Change Summary -->
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <h4 class="text-sm font-bold text-gray-700 mb-3">Change Summary</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Previous Variance:</span>
                            <span id="editPrevVariance" class="font-bold text-red-600">-₱0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">New Variance:</span>
                            <span id="editNewVariance" class="font-bold text-red-600">-₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeModal('editLineItemModal')" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button onclick="saveLineItemEdit()" class="px-6 py-2.5 bg-purple-600 text-white rounded-lg font-bold hover:bg-purple-700 transition-colors shadow-md">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Adjust by Percentage Modal -->
<div id="adjustPercentageModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeModal('adjustPercentageModal')"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-30">
            <!-- Header -->
            <div class="flex justify-between items-center px-6 py-5 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-percentage text-gray-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Adjust by Percentage</h3>
                        <p class="text-xs text-gray-500" id="adjustCategoryLabel">Expense Account: -</p>
                    </div>
                </div>
                <button onclick="closeModal('adjustPercentageModal')" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Current Finance Recommendation -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Current Finance Recommendation</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-gray-500 font-bold">₱</span>
                        <input type="text" id="adjustCurrentAmount" readonly class="w-full pl-8 pr-4 py-3 bg-gray-100 border border-gray-300 rounded-lg text-sm font-bold text-gray-700 cursor-not-allowed">
                    </div>
                </div>

                <!-- Adjustment Percentage -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Adjustment Percentage <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" id="adjustPercentage" step="0.01" oninput="calculateNewAmount()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 text-sm font-bold" placeholder="0">
                        <span class="absolute right-4 top-3.5 text-gray-500 font-bold">%</span>
                    </div>
                </div>

                <!-- New Recommended Amount -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">New Recommended Amount</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3.5 text-emerald-600 font-bold">₱</span>
                        <input type="text" id="adjustNewAmount" readonly class="w-full pl-8 pr-4 py-3 bg-emerald-50 border-2 border-emerald-200 rounded-lg text-sm font-black text-emerald-700 cursor-not-allowed">
                    </div>
                </div>

                <!-- Adjustment Reason -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Adjustment Reason <span class="text-red-500">*</span></label>
                    <textarea id="adjustReason" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500 text-sm" placeholder="Explain the reason for this percentage adjustment..."></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="closeModal('adjustPercentageModal')" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg font-bold hover:bg-gray-300 transition-colors">
                    Cancel
                </button>
                <button onclick="applyPercentageAdjustment()" class="px-6 py-2.5 bg-gray-700 text-white rounded-lg font-bold hover:bg-gray-800 transition-colors shadow-md">
                    Apply Adjustment
                </button>
            </div>
        </div>
    </div>
</div>
    
    
    <!-- Forecast Modal -->
    <div id="forecastModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal('forecastModal')"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl modal-content w-full max-w-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Add Forecast</h3>
                        <button onclick="closeModal('forecastModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <form id="forecastForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <input type="text" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Marketing Expenses" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forecast Period</label>
                                <input type="month" name="forecast_period" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Forecasted Amount</label>
                                <input type="number" name="forecasted_amount" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confidence Level (%)</label>
                                <input type="range" name="confidence_level" min="1" max="100" value="80" class="w-full">
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Low</span>
                                    <span id="confidenceValue">80%</span>
                                    <span>High</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Assumptions</label>
                                <textarea name="assumptions" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Key assumptions for this forecast..."></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                Save Forecast
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Archive Modal -->
    <div id="archiveModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal('archiveModal')"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl modal-content w-full max-w-md">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Archive Plan</h3>
                        <button onclick="closeModal('archiveModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <form id="archiveForm">
                        <input type="hidden" name="plan_id" id="archivePlanId">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Archive Reason</label>
                                <select name="archive_reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                                    <option value="completed">Project Completed</option>
                                    <option value="cancelled">Project Cancelled</option>
                                    <option value="superseded">Superseded by New Plan</option>
                                    <option value="obsolete">No Longer Relevant</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="archive_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal('archiveModal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                                Archive Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 z-[100] hidden overflow-y-auto modal-backdrop">
        <div class="fixed inset-0" onclick="closeModal('confirmModal')"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all relative z-[110]">
                <!-- Header -->
                <div id="confirmModalHeader" class="p-6 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                    <div class="flex items-center justify-center w-16 h-16 bg-white/20 rounded-2xl mb-4 mx-auto backdrop-blur-md">
                        <i id="confirmModalIcon" class="fas fa-question-circle text-3xl"></i>
                    </div>
                    <h3 id="confirmModalTitle" class="text-xl font-bold text-center">Confirmation</h3>
                </div>
                
                <!-- Body -->
                <div class="p-8 text-center">
                    <p id="confirmModalMessage" class="text-gray-600 font-medium leading-relaxed"></p>
                </div>
                
                <!-- Footer -->
                <div class="p-6 bg-gray-50 flex gap-3">
                    <button id="confirmCancelBtn" onclick="closeModal('confirmModal')" class="flex-1 px-6 py-3 bg-white border border-gray-200 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all shadow-sm">
                        Cancel
                    </button>
                    <button id="confirmProceedBtn" class="flex-1 px-6 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-md hover:shadow-lg">
                        Proceed
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Restore Modal -->
    <div id="restoreModal" class="fixed inset-0 z-[60] hidden overflow-y-auto modal-backdrop">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal('restoreModal')"></div>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl modal-content w-full max-w-md">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">Restore Plan</h3>
                        <button onclick="closeModal('restoreModal')" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <form id="restoreForm">
                        <input type="hidden" name="archive_id" id="restoreArchiveId">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Restore Reason</label>
                                <input type="text" name="restore_reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Why are you restoring this plan?" required>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeModal('restoreModal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Restore Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Global variables
    let tnvsCategories = <?php echo json_encode($tnvs_categories); ?>;
    
    // Hierarchical COA Data for Modal
    const modalCategories = <?php echo json_encode($gl_categories); ?>;
    const modalSubcategories = <?php echo json_encode($gl_subcategories); ?>;
    const modalGLAccounts = <?php echo json_encode($gl_accounts_by_sub); ?>;

    function formatIntegerInput(input) {
        // Remove everything except digits
        let val = input.value.replace(/[^0-9]/g, '');
        
        // Add commas for readability in the input itself
        if (val !== '') {
            input.value = parseInt(val).toLocaleString();
        } else {
            input.value = '';
        }
    }

    function syncIntegerDisplay(input) {
        // Ensure formatting on blur
        if (input.value !== '') {
            const raw = input.value.replace(/,/g, '');
            input.value = parseInt(raw).toLocaleString();
        }
    }

    function updateGlobalAllocationTotal() {
        // Find all subcategory inputs
        const subcatInputs = document.querySelectorAll('input[oninput*="distributeSubcategoryBudget"]');
        let total = 0;
        
        subcatInputs.forEach(input => {
            total += getRawValue(input.value);
        });
        
        const disp1 = document.getElementById('current-mapping-total');
        const disp2 = document.getElementById('calculated-total-summary');
        
        if (disp1) disp1.textContent = formatCurrency(total);
        if (disp2) disp2.textContent = formatCurrency(total);
        
        const totalBudgetField = document.querySelector('input[name="total_budget"]');
        if (totalBudgetField) {
            totalBudgetField.value = total.toFixed(2);
        }
        
        // Budget limit check - if globalRemainingBudget is 0, any total > 0 is over.
        // We use a threshold of 0.01 for floating point safety.
        const isOverBudget = (total - globalRemainingBudget) > 0.01;
        const submitBtn = document.getElementById('submit-btn');

        if (isOverBudget) {
            // Header Badge
            const badge = disp1?.closest('span');
            if (badge) {
                badge.classList.remove('bg-emerald-50', 'text-emerald-600');
                badge.classList.add('bg-red-50', 'text-red-600');
            }
            
            // Summary Box (Bottom)
            if (disp2) {
                const summaryBox = disp2.parentElement;
                summaryBox.classList.add('bg-red-50', 'border-red-200', 'animate-shake');
                summaryBox.classList.remove('bg-indigo-50', 'border-indigo-100');
                disp2.classList.add('text-red-600', 'font-black');
            }
            
            // Subcategory Inputs
            subcatInputs.forEach(input => {
                const wrapper = input.closest('.amount-input-wrapper');
                if (getRawValue(input.value) > 0) {
                    if (wrapper) {
                        wrapper.classList.add('is-error');
                        const decimalSpan = wrapper.querySelector('.static-decimal');
                        if (decimalSpan) {
                            decimalSpan.classList.add('text-red-600');
                            decimalSpan.classList.remove('text-indigo-600');
                        }
                    }
                    input.classList.remove('text-indigo-600');
                    input.classList.add('text-red-600', '!placeholder-red-300');
                }
            });
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'grayscale', 'cursor-not-allowed', 'transform-none');
            }
        } else {
            // Header Badge
            const badge = disp1?.closest('span');
            if (badge) {
                badge.classList.add('bg-emerald-50', 'text-emerald-600');
                badge.classList.remove('bg-red-50', 'text-red-600');
            }
            
            // Summary Box (Bottom)
            if (disp2) {
                const summaryBox = disp2.parentElement;
                summaryBox.classList.remove('bg-red-50', 'border-red-200', 'animate-shake');
                summaryBox.classList.add('bg-indigo-50', 'border-indigo-100');
                disp2.classList.remove('text-red-600', 'font-black');
            }
            
            // Subcategory Inputs
            subcatInputs.forEach(input => {
                const wrapper = input.closest('.amount-input-wrapper');
                if (wrapper) {
                    wrapper.classList.remove('is-error');
                    const decimalSpan = wrapper.querySelector('.static-decimal');
                    if (decimalSpan) {
                        decimalSpan.classList.remove('text-red-600');
                    }
                }
                input.classList.remove('text-red-600', '!placeholder-red-300');
                input.classList.add('text-indigo-600');
            });
        }

        updateSuggestedMetrics(total);
        validateCreateBudgetPlan();
    }

    function updateSuggestedMetrics(totalBudget) {
        const revenueInput = document.querySelector('input[name="project_revenue"]');
        const impactInput = document.querySelector('input[name="impact_percentage"]');
        const taxationInput = document.querySelector('input[name="taxation_adj"]');
        
        if (!revenueInput || !impactInput || !taxationInput) return;
        
        const isRevenueAuto = revenueInput.getAttribute('data-suggested') !== 'false';
        const isImpactAuto = impactInput.getAttribute('data-suggested') !== 'false';
        const isTaxationAuto = taxationInput.getAttribute('data-suggested') !== 'false';
        
        if (totalBudget > 0) {
            if (isRevenueAuto) {
                const val = totalBudget * 1.15;
                revenueInput.value = parseInt(val).toLocaleString();
                const wrapper = revenueInput.closest('.amount-input-wrapper');
                if (wrapper) wrapper.style.borderColor = '#10b981';
            }
            if (isImpactAuto) {
                let suggestedImpact = 10;
                if (totalBudget >= 10000000) suggestedImpact = 85;
                else if (totalBudget >= 5000000) suggestedImpact = 65;
                else if (totalBudget >= 1000000) suggestedImpact = 45;
                else if (totalBudget >= 500000) suggestedImpact = 25;
                impactInput.value = parseInt(suggestedImpact).toLocaleString();
                const wrapper = impactInput.closest('.amount-input-wrapper');
                if (wrapper) wrapper.style.borderColor = '#10b981';
            }
            if (isTaxationAuto) {
                const val = totalBudget * 0.12;
                taxationInput.value = parseInt(val).toLocaleString();
                const wrapper = taxationInput.closest('.amount-input-wrapper');
                if (wrapper) wrapper.style.borderColor = '#10b981';
            }
        }
    }

    function filterGLAllocationList(query) {
        query = query.toLowerCase().trim();
        const subcategories = document.querySelectorAll('.subcategory-block');
        const categories = document.querySelectorAll('.category-block');

        // Hide/Show subcategories based on name
        subcategories.forEach(sub => {
            const searchable = sub.getAttribute('data-searchable') || '';
            if (searchable.includes(query) || query === '') {
                sub.classList.remove('hidden');
            } else {
                sub.classList.add('hidden');
            }
        });

        // Hide categories if all children are hidden
        categories.forEach(cat => {
            const visibleSubs = cat.querySelectorAll('.subcategory-block:not(.hidden)');
            if (visibleSubs.length === 0 && query !== '') {
                cat.classList.add('hidden');
            } else {
                cat.classList.remove('hidden');
            }
        });
    }

    function updateBudgetDates() {
        const yearInput = document.getElementById('modal_fiscal_year');
        const typeInput = document.getElementById('plan_type_input');
        if (!yearInput || !typeInput) return;
        
        const year = yearInput.value;
        const type = typeInput.value;
        
        const now = new Date();
        const currentYear = now.getFullYear();
        const todayStr = now.toISOString().split('T')[0];

        if (type === 'monthly') {
            const currentMonth = now.getMonth(); // 0-11
            const monthToUse = (parseInt(year) === currentYear) ? currentMonth : 0;
            
            const firstDay = new Date(year, monthToUse, 1);
            const lastDay = new Date(year, monthToUse + 1, 0);
            
            const formatDate = (date) => {
                let d = date.getDate(), m = date.getMonth() + 1, y = date.getFullYear();
                return `${y}-${m < 10 ? '0'+m : m}-${d < 10 ? '0'+d : d}`;
            };
            
            // For start date, if it's the current year and current month, use today as minimum
            let startVal = formatDate(firstDay);
            if (parseInt(year) === currentYear && monthToUse === currentMonth) {
                startVal = todayStr;
            }
            
            if (document.getElementById('modal_start_date')) document.getElementById('modal_start_date').value = startVal;
            if (document.getElementById('modal_end_date')) document.getElementById('modal_end_date').value = formatDate(lastDay);
        } else {
            // Yearly
            let startVal = `${year}-01-01`;
            if (parseInt(year) === currentYear) {
                startVal = todayStr; // Latest valid date is today
            }
            if (document.getElementById('modal_start_date')) document.getElementById('modal_start_date').value = startVal;
            if (document.getElementById('modal_end_date')) document.getElementById('modal_end_date').value = `${year}-12-31`;
        }
        
        validateCreateBudgetPlan();
    }

    function updateProposalDates() {
        try {
            console.log('Initializing proposal dates...');
            const yearInput = document.getElementById('fiscal_year');
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            
            if (!yearInput || !startInput || !endInput) {
                console.warn('Could not find proposal date inputs:', { yearInput, startInput, endInput });
                return;
            }
            
            const year = yearInput.value || <?php echo date('Y'); ?>;
            const now = new Date();
            const currentYear = now.getFullYear();
            const todayStr = now.toISOString().split('T')[0];
            
            // Start Date: Today if current year, else January 1st of selected year
            let startVal = `${year}-01-01`;
            if (parseInt(year) === currentYear) {
                startVal = todayStr;
            } else if (parseInt(year) < currentYear) {
                startVal = todayStr;
            }
            
            startInput.value = startVal;
            
            // End Date: Always Dec 31st of selected fiscal year
            endInput.value = `${year}-12-31`;
            
            console.log(`Dates set: Start=${startVal}, End=${year}-12-31`);
            
            if (typeof calculateDuration === 'function') {
                calculateDuration();
            }
        } catch (err) {
            console.error('Error in updateProposalDates:', err);
        }
    }

    function distributeSubcategoryBudget(subId, totalAmountStr) {
        const subBlock = document.querySelector(`.subcategory-block[data-sub-id="${subId}"]`);
        if (!subBlock) return;
        
        const glInputs = subBlock.querySelectorAll('.gl-input-actual');
        if (glInputs.length === 0) return;
        
        const amount = getRawValue(totalAmountStr);
        
        if (amount <= 0) {
            // Clear all inputs if amount is 0
            glInputs.forEach(input => {
                input.value = '';
            });
        } else {
            const count = glInputs.length;
            const baseShare = Math.floor((amount * 100) / count) / 100; // Round down to 2 decimals
            const remainder = Math.round((amount - (baseShare * count)) * 100) / 100; // Calculate remainder
            
            glInputs.forEach((input, index) => {
                if (index === 0) {
                    // First input gets the base share PLUS the remainder to avoid rounding loss
                    input.value = (baseShare + remainder).toFixed(2);
                } else {
                    // Other inputs get equal base share
                    input.value = baseShare.toFixed(2);
                }
            });
        }
        
        updateGlobalAllocationTotal();
    }
    let costCategories = <?php echo json_encode($cost_categories); ?>;
    let currentStep = 1;
    let myProposalsOnly = false;
    let monitoringChart = null;
    let departmentChart = null;
    let currentPage = {
        plans: 1,
        proposals: 1,
        archived: 1,
        monitoring: 1
    };
    let searchTimeout = null;
    let currentReviewProposalId = null;
    let selectedFiles = [];
    let currentProposalType = 'pending';
    let costItems = [];
    let currentSupportingDocPath = '';
    let costBreakdownChart = null;
    let globalTotalRevenue = 0;
    let globalRemainingBudget = 0;
    
    // Formatting helper
    function formatInputAmount(input) {
        // Remove non-numeric characters except period
        let val = input.value.replace(/[^0-9.]/g, '');
        
        // Handle multiple periods
        const parts = val.split('.');
        if (parts.length > 2) {
            val = parts[0] + '.' + parts.slice(1).join('');
        }
        
        // Format with commas
        if (val !== '') {
            const numParts = val.split('.');
            numParts[0] = numParts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            input.value = numParts.join('.');
        } else {
            input.value = '';
        }
    }

    // Helper to get raw numeric value
    function getRawValue(val) {
        if (!val) return 0;
        return parseFloat(val.toString().replace(/,/g, '')) || 0;
    }
    
    // Modal management functions
    // (Functions openModal and closeModal are defined later in the script)
    
    // Update subcategories based on selected category
    function updateSubcategories() {
        const categorySelect = document.getElementById('categorySelect');
        const subCategorySelect = document.getElementById('subCategorySelect');
        const selectedCategory = categorySelect.value;
        
        console.log('Selected category:', selectedCategory);
        console.log('Available categories:', tnvsCategories);
        
        // Clear existing options
        subCategorySelect.innerHTML = '<option value="">Select Sub-category</option>';
        
        // If a category is selected and it exists in tnvsCategories
        if (selectedCategory && tnvsCategories[selectedCategory]) {
            console.log('Subcategories for', selectedCategory, ':', tnvsCategories[selectedCategory]);
            
            // Add each subcategory as an option
            tnvsCategories[selectedCategory].forEach(function(subCategory) {
                const option = document.createElement('option');
                option.value = subCategory;
                option.textContent = subCategory;
                subCategorySelect.appendChild(option);
            });
            
            // Enable the subcategory select
            subCategorySelect.disabled = false;
        } else {
            // Disable if no category selected
            subCategorySelect.disabled = true;
        }
    }
    
    // Utility function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Utility function to format currency
    function formatCurrency(amount) {
        if (isNaN(amount)) return '₱0.00';
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Utility function to get file icon
    function getFileIcon(extension) {
        const icons = {
            'pdf': '<i class="fas fa-file-pdf text-red-600"></i>',
            'doc': '<i class="fas fa-file-word text-blue-600"></i>',
            'docx': '<i class="fas fa-file-word text-blue-600"></i>',
            'xls': '<i class="fas fa-file-excel text-green-600"></i>',
            'xlsx': '<i class="fas fa-file-excel text-green-600"></i>',
            'jpg': '<i class="fas fa-file-image text-purple-600"></i>',
            'jpeg': '<i class="fas fa-file-image text-purple-600"></i>',
            'png': '<i class="fas fa-file-image text-purple-600"></i>',
            'zip': '<i class="fas fa-file-archive text-yellow-600"></i>'
        };
        return icons[extension.toLowerCase()] || '<i class="fas fa-file text-gray-600"></i>';
    }
    
    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadPlans();
        loadProposals();
        loadMonitoringData();
        updateDepartmentChart(<?php echo $current_year; ?>);
        loadArchived();
        
        // Initialize dates
        updateProposalDates();
        updateBudgetDates();
        
        // Setup event listeners
        document.getElementById('proposalForm')?.addEventListener('submit', submitProposal);
        document.getElementById('forecastForm')?.addEventListener('submit', saveForecast);
        document.getElementById('archiveForm')?.addEventListener('submit', archivePlan);
        document.getElementById('restoreForm')?.addEventListener('submit', restorePlan);
        document.getElementById('reviewCommentForm')?.addEventListener('submit', addProposalComment);
        
        // Setup file upload
        setupFileUpload();
        
        // Setup filter listeners
        document.getElementById('filterDepartment')?.addEventListener('change', () => {
            currentPage.plans = 1;
            loadPlans();
        });
        document.getElementById('filterYear')?.addEventListener('change', () => {
            currentPage.plans = 1;
            loadPlans();
        });
        document.getElementById('proposalFilterDepartment')?.addEventListener('change', () => {
            currentPage.proposals = 1;
            loadProposals();
        });
        document.getElementById('proposalFilterYear')?.addEventListener('change', () => {
            currentPage.proposals = 1;
            loadProposals();
        });
        const syncMonitoringFilters = (type, value) => {
            const ids = type === 'year' 
                ? ['monitoringYear', 'monitoringTableYear', 'deptMonitoringYear']
                : ['monitoringDepartment', 'monitoringTableDepartment', 'deptMonitoringDepartment'];
            
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = value;
            });
            currentPage.monitoring = 1;
            loadMonitoringData();
        };

        document.getElementById('monitoringYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });
        document.getElementById('monitoringTableYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });
        document.getElementById('deptMonitoringYear')?.addEventListener('change', function() {
            syncMonitoringFilters('year', this.value);
        });

        document.getElementById('monitoringDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('monitoringTableDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('deptMonitoringDepartment')?.addEventListener('change', function() {
            syncMonitoringFilters('department', this.value);
        });
        document.getElementById('departmentGraphYear')?.addEventListener('change', function() {
            updateDepartmentChart(this.value);
        });
        
        // Initialize date fields
        const today = new Date();
        const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, 1);
        document.querySelector('input[name="start_date"]').valueAsDate = today;
        document.querySelector('input[name="end_date"]').valueAsDate = nextMonth;
        calculateDuration();
        
        // Update budget preview initially
        updateBudgetPreview();
    });
    
    // Tab switching functions
    function switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        document.getElementById(tab + 'Tab').classList.remove('hidden');
        
        const activeBtn = document.querySelector(`[onclick="switchTab('${tab}')"]`);
        if (activeBtn) activeBtn.classList.add('active');
        
        if (tab === 'plans') loadPlans();
        else if (tab === 'proposals') loadProposals();
        else if (tab === 'monitoring') {
            loadMonitoringData();
            if (typeof updateCategoryChart === 'function') updateCategoryChart();
            updateDepartmentChart();
        }
        else if (tab === 'archived') loadArchived();
    }
    
    // Toggle monitoring view between category and department
    let currentMonitoringView = 'category';
    
    function toggleMonitoringView(view) {
        currentMonitoringView = view;
        
        // Update button styles
        const categoryBtn = document.getElementById('categoryViewBtn');
        const departmentBtn = document.getElementById('departmentViewBtn');
        
        if (view === 'category') {
            categoryBtn.classList.add('bg-purple-600', 'text-white');
            categoryBtn.classList.remove('bg-gray-200', 'text-gray-700');
            departmentBtn.classList.remove('bg-purple-600', 'text-white');
            departmentBtn.classList.add('bg-gray-200', 'text-gray-700');
            
            // Show category chart and table, hide department
            document.getElementById('categoryChartSection').classList.remove('hidden');
            document.getElementById('categoryTableSection').classList.remove('hidden');
            document.getElementById('departmentChartSection').classList.add('hidden');
            document.getElementById('departmentTableSection').classList.add('hidden');
        } else {
            departmentBtn.classList.add('bg-purple-600', 'text-white');
            departmentBtn.classList.remove('bg-gray-200', 'text-gray-700');
            categoryBtn.classList.remove('bg-purple-600', 'text-white');
            categoryBtn.classList.add('bg-gray-200', 'text-gray-700');
            
            // Show department chart and table, hide category
            document.getElementById('categoryChartSection').classList.add('hidden');
            document.getElementById('categoryTableSection').classList.add('hidden');
            document.getElementById('departmentChartSection').classList.remove('hidden');
            document.getElementById('departmentTableSection').classList.remove('hidden');
        }
    }
    
    // Proposal step navigation - DISABLED (Single-page form now)
    /*
    function nextStep() {
        if (currentStep < 4) {
            // Validate current step
            if (!validateStep(currentStep)) {
                return;
            }
            
            // Hide current step
            document.getElementById(`proposalStep${currentStep}`).classList.add('hidden');
            
            // Update step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-active');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-completed');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = '<i class="fas fa-check"></i>';
            
            // Move to next step
            currentStep++;
            
            // Show next step
            document.getElementById(`proposalStep${currentStep}`).classList.remove('hidden');
            
            // Update next step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-pending');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-active');
            
            // Update previews
            if (currentStep === 2) {
                updateBudgetPreview();
            } else if (currentStep === 4) {
                updateReviewSummary();
                renderCostBreakdownChart();
            }
        }
    }
    
    function previousStep() {
        if (currentStep > 1) {
            // Hide current step
            document.getElementById(`proposalStep${currentStep}`).classList.add('hidden');
            
            // Update step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-active');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-pending');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = currentStep;
            
            // Move to previous step
            currentStep--;
            
            // Show previous step
            document.getElementById(`proposalStep${currentStep}`).classList.remove('hidden');
            
            // Update previous step indicator
            document.getElementById(`step${currentStep}Indicator`).classList.remove('step-completed');
            document.getElementById(`step${currentStep}Indicator`).classList.add('step-active');
            document.getElementById(`step${currentStep}Indicator`).innerHTML = currentStep;
        }
    }
    
    function validateStep(step) {
        const form = document.getElementById('proposalForm');
        
        if (step === 1) {
            const requiredFields = ['proposal_title', 'department', 'project_type', 'start_date', 'end_date', 'project_objectives', 'project_scope', 'project_deliverables', 'implementation_timeline'];
            
            for (const fieldName of requiredFields) {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field && !field.value.trim()) {
                    showToast(`Please fill in ${fieldName.replace('_', ' ')}`, 'error');
                    field.focus();
                    return false;
                }
            }
            
            // Validate dates
            const startDate = new Date(form.querySelector('[name="start_date"]').value);
            const endDate = new Date(form.querySelector('[name="end_date"]').value);
            
            if (endDate <= startDate) {
                showToast('End date must be after start date', 'error');
                return false;
            }
        }
        
        return true;
    }
    */
    
    // Calculate project duration
    function calculateDuration() {
        const startDate = document.querySelector('[name="start_date"]')?.value;
        const endDate = document.querySelector('[name="end_date"]')?.value;
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            
            const durationPreview = document.getElementById('durationPreview');
            if (durationPreview) {
                durationPreview.textContent = duration + ' days';
            }
        }
    }
    
    // Update budget preview
    function updateBudgetPreview() {
        // Check if cost breakdown fields exist (they don't in simplified form)
        const directCostField = document.querySelector('[name="direct_costs"]');
        const indirectCostField = document.querySelector('[name="indirect_costs"]');
        const equipmentCostField = document.querySelector('[name="equipment_costs"]');
        const travelCostField = document.querySelector('[name="travel_costs"]');
        const contingencyPercentageField = document.querySelector('[name="contingency_percentage"]');
        
        // If these fields don't exist, skip the preview update
        if (!directCostField || !indirectCostField || !equipmentCostField || !travelCostField) {
            return;
        }
        
        const direct = parseFloat(directCostField.value) || 0;
        const indirect = parseFloat(indirectCostField.value) || 0;
        const equipment = parseFloat(equipmentCostField.value) || 0;
        const travel = parseFloat(travelCostField.value) || 0;
        const contingencyPercentage = parseFloat(contingencyPercentageField?.value) || 5;
        
        // Calculate subtotal (before taxes)
        const subtotal = direct + indirect + equipment + travel;
        
        // Calculate contingency
        const contingencyAmount = subtotal * (contingencyPercentage / 100);
        
        // Calculate taxes
        const vat = subtotal * 0.12; // 12% VAT
        const withholdingTax = subtotal * 0.02; // 2% Withholding Tax
        
        // Calculate total budget (subtotal + contingency + VAT + withholding tax)
        const total = subtotal + contingencyAmount + vat + withholdingTax;
        
        // Update preview displays (with null checks)
        const subtotalPreview = document.getElementById('subtotalPreview');
        const vatPreview = document.getElementById('vatPreview');
        const wtaxPreview = document.getElementById('wtaxPreview');
        const totalBudgetPreview = document.getElementById('totalBudgetPreview');
        
        if (subtotalPreview) subtotalPreview.textContent = formatCurrency(subtotal);
        if (vatPreview) vatPreview.textContent = formatCurrency(vat);
        if (wtaxPreview) wtaxPreview.textContent = formatCurrency(withholdingTax);
        if (totalBudgetPreview) totalBudgetPreview.textContent = formatCurrency(total);
        
        // Update contingency amount field (with null check)
        const contingencyAmountField = document.querySelector('[name="contingency_amount"]');
        if (contingencyAmountField) {
            contingencyAmountField.value = contingencyAmount.toFixed(2);
        }
        
        // Update hidden total budget field (with null check)
        const totalBudgetField = document.getElementById('totalBudgetField');
        if (totalBudgetField) {
            totalBudgetField.value = total.toFixed(2);
        }
    }
    
    function updateTotalBudget() {
        updateBudgetPreview();
    }
    
    function updateContingency(percentage) {
        document.getElementById('contingencyPercentageDisplay').textContent = percentage + '%';
        document.querySelector('[name="contingency_percentage"]').value = percentage;
        updateBudgetPreview();
    }
    
    function updatePriorityPreview(priority) {
        document.getElementById('priorityPreview').textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
    }
    
    // Add cost item
    function addCostItem() {
        const type = document.getElementById('itemType').value;
        const description = document.getElementById('itemDescription').value;
        const quantity = parseFloat(document.getElementById('itemQuantity').value) || 1;
        const unitCost = parseFloat(document.getElementById('itemUnitCost').value) || 0;
        const totalCost = quantity * unitCost;
        
        if (!description.trim()) {
            showToast('Please enter a description', 'error');
            return;
        }
        
        if (unitCost <= 0) {
            showToast('Please enter a valid unit cost', 'error');
            return;
        }
        
        const item = {
            type: type,
            description: description,
            quantity: quantity,
            unitCost: unitCost,
            totalCost: totalCost
        };
        
        costItems.push(item);
        renderCostItems();
        
        // Update relevant cost category
        updateCostCategory(type, totalCost);
        
        // Clear form
        document.getElementById('itemDescription').value = '';
        document.getElementById('itemQuantity').value = 1;
        document.getElementById('itemUnitCost').value = '';
        
        showToast('Cost item added', 'success');
    }
    
    function updateCostCategory(type, amount) {
        const fieldMap = {
            'direct': 'direct_costs',
            'indirect': 'indirect_costs',
            'equipment': 'equipment_costs',
            'travel': 'travel_costs'
        };
        
        const field = document.querySelector(`[name="${fieldMap[type]}"]`);
        if (field) {
            const currentValue = parseFloat(field.value) || 0;
            field.value = (currentValue + amount).toFixed(2);
            updateBudgetPreview();
        }
    }
    
    function renderCostItems() {
        const table = document.getElementById('costItemsTable');
        table.innerHTML = '';
        
        costItems.forEach((item, index) => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-4 py-3 border-b">
                    <span class="px-2 py-1 text-xs rounded ${getCostTypeColor(item.type)}">
                        ${costCategories[item.type] || item.type}
                    </span>
                </td>
                <td class="px-4 py-3 border-b">${escapeHtml(item.description)}</td>
                <td class="px-4 py-3 border-b">${item.quantity}</td>
                <td class="px-4 py-3 border-b">${formatCurrency(item.unitCost)}</td>
                <td class="px-4 py-3 border-b font-bold">${formatCurrency(item.totalCost)}</td>
                <td class="px-4 py-3 border-b">
                    <button onclick="removeCostItem(${index})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            table.appendChild(row);
        });
    }
    
    function getCostTypeColor(type) {
        const colors = {
            'direct': 'bg-pink-100 text-pink-800',
            'indirect': 'bg-blue-100 text-blue-800',
            'equipment': 'bg-amber-100 text-amber-800',
            'travel': 'bg-emerald-100 text-emerald-800'
        };
        return colors[type] || 'bg-gray-100 text-gray-800';
    }
    
    function removeCostItem(index) {
        const item = costItems[index];
        
        // Remove from cost category
        updateCostCategory(item.type, -item.totalCost);
        
        costItems.splice(index, 1);
        renderCostItems();
        showToast('Cost item removed', 'success');
    }
    
    // Update proposal sub-categories
    function updateProposalSubCategories(category) {
        const subSelect = document.getElementById('subCategorySelect');
        subSelect.innerHTML = '<option value="">Select Sub-category</option>';
        
        if (category && tnvsCategories[category]) {
            tnvsCategories[category].forEach(sub => {
                const option = document.createElement('option');
                option.value = sub;
                option.textContent = sub;
                subSelect.appendChild(option);
            });
        }
    }
    
    // Update review summary
    function updateReviewSummary() {
        const direct = parseFloat(document.querySelector('[name="direct_costs"]').value) || 0;
        const indirect = parseFloat(document.querySelector('[name="indirect_costs"]').value) || 0;
        const equipment = parseFloat(document.querySelector('[name="equipment_costs"]').value) || 0;
        const travel = parseFloat(document.querySelector('[name="travel_costs"]').value) || 0;
        const contingency = parseFloat(document.querySelector('[name="contingency_amount"]').value) || 0;
        const total = parseFloat(document.getElementById('totalBudgetField').value) || 0;
        
        // Update amounts
        document.getElementById('reviewDirectCosts').textContent = formatCurrency(direct);
        document.getElementById('reviewIndirectCosts').textContent = formatCurrency(indirect);
        document.getElementById('reviewEquipmentCosts').textContent = formatCurrency(equipment);
        document.getElementById('reviewTravelCosts').textContent = formatCurrency(travel);
        document.getElementById('reviewContingency').textContent = formatCurrency(contingency);
        document.getElementById('reviewTotalBudget').textContent = formatCurrency(total);
        
        // Update percentages
        if (total > 0) {
            document.getElementById('reviewDirectPercentage').textContent = ((direct / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewIndirectPercentage').textContent = ((indirect / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewEquipmentPercentage').textContent = ((equipment / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewTravelPercentage').textContent = ((travel / total) * 100).toFixed(1) + '%';
            document.getElementById('reviewContingencyPercentage').textContent = ((contingency / total) * 100).toFixed(1) + '%';
        }
    }
    
    // Render cost breakdown chart
    function renderCostBreakdownChart() {
        const direct = parseFloat(document.querySelector('[name="direct_costs"]').value) || 0;
        const indirect = parseFloat(document.querySelector('[name="indirect_costs"]').value) || 0;
        const equipment = parseFloat(document.querySelector('[name="equipment_costs"]').value) || 0;
        const travel = parseFloat(document.querySelector('[name="travel_costs"]').value) || 0;
        const contingency = parseFloat(document.querySelector('[name="contingency_amount"]').value) || 0;
        
        const data = [direct, indirect, equipment, travel, contingency];
        const labels = ['Direct Costs', 'Indirect Costs', 'Equipment & Supplies', 'Travel & Expenses', 'Contingency'];
        const colors = ['#ec4899', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6'];
        
        if (costBreakdownChart) {
            costBreakdownChart.destroy();
        }
        
        const ctx = document.getElementById('costBreakdownChart');
        costBreakdownChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 1,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Handle file selection
    function handleFileSelect(input) {
        const fileList = document.getElementById('fileList');
        fileList.innerHTML = '';
        
        for (let i = 0; i < input.files.length; i++) {
            const file = input.files[i];
            const fileElement = document.createElement('div');
            fileElement.className = 'file-preview';
            fileElement.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file text-gray-400 mr-2"></i>
                    <span class="text-sm">${escapeHtml(file.name)} (${formatFileSize(file.size)})</span>
                </div>
                <button type="button" onclick="removeFile(${i})" class="remove-file">
                    <i class="fas fa-times"></i>
                </button>
            `;
            fileList.appendChild(fileElement);
        }
    }
    
    // Remove file
    function removeFile(index) {
        const dt = new DataTransfer();
        const input = document.getElementById('supportingDocs');
        const { files } = input;
        
        for (let i = 0; i < files.length; i++) {
            if (index !== i) {
                dt.items.add(files[i]);
            }
        }
        
        input.files = dt.files;
        handleFileSelect(input);
    }
    
    // Handle file selection
    function handleFileSelect(input) {
        const files = input.files;
        const fileList = document.getElementById('fileList');
        
        if (!fileList) return;
        
        // Clear previous file list
        fileList.innerHTML = '';
        selectedFiles = Array.from(files);
        
        // Display selected files
        Array.from(files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded border border-gray-200';
            fileItem.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-file text-purple-600 mr-2"></i>
                    <span class="text-sm">${escapeHtml(file.name)}</span>
                    <span class="text-xs text-gray-500 ml-2">(${formatFileSize(file.size)})</span>
                </div>
                <button type="button" onclick="removeFile(${index})" class="text-red-600 hover:text-red-800">
                    <i class="fas fa-times"></i>
                </button>
            `;
            fileList.appendChild(fileItem);
        });
    }
    
    // Remove file from selection
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        // Update file input
        const fileInput = document.getElementById('supportingDocs');
        if (fileInput) {
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
            handleFileSelect(fileInput);
        }
    }
    
    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Setup file upload
    function setupFileUpload() {
        const fileInput = document.getElementById('supportingDocs');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                handleFileSelect(this);
            });
        }
    }
    
    // Save draft
    async function saveDraft() {
        const form = document.getElementById('proposalForm');
        const formData = new FormData(form);
        formData.append('action', 'save_proposal_draft');
        
        try {
            showToast('Saving draft...', 'info');
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('newProposalModal');
                resetProposalForm();
                loadProposals();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred: ' + error.message, 'error');
        }
    }
    
    // Submit proposal - FIXED VERSION
    async function submitProposal(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        
        // Validation behavior matching Request Portal
        const proposalTitle = form.querySelector('[name="proposal_title"]')?.value.trim();
        const department = form.querySelector('[name="department"]')?.value;
        const fiscalYear = form.querySelector('[name="fiscal_year"]')?.value;
        const startDate = form.querySelector('[name="start_date"]')?.value;
        const endDate = form.querySelector('[name="end_date"]')?.value;
        const objectives = form.querySelector('[name="project_objectives"]')?.value.trim();
        const totalBudget = parseFloat(form.querySelector('[name="total_budget"]')?.value) || 0;
        const catId = document.getElementById('proposal_category_select').value;
        const subId = document.getElementById('subcategorySelect').value;
        
        if (!proposalTitle || !department || !fiscalYear || !startDate || !endDate || !objectives || !catId || !subId) {
            showToast('Please fill in all required fields marked with *', 'error');
            return;
        }

        // Strict Validation Check
        const subRecord = Object.values(glSubcategories).flat().find(s => s.id == subId);
        if (subRecord && subRecord.parent_id != catId) {
            showToast('Selected subcategory does not match the chosen category.', 'error');
            return;
        }

        const breakdownItems = document.getElementsByName('breakdown_account[]');
        if (breakdownItems.length === 0) {
            showToast("Please add at least one GL account to the budget breakdown.", "error");
            return;
        }

        if (totalBudget <= 0) {
            showToast('Amount must be greater than 0 and calculated from breakdown.', 'error');
            return;
        }
        
        try {
            const submitBtn = document.getElementById('submitProposalBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Submitting...';
            submitBtn.disabled = true;

            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message || 'Proposal submitted successfully!', 'success');
                closeModal('newProposalModal');
                loadProposals();
                loadStats();
                form.reset();
                document.getElementById('breakdownBody').innerHTML = `
                    <tr id="emptyBreakdownRow">
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400 italic font-medium bg-gray-50/30">No GL accounts selected yet.</td>
                    </tr>`;
                document.getElementById('breakdownTotalDisplay').textContent = '₱ 0.00';
            } else {
                showToast(result.message || 'Failed to submit proposal', 'error');
            }
        } catch (error) {
            console.error('Error submitting proposal:', error);
            showToast('An unexpected error occurred', 'error');
        } finally {
            const submitBtn = document.getElementById('submitProposalBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Submit Proposal';
                submitBtn.disabled = false;
            }
        }
    }

    // Hierarchical COA Selection for Proposals
    const glCategories = <?= json_encode($gl_categories) ?>;
    const glSubcategories = <?= json_encode($gl_subcategories) ?>;
    const glAccountsBySub = <?= json_encode($gl_accounts_by_sub) ?>;

    function updateSubcategoriesProp() {
        const catId = document.getElementById('proposal_category_select').value;
        const subSelect = document.getElementById('subcategorySelect');
        const glSelect = document.getElementById('glSelect');
        const errorMsg = document.getElementById('subcategory-error');

        // Reset subcategory and GL select
        subSelect.innerHTML = '<option value="">Select a Subcategory</option>';
        subSelect.disabled = !catId;
        glSelect.innerHTML = '<option value="">Select Subcategory first...</option>';
        glSelect.disabled = true;
        errorMsg.classList.add('hidden');

        if (!catId) return;

        const subs = glSubcategories[catId] || [];
        // Sort subcategories alphabetically
        subs.sort((a, b) => a.name.localeCompare(b.name));

        subs.forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.id;
            option.textContent = sub.name;
            subSelect.appendChild(option);
        });

        // Validation rule: Prevent saving if subcategory does not belong to category (implicit here)
    }

    function filterGLAccountsProp() {
        const subId = document.getElementById('subcategorySelect').value;
        const glSelect = document.getElementById('glSelect');
        const catId = document.getElementById('proposal_category_select').value;
        const errorMsg = document.getElementById('subcategory-error');

        if (!subId) {
            glSelect.disabled = true;
            glSelect.innerHTML = '<option value="">Select Subcategory first...</option>';
            return;
        }

        // Strict Validation Check
        const subcategoryRecord = Object.values(glSubcategories).flat().find(s => s.id == subId);
        if (subcategoryRecord && subcategoryRecord.parent_id != catId) {
            errorMsg.classList.remove('hidden');
            errorMsg.textContent = "Selected subcategory does not match the chosen category.";
            glSelect.disabled = true;
            return;
        } else {
            errorMsg.classList.add('hidden');
        }

        glSelect.disabled = false;
        glSelect.innerHTML = '<option value="">Select a GL Account</option>';

        const accounts = glAccountsBySub[subId] || [];
        // Sort accounts alphabetically
        accounts.sort((a, b) => a.name.localeCompare(b.name));

        accounts.forEach(acc => {
            const option = document.createElement('option');
            option.value = acc.code;
            option.textContent = `${acc.code} - ${acc.name}`;
            glSelect.appendChild(option);
        });
    }

    function addAccountToPropBreakdown() {
        const glCode = document.getElementById('glSelect').value;
        const subId = document.getElementById('subcategorySelect').value;
        const catId = document.getElementById('proposal_category_select').value;
        
        if (!glCode || !subId || !catId) return;

        const accounts = glAccountsBySub[subId] || [];
        const acc = accounts.find(a => a.code == glCode);
        if (!acc) return;

        const catName = glCategories.find(c => c.id == catId)?.name || '';
        const subName = Object.values(glSubcategories).flat().find(s => s.id == subId)?.name || '';

        const existingRow = document.querySelector(`#breakdownBody tr[data-code="${glCode}"]`);
        if (existingRow) {
            showToast('This account is already in the list.', 'info');
            document.getElementById('glSelect').value = '';
            return;
        }

        const tbody = document.getElementById('breakdownBody');
        const emptyRow = document.getElementById('emptyBreakdownRow');
        if (emptyRow) emptyRow.remove();

        const row = document.createElement('tr');
        row.setAttribute('data-code', glCode);
        row.className = "hover:bg-indigo-50/30 transition-colors group";
        row.innerHTML = `
            <td class="px-4 py-4 font-mono font-bold text-indigo-600">${acc.code}</td>
            <td class="px-4 py-4 font-semibold text-gray-700">${acc.name}</td>
            <td class="px-4 py-4 text-gray-500 text-[10px] font-bold uppercase tracking-tight">${catName}</td>
            <td class="px-4 py-4 text-gray-500 text-[10px] font-bold uppercase tracking-tight">${subName}</td>
            <td class="px-4 py-4">
                <div class="relative max-w-[160px] ml-auto">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-indigo-400 font-bold text-[10px]">₱</span>
                    <input type="number" name="proposal_items[${glCode}][total_cost]" required step="0.01" min="0" oninput="calcPropTotal()" class="w-full pl-7 pr-3 py-2.5 border border-indigo-100 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-right font-black text-gray-800 bg-white shadow-sm" placeholder="0.00">
                    <input type="hidden" name="proposal_items[${glCode}][description]" value="${acc.name}">
                    <input type="hidden" name="proposal_items[${glCode}][category_id]" value="${catId}">
                    <input type="hidden" name="proposal_items[${glCode}][subcategory_id]" value="${subId}">
                    <input type="hidden" name="proposal_items[${glCode}][type]" value="direct">
                    <input type="hidden" name="proposal_items[${glCode}][quantity]" value="1">
                    <input type="hidden" name="proposal_items[${glCode}][unit_cost]" value="0">
                    <input type="hidden" name="breakdown_account[]" value="${glCode}">
                </div>
            </td>
            <td class="px-4 py-4 text-center">
                <button type="button" onclick="removePropRow(this)" class="w-9 h-9 rounded-xl flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition-all opacity-0 group-hover:opacity-100 border border-transparent hover:border-red-100 shadow-sm">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        document.getElementById('glSelect').value = '';
    }

    function removePropRow(btn) {
        btn.closest('tr').remove();
        const tbody = document.getElementById('breakdownBody');
        if (tbody.children.length === 0) {
            tbody.innerHTML = `
                <tr id="emptyBreakdownRow">
                    <td colspan="6" class="px-4 py-12 text-center text-gray-400 italic font-medium bg-gray-50/30">No GL accounts selected yet.</td>
                </tr>`;
        }
        calcPropTotal();
    }

    function calcPropTotal() {
        const amounts = document.querySelectorAll('#breakdownBody input[type="number"]');
        let total = 0;
        amounts.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
            const row = input.closest('tr');
            const unitCostInput = row?.querySelector('input[name*="unit_cost"]');
            if (unitCostInput) unitCostInput.value = val;
        });
        
        document.getElementById('breakdownTotalDisplay').textContent = '₱ ' + total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalBudgetInput').value = total.toFixed(2);
    }
    
    // Reset proposal form
    function resetProposalForm() {
        currentStep = 1;
        costItems = [];
        
        // Reset steps (added null checks since form was simplified)
        for (let i = 1; i <= 4; i++) {
            const step = document.getElementById(`proposalStep${i}`);
            const indicator = document.getElementById(`step${i}Indicator`);
            
            if (step) {
                if (i === 1) step.classList.remove('hidden');
                else step.classList.add('hidden');
            }
            
            if (indicator) {
                if (i === 1) {
                    indicator.classList.add('step-active');
                    indicator.classList.remove('step-completed', 'step-pending');
                } else {
                    indicator.classList.remove('step-active', 'step-completed');
                    indicator.classList.add('step-pending');
                }
                indicator.textContent = i;
            }
        }
        
        // Reset form
        const form = document.getElementById('proposalForm');
        if (form) form.reset();
        
        const costItemsTable = document.getElementById('costItemsTable');
        if (costItemsTable) costItemsTable.innerHTML = '';
        
        const fileList = document.getElementById('fileList');
        if (fileList) fileList.innerHTML = '';
        
        // Reset previews (with null checks)
        const totalBudgetPreview = document.getElementById('totalBudgetPreview');
        if (totalBudgetPreview) totalBudgetPreview.textContent = '₱0.00';
        
        const durationPreview = document.getElementById('durationPreview');
        if (durationPreview) durationPreview.textContent = '0 days';
        
        const contingencyPreview = document.getElementById('contingencyPreview');
        if (contingencyPreview) contingencyPreview.textContent = '5%';
        
        const priorityPreview = document.getElementById('priorityPreview');
        if (priorityPreview) priorityPreview.textContent = 'Medium';
        
        // Reset date fields via auto-logic
        updateProposalDates();
        
        // Reset sub-category select
        const subCategorySelect = document.getElementById('subCategorySelect');
        if (subCategorySelect) subCategorySelect.innerHTML = '<option value="">Select Sub-category</option>';
    }
    
    // Modal functions
    function openModal(id) {
        console.log('openModal called with id:', id);
        const modal = document.getElementById(id);
        console.log('Modal element:', modal);
        if (modal) {
            modal.classList.remove('hidden');
            console.log('Modal opened successfully:', id);
            // IMPORTANT: Do NOT add overflow-hidden - it hides the scrollbar!
            
            // Auto-initialize dates when opening relevant modals
            if (id === 'newProposalModal') {
                updateProposalDates();
            } else if (id === 'createPlanModal') {
                updateBudgetDates();
                loadStats(); // Ensure we have the latest budget capacity basis
            }
        } else {
            console.error('Modal not found with id:', id);
        }
    }
    
    function closeModal(id) {
        if (id) {
            document.getElementById(id).classList.add('hidden');
            if (id === 'createPlanModal') {
                const form = document.getElementById('createPlanForm');
                if (form) form.reset();
                const fn = document.getElementById('file-preview-name');
                if (fn) fn.textContent = 'Upload Justification';
                const fs = document.getElementById('file-preview-size');
                if (fs) fs.textContent = 'PDF, XL, IMG';
                document.querySelectorAll('#createPlanForm .gl-input-actual').forEach(input => input.value = '');
                document.querySelectorAll('#createPlanForm .input-integer-only').forEach(input => {
                    input.value = '0';
                    const wrapper = input.closest('.amount-input-wrapper');
                    if(wrapper) {
                        wrapper.classList.remove('is-error');
                        wrapper.style.borderColor = '';
                    }
                    input.classList.remove('!placeholder-red-300', 'text-red-600');
                    input.classList.add('text-indigo-600');
                });
                const totalDisp1 = document.getElementById('current-mapping-total');
                const totalDisp2 = document.getElementById('calculated-total-summary');
                if (totalDisp1) {
                    totalDisp1.textContent = '₱0.00';
                    const badge = totalDisp1.closest('span');
                    if (badge) {
                        badge.classList.add('bg-emerald-50', 'text-emerald-600');
                        badge.classList.remove('bg-red-50', 'text-red-600');
                    }
                }
                if (totalDisp2) {
                    totalDisp2.textContent = '₱0.00';
                    const summaryBox = totalDisp2.parentElement;
                    if (summaryBox) {
                        summaryBox.classList.remove('bg-red-50', 'border-red-200', 'animate-shake');
                        summaryBox.classList.add('bg-indigo-50', 'border-indigo-100');
                    }
                    totalDisp2.classList.remove('text-red-600', 'font-black');
                }
                if (typeof updateGlobalAllocationTotal === 'function') updateGlobalAllocationTotal();
            }
        } else {
            document.querySelectorAll('.fixed.inset-0.z-50').forEach(modal => {
                modal.classList.add('hidden');
            });
        }
    }
    
    // Switch tabs in view plan modal
    function switchViewPlanTab(tab) {
        document.getElementById('viewPlanTab-details').className = 'px-6 py-4 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all';
        document.getElementById('viewPlanTab-document').className = 'hidden px-6 py-4 font-bold text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-all';
        
        document.getElementById('viewPlanContent-details').classList.add('hidden');
        document.getElementById('viewPlanContent-document').classList.add('hidden');
        
        const activeClass = 'px-6 py-4 font-bold text-sm border-b-2 border-purple-600 text-purple-700 bg-purple-50/50 transition-all';
        if (tab === 'details') {
            document.getElementById('viewPlanTab-details').className = activeClass;
            document.getElementById('viewPlanContent-details').classList.remove('hidden');
        } else if (tab === 'document') {
            document.getElementById('viewPlanTab-document').className = activeClass;
            document.getElementById('viewPlanContent-document').classList.remove('hidden');
        }
        
        // Ensure doc tab stays visible if it should be
        const docBtn = document.getElementById('viewPlanTab-document');
        if (docBtn && docBtn.getAttribute('data-has-doc') === 'true') {
            docBtn.classList.remove('hidden');
        } else if (docBtn) {
            docBtn.classList.add('hidden');
            if (tab === 'document') {
                document.getElementById('viewPlanTab-details').className = activeClass;
                document.getElementById('viewPlanContent-details').classList.remove('hidden');
            }
        }
    }

    // Switch tabs in review modal
    function switchReviewTab(tabName) {
        const detailsTab = document.getElementById('detailsTab');
        const documentsTab = document.getElementById('documentsTab');
        const pastTab = document.getElementById('pastTransactionsTab');
        const detailsContent = document.getElementById('detailsTabContent');
        const documentsContent = document.getElementById('documentsTabContent');
        const pastContent = document.getElementById('pastTransactionsTabContent');
        
        // Reset all tabs
        [detailsTab, documentsTab, pastTab].forEach(tab => {
            if (tab) {
                tab.classList.remove('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
                tab.classList.add('border-transparent', 'text-gray-500');
            }
        });
        
        [detailsContent, documentsContent, pastContent].forEach(content => {
            if (content) content.classList.add('hidden');
        });
        
        if (tabName === 'details') {
            detailsTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            detailsTab.classList.remove('border-transparent', 'text-gray-500');
            detailsContent.classList.remove('hidden');
        } else if (tabName === 'documents') {
            documentsTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            documentsTab.classList.remove('border-transparent', 'text-gray-500');
            documentsContent.classList.remove('hidden');
        } else if (tabName === 'past_transactions') {
            pastTab.classList.add('border-purple-600', 'text-purple-700', 'bg-purple-50/50');
            pastTab.classList.remove('border-transparent', 'text-gray-500');
            pastContent.classList.remove('hidden');
        }
    }

    // Modal Action Handlers
    let currentEditItem = null;

    function openCommentModal(category) {
        document.getElementById('commentCategoryLabel').textContent = `Expense Account: ${category}`;
        document.getElementById('commentText').value = '';
        document.getElementById('commentType').value = 'internal_note';
        document.getElementById('commentPriority').value = 'normal';
        openModal('addCommentModal');
    }

    function submitComment() {
        const text = document.getElementById('commentText').value;
        const type = document.getElementById('commentType').value;
        const priority = document.getElementById('commentPriority').value;

        if (!text.trim()) {
            showToast('Please enter a comment', 'warning');
            return;
        }

        // For now, just show success and close
        showToast('Comment posted successfully', 'success');
        closeModal('addCommentModal');
    }

    function openEditLineItem(category, requested, recommended) {
        currentEditItem = { category, requested, recommended };
        
        document.getElementById('editCategory').value = category;
        document.getElementById('editRequestedAmount').value = requested;
        document.getElementById('editRecommendedAmount').value = recommended;
        document.getElementById('editAdjustmentReason').value = '';
        
        updateEditVariance();
        openModal('editLineItemModal');
    }

    function updateEditVariance() {
        const requested = parseFloat(document.getElementById('editRequestedAmount').value) || 0;
        const recommended = parseFloat(document.getElementById('editRecommendedAmount').value) || 0;
        
        // Prev Variance (from initial open)
        const prevRequested = currentEditItem.requested;
        const prevRecommended = currentEditItem.recommended;
        const prevVariance = prevRecommended - prevRequested;
        
        // New Variance
        const newVariance = recommended - requested;
        
        const prevVarEl = document.getElementById('editPrevVariance');
        const newVarEl = document.getElementById('editNewVariance');
        
        prevVarEl.textContent = (prevVariance >= 0 ? '+' : '') + formatCurrency(prevVariance);
        prevVarEl.className = `font-bold ${prevVariance >= 0 ? 'text-emerald-600' : 'text-red-600'}`;
        
        newVarEl.textContent = (newVariance >= 0 ? '+' : '') + formatCurrency(newVariance);
        newVarEl.className = `font-bold ${newVariance >= 0 ? 'text-emerald-600' : 'text-red-600'}`;
    }

    async function saveLineItemEdit() {
        // Implementation for saving the edit
        showToast('Changes saved successfully', 'success');
        closeModal('editLineItemModal');
        // Ideally reload the review modal data here
    }

    function openAdjustPercentage(category, currentAmount) {
        currentEditItem = { category, currentAmount };
        
        document.getElementById('adjustCategoryLabel').textContent = `Expense Account: ${category}`;
        document.getElementById('adjustCurrentAmount').value = formatCurrency(currentAmount);
        document.getElementById('adjustPercentage').value = '';
        document.getElementById('adjustNewAmount').value = formatCurrency(currentAmount);
        document.getElementById('adjustReason').value = '';
        
        openModal('adjustPercentageModal');
    }

    function calculateNewAmount() {
        const currentAmount = currentEditItem.currentAmount;
        const percentage = parseFloat(document.getElementById('adjustPercentage').value) || 0;
        
        const adjustment = currentAmount * (percentage / 100);
        const newAmount = currentAmount + adjustment;
        
        document.getElementById('adjustNewAmount').value = formatCurrency(newAmount);
    }

    function applyPercentageAdjustment() {
        const reason = document.getElementById('adjustReason').value;
        if (!reason.trim()) {
            showToast('Please provide an adjustment reason', 'warning');
            return;
        }
        
        showToast('Percentage adjustment applied', 'success');
        closeModal('adjustPercentageModal');
    }
    
    // Toggle filter menu
    function toggleFilterMenu(event, menuId) {
        event.stopPropagation();
        
        // Close all other menus first
        document.querySelectorAll('.filter-menu').forEach(menu => {
            if (menu.id !== menuId) menu.classList.add('hidden');
        });
        
        const menu = document.getElementById(menuId);
        menu.classList.toggle('hidden');
    }
    
    // Close menus when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.filter-menu') && !event.target.closest('.filter-btn-sleek')) {
            document.querySelectorAll('.filter-menu').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    function applyFilters(type) {
        if (type === 'plans') {
            document.getElementById('planFilterMenu').classList.add('hidden');
            currentPage.plans = 1;
            loadPlans();
        } else if (type === 'proposals') {
            document.getElementById('proposalFilterMenu').classList.add('hidden');
            currentPage.proposals = 1;
            loadProposals();
        }
    }

    function resetFilters(type) {
        if (type === 'plans') {
            document.getElementById('filterYear').value = "0";
            applyFilters('plans');
        } else if (type === 'proposals') {
            document.getElementById('proposalFilterDepartment').value = "";
            document.getElementById('proposalFilterYear').value = "0";
            applyFilters('proposals');
        }
    }
    
    // Toggle my proposals
    function toggleMyProposals() {
        myProposalsOnly = !myProposalsOnly;
        const btn = document.getElementById('myProposalsBtn');
        if (myProposalsOnly) {
            btn.classList.add('bg-blue-100', 'text-blue-800');
            btn.classList.remove('border-gray-300');
        } else {
            btn.classList.remove('bg-blue-100', 'text-blue-800');
            btn.classList.add('border-gray-300');
        }
        currentPage.proposals = 1;
        loadProposals();
    }
    
    // Switch proposal type
    function switchProposalType(type) {
        currentProposalType = type;
        
        // Update tab styles
        document.querySelectorAll('.proposal-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        document.getElementById(`tab${type.charAt(0).toUpperCase() + type.slice(1)}`).classList.add('active');
        
        // Reload proposals
        currentPage.proposals = 1;
        loadProposals();
    }
    
    // Load stats
    
    // Load plans
    async function loadPlans() {
        const search = document.getElementById('searchInput').value;
        const year = document.getElementById('filterYear').value;
        const page = currentPage.plans;
        
        const params = new URLSearchParams({
            ajax: 'load_plans',
            page: page,
            search: search
        });
        
        if (year && year != '0') params.append('year', year);
        
        // Show loading state (subtle)
        document.getElementById('plansTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('plansTableBody').style.opacity = '1';
            document.getElementById('plansLoading').classList.add('hidden');
            
            if (data.success) {
                // Update counts
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('plansPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                // Render table
                const tableBody = document.getElementById('plansTableBody');
                if(tableBody) tableBody.innerHTML = '';
                
                let tableHtml = '';
                if (data.plans && data.plans.length > 0) {
                    data.plans.forEach(plan => {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch(plan.status) {
                            case 'approved':
                                statusClass = 'badge-approved';
                                statusText = 'Approved';
                                break;
                            case 'pending_review':
                                statusClass = 'badge-pending_review';
                                statusText = 'Pending Review';
                                break;
                            case 'archived':
                                statusClass = 'badge-archived';
                                statusText = 'Archived';
                                break;
                            default:
                                statusClass = 'badge-draft';
                                statusText = plan.status;
                        }
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(plan.plan_name)}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-[10px] font-bold uppercase tracking-wider">${escapeHtml(plan.plan_type || 'Yearly')}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    ${plan.start_date ? new Date(plan.start_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'2-digit'}) : 'N/A'} - 
                                    ${plan.end_date ? new Date(plan.end_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'2-digit'}) : 'N/A'}
                                </td>
                                <td class="px-6 py-4 font-bold text-gray-900">${formatCurrency(plan.planned_amount)}</td>
                                <td class="px-6 py-4">
                                    <span class="${statusClass}">${statusText}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex space-x-2">
                                        <button onclick="openViewPlan(${plan.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    tableBody.innerHTML = tableHtml;
                    document.getElementById('plansEmpty').classList.add('hidden');
                } else {
                    document.getElementById('plansTableBody').innerHTML = ''; // Clear table if empty
                    document.getElementById('plansEmpty').classList.remove('hidden');
                }
                
                // Update pagination buttons
                const prevBtn = document.getElementById('plansPrevBtn');
                const nextBtn = document.getElementById('plansNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading plans:', error);
            document.getElementById('plansLoading').classList.add('hidden');
            showToast('Error loading plans: ' + error.message, 'error');
        }
    }
    

    
    // Open archive plan modal

    
    // Load proposals
    async function loadProposals() {
        const search = document.getElementById('proposalSearchInput').value;
        const department = document.getElementById('proposalFilterDepartment').value;
        const year = document.getElementById('proposalFilterYear').value;
        const page = currentPage.proposals;
        
        const params = new URLSearchParams({
            ajax: 'load_proposals',
            page: page,
            search: search,
            my_proposals: myProposalsOnly,
            proposal_type: currentProposalType
        });
        
        if (department) params.append('department', department);
        if (year && year != '0') params.append('year', year);
        
        // Show loading (subtle)
        document.getElementById('proposalsTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('proposalsTableBody').style.opacity = '1';
            document.getElementById('proposalsLoading').classList.add('hidden');
            
            if (data.success) {
                // Update stats
                if (data.stats) {
                    document.getElementById('totalProposals').textContent = data.stats.total || 0;
                    document.getElementById('pendingProposals').textContent = data.stats.pending || 0;
                    document.getElementById('approvedStatCount').textContent = data.stats.approved || 0;
                    document.getElementById('rejectedProposals').textContent = data.stats.rejected || 0;
                    
                    // Update main navigation stats
                    const pendingEl = document.getElementById('pendingPlans');
                    if (pendingEl) {
                         pendingEl.textContent = data.stats.pending || 0;
                    }
                }
                
                // Render table
                let tableHtml = '';
                if (data.proposals && data.proposals.length > 0) {
                    data.proposals.forEach(proposal => {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch(proposal.status) {
                            case 'draft':
                                statusClass = 'badge-draft';
                                statusText = 'Draft';
                                break;
                            case 'submitted':
                                statusClass = 'badge-submitted';
                                statusText = 'Submitted';
                                break;
                            case 'pending_review':
                                statusClass = 'badge-pending_review';
                                statusText = 'Pending Review';
                                break;
                            case 'pending_executive':
                                statusClass = 'badge-pending_executive';
                                statusText = 'Pending Executive Approval';
                                break;
                            case 'approved':
                                statusClass = 'badge-approved';
                                statusText = 'Approved';
                                break;
                            case 'executive_approved':
                                statusClass = 'badge-executive_approved';
                                statusText = 'Executive Approved';
                                break;
                            case 'rejected':
                                statusClass = 'badge-rejected';
                                statusText = 'Rejected';
                                break;
                            default:
                                statusClass = 'badge-draft';
                                statusText = proposal.status;
                        }
                        
                        let priorityClass = '';
                        let priorityText = '';
                        
                        switch(proposal.priority_level) {
                            case 'low':
                                priorityClass = 'badge-priority-low';
                                priorityText = 'Low';
                                break;
                            case 'medium':
                                priorityClass = 'badge-priority-medium';
                                priorityText = 'Medium';
                                break;
                            case 'high':
                                priorityClass = 'badge-priority-high';
                                priorityText = 'High';
                                break;
                            case 'critical':
                                priorityClass = 'badge-priority-critical';
                                priorityText = 'Critical';
                                break;
                            default:
                                priorityClass = 'badge-priority-medium';
                                priorityText = proposal.priority_level;
                        }
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(proposal.proposal_title)}</div>
                                    <div class="text-sm text-gray-500">${proposal.proposal_code}</div>
                                </td>
                                <td class="px-6 py-4">${escapeHtml(proposal.department)}</td>
                                <td class="px-6 py-4 font-bold">${formatCurrency(proposal.requested_amount)}</td>
                                <td class="px-6 py-4">
                                    <span class="${priorityClass}">${priorityText}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="${statusClass}">${statusText}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <button onclick="viewProposal(${proposal.id})" class="px-3 py-1 bg-blue-100 text-blue-800 rounded text-sm hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-eye mr-1"></i> Review
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('proposalsTableBody').innerHTML = tableHtml;
                    document.getElementById('proposalsEmpty').classList.add('hidden');
                } else {
                    document.getElementById('proposalsTableBody').innerHTML = '';
                    document.getElementById('proposalsEmpty').classList.remove('hidden');
                }
                
                // Update pagination
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('proposalsPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                const prevBtn = document.getElementById('proposalsPrevBtn');
                const nextBtn = document.getElementById('proposalsNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading proposals:', error);
            document.getElementById('proposalsLoading').classList.add('hidden');
            showToast('Error loading proposals: ' + error.message, 'error');
        }
    }
    
    // View proposal - FIXED VERSION
    async function viewProposal(proposalId) {
        try {
            const response = await fetch(`?ajax=get_proposal_details&proposal_id=${proposalId}`);
            const data = await response.json();
            
            if (data.success && data.proposal) {
                const proposal = data.proposal;
                currentReviewProposalId = proposalId;
                
                // Build proposal information HTML
                const totalBudget = parseFloat(proposal.total_budget) || 0;
                const statusText = (proposal.status || 'draft').replace('_', ' ').charAt(0).toUpperCase() + 
                                  (proposal.status || 'draft').replace('_', ' ').slice(1);
                
                // Get status badge color
                let statusClass = 'bg-gray-100 text-gray-800';
                switch(proposal.status) {
                    case 'draft': statusClass = 'bg-gray-100 text-gray-800'; break;
                    case 'submitted':
                    case 'pending_review': statusClass = 'bg-yellow-100 text-yellow-800'; break;
                    case 'approved': statusClass = 'bg-green-100 text-green-800'; break;
                    case 'rejected': statusClass = 'bg-red-100 text-red-800'; break;
                }
                
                const modalContent = `
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-200 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-lg font-bold text-gray-900">${escapeHtml(proposal.proposal_title || 'Untitled Proposal')}</h4>
                                <p class="text-sm text-gray-600 mt-1">${proposal.proposal_code || `PROP-${proposal.id}`}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span>
                                <p class="text-xl font-bold text-purple-700 mt-2">${formatCurrency(totalBudget)}</p>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Submitted by: ${proposal.submitted_by || 'Unknown'} on ${new Date(proposal.submitted_at || proposal.created_at || new Date()).toLocaleDateString()}</p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Department</p>
                                <p class="font-medium text-sm">${proposal.department || 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Project Type</p>
                                <p class="font-medium text-sm">${proposal.project_type || 'Operational'}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Fiscal Year</p>
                                <p class="font-medium text-sm">${proposal.fiscal_year || 'N/A'}${proposal.quarter ? ' Q' + proposal.quarter : ''}</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Start Date</p>
                                <p class="font-medium text-sm">${proposal.start_date ? new Date(proposal.start_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">End Date</p>
                                <p class="font-medium text-sm">${proposal.end_date ? new Date(proposal.end_date).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                        
                        <div class="border-t pt-3">
                            <p class="text-xs text-gray-500 mb-1">Description / Purpose</p>
                            <div class="bg-gray-50 rounded p-2 max-h-40 overflow-y-auto">
                                <p class="text-sm text-gray-700">${escapeHtml(proposal.project_objectives || proposal.justification || 'Not provided')}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('reviewModalContent').innerHTML = modalContent;
                
                // Fetch Budget data for table rendering
                    try {
                    const budgetRes = await fetch(`?ajax=get_budget&department=${encodeURIComponent(proposal.department)}&year=${proposal.fiscal_year}`);
                    const budgetData = await budgetRes.json();
                    
                    const glBudgets = budgetData.gl_budgets || {};
                    const glActuals = budgetData.gl_actuals || {};
                    
                    const breakdownContent = document.getElementById('costBreakdownContent');

                    if (proposal.detailed_breakdown_array && proposal.detailed_breakdown_array.length > 0) {
                        
                        // Bottom Section: Detailed Table
                        let tableRows = '';
                        let totalRequested = 0;
                        
                        proposal.detailed_breakdown_array.forEach(item => {
                            const accountCode = item.account_code || '';
                            const allocated = glBudgets[accountCode] || 0;
                            
                            // Sample Data Logic for demo
                            let actualLastYear = glActuals[accountCode] || 0;
                            if (actualLastYear === 0) {
                                // Generate a sample value between 70% and 120% of requested amount for demo
                                actualLastYear = parseFloat(item.amount) * (0.7 + Math.random() * 0.5);
                            }

                            const requested = parseFloat(item.amount) || 0;
                            const financeRec = requested; // Same as requested by default
                            const variance = financeRec - requested;
                            
                            totalRequested += requested;

                            // Category icon based on category name
                            let categoryIcon = 'fa-folder';
                            const catLower = (item.category || '').toLowerCase();
                            if (catLower.includes('salary') || catLower.includes('benefit')) categoryIcon = 'fa-user';
                            else if (catLower.includes('marketing')) categoryIcon = 'fa-bullhorn';
                            else if (catLower.includes('software') || catLower.includes('tool')) categoryIcon = 'fa-laptop';

                            tableRows += `
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-5 py-4 border-b border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                                                <i class="fas ${categoryIcon} text-indigo-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">${escapeHtml(item.name || item.description || 'General')}</p>
                                                <p class="text-xs text-gray-500">${escapeHtml(item.account_code || 'N/A')}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-gray-900">${formatCurrency(requested)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-gray-500">${formatCurrency(actualLastYear)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-emerald-600">${formatCurrency(financeRec)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold ${variance >= 0 ? 'text-emerald-600' : 'text-red-600'}">${variance >= 0 ? '+' : ''}${formatCurrency(variance)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openCommentModal('${escapeHtml(item.name || item.description || 'General')}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Add Comment">
                                                <i class="fas fa-comment-alt"></i>
                                            </button>
                                            <button onclick="openEditLineItem('${escapeHtml(item.name || item.description || 'General')}', ${requested}, ${financeRec})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Edit Amount">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openAdjustPercentage('${escapeHtml(item.name || item.description || 'General')}', ${financeRec})" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" title="Adjust by Percentage">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        breakdownContent.innerHTML = tableRows;
                        
                        // Update final review amount
                        document.getElementById('finalReviewAmount').textContent = formatCurrency(totalRequested);
                        
                    } else if (proposal.items && proposal.items.length > 0) {
                        // Fallback for simple items
                        
                        let itemsHtml = '';
                        let totalRequested = 0;
                        
                        proposal.items.forEach(item => {
                            const desc = item.description || 'No description';
                            const total = parseFloat(item.total_cost || item.amount || 0);
                            
                            // Sample Data Logic for demo
                            let actualLastYear = 0;
                            // Generate a sample value between 70% and 120% of total for demo
                            actualLastYear = total * (0.7 + Math.random() * 0.5);

                            totalRequested += total;

                            itemsHtml += `
                                <tr class="hover:bg-gray-50 transition-all">
                                    <td class="px-5 py-4 border-b border-gray-100">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                                                <i class="fas fa-box text-gray-600"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900">${escapeHtml(desc)}</p>
                                                <p class="text-xs text-gray-500">${escapeHtml(item.account_code || 'GL Account')}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-gray-900">${formatCurrency(total)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-gray-500">${formatCurrency(actualLastYear)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-black text-emerald-600">${formatCurrency(total)}</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-right">
                                        <p class="font-bold text-emerald-600">0.00</p>
                                    </td>
                                    <td class="px-5 py-4 border-b border-gray-100 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="openCommentModal('${escapeHtml(desc)}')" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Add Comment">
                                                <i class="fas fa-comment-alt"></i>
                                            </button>
                                            <button onclick="openEditLineItem('${escapeHtml(desc)}', ${total}, ${total})" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Edit Amount">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openAdjustPercentage('${escapeHtml(desc)}', ${total})" class="p-2 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors" title="Adjust by Percentage">
                                                <i class="fas fa-percentage"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                        breakdownContent.innerHTML = itemsHtml;
                        
                        // Update final review amount
                        document.getElementById('finalReviewAmount').textContent = formatCurrency(totalRequested);
                    }

                } catch (e) {
                    console.error('Error loading budget data:', e);
                }
                
                // Document Viewer - Always initialize automated view, then check for files
                renderAutoGeneratedProposal(proposal);
                renderPastTransactions(proposal);

                displaySupportingDocuments(proposal.supporting_docs_array || []);
                
                // Update proposal code in header
                document.getElementById('viewProposalCode').textContent = proposal.proposal_code || `PROP-${proposal.id}`;
                
                // Ensure Details tab is active when modal opens
                switchReviewTab('details');
                
                // Open modal
                openModal('proposalReviewModal');
                
                // Show/hide action buttons based on status
                const approveBtn = document.querySelector('button[onclick="approveFromReview()"]');
                const rejectBtn = document.querySelector('button[onclick="showRejectFromReview()"]');
                
                if (proposal.status === 'pending_review' || proposal.status === 'submitted') {
                    approveBtn.classList.remove('hidden');
                    rejectBtn.classList.remove('hidden');
                } else {
                    approveBtn.classList.add('hidden');
                    rejectBtn.classList.add('hidden');
                }
            } else {
                showToast(data.message || 'Failed to load proposal details', 'error');
            }
        } catch (error) {
            console.error('Error loading proposal:', error);
            showToast('Error loading proposal details: ' + error.message, 'error');
        }
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        
        let icon = 'fa-info-circle';
        let bgColor = 'bg-blue-50';
        let borderColor = 'border-blue-200';
        let textColor = 'text-blue-800';
        
        switch(type) {
            case 'success':
                icon = 'fa-check-circle';
                bgColor = 'bg-green-50';
                borderColor = 'border-green-200';
                textColor = 'text-green-800';
                break;
            case 'error':
                icon = 'fa-times-circle';
                bgColor = 'bg-red-50';
                borderColor = 'border-red-200';
                textColor = 'text-red-800';
                break;
            case 'warning':
                icon = 'fa-exclamation-triangle';
                bgColor = 'bg-yellow-50';
                borderColor = 'border-yellow-200';
                textColor = 'text-yellow-800';
                break;
        }
        
        toast.className = `toast ${bgColor} ${borderColor} ${textColor} border rounded-lg shadow-lg p-4 max-w-sm`;
        
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${icon} mr-3"></i>
                <div class="flex-1">${escapeHtml(message)}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
    
    // Approve proposal
    
    // Review modal action functions (matching payables_ia.php style)
    function showCustomConfirm(title, message, icon, onConfirm, colorClass = 'indigo') {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');
        const iconEl = document.getElementById('confirmModalIcon');
        const headerEl = document.getElementById('confirmModalHeader');
        const proceedBtn = document.getElementById('confirmProceedBtn');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        iconEl.className = `fas ${icon} text-3xl`;
        
        // Dynamic colors
        headerEl.className = `p-6 bg-gradient-to-r from-${colorClass}-600 to-${colorClass}-700 text-white`;
        proceedBtn.className = `flex-1 px-6 py-3 bg-${colorClass}-600 text-white rounded-xl font-bold hover:bg-${colorClass}-700 transition-all shadow-md hover:shadow-lg`;
        
        proceedBtn.onclick = async () => {
            closeModal('confirmModal');
            await onConfirm();
        };
        
        openModal('confirmModal');
    }

    async function approveFromReview() {
        if (!currentReviewProposalId) {
            showToast('No proposal selected', 'error');
            return;
        }
        
        showCustomConfirm(
            'Approve Proposal',
            'Are you sure you want to approve this budget proposal? This will mark it as approved for the next strategic phase.',
            'fa-check-circle',
            async () => {
                await updateProposalStatus(currentReviewProposalId, 'approved');
            },
            'green'
        );
    }
    
    function showRejectFromReview() {
        document.getElementById('rejectFormInReview').classList.remove('hidden');
    }
    
    function cancelRejectFromReview() {
        document.getElementById('rejectFormInReview').classList.add('hidden');
        document.getElementById('rejectReasonInReview').value = '';
    }
    
    async function confirmRejectFromReview() {
        const reason = document.getElementById('rejectReasonInReview').value.trim();
        
        if (!reason) {
            showToast('Please provide a reason for rejection', 'error');
            return;
        }
        
        if (!currentReviewProposalId) {
            showToast('No proposal selected', 'error');
            return;
        }
        
        await updateProposalStatus(currentReviewProposalId, 'rejected', reason);
        cancelRejectFromReview();
    }
    
    async function approveProposal() {
        await updateProposalStatus(currentReviewProposalId, 'approved');
    }
    
    // Load and update dashboard statistics/cards
    async function loadStats() {
        try {
            console.log('Loading stats...');
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`?ajax=get_stats&_=${timestamp}`);
            const data = await response.json();
            
            console.log('Stats data received:', data);
            
            if (data.success && data.stats) {
                const stats = data.stats;
                console.log('Updating cards with stats:', stats);
                
                // Update Revenue Card (New)
                const revenueEl = document.getElementById('totalRevenue');
                if (revenueEl) {
                    revenueEl.textContent = formatCurrency(stats.total_revenue || 0);
                }

                // Update Allocated Budget card
                const totalBudgetEl = document.getElementById('totalBudget');
                if (totalBudgetEl) {
                    totalBudgetEl.textContent = formatCurrency(stats.total_planned || 0);
                }
                
                const totalActual = stats.total_actual || 0;
                const totalPlanned = stats.total_planned || 0;
                const totalBudgetMetricEl = document.getElementById('totalBudgetMetric');
                if (totalBudgetMetricEl) {
                    totalBudgetMetricEl.innerHTML = 
                        `<span class="metric-neutral">vs Actual: ${formatCurrency(totalActual)}</span>`;
                }
                
                // Update Approved Plans card
                const approvedCount = stats.approved_plans || 0;
                const totalPlans = approvedCount + (stats.draft_plans || 0) + (stats.pending_plans || 0);
                const approvedPlansEl = document.getElementById('approvedPlans');
                if (approvedPlansEl) {
                    approvedPlansEl.textContent = approvedCount;
                }
                
                const approvedPlansMetricEl = document.getElementById('approvedPlansMetric');
                if (approvedPlansMetricEl) {
                    approvedPlansMetricEl.innerHTML = 
                        `<span class="metric-neutral">out of ${totalPlans} total</span>`;
                }
                
                // Update Pending Review card (Use Pending Plans count, matching the table)
                const pendingPlansEl = document.getElementById('pendingPlans');
                if (pendingPlansEl) {
                    pendingPlansEl.textContent = stats.pending_plans || 0;
                }
                
                const pendingPlansMetricEl = document.getElementById('pendingPlansMetric');
                if (pendingPlansMetricEl) {
                    pendingPlansMetricEl.innerHTML = 
                        `<span class="metric-neutral">plans pending review</span>`;
                }
                
                // Update Remaining (Unallocated) Budget card
                const remaining = stats.remaining_budget || 0;
                const totalRevenue = stats.total_revenue || 0;
                const totalAllocated = stats.total_planned || 0;
                
                const remainingBudgetEl = document.getElementById('remainingBudget');
                if (remainingBudgetEl) {
                    remainingBudgetEl.textContent = formatCurrency(remaining);
                }
                
                const remainingPercent = totalRevenue > 0 ? ((remaining / totalRevenue) * 100).toFixed(1) : 0;
                const remainingBudgetMetricEl = document.getElementById('remainingBudgetMetric');
                if (remainingBudgetMetricEl) {
                    remainingBudgetMetricEl.innerHTML = 
                        remainingPercent >= 0 ?
                        `<span class="metric-positive">${remainingPercent}% of Revenue</span>` :
                        `<span class="metric-negative">${remainingPercent}% (Over Allocated)</span>`;
                }

                console.log('Stats loaded successfully!');

                // Update global budget constraints
                globalTotalRevenue = stats.total_revenue || 0;
                globalRemainingBudget = stats.remaining_budget || 0;

                const modalBasis = document.getElementById('modal-available-budget');
                if (modalBasis) {
                    modalBasis.textContent = formatCurrency(globalRemainingBudget);
                }
                updateGlobalAllocationTotal();
            } else {
                console.error('Stats response not successful:', data);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }
    
    // Apply amount adjustment
    async function applyAmountAdjustment() {
        const adjustedAmount = document.getElementById('adjustedAmount').value;
        if (!adjustedAmount || isNaN(adjustedAmount) || parseFloat(adjustedAmount) <= 0) {
            showToast('Please enter a valid amount', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'update_proposal_status');
            formData.append('proposal_id', currentReviewProposalId);
            formData.append('status', 'pending_review');
            formData.append('adjusted_amount', adjustedAmount);
            formData.append('notes', `Amount adjusted from ${document.getElementById('originalAmount').textContent} to ${formatCurrency(adjustedAmount)}`);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(`Amount adjusted to ${formatCurrency(adjustedAmount)}`, 'success');
                // Update the display
                document.getElementById('reviewRequestedAmount').textContent = formatCurrency(adjustedAmount);
                document.getElementById('originalAmount').textContent = formatCurrency(adjustedAmount);
                
                // Reload proposal to update all data
                viewProposal(currentReviewProposalId);
                loadStats(); // Update dashboard cards
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error applying adjustment:', error);
            showToast('Error applying amount adjustment', 'error');
        }
    }
    
    // Show rejection section
    function showRejectionSection() {
        document.getElementById('rejectionSection').classList.remove('hidden');
    }
    
    // Hide rejection section
    function hideRejectionSection() {
        document.getElementById('rejectionSection').classList.add('hidden');
        document.getElementById('rejectionReason').value = '';
    }
    
    // Submit rejection
    async function submitRejection() {
        const reason = document.getElementById('rejectionReason').value;
        if (!reason.trim()) {
            showToast('Please provide a rejection reason', 'error');
            return;
        }
        
        await updateProposalStatus(currentReviewProposalId, 'rejected', reason);
        hideRejectionSection();
    }
    
    // Update proposal status
    async function updateProposalStatus(proposalId, status, reason = '') {
        const formData = new FormData();
        formData.append('action', 'update_proposal_status');
        formData.append('proposal_id', proposalId);
        formData.append('status', status);
        
        // Get adjusted amount if it exists in the review modal
        const adjustedInput = document.getElementById('reviewAdjustedAmount');
        if (adjustedInput) {
            const adjustedVal = adjustedInput.value.replace(/[^0-9.]/g, '');
            if (adjustedVal && !isNaN(adjustedVal) && parseFloat(adjustedVal) > 0) {
                formData.append('adjusted_amount', adjustedVal);
            }
        }
        
        if (reason) {
            if (status === 'rejected') {
                formData.append('rejection_reason', reason);
            }
            formData.append('notes', reason);
        }
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('proposalReviewModal');
                loadProposals();
                loadPlans();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Add proposal comment
    async function addProposalComment(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'add_proposal_comment');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                form.reset();
                // Reload proposal to show new comment
                viewProposal(currentReviewProposalId);
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Save forecast
    async function saveForecast(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'save_forecast');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('forecastModal');
                form.reset();
                loadMonitoringData();
                loadStats(); // Update dashboard cards
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Archive plan
    async function archivePlan(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'archive_plan');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('archiveModal');
                form.reset();
                loadPlans();
                loadArchived();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Restore plan
    async function restorePlan(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'restore_plan');
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('restoreModal');
                form.reset();
                loadPlans();
                loadArchived();
                loadStats();
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Network error occurred', 'error');
        }
    }
    
    // Open restore plan modal
    function openRestorePlan(archiveId) {
        document.getElementById('restoreArchiveId').value = archiveId;
        openModal('restoreModal');
    }
    
    // Load monitoring data
    async function loadMonitoringData() {
        let year, department;
        if (currentMonitoringView === 'department') {
            year = document.getElementById('deptMonitoringYear')?.value || <?php echo $current_year; ?>;
            department = document.getElementById('deptMonitoringDepartment')?.value || '';
        } else {
            year = document.getElementById('monitoringYear')?.value || <?php echo $current_year; ?>;
            department = document.getElementById('monitoringDepartment')?.value || '';
        }
        
        const params = new URLSearchParams({ 
            ajax: 'load_monitoring_data',
            year: year,
            page: currentPage.monitoring
        });
        if (department) params.append('department', department);
        
        // Show loading (subtle)
        document.getElementById('monitoringTableBody').style.opacity = '0.5';
        document.getElementById('deptMonitoringTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?${params}`);
            const data = await response.json();
            
            document.getElementById('monitoringTableBody').style.opacity = '1';
            document.getElementById('deptMonitoringTableBody').style.opacity = '1';
            document.getElementById('monitoringLoading')?.classList.add('hidden');
            document.getElementById('deptMonitoringLoading')?.classList.add('hidden');
            
            if (data.success) {
                // Update alerts count
                document.getElementById('alertCount').textContent = data.alerts?.length || 0;
                document.getElementById('alertsCount').textContent = data.alerts?.length || 0;
                
                // Render alerts
                let alertsHtml = '';
                if (data.alerts && data.alerts.length > 0) {
                    data.alerts.forEach(alert => {
                        alertsHtml += `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-red-800">${escapeHtml(alert.message)}</p>
                                        <p class="text-xs text-red-600 mt-1">${new Date(alert.created_at).toLocaleDateString()}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    alertsHtml = '<p class="text-gray-500 text-center py-4">No active alerts</p>';
                }
                document.getElementById('alertsList').innerHTML = alertsHtml;
                
                // Render monitoring table by GL Category
                let tableHtml = '';
                if (data.monitoring_data && data.monitoring_data.length > 0) {
                    data.monitoring_data.forEach(item => {
                        const variance = item.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        const utilization = item.utilization || 0;
                        let utilizationClass = 'bg-green-500';
                        if (utilization > 90) utilizationClass = 'bg-red-500';
                        else if (utilization > 70) utilizationClass = 'bg-yellow-500';
                        
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">${escapeHtml(item.category || 'Uncategorized')}</td>
                                <td class="px-6 py-4">${escapeHtml(item.gl_category || 'Uncategorized')}</td>
                                <td class="px-6 py-4">${item.dept_count} departments</td>
                                <td class="px-6 py-4">${formatCurrency(item.planned)}</td>
                                <td class="px-6 py-4">${formatCurrency(item.actual)}</td>
                                <td class="px-6 py-4 ${varianceClass}">
                                    ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                    (${variance >= 0 ? '+' : ''}${(item.variance_percentage || 0).toFixed(1)}%)
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full ${utilizationClass}" style="width: ${Math.min(utilization, 100)}%"></div>
                                        </div>
                                        <span class="text-sm">${utilization.toFixed(1)}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('monitoringTableBody').innerHTML = tableHtml;
                    
                    // Update pagination info
                    if (data.pagination) {
                        const { total, page, pages, per_page } = data.pagination;
                        const start = total === 0 ? 0 : ((page - 1) * per_page) + 1;
                        const end = Math.min(page * per_page, total);
                        document.getElementById('monitoringPaginationInfo').textContent = 
                            `Showing ${start} to ${end} of ${total} entries`;
                        
                        const prevBtn = document.getElementById('monitoringPrevBtn');
                        const nextBtn = document.getElementById('monitoringNextBtn');
                        
                        prevBtn.disabled = page <= 1;
                        nextBtn.disabled = page >= pages;
                    }
                }
                
                // Render monitoring table by department
                let deptTableHtml = '';
                if (data.dept_monitoring_data && data.dept_monitoring_data.length > 0) {
                    data.dept_monitoring_data.forEach(item => {
                        const variance = item.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        const utilization = item.utilization || 0;
                        let utilizationClass = 'bg-green-500';
                        if (utilization > 90) utilizationClass = 'bg-red-500';
                        else if (utilization > 70) utilizationClass = 'bg-yellow-500';
                        
                        deptTableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">${escapeHtml(item.department)}</td>
                                <td class="px-6 py-4">${item.category_count} categories</td>
                                <td class="px-6 py-4">${formatCurrency(item.planned)}</td>
                                <td class="px-6 py-4">${formatCurrency(item.actual)}</td>
                                <td class="px-6 py-4 ${varianceClass}">
                                    ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                    (${variance >= 0 ? '+' : ''}${(item.variance_percentage || 0).toFixed(1)}%)
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-full ${utilizationClass}" style="width: ${Math.min(utilization, 100)}%"></div>
                                        </div>
                                        <span class="text-sm">${utilization.toFixed(1)}%</span>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('deptMonitoringTableBody').innerHTML = deptTableHtml;
                }
                
                // Render forecasts
                let forecastsHtml = '';
                if (data.forecasts && data.forecasts.length > 0) {
                    data.forecasts.forEach(forecast => {
                        const variance = forecast.variance;
                        const varianceClass = variance >= 0 ? 'text-green-600' : 'text-red-600';
                        
                        forecastsHtml += `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-blue-800">${escapeHtml(forecast.department)} - ${escapeHtml(forecast.category)}</p>
                                        <p class="text-xs text-blue-600 mt-1">${forecast.forecast_period}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold">${formatCurrency(forecast.forecasted_amount)}</p>
                                        <p class="text-sm ${varianceClass}">
                                            ${variance >= 0 ? '+' : ''}${formatCurrency(Math.abs(variance))}
                                            (${variance >= 0 ? '+' : ''}${(forecast.variance_percentage || 0).toFixed(1)}%)
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    forecastsHtml = '<p class="text-gray-500 text-center py-4">No forecasts available</p>';
                }
                document.getElementById('forecastsList').innerHTML = forecastsHtml;
                
                // Update charts
                updateMonitoringChart(data.chart_data);
            }
        } catch (error) {
            console.error('Error loading monitoring data:', error);
            document.getElementById('monitoringLoading')?.classList.add('hidden');
            document.getElementById('deptMonitoringLoading')?.classList.add('hidden');
            showToast('Error loading monitoring data: ' + error.message, 'error');
        }
    }
    
    // Update monitoring chart
    function updateMonitoringChart(data) {
        if (!data || data.length === 0) return;
        
        const categories = data.map(item => item.category || 'Uncategorized').slice(0, 8);
        const planned = data.map(item => item.planned || 0).slice(0, 8);
        const actual = data.map(item => item.actual || 0).slice(0, 8);
        
        const ctx = document.getElementById('monitoringChart');
        
        if (monitoringChart) {
            monitoringChart.destroy();
        }
        
        monitoringChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categories,
                datasets: [
                    {
                        label: 'Planned',
                        data: planned,
                        backgroundColor: 'rgba(124, 58, 237, 0.7)',
                        borderColor: 'rgba(124, 58, 237, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual',
                        data: actual,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
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
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += formatCurrency(context.raw);
                                return label;
                            }
                        }
                    }
                },
                scales: {
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
    }
    
    // Update department chart
    async function updateDepartmentChart(year) {
        try {
            const response = await fetch(`?ajax=get_department_stats&year=${year}`);
            const data = await response.json();
            
            if (data.success && data.departments && data.departments.length > 0) {
                const departments = data.departments.map(dept => dept.department);
                const budgets = data.departments.map(dept => dept.budget || 0);
                const spent = data.departments.map(dept => dept.spent || 0);
                
                const ctx = document.getElementById('departmentChart');
                
                if (departmentChart) {
                    departmentChart.destroy();
                }
                
                departmentChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: departments,
                        datasets: [
                            {
                                label: 'Budget',
                                data: budgets,
                                backgroundColor: 'rgba(124, 58, 237, 0.7)',
                                borderColor: 'rgba(124, 58, 237, 1)',
                                borderWidth: 1
                            },
                            {
                                label: 'Spent',
                                data: spent,
                                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                borderColor: 'rgba(16, 185, 129, 1)',
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
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        label += formatCurrency(context.raw);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
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
            }
        } catch (error) {
            console.error('Error loading department chart data:', error);
        }
    }
    
    // Load archived
    async function loadArchived() {
        const page = currentPage.archived;
        
        // Show loading (subtle)
        document.getElementById('archivedTableBody').style.opacity = '0.5';
        
        try {
            const response = await fetch(`?ajax=load_archived&page=${page}`);
            const data = await response.json();
            
            document.getElementById('archivedTableBody').style.opacity = '1';
            document.getElementById('archivedLoading').classList.add('hidden');
            
            if (data.success) {
                // Update counts
                const start = ((page - 1) * 10) + 1;
                const end = Math.min(page * 10, data.total);
                document.getElementById('archivedPaginationInfo').textContent = 
                    `Showing ${start} to ${end} of ${data.total} entries`;
                
                // Render table
                let tableHtml = '';
                if (data.data && data.data.length > 0) {
                    data.data.forEach(archived => {
                        tableHtml += `
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">${escapeHtml(archived.plan_name)}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">${escapeHtml(archived.department)}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-slate-50 text-slate-600 rounded text-[10px] font-bold uppercase tracking-wider">${escapeHtml(archived.plan_type || 'Yearly')}</span>
                                </td>
                                <td class="px-6 py-4 text-sm">${new Date(archived.archived_at).toLocaleDateString()}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-yellow-100 text-yellow-800 text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider">${archived.archive_reason || 'Archived'}</span>
                                </td>
                                <td class="px-6 py-4 font-bold">${formatCurrency(archived.planned_amount)}</td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <button onclick="openRestorePlan(${archived.id})" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Restore Plan">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                    document.getElementById('archivedTableBody').innerHTML = tableHtml;
                } else {
                    document.getElementById('archivedTableBody').innerHTML = '';
                }
                
                // Update pagination buttons
                const prevBtn = document.getElementById('archivedPrevBtn');
                const nextBtn = document.getElementById('archivedNextBtn');
                
                prevBtn.disabled = page <= 1;
                nextBtn.disabled = page >= data.pages;
                
                if (page <= 1) {
                    prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
                
                if (page >= data.pages) {
                    nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        } catch (error) {
            console.error('Error loading archived:', error);
            document.getElementById('archivedLoading').classList.add('hidden');
            showToast('Error loading archived data: ' + error.message, 'error');
        }
    }
    
    // Pagination functions
    function previousPage(type) {
        if (currentPage[type] > 1) {
            currentPage[type]--;
            if (type === 'plans') loadPlans();
            else if (type === 'proposals') loadProposals();
            else if (type === 'archived') loadArchived();
        }
    }
    
    function nextPage(type) {
        currentPage[type]++;
        if (type === 'plans') loadPlans();
        else if (type === 'proposals') loadProposals();
        else if (type === 'archived') loadArchived();
    }
    
    // Debounce search functions
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage.plans = 1;
            loadPlans();
        }, 500);
    }
    
    function debounceProposalSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentPage.proposals = 1;
            loadProposals();
        }, 500);
    }
    // Create Plan Modal Logic
    function validateField(input) {
        const msg = input.parentElement.querySelector('.validation-msg');
        if (!input.value.trim()) {
            input.classList.add('border-red-500', 'bg-red-50');
            if (msg) msg.classList.remove('hidden');
        } else {
            input.classList.remove('border-red-500', 'bg-red-50');
            if (msg) msg.classList.add('hidden');
        }
        validateCreateBudgetPlan();
    }

    function validateCreateBudgetPlan() {
        const modal = document.getElementById('createPlanModal');
        if (!modal || modal.classList.contains('hidden')) return;

        const form = document.getElementById('createPlanForm');
        const submitBtn = document.getElementById('submit-btn');
        if (!form || !submitBtn) return;

        const requiredFields = form.querySelectorAll('input[required], textarea[required]');
        let allFilled = true;
        requiredFields.forEach(field => {
            if (!field.value.trim() || field.value === "0.00" || field.value === "0") {
                if (field.name !== 'total_budget') { // Budget is auto-calculated
                    allFilled = false;
                }
            }
        });

        // Calculate allocation sum from the actual hidden fields
        let allocationSum = 0;
        form.querySelectorAll('.gl-input-actual').forEach(input => {
            allocationSum += parseFloat(input.value) || 0;
        });

        const isOverBudget = allocationSum > globalRemainingBudget && globalRemainingBudget > 0;
        const hasAllocation = allocationSum > 0;
        
        const isValid = allFilled && hasAllocation && !isOverBudget;

        if (isValid) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'grayscale', 'cursor-not-allowed', 'transform-none');
            submitBtn.innerHTML = `Save Budget Plan <i class="fas fa-check-circle ml-1 animate-pulse"></i>`;
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'grayscale', 'cursor-not-allowed', 'transform-none');
            
            let statusText = "Check Highlights";
            if (!allFilled) statusText = "Fill Required Fields";
            else if (!hasAllocation) statusText = "Allocate Amounts";
            else if (isOverBudget) statusText = "Over Budget Limit";
            
            submitBtn.innerHTML = `${statusText} <i class="fas fa-exclamation-triangle ml-1"></i>`;
        }
    }

    // Add listeners for real-time validation
    document.addEventListener('input', function(e) {
        if (e.target.closest('#createPlanForm')) {
            validateCreateBudgetPlan();
        }
    });

    // defunct impact circle logic removed

    function previewFile(input) {
        const file = input.files[0];
        const previewName = document.getElementById('file-preview-name');
        
        if (file) {
            if (previewName) previewName.textContent = file.name;
        } else {
            if (previewName) previewName.textContent = 'Drop file here or click to upload';
        }
    }

    function setBudgetType(el, type) {
        const form = document.getElementById('createPlanForm');
        if (!form) return;

        // Update hidden input
        document.getElementById('plan_type_input').value = type;
        
        // Update labels for computed section
        const wageLabel = document.getElementById('wage-label');
        const taxLabel = document.getElementById('tax-label');
        if (wageLabel) wageLabel.textContent = (type === 'yearly' ? 'Yearly' : 'Monthly') + " Base Wage";
        if (taxLabel) taxLabel.textContent = (type === 'yearly' ? 'Yearly' : 'Monthly') + " Taxation Cost";
        
        validateCreateBudgetPlan();
        updateBudgetDates();
    }


    function previewFile(input) {
        const file = input.files[0];
        const previewName = document.getElementById('file-preview-name');
        const previewSize = document.getElementById('file-preview-size');
        const previewIcon = document.getElementById('file-preview-icon');
        
        if (file) {
            if (previewName) previewName.textContent = file.name;
            if (previewSize) previewSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            if (previewIcon) {
                previewIcon.classList.remove('text-slate-300');
                previewIcon.classList.add('text-indigo-600');
                
                if (file.type.includes('image')) {
                    previewIcon.innerHTML = '<i class="fas fa-file-image text-3xl"></i>';
                } else if (file.type.includes('pdf')) {
                    previewIcon.innerHTML = '<i class="fas fa-file-pdf text-3xl"></i>';
                } else {
                    previewIcon.innerHTML = '<i class="fas fa-file-csv text-3xl"></i>';
                }
            }
        }
    }

    // Handle Create Plan Form Submission
    // Handle Create Plan Form Submission
    document.getElementById('createPlanForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'create_plan');
        
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (pErr) {
                console.error('Create Plan Single Modal Validation Error: JSON Parse Failed', text);
                throw new Error("Create Plan Single Modal Validation Error: Server returned invalid JSON");
            }
            
            if (data.success) {
                showToast('Budget plan created successfully!', 'success');
                closeModal('createPlanModal');
                
                // Reset Form
                this.reset();
                const feedback = document.getElementById('allocation-status-feedback');
                if (feedback) feedback.innerHTML = '';
                
                loadPlans(); // Refresh the list
                loadStats(); // Update dashboard cards
            } else {
                console.error("Create Plan Single Modal Validation Error:", data.message);
                showToast(data.message || 'Failed to submit budget plan', 'error');
            }
        } catch (error) {
            console.error('Create Plan Single Modal Validation Error:', error);
            showToast('An error occurred while submitting the plan.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            validateCreateBudgetPlan();
        }
    });
    
    // Open View Plan Modal
    async function openViewPlan(planId) {
        try {
            const response = await fetch(`?ajax=get_plan_details&plan_id=${planId}`);
            const data = await response.json();
            
            if (data.success && data.plan) {
                const plan = data.plan;
                
                // Populate modal fields
                document.getElementById('viewPlanTitle').textContent = plan.plan_name || 'Plan Details';
                document.getElementById('viewPlanCode').textContent = plan.plan_code || ('BATCH-' + plan.created_at.replace(/[- :]/g, ''));
                document.getElementById('viewPlanAmount').textContent = formatCurrency(parseFloat(plan.planned_amount || 0));
                document.getElementById('viewPlanFooterAmount').textContent = formatCurrency(parseFloat(plan.planned_amount || 0));
                document.getElementById('viewPlanYear').textContent = plan.plan_year || '';
                document.getElementById('viewPlanType').textContent = (plan.plan_type || 'yearly').toUpperCase();
                
                // Status Badge
                const statusBadge = document.getElementById('viewPlanStatusBadge');
                if (statusBadge) {
                    const status = (plan.status || 'approved').toLowerCase();
                    statusBadge.textContent = status;
                    statusBadge.className = 'px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest ';
                    if (status === 'approved') statusBadge.classList.add('bg-emerald-100', 'text-emerald-700', 'border', 'border-emerald-200');
                    else if (status === 'pending') statusBadge.classList.add('bg-amber-100', 'text-amber-700', 'border', 'border-amber-200');
                    else statusBadge.classList.add('bg-slate-100', 'text-slate-700', 'border', 'border-slate-200');
                }

                // Date Formatting
                const formatDateStr = (dateStr) => {
                    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return 'N/A';
                    try {
                        const parts = dateStr.split(/[- :]/);
                        if (parts.length >= 3) {
                            const d = new Date(parts[0], parts[1] - 1, parts[2]);
                            return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        }
                        return dateStr;
                    } catch(e) { return dateStr; }
                };

                document.getElementById('viewPlanStartDate').textContent = formatDateStr(plan.start_date);
                document.getElementById('viewPlanEndDate').textContent = formatDateStr(plan.end_date);

                // Financial metrics
                document.getElementById('viewPlanRevenue').textContent = formatCurrency(parseFloat(plan.project_revenue || 0));
                document.getElementById('viewPlanImpact').textContent = (parseFloat(plan.impact_percentage || 0)).toFixed(2) + '%';
                document.getElementById('viewPlanTaxation').textContent = formatCurrency(parseFloat(plan.taxation_adj || 0));
                
                // Rationale & Document
                const rationaleEl = document.getElementById('viewPlanRationale');
                const breakdownBody = document.getElementById('viewPlanBreakdownBody');
                const docContainer = document.getElementById('viewPlanDocContainer');
                
                let rationaleText = plan.description || 'No rationale provided.';
                let breakdownToRender = plan.breakdown || [];

                // Handle justification doc
                let hasDoc = false;
                if (plan.justification_doc) {
                    try {
                        const docs = JSON.parse(plan.justification_doc);
                        if (docs && docs.length > 0) {
                            hasDoc = true;
                            // Privacy update: Use plan_id instead of exposing the filename in the URL
                            document.getElementById('viewPlanPdfFrame').src = 'view_pdf.php?plan_id=' + plan.id;
                        }
                    } catch(e) { }
                }
                
                // Control tab visibility
                const docBtn = document.getElementById('viewPlanTab-document');
                if (docBtn) {
                    if (hasDoc) {
                        docBtn.setAttribute('data-has-doc', 'true');
                        docBtn.classList.remove('hidden');
                    } else {
                        docBtn.setAttribute('data-has-doc', 'false');
                        docBtn.classList.add('hidden');
                    }
                }
                
                // Reset to details tab
                if (typeof switchViewPlanTab === 'function') switchViewPlanTab('details');

                // (Definitions already handled above)

                if (typeof plan.description === 'string' && plan.description.trim().startsWith('{')) {
                    try {
                        const parsed = JSON.parse(plan.description);
                        if (parsed.justification) rationaleText = parsed.justification;
                    } catch(e) {}
                }

                if (rationaleEl) rationaleEl.textContent = rationaleText;
                if (breakdownBody) breakdownBody.innerHTML = '';
                
                let renderTotal = 0;
                // Double-Layer Fallback: If both server-side and client-side breakdown are empty, use primary record
                if (!breakdownToRender || !Array.isArray(breakdownToRender) || breakdownToRender.length === 0) {
                    breakdownToRender = [{
                        name: plan.plan_name || 'Budget Item',
                        category: plan.category || 'General',
                        subcategory: plan.sub_category || 'Miscellaneous',
                        amount: parseFloat(plan.planned_amount || 0)
                    }];
                }

                let currentCategory = '';
                let currentSubcategory = '';

                breakdownToRender.forEach(item => {
                    renderTotal += parseFloat(item.amount || 0);
                    if (breakdownBody) {
                        // Category Header
                        if (item.category && item.category !== currentCategory) {
                            currentCategory = item.category;
                            currentSubcategory = ''; // Reset subcategory on category change
                            const headRow = document.createElement('tr');
                            headRow.innerHTML = `
                                <td colspan="2" class="px-6 py-2.5 bg-slate-100 text-[10px] font-black text-indigo-800 uppercase tracking-widest border-y border-slate-200">
                                    <i class="fas fa-folder-open mr-2 text-indigo-500"></i>${escapeHtml(currentCategory)}
                                </td>
                            `;
                            breakdownBody.appendChild(headRow);
                        }

                        // Subcategory Header
                        if (item.subcategory && item.subcategory !== currentSubcategory) {
                            currentSubcategory = item.subcategory;
                            const subRow = document.createElement('tr');
                            subRow.innerHTML = `
                                <td colspan="2" class="px-8 py-2 bg-slate-50/50 text-[9px] font-bold text-slate-500 uppercase tracking-tight border-b border-slate-100">
                                    <i class="fas fa-chevron-right mr-2 text-indigo-300"></i>${escapeHtml(currentSubcategory)}
                                </td>
                            `;
                            breakdownBody.appendChild(subRow);
                        }

                        // GL Account Row
                        const row = document.createElement('tr');
                        row.classList.add('hover:bg-indigo-50/30', 'transition-all', 'group');
                        row.innerHTML = `
                            <td class="px-10 py-3 border-b border-slate-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-200 group-hover:bg-indigo-400"></div>
                                    <div>
                                        <div class="font-bold text-slate-700 text-xs group-hover:text-indigo-600 transition-colors">${escapeHtml(item.name || 'Account')}</div>
                                        <div class="text-[9px] text-slate-400 font-mono mt-0.5 tracking-tighter">${escapeHtml(item.account_code || '')}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-right font-black text-slate-600 text-sm border-b border-slate-50">
                                <span class="text-xs text-slate-300 mr-1 font-normal">₱</span>${(parseFloat(item.amount || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        `;
                        breakdownBody.appendChild(row);
                    }
                });

                // Final sync of totals to be absolutely sure they match the breakdown
                if (document.getElementById('viewPlanBreakdownTotal')) {
                    document.getElementById('viewPlanBreakdownTotal').textContent = formatCurrency(renderTotal);
                }
                document.getElementById('viewPlanAmount').textContent = formatCurrency(renderTotal);
                const consolidatedTotalEls = [
                    document.getElementById('viewPlanAmount'),
                    document.getElementById('viewPlanFooterAmount')
                ];
                consolidatedTotalEls.forEach(el => {
                    if (el) el.textContent = formatCurrency(renderTotal);
                });
                
                openModal('viewPlanModal');
            } else {
                showToast(data.message || 'Error fetching plan details', 'error');
            }
        } catch (error) {
            console.error('Error viewing plan:', error);
            showToast('An unexpected error occurred.', 'error');
        }
    }
    
    // Open Archive Plan Modal
    // Auto-suggest Financial Metrics based on Total Budget
    function suggestFinancialMetrics() {
        // Just call the unified global total update which now handles everything
        updateGlobalAllocationTotal();
    }
    
    // Mark field as custom when user edits it
    function markAsCustomValue(input) {
        if (input.getAttribute('data-suggested') === 'true') {
            const wrapper = input.closest('.amount-input-wrapper');
            if (wrapper) wrapper.style.borderColor = '#6366f1'; // Indigo border for custom
            input.setAttribute('data-suggested', 'false');
        }
    }
    
    // Attach event listeners to financial metric inputs
    document.addEventListener('DOMContentLoaded', function() {
        const revenueInput = document.querySelector('input[name="project_revenue"]');
        const impactInput = document.querySelector('input[name="impact_percentage"]');
        const taxationInput = document.querySelector('input[name="taxation_adj"]');
        
        if (revenueInput) {
            revenueInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        if (impactInput) {
            impactInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        if (taxationInput) {
            taxationInput.addEventListener('input', function() {
                markAsCustomValue(this);
            });
        }
        
        // Attach to all GL allocation inputs
        const glInputs = document.querySelectorAll('input[name^="gl_allocation"]');
        glInputs.forEach(input => {
            input.addEventListener('input', suggestFinancialMetrics);
        });
        
        // Also trigger on subcategory budget distribution
        const subcatInputs = document.querySelectorAll('input[oninput*="distributeSubcategoryBudget"]');
        subcatInputs.forEach(input => {
            const originalOnInput = input.getAttribute('oninput');
            input.setAttribute('oninput', originalOnInput + '; suggestFinancialMetrics();');
        });
        
        // Use event delegation to capture ALL inputs (including dynamic ones)
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[oninput*="distributeSubcategoryBudget"]') || 
                e.target.matches('input[name^="gl_allocation"]')) {
                suggestFinancialMetrics();
            }
        });

        // Monitoring page filter change listeners
        const monitoringFilterSelects = document.querySelectorAll('#monitoringFilterForm select');
        monitoringFilterSelects.forEach(select => {
            select.addEventListener('change', function() {
                currentPage.monitoring = 1; // Reset to first page on filter change
                loadMonitoringData();
            });
            // Sync filter select values from URL on load
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has(select.name)) {
                select.value = urlParams.get(select.name);
            }
        });
    });
    
    function changeMonitoringPage(delta) {
        currentPage.monitoring += delta;
        loadMonitoringData();
    }

    function displaySupportingDocuments(docs) {
        const tray = document.getElementById('receiptsTray');
        const list = document.getElementById('receiptsList');
        const count = document.getElementById('receiptsCount');
        
        if (!list || !tray) return;

        list.innerHTML = '';
        const totalDocs = (docs ? docs.length : 0);
        count.textContent = (totalDocs + 1) + ' Views';
        
        // Always add official proposal tab
        const officialTab = document.createElement('div');
        officialTab.className = `receipt-tab-item px-3 py-2 rounded-lg border border-purple-700 bg-purple-900/30 cursor-pointer flex items-center gap-2 transition-all active-receipt-tab`;
        officialTab.innerHTML = `
            <i class="fas fa-file-contract text-purple-400 text-sm"></i>
            <span class="text-[10px] font-bold">Official Proposal</span>
        `;
        officialTab.onclick = () => {
            document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
            officialTab.classList.add('active-receipt-tab');
            document.getElementById('automatedProposalView').classList.remove('hidden');
            document.getElementById('documentViewerContainer').classList.add('hidden');
        };
        list.appendChild(officialTab);

        if (docs && docs.length > 0) {
            docs.forEach((file, index) => {
                const ext = file.split('.').pop().toLowerCase();
                let icon = 'fa-file';
                let color = 'text-gray-400';
                
                if (ext === 'pdf') { icon = 'fa-file-pdf'; color = 'text-red-400'; }
                else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) { icon = 'fa-file-image'; color = 'text-blue-400'; }
                else if (['doc', 'docx'].includes(ext)) { icon = 'fa-file-word'; color = 'text-indigo-400'; }
                else if (['xls', 'xlsx'].includes(ext)) { icon = 'fa-file-excel'; color = 'text-green-400'; }
                
                const tab = document.createElement('div');
                tab.className = `receipt-tab-item px-3 py-2 rounded-lg border border-gray-700 bg-gray-800/50 cursor-pointer flex items-center gap-2 transition-all`;
                tab.innerHTML = `
                    <i class="fas ${icon} ${color} text-sm"></i>
                    <span class="text-[10px] font-bold truncate max-w-[100px]">${escapeHtml(file)}</span>
                `;
                
                tab.onclick = () => {
                    document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
                    tab.classList.add('active-receipt-tab');
                    document.getElementById('automatedProposalView').classList.add('hidden');
                    document.getElementById('documentViewerContainer').classList.remove('hidden');
                    openSupportingDocViewer(file, tab);
                };
                list.appendChild(tab);
            });
        }
        
        tray.classList.remove('hidden');
        
        // Show official proposal by default
        document.getElementById('automatedProposalView').classList.remove('hidden');
        document.getElementById('documentViewerContainer').classList.add('hidden');
    }

    function renderAutoGeneratedProposal(proposal) {
        const container = document.getElementById('automatedProposalView');
        if (!container) return;

        const dateStr = new Date(proposal.submitted_at || proposal.created_at || new Date()).toLocaleDateString('en-US', {
            year: 'numeric', month: 'long', day: 'numeric'
        });

        const breakdown = proposal.detailed_breakdown_array || [];
        let itemsHtml = '';
        breakdown.forEach(item => {
            itemsHtml += `
                <tr class="border-b border-gray-100">
                    <td class="py-3 text-sm text-gray-700 font-medium">${escapeHtml(item.name || item.description || 'General Expense')}</td>
                    <td class="py-3 text-xs text-gray-500 font-mono">${escapeHtml(item.account_code || '---')}</td>
                    <td class="py-3 text-right text-sm font-bold text-gray-900">${formatCurrency(item.amount)}</td>
                </tr>
            `;
        });

        if (!itemsHtml) {
            itemsHtml = `<tr><td colspan="3" class="py-6 text-center text-gray-400 italic">No detailed items recorded.</td></tr>`;
        }

        container.innerHTML = `
            <div class="max-w-4xl mx-auto bg-white shadow-2xl p-16 min-h-[1000px] relative border border-gray-200">
                <!-- Watermark -->
                <div class="absolute inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none rotate-[-45deg] select-none">
                    <p class="text-[140px] font-black uppercase tracking-[20px]">FINANCIAL PROPOSAL</p>
                </div>

                <!-- Header Area -->
                <div class="flex justify-between items-start mb-12 border-b-4 border-indigo-600 pb-8 relative z-10">
                    <div>
                        <div class="flex items-center gap-4 mb-4">
                            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                                <i class="fas fa-university text-white text-3xl"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-black text-gray-900 tracking-tighter uppercase mb-0">ViaHale Corporation</h1>
                                <p class="text-xs font-bold text-indigo-600 tracking-[0.2em] uppercase">Strategic Financial Services</p>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500 space-y-1">
                            <p>123 Strategic Plaza, Makati City</p>
                            <p>finance.compliance@viahale.ph</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="bg-gray-100 px-6 py-4 rounded-2xl inline-block border border-gray-200">
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Proposal Reference</p>
                            <p class="text-lg font-black text-gray-900 mb-2">${proposal.proposal_code || 'N/A'}</p>
                            <p class="text-[10px] font-bold text-gray-500 uppercase">${dateStr}</p>
                        </div>
                    </div>
                </div>

                <!-- Title Section -->
                <div class="mb-12 relative z-10">
                    <h2 class="text-2xl font-black text-gray-900 mb-2 underline decoration-indigo-200 decoration-8 underline-offset-4">${escapeHtml(proposal.proposal_title || 'BUDGET PROPOSAL')}</h2>
                    <p class="text-sm text-gray-600 max-w-2xl leading-relaxed">${escapeHtml(proposal.project_objectives || proposal.justification || 'No objectives provided for this proposal.')}</p>
                </div>

                <!-- Details Grid -->
                <div class="grid grid-cols-2 gap-12 mb-12 border-y border-gray-100 py-8 relative z-10">
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Requesting Department</p>
                            <p class="text-sm font-bold text-gray-900">${escapeHtml(proposal.department || 'General')}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Fiscal Allocation</p>
                            <p class="text-sm font-bold text-gray-900">FY ${proposal.fiscal_year || new Date().getFullYear()}${proposal.quarter ? ' Q' + proposal.quarter : ''}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Proposal Class</p>
                            <p class="text-sm font-bold text-gray-900 uppercase tracking-wide">${escapeHtml(proposal.project_type || 'Operational')}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Project Period</p>
                            <p class="text-sm font-bold text-gray-900">${proposal.start_date ? new Date(proposal.start_date).toLocaleDateString() : '---'} to ${proposal.end_date ? new Date(proposal.end_date).toLocaleDateString() : '---'}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Breakdown -->
                <div class="mb-12 relative z-10">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <i class="fas fa-list text-indigo-600"></i> Cost Breakdown Schedule
                    </h3>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                <th class="py-3 px-0 text-left">Description of Requirement</th>
                                <th class="py-3 px-0 text-left">Account Code</th>
                                <th class="py-3 px-0 text-right">Requested Allocation</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsHtml}
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-900">
                                <td colspan="2" class="py-4 text-sm font-black text-gray-900 uppercase tracking-widest">Total Proposed Budget</td>
                                <td class="py-4 text-right text-xl font-black text-indigo-700">${formatCurrency(proposal.total_budget || 0)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Approval Tracking -->
                <div class="mt-auto pt-12 relative z-10">
                    <div class="grid grid-cols-3 gap-8">
                        <div class="border-t border-gray-300 pt-3">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-2">Submitted By</p>
                            <p class="text-sm font-black text-gray-900 mb-1">${escapeHtml(proposal.submitted_by || 'Unauthorized Personnel')}</p>
                            <p class="text-[9px] text-gray-400">${dateStr}</p>
                        </div>
                        <div class="border-t border-gray-200 pt-3 opacity-30">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-8">Department Head Approval</p>
                            <div class="w-24 border-b border-gray-300"></div>
                        </div>
                        <div class="border-t border-gray-200 pt-3 opacity-30">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-tight mb-8">Finance Review Marker</p>
                            <div class="w-24 border-b border-gray-300"></div>
                        </div>
                    </div>
                </div>

                <!-- Footer Text -->
                <div class="mt-12 text-[9px] text-gray-400 italic text-center border-t border-gray-100 pt-4">
                    This document is a formal system-generated budget proposal. Internal use only. Unauthorized replication is strictly prohibited under ViaHale Data Privacy Policy 2024.
                </div>
            </div>
        `;
    }

    function renderPastTransactions(proposal) {
        const body = document.getElementById('pastTransactionsBody');
        const avgEl = document.getElementById('avgMonthlySpend');
        const peakEl = document.getElementById('peakSpend');
        const badge = document.getElementById('trendStatusBadge');
        
        if (!body) return;

        // Generate Sample Data for the specific department
        const depts = ['HR', 'Finance', 'Operations', 'Marketing', 'IT'];
        const seed = (proposal.department || 'General').length;
        
        let totalVal = 0;
        let peakVal = 0;
        let rowsHtml = '';
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const currentYear = new Date().getFullYear();
        
        for (let i = 2; i < 26; i++) {
            const amount = 5000 + (Math.sin(seed + i) * 3000) + (Math.random() * 1000);
            totalVal += amount;
            if (amount > peakVal) peakVal = amount;
            
            const d = new Date();
            d.setMonth(d.getMonth() - i);
            const period = months[d.getMonth()] + ' ' + d.getFullYear();
            const ref = 'EXP-' + (d.getFullYear() % 100) + '-' + (Math.floor(Math.random() * 9000) + 1000);
            const utilization = 80 + (Math.random() * 15);
            
            rowsHtml += `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-4">
                        <p class="text-sm font-bold text-gray-900">${period}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs font-mono text-gray-500">${ref}</p>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs text-gray-700 font-medium">Monthly Allocation</p>
                        <p class="text-[10px] text-gray-400">${proposal.department || 'Operations'}</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <p class="text-sm font-black text-gray-900">${formatCurrency(amount)}</p>
                    </td>
                    <td class="px-5 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                             <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-indigo-500 rounded-full" style="width: ${utilization}%"></div>
                             </div>
                             <span class="text-[10px] font-bold text-gray-500">${utilization.toFixed(0)}%</span>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        body.innerHTML = rowsHtml;
        avgEl.textContent = formatCurrency(totalVal / 24);
        peakEl.textContent = formatCurrency(peakVal);
        
        // Random badge
        const trends = [
            { text: 'Stable', class: 'bg-emerald-100 text-emerald-700' },
            { text: 'Increasing', class: 'bg-amber-100 text-amber-700' },
            { text: 'Optimized', class: 'bg-blue-100 text-blue-700' }
        ];
        const trend = trends[seed % trends.length];
        badge.textContent = trend.text;
        badge.className = `px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-tighter ${trend.class}`;
    }

    function openSupportingDocViewer(fileName, element = null) {
        const frame = document.getElementById('pdfFrame');
        const placeholder = document.getElementById('pdfViewerPlaceholder');
        const imgContainer = document.getElementById('imageViewerContainer');
        const img = document.getElementById('docImage');
        const ext = fileName.split('.').pop().toLowerCase();
        
        // Files are stored in uploads/proposals/
        currentSupportingDocPath = `uploads/proposals/${fileName}`;
        const viewUrl = `view_pdf.php?file=${encodeURIComponent('proposals/' + fileName)}`;
        
        // Highlight active tab
        if (element) {
            document.querySelectorAll('.receipt-tab-item').forEach(el => el.classList.remove('active-receipt-tab'));
            element.classList.add('active-receipt-tab');
        }

        placeholder.classList.add('hidden');
        imgContainer.classList.add('hidden');
        frame.classList.add('hidden');
        
        if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
            img.src = currentSupportingDocPath;
            imgContainer.classList.remove('hidden');
        } else if (ext === 'pdf') {
            frame.src = viewUrl;
            frame.classList.remove('hidden');
        } else {
            // For non-previewable files, show a nice placeholder with download + open link
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = `
                <div class="text-center p-8">
                    <i class="fas fa-file-alt text-5xl text-purple-300 mb-4 block"></i>
                    <p class="text-gray-500 font-semibold text-sm">${escapeHtml(fileName)}</p>
                    <p class="text-gray-400 text-xs mt-1 mb-4">This file type cannot be previewed inline.</p>
                    <div class="flex gap-3 justify-center">
                        <a href="${viewUrl}" target="_blank" class="inline-flex items-center px-5 py-2 bg-purple-600 text-white rounded-xl font-bold text-sm hover:bg-purple-700 transition-all shadow-md">
                            <i class="fas fa-eye mr-2"></i>View / Open
                        </a>
                        <a href="${currentSupportingDocPath}" download class="inline-flex items-center px-5 py-2 bg-gray-600 text-white rounded-xl font-bold text-sm hover:bg-gray-700 transition-all shadow-md">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>
                    </div>
                </div>
            `;
        }
    }

    function openCurrentDocInNewTab() {
        if (currentSupportingDocPath) {
            window.open(currentSupportingDocPath, '_blank');
        }
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('fixed') && event.target.id) {
            closeModal(event.target.id);
        } else if (event.target.classList.contains('min-h-screen')) {
            const modal = event.target.closest('.fixed');
            if (modal && modal.id) {
                closeModal(modal.id);
            }
        }
    });

    // Initialize page - load stats on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Fina Budgeting System Initialized');
        loadStats();
    });
</script>
</div>
</main>
</body>
</html>