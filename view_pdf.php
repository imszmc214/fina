<?php
/**
 * view_pdf.php - Inline document viewer
 * Supports: PDF (inline), JPG/PNG (inline), DOC/DOCX/XLS/XLSX (download)
 * Fallback: Fetches from DB BLOB if physical file is missing.
 */
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit('Access denied.');
}

$fileData = null;
$mimeType = null;
$fileNameOnly = 'document';

include('connection.php');

// Priority 1: View by Plan ID (Best for privacy - no filename in URL)
if (isset($_GET['plan_id'])) {
    $plan_id = (int)$_GET['plan_id'];
    $stmt = $conn->prepare("SELECT justification_blob, justification_doc FROM budget_plans WHERE id = ? AND justification_blob IS NOT NULL LIMIT 1");
    $stmt->bind_param("i", $plan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $fileData = $row['justification_blob'];
        // Try to get extension from the stored JSON if it exists, otherwise use 'pdf'
        $ext = 'pdf';
        try {
            $docs = json_parse_safe($row['justification_doc']);
            if (!empty($docs) && is_array($docs)) {
                $ext = pathinfo($docs[0], PATHINFO_EXTENSION) ?: 'pdf';
            }
        } catch(Exception $e) {}
        $fileNameOnly = "Plan_Document_{$plan_id}.{$ext}";
    }
    $stmt->close();
}
// Priority 2: View by Proposal ID (Future proofing)
else if (isset($_GET['proposal_id'])) {
    $proposal_id = (int)$_GET['proposal_id'];
    // Assuming budget_proposals might add blobs later
    $stmt = $conn->prepare("SELECT justification_blob, supporting_docs FROM budget_proposals WHERE id = ? AND justification_blob IS NOT NULL LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $fileData = $row['justification_blob'];
            $fileNameOnly = "Proposal_Document_{$proposal_id}.pdf";
        }
        $stmt->close();
    }
}
// Priority 3: View by Filename (Legacy mode)
else if (isset($_GET['file'])) {
    $requested = urldecode($_GET['file']);
    $requested = preg_replace('/[^a-zA-Z0-9_\-\.\/]/', '', $requested);
    $segments = explode('/', $requested);
    $safe_segments = [];
    foreach ($segments as $seg) {
        if ($seg === '..' || $seg === '.') continue;
        if ($seg !== '') $safe_segments[] = $seg;
    }
    $safe_path = implode('/', $safe_segments);
    $fileNameOnly = basename($safe_path);
    $base_dir = __DIR__ . '/uploads/';
    $filePath = $base_dir . $safe_path;

    if (file_exists($filePath)) {
        $fileData = file_get_contents($filePath);
    } else {
        // Fallback to blob search by filename
        $jsonSearch = '%' . $conn->real_escape_string($fileNameOnly) . '%';
        $stmt = $conn->prepare("SELECT justification_blob FROM budget_plans WHERE justification_doc LIKE ? AND justification_blob IS NOT NULL LIMIT 1");
        $stmt->bind_param("s", $jsonSearch);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $fileData = $row['justification_blob'];
        }
        $stmt->close();
    }
}

if (!$fileData) {
    http_response_code(404);
    exit('Document not found or access denied.');
}

// Detect MIME type from binary data
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_buffer($finfo, $fileData);
finfo_close($finfo);

function json_parse_safe($json) {
    if (empty($json)) return [];
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

// Decide whether to view inline or force download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . $fileNameOnly . '"');
header('Content-Length: ' . strlen($fileData));
header('Cache-Control: private, max-age=3600');
echo $fileData;
exit;

?>