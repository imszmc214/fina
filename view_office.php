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

// Define allowed Office extensions
$allowedExtensions = ['doc', 'docx', 'xls', 'xlsx'];
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    die("File type not allowed. Only Office files (doc, docx, xls, xlsx) are allowed.");
}

// Create a public URL for the file (this assumes your uploads folder is accessible via web)
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$scriptPath = dirname($_SERVER['PHP_SELF']);
$fileUrl = $baseUrl . $scriptPath . '/uploads/' . urlencode($file);

// Use Google Docs Viewer or Microsoft Office Online Viewer
$googleDocsUrl = "https://docs.google.com/viewer?url=" . urlencode($fileUrl) . "&embedded=true";
$msOfficeUrl = "https://view.officeapps.live.com/op/embed.aspx?src=" . urlencode($fileUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office File Viewer - <?php echo htmlspecialchars($file); ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .viewer-container {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .viewer-header {
            background: #2d3748;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .viewer-content {
            flex: 1;
            overflow: hidden;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .btn {
            padding: 0.5rem 1rem;
            background: #4a5568;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .btn:hover {
            background: #2d3748;
        }
        .btn-download {
            background: #3182ce;
        }
        .btn-download:hover {
            background: #2c5282;
        }
        .viewer-options {
            background: #e2e8f0;
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        .viewer-tab {
            padding: 0.5rem 1rem;
            background: #cbd5e0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .viewer-tab.active {
            background: #4299e1;
            color: white;
        }
    </style>
</head>
<body>
    <div class="viewer-container">
        <div class="viewer-header">
            <div>
                <h3>Office File Viewer</h3>
                <p><?php echo htmlspecialchars($file); ?></p>
            </div>
            <div>
                <button class="btn" onclick="window.history.back()">Back</button>
                <a href="download_office.php?file=<?php echo urlencode($file); ?>" class="btn btn-download" download>Download</a>
            </div>
        </div>
        
        <div class="viewer-options">
            <button class="viewer-tab active" onclick="switchViewer('google')">Google Docs Viewer</button>
            <button class="viewer-tab" onclick="switchViewer('msoffice')">Microsoft Office Online</button>
        </div>
        
        <div class="viewer-content">
            <iframe id="googleViewer" src="<?php echo $googleDocsUrl; ?>"></iframe>
            <iframe id="msOfficeViewer" src="<?php echo $msOfficeUrl; ?>" style="display:none;"></iframe>
        </div>
    </div>

    <script>
        function switchViewer(type) {
            const googleTab = document.querySelector('.viewer-tab:nth-child(1)');
            const msTab = document.querySelector('.viewer-tab:nth-child(2)');
            const googleViewer = document.getElementById('googleViewer');
            const msViewer = document.getElementById('msOfficeViewer');
            
            if (type === 'google') {
                googleTab.classList.add('active');
                msTab.classList.remove('active');
                googleViewer.style.display = 'block';
                msViewer.style.display = 'none';
            } else {
                googleTab.classList.remove('active');
                msTab.classList.add('active');
                googleViewer.style.display = 'none';
                msViewer.style.display = 'block';
            }
        }
    </script>
</body>
</html>