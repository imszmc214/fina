<?php
/**
 * Audit Logger
 * 
 * This file contains functions for logging user actions to the audit_logs table.
 * Use this to track all important user activities in the system.
 */

/**
 * Get next available ID for a table (workaround for missing auto-increment)
 * 
 * @param mysqli $conn Database connection
 * @param string $table Table name
 * @param string $id_col ID column name (default 'id')
 * @return int Next available ID
 */
function getNextAvailableId($conn, $table, $id_col = 'id') {
    $sql = "SELECT MAX(`$id_col`) as max_id FROM `$table` ";
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return intval($row['max_id'] ?? 0) + 1;
    }
    return 1;
}

/**
 * Log an action to the audit_logs table
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user performing the action
 * @param string $action Description of the action performed
 * @return bool True if successful, false otherwise
 */
function log_audit($conn, $username, $action) {
    try {
        // Safe ID Workaround
        $next_id = getNextAvailableId($conn, 'audit_logs');
        
        $stmt = $conn->prepare("INSERT INTO audit_logs (id, user, action, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            error_log("Audit log prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("iss", $next_id, $username, $action);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log a user login action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user logging in
 */
function log_login($conn, $username) {
    log_audit($conn, $username, "Logged into system");
}

/**
 * Log a user logout action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user logging out
 */
function log_logout($conn, $username) {
    log_audit($conn, $username, "Logged out from system");
}

/**
 * Log a role assignment action
 * 
 * @param mysqli $conn Database connection
 * @param string $admin_username Username of the admin performing the action
 * @param string $target_username Username of the user being assigned a role
 * @param string $role Role being assigned
 */
function log_role_assignment($conn, $admin_username, $target_username, $role) {
    $action = "Assigned role '$role' to user '$target_username'";
    log_audit($conn, $admin_username, $action);
}

/**
 * Log an account status change
 * 
 * @param mysqli $conn Database connection
 * @param string $admin_username Username of the admin performing the action
 * @param string $target_username Username of the user whose status is being changed
 * @param string $status_action Action performed (suspend, unsuspend, unlock, etc.)
 */
function log_status_change($conn, $admin_username, $target_username, $status_action) {
    $action_map = [
        'suspend' => "Suspended user account '$target_username'",
        'unsuspend' => "Unsuspended user account '$target_username'",
        'unlock' => "Unlocked user account '$target_username'",
        'lock' => "Locked user account '$target_username'",
        'activate' => "Activated user account '$target_username'",
        'deactivate' => "Deactivated user account '$target_username'"
    ];
    
    $action = $action_map[$status_action] ?? "Updated status of user '$target_username' to '$status_action'";
    log_audit($conn, $admin_username, $action);
}

/**
 * Log a role management action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the admin performing the action
 * @param string $role_action Action performed (add, edit, delete)
 * @param string $role_name Name of the role
 */
function log_role_management($conn, $username, $role_action, $role_name) {
    $action_map = [
        'add' => "Created new role '$role_name'",
        'edit' => "Updated role settings '$role_name'",
        'delete' => "Deleted role '$role_name'"
    ];
    
    $action = $action_map[$role_action] ?? "Performed '$role_action' on role '$role_name'";
    log_audit($conn, $username, $action);
}



/**
 * Log a data export action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user exporting data
 * @param string $export_type Type of export (PDF, CSV, Excel)
 * @param string $data_type Type of data being exported
 */
function log_export($conn, $username, $export_type, $data_type) {
    $action = "Exported $data_type as $export_type";
    log_audit($conn, $username, $action);
}

/**
 * Log a generic action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user performing the action
 * @param string $module Module where the action occurred
 * @param string $action_type Type of action (create, update, delete, view)
 * @param string $details Additional details about the action
 */
function log_action($conn, $username, $module, $action_type, $details = '') {
    $action = ucfirst($action_type) . " in $module";
    if (!empty($details)) {
        $action .= ": $details";
    }
    log_audit($conn, $username, $action);
}
/**
 * Log a page view action
 * 
 * @param mysqli $conn Database connection
 * @param string $username Username of the user viewing the page
 * @param string $page_name Name of the page being viewed
 */
function log_page_view($conn, $username, $page_name) {
    $action = "Viewed $page_name";
    log_audit($conn, $username, $action);
}
?>
