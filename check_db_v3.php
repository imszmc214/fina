<?php
require 'connection.php';
echo "Current Database: " . $database . "\n";
echo "--- Table: budget_proposals ---\n";
$r = $conn->query('DESCRIBE budget_proposals');
if (!$r) {
    echo "Error: " . $conn->error . "\n";
} else {
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
}
echo "\n--- Table: budget_plans ---\n";
$r = $conn->query('DESCRIBE budget_plans');
if (!$r) {
    echo "Error: " . $conn->error . "\n";
} else {
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
}
?>
