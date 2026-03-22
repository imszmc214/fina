<?php
// filepath: c:\xampp\htdocs\Administrative\LegalManagement\LegalOfficer\index.php
include_once("connection.php");
// Enhanced session validation

session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Legal Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <style>
    body { background: #f7f8fa; }
    .sidebar {
      width: 260px;
      min-height: 100vh;
      background: #18181b;
      color: #fff;
      position: fixed;
      left: 0; top: 0; bottom: 0;
      z-index: 100;
      padding: 2rem 1rem 1rem 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .sidebar .logo {
      width: 48px; height: 48px;
      background: #fff;
      color: #18181b;
      font-weight: bold;
      font-size: 1.5rem;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 2rem;
    }
    .sidebar .sidebar-title {
      font-size: 1.1rem;
      letter-spacing: 1px;
      font-weight: 700;
      margin-bottom: 2rem;
      text-transform: uppercase;
      color: #a78bfa;
    }
    .sidebar .nav-link {
      color: #bfc7d1;
      border-radius: 8px;
      margin-bottom: 0.5rem;
      font-weight: 500;
      transition: background 0.2s, color 0.2s;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      font-size: 1.08rem;
    }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover {
      background: linear-gradient(90deg, #a78bfa 0%, #6366f1 100%);
      color: #fff;
    }
    .sidebar .logout-link {
      color: #f87171;
      font-weight: 600;
      margin-top: 2rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
    }
    .main-content {
      margin-left: 260px;
      padding: 2.5rem 2.5rem 2.5rem 2.5rem;
      min-height: 100vh;
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
      margin-bottom: 2.2rem;
    }
    .stats-cards {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 2.2rem;
      flex-wrap: wrap;
    }
    .stats-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 2px 8px rgba(140, 140, 200, 0.07);
      flex: 1;
      padding: 1.5rem 1.2rem;
      text-align: center;
      min-width: 170px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      border: 1px solid #f0f0f0;
      transition: box-shadow 0.2s, transform 0.2s;
      cursor: pointer;
    }
    .stats-card:hover {
      box-shadow: 0 6px 24px rgba(108,71,255,0.13);
      transform: translateY(-4px) scale(1.03);
      border-color: #a78bfa;
    }
    .stats-card .icon {
      background: linear-gradient(135deg, #a78bfa 0%, #6366f1 100%);
      color: #fff;
      border-radius: 50%;
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-bottom: 0.5rem;
      box-shadow: 0 2px 8px rgba(140,140,200,0.13);
    }
    .stats-card .label {
      font-size: 1.08rem;
      color: #6366f1;
      margin-bottom: 0.2rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    .stats-card .value {
      font-size: 2.1rem;
      font-weight: 800;
      color: #18181b;
      letter-spacing: -1px;
    }
    .filter-bar {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(140,140,200,0.07);
      padding: 1.2rem 1rem;
      margin-bottom: 2rem;
      border: 1px solid #ececec;
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: center;
    }
    .request-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 8px rgba(140,140,200,0.07);
      overflow: hidden;
      margin-bottom: 1.5rem;
      transition: box-shadow 0.2s;
      padding: 1.5rem 1.2rem;
      border: 1px solid #ececec;
      cursor: pointer;
      position: relative;
    }
    .request-card .fw-bold {
      color: #18181b;
      font-size: 1.2rem;
    }
    .request-card .badge {
      font-size: 1rem;
      font-weight: 600;
      padding: 0.4em 1em;
    }
    .request-card .text-muted {
      font-size: 1.01rem;
    }
    .request-card .status-badge {
      position: absolute;
      top: 1.2rem;
      right: 1.2rem;
      font-size: 1rem;
      font-weight: 700;
      border-radius: 8px;
      padding: 0.3em 1em;
      background: #f3f4f6;
      color: #a78bfa;
      text-transform: lowercase;
    }
    .request-card .status-badge.pending { background: #fef9c3; color: #eab308; }
    .request-card .status-badge.approved { background: #bbf7d0; color: #22c55e; }
    .request-card .status-badge.rejected { background: #fecaca; color: #ef4444; }
    .request-card .status-badge.completed { background: #dbeafe; color: #2563eb; }
    #requestDetailsModal .modal-content {
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(108,71,255,0.10);
      border: 1px solid #ececec;
    }
    #requestDetailsModal .modal-header {
      border-bottom: 1px solid #f0f0f0;
      background: #f7f8fa;
    }
    #requestDetailsModal .modal-title {
      font-weight: 700;
      color: #6366f1;
    }
    #requestDetailsModal .badge {
      font-size: 1rem;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
    @media (max-width: 900px) {
      .main-content { margin-left: 0; width: 100%; padding: 1rem 0.5rem; }
      .sidebar { width: 100vw; left: -100vw; }
      .sidebar.show { left: 0; }
      .stats-cards { flex-direction: column; gap: 1rem; }
    }
  </style>
</head>
<body>
<div class="sidebar">
  <div>
    <div class="logo-container m-2 ">
      <img src="/admin/logo2.png" class="img-fluid" style="height:45px;" alt="Logo">
    </div>
    <nav class="nav flex-column">
      <a class="nav-link active" href="#"><i class="bi bi-folder2-open"></i> Requests</a>
    </nav>
  </div>
  <div>
    <hr class="bg-secondary">
    <a href="/admin/dashboard_admin.php" class="nav-link logout-link">
      <i class="bi bi-box-arrow-right"></i> Back
    </a>
  </div>
</div>
<div class="main-content">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <div class="dashboard-title">Document Management</div>
      <div class="dashboard-desc">Organize and manage your documents</div>
    </div>
    <div>
      <button class="btn btn-secondary me-2" data-bs-toggle="modal" data-bs-target="#legalRequestModal"><i class="bi bi-file-earmark-text"></i> Send Legal Request</button>
      <button class="btn btn-success me-2"><i class="bi bi-file-earmark-excel"></i> Export Report</button>
      <button class="btn btn-primary"><i class="bi bi-bar-chart"></i> Analytics</button>
      <div class="position-relative d-inline-block">
  <button id="notifBell" class="btn btn-light position-relative" style="border-radius:50%;padding:0.6rem 0.7rem;">
    <i class="bi bi-bell" style="font-size:1.5rem;"></i>
    <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.8rem;display:none;">0</span>
  </button>
  <div id="notifDropdown" class="card shadow-sm" style="display:none;position:absolute;right:0;top:110%;min-width:320px;z-index:999;">
    <div class="card-header fw-bold py-2 px-3">Notifications</div>
    <ul class="list-group list-group-flush" style="max-height:300px;overflow-y:auto;" id="notifList">
      <!-- Notifications will be loaded here by JS -->
    </ul>
  </div>
</div>
    </div>
  </div>
  <!-- Stats Cards -->
  <div class="stats-cards" id="statsCards">
    <!-- Filled by JS -->
  </div>
  <!-- Filter/Search Bar -->
  <div class="filter-bar">
    <input type="text" class="form-control" id="searchInput" placeholder="Search by title, ID, or requester..." style="max-width:220px;">
    <select class="form-select" id="departmentFilter" style="max-width:180px;">
      <option value="">All Departments</option>
      <option value="Administrative">Administrative</option>
      <option value="HR">HR</option>
      <option value="Finance">Finance</option>
    </select>
    <select class="form-select" id="typeFilter" style="max-width:150px;">
      <option value="">All Types</option>
      <option>Contract Review</option>
      <option>Documentation Validation</option>
      <option>Legal Opinion</option>
      <option>Template Request</option>
      <option>Signature Coordination</option>
      <option>Policy Drafting</option>
      <option>Compliance Check</option>
      <option>Risk Assessment</option>
      <option>Others</option>
    </select>
    <input type="date" class="form-control" id="dateFrom" style="max-width:150px;">
    <span>to</span>
    <input type="date" class="form-control" id="dateTo" style="max-width:150px;">
    <button class="btn btn-outline-secondary" id="filterBtn">Filter</button>
    <a href="#" class="ms-2" id="clearDates" style="font-size:0.95rem;">Clear Dates</a>
  </div>
  <!-- Legal Requests List -->
  <div id="legalRequestsList"></div>
</div>

<!-- Legal Request Modal -->
<div class="modal fade" id="legalRequestModal" tabindex="-1" aria-labelledby="legalRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="legalRequestForm" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="legalRequestModalLabel">Send Legal Request</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="user_id" value="EMP001">
        <input type="hidden" name="employee_id" value="EMP001">
        <div class="mb-3">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description <span class="text-danger">*</span></label>
          <textarea name="description" class="form-control" rows="3" required></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Request Type <span class="text-danger">*</span></label>
          <select name="request_type" class="form-select" required id="request_type_select">
            <option value="">Select type...</option>
            <option>Contract Review</option>
            <option>Documentation Validation</option>
            <option>Legal Opinion</option>
            <option>Template Request</option>
            <option>Signature Coordination</option>
            <option>Policy Drafting</option>
            <option>Compliance Check</option>
            <option>Risk Assessment</option>
            <option>Others</option>
          </select>
        </div>
        <div class="mb-3" id="otherRequestTypeDesc" style="display:none;">
          <label class="form-label">Other Request Type</label>
          <input type="text" name="other_request_type" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select">
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Low">Low</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Complexity Level</label>
          <select name="complexity_level" class="form-select">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Urgency</label>
          <select name="urgency" class="form-select">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Stakeholders</label>
          <input type="text" name="stakeholders" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Purpose</label>
          <textarea name="purpose" class="form-control"></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Deadline</label>
          <input type="date" name="deadline" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Attach Document (optional)</label>
          <input type="file" name="document" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Send Request</button>
      </div>
    </form>
  </div>
</div>

<!-- Request Details Modal -->
<div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 shadow border-0">
      <div class="modal-header px-4 py-3" style="border-bottom:1px solid #f0f0f0;">
        <div>
          <div class="fw-bold fs-4 mb-0" id="detailsTitle">Request Title</div>
          <div class="text-muted small" id="detailsRequestId">Request ID: REQ-XXXX</div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body px-4 py-3">
        <div class="row mb-3">
          <div class="col-md-4 mb-2">
            <div class="fw-semibold">Department</div>
            <div class="text-muted" id="detailsDepartment">Department Name</div>
          </div>
          <div class="col-md-4 mb-2">
            <div class="fw-semibold">Priority</div>
            <span class="badge bg-warning text-dark px-3 py-2" id="detailsPriority">Medium</span>
          </div>
          <div class="col-md-4 mb-2">
            <div class="fw-semibold">Status</div>
            <span class="badge px-3 py-2" id="detailsStatus">pending</span>
          </div>
        </div>
        <div class="mb-3">
          <div class="fw-semibold">Description</div>
          <div class="text-muted" id="detailsDescription">Description here...</div>
        </div>
        <div class="mb-3">
          <div class="fw-semibold">Attached Documents</div>
          <div id="detailsAttachmentDiv">
            <a id="detailsAttachment" class="btn btn-link p-0" target="_blank">
              <i class="bi bi-paperclip"></i> View Attachment
            </a>
          </div>
          <div id="detailsNoAttachment" class="text-muted small">No attached documents.</div>
        </div>
        <div class="mb-3">
          <div class="fw-semibold">Status Timeline</div>
          <ul class="list-unstyled mb-0" id="detailsTimeline">
            <!-- Timeline items will be filled by JS -->
          </ul>
        </div>
        <div class="mb-2">
          <div class="fw-semibold">Comments</div>
          <div id="commentsList" class="mb-2" style="max-height:180px;overflow-y:auto;background:#f8fafc;border-radius:8px;padding:10px;">
            <!-- Comments will be filled by JS -->
          </div>
          <div class="input-group">
            <input type="text" class="form-control" id="commentInput" placeholder="Add a comment...">
            <button class="btn btn-primary" id="postCommentBtn" type="button">Post Comment</button>
          </div>
        </div>
        <div id="actionsArea" class="mt-3">
          <!-- Action buttons will be dynamically inserted here -->
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
  #detailsStatus.bg-success { background: #bbf7d0 !important; color: #22c55e !important; }
  #detailsStatus.bg-warning { background: #fef9c3 !important; color: #eab308 !important; }
  #detailsStatus.bg-danger { background: #fecaca !important; color: #ef4444 !important; }
  #detailsStatus.bg-primary { background: #dbeafe !important; color: #2563eb !important; }
  #detailsStatus.bg-secondary { background: #e5e7eb !important; color: #374151 !important; }
  #detailsTimeline li { margin-bottom: 6px; font-size: 1.05rem; }
  #detailsTimeline .bi-check-circle-fill { font-size: 1.1rem; vertical-align: middle; }
  #commentsList .comment-item { margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #ececec; }
  #commentsList .comment-item:last-child { border-bottom: none; }
  #commentsList .comment-author { font-weight: 600; color: #6366f1; }
  #commentsList .comment-date { font-size: 0.92em; color: #888; margin-left: 8px; }
  #commentsList .comment-text { margin-top: 2px; }
</style>
<script>
let allRequests = [];
let stats = { total: 0, pending: 0, approved: 0, rejected: 0, completed: 0 };
let currentRequestId = null;

function fetchLegalRequests() {
  fetch('https://administrative.viahale.com/api_endpoint/legaldocument.php')
    .then(res => res.json())
    .then(data => {
      allRequests = Array.isArray(data) ? data : [];
      updateStats();
      renderRequests();
    });
}

function updateStats() {
  stats = { total: 0, pending: 0, approved: 0, rejected: 0, completed: 0 };
  allRequests.forEach(req => {
    stats.total++;
    const status = (req.status || '').toLowerCase();
    if (status === 'pending' || status === 'in progress') stats.pending++;
    else if (status === 'approved') stats.approved++;
    else if (status === 'rejected') stats.rejected++;
    else if (status === 'completed') stats.completed++;
  });
  document.getElementById('statsCards').innerHTML = `
    <div class="stats-card">
      <div class="icon"><i class="bi bi-file-earmark-text"></i></div>
      <div class="label">Total Legal Documents</div>
      <div class="value">${stats.total}</div>
    </div>
    <div class="stats-card">
      <div class="icon"><i class="bi bi-hourglass-split"></i></div>
      <div class="label">Pending</div>
      <div class="value text-warning">${stats.pending}</div>
    </div>
    <div class="stats-card">
      <div class="icon"><i class="bi bi-check-circle"></i></div>
      <div class="label">Approved</div>
      <div class="value text-success">${stats.approved}</div>
    </div>
    <div class="stats-card">
      <div class="icon"><i class="bi bi-x-circle"></i></div>
      <div class="label">Rejected</div>
      <div class="value text-danger">${stats.rejected}</div>
    </div>
  `;
}

function renderRequests() {
  const list = document.getElementById('legalRequestsList');
  let filtered = allRequests;

  // Filtering
  const search = document.getElementById('searchInput').value.trim().toLowerCase();
  const dept = document.getElementById('departmentFilter').value;
  const type = document.getElementById('typeFilter').value;
  const dateFrom = document.getElementById('dateFrom').value;
  const dateTo = document.getElementById('dateTo').value;

  if (search) {
    filtered = filtered.filter(r =>
      (r.title && r.title.toLowerCase().includes(search)) ||
      (r.request_id && String(r.request_id).includes(search)) ||
      (r.requested_by && r.requested_by.toLowerCase().includes(search))
    );
  }
  if (dept) filtered = filtered.filter(r => (r.department || '') === dept);
  if (type) filtered = filtered.filter(r => (r.request_type || '') === type);
  if (dateFrom) filtered = filtered.filter(r => r.created_at && r.created_at >= dateFrom);
  if (dateTo) filtered = filtered.filter(r => r.created_at && r.created_at <= dateTo);

  if (filtered.length === 0) {
    list.innerHTML = '<div class="text-muted">No requests found.</div>';
    return;
  }

  list.innerHTML = `
    <div class="mb-3 fw-bold fs-5">Archived Requests (${filtered.length})</div>
    ${filtered.map(req => `
      <div class="request-card mb-3" data-request-id="${req.request_id || ''}">
        <span class="status-badge ${req.status ? req.status.toLowerCase().replace(' ', '-') : ''}">
          ${req.status || 'Pending'}
        </span>
        <div class="fw-bold">${req.title || ''}</div>
        <div class="mb-2">${req.description || ''}</div>
        <div class="d-flex flex-wrap gap-3 mb-2 align-items-center">
          ${req.deadline ? <span class="text-success"><i class="bi bi-clock"></i> Due: ${new Date(req.deadline).toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' })}</span> : ''}
          ${req.request_type ? <span class="badge bg-light text-dark me-2"><i class="bi bi-tag"></i> ${req.request_type}</span> : ''}
        </div>
        <div class="text-muted mb-1">
          From: <strong>${req.requested_by || ''}</strong>
          ${req.department ? (${req.department}) : ''}
        </div>
        <div class="text-muted small">
          Submitted: ${req.created_at ? new Date(req.created_at).toLocaleDateString() : ''}
        </div>
      </div>
    `).join('')}
  `;
}

// Filter events
document.getElementById('filterBtn').onclick = renderRequests;
document.getElementById('clearDates').onclick = function(e) {
  e.preventDefault();
  document.getElementById('dateFrom').value = '';
  document.getElementById('dateTo').value = '';
  renderRequests();
};
document.getElementById('searchInput').oninput = renderRequests;
document.getElementById('departmentFilter').onchange = renderRequests;
document.getElementById('typeFilter').onchange = renderRequests;

// Modal: Show/hide Other Request Type
document.addEventListener('DOMContentLoaded', function() {
  const reqType = document.getElementById('request_type_select');
  const otherType = document.getElementById('otherRequestTypeDesc');
  if (reqType && otherType) {
    reqType.addEventListener('change', function() {
      otherType.style.display = this.value === 'Others' ? '' : 'none';
    });
  }
});

// Handle legal request form submission via API
document.getElementById('legalRequestForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = this;
  const formData = new FormData(form);

  // Set defaults if not provided
  if (!formData.get('priority')) formData.set('priority', 'Medium');
  if (!formData.get('complexity_level')) formData.set('complexity_level', 'Low');
  if (!formData.get('urgency')) formData.set('urgency', 'Low');
  formData.set('action', 'add');

  fetch('https://administrative.viahale.com/api_endpoint/legaldocument.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success || data.status === 'success') {
      alert(data.message || 'Legal request submitted successfully!');
      form.reset();
      var modal = bootstrap.Modal.getInstance(document.getElementById('legalRequestModal'));
      modal.hide();
      fetchLegalRequests();
    } else {
      alert(data.message || 'Submission failed.');
    }
  })
  .catch(err => {
    alert('Submission error: ' + err);
  });
});

// Initial fetch
fetchLegalRequests();

function renderStatusTimeline(history) {
  if (!Array.isArray(history) || history.length === 0) {
    return <li><i class="bi bi-check-circle-fill text-secondary me-1"></i> No status history yet.</li>;
  }
  return history.map(h => `
    <li>
      <span class="badge ${getStatusBadgeClass(h.status)}">${h.status}</span>
      <span class="ms-2">${h.by} (${h.role})</span>
      <span class="text-muted ms-2">${new Date(h.date).toLocaleString()}</span>
    </li>
  `).join('');
}

function getStatusBadgeClass(status) {
  status = (status || '').toLowerCase();
  if (status === 'approved' || status === 'completed' || status === 'finalized') return 'bg-success';
  if (status === 'pending' || status === 'in progress' || status === 'under review') return 'bg-warning text-dark';
  if (status === 'rejected') return 'bg-danger';
  if (status === 'closed') return 'bg-secondary';
  return 'bg-secondary';
}

function renderComments(comments) {
  if (!comments || comments.length === 0) {
    return <div class="text-muted">No comments yet.</div>;
  }
  return comments.map(c => `
    <div class="comment-item">
      <span class="comment-author">${c.author || 'Anonymous'}</span>
      <span class="comment-date">${c.date ? new Date(c.date).toLocaleString() : ''}</span>
      <span class="badge bg-light text-dark ms-2">${c.role}</span>
      <div class="comment-text">${c.text || ''}</div>
    </div>
  `).join('');
}

// Request Details Modal (update this part)
document.getElementById('legalRequestsList').addEventListener('click', function(e) {
  const requestCard = e.target.closest('.request-card');
  if (!requestCard) return;

  const requestId = requestCard.getAttribute('data-request-id');
  currentRequestId = requestId; // Store for posting comments
  const requestData = allRequests.find(req => req.request_id == requestId);

  if (!requestData) return;

  document.getElementById('detailsTitle').textContent = requestData.title || '';
  document.getElementById('detailsRequestId').textContent = 'Request ID: ' + (requestData.request_id || '');
  document.getElementById('detailsPriority').textContent = requestData.priority || '';
  document.getElementById('detailsStatus').textContent = requestData.status || '';
  document.getElementById('detailsStatus').className = 'badge px-3 py-2 ' + getStatusBadgeClass(requestData.status);
  document.getElementById('detailsDepartment').textContent = requestData.department || '';
  document.getElementById('detailsDescription').textContent = requestData.description || '';
  if (requestData.document_path) {
    document.getElementById('detailsAttachmentDiv').style.display = '';
    document.getElementById('detailsNoAttachment').style.display = 'none';
    document.getElementById('detailsAttachment').href = requestData.document_path;
  } else {
    document.getElementById('detailsAttachmentDiv').style.display = 'none';
    document.getElementById('detailsNoAttachment').style.display = '';
  }

  // Dynamic Status Timeline
  document.getElementById('detailsTimeline').innerHTML = renderStatusTimeline(requestData.status_history);

  // Comments from API
  document.getElementById('commentsList').innerHTML = renderComments(requestData.comments);

  // Remove actions at the bottom of the modal
  document.getElementById('actionsArea').innerHTML = '';

  var modal = new bootstrap.Modal(document.getElementById('requestDetailsModal'));
  modal.show();
});

// Post Comment action
document.getElementById('postCommentBtn').onclick = function() {
  const commentText = document.getElementById('commentInput').value.trim();
  if (!commentText || !currentRequestId) return;

  fetch('https://administrative.viahale.com/api_endpoint/legaldocument.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'post_comment',
      request_id: currentRequestId,
      comment: commentText,
      created_by: window.currentEmployeeId // set this from session or JS variable
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('commentInput').value = '';
      // Refresh comments (assume API returns updated comments)
      fetch('https://administrative.viahale.com/api_endpoint/legaldocument.php?action=get_comments&request_id=' + currentRequestId)
        .then(res => res.json())
        .then(comments => {
          document.getElementById('commentsList').innerHTML = renderComments(comments);
        });
    } else {
      alert(data.message || 'Failed to post comment.');
    }
  })
  .catch(err => {
    alert('Error posting comment: ' + err);
  });
};

document.addEventListener('DOMContentLoaded', function() {
  const notifBtn = document.getElementById('notifBell');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifList = document.getElementById('notifList');
  const notifBadge = document.getElementById('notifBadge');

  // Dummy fetch function (replace with your API endpoint)
  function loadNotifications() {
    // Example: Replace with your actual notification API
    fetch('https://administrative.viahale.com/api_endpoint/notifications.php?user_id=EMP001')
      .then(res => res.json())
      .then(data => {
        notifList.innerHTML = '';
        let unread = 0;
        if (!data.notifications || data.notifications.length === 0) {
          notifList.innerHTML = '<li class="list-group-item text-muted">No notifications.</li>';
        } else {
          data.notifications.forEach(notif => {
            if (!notif.is_read) unread++;
            notifList.innerHTML += `
              <li class="list-group-item${notif.is_read == 0 ? ' bg-light' : ''}">
                <div class="d-flex justify-content-between align-items-center">
                  <span>${notif.message}</span>
                  <small class="text-muted">${notif.created_at}</small>
                </div>
              </li>
            `;
          });
        }
        notifBadge.style.display = unread > 0 ? 'inline-block' : 'none';
        notifBadge.textContent = unread;
      });
  }

  if (notifBtn && notifDropdown) {
    notifBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      notifDropdown.style.display = notifDropdown.style.display === 'none' ? 'block' : 'none';
      // Mark notifications as read (replace with your API)
      fetch('https://administrative.viahale.com/api_endpoint/notifications_mark_read.php?user_id=EMP001', { method: 'POST' })
        .then(() => loadNotifications());
    });

    document.addEventListener('click', function(e) {
      if (!notifDropdown.contains(e.target) && e.target !== notifBtn) {
        notifDropdown.style.display = 'none';
      }
    });
  }

  loadNotifications();
  setInterval(loadNotifications, 60000);
});
</script>
</body>
</html>