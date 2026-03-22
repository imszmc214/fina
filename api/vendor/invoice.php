<?php
/**
 * API Endpoint: Submit Vendor Invoice
 * path: api/vendor/invoice.php
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../auth.php';
$tokenData = authenticateAPI();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

// Support JSON input
$json_input = json_decode(file_get_contents('php://input'), true) ?? [];
$request = array_merge($_POST, $json_input);

// Helper to normalize department names (e.g., 'CORE 1' -> 'Core-1')
function normalizeDepartmentName($dept) {
    if (empty($dept)) return '';
    $dept = trim($dept);
    $mapping = [
        'CORE 1' => 'Core-1',
        'CORE 2' => 'Core-2',
        'HR 1'   => 'Human Resource-1',
        'HR 2'   => 'Human Resource-2',
        'HR 3'   => 'Human Resource-3',
        'HR 4'   => 'Human Resource-4',
        'HUMAN RESOURCE 1' => 'Human Resource-1',
        'HUMAN RESOURCE 2' => 'Human Resource-2',
        'HUMAN RESOURCE 3' => 'Human Resource-3',
        'HUMAN RESOURCE 4' => 'Human Resource-4',
        'LOGISTIC 1' => 'Logistic-1',
        'LOGISTIC 2' => 'Logistic-2',
    ];
    
    $upperDept = strtoupper($dept);
    foreach ($mapping as $alias => $standard) {
        if ($upperDept === strtoupper($alias)) return $standard;
    }
    
    // Fallback search for standard names ignoring case
    $standardNames = ['Administrative', 'Core-1', 'Core-2', 'Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Logistic-1', 'Logistic-2', 'Financials'];
    foreach ($standardNames as $std) {
        if ($upperDept === strtoupper($std)) return $std;
    }
    return $dept;
}

// Basic fields
$invoice_id = $request['invoice_id'] ?? '';
$po_number = $request['po_number'] ?? '';
$department = normalizeDepartmentName($request['department'] ?? $tokenData['department']); // Normalized department
$vendor_type = $request['vendor_type'] ?? 'Vendor';
$vendor_name = $request['vendor_name'] ?? $request['account_name'] ?? ''; // Support both for transition
$vendor_address = $request['vendor_address'] ?? '';
$gl_account = $request['gl_account'] ?? '';
$invoice_date = $request['invoice_date'] ?? date('Y-m-d');
$expense_categories = $request['expense_categories'] ?? '';
$expense_subcategory = $request['expense_subcategory'] ?? '';
$payment_method = $request['payment_method'] ?? 'Cash';
$amount = (float)($request['amount'] ?? 0);
$payment_due = $request['payment_due'] ?? date('Y-m-d', strtotime('+30 days'));
$description = $request['description'] ?? '';

// Auto-generate invoice_id if empty OR if it's been sent with a PO- prefix (to prevent PO overriding the invoice_id)
if (empty($invoice_id) || strpos($invoice_id, 'PO-') === 0) {
    // If it was a PO number in the invoice_id field, and po_number is empty, use it as po_number
    if (strpos($invoice_id, 'PO-') === 0 && empty($po_number)) {
        $po_number = $invoice_id;
    }
    $invoice_id = 'INV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

// Validate required fields (Simplified: only need vendor_name, amount and gl_account keyword)
$required = ['vendor_name', 'amount', 'gl_account'];
foreach ($required as $field) {
    if (empty($$field)) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
        exit;
    }
}

// SMART GL MAPPING: Automatically resolve hierarchy
require_once __DIR__ . '/../../includes/accounting_functions.php';
$resolved_gl = getExpenseGLAccount($conn, $gl_account, $description);

if ($resolved_gl) {
    $hierarchy = resolveGLAccountHierarchy($conn, $resolved_gl['id']);
    
    // Auto-populate based on hierarchy
    $gl_account = $resolved_gl['code'] . ' - ' . $resolved_gl['name'];
    $expense_categories = $hierarchy['level2'] ? $hierarchy['level2']['name'] : $expense_categories;
    $expense_subcategory = $hierarchy['level3'] ? $hierarchy['level3']['name'] : $expense_subcategory;
    // REMOVED: Level 1 (Type) overwrite to vendor_type to avoid ENUM truncation error
} else {
    // Fallback if mapping fails completely
    error_log("API WARNING: Could not resolve GL mapping for: $gl_account");
}

// Bank details
$bank_name = $request['bank_name'] ?? '';
$bank_account_name = $request['bank_account_name'] ?? '';
$bank_account_number = $request['bank_account_number'] ?? '';

// E-cash details
$ecash_provider = ($payment_method === 'GCash' || $payment_method === 'PayMaya') ? $payment_method : '';
$ecash_account_name = $request['ecash_account_name'] ?? '';
$ecash_account_number = $request['ecash_account_number'] ?? '';

// Document Handling (Prefer uploaded files via multipart/form-data, handle Base64 in JSON, fallback to request parameter)
$document_list = [];

$upload_dir = UPLOAD_PATH;

// 1. Handle actual file uploads via multipart/form-data
if (isset($_FILES['document'])) {
    $file_field = $_FILES['document'];
    if (is_array($file_field['name'])) {
        foreach ($file_field['tmp_name'] as $k => $tmp) {
            if ($file_field['error'][$k] === 0) {
                $saved = time() . '_' . basename($file_field['name'][$k]);
                if (move_uploaded_file($tmp, $upload_dir . $saved)) {
                    $document_list[] = $saved;
                }
            }
        }
    } else {
        if ($file_field['error'] === 0) {
            $saved = time() . '_' . basename($file_field['name']);
            if (move_uploaded_file($file_field['tmp_name'], $upload_dir . $saved)) {
                $document_list[] = $saved;
            }
        }
    }
}

// 2. Handle Base64 strings in JSON payload (if no files were uploaded)
if (empty($document_list) && isset($request['document'])) {
    $docs = is_array($request['document']) ? $request['document'] : [$request['document']];
    foreach ($docs as $doc_str) {
        // Check if it's a Base64 string (often starts with data:image/...)
        if (preg_match('/^data:image\/(\w+);base64,/', $doc_str, $matches)) {
            $type = strtolower($matches[1]); // jpg, png, etc
            $data = substr($doc_str, strpos($doc_str, ',') + 1);
            $data = base64_decode($data);
            
            if ($data !== false) {
                $saved = 'api_' . time() . '_' . uniqid() . '.' . $type;
                if (file_put_contents($upload_dir . $saved, $data)) {
                    $document_list[] = $saved;
                }
            }
        } elseif (filter_var($doc_str, FILTER_VALIDATE_URL)) {
            // It's a URL! Try to download it so we have a local copy
            $url_path = parse_url($doc_str, PHP_URL_PATH);
            $type = strtolower(pathinfo($url_path, PATHINFO_EXTENSION));
            if (empty($type)) $type = 'pdf'; // fallback
            
            // Limit download size or timeout, and handle SSL issues often found in local dev
            $ctx = stream_context_create([
                'http' => ['timeout' => 10, 'user_agent' => 'ViaHale-API/1.0'],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
            ]);
            
            $data = @file_get_contents($doc_str, false, $ctx);
            if ($data !== false) {
                $saved = 'api_url_' . time() . '_' . uniqid() . '.' . $type;
                if (file_put_contents($upload_dir . $saved, $data)) {
                    $document_list[] = $saved;
                } else {
                    $document_list[] = $doc_str; // save link if local write fails
                }
            } else {
                $document_list[] = $doc_str; // keep as link if download fails
            }
        } else {
            // Not a URL or Base64, keep it as a filename reference
            $document_list[] = $doc_str;
        }
    }
}

// 2. Fallback to 'document' field in request body (e.g. if sending URLs or filenames directly)
if (empty($document_list) && isset($request['document'])) {
    $document_list = is_array($request['document']) ? $request['document'] : [$request['document']];
}

$document_json = json_encode($document_list);

$insert_sql = "INSERT INTO accounts_payable (
                    invoice_id, po_number, department, vendor_type, vendor_name, vendor_address, gl_account, 
                    expense_categories, expense_subcategory,
                    payment_method, amount, description, document, invoice_date, payment_due, 
                    bank_name, bank_account_name, bank_account_number, 
                    ecash_provider, ecash_account_name, ecash_account_number, status
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
               
$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssssssdssssssssss", 
    $invoice_id, $po_number, $department, $vendor_type, $vendor_name, $vendor_address, $gl_account, 
    $expense_categories, $expense_subcategory,
    $payment_method, $amount, $description, $document_json, $invoice_date, $payment_due,
    $bank_name, $bank_account_name, $bank_account_number,
    $ecash_provider, $ecash_account_name, $ecash_account_number
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => "Invoice $invoice_id submitted successfully.",
        'data' => [
            'invoice_id' => $invoice_id,
            'amount' => $amount,
            'department' => $department,
            'status' => 'pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
