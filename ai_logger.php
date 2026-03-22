<?php
/**
 * ============================================================================
 * AI LOGGER - SERVER-SIDE LOGGING FOR AUDIT TRAIL
 * ============================================================================
 * FILE: ai_logger.php
 * 
 * FEATURES:
 * - Logs all AI analyses to database
 * - Creates audit trail
 * - Stores error logs
 * - Tracks AI system health
 * ============================================================================
 */

// Database connection
require_once 'connection.php';

// Set JSON header
header('Content-Type: application/json');

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit();
}

// Determine log type
$logType = $data['action'] ?? 'AI_ANALYSIS';

try {
    switch ($logType) {
        case 'AI_ANALYSIS':
            logAIAnalysis($conn, $data);
            break;
            
        case 'TRANSACTION_CONFIRMED':
            logTransactionConfirmation($conn, $data);
            break;
            
        case 'SYSTEM_ERROR':
            logSystemError($conn, $data);
            break;
            
        default:
            logGeneral($conn, $data);
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Log AI analysis to database
 */
function logAIAnalysis($conn, $data) {
    $sql = "INSERT INTO ai_validation_logs (
        payout_id,
        risk_level,
        risk_score,
        issues_detected,
        recommendation,
        checks_performed,
        checked_at,
        ai_version
    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $payout_id = $data['payout_id'] ?? 'UNKNOWN';
    $risk_level = $data['risk_level'] ?? 'UNKNOWN';
    $risk_score = $data['risk_score'] ?? 0;
    $issues = json_encode($data['issues'] ?? []);
    $recommendation = $data['recommendation'] ?? 'UNKNOWN';
    $checks = json_encode($data['checks'] ?? []);
    $ai_version = $data['ai_version'] ?? 'v3.0';
    
    $stmt->bind_param(
        'ssissss',
        $payout_id,
        $risk_level,
        $risk_score,
        $issues,
        $recommendation,
        $checks,
        $ai_version
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Insert failed: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // Also log to text file as backup
    logToFile('AI_ANALYSIS', $data);
}

/**
 * Log transaction confirmation
 */
function logTransactionConfirmation($conn, $data) {
    $sql = "INSERT INTO payout_tracking (
        payout_id,
        action_type,
        risk_level,
        performed_by,
        created_at
    ) VALUES (?, 'CONFIRMED', ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Fallback to file log if table doesn't exist
        logToFile('TRANSACTION_CONFIRMED', $data);
        return;
    }
    
    $payout_id = $data['payout_id'] ?? 'UNKNOWN';
    $risk_level = $data['risk_level'] ?? 'UNKNOWN';
    $user_id = $_SESSION['user_id'] ?? 'SYSTEM';
    
    $stmt->bind_param('sss', $payout_id, $risk_level, $user_id);
    $stmt->execute();
    $stmt->close();
    
    logToFile('TRANSACTION_CONFIRMED', $data);
}

/**
 * Log system errors
 */
function logSystemError($conn, $data) {
    $sql = "INSERT INTO error_detection_logs (
        error_type,
        error_message,
        payout_id,
        detected_at
    ) VALUES (?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        logToFile('ERROR', $data);
        return;
    }
    
    $error_type = $data['error_type'] ?? 'UNKNOWN';
    $error_message = $data['error_message'] ?? 'No message';
    $payout_id = $data['payout_id'] ?? null;
    
    $stmt->bind_param('sss', $error_type, $error_message, $payout_id);
    $stmt->execute();
    $stmt->close();
    
    logToFile('ERROR', $data);
}

/**
 * General logging
 */
function logGeneral($conn, $data) {
    logToFile('GENERAL', $data);
}

/**
 * Log to text file as backup
 */
function logToFile($type, $data) {
    $logFile = 'ai_logs_' . date('Y-m') . '.txt';
    
    $logEntry = sprintf(
        "[%s] %s: %s\n",
        date('Y-m-d H:i:s'),
        $type,
        json_encode($data, JSON_UNESCAPED_SLASHES)
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Health check endpoint
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['health'])) {
    echo json_encode([
        'status' => 'online',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => 'v3.0'
    ]);
    exit();
}
?>
