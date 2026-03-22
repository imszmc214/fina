<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php");
  exit();
}

include 'connection.php';
include 'session_manager.php';
// Note: audit_logger.php is already included in session_manager.php

// Get current user info
$current_user_role = get_user_role();
$current_username = $_SESSION['users_username'] ?? $_SESSION['username'] ?? 'Unknown User';

// User permissions
$permissions = [
    'financial admin' => ['users' => true, 'roles' => true, 'audit' => true],
    'budget manager' => ['users' => true, 'roles' => true, 'audit' => true],
    'auditor' => ['users' => true, 'roles' => true, 'audit' => true],
    'collector' => ['users' => true, 'roles' => true, 'audit' => true],
    'disburse officer' => ['users' => true, 'roles' => true, 'audit' => true]
];

$userPermissions = $permissions[$current_user_role] ?? ['users' => false, 'roles' => false, 'audit' => false];

// ========== USERS TAB DATA ==========
$users = [];

// Handle POST requests for users tab
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($userId > 0) {
        // Get target username for audit logging
        $user_stmt = $conn->prepare("SELECT username FROM userss WHERE id = ?");
        $user_stmt->bind_param("i", $userId);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $target_user = $user_result->fetch_assoc();
        $target_username = $target_user['username'] ?? 'Unknown';
        $user_stmt->close();
        
        switch($action) {
            case 'save_assignment':
                $role = trim($_POST['role'] ?? '');
                if (!empty($role)) {
                    $stmt = $conn->prepare("UPDATE userss SET role = ?, account_status = 'active' WHERE id = ?");
                    $stmt->bind_param("si", $role, $userId);
                    $stmt->execute();
                    $stmt->close();
                    $_SESSION['feedback'] = "Role assigned successfully";
                    
                    // Log the role assignment
                    log_role_assignment($conn, $current_username, $target_username, $role);
                }
                break;
                
            case 'unlock':
                $stmt = $conn->prepare("UPDATE userss SET account_status = 'active', failed_attempts = 0 WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['feedback'] = "User account unlocked successfully";
                
                // Log the unlock action
                log_status_change($conn, $current_username, $target_username, 'unlock');
                break;
                
            case 'suspend':
                $stmt = $conn->prepare("UPDATE userss SET account_status = 'suspended' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['feedback'] = "User account suspended successfully";
                
                // Log the suspend action
                log_status_change($conn, $current_username, $target_username, 'suspend');
                break;
                
            case 'unsuspend':
                $stmt = $conn->prepare("UPDATE userss SET account_status = 'active' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['feedback'] = "User account unsuspended successfully";
                
                // Log the unsuspend action
                log_status_change($conn, $current_username, $target_username, 'unsuspend');
                break;
                
            case 'deactivate':
                $stmt = $conn->prepare("UPDATE userss SET account_status = 'deactivated' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['feedback'] = "User deactivated successfully";
                
                // Log the deactivate action
                log_status_change($conn, $current_username, $target_username, 'deactivate');
                break;

            case 'reactivate':
                $stmt = $conn->prepare("UPDATE userss SET account_status = 'active' WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                $_SESSION['feedback'] = "User reactivated successfully";
                
                // Log the reactivate action
                log_status_change($conn, $current_username, $target_username, 'activate');
                break;
        }
    }
}

// Fetch users data
$res = $conn->query("SELECT * FROM userss ORDER BY id DESC");
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $users[] = $r;
    }
    $res->free();
}

// ========== ROLES TAB DATA ==========
$roles = [];
$editRole = null;

// Fetch roles from database
$res_roles = $conn->query("SELECT * FROM roles ORDER BY id ASC");
if ($res_roles && $res_roles->num_rows > 0) {
    while ($row = $res_roles->fetch_assoc()) {
        $roles[] = $row;
    }
    $res_roles->free();
}

// Handle role actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_action'])) {
    $role_action = $_POST['role_action'];
    
    if ($role_action === 'add') {
        $role = trim($_POST['role'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!empty($role)) {
            $stmt = $conn->prepare("INSERT INTO roles (role, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $role, $description);
            if ($stmt->execute()) {
                $_SESSION['feedback'] = "Role added successfully";
                
                // Log the role creation
                log_role_management($conn, $current_username, 'add', $role);
            } else {
                $_SESSION['feedback'] = "Error adding role";
            }
            $stmt->close();
        }
    }
    elseif ($role_action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $role = trim($_POST['role'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($id > 0 && !empty($role)) {
            $stmt = $conn->prepare("UPDATE roles SET role = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $role, $description, $id);
            if ($stmt->execute()) {
                $_SESSION['feedback'] = "Role updated successfully";
                
                // Log the role update
                log_role_management($conn, $current_username, 'edit', $role);
            } else {
                $_SESSION['feedback'] = "Error updating role";
            }
            $stmt->close();
        }
    }
}

// ========== AUDIT LOGS TAB DATA ==========
$auditLogs = [];
$res_audit = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC");
if ($res_audit && $res_audit->num_rows > 0) {
    while ($row = $res_audit->fetch_assoc()) {
        $auditLogs[] = $row;
    }
    $res_audit->free();
}

// Log page view (Only logs once per session to avoid spamming the table)
if (!isset($_SESSION['viewed_user_management'])) {
    log_page_view($conn, $current_username, 'Audit Logs');
    $_SESSION['viewed_user_management'] = true;
}

// Handle feedback message for JavaScript toast
$feedback = "";
$feedbackType = "success";
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    $feedbackType = strpos($feedback, 'Error') === 0 ? "error" : "success";
    unset($_SESSION['feedback']);
}

$conn->close();
?>

<html>
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.0/jspdf.plugin.autotable.min.js"></script>
    <style>
        .statement-tab.active {
            border-bottom: 4px solid #f59e0b;
            color: #d97706;
            font-weight: 600;
        }
        .hover-scale:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        body {
            overflow-y: hidden;
        }
        .main-content-container {
            width: 100%;
            overflow-x: hidden;
        }
    </style>
</head>
<body class="bg-white">
    <?php include('sidebar.php'); ?>
<div class="overflow-y-auto h-full px-6">
    <!-- Breadcrumb -->
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl font-poppins text-gray-800">User Management</h1>
        <div class="text-sm text-gray-600">
            <a href="dashboard.php?page=dashboard" class="text-gray-600 hover:text-blue-600 transition duration-200">Home</a>
            /
            <a href="user_management.php" class="text-blue-600 hover:text-blue-700 transition duration-200">User Management</a>
        </div>
    </div>

    <!-- Main content area -->
    <div class="flex-1 bg-white p-6 h-full w-full main-content-container" style="overflow-x: hidden;">
        <!-- Header with Tabs and Actions -->
        <div class="flex justify-between items-center mb-4 mx-6 flex-wrap gap-4">
            <!-- Left: Tabs + Filters -->
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Tabs -->
                <div class="flex gap-2 font-poppins text-sm font-medium border-b border-gray-300">
                <a href="javascript:void(0)" onclick="switchTab('users')" class="statement-tab active px-4 py-2 rounded-t-full" data-tab="users">USERS</a>
                <a href="javascript:void(0)" onclick="switchTab('roles')" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300" data-tab="roles">ROLES</a>
                <a href="javascript:void(0)" onclick="switchTab('audit')" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300" data-tab="audit">AUDIT LOGS</a>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <input
                    type="text"
                    id="searchInput"
                    class="border px-4 py-2 rounded-full text-sm font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-200"
                    placeholder="Search Here..."
                    onkeyup="filterTable()" />
            </div>
        </div>
            <!-- Right: Search Bar, Export Buttons, and Add Role Button -->
            <div class="flex items-center gap-2">                
                <!-- Export Buttons (only for users and audit tabs) -->
                <div id="exportButtons" class="flex gap-2">
                    <button onclick="exportPDF()" class="bg-red-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-red-700" title="Export PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>
                    <button onclick="exportCSV()" class="bg-green-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700" title="Export CSV">
                        <i class="fas fa-file-csv"></i>
                    </button>
                    <button onclick="exportExcel()" class="bg-blue-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700" title="Export Excel">
                        <i class="fas fa-file-excel"></i>
                    </button>
                </div>

                <!-- Add Role Button (only for roles tab) -->
                <button id="addRoleBtn" onclick="showAddModal()" class="bg-purple-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-purple-700 transition duration-200 flex items-center gap-2 hidden">
                    <i class="fas fa-plus"></i>
                    Add Role
                </button>

                <!-- Refresh Button (only for audit tab) -->
                <button id="refreshBtn" onclick="refreshAuditLogs()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center gap-2 hidden">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>
<!-- Toast Notification -->
<div id="toast" class="fixed top-4 right-4 z-50 transition-all duration-300 transform translate-x-full">
        <!-- Feedback Message -->
        <?php if ($feedback): ?>
            <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($feedback, 'Error') === 0 ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200'; ?>">
                <div class="flex items-center">
                    <?php if (strpos($feedback, 'Error') === 0): ?>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php else: ?>
                        <i class="fas fa-check-circle mr-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($feedback); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

        <!-- USERS TAB CONTENT -->
        <div id="users-content" class="tab-content active">
            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
                <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                    <i class="fas fa-users text-xl"></i>
                    <h2 class="text-2xl font-poppins text-black">List of Users</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full table-auto bg-white">
                        <thead>
                            <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                <th class="px-4 py-2">Username</th>
                                <th class="px-4 py-2">Name</th>
                                <th class="px-4 py-2">Email</th>
                                <th class="px-4 py-2">Role</th>
                                <th class="px-4 py-2">Status</th>
                                <th class="px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="userTable" class="text-gray-900 text-sm font-light">
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $u): 
                                    $statusVal = isset($u['account_status']) ? trim((string)$u['account_status']) : '';
                                    $isNew = ($statusVal === '' || $statusVal === null || strcasecmp($statusVal, 'new user') === 0);
                                    ?>
                                    <tr class="border-b hover:bg-violet-100 transition-all duration-300 hover-scale" data-user-id="<?php echo intval($u['id']); ?>">
                                        <td class="px-4 py-2 whitespace-nowrap"><?php echo htmlspecialchars($u['username'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($u['gname'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($u['email'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2 text-left">
                                            <?php echo $isNew ? '—' : htmlspecialchars($u['role'] ?? '—'); ?>
                                        </td>
                                        <td class="px-4 py-2 text-left">
                                            <?php
                                                if ($isNew) {
                                                    echo '<span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold">New User</span>';
                                                } elseif (strcasecmp($statusVal, 'active') === 0) {
                                                    echo '<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold">Active</span>';
                                                } elseif (strcasecmp($statusVal, 'suspended') === 0) {
                                                    echo '<span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold">Suspended</span>';
                                                } elseif (strcasecmp($statusVal, 'locked') === 0) {
                                                    echo '<span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-semibold">Locked</span>';
                                                } elseif (strcasecmp($statusVal, 'deactivated') === 0) {
                                                    echo '<span class="bg-gray-300 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold">Deactivated</span>';
                                                } else {
                                                    echo '<span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs font-semibold">'.htmlspecialchars($statusVal ?: 'New User').'</span>';
                                                }
                                            ?>
                                        </td>
                                        <td class="px-4 py-2 text-left">
                                            <button
                                                class="editBtn bg-blue-100 text-blue-600 px-2 py-1 rounded-lg hover:bg-blue-500 hover:text-white text-sm font-poppins transition duration-200"
                                                data-user-id="<?php echo $u['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                data-status="<?php echo htmlspecialchars($statusVal); ?>"
                                            ><i class="fas fa-edit mr-2"></i>Edit</button>
        
                                            <?php if (strcasecmp($statusVal, 'deactivated') === 0): ?>
                                                <!-- Reactivate button for deactivated users -->
                                                <form method="POST" action="user_management.php?tab=users" style="display:inline" onsubmit="return confirm('Reactivate this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="action" value="reactivate">
                                                    <button type="submit" class="bg-green-100 text-green-600 px-2 py-1 rounded-lg hover:bg-green-500 hover:text-white text-sm font-poppins transition duration-200">
                                                        <i class="fas fa-user-check mr-2"></i>Reactivate
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <!-- Deactivate button for active users -->
                                                <form method="POST" action="user_management.php?tab=users" style="display:inline" onsubmit="return confirm('Deactivate this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <button type="submit" class="bg-red-100 text-red-500 px-2 py-1 rounded-lg hover:bg-red-500 hover:text-white text-sm font-poppins transition duration-200">
                                                        <i class="fas fa-user-slash mr-2"></i>Deactivate
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-users text-4xl mb-4 text-gray-400"></i>
                                            <p class="text-lg font-medium mb-2">No users found</p>
                                            <p class="text-sm">No user accounts have been created yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
            <div class="mt-6 flex justify-between items-center">
                <div id="pageStatus" class="text-gray-700 font-bold"></div>
                <div class="flex gap-2">
                    <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="prevPage()">Previous</button>
                    <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="nextPage()">Next</button>
                </div>
            </div>
        </div>

        <!-- ROLES TAB CONTENT -->
        <div id="roles-content" class="tab-content">
            <!-- Roles Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
                <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                    <i class="fas fa-user-shield text-xl"></i>
                    <h2 class="text-2xl font-poppins text-black">List of Roles</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto bg-white">
                        <thead>
                            <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                <th class="px-4 py-2">ID</th>
                                <th class="px-4 py-2">Role</th>
                                <th class="px-4 py-2">Description</th>
                                <th class="pl-12 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roleTable" class="text-gray-900 text-sm font-light">
                            <?php if (!empty($roles)): ?>
                                <?php foreach ($roles as $role_row): ?>
                                    <tr class="border-b hover:bg-violet-100 transition-all duration-300 hover-scale">
                                        <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($role_row['id']); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($role_row['role']); ?>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600">
                                            <?php echo htmlspecialchars($role_row['description']); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="flex gap-2">
                                                <!-- Edit Button -->
                                                <button onclick="showEditModal(<?php echo $role_row['id']; ?>)" 
                                                       class="bg-blue-100 text-blue-600 px-2 py-1 rounded-lg hover:bg-blue-500 hover:text-white text-sm font-poppins transition duration-200 flex items-center gap-1">
                                                    <i class="fas fa-edit"></i>
                                                    Edit
                                                </button>
                                                
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                                            <p class="text-lg font-medium mb-2">No roles found</p>
                                            <p class="text-sm">Get started by adding your first role.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pagination -->
                <div class="mt-6 flex justify-between items-center">
                    <div id="rolesPageStatus" class="text-gray-700 font-bold"></div>
                    <div class="flex gap-2">
                        <button id="rolesPrevPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="prevRolesPage()">Previous</button>
                        <button id="rolesNextPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="nextRolesPage()">Next</button>
                    </div>
                </div>
        </div>

        <!-- AUDIT LOGS TAB CONTENT -->
        <div id="audit-content" class="tab-content">
            <!-- Summary Cards -->
            <?php if (!empty($auditLogs)): ?>
                <div class="mt-4 mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Actions Card -->
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90 mb-1">Total Actions</p>
                                <p class="text-2xl font-bold"><?php echo count($auditLogs); ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-2 rounded-full">
                                <i class="fas fa-chart-bar text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs opacity-90">
                            <i class="fas fa-clock mr-1"></i>
                            <span>All time activities</span>
                        </div>
                    </div>

                    <!-- Today's Activities Card -->
                    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90 mb-1">Today's Activities</p>
                                <p class="text-2xl font-bold">
                                    <?php
                                    $today = date('Y-m-d');
                                    $todayActivities = array_filter($auditLogs, function($log) use ($today) {
                                        return date('Y-m-d', strtotime($log['created_at'])) === $today;
                                    });
                                    echo count($todayActivities);
                                    ?>
                                </p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-2 rounded-full">
                                <i class="fas fa-calendar-day text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs opacity-90">
                            <i class="fas fa-calendar mr-1"></i>
                            <span><?php echo date('M j, Y'); ?></span>
                        </div>
                    </div>

                    <!-- Unique Users Card -->
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90 mb-1">Unique Users</p>
                                <p class="text-2xl font-bold">
                                    <?php
                                    $uniqueUsers = array_unique(array_column($auditLogs, 'user'));
                                    echo count($uniqueUsers);
                                    ?>
                                </p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-2 rounded-full">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs opacity-90">
                            <i class="fas fa-user-check mr-1"></i>
                            <span>Active users in system</span>
                        </div>
                    </div>

                    <!-- Latest Activity Card -->
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium opacity-90 mb-1">Latest Activity</p>
                                <p class="text-lg font-bold">
                                    <?php 
                                    $latest = $auditLogs[0]['created_at'];
                                    $date = new DateTime($latest);
                                    echo $date->format('g:i A');
                                    ?>
                                </p>
                                <p class="text-xs opacity-90 mt-1"><?php echo $date->format('M j, Y'); ?></p>
                            </div>
                            <div class="bg-white bg-opacity-20 p-2 rounded-full">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs opacity-90">
                            <i class="fas fa-user mr-1"></i>
                            <span><?php echo htmlspecialchars($auditLogs[0]['user']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Audit Logs Table -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
                <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                    <i class="fas fa-clipboard-list text-xl"></i>
                    <h2 class="text-2xl font-poppins text-black">Audit Logs</h2>
                </div>
                <p class="text-sm text-gray-600 mt-1 mb-6 ml-6">Monitor system activities and user actions</p>

                <div class="overflow-x-auto w-full transition-all duration-500">
                    <table class="w-full table-auto bg-white">
                        <thead>
                            <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                <th class="px-4 py-2">ID</th>
                                <th class="pl-8 py-2">User</th>
                                <th class="px-4 py-2">Action</th>
                                <th class="pl-8 py-2">Date</th>
                                <th class="px-4 py-2">Time</th>
                            </tr>
                        </thead>
                        <tbody id="auditTable" class="text-gray-900 text-sm font-light">
                            <?php if (!empty($auditLogs)): ?>
                                <?php foreach ($auditLogs as $index => $audit_row): ?>
                                    <tr class="border-b hover:bg-violet-100 transition-all duration-300 hover-scale">
                                        <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">
                                            #<?php echo htmlspecialchars($audit_row['id']); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                                <i class="fas fa-user mr-2"></i>
                                                <?php echo htmlspecialchars($audit_row['user']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex items-center">
                                                <?php 
                                                $action = strtolower($audit_row['action']);
                                                if (strpos($action, 'login') !== false) {
                                                    echo '<div class="flex items-center text-green-600"><i class="fas fa-sign-in-alt mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } elseif (strpos($action, 'logout') !== false) {
                                                    echo '<div class="flex items-center text-red-600"><i class="fas fa-sign-out-alt mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } elseif (strpos($action, 'view') !== false) {
                                                    echo '<div class="flex items-center text-blue-600"><i class="fas fa-eye mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
                                                    echo '<div class="flex items-center text-yellow-600"><i class="fas fa-edit mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
                                                    echo '<div class="flex items-center text-green-600"><i class="fas fa-plus-circle mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
                                                    echo '<div class="flex items-center text-red-600"><i class="fas fa-trash mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                } else {
                                                    echo '<div class="flex items-center text-gray-600"><i class="fas fa-info-circle mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-600 font-medium">
                                            <?php 
                                            $date = new DateTime($audit_row['created_at']);
                                            echo $date->format('M j, Y');
                                            ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-gray-500">
                                            <?php 
                                            echo $date->format('g:i A');
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-clipboard-list text-6xl mb-4 text-gray-300"></i>
                                            <p class="text-xl font-medium mb-2 text-gray-400">No Audit Logs Found</p>
                                            <p class="text-sm text-gray-500 max-w-md text-center">Audit logs will appear here as users perform actions in the system.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-6">
                        <canvas id="pdf-viewer" width="600" height="400"></canvas>
                    </div>
</div>

<!-- ASSIGN/EDIT USER MODAL -->
<div id="assignModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 flex">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl border border-gray-200 p-6 relative mx-4">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl transition duration-200">&times;</button>
        <h3 id="modalTitle" class="text-xl font-semibold mb-4 text-gray-800">Assign Role</h3>

        <form id="assignForm" method="POST">
            <input type="hidden" name="user_id" id="modal_user_id" value="">
            <input type="hidden" name="action" value="save_assignment">
            <div class="mb-4">
                <label class="block font-semibold mb-2 text-gray-700">Username</label>
                <input type="text" id="modal_username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200" readonly>
            </div>

            <div class="mb-6">
                <label class="block font-semibold mb-2 text-gray-700">Role</label>
                <select name="role" id="modal_role" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200">
                    <option value="">-- Select Role --</option>
                    <option value="financial admin">Financial Admin</option>
                    <option value="budget manager">Budget Manager</option>
                    <option value="disburse officer">Disburse Officer</option>
                    <option value="collector">Collector</option>
                    <option value="auditor">Auditor</option>
                </select>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeModal()" class="px-6 py-3 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition duration-200 font-semibold">Cancel</button>
                <button type="submit" class="px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition duration-200 font-semibold">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL WITH STATUS MANAGEMENT -->
<div id="editUserModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 flex">
    <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl border border-gray-200 p-6 relative mx-4">
        <button onclick="closeEditModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-2xl transition duration-200">&times;</button>
        <h3 class="text-xl font-semibold mb-4 text-gray-800">Manage User</h3>

        <div class="mb-6">
            <div class="mb-4">
                <label class="block font-semibold mb-2 text-gray-700">Username</label>
                <input type="text" id="edit_modal_username" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200" readonly>
            </div>

            <div class="mb-4">
                <label class="block font-semibold mb-2 text-gray-700">Current Status</label>
                <div id="current_status_display" class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"></div>
            </div>

            <div class="mb-6">
                <label class="block font-semibold mb-2 text-gray-700">Role</label>
                <input type="text" id="edit_modal_role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 transition duration-200 bg-gray-50 cursor-not-allowed" readonly>
            </div>

            <!-- Status Management Buttons -->
            <div class="mb-6">
                <label class="block font-semibold mb-3 text-gray-700">Account Management</label>
                <div class="grid grid-cols-1 gap-3" id="status_management_buttons">
                    <!-- Buttons will be dynamically inserted here -->
                </div>
            </div>
        </div>

        <div class="flex gap-3 justify-end">
            <button type="button" onclick="closeEditModal()" class="px-6 py-3 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition duration-200 font-semibold">Cancel</button>
            <button type="button" onclick="saveUserChanges()" class="px-6 py-3 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition duration-200 font-semibold">Save Changes</button>
        </div>
    </div>
</div>

<!-- ADD ROLE MODAL -->
<div id="addRoleModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="bg-white p-8 rounded-2xl shadow-2xl min-w-[480px] space-y-6 relative transform transition-transform duration-300">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-2xl font-bold text-gray-800">Add New Role</h3>
            <button type="button" onclick="hideAddModal()" class="text-gray-400 hover:text-gray-600 transition duration-200 text-2xl">
                &times;
            </button>
        </div>
        <form method="post">
            <input type="hidden" name="role_action" value="add">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Role Name</label>
                    <input name="role" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                           placeholder="Enter role name">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <input name="description" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                           placeholder="Enter role description">
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200">
                    Add Role
                </button>
                <button type="button" onclick="hideAddModal()" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT ROLE MODAL -->
<div id="editRoleModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="bg-white p-8 rounded-2xl shadow-2xl min-w-[480px] space-y-6 relative transform transition-transform duration-300">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-2xl font-bold text-gray-800">Edit Role</h3>
            <button type="button" onclick="hideEditModal()" class="text-gray-400 hover:text-gray-600 transition duration-200 text-2xl">
                &times;
            </button>
        </div>
        <!-- I-CHANGE: Tanggalin ang form at gawing regular div -->
        <div id="editRoleForm">
            <input type="hidden" name="role_action" value="edit">
            <input type="hidden" name="id" id="edit_role_id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Role Name</label>
                    <input name="role" id="edit_role_name" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <input name="description" id="edit_role_description" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200">
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <!-- I-CHANGE: Gamitin ang onclick para sa save -->
                <button type="button" onclick="saveRoleChanges()" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200">
                    Save Changes
                </button>
                <button type="button" onclick="hideEditModal()" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>

// ========== SAVE ROLE CHANGES ==========
function saveRoleChanges() {
    const roleId = document.getElementById('edit_role_id').value;
    const roleName = document.getElementById('edit_role_name').value;
    const roleDescription = document.getElementById('edit_role_description').value;
    
    if (!roleId || !roleName.trim()) {
        showToast('Please fill in all required fields', true);
        return;
    }
    
    // Create form for submission
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'user_management.php';
    
    const roleActionInput = document.createElement('input');
    roleActionInput.type = 'hidden';
    roleActionInput.name = 'role_action';
    roleActionInput.value = 'edit';
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = roleId;
    
    const roleInput = document.createElement('input');
    roleInput.type = 'hidden';
    roleInput.name = 'role';
    roleInput.value = roleName;
    
    const descInput = document.createElement('input');
    descInput.type = 'hidden';
    descInput.name = 'description';
    descInput.value = roleDescription;
    
    form.appendChild(roleActionInput);
    form.appendChild(idInput);
    form.appendChild(roleInput);
    form.appendChild(descInput);
    
    document.body.appendChild(form);
    form.submit();
}

    // ========== TOAST NOTIFICATIONS ==========
function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    
    // Remove existing toast
    toast.innerHTML = '';
    toast.className = 'fixed top-4 right-4 z-50 transition-all duration-300 transform translate-x-full';
    
    // Create toast content
    const toastClass = isError ? 
        'bg-red-100 text-red-800 border border-red-200' : 
        'bg-green-100 text-green-800 border border-green-200';
    
    const toastIcon = isError ? 
        '<i class="fas fa-exclamation-circle mr-2"></i>' : 
        '<i class="fas fa-check-circle mr-2"></i>';
    
    toast.innerHTML = `
        <div class="px-6 py-4 rounded-lg shadow-lg ${toastClass} flex items-center">
            ${toastIcon}
            ${message}
        </div>
    `;
    
    // Show toast
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Hide toast after 5 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
    }, 5000);
}

// ========== SHOW TOAST ON PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', function() {
    // Restore active tab from session storage
    const savedTab = sessionStorage.getItem('currentTab');
    if (savedTab && savedTab !== 'users') {
        switchTab(savedTab);
    }
    
    // Initialize pagination
    updatePagination();
    updateRolesPagination();
    updateAuditPagination();
    
    // Show toast if there's a feedback message
    <?php if (!empty($feedback)): ?>
        setTimeout(() => {
            showToast('<?php echo addslashes($feedback); ?>', <?php echo $feedbackType === 'error' ? 'true' : 'false'; ?>);
        }, 500);
    <?php endif; ?>
});

// ========== TAB MANAGEMENT ==========
function switchTab(tabName) {
    console.log('Switching to tab:', tabName);
    
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Show selected content
    const targetContent = document.getElementById(tabName + '-content');
    if (targetContent) {
        targetContent.classList.add('active');
    }
    
    // Update tab styles
    document.querySelectorAll('.statement-tab').forEach(tab => {
        tab.classList.remove('active');
        tab.classList.remove('text-yellow-500', 'border-b-2', 'border-yellow-300');
        tab.classList.add('text-gray-900', 'hover:text-yellow-500', 'hover:border-b-2', 'hover:border-yellow-300');
    });
    
    // Activate current tab
    const currentTab = document.querySelector(`[data-tab="${tabName}"]`);
    if (currentTab) {
        currentTab.classList.add('active');
        currentTab.classList.remove('text-gray-900', 'hover:text-yellow-500', 'hover:border-b-2', 'hover:border-yellow-300');
    }
    
    // Show/hide action buttons based on tab
    const addRoleBtn = document.getElementById('addRoleBtn');
    const refreshBtn = document.getElementById('refreshBtn');
    const exportButtons = document.getElementById('exportButtons');
    
    if (tabName === 'roles') {
        addRoleBtn.classList.remove('hidden');
        refreshBtn.classList.add('hidden');
        exportButtons.classList.add('hidden');
    } else if (tabName === 'audit') {
        addRoleBtn.classList.add('hidden');
        refreshBtn.classList.remove('hidden');
        exportButtons.classList.remove('hidden');
    } else {
        addRoleBtn.classList.add('hidden');
        refreshBtn.classList.add('hidden');
        exportButtons.classList.remove('hidden');
    }
    
    // Store current tab in session storage
    sessionStorage.setItem('currentTab', tabName);
    
    // Reset pagination for current tab
    if (tabName === 'users') {
        currentPage = 1;
        updatePagination();
    } else if (tabName === 'roles') {
        currentRolesPage = 1;
        updateRolesPagination();
    } else if (tabName === 'audit') {
        currentAuditPage = 1;
        updateAuditPagination();
    }
}

// ========== SEARCH FUNCTIONALITY ==========
function filterTable() {
    const searchText = document.getElementById('searchInput').value.toLowerCase().trim();
    const activeTab = document.querySelector('.tab-content.active').id;
    
    console.log('Searching for:', searchText, 'in tab:', activeTab);
    
    let rows;
    if (activeTab === 'users-content') {
        rows = document.querySelectorAll('#userTable tr[data-user-id]');
    } else if (activeTab === 'roles-content') {
        rows = document.querySelectorAll('#roleTable tr');
    } else if (activeTab === 'audit-content') {
        rows = document.querySelectorAll('#auditTable tr');
    } else {
        return;
    }

    let visibleCount = 0;
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (searchText === '' || text.includes(searchText)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    console.log('Visible rows:', visibleCount);

    // Reset pagination for current tab
    if (activeTab === 'users-content') {
        currentPage = 1;
        updatePagination();
    } else if (activeTab === 'roles-content') {
        currentRolesPage = 1;
        updateRolesPagination();
    } else if (activeTab === 'audit-content') {
        currentAuditPage = 1;
        updateAuditPagination();
    }
}

// ========== USERS TAB FUNCTIONALITY ==========
const users = <?php echo json_encode($users, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;

const modal = document.getElementById('assignModal');
const editUserModal = document.getElementById('editUserModal');
const modalUserId = document.getElementById('modal_user_id');
const modalUsername = document.getElementById('modal_username');
const modalRole = document.getElementById('modal_role');
const modalTitle = document.getElementById('modalTitle');

const editModalUsername = document.getElementById('edit_modal_username');
const editModalRole = document.getElementById('edit_modal_role');
const currentStatusDisplay = document.getElementById('current_status_display');
const statusManagementButtons = document.getElementById('status_management_buttons');

let currentEditUserId = null;

function openModalForUser(userId, mode = 'assign') {
    const u = users.find(x => parseInt(x.id) === parseInt(userId));
    if (!u) return;
    
    if (mode === 'assign') {
        modalUserId.value = u.id;
        modalUsername.value = u.username || '';
        modalRole.value = u.role || '';
        modalTitle.innerText = 'Assign Role';
        modal.classList.remove('hidden');
    } else {
        currentEditUserId = u.id;
        editModalUsername.value = u.username || '';
        editModalRole.value = u.role || '';
        
        const statusVal = u.account_status ? u.account_status.trim() : '';
        let statusText = '';
        let statusClass = '';
        
        if (statusVal === '' || statusVal === null || statusVal.toLowerCase() === 'new user') {
            statusText = 'New User';
            statusClass = 'bg-gray-100 text-gray-700';
        } else if (statusVal.toLowerCase() === 'active') {
            statusText = 'Active';
            statusClass = 'bg-green-100 text-green-700';
        } else if (statusVal.toLowerCase() === 'suspended') {
            statusText = 'Suspended';
            statusClass = 'bg-red-100 text-red-700';
        } else if (statusVal.toLowerCase() === 'locked') {
            statusText = 'Locked';
            statusClass = 'bg-orange-100 text-orange-700';
        } else {
            statusText = statusVal;
            statusClass = 'bg-gray-100 text-gray-700';
        }
        
        currentStatusDisplay.innerHTML = `<span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span>`;
        
        // Set up status management buttons
        statusManagementButtons.innerHTML = '';
        
        if (statusVal.toLowerCase() === 'locked') {
            statusManagementButtons.innerHTML = `
                <button type="button" onclick="performStatusAction('unlock')" class="w-full bg-green-200 text-green-600 px-4 py-3 rounded-lg hover:bg-green-500 hover:text-white text-sm font-semibold transition duration-200 text-center">
                    <i class="fas fa-lock-open mr-2"></i>Unlock Account
                </button>
            `;
        } else if (statusVal.toLowerCase() === 'suspended') {
            statusManagementButtons.innerHTML = `
                <button type="button" onclick="performStatusAction('unsuspend')" class="w-full bg-blue-200 text-blue-600 px-4 py-3 rounded-lg hover:bg-blue-500 hover:text-white text-sm font-semibold transition duration-200 text-center">
                    <i class="fas fa-play mr-2"></i>Unsuspend Account
                </button>
            `;
        } else if (statusVal.toLowerCase() === 'active') {
            statusManagementButtons.innerHTML = `
                <button type="button" onclick="performStatusAction('suspend')" class="w-full bg-red-200 text-red-500 px-4 py-3 rounded-lg hover:bg-red-500 hover:text-white text-sm font-semibold transition duration-200 text-center">
                    <i class="fas fa-pause mr-2"></i>Suspend Account
                </button>
            `;
        }
        
        editUserModal.classList.remove('hidden');
    }
}

function closeModal() {
    modal.classList.add('hidden');
}

function closeEditModal() {
    editUserModal.classList.add('hidden');
    currentEditUserId = null;
}

function performStatusAction(action) {
    if (!currentEditUserId) return;
    
    let confirmMessage = '';
    switch(action) {
        case 'suspend':
            confirmMessage = 'Are you sure you want to suspend this user account?';
            break;
        case 'unsuspend':
            confirmMessage = 'Are you sure you want to unsuspend this user account?';
            break;
        case 'unlock':
            confirmMessage = 'Are you sure you want to unlock this user account?';
            break;
    }
    
    if (confirm(confirmMessage)) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'user_management.php?tab=users';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = currentEditUserId;
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        
        form.appendChild(userIdInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function saveUserChanges() {
    if (!currentEditUserId) return;
    
    // Create a form to save role changes
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'user_management.php?tab=users';
    
    const userIdInput = document.createElement('input');
    userIdInput.type = 'hidden';
    userIdInput.name = 'user_id';
    userIdInput.value = currentEditUserId;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'save_assignment';
    
    const roleInput = document.createElement('input');
    roleInput.type = 'hidden';
    roleInput.name = 'role';
    roleInput.value = editModalRole.value;
    
    form.appendChild(userIdInput);
    form.appendChild(actionInput);
    form.appendChild(roleInput);
    document.body.appendChild(form);
    form.submit();
}

// Event delegation for assign/edit buttons
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('assignBtn')) {
        openModalForUser(parseInt(e.target.dataset.userId), 'assign');
    } else if (e.target.classList.contains('editBtn')) {
        openModalForUser(parseInt(e.target.dataset.userId), 'edit');
    }
});

// ========== USERS PAGINATION ==========
let currentPage = 1;
const rowsPerPage = 10;
const tableBody = document.getElementById('userTable');
const pageStatus = document.getElementById('pageStatus');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');

function updatePagination() {
    const rows = Array.from(tableBody.querySelectorAll('tr[data-user-id]')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    rows.forEach((row, i) => {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;

    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    
    if (prevBtn.disabled) {
        prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    if (nextBtn.disabled) {
        nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        updatePagination();
    }
}

function nextPage() {
    const rows = Array.from(tableBody.querySelectorAll('tr[data-user-id]')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updatePagination();
    }
}

// ========== ROLES PAGINATION ==========
let currentRolesPage = 1;
const rolesTableBody = document.getElementById('roleTable');
const rolesPageStatus = document.getElementById('rolesPageStatus');
const rolesPrevBtn = document.getElementById('rolesPrevPage');
const rolesNextBtn = document.getElementById('rolesNextPage');

function updateRolesPagination() {
    const rows = Array.from(rolesTableBody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentRolesPage > totalPages) currentRolesPage = totalPages;
    if (currentRolesPage < 1) currentRolesPage = 1;

    rows.forEach((row, i) => {
        const start = (currentRolesPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    const start = rows.length > 0 ? (currentRolesPage - 1) * rowsPerPage + 1 : 0;
    const last = Math.min(currentRolesPage * rowsPerPage, rows.length);
    rolesPageStatus.textContent = `Showing ${start} to ${last} of ${rows.length} entries`;

    rolesPrevBtn.disabled = currentRolesPage === 1;
    rolesNextBtn.disabled = currentRolesPage === totalPages || totalPages === 0;
    
    if (rolesPrevBtn.disabled) {
        rolesPrevBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        rolesPrevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    if (rolesNextBtn.disabled) {
        rolesNextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        rolesNextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function prevRolesPage() {
    if (currentRolesPage > 1) {
        currentRolesPage--;
        updateRolesPagination();
    }
}

function nextRolesPage() {
    const rows = Array.from(rolesTableBody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentRolesPage < totalPages) {
        currentRolesPage++;
        updateRolesPagination();
    }
}

// ========== AUDIT PAGINATION ==========
let currentAuditPage = 1;
const auditTableBody = document.getElementById('auditTable');

function updateAuditPagination() {
    const rows = Array.from(auditTableBody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentAuditPage > totalPages) currentAuditPage = totalPages;
    if (currentAuditPage < 1) currentAuditPage = 1;

    rows.forEach((row, i) => {
        const start = (currentAuditPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });
}

function prevAuditPage() {
    if (currentAuditPage > 1) {
        currentAuditPage--;
        updateAuditPagination();
    }
}

function nextAuditPage() {
    const rows = Array.from(auditTableBody.querySelectorAll('tr')).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentAuditPage < totalPages) {
        currentAuditPage++;
        updateAuditPagination();
    }
}

// ========== ROLE MODAL FUNCTIONALITY ==========
function showAddModal() {
    document.getElementById('addRoleModal').classList.remove('hidden');
}

function hideAddModal() {
    document.getElementById('addRoleModal').classList.add('hidden');
}

function showEditModal(roleId) {
    const roles = <?php echo json_encode($roles, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
    const role = roles.find(r => r.id == roleId);
    
    if (role) {
        document.getElementById('edit_role_id').value = role.id;
        document.getElementById('edit_role_name').value = role.role;
        document.getElementById('edit_role_description').value = role.description;
        document.getElementById('editRoleModal').classList.remove('hidden');
    }
}

function hideEditModal() {
    document.getElementById('editRoleModal').classList.add('hidden');
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const modals = ['assignModal', 'editUserModal', 'addRoleModal', 'editRoleModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal && !modal.classList.contains('hidden') && event.target === modal) {
            if (modalId === 'assignModal') closeModal();
            if (modalId === 'editUserModal') closeEditModal();
            if (modalId === 'addRoleModal') hideAddModal();
            if (modalId === 'editRoleModal') hideEditModal();
        }
    });
});

// Escape key to close modals
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
        closeEditModal();
        hideAddModal();
        hideEditModal();
    }
});

// ========== EXPORT FUNCTIONS ==========
function getExportData() {
    const activeTab = document.querySelector('.tab-content.active').id;
    
    if (activeTab === 'users-content') {
        const headers = ["Username", "Name", "Email", "Role", "Status"];
        const data = [];
        
        users.forEach(user => {
            const statusVal = user.account_status ? user.account_status.trim() : '';
            const isNew = (statusVal === '' || statusVal === null || statusVal.toLowerCase() === 'new user');
            
            let statusDisplay = '';
            if (isNew) {
                statusDisplay = 'New User';
            } else if (statusVal.toLowerCase() === 'active') {
                statusDisplay = 'Active';
            } else if (statusVal.toLowerCase() === 'suspended') {
                statusDisplay = 'Suspended';
            } else if (statusVal.toLowerCase() === 'locked') {
                statusDisplay = 'Locked';
            } else {
                statusDisplay = statusVal || 'New User';
            }
            
            data.push([
                user.username || 'N/A',
                user.gname || 'N/A',
                user.email || 'N/A',
                isNew ? '—' : (user.role || '—'),
                statusDisplay
            ]);
        });
        
        return {headers, data, title: "User Management Report", filename: "user-management-report"};
        
    } else if (activeTab === 'audit-content') {
        const headers = ["ID", "User", "Action", "Date", "Time"];
        const data = [];
        
        const auditLogs = <?php echo json_encode($auditLogs, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
        auditLogs.forEach(log => {
            const date = new Date(log.created_at);
            const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const formattedTime = date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            
            data.push([
                log.id,
                log.user,
                log.action,
                formattedDate,
                formattedTime
            ]);
        });
        
        return {headers, data, title: "Audit Logs Report", filename: "audit-logs-report"};
    }
}

function exportPDF() {
    const {headers, data, title, filename} = getExportData();
    const doc = new window.jspdf.jsPDF('p', 'pt', 'a4');
    doc.setFontSize(15);
    doc.text(title, 40, 40);
    doc.autoTable({
        head: [headers],
        body: data,
        startY: 60,
        theme: 'striped',
        headStyles: { fillColor: [44,62,80], textColor: 255, fontStyle: 'bold' },
        bodyStyles: { fontSize: 10 },
        margin: {left: 40, right: 40}
    });
    doc.save(filename + ".pdf");
}

function exportCSV() {
    const {headers, data, filename} = getExportData();
    let csvRows = [headers];
    data.forEach(row => {
        csvRows.push(row.map(v => `"${(v+'').replace(/"/g,'""')}"`));
    });
    let csvContent = csvRows.map(e => e.join(",")).join("\n");
    let blob = new Blob([csvContent], { type: 'text/csv' });
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = filename + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function exportExcel() {
    const {headers, data, filename} = getExportData();
    let ws_data = [headers, ...data];
    let ws = XLSX.utils.aoa_to_sheet(ws_data);
    let wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, filename);
    XLSX.writeFile(wb, filename + ".xlsx");
}

// ========== REFRESH AUDIT LOGS ==========
function refreshAuditLogs() {
    const refreshBtn = document.getElementById('refreshBtn');
    const originalHTML = refreshBtn.innerHTML;
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    refreshBtn.disabled = true;
    
    // Store current tab before refresh
    sessionStorage.setItem('currentTab', 'audit');
    
    // Reload the page - it will stay on audit tab due to sessionStorage
    setTimeout(() => {
        window.location.reload();
    }, 500);
}

// ========== INITIALIZATION ==========
document.addEventListener('DOMContentLoaded', function() {
    // Restore active tab from session storage
    const savedTab = sessionStorage.getItem('currentTab');
    if (savedTab && savedTab !== 'users') {
        switchTab(savedTab);
    }
    
    // Initialize pagination
    updatePagination();
    updateRolesPagination();
    updateAuditPagination();
});

// Auto-refresh for audit logs
setTimeout(function() {
    if (document.getElementById('audit-content').classList.contains('active')) {
        sessionStorage.setItem('currentTab', 'audit');
        window.location.reload();
    }
}, 60000);
</script>

<?php
// Helper function for time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
</body>
</html>