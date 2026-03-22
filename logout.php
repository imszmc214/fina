<?php
session_start();
include 'session_manager.php'; // Include your session manager

// Database connection
include 'connection.php';

// Clear the user's session
if (isset($_SESSION['users_username'])) {
    log_user_out($_SESSION['users_username'], $conn); // Log user out in the database
}

// Clear PIN verification status
unset($_SESSION['pin_verified']);
unset($_SESSION['pin_required']);
unset($_SESSION['redirect_after_pin']);

session_unset(); // Remove all session variables
session_destroy(); // Destroy the session
header("Location: login.php"); // Redirect to login page
exit();
?>