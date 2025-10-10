<?php
session_start();

date_default_timezone_set('Asia/Manila');
// Check if user is logged in and is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cafe_pos");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

require_once 'refresh_menu.php';
// Initialize message variable
$message = '';

// Handle archive/restore operations
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Archive item
        if (isset($_POST['archive_item'])) {
            $id = (int)$_POST['id'];
            $sql = "UPDATE products SET archived = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Item moved to archive successfully.";
            } else {
                throw new Exception("Error archiving item.");
            }
        }

        // Restore item
        if (isset($_POST['restore_item'])) {
            $id = (int)$_POST['id'];
            $sql = "UPDATE products SET archived = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Item restored successfully.";
            } else {
                throw new Exception("Error restoring item.");
            }
        }

        // Archive category
        if (isset($_POST['archive_category'])) {
            $id = (int)$_POST['category_id'];
            
            // Check if category has active products
            $check = $conn->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id AND archived = 0");
            $result = $check->fetch_assoc();
            
            if ($result['count'] > 0) {
                throw new Exception("Cannot archive category with active products. Please archive all products in this category first.");
            }

            $sql = "UPDATE categories SET archived = 1 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Category moved to archive successfully.";
            } else {
                throw new Exception("Error archiving category.");
            }
        }

        // Restore category
        if (isset($_POST['restore_category'])) {
            $id = (int)$_POST['category_id'];
            $sql = "UPDATE categories SET archived = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $message = "Category restored successfully.";
            } else {
                throw new Exception("Error restoring category.");
            }
        }

        // Edit item
if (isset($_POST['edit_item'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $code = $conn->real_escape_string($_POST['code']);
    $category_id = (int)$_POST['category_id'];
    
    // Get category classification
    $cat_query = "SELECT classification FROM categories WHERE id = ?";
    $stmt = $conn->prepare($cat_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $cat_result = $stmt->get_result();
    $category = $cat_result->fetch_assoc();
    $classification = $category['classification'];
    
    $price_hot = !empty($_POST['price_hot']) ? (float)$_POST['price_hot'] : null;
    $price_medium = !empty($_POST['price_medium']) ? (float)$_POST['price_medium'] : null;
    $price_large = !empty($_POST['price_large']) ? (float)$_POST['price_large'] : null;
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;

    // Check if code exists for other items
    $check = $conn->query("SELECT id FROM products WHERE code = '$code' AND id != $id AND archived = 0");
    if ($check->num_rows > 0) {
        $message = "Product code already exists!";
    } else {
        $sql = "UPDATE products 
                SET name = ?, 
                    code = ?, 
                    category_id = ?, 
                    classification = ?, 
                    price_hot = ?, 
                    price_medium = ?, 
                    price_large = ?, 
                    price = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissdddi", $name, $code, $category_id, $classification, $price_hot, $price_medium, $price_large, $price, $id);
        
        if ($stmt->execute()) {
            $message = "Item updated successfully!";
            clearMenuCache();
        } else {
            $message = "Error updating item!";
        }
    }
}
        // Edit category
        if (isset($_POST['edit_category'])) {
            $category_id = (int)$_POST['category_id'];
            $category_name = $conn->real_escape_string($_POST['category_name']);
            $classification = $conn->real_escape_string($_POST['category_classification']);
            
            $sql = "UPDATE categories SET name = ?, classification = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $category_name, $classification, $category_id);
            
            if ($stmt->execute()) {
                $message = "Category updated successfully!";
            } else {
                $message = "Error updating category!";
            }
        }

        // Add new category
        if (isset($_POST['add_category'])) {
    $category_name = $conn->real_escape_string($_POST['category_name']);
    $classification = $conn->real_escape_string($_POST['category_classification']);
    
    $sql = "INSERT INTO categories (name, classification) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $category_name, $classification);
    
    if ($stmt->execute()) {
        $message = "Category added successfully!";
    } else {
        $message = "Error adding category!";
    }
}

        // Add new item
if (isset($_POST['add_item'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $code = $conn->real_escape_string($_POST['code']);
    $category_id = (int)$_POST['category_id'];
    
    // Get category classification
    $cat_query = "SELECT classification FROM categories WHERE id = ?";
    $stmt = $conn->prepare($cat_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $cat_result = $stmt->get_result();
    $category = $cat_result->fetch_assoc();
    $classification = $category['classification'];
    
    $price_hot = !empty($_POST['price_hot']) ? (float)$_POST['price_hot'] : null;
    $price_medium = !empty($_POST['price_medium']) ? (float)$_POST['price_medium'] : null;
    $price_large = !empty($_POST['price_large']) ? (float)$_POST['price_large'] : null;
    $price = !empty($_POST['price']) ? (float)$_POST['price'] : null;

    // Check if code exists
    $check = $conn->query("SELECT id FROM products WHERE code = '$code' AND archived = 0");
    if ($check->num_rows > 0) {
        $message = "Product code already exists!";
    } else {
        $sql = "INSERT INTO products (name, code, category_id, classification, price_hot, price_medium, price_large, price) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissddd", $name, $code, $category_id, $classification, $price_hot, $price_medium, $price_large, $price);
        
        if ($stmt->execute()) {
            $message = "Item added successfully!";
            clearMenuCache();
        } else {
            $message = "Error adding item!";
        }
    }
}
        // In your POST handling section for adding/editing items
if (isset($_POST['add_item']) || isset($_POST['edit_item'])) {
    
}

    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}

// Get display mode
$show_archived = isset($_GET['show_archived']) && $_GET['show_archived'] == 1;

// Fetch categories based on archive status
$archived_clause = $show_archived ? "archived = 1" : "archived = 0";
$categories = $conn->query("SELECT * FROM categories WHERE $archived_clause ORDER BY name");

// Fetch products with category names based on archive status
$products = $conn->query("SELECT p.*, c.name as category_name, c.classification as category_classification 
                         FROM products p 
                         LEFT JOIN categories c ON p.category_id = c.id 
                         WHERE p.$archived_clause 
                         ORDER BY c.name, p.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_archived ? 'Archived Items' : 'Settings'; ?> - Café POS System</title>
    <link rel="stylesheet" href="css/settings.css">
    <style>.add-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        text-decoration: none;
        display: inline-block;
        transition: background-color 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .add-btn:hover {
        background-color: #45a049;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
</style>
</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'Cannot') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="header">
            <h1><?php echo $show_archived ? 'Archived Items' : 'Menu Settings'; ?></h1>
            <div>
                <div class="user-info">
                    <span>Current User: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div id="current-datetime" class="datetime">
                        Loading time...
                    </div>
                    <div class="datetime-label">
                        Philippine Time (UTC+8)
                    </div>
                </div>
                <div class="header-buttons">
                    <a href="?<?php echo $show_archived ? '' : 'show_archived=1'; ?>" class="toggle-archived">
                        <?php echo $show_archived ? 'Back to Active Items' : 'View Archived Items'; ?>
                    </a>
                    <a href="settings.php" class="back-button">Back to Settings</a>
                    <a href="index.php" class="back-button">Back to POS</a>
                    <a href="logout.php" class="logout-button">Logout</a>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="section">
            <div class="section-header">
                <h2><?php echo $show_archived ? 'Archived Categories' : 'Categories'; ?></h2>
                <?php if (!$show_archived): ?>
                    <button class="add-btn" onclick="document.getElementById('addCategoryModal').style.display='block'">Add New Category</button>
                <?php endif; ?>
            </div>
            <table class="category-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Classification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($category = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                        <td><?php echo ucfirst(htmlspecialchars($category['classification'] ?? 'N/A')); ?></td>
                        <td>
                            <?php if ($show_archived): ?>
                                <button class="restore-btn" onclick="confirmRestore('category', <?php echo $category['id']; ?>)">
                                    Restore
                                </button>
                            <?php else: ?>
                                <button class="edit-btn" onclick="showEditCategoryModal(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                    Edit
                                </button>
                                <button class="archive-btn" onclick="confirmArchive('category', <?php echo $category['id']; ?>)">
                                    Archive
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Menu Items Section -->
        <div class="section">
        <div class="section-header">
            <h2><?php echo $show_archived ? 'Archived Menu Items' : 'Menu Items'; ?></h2>
            <?php if (!$show_archived): ?>
                <button class="add-btn" onclick="document.getElementById('addItemModal').style.display='block'">Add New Item</button>
            <?php endif; ?>
        </div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price (Medium)</th>
                        <th>Price (Large)</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($item = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo $item['price_medium'] ? '₱'.number_format($item['price_medium'], 2) : '-'; ?></td>
                        <td><?php echo $item['price_large'] ? '₱'.number_format($item['price_large'], 2) : '-'; ?></td>
                        <td><?php echo $item['price'] ? '₱'.number_format($item['price'], 2) : '-'; ?></td>
                        <td>
                        <?php if ($show_archived): ?>
                            <button class="restore-btn" onclick="confirmRestore('item', <?php echo $item['id']; ?>)">
                                Restore
                            </button>
                        <?php else: ?>
                            <button class="edit-btn" onclick='showEditItemModal(<?php echo json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                            Edit
                        </button>
                            <button class="archive-btn" onclick="confirmArchive('item', <?php echo $item['id']; ?>)">
                                Archive
                            </button>
                        <?php endif; ?>
                    </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
                <!-- Edit Item Modal -->
       <!-- Edit Item Modal -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editItemModal')">&times;</span>
        <h2>Edit Item</h2>
        <form method="POST">
            <input type="hidden" name="id" id="edit_item_id">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" id="edit_item_name" required>
            </div>
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" id="edit_item_code" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" id="edit_item_category" required onchange="updatePriceFieldsVisibility(this, 'editItemModal')">
                    <?php
                    $active_categories = $conn->query("SELECT * FROM categories WHERE archived = 0 ORDER BY name");
                    while($cat = $active_categories->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                data-classification="<?php echo htmlspecialchars($cat['classification']); ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- Dynamic price fields -->
            <div class="form-group drinks-prices" style="display: none;">
                <label>Hot Price:</label>
                <input type="number" step="0.01" name="price_hot" id="edit_item_price_hot" class="form-control">
                
                <label>Medium Price:</label>
                <input type="number" step="0.01" name="price_medium" id="edit_item_price_medium" class="form-control">
                
                <label>Large Price:</label>
                <input type="number" step="0.01" name="price_large" id="edit_item_price_large" class="form-control">
            </div>
            <div class="form-group food-price">
                <label>Price:</label>
                <input type="number" step="0.01" name="price" id="edit_item_price" class="form-control">
            </div>
            <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>
                <!-- Edit Category Modal -->
       <div id="editCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('editCategoryModal')">&times;</span>
                <h2>Edit Category</h2>
                <form method="POST">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="category_name" id="edit_category_name" required>
                    </div>
                    <div class="form-group">
                        <label>Classification:</label>
                        <select name="category_classification" id="edit_category_classification" required>
                            <option value="">Select Classification</option>
                            <option value="food">Food</option>
                            <option value="drinks">Drinks</option>
                        </select>
                    </div>
                    <button type="submit" name="edit_category" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Add New Category Modal -->
        <div id="addCategoryModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('addCategoryModal')">&times;</span>
                <h2>Add New Category</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="category_name" required>
                    </div>
                    <div class="form-group">
                        <label>Classification:</label>
                        <select name="category_classification" required>
                            <option value="">Select Classification</option>
                            <option value="food">Food</option>
                            <option value="drinks">Drinks</option>
                        </select>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                </form>
            </div>
        </div>

        <!-- Add New Item Modal -->
<div id="addItemModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addItemModal')">&times;</span>
        <h2>Add New Item</h2>
        <form method="POST">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" id="add_item_category" required onchange="updatePriceFieldsVisibility(this, 'addItemModal')">
                    <?php
                    $active_categories = $conn->query("SELECT * FROM categories WHERE archived = 0 ORDER BY name");
                    while($cat = $active_categories->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                data-classification="<?php echo htmlspecialchars($cat['classification']); ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <!-- Dynamic price fields -->
            <div class="form-group drinks-prices" style="display: none;">
                <label>Hot Price:</label>
                <input type="number" step="0.01" name="price_hot" class="form-control">
                
                <label>Medium Price:</label>
                <input type="number" step="0.01" name="price_medium" class="form-control">
                
                <label>Large Price:</label>
                <input type="number" step="0.01" name="price_large" class="form-control">
            </div>
            <div class="form-group food-price">
                <label>Price:</label>
                <input type="number" step="0.01" name="price" class="form-control">
            </div>
            <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
        </form>
    </div>
</div>
    </div>
    

    <script>
        // Function to confirm and handle archive operations
        function confirmArchive(type, id) {
            const message = type === 'category' 
                ? 'Are you sure you want to archive this category?' 
                : 'Are you sure you want to archive this item?';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="${type === 'category' ? 'category_id' : 'id'}" value="${id}">
                    <input type="hidden" name="archive_${type}" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Function to confirm and handle restore operations
        function confirmRestore(type, id) {
            const message = type === 'category' 
                ? 'Are you sure you want to restore this category?' 
                : 'Are you sure you want to restore this item?';
                
            if (confirm(message)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="${type === 'category' ? 'category_id' : 'id'}" value="${id}">
                    <input type="hidden" name="restore_${type}" value="1">
                    <input type="hidden" name="show_archived" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-hide messages after 3 seconds
        setTimeout(function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'none';
            }
        }, 3000);

        function showEditItemModal(item) {
    console.log('Item data:', item); // For debugging

    // Set basic item details
    document.getElementById('edit_item_id').value = item.id;
    document.getElementById('edit_item_name').value = item.name;
    document.getElementById('edit_item_code').value = item.code;
    
    // Set category and update price fields visibility
    const categorySelect = document.getElementById('edit_item_category');
    categorySelect.value = item.category_id;
    
    // Set prices
    document.getElementById('edit_item_price_hot').value = item.price_hot || '';
    document.getElementById('edit_item_price_medium').value = item.price_medium || '';
    document.getElementById('edit_item_price_large').value = item.price_large || '';
    document.getElementById('edit_item_price').value = item.price || '';
    
    // Get the selected category's classification
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const classification = selectedOption.getAttribute('data-classification');
    
    // Show/hide price fields based on category classification
    const drinksPrices = document.getElementById('editItemModal').querySelector('.drinks-prices');
    const foodPrice = document.getElementById('editItemModal').querySelector('.food-price');
    
    if (classification === 'drinks') {
        drinksPrices.style.display = 'block';
        foodPrice.style.display = 'none';
    } else {
        drinksPrices.style.display = 'none';
        foodPrice.style.display = 'block';
    }
    
    // Display the modal
    document.getElementById('editItemModal').style.display = 'block';
}

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }

        function updateDateTime() {
            const now = new Date();
            // Add 8 hours for Philippines time
            const phTime = new Date(now.getTime() + (8 * 60 * 60 * 1000));
            
            const year = phTime.getUTCFullYear();
            const month = String(phTime.getUTCMonth() + 1).padStart(2, '0');
            const day = String(phTime.getUTCDate()).padStart(2, '0');
            const hours = String(phTime.getUTCHours()).padStart(2, '0');
            const minutes = String(phTime.getUTCMinutes()).padStart(2, '0');
            const seconds = String(phTime.getUTCSeconds()).padStart(2, '0');
            
            const formattedDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            document.getElementById('current-datetime').textContent = formattedDateTime;
        }

        // Update time immediately and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        function showEditCategoryModal(category) {
            document.getElementById('edit_category_id').value = category.id;
            document.getElementById('edit_category_name').value = category.name;
            document.getElementById('edit_category_classification').value = category.classification || '';
            document.getElementById('editCategoryModal').style.display = 'block';
        }

        function clearMenuCache() {
            if (isset($_SESSION['menu_items'])) {
                unset($_SESSION['menu_items']);
            }
        }

        // Add this to your JavaScript section
function updatePriceFields() {
    const category = document.querySelector('select[name="category_id"]').value;
    const mediumPrice = document.querySelector('.price-medium');
    const largePrice = document.querySelector('.price-large');
    const regularPrice = document.querySelector('.price-regular');
    
    // Show/hide price fields based on category
    if (category) {
        mediumPrice.style.display = 'none';
        largePrice.style.display = 'none';
        regularPrice.style.display = 'block';
    }
}
function updatePriceFieldsVisibility(select, modalId) {
    const selectedOption = select.options[select.selectedIndex];
    const classification = selectedOption.getAttribute('data-classification');
    const modal = document.getElementById(modalId);
    const drinksPrices = modal.querySelector('.drinks-prices');
    const foodPrice = modal.querySelector('.food-price');
    
    if (classification === 'drinks') {
        drinksPrices.style.display = 'block';
        foodPrice.style.display = 'none';
    } else {
        drinksPrices.style.display = 'none';
        foodPrice.style.display = 'block';
    }
}
// Add this to your form
document.querySelector('select[name="category_id"]').addEventListener('change', updatePriceFields);
// Add this to your existing script section
document.addEventListener('DOMContentLoaded', function() {
    // For Add Item form
    const addClassificationSelect = document.querySelector('#addItemModal select[name="classification"]');
    if (addClassificationSelect) {
        addClassificationSelect.addEventListener('change', function() {
            const drinksPrices = document.querySelector('#addItemModal .drinks-prices');
            const foodPrice = document.querySelector('#addItemModal .food-price');
            if (this.value === 'drinks') {
                drinksPrices.style.display = 'block';
                foodPrice.style.display = 'none';
            } else {
                drinksPrices.style.display = 'none';
                foodPrice.style.display = 'block';
            }
        });
    }

    // For Edit Item form
    const editClassificationSelect = document.querySelector('#editItemModal select[name="classification"]');
    if (editClassificationSelect) {
        editClassificationSelect.addEventListener('change', function() {
            const drinksPrices = document.querySelector('#editItemModal .drinks-prices');
            const foodPrice = document.querySelector('#editItemModal .food-price');
            if (this.value === 'drinks') {
                drinksPrices.style.display = 'block';
                foodPrice.style.display = 'none';
            } else {
                drinksPrices.style.display = 'none';
                foodPrice.style.display = 'block';
            }
        });
    }
});


    </script>
</body>
</html>