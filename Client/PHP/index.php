<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";

// Database Connection for dynamic services
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

// Initialize services content
$services_content = null;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); // 5 second timeout
    
    // Fetch services from database with error handling
    $stmt = $pdo->prepare("SELECT * FROM services_content WHERE status = 'active' ORDER BY sort_order");
    $stmt->execute();
    $services_content = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Silently fail and use default services
    error_log("Database connection failed: " . $e->getMessage());
    $services_content = null;
} catch (Exception $e) {
    // Silently fail and use default services
    error_log("Service query failed: " . $e->getMessage());
    $services_content = null;
}

// Default services if database is not available
$default_services = [
    [
        'service_name' => 'Wedding',
        'image_path' => '../Img/Wedding.png',
        'price_info' => '₱50,000 (50 pax, ₱900 per head excess)',
        'features' => 'Venue rental for 8 hours|Event Coordination & Setup|Lights (2x)|Speakers (4x)|Tables & Chairs with linens|Backdrop & stage decor|Basic catering for 50 pax'
    ],
    [
        'service_name' => 'Birthday Party',
        'image_path' => '../Img/bday.png',
        'price_info' => '₱25,000 (30 pax, ₱500 per head excess)',
        'features' => 'Themed backdrop & balloons|Lights (2x)|Speakers (2x)|Tables & chairs with covers|Basic catering for 30 pax'
    ],
    [
        'service_name' => 'Corporate Event',
        'image_path' => '../Img/Corporate.png',
        'price_info' => '₱40,000 (100 pax, ₱700 per head excess)',
        'features' => 'Professional stage & backdrop|Projector & screen|Lights (4x)|Speakers (4x)|Tables & chairs|Basic catering for 100 pax'
    ],
    [
        'service_name' => 'Christening',
        'image_path' => '../Img/Christening.png',
        'price_info' => '₱20,000 (30 pax, ₱400 per head excess)',
        'features' => 'Simple backdrop & floral decor|Lights (2x)|Speakers (2x)|Tables & chairs with linens|Basic catering for 30 pax'
    ],
    [
        'service_name' => 'Debut',
        'image_path' => '../Img/18th.png',
        'price_info' => '₱35,000 (50 pax, ₱800 per head excess)',
        'features' => 'Themed stage & backdrop|Lights (3x)|Speakers (3x)|Tables & chairs with covers|Basic catering for 50 pax'
    ]
];

// Use database services if available, otherwise use defaults
if ($services_content && count($services_content) > 0) {
    $services_to_display = $services_content;
} else {
    $services_to_display = $default_services;
}

// Initialize message variables to prevent undefined variable errors
$message = isset($message) ? $message : '';
$isSuccess = isset($isSuccess) ? $isSuccess : false;

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

  <style>
.card-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 50px; /* Even more space between cards */
  margin: 30px 0;
  width: 100%;
}

.card-section .card {
  width: 90%;
  max-width: 900px;
  border: 1px solid #ddd;
  border-radius: 15px;
  overflow: hidden;
  background: white;
  box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.card-section .card .image-container {
  width: 100%;
  height: 400px; /* Very tall image area */
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
}

.card-section .card .image-container img {
  width: 100%;
  height: 100%;
  object-fit: cover; /* Fill the entire image area */
}

.card-section .card .card-content {
  padding: 40px;
}

.card-section .card .card-content h3 {
  font-size: 2.5em; /* Very large title */
  margin: 0 0 25px 0;
  color: #333;
  text-align: center;
}

.card-section .card .card-content ul {
  margin: 0 0 25px 0;
  padding-left: 30px;
  font-size: 1.2em;
  line-height: 1.6;
}

.card-section .card .card-content ul li {
  margin-bottom: 12px;
}

.card-section .card .card-content .price {
  font-size: 1.5em;
  font-weight: bold;
  color: #2c5530;
  text-align: center;
  margin: 0;
}
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
            <li><a href="user_cart.php">Cart</a></li>
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
          catering services, and professional audio & lighting for any occasion. Whether it's a small gathering or a
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
    <?php foreach ($services_to_display as $service): ?>
        <div class="card"> 
            <div class="image-container">
                <img src="<?php echo htmlspecialchars($service['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($service['service_name']); ?>"
                     onerror="this.src='../Img/placeholder-image.jpg'"> 
            </div>
            <div class="card-content"> 
                <h3><?php echo htmlspecialchars($service['service_name']); ?></h3> 
                <ul> 
                    <?php 
                    $features = isset($service['features']) ? explode('|', $service['features']) : [];
                    foreach ($features as $feature): 
                        if (!empty(trim($feature))): 
                    ?>
                    <li><?php echo htmlspecialchars($feature); ?></li> 
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </ul> 
                <p class="price"><?php echo htmlspecialchars($service['price_info']); ?></p> 
            </div> 
        </div>
    <?php endforeach; ?>
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
            gathering, or any special occasion, we're here to make it seamless and memorable.
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
    
    // Function to open login modal
    function openLoginModal(event) {
      event.preventDefault();
      const modal = document.querySelector('.pop-up-modal');
      if (modal) {
        modal.classList.remove('d-none');
      }
    }
    
    // Close modal functionality
    document.addEventListener('DOMContentLoaded', function() {
      const closeModal = document.querySelector('.close-modal');
      const modal = document.querySelector('.pop-up-modal');
      
      if (closeModal && modal) {
        closeModal.addEventListener('click', function() {
          modal.classList.add('d-none');
        });
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            modal.classList.add('d-none');
          }
        });
      }
      
      // Initialize dropdown functionality
      const dropdown = document.querySelector('.dropdown');
      if (dropdown && !isLoggedIn) {
        dropdown.classList.add('d-none');
      }
    });
  </script>
  
  <!-- toast start -->
  <?php if (!empty($message)): ?>
  <div class="toast <?php echo $isSuccess ? 'bg-green' : 'bg-red'; ?>">
    <div class="toast-body">
      <?php echo htmlspecialchars($message); ?>
    </div>
  </div>
  
  <script>
    // Auto-hide toast after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
      const toast = document.querySelector('.toast');
      if (toast) {
        setTimeout(function() {
          toast.classList.add('d-none');
        }, 5000);
      }
    });
  </script>
  <?php endif; ?>
  <!-- toast end -->
  
</body>

</html>