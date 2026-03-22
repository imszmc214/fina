<?php
/**
 * MIGRATION SCRIPT - Run once on live server
 * Purpose: Add missing columns to budget_plans table
 * 
 * HOW TO USE:
 * 1. Upload this file to your live server (same folder as budget_planning.php)
 * 2. Visit: https://financials.viahale.com/migrate_budget_plans.php
 * 3. After success, DELETE this file from the server
 */

// Basic protection
define('ALLOWED_IP', ''); // Leave empty to allow any IP, or set to your IP e.g. '123.456.789.0'
if (!empty(ALLOWED_IP) && $_SERVER['REMOTE_ADDR'] !== ALLOWED_IP) {
    die('Access denied.');
}

require 'connection.php';

$results = [];
$has_error = false;

// ---- Define columns that must exist in budget_plans ----
$required_columns = [
    'project_revenue'    => "DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER description",
    'impact_percentage'  => "DECIMAL(5, 2) NOT NULL DEFAULT 0.00 AFTER project_revenue",
    'taxation_adj'       => "DECIMAL(15, 2) NOT NULL DEFAULT 0.00 AFTER impact_percentage",
    'start_date'         => "DATE NULL AFTER taxation_adj",
    'end_date'           => "DATE NULL AFTER start_date",
    'justification_doc'  => "VARCHAR(255) NULL AFTER status",
    'justification_blob' => "LONGBLOB NULL AFTER justification_doc",
];

foreach ($required_columns as $col => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM budget_plans LIKE '$col'");
    if ($check === false) {
        $results[] = ['column' => $col, 'status' => 'ERROR', 'msg' => 'Query failed: ' . $conn->error];
        $has_error = true;
        continue;
    }

    if ($check->num_rows === 0) {
        // Column is missing — add it
        $alter_sql = "ALTER TABLE budget_plans ADD COLUMN $col $definition";
        if ($conn->query($alter_sql)) {
            $results[] = ['column' => $col, 'status' => 'ADDED', 'msg' => 'Column added successfully.'];
        } else {
            $results[] = ['column' => $col, 'status' => 'ERROR', 'msg' => 'Failed to add: ' . $conn->error];
            $has_error = true;
        }
    } else {
        $results[] = ['column' => $col, 'status' => 'OK', 'msg' => 'Already exists, no changes needed.'];
    }
}

// ---- Display results ----
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Plans Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; background: #f5f5f5; }
        .card { background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #4f46e5; color: #fff; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .ADDED { color: #059669; font-weight: bold; }
        .OK { color: #2563eb; }
        .ERROR { color: #dc2626; font-weight: bold; }
        .summary { margin-top: 20px; padding: 15px; border-radius: 8px; }
        .success { background: #d1fae5; color: #065f46; }
        .failure { background: #fee2e2; color: #991b1b; }
        .warning { background: #fef3c7; color: #92400e; margin-top: 15px; padding: 10px; border-radius: 6px; font-size: 14px; }
    </style>
</head>
<body>
<div class="card">
    <h2>🛠️ Budget Plans — Database Migration</h2>
    <p>Database: <strong><?= htmlspecialchars($database) ?></strong></p>

    <table>
        <tr>
            <th>Column</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><code><?= htmlspecialchars($r['column']) ?></code></td>
            <td class="<?= $r['status'] ?>"><?= $r['status'] ?></td>
            <td><?= htmlspecialchars($r['msg']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="summary <?= $has_error ? 'failure' : 'success' ?>">
        <?php if ($has_error): ?>
            ❌ Migration completed with errors. Check the table above.
        <?php else: ?>
            ✅ Migration completed successfully! All required columns are present.
        <?php endif; ?>
    </div>

    <div class="warning">
        ⚠️ <strong>Security Notice:</strong> Please DELETE this file (<code>migrate_budget_plans.php</code>) from your server after migration is complete.
    </div>
</div>
</body>
</html>
