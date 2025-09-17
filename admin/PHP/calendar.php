<?php
require_once "../../Client/php/calendar-utils.php";
// Use the same PDO connection as in your dashboard
if (!isset($pdo)) {
    // If $pdo not defined, connect (for direct access)
    $host = 'localhost';
    $db   = 'btonedatabase';
    $user = 'root';
    $pass = '';
    $port = '3306';
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        exit("Database connection failed: " . $e->getMessage());
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status=? WHERE id=?");
    $stmt->execute([$_POST['status'], $_POST['booking_id']]);
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$bookings = getBookingsForMonth($pdo, $year, str_pad($month,2,"0",STR_PAD_LEFT));
$firstDay = date('N', strtotime("$year-$month-01"));
$daysInMonth = date('t', strtotime("$year-$month-01"));
?>
<style>
body {
    background: #f7f8fb;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
}
.back-btn {
    display: inline-block;
    padding: 8px 18px;
    background: #36A2EB;
    color: #fff;
    border: none;
    border-radius: 22px;
    font-weight: bold;
    margin: 24px 0 0 24px;
    font-size: 1rem;
    box-shadow: 0 2px 4px rgba(54,162,235,0.13);
    cursor: pointer;
    text-decoration: none;
    transition: background 0.18s;
}
.back-btn:hover {
    background: #0074d9;
    color: #fff;
}
.calendar-main {
    max-width: 600px;
    margin: 32px auto 0 auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(54,162,235,0.09);
    padding: 32px 18px 24px 18px;
}
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 18px;
    margin-bottom: 14px;
}
.calendar-arrow {
    background: none;
    border: none;
    font-size: 1.7rem;
    color: #36A2EB;
    cursor: pointer;
    transition: color 0.18s;
}
.calendar-arrow:hover {
    color: #0074d9;
}
.calendar-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px 6px;
    text-align: center;
    background: #f2f6fa;
    border-radius: 12px;
    overflow: hidden;
}
.calendar-table th {
    background: #36A2EB;
    color: #fff;
    padding: 12px 0;
    font-weight: 500;
    letter-spacing: 1px;
    border-radius: 6px;
}
.calendar-day {
    background: #75d377;
    color: #fff;
    padding: 13px 0;
    border-radius: 7px;
    cursor: pointer;
    font-size: 1.18rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.09);
    transition: background 0.2s, color 0.15s, transform 0.15s;
    outline: none;
    border: none;
}
.calendar-day[data-color="yellow"] {
    background: #ffe066;
    color: #5e5e00;
}
.calendar-day[data-color="red"] {
    background: #f36c6c;
    color: #fff;
}
.calendar-day:hover {
    transform: scale(1.06);
    background: #36A2EB !important;
    color: #fff !important;
}
.legend {
    margin: 18px 0 0 0;
    text-align: center;
}
.legend span {
    display: inline-block;
    margin: 0 10px;
    padding: 3px 15px;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 500;
}
.legend .available { background: #75d377; color: #fff; }
.legend .partial   { background: #ffe066; color: #5e5e00; }
.legend .full      { background: #f36c6c; color: #fff; }

.modal-bg {
    display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.23); z-index:1000;
}
.modal {
    background:#fff; padding:24px 20px 16px 20px; border-radius:12px; max-width:430px; margin:7% auto 0 auto; position:relative;
    box-shadow: 0 6px 30px rgba(54,162,235,0.13);
}
@media(max-width:650px){ .calendar-main{max-width:99vw;padding:12px;} }
@media(max-width:500px){ .modal{max-width:98vw;} }
.modal h4 {margin-top:0;}
.close-modal-btn {
    display: inline-block;
    margin-top: 18px;
    background: #36A2EB;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 7px 18px;
    font-size: 1.01rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.16s;
}
.close-modal-btn:hover {
    background: #0074d9;
}
.booking-entry {
    border-bottom:1px solid #e7e7e7;
    margin-bottom:12px;
    padding-bottom:9px;
}
.booking-entry:last-child {border-bottom:none;}
.booking-label {font-weight:600;}
</style>
<script>
function showModal(id) {
    document.getElementById('modal-bg-'+id).style.display = 'block';
}
function hideModal(id) {
    document.getElementById('modal-bg-'+id).style.display = 'none';
}
</script>

<a href="dashboard.php" class="back-btn">&#8592; Back to Dashboard</a>
<div class="calendar-main">
    <div class="calendar-header">
        <form style="display:inline;" method="get">
            <input type="hidden" name="year" value="<?=$month==1?$year-1:$year;?>">
            <input type="hidden" name="month" value="<?=$month==1?12:$month-1;?>">
            <button class="calendar-arrow" type="submit">&#8592;</button>
        </form>
        <span style="font-size:1.35rem;font-weight:600;">
            <?=date('F Y', strtotime("$year-$month-01"));?>
        </span>
        <form style="display:inline;" method="get">
            <input type="hidden" name="year" value="<?=$month==12?$year+1:$year;?>">
            <input type="hidden" name="month" value="<?=$month==12?1:$month+1;?>">
            <button class="calendar-arrow" type="submit">&#8594;</button>
        </form>
    </div>
    <table class="calendar-table">
        <tr>
            <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
        </tr>
        <tr>
            <?php
            for ($blank=1; $blank<$firstDay; $blank++) echo "<td></td>";
            for ($day=1, $cell=$firstDay; $day<=$daysInMonth; $day++, $cell++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $color = isset($bookings[$dateStr]) ? getDayColor($bookings[$dateStr]) : 'green';
                $data_color = $color;
                if ($color == 'yellow') $data_color = 'yellow';
                elseif ($color == 'red') $data_color = 'red';
                else $data_color = 'green';
                echo "<td class='calendar-day' data-color='$data_color' onclick=\"showModal('$dateStr')\">$day</td>";
                if ($cell%7==0) echo "</tr><tr>";
            }
            ?>
        </tr>
    </table>
    <div class="legend">
        <span class="available">Available</span>
        <span class="partial">Partially</span>
        <span class="full">Fully Booked</span>
    </div>
</div>
<?php
for ($day=1; $day<=$daysInMonth; $day++) {
    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
    echo "<div class='modal-bg' id='modal-bg-$dateStr' onclick=\"hideModal('$dateStr')\"><div class='modal' onclick=\"event.stopPropagation()\">";
    echo "<h4>Bookings for ".date('F d, Y',strtotime($dateStr))."</h4>";
    if (isset($bookings[$dateStr]) && count($bookings[$dateStr]) > 0) {
        foreach ($bookings[$dateStr] as $b) {
            echo "<div class='booking-entry'>";
            echo "<span class='booking-label'>Event:</span> ".htmlspecialchars($b['btevent'])."<br>";
            echo "<span class='booking-label'>Time:</span> ".date('H:i',strtotime($b['btschedule']))."<br>";
            echo "<span class='booking-label'>Status:</span> ";
            echo "<form method='post' style='display:inline;'>";
            echo "<input type='hidden' name='booking_id' value='".$b['id']."'>";
            echo "<select name='status' onchange='this.form.submit()'>";
            foreach (['Pending','Approved','Canceled','Completed'] as $status) {
                $sel = $b['status']==$status ? 'selected' : '';
                echo "<option value='$status' $sel>$status</option>";
            }
            echo "</select></form><br>";
            echo "<span class='booking-label'>Customer:</span> ".htmlspecialchars($b['bt_first_name']." ".$b['bt_last_name'])."<br>";
            echo "<span class='booking-label'>Email:</span> ".htmlspecialchars($b['bt_email'])."<br>";
            echo "<span class='booking-label'>Phone:</span> ".htmlspecialchars($b['bt_phone_number']);
            echo "</div>";
        }
    } else {
        echo "<p>No bookings for this date.</p>";
    }
    echo "<button class='close-modal-btn' onclick=\"hideModal('$dateStr')\">Close</button>";
    echo "</div></div>";
}
?>