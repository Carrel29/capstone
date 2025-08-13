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
    <div class="card-section">
      <div class="card">
        <div class="card-content">
        </div>
        <img
          src="https://scontent.fmnl4-1.fna.fbcdn.net/v/t39.30808-6/490303249_1145288537615239_445907435890221450_n.jpg?_nc_cat=103&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeGs43N94GhC-__pPON565ObpxPSK7_3g1WnE9Irv_eDVdV6kP0XkUuW2jQtZ7Xencn-x-r3_berWew1IMI4jQ_L&_nc_ohc=l1UFba35v5sQ7kNvwHcnx_O&_nc_oc=AdkEi40ZoQinNw6mHjTOcIM16YLL0IQS2Xa6bGOXh-7ACy3btj1RKR3_fRrNb-SlkmervxbTG-BguZ5y3b7k_HJz&_nc_zt=23&_nc_ht=scontent.fmnl4-1.fna&_nc_gid=ZupgnZvM-AM-9uh1fDGQBA&oh=00_AfLNWprFPN4VFEcEIu-BnVz2RZAdH8q8eWhcjYFKSse-aA&oe=6822C747"
          alt="">
        <div class="event-info">
          <h3>Wedding Package</h3>
          <p><strong>Base Price:</strong> ₱50,000 (50 pax, ₱900/additional head)</p>
          <ul>
            <li>Event coordination & setup</li>
            <li>Lights (2x)</li>
            <li>Speakers (4x)</li>
            <li>Backdrop & stage decor</li>
            <li>Tables & chairs with linens</li>
            <li>Basic catering for 50 pax</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
        </div>
        <img
          src="https://scontent.fmnl4-4.fna.fbcdn.net/v/t39.30808-6/489952865_1145288264281933_377618046300838303_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=127cfc&_nc_eui2=AeHVuwnqOzOckeRVmySiBcr00-tlRVqrL6_T62VFWqsvr8BEKJCSTkx2SfsPZsw1KsXfZogbd9UFDczQW5qYHsnY&_nc_ohc=RQSFEKMXE0MQ7kNvwFWVxcd&_nc_oc=Admy43BGGA_cm2rmLpto6p3DX9adGmqpXU-fDfFsYflheYKni00o2b8XJ2_0PiakWQ9MG4prxBE3xeF3XnSHHmfn&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&_nc_gid=99yU4cySkf30tYoZw6d7gQ&oh=00_AfJoOEZvcPYa2yvzUSvYzINBJMOpX00VLbriibLnFyis7Q&oe=6822B2B9"
          alt="Btone">
        <div class="event-info">
          <h3>Birthday Party</h3>
          <p><strong>Base Price:</strong> ₱25,000 (30 pax, ₱500/additional head)</p>
          <ul>
            <li>Themed backdrop & balloons</li>
            <li>Lights (2x)</li>
            <li>Speakers (2x)</li>
            <li>Tables & chairs with covers</li>
            <li>Basic catering for 30 pax</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
        </div>
        <img
          src="https://scontent.fmnl4-7.fna.fbcdn.net/v/t39.30808-6/472554018_1066996975444396_3956362196041986281_n.jpg?_nc_cat=108&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeGXH30RUotMnV1EOaK6ct6kujgAuS9__qy6OAC5L3_-rI0xnXiaL0I3V5N2018OrITye7r17JfxqfkmFyV8bAw7&_nc_ohc=wH7ZR59793UQ7kNvwHw5d1s&_nc_oc=Adl1rX5MOdQvOeIP1dzMnNyGo0m7HrCuy9ms0_NUlNYgRUW1ISrwFWiPewMnD2r24Y3LmxUbYxH8Wp1TNUwk_54l&_nc_zt=23&_nc_ht=scontent.fmnl4-7.fna&_nc_gid=79381u6pUb0PGm848oAJAg&oh=00_AfJwX_8SliXIly1GSN1poI58XmBFY0-RUg1ql73bFlYD1g&oe=6822CBEF"
          alt="Btone">
        <div class="event-info">
          <h3>Corporate Event</h3>
          <p><strong>Base Price:</strong> ₱40,000 (100 pax, ₱700/additional head)</p>
          <ul>
            <li>Professional stage & backdrop</li>
            <li>Projector & screen</li>
            <li>Lights (4x)</li>
            <li>Speakers (4x)</li>
            <li>Tables & chairs</li>
            <li>Basic catering for 100 pax</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
        </div>
        <img
          src="https://scontent.fmnl4-2.fna.fbcdn.net/v/t39.30808-6/494351188_1227894656011759_2296900455128356496_n.jpg?stp=cp6_dst-jpg_s600x600_tt6&_nc_cat=105&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeFYFA0T5Qa4bO0pcScdCTjW_ezdJ8P5DzT97N0nw_kPNF_vTuoE2tX2H7OdekYDMoPRiZEjwCYhMSLJid_nGHs6&_nc_ohc=av06CBpMD2MQ7kNvwEM4ZCM&_nc_oc=AdlhqCd3DZL8PNTGT8FiVbXWt47u0lsoXIjNkb4bLebvgYzFrIr0eXCtlnoSIWGU0rMPDq59xt3eU5lUWAUEJRm_&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&_nc_gid=3yaYsquCptAUZn4cd1xebQ&oh=00_AfK7SRwqnDvNGsSFUMIeEimf3me2wI538AJbtSBCCxqIpw&oe=6822B9C0"
          alt="Btone">
        <div class="event-info">
          <h3>Christening</h3>
          <p><strong>Base Price:</strong> ₱20,000 (30 pax, ₱400/additional head)</p>
          <ul>
            <li>Simple backdrop & floral decor</li>
            <li>Lights (2x)</li>
            <li>Speakers (2x)</li>
            <li>Tables & chairs with linens</li>
            <li>Basic catering for 30 pax</li>
          </ul>
        </div>
      </div>

      <div class="card">
        <div class="card-content">
        </div>
        <img
          src="https://scontent.fmnl4-4.fna.fbcdn.net/v/t39.30808-6/475592200_122208751952199448_8444367844632348596_n.jpg?_nc_cat=102&ccb=1-7&_nc_sid=833d8c&_nc_eui2=AeEZ7abhFvbbczUs96Atz_oMTWTmDJgFApJNZOYMmAUCkvm0JGvtgUgh_UoU5yGmMeF0iF79wOqtGuY62vTA8gdK&_nc_ohc=xwOZAcW3N8IQ7kNvwHy8b-M&_nc_oc=Adn26wT1BKapqUx2jl7KpSU7zeFjEFZLZCiYuzJKZA7Hq8S-y9cnIl-0978wQlra-m6jbwZHrKiVSkFOdW5JApbq&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&_nc_gid=a2WUu7FHsrwCMUQpyd49vA&oh=00_AfLP8qnoJR31sXuXG8HES1m-BxEi7KhqGi7584lNNWccLQ&oe=6822C545"
          alt="Btone">
        <div class="event-info">
          <h3>Debut</h3>
          <p><strong>Base Price:</strong> ₱35,000 (50 pax, ₱800/additional head)</p>
          <ul>
            <li>Themed stage & backdrop</li>
            <li>Lights (3x)</li>
            <li>Speakers (3x)</li>
            <li>Tables & chairs with covers</li>
            <li>Basic catering for 50 pax</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
    <section id="aboutus" class="about-section py-1 px-5-percent">
      <div class="section-header">
        <h1 class="text-center">ABOUT US</h1>
      </div>

      <div class="card-section">
        <div class="card">
          <div class="card-content">
            <div class="mission my-30">
              <p>
                Welcome to [Business Name], your trusted destination for unforgettable events and exceptional service.
                We offer a convenient online booking system where you can easily reserve our event place and include our
                in-house catering services to complete your celebration. Whether it's a birthday, wedding, corporate
                gathering, or any special occasion, we’re here to make it seamless and memorable.
              </p>
            </div>
            <div class="vission my-30">
              <p>
              <p><b>Address: </b>Km. 51 Manila East Road Barangay Concepcion, Baras, Rizal, Baras, Philippines</p>
              <p><b>Contact No.</b> 0917 140 1708</p>
              <p><b>Email ad: </b></p>btone_events@yahoo.com.php>
              <p><b>Facebook: </b><a href="https://www.facebook.com/Houbbuig"
                  target="_blank">https://www.facebook.com/Houbbuig</a></p>
              </p>
            </div>
          </div>
          <img src="https://www.shutterstock.com/image-photo/coffee-600nw-222414250.jpg" alt="">
        </div>
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