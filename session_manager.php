<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session only if it's not already started
}

// Include audit logger for tracking user actions
require_once __DIR__ . '/audit_logger.php';

// Function to check if the user is logged in
function is_user_logged_in() {
    global $conn;
    
    // Check multiple possible session variables
    if (!isset($_SESSION['users_username']) && !isset($_SESSION['username'])) {
        return false;
    }
    
    $username = $_SESSION['users_username'] ?? $_SESSION['username'] ?? '';
    
    if (empty($username)) {
        return false;
    }
    
    // First check if account is locked or suspended
    $status_check_sql = "SELECT account_status FROM userss WHERE username=?";
    $status_stmt = $conn->prepare($status_check_sql);
    $status_stmt->bind_param("s", $username);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $user_status = $status_result->fetch_assoc();
        if ($user_status['account_status'] === 'locked' || $user_status['account_status'] === 'suspended') {
            // Log user out if account is locked or suspended
            log_user_out($username, $conn);
            session_unset();
            session_destroy();
            return false;
        }
    }
    $status_stmt->close();
    
    return true;
}

// Function to mark the user as logged in
function log_user_in($username, $conn) {
    // Example: Update the user status in the database (if applicable)
    // You might have a column like 'is_logged_in' or similar in your 'userss' table
    $stmt = $conn->prepare("UPDATE userss SET is_logged_in = 1 WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
    
    // Log the login action to audit logs
    log_login($conn, $username);
}

// Function to log user out
function log_user_out($username, $conn) {
    // Example: Update the user status in the database (if applicable)
    // You might have a column like 'is_logged_in' or similar in your 'userss' table
    $stmt = $conn->prepare("UPDATE userss SET is_logged_in = 0 WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->close();
    
    // Log the logout action to audit logs
    log_logout($conn, $username);
}

// Function to check if user account is locked
function is_account_locked($username, $conn) {
    $lock_check_sql = "SELECT account_status FROM userss WHERE username=?";
    $lock_stmt = $conn->prepare($lock_check_sql);
    $lock_stmt->bind_param("s", $username);
    $lock_stmt->execute();
    $lock_result = $lock_stmt->get_result();
    
    if ($lock_result->num_rows > 0) {
        $user_status = $lock_result->fetch_assoc();
        $lock_stmt->close();
        return $user_status['account_status'] === 'locked';
    }
    
    $lock_stmt->close();
    return false;
}

// Function to check if user account is suspended
function is_account_suspended($username, $conn) {
    $status_check_sql = "SELECT account_status FROM userss WHERE username=?";
    $status_stmt = $conn->prepare($status_check_sql);
    $status_stmt->bind_param("s", $username);
    $status_stmt->execute();
    $status_result = $status_stmt->get_result();
    
    if ($status_result->num_rows > 0) {
        $user_status = $status_result->fetch_assoc();
        $status_stmt->close();
        return $user_status['account_status'] === 'suspended';
    }
    
    $status_stmt->close();
    return false;
}

// Get user role
function get_user_role() {
    $role = $_SESSION['user_role'] ?? '';
    
    // Clean the role name - remove brackets and fix typos
    $cleaned_role = trim($role, "[]");
    $cleaned_role = str_replace('[fnancial admin]', 'financial admin', $cleaned_role);
    $cleaned_role = str_replace('[financial admin]', 'financial admin', $cleaned_role);
    $cleaned_role = str_replace('fnancial admin', 'financial admin', $cleaned_role);
    
    return $cleaned_role;
}

function check_user_permission($requiredPermission) {
    $role = get_user_role();
    $permissions = [
        'financial admin' => [
            'roles' => true, 'add_role' => true, 'edit_role' => true, 'delete_role' => true,
            'audit' => true, 'user_management' => true
        ],
        'budget manager' => [
            'roles' => false, 'add_role' => false, 'edit_role' => false, 'delete_role' => false,
            'audit' => true, 'user_management' => false
        ],
        'auditor' => [
            'roles' => false, 'add_role' => false, 'edit_role' => false, 'delete_role' => false,
            'audit' => true, 'user_management' => false
        ],
        'collector' => [
            'roles' => false, 'add_role' => false, 'edit_role' => false, 'delete_role' => false,
            'audit' => false, 'user_management' => false
        ],
        'disburse officer' => [
            'roles' => false, 'add_role' => false, 'edit_role' => false, 'delete_role' => false,
            'audit' => false, 'user_management' => false
        ]
    ];
    
    $userPermissions = $permissions[$role] ?? [];
    return $userPermissions[$requiredPermission] ?? false;
}
?>