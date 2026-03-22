<?php
/**
 * Shared API Authentication Helper
 * Ensures requests have a valid and active department token.
 */

require_once __DIR__ . '/../connection.php';

function authenticateAPI() {
    global $conn;
    
    // Get token from header
    $token = '';
    if (isset($_SERVER['HTTP_X_API_TOKEN'])) {
        $token = $_SERVER['HTTP_X_API_TOKEN'];
    } elseif (isset($_POST['token'])) {
        $token = $_POST['token'];
    } elseif (isset($_GET['token'])) {
        $token = $_GET['token'];
    } else {
        // Check for JSON token
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['token'])) {
            $token = $input['token'];
        }
    }

    if (empty($token)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'API Token is required. Please provide it in the X-API-Token header.']);
        exit;
    }

    // Prepare statement to prevent injection
    $stmt = $conn->prepare("SELECT id, department, is_active, expires_at FROM department_tokens WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid API Token.']);
        exit;
    }

    $tokenData = $result->fetch_assoc();

    // Check if active
    if (!$tokenData['is_active']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'API Token is disabled.']);
        exit;
    }

    // Check expiration
    if (!empty($tokenData['expires_at'])) {
        $expiry = strtotime($tokenData['expires_at']);
        if ($expiry < time()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'API Token has expired.']);
            exit;
        }
    }

    // Update usage stats
    $updateStmt = $conn->prepare("UPDATE department_tokens SET last_used_at = NOW(), usage_count = usage_count + 1 WHERE id = ?");
    $updateStmt->bind_param("i", $tokenData['id']);
    $updateStmt->execute();

    return $tokenData;
}
?>
