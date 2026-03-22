<?php
/**
 * API Endpoint: Submit Driver Payout
 * path: api/driver/payout.php
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

// Fields from driver_payable.php
$payout_id = $request['payout_id'] ?? '';
$driver_id = $request['driver_id'] ?? '';
$driver_name = $request['driver_name'] ?? '';
$wallet_id = $request['wallet_id'] ?? '';
$amount = (float)($request['amount'] ?? 0);
$gl_account = $request['gl_account'] ?? '';
$description = $request['description'] ?? '';

// Auto-generate payout_id if empty
if (empty($payout_id)) {
    $payout_id = 'DW-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
}

// Validate required fields
$required = ['driver_name', 'amount'];
foreach ($required as $field) {
    if (empty($$field)) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required."]);
        exit;
    }
}

$insert_sql = "INSERT INTO driver_payouts (
                    payout_id, department, driver_id, driver_name, wallet_id, amount, gl_account, description, status
               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')";
               
$stmt = $conn->prepare($insert_sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("sssssdss", 
    $payout_id, $tokenData['department'], $driver_id, $driver_name, $wallet_id, $amount, $gl_account, $description
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => "Payout $payout_id submitted successfully.",
        'data' => [
            'payout_id' => $payout_id,
            'driver_name' => $driver_name,
            'amount' => $amount,
            'status' => 'Pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
