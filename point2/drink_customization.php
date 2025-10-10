<?php
session_start();
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if we have item data
if (!isset($_GET['item_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get item details
$item_id = $_GET['item_id'];
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

// Get add-ons
$addonsQuery = "SELECT * FROM products WHERE category_id = 3 AND archived = 0";
$addonsResult = $conn->query($addonsQuery);
$addons = $addonsResult->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm'])) {
        $size = $_POST['size'];
        $sugar_level = $_POST['sugar_level'];
        $selected_addons = isset($_POST['addons']) ? $_POST['addons'] : [];
        
        // Calculate price based on size
        $price = 0;
        switch($size) {
            case 'hot':
                $price = floatval($item['price_hot']);
                break;
            case 'medium':
                $price = floatval($item['price_medium']);
                break;
            case 'large':
                $price = floatval($item['price_large']);
                break;
        }
        
        // Build item name with customizations
        $name = $item['name'] . " ($size, {$sugar_level}% sugar)";
        
        // Add selected add-ons
        $addon_names = [];
        foreach ($selected_addons as $addon_id) {
            foreach ($addons as $addon) {
                if ($addon['id'] == $addon_id) {
                    $price += floatval($addon['price']);
                    $addon_names[] = $addon['name'];
                }
            }
        }
        
        if (!empty($addon_names)) {
            $name .= " with " . implode(", ", $addon_names);
        }
        
        // Create cart item
        $cartItem = [
            'id' => $item['id'],
            'name' => $name,
            'price' => $price,
            'quantity' => 1,
            'category' => $item['category'],
            'code' => $item['code'] . strtoupper($size[0])
        ];
        
        // Add to cart
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if item already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$existingItem) {
            if ($existingItem['code'] === $cartItem['code']) {
                $existingItem['quantity']++;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $_SESSION['cart'][] = $cartItem;
        }
        
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Drink</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .addon-list {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .sugar-btn.active {
            background-color: #0d6efd;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4">Customize <?php echo htmlspecialchars($item['name']); ?></h2>
        
        <form method="POST">
            <!-- Size Selection -->
            <div class="mb-3">
                <label class="form-label">Size:</label>
                <div class="btn-group w-100" role="group">
                    <?php if ($item['price_hot'] > 0): ?>
                    <input type="radio" class="btn-check" name="size" id="hotSize" value="hot" required>
                    <label class="btn btn-outline-primary" for="hotSize">
                        Hot (₱<?php echo number_format($item['price_hot'], 2); ?>)
                    </label>
                    <?php endif; ?>
                    
                    <input type="radio" class="btn-check" name="size" id="mediumSize" value="medium" required>
                    <label class="btn btn-outline-primary" for="mediumSize">
                        Medium (₱<?php echo number_format($item['price_medium'], 2); ?>)
                    </label>
                    
                    <input type="radio" class="btn-check" name="size" id="largeSize" value="large">
                    <label class="btn btn-outline-primary" for="largeSize">
                        Large (₱<?php echo number_format($item['price_large'], 2); ?>)
                    </label>
                </div>
            </div>
            
            <!-- Sugar Level -->
            <div class="mb-3">
                <label class="form-label">Sugar Level:</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="sugar_level" id="sugar0" value="0" required>
                    <label class="btn btn-outline-secondary sugar-btn" for="sugar0">0%</label>
                    
                    <input type="radio" class="btn-check" name="sugar_level" id="sugar25" value="25">
                    <label class="btn btn-outline-secondary sugar-btn" for="sugar25">25%</label>
                    
                    <input type="radio" class="btn-check" name="sugar_level" id="sugar50" value="50">
                    <label class="btn btn-outline-secondary sugar-btn" for="sugar50">50%</label>
                    
                    <input type="radio" class="btn-check" name="sugar_level" id="sugar75" value="75">
                    <label class="btn btn-outline-secondary sugar-btn" for="sugar75">75%</label>
                    
                    <input type="radio" class="btn-check" name="sugar_level" id="sugar100" value="100" checked>
                    <label class="btn btn-outline-secondary sugar-btn" for="sugar100">100%</label>
                </div>
            </div>
            
            <!-- Add-ons -->
            <div class="mb-3">
                <label class="form-label">Add-ons:</label>
                <div class="addon-list">
                    <?php foreach ($addons as $addon): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="addons[]" 
                               value="<?php echo $addon['id']; ?>" id="addon<?php echo $addon['id']; ?>">
                        <label class="form-check-label" for="addon<?php echo $addon['id']; ?>">
                            <?php echo htmlspecialchars($addon['name']); ?> 
                            (+₱<?php echo number_format($addon['price'], 2); ?>)
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Buttons -->
            <div class="d-grid gap-2">
                <button type="submit" name="confirm" class="btn btn-primary">Confirm</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add active class styling for sugar level buttons
        document.addEventListener('DOMContentLoaded', function() {
            const sugarButtons = document.querySelectorAll('.sugar-btn');
            sugarButtons.forEach(button => {
                button.addEventListener('click', function() {
                    sugarButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // Initialize with 100% sugar selected
            document.querySelector('label[for="sugar100"]').classList.add('active');
        });
    </script>
</body>
</html>