<?php
    // filepath: c:\xampp\htdocs\Administrative\LegalManagement\LegalOfficer\document.php
    include_once("../connection.php");
    // Enhanced session validation
    
    session_start();
    
    if (
        !isset($_SESSION['username']) ||
        !isset($_SESSION['role']) ||
        empty($_SESSION['username']) ||
        empty($_SESSION['role']) ||
        $_SESSION['role'] !== 'financial admin'
    ) {
        // Clear any existing session data
        session_unset();
        session_destroy();
    
        // Prevent caching
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    
        // Redirect to login
        header("Location: ../login.php");
        exit();
    }
    
    // Optional: Add session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        header("Location:../login.php");
        exit();
    }
    $_SESSION['last_activity'] = time();
    $fullname = $_SESSION['full_name'] ?? $_SESSION['fullname'] ?? 'Administrator';
    $role = $_SESSION['role'] ?? 'admin';
    $employee_id = $_SESSION['employee_id'] ?? 'EMP001';
    $department = $_SESSION['department'] ?? 'Administrative';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Document Library - Legal Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #a78bfa 0%, #6366f1 100%);
      --sidebar-bg: #18181b;
      --card-shadow: 0 2px 8px rgba(140, 140, 200, 0.07);
      --card-hover-shadow: 0 6px 24px rgba(108,71,255,0.13);
    }
    
    * { box-sizing: border-box; }
    body { 
      background: #f7f8fa; 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      margin: 0;
      padding: 0;
    }
    
    /* Sidebar Styles */
    .sidebar {
      width: 260px;
      min-height: 100vh;
      background: var(--sidebar-bg);
      color: #fff;
      position: fixed;
      left: 0; top: 0; bottom: 0;
      z-index: 100;
      padding: 2rem 1rem 1rem 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      transition: left 0.3s ease;
    }
    
    .sidebar .logo-container {
      margin-bottom: 2rem;
    }
    
    .sidebar .nav-link {
      color: #bfc7d1;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      font-weight: 500;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      font-size: 1.08rem;
      padding: 0.75rem 1rem;
    }
    
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: var(--primary-gradient);
      color: #fff;
      text-decoration: none;
    }
    
    .sidebar .logout-link {
      color: #f87171;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      text-decoration: none;
      padding: 0.75rem 1rem;
      border-radius: 8px;
      transition: background 0.2s;
    }
    
    .sidebar .logout-link:hover {
      background: rgba(248, 113, 113, 0.1);
    }
    
    /* Main Content */
    .main-content {
      margin-left: 260px;
      padding: 2.5rem;
      min-height: 100vh;
    }
    
    /* Header */
    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    
    .dashboard-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: #18181b;
      margin-bottom: 0.2rem;
      letter-spacing: 0.5px;
    }
    
    .dashboard-desc {
      color: #6c757d;
      font-size: 1.13rem;
    }
    
    .header-actions {
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }
    
    /* Filter Bar */
    .filter-bar {
      background: #fff;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
      padding: 1.2rem 1rem;
      margin-bottom: 2rem;
      border: 1px solid #ececec;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }
    
    .filter-bar .form-control,
    .filter-bar .form-select {
      border-radius: 8px;
      border: 1px solid #e5e7eb;
    }
    
    /* View Toggle */
    .view-toggle {
      background: #fff;
      border-radius: 8px;
      padding: 0.3rem;
      border: 1px solid #e5e7eb;
    }
    
    .view-toggle .btn {
      border: none;
      background: transparent;
      color: #6c757d;
      padding: 0.5rem 1rem;
      border-radius: 6px;
      transition: all 0.2s;
    }
    
    .view-toggle .btn.active {
      background: var(--primary-gradient);
      color: #fff;
    }
    
    /* Document Cards - Grid View */
    .documents-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    
    .document-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      transition: all 0.3s ease;
      border: 1px solid #ececec;
      cursor: pointer;
      position: relative;
    }
    
    .document-card:hover {
      box-shadow: var(--card-hover-shadow);
      border-color: #a78bfa;
      transform: translateY(-4px);
    }
    
    .document-card.locked {
      opacity: 0.7;
      cursor: not-allowed;
    }
    
    .document-card.locked:hover {
      transform: none;
    }
    
    .document-icon-wrapper {
      background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
      padding: 2.5rem 1rem;
      text-align: center;
      position: relative;
    }
    
    .document-card.locked .document-icon-wrapper::after {
      content: '\F42F';
      font-family: 'bootstrap-icons';
      position: absolute;
      top: 1rem;
      right: 1rem;
      font-size: 1.5rem;
      color: #ef4444;
    }
    
    .document-icon {
      font-size: 4rem;
      color: #6366f1;
    }
    
    .document-body {
      padding: 1.2rem;
    }
    
    .document-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: #18181b;
      margin-bottom: 0.5rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .document-meta {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
      font-size: 0.875rem;
      color: #6c757d;
    }
    
    .document-meta .meta-item {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    
    .access-badge {
      display: inline-block;
      padding: 0.3em 0.8em;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-top: 0.5rem;
    }
    
    .access-badge.granted {
      background: #bbf7d0;
      color: #166534;
    }
    
    .access-badge.denied {
      background: #fecaca;
      color: #991b1b;
    }
    
    /* Document List - List View */
    .documents-list {
      background: #fff;
      border-radius: 12px;
      box-shadow: var(--card-shadow);
      overflow: hidden;
      border: 1px solid #ececec;
    }
    
    .document-list-item {
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid #f0f0f0;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s;
      cursor: pointer;
    }
    
    .document-list-item:hover {
      background: #f9fafb;
    }
    
    .document-list-item.locked {
      opacity: 0.7;
      cursor: not-allowed;
    }
    
    .document-list-item.locked:hover {
      background: transparent;
    }
    
    .document-list-item:last-child {
      border-bottom: none;
    }
    
    .doc-icon-small {
      font-size: 2rem;
      color: #6366f1;
      min-width: 50px;
      text-align: center;
    }
    
    .doc-info {
      flex: 1;
    }
    
    .doc-name {
      font-weight: 700;
      color: #18181b;
      margin-bottom: 0.25rem;
    }
    
    .doc-details {
      display: flex;
      gap: 1rem;
      font-size: 0.875rem;
      color: #6c757d;
      flex-wrap: wrap;
    }
    
    .doc-actions {
      display: flex;
      gap: 0.5rem;
    }
    
    /* Modal Styles */
    .modal-content {
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(108,71,255,0.10);
      border: 1px solid #ececec;
    }
    
    .modal-header {
      border-bottom: 1px solid #f0f0f0;
      background: #f7f8fa;
      border-radius: 18px 18px 0 0;
    }
    
    .modal-title {
      font-weight: 700;
      color: #6366f1;
    }
    
    /* Access Denied State */
    .access-denied-overlay {
      padding: 3rem;
      text-align: center;
    }
    
    .access-denied-overlay i {
      font-size: 5rem;
      color: #ef4444;
      margin-bottom: 1rem;
    }
    
    .access-denied-overlay h4 {
      color: #18181b;
      margin-bottom: 0.5rem;
    }
    
    .access-denied-overlay p {
      color: #6c757d;
    }
    
    /* Empty State */
    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
      color: #6c757d;
    }
    
    .empty-state i {
      font-size: 4rem;
      color: #d1d5db;
      margin-bottom: 1rem;
    }
    
    /* Loading */
    .spinner-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 3rem;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
      .sidebar {
        left: -260px;
      }
      
      .sidebar.show {
        left: 0;
      }
      
      .main-content {
        margin-left: 0;
      }
      
      .mobile-menu-btn {
        display: block;
        position: fixed;
        top: 1rem;
        left: 1rem;
        z-index: 99;
        background: var(--sidebar-bg);
        color: #fff;
        border: none;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
      }
    }
    
    .mobile-menu-btn {
      display: none;
    }
  </style>
</head>
<body>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
  <i class="bi bi-list"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div>
    <div class="logo-container m-2">
      <img src="../logo2.png" class="img-fluid" style="height:45px;" alt="Logo">
    </div>
    <nav class="nav flex-column">
      <a class="nav-link" href="index.php"><i class="bi bi-folder2-open"></i> Requests</a>
      <a class="nav-link active" href="#"><i class="bi bi-archive"></i> Documents</a>
    </nav>
  </div>
  <div>
    <hr class="bg-secondary">
    <a href="../dashboard_admin.php" class="logout-link">
      <i class="bi bi-box-arrow-left"></i> Back to Dashboard
    </a>
  </div>
</div>

<!-- Main Content -->
<div class="main-content">
  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="dashboard-title">Document Library</div>
      <div class="dashboard-desc">Access and manage legal documents</div>
    </div>
    <div class="header-actions">
      <div class="view-toggle">
        <button class="btn active" id="gridViewBtn" data-view="grid">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button class="btn" id="listViewBtn" data-view="list">
          <i class="bi bi-list-ul"></i>
        </button>
      </div>
      <button class="btn btn-primary" id="uploadDocBtn">
        <i class="bi bi-cloud-upload"></i> Upload Document
      </button>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <input type="text" class="form-control" id="searchInput" placeholder="Search documents..." style="max-width:250px;">
    <select class="form-select" id="categoryFilter" style="max-width:180px;">
      <option value="">All Categories</option>
      <option value="Contract">Contract</option>
      <option value="Policy">Policy</option>
      <option value="Template">Template</option>
      <option value="Legal Opinion">Legal Opinion</option>
      <option value="Compliance">Compliance</option>
      <option value="Others">Others</option>
    </select>
    <select class="form-select" id="accessFilter" style="max-width:180px;">
      <option value="">All Documents</option>
      <option value="accessible">Accessible Only</option>
      <option value="restricted">Restricted Only</option>
    </select>
    <button class="btn btn-primary" id="filterBtn">
      <i class="bi bi-funnel"></i> Filter
    </button>
    <button class="btn btn-outline-secondary" id="clearFiltersBtn">
      <i class="bi bi-x-circle"></i> Clear
    </button>
  </div>

  <!-- Documents Container -->
  <div id="documentsContainer">
    <div class="spinner-wrapper">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
  </div>
</div>

<!-- Document Details Modal -->
<div class="modal fade" id="documentDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h4 class="modal-title mb-1" id="docModalTitle">Document Title</h4>
          <div class="text-muted small" id="docModalCategory">Category</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="docModalBody">
        <!-- Content will be loaded dynamically -->
      </div>
    </div>
  </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="uploadDocumentForm" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-cloud-upload me-2"></i>Upload Document
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
        <input type="hidden" name="uploaded_by" value="<?php echo htmlspecialchars($employee_id); ?>">
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Document Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" placeholder="Enter document title" required>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description"></textarea>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
          <select name="category" class="form-select" required>
            <option value="">Select category...</option>
            <option value="Contract">Contract</option>
            <option value="Policy">Policy</option>
            <option value="Template">Template</option>
            <option value="Legal Opinion">Legal Opinion</option>
            <option value="Compliance">Compliance</option>
            <option value="Others">Others</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Access Level <span class="text-danger">*</span></label>
          <select name="access_level" class="form-select" required>
            <option value="department">Department Only</option>
            <option value="restricted">Restricted (Specific Users)</option>
            <option value="public">Public (All Users)</option>
          </select>
        </div>
        
        <div class="mb-3" id="allowedUsersDiv" style="display:none;">
          <label class="form-label fw-semibold">Allowed Users (Employee IDs)</label>
          <input type="text" name="allowed_users" class="form-control" placeholder="e.g., EMP001, EMP002, EMP003">
          <small class="text-muted">Comma-separated employee IDs</small>
        </div>
        
        <div class="mb-3">
          <label class="form-label fw-semibold">Select File <span class="text-danger">*</span></label>
          <input type="file" name="document" class="form-control" id="docFileInput" accept=".pdf,.doc,.docx,.xls,.xlsx" required>
          <small class="text-muted">Accepted: PDF, DOC, DOCX, XLS, XLSX (Max: 10MB)</small>
        </div>
        
        <div id="uploadFilePreview" style="display:none; margin-top:0.5rem;">
          <div class="p-3 bg-light rounded border">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-file-earmark-text" style="font-size:1.5rem; color:#6366f1;"></i>
              <div class="flex-grow-1">
                <div class="fw-semibold" id="uploadFileName"></div>
                <div class="text-muted small" id="uploadFileSize"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="submitUploadBtn">
          <i class="bi bi-upload me-1"></i>Upload
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Global Variables
let allDocuments = [];
let currentView = 'grid';
const currentEmployeeId = '<?php echo $employee_id; ?>';
const currentDepartment = '<?php echo $department; ?>';
const API_BASE = 'https://administrative.viahale.com/api_endpoint/document.php';

// ==================== INITIALIZATION ====================
document.addEventListener('DOMContentLoaded', function() {
  initializeApp();
  setupEventListeners();
  fetchDocuments();
  
  // Auto-refresh every 60 seconds
  setInterval(fetchDocuments, 60000);
});

function initializeApp() {
  // Mobile menu toggle
  const mobileMenuBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function() {
      sidebar.classList.toggle('show');
    });
  }
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
      if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
        sidebar.classList.remove('show');
      }
    }
  });
}

function setupEventListeners() {
  // View toggle
  document.getElementById('gridViewBtn').addEventListener('click', () => switchView('grid'));
  document.getElementById('listViewBtn').addEventListener('click', () => switchView('list'));
  
  // Filters
  document.getElementById('filterBtn').addEventListener('click', renderDocuments);
  document.getElementById('clearFiltersBtn').addEventListener('click', clearFilters);
  document.getElementById('searchInput').addEventListener('input', debounce(renderDocuments, 300));
  document.getElementById('categoryFilter').addEventListener('change', renderDocuments);
  document.getElementById('accessFilter').addEventListener('change', renderDocuments);
  
  // Upload
  document.getElementById('uploadDocBtn').addEventListener('click', () => {
    const modal = new bootstrap.Modal(document.getElementById('uploadDocumentModal'));
    modal.show();
  });
  
  // Access level change
  document.querySelector('select[name="access_level"]').addEventListener('change', function() {
    document.getElementById('allowedUsersDiv').style.display = 
      this.value === 'restricted' ? 'block' : 'none';
  });
  
  // File input
  document.getElementById('docFileInput').addEventListener('change', handleFileSelect);
  
  // Form submit
  document.getElementById('uploadDocumentForm').addEventListener('submit', handleUploadSubmit);
}

// ==================== VIEW SWITCHING ====================
function switchView(view) {
  currentView = view;
  
  document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
  document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
  
  renderDocuments();
}

// ==================== DATA FETCHING ====================
async function fetchDocuments() {
  try {
    const response = await fetch(`${API_BASE}?action=get_documents&department=${encodeURIComponent(currentDepartment)}&employee_id=${encodeURIComponent(currentEmployeeId)}`);
    const data = await response.json();
    
    if (data.success) {
      allDocuments = Array.isArray(data.documents) ? data.documents : [];
    } else {
      allDocuments = [];
    }
    
    renderDocuments();
  } catch (error) {
    console.error('Error fetching documents:', error);
    document.getElementById('documentsContainer').innerHTML = `
      <div class="empty-state">
        <i class="bi bi-exclamation-triangle"></i>
        <h5>Error Loading Documents</h5>
        <p>Unable to load documents. Please try again later.</p>
      </div>
    `;
  }
}

// ==================== RENDER DOCUMENTS ====================
function renderDocuments() {
  const container = document.getElementById('documentsContainer');
  let filtered = [...allDocuments];
  
  // Apply filters
  const search = document.getElementById('searchInput').value.trim().toLowerCase();
  const category = document.getElementById('categoryFilter').value;
  const accessFilter = document.getElementById('accessFilter').value;
  
  if (search) {
    filtered = filtered.filter(doc =>
      (doc.title && doc.title.toLowerCase().includes(search)) ||
      (doc.description && doc.description.toLowerCase().includes(search))
    );
  }
  
  if (category) {
    filtered = filtered.filter(doc => doc.category === category);
  }
  
  if (accessFilter === 'accessible') {
    filtered = filtered.filter(doc => doc.has_access);
  } else if (accessFilter === 'restricted') {
    filtered = filtered.filter(doc => !doc.has_access);
  }
  
  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <i class="bi bi-inbox"></i>
        <h5>No Documents Found</h5>
        <p>Try adjusting your filters or upload a new document.</p>
      </div>
    `;
    return;
  }
  
  if (currentView === 'grid') {
    renderGridView(filtered, container);
  } else {
    renderListView(filtered, container);
  }
}

function renderGridView(documents, container) {
  container.innerHTML = `
    <div class="documents-grid">
      ${documents.map(doc => createDocumentCard(doc)).join('')}
    </div>
  `;
  
  // Add click listeners
  addDocumentClickListeners();
}

function renderListView(documents, container) {
  container.innerHTML = `
    <div class="documents-list">
      ${documents.map(doc => createDocumentListItem(doc)).join('')}
    </div>
  `;
  
  // Add click listeners
  addDocumentClickListeners();
}

function createDocumentCard(doc) {
  const iconClass = getFileIcon(doc.file_extension || 'pdf');
  const hasAccess = doc.has_access;
  const lockedClass = hasAccess ? '' : 'locked';
  
  return `
    <div class="document-card ${lockedClass}" data-doc-id="${doc.document_id}" data-has-access="${hasAccess}">
      <div class="document-icon-wrapper">
        <i class="bi ${iconClass} document-icon"></i>
      </div>
      <div class="document-body">
        <div class="document-title">${escapeHtml(doc.title || 'Untitled')}</div>
        <div class="document-meta">
          <div class="meta-item">
            <i class="bi bi-tag"></i>
            <span>${escapeHtml(doc.category || 'Uncategorized')}</span>
          </div>
          <div class="meta-item">
            <i class="bi bi-calendar"></i>
            <span>${doc.created_at ? formatDate(new Date(doc.created_at)) : 'N/A'}</span>
          </div>
          <div class="meta-item">
            <i class="bi bi-person"></i>
            <span>${escapeHtml(doc.uploaded_by_name || 'Unknown')}</span>
          </div>
        </div>
        <span class="access-badge ${hasAccess ? 'granted' : 'denied'}">
          <i class="bi ${hasAccess ? 'bi-unlock' : 'bi-lock'}"></i>
          ${hasAccess ? 'Access Granted' : 'Restricted'}
        </span>
      </div>
    </div>
  `;
}

function createDocumentListItem(doc) {
  const iconClass = getFileIcon(doc.file_extension || 'pdf');
  const hasAccess = doc.has_access;
  const lockedClass = hasAccess ? '' : 'locked';
  
  return `
    <div class="document-list-item ${lockedClass}" data-doc-id="${doc.document_id}" data-has-access="${hasAccess}">
      <i class="bi ${iconClass} doc-icon-small"></i>
      <div class="doc-info">
        <div class="doc-name">${escapeHtml(doc.title || 'Untitled')}</div>
        <div class="doc-details">
          <span><i class="bi bi-tag me-1"></i>${escapeHtml(doc.category || 'Uncategorized')}</span>
          <span><i class="bi bi-calendar me-1"></i>${doc.created_at ? formatDate(new Date(doc.created_at)) : 'N/A'}</span>
          <span><i class="bi bi-person me-1"></i>${escapeHtml(doc.uploaded_by_name || 'Unknown')}</span>
        </div>
      </div>
      <div class="doc-actions">
        <span class="access-badge ${hasAccess ? 'granted' : 'denied'}">
          <i class="bi ${hasAccess ? 'bi-unlock' : 'bi-lock'}"></i>
          ${hasAccess ? 'Access Granted' : 'Restricted'}
        </span>
      </div>
    </div>
  `;
}

function addDocumentClickListeners() {
  document.querySelectorAll('.document-card, .document-list-item').forEach(card => {
    card.addEventListener('click', function() {
      const docId = this.getAttribute('data-doc-id');
      const hasAccess = this.getAttribute('data-has-access') === 'true';
      showDocumentDetails(docId, hasAccess);
    });
  });
}

// ==================== DOCUMENT DETAILS ====================
function showDocumentDetails(docId, hasAccess) {
  const doc = allDocuments.find(d => d.document_id == docId);
  if (!doc) return;
  
  const modal = document.getElementById('documentDetailsModal');
  const modalTitle = document.getElementById('docModalTitle');
  const modalCategory = document.getElementById('docModalCategory');
  const modalBody = document.getElementById('docModalBody');
  
  modalTitle.textContent = doc.title || 'Untitled';
  modalCategory.textContent = doc.category || 'Uncategorized';
  
  if (!hasAccess) {
    modalBody.innerHTML = `
      <div class="access-denied-overlay">
        <i class="bi bi-lock-fill"></i>
        <h4>Access Denied</h4>
        <p>You don't have permission to view this document.</p>
        <p class="text-muted small">Contact the document owner or administrator for access.</p>
      </div>
    `;
  } else {
    modalBody.innerHTML = `
      <div class="row mb-3">
        <div class="col-md-6">
          <div class="fw-semibold text-muted small">UPLOADED BY</div>
          <div>${escapeHtml(doc.uploaded_by_name || 'Unknown')}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-semibold text-muted small">UPLOAD DATE</div>
          <div>${doc.created_at ? formatDateTime(new Date(doc.created_at)) : 'N/A'}</div>
        </div>
      </div>
      
      ${doc.description ? `
        <div class="mb-3">
          <div class="fw-semibold text-muted small mb-2">DESCRIPTION</div>
          <div class="p-3 bg-light rounded">${escapeHtml(doc.description)}</div>
        </div>
      ` : ''}
      
      <div class="mb-3">
        <div class="fw-semibold text-muted small mb-2">FILE INFORMATION</div>
        <div class="p-3 bg-light rounded">
          <div class="d-flex align-items-center gap-3">
            <i class="bi ${getFileIcon(doc.file_extension || 'pdf')}" style="font-size:2.5rem; color:#6366f1;"></i>
            <div class="flex-grow-1">
              <div class="fw-semibold">${doc.filename || 'document.' + (doc.file_extension || 'pdf')}</div>
              <div class="text-muted small">${doc.file_size ? formatFileSize(doc.file_size) : 'Unknown size'}</div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="d-flex gap-2">
        <a href="${doc.file_path}" target="_blank" class="btn btn-primary">
          <i class="bi bi-eye me-1"></i>View Document
        </a>
        <a href="${doc.file_path}" download class="btn btn-success">
          <i class="bi bi-download me-1"></i>Download
        </a>
      </div>
    `;
  }
  
  const bsModal = new bootstrap.Modal(modal);
  bsModal.show();
}

// ==================== FILE UPLOAD ====================
function handleFileSelect(e) {
  const file = e.target.files[0];
  if (!file) {
    document.getElementById('uploadFilePreview').style.display = 'none';
    return;
  }
  
  // Validate file size
  const maxSize = 10 * 1024 * 1024; // 10MB
  if (file.size > maxSize) {
    alert('File size exceeds 10MB. Please choose a smaller file.');
    e.target.value = '';
    document.getElementById('uploadFilePreview').style.display = 'none';
    return;
  }
  
  // Show preview
  document.getElementById('uploadFileName').textContent = file.name;
  document.getElementById('uploadFileSize').textContent = formatFileSize(file.size);
  document.getElementById('uploadFilePreview').style.display = 'block';
}

async function handleUploadSubmit(e) {
  e.preventDefault();
  
  const submitBtn = document.getElementById('submitUploadBtn');
  const originalText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading...';
  submitBtn.disabled = true;
  
  try {
    const formData = new FormData(e.target);
    formData.append('action', 'upload_document');
    
    const response = await fetch(API_BASE, {
      method: 'POST',
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      showNotification('Success!', data.message || 'Document uploaded successfully', 'success');
      e.target.reset();
      document.getElementById('uploadFilePreview').style.display = 'none';
      
      const modal = bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal'));
      if (modal) modal.hide();
      
      setTimeout(() => {
        fetchDocuments();
      }, 500);
    } else {
      showNotification('Error', data.message || 'Failed to upload document', 'danger');
    }
  } catch (error) {
    console.error('Upload error:', error);
    showNotification('Error', 'Failed to upload document. Please try again.', 'danger');
  } finally {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
  }
}

// ==================== UTILITY FUNCTIONS ====================
function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('categoryFilter').value = '';
  document.getElementById('accessFilter').value = '';
  renderDocuments();
}

function getFileIcon(extension) {
  const ext = (extension || '').toLowerCase().replace('.', '');
  const icons = {
    'pdf': 'bi-file-pdf-fill',
    'doc': 'bi-file-word-fill',
    'docx': 'bi-file-word-fill',
    'xls': 'bi-file-excel-fill',
    'xlsx': 'bi-file-excel-fill'
  };
  return icons[ext] || 'bi-file-earmark-text-fill';
}

function formatDate(date) {
  return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}

function formatDateTime(date) {
  return date.toLocaleString('en-US', { 
    month: 'short', 
    day: '2-digit', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function showNotification(title, message, type = 'info') {
  const toastHTML = `
    <div class="toast align-items-center text-white bg-${type} border-0" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
      <div class="d-flex">
        <div class="toast-body">
          <strong>${title}</strong><br>${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `;
  
  const toastContainer = document.createElement('div');
  toastContainer.innerHTML = toastHTML;
  document.body.appendChild(toastContainer);
  
  const toastElement = toastContainer.querySelector('.toast');
  const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
  toast.show();
  
  toastElement.addEventListener('hidden.bs.toast', () => {
    toastContainer.remove();
  });
}
</script>
</body>
</html>