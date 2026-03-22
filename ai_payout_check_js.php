<?php
/**
 * ============================================================================
 * AI PAYOUT CHECKER - JAVASCRIPT VERSION (NO PYTHON REQUIRED!)
 * ============================================================================
 * LOCATION: DOMAIN (financials.viahale.com)
 * FILE: ai_payout_check_js.php
 * 
 * TECHNOLOGY: Pure JavaScript + TensorFlow.js
 * 
 * ADVANTAGES:
 * ✅ No Python installation needed
 * ✅ No localhost API required
 * ✅ Works directly on domain
 * ✅ Client-side processing
 * ✅ Same AI capabilities
 * 
 * USAGE:
 * This file provides the bridge between PHP and JavaScript AI.
 * The actual AI runs in the browser using TensorFlow.js.
 */

class JavaScriptAIPayoutChecker {
    
    private $log_file = 'ai_payout_logs.txt';
    
    /**
     * Check if AI is available (always true for JS version)
     */
    public function checkHealth() {
        // JavaScript AI is always available if browser supports it
        return true;
    }
    
    /**
     * This method returns a placeholder since actual analysis happens in JavaScript
     * Use this for server-side logging only
     */
    public function analyzePayout($payout_data) {
        // Log the request
        $this->logRequest($payout_data);
        
        // Return a response indicating JS will handle it
        return [
            'status' => 'pending_js_analysis',
            'message' => 'Analysis will be performed by JavaScript AI',
            'payout_id' => $payout_data['payout_id'] ?? 'UNKNOWN',
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get approval requirements based on amount
     */
    public function getRequiredApprovals($amount) {
        if ($amount <= 10000) {
            return ['supervisor'];
        } elseif ($amount <= 50000) {
            return ['supervisor', 'manager'];
        } elseif ($amount <= 100000) {
            return ['supervisor', 'manager', 'finance_head'];
        } else {
            return ['supervisor', 'manager', 'finance_head', 'ceo'];
        }
    }
    
    /**
     * Log request for audit trail
     */
    private function logRequest($payout_data) {
        $log_entry = sprintf(
            "[%s] JS-AI Request: Payout %s | Amount: ₱%s | Payee: %s\n",
            date('Y-m-d H:i:s'),
            $payout_data['payout_id'] ?? 'UNKNOWN',
            number_format($payout_data['amount'] ?? 0, 2),
            $payout_data['payee_id'] ?? 'UNKNOWN'
        );
        
        @file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Log AI result (called from JavaScript via AJAX)
     */
    public function logResult($payout_id, $result) {
        $log_entry = sprintf(
            "[%s] JS-AI Result: %s | Risk: %s (%d/100) | Recommendation: %s\n",
            date('Y-m-d H:i:s'),
            $payout_id,
            $result['risk_level'] ?? 'UNKNOWN',
            $result['risk_score'] ?? 0,
            $result['recommendation'] ?? 'UNKNOWN'
        );
        
        @file_put_contents($this->log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Validate reference document exists in database
     */
    public function validateReference($conn, $reference_type, $reference_id) {
        $errors = [];
        
        switch ($reference_type) {
            case 'Invoice':
                $sql = "SELECT * FROM invoices WHERE invoice_id = ? LIMIT 1";
                break;
            case 'Purchase Order':
                $sql = "SELECT * FROM purchase_orders WHERE po_id = ? LIMIT 1";
                break;
            case 'Payroll':
                $sql = "SELECT * FROM payroll WHERE payroll_id = ? LIMIT 1";
                break;
            case 'Reimbursement':
                $sql = "SELECT * FROM reimbursements WHERE reimbursement_id = ? LIMIT 1";
                break;
            default:
                return ["Unknown reference type: $reference_type"];
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ["Database error: " . $conn->error];
        }
        
        $stmt->bind_param("s", $reference_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $errors[] = "$reference_type $reference_id not found in system";
        } else {
            $record = $result->fetch_assoc();
            
            // Check if already paid
            if (isset($record['payment_status']) && $record['payment_status'] === 'Paid') {
                $errors[] = "$reference_type $reference_id already marked as paid";
            }
        }
        
        $stmt->close();
        return $errors;
    }
}

// ============================================================================
// AJAX HANDLER FOR LOGGING AI RESULTS
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_ai_result') {
    header('Content-Type: application/json');
    
    $payout_id = $_POST['payout_id'] ?? 'UNKNOWN';
    $result = json_decode($_POST['result'] ?? '{}', true);
    
    $checker = new JavaScriptAIPayoutChecker();
    $checker->logResult($payout_id, $result);
    
    echo json_encode(['success' => true, 'message' => 'Result logged']);
    exit();
}

?>
