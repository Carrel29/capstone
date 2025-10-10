<?php
session_start();
include_once "../includes/loginSession.php";
include_once "../includes/userData.php";
include_once "../includes/db.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit();
}

// Get the current booking ID from either GET parameter (from cart) or session (from booking form)
if (isset($_GET['booking_id'])) {
    // Coming from cart - use GET parameter
    $booking_id = $_GET['booking_id'];
    $_SESSION['current_booking_id'] = $booking_id; // Store in session for future use
} elseif (isset($_SESSION['current_booking_id'])) {
    // Coming from booking form - use session
    $booking_id = $_SESSION['current_booking_id'];
} else {
    // If no booking ID provided, redirect to appropriate page
    if (isset($_SESSION['user_id'])) {
        // User is logged in but no booking, redirect to cart
        header("Location: user_cart.php");
    } else {
        // Not logged in, redirect to booking form
        header("Location: booking-form.php");
    }
    exit();
}

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

// Temporary fix for missing image_path
foreach ($catering_dishes as &$dish) {
    if (!isset($dish['image_path'])) {
        $dish['image_path'] = '../Img/menu.png';
    }
}
unset($dish); // break the reference

// Group dishes by category with proper handling
$dishes_by_category = [];

foreach ($catering_dishes as $dish) {
    $category = $dish['category'];
    
    // Fix null/empty categories and categorize properly
    if (empty($category) || $category === 'null') {
        // Determine category from dish name
        $dishName = strtolower($dish['name']);
        if (strpos($dishName, 'beef') !== false) {
            $category = 'Beef';
        } elseif (strpos($dishName, 'pork') !== false) {
            $category = 'Pork';
        } elseif (strpos($dishName, 'chicken') !== false) {
            $category = 'Chicken';
        } elseif (strpos($dishName, 'fish') !== false || strpos($dishName, 'shrimp') !== false) {
            $category = 'Fish';
        } else {
            $category = 'Other';
        }
    }
    
    $dishes_by_category[$category][] = $dish;
}

// Define category display order - INCLUDING ALL CATEGORIES
$main_categories = ['Vegetables', 'Beef', 'Pork', 'Chicken', 'Fish', 'Seafood', 'Pasta'];
$beverage_categories = ['Juice', 'Dessert', 'Soup', 'Appetizer'];

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
                $selected_soup = 0;
                $selected_appetizer = 0;
                
                // Count selected dishes by category
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
                        
                        // Count ALL meat types including beef
                        if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'beef', 'null', ''])) {
                            $selected_meat++;
                        } else if ($category == 'vegetables') {
                            $selected_vegetables++;
                        } else if ($category == 'pasta') {
                            $selected_pasta++;
                        } else if ($category == 'dessert') {
                            $selected_dessert++;
                        } else if ($category == 'juice') {
                            $selected_juice++;
                        } else if ($category == 'soup') {
                            $selected_soup++;
                        } else if ($category == 'appetizer') {
                            $selected_appetizer++;
                        }
                    }
                }
                
                // Count only main dishes for package validation
                $main_dish_count = $selected_meat + $selected_vegetables + $selected_pasta;
                
                // REMOVED THE LIMIT VALIDATION FOR DESSERTS, JUICE, SOUP AND APPETIZER
                // Users can now select as many as they want, they'll be charged extra
                
                // Validate main dish count matches package requirement
                // Allow extra main dishes (they'll be charged extra)
                $base_main_dish_count = $dish_count;
                if ($main_dish_count < $base_main_dish_count) {
                    throw new Exception("Please select at least {$base_main_dish_count} main dishes (meat, vegetables, pasta)");
                }
                
                // Validate base package rules (only for the base number of dishes)
                $base_meat = $selected_meat;
                $base_vegetables = $selected_vegetables;
                $base_pasta = $selected_pasta;
                
                // For validation, only consider the base number of dishes
                if ($main_dish_count > $base_main_dish_count) {
                    // User selected extra dishes - validate the base selection
                    $is_valid = validateBaseSelection($base_main_dish_count, $base_meat, $base_vegetables, $base_pasta);
                } else {
                    // Normal selection - validate all
                    $is_valid = validateBaseSelection($main_dish_count, $selected_meat, $selected_vegetables, $selected_pasta);
                }
                
                if (!$is_valid) {
                    throw new Exception("Invalid base dish selection. Please follow the package rules for the first {$base_main_dish_count} dishes.");
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

            // Calculate extra dish charges
            if (!empty($_SESSION['selected_dishes'])) {
                $base_dish_count = 0;
                $selected_package = null;
                foreach ($catering_packages as $package) {
                    if ($package['id'] == $_SESSION['selected_package']) {
                        $selected_package = $package;
                        $base_dish_count = $package['dish_count'];
                        break;
                    }
                }
                
                // Count main dishes and extras
                $main_dish_count = 0;
                $dessert_count = 0;
                $juice_count = 0;
                $soup_count = 0;
                $appetizer_count = 0;
                
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
                        if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'beef', 'vegetables', 'pasta'])) {
                            $main_dish_count++;
                        } else if ($category == 'dessert') {
                            $dessert_count++;
                        } else if ($category == 'juice') {
                            $juice_count++;
                        } else if ($category == 'soup') {
                            $soup_count++;
                        } else if ($category == 'appetizer') {
                            $appetizer_count++;
                        }
                    }
                }
                
                // Calculate extra charges
                $extra_main_dishes = max(0, $main_dish_count - $base_dish_count);
                $extra_desserts = max(0, $dessert_count - 2);
                $extra_juices = max(0, $juice_count - 2);
                $extra_soups = max(0, $soup_count - 1); // Base includes 1 soup
                $extra_appetizers = max(0, $appetizer_count - 2); // Base includes 2 appetizers
                
                $catering_total += $extra_main_dishes * 5000; // ₱5,000 per extra main dish
                $catering_total += $extra_desserts * 3000;    // ₱3,000 per extra dessert
                $catering_total += $extra_juices * 2000;      // ₱2,000 per extra juice
                $catering_total += $extra_soups * 3000;       // ₱3,000 per extra soup
                $catering_total += $extra_appetizers * 2000;  // ₱2,000 per extra appetizer
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
        
        // Redirect to payment page with booking ID
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

// Helper function to validate base dish selection
function validateBaseSelection($dish_count, $meat, $vegetables, $pasta) {
    if ($dish_count == 5) {
        // 5-dish package: 1 vegetables + 4 meat OR 1 vegetables + 3 meat + 1 pasta
        return ($vegetables == 1 && $meat == 4 && $pasta == 0) ||
               ($vegetables == 1 && $meat == 3 && $pasta == 1);
    } else if ($dish_count == 4) {
        // 4-dish package: 1 vegetables + 3 meat OR 1 vegetables + 2 meat + 1 pasta
        return ($vegetables == 1 && $meat == 3 && $pasta == 0) ||
               ($vegetables == 1 && $meat == 2 && $pasta == 1);
    }
    return false;
}

// Calculate total price with extra charges
$total_price = 0;
$package_price = 0;
$addons_price = 0;
$extra_charges = 0;

if (isset($_SESSION['selected_package'])) {
    foreach ($catering_packages as $package) {
        if ($package['id'] == $_SESSION['selected_package']) {
            $package_price = $package['base_price'];
            break;
        }
    }
}

// Calculate extra dish charges
if (!empty($_SESSION['selected_dishes'])) {
    $base_dish_count = 0;
    $selected_package = null;
    foreach ($catering_packages as $package) {
        if ($package['id'] == $_SESSION['selected_package']) {
            $selected_package = $package;
            $base_dish_count = $package['dish_count'];
            break;
        }
    }
    
    // Count main dishes and extras
    $main_dish_count = 0;
    $dessert_count = 0;
    $juice_count = 0;
    $soup_count = 0;
    $appetizer_count = 0;
    
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
            if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'beef', 'vegetables', 'pasta'])) {
                $main_dish_count++;
            } else if ($category == 'dessert') {
                $dessert_count++;
            } else if ($category == 'juice') {
                $juice_count++;
            } else if ($category == 'soup') {
                $soup_count++;
            } else if ($category == 'appetizer') {
                $appetizer_count++;
            }
        }
    }
    
    // Calculate extra charges
    $extra_main_dishes = max(0, $main_dish_count - $base_dish_count);
    $extra_desserts = max(0, $dessert_count - 2);
    $extra_juices = max(0, $juice_count - 2);
    $extra_soups = max(0, $soup_count - 1); // Base includes 1 soup
    $extra_appetizers = max(0, $appetizer_count - 2); // Base includes 2 appetizers
    
    $extra_charges += $extra_main_dishes * 5000; // ₱5,000 per extra main dish
    $extra_charges += $extra_desserts * 3000;    // ₱3,000 per extra dessert
    $extra_charges += $extra_juices * 2000;      // ₱2,000 per extra juice
    $extra_charges += $extra_soups * 3000;       // ₱3,000 per extra soup
    $extra_charges += $extra_appetizers * 2000;  // ₱2,000 per extra appetizer
}

foreach ($_SESSION['selected_addons'] as $addon_id) {
    foreach ($catering_addons as $addon) {
        if ($addon['id'] == $addon_id) {
            $addons_price += $addon['price'];
            break;
        }
    }
}

$total_price = $package_price + $addons_price + $extra_charges;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .dish-item {
            background: #faf7f4;
            border: 1px solid #e8e2da;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .dish-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(141,110,99,0.2);
        }
        
        .dish-image img {
            transition: transform 0.3s ease;
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e8e2da;
        }
        
        .dish-item:hover .dish-image img {
            transform: scale(1.05);
        }
        
        .dish-checkbox {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .dish-info {
            flex: 1;
            margin-left: 15px;
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
            
            .dish-item {
                flex-direction: column;
                text-align: center;
            }
            
            .dish-image {
                margin-bottom: 10px;
            }
            
            .dish-info {
                margin-left: 0;
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
                <li><strong>Beverages & Desserts:</strong></li>
                <li>Base package includes up to 2 dessert selections</li>
                <li>Base package includes up to 2 juice selections</li>
                <li><strong>Soups & Appetizers:</strong></li>
                <li>Base package includes up to 1 soup selection</li>
                <li>Base package includes up to 2 appetizer selections</li>
                <li><strong>Extra selections available:</strong></li>
                <li>Extra dessert: +₱3,000 per additional selection</li>
                <li>Extra juice: +₱2,000 per additional selection</li>
                <li>Extra soup: +₱3,000 per additional selection</li>
                <li>Extra appetizer: +₱2,000 per additional selection</li>
                <li>Extra main dish: +₱5,000 per additional dish</li>
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
                $selected_soup = 0;
                $selected_appetizer = 0;
                
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
                        if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'beef', 'null', ''])) {
                            $selected_meat++;
                        } else if ($category == 'vegetables') {
                            $selected_vegetables++;
                        } else if ($category == 'pasta') {
                            $selected_pasta++;
                        } else if ($category == 'dessert') {
                            $selected_dessert++;
                        } else if ($category == 'juice') {
                            $selected_juice++;
                        } else if ($category == 'soup') {
                            $selected_soup++;
                        } else if ($category == 'appetizer') {
                            $selected_appetizer++;
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
                    Vegetables: <span id="vegetablesCount"><?php echo $selected_vegetables; ?></span>, 
                    Pasta: <span id="pastaCount"><?php echo $selected_pasta; ?></span>)<br>
                    <strong>Beverages & Desserts:</strong> 
                    Dessert: <span id="dessertCount"><?php echo $selected_dessert; ?></span>/2,
                    Juice: <span id="juiceCount"><?php echo $selected_juice; ?></span>/2<br>
                    <strong>Soups & Appetizers:</strong>
                    Soup: <span id="soupCount"><?php echo $selected_soup; ?></span>/1,
                    Appetizer: <span id="appetizerCount"><?php echo $selected_appetizer; ?></span>/2
                </div>
                
                <div class="instruction-text" style="background: #e3f2fd; color: #1565c0; padding: 10px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #2196f3;">
                    <strong>💡 Important:</strong> Please click the <strong>"Update Dish Selection"</strong> button after selecting your dishes to save your choices.
                </div>

                <form method="POST" id="dishForm">
                    <!-- Main Dishes Section -->
                    <div class="category-section main-dishes">
                        <h3 style="color: #6d4c41; margin-top: 0;">Main Dishes (Select <?php echo $required_total; ?> dishes)</h3>
                        
                        <?php 
                        // Display main dish categories in specific order
                        foreach ($main_categories as $category): 
                            if (isset($dishes_by_category[$category])): ?>
                                <div class="dishes-category">
                                    <h4><?php echo htmlspecialchars($category); ?></h4>
                                    <div class="dishes-grid">
                                        <?php foreach ($dishes_by_category[$category] as $dish): ?>
                                            <?php
                                            // Determine the category group for validation
                                            $original_category = strtolower($dish['category'] ?: '');
                                            $category_group = $original_category;

                                            // DEBUG: Show what's happening
                                            $debug_info = "Original: '$original_category', Final: '$category_group'";

                                            // Group all meat types including beef under 'meat' for counting
                                            if (in_array($original_category, ['beef', 'pork', 'chicken', 'fish', 'seafood', ''])) {
                                                $category_group = 'meat';
                                                $debug_info = "Original: '$original_category', Final: meat (CONVERTED)";
                                            }

                                            // Ensure we never have an empty category
                                            if (empty($category_group)) {
                                                $category_group = 'meat';
                                                $debug_info = "Original: '$original_category', Final: meat (FIXED EMPTY)";
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
                                                
                                                <!-- Dish Image -->
                                                <div class="dish-image">
                                                    <img src="<?php echo htmlspecialchars($dish['image_path'] ?? '../Img/menu.png'); ?>" 
                                                         alt="<?php echo htmlspecialchars($dish['name']); ?>"
                                                         onerror="this.src='../Img/menu.png'">
                                                </div>
                                                
                                                <div class="dish-info">
                                                    <h4><?php echo htmlspecialchars($dish['name']); ?></h4>
                                                    <?php if (!empty($dish['description'])): ?>
                                                    <p><?php echo htmlspecialchars($dish['description']); ?></p>
                                                    <?php endif; ?>
                                                    <!-- DEBUG INFO -->
                                              <!-- DEBUG INFO      <p style="color: white; font-size: 0.8em;"><?php echo $debug_info; ?></p> -->
                                                    <?php if ($dish['is_default']): ?>
                                                    <p><em>✓ Best Choice</em></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Beverages, Desserts, Soups & Appetizers Section -->
                    <div class="category-section beverages-desserts">
                        <h3 style="color: #6d4c41; margin-top: 0;">Beverages, Desserts, Soups & Appetizers (Optional)</h3>
                        
                        <?php 
                        // Display all optional categories
                        foreach ($beverage_categories as $category): 
                            if (isset($dishes_by_category[$category])): ?>
                                <div class="dishes-category">
                                    <h4><?php echo htmlspecialchars($category); ?></h4>
                                    <div class="dishes-grid">
                                        <?php foreach ($dishes_by_category[$category] as $dish): ?>
                                            <div class="dish-item">
                                                <input type="checkbox" 
                                                       id="dish_<?php echo $dish['id']; ?>" 
                                                       name="dishes[]" 
                                                       value="<?php echo $dish['id']; ?>" 
                                                       class="dish-checkbox beverage-dessert"
                                                       data-category="<?php echo strtolower($category); ?>"
                                                       <?php echo in_array($dish['id'], $_SESSION['selected_dishes']) ? 'checked' : ''; ?>>
                                                
                                                <!-- Dish Image -->
                                                <div class="dish-image">
                                                    <img src="<?php echo htmlspecialchars($dish['image_path'] ?? '../Img/menu.png'); ?>" 
                                                         alt="<?php echo htmlspecialchars($dish['name']); ?>"
                                                         onerror="this.src='../Img/menu.png'">
                                                </div>
                                                
                                                <div class="dish-info">
                                                    <h4><?php echo htmlspecialchars($dish['name']); ?></h4>
                                                    <?php if (!empty($dish['description'])): ?>
                                                    <p><?php echo htmlspecialchars($dish['description']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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
                    <?php 
                    // Calculate extras for display
                    $base_dish_count = $selected_package['dish_count'];
                    $main_dish_count = 0;
                    $dessert_count = 0;
                    $juice_count = 0;
                    $soup_count = 0;
                    $appetizer_count = 0;
                    
                    foreach ($_SESSION['selected_dishes'] as $dish_id): ?>
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
                            <?php
                            $category = strtolower($dish['category'] ?: '');
                            $is_extra = false;
                            $extra_charge = 0;
                            
                            if (in_array($category, ['meat', 'pork', 'chicken', 'fish', 'seafood', 'beef', 'vegetables', 'pasta'])) {
                                $main_dish_count++;
                                if ($main_dish_count > $base_dish_count) {
                                    $is_extra = true;
                                    $extra_charge = 5000;
                                }
                            } else if ($category == 'dessert') {
                                $dessert_count++;
                                if ($dessert_count > 2) {
                                    $is_extra = true;
                                    $extra_charge = 3000;
                                }
                            } else if ($category == 'juice') {
                                $juice_count++;
                                if ($juice_count > 2) {
                                    $is_extra = true;
                                    $extra_charge = 2000;
                                }
                            } else if ($category == 'soup') {
                                $soup_count++;
                                if ($soup_count > 1) {
                                    $is_extra = true;
                                    $extra_charge = 3000;
                                }
                            } else if ($category == 'appetizer') {
                                $appetizer_count++;
                                if ($appetizer_count > 2) {
                                    $is_extra = true;
                                    $extra_charge = 2000;
                                }
                            }
                            ?>
                            <div class="receipt-item">
                                <span>
                                    <?php echo htmlspecialchars($dish['name']); ?> (<?php echo htmlspecialchars($dish['category'] ?: 'Main'); ?>)
                                    <?php if ($is_extra): ?>
                                        <span style="color: #ff9800; font-weight: bold;">[EXTRA]</span>
                                    <?php endif; ?>
                                </span>
                                <span>
                                    <?php if ($is_extra): ?>
                                        ₱<?php echo number_format($extra_charge, 2); ?>
                                    <?php else: ?>
                                        Included
                                    <?php endif; ?>
                                </span>
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
                
                <?php if ($extra_charges > 0): ?>
                    <div class="receipt-item" style="border-top: 2px dashed #8d6e63; padding-top: 10px;">
                        <span><strong>Extra Selection Charges:</strong></span>
                        <span>₱<?php echo number_format($extra_charges, 2); ?></span>
                    </div>
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
                let soupCount = 0;
                let appetizerCount = 0;
                let mainDishCount = 0;
                
                console.log('=== STARTING COUNT ===');
                
                checkboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        const category = checkbox.getAttribute('data-category');
                        const dishId = checkbox.value;
                        const dishName = checkbox.closest('.dish-item').querySelector('h4').textContent;
                        
                        console.log('Checked:', dishName, 'Category:', category, 'ID:', dishId);
                        
                        // Count all meat types including beef - handle empty categories too
                        if (['meat', 'beef', 'pork', 'chicken', 'fish', 'seafood', ''].includes(category)) {
                            meatCount++; 
                            mainDishCount++;
                            console.log('✓ Counted as MEAT:', dishName, 'Category was:', category);
                        } else if (category === 'vegetables') { 
                            vegetablesCount++; 
                            mainDishCount++;
                        } else if (category === 'pasta') { 
                            pastaCount++; 
                            mainDishCount++;
                        } else if (category === 'dessert') { 
                            dessertCount++; 
                        } else if (category === 'juice') { 
                            juiceCount++; 
                        } else if (category === 'soup') { 
                            soupCount++; 
                        } else if (category === 'appetizer') { 
                            appetizerCount++; 
                        } else {
                            console.log('⚠️ UNCATEGORIZED:', dishName, 'Category:', category);
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
                    document.getElementById('soupCount').textContent = soupCount;
                    document.getElementById('appetizerCount').textContent = appetizerCount;
                }
                
                console.log('=== FINAL COUNTS ===');
                console.log('Meat:', meatCount, 'Main Dishes:', mainDishCount);
                console.log('====================');
            }
            
            function validateSelections() {
                const mainDishCount = parseInt(document.getElementById('mainDishCount').textContent);
                const meatCount = parseInt(document.getElementById('meatCount').textContent);
                const vegetablesCount = parseInt(document.getElementById('vegetablesCount').textContent);
                const pastaCount = parseInt(document.getElementById('pastaCount').textContent);
                const dessertCount = parseInt(document.getElementById('dessertCount').textContent);
                const juiceCount = parseInt(document.getElementById('juiceCount').textContent);
                const soupCount = parseInt(document.getElementById('soupCount').textContent);
                const appetizerCount = parseInt(document.getElementById('appetizerCount').textContent);
                
                // Enable all checkboxes first
                checkboxes.forEach(checkbox => {
                    checkbox.disabled = false;
                });
                
                // REMOVED THE DISABLING LOGIC FOR BEVERAGES, DESSERTS, SOUPS AND APPETIZERS
                // Users can now select unlimited extras
                
                // Only disable main dishes if they don't meet the base requirements
                checkboxes.forEach(checkbox => {
                    const category = checkbox.getAttribute('data-category');
                    const isChecked = checkbox.checked;
                    const isMainDish = checkbox.classList.contains('main-dish');
                    
                    if (!isChecked && isMainDish) {
                        // For main dishes, only disable if base requirements aren't met
                        if (mainDishCount < requiredTotal) {
                            // Still need to reach base requirement
                            if (category === 'vegetables' && vegetablesCount >= 1 && mainDishCount < requiredTotal) {
                                // Don't disable vegetables if we still need to reach base count
                            } else if (category === 'pasta' && pastaCount >= 1 && mainDishCount < requiredTotal) {
                                // Don't disable pasta if we still need to reach base count
                            }
                        }
                    }
                });
                
                // Update selection status color based on validity
                const baseMainDishesValid = validateBaseMainDishRules(Math.min(mainDishCount, requiredTotal), 
                                                                     Math.min(meatCount, requiredTotal), 
                                                                     Math.min(vegetablesCount, 1), 
                                                                     Math.min(pastaCount, 1), 
                                                                     requiredTotal);
                
                if (baseMainDishesValid) {
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
                if (!baseMainDishesValid) {
                    if (mainDishCount < requiredTotal) {
                        errorMessage = `Please select at least ${requiredTotal} main dishes. `;
                    } else {
                        errorMessage = 'Base main dish selection does not follow package rules. ';
                    }
                }
                
                // Show extra selection info
                let extraMessage = '';
                const extraMainDishes = Math.max(0, mainDishCount - requiredTotal);
                const extraDesserts = Math.max(0, dessertCount - 2);
                const extraJuices = Math.max(0, juiceCount - 2);
                const extraSoups = Math.max(0, soupCount - 1);
                const extraAppetizers = Math.max(0, appetizerCount - 2);
                
                if (extraMainDishes > 0 || extraDesserts > 0 || extraJuices > 0 || extraSoups > 0 || extraAppetizers > 0) {
                    extraMessage = 'Extra selections: ';
                    if (extraMainDishes > 0) extraMessage += `${extraMainDishes} main dish(es) `;
                    if (extraDesserts > 0) extraMessage += `${extraDesserts} dessert(s) `;
                    if (extraJuices > 0) extraMessage += `${extraJuices} juice(s) `;
                    if (extraSoups > 0) extraMessage += `${extraSoups} soup(s) `;
                    if (extraAppetizers > 0) extraMessage += `${extraAppetizers} appetizer(s) `;
                    extraMessage += '- will be charged extra';
                }
                
                // Add messages to status if needed
                const existingError = selectionStatus.querySelector('.error-message');
                const existingExtra = selectionStatus.querySelector('.extra-message');
                
                if (existingError) existingError.remove();
                if (existingExtra) existingExtra.remove();
                
                if (errorMessage) {
                    const errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    errorElement.style.fontSize = '0.9em';
                    errorElement.style.marginTop = '5px';
                    errorElement.style.color = '#c62828';
                    errorElement.textContent = errorMessage;
                    selectionStatus.appendChild(errorElement);
                }
                
                if (extraMessage) {
                    const extraElement = document.createElement('div');
                    extraElement.className = 'extra-message';
                    extraElement.style.fontSize = '0.9em';
                    extraElement.style.marginTop = '5px';
                    extraElement.style.color = '#ff9800';
                    extraElement.textContent = extraMessage;
                    selectionStatus.appendChild(extraElement);
                }
            }
            
            function validateBaseMainDishRules(total, meat, vegetables, pasta, required) {
                if (total < required) {
                    return false;
                }
                
                // Only validate the base required number of dishes
                if (required === 5) {
                    return (vegetables === 1 && meat >= 4) || 
                           (vegetables === 1 && meat >= 3 && pasta >= 1);
                } else if (required === 4) {
                    return (vegetables === 1 && meat >= 3) || 
                           (vegetables === 1 && meat >= 2 && pasta >= 1);
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