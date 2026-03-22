<?php
session_start();
include('connection.php');

// Check if user is admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Access denied. Please login first.");
}

echo "<h1>Setting up Budget Planning Module...</h1>";

// First, check if connection works
if (!$conn) {
    die("<p style='color: red;'>Database connection failed!</p>");
} else {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
}

// Create tables in correct order
$tables = [
    // Main budget plans table
    "CREATE TABLE IF NOT EXISTS budget_plans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_name VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL,
        sub_category VARCHAR(255) NOT NULL,
        plan_type ENUM('operational', 'capital', 'strategic', 'contingency') NOT NULL,
        plan_year INT NOT NULL,
        plan_month INT NULL,
        planned_amount DECIMAL(15,2) NOT NULL,
        gl_account_code VARCHAR(50),
        description TEXT,
        created_by VARCHAR(255) NOT NULL,
        approved_by VARCHAR(255) NULL,
        status ENUM('draft', 'pending_review', 'approved', 'archived', 'deleted') DEFAULT 'draft',
        justification_doc VARCHAR(255),
        source_plan_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        deleted_at TIMESTAMP NULL,
        INDEX idx_department (department),
        INDEX idx_year (plan_year),
        INDEX idx_status (status),
        INDEX idx_type (plan_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Snapshots table
    "CREATE TABLE IF NOT EXISTS budget_plan_snapshots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plan_id INT NOT NULL,
        snapshot_date DATE NOT NULL,
        planned_amount DECIMAL(15,2) NOT NULL,
        actual_amount DECIMAL(15,2) DEFAULT 0,
        variance DECIMAL(15,2) DEFAULT 0,
        snapshot_type VARCHAR(50) NOT NULL DEFAULT 'regular',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_plan_id (plan_id),
        INDEX idx_snapshot_date (snapshot_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    // Archive table
    "CREATE TABLE IF NOT EXISTS budget_plan_archive (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_plan_id INT,
        plan_name VARCHAR(255) NOT NULL,
        department VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL,
        sub_category VARCHAR(255) NOT NULL,
        plan_type ENUM('operational', 'capital', 'strategic', 'contingency') NOT NULL,
        plan_year INT NOT NULL,
        plan_month INT NULL,
        planned_amount DECIMAL(15,2) NOT NULL,
        gl_account_code VARCHAR(50),
        description TEXT,
        created_by VARCHAR(255) NOT NULL,
        created_at TIMESTAMP,
        archived_by VARCHAR(255),
        archive_reason VARCHAR(255),
        archive_notes TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        restored_by VARCHAR(255) NULL,
        restored_at TIMESTAMP NULL,
        restored_plan_id INT NULL,
        justification_doc VARCHAR(255)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $sql) {
    echo "<p>Creating table...</p>";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Success</p>";
    } else {
        echo "<p style='color: red;'>✗ Error: " . $conn->error . "</p>";
    }
}

// Check and create chart_of_accounts table if it doesn't exist
// Check and create chart_of_accounts_hierarchy table if it doesn't exist
$check_chart = "SHOW TABLES LIKE 'chart_of_accounts_hierarchy'";
$result = $conn->query($check_chart);
if ($result->num_rows == 0) {
    echo "<p>Creating chart_of_accounts_hierarchy table...</p>";
    // Note: This matches the structure in chart_of_accounts_hierarchy.sql
    $chart_sql = "CREATE TABLE IF NOT EXISTS chart_of_accounts_hierarchy (
        id INT AUTO_INCREMENT PRIMARY KEY,
        parent_id INT DEFAULT NULL,
        level INT(1) NOT NULL,
        code VARCHAR(20) UNIQUE,
        name VARCHAR(100) NOT NULL,
        type ENUM('Asset','Liability','Equity','Revenue','Expense'),
        description TEXT,
        balance DECIMAL(15,2) DEFAULT 0.00,
        allocated_amount DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_archived TINYINT(1) DEFAULT 0,
        INDEX idx_level (level),
        INDEX idx_type (type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($chart_sql) === TRUE) {
        echo "<p style='color: green;'>✓ chart_of_accounts table created</p>";
        
        // Insert sample chart of accounts - FIXED ARRAY SYNTAX
        $sample_accounts_sql = "INSERT INTO chart_of_accounts (code, name, type) VALUES
            ('50100', 'Fuel Expenses', 'Expense'),
            ('50101', 'Vehicle Maintenance', 'Expense'),
            ('50102', 'Driver Salaries', 'Expense'),
            ('50200', 'Marketing Expenses', 'Expense'),
            ('50300', 'Office Supplies', 'Expense'),
            ('50400', 'Software Licenses', 'Expense'),
            ('50500', 'Insurance Premiums', 'Expense'),
            ('60100', 'Vehicle Purchase', 'Asset'),
            ('60200', 'Equipment', 'Asset')";
        
        if ($conn->query($sample_accounts_sql) === TRUE) {
            echo "<p style='color: green;'>✓ Sample chart accounts inserted</p>";
        } else {
            echo "<p style='color: red;'>✗ Error inserting sample accounts: " . $conn->error . "</p>";
        }
    }
} else {
    echo "<p style='color: green;'>✓ chart_of_accounts table already exists</p>";
}

// Insert sample data for testing
echo "<h2>Inserting sample data...</h2>";

// First, check if we already have sample data
$check_data = "SELECT COUNT(*) as count FROM budget_plans";
$result = $conn->query($check_data);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Sample plans - SINGLE SQL STRING (not array)
    $sample_plans_sql = "INSERT INTO budget_plans (plan_name, department, category, sub_category, plan_type, plan_year, plan_month, planned_amount, gl_account_code, description, created_by, status, justification_doc) VALUES
        ('Q1 Vehicle Maintenance', 'Logistic-1', 'Vehicle Operations', 'Vehicle Maintenance', 'operational', 2024, 3, 50000.00, '50101', 'Quarterly vehicle maintenance budget for Logistic-1', 'Admin User', 'approved', 'maintenance_plan.pdf'),
        ('Annual Driver Training', 'Human Resource-1', 'Driver Costs', 'Driver Training', 'operational', 2024, NULL, 100000.00, '50102', 'Annual driver training and certification program', 'Admin User', 'approved', 'training_proposal.docx'),
        ('Q2 Fuel Budget', 'Logistic-1', 'Vehicle Operations', 'Fuel Expenses', 'operational', 2024, 6, 75000.00, '50100', 'Second quarter fuel budget for fleet vehicles', 'Admin User', 'draft', 'fuel_budget.xlsx'),
        ('App Platform Upgrade', 'Technology', 'Technology & Platform', 'App Platform Fees', 'capital', 2024, NULL, 250000.00, '50400', 'Annual platform subscription and upgrade costs', 'Admin User', 'pending_review', 'platform_quote.pdf')";

    if ($conn->query($sample_plans_sql) === TRUE) {
        echo "<p style='color: green;'>✓ Sample data inserted</p>";
        
        // Get the last inserted ID (will be the first one)
        $last_id = $conn->insert_id;
        
        // Create snapshots for each plan
        for ($i = 0; $i < 4; $i++) {
            $plan_id = $last_id + $i;
            $planned_amount = [50000.00, 100000.00, 75000.00, 250000.00][$i];
            
            $snapshot_sql = "INSERT INTO budget_plan_snapshots (plan_id, snapshot_date, planned_amount, snapshot_type) 
                            VALUES ($plan_id, CURDATE(), $planned_amount, 'initial')";
            
            if ($conn->query($snapshot_sql) === TRUE) {
                echo "<p style='color: green;'>✓ Created snapshot for plan ID $plan_id</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating snapshot: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ Error inserting sample data: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Sample data already exists (" . $row['count'] . " plans found)</p>";
}

// Create uploads directory
$upload_dir = 'uploads/budget_justifications/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color: green;'>✓ Created uploads directory</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create uploads directory</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Uploads directory already exists</p>";
}

echo "<h2 style='color: green;'>Setup completed successfully!</h2>";
echo "<p><a href='test_ajax.php'>Test AJAX Connection</a></p>";
echo "<p><a href='budget_planning.php'>Go to Budget Planning Module</a></p>";

$conn->close();
?>