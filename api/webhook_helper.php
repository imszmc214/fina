<?php
/**
 * Webhook Helper
 * Sends status updates to external systems via callback URLs.
 */

function sendWebhookUpdate($conn, $department, $type, $target_id, $status, $reason = '') {
    // 1. Fetch callback URL for this department
    try {
        $stmt = $conn->prepare("SELECT callback_url FROM department_tokens WHERE department = ? AND callback_url IS NOT NULL AND callback_url != '' AND is_active = 1 LIMIT 1");
        if (!$stmt) {
            error_log("Webhook Error: Could not prepare statement. Likely missing 'callback_url' column in department_tokens.");
            return false;
        }
        
        $stmt->bind_param("s", $department);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return false; // No callback URL configured or active
        }
        
        $row = $result->fetch_assoc();
        $callback_url = $row['callback_url'];
        $stmt->close();
    } catch (Exception $e) {
        error_log("Webhook Exception: " . $e->getMessage());
        return false;
    }
    
    // 2. Prepare payload
    $payload = [
        'id' => $target_id,
        'type' => $type,         // 'vendor_invoice' or 'driver_payout'
        'status' => $status,     // 'Approved', 'Rejected', etc.
        'reason' => $reason,     // Rejection reason or notes
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $json_payload = json_encode($payload);
    
    // 3. Send POST request via cURL
    $ch = curl_init($callback_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_payload),
        'X-Source: Fina-Finance-System'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log result for debugging
    error_log("Webhook sent to $callback_url. Status: $status. Res Code: $http_code");
    
    return $http_code >= 200 && $http_code < 300;
}
?>
