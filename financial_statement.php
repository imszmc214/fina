<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
include('connection.php');

/** 
 * SECURITY SETTINGS 
 * Fetching the current user's PIN from the database
 */
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT pin FROM userss WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$u_data = $res->fetch_assoc();
$SECURE_PIN = $u_data['pin'] ?? "1234"; // Default fallback if not set
$stmt->close();

// Helper to get detailed data grouped by Section (Level 2) then Account (Level 3)
function getDetailedReportData($conn, $l1_id, $from, $to) {
    if (!$from) $from = date('Y-01-01');
    if (!$to) $to = date('Y-12-31');

    $sql = "SELECT 
                l2.id as section_id, l2.name AS section_name,
                l3.id as account_id, l3.name AS account_name,
                SUM(CASE 
                    WHEN ? IN (1, 5) THEN (gl.debit_amount - gl.credit_amount)
                    ELSE (gl.credit_amount - gl.debit_amount)
                END) AS balance
            FROM chart_of_accounts_hierarchy l1
            JOIN chart_of_accounts_hierarchy l2 ON l2.parent_id = l1.id AND l2.level = 2
            JOIN chart_of_accounts_hierarchy l3 ON l3.parent_id = l2.id AND l3.level = 3
            LEFT JOIN chart_of_accounts_hierarchy l4 ON l4.parent_id = l3.id AND l4.level = 4
            JOIN general_ledger gl ON (gl.gl_account_id = l4.id OR (l4.id IS NULL AND gl.gl_account_id = l3.id))
            WHERE l1.id = ? AND l2.is_archived = 0
              AND (gl.transaction_date BETWEEN ? AND ?)
            GROUP BY l2.id, l3.id
            HAVING balance != 0
            ORDER BY l2.code, l3.code";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $l1_id, $l1_id, $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $sections = [];
    $total = 0;
    while ($row = $res->fetch_assoc()) {
        $sid = $row['section_id'];
        if (!isset($sections[$sid])) {
            $sections[$sid] = ['name' => $row['section_name'], 'accounts' => [], 'subtotal' => 0];
        }
        $sections[$sid]['accounts'][] = $row;
        $sections[$sid]['subtotal'] += $row['balance'];
        $total += $row['balance'];
    }
    return ['sections' => $sections, 'total' => $total];
}

// Helper for multi-year comparison
function getMultiYearReportData($conn, $l1_id, $from, $to) {
    $startYear = (int)date('Y', strtotime($from));
    $endYear = (int)date('Y', strtotime($to));
    $years = [];
    for ($y = $endYear; $y >= $startYear; $y--) {
        $years[] = $y;
    }

    $all_data = [];
    foreach ($years as $year) {
        $y_from = max($from, "$year-01-01");
        $y_to = min($to, "$year-12-31");
        $all_data[$year] = getDetailedReportData($conn, $l1_id, $y_from, $y_to);
    }

    $sections = [];
    foreach ($all_data as $year => $data) {
        foreach ($data['sections'] as $sid => $sec) {
            if (!isset($sections[$sid])) {
                $sections[$sid] = ['name' => $sec['name'], 'accounts' => [], 'subtotals' => array_fill_keys($years, 0)];
            }
            foreach ($sec['accounts'] as $acc) {
                $aid = $acc['account_id'];
                if (!isset($sections[$sid]['accounts'][$aid])) {
                    $sections[$sid]['accounts'][$aid] = ['name' => $acc['account_name'], 'balances' => array_fill_keys($years, 0)];
                }
                $sections[$sid]['accounts'][$aid]['balances'][$year] = $acc['balance'];
            }
            $sections[$sid]['subtotals'][$year] = $sec['subtotal'];
        }
    }
    
    $totals = array_fill_keys($years, 0);
    foreach($all_data as $year => $data) {
        $totals[$year] = $data['total'];
    }

    return ['years' => $years, 'sections' => $sections, 'totals' => $totals];
}

// Cash Flow Engine (Direct Method)
function getCashFlowData($conn, $from, $to) {
    if (!$from) $from = date('Y-01-01');
    if (!$to) $to = date('Y-12-31');
    $cash_ids = "97, 98, 99, 216, 217, 223, 224";

    $sql = "SELECT 
                gl_other.account_type,
                gl_other.gl_account_id,
                gl_other.gl_account_name,
                SUM(CASE WHEN gl_cash.debit_amount > 0 THEN gl_cash.debit_amount ELSE -gl_cash.credit_amount END) as net_cash_flow
            FROM general_ledger gl_cash
            INNER JOIN general_ledger gl_other ON gl_cash.journal_entry_id = gl_other.journal_entry_id
            WHERE gl_cash.gl_account_id IN ($cash_ids)
              AND gl_other.gl_account_id NOT IN ($cash_ids)
              AND (gl_cash.transaction_date BETWEEN ? AND ?)
            GROUP BY gl_other.account_type, gl_other.gl_account_id, gl_other.gl_account_name";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $from, $to);
    $stmt->execute();
    $res = $stmt->get_result();

    $cf = [
        'operating' => ['in' => [], 'out' => [], 'total' => 0],
        'investing' => ['in' => [], 'out' => [], 'total' => 0],
        'financing' => ['in' => [], 'out' => [], 'total' => 0]
    ];

    while ($row = $res->fetch_assoc()) {
        $val = (float)$row['net_cash_flow'];
        $type = $row['account_type'];
        $cat = 'operating'; // Default

        // Categorization logic based on Chart of Accounts structure
        if ($type == 'Asset' && !in_array($row['gl_account_id'], [97, 98, 99, 216, 217, 223, 224, 218])) {
            // Purchases of Equipment/Technology are Investing
            $cat = 'investing';
        } else if ($type == 'Equity' || ($type == 'Liability' && $row['gl_account_id'] > 300)) { 
            // Simplified: Equity and Long Term Debt for Financing
            $cat = 'financing';
        }

        $sub = ($val > 0) ? 'in' : 'out';
        $cf[$cat][$sub][] = ['name' => $row['gl_account_name'], 'amount' => abs($val)];
        $cf[$cat]['total'] += $val;
    }

    // Cash at Beginning (Sum of balances before $from)
    $sql_beg = "SELECT SUM(debit_amount - credit_amount) as beg FROM general_ledger 
                WHERE gl_account_id IN ($cash_ids) AND transaction_date < ?";
    $stmt_beg = $conn->prepare($sql_beg);
    $stmt_beg->bind_param("s", $from);
    $stmt_beg->execute();
    $beg_data = $stmt_beg->get_result()->fetch_assoc();
    $cf['beginning_balance'] = (float)($beg_data['beg'] ?? 0);
    $cf['net_change'] = $cf['operating']['total'] + $cf['investing']['total'] + $cf['financing']['total'];
    $cf['ending_balance'] = $cf['beginning_balance'] + $cf['net_change'];

    return $cf;
}

function getMultiYearCashFlowData($conn, $from, $to) {
    $startYear = (int)date('Y', strtotime($from));
    $endYear = (int)date('Y', strtotime($to));
    $years = [];
    for ($y = $endYear; $y >= $startYear; $y--) {
        $years[] = $y;
    }

    $all_data = [];
    foreach ($years as $year) {
        $y_from = max($from, "$year-01-01");
        $y_to = min($to, "$year-12-31");
        $all_data[$year] = getCashFlowData($conn, $y_from, $y_to);
    }
    return ['years' => $years, 'data' => $all_data];
}

/**
 * -------------------------------------------------------------------
 * AJAX HANDLER: SAVE REPORT METADATA
 * -------------------------------------------------------------------
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'save_report') {
        try {
            $type = $_POST['type'];
            $from = $_POST['from_date'];
            $to = $_POST['to_date'];
            $format = $_POST['format'] ?? 'pdf';
            
            $f_obj = new DateTime($from);
            $t_obj = new DateTime($to);
            $period_label = $f_obj->format('M d, Y') . " - " . $t_obj->format('M d, Y');

            if ($f_obj->format('m-d') == '01-01' && $t_obj->format('m-d') == '12-31') {
                $period_label = "Annual " . $t_obj->format('Y');
            } else if ($f_obj->format('d') == '01' && $t_obj->format('Y-m-t') == $to) {
                $period_label = $t_obj->format('F Y');
            }
            
            $type_label = ($type == 'income' ? 'Income Statement' : ($type == 'balance' ? 'Balance Sheet' : ($type == 'trial' ? 'Trial Balance' : ($type == 'cashflow' ? 'Cash Flow Statement' : ucfirst($type)))));
            $name = "$type_label - $period_label";
            
            $stmt = $conn->prepare("INSERT INTO saved_reports (report_name, report_type, from_date, to_date, format) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $type, $from, $to, $format);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit();
    }
}

/**
 * -------------------------------------------------------------------
 * UNITARY REPORT ENGINE (HTML PREVIEW & EXPORTS)
 * -------------------------------------------------------------------
 */
if (isset($_GET['generate_report'])) {
    $report_type = $_GET['type'] ?? 'income';
    $report_format = $_GET['format'] ?? 'pdf';
    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-t');
    $is_download = isset($_GET['download']) && $_GET['download'] == '1';

    // Fetch data via Multi-Year Engine
    if ($report_type == 'income') {
        $r_revenue = getMultiYearReportData($conn, 4, $from_date, $to_date);
        $r_expenses = getMultiYearReportData($conn, 5, $from_date, $to_date);
        $years = $r_revenue['years'];
        $report_title = "Income Statement";
    } else if ($report_type == 'balance') {
        $r_assets = getMultiYearReportData($conn, 1, $from_date, $to_date);
        $r_liabilities = getMultiYearReportData($conn, 2, $from_date, $to_date);
        $r_equity_base = getMultiYearReportData($conn, 3, $from_date, $to_date);
        $years = $r_assets['years'];
        $report_title = "Balance Sheet";

        // Retained Earnings (simplified for multi-column)
        $r_retained_earnings = array_fill_keys($years, 0);
        foreach($years as $year) {
            $y_from = max($from_date, "$year-01-01");
            $y_to = min($to_date, "$year-12-31");
            $temp_rev = getDetailedReportData($conn, 4, $y_from, $y_to);
            $temp_exp = getDetailedReportData($conn, 5, $y_from, $y_to);
            $r_retained_earnings[$year] = $temp_rev['total'] - $temp_exp['total'];
        }
    } else if ($report_type == 'trial') {
        $report_title = "Trial Balance";
        $years = [(int)date('Y', strtotime($to_date))]; // Trial Balance is usually point-in-time
        $sql = "SELECT gl_account_name AS account, gl_account_id,
                       SUM(debit_amount) AS debit, 
                       SUM(credit_amount) AS credit 
                FROM general_ledger 
                WHERE transaction_date BETWEEN ? AND ?
                GROUP BY gl_account_id, gl_account_name
                HAVING (SUM(debit_amount) != 0 OR SUM(credit_amount) != 0)
                ORDER BY gl_account_id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $from_date, $to_date);
        $stmt->execute();
        $r_trial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else if ($report_type == 'cashflow') {
        $report_title = "Cash Flow Statement";
        $r_cashflow = getMultiYearCashFlowData($conn, $from_date, $to_date);
        $years = $r_cashflow['years'];
    }

    if ($is_download) {
        if ($report_format == 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="'.$report_type.'_report_'.date('Ymd').'.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ["ViaHale Financial Report"]);
            fputcsv($output, ["Type:", $report_title, "Period:", $from_date . " to " . $to_date]);
            fputcsv($output, []);
            fputcsv($output, ["Category", "Amount"]);
            if ($report_type == 'income') {
                fputcsv($output, ["REVENUE"]);
                foreach ($r_revenue['categories'] as $cat) fputcsv($output, [$cat['category'], $cat['balance']]);
                fputcsv($output, ["Total Revenue", $r_total_revenue]);
                fputcsv($output, ["EXPENSES"]);
                foreach ($r_expenses['categories'] as $cat) fputcsv($output, [$cat['category'], $cat['balance']]);
                fputcsv($output, ["Net Profit", $r_net_profit]);
            } else {
                fputcsv($output, ["ASSETS"]);
                foreach ($r_assets['categories'] as $cat) fputcsv($output, [$cat['category'], $cat['balance']]);
                fputcsv($output, ["LIABILITIES"]);
                foreach ($r_liabilities['categories'] as $cat) fputcsv($output, [$cat['category'], $cat['balance']]);
                fputcsv($output, ["Retained Earnings", $r_retained_earnings]);
            }
            fclose($output); exit();
        } else if ($report_format == 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="'.$report_type.'_report_'.date('Ymd').'.xls"');
        } else if ($report_format == 'pdf') {
            // Force download for the HTML-based "PDF" to prevent page navigation
            header('Content-Type: text/html');
            header('Content-Disposition: attachment; filename="'.$report_type.'_report_'.date('Ymd').'.html"');
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            * { font-family: 'Montserrat', Arial, sans-serif; -webkit-print-color-adjust: exact; box-sizing: border-box; }
            body { background: #525659; margin: 0; padding: 40px 0; color: #333; }
            .a4-page { background: #fff; width: 210mm; min-height: 297mm; margin: 0 auto; padding: 25mm; box-shadow: 0 0 20px rgba(0,0,0,0.3); position: relative; }
            .pro-header { text-align: center; margin-bottom: 40px; }
            .pro-title { font-size: 20px; font-weight: 800; color: #444; border-bottom: 2px solid #ddd; display: inline-block; padding-bottom: 10px; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 2px; }
            .company-name { font-size: 26px; font-weight: 900; color: #000; text-transform: uppercase; margin: 5px 0; }
            .company-sub { font-size: 11px; color: #666; font-weight: 500; }
            
            .meta-grid { display: flex; justify-content: space-between; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
            .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 20px; border-radius: 8px; flex: 1; margin: 0 10px; text-align: center; }
            .meta-label { font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
            .meta-value { font-size: 13px; font-weight: 700; color: #1e293b; }

            .report-table { width: 100%; border-collapse: collapse; margin-top: 40px; border: 1px solid #475569; }
            .report-table th, .report-table td { border: 1px solid #475569; }
            .section-header { background: #2f5597; color: #fff; font-size: 11px; font-weight: 900; padding: 12px 20px; text-transform: uppercase; border-bottom: 2px solid #000; }
            .sub-section-header { font-size: 11px; font-weight: 800; color: #000; padding: 15px 20px 8px; text-transform: uppercase; letter-spacing: 1px; background: #e2e8f0; }
            .item-row td { padding: 8px 15px 8px 30px; font-size: 11px; color: #000; border-bottom: 1px solid #94a3b8; }
            .item-balance { text-align: right; font-weight: 700; font-family: 'Courier New', monospace; font-size: 13px; color: #000; }
            .subtotal-row td { padding: 12px 20px; font-size: 11px; font-weight: 800; border-top: 2px solid #2f5597; border-bottom: 2px solid #2f5597; text-transform: uppercase; background: #cbd5e1; color: #000; }
            .excel-header { background: #2f5597; color: #fff; font-size: 13px; font-weight: 800; padding: 12px 20px; text-transform: uppercase; border-bottom: 2px solid #1a335a; }
            .excel-subtotal { background: #f8fafc; border-top: 1px solid #2f5597; border-bottom: 2px double #2f5597; font-weight: 800; }
            .grand-total-row td { background: #1e293b; color: #fff; padding: 18px 20px; font-size: 15px; font-weight: 900; text-transform: uppercase; }
            .net-income-row { border-top: 3px solid #000; border-bottom: 5px double #000; background: #f1f5f9; }
            .net-income-label { font-size: 14px; font-weight: 900; color: #000; padding: 15px !important; }
            
            .year-header { text-align: right; padding-right: 20px; color: #fff; font-weight: 900; font-size: 14px; }
            .ratio-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 50px; page-break-inside: avoid; }
            .ratio-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; background: #fff; text-align: center; }
            .ratio-name { font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 5px; }
            .ratio-value { font-size: 18px; font-weight: 900; color: #1e1b4b; }
            
            .currency { opacity: 0.5; font-size: 10px; margin-right: 5px; }
            @media print { body { padding: 0; background: none; } .a4-page { box-shadow: none; margin: 0; width: 100%; } .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="a4-page">
            <div class="pro-header">
                <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 4px solid #2f5597; padding-bottom: 15px; margin-bottom: 30px;">
                    <img src="logo2.png" alt="ViaHale Logo" style="height: 70px; object-fit: contain;">
                    <div style="text-align: right;">
                        <div style="font-size: 26px; font-weight: 900; color: #2f5597; text-transform: uppercase; letter-spacing: 1px;"><?php echo $report_title; ?></div>
                        <div style="font-size: 13px; font-weight: 800; color: #64748b; margin-top: 5px; text-transform: uppercase;">
                            <?php 
                                $f_obj = new DateTime($from_date);
                                $t_obj = new DateTime($to_date);
                                $is_multi = count($years) > 1;
                                $is_annual = ($f_obj->format('m-d') == '01-01' && $t_obj->format('m-d') == '12-31');
                                
                                if ($report_type == 'income') {
                                    if ($is_multi) echo "Years ended December 31";
                                    else if ($is_annual) echo "For the year ended December 31, " . $t_obj->format('Y');
                                    else echo "As of " . $t_obj->format('F') . " " . $t_obj->format('t') . ", " . $t_obj->format('Y');
                                } else if ($report_type == 'balance' || $report_type == 'trial') {
                                    if ($is_multi && $report_type == 'balance') {
                                        $labels = [];
                                        foreach($years as $y) {
                                            // Assume multi-year comparison is for year-ends
                                            $labels[] = "December 31, $y";
                                        }
                                        echo "As of " . implode(" and ", $labels);
                                    } else {
                                        echo "As of " . $t_obj->format('F') . " " . $t_obj->format('t') . ", " . $t_obj->format('Y');
                                    }
                                } else if ($report_type == 'cashflow') {
                                    echo "For the Year Ending " . $t_obj->format('F') . " " . $t_obj->format('t') . ", " . $t_obj->format('Y');
                                }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <table class="report-table">
                <?php if ($report_type == 'income'): 
                    $income_before_tax = array_fill_keys($years, 0);
                    $tax_provision = array_fill_keys($years, 0);
                    $final_net_income = array_fill_keys($years, 0);

                    foreach($years as $year) {
                        $rev = $r_revenue['totals'][$year];
                        $exp = 0;
                        // Exclude tax payments/expenses from normal operating expenses
                        foreach($r_expenses['sections'] as $sec) {
                            foreach($sec['accounts'] as $acc) {
                                // Broaden tax detection to catch Business Taxes and other tax expenses
                                if (stripos($acc['name'], 'Tax') !== false && stripos($acc['name'], 'Payable') === false) {
                                    $tax_provision[$year] += $acc['balances'][$year];
                                } else {
                                    $exp += $acc['balances'][$year];
                                }
                            }
                        }
                        $income_before_tax[$year] = $rev - $exp;
                        // Minimum tax provision fallback if no payments but income is positive
                        if ($tax_provision[$year] == 0 && $income_before_tax[$year] > 0) {
                            $tax_provision[$year] = $income_before_tax[$year] * 0.25;
                        }
                        $final_net_income[$year] = $income_before_tax[$year] - $tax_provision[$year];
                    }
                ?>
                    <tr>
                        <th class="excel-header" style="text-align:left">Account Description</th>
                        <?php foreach($years as $year): ?>
                            <th class="excel-header year-header"><?php echo $year; ?></th>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr><td colspan="<?php echo count($years)+1; ?>" class="sub-section-header" style="color: #2f5597; font-weight:900">Operating Revenue</td></tr>
                    <?php foreach ($r_revenue['sections'] as $sec): ?>
                        <?php foreach ($sec['accounts'] as $acc): ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($acc['name']); ?></td>
                                <?php foreach($years as $year): ?>
                                    <td class="item-balance"><?php echo number_format($acc['balances'][$year], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td>Total Operating Revenues</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_revenue['totals'][$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr><td colspan="<?php echo count($years)+1; ?>" class="sub-section-header" style="color: #2f5597; font-weight:900">Operating Expenses</td></tr>
                    <?php foreach ($r_expenses['sections'] as $sec): ?>
                        <?php foreach ($sec['accounts'] as $acc): ?>
                            <?php 
                                // Hide tax payments here as they move to provision
                                if (stripos($acc['name'], 'Tax') !== false && stripos($acc['name'], 'Payment') !== false) continue; 
                            ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($acc['name']); ?></td>
                                <?php foreach($years as $year): ?>
                                    <td class="item-balance"><?php echo number_format($acc['balances'][$year], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td>Total Operating Expenses</td>
                        <?php foreach($years as $year): ?>
                            <?php 
                                $y_exp = 0;
                                foreach($r_expenses['sections'] as $sec) {
                                    foreach($sec['accounts'] as $acc) {
                                        if (!(stripos($acc['name'], 'Tax') !== false && stripos($acc['name'], 'Payable') === false)) {
                                            $y_exp += $acc['balances'][$year];
                                        }
                                    }
                                }
                            ?>
                            <td class="item-balance">₱<?php echo number_format($y_exp, 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="item-row" style="background:#f8fafc">
                        <td style="padding-top:20px; font-weight:800; color:#000">Net Income Before Taxes</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance" style="padding-top:20px">₱<?php echo number_format($income_before_tax[$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="item-row" style="background:#f8fafc">
                        <td style="font-weight:700; color:#666; font-style:italic">Provision for Income Tax</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($tax_provision[$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="net-income-row">
                        <td class="net-income-label">TOTAL NET INCOME</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance" style="padding:15px !important; font-size:16px; color:#c00">₱<?php echo number_format($final_net_income[$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php elseif ($report_type == 'balance'): ?>
                    <tr>
                        <th class="excel-header" style="text-align:left">Account Description</th>
                        <?php foreach($years as $year): ?>
                            <th class="excel-header year-header"><?php echo $year; ?></th>
                        <?php endforeach; ?>
                    </tr>

                    <tr><td colspan="<?php echo count($years)+1; ?>" class="section-header">Assets</td></tr>
                    <?php foreach ($r_assets['sections'] as $sec): ?>
                        <tr><td colspan="<?php echo count($years)+1; ?>" class="sub-section-header"><?php echo htmlspecialchars($sec['name']); ?></td></tr>
                        <?php foreach ($sec['accounts'] as $acc): ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($acc['name']); ?></td>
                                <?php foreach($years as $year): ?>
                                    <td class="item-balance"><?php echo number_format($acc['balances'][$year], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td>Total Assets</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_assets['totals'][$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr><td colspan="<?php echo count($years)+1; ?>" class="section-header" style="border-top: 3px solid #000">Liabilities & Equity</td></tr>
                    <?php foreach ($r_liabilities['sections'] as $sec): ?>
                        <tr><td colspan="<?php echo count($years)+1; ?>" class="sub-section-header"><?php echo htmlspecialchars($sec['name']); ?></td></tr>
                        <?php foreach ($sec['accounts'] as $acc): ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($acc['name']); ?></td>
                                <?php foreach($years as $year): ?>
                                    <td class="item-balance"><?php echo number_format($acc['balances'][$year], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    
                    <tr><td colspan="<?php echo count($years)+1; ?>" class="sub-section-header">Owner Equity</td></tr>
                    <?php foreach ($r_equity_base['sections'] as $sec): ?>
                        <?php foreach ($sec['accounts'] as $acc): ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($acc['name']); ?></td>
                                <?php foreach($years as $year): ?>
                                    <td class="item-balance"><?php echo number_format($acc['balances'][$year], 2); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    <tr class="item-row">
                        <td>Retained Earnings (Period)</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance"><?php echo number_format($r_retained_earnings[$year], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <tr class="subtotal-row">
                        <td>Total Liabilities & Equity</td>
                        <?php foreach($years as $year): ?>
                            <?php 
                                $total_l_e = $r_liabilities['totals'][$year] + $r_equity_base['totals'][$year] + $r_retained_earnings[$year];
                            ?>
                            <td class="item-balance">₱<?php echo number_format($total_l_e, 2); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php elseif ($report_type == 'cashflow'): ?>
                    <tr>
                        <th class="excel-header" style="text-align:left">Activity Description</th>
                        <?php foreach($years as $year): ?>
                            <th class="excel-header year-header"><?php echo $year; ?></th>
                        <?php endforeach; ?>
                    </tr>

                    <tr class="item-row" style="background:#f1f5f9">
                        <td style="font-weight:800; color:#2f5597">Cash at Beginning of Year</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_cashflow['data'][$year]['beginning_balance'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- OPERATIONS -->
                    <tr><td colspan="<?php echo count($years)+1; ?>" class="section-header">Cash Flow from Operating Activities</td></tr>
                    <tr class="item-row">
                        <td style="font-weight:700; color:#666">Cash Inflows</td>
                        <?php foreach($years as $year): ?><td class="item-balance"></td><?php endforeach; ?>
                    </tr>
                    <?php 
                        $op_in_accts = [];
                        foreach($years as $year) {
                            foreach($r_cashflow['data'][$year]['operating']['in'] as $i) $op_in_accts[$i['name']] = true;
                        }
                        foreach(array_keys($op_in_accts) as $name): ?>
                        <tr class="item-row">
                            <td style="padding-left:50px"><?php echo htmlspecialchars($name); ?></td>
                            <?php foreach($years as $year): ?>
                                <?php 
                                    $val = 0;
                                    foreach($r_cashflow['data'][$year]['operating']['in'] as $i) if($i['name'] == $name) $val = $i['amount'];
                                ?>
                                <td class="item-balance"><?php echo number_format($val, 2); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="item-row">
                        <td style="font-weight:700; color:#666">Cash Outflows</td>
                        <?php foreach($years as $year): ?><td class="item-balance"></td><?php endforeach; ?>
                    </tr>
                    <?php 
                        $op_out_accts = [];
                        foreach($years as $year) {
                            foreach($r_cashflow['data'][$year]['operating']['out'] as $i) $op_out_accts[$i['name']] = true;
                        }
                        foreach(array_keys($op_out_accts) as $name): ?>
                        <tr class="item-row">
                            <td style="padding-left:50px"><?php echo htmlspecialchars($name); ?></td>
                            <?php foreach($years as $year): ?>
                                <?php 
                                    $val = 0;
                                    foreach($r_cashflow['data'][$year]['operating']['out'] as $i) if($i['name'] == $name) $val = $i['amount'];
                                ?>
                                <td class="item-balance" style="color:#c00">(<?php echo number_format($val, 2); ?>)</td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <tr class="subtotal-row">
                        <td>Net Cash Flow from Operations</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_cashflow['data'][$year]['operating']['total'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- INVESTING -->
                    <tr><td colspan="<?php echo count($years)+1; ?>" class="section-header">Cash Flow from Investing Activities</td></tr>
                    <?php 
                        $inv_accts = [];
                        foreach($years as $year) {
                            foreach($r_cashflow['data'][$year]['investing']['in'] as $i) $inv_accts[$i['name']] = true;
                            foreach($r_cashflow['data'][$year]['investing']['out'] as $i) $inv_accts[$i['name']] = true;
                        }
                        foreach(array_keys($inv_accts) as $name): ?>
                        <tr class="item-row">
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <?php foreach($years as $year): ?>
                                <?php 
                                    $val = 0;
                                    foreach($r_cashflow['data'][$year]['investing']['in'] as $i) if($i['name'] == $name) $val += $i['amount'];
                                    foreach($r_cashflow['data'][$year]['investing']['out'] as $i) if($i['name'] == $name) $val -= $i['amount'];
                                ?>
                                <td class="item-balance" style="<?php echo $val < 0 ? 'color:#c00' : ''; ?>">
                                    <?php echo $val < 0 ? '('.number_format(abs($val), 2).')' : number_format($val, 2); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td>Net Cash Flow from Investing</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_cashflow['data'][$year]['investing']['total'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- FINANCING -->
                    <tr><td colspan="<?php echo count($years)+1; ?>" class="section-header">Cash Flow from Financing Activities</td></tr>
                    <?php 
                        $fin_accts = [];
                        foreach($years as $year) {
                            foreach($r_cashflow['data'][$year]['financing']['in'] as $i) $fin_accts[$i['name']] = true;
                            foreach($r_cashflow['data'][$year]['financing']['out'] as $i) $fin_accts[$i['name']] = true;
                        }
                        foreach(array_keys($fin_accts) as $name): ?>
                        <tr class="item-row">
                            <td><?php echo htmlspecialchars($name); ?></td>
                            <?php foreach($years as $year): ?>
                                <?php 
                                    $val = 0;
                                    foreach($r_cashflow['data'][$year]['financing']['in'] as $i) if($i['name'] == $name) $val += $i['amount'];
                                    foreach($r_cashflow['data'][$year]['financing']['out'] as $i) if($i['name'] == $name) $val -= $i['amount'];
                                ?>
                                <td class="item-balance" style="<?php echo $val < 0 ? 'color:#c00' : ''; ?>">
                                    <?php echo $val < 0 ? '('.number_format(abs($val), 2).')' : number_format($val, 2); ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="subtotal-row">
                        <td>Net Cash Flow from Financing</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_cashflow['data'][$year]['financing']['total'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr class="item-row" style="background:#f1f5f9; border-top:2px solid #2f5597">
                        <td style="font-weight:800; color:#000">Net Increase in Cash</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance" style="font-weight:900; color:#2f5597">₱<?php echo number_format($r_cashflow['data'][$year]['net_change'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr class="grand-total-row">
                        <td>Cash at End of Year</td>
                        <?php foreach($years as $year): ?>
                            <td class="item-balance">₱<?php echo number_format($r_cashflow['data'][$year]['ending_balance'], 2); ?></td>
                        <?php endforeach; ?>
                    </tr>

                <?php elseif ($report_type == 'trial'): 
                    $total_debit = 0;
                    $total_credit = 0;
                ?>
                    <tr>
                        <th class="excel-header" style="text-align:left">Account</th>
                        <th class="excel-header year-header">Debit</th>
                        <th class="excel-header year-header">Credit</th>
                    </tr>
                    <?php if (!empty($r_trial)): ?>
                        <?php foreach ($r_trial as $account): 
                            $total_debit += $account['debit'];
                            $total_credit += $account['credit'];
                        ?>
                            <tr class="item-row">
                                <td><?php echo htmlspecialchars($account['account']); ?></td>
                                <td class="item-balance">₱<?php echo number_format($account['debit'], 2); ?></td>
                                <td class="item-balance">₱<?php echo number_format($account['credit'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="item-row" style="text-align: center;">No transactions found for this period.</td></tr>
                    <?php endif; ?>
                    <tr class="grand-total-row">
                        <td>TOTAL</td>
                        <td class="item-balance">₱<?php echo number_format($total_debit, 2); ?></td>
                        <td class="item-balance">₱<?php echo number_format($total_credit, 2); ?></td>
                    </tr>
                    <tr class="net-income-row">
                        <td class="net-income-label">BALANCE STATUS</td>
                        <td colspan="2" class="item-balance" style="padding:15px !important; font-size:16px; color:<?php echo abs($total_debit - $total_credit) < 0.01 ? '#2f5597' : '#c00'; ?>">
                            <?php echo abs($total_debit - $total_credit) < 0.01 ? 'BALANCED' : 'UNBALANCED (Diff: ₱'.number_format(abs($total_debit - $total_credit), 2).')'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>

            <?php if($report_type == 'balance'): ?>
            <div style="margin-top: 40px; font-weight: 800; color: #2f5597; text-transform: uppercase;">Financial Analysis (Comparison)</div>
            <table class="report-table" style="margin-top: 10px;">
                <tr>
                    <th class="excel-header" style="text-align:left">Financial Metric</th>
                    <?php foreach($years as $year): ?>
                        <th class="excel-header year-header"><?php echo $year; ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr class="item-row">
                    <td>Debt Ratio (Total Liabs / Total Assets)</td>
                    <?php foreach($years as $year): ?>
                        <td class="item-balance"><?php echo $r_assets['totals'][$year] > 0 ? number_format($r_liabilities['totals'][$year] / $r_assets['totals'][$year], 2) : '0.00'; ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr class="item-row">
                    <td>Current Ratio (Current Assets / Current Liabs)</td>
                    <?php foreach($years as $year): ?>
                        <?php 
                            $ca = $r_assets['sections'][6]['subtotals'][$year] ?? 0;
                            $cl = $r_liabilities['sections'][9]['subtotals'][$year] ?? 0;
                        ?>
                        <td class="item-balance"><?php echo $cl > 0 ? number_format($ca / $cl, 2) : '0.00'; ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr class="item-row">
                    <td>Working Capital (CA - CL)</td>
                    <?php foreach($years as $year): ?>
                        <?php 
                            $ca = $r_assets['sections'][6]['subtotals'][$year] ?? 0;
                            $cl = $r_liabilities['sections'][9]['subtotals'][$year] ?? 0;
                        ?>
                        <td class="item-balance">₱<?php echo number_format($ca - $cl, 0); ?></td>
                    <?php endforeach; ?>
                </tr>
            </table>
            <?php endif; ?>

            <div style="margin-top: 60px; font-size: 10px; color: #999; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                <div style="margin-bottom: 5px;">Generated safely by ViaHale Financial Ecosystem. Secure Document ID: <?php echo strtoupper(bin2hex(random_bytes(4))); ?></div>
                <div style="font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 1px;">Prepared on <?php echo date('F d, Y'); ?> at <?php echo date('h:i A'); ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php exit();
}

/**
 * METRICS CALCULATION
 */
$total_reports = $conn->query("SELECT COUNT(*) FROM saved_reports")->fetch_row()[0];
$monthly_reports = $conn->query("SELECT COUNT(*) FROM saved_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)")->fetch_row()[0];
$quarterly_reports = $conn->query("SELECT COUNT(*) FROM saved_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)")->fetch_row()[0];
$annual_reports = $conn->query("SELECT COUNT(*) FROM saved_reports WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)")->fetch_row()[0];

// Fetch Reports for List
$search = $_GET['search'] ?? '';
// Fetch all reports for client-side handling
$sql = "SELECT * FROM saved_reports ORDER BY created_at DESC";
$saved_reports_result = $conn->query($sql);
$saved_reports = [];
if ($saved_reports_result) {
    while($row = $saved_reports_result->fetch_assoc()) {
        $saved_reports[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Reports</title>
    <link rel="icon" href="logo.png" type="img">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .sidebar { z-index: 1000; }
        .metric-card { background: #fff; border-radius: 20px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); border: 1px solid #cbd5e1; position: relative; overflow: hidden; }
        .metric-card::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
        .card-total::after { background: #6366f1; }
        .card-monthly::after { background: #10b981; }
        .card-quarterly::after { background: #f59e0b; }
        .card-annual::after { background: #ef4444; }
        
        .reports-table { width: 100%; background: #fff; border-radius: 24px; border: 1px solid #cbd5e1; overflow: hidden; }
        .reports-table th { text-align: left; padding: 20px 24px; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
        .reports-table td { padding: 20px 24px; font-size: 13px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #f8fafc; }
        .report-name { color: #3f36bd; font-weight: 800; cursor: pointer; }
        .action-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; border: 1px solid #f1f5f9; color: #94a3b8; cursor: pointer; transition: all 0.2s; }
        .action-icon:hover { background: #f8fafc; color: #3f36bd; border-color: #3f36bd; }

        /* Modal Redesign */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); z-index: 20002; display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-card { background: #fff; border-radius: 32px; width: 480px; padding: 40px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.15); animation: modalIn 0.3s ease-out; position: relative; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-title { font-size: 24px; font-weight: 800; color: #0f172a; margin-bottom: 32px; }
        .input-group { margin-bottom: 24px; }
        .input-label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .modal-select { width: 100%; border: 2px solid #e2e8f0; border-radius: 14px; padding: 14px 20px; font-size: 14px; font-weight: 700; color: #0f172a; cursor: pointer; transition: all 0.2s; outline: none; }
        .modal-select:focus { border-color: #3f36bd; }
        .modal-actions { display: grid; grid-cols: 2; gap: 16px; margin-top: 40px; display: flex; }
        .btn-modal { flex: 1; padding: 16px; border-radius: 14px; font-size: 14px; font-weight: 800; letter-spacing: 1px; transition: all 0.2s; }
        .btn-cancel { background: #f1f5f9; color: #64748b; }
        .btn-confirm { background: #2563eb; color: #fff; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); }

        /* PIN Modal */
        .pin-input-container { display: flex; gap: 12px; justify-content: center; margin: 32px 0; }
        .pin-digit { width: 48px; height: 56px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; font-size: 24px; font-weight: 800; color: #0f172a; outline: none; }
        .pin-digit:focus { border-color: #3f36bd; }
        .pin-digit.error { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
        .shake { animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        #pinErrorMessage { color: #ef4444; font-size: 13px; font-weight: 800; margin-top: 10px; display: none; text-align: center; text-transform: uppercase; letter-spacing: 1px; }
        /* Preview Modal Redesign */
        .preview-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 30000; display: none; align-items: center; justify-content: center; padding: 40px; }
        .preview-modal { background: #fff; border-radius: 24px; width: 95%; height: 90%; max-width: 1200px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalIn 0.3s ease-out; }
        .preview-nav { background: #1e1e2d; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; color: #fff; border-bottom: 1px solid #2d2d3f; }
        .preview-iframe { flex: 1; border: none; background: #fff; width: 100%; }

        /* Animated Tab Styles (from COA) */
        .tabs-container { 
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px;
            background-color: #f3f4f6;
            border-radius: 9999px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: visible;
        }

        .tab-indicator {
            position: absolute;
            height: calc(100% - 12px);
            background: #3f36bd;
            border-radius: 9999px;
            box-shadow: 0 4px 12px rgba(63, 54, 189, 0.3);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1;
            top: 6px;
            left: 6px;
            pointer-events: none;
        }

        .account-type-tab {
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 24px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            background: transparent;
            border: none;
            position: relative;
            z-index: 2;
        }
        
        .account-type-tab:hover {
            color: #111827;
        }
        
        .account-type-tab.active {
            color: white !important;
        }
        
        .account-type-tab i {
            font-size: 14px;
            opacity: 0.7;
        }
        
        .account-type-tab.active i {
            opacity: 1;
            color: white;
        }

        /* Fix scrollbar layout - Remove outer scrollbar */
        html, body {
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* Ensure Sidebar's main container fills the space but doesn't overflow */
        main {
            height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
    </style>
</head>
<body class="bg-[#f8fafc]">
    <?php include('sidebar.php'); ?>
    
    <div class="flex-1 overflow-y-auto">
        <div class="px-10 py-8">
        <header class="mb-10">
            <h1 class="text-3xl font-black text-[#0f172a] flex items-center gap-3">
                <i class="fas fa-file-invoice text-blue-600"></i> Financial Reports
            </h1>
            <p class="text-[11px] font-black text-blue-600/70 uppercase tracking-[3px] mt-1 ml-10">Module: Reporting & Analytics</p>
        </header>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="metric-card card-total">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Reports</p>
                <h3 class="text-4xl font-black text-slate-800"><?php echo $total_reports; ?></h3>
            </div>
            <div class="metric-card card-monthly">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Monthly</p>
                <h3 class="text-4xl font-black text-slate-800"><?php echo $monthly_reports; ?></h3>
            </div>
            <div class="metric-card card-quarterly">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Quarterly</p>
                <h3 class="text-4xl font-black text-slate-800"><?php echo $quarterly_reports; ?></h3>
            </div>
            <div class="metric-card card-annual">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Annual</p>
                <h3 class="text-4xl font-black text-slate-800"><?php echo $annual_reports; ?></h3>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6 bg-white p-4 rounded-xl border border-gray-200">
            <div class="tabs-container">
                <div id="tab-indicator" class="tab-indicator"></div>
                <button class="account-type-tab active" data-type="">
                    <i class="fas fa-th-large"></i> All
                </button>
                <button class="account-type-tab" data-type="income">
                    <i class="fas fa-file-invoice-dollar"></i> Income
                </button>
                <button class="account-type-tab" data-type="balance">
                    <i class="fas fa-balance-scale"></i> Balance
                </button>
                <button class="account-type-tab" data-type="trial">
                    <i class="fas fa-clipboard-list"></i> Trial
                </button>
                <button class="account-type-tab" data-type="cashflow">
                    <i class="fas fa-money-bill-wave"></i> Cashflow
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" id="searchInput"
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent w-64"
                        placeholder="Search reports...">
                </div>
                <button onclick="openGenerateModal()" class="px-6 py-2 bg-[#1e1e2d] text-white rounded-lg font-bold text-sm hover:bg-black transition-all flex items-center gap-2">
                    <i class="fas fa-plus"></i> GENERATE
                </button>
            </div>
        </div>

        <!-- Reports Table -->
        <div class="reports-table">
            <table class="w-full">
                <thead>
                    <tr>
                        <th width="10%">Report ID</th>
                        <th width="35%">Report Name</th>
                        <th width="15%">Type</th>
                        <th width="15%">Period</th>
                        <th width="15%">Generated Date</th>
                        <th width="10%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody">
                    <!-- Data will be injected by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer">
            <!-- Pagination will be injected by JavaScript -->
        </div>
    </div>

    <!-- Generate Modal -->
    <div id="generateModal" class="modal-overlay">
        <div class="modal-card">
            <h3 class="modal-title">Generate New Report</h3>
            <form id="generateForm">
                <div class="input-group">
                    <label class="input-label">Report Type</label>
                    <select name="type" class="modal-select">
                        <option value="income">Income Statement</option>
                        <option value="balance">Balance Sheet</option>
                        <option value="trial">Trial Balance</option>
                        <option value="cashflow">Cash Flow Statement</option>
                    </select>
                </div>
                <div class="input-group">
                    <label class="input-label">Period Type</label>
                    <select name="period_mode" id="periodMode" class="modal-select" onchange="togglePeriodInputs()">
                        <option value="month">Specific Month</option>
                        <option value="year">Specific Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                <div id="monthSelector" class="input-group">
                    <label class="input-label">Select Month</label>
                    <input type="month" name="report_month" class="modal-select" value="<?php echo date('Y-m'); ?>">
                </div>

                <div id="yearSelector" class="input-group hidden">
                    <label class="input-label">Select Year</label>
                    <select name="report_year" class="modal-select">
                        <?php for($y=date('Y'); $y>=2020; $y--) echo "<option value='$y'>$y</option>"; ?>
                    </select>
                </div>

                <div id="customRangeSelector" class="hidden grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="input-label">Start Date</label>
                        <input type="date" name="custom_from" class="modal-select">
                    </div>
                    <div>
                        <label class="input-label">End Date</label>
                        <input type="date" name="custom_to" class="modal-select">
                    </div>
                </div>
                <div class="input-group">
                    <label class="input-label">Format</label>
                    <select name="format" class="modal-select">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeGenerateModal()" class="btn-modal btn-cancel">CANCEL</button>
                    <button type="button" onclick="handleGenerate()" class="btn-modal btn-confirm uppercase">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- PIN Confirmation Modal -->
    <div id="pinModal" class="modal-overlay" style="z-index: 30001;">
        <div class="modal-card !w-[440px]">
            <h3 class="modal-title text-center !mb-4">Security Confirmation</h3>
            <p class="text-center text-slate-400 text-xs font-bold uppercase tracking-wider mb-8">Confirm your <span class="text-blue-600">6-Digit Login PIN</span> to unlock export</p>
            <div class="pin-input-container" id="pinInputGroup">
                <input type="password" maxlength="1" class="pin-digit" autofocus onkeydown="moveFocus(this, 1, event)">
                <input type="password" maxlength="1" class="pin-digit" onkeydown="moveFocus(this, 2, event)">
                <input type="password" maxlength="1" class="pin-digit" onkeydown="moveFocus(this, 3, event)">
                <input type="password" maxlength="1" class="pin-digit" onkeydown="moveFocus(this, 4, event)">
                <input type="password" maxlength="1" class="pin-digit" onkeydown="moveFocus(this, 5, event)">
                <input type="password" maxlength="1" class="pin-digit" onkeydown="moveFocus(this, 6, event)">
            </div>
            <div id="pinErrorMessage">Incorrect PIN! Please try again.</div>
            <div class="modal-actions">
                <button type="button" onclick="closePinModal()" class="btn-modal btn-cancel">ABORT</button>
                <button type="button" onclick="validatePin()" class="btn-modal btn-confirm !bg-[#3f36bd]">CONFIRM</button>
            </div>
        </div>
    </div>

    <!-- Preview Engine Overlay -->
    <div id="previewOverlay" class="preview-overlay">
        <div class="preview-modal">
            <div class="preview-nav">
                <span class="text-sm font-black uppercase tracking-[2px] opacity-70"><i class="fas fa-file-invoice mr-3 text-blue-400"></i>Report Preview System</span>
                <button onclick="closePreview()" class="bg-red-500/20 text-red-400 px-4 py-2 rounded-lg font-black text-xs hover:bg-red-500 hover:text-white transition uppercase">Close Viewer</button>
            </div>
            <iframe id="previewIframe" class="preview-iframe"></iframe>
        </div>
    </div>

    <!-- Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
    console.log("Financial Statement Script Loaded");

    // Load data from PHP
    const data = <?php echo json_encode($saved_reports); ?>;
    let activeDownloadReport = null;
    let currentPage = 1;
    let rowsPerPage = 10;
    let selectedType = "";

    function getFormattedId(report) {
        let prefix = '';
        if (report.report_type === 'income') prefix = 'IS';
        else if (report.report_type === 'balance') prefix = 'BS';
        else if (report.report_type === 'trial') prefix = 'TB';
        else if (report.report_type === 'cashflow') prefix = 'CF';
        
        const date = new Date(report.created_at);
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth() + 1).padStart(2, '0');
        const dd = String(date.getDate()).padStart(2, '0');
        
        return `${prefix}${yyyy}${mm}${dd}-${report.id}`;
    }

    function renderTable() {
        const tableBody = document.getElementById("reportsTableBody");
        if (!tableBody) return;
        tableBody.innerHTML = "";

        const filteredData = filterData();
        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, filteredData.length);

        if (filteredData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-[10px]">
                        <i class="fas fa-search text-3xl mb-2 text-gray-300"></i>
                        <p class="text-lg">No reports found</p>
                    </td>
                </tr>
            `;
        } else {
            for (let i = startIndex; i < endIndex; i++) {
                const report = filteredData[i];
                const row = document.createElement("tr");
                row.className = "report-row hover:bg-slate-50 transition";
                
                let typeLabel = '';
                if (report.report_type === 'income') typeLabel = 'INCOME STATEMENT';
                else if (report.report_type === 'balance') typeLabel = 'BALANCE SHEET';
                else if (report.report_type === 'trial') typeLabel = 'TRIAL BALANCE';
                else if (report.report_type === 'cashflow') typeLabel = 'CASH FLOW STATEMENT';

                const reportJson = JSON.stringify(report).replace(/'/g, "&apos;");
                const formattedId = getFormattedId(report);

                row.innerHTML = `
                    <td class="text-slate-500 font-bold whitespace-nowrap">${formattedId}</td>
                    <td>
                        <div class="report-name" onclick='viewReportById(${report.id})'>
                            ${report.report_name}
                        </div>
                    </td>
                    <td class="uppercase text-[11px] font-bold text-slate-400">${typeLabel}</td>
                    <td class="font-bold text-slate-600">${new Date(report.from_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</td>
                    <td class="text-slate-400 font-medium">${new Date(report.created_at).toISOString().split('T')[0]}</td>
                    <td class="text-center space-x-2">
                        <button onclick='viewReportById(${report.id})' class="action-icon" title="View"><i class="far fa-eye"></i></button>
                        <button type="button" onclick="window.initiateDownload(this)" class="action-icon btn-download-report" data-report='${reportJson}' title="Download"><i class="fas fa-file-download"></i></button>
                    </td>
                `;
                tableBody.appendChild(row);
            }
        }

        renderPagination(filteredData.length);
    }

    function viewReportById(id) {
        const report = data.find(r => r.id == id);
        if (report) viewReport(report);
    }

    function filterData() {
        const searchInput = document.getElementById("searchInput").value.toLowerCase();
        
        return data.filter(item => {
            const formattedId = getFormattedId(item).toLowerCase();
            const matchesSearch = item.report_name.toLowerCase().includes(searchInput) || 
                                formattedId.includes(searchInput);
            const matchesType = selectedType === "" || item.report_type === selectedType;
            return matchesSearch && matchesType;
        });
    }

    function filterReports() {
        currentPage = 1;
        renderTable();
    }

    function renderPagination(totalItems) {
        const container = document.getElementById("paginationContainer");
        const totalPages = Math.ceil(totalItems / rowsPerPage);
        
        if (totalPages <= 1) {
            container.innerHTML = "";
            return;
        }

        const startIndex = (currentPage - 1) * rowsPerPage;
        const endIndex = Math.min(startIndex + rowsPerPage, totalItems);

        let html = `
            <div class="flex items-center justify-between mt-6 px-2">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">
                    Showing ${startIndex + 1} to ${endIndex} of ${totalItems} Reports
                </p>
                <div class="flex items-center gap-2">
        `;

        // Prev Button
        if (currentPage > 1) {
            html += `
                <button onclick="changePage(${currentPage - 1})" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider">
                    <i class="fas fa-chevron-left mr-2"></i> Prev
                </button>
            `;
        } else {
            html += `
                <span class="px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[11px] font-black text-slate-300 uppercase tracking-wider cursor-not-allowed">
                    <i class="fas fa-chevron-left mr-2"></i> Prev
                </span>
            `;
        }

        // Next Button
        if (currentPage < totalPages) {
            html += `
                <button onclick="changePage(${currentPage + 1})" class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-[11px] font-black text-slate-600 hover:bg-slate-50 transition uppercase tracking-wider">
                    Next <i class="fas fa-chevron-right ml-2"></i>
                </button>
            `;
        } else {
            html += `
                <span class="px-4 py-2 bg-slate-50 border border-slate-100 rounded-lg text-[11px] font-black text-slate-300 uppercase tracking-wider cursor-not-allowed">
                    Next <i class="fas fa-chevron-right ml-2"></i>
                </span>
            `;
        }

        html += `</div></div>`;
        container.innerHTML = html;
    }

    function changePage(page) {
        currentPage = page;
        renderTable();
    }

    // Tab indicator logic
    function initTabIndicator() {
        const activeTab = document.querySelector('.account-type-tab.active');
        const indicator = document.getElementById('tab-indicator');
        if (activeTab && indicator) {
            indicator.style.transition = 'none';
            indicator.style.width = `${activeTab.offsetWidth}px`;
            indicator.style.left = `${activeTab.offsetLeft}px`;
            setTimeout(() => indicator.style.transition = '', 50);
        }
    }

    document.querySelectorAll('.account-type-tab').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            document.querySelectorAll('.account-type-tab').forEach(tab => tab.classList.remove('active'));
            this.classList.add('active');
            
            const indicator = document.getElementById('tab-indicator');
            if (indicator) {
                indicator.style.width = `${this.offsetWidth}px`;
                indicator.style.left = `${this.offsetLeft}px`;
            }
            
            selectedType = this.getAttribute('data-type') || "";
            currentPage = 1;
            renderTable();
        });
    });

    window.addEventListener('load', () => {
        initTabIndicator();
        renderTable();
    });
    window.addEventListener('resize', initTabIndicator);
    document.getElementById("searchInput").addEventListener("input", filterReports);

    
    // Explicitly bind to window to ensure global access
    window.initiateDownload = function(arg) {
        let report;
        if (arg instanceof HTMLElement) {
            try {
                // Support both data-report attribute and direct JSON object (legacy)
                const data = arg.getAttribute('data-report');
                if (data) report = JSON.parse(data);
                else report = arg; 
            } catch (e) {
                console.error("Failed to parse report data", e);
                alert("Error: Could not retrieve report data.");
                return;
            }
        } else {
            report = arg; 
        }
        
        console.log("Initiating download for:", report);
        if (!report) {
            alert("No report data found.");
            return;
        }

        activeDownloadReport = report;
        const modal = document.getElementById('pinModal');
        if(modal) {
            modal.style.display = 'flex';
            const inputs = document.querySelectorAll('.pin-digit');
            inputs.forEach(i => i.value = '');
            if(inputs.length > 0) inputs[0].focus();
        } else {
            console.error("PIN Modal not found!");
        }
    };

    function openGenerateModal() { document.getElementById('generateModal').style.display = 'flex'; }
    function closeGenerateModal() { document.getElementById('generateModal').style.display = 'none'; }

    function handleGenerate() {
        const form = document.getElementById('generateForm');
        const mode = form.period_mode.value;
        let from = '', to = '';

        if (mode === 'month') {
            const m = form.report_month.value; // YYYY-MM
            if (!m) return alert('Select a month!');
            from = `${m}-01`;
            const [y, mm] = m.split('-');
            const lastDay = new Date(y, mm, 0).getDate();
            to = `${m}-${lastDay}`;
        } else if (mode === 'year') {
            const y = form.report_year.value;
            from = `${y}-01-01`;
            to = `${y}-12-31`;
        } else {
            from = form.custom_from.value;
            to = form.custom_to.value;
            if (!from || !to) return alert('Select both dates!');
        }

        const formData = new FormData();
        formData.append('action', 'save_report');
        formData.append('type', form.type.value);
        formData.append('from_date', from);
        formData.append('to_date', to);
        formData.append('format', form.format.value);
        
        fetch('financial_statement.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) { location.reload(); }
            else { alert('Error: ' + data.error); }
        });
    }

    function togglePeriodInputs() {
        const mode = document.getElementById('periodMode').value;
        document.getElementById('monthSelector').classList.toggle('hidden', mode !== 'month');
        document.getElementById('yearSelector').classList.toggle('hidden', mode !== 'year');
        document.getElementById('customRangeSelector').classList.toggle('hidden', mode !== 'custom');
    }


    function viewReport(report) {
        const overlay = document.getElementById('previewOverlay');
        const iframe = document.getElementById('previewIframe');
        iframe.src = `?generate_report=1&type=${report.report_type}&format=pdf&from_date=${report.from_date}&to_date=${report.to_date}`;
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closePreview() {
        document.getElementById('previewOverlay').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    

    

    function moveFocus(el, p, event) {
        if (event.key === 'Backspace') {
            if (el.value.length === 0 && p > 1) {
                event.preventDefault();
                const prev = document.querySelectorAll('.pin-digit')[p-2];
                prev.value = ''; 
                prev.focus();
            }
            return;
        }
        if(el.value.length === 1 && p < 6) {
            document.querySelectorAll('.pin-digit')[p].focus();
        }
        if(el.value.length === 1 && p === 6) {
            validatePin();
        }
    }
 
    function validatePin() {
        const pin = Array.from(document.querySelectorAll('.pin-digit')).map(i => i.value).join('');
        const pinInputs = document.querySelectorAll('.pin-digit');
        const loginPin = '<?php echo $SECURE_PIN; ?>';
        const errorMsg = document.getElementById('pinErrorMessage');
        const container = document.getElementById('pinInputGroup');

        if(pin === loginPin) {
            const r = activeDownloadReport;
            const downloadUrl = `?generate_report=1&type=${r.report_type}&format=${r.format}&from_date=${r.from_date}&to_date=${r.to_date}&download=1`;
            
            if (r.format === 'pdf') {
                // Generate True PDF client-side
                generatePDF(downloadUrl);
            } else {
                // Direct Download for Excel/CSV
                window.location.href = downloadUrl;
                setTimeout(() => closePinModal(), 500);
            }
        } else {
            // Visual Error State
            container.classList.add('shake');
            pinInputs.forEach(i => i.classList.add('error'));
            errorMsg.style.display = 'block';

            // Reset after animation
            setTimeout(() => {
                container.classList.remove('shake');
                pinInputs.forEach(i => {
                    i.value = '';
                    i.classList.remove('error');
                });
                errorMsg.style.display = 'none';
                pinInputs[0].focus();
            }, 1000);
        }
    }

    async function generatePDF(url) {
        const btn = document.querySelector('#pinModal .btn-confirm');
        const originalText = btn.innerText;
        btn.innerText = 'GENERATING PDF...';
        btn.disabled = true;

        try {
            // Fetch HTML content (remove download=1 to get HTML)
            const cleanUrl = url.replace('&download=1', '');
            const response = await fetch(cleanUrl);
            const text = await response.text();
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(text, 'text/html');
            const styles = doc.querySelectorAll('style');
            const content = doc.querySelector('.a4-page');
            
            if (!content) throw new Error('Report content not found');

            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.style.position = 'absolute';
            wrapper.style.top = '-9999px';
            wrapper.style.left = '-9999px';
            styles.forEach(s => wrapper.appendChild(s.cloneNode(true)));
            wrapper.appendChild(content.cloneNode(true));
            document.body.appendChild(wrapper);

            // Wait for images to load if any
            await new Promise(resolve => setTimeout(resolve, 500));

            const canvas = await html2canvas(wrapper, { scale: 2, useCORS: true });
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`${activeDownloadReport.report_type}_report_${new Date().toISOString().slice(0,10)}.pdf`);
            
            document.body.removeChild(wrapper);
            closePinModal();

        } catch (e) {
            console.error(e);
            alert('Error creating PDF: ' + e.message);
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    function closePinModal() { 
        document.getElementById('pinModal').style.display = 'none'; 
        // Reset error states just in case
        document.getElementById('pinErrorMessage').style.display = 'none';
        document.querySelectorAll('.pin-digit').forEach(i => {
            i.classList.remove('error');
            i.value = '';
        });
        document.getElementById('pinInputGroup').classList.remove('shake');
    }
    </script>
    </div>
</body>
</html>
