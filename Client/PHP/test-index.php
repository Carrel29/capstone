<?php
session_start();

// Simple hardcoded services without database
$services_to_display = [
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
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BTONE - Home</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
    header { background: #422b0d; color: white; padding: 1rem; }
    .card-section { display: flex; flex-wrap: wrap; padding: 2rem; }
    .card { border: 1px solid #ddd; margin: 1rem; padding: 1rem; max-width: 300px; }
    .card img { max-width: 100%; height: auto; }
  </style>
</head>
<body>
  <header>
    <h1>BTONE</h1>
    <nav>
      <a href="index.php">Home</a> | 
      <a href="#services">Services</a> | 
      <a href="#aboutus">About Us</a>
    </nav>
  </header>

  <section id="services">
    <h2>Our Services</h2>
    <div class="card-section">
      <?php foreach ($services_to_display as $service): ?>
        <div class="card">
          <img src="<?php echo $service['image_path']; ?>" alt="<?php echo $service['service_name']; ?>">
          <h3><?php echo $service['service_name']; ?></h3>
          <p><?php echo $service['price_info']; ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</body>
</html>