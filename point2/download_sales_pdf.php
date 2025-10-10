<?php
session_start();

// Ensure the user is an admin and logged in
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function safeNumberFormat($number, $decimals = 2) {
    return number_format(floatval($number ?? 0), $decimals);
}

// Set default timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Get filter parameters
$timeFilter = isset($_GET['time_filter']) ? $_GET['time_filter'] : 'day';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get current date in Philippines timezone
$currentDate = new DateTime('now', new DateTimeZone('Asia/Manila'));

// Prepare date condition based on filter
switch($timeFilter) {
    case 'day':
        $dateCondition = "1=1";
        $displayDateRange = "Daily Sales (All Time)";
        break;
    case 'month':
        $currentYear = $currentDate->format('Y');
        $dateCondition = "YEAR(t.transaction_date) = '$currentYear'";
        $displayDateRange = "Monthly Sales for " . $currentDate->format('Y');
        break;
    case 'year':
        $dateCondition = "1=1";
        $displayDateRange = "Yearly Sales";
        break;
    case 'custom':
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $dateCondition = "DATE(t.transaction_date) BETWEEN '" . $startDateTime->format('Y-m-d') . "' AND '" . $endDateTime->format('Y-m-d') . "'";
        $displayDateRange = "Sales from " . $startDateTime->format('F d, Y') . " to " . $endDateTime->format('F d, Y');
        break;
    default:
        $dateCondition = "DATE(t.transaction_date) = '" . $currentDate->format('Y-m-d') . "'";
        $displayDateRange = "Sales for " . $currentDate->format('F d, Y');
}

// Query for sales data
$salesTableQuery = "
    SELECT 
        CASE 
            WHEN '$timeFilter' = 'year' THEN YEAR(t.transaction_date)
            WHEN '$timeFilter' = 'month' THEN DATE_FORMAT(t.transaction_date, '%Y-%m')
            ELSE DATE(t.transaction_date)
        END as date_group,
        COUNT(*) as transaction_count,
        SUM(t.total_amount) as total_sales,
        SUM(CASE WHEN t.payment_code = 'CASH' THEN t.total_amount ELSE 0 END) as cash_sales,
        SUM(CASE WHEN t.payment_code = 'GCASH' THEN t.total_amount ELSE 0 END) as gcash_sales,
        SUM(CASE WHEN t.payment_code = 'MAYA' THEN t.total_amount ELSE 0 END) as maya_sales
    FROM transactions t
    WHERE $dateCondition
    GROUP BY date_group
    ORDER BY date_group DESC";

$salesTableResult = $conn->query($salesTableQuery);
$tableData = [];
$grandTotal = 0;
$grandTransactions = 0;

while ($row = $salesTableResult->fetch_assoc()) {
    $tableData[] = $row;
    $grandTotal += $row['total_sales'];
    $grandTransactions += $row['transaction_count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sales Report - Café POS System</title>
    <style>
        @media print {
            body { 
                margin: 0; 
                padding: 0; 
                font-family: 'Arial', sans-serif; 
                background: white;
                color: #000;
                font-size: 12px;
            }
            .no-print { display: none !important; }
            .container { 
                max-width: 100%; 
                margin: 0; 
                padding: 20px; 
                box-shadow: none;
            }
            .header { 
                background: white;
                color: #000;
                padding: 0;
                margin: 0 0 20px 0;
                border-bottom: 2px solid #333;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 15px 0;
            }
            .auto-redirect { display: none; }
        }
        
        @media screen {
            body { 
                margin: 0; 
                padding: 20px; 
                font-family: 'Arial', sans-serif; 
                background: #f5f5f5;
            }
            .container { 
                max-width: 210mm; 
                margin: 0 auto; 
                background: white;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
        }

        .header { 
            padding: 30px;
            border-bottom: 2px solid #333;
        }

        .company-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }

        .report-period {
            font-size: 14px;
            margin: 5px 0;
            color: #666;
        }

        .report-meta {
            font-size: 12px;
            margin: 5px 0;
            color: #666;
        }

        .summary-section {
            padding: 20px 30px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .summary-item {
            text-align: center;
        }

        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .summary-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .content {
            padding: 30px;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            font-size: 11px;
        }

        th { 
            background: #333;
            color: white; 
            padding: 12px 8px; 
            text-align: left; 
            font-weight: bold;
            border: 1px solid #333;
        }

        td { 
            padding: 10px 8px; 
            border: 1px solid #ddd;
        }

        .total-row { 
            background: #f0f0f0;
            font-weight: bold;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }

        .footer { 
            margin-top: 30px; 
            text-align: center; 
            color: #666; 
            font-size: 10px;
            padding: 20px;
            border-top: 1px solid #ddd;
        }

        .auto-redirect {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            margin: 0 -30px;
            border-top: 1px solid #ddd;
        }

        .redirect-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .redirect-timer {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .print-button {
            background: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 5px;
        }

        .print-button:hover {
            background: #555;
        }

        .button-group {
            margin: 15px 0;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <div class="company-name">BTONE CAFÉ POS SYSTEM</div>
                <div class="report-title">SALES REPORT</div>
                <div class="report-period"><?php echo htmlspecialchars($displayDateRange); ?></div>
                <div class="report-meta">Generated on: <?php echo date('F d, Y \\a\\t h:i A'); ?></div>
            </div>
        </div>

        <div class="summary-section">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-value"><?php echo count($tableData); ?></div>
                    <div class="summary-label">PERIODS</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value"><?php echo $grandTransactions; ?></div>
                    <div class="summary-label">TOTAL TRANSACTIONS</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">₱<?php echo safeNumberFormat($grandTotal); ?></div>
                    <div class="summary-label">TOTAL REVENUE</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">₱<?php echo safeNumberFormat($grandTotal / max($grandTransactions, 1)); ?></div>
                    <div class="summary-label">AVG PER TRANSACTION</div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="auto-redirect no-print">
                <div class="redirect-message">
                    Print dialog should open automatically. This page will redirect back to Sales Report in <span id="countdown">10</span> seconds.
                </div>
                <div class="button-group">
                    <button class="print-button" onclick="window.print()">
                        Print Report Now
                    </button>
                    <button class="print-button" onclick="redirectNow()" style="background: #666;">
                        Return Now
                    </button>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-left"><?php echo $timeFilter == 'year' ? 'YEAR' : ($timeFilter == 'month' ? 'MONTH' : 'DATE'); ?></th>
                        <th class="text-center">TRANSACTIONS</th>
                        <th class="text-right">CASH SALES</th>
                        <th class="text-right">G-CASH SALES</th>
                        <th class="text-right">MAYA SALES</th>
                        <th class="text-right">TOTAL SALES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tableData as $row): ?>
                    <tr>
                        <td class="text-left">
                            <?php
                            switch($timeFilter) {
                                case 'year':
                                    echo $row['date_group'];
                                    break;
                                case 'month':
                                    $date = DateTime::createFromFormat('Y-m', $row['date_group']);
                                    echo $date->format('F Y');
                                    break;
                                default:
                                    $date = new DateTime($row['date_group']);
                                    echo $date->format('M d, Y');
                            }
                            ?>
                        </td>
                        <td class="text-center"><?php echo $row['transaction_count']; ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat($row['cash_sales']); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat($row['gcash_sales']); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat($row['maya_sales']); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat($row['total_sales']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <tr class="total-row">
                        <td class="text-left">GRAND TOTAL</td>
                        <td class="text-center"><?php echo $grandTransactions; ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'cash_sales'))); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'gcash_sales'))); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'maya_sales'))); ?></td>
                        <td class="text-right">₱<?php echo safeNumberFormat($grandTotal); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="footer">
            <div>Generated by Café POS System</div>
            <div>Page 1 of 1 • <?php echo date('m/d/Y H:i:s'); ?></div>
        </div>
    </div>

    <script>
        // Auto-print and redirect functions
        function redirectNow() {
            window.location.href = 'sales_report.php';
        }

        function startCountdown() {
            let seconds = 10;
            const countdownElement = document.getElementById('countdown');
            
            const countdownInterval = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(countdownInterval);
                    redirectNow();
                }
            }, 1000);
        }

        window.onload = function() {
            // Auto-print after a short delay
            setTimeout(function() {
                window.print();
            }, 500);
            
            // Start countdown for auto-redirect
            startCountdown();
        };

        // Redirect immediately if print dialog is closed
        window.onafterprint = function() {
            setTimeout(function() {
                redirectNow();
            }, 1000);
        };
    </script>
</body>
</html>
<?php
$conn->close();
?>