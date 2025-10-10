<?php
ob_start();
session_start();
date_default_timezone_set('Asia/Manila');
unset($_SESSION['menu_items']); // Force refresh menu items
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'refresh_menu.php';

// Check if menu items exist in session, if not fetch them
if (!isset($_SESSION['menu_items'])) {
    $_SESSION['menu_items'] = refreshMenuItems($conn);
}

$menuItems = $_SESSION['menu_items'];

// Fetch all active categories
$categories_query = "SELECT DISTINCT c.name 
                    FROM categories c 
                    JOIN products p ON c.id = p.category_id 
                    WHERE c.archived = 0 AND p.archived = 0 
                    ORDER BY 
                        CASE c.name 
                            WHEN 'coffee' THEN 1
                            WHEN 'breakfast' THEN 2
                            WHEN 'addons' THEN 3
                            ELSE 4 
                        END";
$categories_result = $conn->query($categories_query);
$active_categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $active_categories[] = strtolower($row['name']);
}

// Remove the second while loop since we're already using $_SESSION['menu_items']

// Debug output - remove this after testing
echo "<!-- Debug: Menu Items -->";
echo "<!-- " . print_r($menuItems, true) . " -->";
// Handle add-ons
// Handle add-ons
// Handle add-ons
if (isset($_POST['add_addon'])) {
    $addonId = $_POST['addon_id'] ?? '';
    $coffeeCode = $_POST['coffee_code'] ?? '';
    
    // Find the addon item
    $addon = array_filter($menuItems, function($item) use ($addonId) {
        return ($item['id'] ?? '') == $addonId && ($item['category'] ?? '') === 'addons';
    });
    $addon = reset($addon);
    
    if ($addon && isset($addon['name']) && isset($addon['price'])) {
        // Find the coffee in the cart
        foreach ($_SESSION['cart'] as &$cartItem) {
            if (($cartItem['code'] ?? '') === $coffeeCode && ($cartItem['category'] ?? '') === 'coffee') {
                // Add addon name to coffee name
                $cartItem['name'] .= ' + ' . $addon['name'];
                // Add addon price to coffee price
                $cartItem['price'] += $addon['price'];
                break;
            }
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['category']) ? '?category=' . $_GET['category'] : ''));
    exit;
}

// Handle form submissions
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $needsRedirect = false; // Flag to track if we need to redirect

    // Handle add to cart
    if (isset($_POST['add_to_cart'])) {
        $itemId = $_POST['item_id'];
        $size = isset($_POST['size']) ? $_POST['size'] : null;
        
        $item = array_filter($menuItems, function($item) use ($itemId) {
            return $item['id'] == $itemId;
        });
        $item = reset($item);
        
        if ($item) {
            // Initialize price
            $price = 0;
            
            // Determine price based on size for drinks
            if ($item['classification'] === 'drinks' && $size) {
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
            } else {
                $price = floatval($item['price']);
            }

            // Build the item name with customizations
            $name = $item['name'];
            if ($size) {
                $name .= " ($size)";
            }
            if (isset($_POST['sugar_level'])) {
                $name .= " {$_POST['sugar_level']}% sugar";
            }

            $cartItem = [
                'id' => $item['id'],
                'name' => isset($_POST['name']) ? $_POST['name'] : $name,
                'price' => $price,
                'quantity' => 1,
                'category' => $item['category'],
                'code' => $item['code'] . ($size ? strtoupper($size[0]) : '')
            ];

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
            
            $needsRedirect = true;
        }
    }
    
    // Handle update quantity
    if (isset($_POST['update_quantity'])) {
        $index = $_POST['index'];
        $delta = intval($_POST['delta']);
        
        if (isset($_SESSION['cart'][$index])) {
            $newQuantity = $_SESSION['cart'][$index]['quantity'] + $delta;
            if ($newQuantity > 0) {
                $_SESSION['cart'][$index]['quantity'] = $newQuantity;
            } else {
                array_splice($_SESSION['cart'], $index, 1);
            }
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
        
        $needsRedirect = true;
    }
    
    // Handle clear cart
    if (isset($_POST['clear_cart'])) {
        $_SESSION['cart'] = array();
        $needsRedirect = true;
    }
    
    // Handle add addon
    if (isset($_POST['add_addon'])) {
        $addonId = $_POST['addon_id'] ?? '';
        $coffeeCode = $_POST['coffee_code'] ?? '';
        
        $addon = array_filter($menuItems, function($item) use ($addonId) {
            return ($item['id'] ?? '') == $addonId && ($item['category'] ?? '') === 'addons';
        });
        $addon = reset($addon);
        
        if ($addon && isset($addon['name']) && isset($addon['price'])) {
            foreach ($_SESSION['cart'] as &$cartItem) {
                if (($cartItem['code'] ?? '') === $coffeeCode && ($cartItem['category'] ?? '') === 'coffee') {
                    $cartItem['name'] .= ' + ' . $addon['name'];
                    $cartItem['price'] += $addon['price'];
                    break;
                }
            }
        }
        
        $needsRedirect = true;
    }
    
    // Single redirect at the end if needed
    if ($needsRedirect) {
        header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['category']) ? '?category=' . $_GET['category'] : ''));
        exit();
    }
}
// Filter items based on category
$category = isset($_GET['category']) ? $_GET['category'] : null;
$searchTerm = isset($_GET['search']) ? strtolower($_GET['search']) : '';

$filteredItems = array_filter($menuItems, function($item) use ($category, $searchTerm) {
    $matchesCategory = !$category || strtolower($item['category_name']) === $category;
    $matchesSearch = !$searchTerm || 
                    stripos($item['name'], $searchTerm) !== false || 
                    strtolower($item['code'] ?? '') === $searchTerm;
    return $matchesCategory && $matchesSearch;
});

// Calculate total
// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $price = is_array($item['price']) ? 0 : floatval($item['price']); // Handle array prices
    $quantity = intval($item['quantity'] ?? 1);
    $total += $price * $quantity;
}

$addonsQuery = "SELECT * FROM products WHERE category_id = 3 AND archived = 0";
$addonsResult = $conn->query($addonsQuery);
$addons = $addonsResult->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Café POS System</title>
    <link rel="stylesheet" href="css/style.css">
<style>
body {
    font-family: 'Arial', sans-serif;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    margin: 0;
    padding: 20px 320px 20px 20px; /* Add right padding to make space for cart */
}

.container {
    width: 100%; /* Use full width of the available space */
    padding: 20px;
    background-color: transparent;
    border-radius: 10px;
}

.menu {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); /* Even smaller grid items */
    gap: 15px;
    margin: 20px 0;
    padding: 15px;
    width: 100%;
}

.item {
    background-color: rgba(249, 249, 249, 0.9);
    border: 1px solid rgba(221, 221, 221, 0.8);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.cart {
    position: fixed;
    right: 0;
    top: 0;
    width: 300px;
    height: 100vh;
    background: white;
    padding: 20px;
    box-shadow: -2px 0 10px rgba(0,0,0,0.1);
    overflow-y: auto;
    z-index: 1000;
}

/* Responsive design */
@media (max-width: 900px) {
    body {
        padding: 20px; /* Remove right padding on mobile */
    }

    .container {
        width: 100%;
        margin-bottom: 300px; /* Space for cart at bottom */
    }

    .cart {
        width: 100%;
        height: auto;
        max-height: 300px;
        position: fixed;
        bottom: 0;
        top: auto;
        right: 0;
    }

    .menu {
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 10px;
    }
}
        .top-buttons {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .top-buttons a {
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
            transition: background-color 0.3s;
        }

        .settings-button {
            background-color: #6c5ce7;
            color: white;
        }

        .settings-button:hover {
            background-color: #5a4dcc;
        }

        .history-button {
            background-color: #00b894;
            color: white;
        }

        .history-button:hover {
            background-color: #00a187;
        }

        .logout-button {
            background-color: #e74c3c;
            color: white;
        }

        .logout-button:hover {
            background-color: #c0392b;
        }
        .container {
    width: calc(100% - 340px); 
    padding: 20px;
    background-color: transparent; 
    border-radius: 10px;
}
.header h1 {
    color:rgb(0, 0, 0);
    font-size: 2em;
    margin: 0;
}
.customize-drink {
    background-color: #6c5ce7;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    margin-top: 10px;
}

.customize-drink:hover {
    background-color: #5a4dcc;
}

.modal-content {
    padding: 20px;
}

.btn-group {
    width: 100%;
    margin-bottom: 15px;
}

.btn-check + .btn {
    flex: 1;
}

.form-check {
    margin: 10px 0;
}

#addonsContainer {
    max-height: 200px;
    overflow-y: auto;
}

.total-price {
    font-size: 1.2em;
    font-weight: bold;
    margin: 15px 0;
}
.modal-content {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 0.375rem 0.75rem;
}

.btn-outline-primary {
    border-color: #007bff;
    color: #007bff;
}

.btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
}
/* Consistent button styling */
.customize-drink,
button[name="add_to_cart"] {
    display: block;
    width: 100%;
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    text-align: center;
    font-size: 14px;
    margin-top: auto;
    height: 35px;      /* Fixed height for all buttons */
    line-height: 18px; /* Consistent line height */
}

.customize-drink {
    background-color: #6c5ce7;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.customize-drink:hover {
    background-color: #5a4dcc;
    color: white;
    text-decoration: none;
}

button[name="add_to_cart"] {
    background-color: #00b894;
    color: white;
}

button[name="add_to_cart"]:hover {
    background-color: #00a187;
}

/* Ensure consistent item height */
.item {
    background-color: rgba(249, 249, 249, 0.9);
    border: 1px solid rgba(221, 221, 221, 0.8);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120px;
    height: 150px;  /* Fixed height for items */
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.item h3 {
    font-size: 0.9em;
    margin-bottom: 8px;
    word-wrap: break-word;
    line-height: 1.2;
    max-height: 2.4em;
    overflow: hidden;
}

.item p {
    margin: 8px 0;
    font-size: 0.85em;
    line-height: 1.2;
}

</style>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="top-buttons">
    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
        <a href="settings.php" class="settings-button">Settings</a>
        <a href="history.php" class="history-button">History</a>
    <?php endif; ?>
    <a href="logout.php" class="logout-button">Logout</a>
</div>
    <div class="container">
        <div class="header">
            <h1>Café POS System</h1>
        </div>
        
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search for items or type code names..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>">
                <input type="submit" value="Search">
            </form>
        </div>
        
        <div class="filter-buttons">
    <a href="?"><button>Show All</button></a>
    <?php foreach ($active_categories as $cat): ?>
        <a href="?category=<?php echo urlencode($cat); ?>">
            <button><?php echo ucfirst($cat); ?></button>
        </a>
    <?php endforeach; ?>
</div>
        
<div class="menu">
    <?php foreach ($filteredItems as $item): ?>
        <!-- Add this debug line -->
        <?php echo "<!-- Debug: Item: {$item['name']}, Classification: {$item['classification']} -->"; ?>
        <div class="item">
    <h3><?php echo htmlspecialchars($item['name'] ?? ''); ?> (<?php echo htmlspecialchars($item['code'] ?? ''); ?>)</h3>
    <?php if (isset($item['classification']) && strtolower(trim($item['classification'])) === 'drinks'): ?>
        <a href="drink_customization.php?item_id=<?php echo $item['id']; ?>" class="customize-drink">
            Add to Cart
        </a>
    <?php else: ?>
        <p>Price: ₱<?php echo number_format(floatval($item['price']), 2); ?></p>
        <form method="POST">
            <input type="hidden" name="item_id" value="<?php echo $item['id'] ?? ''; ?>">
            <button type="submit" name="add_to_cart">Add to Cart</button>
        </form>
    <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
    
<div class="cart">
    <h2>Cart</h2>
    <?php if (empty($_SESSION['cart'])): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
            <div class="cart-item">
                <span>
                    <?php echo htmlspecialchars($item['name'] ?? ''); ?> - 
                    ₱<?php echo number_format($item['price'] ?? 0, 2); ?> x 
                    <?php echo $item['quantity'] ?? 1; ?>
                </span>
                <div class="quantity-control">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="hidden" name="delta" value="-1">
                        <button type="submit" name="update_quantity">-</button>
                    </form>
                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <input type="hidden" name="delta" value="1">
                        <button type="submit" name="update_quantity">+</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <p class="total"><strong>Total: </strong>₱<?php echo number_format($total, 2); ?></p>
    
    <div class="remove-all">
    <form method="POST">
        <button type="submit" name="clear_cart" class="btn btn-danger">Remove All</button>
    </form>
</div>
    
    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="checkout">
            <form action="checkout.php" method="POST">
                <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                    <input type="hidden" name="cart[<?php echo $index; ?>][name]" value="<?php echo htmlspecialchars($item['name']); ?>">
                    <input type="hidden" name="cart[<?php echo $index; ?>][price]" value="<?php echo $item['price']; ?>">
                    <input type="hidden" name="cart[<?php echo $index; ?>][quantity]" value="<?php echo $item['quantity']; ?>">
                <?php endforeach; ?>
                <input type="hidden" name="total" value="<?php echo $total; ?>">
                <button type="submit">Checkout</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Drink Selection Modal -->
<!-- Drink Selection Modal -->
<div class="modal fade" id="drinkModal" tabindex="-1" aria-labelledby="drinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="drinkModalLabel">Customize Your Drink</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="drinkCustomizationForm" method="POST">
                    <input type="hidden" id="drinkId" name="item_id">
                    <input type="hidden" id="drinkName" name="name">
                    
                    <!-- Size Selection -->
                    <div class="form-group mb-3">
                        <label class="form-label">Size:</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="size" id="mediumSize" value="medium" required>
                            <label class="btn btn-outline-primary" for="mediumSize">Medium</label>
                            
                            <input type="radio" class="btn-check" name="size" id="largeSize" value="large">
                            <label class="btn btn-outline-primary" for="largeSize">Large</label>
                        </div>
                    </div>
                    
                    <!-- Sugar Level -->
                    <div class="form-group mb-3">
                        <label class="form-label">Sugar Level:</label>
                        <input type="number" class="form-control" name="sugar_level" min="0" max="100" value="100" required>
                        <small class="form-text text-muted">Enter sugar level (0-100%)</small>
                    </div>
                    
                    <!-- Add-ons -->
                    <div class="form-group mb-3">
                        <label class="form-label">Add-ons:</label>
                        <div id="addonsContainer" class="form-group">
                            <!-- Add-ons will be populated dynamically -->
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="add_to_cart">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
<script>
    let currentDrinkPrices = {};
let selectedAddons = [];

function openDrinkModal(product) {
    currentDrinkPrices = {
        hot: parseFloat(product.price_hot),
        medium: parseFloat(product.price_medium),
        large: parseFloat(product.price_large)
    };
    
    $('#drinkId').val(product.id);
    $('#drinkName').val(product.name);
    $('#drinkModalLabel').text(product.name);
    
    // Reset form
    $('#drinkCustomizationForm')[0].reset();
    updateTotalPrice();
    
    // Load add-ons
    loadAddons();
    
    $('#drinkModal').modal('show');
}

function loadAddons() {
    $.ajax({
        url: 'get_addons.php',
        method: 'GET',
        success: function(addons) {
            let html = '';
            addons.forEach(addon => {
                html += `
                    <div class="form-check">
                        <input class="form-check-input addon-checkbox" type="checkbox" 
                               value="${addon.id}" data-price="${addon.price}" 
                               id="addon${addon.id}">
                        <label class="form-check-label" for="addon${addon.id}">
                            ${addon.name} (+₱${addon.price.toFixed(2)})
                        </label>
                    </div>
                `;
            });
            $('#addonsContainer').html(html);
            
            // Add change event listeners
            $('.addon-checkbox').change(updateTotalPrice);
        }
    });
}

function updateTotalPrice() {
    const size = $('input[name="size"]:checked').val();
    let total = currentDrinkPrices[size];
    
    // Add addon prices
    $('.addon-checkbox:checked').each(function() {
        total += parseFloat($(this).data('price'));
    });
    
    $('#totalPrice').text(total.toFixed(2));
}

$('#addToCartBtn').click(function() {
    const productId = $('#drinkId').val();
    const productName = $('#drinkName').val();
    const size = $('input[name="size"]:checked').val();
    const sugarLevel = $('#sugarLevel').val();
    let price = currentDrinkPrices[size];
    
    let selectedAddons = [];
    $('.addon-checkbox:checked').each(function() {
        selectedAddons.push({
            id: $(this).val(),
            name: $(this).closest('label').text().split('(')[0].trim(),
            price: parseFloat($(this).data('price'))
        });
        price += parseFloat($(this).data('price'));
    });
    
    // Create customized name
    let customizedName = `${productName} (${size}, ${sugarLevel}% sugar`;
    if (selectedAddons.length > 0) {
        customizedName += `, with ${selectedAddons.map(a => a.name).join(', ')}`;
    }
    customizedName += ')';
    
    // Add to cart with customizations
    addToCart(productId, customizedName, price, 1, {
        size: size,
        sugarLevel: sugarLevel,
        addons: selectedAddons
    });
    
    $('#drinkModal').modal('hide');
});
// Update addToCart function
function addToCart(productId, productName, price, quantity, customizations) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const addToCartInput = document.createElement('input');
    addToCartInput.type = 'hidden';
    addToCartInput.name = 'add_to_cart';
    addToCartInput.value = '1';
    form.appendChild(addToCartInput);

    const itemIdInput = document.createElement('input');
    itemIdInput.type = 'hidden';
    itemIdInput.name = 'item_id';
    itemIdInput.value = productId;
    form.appendChild(itemIdInput);

    const nameInput = document.createElement('input');
    nameInput.type = 'hidden';
    nameInput.name = 'name';
    nameInput.value = productName;
    form.appendChild(nameInput);

    const priceInput = document.createElement('input');
    priceInput.type = 'hidden';
    priceInput.name = 'price';
    priceInput.value = price;
    form.appendChild(priceInput);

    if (customizations) {
        Object.entries(customizations).forEach(([key, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = typeof value === 'object' ? JSON.stringify(value) : value;
            form.appendChild(input);
        });
    }

    document.body.appendChild(form);
    form.submit();
}

// Add event listeners for price updates
$('input[name="size"]').change(updateTotalPrice);
function addToCart(productId, productName, price, quantity, customizations) {
    // Create form data
    const formData = new FormData();
    formData.append('add_to_cart', '1');
    formData.append('item_id', productId);
    formData.append('name', productName);
    formData.append('price', price);
    formData.append('quantity', quantity);
    if (customizations.size) formData.append('size', customizations.size);
    if (customizations.sugarLevel) formData.append('sugar_level', customizations.sugarLevel);
    if (customizations.addons) formData.append('addons', JSON.stringify(customizations.addons));

    // Send POST request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(() => {
        // Reload page after successful addition
        window.location.reload();
    }).catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}
function restoreCartState() {
    const savedCart = localStorage.getItem('savedCart');
    if (savedCart) {
        const cartState = JSON.parse(savedCart);
        // Restore cart items
        cartData = cartState.cart;
        totalAmount = cartState.total;
        
        // Update the display
        updateCartDisplay();
        
        // Clear the saved cart after restoring
        localStorage.removeItem('savedCart');
    }
}

// Call this function when the page loads
document.addEventListener('DOMContentLoaded', function() {
    restoreCartState();
});

function updateCartDisplay() {
    const cartContainer = document.getElementById('cart-items');
    const totalDisplay = document.getElementById('total-amount');
    
    // Clear existing cart display
    cartContainer.innerHTML = '';
    
    // Add each item to the cart display
    Object.values(cartData).forEach(item => {
        const cartItem = document.createElement('div');
        cartItem.classList.add('cart-item');
        
        let itemHTML = `
            <div class="cart-item-details">
                <span class="item-name">${item.name}</span>
                <span class="item-quantity">x ${item.quantity}</span>
                <span class="item-price">₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</span>
            </div>
        `;
        
        if (item.customization) {
            itemHTML += `<div class="item-customization">
                Size: ${item.customization.size}, 
                Sugar Level: ${item.customization.sugarLevel}%
                ${item.customization.addons && item.customization.addons.length > 0 
                    ? `, Add-ons: ${item.customization.addons.map(addon => addon.name).join(', ')}` 
                    : ''}
            </div>`;
        }
        
        cartItem.innerHTML = itemHTML;
        cartContainer.appendChild(cartItem);
    });
    
    // Update total
    if (totalDisplay) {
        totalDisplay.textContent = `₱${totalAmount.toFixed(2)}`;
    }
}
</script>
</body>
</html>