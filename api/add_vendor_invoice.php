<?php
ob_start();
session_start();
header('Content-Type: application/json');

if (!defined('UPLOAD_PATH')) define('UPLOAD_PATH', 'uploads/');
require_once('../connection.php');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_invoice') {
        $response = ['success' => false, 'message' => ''];
        
        // Basic fields
        $invoice_id = $_POST['invoice_id'] ?? '';
        $department = $_POST['department'] ?? '';
        $vendor_type = $_POST['vendor_type'] ?? 'Vendor';
        $vendor_name = $_POST['account_name'] ?? ''; 
        $vendor_address = $_POST['vendor_address'] ?? '';
        $gl_account = $_POST['gl_account'] ?? '';
        $invoice_date = $_POST['invoice_date'] ?? '';
        $expense_categories = $_POST['expense_categories'] ?? '';
        $expense_subcategory = $_POST['expense_subcategory'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        $amount = (float)($_POST['amount'] ?? 0);
        $payment_due = $_POST['payment_due'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Bank details
        $bank_name = $_POST['bank_name'] ?? '';
        $bank_account_name = $_POST['bank_account_name'] ?? '';
        $bank_account_number = $_POST['bank_account_number'] ?? '';
        
        // E-cash details
        $ecash_provider = $_POST['payment_method'] === 'GCash' ? 'GCash' : ($_POST['payment_method'] === 'PayMaya' ? 'PayMaya' : '');
        $ecash_account_name = $_POST['ecash_account_name'] ?? '';
        $ecash_account_number = $_POST['ecash_account_number'] ?? '';
        
        // Handle File Uploads
        $uploadedFiles = [];
        // Ensure we are using the correct path relative to this API file
        // ../uploads/ because we are in api/ folder
        $uploadDir = '../' . UPLOAD_PATH; 
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (isset($_FILES['receipts'])) {
            $files = $_FILES['receipts'];
            if (is_array($files['name'])) {
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === 0) {
                        $fileName = time() . '_' . basename($files['name'][$i]);
                        $targetPath = $uploadDir . $fileName;
                        if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                            $uploadedFiles[] = $fileName;
                        }
                    }
                }
            }
        }
        
        $document_json = json_encode($uploadedFiles);
        
        $insert_sql = "INSERT INTO accounts_payable (
                            invoice_id, department, vendor_type, vendor_name, vendor_address, gl_account, 
                            expense_categories, expense_subcategory,
                            payment_method, amount, description, document, invoice_date, payment_due, 
                            bank_name, bank_account_name, bank_account_number, 
                            ecash_provider, ecash_account_name, ecash_account_number, status, created_at
                       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
                       
        $stmt = $conn->prepare($insert_sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssssssssdssssssssss", 
            $invoice_id, $department, $vendor_type, $vendor_name, $vendor_address, $gl_account, 
            $expense_categories, $expense_subcategory,
            $payment_method, $amount, $description, $document_json, $invoice_date, $payment_due,
            $bank_name, $bank_account_name, $bank_account_number,
            $ecash_provider, $ecash_account_name, $ecash_account_number
        );
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Invoice $invoice_id successfully created.";
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        ob_end_clean();
        echo json_encode($response);
        exit();
    } else {
        throw new Exception("Invalid request method or action");
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
} catch (Error $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => "System error: " . $e->getMessage()]);
    exit();
}
