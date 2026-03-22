<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database connection
include('connection.php');

// Confirm invoice (update status)
if (isset($_POST['invoice_id'])) {
    // CORRECTED: invoice_id is a string, not an integer
    $invoice_id = $_POST['invoice_id']; // Remove intval() since invoice_id is a string

    include_once('includes/accounting_functions.php');
    
    $fetch_sql = "SELECT amount, payment_method, driver_name FROM account_receivable WHERE invoice_id = ?";
    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param("s", $invoice_id); 
    $stmt_fetch->execute();
    $stmt_fetch->bind_result($amount, $payment_method, $driver_name);
    $stmt_fetch->fetch();
    $stmt_fetch->close();

    if ($amount > 0) {
        $payment_data = [
            'invoice_id' => $invoice_id,
            'amount' => $amount,
            'payment_method' => $payment_method,
            'driver_name' => $driver_name,
            'department' => 'Operations'
        ];
        
        try {
            createARPaymentJournalEntry($conn, $payment_data);
        } catch (Exception $e) {
            error_log("AR Payment Error for $invoice_id in confirm_invoice.php: " . $e->getMessage());
        }
    }

    $stmt = $conn->prepare("UPDATE account_receivable SET status = 'confirmed', approval_date = NOW() WHERE invoice_id = ?");
    $stmt->bind_param("s", $invoice_id); // Changed "i" to "s"

    if ($stmt->execute()) {
        $_SESSION['success'] = "Invoice ID $invoice_id confirmed successfully.";
    } else {
        $_SESSION['error'] = "Failed to confirm Invoice ID $invoice_id.";
    }

    $stmt->close();
}

$conn->close();

// Redirect back to the page
header("Location: receivables_ia.php?page=iareceivables");
exit();