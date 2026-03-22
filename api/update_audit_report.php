<?php
session_start();
include('../connection.php');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = $_POST['report_id'];
    $report_title = $_POST['report_title'];
    $audit_team = $_POST['audit_team'];
    $site_section = $_POST['site_section'];
    
    // Update findings with posted data
    $updated_findings = [];
    if (isset($_POST['findings'])) {
        foreach ($_POST['findings'] as $index => $finding_data) {
            $updated_finding = [
                'element' => $finding_data['element'] ?? '',
                'compliance' => $finding_data['compliance'] ?? '',
                'corrective_action' => $finding_data['corrective_action'] ?? '',
                'status' => $finding_data['status'] ?? 'pending'
            ];
            $updated_findings[] = $updated_finding;
        }
    }
    
    // Update the report
    $update_sql = "UPDATE audit_reports SET report_title = ?, audit_team = ?, site_section = ?, audit_findings = ?, updated_date = NOW() WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $updated_findings_json = json_encode($updated_findings);
    $stmt->bind_param("ssssi", $report_title, $audit_team, $site_section, $updated_findings_json, $report_id);
    
    if ($stmt->execute()) {
        // Fetch updated data to return to UI
        $fetch_sql = "SELECT * FROM audit_reports WHERE id = ?";
        $fstmt = $conn->prepare($fetch_sql);
        $fstmt->bind_param("i", $report_id);
        $fstmt->execute();
        $updated_data = $fstmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Report updated successfully',
            'data' => $updated_data
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
