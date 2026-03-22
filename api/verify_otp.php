<?php
session_start();
include('../connection.php');

header('Content-Type: application/json');

if (!isset($_SESSION['users_username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$username = $_SESSION['users_username'];

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['otp_code'])) {
    echo json_encode(['success' => false, 'message' => 'OTP code is required.']);
    exit;
}

$entered_otp = $data['otp_code'];

// Fetch stored OTP
$sql = "SELECT otp_code, otp_expiry FROM userss WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();
$stored_otp = $user['otp_code'];
$otp_expiry = $user['otp_expiry'];

if ($stored_otp === null || $otp_expiry === null) {
    echo json_encode(['success' => false, 'message' => 'No active OTP found. Please resend.']);
    exit;
}

if ($stored_otp === $entered_otp) {
    if (strtotime($otp_expiry) > time()) {
        // Success - clear OTP
        $clear_sql = "UPDATE userss SET otp_code = NULL, otp_expiry = NULL WHERE username = ?";
        $clear_stmt = $conn->prepare($clear_sql);
        $clear_stmt->bind_param("s", $username);
        $clear_stmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'OTP has expired.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect OTP code.']);
}
?>
