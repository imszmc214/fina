<?php
session_start();
include('connection.php');

// Handle view request
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['reference_id'])) {
    header('Content-Type: application/json');
    
    $reference_id = $_GET['reference_id'];
    $type = $_GET['type'] ?? 'budget';
    
    $tableInfo = [
        'budget' => [
            'table' => 'budget_request',
            'refCol' => 'reference_id',
            'dateCol' => 'created_at',
            'catCol' => 'expense_categories',
            'descCol' => 'description',
            'docCol' => 'document',
            'amtCol' => 'amount',
            'userCol' => 'account_name',
            'statusCol' => 'status',
            'deptCol' => 'requested_department',
            'modeCol' => 'mode_of_payment',
            'timePeriodCol' => 'time_period',
            'paymentDueCol' => 'payment_due',
            'bankNameCol' => 'bank_name',
            'bankAccNameCol' => 'bank_account_name',
            'bankAccNumCol' => 'bank_account_number',
            'ecashProviderCol' => 'ecash_provider',
            'ecashAccNameCol' => 'ecash_account_name',
            'ecashAccNumCol' => 'ecash_account_number'
        ],
        'petty_cash' => [
            'table' => 'pettycash',
            'refCol' => 'reference_id',
            'dateCol' => 'created_at',
            'catCol' => 'expense_categories',
            'descCol' => 'description',
            'docCol' => 'document',
            'amtCol' => 'amount',
            'userCol' => 'account_name',
            'statusCol' => 'status',
            'deptCol' => 'requested_department',
            'modeCol' => 'mode_of_payment',
            'timePeriodCol' => 'time_period',
            'paymentDueCol' => 'payment_due',
            'bankNameCol' => 'bank_name',
            'bankAccNameCol' => 'bank_account_name',
            'bankAccNumCol' => 'bank_account_number',
            'ecashProviderCol' => 'ecash_provider',
            'ecashAccNameCol' => 'ecash_account_name',
            'ecashAccNumCol' => 'ecash_account_number'
        ],
        'payable' => [
            'table' => 'accounts_payable',
            'refCol' => 'invoice_id',
            'dateCol' => 'created_at',
            'catCol' => 'department',
            'descCol' => 'description',
            'docCol' => 'document',
            'amtCol' => 'amount',
            'userCol' => 'account_name',
            'statusCol' => 'status',
            'deptCol' => 'department',
            'modeCol' => 'payment_mode',
            'timePeriodCol' => 'payment_terms',
            'paymentDueCol' => 'due_date',
            'bankNameCol' => 'bank_name',
            'bankAccNameCol' => 'account_name',
            'bankAccNumCol' => 'account_number',
            'ecashProviderCol' => 'ecash_provider',
            'ecashAccNameCol' => 'ecash_account_name',
            'ecashAccNumCol' => 'ecash_account_number'
        ],
        'emergency' => [
            'table' => 'pa',
            'refCol' => 'reference_id',
            'dateCol' => 'requested_at',
            'catCol' => 'expense_categories',
            'descCol' => 'description',
            'docCol' => 'document',
            'amtCol' => 'amount',
            'userCol' => 'account_name',
            'statusCol' => 'status',
            'deptCol' => 'requested_department',
            'modeCol' => 'mode_of_payment',
            'timePeriodCol' => 'time_period',
            'paymentDueCol' => 'payment_due',
            'bankNameCol' => 'bank_name',
            'bankAccNameCol' => 'bank_account_name',
            'bankAccNumCol' => 'bank_account_number',
            'ecashProviderCol' => 'ecash_provider',
            'ecashAccNameCol' => 'ecash_account_name',
            'ecashAccNumCol' => 'ecash_account_number'
        ]
    ];
    
    $info = $tableInfo[$type] ?? $tableInfo['budget'];
    $table = $info['table'];
    $refCol = $info['refCol'];
    
    // LEFT JOIN budget_proposals to get project_objectives
    // Use explicit column selection to avoid conflicts
    $sql = "SELECT br.*, bp.project_objectives as bp_project_objectives
            FROM $table br 
            LEFT JOIN budget_proposals bp ON br.reference_id = bp.reference_id 
            WHERE br.$refCol = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $reference_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Map all columns to consistent names for frontend
        $mappedRow = [
            'reference_id' => $row[$info['refCol']] ?? '',
            'account_name' => $row[$info['userCol']] ?? '',
            'requested_department' => $row[$info['deptCol']] ?? '',
            'expense_categories' => $row[$info['catCol']] ?? '',
            'description' => $row[$info['descCol']] ?? '',
            'mode_of_payment' => $row[$info['modeCol']] ?? '',
            'amount' => $row[$info['amtCol']] ?? '',
            'document' => $row[$info['docCol']] ?? '',
            'time_period' => $row[$info['timePeriodCol']] ?? '',
            'payment_due' => $row[$info['paymentDueCol']] ?? '',
            'bank_name' => $row[$info['bankNameCol']] ?? '',
            'bank_account_name' => $row[$info['bankAccNameCol']] ?? '',
            'bank_account_number' => $row[$info['bankAccNumCol']] ?? '',
            'ecash_provider' => $row[$info['ecashProviderCol']] ?? '',
            'ecash_account_name' => $row[$info['ecashAccNameCol']] ?? '',
            'ecash_account_number' => $row[$info['ecashAccNumCol']] ?? '',
            'status' => $row[$info['statusCol']] ?? 'pending',
            'created_at' => $row[$info['dateCol']] ?? '',
            'detailed_breakdown' => $row['detailed_breakdown'] ?? '[]',
            'project_objectives' => $row['bp_project_objectives'] ?? ''
        ];
        
        echo json_encode(['success' => true, 'request' => $mappedRow]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'Request not found']);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

// Original code for loading table
$type = $_GET['type'] ?? 'budget';
$tab = $_GET['tab'] ?? 'recent';
$search = $_GET['search'] ?? '';
$req_name = $_SESSION['givenname'] . ' ' . $_SESSION['surname'];

$tableInfo = [
    'budget' => [
        'table' => 'budget_request',
        'refCol' => 'reference_id',
        'dateCol' => 'created_at',
        'catCol' => 'expense_categories',
        'descCol' => 'description',
        'docCol' => 'document',
        'amtCol' => 'amount',
        'userCol' => 'account_name',
        'statusCol' => 'status',
        'deptCol' => 'requested_department'
    ],
    'petty_cash' => [
        'table' => 'pettycash',
        'refCol' => 'reference_id',
        'dateCol' => 'created_at',
        'catCol' => 'expense_categories',
        'descCol' => 'description',
        'docCol' => 'document',
        'amtCol' => 'amount',
        'userCol' => 'account_name',
        'statusCol' => 'status',
        'deptCol' => 'requested_department'
    ],
    'payable' => [
        'table' => 'accounts_payable',
        'refCol' => 'invoice_id',
        'dateCol' => 'created_at',
        'catCol' => 'department',
        'descCol' => 'description',
        'docCol' => 'document',
        'amtCol' => 'amount',
        'userCol' => 'account_name',
        'statusCol' => 'status',
        'deptCol' => 'department'
    ],
    'emergency' => [
        'table' => 'pa',
        'refCol' => 'reference_id',
        'dateCol' => 'requested_at',
        'catCol' => 'expense_categories',
        'descCol' => 'description',
        'docCol' => 'document',
        'amtCol' => 'amount',
        'userCol' => 'account_name',
        'statusCol' => 'status',
        'deptCol' => 'requested_department'
    ]
];

$info = $tableInfo[$type];
$table = $info['table'];
$refCol = $info['refCol'];
$dateCol = $info['dateCol'];
$catCol = $info['catCol'];
$descCol = $info['descCol'];
$docCol = $info['docCol'];
$amtCol = $info['amtCol'];
$userCol = $info['userCol'];
$statusCol = $info['statusCol'];
$deptCol = $info['deptCol'] ?? 'requested_department';

$whereUser = "$userCol = '".$conn->real_escape_string($req_name)."'";
$searchClause = $search ? "AND ($refCol LIKE '%$search%' OR $catCol LIKE '%$search%' OR $amtCol LIKE '%$search%' OR $descCol LIKE '%$search%')" : "";

if ($tab == 'recent') {
    $where = "$whereUser AND TIMESTAMPDIFF(DAY, $dateCol, NOW()) < 7 $searchClause";
} else {
    $where = "$whereUser AND TIMESTAMPDIFF(DAY, $dateCol, NOW()) >= 7 $searchClause";
}

$sql = "SELECT * FROM $table WHERE $where ORDER BY $dateCol DESC LIMIT 20";
$result = $conn->query($sql);

echo '<div class="overflow-x-auto w-full">';
echo '<table class="w-full table-auto bg-white mt-4 rounded-xl border">';
echo '<thead>
<tr class="text-blue-800 uppercase text-sm leading-normal text-left">
    <th class="pl-6 py-3">Ref. ID</th>
    <th class="px-4 py-3">Department</th>
    <th class="px-4 py-3">Category</th>
    <th class="px-4 py-3">Amount</th>
    <th class="px-4 py-3">Description</th>
    <th class="px-4 py-3">Status</th>
    <th class="px-4 py-3">Created</th>
    <th class="px-4 py-3">Actions</th>
</tr>
</thead>';
echo '<tbody class="text-gray-900 text-sm font-light">';
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $refid = $row[$refCol] ?? '';
        $dept = $row[$deptCol] ?? '';
        $cat = $row[$catCol] ?? '';
        $amt = $row[$amtCol] ?? '';
        $desc = $row[$descCol] ?? '';
        $doc = $row[$docCol] ?? '';
        $status = $row[$statusCol] ?? 'pending';
        $created = $row[$dateCol] ?? '';
        $showAppeal = ($status === 'rejected');
        
        // Determine status badge class
        $statusBadgeClass = 'status-badge ';
        switch(strtolower($status)) {
            case 'pending':
                $statusBadgeClass .= 'status-pending';
                break;
            case 'approved':
                $statusBadgeClass .= 'status-approved';
                break;
            case 'rejected':
                $statusBadgeClass .= 'status-rejected';
                break;
            default:
                $statusBadgeClass .= 'status-pending';
        }
        
        echo "<tr class='border-b hover:bg-gray-50'>
            <td class='pl-6 py-4 font-mono text-xs'>" . htmlspecialchars($refid) . "</td>
            <td class='px-4 py-4'>" . htmlspecialchars($dept) . "</td>
            <td class='px-4 py-4'>" . htmlspecialchars($cat) . "</td>
            <td class='px-4 py-4 font-semibold'>₱" . number_format($amt, 2) . "</td>
            <td class='px-4 py-4 max-w-xs truncate' title='" . htmlspecialchars($desc) . "'>" . htmlspecialchars($desc) . "</td>
            <td class='px-4 py-4'>
                <span class='$statusBadgeClass'>" . ucfirst($status) . "</span>
            </td>
            <td class='px-4 py-4'>" . ($created ? date('Y-m-d H:i', strtotime($created)) : '') . "</td>
            <td class='px-4 py-4 flex gap-2'>";
        
        // Add View button with document indicator
        $hasDocument = !empty($doc);
        $viewBtnText = $hasDocument ? 
            "<i class='fas fa-eye mr-1'></i> View <span class='ml-1 text-green-600' title='Has document'>📄</span>" : 
            "<i class='fas fa-eye mr-1'></i> View";
        
        echo "<button class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded view-btn flex items-center gap-1 text-sm' 
                onclick='viewRequest(\"" . htmlspecialchars($refid) . "\", \"$type\")'>
                $viewBtnText
              </button>";
        
        if ($showAppeal) {
            echo '<button class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded appeal-btn flex items-center gap-1 text-sm" 
                    onclick="alert(\'Appeal feature to be implemented.\')">
                    <i class="fas fa-undo mr-1"></i> Appeal
                  </button>';
        }
        
        echo "</td></tr>";
    }
} else {
    echo "<tr><td colspan='8' class='text-center py-8 text-gray-500'>No records found</td></tr>";
}
echo '</tbody>';
echo '</table>';
echo '</div>';

$conn->close();
?>