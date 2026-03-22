<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php");
  exit();
}

include('connection.php');

$success_message = '';

// Handle approval action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['approve_id'])) {
    $approveId = $_POST['approve_id'];

    // Fetch the record from bank table
    $fetch_sql = "SELECT * FROM bank WHERE id = ?";
    $stmt_fetch = $conn->prepare($fetch_sql);
    $stmt_fetch->bind_param("i", $approveId);
    $stmt_fetch->execute();
    $result_fetch = $stmt_fetch->get_result();
    $bank_record = $result_fetch->fetch_assoc();
    $stmt_fetch->close();

    if ($bank_record) {
      $expense_category = $bank_record['expense_categories'];
      $mode_of_payment = $bank_record['mode_of_payment'];
      $amount = $bank_record['amount'];
      $requested_department = $bank_record['requested_department'];
      $description = $bank_record['description'];
      $document = $bank_record['document'];
      $payment_due = $bank_record['payment_due'];
      $account_name = $bank_record['account_name'];
      $reference_id = $bank_record['reference_id'];
      $bank_name = $bank_record['bank_name'];
      $bank_account_name = $bank_record['bank_account_name'];
      $bank_account_number = $bank_record['bank_account_number'];

      // Create journal entry and post to general ledger using modern function
      require_once 'includes/accounting_functions.php';
      
      $disb_data = [
          'reference_id' => $reference_id,
          'amount' => $amount,
          'mode_of_payment' => $mode_of_payment . " (" . $bank_name . ")",
          'expense_category' => $expense_category,
          'department' => $requested_department
      ];
      
      try {
          createDisbursementJournalEntry($conn, $disb_data);
          error_log("Disbursement journal entry created for $reference_id via banktransfer.php");
      } catch (Exception $e) {
          error_log("Disbursement Error for $reference_id: " . $e->getMessage());
      }

      // Insert record into dr table with all required fields
      $insert_sql = "INSERT INTO dr (reference_id, account_name, requested_department, mode_of_payment, 
                    expense_categories, amount, description, document, payment_due, 
                    bank_account_number, bank_name, bank_account_name, 
                    ecash_provider, ecash_account_name, ecash_account_number, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '', 'disbursed')";
      
      $stmt_insert = $conn->prepare($insert_sql);
      $stmt_insert->bind_param("sssssdssssss", 
        $reference_id, 
        $account_name, 
        $requested_department, 
        $mode_of_payment,
        $expense_category,
        $amount,
        $description,
        $document,
        $payment_due,
        $bank_account_number,
        $bank_name,
        $bank_account_name
      );

      if ($stmt_insert->execute()) {
        // Delete the record from bank table
        $delete_sql = "DELETE FROM bank WHERE id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("i", $approveId);
        
        if ($stmt_delete->execute()) {
          $success_message = 'Bank Transfer Disbursement Approved!';
        } else {
          echo "<div class='bg-red-500 text-white p-4 rounded mb-4'>Error deleting record: " . $conn->error . "</div>";
        }
        $stmt_delete->close();
      } else {
        echo "<div class='bg-red-500 text-white p-4 rounded mb-4'>Error inserting record: " . $conn->error . "</div>";
      }
      $stmt_insert->close();
    }
  }
}

// Fetch the filtered records with limit
$sql = "SELECT * FROM bank ORDER BY id DESC";
$result = $conn->query($sql);
?>

<html>
<head>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
  <title>Bank Transfer Payout</title>
  <link rel="icon" href="logo.png" type="img">
</head>
<body class="bg-white">
  <?php include('sidebar.php'); ?>

  <!-- Breadcrumb -->
  <div class="overflow-y-auto h-full px-6">
    <div class="flex justify-between items-center px-6 py-6 font-poppins">
      <h1 class="text-2xl">Bank Transfer</h1>
      <div class="text-sm">
        <a href="dashboard.php?page=dashboard" class="text-black hover:text-blue-600">Home</a>
        /
        <a class="text-black">Disbursement</a>
        /
        <a href="banktransfer.php" class="text-blue-600 hover:text-blue-600">Bank Transfer</a>
      </div>
    </div>

    <div class="flex-1 bg-white p-6 h-full w-full">
      <div class="w-full">
        <!-- Success Message -->
        <?php if ($success_message): ?>
        <div id="successMessage" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-opacity duration-300">
          <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo $success_message; ?></span>
          </div>
        </div>
        <?php endif; ?>

        <div>
          <div class="flex items-center justify-between">
            <form method="GET" action="banktransfer.php" class="flex flex-wrap items-center gap-4 mb-4">
              <input type="text" id="searchInput" class="border px-3 py-2 rounded-full text-m font-medium text-black font-poppins focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Search here" />
              <div class="flex items-center space-x-2">
                <label for="dueDate" class="font-semibold">Payment Due:</label>
                <input type="date" id="dueDate" class="border border-gray-300 rounded-lg px-2 py-1 shadow-sm" />
              </div>
            </form>
          </div>
          <!-- Main content area -->
          <div class="bg-white rounded-xl shadow-md border border-gray-200 p-4 mb-6">
            <div class="flex items-center mt-4 mb-4 mx-4 space-x-3 text-purple-700">
              <i class="far fa-file-alt text-xl"></i>
              <h2 class="text-2xl font-poppins text-black">Bank Transfer Payout</h2>
            </div>
            <!-- TABLE -->
            <div class="overflow-x-auto w-full">
              <table class="w-full table-auto bg-white mt-4" id="bankTable">
                <thead>
                  <tr class="text-blue-800 uppercase text-sm leading-normal text-left">
                    <th class="pl-10 pr-6 py-2">ID</th>
                    <th class="px-6 py-2">Reference ID</th>
                    <th class="px-6 py-2">Account Name</th>
                    <th class="px-6 py-2">Department</th>
                    <th class="px-6 py-2">Mode of Payment</th>
                    <th class="px-6 py-2">Expense Categories</th>
                    <th class="px-6 py-2">Amount</th>
                    <th class="px-6 py-2">Bank Name</th>
                    <th class="px-6 py-2">Bank Account Name</th>
                    <th class="px-6 py-2">Bank Account Number</th>
                    <th class="px-6 py-2">Payment Due</th>
                    <th class="px-6 py-2">Actions</th>
                  </tr>
                </thead>
                <tbody class="text-gray-900 text-sm">
                  <?php
                  if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      // Always output payment_due as YYYY-MM-DD
                      $payment_due = $row['payment_due'] ? date('Y-m-d', strtotime($row['payment_due'])) : '';
                      echo "<tr class='hover:bg-violet-100'>";
                      echo "<td class='pl-10 pr-6 py-2'>{$row['id']}</td>";
                      echo "<td class='px-6 py-2'>{$row['reference_id']}</td>";
                      echo "<td class='px-6 py-2'>{$row['account_name']}</td>";
                      echo "<td class='px-6 py-2'>{$row['requested_department']}</td>";
                      echo "<td class='px-6 py-2'>{$row['mode_of_payment']}</td>";
                      echo "<td class='px-6 py-2'>{$row['expense_categories']}</td>";
                      echo "<td class='px-6 py-2'>" . number_format($row['amount'], 2) . "</td>";
                      echo "<td class='px-6 py-2'>{$row['bank_name']}</td>";
                      echo "<td class='px-6 py-2'>{$row['bank_account_name']}</td>";
                      echo "<td class='px-6 py-2'>{$row['bank_account_number']}</td>";
                      echo "<td class='px-6 py-2'>{$payment_due}</td>";
                      echo "<td class='px-6 py-2'>
                                <button type='button' class='disburseButton bg-green-500 text-white py-1 px-2 rounded-full font-semibold' data-approve-id='{$row['id']}'>Disburse</button>
                              </td>";
                      echo "</tr>";
                    }
                  } else {
                    echo "<tr><td colspan='12' class='text-center py-3'>No records found</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="mt-4 flex justify-between items-center">
          <div id="pageStatus" class="text-gray-700 font-bold"></div>
          <div class="flex">
            <button id="prevPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500">Previous</button>
            <button id="nextPage" class="bg-purple-500 text-white px-4 py-2 rounded mr-2 hover:bg-violet-200 hover:text-violet-700 border border-purple-500">Next</button>
          </div>
        </div>
        <div class="mt-6">
          <canvas id="pdf-viewer" width="600" height="400"></canvas>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal -->
  <div id="disburseModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded shadow-lg">
      <h2 class="text-xl font-bold mb-4">Confirm Disbursement</h2>
      <p>Are you sure you want to disburse this?</p>
      <div class="mt-4 flex items-center justify-end">
        <button id="cancelButton" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
        <form id="disburseForm" method="POST">
          <input type="hidden" name="approve_id" id="approveIdInput">
          <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded mt-4">Disburse</button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Auto-hide success message after 3 seconds
    <?php if ($success_message): ?>
    setTimeout(function() {
      const successMessage = document.getElementById('successMessage');
      if (successMessage) {
        successMessage.style.opacity = '0';
        setTimeout(() => successMessage.remove(), 300);
      }
    }, 3000);
    <?php endif; ?>

    // Modal logic
    document.querySelectorAll('.disburseButton').forEach(button => {
      button.addEventListener('click', function() {
        const approveId = this.getAttribute('data-approve-id');
        document.getElementById('approveIdInput').value = approveId;
        document.getElementById('disburseModal').classList.remove('hidden');
      });
    });

    document.getElementById('cancelButton').addEventListener('click', function() {
      document.getElementById('disburseModal').classList.add('hidden');
    });

    // Pagination/filter/search logic
    let table = document.querySelector("#bankTable");
    let tbody = table.querySelector("tbody");

    // Take a snapshot of all rows at page load
    let masterRows = Array.from(tbody.querySelectorAll("tr"));
    let currentPage = 1;
    const rowsPerPage = 10;

    function displayData(page) {
      let filteredRows = filterRows();
      tbody.innerHTML = "";
      let start = (page - 1) * rowsPerPage;
      let end = start + rowsPerPage;
      let paginatedRows = filteredRows.slice(start, end);

      if (paginatedRows.length === 0) {
        tbody.innerHTML = "<tr><td colspan='12' class='text-center py-4'>No records found</td></tr>";
      } else {
        paginatedRows.forEach(row => tbody.appendChild(row));
      }

      document.getElementById("prevPage").disabled = currentPage === 1;
      document.getElementById("nextPage").disabled = end >= filteredRows.length;
      const pageStatus = document.getElementById("pageStatus");
      const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
      pageStatus.textContent = `Page ${currentPage} of ${totalPages}`;
    }

    function filterRows() {
      const searchInput = document.getElementById("searchInput").value.toLowerCase();
      const dueDate = document.getElementById("dueDate").value;
      return masterRows.filter(row => {
        const cells = row.children;
        if (cells.length < 12) return false;
        const id = cells[0].textContent.toLowerCase();
        const referenceId = cells[1].textContent.toLowerCase();
        const accountName = cells[2].textContent.toLowerCase();
        const department = cells[3].textContent.toLowerCase();
        const modeOfPayment = cells[4].textContent.toLowerCase();
        const expenseCategories = cells[5].textContent.toLowerCase();
        const amount = cells[6].textContent.trim();
        const bankName = cells[7].textContent.toLowerCase();
        const bankAccountName = cells[8].textContent.toLowerCase();
        const bankAccountNumber = cells[9].textContent.trim();
        const rowDate = cells[10].textContent.trim();
        const matchSearch = (
          id.includes(searchInput) ||
          referenceId.includes(searchInput) ||
          accountName.includes(searchInput) ||
          department.includes(searchInput) ||
          modeOfPayment.includes(searchInput) ||
          expenseCategories.includes(searchInput) ||
          amount.includes(searchInput) ||
          bankName.includes(searchInput) ||
          bankAccountName.includes(searchInput) ||
          bankAccountNumber.includes(searchInput)
        );
        const matchDate = (!dueDate || rowDate === dueDate);
        return matchSearch && matchDate;
      });
    }

    function filterTable() {
      currentPage = 1;
      displayData(currentPage);
    }

    function prevPage() {
      if (currentPage > 1) {
        currentPage--;
        displayData(currentPage);
      }
    }

    function nextPage() {
      let filteredRows = filterRows();
      if (currentPage * rowsPerPage < filteredRows.length) {
        currentPage++;
        displayData(currentPage);
      }
    }

    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('dueDate').addEventListener('change', filterTable);
    document.getElementById('prevPage').addEventListener('click', prevPage);
    document.getElementById('nextPage').addEventListener('click', nextPage);

    window.onload = () => {
      displayData(currentPage);
    };
  </script>
</body>
</html>