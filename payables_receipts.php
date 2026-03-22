<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
include('connection.php');

// Fix: Get data from payables_receipts table instead of pa table
$sql = "SELECT * FROM payables_receipts WHERE status = 'disbursed' AND LOWER(mode_of_payment) != 'ecash' ORDER BY disbursed_date DESC";
$result = $conn->query($sql);

$receipts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) { 
        $receipts[] = $row; 
    }
}
?>
<html>
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <!-- jsPDF and autotable for PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
    <!-- SheetJS for Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <title>Payables Receipts</title>
    <link rel="icon" href="logo.png" type="img">
</head>
<body class="bg-white">
<?php include('sidebar.php'); ?>
<div class="overflow-y-auto h-full px-6">
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
        <h1 class="text-2xl">Payables Receipts</h1>
        <div class="text-sm">
            <a href="dashboard.php?page=dashboard" class="text-black hover:text-blue-600">Home</a>
            /
            <a class="text-black">Accounts Payable</a>
            /
            <a href="payables_receipts.php" class="text-blue-600 hover:text-blue-600">Payables Receipts</a>
        </div>
    </div>
    <div class="flex justify-between items-center mb-4 mx-6 flex-wrap gap-4">
            <!-- Left: Tabs + Filters -->
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Tabs -->
                <div class="flex gap-2 font-poppins text-sm font-medium border-b border-gray-300">
                    <button type="button" class="mode-tab px-4 py-2 rounded-t-full border-b-4 border-yellow-400 text-yellow-600 font-semibold" data-mode="all">ALL</button>
                    <button type="button" class="mode-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300" data-mode="cash">CASH</button>
                    <button type="button" class="mode-tab px-4 py-2 rounded-t-full text-gray-900 hover:text-yellow-500 hover:border-b-2 hover:border-yellow-300" data-mode="bank">BANK</button>
                </div>

                <!-- Filters -->
                <div class="flex items-center gap-2 flex-wrap">
                <input
                    type="text"
                    id="searchInput"
                    class="border px-3 py-2 rounded-full text-sm font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400"
                    placeholder="Search here"
                    onkeyup="filterTable()" />

                <label for="dateReceived" class="font-semibold ml-2">Date Disbursed:</label>
                <input
                    type="date"
                    id="dateReceived"
                    class="border border-gray-300 rounded-lg px-2 py-1 shadow-sm"
                    onchange="filterTable()" />
                </div>
            </div>

                <!-- Right: Export Buttons -->
                <div class="flex items-center gap-2">
                    <button onclick="exportPDF()" class="bg-red-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-red-700" title="Export PDF">
                    <i class="fas fa-file-pdf"></i>
                    </button>
                    <button onclick="exportCSV()" class="bg-green-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700" title="Export CSV">
                    <i class="fas fa-file-csv"></i>
                    </button>
                    <button onclick="exportExcel()" class="bg-blue-500 text-white px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-700" title="Export Excel">
                    <i class="fas fa-file-excel"></i>
                    </button>
                </div>
    </div>

            <!-- Main content area -->
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6 mx-6">
                <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
                    <i class="far fa-file-alt text-xl"></i>
                    <h2 class="text-2xl font-poppins text-black">Disbursed Receipts</h2>
                </div>
                <div id="table-container">
                    <table id="receiptsTable" class="w-full table-fixed bg-white mt-4">
                        <thead>
                            <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                                <th class="pl-6 w-1/6 py-2">Receipt ID</th>
                                <th class="pl-12 py-2">Account Name</th>
                                <th class="py-2">Description</th>
                                <th class="pl-12 py-2">Amount</th>
                                <th class="py-2">Payment Method</th>
                                <th class="py-2">Date Disbursed</th>
                            </tr>
                        </thead>
                        <tbody id="receiptsTableBody" class="text-gray-900 text-sm font-light">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4 flex justify-between items-center mx-6">
                <div id="pageStatus" class="text-gray-700 font-bold"></div>
                <div class="flex">
                    <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500" onclick="prevPage()">Previous</button>
                    <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500" onclick="nextPage()">Next</button>
                </div>
            </div>
            <div class="mt-6">
                <canvas id="pdf-viewer" width="600" height="400"></canvas>
            </div>
        </div>
    </div>
</div>
<script>
const receipts = <?php echo json_encode($receipts); ?>;
let filtered = receipts.slice();
let modeOfPayment = 'all';
let currentPage = 1;
const rowsPerPage = 10;

function matchesMode(row, mode) {
    if (mode === 'all') return true;
    const val = (row.mode_of_payment || '').toLowerCase();
    if (mode === 'bank') return val.includes('bank') || val.includes('bank transfer');
    return val === mode;
}

document.querySelectorAll('.mode-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.mode-tab').forEach(tab => tab.classList.remove('border-b-4', 'border-yellow-400', 'text-yellow-600', 'font-semibold'));
        this.classList.add('border-b-4', 'border-yellow-400', 'text-yellow-600', 'font-semibold');
        modeOfPayment = this.getAttribute('data-mode');
        filterTable();
    });
});

function renderTable(page) {
    const tbody = document.getElementById('receiptsTableBody');
    tbody.innerHTML = '';
    let start = (page - 1) * rowsPerPage;
    let end = start + rowsPerPage;
    let paginated = filtered.slice(start, end);
    if (paginated.length === 0) {
        tbody.innerHTML = "<tr><td colspan='6' class='text-center py-4'>No disbursed receipts found</td></tr>";
    } else {
        paginated.forEach(row => {
            const receiptId = row.reference_id || 'N/A';
            const accountName = row.account_name || '';
            const desc = row.description || '';
            const amt = Number(row.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            const payMethod = row.mode_of_payment || '';
            // Use disbursed_date column
            const date = row.disbursed_date ? (new Date(row.disbursed_date)).toISOString().substring(0,10) : 
                        (row.created_at ? (new Date(row.created_at)).toISOString().substring(0,10) : '');
            tbody.innerHTML += `<tr class="hover:bg-violet-100">
                <td class="pl-6 py-2 ">${receiptId}</td>
                <td class="pl-12 py-2">${accountName}</td>
                <td class="py-2">${desc}</td>
                <td class="pl-12 py-2">₱${amt}</td>
                <td class="py-2">${payMethod}</td>
                <td class="py-2">${date}</td>
            </tr>`;
        });
    }
    document.getElementById("prevPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = end >= filtered.length;
    const pageStatus = document.getElementById("pageStatus");
    const totalPages = Math.max(1, Math.ceil(filtered.length / rowsPerPage));
    pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
}

function filterTable() {
    const searchInput = document.getElementById("searchInput").value.toLowerCase();
    const dateReceived = document.getElementById("dateReceived").value;
    filtered = receipts.filter(row => {
        if (!matchesMode(row, modeOfPayment)) return false;
        const accountName = (row.account_name || '').toLowerCase();
        const description = (row.description || '').toLowerCase();
        const paymentMethod = (row.mode_of_payment || '').toLowerCase();
        const receiptId = (row.reference_id || '').toLowerCase();
        // Use disbursed_date for filtering
        const rowDate = row.disbursed_date ? (new Date(row.disbursed_date)).toISOString().substring(0,10) : 
                       (row.created_at ? (new Date(row.created_at)).toISOString().substring(0,10) : '');
        let matchSearch = accountName.includes(searchInput) || 
                         description.includes(searchInput) || 
                         paymentMethod.includes(searchInput) ||
                         receiptId.includes(searchInput);
        let matchDate = (!dateReceived || rowDate === dateReceived);
        return matchSearch && matchDate;
    });
    currentPage = 1;
    renderTable(currentPage);
}

function prevPage() { if (currentPage > 1) { currentPage--; renderTable(currentPage); } }
function nextPage() { if (currentPage * rowsPerPage < filtered.length) { currentPage++; renderTable(currentPage); } }
window.onload = () => { filterTable(); };

// --- Export PDF ---
function exportPDF() {
    const { jsPDF } = window.jspdf;
    let doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });
    let title = "Payables Receipts - Disbursed Records";
    doc.setFontSize(18);
    doc.text(title, 40, 40);
    let headers = [["Receipt ID", "Account Name", "Description", "Amount", "Payment Method", "Date Disbursed"]];
    let data = [];
    filtered.forEach(row => {
        data.push([
            row.reference_id || 'N/A',
            row.account_name || '',
            row.description || '',
            '₱' + Number(row.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}),
            row.mode_of_payment || '',
            row.disbursed_date ? (new Date(row.disbursed_date)).toISOString().substring(0,10) : 
            (row.created_at ? (new Date(row.created_at)).toISOString().substring(0,10) : '')
        ]);
    });
    doc.autoTable({
        head: headers,
        body: data,
        startY: 60,
        theme: 'grid',
        headStyles: { fillColor: [44,62,80] }
    });
    doc.save('payables_receipts_disbursed.pdf');
}

// --- Export CSV ---
function exportCSV() {
    let csvRows = [["Receipt ID","Account Name","Description","Amount","Payment Method","Date Disbursed"]];
    filtered.forEach(row => {
        csvRows.push([
            row.reference_id || '',
            row.account_name || '',
            row.description || '',
            '₱' + Number(row.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}),
            row.mode_of_payment || '',
            row.disbursed_date ? (new Date(row.disbursed_date)).toISOString().substring(0,10) : 
            (row.created_at ? (new Date(row.created_at)).toISOString().substring(0,10) : '')
        ]);
    });
    let csvContent = csvRows.map(e => e.map(v => `"${(v+'').replace(/"/g,'""')}"`).join(",")).join("\n");
    let blob = new Blob([csvContent], { type: 'text/csv' });
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = 'payables_receipts_disbursed.csv';
    a.click();
    URL.revokeObjectURL(url);
}

// --- Export Excel ---
function exportExcel() {
    let ws_data = [["Receipt ID","Account Name","Description","Amount","Payment Method","Date Disbursed"]];
    filtered.forEach(row => {
        ws_data.push([
            row.reference_id || '',
            row.account_name || '',
            row.description || '',
            Number(row.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}),
            row.mode_of_payment || '',
            row.disbursed_date ? (new Date(row.disbursed_date)).toISOString().substring(0,10) : 
            (row.created_at ? (new Date(row.created_at)).toISOString().substring(0,10) : '')
        ]);
    });
    let ws = XLSX.utils.aoa_to_sheet(ws_data);
    let wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Disbursed Receipts");
    XLSX.writeFile(wb, "payables_receipts_disbursed.xlsx");
}
</script>
</body>
</html>