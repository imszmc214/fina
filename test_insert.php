<?php
require 'connection.php';
$batch_id = 'TEST-' . time();
$title = 'Test Plan';
$user_dept = 'Financials';
$row_cat = 'Miscellaneous';
$row_sub = 'Uncategorized';
$p_type = 'operational';
$year = 2026;
$amount = 1000.00;
$gl_code = 'TEST-GL';
$justification = 'Test justification';
$user_name = 'Test User';
$docs = json_encode(['test.pdf']);
$null_blob = null;
$project_revenue = 0;
$impact_percentage = 0;
$taxation_adj = 0;
$start = '2026-01-01';
$end = '2026-12-31';
$batch_time = date('Y-m-d H:i:s');

$plan_sql = "INSERT INTO budget_plans (
    plan_code, plan_name, department, category, sub_category, 
    plan_type, plan_year, planned_amount, gl_account_code, description, 
    status, created_by, justification_doc, justification_blob, project_revenue, impact_percentage, taxation_adj, start_date, end_date, created_at, updated_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_review', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($plan_sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ssssssidssssbdddssss", 
    $batch_id, $title, $user_dept, $row_cat, $row_sub, $p_type, $year, $amount, $gl_code, $justification, 
    $user_name, $docs, $null_blob, $project_revenue, $impact_percentage, $taxation_adj, $start, $end, $batch_time, $batch_time
);

if ($stmt->execute()) {
    echo "Insert successful!\n";
} else {
    echo "Execute failed: " . $stmt->error . "\n";
}
?>
