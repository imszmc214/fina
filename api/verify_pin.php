<?php
session_start();
include('../connection.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Get the PIN from the request
$data = json_decode(file_get_contents('php://input'), true);
$entered_pin = $data['pin'] ?? '';

if (strlen($entered_pin) !== 6) {
    echo json_encode(['success' => false, 'message' => 'Invalid PIN format.']);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User session expired.']);
    exit();
}

// Fetch the user's PIN from the database
$sql = "SELECT pin FROM userss WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $db_pin = $user['pin'];

    // Use hash_equals for timing-safe comparison
    if (hash_equals($db_pin, $entered_pin)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect PIN.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
}

$stmt->close();
$conn->close();
?>
