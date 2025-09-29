<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "btonedatabase");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Example: fetch booking by ID
$booking_id = 13; // change this dynamically
$sql = "
    SELECT b.id, b.btaddress, b.btevent, b.btschedule, b.total_cost, b.btattendees, 
           b.status, b.payment_status,
           u.bt_first_name, u.bt_last_name, u.bt_email, u.bt_phone_number
    FROM bookings b
    JOIN btuser u ON b.btuser_id = u.bt_user_id
    WHERE b.id = $booking_id
";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    $clientName = $row['bt_first_name'] . " " . $row['bt_last_name'];
    $clientEmail = $row['bt_email'];
    $clientAddress = $row['btaddress'];
    $eventName = $row['btevent'];
    $eventDate = date("F d, Y", strtotime($row['btschedule']));
    $totalCost = number_format($row['total_cost'], 2);
    $downpayment = number_format($row['total_cost'] * 0.20, 2);
    $balance = number_format($row['total_cost'] - ($row['total_cost'] * 0.20), 2);

    $moa = "
    MEMORANDUM OF AGREEMENT
    Between
    BTONE EVENTS PLACE
    -and-
    $clientName

    I. PARTIES
    This Memorandum of Agreement (“MOA”) is entered into on this " . date("F d, Y") . ", by and between:

    BTONE Events Place, a duly registered company with principal office at Baras, Rizal, represented herein by its duly authorized representative, hereinafter referred to as the “Events Place Provider”;

    -and-

    $clientName, with principal office/residential address at $clientAddress, represented herein by $clientName, hereinafter referred to as the “Client.”

    II. PURPOSE
    The purpose of this MOA is to establish the terms and conditions for the reservation, use, and payment of facilities at BTONE Events Place in Baras, Rizal, booked through its online platform for the event of the Client.

    III. TERMS AND CONDITIONS

    1. Downpayment and Booking
       * The Client shall reserve the venue through the official BTONE Events Place online booking system.
       * A 20% downpayment of the total rental fee is required to secure the booking.
       * The downpayment shall be deductible from the total rental fee.

    2. Use of Venue
       * The Events Place Provider grants the Client the right to use the venue located at Baras, Rizal, on the agreed date: $eventDate.
       * The Client shall use the venue only for lawful and agreed purposes.

    3. Payment Terms
       * Total rental fee: ₱$totalCost.
       * Downpayment: ₱$downpayment (non-refundable except as provided under cancellation terms).
       * Balance payment of ₱$balance shall be settled no later than 7 days before the event.

    4. Obligations of BTONE Events Place
       * Provide access to the venue and facilities as agreed.
       * Ensure the venue is clean, safe, and in good condition prior to the event.
       * Provide necessary support staff (if applicable and agreed upon).

    5. Obligations of the Client
       * Comply with venue rules and regulations.
       * Be responsible for the conduct of guests and participants during the event.
       * Shoulder any damages to the venue or facilities.
       * In cases where items are broken, lost, or damaged due to the Client or their attendees, the Client agrees to shoulder the cost of repair or replacement. A corresponding fee will be determined and mutually agreed upon by both parties after the event.

    6. Cancellations and Refunds
       * If canceled at least 4 weeks before the event date, 10% of the total rental fee will be refunded.
       * If canceled 3 weeks before the event, 5% of the total rental fee will be refunded.
       * If canceled less than 3 weeks before the event, no refund will be provided.

    7. Liability and Force Majeure
       * BTONE Events Place shall not be liable for any loss, accident, or damage to persons or property during the event, except when caused by gross negligence.
       * Neither party shall be held liable for failure to perform obligations due to force majeure events such as natural disasters, government restrictions, or other unforeseen circumstances beyond control.

    IV. EFFECTIVITY
    This MOA shall take effect on the date of confirmation via email by both parties and shall remain valid until the completion of the agreed event and full settlement of obligations.

    V. AMENDMENTS
    Any amendments to this MOA shall be made in writing and confirmed via email by both parties.

    VI. GOVERNING LAW
    This MOA shall be governed by and construed in accordance with the laws of the Republic of the Philippines.

    VII. CONFIRMATION VIA EMAIL
    As this MOA is transmitted electronically, no handwritten signatures are required. The parties agree that confirmation and acknowledgment via email shall constitute full and binding acceptance of this Agreement.
    ";

    echo nl2br($moa); // display formatted agreement
} else {
    echo "No booking found.";
}
?>
