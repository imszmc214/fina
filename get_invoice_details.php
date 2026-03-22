<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Database credentials
include('connection.php');

// Fetch invoice details
if (isset($_GET['invoice_id'])) {
    $invoice_id = $_GET['invoice_id'];
    $sql = "SELECT * FROM account_receivable WHERE invoice_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $invoice_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $invoice = $result->fetch_assoc();

    echo json_encode($invoice);
}

$stmt->close();
$conn->close();
?>
