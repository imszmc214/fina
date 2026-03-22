<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// Database connection
include('connection.php');

// AJAX handler for metadata fetching (formerly get_coa_meta.php)
if (isset($_GET['ajax_get_meta'])) {
    header('Content-Type: application/json');
    $parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
    $level = isset($_GET['level']) ? intval($_GET['level']) : 0;

    try {
        if ($level == 1) {
            $result = $conn->query("SELECT id, name, type, code FROM chart_of_accounts_hierarchy WHERE level = 1 ORDER BY code");
            $data = [];
            while ($row = $result->fetch_assoc()) $data[] = $row;
            echo json_encode(['success' => true, 'data' => $data]);
        } elseif ($level == 2 && $parentId > 0) {
            $stmt = $conn->prepare("SELECT id, name, code FROM chart_of_accounts_hierarchy WHERE parent_id = ? AND level = 2 AND is_archived = 0 ORDER BY code");
            $stmt->bind_param("i", $parentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) $data[] = $row;
            echo json_encode(['success' => true, 'data' => $data]);
        } elseif ($level == 3 && $parentId > 0) {
            $stmt = $conn->prepare("SELECT id, name, code FROM chart_of_accounts_hierarchy WHERE parent_id = ? AND level = 3 AND is_archived = 0 ORDER BY code");
            $stmt->bind_param("i", $parentId);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) $data[] = $row;
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// AJAX handler for hierarchy fetching (formerly get_hierarchy.php)
if (isset($_GET['ajax_get_hierarchy'])) {
    header('Content-Type: application/json');
    $categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($categoryId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
        exit();
    }
    try {
        $stmt = $conn->prepare("SELECT id, code, name, status, parent_id FROM chart_of_accounts_hierarchy WHERE id = ? AND level = 2 AND is_archived = 0");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $category = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$category) {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
            exit();
        }
        $stmt = $conn->prepare("SELECT id, code, name, type FROM chart_of_accounts_hierarchy WHERE id = ?");
        $stmt->bind_param("i", $category['parent_id']);
        $stmt->execute();
        $level1 = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = $conn->prepare("SELECT id, code, name, balance FROM chart_of_accounts_hierarchy WHERE parent_id = ? AND level = 3 AND is_archived = 0 ORDER BY code");
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $res3 = $stmt->get_result();
        $level3 = [];
        while ($sub = $res3->fetch_assoc()) {
            $stmt2 = $conn->prepare("SELECT id, code, name, balance, allocated_amount FROM chart_of_accounts_hierarchy WHERE parent_id = ? AND level = 4 AND is_archived = 0 ORDER BY code");
            $stmt2->bind_param("i", $sub['id']);
            $stmt2->execute();
            $res4 = $stmt2->get_result();
            $accounts = []; $subBal = 0;
            while ($acc = $res4->fetch_assoc()) {
                $accounts[] = $acc; $subBal += floatval($acc['balance']);
            }
            $stmt2->close();
            $sub['balance'] = $subBal; $sub['accounts'] = $accounts; $level3[] = $sub;
        }
        $stmt->close();
        echo json_encode([
            'success' => true,
            'category' => $category,
            'hierarchy' => [
                'level1' => ['code' => $level1['code'], 'name' => strtoupper($level1['type'])],
                'level2' => ['code' => $category['code'], 'name' => strtoupper($category['name'])],
                'level3' => $level3
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// AJAX handler for updates (formerly update_coa.php)
if (isset($_POST['ajax_update_coa'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    if ($action === 'update_names') {
        $updates = json_decode($_POST['updates'] ?? '[]', true);
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET name = ? WHERE id = ?");
            foreach ($updates as $upd) {
                $stmt->bind_param("si", $upd['name'], $upd['id']);
                $stmt->execute();
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Updates saved successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } elseif ($action === 'archive_item') {
        $id = $_POST['id'] ?? 0;
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET is_archived = 1, status = 'archived', code = NULL WHERE id = ?");
            $stmt->bind_param("i", $id); $stmt->execute();
            $lvStmt = $conn->prepare("SELECT level FROM chart_of_accounts_hierarchy WHERE id = ?");
            $lvStmt->bind_param("i", $id); $lvStmt->execute();
            $target = $lvStmt->get_result()->fetch_assoc();
            if ($target) {
                if ($target['level'] == 2) {
                    $sSub = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET is_archived = 1, status = 'archived', code = NULL WHERE parent_id = ? AND level = 3");
                    $sSub->bind_param("i", $id); $sSub->execute();
                    $conn->query("UPDATE chart_of_accounts_hierarchy h4 JOIN chart_of_accounts_hierarchy h3 ON h4.parent_id = h3.id SET h4.is_archived = 1, h4.status = 'archived', h4.code = NULL WHERE h3.parent_id = $id AND h4.level = 4");
                } elseif ($target['level'] == 3) {
                    $sAcc = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET is_archived = 1, status = 'archived', code = NULL WHERE parent_id = ? AND level = 4");
                    $sAcc->bind_param("i", $id); $sAcc->execute();
                }
            }
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Item archived successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    exit();
}

// AJAX handler for toggle account status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_toggle'])) {
    $id = $_POST['id'];
    $response = ['success' => false, 'message' => '', 'new_status' => ''];
    
    // Get current status
    $stmt = $conn->prepare("SELECT status FROM chart_of_accounts_hierarchy WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();
    
    // Toggle status
    $new_status = ($current_status == 'active') ? 'inactive' : 'active';
    
    $stmt = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Account " . (($current_status == 'active') ? 'deactivated' : 'activated') . " successfully!";
        $response['new_status'] = $new_status;
    } else {
        $response['message'] = "Error updating account: " . $stmt->error;
    }
    $stmt->close();
    
    echo json_encode($response);
    exit();
}

// Handle complex form submission for adding hierarchical accounts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complex_add_account'])) {
    header('Content-Type: application/json');
    $conn->begin_transaction();
    
    try {
        $mode = $_POST['add_mode'];
        $type_id = intval($_POST['type_id']);
        
        // Helper function to get next available code for a level (10k, 1k, 1 sequence)
        function getNextCode($conn, $parentId, $level) {
            // Get last code at this level for this parent
            $stmt = $conn->prepare("SELECT code FROM chart_of_accounts_hierarchy WHERE parent_id = ? AND level = ? AND is_archived = 0 ORDER BY code DESC LIMIT 1");
            $stmt->bind_param("ii", $parentId, $level);
            $stmt->execute();
            $result = $stmt->get_result();
            
            // Get parent code to derive from
            $pStmt = $conn->prepare("SELECT code FROM chart_of_accounts_hierarchy WHERE id = ?");
            $pStmt->bind_param("i", $parentId);
            $pStmt->execute();
            $pResult = $pStmt->get_result();
            if ($pRow = $pResult->fetch_assoc()) {
                $parentCode = $pRow['code'];
            } else {
                $parentCode = '000000';
            }

            if ($row = $result->fetch_assoc()) {
                // Determine increment based on level
                $step = 1;
                if ($level == 2) $step = 10000;
                elseif ($level == 3) $step = 1000;
                
                $code = strval(intval($row['code']) + $step);
            } else {
                // Initialize based on parent structure
                if ($level == 2) $code = strval(intval($parentCode) + 10000);
                elseif ($level == 3) $code = strval(intval($parentCode) + 1000);
                elseif ($level == 4) $code = strval(intval($parentCode) + 1);
                else $code = "000000";
            }

            // Verify global uniqueness - crucial to prevent collisions
            while (true) {
                $check = $conn->prepare("SELECT id FROM chart_of_accounts_hierarchy WHERE code = ? LIMIT 1");
                $check->bind_param("s", $code);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $step = ($level == 2) ? 10000 : (($level == 3) ? 1000 : 1);
                    $code = strval(intval($code) + $step);
                } else {
                    break;
                }
            }
            return $code;
        }

        if ($mode === 'new_category') {
            $categoryName = $_POST['category_name'];
            
            // 1. Insert Category (Level 2)
            $catCode = getNextCode($conn, $type_id, 2);
            
            $stmt = $conn->prepare("INSERT INTO chart_of_accounts_hierarchy (parent_id, level, code, name, status, is_archived) VALUES (?, 2, ?, ?, 'active', 0)");
            $stmt->bind_param("iss", $type_id, $catCode, $categoryName);
            $stmt->execute();
            $categoryId = $conn->insert_id;
            
            // 2. Insert Subcategories and their GL Accounts
            $subNames = $_POST['sub_names'] ?? [];
            foreach ($subNames as $subIdx => $subName) {
                $realSubIdx = $subIdx + 1;
                $subCode = getNextCode($conn, $categoryId, 3);
                
                $sStmt = $conn->prepare("INSERT INTO chart_of_accounts_hierarchy (parent_id, level, code, name, status, is_archived) VALUES (?, 3, ?, ?, 'active', 0)");
                $sStmt->bind_param("iss", $categoryId, $subCode, $subName);
                $sStmt->execute();
                $subId = $conn->insert_id;
                
                // GL Accounts for this subcategory
                $accNames = $_POST["sub_{$realSubIdx}_acc_names"] ?? [];
                
                // Get starting code for accounts - prevents duplicate entry error
                $nextAccCode = intval(getNextCode($conn, $subId, 4));
                foreach ($accNames as $aIdx => $accName) {
                    $accCode = strval($nextAccCode + $aIdx);
                    $aStmt = $conn->prepare("INSERT INTO chart_of_accounts_hierarchy (parent_id, level, code, name, status, is_archived) VALUES (?, 4, ?, ?, 'active', 0)");
                    $aStmt->bind_param("iss", $subId, $accCode, $accName);
                    $aStmt->execute();
                }
            }
        } else {
            // Existing Category Mode
            $categoryId = intval($_POST['category_id']);
            $subMode = $_POST['sub_mode_final'];
            $subId = null;

            if ($subMode === 'new') {
                $subName = $_POST['sub_name_final'];
                $subCode = getNextCode($conn, $categoryId, 3);
                
                $sStmt = $conn->prepare("INSERT INTO chart_of_accounts_hierarchy (parent_id, level, code, name, status, is_archived) VALUES (?, 3, ?, ?, 'active', 0)");
                $sStmt->bind_param("iss", $categoryId, $subCode, $subName);
                $sStmt->execute();
                $subId = $conn->insert_id;
            } else {
                $subId = intval($_POST['sub_id_final']);
            }
            
            // Insert GL Accounts
            $accNames = $_POST['acc_names'] ?? [];
            
            // Get starting code for accounts - prevents duplicate entry error
            $nextAccCode = intval(getNextCode($conn, $subId, 4));
            foreach ($accNames as $aIdx => $accName) {
                $accCode = strval($nextAccCode + $aIdx);
                $aStmt = $conn->prepare("INSERT INTO chart_of_accounts_hierarchy (parent_id, level, code, name, status, is_archived) VALUES (?, 4, ?, ?, 'active', 0)");
                $aStmt->bind_param("iss", $subId, $accCode, $accName);
                $aStmt->execute();
            }
        }
        
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Accounts added successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle edit account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_account'])) {
    $id = $_POST['id'];
    $code = $_POST['code'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'];
    $description = $_POST['description'];
    $current_page = isset($_POST['current_page']) ? intval($_POST['current_page']) : 1;
    
    // Check if account code already exists (excluding current account)
    $check_stmt = $conn->prepare("SELECT code FROM chart_of_accounts_hierarchy WHERE code = ? AND id != ?");
    $check_stmt->bind_param("si", $code, $id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $_SESSION['error'] = "Account code already exists!";
    } else {
        // Update account (level 4 only)
        $stmt = $conn->prepare("UPDATE chart_of_accounts_hierarchy SET code = ?, name = ?, type = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $code, $name, $type, $description, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Account updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating account: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
    
    // Redirect to clear POST data, preserving the page number
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $current_page);
    exit();
}

// Check if editing an account
$editAccount = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    // Get level 4 account with parent hierarchy
    $query = "SELECT 
        l4.id, l4.code, l4.name, l4.type, l4.description, l4.status,
        l2.name as category,
        l3.name as subcategory
    FROM chart_of_accounts_hierarchy l4
    LEFT JOIN chart_of_accounts_hierarchy l3 ON l4.parent_id = l3.id
    LEFT JOIN chart_of_accounts_hierarchy l2 ON l3.parent_id = l2.id
    WHERE l4.id = ? AND l4.level = 4";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $editAccount = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fetch categories from database (Level 2) with aggregated balances
$accounts = [];
$query = "SELECT 
    l2.id,
    l2.code,
    l2.name as category,
    l1.type,
    l2.status,
    COALESCE(SUM(l4.balance), 0) as balance
FROM chart_of_accounts_hierarchy l2
LEFT JOIN chart_of_accounts_hierarchy l1 ON l2.parent_id = l1.id
LEFT JOIN chart_of_accounts_hierarchy l3 ON l3.parent_id = l2.id AND l3.level = 3
LEFT JOIN chart_of_accounts_hierarchy l4 ON l4.parent_id = l3.id AND l4.level = 4
WHERE l2.level = 2 AND l2.is_archived = 0
GROUP BY l2.id, l2.code, l2.name, l1.type, l2.status
ORDER BY l2.code";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
    }
    $result->free();
}

// Get account statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'inactive' => 0,
    'assets' => 0,
    'liabilities' => 0,
    'equity' => 0,
    'revenue' => 0,
    'expenses' => 0
];

$stat_result = $conn->query("SELECT 
    COUNT(DISTINCT l2.id) as total,
    SUM(CASE WHEN l2.status = 'active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN l2.status = 'inactive' THEN 1 ELSE 0 END) as inactive,
    SUM(CASE WHEN l1.type = 'Asset' THEN 1 ELSE 0 END) as assets,
    SUM(CASE WHEN l1.type = 'Liability' THEN 1 ELSE 0 END) as liabilities,
    SUM(CASE WHEN l1.type = 'Equity' THEN 1 ELSE 0 END) as equity,
    SUM(CASE WHEN l1.type = 'Revenue' THEN 1 ELSE 0 END) as revenue,
    SUM(CASE WHEN l1.type = 'Expense' THEN 1 ELSE 0 END) as expenses
    FROM chart_of_accounts_hierarchy l2
    LEFT JOIN chart_of_accounts_hierarchy l1 ON l2.parent_id = l1.id
    WHERE l2.level = 2 AND l2.is_archived = 0");

if ($stat_result && $row = $stat_result->fetch_assoc()) {
    $stats = $row;
}

// Get messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
// Clear messages after retrieving
unset($_SESSION['success'], $_SESSION['error']);

// $conn->close(); // Connection should stay open for modal queries
?>

<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Chart of Accounts | TNVS Financial System</title>
    <link rel="icon" href="logo1.png" type="img">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .subcategory-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
        }
        
        .toast-success { background-color: #10b981; }
        .toast-error { background-color: #ef4444; }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 20000;
            display: none;
        }
        
        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 20001;
            display: none;
            width: 100%;
            height: 100%;
            pointer-events: none;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            pointer-events: auto;
            position: relative;
            z-index: 20002;
            overflow: hidden;
        }
        
        .modal-backdrop.show {
            display: block;
        }
        
        /* Hierarchy Tree Styles */
        .hierarchy-tree {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 14px;
        }

        #hierarchyContent {
            max-height: 60vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #8b5cf6 #f3f4f6;
        }

        #hierarchyContent::-webkit-scrollbar {
            width: 6px;
        }

        #hierarchyContent::-webkit-scrollbar-track {
            background: #f3f4f6;
        }

        #hierarchyContent::-webkit-scrollbar-thumb {
            background-color: #8b5cf6;
            border-radius: 20px;
        }
        
        .hierarchy-level {
            margin-left: 24px;
            padding: 12px 16px;
            margin-bottom: 10px;
            position: relative;
            transition: all 0.2s ease;
        }
        
        .hierarchy-level-1 {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-left: 0;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .hierarchy-level-2 {
            background: #ffffff;
            border: 1px solid #e9d5ff;
            border-left: 4px solid #8b5cf6;
            border-radius: 10px;
            margin-left: 0;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .hierarchy-level-3 {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #6b7280;
            border-radius: 8px;
        }
        
        .hierarchy-level-4 {
            background: #fff;
            border: 1px solid #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
        }
        
        .gl-code {
            font-weight: 700;
            color: #6366f1;
            font-size: 10px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            display: inline-block;
            background: #eef2ff;
            padding: 2px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }
        
        .hierarchy-level-1 .gl-code {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .account-name {
            font-weight: 600;
            color: #111827;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .hierarchy-level-1 .account-name {
            color: white;
            font-size: 18px;
        }

        .level-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #9ca3af;
            font-weight: 500;
        }

        .hierarchy-level-1 .level-label {
            color: rgba(255,255,255,0.7);
        }
        
        .balance-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e5e7eb;
        }
        
        .hierarchy-level-4 .balance-info {
            border-top: none;
            margin-top: 4px;
        }
        
        .balance-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .balance-amount {
            font-weight: 700;
            color: #059669;
            font-size: 14px;
        }
        
        .allocated-amount {
            font-weight: bold;
            color: #3b82f6;
        }

        /* Edit Mode Styles */
        .name-input {
            padding: 2px 4px;
            margin: -2px -4px;
            transition: all 0.2s ease;
            width: 100%;
            max-width: 300px;
        }

        .edit-mode-active .hierarchy-level:hover {
            border-color: #8b5cf6;
            background-color: #f5f3ff;
        }

        .edit-mode-active .name-input:focus {
            background-color: white;
            box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.2);
            border-radius: 4px;
        }

        /* Segmented Control */
        .segmented-control {
            display: flex;
            background: #f3f4f6;
            padding: 4px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .segmented-control button {
            flex: 1;
            padding: 8px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .segmented-control button.active {
            background: white;
            color: #7c3aed;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .segmented-control button:not(.active) {
            color: #6b7280;
        }

        /* Add Group Cards */
        .add-group-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            background: #f9fafb;
            position: relative;
        }

        .add-group-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .gl-account-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #e5e7eb;
        }

        .gl-account-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        /* Confirm Modal */
        #confirmModal .modal-content {
            max-width: 350px;
            text-align: center;
            padding: 24px;
        }

        .confirm-icon {
            width: 64px;
            height: 64px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
        }

        /* Animated Tab Styles */
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

        .account-type-tab {
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            background: transparent;
            border: none;
            position: relative;
            z-index: 2;
        }
        
        .account-type-tab:hover {
            color: #111827;
        }
        
        .account-type-tab.active {
            color: white !important;
        }
        
        .account-type-tab i {
            font-size: 14px;
            opacity: 0.7;
        }
        
        .account-type-tab.active i {
            opacity: 1;
            color: white;
        }

        .confirm-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .confirm-message {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.5;
        }

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
</head>
<body class="bg-gray-50">

    <?php include('sidebar.php'); ?>
    
    <div class="flex-1 overflow-y-auto">
        <!-- Toast container -->
        <div id="toastContainer"></div>

        <!-- Breadcrumb -->
        <div class="px-6 py-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Chart of Accounts</h1>
                    <p class="text-gray-600 mt-1">Manage financial accounts with detailed categorization</p>
                </div>
                <div class="text-sm text-gray-500">
                    <a href="dashboard.php?page=dashboard" class="text-gray-500 hover:text-purple-600">Home</a>
                    /
                    <a class="text-gray-500">General Ledger</a>
                    /
                    <a href="charts_of_accounts.php" class="text-purple-600 hover:text-purple-600 font-medium">Chart of Accounts</a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="px-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total Accounts</p>
                            <p class="text-2xl font-bold text-gray-800" id="totalAccounts"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-purple-100">
                            <i class="fas fa-layer-group text-purple-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Active Accounts</p>
                            <p class="text-2xl font-bold text-green-600" id="activeAccounts"><?php echo $stats['active']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-green-100">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Assets</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo $stats['assets']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100">
                            <i class="fas fa-chart-line text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Revenue Accounts</p>
                            <p class="text-2xl font-bold text-indigo-600"><?php echo $stats['revenue']; ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-indigo-100">
                            <i class="fas fa-money-bill-wave text-indigo-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="px-6">
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div id="successMessage" class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-r shadow-sm fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="text-green-700 font-medium"><?php echo $success; ?></p>
                        </div>
                        <button onclick="hideMessage('successMessage')" class="text-green-500 hover:text-green-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div id="errorMessage" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-r shadow-sm fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="text-red-700 font-medium"><?php echo $error; ?></p>
                        </div>
                        <button onclick="hideMessage('errorMessage')" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Account Management Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center space-x-4">
                            <div class="p-2 rounded-lg bg-purple-100">
                                <i class="fas fa-file-invoice-dollar text-purple-600"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Account Records</h2>
                                <p class="text-sm text-gray-600">Manage all financial accounts</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <!-- Search Bar -->
                            <div class="relative">
                                <input type="text" id="searchInput" 
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" 
                                    placeholder="Search accounts...">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                            
                            <!-- Add Account Button -->
                            <button id="addAccountBtn" 
                                class="gradient-bg hover:opacity-90 text-white px-6 py-2.5 rounded-lg flex items-center gap-2 transition-all duration-300 shadow-md">
                                <i class="fas fa-plus"></i>
                                Add Account
                            </button>
                        </div>
                    </div>
                </div>

                <div class="px-6 pt-6">
                    <div class="tabs-container no-scrollbar">
                        <div id="tab-indicator" class="tab-indicator"></div>
                        <button type="button" class="account-type-tab active" data-type="">
                            <i class="fas fa-layer-group"></i>
                            All Accounts
                        </button>
                        <button type="button" class="account-type-tab" data-type="Asset">
                            <i class="fas fa-building"></i>
                            Assets
                        </button>
                        <button type="button" class="account-type-tab" data-type="Liability">
                            <i class="fas fa-hand-holding-usd"></i>
                            Liabilities
                        </button>
                        <button type="button" class="account-type-tab" data-type="Equity">
                            <i class="fas fa-users-cog"></i>
                            Equity
                        </button>
                        <button type="button" class="account-type-tab" data-type="Revenue">
                            <i class="fas fa-arrow-trend-up"></i>
                            Revenue
                        </button>
                        <button type="button" class="account-type-tab" data-type="Expense">
                            <i class="fas fa-receipt"></i>
                            Expenses
                        </button>
                    </div>
                </div>

                <!-- Accounts Table -->
                <div class="p-6">
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">GL Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="accountTable" class="bg-white divide-y divide-gray-200">
                                <!-- Data will be dynamically inserted -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="flex items-center justify-between mt-4 px-2">
                        <div id="pageStatus" class="text-sm text-gray-600"></div>
                        <div class="flex space-x-2">
                            <button id="prevPage" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Previous
                            </button>
                            <button id="nextPage" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <canvas id="pdf-viewer" width="600" height="400"></canvas>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal" id="addAccountModal">
        <div class="modal-backdrop" id="addAccountModalBackdrop" onclick="closeAddModal()"></div>
        <div class="modal-content w-full max-w-2xl mx-4" style="max-height: 90vh; overflow-y: auto;">
            <div class="p-6 border-b border-gray-200 sticky top-0 bg-white z-10">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold font-inter text-gray-800" id="addModalTitle">Add to Chart of Accounts</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <form id="complexAddAccountForm" method="POST" action="">
                <div class="p-6 space-y-6">
                    <input type="hidden" name="complex_add_account" value="1">
                    <input type="hidden" name="add_mode" id="addModeInput" value="new_category">
                    
                    <!-- Mode Switcher -->
                    <div class="segmented-control">
                        <button type="button" id="btnNewCategory" class="active" onclick="switchAddMode('new_category')">
                            <i class="fas fa-plus-circle mr-2"></i> Create New Category
                        </button>
                        <button type="button" id="btnExistingCategory" onclick="switchAddMode('existing_category')">
                            <i class="fas fa-folder-open mr-2"></i> With Existing Category
                        </button>
                    </div>

                    <!-- Common Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Account Type (L1) *</label>
                            <select name="type_id" id="addAccountTypeSelect" required onchange="onTypeChange()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Select Account Type</option>
                                <?php
                                $type_query = "SELECT id, name, type FROM chart_of_accounts_hierarchy WHERE level = 1 ORDER BY code";
                                $type_result = $conn->query($type_query);
                                while($type_row = $type_result->fetch_assoc()) {
                                    echo "<option value='{$type_row['id']}'>{$type_row['name']} ({$type_row['type']})</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <!-- Dynamic Title for Category -->
                        <div id="categoryInputContainer">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Category Name (L2) *</label>
                            <input type="text" name="category_name" id="newCategoryName" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                                placeholder="Enter Category Name">
                        </div>

                        <div id="categorySelectContainer" class="hidden">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Select Category (L2) *</label>
                            <select name="category_id" id="existingCategorySelect" required onchange="onCategoryChange()"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                                <option value="">Select Category</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    <!-- Mode 1: New Category Fields (Subcategories & Accounts) -->
                    <div id="newCategoryFields" class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="text-sm font-bold text-gray-700">Subcategories & GL Accounts</h4>
                            <button type="button" onclick="addSubcategoryRow()" class="text-xs font-bold text-purple-600 hover:text-purple-800 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Add Subcategory
                            </button>
                        </div>
                        
                        <div id="subcategoriesContainer" class="space-y-4">
                            <!-- Injected by JS -->
                        </div>
                    </div>

                    <!-- Mode 2: Existing Category Fields -->
                    <div id="existingCategoryFields" class="hidden space-y-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Target Subcategory (L3)</label>
                                <div class="flex gap-4 mb-3">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="sub_mode" value="existing" checked class="text-purple-600 focus:ring-purple-500" onchange="toggleSubMode()">
                                        <span class="ml-2 text-sm text-gray-700">Existing Subcategory</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="sub_mode" value="new" class="text-purple-600 focus:ring-purple-500" onchange="toggleSubMode()">
                                        <span class="ml-2 text-sm text-gray-700">Add New Subcategory</span>
                                    </label>
                                </div>
                                
                                <select id="existingSubSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 mb-3">
                                    <option value="">Select Subcategory</option>
                                </select>
                                
                                <input type="text" id="newSubName" class="hidden w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 mb-3" placeholder="Enter New Subcategory Name">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-bold text-gray-700">GL Accounts</h4>
                                    <button type="button" onclick="addGLAccountToExistingSub()" class="text-xs font-bold text-green-600 hover:text-green-800 flex items-center gap-1">
                                        <i class="fas fa-plus"></i> Add GL Account
                                    </button>
                                </div>
                                <div id="existingModeAccountsContainer" class="space-y-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                                    <!-- Injected by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 flex justify-end gap-3 sticky bottom-0 bg-white">
                    <button type="button" onclick="closeAddModal()"
                        class="px-5 py-2 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitAddAccount"
                        class="px-5 py-2 text-sm font-bold text-white gradient-bg rounded-xl shadow-lg hover:shadow-purple-500/25 transition-all flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        Confirm Creation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal (Keep it simple as requested previously) -->
    <div class="modal" id="editAccountModal">
        <div class="modal-backdrop" id="editAccountModalBackdrop" onclick="closeEditModal()"></div>
        <div class="modal-content w-full max-w-md mx-4">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Account</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="editAccountForm" method="POST" action="">
                <div class="p-6 space-y-4">
                    <input type="hidden" name="edit_account" value="1">
                    <input type="hidden" name="id" id="editAccountId">
                    <input type="hidden" name="current_page" id="editCurrentPage">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Code *</label>
                            <input type="number" name="code" id="editAccountCode" required min="1000" max="99999"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type *</label>
                            <select name="type" id="editAccountType" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Select Type</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GL Account *</label>
                        <input type="text" name="name" id="editAccountName" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition duration-200">
                        Update Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal" id="editAccountModal">
        <div class="modal-backdrop" id="editAccountModalBackdrop" onclick="closeEditModal()"></div>
        <div class="modal-content w-full max-w-md mx-4">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Account</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <form id="editAccountForm" method="POST" action="">
                <div class="p-6 space-y-4">
                    <input type="hidden" name="edit_account" value="1">
                    <input type="hidden" name="id" id="editAccountId">
                    <input type="hidden" name="current_page" id="editCurrentPage">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Code *</label>
                            <input type="number" name="code" id="editAccountCode" required min="1000" max="99999"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Type *</label>
                            <select name="type" id="editAccountType" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">Select Type</option>
                                <option value="Asset">Asset</option>
                                <option value="Liability">Liability</option>
                                <option value="Equity">Equity</option>
                                <option value="Revenue">Revenue</option>
                                <option value="Expense">Expense</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">GL Account *</label>
                        <input type="text" name="name" id="editAccountName" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                        <input type="text" name="category" id="editAccountCategory" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Subcategory *</label>
                        <input type="text" name="subcategory" id="editAccountSubcategory" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="editAccountDescription" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                    </div>
                </div>
                
                <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition duration-200">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white gradient-bg rounded-lg hover:opacity-90 transition duration-200 flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        Update Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Hierarchy Modal -->
    <div id="hierarchyModal" class="modal">
        <div class="modal-backdrop" id="hierarchyModalBackdrop" onclick="closeHierarchyModal()"></div>
        <div class="modal-content" style="width: 90%; max-width: 900px; max-height: 85vh; border-radius: 1rem; overflow: hidden; background: white; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
            <div class="modal-header gradient-bg p-4 sticky top-0 z-20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-sitemap"></i>
                            Account Hierarchy
                        </h2>
                        <div class="flex items-center gap-2 border-l border-white/20 pl-4 h-6">
                            <button type="button" id="editModeBtn" onclick="toggleEditMode()" 
                                class="text-white/90 hover:text-white flex items-center gap-1.5 transition-all px-3 py-1 rounded-md hover:bg-white/10 text-sm font-medium">
                                <i class="fas fa-edit text-xs"></i>
                                Edit Names
                            </button>
                            <button type="button" id="archiveCategoryBtn" onclick="archiveCurrentCategory()" 
                                class="text-white/90 hover:text-red-300 flex items-center gap-1.5 transition-all px-3 py-1 rounded-md hover:bg-red-500/10 text-sm font-medium">
                                <i class="fas fa-archive text-xs"></i>
                                Archive Category
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="saveChangesBtn" onclick="saveHierarchyChanges()" 
                            class="hidden bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-lg transition-all flex items-center gap-2">
                            <i class="fas fa-save font-bold"></i>
                            Save Changes
                        </button>
                        <button type="button" onclick="closeHierarchyModal()" class="text-white hover:text-gray-200 transition duration-200 p-1">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-0" id="hierarchyContent">
                <!-- Hierarchy content will be dynamically inserted here -->
                <div class="text-center py-12">
                    <i class="fas fa-spinner fa-spin text-4xl text-purple-600"></i>
                    <p class="mt-4 text-gray-600 font-medium">Loading hierarchical data...</p>
                </div>
            </div>
        </div>
    </div>

<script>
// Convert PHP data to JavaScript
const accountData = <?php echo json_encode($accounts); ?>;
const editAccountData = <?php echo json_encode($editAccount); ?>;

// Debug: Check if data is loaded from PHP
console.log('PHP Account Data Count:', accountData ? accountData.length : 0);

// Default data structure if no database data (Level 2 categories)
const defaultData = [
    // Assets
    { id: 1, code: "10100000", category: "Current Assets", type: "Asset", balance: 150000.00, status: "active" },
    { id: 2, code: "10200000", category: "Fixed Assets", type: "Asset", balance: 500000.00, status: "active" },
    { id: 3, code: "10300000", category: "Intangible Assets", type: "Asset", balance: 50000.00, status: "active" },
    
    // Liabilities
    { id: 4, code: "20100000", category: "Current Liabilities", type: "Liability", balance: 80000.00, status: "active" },
    { id: 5, code: "20200000", category: "Long-term Liabilities", type: "Liability", balance: 200000.00, status: "active" },
    
    // Equity
    { id: 6, code: "30100000", category: "Owner Equity", type: "Equity", balance: 420000.00, status: "active" },
    
    // Revenue
    { id: 7, code: "40100000", category: "Transportation Revenue", type: "Revenue", balance: 250000.00, status: "active" },
    { id: 8, code: "40200000", category: "Commission Revenue", type: "Revenue", balance: 75000.00, status: "active" },
    
    // Expenses
    { id: 9, code: "50100000", category: "Vehicle Operations", type: "Expense", balance: 120000.00, status: "active" },
    { id: 10, code: "50200000", category: "Personnel & Workforce", type: "Expense", balance: 180000.00, status: "active" },
    { id: 11, code: "50300000", category: "Direct Operating Expenses", type: "Expense", balance: 60000.00, status: "active" },
];

const data = accountData.length > 0 ? accountData : defaultData;

// Debug logging
console.log('=== COA Data Debug ===');
console.log('Account Data from PHP:', accountData);
console.log('Using data:', data);
console.log('Data length:', data.length);
if (data.length > 0) {
    console.log('First item:', data[0]);
}
console.log('=====================');

let currentPage = 1;
const rowsPerPage = 10;
let selectedType = "";

// Read page parameter from URL if present (must be before any rendering)
const urlParams = new URLSearchParams(window.location.search);
const pageParam = urlParams.get('page');
if (pageParam) {
    const parsedPage = parseInt(pageParam, 10);
    if (!isNaN(parsedPage) && parsedPage > 0) {
        currentPage = parsedPage;
    }
}

// Toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} fade-in`;
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-4 text-white/80 hover:text-white">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

// Modal functionality
function showAddModal() {
    document.getElementById('addAccountModal').classList.add('show');
    document.getElementById('addAccountModalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeAddModal() {
    document.getElementById('addAccountModal').classList.remove('show');
    document.getElementById('addAccountModalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
    document.getElementById('addAccountForm').reset();
}

function showEditModal(accountId) {
    const account = data.find(acc => acc.id == accountId);
    if (account) {
        document.getElementById('editAccountId').value = account.id;
        document.getElementById('editAccountCode').value = account.code;
        document.getElementById('editAccountName').value = account.name;
        document.getElementById('editAccountType').value = account.type;
        document.getElementById('editAccountCategory').value = account.category;
        document.getElementById('editAccountSubcategory').value = account.subcategory;
        document.getElementById('editAccountDescription').value = account.description || '';
        document.getElementById('editCurrentPage').value = currentPage; // Preserve current page
        
        document.getElementById('editAccountModal').classList.add('show');
        document.getElementById('editAccountModalBackdrop').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeEditModal() {
    document.getElementById('editAccountModal').classList.remove('show');
    document.getElementById('editAccountModalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}

// Hierarchy Modal Functions
let currentCategoryId = null;
let currentHierarchyData = null;
let isEditMode = false;

function showHierarchyModal(categoryId) {
    currentCategoryId = categoryId;
    isEditMode = false;
    
    // Reset buttons
    const editBtn = document.getElementById('editModeBtn');
    const saveBtn = document.getElementById('saveChangesBtn');
    if (editBtn) {
        editBtn.innerHTML = '<i class="fas fa-edit text-xs"></i> Edit Names';
        editBtn.classList.remove('bg-white/10', 'text-white');
    }
    if (saveBtn) saveBtn.classList.add('hidden');
    
    document.getElementById('hierarchyModal').classList.add('show');
    document.getElementById('hierarchyModalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
    
    loadHierarchyData(categoryId);
}

function closeHierarchyModal() {
    document.getElementById('hierarchyModal').classList.remove('show');
    document.getElementById('hierarchyModalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
    currentCategoryId = null;
    currentHierarchyData = null;
    isEditMode = false;
}

async function loadHierarchyData(categoryId) {
    const hierarchyContent = document.getElementById('hierarchyContent');
    
    try {
        const response = await fetch(`?ajax_get_hierarchy=1&id=${categoryId}`);
        const data = await response.json();
        
        if (data.success) {
            currentHierarchyData = data.hierarchy;
            hierarchyContent.innerHTML = renderHierarchy(data.hierarchy);
            
            // Update toggle button if it exists
            const toggleBtn = document.getElementById('toggleStatusBtn');
            if (toggleBtn) {
                if (data.category.status === 'active') {
                    toggleBtn.innerHTML = '<i class="fas fa-ban mr-2"></i> Mark as Inactive';
                    toggleBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition duration-200';
                } else {
                    toggleBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Mark as Active';
                    toggleBtn.className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition duration-200';
                }
            }
        } else {
            hierarchyContent.innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <i class="fas fa-exclamation-circle text-3xl mb-2"></i>
                    <p>${data.message || 'Error loading hierarchy'}</p>
                </div>
            `;
        }
    } catch (error) {
        hierarchyContent.innerHTML = `
            <div class="text-center py-8 text-red-600">
                <i class="fas fa-exclamation-circle text-3xl mb-2"></i>
                <p>Error loading hierarchy data</p>
            </div>
        `;
    }
}

function renderHierarchy(hierarchy) {
    let html = `<div class="hierarchy-tree p-6 ${isEditMode ? 'edit-mode-active' : ''}">`;
    
    // Level 1 - Type
    html += `
        <div class="hierarchy-level hierarchy-level-1">
            <div class="gl-code">GL CODE: ${hierarchy.level1.code}</div>
            <div class="account-name">
                <span class="level-label">Account Type:</span>
                ${hierarchy.level1.name}
            </div>
        </div>
    `;
    
    // Level 2 - Category
    html += `
        <div class="hierarchy-level hierarchy-level-2 group">
            <div class="flex items-center justify-between">
                <div>
                    <div class="gl-code">GL CODE: ${hierarchy.level2.code}</div>
                    <div class="account-name">
                        <span class="level-label">Category:</span>
                        <span class="name-text ${isEditMode ? 'hidden' : ''}">${hierarchy.level2.name}</span>
                        <input type="text" class="name-input ${isEditMode ? '' : 'hidden'} border-b border-purple-300 focus:border-purple-600 outline-none bg-transparent font-semibold" 
                            value="${hierarchy.level2.name}" data-level="2" data-id="${currentCategoryId}">
                    </div>
                </div>
            </div>
    `;
    
    // Level 3 - Subcategories
    if (hierarchy.level3 && hierarchy.level3.length > 0) {
        hierarchy.level3.forEach(sub => {
            html += `
                <div class="hierarchy-level hierarchy-level-3 group">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="gl-code">GL CODE: ${sub.code}</div>
                            <div class="account-name">
                                <span class="level-label">Sub-category:</span>
                                <span class="name-text ${isEditMode ? 'hidden' : ''}">${sub.name}</span>
                                <input type="text" class="name-input ${isEditMode ? '' : 'hidden'} border-b border-gray-300 focus:border-purple-600 outline-none bg-transparent font-semibold" 
                                    value="${sub.name}" data-level="3" data-id="${sub.id}">
                            </div>
                        </div>
                        <button onclick="archiveItem(${sub.id}, '${sub.name}')" class="${isEditMode ? '' : 'hidden'} text-red-400 hover:text-red-600 p-2 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="balance-info">
                        <span class="balance-label">Current Balance:</span>
                        <span class="balance-amount">₱${parseFloat(sub.balance || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
            `;
            
            // Level 4 - GL Accounts
            if (sub.accounts && sub.accounts.length > 0) {
                sub.accounts.forEach(acc => {
                    html += `
                        <div class="hierarchy-level hierarchy-level-4 group">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="gl-code">GL CODE: ${acc.code}</div>
                                    <div class="account-name">
                                        <span class="level-label">GL Account:</span>
                                        <span class="name-text ${isEditMode ? 'hidden' : ''}">${acc.name}</span>
                                        <input type="text" class="name-input ${isEditMode ? '' : 'hidden'} border-b border-yellow-300 focus:border-purple-600 outline-none bg-transparent font-semibold text-sm" 
                                            value="${acc.name}" data-level="4" data-id="${acc.id}">
                                    </div>
                                </div>
                                <button onclick="archiveItem(${acc.id}, '${acc.name}')" class="${isEditMode ? '' : 'hidden'} text-red-400 hover:text-red-600 p-2 transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mt-2">
                                <div class="balance-info">
                                    <span class="balance-label text-[10px]">BALANCE</span>
                                    <span class="balance-amount text-sm">₱${parseFloat(acc.balance || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                                </div>
                                <div class="balance-info">
                                    <span class="balance-label text-[10px]">ALLOCATED</span>
                                    <span class="allocated-amount text-sm text-blue-600 font-bold">₱${parseFloat(acc.allocated_amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            html += '</div>'; // Close level 3
        });
    }
    
    html += '</div>'; // Close level 2
    html += '</div>'; // Close hierarchy-tree
    
    return html;
}

function toggleEditMode() {
    isEditMode = !isEditMode;
    const editBtn = document.getElementById('editModeBtn');
    const saveBtn = document.getElementById('saveChangesBtn');
    
    if (isEditMode) {
        editBtn.innerHTML = '<i class="fas fa-times text-xs"></i> Cancel Editing';
        editBtn.classList.add('bg-white/10', 'text-white');
        if (saveBtn) saveBtn.classList.remove('hidden');
    } else {
        editBtn.innerHTML = '<i class="fas fa-edit text-xs"></i> Edit Names';
        editBtn.classList.remove('bg-white/10', 'text-white');
        if (saveBtn) saveBtn.classList.add('hidden');
    }
    
    if (currentHierarchyData) {
        document.getElementById('hierarchyContent').innerHTML = renderHierarchy(currentHierarchyData);
    }
}

async function saveHierarchyChanges() {
    const inputs = document.querySelectorAll('.name-input');
    const updates = [];
    
    inputs.forEach(input => {
        updates.push({
            id: input.getAttribute('data-id'),
            name: input.value
        });
    });
    
    if (updates.length === 0) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_names');
        formData.append('updates', JSON.stringify(updates));
        
        const response = await fetch('?ajax_update_coa=1', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message, 'success');
            isEditMode = false;
            loadHierarchyData(currentCategoryId);
            
            // Refresh main table if needed (could just reload page for simplicity)
            // location.reload(); 
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to save changes', 'error');
    }
}

async function archiveItem(id, name) {
    showConfirmModal('Archive Item', `Are you sure you want to archive "${name}"? This item will no longer appear in the system.`, 'warning', async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'archive_item');
            formData.append('id', id);
            
            const response = await fetch('?ajax_update_coa=1', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                loadHierarchyData(currentCategoryId);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to archive item', 'error');
        }
    });
}

async function archiveCurrentCategory() {
    if (!currentCategoryId) return;
    
    const categoryName = currentHierarchyData.level2.name;
    showConfirmModal('Archive Category', `Are you sure you want to archive the entire category "${categoryName}"? All its sub-categories and accounts will also be hidden.`, 'warning', async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'archive_item');
            formData.append('id', currentCategoryId);
            
            const response = await fetch('?ajax_update_coa=1', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                closeHierarchyModal();
                location.reload(); // Refresh to update main table
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Failed to archive category', 'error');
        }
    });
}

async function toggleCategoryStatus() {
    const category = data.find(c => c.id === currentCategoryId);
    if (!category) return;
    
    showConfirmModal('Toggle Status', `Are you sure you want to ${category.status === 'active' ? 'deactivate' : 'activate'} this category?`, 'info', async () => {
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_toggle=1&id=${currentCategoryId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                closeHierarchyModal();
                // Reload the page to refresh data
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Error toggling category status', 'error');
        }
    });
}

// Toggle account status via AJAX
async function toggleAccount(accountId, accountName) {
    showConfirmModal('Toggle Status', `Are you sure you want to toggle the status of "${accountName}"?`, 'info', async () => {
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_toggle=1&id=${accountId}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update the account status in local data
                const accountIndex = data.findIndex(acc => acc.id == accountId);
                if (accountIndex !== -1) {
                    data[accountIndex].status = result.new_status;
                }
                
                // Update the table
                renderTable();
                
                // Update statistics
                updateStatistics();
                
                // Show success toast
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Error updating account status', 'error');
            console.error('Error:', error);
        }
    });
}

function updateStatistics() {
    const total = data.length;
    const active = data.filter(acc => acc.status === 'active').length;
    const inactive = data.filter(acc => acc.status === 'inactive').length;
    
    // Update DOM elements
    document.getElementById('totalAccounts').textContent = total;
    document.getElementById('activeAccounts').textContent = active;
}

// Custom Confirmation Modal Helper
function showConfirmModal(title, message, type = 'warning', onConfirm) {
    const modal = document.getElementById('confirmModal');
    const backdrop = document.getElementById('confirmModalBackdrop');
    const titleEl = document.getElementById('confirmTitle');
    const messageEl = document.getElementById('confirmMessage');
    const iconEl = document.getElementById('confirmIcon');
    const btnOk = document.getElementById('btnConfirmOk');
    const btnCancel = document.getElementById('btnConfirmCancel');

    titleEl.textContent = title;
    messageEl.textContent = message;

    // Reset styles
    iconEl.className = 'confirm-icon';
    btnOk.className = 'flex-1 px-4 py-2.5 text-sm font-bold text-white rounded-xl shadow-lg transition-all';
    
    if (type === 'warning') {
        iconEl.classList.add('bg-red-100', 'text-red-600');
        iconEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        btnOk.classList.add('bg-red-500', 'hover:bg-red-600', 'shadow-red-500/30');
    } else if (type === 'info') {
        iconEl.classList.add('bg-blue-100', 'text-blue-600');
        iconEl.innerHTML = '<i class="fas fa-info-circle"></i>';
        btnOk.classList.add('bg-blue-500', 'hover:bg-blue-600', 'shadow-blue-500/30');
    }

    modal.classList.add('show');
    backdrop.classList.add('show');

    const handleConfirm = () => {
        closeConfirmModal();
        if (onConfirm) onConfirm();
        btnOk.removeEventListener('click', handleConfirm);
    };

    const closeConfirmModal = () => {
        modal.classList.remove('show');
        backdrop.classList.remove('show');
        btnOk.removeEventListener('click', handleConfirm);
    };

    btnOk.onclick = handleConfirm;
    btnCancel.onclick = closeConfirmModal;
}

// Complex Add Account Modal Logic
let subcategoryCount = 0;
let addMode = 'new_category';

function showAddModal() {
    addMode = 'new_category';
    switchAddMode('new_category');
    document.getElementById('complexAddAccountForm').reset();
    document.getElementById('subcategoriesContainer').innerHTML = '';
    document.getElementById('existingModeAccountsContainer').innerHTML = '';
    addSubcategoryRow(); // Add first subcategory by default
    
    document.getElementById('addAccountModal').classList.add('show');
    document.getElementById('addAccountModalBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function switchAddMode(mode) {
    addMode = mode;
    document.getElementById('addModeInput').value = mode;
    
    const btnNew = document.getElementById('btnNewCategory');
    const btnExisting = document.getElementById('btnExistingCategory');
    const newFields = document.getElementById('newCategoryFields');
    const existingFields = document.getElementById('existingCategoryFields');
    const catInput = document.getElementById('categoryInputContainer');
    const catSelect = document.getElementById('categorySelectContainer');
    const modalTitle = document.getElementById('addModalTitle');

    if (mode === 'new_category') {
        btnNew.classList.add('active');
        btnExisting.classList.remove('active');
        newFields.classList.remove('hidden');
        existingFields.classList.add('hidden');
        catInput.classList.remove('hidden');
        catSelect.classList.add('hidden');
        modalTitle.textContent = 'Create New Category';
        
        // Reset and show New Category fields
        document.getElementById('newCategoryName').value = '';
        if (document.getElementById('subcategoriesContainer').children.length === 0) {
            addSubcategoryRow();
        }
        
        // Enable new fields, disable existing fields to avoid "not focusable" error
        newFields.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        catInput.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        
        existingFields.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        catSelect.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
    } else {
        btnNew.classList.remove('active');
        btnExisting.classList.add('active');
        newFields.classList.add('hidden');
        existingFields.classList.remove('hidden');
        catInput.classList.add('hidden');
        catSelect.classList.remove('hidden');
        modalTitle.textContent = 'Add to Existing Category';
        
        // Ensure existing fields are initialized
        if (document.getElementById('existingModeAccountsContainer').children.length === 0) {
            addGLAccountToExistingSub();
        }
        
        // Enable existing fields, disable new fields to avoid "not focusable" error
        newFields.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        catInput.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
        
        existingFields.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        catSelect.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
        
        onTypeChange(); // Automatically load categories
    }
}

async function onTypeChange() {
    if (addMode !== 'existing_category') return;
    
    const typeId = document.getElementById('addAccountTypeSelect').value;
    const catSelect = document.getElementById('existingCategorySelect');
    
    catSelect.innerHTML = '<option value="">Loading categories...</option>';
    
    if (!typeId) {
        catSelect.innerHTML = '<option value="">Select Category</option>';
        return;
    }
    
    try {
        const response = await fetch(`?ajax_get_meta=1&level=2&parent_id=${typeId}`);
        const result = await response.json();
        
        catSelect.innerHTML = '<option value="">Select Category</option>';
        if (result.success) {
            result.data.forEach(cat => {
                catSelect.innerHTML += `<option value="${cat.id}">${cat.name} (${cat.code})</option>`;
            });
        }
    } catch (error) {
        console.error('Error fetching categories:', error);
        catSelect.innerHTML = '<option value="">Error loading categories</option>';
        showToast('Failed to fetch categories. Please check your connection.', 'error');
    }
}

async function onCategoryChange() {
    const catId = document.getElementById('existingCategorySelect').value;
    const subSelect = document.getElementById('existingSubSelect');
    
    subSelect.innerHTML = '<option value="">Loading subcategories...</option>';
    
    if (!catId) {
        subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        return;
    }
    
    try {
        const response = await fetch(`?ajax_get_meta=1&level=3&parent_id=${catId}`);
        const result = await response.json();
        
        subSelect.innerHTML = '<option value="">Select Subcategory</option>';
        if (result.success) {
            result.data.forEach(sub => {
                subSelect.innerHTML += `<option value="${sub.id}">${sub.name} (${sub.code})</option>`;
            });
        }
    } catch (error) {
        console.error('Error fetching subcategories:', error);
        subSelect.innerHTML = '<option value="">Error loading subcategories</option>';
        showToast('Failed to fetch subcategories.', 'error');
    }
}

function toggleSubMode() {
    const isExisting = document.querySelector('input[name="sub_mode"]:checked').value === 'existing';
    const subSelect = document.getElementById('existingSubSelect');
    const subNameInput = document.getElementById('newSubName');
    
    if (isExisting) {
        subSelect.classList.remove('hidden');
        subSelect.disabled = false;
        subSelect.required = true;
        
        subNameInput.classList.add('hidden');
        subNameInput.disabled = true;
        subNameInput.required = false;
    } else {
        subSelect.classList.add('hidden');
        subSelect.disabled = true;
        subSelect.required = false;
        
        subNameInput.classList.remove('hidden');
        subNameInput.disabled = false;
        subNameInput.required = true;
    }
}

function addSubcategoryRow() {
    subcategoryCount++;
    const container = document.getElementById('subcategoriesContainer');
    const card = document.createElement('div');
    card.className = 'add-group-card animate-fade-in';
    card.id = `sub-card-${subcategoryCount}`;
    card.innerHTML = `
        <div class="add-group-card-header">
            <input type="text" name="sub_names[]" required placeholder="Subcategory Name" 
                class="bg-transparent border-b border-purple-200 outline-none font-bold text-gray-800 focus:border-purple-500 transition-colors">
            <button type="button" onclick="removeSubcategory(${subcategoryCount})" class="text-red-400 hover:text-red-600 text-xs font-bold">
                <i class="fas fa-times-circle"></i> Remove
            </button>
        </div>
        <div id="gl-accounts-container-${subcategoryCount}" class="space-y-3">
            <!-- GL Accounts will be added here -->
        </div>
        <button type="button" onclick="addGLAccountToSub(${subcategoryCount})" class="mt-4 text-[10px] font-bold text-purple-600 uppercase tracking-widest hover:text-purple-800">
            <i class="fas fa-plus"></i> Add Account
        </button>
    `;
    container.appendChild(card);
    addGLAccountToSub(subcategoryCount); // Add first GL account to the subcategory
}

function removeSubcategory(index) {
    const card = document.getElementById(`sub-card-${index}`);
    if (document.getElementById('subcategoriesContainer').children.length > 1) {
        card.remove();
    } else {
        showToast('At least one subcategory is required', 'error');
    }
}

function addGLAccountToSub(subIndex) {
    const container = document.getElementById(`gl-accounts-container-${subIndex}`);
    const div = document.createElement('div');
    div.className = 'gl-account-item animate-fade-in';
    div.innerHTML = `
        <div class="flex-1">
            <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase">Automated Code</span>
            <div class="w-full text-xs px-2 py-1.5 bg-gray-50 border border-gray-100 rounded-lg text-gray-400 font-mono italic">Auto-gen</div>
        </div>
        <div class="flex-[3]">
            <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase">GL Account Name</span>
            <input type="text" name="sub_${subIndex}_acc_names[]" required placeholder="Enter GL Account Name" 
                class="w-full text-xs px-2 py-1.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-purple-400">
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-gray-300 hover:text-red-500 mt-5">
            <i class="fas fa-trash-alt text-xs"></i>
        </button>
    `;
    container.appendChild(div);
}

function addGLAccountToExistingSub() {
    const container = document.getElementById('existingModeAccountsContainer');
    const div = document.createElement('div');
    div.className = 'gl-account-item animate-fade-in';
    div.innerHTML = `
        <div class="flex-1">
            <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase tracking-tight">Code</span>
            <div class="w-full text-sm px-3 py-2 bg-white border border-blue-50 rounded-lg text-gray-400 font-mono italic">Automated</div>
        </div>
        <div class="flex-[3]">
            <span class="text-[10px] font-bold text-gray-400 block mb-1 uppercase tracking-tight">GL Account Name</span>
            <input type="text" name="acc_names[]" required placeholder="Enter GL Account Name" 
                class="w-full text-sm px-3 py-2 border border-blue-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-gray-300 hover:text-red-500 mt-5">
            <i class="fas fa-trash-alt"></i>
        </button>
    `;
    container.appendChild(div);
}

// Intercept form submission
document.getElementById('complexAddAccountForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (addMode === 'new_category') {
        const categoryName = document.getElementById('newCategoryName').value;
        showConfirmModal('Confirm Creation', `Create new category "${categoryName}" with all subcategories and accounts?`, 'info', () => {
            submitNewCategory();
        });
    } else {
        const catSelect = document.getElementById('existingCategorySelect');
        const catName = catSelect.options[catSelect.selectedIndex].text;
        showConfirmModal('Confirm Addition', `Add these accounts to category "${catName}"?`, 'info', () => {
            submitExistingCategory();
        });
    }
});

async function submitNewCategory() {
    const form = document.getElementById('complexAddAccountForm');
    const formData = new FormData(form);
    formData.append('subcategory_count', subcategoryCount);
    
    await performSubmission(formData);
}

async function submitExistingCategory() {
    const form = document.getElementById('complexAddAccountForm');
    const formData = new FormData(form);
    
    const catId = document.getElementById('existingCategorySelect').value;
    if (!catId) {
        showToast('Please select a Category', 'error');
        return;
    }

    const subModeElement = document.querySelector('input[name="sub_mode"]:checked');
    const isExistingSub = subModeElement && subModeElement.value === 'existing';
    
    const subId = document.getElementById('existingSubSelect').value;
    const subName = document.getElementById('newSubName').value;
    
    if (isExistingSub && !subId) {
        showToast('Please select a Subcategory', 'error');
        return;
    }
    if (!isExistingSub && !subName.trim()) {
        showToast('Please enter a new Subcategory name', 'error');
        return;
    }

    formData.append('sub_mode_final', isExistingSub ? 'existing' : 'new');
    formData.append('sub_id_final', subId);
    formData.append('sub_name_final', subName);
    
    await performSubmission(formData);
}

async function performSubmission(formData) {
    // Debug what's being sent
    console.log('Submitting COA Form:', Object.fromEntries(formData.entries()));
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid data format');
        }
        
        if (result.success) {
            showToast(result.message, 'success');
            closeAddModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        showToast('Error saving data', 'error');
        console.error('Error:', error);
    }
}

function renderTable() {
    const tableBody = document.getElementById("accountTable");
    tableBody.innerHTML = "";

    // Ensure currentPage is always a valid number
    if (isNaN(currentPage) || currentPage < 1) {
        currentPage = 1;
    }

    const filteredData = filterData();
    const startIndex = (currentPage - 1) * rowsPerPage;
    const endIndex = Math.min(startIndex + rowsPerPage, filteredData.length);

    if (filteredData.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-search text-3xl mb-2 text-gray-300"></i>
                    <p class="text-lg">No accounts found</p>
                    <p class="text-sm mt-1">Try adjusting your search or add a new account</p>
                </td>
            </tr>
        `;
    } else {
        for (let i = startIndex; i < endIndex; i++) {
            const account = filteredData[i];
            const row = document.createElement("tr");
            row.className = "hover:bg-gray-50 transition duration-150";
            
            // Determine type badge color
            let typeBadgeClass = "px-2 py-1 rounded-full text-xs font-medium ";
            switch(account.type) {
                case 'Asset': typeBadgeClass += "bg-green-100 text-green-800"; break;
                case 'Liability': typeBadgeClass += "bg-yellow-100 text-yellow-800"; break;
                case 'Equity': typeBadgeClass += "bg-blue-100 text-blue-800"; break;
                case 'Revenue': typeBadgeClass += "bg-purple-100 text-purple-800"; break;
                case 'Expense': typeBadgeClass += "bg-red-100 text-red-800"; break;
                default: typeBadgeClass += "bg-gray-100 text-gray-800";
            }
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="font-mono font-semibold text-gray-700">${account.code}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="font-medium text-gray-900">${account.category}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="${typeBadgeClass}">${account.type}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="status-badge ${account.status === 'active' ? 'status-active' : 'status-inactive'}">
                        ${account.status === 'active' ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center justify-center space-x-2">
                        <button onclick="showHierarchyModal(${account.id})" 
                           class="text-purple-600 hover:text-purple-900 transition duration-200 p-1.5 rounded hover:bg-purple-50"
                           title="View & Edit Hierarchy">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                        <button onclick="toggleAccount(${account.id}, '${account.category.replace(/'/g, "\\'")}')" 
                           class="text-${account.status === 'active' ? 'yellow' : 'green'}-600 hover:text-${account.status === 'active' ? 'yellow' : 'green'}-900 transition duration-200 p-1.5 rounded hover:bg-${account.status === 'active' ? 'yellow' : 'green'}-50"
                           title="${account.status === 'active' ? 'Deactivate' : 'Activate'}">
                            <i class="fas fa-${account.status === 'active' ? 'pause' : 'check-circle'} text-lg"></i>
                        </button>
                    </div>
                </td>
            `;
            tableBody.appendChild(row);
        }
    }

    // Update page status
    const pageStatus = document.getElementById("pageStatus");
    pageStatus.innerHTML = `Showing <span class="font-semibold">${startIndex + 1}-${endIndex}</span> of <span class="font-semibold">${filteredData.length}</span> accounts`;

    // Update button states
    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = endIndex === filteredData.length;
}

function filterData() {
    const searchInputElement = document.getElementById("searchInput");
    const searchInput = searchInputElement ? searchInputElement.value.toLowerCase() : "";

    if (!data || data.length === 0) {
        return [];
    }

    return data.filter((item) => {
        const matchesSearch =
            (item.category && item.category.toLowerCase().includes(searchInput)) ||
            (item.code && item.code.toString().toLowerCase().includes(searchInput)) ||
            (item.type && item.type.toLowerCase().includes(searchInput));
        
        const matchesType = selectedType === "" || item.type === selectedType;

        return matchesSearch && matchesType;
    });
}

function filterTable() {
    currentPage = 1;
    renderTable();
}

function nextPage() {
    const filteredData = filterData();
    if (currentPage * rowsPerPage < filteredData.length) {
        currentPage++;
        renderTable();
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        renderTable();
    }
}

// Account type tabs functionality
function initTabIndicator() {
    const activeTab = document.querySelector('.account-type-tab.active');
    const indicator = document.getElementById('tab-indicator');
    if (activeTab && indicator) {
        indicator.style.transition = 'none';
        indicator.style.width = `${activeTab.offsetWidth}px`;
        indicator.style.left = `${activeTab.offsetLeft}px`;
        setTimeout(() => indicator.style.transition = '', 50);
    }
}

document.querySelectorAll('.account-type-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        if (this.classList.contains('active')) return;
        
        // Update active tab styling
        document.querySelectorAll('.account-type-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        this.classList.add('active');
        
        // Animate indicator
        const indicator = document.getElementById('tab-indicator');
        if (indicator) {
            indicator.style.width = `${this.offsetWidth}px`;
            indicator.style.left = `${this.offsetLeft}px`;
        }
        
        selectedType = this.getAttribute('data-type') || "";
        currentPage = 1;
        renderTable();
    });
});

// Initialize on load and resize
window.addEventListener('load', initTabIndicator);
window.addEventListener('resize', initTabIndicator);

// Attach event listeners
document.getElementById("prevPage").addEventListener("click", prevPage);
document.getElementById("nextPage").addEventListener("click", nextPage);
document.getElementById("searchInput").addEventListener("input", filterTable);

// Log data for debugging
console.log('Account Data:', data);
console.log('Current Page:', currentPage);

// Initially render the table
renderTable();

function closeAddModal() {
    document.getElementById('addAccountModal').classList.remove('show');
    document.getElementById('addAccountModalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}

function closeEditModal() {
    document.getElementById('editAccountModal').classList.remove('show');
    document.getElementById('editAccountModalBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.id === 'addAccountModalBackdrop') closeAddModal();
    if (event.target.id === 'editAccountModalBackdrop') closeEditModal();
    if (event.target.id === 'hierarchyModalBackdrop') closeHierarchyModal();
    if (event.target.id === 'confirmModalBackdrop') {
        document.getElementById('confirmModal').classList.remove('show');
        document.getElementById('confirmModalBackdrop').classList.remove('show');
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAddModal();
        closeEditModal();
        document.getElementById('confirmModal').classList.remove('show');
        document.getElementById('confirmModalBackdrop').classList.remove('show');
    }
});

// Event Listener for the Add Account button
document.getElementById('addAccountBtn').addEventListener('click', showAddModal);
</script>

    <!-- Dynamic Confirm Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-backdrop" id="confirmModalBackdrop"></div>
        <div class="modal-content">
            <div class="confirm-icon" id="confirmIcon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="confirm-title" id="confirmTitle">Confirm Action</h3>
            <p class="confirm-message" id="confirmMessage">Are you sure you want to proceed with this action?</p>
            <div class="flex gap-3">
                <button type="button" id="btnConfirmCancel" class="flex-1 px-4 py-2.5 text-sm font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-all">
                    Cancel
                </button>
                <button type="button" id="btnConfirmOk" class="flex-1 px-4 py-2.5 text-sm font-bold text-white bg-red-500 rounded-xl hover:bg-red-600 shadow-lg shadow-red-500/30 transition-all">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    </div>
</body>
</html>