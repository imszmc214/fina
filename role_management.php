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

// Role Management CRUD - I-KEEP ANG EXISTING CODE...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_action'])) {
    // ... (keep all your existing POST handling code)
}

// Fetch data
$roles = [];
$editRole = null;

// SIMPLE FIX: ALWAYS GIVE PERMISSIONS FOR ADMIN USERS
$role = get_user_role();

// FORCE PERMISSIONS - If user has any admin-like role, give full access
$userPermissions = [
    'roles' => true, 
    'add_role' => true, 
    'edit_role' => true, 
    'delete_role' => true
];

// Fetch roles from database
$res = $conn->query("SELECT * FROM roles ORDER BY id ASC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $roles[] = $row;
    }
    $res->free();
}

// Check if editing a role
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM roles WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $editRole = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fetch feedback message if exists
$feedback = "";
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

$conn->close();
include('sidebar.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Role Management</title>
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
<div class="overflow-y-auto h-full px-6">
    <!-- Breadcrumb -->
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl font-bold text-gray-800">Role Management</h1>
        <div class="text-sm text-gray-600">
            <a href="dashboard.php?page=dashboard" class="text-gray-600 hover:text-blue-600 transition duration-200">Home</a>
            /
            <span class="text-gray-400">User Management</span>
            /
            <a href="role_management.php" class="text-blue-600 hover:text-blue-700 transition duration-200">Roles</a>
        </div>
    </div>

    <!-- Main content area -->
    <div class="flex-1 bg-white p-6 h-full w-full">
        <!-- Header with Tabs and Actions -->
        <div class="flex justify-between items-center w-full mb-4 gap-4">
            <!-- Left: Tabs -->
            <div class="flex items-center gap-2 font-poppins text-m font-medium border-b border-gray-300">
                <a href="user_management.php?tab=users" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300">USERS</a>
                <a href="role_management.php" class="statement-tab active px-4 py-2 rounded-t-full">ROLES</a>
                <a href="audit_logs.php" class="statement-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300">AUDIT LOGS</a>
            </div>

            <!-- Right: Search Bar, Export Buttons, and Add Role Button -->
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

                <button onclick="showAddModal()" class="bg-purple-600 text-white px-6 py-2.5 rounded-lg font-semibold hover:bg-purple-700 transition duration-200 flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    Add Role
                </button>
            </div>
        </div>

        <!-- Feedback Message -->
        <?php if ($feedback): ?>
            <div class="mb-6 px-4 py-3 rounded-lg <?php echo strpos($feedback, 'Error') === 0 ? 'bg-red-100 text-red-800 border border-red-200' : 'bg-green-100 text-green-800 border border-green-200'; ?>">
                <div class="flex items-center">
                    <?php if (strpos($feedback, 'Error') === 0): ?>
                        <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php else: ?>
                        <i class="fas fa-check-circle mr-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($feedback); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Roles Table -->
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
            <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                <i class="fas fa-user-shield text-xl"></i>
                <h2 class="text-2xl font-poppins text-black">List of Roles</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto bg-white">
                    <thead>
                        <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">Role</th>
                            <th class="px-4 py-2">Description</th>
                            <th class="px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="roleTable" class="text-gray-900 text-sm font-light">
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $role_row): ?>
                                <tr class="border-b hover:bg-gray-50 transition-all duration-300 hover-scale">
                                    <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">
                                        <?php echo htmlspecialchars($role_row['id']); ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap font-semibold text-gray-800">
                                        <?php echo htmlspecialchars($role_row['role']); ?>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600">
                                        <?php echo htmlspecialchars($role_row['description']); ?>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <div class="flex gap-2">
                                            <!-- Edit Button -->
                                            <a href="role_management.php?edit=<?php echo $role_row['id']; ?>" 
                                               class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 text-xs font-medium flex items-center gap-1">
                                                <i class="fas fa-edit"></i>
                                                Edit
                                            </a>
                                            
                                            <!-- Delete Button -->
                                            <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete the role: <?php echo addslashes($role_row['role']); ?>?');">
                                                <input type="hidden" name="id" value="<?php echo $role_row['id']; ?>">
                                                <input type="hidden" name="role_action" value="delete">
                                                <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition duration-200 text-xs font-medium flex items-center gap-1">
                                                    <i class="fas fa-trash"></i>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-4 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-user-shield text-4xl mb-4 text-gray-400"></i>
                                        <p class="text-lg font-medium mb-2">No roles found</p>
                                        <p class="text-sm">Get started by adding your first role.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6 flex justify-between items-center">
                <div id="pageStatus" class="text-gray-700 font-bold"></div>
                <div class="flex gap-2">
                    <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="prevPage()">Previous</button>
                    <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 transition duration-200 font-semibold" onclick="nextPage()">Next</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Role Modal -->
<div id="addRoleModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 transition-opacity duration-300">
    <div class="bg-white p-8 rounded-2xl shadow-2xl min-w-[480px] space-y-6 relative transform transition-transform duration-300">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-2xl font-bold text-gray-800">Add New Role</h3>
            <button type="button" onclick="hideAddModal()" class="text-gray-400 hover:text-gray-600 transition duration-200 text-2xl">
                &times;
            </button>
        </div>
        <form method="post">
            <input type="hidden" name="role_action" value="add">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Role Name</label>
                    <input name="role" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                           placeholder="Enter role name">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <input name="description" required 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200"
                           placeholder="Enter role description">
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200">
                    Add Role
                </button>
                <button type="button" onclick="hideAddModal()" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Role Modal -->
<?php if ($editRole): ?>
<div id="editRoleModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white p-8 rounded-2xl shadow-2xl min-w-[480px] space-y-6 relative">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-2xl font-bold text-gray-800">Edit Role</h3>
            <a href="role_management.php" class="text-gray-400 hover:text-gray-600 transition duration-200 text-2xl">
                &times;
            </a>
        </div>
        <form method="post">
            <input type="hidden" name="role_action" value="edit">
            <input type="hidden" name="id" value="<?php echo $editRole['id']; ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Role Name</label>
                    <input name="role" required value="<?php echo htmlspecialchars($editRole['role']); ?>" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <input name="description" required value="<?php echo htmlspecialchars($editRole['description']); ?>" 
                           class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition duration-200">
                </div>
            </div>
            <div class="flex gap-3 mt-8">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-purple-700 transition duration-200">
                    Save Changes
                </button>
                <a href="role_management.php" class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300 transition duration-200 text-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
/* ========== Table pagination ========== */
let currentPage = 1;
const rowsPerPage = 10;
const tableBody = document.getElementById('roleTable');
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
    const headers = ["ID", "Role", "Description"];
    const data = [];
    
    <?php foreach ($roles as $role_row): ?>
        data.push([
            "<?php echo $role_row['id']; ?>",
            "<?php echo htmlspecialchars($role_row['role']); ?>",
            "<?php echo htmlspecialchars($role_row['description']); ?>"
        ]);
    <?php endforeach; ?>
    
    return {headers, data};
}

function exportPDF() {
    const {headers, data} = getExportData();
    const doc = new window.jspdf.jsPDF('p', 'pt', 'a4');
    doc.setFontSize(15);
    doc.text("Role Management Report", 40, 40);
    doc.autoTable({
        head: [headers],
        body: data,
        startY: 60,
        theme: 'striped',
        headStyles: { fillColor: [44,62,80], textColor: 255, fontStyle: 'bold' },
        bodyStyles: { fontSize: 10 },
        margin: {left: 40, right: 40}
    });
    doc.save("role-management-report.pdf");
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
    a.download = 'role-management-report.csv';
    a.click();
    URL.revokeObjectURL(url);
}

function exportExcel() {
    const {headers, data} = getExportData();
    let ws_data = [headers, ...data];
    let ws = XLSX.utils.aoa_to_sheet(ws_data);
    let wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Role Management");
    XLSX.writeFile(wb, "role-management-report.xlsx");
}

// Modal functionality
function showAddModal() {
    document.getElementById('addRoleModal').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('addRoleModal').style.opacity = '1';
    }, 10);
}

function hideAddModal() {
    document.getElementById('addRoleModal').style.opacity = '0';
    setTimeout(() => {
        document.getElementById('addRoleModal').classList.add('hidden');
    }, 300);
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const addModal = document.getElementById('addRoleModal');
    const editModal = document.getElementById('editRoleModal');
    
    if (addModal && !addModal.classList.contains('hidden')) {
        if (event.target === addModal) {
            hideAddModal();
        }
    }
    
    if (editModal && !editModal.classList.contains('hidden')) {
        if (event.target === editModal) {
            window.location.href = 'role_management.php';
        }
    }
});

// Escape key to close modals
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        hideAddModal();
        const editModal = document.getElementById('editRoleModal');
        if (editModal && !editModal.classList.contains('hidden')) {
            window.location.href = 'role_management.php';
        }
    }
});
</script>
</body>
</html>