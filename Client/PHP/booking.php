<?php
session_start();

include_once "../includes/dbh.inc.php";
include_once "../includes/userData.php";
include_once "../includes/allData.php";

$data = new AllData($pdo);
$allBookingAndUser = $data->getBookingAndUserById($user_id);
$services = $data->getAllServices();

$totalPrice = $data->computeTotalPrices($allBookingAndUser['btservices']);

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../CSS/style.css" />
  <script src="../JS/payment.js"></script>
  <title>Booking Receipt</title>
</head>

<body>
  <header>
    <h1 class="company-name">BTONE</h1>
    <nav class="nav-bar">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#aboutus">About Us</a></li>
        <li class="dropdown">
          <a href="#"><img src="../Img/menu.png" alt="" class="img-round"></a>
          <ul class="dropdown-content">
            <li><a href="#"><?php echo $fullname ?></a></li>
            <li><a href="../includes/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <main class="combo-display-flex-start h-100 gap-20">
    <div class="card w-50">
      <div class="combo-display-flex-column-start w-100 mx-20">
        <h2>Booking Receipt</h2>
        <div class="w-100 bottom-border">
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Name:</span>
            <?php echo $allBookingAndUser['fullname'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Address:</span>
            <?php echo $allBookingAndUser['btaddress'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Contact No.:</span>
            <?php echo $allBookingAndUser['bt_phone_number'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Email:</span>
            <?php echo $allBookingAndUser['bt_email'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Event Type:</span>
            <?php echo $allBookingAndUser['btevent'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Schedule:</span>
            <?php echo $allBookingAndUser['btschedule'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">No. of Attendees:</span>
            <?php echo $allBookingAndUser['btattendees'] ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Services:</span>
            <?php
            $services = $allBookingAndUser['btservices'];
            $displayServices = (strlen($services) > 50) ? substr($services, 0, 50) . '...' : $services;
            echo '<span title="' . htmlspecialchars($services, ENT_QUOTES, 'UTF-8') . '">' . $displayServices . '</span>';
            ?>
          </p>
          <p class="my-30 combo-display-flex-space-between"><span class="bold">Message:</span>
            <?php echo $allBookingAndUser['btmessage'] ?>
          </p>
        </div>

        <div class="btn-group-card w-100 my-30">
          <a class="btn btn-view-now" type="submit"> Cancel </a>
          <a class="btn btn-view-now mx-20 payment-confirm" type="submit"> Confirm </a>
        </div>
      </div>
    </div>

    <div class="card w-50 d-none payment">
      <div class="combo-display-flex-column-start w-100 mx-20">
        <h2 class="">Payment</h2>
        <div class="w-100">
          <select name="payment_method" class="form-control my-30" id="payment_method" required>
            <option value="" disabled selected>Select Payment Method</option>
            <option value="Gcash">G-Cash</option>
          </select>

          <form action="../includes/payment.php" method="POST" class="gcash d-none">
            <div class="combo-display-flex flex-start">
              <img src="../Img/sample.png" alt="g-cash QR code" class="qr-code">
              <div class="combo-display-flex-column-start mx-20">
                <h3>G-Cash QR Code</h3>
                <p>Scan the QR code to pay</p>
                <p>Account Name: BTONE</p>
                <p>G-cash No: 09-0000-0000</p>
              </div>
            </div>
            <div class="my-30">
              <span class="amount-display"><strong>Total Amount:</strong> <?php echo $totalPrice ?></span>
            </div>
            <input type="hidden" name="btuser_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <input type="hidden" name="booking_id"
              value="<?php echo htmlspecialchars($allBookingAndUser['booking_id']); ?>">
            <input type="hidden" name="paymentAmount" value="<?php echo htmlspecialchars($totalPrice); ?>">

            <div class="w-100">
              <input type="number" name="ref" id="refId" placeholder="Reference No." class="form-control my-30"
                pattern="\d{13,15}" title="Reference number must be between 13 to 15 digits" required>
              <span class="note">Note: Double-check the reference number before proceeding.</span>
            </div>
            <div class="btn-group-card w-100  my-30 gap-20">
              <a class="prices-link view-computation"> View Computation </a>
              <input class="btn btn-view-now payment-proceed" type="submit" value="Payment">
            </div>
          </form>
        </div>
  </main>

  <div class="toast d-none <?php echo $isSuccess ? 'bg-green' : 'bg-red'; ?>">
    <div class="toast-body">
      <?php echo $message; ?>
    </div>
  </div>

  <!-- toast end -->
  <script src="../JS/toast.js"></script>
<script>
document.querySelector('.payment-proceed').addEventListener('click', function(e) {
    e.preventDefault();
    
    const form = document.querySelector('.gcash');
    const formData = new FormData(form);
    
    fetch('../includes/payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // Update the booking status to 'Confirmed' after payment
            return fetch('../includes/update_booking_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    booking_id: formData.get('booking_id'),
                    status: 'Confirmed',
                    payment_status: 'paid',
                    payment_ref: formData.get('ref')
                })
            });
        } else {
            throw new Error(data.message);
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Payment successful! Your booking has been confirmed.');
            window.location.href = 'index.php'; // Redirect to home page
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    });
});
</script>
</body>

</html>