<?php
require 'connection.php';

echo "Checking budget_plans table structure...\n";
$columns_to_add = [
    'project_revenue' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER description",
    'impact_percentage' => "DECIMAL(5, 2) DEFAULT 0.00 AFTER project_revenue",
    'taxation_adj' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER impact_percentage",
    'justification_doc' => "VARCHAR(255) NULL AFTER status",
    'justification_blob' => "LONGBLOB NULL AFTER justification_doc"
];

foreach ($columns_to_add as $column => $def) {
    $check = $conn->query("SHOW COLUMNS FROM budget_plans LIKE '$column'");
    if ($check && $check->num_rows == 0) {
        echo "Adding column $column...\n";
        $sql = "ALTER TABLE budget_plans ADD $column $def";
        if ($conn->query($sql)) {
            echo "Successfully added $column\n";
        } else {
            echo "Error adding $column: " . $conn->error . "\n";
        }
    } else {
        echo "Column $column already exists.\n";
    }
}

echo "\nDone.\n";
?>
