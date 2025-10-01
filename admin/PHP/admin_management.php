<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin authentication
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: Index.php");
    exit();
}

// Database Connection
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Create archive tables if they don't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS archived_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        base_attendees INT NOT NULL,
        min_attendees INT DEFAULT 100,
        max_attendees INT DEFAULT 150,
        excess_price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        includes TEXT NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        reason TEXT
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS archived_services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        category VARCHAR(50) DEFAULT 'General',
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        reason TEXT
    )
");

// Catering tables
$pdo->exec("
    CREATE TABLE IF NOT EXISTS catering_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        min_attendees INT NOT NULL DEFAULT 100,
        dish_count INT NOT NULL,
        includes TEXT NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS catering_dishes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category ENUM('Pork','Chicken','Beef','Fish','Vegetables','Pasta','Dessert','Juice','Soup','Appetizer') NOT NULL,
        description TEXT,
        is_default TINYINT(1) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS catering_addons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS archived_catering_packages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        base_price DECIMAL(10,2) NOT NULL,
        min_attendees INT NOT NULL DEFAULT 100,
        dish_count INT NOT NULL,
        includes TEXT NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        reason TEXT
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS archived_catering_dishes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        category ENUM('Pork','Chicken','Beef','Fish','Vegetables','Pasta','Dessert','Juice','Soup','Appetizer') NOT NULL,
        description TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        reason TEXT
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS archived_catering_addons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        original_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        description TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by INT,
        reason TEXT
    )
");

// Insert sample catering packages if they don't exist
$checkPackages = $pdo->query("SELECT COUNT(*) FROM catering_packages")->fetchColumn();
if ($checkPackages == 0) {
    $pdo->exec("INSERT INTO catering_packages (name, base_price, min_attendees, dish_count, includes) VALUES
        ('4-Dish Package', 10000.00, 100, 4, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts'),
        ('5-Dish Package', 15000.00, 100, 5, 'Rental service, Buffet service, Registration/gift/cake tables, Complete silverware, Steamed rice, Drinks, Desserts')");
}

// Insert sample catering dishes if they don't exist
$checkDishes = $pdo->query("SELECT COUNT(*) FROM catering_dishes")->fetchColumn();
if ($checkDishes == 0) {
    $pdo->exec("INSERT INTO catering_dishes (name, category, description, is_default) VALUES
        -- Pork Dishes
        ('Patatim', 'Pork', 'Slow-braised pork leg in savory sauce', 0),
        ('Sweet & Sour Meat Balls', 'Pork', 'Pork meatballs in tangy sweet and sour sauce', 0),
        ('Menudo', 'Pork', 'Traditional Filipino pork stew with vegetables', 0),
        ('Pork Tonkatsu', 'Pork', 'Japanese-style breaded and fried pork cutlet', 0),
        ('Crispy Pork Kare Kare', 'Pork', 'Crispy pork with peanut sauce and vegetables', 0),
        ('Oriental Pork Special', 'Pork', 'Asian-inspired pork dish with special sauce', 0),
        
        -- Beef Dishes
        ('Stir Fry Beef Broccoli', 'Beef', 'Tender beef with fresh broccoli in savory sauce', 0),
        ('Special Beef Morcon', 'Beef', 'Filipino beef roll with savory filling', 0),
        ('Special Caldereta', 'Beef', 'Spicy beef stew with tomato sauce and vegetables', 0),
        ('Beef in Mushroom Sauce', 'Beef', 'Tender beef slices in creamy mushroom sauce', 0),
        
        -- Chicken Dishes
        ('Cordon Bleu with Garlic Sauce', 'Chicken', 'Breaded chicken stuffed with ham and cheese', 0),
        ('Buffalo Wings with Veg Sticks & Onion Dip', 'Chicken', 'Spicy chicken wings with vegetable sticks and dip', 0),
        ('Classic Fried Chicken with Gravy', 'Chicken', 'Crispy fried chicken with creamy gravy', 0),
        ('Korean Style Fried Chicken', 'Chicken', 'Crispy chicken with sweet and spicy Korean sauce', 0),
        ('Chicken Fingers with Honey Mustard Sauce', 'Chicken', 'Breaded chicken strips with honey mustard dip', 0),
        ('Creamy Chicken Pastel', 'Chicken', 'Creamy chicken pot pie filling', 0),
        
        -- Fish Dishes
        ('Seared Fish Fillet in Lemon Beurre Blanc Sauce', 'Fish', 'Pan-seared fish with lemon butter sauce', 0),
        ('Breaded Fish Fillet with Creamy Sauce', 'Fish', 'Crispy breaded fish with creamy dressing', 0),
        ('Fish Tempura with Honey Mustard Cream Sauce', 'Fish', 'Lightly battered fish with special sauce', 0),
        ('Beer-Battered Fish Fingers with Honey Mustard Sauce', 'Fish', 'Crispy beer-battered fish with dipping sauce', 0),
        ('Shrimp in Creamy Garlic Parmesan Sauce', 'Fish', 'Shrimp in rich garlic parmesan cream sauce', 0),
        ('Shrimp Gambas', 'Fish', 'Spanish-style garlic shrimp', 0),
        ('Relyenong Bangus', 'Fish', 'Stuffed milkfish, a Filipino delicacy', 0),
        ('Steamed Fish Fillet with Sauce or Mayo', 'Fish', 'Healthy steamed fish with choice of sauce (+₱50/head)', 0),
        
        -- Vegetable Dishes
        ('Buttered Mixed Vegetables with Quail Eggs', 'Vegetables', 'Fresh vegetables in butter sauce with quail eggs', 1),
        ('Herbed Garlic Potatoes', 'Vegetables', 'Roasted potatoes with herbs and garlic', 0),
        ('Lumpiang Sariwa with Special Peanut Sauce', 'Vegetables', 'Fresh spring rolls with peanut sauce', 0),
        ('Herb-Buttered Glazed Vegetables', 'Vegetables', 'Seasonal vegetables with herb butter glaze', 0),
        ('French Steak Fries with Sriracha Sauce', 'Vegetables', 'Thick-cut fries with spicy sriracha sauce', 0),
        ('Oven-Baked Cheesy Vegetables', 'Vegetables', 'Mixed vegetables baked with cheese topping', 0),
        
        -- Pasta Dishes
        ('100% Beef Spaghetti with Meatballs', 'Pasta', 'Classic spaghetti with beef meatballs', 1),
        ('Kids Style Double Cheese Spaghetti', 'Pasta', 'Cheesy spaghetti that kids love', 0),
        ('Fettuccini Alfredo', 'Pasta', 'Creamy fettuccini with parmesan sauce', 0),
        ('Creamy Bacon Carbonara', 'Pasta', 'Classic carbonara with bacon and cream sauce', 0),
        ('Baked Beef Lasagna', 'Pasta', 'Layered pasta with beef and cheese', 0),
        ('Cheesy Baked Mac', 'Pasta', 'Baked macaroni with three cheeses', 0),
        ('Vegetarian Pasta with Olives & Tomato Herbs', 'Pasta', 'Healthy pasta with olives and fresh herbs', 0),
        ('Oriental Pasta', 'Pasta', 'Asian-inspired pasta dish', 0),
        
        -- Desserts
        ('Buko Pandan', 'Dessert', 'Filipino dessert with coconut and pandan', 1),
        ('Buko Salad', 'Dessert', 'Young coconut salad with fruits and cream', 0),
        ('Fruit Salad', 'Dessert', 'Mixed fruits in creamy dressing', 0),
        ('Leche Flan', 'Dessert', 'Caramel custard dessert', 0),
        ('Macapuno', 'Dessert', 'Sweet coconut sport dessert', 0),
        ('Mango Sago', 'Dessert', 'Refreshing mango and sago pudding', 0),
        
        -- Juice Flavors
        ('Cucumber Lemonade', 'Juice', 'Refreshing cucumber-infused lemonade', 1),
        ('Blue Lemonade', 'Juice', 'Vibrant blue lemonade drink', 0),
        ('House Blend Iced Tea', 'Juice', 'Specialty iced tea blend', 0),
        ('Red Iced Tea', 'Juice', 'Fruity red iced tea', 0),
        
        -- Soup Options
        ('Pumpkin Soup with Bacon Bits', 'Soup', 'Creamy pumpkin soup with crispy bacon', 0),
        ('Cream of Mushroom Soup', 'Soup', 'Classic creamy mushroom soup', 0),
        ('Crab & Corn Soup', 'Soup', 'Rich crab and sweet corn soup', 0),
        
        -- Appetizers
        ('Pica-Pica Crackers', 'Appetizer', 'Assorted crackers and bites', 0),
        ('Cornicks', 'Appetizer', 'Crunchy corn snacks', 0),
        ('Nuts', 'Appetizer', 'Assorted roasted nuts', 0)");
}

// Insert sample catering addons if they don't exist
$checkAddons = $pdo->query("SELECT COUNT(*) FROM catering_addons")->fetchColumn();
if ($checkAddons == 0) {
    $pdo->exec("INSERT INTO catering_addons (name, price, description) VALUES
        ('Mini Dessert Bar + Organic Salad Buffet', 9000.00, 'Delicious mini desserts and fresh organic salad bar'),
        ('Soup Option', 40.00, 'Per person soup addition'),
        ('Appetizer Option', 15.00, 'Per person appetizer addition'),
        ('Extra Dessert Selection', 3000.00, 'Additional dessert option'),
        ('Extra Juice Selection', 2000.00, 'Additional juice flavor'),
        ('Extra Dish', 5000.00, 'Additional main dish selection')");
}

// Handle form submissions
$message = '';
$error = '';

// Handle archive requests from the modal
if (isset($_GET['archive_type']) && isset($_GET['archive_id']) && isset($_GET['reason'])) {
    $archive_type = $_GET['archive_type'];
    $archive_id = $_GET['archive_id'];
    $reason = $_GET['reason'];
    
    try {
        switch ($archive_type) {
            case 'package':
                // Archive package
                $package_stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
                $package_stmt->execute([$archive_id]);
                $package = $package_stmt->fetch();
                
                if ($package) {
                    $stmt = $pdo->prepare("INSERT INTO archived_packages (original_id, name, base_price, base_attendees, min_attendees, max_attendees, excess_price, duration, includes, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $package['id'],
                        $package['name'],
                        $package['base_price'],
                        $package['base_attendees'],
                        $package['min_attendees'],
                        $package['max_attendees'],
                        $package['excess_price'],
                        $package['duration'],
                        $package['includes'],
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Package archived successfully!";
                }
                break;
                
            case 'service':
                // Archive service
                $service_stmt = $pdo->prepare("SELECT * FROM service WHERE services_id = ?");
                $service_stmt->execute([$archive_id]);
                $service = $service_stmt->fetch();
                
                if ($service) {
                    $stmt = $pdo->prepare("INSERT INTO archived_services (original_id, name, price, description, category, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $service['services_id'],
                        $service['name'],
                        $service['price'],
                        $service['description'] ?? '',
                        'General',
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM service WHERE services_id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Service archived successfully!";
                }
                break;
                
            case 'equipment':
                // Archive equipment
                $equipment_stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
                $equipment_stmt->execute([$archive_id]);
                $equipment = $equipment_stmt->fetch();
                
                if ($equipment) {
                    $stmt = $pdo->prepare("INSERT INTO archived_inventory (original_id, item_name, category, category_id, quantity, available_quantity, unit_price, supplier, reorder_level, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $equipment['id'],
                        $equipment['item_name'],
                        $equipment['category'],
                        $equipment['category_id'],
                        $equipment['quantity'],
                        $equipment['available_quantity'] ?? $equipment['quantity'],
                        $equipment['unit_price'],
                        $equipment['supplier'] ?? 'Unknown',
                        $equipment['reorder_level'] ?? 5,
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Equipment archived successfully!";
                }
                break;
                
            case 'catering_package':
                // Archive catering package
                $package_stmt = $pdo->prepare("SELECT * FROM catering_packages WHERE id = ?");
                $package_stmt->execute([$archive_id]);
                $package = $package_stmt->fetch();
                
                if ($package) {
                    $stmt = $pdo->prepare("INSERT INTO archived_catering_packages (original_id, name, base_price, min_attendees, dish_count, includes, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $package['id'],
                        $package['name'],
                        $package['base_price'],
                        $package['min_attendees'],
                        $package['dish_count'],
                        $package['includes'],
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM catering_packages WHERE id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Catering package archived successfully!";
                }
                break;
                
            case 'catering_dish':
                // Archive catering dish
                $dish_stmt = $pdo->prepare("SELECT * FROM catering_dishes WHERE id = ?");
                $dish_stmt->execute([$archive_id]);
                $dish = $dish_stmt->fetch();
                
                if ($dish) {
                    $stmt = $pdo->prepare("INSERT INTO archived_catering_dishes (original_id, name, category, description, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $dish['id'],
                        $dish['name'],
                        $dish['category'],
                        $dish['description'],
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM catering_dishes WHERE id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Catering dish archived successfully!";
                }
                break;
                
            case 'catering_addon':
                // Archive catering addon
                $addon_stmt = $pdo->prepare("SELECT * FROM catering_addons WHERE id = ?");
                $addon_stmt->execute([$archive_id]);
                $addon = $addon_stmt->fetch();
                
                if ($addon) {
                    $stmt = $pdo->prepare("INSERT INTO archived_catering_addons (original_id, name, price, description, archived_by, reason) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $addon['id'],
                        $addon['name'],
                        $addon['price'],
                        $addon['description'],
                        $_SESSION['user_id'],
                        $reason
                    ]);
                    
                    $delete_stmt = $pdo->prepare("DELETE FROM catering_addons WHERE id = ?");
                    $delete_stmt->execute([$archive_id]);
                    $message = "Catering addon archived successfully!";
                }
                break;
        }
        
    } catch (Exception $e) {
        $error = "Error archiving item: " . $e->getMessage();
    }
}

// Package Management
if (isset($_POST['add_package'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO packages (name, base_price, base_attendees, min_attendees, max_attendees, excess_price, duration, includes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['package_name'],
            $_POST['base_price'],
            $_POST['base_attendees'],
            $_POST['min_attendees'] ?? 100,
            $_POST['max_attendees'] ?? 150,
            $_POST['excess_price'],
            $_POST['duration'],
            $_POST['includes']
        ]);
        $message = "Package added successfully!";
    } catch (Exception $e) {
        $error = "Error adding package: " . $e->getMessage();
    }
}

if (isset($_POST['update_package'])) {
    try {
        $stmt = $pdo->prepare("UPDATE packages SET name = ?, base_price = ?, base_attendees = ?, min_attendees = ?, max_attendees = ?, excess_price = ?, duration = ?, includes = ? WHERE id = ?");
        $stmt->execute([
            $_POST['package_name'],
            $_POST['base_price'],
            $_POST['base_attendees'],
            $_POST['min_attendees'],
            $_POST['max_attendees'],
            $_POST['excess_price'],
            $_POST['duration'],
            $_POST['includes'],
            $_POST['package_id']
        ]);
        $message = "Package updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating package: " . $e->getMessage();
    }
}

// Service Management
if (isset($_POST['add_service'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO service (name, price) VALUES (?, ?)");
        $stmt->execute([
            $_POST['service_name'],
            $_POST['service_price']
        ]);
        $message = "Service added successfully!";
    } catch (Exception $e) {
        $error = "Error adding service: " . $e->getMessage();
    }
}

if (isset($_POST['update_service'])) {
    try {
        $stmt = $pdo->prepare("UPDATE service SET name = ?, price = ? WHERE services_id = ?");
        $stmt->execute([
            $_POST['service_name'],
            $_POST['service_price'],
            $_POST['service_id']
        ]);
        $message = "Service updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating service: " . $e->getMessage();
    }
}

// Equipment Management
if (isset($_POST['add_equipment'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, category_id, quantity, available_quantity, unit_price, supplier, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['equipment_name'],
            $_POST['equipment_category'],
            $_POST['category_id'],
            $_POST['quantity'],
            $_POST['quantity'], // Set available_quantity same as quantity initially
            $_POST['unit_price'],
            $_POST['supplier'] ?? 'Unknown',
            $_POST['reorder_level'] ?? 5
        ]);
        $message = "Equipment added successfully!";
    } catch (Exception $e) {
        $error = "Error adding equipment: " . $e->getMessage();
    }
}

if (isset($_POST['update_equipment'])) {
    try {
        $stmt = $pdo->prepare("UPDATE inventory SET item_name = ?, category = ?, category_id = ?, quantity = ?, unit_price = ?, supplier = ?, reorder_level = ? WHERE id = ?");
        $stmt->execute([
            $_POST['equipment_name'],
            $_POST['equipment_category'],
            $_POST['category_id'],
            $_POST['quantity'],
            $_POST['unit_price'],
            $_POST['supplier'],
            $_POST['reorder_level'],
            $_POST['equipment_id']
        ]);
        $message = "Equipment updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating equipment: " . $e->getMessage();
    }
}

// Restore functionality
if (isset($_GET['restore_package'])) {
    try {
        // Get archived package
        $package_stmt = $pdo->prepare("SELECT * FROM archived_packages WHERE id = ?");
        $package_stmt->execute([$_GET['restore_package']]);
        $package = $package_stmt->fetch();
        
        // Restore to packages table
        $stmt = $pdo->prepare("INSERT INTO packages (name, base_price, base_attendees, min_attendees, max_attendees, excess_price, duration, includes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $package['name'],
            $package['base_price'],
            $package['base_attendees'],
            $package['min_attendees'],
            $package['max_attendees'],
            $package['excess_price'],
            $package['duration'],
            $package['includes']
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_packages WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_package']]);
        
        $message = "Package restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring package: " . $e->getMessage();
    }
}

if (isset($_GET['restore_service'])) {
    try {
        // Get archived service
        $service_stmt = $pdo->prepare("SELECT * FROM archived_services WHERE id = ?");
        $service_stmt->execute([$_GET['restore_service']]);
        $service = $service_stmt->fetch();
        
        // Restore to service table
        $stmt = $pdo->prepare("INSERT INTO service (name, price) VALUES (?, ?)");
        $stmt->execute([
            $service['name'],
            $service['price']
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_services WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_service']]);
        
        $message = "Service restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring service: " . $e->getMessage();
    }
}

if (isset($_GET['restore_equipment'])) {
    try {
        // Get archived equipment
        $equipment_stmt = $pdo->prepare("SELECT * FROM archived_inventory WHERE id = ?");
        $equipment_stmt->execute([$_GET['restore_equipment']]);
        $equipment = $equipment_stmt->fetch();
        
        // Restore to inventory table
        $stmt = $pdo->prepare("INSERT INTO inventory (item_name, category, category_id, quantity, available_quantity, unit_price, supplier, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $equipment['item_name'],
            $equipment['category'],
            $equipment['category_id'],
            $equipment['quantity'],
            $equipment['available_quantity'] ?? $equipment['quantity'],
            $equipment['unit_price'],
            $equipment['supplier'],
            $equipment['reorder_level']
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_inventory WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_equipment']]);
        
        $message = "Equipment restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring equipment: " . $e->getMessage();
    }
}

// Catering Package Management
if (isset($_POST['add_catering_package'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO catering_packages (name, base_price, min_attendees, dish_count, includes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['catering_package_name'],
            $_POST['catering_base_price'],
            $_POST['catering_min_attendees'],
            $_POST['dish_count'],
            $_POST['catering_includes']
        ]);
        $message = "Catering package added successfully!";
    } catch (Exception $e) {
        $error = "Error adding catering package: " . $e->getMessage();
    }
}

if (isset($_POST['update_catering_package'])) {
    try {
        $stmt = $pdo->prepare("UPDATE catering_packages SET name = ?, base_price = ?, min_attendees = ?, dish_count = ?, includes = ? WHERE id = ?");
        $stmt->execute([
            $_POST['catering_package_name'],
            $_POST['catering_base_price'],
            $_POST['catering_min_attendees'],
            $_POST['dish_count'],
            $_POST['catering_includes'],
            $_POST['catering_package_id']
        ]);
        $message = "Catering package updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating catering package: " . $e->getMessage();
    }
}

// Catering Dish Management
if (isset($_POST['add_catering_dish'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO catering_dishes (name, category, description, is_default) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['dish_name'],
            $_POST['dish_category'],
            $_POST['dish_description'],
            $_POST['is_default'] ?? 0
        ]);
        $message = "Catering dish added successfully!";
    } catch (Exception $e) {
        $error = "Error adding catering dish: " . $e->getMessage();
    }
}

if (isset($_POST['update_catering_dish'])) {
    try {
        $stmt = $pdo->prepare("UPDATE catering_dishes SET name = ?, category = ?, description = ?, is_default = ? WHERE id = ?");
        $stmt->execute([
            $_POST['dish_name'],
            $_POST['dish_category'],
            $_POST['dish_description'],
            $_POST['is_default'] ?? 0,
            $_POST['dish_id']
        ]);
        $message = "Catering dish updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating catering dish: " . $e->getMessage();
    }
}

// Catering Addon Management
if (isset($_POST['add_catering_addon'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO catering_addons (name, price, description) VALUES (?, ?, ?)");
        $stmt->execute([
            $_POST['addon_name'],
            $_POST['addon_price'],
            $_POST['addon_description']
        ]);
        $message = "Catering addon added successfully!";
    } catch (Exception $e) {
        $error = "Error adding catering addon: " . $e->getMessage();
    }
}

if (isset($_POST['update_catering_addon'])) {
    try {
        $stmt = $pdo->prepare("UPDATE catering_addons SET name = ?, price = ?, description = ? WHERE id = ?");
        $stmt->execute([
            $_POST['addon_name'],
            $_POST['addon_price'],
            $_POST['addon_description'],
            $_POST['addon_id']
        ]);
        $message = "Catering addon updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating catering addon: " . $e->getMessage();
    }
}

// Restore catering items
if (isset($_GET['restore_catering_package'])) {
    try {
        // Get archived package
        $package_stmt = $pdo->prepare("SELECT * FROM archived_catering_packages WHERE id = ?");
        $package_stmt->execute([$_GET['restore_catering_package']]);
        $package = $package_stmt->fetch();
        
        // Restore to packages table
        $stmt = $pdo->prepare("INSERT INTO catering_packages (name, base_price, min_attendees, dish_count, includes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $package['name'],
            $package['base_price'],
            $package['min_attendees'],
            $package['dish_count'],
            $package['includes']
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_catering_packages WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_catering_package']]);
        
        $message = "Catering package restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring catering package: " . $e->getMessage();
    }
}

if (isset($_GET['restore_catering_dish'])) {
    try {
        // Get archived dish
        $dish_stmt = $pdo->prepare("SELECT * FROM archived_catering_dishes WHERE id = ?");
        $dish_stmt->execute([$_GET['restore_catering_dish']]);
        $dish = $dish_stmt->fetch();
        
        // Restore to dishes table
        $stmt = $pdo->prepare("INSERT INTO catering_dishes (name, category, description, is_default) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $dish['name'],
            $dish['category'],
            $dish['description'],
            0
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_catering_dishes WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_catering_dish']]);
        
        $message = "Catering dish restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring catering dish: " . $e->getMessage();
    }
}

if (isset($_GET['restore_catering_addon'])) {
    try {
        // Get archived addon
        $addon_stmt = $pdo->prepare("SELECT * FROM archived_catering_addons WHERE id = ?");
        $addon_stmt->execute([$_GET['restore_catering_addon']]);
        $addon = $addon_stmt->fetch();
        
        // Restore to addons table
        $stmt = $pdo->prepare("INSERT INTO catering_addons (name, price, description) VALUES (?, ?, ?)");
        $stmt->execute([
            $addon['name'],
            $addon['price'],
            $addon['description']
        ]);
        
        // Remove from archive
        $delete_stmt = $pdo->prepare("DELETE FROM archived_catering_addons WHERE id = ?");
        $delete_stmt->execute([$_GET['restore_catering_addon']]);
        
        $message = "Catering addon restored successfully!";
    } catch (Exception $e) {
        $error = "Error restoring catering addon: " . $e->getMessage();
    }
}

// Fetch data
$packages = $pdo->query("SELECT * FROM packages")->fetchAll();
$services = $pdo->query("SELECT * FROM service")->fetchAll();
$equipment = $pdo->query("SELECT i.*, ec.name as category_name FROM inventory i LEFT JOIN equipment_categories ec ON i.category_id = ec.id ORDER BY ec.name, i.item_name")->fetchAll();
$categories = $pdo->query("SELECT * FROM equipment_categories WHERE status = 'active'")->fetchAll();

// Fetch catering data
$catering_packages = $pdo->query("SELECT * FROM catering_packages")->fetchAll();
$catering_dishes = $pdo->query("SELECT * FROM catering_dishes ORDER BY category, name")->fetchAll();
$catering_addons = $pdo->query("SELECT * FROM catering_addons")->fetchAll();

// Fetch archived items
$archived_packages = $pdo->query("SELECT * FROM archived_packages ORDER BY archived_at DESC")->fetchAll();
$archived_services = $pdo->query("SELECT * FROM archived_services ORDER BY archived_at DESC")->fetchAll();
$archived_equipment = $pdo->query("SELECT * FROM archived_inventory ORDER BY archived_at DESC")->fetchAll();
$archived_catering_packages = $pdo->query("SELECT * FROM archived_catering_packages ORDER BY archived_at DESC")->fetchAll();
$archived_catering_dishes = $pdo->query("SELECT * FROM archived_catering_dishes ORDER BY archived_at DESC")->fetchAll();
$archived_catering_addons = $pdo->query("SELECT * FROM archived_catering_addons ORDER BY archived_at DESC")->fetchAll();

// Get items for editing
$edit_package = null;
if (isset($_GET['edit_package'])) {
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$_GET['edit_package']]);
    $edit_package = $stmt->fetch();
}

$edit_service = null;
if (isset($_GET['edit_service'])) {
    $stmt = $pdo->prepare("SELECT * FROM service WHERE services_id = ?");
    $stmt->execute([$_GET['edit_service']]);
    $edit_service = $stmt->fetch();
}

$edit_equipment = null;
if (isset($_GET['edit_equipment'])) {
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$_GET['edit_equipment']]);
    $edit_equipment = $stmt->fetch();
}

$edit_catering_package = null;
if (isset($_GET['edit_catering_package'])) {
    $stmt = $pdo->prepare("SELECT * FROM catering_packages WHERE id = ?");
    $stmt->execute([$_GET['edit_catering_package']]);
    $edit_catering_package = $stmt->fetch();
}

$edit_catering_dish = null;
if (isset($_GET['edit_catering_dish'])) {
    $stmt = $pdo->prepare("SELECT * FROM catering_dishes WHERE id = ?");
    $stmt->execute([$_GET['edit_catering_dish']]);
    $edit_catering_dish = $stmt->fetch();
}

$edit_catering_addon = null;
if (isset($_GET['edit_catering_addon'])) {
    $stmt = $pdo->prepare("SELECT * FROM catering_addons WHERE id = ?");
    $stmt->execute([$_GET['edit_catering_addon']]);
    $edit_catering_addon = $stmt->fetch();
}

// Determine active tab based on what we're editing
$active_tab = 'packages';
if (isset($_GET['edit_service'])) $active_tab = 'services';
if (isset($_GET['edit_equipment'])) $active_tab = 'equipment';
if (isset($_GET['edit_catering_package'])) $active_tab = 'catering-packages';
if (isset($_GET['edit_catering_dish'])) $active_tab = 'catering-dishes';
if (isset($_GET['edit_catering_addon'])) $active_tab = 'catering-addons';
if (isset($_GET['archive_type'])) $active_tab = 'archived';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BTONE - Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-bg: #422b0d;
            --secondary-bg: #eae7de;
            --card-bg: #ffffff;
            --text-light: #ffffff;
            --text-dark: #000000;
            --accent: #A08963;
            --accent-dark: #8a745a;
            --highlight: #6b411e;
            --success: #4caf50;
            --danger: #f44336;
            --warning: #ff9800;
            --info: #2196f3;
            --border: #d7ccc8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--secondary-bg);
            color: var(--text-dark);
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--primary-bg);
            color: var(--text-light);
            height: 100vh;
            padding: 20px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        
        .sidebar .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--accent);
        }
        
        .sidebar .logo h2 {
            font-size: 24px;
            color: var(--text-light);
            margin: 0;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-menu li {
            margin: 8px 0;
        }
        
        .nav-menu li a {
            text-decoration: none;
            color: var(--text-light);
            padding: 12px 16px;
            display: block;
            border-radius: 6px;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .nav-menu li a:hover,
        .nav-menu li a.active {
            background-color: var(--accent-dark);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
            width: calc(100% - 250px);
        }
        
        .management-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .management-header {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            text-align: center;
        }
        
        .management-header h1 {
            color: var(--primary-bg);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        /* Tabs */
        .tab-container {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .tab-buttons {
            display: flex;
            background: var(--secondary-bg);
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s ease;
            position: relative;
            font-size: 14px;
        }
        
        .tab-btn.active {
            background: var(--card-bg);
            color: var(--primary-bg);
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--primary-bg);
        }
        
        .tab-btn i {
            margin-right: 8px;
        }
        
        .tab-content {
            display: none;
            padding: 25px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
            border: 1px solid #f0f0f0;
        }
        
        .card-header {
            background: var(--primary-bg);
            color: white;
            padding: 18px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--highlight);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-bg);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background: var(--primary-bg);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--highlight);
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #d32f2f;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn-warning:hover {
            background: #f57c00;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #388e3c;
        }
        
        .btn-sm {
            padding: 8px 15px;
            font-size: 12px;
        }
        
        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-top: 15px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        
        .table th {
            background: var(--secondary-bg);
            font-weight: 600;
            color: var(--primary-bg);
        }
        
        .table tr:hover {
            background: rgba(66, 43, 13, 0.05);
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
            font-size: 14px;
        }
        
        .message.success {
            background: #e8f5e9;
            border-color: var(--success);
            color: #2e7d32;
        }
        
        .message.error {
            background: #ffebee;
            border-color: var(--danger);
            color: #c62828;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .modal-header {
            background: var(--primary-bg);
            color: white;
            padding: 18px 25px;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 18px;
        }
        
        .close {
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: var(--accent);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                padding: 15px;
            }
            
            .main-content {
                margin-left: 200px;
                padding: 20px;
                width: calc(100% - 200px);
            }
            
            .tab-buttons {
                flex-direction: column;
            }
            
            .tab-btn {
                padding: 12px 20px;
                text-align: left;
            }
            
            .management-header {
                padding: 20px;
            }
            
            .management-header h1 {
                font-size: 24px;
            }
            
            .card-body {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 60px;
                padding: 10px;
            }
            
            .sidebar .logo h2 {
                font-size: 0;
            }
            
            .sidebar .logo h2:after {
                content: "B";
                font-size: 20px;
            }
            
            .nav-menu li a span {
                display: none;
            }
            
            .main-content {
                margin-left: 60px;
                padding: 15px;
                width: calc(100% - 60px);
            }
            
            .management-header h1 {
                font-size: 20px;
            }
            
            .tab-content {
                padding: 15px;
            }
            
            .table th, .table td {
                padding: 8px 10px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar">
            <div class="logo">
        <h2>Admin Dashboard</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_user.php">Admin Management</a></li>
        <li><a href="user_management.php">User Management</a></li>
        <li><a href="calendar.php">Calendar</a></li>
        <li><a href="Inventory.php">Inventory</a></li>
        <li><a href="admin_management.php">Edit</a></li>
        <li><a href="Index.php?logout=true">Logout</a></li>
    </ul>
</nav>

        <main class="main-content">
            <div class="management-container">
                <div class="management-header">
                    <h1><i class="fas fa-cogs"></i> BTONE Management System</h1>
                    <p>Manage Packages, Services, Equipment, and Catering</p>
                </div>

                <?php if ($message): ?>
                    <div class="message success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="message error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="tab-container">
                    <div class="tab-buttons">
                        <button class="tab-btn <?php echo $active_tab == 'packages' ? 'active' : ''; ?>" onclick="showTab('packages')">
                            <i class="fas fa-box"></i> Packages
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'services' ? 'active' : ''; ?>" onclick="showTab('services')">
                            <i class="fas fa-concierge-bell"></i> Services
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>" onclick="showTab('equipment')">
                            <i class="fas fa-tools"></i> Equipment
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'catering-packages' ? 'active' : ''; ?>" onclick="showTab('catering-packages')">
                            <i class="fas fa-utensils"></i> Catering Packages
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'catering-dishes' ? 'active' : ''; ?>" onclick="showTab('catering-dishes')">
                            <i class="fas fa-drumstick-bite"></i> Catering Dishes
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'catering-addons' ? 'active' : ''; ?>" onclick="showTab('catering-addons')">
                            <i class="fas fa-plus-circle"></i> Catering Addons
                        </button>
                        <button class="tab-btn <?php echo $active_tab == 'archived' ? 'active' : ''; ?>" onclick="showTab('archived')">
                            <i class="fas fa-archive"></i> Archived Items
                        </button>
                    </div>

                    <!-- Packages Tab -->
                    <div id="packages-tab" class="tab-content <?php echo $active_tab == 'packages' ? 'active' : ''; ?>">
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_package ? 'Edit Package' : 'Add New Package'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_package): ?>
                                        <input type="hidden" name="package_id" value="<?php echo $edit_package['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Package Name</label>
                                            <input type="text" name="package_name" class="form-control" 
                                                value="<?php echo $edit_package ? htmlspecialchars($edit_package['name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Base Price (₱)</label>
                                            <input type="number" name="base_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['base_price'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Base Attendees</label>
                                            <input type="number" name="base_attendees" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['base_attendees'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Min Attendees</label>
                                            <input type="number" name="min_attendees" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['min_attendees'] : '100'; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Max Attendees</label>
                                            <input type="number" name="max_attendees" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['max_attendees'] : '150'; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Excess Price per Person (₱)</label>
                                            <input type="number" name="excess_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['excess_price'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Duration (hours)</label>
                                            <input type="number" name="duration" class="form-control" 
                                                value="<?php echo $edit_package ? $edit_package['duration'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Includes (one per line)</label>
                                        <textarea name="includes" class="form-control" rows="5" required><?php echo $edit_package ? htmlspecialchars($edit_package['includes']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_package ? 'update_package' : 'add_package'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_package ? 'Update Package' : 'Add Package'; ?>
                                    </button>
                                    <?php if ($edit_package): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-box-open"></i> Existing Packages</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Base Price</th>
                                                <th>Attendees (Base/Min/Max)</th>
                                                <th>Excess Price</th>
                                                <th>Duration</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td>₱<?php echo number_format($package['base_price'], 2); ?></td>
                                                <td><?php echo $package['base_attendees']; ?> / <?php echo $package['min_attendees']; ?> / <?php echo $package['max_attendees']; ?></td>
                                                <td>₱<?php echo number_format($package['excess_price'], 2); ?></td>
                                                <td><?php echo $package['duration']; ?> hours</td>
                                                <td>
                                                    <a href="?edit_package=<?php echo $package['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('package', <?php echo $package['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Tab -->
                    <div id="services-tab" class="tab-content <?php echo $active_tab == 'services' ? 'active' : ''; ?>">
                        <!-- Services content remains the same as before -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_service ? 'Edit Service' : 'Add New Service'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_service): ?>
                                        <input type="hidden" name="service_id" value="<?php echo $edit_service['services_id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Service Name</label>
                                            <input type="text" name="service_name" class="form-control" 
                                                value="<?php echo $edit_service ? htmlspecialchars($edit_service['name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Price (₱)</label>
                                            <input type="number" name="service_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_service ? $edit_service['price'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_service ? 'update_service' : 'add_service'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_service ? 'Update Service' : 'Add Service'; ?>
                                    </button>
                                    <?php if ($edit_service): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-concierge-bell"></i> Existing Services</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td>₱<?php echo number_format($service['price'], 2); ?></td>
                                                <td>
                                                    <a href="?edit_service=<?php echo $service['services_id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('service', <?php echo $service['services_id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Tab -->
                    <div id="equipment-tab" class="tab-content <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>">
                        <!-- Equipment content remains the same as before -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_equipment ? 'Edit Equipment' : 'Add New Equipment'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_equipment): ?>
                                        <input type="hidden" name="equipment_id" value="<?php echo $edit_equipment['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Equipment Name</label>
                                            <input type="text" name="equipment_name" class="form-control" 
                                                value="<?php echo $edit_equipment ? htmlspecialchars($edit_equipment['item_name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="equipment_category" class="form-control" required onchange="updateCategoryId(this)">
                                                <option value="">Select Category</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo htmlspecialchars($category['name']); ?>" 
                                                    data-id="<?php echo $category['id']; ?>"
                                                    <?php echo ($edit_equipment && $edit_equipment['category'] == $category['name']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="category_id" id="category_id" 
                                                value="<?php echo $edit_equipment ? $edit_equipment['category_id'] : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Total Quantity</label>
                                            <input type="number" name="quantity" class="form-control" 
                                                value="<?php echo $edit_equipment ? $edit_equipment['quantity'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Unit Price (₱)</label>
                                            <input type="number" name="unit_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_equipment ? $edit_equipment['unit_price'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Supplier</label>
                                            <input type="text" name="supplier" class="form-control" 
                                                value="<?php echo $edit_equipment ? htmlspecialchars($edit_equipment['supplier'] ?? '') : ''; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Reorder Level</label>
                                            <input type="number" name="reorder_level" class="form-control" 
                                                value="<?php echo $edit_equipment ? $edit_equipment['reorder_level'] : '5'; ?>">
                                        </div>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_equipment ? 'update_equipment' : 'add_equipment'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_equipment ? 'Update Equipment' : 'Add Equipment'; ?>
                                    </button>
                                    <?php if ($edit_equipment): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-tools"></i> Existing Equipment</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Available Qty</th>
                                                <th>Unit Price</th>
                                                <th>Supplier</th>
                                                <th>Reorder Level</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($equipment as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category_name'] ?? $item['category'] ?? 'No category'); ?></td>
                                                <td><?php echo $item['available_quantity'] ?? 0; ?> / <?php echo $item['quantity']; ?></td>
                                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($item['supplier'] ?? 'Not specified'); ?></td>
                                                <td><?php echo $item['reorder_level'] ?? 5; ?></td>
                                                <td>
                                                    <a href="?edit_equipment=<?php echo $item['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('equipment', <?php echo $item['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catering Packages Tab -->
                    <div id="catering-packages-tab" class="tab-content <?php echo $active_tab == 'catering-packages' ? 'active' : ''; ?>">
                        <!-- Catering packages content remains the same -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_catering_package ? 'Edit Catering Package' : 'Add New Catering Package'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_catering_package): ?>
                                        <input type="hidden" name="catering_package_id" value="<?php echo $edit_catering_package['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Package Name</label>
                                            <input type="text" name="catering_package_name" class="form-control" 
                                                value="<?php echo $edit_catering_package ? htmlspecialchars($edit_catering_package['name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Base Price (₱)</label>
                                            <input type="number" name="catering_base_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_catering_package ? $edit_catering_package['base_price'] : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Minimum Attendees</label>
                                            <input type="number" name="catering_min_attendees" class="form-control" 
                                                value="<?php echo $edit_catering_package ? $edit_catering_package['min_attendees'] : '100'; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Dish Count</label>
                                            <input type="number" name="dish_count" class="form-control" 
                                                value="<?php echo $edit_catering_package ? $edit_catering_package['dish_count'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Includes (one per line)</label>
                                        <textarea name="catering_includes" class="form-control" rows="5" required><?php echo $edit_catering_package ? htmlspecialchars($edit_catering_package['includes']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_catering_package ? 'update_catering_package' : 'add_catering_package'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_catering_package ? 'Update Package' : 'Add Package'; ?>
                                    </button>
                                    <?php if ($edit_catering_package): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-utensils"></i> Existing Catering Packages</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Base Price</th>
                                                <th>Min Attendees</th>
                                                <th>Dish Count</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($catering_packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td>₱<?php echo number_format($package['base_price'], 2); ?></td>
                                                <td><?php echo $package['min_attendees']; ?></td>
                                                <td><?php echo $package['dish_count']; ?></td>
                                                <td>
                                                    <a href="?edit_catering_package=<?php echo $package['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('catering_package', <?php echo $package['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catering Dishes Tab -->
                    <div id="catering-dishes-tab" class="tab-content <?php echo $active_tab == 'catering-dishes' ? 'active' : ''; ?>">
                        <!-- Catering dishes content remains the same -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_catering_dish ? 'Edit Catering Dish' : 'Add New Catering Dish'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_catering_dish): ?>
                                        <input type="hidden" name="dish_id" value="<?php echo $edit_catering_dish['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Dish Name</label>
                                            <input type="text" name="dish_name" class="form-control" 
                                                value="<?php echo $edit_catering_dish ? htmlspecialchars($edit_catering_dish['name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="dish_category" class="form-control" required>
                                                <option value="">Select Category</option>
                                                <option value="Pork" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Pork') ? 'selected' : ''; ?>>Pork</option>
                                                <option value="Chicken" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Chicken') ? 'selected' : ''; ?>>Chicken</option>
                                                <option value="Beef" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Beef') ? 'selected' : ''; ?>>Beef</option>
                                                <option value="Fish" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Fish') ? 'selected' : ''; ?>>Fish</option>
                                                <option value="Vegetables" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Vegetables') ? 'selected' : ''; ?>>Vegetables</option>
                                                <option value="Pasta" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Pasta') ? 'selected' : ''; ?>>Pasta</option>
                                                <option value="Dessert" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Dessert') ? 'selected' : ''; ?>>Dessert</option>
                                                <option value="Juice" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Juice') ? 'selected' : ''; ?>>Juice</option>
                                                <option value="Soup" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Soup') ? 'selected' : ''; ?>>Soup</option>
                                                <option value="Appetizer" <?php echo ($edit_catering_dish && $edit_catering_dish['category'] == 'Appetizer') ? 'selected' : ''; ?>>Appetizer</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Default Item</label>
                                            <select name="is_default" class="form-control">
                                                <option value="0" <?php echo ($edit_catering_dish && $edit_catering_dish['is_default'] == 0) ? 'selected' : ''; ?>>No</option>
                                                <option value="1" <?php echo ($edit_catering_dish && $edit_catering_dish['is_default'] == 1) ? 'selected' : ''; ?>>Yes</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="dish_description" class="form-control" rows="3"><?php echo $edit_catering_dish ? htmlspecialchars($edit_catering_dish['description']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_catering_dish ? 'update_catering_dish' : 'add_catering_dish'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_catering_dish ? 'Update Dish' : 'Add Dish'; ?>
                                    </button>
                                    <?php if ($edit_catering_dish): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-drumstick-bite"></i> Existing Catering Dishes</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Description</th>
                                                <th>Default</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($catering_dishes as $dish): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dish['name']); ?></td>
                                                <td><?php echo htmlspecialchars($dish['category']); ?></td>
                                                <td><?php echo htmlspecialchars($dish['description']); ?></td>
                                                <td><?php echo $dish['is_default'] ? 'Yes' : 'No'; ?></td>
                                                <td>
                                                    <a href="?edit_catering_dish=<?php echo $dish['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('catering_dish', <?php echo $dish['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catering Addons Tab -->
                    <div id="catering-addons-tab" class="tab-content <?php echo $active_tab == 'catering-addons' ? 'active' : ''; ?>">
                        <!-- Catering addons content remains the same -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> <?php echo $edit_catering_addon ? 'Edit Catering Addon' : 'Add New Catering Addon'; ?></h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <?php if ($edit_catering_addon): ?>
                                        <input type="hidden" name="addon_id" value="<?php echo $edit_catering_addon['id']; ?>">
                                    <?php endif; ?>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label>Addon Name</label>
                                            <input type="text" name="addon_name" class="form-control" 
                                                value="<?php echo $edit_catering_addon ? htmlspecialchars($edit_catering_addon['name']) : ''; ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Price (₱)</label>
                                            <input type="number" name="addon_price" step="0.01" class="form-control" 
                                                value="<?php echo $edit_catering_addon ? $edit_catering_addon['price'] : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="addon_description" class="form-control" rows="3"><?php echo $edit_catering_addon ? htmlspecialchars($edit_catering_addon['description']) : ''; ?></textarea>
                                    </div>
                                    <button type="submit" name="<?php echo $edit_catering_addon ? 'update_catering_addon' : 'add_catering_addon'; ?>" class="btn btn-primary">
                                        <i class="fas fa-save"></i> <?php echo $edit_catering_addon ? 'Update Addon' : 'Add Addon'; ?>
                                    </button>
                                    <?php if ($edit_catering_addon): ?>
                                        <a href="admin_management.php" class="btn btn-danger">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-plus-circle"></i> Existing Catering Addons</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Description</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($catering_addons as $addon): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($addon['name']); ?></td>
                                                <td>₱<?php echo number_format($addon['price'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($addon['description']); ?></td>
                                                <td>
                                                    <a href="?edit_catering_addon=<?php echo $addon['id']; ?>" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <button class="btn btn-danger btn-sm" onclick="archiveItem('catering_addon', <?php echo $addon['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archived Items Tab -->
                    <div id="archived-tab" class="tab-content <?php echo $active_tab == 'archived' ? 'active' : ''; ?>">
                        <!-- Archived items content remains the same -->
                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Packages</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Base Price</th>
                                                <th>Attendees</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td>₱<?php echo number_format($package['base_price'], 2); ?></td>
                                                <td><?php echo $package['base_attendees']; ?> / <?php echo $package['min_attendees']; ?> / <?php echo $package['max_attendees']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($package['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($package['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_package=<?php echo $package['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this package?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Services</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_services as $service): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($service['name']); ?></td>
                                                <td>₱<?php echo number_format($service['price'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($service['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($service['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_service=<?php echo $service['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this service?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Equipment</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Quantity</th>
                                                <th>Unit Price</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_equipment as $item): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                                <td><?php echo $item['available_quantity'] ?? $item['quantity']; ?> / <?php echo $item['quantity']; ?></td>
                                                <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($item['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($item['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_equipment=<?php echo $item['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this equipment?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Catering Packages</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Base Price</th>
                                                <th>Min Attendees</th>
                                                <th>Dish Count</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_catering_packages as $package): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($package['name']); ?></td>
                                                <td>₱<?php echo number_format($package['base_price'], 2); ?></td>
                                                <td><?php echo $package['min_attendees']; ?></td>
                                                <td><?php echo $package['dish_count']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($package['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($package['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_catering_package=<?php echo $package['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this catering package?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Catering Dishes</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_catering_dishes as $dish): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dish['name']); ?></td>
                                                <td><?php echo htmlspecialchars($dish['category']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($dish['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($dish['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_catering_dish=<?php echo $dish['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this catering dish?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3><i class="fas fa-archive"></i> Archived Catering Addons</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-container">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Price</th>
                                                <th>Archived Date</th>
                                                <th>Reason</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archived_catering_addons as $addon): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($addon['name']); ?></td>
                                                <td>₱<?php echo number_format($addon['price'], 2); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($addon['archived_at'])); ?></td>
                                                <td><?php echo htmlspecialchars($addon['reason']); ?></td>
                                                <td>
                                                    <a href="?restore_catering_addon=<?php echo $addon['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Restore this catering addon?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Archive Item</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="archiveForm" method="GET" action="admin_management.php">
                    <input type="hidden" name="archive_type" id="archiveType">
                    <input type="hidden" name="archive_id" id="archiveId">
                    <div class="form-group">
                        <label>Reason for archiving:</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-archive"></i> Confirm Archive
                    </button>
                    <button type="button" class="btn" onclick="closeModal()" style="background: #ccc; margin-left: 10px;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }
        
        function updateCategoryId(select) {
            const selectedOption = select.options[select.selectedIndex];
            document.getElementById('category_id').value = selectedOption.getAttribute('data-id');
        }
        
        function archiveItem(type, id) {
            document.getElementById('archiveType').value = type;
            document.getElementById('archiveId').value = id;
            document.getElementById('archiveModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('archiveModal').style.display = 'none';
        }
        
        // Close modal if clicked outside
        window.onclick = function(event) {
            const modal = document.getElementById('archiveModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Initialize category ID if editing equipment
        <?php if ($edit_equipment): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.querySelector('select[name="equipment_category"]');
            if (categorySelect) {
                updateCategoryId(categorySelect);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>