<?php
$c = new mysqli('localhost', 'root', '', 'fina_db');
$c->query("ALTER TABLE budget_request ADD COLUMN detailed_breakdown LONGTEXT AFTER description");
if ($c->error) {
    echo "Error: " . $c->error;
} else {
    echo "Column detailed_breakdown added successfully.";
}
