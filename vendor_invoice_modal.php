<?php
session_start();
/**
 * Vendor Invoice Modal Component
 * Handles the creation of new vendor invoices.
 */

// Database connection
include('connection.php');

// AJAX request handling for Create Invoice - MOVED TO api/add_vendor_invoice.php
// This file now only handles the Modal UI and JavaScript

// Standalone handling for direct access
$isStandalone = (basename($_SERVER['PHP_SELF']) == 'vendor_invoice_modal.php');
if ($isStandalone) {
    // Fetch departments
    $departments = ['Human Resource-1', 'Human Resource-2', 'Human Resource-3', 'Human Resource-4', 'Core-1', 'Core-2', 'Logistic-1', 'Logistic-2', 'Administrative', 'Financials'];
    
    // Fetch expense categories and subcategories
    $expense_categories = [];
    $expense_subcategories = [];
    $category_query = "SELECT DISTINCT cat.name as category, sub.name as subcategory FROM chart_of_accounts_hierarchy sub JOIN chart_of_accounts_hierarchy cat ON sub.parent_id = cat.id WHERE sub.level = 3 AND cat.level = 2 AND sub.status = 'active' AND sub.type = 'Expense' ORDER BY category, subcategory";
    $category_result = $conn->query($category_query);
    if ($category_result) {
        while ($cat_row = $category_result->fetch_assoc()) {
            $category = $cat_row['category'];
            $subcategory = $cat_row['subcategory'];
            if (!in_array($category, $expense_categories)) $expense_categories[] = $category;
            if (!isset($expense_subcategories[$category])) $expense_subcategories[$category] = [];
            $expense_subcategories[$category][] = $subcategory;
        }
    }
    
    // Fetch GL Accounts
    $gl_accounts = [];
    $gl_query = "SELECT code, name FROM chart_of_accounts_hierarchy WHERE level = 4 AND status = 'active' ORDER BY code";
    $gl_result = $conn->query($gl_query);
    if ($gl_result) {
        while ($gl_row = $gl_result->fetch_assoc()) $gl_accounts[] = $gl_row;
    }
}
?>

<style>
    /* Modal backdrop with blur */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
    }
    
    .modal-content {
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        margin: 5vh auto;
        width: 95%;
        scrollbar-width: thin;
        scrollbar-color: #8b5cf6 #f3f4f6;
    }
    
    .modal-content::-webkit-scrollbar {
        width: 6px;
    }
    
    .modal-content::-webkit-scrollbar-track {
        background: #f3f4f6;
    }
    
    .modal-content::-webkit-scrollbar-thumb {
        background-color: #8b5cf6;
        border-radius: 20px;
    }
    
    /* Toast Notification Styles */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 350px;
    }
    
    .toast {
        padding: 16px 20px;
        margin-bottom: 10px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        animation: slideInRight 0.3s ease-out;
    }
    
    .toast.success { background-color: #10b981; border-left: 4px solid #059669; }
    .toast.error { background-color: #ef4444; border-left: 4px solid #dc2626; }
    .toast.info { background-color: #3b82f6; border-left: 4px solid #1d4ed8; }
    
    .toast-icon { margin-right: 12px; font-size: 20px; }
    .toast-close { background: none; border: none; color: white; font-size: 20px; cursor: pointer; margin-left: 15px; opacity: 0.8; }
    .toast-close:hover { opacity: 1; }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .modal.active { display: block !important; }
</style>

<?php if ($isStandalone): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Vendor Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div id="standalone-container" class="w-full max-w-xl">
        <button onclick="openNewModal()" class="w-full py-4 bg-purple-600 text-white font-bold rounded-xl shadow-lg hover:bg-purple-700 transition-all flex items-center justify-center gap-2">
            <i class="fas fa-plus-circle"></i> Create New Invoice
        </button>
<?php endif; ?>

<!-- New Vendor Invoice Modal -->
<div id="newModal" class="modal">
    <div class="modal-content !max-w-xl !p-0 relative">
        <button onclick="closeModal('newModal')" class="absolute top-4 right-4 text-gray-500 hover:text-purple-700 transition-colors z-10">
            <i class="fas fa-times text-2xl"></i>
        </button>
        <div class="p-8">
            <!-- Header -->
            <div class="mb-8 pr-8">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                    New Vendor Invoice
                </h2>
                <p class="text-gray-500 text-sm mt-1">Submit a new vendor invoice for record keeping and approval.</p>
            </div>
            
            <!-- Form -->
            <form id="invoiceForm" enctype="multipart/form-data" onsubmit="submitNewInvoice(event)">
                <div class="space-y-6">
                    <!-- Invoice Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Invoice ID *</label>
                            <input type="text" id="newInvoiceId" name="invoice_id" required readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-300 rounded-lg text-sm font-mono text-gray-500 cursor-not-allowed focus:outline-none" placeholder="Generating...">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Department *</label>
                            <select name="department" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept ?>"><?= $dept ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Vendor Type *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="vendor_type" value="Vendor" checked class="text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-700">Vendor</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="vendor_type" value="Supplier" class="text-purple-600 focus:ring-purple-500">
                                <span class="text-sm text-gray-700">Supplier</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Vendor Name *</label>
                        <input type="text" name="account_name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="Enter Vendor Name">
                    </div>

                     <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Address</label>
                        <textarea name="vendor_address" rows="2" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none" placeholder="Vendor/Supplier Address"></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">GL Account *</label>
                        <select name="gl_account" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all cursor-pointer">
                            <option value="">Select GL Account</option>
                            <?php foreach ($gl_accounts as $acc): ?>
                                <option value="<?= htmlspecialchars($acc['code'] . ' - ' . $acc['name']) ?>">
                                    <?= htmlspecialchars($acc['code'] . ' - ' . $acc['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Category -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Expense Category *</label>
                            <select name="expense_categories" id="expenseCategory" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer" onchange="updateSubcategories()">
                                <option value="">Select Category</option>
                                <?php foreach ($expense_categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Subcategory *</label>
                            <select name="expense_subcategory" id="expenseSubcategory" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all appearance-none cursor-pointer">
                                <option value="">Select Category First</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Amount and Due Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Amount *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-2.5 text-gray-400 text-sm font-bold">₱</span>
                                <input type="number" name="amount" required step="0.01" min="0" class="w-full pl-9 pr-4 py-2.5 bg-purple-50 border border-purple-200 rounded-lg text-sm font-bold text-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" placeholder="0.00">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Payment Due Date *</label>
                            <input type="date" name="payment_due" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Invoice Date *</label>
                        <input type="date" name="invoice_date" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
                    </div>

                    <!-- Payment Method -->
                    <div class="space-y-4 pt-2">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight mb-2">Payment Method *</label>
                        <div class="grid grid-cols-4 gap-2">
                            <label onclick="togglePaymentMethodFields('Bank Transfer')" class="cursor-pointer group">
                                <input type="radio" name="payment_method" value="Bank Transfer" required class="sr-only peer">
                                <div class="p-3 border rounded-xl text-center transition-all peer-checked:border-purple-600 peer-checked:bg-purple-50 hover:bg-gray-50">
                                    <i class="fas fa-university text-lg block mb-1 text-gray-400 peer-checked:text-purple-600"></i>
                                    <span class="text-[10px] font-bold uppercase text-gray-500 peer-checked:text-purple-700">BANK</span>
                                </div>
                            </label>
                            <label onclick="togglePaymentMethodFields('GCash')" class="cursor-pointer group">
                                <input type="radio" name="payment_method" value="GCash" required class="sr-only peer">
                                <div class="p-3 border rounded-xl text-center transition-all peer-checked:border-blue-600 peer-checked:bg-blue-50 hover:bg-gray-50">
                                    <i class="fas fa-wallet text-lg block mb-1 text-gray-400 peer-checked:text-blue-600"></i>
                                    <span class="text-[10px] font-bold uppercase text-gray-500 peer-checked:text-blue-700">GCASH</span>
                                </div>
                            </label>
                            <label onclick="togglePaymentMethodFields('PayMaya')" class="cursor-pointer group">
                                <input type="radio" name="payment_method" value="PayMaya" required class="sr-only peer">
                                <div class="p-3 border rounded-xl text-center transition-all peer-checked:border-green-600 peer-checked:bg-green-50 hover:bg-gray-50">
                                    <i class="fas fa-mobile-alt text-lg block mb-1 text-gray-400 peer-checked:text-green-600"></i>
                                    <span class="text-[10px] font-bold uppercase text-gray-500 peer-checked:text-green-700">MAYA</span>
                                </div>
                            </label>
                            <label onclick="togglePaymentMethodFields('Cash')" class="cursor-pointer group">
                                <input type="radio" name="payment_method" value="Cash" required class="sr-only peer">
                                <div class="p-3 border rounded-xl text-center transition-all peer-checked:border-yellow-600 peer-checked:bg-yellow-50 hover:bg-gray-50">
                                    <i class="fas fa-money-bill-wave text-lg block mb-1 text-gray-400 peer-checked:text-yellow-600"></i>
                                    <span class="text-[10px] font-bold uppercase text-gray-500 peer-checked:text-yellow-700">CASH</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Dynamic Bank Details -->
                    <div id="bankDetailsFields" class="hidden space-y-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Bank Name *</label>
                                <input type="text" name="bank_name" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Account Name *</label>
                                <input type="text" name="bank_account_name" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Account Number *</label>
                            <input type="text" name="bank_account_number" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                        </div>
                    </div>

                    <!-- Dynamic E-Cash Details -->
                    <div id="ecashDetailsFields" class="hidden space-y-4 p-4 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Account Name *</label>
                                <input type="text" name="ecash_account_name" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Mobile Number *</label>
                                <input type="text" name="ecash_account_number" class="w-full px-4 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Description *</label>
                        <textarea name="description" required rows="3" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all resize-none" placeholder="Invoice details..."></textarea>
                    </div>
                    
                    <!-- Document Upload (Mirroring Reimbursement UI) -->
                    <div class="space-y-1">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-tight">Supporting Documents</label>
                        
                        <!-- Drag Drop Area -->
                        <div class="drag-drop-area border-2 !border-dashed border-gray-300 hover:border-purple-400 hover:bg-purple-50 transition-all rounded-xl p-8 text-center cursor-pointer" id="dragDropArea" ondragover="handleDragOver(event)" ondrop="handleDrop(event)" ondragleave="handleDragLeave(event)" onclick="document.getElementById('fileInput').click()">
                            <div class="bg-purple-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-file-invoice text-2xl text-purple-500"></i>
                            </div>
                            <div class="text-gray-900 text-base mb-1 font-bold">Drag & drop files here</div>
                            <div class="text-xs text-gray-500 mb-6 font-medium">Support PDF, JPG, PNG (Max 5MB each)</div>
                            <div class="px-6 py-2.5 bg-purple-600 text-white text-sm font-bold rounded-xl hover:bg-purple-700 transition-all shadow-sm inline-flex items-center gap-2">
                                <i class="fas fa-search"></i> Browse Files
                            </div>
                        </div>

                        <!-- Hidden File Input -->
                        <input type="file" id="fileInput" name="receipts[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden" onchange="handleFileSelect(event)">
                        
                        <!-- Add File Button (Hidden initially) -->
                        <div id="addFileContainer" class="hidden mt-2">
                            <button type="button" onclick="document.getElementById('fileInput').click()" class="w-full py-4 border-2 border-dashed border-purple-200 rounded-xl text-purple-600 font-bold text-sm hover:bg-purple-50 transition-all flex items-center justify-center gap-2 bg-purple-50/30">
                                <i class="fas fa-plus-circle"></i> Add Another File
                            </button>
                        </div>

                        <!-- File List -->
                        <div class="mt-4 space-y-3" id="fileList"></div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex gap-4 mt-8 pt-6 border-t border-gray-100">
                    <button type="submit" id="submitInvoiceBtn" class="flex-1 px-6 py-3 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        Submit Invoice
                    </button>
                    <button type="button" onclick="closeModal('newModal')" class="flex-1 px-6 py-3 bg-gray-100 text-gray-600 font-bold rounded-xl hover:bg-gray-200 transition-all">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ========== NEW INVOICE MODAL & FILE HANDLING ==========
let filesToUpload = [];
const expenseSubcategories = <?php echo json_encode($expense_subcategories ?? []); ?>;

function updateSubcategories() {
    const categorySelect = document.getElementById('expenseCategory');
    const subcategorySelect = document.getElementById('expenseSubcategory');
    if (!categorySelect || !subcategorySelect) return;
    
    const selectedCategory = categorySelect.value;
    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
    
    if (selectedCategory && expenseSubcategories[selectedCategory]) {
        expenseSubcategories[selectedCategory].forEach(subcategory => {
            const option = document.createElement('option');
            option.value = subcategory;
            option.textContent = subcategory;
            subcategorySelect.appendChild(option);
        });
    } else {
        subcategorySelect.innerHTML = '<option value="">Select Category First</option>';
    }
}

function openNewModal() {
    if (typeof openModal === 'function') {
        openModal('newModal');
    } else {
        document.getElementById('newModal').classList.add('active');
    }
    
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    const invoiceId = `INV-${year}${month}${day}-${random}`;
    
    const input = document.getElementById('newInvoiceId');
    if (input) input.value = invoiceId;
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.remove('active');
    if (typeof window.closeModal === 'function' && window.closeModal !== closeModal) {
        window.closeModal(id);
    }
}

function openModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    modal.classList.add('active');
}

function togglePaymentMethodFields(method) {
    const bankFields = document.getElementById('bankDetailsFields');
    const ecashFields = document.getElementById('ecashDetailsFields');
    if (!bankFields || !ecashFields) return;
    
    if (method === 'Bank Transfer') {
        bankFields.classList.remove('hidden');
        ecashFields.classList.add('hidden');
    } else if (method === 'GCash' || method === 'PayMaya') {
        bankFields.classList.add('hidden');
        ecashFields.classList.remove('hidden');
    } else {
        bankFields.classList.add('hidden');
        ecashFields.classList.add('hidden');
    }
}

function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    addFilesToList(files);
    event.target.value = '';
}

function handleDragOver(event) {
    event.preventDefault();
    event.stopPropagation();
    const area = document.getElementById('dragDropArea');
    if (area) {
        area.style.borderColor = '#8b5cf6';
        area.style.backgroundColor = '#faf5ff';
    }
}

function handleDragLeave(event) {
    event.preventDefault();
    event.stopPropagation();
    const area = document.getElementById('dragDropArea');
    if (area) {
        area.style.borderColor = '#d1d5db';
        area.style.backgroundColor = '';
    }
}

function handleDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    handleDragLeave(event);
    const files = Array.from(event.dataTransfer.files);
    addFilesToList(files);
}

function addFilesToList(files) {
    const MAX_SIZE = 5 * 1024 * 1024;
    files.forEach(file => {
        if (file.size > MAX_SIZE) {
            showToast(`File ${file.name} exceeds 5MB limit.`, 'error');
            return;
        }
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
            showToast(`File ${file.name} is invalid. Only PDF, JPG, PNG allowed.`, 'error');
            return;
        }
        filesToUpload.push(file);
    });
    updateFileList();
    updateUploadUI();
}

function updateUploadUI() {
    const dragDropArea = document.getElementById('dragDropArea');
    const addFileContainer = document.getElementById('addFileContainer');
    if (!dragDropArea || !addFileContainer) return;
    
    if (filesToUpload.length > 0) {
        dragDropArea.classList.add('hidden');
        addFileContainer.classList.remove('hidden');
    } else {
        dragDropArea.classList.remove('hidden');
        addFileContainer.classList.add('hidden');
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function updateFileList() {
    const fileList = document.getElementById('fileList');
    if (!fileList) return;
    
    if (filesToUpload.length === 0) {
        fileList.innerHTML = '';
        return;
    }
    
    let html = '';
    filesToUpload.forEach((file, index) => {
        const isPdf = file.name.toLowerCase().endsWith('.pdf');
        const iconClass = isPdf ? 'fa-file-pdf text-red-500' : 'fa-file-image text-blue-500';
        html += `
            <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm hover:border-purple-300 transition-all group">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-lg bg-gray-50 flex items-center justify-center flex-shrink-0">
                        <i class="fas ${iconClass} text-xl"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-sm font-bold text-gray-900 truncate">${file.name}</div>
                        <div class="text-xs text-gray-500">${formatFileSize(file.size)}</div>
                    </div>
                </div>
                <button type="button" onclick="removeFile(${index})" class="w-8 h-8 rounded-full flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });
    fileList.innerHTML = html;
}

function removeFile(index) {
    filesToUpload.splice(index, 1);
    updateFileList();
    updateUploadUI();
}

async function submitNewInvoice(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = document.getElementById('submitInvoiceBtn');
    if (!submitBtn) return;
    
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Submitting...';
    submitBtn.disabled = true;
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'create_invoice');
        formData.delete('receipts[]');
        filesToUpload.forEach(file => { formData.append('receipts[]', file); });
        
        const response = await fetch('api/add_vendor_invoice.php', { method: 'POST', body: formData });
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            closeModal('newModal');
            filesToUpload = [];
            form.reset();
            updateFileList();
            updateUploadUI();
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Network error occurred', 'error');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

function showToast(msg, type) {
    const container = document.getElementById('toast-container-standalone') || createToastContainer();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
    toast.innerHTML = `<i class="fas ${icon} toast-icon"></i><span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">&times;</button>`;
    container.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 5000);
}

function createToastContainer() {
    let container = document.getElementById('toast-container-standalone');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container-standalone';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    return container;
}
</script>

<?php if ($isStandalone): ?>
    </div>
</body>
</html>
<?php endif; ?>
