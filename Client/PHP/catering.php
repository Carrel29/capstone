<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";
include_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Get the current booking ID from session
if (!isset($_SESSION['current_booking_id'])) {
    // If no current booking, redirect to booking form
    header("Location: booking-form.php");
    exit();
}

$booking_id = $_SESSION['current_booking_id'];

// Verify the booking exists and belongs to the user
$booking_stmt = $pdo->prepare("SELECT id, btevent, btschedule FROM bookings WHERE id = ? AND btuser_id = ? AND status = 'Pending'");
$booking_stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $booking_stmt->fetch();

if (!$booking) {
    // Invalid booking, redirect to booking form
    header("Location: booking-form.php");
    exit();
}

// Fetch catering data from database
$catering_packages = $pdo->query("SELECT * FROM catering_packages WHERE status = 'active'")->fetchAll();
$catering_dishes = $pdo->query("SELECT * FROM catering_dishes WHERE status = 'active' ORDER BY category, name")->fetchAll();
$catering_addons = $pdo->query("SELECT * FROM catering_addons WHERE status = 'active'")->fetchAll();

// Group dishes by category
$dishes_by_category = [];
foreach ($catering_dishes as $dish) {
    $category = $dish['category'] ?: 'Other';
    $dishes_by_category[$category][] = $dish;
}

// Initialize selected dishes and addons in session if not set
if (!isset($_SESSION['selected_dishes'])) {
    $_SESSION['selected_dishes'] = [];
}
if (!isset($_SESSION['selected_addons'])) {
    $_SESSION['selected_addons'] = [];
}

// Handle catering form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_catering'])) {
        try {
            $package_id = $_POST['package_id'];
            
            // Save catering order to database
            $stmt = $pdo->prepare("INSERT INTO catering_orders (booking_id, package_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$booking_id, $package_id]);
            
            $catering_order_id = $pdo->lastInsertId();
            
            $_SESSION['catering_order_id'] = $catering_order_id;
            $_SESSION['selected_package'] = $package_id;
            $_SESSION['selected_dishes'] = []; // Reset selected dishes when package changes
            $_SESSION['success_message'] = "Catering package selected successfully!";
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error selecting catering package: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_dishes'])) {
        try {
            if (!isset($_SESSION['catering_order_id'])) {
                throw new Exception("Please select a catering package first");
            }
            
            // Get selected dishes from form
            $selected_dishes = isset($_POST['dishes']) ? $_POST['dishes'] : [];
            
            // Validate dish selection based on package rules
            $package_id = $_SESSION['selected_package'];
            $package = null;
            foreach ($catering_packages as $pkg) {
                if ($pkg['id'] == $package_id) {
                    $package = $pkg;
                    break;
                }
            }
            
            if ($package) {
                $dish_count = $package['dish_count'];
                $selected_meat = 0;
                $selected_vegetables = 0;
                $selected_pasta = 0;
                $selected_dessert = 0;
                $selected_juice = 0;
                
                // Count selected dishes by category (only main dishes count toward the package limit)
                foreach ($selected_dishes as $dish_id) {
                    $dish = null;
                    foreach ($catering_dishes as $d) {
                        if ($d['id'] == $dish_id) {
                            $dish = $d;
                            break;
                        }
                    }
                    
                    if ($dish) {
                        $category = strtolower($dish['category'] ?: '');
                        if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'null', ''])) {
                            $selected_meat++;
                        } else if ($category == 'vegetables') {
                            $selected_vegetables++;
                        } else if ($category == 'pasta') {
                            $selected_pasta++;
                        } else if ($category == 'dessert') {
                            $selected_dessert++;
                        } else if ($category == 'juice') {
                            $selected_juice++;
                        }
                    }
                }
                
                // Count only main dishes for package validation
                $main_dish_count = $selected_meat + $selected_vegetables + $selected_pasta;
                
                // Validate dessert and juice limits
                if ($selected_dessert > 2) {
                    throw new Exception("Maximum of 2 dessert selections allowed");
                }
                
                if ($selected_juice > 2) {
                    throw new Exception("Maximum of 2 juice selections allowed");
                }
                
                // Validate main dish count matches package requirement
                if ($main_dish_count != $dish_count) {
                    throw new Exception("Please select exactly {$dish_count} main dishes (meat, vegetables, pasta)");
                }
                
                // Validate based on package rules (only main dishes)
                $is_valid = false;
                if ($dish_count == 5) {
                    // 5-dish package: 1 vegetables + 4 meat OR 1 vegetables + 3 meat + 1 pasta
                    if (($selected_vegetables == 1 && $selected_meat == 4 && $selected_pasta == 0) ||
                        ($selected_vegetables == 1 && $selected_meat == 3 && $selected_pasta == 1)) {
                        $is_valid = true;
                    }
                } else if ($dish_count == 4) {
                    // 4-dish package: 1 vegetables + 3 meat OR 1 vegetables + 2 meat + 1 pasta
                    if (($selected_vegetables == 1 && $selected_meat == 3 && $selected_pasta == 0) ||
                        ($selected_vegetables == 1 && $selected_meat == 2 && $selected_pasta == 1)) {
                        $is_valid = true;
                    }
                }
                
                if (!$is_valid) {
                    throw new Exception("Invalid dish selection. Please follow the package rules.");
                }
                
                // Update selected dishes in session
                $_SESSION['selected_dishes'] = $selected_dishes;
                $_SESSION['success_message'] = "Dish selection updated successfully!";
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating dish selection: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['clear_all'])) {
        // Clear all selected dishes
        $_SESSION['selected_dishes'] = [];
        $_SESSION['success_message'] = "All dish selections cleared!";
    }
    
    if (isset($_POST['add_addon'])) {
        try {
            $addon_id = $_POST['addon_id'];
            
            if (!isset($_SESSION['catering_order_id'])) {
                throw new Exception("Please select a catering package first");
            }
            
            // Add addon to selected addons
            if (!in_array($addon_id, $_SESSION['selected_addons'])) {
                $_SESSION['selected_addons'][] = $addon_id;
                $_SESSION['success_message'] = "Addon added to your order!";
            } else {
                $_SESSION['success_message'] = "Addon is already in your order!";
            }
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error adding addon: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['remove_addon'])) {
        $addon_id = $_POST['addon_id'];
        $key = array_search($addon_id, $_SESSION['selected_addons']);
        if ($key !== false) {
            unset($_SESSION['selected_addons'][$key]);
            $_SESSION['selected_addons'] = array_values($_SESSION['selected_addons']); // Reindex array
            $_SESSION['success_message'] = "Addon removed from your order!";
        }
    }
    
    if (isset($_POST['confirm_order'])) {
        try {
            // Update catering order status to confirmed
            if (isset($_SESSION['catering_order_id'])) {
                $stmt = $pdo->prepare("UPDATE catering_orders SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$_SESSION['catering_order_id']]);
                
                // Save selected dishes to database
                foreach ($_SESSION['selected_dishes'] as $dish_id) {
                    $stmt = $pdo->prepare("INSERT INTO catering_order_dishes (catering_order_id, dish_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['catering_order_id'], $dish_id]);
                }
                
                // Save selected addons to database
                foreach ($_SESSION['selected_addons'] as $addon_id) {
                    $stmt = $pdo->prepare("INSERT INTO catering_order_addons (catering_order_id, addon_id) VALUES (?, ?)");
                    $stmt->execute([$_SESSION['catering_order_id'], $addon_id]);
                }
                
                // Calculate catering total
                $catering_total = 0;
                if (isset($_SESSION['selected_package'])) {
                    foreach ($catering_packages as $package) {
                        if ($package['id'] == $_SESSION['selected_package']) {
                            $catering_total += $package['base_price'];
                            break;
                        }
                    }
                }
                
                foreach ($_SESSION['selected_addons'] as $addon_id) {
                    foreach ($catering_addons as $addon) {
                        if ($addon['id'] == $addon_id) {
                            $catering_total += $addon['price'];
                            break;
                        }
                    }
                }
                

    // Save selected dishes if submitted
if (isset($_POST['dishes']) && is_array($_POST['dishes'])) {
    $_SESSION['selected_dishes'] = $_POST['dishes'];
}

// Save selected addons if submitted
if (isset($_POST['addons']) && is_array($_POST['addons'])) {
    $_SESSION['selected_addons'] = $_POST['addons'];
}


                // Store catering info in session for payment page
                $_SESSION['catering_info'] = [
                    'order_id' => $_SESSION['catering_order_id'],
                    'total' => $catering_total,
                    'package_id' => $_SESSION['selected_package'],
                    'dishes' => $_SESSION['selected_dishes'],
                    'addons' => $_SESSION['selected_addons']
                ];
                
                $_SESSION['success_message'] = "Catering order confirmed! Proceeding to payment.";
            }
            
            // Redirect to payment page
            header("Location: payment.php?booking_id=" . $booking_id);
            exit();
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error confirming order: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['skip_catering'])) {
        // Redirect to payment without catering
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    }
    
    // Refresh page to show messages
    header("Location: catering.php");
    exit();
}

// Calculate total price
$total_price = 0;
$package_price = 0;
$addons_price = 0;

if (isset($_SESSION['selected_package'])) {
    foreach ($catering_packages as $package) {
        if ($package['id'] == $_SESSION['selected_package']) {
            $package_price = $package['base_price'];
            break;
        }
    }
}

foreach ($_SESSION['selected_addons'] as $addon_id) {
    foreach ($catering_addons as $addon) {
        if ($addon['id'] == $addon_id) {
            $addons_price += $addon['price'];
            break;
        }
    }
}

$total_price = $package_price + $addons_price;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catering Services - BTONE Events</title>
    <link rel="stylesheet" href="../CSS/style.css" />
    <style>
        body {
            background: #f5f1e8;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #5d4037;
        }
        
        .back-btn {
            display: inline-block;
            padding: 8px 18px;
            background: #8d6e63;
            color: #fff;
            border: none;
            border-radius: 22px;
            font-weight: bold;
            margin: 24px 0 0 24px;
            font-size: 1rem;
            box-shadow: 0 2px 4px rgba(141,110,99,0.3);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.18s;
        }
        
        .back-btn:hover {
            background: #6d4c41;
            color: #fff;
        }
        
        .catering-main {
            max-width: 1200px;
            margin: 32px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(141,110,99,0.15);
            padding: 32px;
            border: 1px solid #d7ccc8;
        }
        
        .catering-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .catering-header h1 {
            color: #6d4c41;
            margin: 0;
            font-size: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .booking-info {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .booking-info h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .package-card {
            background: #faf7f4;
            border: 2px solid #e8e2da;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            transition: transform 0.3s;
        }
        
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(141,110,99,0.2);
        }
        
        .package-card h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .package-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: #8d6e63;
            margin: 15px 0;
        }
        
        .package-includes {
            text-align: left;
            margin: 15px 0;
        }
        
        .package-includes ul {
            list-style: none;
            padding: 0;
        }
        
        .package-includes li {
            padding: 5px 0;
            border-bottom: 1px solid #e8e2da;
        }
        
        .package-includes li:last-child {
            border-bottom: none;
        }
        
        .package-includes li:before {
            content: "✓ ";
            color: #388e3c;
            font-weight: bold;
        }
        
        .dishes-section {
            margin: 40px 0;
        }
        
        .dishes-category {
            margin-bottom: 30px;
        }
        
        .dishes-category h3 {
            color: #6d4c41;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .dishes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .dish-item {
            background: #faf7f4;
            border: 1px solid #e8e2da;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
        }
        
        .dish-checkbox {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .dish-info {
            flex: 1;
        }
        
        .addons-section {
            margin: 40px 0;
        }
        
        .addons-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .addon-card {
            background: #f5eee6;
            border: 2px solid #d7ccc8;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .addon-price {
            font-weight: bold;
            color: #8d6e63;
            margin: 10px 0;
        }
        
        .btn-select {
            background: linear-gradient(135deg, #8d6e63, #6d4c41);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        
        .btn-select:hover {
            background: linear-gradient(135deg, #6d4c41, #5d4037);
            transform: translateY(-2px);
        }
        
        .btn-clear {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
            margin-left: 10px;
        }
        
        .btn-clear:hover {
            background: linear-gradient(135deg, #d32f2f, #b71c1c);
            transform: translateY(-2px);
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #388e3c, #2e7d32);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            margin: 20px 0;
            display: block;
            width: 100%;
            max-width: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn-confirm:hover {
            background: linear-gradient(135deg, #2e7d32, #1b5e20);
            transform: translateY(-2px);
        }
        
        .rules-section {
            background: #f5eee6;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            border: 2px solid #d7ccc8;
        }
        
        .rules-section h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            text-align: center;
        }
        
        .success {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        
        .error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .receipt-section {
            background: #f9f9f9;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
            border: 2px solid #d7ccc8;
        }
        
        .receipt-section h3 {
            color: #6d4c41;
            margin-top: 0;
            border-bottom: 2px solid #8d6e63;
            padding-bottom: 10px;
        }
        
        .receipt-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e8e2da;
        }
        
        .receipt-total {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-weight: bold;
            font-size: 1.2rem;
            border-top: 2px solid #8d6e63;
            margin-top: 10px;
        }
        
        .dish-selection-form {
            background: #f5eee6;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .dish-selection-form h3 {
            color: #6d4c41;
            margin-top: 0;
        }
        
        .selection-status {
            padding: 10px;
            background: #e8f5e8;
            border-radius: 6px;
            margin: 10px 0;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .selection-status.invalid {
            background: #ffebee;
            color: #c62828;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .category-section {
            margin-bottom: 30px;
            padding: 15px;
            border: 2px solid #e8e2da;
            border-radius: 8px;
        }
        
        .main-dishes {
            background: #f8f5f0;
        }
        
        .beverages-desserts {
            background: #f0f8f0;
        }
        
        @media (max-width: 768px) {
            .package-grid {
                grid-template-columns: 1fr;
            }
            
            .catering-main {
                margin: 15px;
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <a href="booking-form.php" class="back-btn">&#8592; Back to Booking</a>

    <div class="catering-main">
        <div class="catering-header">
            <h1>Catering Services</h1>
            <p style="color:#8d6e63;">Add delicious catering options for your event</p>
        </div>

        <!-- Booking Information -->
        <div class="booking-info">
            <h3>Your Booking Details</h3>
            <p><strong>Event:</strong> <?php echo htmlspecialchars($booking['btevent']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['btschedule'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['btschedule'])); ?></p>
            <p><strong>Booking ID:</strong> #<?php echo $booking_id; ?></p>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="package-grid">
            <?php foreach ($catering_packages as $package): ?>
            <div class="package-card">
                <h3><?php echo htmlspecialchars($package['name']); ?></h3>
                <div class="package-price">₱<?php echo number_format($package['base_price'], 2); ?></div>
                <p>Minimum <?php echo $package['min_attendees']; ?> persons required</p>
                <p><strong><?php echo $package['dish_count']; ?> Main Dishes Included</strong></p>
                
                <div class="package-includes">
                    <h4>Includes:</h4>
                    <ul>
                        <?php
                        $includes = explode(',', $package['includes']);
                        foreach ($includes as $include) {
                            echo '<li>' . trim(htmlspecialchars($include)) . '</li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                    <button type="submit" name="add_catering" class="btn-select">Select This Package</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="rules-section">
            <h3>Dish Selection Rules</h3>
            <ul>
                <li><strong>5 Main Dishes Package:</strong> Must choose either 1 vegetables + 4 meat, OR 1 vegetables + 3 meat + 1 pasta</li>
                <li><strong>4 Main Dishes Package:</strong> Must choose either 1 vegetables + 3 meat, OR 1 vegetables + 2 meat + 1 pasta</li>
                <li>Steamed rice is included by default</li>
                <li><strong>Beverages & Desserts (Separate from main dishes):</strong></li>
                <li>Maximum of 2 dessert selections</li>
                <li>Maximum of 2 juice selections</li>
                <li>Extra dessert: +₱3,000 per additional selection</li>
                <li>Extra juice: +₱2,000 per additional selection</li>
                <li>Extra dish: +₱5,000 per additional dish</li>
            </ul>
        </div>

        <?php if (isset($_SESSION['selected_package'])): ?>
            <?php
            $selected_package = null;
            foreach ($catering_packages as $package) {
                if ($package['id'] == $_SESSION['selected_package']) {
                    $selected_package = $package;
                    break;
                }
            }
            ?>
            
            <div class="dish-selection-form">
                <h3>Select Your Dishes for <?php echo htmlspecialchars($selected_package['name']); ?></h3>
                
                <?php
                // Count selected dishes by category
                $selected_meat = 0;
                $selected_vegetables = 0;
                $selected_pasta = 0;
                $selected_dessert = 0;
                $selected_juice = 0;
                
                foreach ($_SESSION['selected_dishes'] as $dish_id) {
                    $dish = null;
                    foreach ($catering_dishes as $d) {
                        if ($d['id'] == $dish_id) {
                            $dish = $d;
                            break;
                        }
                    }
                    
                    if ($dish) {
                        $category = strtolower($dish['category'] ?: '');
                        if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'null', ''])) {
                            $selected_meat++;
                        } else if ($category == 'vegetables') {
                            $selected_vegetables++;
                        } else if ($category == 'pasta') {
                            $selected_pasta++;
                        } else if ($category == 'dessert') {
                            $selected_dessert++;
                        } else if ($category == 'juice') {
                            $selected_juice++;
                        }
                    }
                }
                
                // Count only main dishes for package validation
                $main_dish_count = $selected_meat + $selected_vegetables + $selected_pasta;
                $required_total = $selected_package['dish_count'];
                ?>
                
                <div class="selection-status" id="selectionStatus">
                    <strong>Main Dishes:</strong> <span id="mainDishCount"><?php echo $main_dish_count; ?></span>/<span id="requiredTotal"><?php echo $required_total; ?></span> 
                    (Meat: <span id="meatCount"><?php echo $selected_meat; ?></span>, 
                    vegetables: <span id="vegetablesCount"><?php echo $selected_vegetables; ?></span>, 
                    Pasta: <span id="pastaCount"><?php echo $selected_pasta; ?></span>)<br>
                    <strong>Beverages & Desserts:</strong> 
                    Dessert: <span id="dessertCount"><?php echo $selected_dessert; ?></span>/2,
                    Juice: <span id="juiceCount"><?php echo $selected_juice; ?></span>/2
                </div>
                
                <form method="POST" id="dishForm">
                    <!-- Main Dishes Section -->
                    <div class="category-section main-dishes">
                        <h3 style="color: #6d4c41; margin-top: 0;">Main Dishes (Select <?php echo $required_total; ?> dishes)</h3>
                        
                        <?php 
                        // Define main dish categories in the correct order with proper handling
                        $main_categories = ['Vegetables', 'Meat', 'Pork', 'Chicken', 'Fish', 'Seafood', 'Pasta'];
                        
                        foreach ($main_categories as $category): ?>
                            <?php 
                            // Check if this category exists in our grouped dishes
                            $category_exists = false;
                            foreach ($dishes_by_category as $cat => $dishes) {
                                if (strtolower($cat) === strtolower($category)) {
                                    $category_exists = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($category_exists): ?>
                                <div class="dishes-category">
                                    <h4><?php echo htmlspecialchars($category); ?></h4>
                                    <div class="dishes-grid">
                                        <?php 
                                        // Find the actual category name as stored in the database
                                        $actual_category = '';
                                        foreach ($dishes_by_category as $cat => $dishes) {
                                            if (strtolower($cat) === strtolower($category)) {
                                                $actual_category = $cat;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($actual_category && isset($dishes_by_category[$actual_category])): ?>
                                            <?php foreach ($dishes_by_category[$actual_category] as $dish): ?>
                                                <?php
                                                // Determine the category group for validation
                                                $category_group = 'other';
                                                $dish_category = strtolower($dish['category'] ?: '');
                                                if (in_array($dish_category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'null', ''])) {
                                                    $category_group = 'meat';
                                                } else if ($dish_category == 'vegetables') {
                                                    $category_group = 'vegetables';
                                                } else if ($dish_category == 'pasta') {
                                                    $category_group = 'pasta';
                                                }
                                                ?>
                                                <div class="dish-item">
                                                    <input type="checkbox" 
                                                           id="dish_<?php echo $dish['id']; ?>" 
                                                           name="dishes[]" 
                                                           value="<?php echo $dish['id']; ?>" 
                                                           class="dish-checkbox main-dish"
                                                           data-category="<?php echo $category_group; ?>"
                                                           <?php echo in_array($dish['id'], $_SESSION['selected_dishes']) ? 'checked' : ''; ?>>
                                                    <div class="dish-info">
                                                        <h4><?php echo htmlspecialchars($dish['name']); ?></h4>
                                                        <?php if (!empty($dish['description'])): ?>
                                                        <p><?php echo htmlspecialchars($dish['description']); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($dish['is_default']): ?>
                                                        <p><em>✓ Included by default</em></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Beverages & Desserts Section -->
                    <div class="category-section beverages-desserts">
                        <h3 style="color: #6d4c41; margin-top: 0;">Beverages & Desserts (Optional - Separate from main dishes)</h3>
                        
                        <?php 
                        $beverage_categories = ['Juice', 'Dessert'];
                        
                        foreach ($beverage_categories as $category): ?>
                            <?php 
                            // Check if this category exists in our grouped dishes
                            $category_exists = false;
                            foreach ($dishes_by_category as $cat => $dishes) {
                                if (strtolower($cat) === strtolower($category)) {
                                    $category_exists = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if ($category_exists): ?>
                                <div class="dishes-category">
                                    <h4><?php echo htmlspecialchars($category); ?></h4>
                                    <div class="dishes-grid">
                                        <?php 
                                        // Find the actual category name as stored in the database
                                        $actual_category = '';
                                        foreach ($dishes_by_category as $cat => $dishes) {
                                            if (strtolower($cat) === strtolower($category)) {
                                                $actual_category = $cat;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($actual_category && isset($dishes_by_category[$actual_category])): ?>
                                            <?php foreach ($dishes_by_category[$actual_category] as $dish): ?>
                                                <div class="dish-item">
                                                    <input type="checkbox" 
                                                           id="dish_<?php echo $dish['id']; ?>" 
                                                           name="dishes[]" 
                                                           value="<?php echo $dish['id']; ?>" 
                                                           class="dish-checkbox beverage-dessert"
                                                           data-category="<?php echo strtolower($category); ?>"
                                                           <?php echo in_array($dish['id'], $_SESSION['selected_dishes']) ? 'checked' : ''; ?>>
                                                    <div class="dish-info">
                                                        <h4><?php echo htmlspecialchars($dish['name']); ?></h4>
                                                        <?php if (!empty($dish['description'])): ?>
                                                        <p><?php echo htmlspecialchars($dish['description']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="button-group">
                        <button type="submit" name="update_dishes" class="btn-select">Update Dish Selection</button>
                        <button type="submit" name="clear_all" class="btn-clear">Clear All Selections</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="addons-section">
            <h2 style="text-align: center; color: #6d4c41;">Optional Add-ons</h2>
            
            <div class="addons-grid">
                <?php foreach ($catering_addons as $addon): ?>
                <div class="addon-card">
                    <h3><?php echo htmlspecialchars($addon['name']); ?></h3>
                    <div class="addon-price">₱<?php echo number_format($addon['price'], 2); ?></div>
                    <?php if (!empty($addon['description'])): ?>
                    <p><?php echo htmlspecialchars($addon['description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (in_array($addon['id'], $_SESSION['selected_addons'])): ?>
                        <form method="POST" class="addon-form">
                            <input type="hidden" name="addon_id" value="<?php echo $addon['id']; ?>">
                            <button type="submit" name="remove_addon" class="btn-select" style="background: #d32f2f;">Remove from Order</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" class="addon-form">
                            <input type="hidden" name="addon_id" value="<?php echo $addon['id']; ?>">
                            <button type="submit" name="add_addon" class="btn-select">Add to Order</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['catering_order_id'])): ?>
        <div class="receipt-section">
            <h3>Your Catering Order Summary</h3>
            
            <?php if (isset($_SESSION['selected_package'])): ?>
                <?php
                $selected_package = null;
                foreach ($catering_packages as $package) {
                    if ($package['id'] == $_SESSION['selected_package']) {
                        $selected_package = $package;
                        break;
                    }
                }
                ?>
                
                <div class="receipt-item">
                    <span><?php echo htmlspecialchars($selected_package['name']); ?> Package</span>
                    <span>₱<?php echo number_format($selected_package['base_price'], 2); ?></span>
                </div>
                
                <?php if (!empty($_SESSION['selected_dishes'])): ?>
                    <h4>Selected Dishes:</h4>
                    <?php foreach ($_SESSION['selected_dishes'] as $dish_id): ?>
                        <?php
                        $dish = null;
                        foreach ($catering_dishes as $d) {
                            if ($d['id'] == $dish_id) {
                                $dish = $d;
                                break;
                            }
                        }
                        ?>
                        <?php if ($dish): ?>
                            <div class="receipt-item">
                                <span><?php echo htmlspecialchars($dish['name']); ?> (<?php echo htmlspecialchars($dish['category'] ?: 'Main'); ?>)</span>
                                <span>Included</span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($_SESSION['selected_addons'])): ?>
                    <h4>Add-ons:</h4>
                    <?php foreach ($_SESSION['selected_addons'] as $addon_id): ?>
                        <?php
                        $addon = null;
                        foreach ($catering_addons as $a) {
                            if ($a['id'] == $addon_id) {
                                $addon = $a;
                                break;
                            }
                        }
                        ?>
                        <?php if ($addon): ?>
                            <div class="receipt-item">
                                <span><?php echo htmlspecialchars($addon['name']); ?></span>
                                <span>₱<?php echo number_format($addon['price'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="receipt-total">
                    <span>Total Catering Cost:</span>
                    <span>₱<?php echo number_format($total_price, 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <form method="POST">
                <button type="submit" name="confirm_order" class="btn-confirm">
                    Confirm Catering Order & Proceed to Payment
                </button>
            </form>
            
            <form method="POST" style="margin-top: 10px;">
                <button type="submit" name="skip_catering" class="btn-select" style="background: #6c757d;">
                    Skip Catering & Proceed to Payment
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Real-time dish counting and validation
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.dish-checkbox');
            const requiredTotal = <?php echo isset($selected_package) ? $selected_package['dish_count'] : 0; ?>;
            const selectionStatus = document.getElementById('selectionStatus');
            
            if (selectionStatus) {
                // Initialize counts
                updateCounts();
                
                // Add event listeners to dish checkboxes
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateCounts();
                        validateSelections();
                    });
                });
                
                // Clear all button functionality
                const clearButton = document.querySelector('button[name="clear_all"]');
                if (clearButton) {
                    clearButton.addEventListener('click', function(e) {
                        if (!confirm('Are you sure you want to clear all dish selections?')) {
                            e.preventDefault();
                            return;
                        }
                        // Uncheck all checkboxes visually
                        checkboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        updateCounts();
                        validateSelections();
                    });
                }
            }
            
            function updateCounts() {
                let meatCount = 0;
                let vegetablesCount = 0;
                let pastaCount = 0;
                let dessertCount = 0;
                let juiceCount = 0;
                let mainDishCount = 0;
                
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const category = checkbox.getAttribute('data-category');
                        
                        switch(category) {
                            case 'meat': 
                                meatCount++; 
                                mainDishCount++;
                                break;
                            case 'vegetables': 
                                vegetablesCount++; 
                                mainDishCount++;
                                break;
                            case 'pasta': 
                                pastaCount++; 
                                mainDishCount++;
                                break;
                            case 'dessert': 
                                dessertCount++; 
                                break;
                            case 'juice': 
                                juiceCount++; 
                                break;
                        }
                    }
                });
                
                // Update the display
                if (document.getElementById('mainDishCount')) {
                    document.getElementById('mainDishCount').textContent = mainDishCount;
                    document.getElementById('meatCount').textContent = meatCount;
                    document.getElementById('vegetablesCount').textContent = vegetablesCount;
                    document.getElementById('pastaCount').textContent = pastaCount;
                    document.getElementById('dessertCount').textContent = dessertCount;
                    document.getElementById('juiceCount').textContent = juiceCount;
                }
            }
            
            function validateSelections() {
                const mainDishCount = parseInt(document.getElementById('mainDishCount').textContent);
                const meatCount = parseInt(document.getElementById('meatCount').textContent);
                const vegetablesCount = parseInt(document.getElementById('vegetablesCount').textContent);
                const pastaCount = parseInt(document.getElementById('pastaCount').textContent);
                const dessertCount = parseInt(document.getElementById('dessertCount').textContent);
                const juiceCount = parseInt(document.getElementById('juiceCount').textContent);
                
                // Enable all checkboxes first
                checkboxes.forEach(checkbox => {
                    checkbox.disabled = false;
                });
                
                // Disable checkboxes based on limits
                checkboxes.forEach(checkbox => {
                    const category = checkbox.getAttribute('data-category');
                    const isChecked = checkbox.checked;
                    const isMainDish = checkbox.classList.contains('main-dish');
                    const isBeverageDessert = checkbox.classList.contains('beverage-dessert');
                    
                    if (!isChecked) {
                        // Main dish limits
                        if (isMainDish) {
                            if (mainDishCount >= requiredTotal) {
                                checkbox.disabled = true;
                            }
                            // Package-specific rules for main dishes
                            else if (requiredTotal === 5) {
                                // 5-dish package rules
                                if (category === 'vegetables' && vegetablesCount >= 1) {
                                    checkbox.disabled = true;
                                }
                                else if (category === 'pasta' && pastaCount >= 1) {
                                    checkbox.disabled = true;
                                }
                                else if (category === 'meat') {
                                    if ((vegetablesCount === 1 && pastaCount === 0 && meatCount >= 4) ||
                                        (vegetablesCount === 1 && pastaCount === 1 && meatCount >= 3)) {
                                        checkbox.disabled = true;
                                    }
                                }
                            }
                            else if (requiredTotal === 4) {
                                // 4-dish package rules
                                if (category === 'vegetables' && vegetablesCount >= 1) {
                                    checkbox.disabled = true;
                                }
                                else if (category === 'pasta' && pastaCount >= 1) {
                                    checkbox.disabled = true;
                                }
                                else if (category === 'meat') {
                                    if ((vegetablesCount === 1 && pastaCount === 0 && meatCount >= 3) ||
                                        (vegetablesCount === 1 && pastaCount === 1 && meatCount >= 2)) {
                                        checkbox.disabled = true;
                                    }
                                }
                            }
                        }
                        
                        // Beverage and dessert limits (separate from main dishes)
                        if (isBeverageDessert) {
                            if (category === 'dessert' && dessertCount >= 2) {
                                checkbox.disabled = true;
                            }
                            else if (category === 'juice' && juiceCount >= 2) {
                                checkbox.disabled = true;
                            }
                        }
                    }
                });
                
                // Update selection status color based on validity
                const mainDishesValid = validateMainDishRules(mainDishCount, meatCount, vegetablesCount, pastaCount, requiredTotal);
                const beveragesValid = (dessertCount <= 2 && juiceCount <= 2);
                
                if (mainDishesValid && beveragesValid) {
                    selectionStatus.style.backgroundColor = '#e8f5e8';
                    selectionStatus.style.color = '#2e7d32';
                    selectionStatus.classList.remove('invalid');
                } else {
                    selectionStatus.style.backgroundColor = '#ffebee';
                    selectionStatus.style.color = '#c62828';
                    selectionStatus.classList.add('invalid');
                }
                
                // Show specific error messages
                let errorMessage = '';
                if (!mainDishesValid) {
                    if (mainDishCount !== requiredTotal) {
                        errorMessage = `Please select exactly ${requiredTotal} main dishes. `;
                    } else {
                        errorMessage = 'Main dish selection does not follow package rules. ';
                    }
                }
                if (!beveragesValid) {
                    if (dessertCount > 2) errorMessage += 'Maximum 2 desserts allowed. ';
                    if (juiceCount > 2) errorMessage += 'Maximum 2 juices allowed. ';
                }
                
                // Add error message to status if needed
                const existingError = selectionStatus.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }
                
                if (errorMessage) {
                    const errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    errorElement.style.fontSize = '0.9em';
                    errorElement.style.marginTop = '5px';
                    errorElement.textContent = errorMessage;
                    selectionStatus.appendChild(errorElement);
                }
            }
            
            function validateMainDishRules(total, meat, vegetables, pasta, required) {
                if (total !== required) {
                    return false;
                }
                
                if (required === 5) {
                    return (vegetables === 1 && meat === 4 && pasta === 0) || 
                           (vegetables === 1 && meat === 3 && pasta === 1);
                } else if (required === 4) {
                    return (vegetables === 1 && meat === 3 && pasta === 0) || 
                           (vegetables === 1 && meat === 2 && pasta === 1);
                }
                return false;
            }
            
            // Initial validation
            if (selectionStatus) {
                validateSelections();
            }
        });
    </script>
</body>
</html>