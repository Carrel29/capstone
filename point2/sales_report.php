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

// Initialize filters
$timeFilter = isset($_GET['time_filter']) ? $_GET['time_filter'] : 'day';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get current date in Philippines timezone
$currentDate = new DateTime('now', new DateTimeZone('Asia/Manila'));
$currentMonth = $currentDate->format('m');
$currentYear = $currentDate->format('Y');
$firstDayOfMonth = $currentDate->format('Y-m-01');
$lastDayOfMonth = $currentDate->format('Y-m-t');

// Prepare date condition based on filter
switch($timeFilter) {
    case 'day':
        $dateCondition = "1=1"; // Show all days with transactions
        $displayDateRange = "Daily Sales (All Time)";
        $groupBy = "DATE(t.transaction_date)";
        break;
    case 'month':
        // Show months in current year
        $dateCondition = "YEAR(t.transaction_date) = '$currentYear'";
        $displayDateRange = "Monthly Sales for " . $currentDate->format('Y');
        $groupBy = "MONTH(t.transaction_date)";
        break;
    case 'year':
        // Show yearly data
        $dateCondition = "1=1"; // Show all years
        $displayDateRange = "Yearly Sales";
        $groupBy = "YEAR(t.transaction_date)";
        break;
    case 'custom':
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $dateCondition = "DATE(t.transaction_date) BETWEEN '" . $startDateTime->format('Y-m-d') . "' AND '" . $endDateTime->format('Y-m-d') . "'";
        $displayDateRange = "Sales from " . $startDateTime->format('F d, Y') . " to " . $endDateTime->format('F d, Y');
        $groupBy = "DATE(t.transaction_date)";
        break;
    default:
        $dateCondition = "DATE(t.transaction_date) = '" . $currentDate->format('Y-m-d') . "'";
        $displayDateRange = "Sales for " . $currentDate->format('F d, Y');
        $groupBy = "DATE(t.transaction_date)";
}

// Query for sales data for the table - FIXED PAYMENT METHOD CHECK
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

// Function to get daily sales details...
function getDailySalesDetails($conn, $date) {
    $query = "
        SELECT 
            p.name,
            p.category_id,
            c.name as category_name,
            p.classification,
            p.price,
            p.price_hot,
            p.price_medium,
            p.price_large,
            SUM(CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED)) as total_count,
            SUM(CASE 
                WHEN json_extract(ti.cart_item, '$.size') = 'hot' 
                THEN CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED) 
                ELSE 0 
            END) as hot_count,
            SUM(CASE 
                WHEN json_extract(ti.cart_item, '$.size') = 'medium' 
                THEN CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED) 
                ELSE 0 
            END) as medium_count,
            SUM(CASE 
                WHEN json_extract(ti.cart_item, '$.size') = 'large' 
                THEN CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED) 
                ELSE 0 
            END) as large_count,
            SUM(
                CASE
                    WHEN p.classification = 'drinks' THEN
                        CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED) * 
                        CASE json_extract(ti.cart_item, '$.size')
                            WHEN 'hot' THEN COALESCE(p.price_hot, p.price)
                            WHEN 'medium' THEN COALESCE(p.price_medium, p.price)
                            WHEN 'large' THEN COALESCE(p.price_large, p.price)
                            ELSE p.price
                        END
                    ELSE
                        CAST(JSON_EXTRACT(ti.cart_item, '$.quantity') AS UNSIGNED) * p.price
                END
            ) as total_amount
        FROM transactions t
        CROSS JOIN JSON_TABLE(
            t.cart_items,
            '$[*]' COLUMNS(
                cart_item JSON PATH '$'
            )
        ) as ti
        JOIN products p ON json_extract(ti.cart_item, '$.name') LIKE CONCAT('%', p.name, '%')
        JOIN categories c ON p.category_id = c.id
        WHERE DATE(t.transaction_date) = ? AND c.archived = 0
        GROUP BY p.id, c.id, p.name, p.classification, p.price, p.price_hot, p.price_medium, p.price_large
        ORDER BY total_count DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($items[$row['category_name']])) {
            $items[$row['category_name']] = [
                'top_items' => [],
                'least_sold' => null
            ];
        }
        
        // Calculate total amount for each size for drinks
        if ($row['classification'] === 'drinks') {
            $hot_total = $row['hot_count'] * floatval($row['price_hot']);
            $medium_total = $row['medium_count'] * floatval($row['price_medium']);
            $large_total = $row['large_count'] * floatval($row['price_large']);
            $row['total_amount'] = $hot_total + $medium_total + $large_total;
        }
        
        // Add all price information to the row
        $row['prices'] = [
            'hot' => floatval($row['price_hot']),
            'medium' => floatval($row['price_medium']),
            'large' => floatval($row['price_large']),
            'regular' => floatval($row['price'])
        ];
        
        if (count($items[$row['category_name']]['top_items']) < 3) {
            $items[$row['category_name']]['top_items'][] = $row;
        }
        
        if ($items[$row['category_name']]['least_sold'] === null || 
            $row['total_count'] < $items[$row['category_name']]['least_sold']['total_count']) {
            $items[$row['category_name']]['least_sold'] = $row;
        }
    }
    
    return $items;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Café POS System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c5ce7;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #5a4dcc;
        }

        .user-info {
            text-align: right;
            color: #666;
            flex-grow: 1;
            margin-right: 20px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-button {
            padding: 10px 20px;
            background-color: #6c5ce7;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-button:hover {
            background-color: #5a4dcc;
        }

        .filter-button.active {
            background-color: #5a4dcc;
        }

        .date-range-display {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
            font-weight: bold;
        }

        .custom-date-form {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .custom-date-form input[type="date"] {
            padding: 8px;
            margin: 0 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .download-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .download-button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .download-button:hover {
            background-color: #218838;
        }

        .download-button.excel {
            background-color: #1d6f42;
        }

        .download-button.excel:hover {
            background-color: #155d32;
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .sales-table th, 
        .sales-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .sales-table th {
            background: #6c5ce7;
            color: white;
            position: sticky;
            top: 0;
        }

        .sales-table tr:hover {
            background: #f5f5f5;
        }

        .sales-table .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 2px solid #6c5ce7;
        }

        .view-details {
            cursor: pointer;
            color: #6c5ce7;
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close-button {
            position: absolute;
            right: 20px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
        }

        .close-button:hover {
            color: #333;
        }

        .category-details {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-section">
        <div class="user-info">
            <div>Current User's Login: <?php echo htmlspecialchars($_SESSION['username']); ?></div>
            <div>Current Date and Time: <span id="current-datetime"></span></div>
        </div>
        <a href="history.php" class="back-button">Back to Transaction History</a>
    </div>

    <h1>Sales Report</h1>
        
    <div class="filter-buttons">
        <form method="GET" class="filter-form">
            <button type="submit" name="time_filter" value="day" class="filter-button <?php echo $timeFilter == 'day' ? 'active' : ''; ?>">Per Day</button>
            <button type="submit" name="time_filter" value="month" class="filter-button <?php echo $timeFilter == 'month' ? 'active' : ''; ?>">Per Month</button>
            <button type="submit" name="time_filter" value="year" class="filter-button <?php echo $timeFilter == 'year' ? 'active' : ''; ?>">Per Year</button>
            <button type="button" onclick="toggleCustomDate()" class="filter-button <?php echo $timeFilter == 'custom' ? 'active' : ''; ?>">Custom Date Range</button>
        </form>
    </div>

    <div class="date-range-display">
        <?php echo htmlspecialchars($displayDateRange); ?>
    </div>

    <form method="GET" id="customDateForm" class="custom-date-form" style="display: <?php echo $timeFilter == 'custom' ? 'block' : 'none'; ?>;">
        <input type="hidden" name="time_filter" value="custom">
        <label>From: <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required></label>
        <label>To: <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required></label>
        <button type="submit" class="filter-button">Apply Date Range</button>
    </form>

    <!-- Download Buttons -->
    <div class="download-buttons">
        <button class="download-button" onclick="downloadPDF()">Download PDF</button>
        <button class="download-button excel" onclick="downloadExcel()">Download Excel</button>
    </div>

    <!-- Sales Table -->
    <table class="sales-table">
        <thead>
            <tr>
                <th>
                    <?php 
                    switch($timeFilter) {
                        case 'year': echo 'Year'; break;
                        case 'month': echo 'Month'; break;
                        default: echo 'Date';
                    }
                    ?>
                </th>
                <th class="text-right">Transactions</th>
                <th class="text-right">Cash Sales</th>
                <th class="text-right">GCash Sales</th>
                <th class="text-right">Maya Sales</th>
                <th class="text-right">Total Sales</th>
                <?php if ($timeFilter == 'day'): ?>
                <th class="text-center">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableData as $row): ?>
            <tr>
                <td>
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
                            echo $date->format('F d, Y');
                    }
                    ?>
                </td>
                <td class="text-right"><?php echo $row['transaction_count']; ?></td>
                <td class="text-right">₱<?php echo safeNumberFormat($row['cash_sales']); ?></td>
                <td class="text-right">₱<?php echo safeNumberFormat($row['gcash_sales']); ?></td>
                <td class="text-right">₱<?php echo safeNumberFormat($row['maya_sales']); ?></td>
                <td class="text-right">₱<?php echo safeNumberFormat($row['total_sales']); ?></td>
                <?php if ($timeFilter == 'day'): ?>
                <td class="text-center">
                    <span class="view-details" onclick="viewDailyDetails('<?php echo $row['date_group']; ?>')">
                        View Details
                    </span>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <!-- Grand Total Row -->
            <tr class="total-row">
                <td><strong>GRAND TOTAL</strong></td>
                <td class="text-right"><strong><?php echo $grandTransactions; ?></strong></td>
                <td class="text-right"><strong>₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'cash_sales'))); ?></strong></td>
                <td class="text-right"><strong>₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'gcash_sales'))); ?></strong></td>
                <td class="text-right"><strong>₱<?php echo safeNumberFormat(array_sum(array_column($tableData, 'maya_sales'))); ?></strong></td>
                <td class="text-right"><strong>₱<?php echo safeNumberFormat($grandTotal); ?></strong></td>
                <?php if ($timeFilter == 'day'): ?>
                <td></td>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modal for Daily Details -->
<div id="detailsModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeModal()">&times;</span>
        <div id="modalContent"></div>
    </div>
</div>

<script>
function updateDateTime() {
    const options = {
        timeZone: 'Asia/Manila',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    };

    const manilaTime = new Date().toLocaleString('en-US', options);
    const [datePart, timePart] = manilaTime.split(', ');
    const [month, day, year] = datePart.split('/');
    const formattedDateTime = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${timePart}`;
    
    document.getElementById('current-datetime').textContent = formattedDateTime;
}

function toggleCustomDate() {
    const form = document.getElementById('customDateForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}

function downloadPDF() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const timeFilter = urlParams.get('time_filter') || 'day';
    const startDate = urlParams.get('start_date') || '';
    const endDate = urlParams.get('end_date') || '';
    
    // Construct download URL
    let downloadUrl = `download_sales_pdf.php?time_filter=${timeFilter}`;
    if (startDate) downloadUrl += `&start_date=${startDate}`;
    if (endDate) downloadUrl += `&end_date=${endDate}`;
    
    window.location.href = downloadUrl;
}

function downloadExcel() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const timeFilter = urlParams.get('time_filter') || 'day';
    const startDate = urlParams.get('start_date') || '';
    const endDate = urlParams.get('end_date') || '';
    
    // Construct download URL
    let downloadUrl = `download_sales_excel.php?time_filter=${timeFilter}`;
    if (startDate) downloadUrl += `&start_date=${startDate}`;
    if (endDate) downloadUrl += `&end_date=${endDate}`;
    
    window.location.href = downloadUrl;
}

function viewDailyDetails(date) {
    try {
        fetch(`get_daily_details.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                let content = `<h2 style="margin-top: 0; padding-right: 40px;">Sales Details for ${date}</h2>`;
                
                let grandTotal = 0;
                
                for (const category in data) {
                    let categoryTotal = 0;
                    content += `
                        <div class="category-details">
                            <h3>${category}</h3>
                            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd;">Item</th>
                                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Size</th>
                                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Quantity</th>
                                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Price</th>
                                        <th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                    
                    data[category].forEach(item => {
                        const subtotal = item.quantity * item.price;
                        categoryTotal += subtotal;
                        grandTotal += subtotal;
                        
                        content += `
                            <tr>
                                <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.name}</td>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">${item.size}</td>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">${item.quantity}</td>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">₱${item.price.toFixed(2)}</td>
                                <td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">₱${subtotal.toFixed(2)}</td>
                            </tr>`;
                    });
                    
                    content += `
                            <tr>
                                <td colspan="4" style="text-align: right; padding: 8px; font-weight: bold;">
                                    Category Total:
                                </td>
                                <td style="text-align: right; padding: 8px; font-weight: bold;">
                                    ₱${categoryTotal.toFixed(2)}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>`;
                }
                
                // Add grand total at the bottom
                content += `
                    <div style="text-align: right; margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px;">
                        <strong>Grand Total: ₱${grandTotal.toFixed(2)}</strong>
                    </div>`;
                
                document.getElementById('modalContent').innerHTML = content;
                showModal();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading details');
            });
    } catch (error) {
        console.error('Error:', error);
        alert('Error loading details');
    }
}

function showModal() {
    const modal = document.getElementById('detailsModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('detailsModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Add escape key listener to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Initialize date inputs with current date if empty
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    const today = new Date().toISOString().split('T')[0];

    if (!startDate.value) startDate.value = today;
    if (!endDate.value) endDate.value = today;

    // Highlight active filter
    const urlParams = new URLSearchParams(window.location.search);
    const timeFilter = urlParams.get('time_filter');
    if (timeFilter) {
        document.querySelector(`button[value="${timeFilter}"]`)?.classList.add('active');
    }

    // Start the clock
    updateDateTime();
    setInterval(updateDateTime, 1000);
});
</script>
</body>
</html>