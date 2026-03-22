<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php");
  exit();
}

// DAGDAG: Include ang session_manager.php
include 'session_manager.php';

include 'connection.php';

// Get current user info from session
$current_user_role = get_user_role();
$current_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown User';

// User permissions for audit tab
$permissions = [
    'financial admin' => ['audit' => true],
    'budget manager' => ['audit' => true],
    'auditor' => ['audit' => true],
    'collector' => ['audit' => true],
    'disburse officer' => ['audit' => true]
];

$userPermissions = $permissions[$current_user_role] ?? ['audit' => false];

// Fetch audit logs
$auditLogs = [];
$res = $conn->query("SELECT * FROM audit_logs ORDER BY created_at DESC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $auditLogs[] = $row;
    }
    $res->free();
}

$conn->close();
?>
<html>
<head>
    <meta charset="UTF-8">
    <title>Audit Logs - User Management</title>
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.0/jspdf.plugin.autotable.min.js"></script>
    <style>
        .statement-tab.active {
            border-bottom: 4px solid #f59e0b;
            color: #d97706;
            font-weight: 600;
        }
        .hover-scale:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease-in-out;
        }
        .user-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .filter-controls {
            flex-direction: column;
            align-items: flex-start;
        }
        .search-date-group {
            margin-top: 10px;
        }
    </style>
</head>
<body class="bg-white">
    <?php include('sidebar.php'); ?>
<div class="overflow-y-auto h-full px-6">
    <!-- Breadcrumb -->
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl font-bold text-gray-800">Audit Logs</h1>
        <div class="text-sm text-gray-600">
            <a href="dashboard.php?page=dashboard" class="text-gray-600 hover:text-blue-600 transition duration-200">Home</a>
            /
            <span class="text-gray-400">User Management</span>
            /
            <a href="audit_logs.php" class="text-blue-600 hover:text-blue-700 transition duration-200">Audit Logs</a>
        </div>
    </div>

    <!-- Main content area -->
    <div class="flex-1 bg-white p-6 h-full w-full">
        <!-- Header with Tabs and Actions -->
        <div class="flex justify-between items-center w-full mb-4 gap-4">
            <!-- Left: Tabs -->
            <div class="flex items-center gap-2 font-poppins text-m font-medium border-b border-gray-300">
                <a href="user_management.php?tab=users" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300">USERS</a>
                <a href="role_management.php" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300">ROLES</a>
                <a href="audit_logs.php" class="statement-tab active px-4 py-2 rounded-t-full">AUDIT LOGS</a>
            </div>

            <!-- Right: Search Bar, Export Buttons, and Refresh -->
            <div class="flex items-center gap-4">
                <input
                    type="text"
                    id="searchInput"
                    class="border px-4 py-2 rounded-full text-sm font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-200"
                    placeholder="Search Here..."
                    onkeyup="filterTable()" />
                
                <button onclick="exportPDF()" class="bg-red-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-red-700" title="Export PDF">
                    <i class="fas fa-file-pdf"></i>
                </button>
                <button onclick="exportCSV()" class="bg-green-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700" title="Export CSV">
                    <i class="fas fa-file-csv"></i>
                </button>
                <button onclick="exportExcel()" class="bg-blue-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700" title="Export Excel">
                    <i class="fas fa-file-excel"></i>
                </button>

                <div class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                    <span class="font-semibold">Total Records:</span> 
                    <span class="text-purple-600 font-bold"><?php echo count($auditLogs); ?></span>
                </div>
                <button onclick="window.location.reload()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Summary Cards - Inilagay sa itaas ng table -->
        <?php if (!empty($auditLogs)): ?>
            <div class="mt-4 mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Actions Card -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-90 mb-1">Total Actions</p>
                            <p class="text-2xl font-bold"><?php echo count($auditLogs); ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-full">
                            <i class="fas fa-chart-bar text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-xs opacity-90">
                        <i class="fas fa-clock mr-1"></i>
                        <span>All time activities</span>
                    </div>
                </div>

                <!-- Today's Activities Card -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-90 mb-1">Today's Activities</p>
                            <p class="text-2xl font-bold">
                                <?php
                                $today = date('Y-m-d');
                                $todayActivities = array_filter($auditLogs, function($log) use ($today) {
                                    return date('Y-m-d', strtotime($log['created_at'])) === $today;
                                });
                                echo count($todayActivities);
                                ?>
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-full">
                            <i class="fas fa-calendar-day text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-xs opacity-90">
                        <i class="fas fa-calendar mr-1"></i>
                        <span><?php echo date('M j, Y'); ?></span>
                    </div>
                </div>

                <!-- Unique Users Card -->
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-90 mb-1">Unique Users</p>
                            <p class="text-2xl font-bold">
                                <?php
                                $uniqueUsers = array_unique(array_column($auditLogs, 'user'));
                                echo count($uniqueUsers);
                                ?>
                            </p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-full">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-xs opacity-90">
                        <i class="fas fa-user-check mr-1"></i>
                        <span>Active users in system</span>
                    </div>
                </div>

                <!-- Latest Activity Card -->
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-xl shadow-lg transform hover:scale-105 transition duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium opacity-90 mb-1">Latest Activity</p>
                            <p class="text-lg font-bold">
                                <?php 
                                $latest = $auditLogs[0]['created_at'];
                                $date = new DateTime($latest);
                                echo $date->format('g:i A');
                                ?>
                            </p>
                            <p class="text-xs opacity-90 mt-1"><?php echo $date->format('M j, Y'); ?></p>
                        </div>
                        <div class="bg-white bg-opacity-20 p-2 rounded-full">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center text-xs opacity-90">
                        <i class="fas fa-user mr-1"></i>
                        <span><?php echo htmlspecialchars($auditLogs[0]['user']); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Audit Logs Table -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
            <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                <i class="fas fa-clipboard-list text-xl"></i>
                <h2 class="text-2xl font-poppins text-black">Audit Logs</h2>
            </div>
            <p class="text-sm text-gray-600 mt-1 mb-6 ml-6">Monitor system activities and user actions</p>

            <div class="overflow-x-auto w-full transition-all duration-500">
                <table class="w-full table-auto bg-white">
                    <thead>
                        <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">User</th>
                            <th class="px-4 py-2">Action</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Time</th>
                        </tr>
                    </thead>
                    <tbody id="auditTable" class="text-gray-900 text-sm font-light">
                        <?php if (!empty($auditLogs)): ?>
                            <?php foreach ($auditLogs as $index => $audit_row): ?>
                                <tr class="border-b hover:bg-gray-50 transition-all duration-300 hover-scale">
                                    <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">
                                        #<?php echo htmlspecialchars($audit_row['id']); ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-2 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                            <i class="fas fa-user mr-2"></i>
                                            <?php echo htmlspecialchars($audit_row['user']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-center">
                                            <?php 
                                            $action = strtolower($audit_row['action']);
                                            if (strpos($action, 'login') !== false) {
                                                echo '<div class="flex items-center text-green-600"><i class="fas fa-sign-in-alt mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } elseif (strpos($action, 'logout') !== false) {
                                                echo '<div class="flex items-center text-red-600"><i class="fas fa-sign-out-alt mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } elseif (strpos($action, 'view') !== false) {
                                                echo '<div class="flex items-center text-blue-600"><i class="fas fa-eye mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
                                                echo '<div class="flex items-center text-yellow-600"><i class="fas fa-edit mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) {
                                                echo '<div class="flex items-center text-green-600"><i class="fas fa-plus-circle mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) {
                                                echo '<div class="flex items-center text-red-600"><i class="fas fa-trash mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            } else {
                                                echo '<div class="flex items-center text-gray-600"><i class="fas fa-info-circle mr-3"></i><span>' . htmlspecialchars($audit_row['action']) . '</span></div>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-600 font-medium">
                                        <?php 
                                        $date = new DateTime($audit_row['created_at']);
                                        echo $date->format('M j, Y');
                                        ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-500">
                                        <?php 
                                        echo $date->format('g:i A');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-clipboard-list text-6xl mb-4 text-gray-300"></i>
                                        <p class="text-xl font-medium mb-2 text-gray-400">No Audit Logs Found</p>
                                        <p class="text-sm text-gray-500 max-w-md text-center">Audit logs will appear here as users perform actions in the system. This could include logins, data modifications, and other important activities.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
            <div class="mt-6 flex justify-between items-center">
                <div id="pageStatus" class="text-gray-700 font-bold"></div>
                <div class="flex gap-2">
                    <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="prevPage()">Previous</button>
                    <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="nextPage()">Next</button>
                </div>
            </div>

        <!-- Activity Statistics - Nasa ibaba pa rin ng table -->
        <?php if (!empty($auditLogs)): ?>
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Activity Types -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-purple-600"></i>
                        Activity Types
                    </h3>
                    <div class="space-y-3">
                        <?php
                        $activityTypes = [];
                        foreach ($auditLogs as $log) {
                            $action = strtolower($log['action']);
                            $type = 'Other';
                            
                            if (strpos($action, 'login') !== false) $type = 'Login';
                            elseif (strpos($action, 'logout') !== false) $type = 'Logout';
                            elseif (strpos($action, 'view') !== false) $type = 'View';
                            elseif (strpos($action, 'create') !== false || strpos($action, 'add') !== false) $type = 'Create';
                            elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) $type = 'Update';
                            elseif (strpos($action, 'delete') !== false || strpos($action, 'remove') !== false) $type = 'Delete';
                            
                            $activityTypes[$type] = ($activityTypes[$type] ?? 0) + 1;
                        }
                        
                        $typeColors = [
                            'Login' => 'bg-green-100 text-green-800',
                            'Logout' => 'bg-red-100 text-red-800',
                            'View' => 'bg-blue-100 text-blue-800',
                            'Create' => 'bg-emerald-100 text-emerald-800',
                            'Update' => 'bg-yellow-100 text-yellow-800',
                            'Delete' => 'bg-red-100 text-red-800',
                            'Other' => 'bg-gray-100 text-gray-800'
                        ];
                        
                        foreach ($activityTypes as $type => $count):
                            $percentage = round(($count / count($auditLogs)) * 100, 1);
                        ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $typeColors[$type] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $type; ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-600 w-12 text-right">
                                    <?php echo $percentage; ?>%
                                </span>
                                <span class="text-sm font-bold text-gray-800 w-8 text-right">
                                    <?php echo $count; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-clock mr-2 text-purple-600"></i>
                        Recent Users
                    </h3>
                    <div class="space-y-3">
                        <?php
                        $recentUsers = [];
                        $userLastActivity = [];
                        
                        foreach ($auditLogs as $log) {
                            $userLastActivity[$log['user']] = $log['created_at'];
                        }
                        
                        // Sort by most recent activity
                        uasort($userLastActivity, function($a, $b) {
                            return strtotime($b) - strtotime($a);
                        });
                        
                        $recentUsers = array_slice($userLastActivity, 0, 5, true);
                        
                        foreach ($recentUsers as $user => $lastActivity):
                            $date = new DateTime($lastActivity);
                            $timeAgo = time_elapsed_string($lastActivity);
                        ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-purple-100 text-purple-600 p-2 rounded-full mr-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($user); ?></p>
                                    <p class="text-xs text-gray-500">Last active: <?php echo $timeAgo; ?></p>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">
                                <?php echo $date->format('M j, g:i A'); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Helper function for time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<script>
/* ========== Table pagination ========== */
let currentPage = 1;
const rowsPerPage = 10;
const tableBody = document.getElementById('auditTable');
const pageStatus = document.getElementById('pageStatus');
const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');

function updatePagination() {
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    // show/hide rows
    rows.forEach((row, i) => {
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // page status
    pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;

    // button states
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    
    // Visual feedback for disabled buttons
    if (prevBtn.disabled) {
        prevBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        prevBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
    
    if (nextBtn.disabled) {
        nextBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        nextBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        updatePagination();
    }
}

function nextPage() {
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        updatePagination();
    }
}

// Initialize pagination
updatePagination();

/* ========== Search filter ========== */
function filterTable() {
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const rows = tableBody.querySelectorAll('tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(searchText)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Reset pagination
    currentPage = 1;
    updatePagination();
}

/* ========== Export functions ========== */
function getExportData() {
    const headers = ["ID", "User", "Action", "Date", "Time"];
    const data = [];
    
    <?php foreach ($auditLogs as $audit_row): ?>
        <?php 
        $date = new DateTime($audit_row['created_at']);
        $formattedDate = $date->format('M j, Y');
        $formattedTime = $date->format('g:i A');
        ?>
        data.push([
            "<?php echo $audit_row['id']; ?>",
            "<?php echo htmlspecialchars($audit_row['user']); ?>",
            "<?php echo htmlspecialchars($audit_row['action']); ?>",
            "<?php echo $formattedDate; ?>",
            "<?php echo $formattedTime; ?>"
        ]);
    <?php endforeach; ?>
    
    return {headers, data};
}

function exportPDF() {
    const {headers, data} = getExportData();
    const doc = new window.jspdf.jsPDF('p', 'pt', 'a4');
    doc.setFontSize(15);
    doc.text("Audit Logs Report", 40, 40);
    doc.autoTable({
        head: [headers],
        body: data,
        startY: 60,
        theme: 'striped',
        headStyles: { fillColor: [44,62,80], textColor: 255, fontStyle: 'bold' },
        bodyStyles: { fontSize: 10 },
        margin: {left: 40, right: 40}
    });
    doc.save("audit-logs-report.pdf");
}

function exportCSV() {
    const {headers, data} = getExportData();
    let csvRows = [headers];
    data.forEach(row => {
        csvRows.push(row.map(v => `"${(v+'').replace(/"/g,'""')}"`));
    });
    let csvContent = csvRows.map(e => e.join(",")).join("\n");
    let blob = new Blob([csvContent], { type: 'text/csv' });
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'audit-logs-report.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function exportExcel() {
    const {headers, data} = getExportData();
    let ws_data = [headers, ...data];
    let ws = XLSX.utils.aoa_to_sheet(ws_data);
    let wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Audit Logs");
    XLSX.writeFile(wb, "audit-logs-report.xlsx");
}

// Auto-refresh the page every 60 seconds to get latest audit logs
setTimeout(function() {
    window.location.reload();
}, 60000);

// Add row click effect
document.addEventListener('DOMContentLoaded', function() {
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function() {
            this.classList.toggle('bg-blue-50');
            this.classList.toggle('border-l-4');
            this.classList.toggle('border-blue-500');
        });
    });
});

// Add hover effects for summary cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.bg-gradient-to-r');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
});
</script>
</body>
</html>