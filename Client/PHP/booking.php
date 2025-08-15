<?php
session_start();

include_once "../includes/dbh.inc.php";
include_once "../includes/userData.php";
include_once "../includes/allData.php";

$data = new AllData($pdo);
$allBookingAndUser = $data->getBookingAndUserById($user_id);
$services = $data->getAllServices();

$totalPrice = $data->computeTotalPrices($allBookingAndUser['btservices']);

// Fetch bookings to display on the calendar
$bookings = $data->getAllBookings(); // should return an array with date, start_time, end_time, status
$bookingData = json_encode($bookings);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../CSS/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> <!-- lightweight calendar -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="../JS/payment.js"></script>
  <title>Booking Receipt</title>
  <style>
    .calendar-legend span {
      display: inline-block;
      width: 15px;
      height: 15px;
      margin-right: 5px;
    }
    .available { background-color: #fff; border: 1px solid #ccc; }
    .partial { background-color: yellow; }
    .full { background-color: red; color: white; }
  </style>
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
          <!-- other booking info here -->

          <p class="my-30 combo-display-flex-space-between"><span class="bold">Select Date:</span>
            <input type="text" id="bookingCalendar" class="form-control">
          </p>
          <div class="calendar-legend">
            <p><span class="full"></span> Fully booked</p>
            <p><span class="partial"></span> Partially booked</p>
            <p><span class="available"></span> Available</p>
          </div>

          <p class="my-30 combo-display-flex-space-between"><span class="bold">Select Time:</span>
            <select id="bookingTime" name="btschedule" class="form-control">
              <option value="">Select Time</option>
              <option value="Morning">Morning</option>
              <option value="Afternoon">Afternoon</option>
              <option value="Evening">Evening</option>
            </select>
          </p>
        </div>

        <div class="btn-group-card w-100 my-30">
          <a class="btn btn-view-now" type="submit"> Cancel </a>
          <a class="btn btn-view-now mx-20 payment-confirm" type="submit"> Confirm </a>
        </div>
      </div>
    </div>
  </main>

  <script>
    const bookings = <?php echo $bookingData; ?>;

    // Transform booking data into calendar events
    const bookedDates = {};
    bookings.forEach(b => {
      if (!bookedDates[b.date]) bookedDates[b.date] = [];
      bookedDates[b.date].push({ start: b.start_time, end: b.end_time, status: b.status });
    });

    flatpickr("#bookingCalendar", {
      inline: true,
      dateFormat: "Y-m-d",
      disable: [
        function(date) {
          const d = date.toISOString().split('T')[0];
          if (bookedDates[d]) {
            const statuses = bookedDates[d].map(x => x.status);
            if (statuses.includes('full')) return true; // disable fully booked dates
          }
          return false;
        }
      ],
      onDayCreate: function(dObj, dStr, fp, dayElem) {
        const date = dayElem.dateObj.toISOString().split('T')[0];
        if (bookedDates[date]) {
          const statuses = bookedDates[date].map(x => x.status);
          if (statuses.includes('full')) {
            dayElem.className += " full";
          } else if (statuses.includes('partial')) {
            dayElem.className += " partial";
            dayElem.title = bookedDates[date].map(x => `${x.start} - ${x.end}`).join(", ");
          } else {
            dayElem.className += " available";
          }
        } else {
          dayElem.className += " available";
        }
      }
    });
  </script>

</body>

</html>
