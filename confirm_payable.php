<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include('connection.php');

if (isset($_POST['invoice_id'])) {
    $invoice_id = trim($_POST['invoice_id']); // Use as string, trim whitespace

    // First check if the invoice exists
    $check_sql = "SELECT invoice_id FROM accounts_payable WHERE invoice_id = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("s", $invoice_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows === 0) {
        $_SESSION['error'] = "Invoice ID {$invoice_id} not found.";
        $stmt_check->close();
        $conn->close();
        header("Location: vendor.php");
        exit();
    }
    $stmt_check->close();

    include_once('includes/accounting_functions.php');
    
    // Fetch all details needed for createVendorInvoiceJournalEntry
    $get_sql = "SELECT * FROM accounts_payable WHERE invoice_id = ?";
    $get_stmt = $conn->prepare($get_sql);
    $get_stmt->bind_param("s", $invoice_id);
    $get_stmt->execute();
    $invoice = $get_stmt->get_result()->fetch_assoc();
    $get_stmt->close();

    if ($invoice && $invoice['amount'] > 0) {
        try {
            // Create journal entry and post to ledger using standardized function
            createVendorInvoiceJournalEntry($conn, $invoice);
            error_log("Journal entry created for payable $invoice_id via confirm_payable.php");
        } catch (Exception $e) {
            error_log("Payable confirmation error for $invoice_id: " . $e->getMessage());
        }
    }

    // Only update status and approval_date
    $stmt = $conn->prepare("UPDATE accounts_payable SET status = 'approved', approval_date = NOW() WHERE invoice_id = ?");
    $stmt->bind_param("s", $invoice_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Invoice ID {$invoice_id} approved successfully.";
    } else {
        $_SESSION['error'] = "Failed to confirm Invoice ID {$invoice_id}.";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "No invoice ID provided.";
}

$conn->close();
header("Location: vendor.php");
exit();