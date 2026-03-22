<?php
/**
 * API Test Script
 * path: api/test_api.php
 */

$baseUrl = 'http://localhost/fina/api';
$token = 'admin123token456'; // Using one of the existing tokens from the dump

function callAPI($endpoint, $data, $token, $isJson = false, $filePath = null) {
    global $baseUrl;
    $ch = curl_init("$baseUrl/$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    if ($isJson) {
        $data['token'] = $token; // Include token in JSON if requested
        $jsonData = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Content-Length: " . strlen($jsonData)
        ]);
    } elseif ($filePath) {
        // Use multipart/form-data for file uploads
        $data['document'] = new CURLFile($filePath);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Token: $token"]);
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["X-API-Token: $token"]);
    }
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'code' => $info['http_code'],
        'body' => json_decode($response, true),
        'raw' => $response
    ];
}

echo "--- API VERIFICATION START ---\n\n";

// 1. Test Vendor Submission
echo "[1] Testing Vendor Invoice Submission...\n";
$vendorData = [
    'po_number' => 'PO-TEST-123',
    'vendor_name' => 'Antigravity Test Vendor',
    'amount' => 5000.50,
    'gl_account' => 'office supplies', // Testing keyword auto-mapping
    'description' => 'Automated test submission for PO reference and API verification.',
    'payment_method' => 'Cash',
    'ecash_account_name' => 'Mary Doe',
    'ecash_account_number' => '09123456789'
];

$res1 = callAPI('vendor/invoice.php', $vendorData, $token);
if ($res1['body'] && $res1['body']['success']) {
    echo "SUCCESS: " . $res1['body']['message'] . " (Invoice ID: " . $res1['body']['data']['invoice_id'] . ")\n";
} else {
    echo "FAILED: " . ($res1['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res1['raw'] . "\n";
}

echo "\n";

// 2. Test Driver Submission
echo "[2] Testing Driver Payout Submission...\n";
$driverData = [
    'driver_id' => 'DRV-999',
    'driver_name' => 'John Test Driver',
    'wallet_id' => 'W-888-777',
    'amount' => 1250.75,
    'description' => 'Weekly payout test via API.',
    'gl_account' => '6002 - Driver Commission'
];

$res2 = callAPI('driver/payout.php', $driverData, $token);
if ($res2['body'] && $res2['body']['success']) {
    echo "SUCCESS: " . $res2['body']['message'] . " (Payout ID: " . $res2['body']['data']['payout_id'] . ")\n";
} else {
    echo "FAILED: " . ($res2['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res2['raw'] . "\n";
}

echo "\n";

// 3. Test Invalid Token
echo "[3] Testing Invalid Token...\n";
$res3 = callAPI('vendor/invoice.php', $vendorData, 'wrong-token');
if (!$res3['body']['success']) {
    echo "SUCCESS: Access correctly denied (" . $res3['body']['message'] . ")\n";
} else {
    echo "FAILED: System accepted an invalid token!\n";
}

// 4. Test JSON Payload
echo "[4] Testing JSON Vendor Submission...\n";
$jsonVendorData = [
    'vendor_name' => 'JSON Test Vendor',
    'amount' => 99.99,
    'gl_account' => 'marketing',
    'description' => 'Testing JSON submission'
];
$res4 = callAPI('vendor/invoice.php', $jsonVendorData, $token, true);
if ($res4['body'] && $res4['body']['success']) {
    echo "SUCCESS: " . $res4['body']['message'] . " (Invoice ID: " . $res4['body']['data']['invoice_id'] . ")\n";
} else {
    echo "FAILED: " . ($res4['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res4['raw'] . "\n";
}

// 5. Test File Upload
echo "[5] Testing Vendor Invoice with File Attachment...\n";
$fileUploadData = [
    'vendor_name' => 'File Upload Test Vendor',
    'amount' => 1500.00,
    'gl_account' => 'Equipment',
    'description' => 'Testing JPG/Image upload via API'
];
$testImagePath = 'c:/xampp/htdocs/fina/test_image.jpg';
$res5 = callAPI('vendor/invoice.php', $fileUploadData, $token, false, $testImagePath);
if ($res5['body'] && $res5['body']['success']) {
    echo "SUCCESS: " . $res5['body']['message'] . " (Invoice ID: " . $res5['body']['data']['invoice_id'] . ")\n";
} else {
    echo "FAILED: " . ($res5['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res5['raw'] . "\n";
}

// 6. Test Base64 Upload
echo "[6] Testing Vendor Invoice with Base64 Image...\n";
$base64Image = 'data:image/jpeg;base64,' . base64_encode('DUMMY JPG CONTENT FOR BASE64');
$base64Data = [
    'vendor_name' => 'Base64 Test Vendor',
    'amount' => 200.00,
    'gl_account' => 'Office Supplies',
    'description' => 'Testing Base64 image upload in JSON',
    'document' => $base64Image
];
$res6 = callAPI('vendor/invoice.php', $base64Data, $token, true);
if ($res6['body'] && $res6['body']['success']) {
    echo "SUCCESS: " . $res6['body']['message'] . " (Invoice ID: " . $res6['body']['data']['invoice_id'] . ")\n";
} else {
    echo "FAILED: " . ($res6['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res6['raw'] . "\n";
}

// 7. Test URL Document (should download or save as link)
echo "[7] Testing Vendor Invoice with URL Document...\n";
$urlData = [
    'vendor_name' => 'URL Test Vendor',
    'amount' => 350.00,
    'gl_account' => 'Subscriptions',
    'description' => 'Testing URL document download/link saving',
    'document' => 'https://viahale.com/logo.png'
];
$res7 = callAPI('vendor/invoice.php', $urlData, $token, true);
if ($res7['body'] && $res7['body']['success']) {
    echo "SUCCESS: " . $res7['body']['message'] . " (Invoice ID: " . $res7['body']['data']['invoice_id'] . ")\n";
} else {
    echo "FAILED: " . ($res7['body']['message'] ?? 'Unknown error') . "\n";
    echo "RAW: " . $res7['raw'] . "\n";
}

// 8. Test PO Number Collision (sending PO- in invoice_id field)
echo "[8] Testing PO Number Collision (forcing new INV- ID)...\n";
$poCollisionData = [
    'vendor_name' => 'Collision Test Vendor',
    'amount' => 500.00,
    'gl_account' => 'Maintenance',
    'description' => 'Testing if sending PO- as invoice_id forces a new INV- identifier',
    'invoice_id' => 'PO-FAKE-12345',
    'po_number' => 'PO-FAKE-12345'
];
$res8 = callAPI('vendor/invoice.php', $poCollisionData, $token, true);
if ($res8['body'] && $res8['body']['success']) {
    $returnedId = $res8['body']['data']['invoice_id'];
    if (strpos($returnedId, 'INV-') === 0) {
        echo "SUCCESS: Correctly generated new ID: $returnedId (Original was PO-FAKE-12345)\n";
    } else {
        echo "FAILED: Still returned the PO number as ID: $returnedId\n";
    }
} else {
    echo "FAILED: " . ($res8['body']['message'] ?? 'Unknown error') . "\n";
}

// 9. Test Department Normalization (sending CORE 1)
echo "[9] Testing Department Normalization ('CORE 1' -> 'Core-1')...\n";
$deptData = [
    'vendor_name' => 'Dept Test Vendor',
    'amount' => 125.00,
    'gl_account' => 'Office',
    'department' => 'CORE 1',
    'description' => 'Testing if CORE 1 is normalized to Core-1'
];
$res9 = callAPI('vendor/invoice.php', $deptData, $token, true);
if ($res9['body'] && $res9['body']['success']) {
    $returnedDept = $res9['body']['data']['department'];
    if ($returnedDept === 'Core-1') {
        echo "SUCCESS: Correctly normalized 'CORE 1' to '$returnedDept'\n";
    } else {
        echo "FAILED: Department remained as '$returnedDept' instead of 'Core-1'\n";
    }
} else {
    echo "FAILED: " . ($res9['body']['message'] ?? 'Unknown error') . "\n";
}

echo "\n--- API VERIFICATION END ---\n";



?>
