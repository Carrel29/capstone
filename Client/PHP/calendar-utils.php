<?php
function getBookingsForMonth($pdo, $year, $month) {
    $start = "$year-$month-01 00:00:00";
    $end   = date("Y-m-t 23:59:59", strtotime($start));
    $sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email, u.bt_phone_number 
            FROM bookings b 
            LEFT JOIN btuser u ON u.bt_user_id = b.btuser_id 
            WHERE b.btschedule BETWEEN :start AND :end";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start' => $start, ':end' => $end]);
    $bookings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = date('Y-m-d', strtotime($row['btschedule']));
        $bookings[$date][] = $row;
    }
    return $bookings;
}

function getDayColor($bookingsOnDay, $threshold = 3) {
    $pendingApproved = 0;
    $fullyBooked = false;
    foreach ($bookingsOnDay as $booking) {
        if (in_array($booking['status'], ['Pending', 'Approved'])) {
            $pendingApproved++;
            $start = strtotime($booking['btschedule']);
            $end = strtotime($booking['EventDuration']);
            if (($end - $start) >= 60*60*23) $fullyBooked = true;
        }
    }
    if ($fullyBooked || $pendingApproved >= $threshold) return 'red';
    if ($pendingApproved > 0) return 'yellow';
    return 'green';
}
?>