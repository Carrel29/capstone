<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../CSS/style.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Comfortaa:wght@400;700&family=M+PLUS+Rounded+1c:wght@400;700&display=swap"
    rel="stylesheet">

  <title>Home</title>
</head>

<body>
  <header>
    <h1 class="company-name">BTONE</h1>
    <nav class="nav-bar">
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#aboutus">About Us</a></li>
        <li>
      <a 
        href="<?php echo isset($_SESSION['loggedin']) && $_SESSION['loggedin'] ? 'booking-form.php' : '#'; ?>" 
        class="btn btn-view-now booking-top"
        <?php if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) echo 'onclick="openLoginModal(event)"'; ?>
      >
        Book
      </a>
    </li>
        <li class="dropdown">
          <a href="#"><img src="../Img/menu.png" alt="" class="img-round"></a>
          <ul class="dropdown-content">
            <li><a href="#">Cart</a></li>
            <li><a href="../includes/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
  <main>
    <section class="overview-section combo-display-flex">
      <div class="overview-content">
        <h2>Btone</h2>
        <p>
          Btone is your go-to spot for coffee, events, and live experiences. We offer a cozy café, venue rentals,
          catering services, and professional audio & lighting for any occasion. Whether it’s a small gathering or a
          full concert, Btone brings your event to life.
        </p>
        <div class="btn-group-card flex-start">
          <a class="btn btn-view-now pop-up-modal-js booking" href="booking-form.php">BOOK NOW</a>
        </div>
      </div>
      <img
        src="https://pixelz.cc/wp-content/uploads/2018/07/cup-of-coffee-and-roasted-beans-on-wood-table-uhd-4k-wallpaper.jpg"
        alt="placeholder" />
    </section>
      <section id="services">
        <div class="section-header">
        <h1 class="text-center">SERVICES</h1>
      </div>
    <div class="card-section">
      <!-- Wedding -->
      <div class="card"> 
        <img src="../Img/Wedding.png" alt="Wedding"> 
        <div class="card-content"> 
          <h3>Wedding</h3> 
            <ul> 
              <li>Venue rental for 8 hours</li> 
              <li>Event Coordination & Setup</li> 
              <li>Lights (2x)</li> 
              <li>Speakers (4x)</li> 
              <li>Tables & Chairs with linens</li> 
              <li>Backdrop & stage decor</li> 
              <li>Basic catering for 50 pax</li> 
            </ul> 
            <p class="price">₱50,000 (50 pax, ₱900 per head excess)</p> </div> 
          </div>
      <!-- Birthday Party -->
<div class="card">
  <img src="../Img/bday.png" alt="Birthday Party">
  <div class="card-content">
    <h3>Birthday Party</h3>
    <ul>
      <li>Themed backdrop & balloons</li>
      <li>Lights (2x)</li>
      <li>Speakers (2x)</li>
      <li>Tables & chairs with covers</li>
      <li>Basic catering for 30 pax</li>
    </ul>
    <p class="price">₱25,000 (30 pax, ₱500 per head excess)</p>
  </div>
</div>

<!-- Corporate Event -->
<div class="card">
  <img src="../Img/Corporate.png" alt="Corporate Event">
  <div class="card-content">
    <h3>Corporate Event</h3>
    <ul>
      <li>Professional stage & backdrop</li>
      <li>Projector & screen</li>
      <li>Lights (4x)</li>
      <li>Speakers (4x)</li>
      <li>Tables & chairs</li>
      <li>Basic catering for 100 pax</li>
    </ul>
    <p class="price">₱40,000 (100 pax, ₱700 per head excess)</p>
  </div>
</div>

<!-- Christening -->
<div class="card">
  <img src="../Img/Christening.png" alt="Christening">
  <div class="card-content">
    <h3>Christening</h3>
    <ul>
      <li>Simple backdrop & floral decor</li>
      <li>Lights (2x)</li>
      <li>Speakers (2x)</li>
      <li>Tables & chairs with linens</li>
      <li>Basic catering for 30 pax</li>
    </ul>
    <p class="price">₱20,000 (30 pax, ₱400 per head excess)</p>
  </div>
</div>

<!-- Debut -->
<div class="card">
  <img src="../Img/18th.png" alt="Debut">
  <div class="card-content">
    <h3>Debut</h3>
    <ul>
      <li>Themed stage & backdrop</li>
      <li>Lights (3x)</li>
      <li>Speakers (3x)</li>
      <li>Tables & chairs with covers</li>
      <li>Basic catering for 50 pax</li>
    </ul>
    <p class="price">₱35,000 (50 pax, ₱800 per head excess)</p>
  </div>
</div>

    </div>
  </section>
    <section id="aboutus" class="about-section py-1 px-5-percent">
      <div class="section-header">
        <h1 class="text-center">ABOUT US</h1>
      </div>

      <div class="card about-card">
      <div class="card-content">
        <div class="mission my-30">
          <p>
            Welcome to BTONE, your trusted destination for unforgettable events and exceptional service.
            We offer a convenient online booking system where you can easily reserve our event place and include our
            in-house catering services to complete your celebration. Whether it's a birthday, wedding, corporate
            gathering, or any special occasion, we’re here to make it seamless and memorable.
          </p>
        </div>
        <div class="vission my-30">
          <p><b>Address: </b>Km. 51 Manila East Road Barangay Concepcion, Baras, Rizal, Baras, Philippines</p>
          <p><b>Contact No.</b> 0917 140 1708</p>
          <p><b>Email ad: </b>btone_events@yahoo.com</p>
          <p><b>Facebook: </b><a href="https://www.facebook.com/Houbbuig"
              target="_blank">https://www.facebook.com/Houbbuig</a></p>
        </div>
      </div>
      <img src="https://www.shutterstock.com/image-photo/coffee-600nw-222414250.jpg" alt="">
    </div>
    </section>


    <!-- modal start -->
    <div class="pop-up-modal d-none">
      <div class="modal card">
        <div class="header-modal">
          <h2>Please Login</h2>
          <span class="close-modal">&times;</span>
        </div>
        <div class="modal-content">
          <p>
            You need to login to book a package.
          </p>
          <div class="btn-group-card">
            <a class="btn btn-view-now js-login" href="login.php">LOGIN</a>
          </div>
        </div>
      </div>
    </div>
    <!-- modal end -->

  </main>


  <footer>
    <div class="footer-content combo-display-flex">
      <p>BTONE 2025</p>
    </div>
  </footer>
  <script src="../JS/modal.js"></script>
  <script src="../JS/nav.js"></script>
  <script>
    // Pass PHP session variable to JavaScript
    const isLoggedIn = <?php echo isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] ? 'true' : 'false'; ?>;
    const message = "<?php echo $message; ?>";
    if (!isLoggedIn) {
      toggleDropdown.classList.add('d-none');
    }
  </script>
  <!-- toast start -->
  <div class="toast d-none <?php echo $isSuccess ? 'bg-green' : 'bg-red'; ?>">
    <div class="toast-body">
      <?php echo $message; ?>
    </div>
  </div>

  <!-- toast end -->
  <script src="../JS/toast.js"></script>
</body>

</html>