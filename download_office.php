<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['file'])) {
    die('No file specified.');
}

$file = urldecode($_GET['file']);
$file = basename($file); // Prevent directory traversal

// Try different paths to find the file
$possiblePaths = [
    'uploads/' . $file,
    './uploads/' . $file,
    '../uploads/' . $file,
    '../../uploads/' . $file,
    $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $file,
    dirname(__FILE__) . '/uploads/' . $file
];

$filePath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $filePath = $path;
        break;
    }
}

if (!$filePath) {
    die("File not found: " . htmlspecialchars($file));
}

// Define allowed extensions
$allowedExtensions = ['doc', 'docx', 'xls', 'xlsx', 'pdf', 'jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    die("File type not allowed.");
}

// Get file size and MIME type
$fileSize = filesize($filePath);
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $filePath);
finfo_close($finfo);

// Force download
header("Content-Type: $mimeType");
header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
header("Content-Length: " . $fileSize);
readfile($filePath);
exit;
?>