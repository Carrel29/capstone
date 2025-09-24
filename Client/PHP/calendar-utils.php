<?php
function getBookingsForMonth($pdo, $year, $month) {
    $start = "$year-$month-01 00:00:00";
    $end   = date("Y-m-t 23:59:59", strtotime($start));
    $sql = "SELECT b.*, u.bt_first_name, u.bt_last_name, u.bt_email, u.bt_phone_number 
            FROM bookings b 
            LEFT JOIN btuser u ON u.bt_user_id = b.btuser_id 
            WHERE b.btschedule BETWEEN :start AND :end 
            AND b.payment_status = 'paid' 
            AND b.status != 'Cancelled'";
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
    if (empty($bookingsOnDay)) return 'green';
    
    $totalHoursBooked = 0;
    $bookedSlots = [];
    
    foreach ($bookingsOnDay as $booking) {
        $start = strtotime($booking['btschedule']);
        $end = strtotime($booking['EventDuration']);
        $durationHours = ($end - $start) / 3600;
        $totalHoursBooked += $durationHours;
        
        $bookedSlots[] = [
            'start' => date('g:i A', $start),
            'end' => date('g:i A', $end),
            'duration' => $durationHours
        ];
    }
    
    // If more than 12 hours are booked, consider it fully booked
    if ($totalHoursBooked >= 12) return 'red';
    
    // If any bookings exist, it's partially booked
    if ($totalHoursBooked > 0) return 'yellow';
    
    return 'green';
}

function getAvailableTimeSlots($bookings) {
    $allSlots = [
        ['08:00', '12:00'], // Morning
        ['12:00', '16:00'], // Afternoon  
        ['16:00', '20:00'], // Evening
        ['20:00', '24:00']  // Night
    ];
    
    $bookedSlots = [];
    foreach ($bookings as $booking) {
        $start = date('H:i', strtotime($booking['btschedule']));
        $end = date('H:i', strtotime($booking['EventDuration']));
        $bookedSlots[] = [$start, $end];
    }
    
    $availableSlots = [];
    foreach ($allSlots as $slot) {
        $slotStart = $slot[0];
        $slotEnd = $slot[1];
        $isAvailable = true;
        
        foreach ($bookedSlots as $booked) {
            $bookedStart = $booked[0];
            $bookedEnd = $booked[1];
            
            // Check if slots overlap
            if (!($slotEnd <= $bookedStart || $slotStart >= $bookedEnd)) {
                $isAvailable = false;
                break;
            }
        }
        
        if ($isAvailable) {
            $availableSlots[] = [
                'start' => date('g:i A', strtotime($slotStart)),
                'end' => date('g:i A', strtotime($slotEnd))
            ];
        }
    }
    
    return $availableSlots;
}

function getBookedTimesFormatted($bookings) {
    $bookedTimes = [];
    foreach ($bookings as $booking) {
        $start = date('g:i A', strtotime($booking['btschedule']));
        $end = date('g:i A', strtotime($booking['EventDuration']));
        $bookedTimes[] = "$start - $end";
    }
    return $bookedTimes;
}
?>