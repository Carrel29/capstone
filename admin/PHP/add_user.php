<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'ADMIN') {
    header("Location: Index.php");
    exit();
}

// Database Configuration
$host = 'localhost';
$dbname = 'btonedatabase';
$username = 'root';
$password = '';

// Error and success message variables
$error = '';
$success = '';

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error = "Database connection error: " . $e->getMessage();
}

// Handle form submission for adding new admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    try {
        // Collect form data
        $first_name = $_POST['first_name'];
        $last_name  = $_POST['last_name'];
        $email      = $_POST['email'];
        $phone      = $_POST['phone'];
        $password   = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure hashing

        $bt_privilege_id = 1; // Always Admin

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM btuser WHERE bt_email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = "❌ Email already exists";
        } else {
            // Insert new admin
            $stmt = $pdo->prepare("
                INSERT INTO btuser 
                    (bt_first_name, bt_last_name, bt_email, bt_phone_number, bt_password_hash, bt_privilege_id)
                VALUES 
                    (:first_name, :last_name, :email, :phone, :password, :privilege_id)
            ");

            $stmt->execute([
                'first_name'   => $first_name,
                'last_name'    => $last_name,  
                'email'        => $email,
                'phone'        => $phone,
                'password'     => $password,
                'privilege_id' => $bt_privilege_id
            ]);

            $success = "✅ Admin added successfully";
        }
    } catch (PDOException $e) {
        $error = "😥 Error: " . $e->getMessage();
    }
}
// Handle edit user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    try {
        $user_id    = $_POST['user_id'];
        $first_name = $_POST['first_name'];
        $last_name  = $_POST['last_name'];
        $email      = $_POST['email'];
        $phone      = $_POST['phone'];

        // Always force Admin
        $bt_privilege_id = 1;

        // Check if password should be updated
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                UPDATE btuser 
                SET bt_first_name    = :first_name, 
                    bt_last_name     = :last_name, 
                    bt_email         = :email, 
                    bt_phone_number  = :phone,
                    bt_password_hash = :password,
                    bt_privilege_id  = :privilege_id
                WHERE bt_user_id     = :id
            ");

            $params = [
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'email'        => $email,
                'phone'        => $phone,
                'password'     => $password,
                'privilege_id' => $bt_privilege_id,
                'id'           => $user_id
            ];
        } else {
            $stmt = $pdo->prepare("
                UPDATE btuser 
                SET bt_first_name    = :first_name, 
                    bt_last_name     = :last_name, 
                    bt_email         = :email, 
                    bt_phone_number  = :phone,
                    bt_privilege_id  = :privilege_id
                WHERE bt_user_id     = :id
            ");

            $params = [
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'email'        => $email,
                'phone'        => $phone,
                'privilege_id' => $bt_privilege_id,
                'id'           => $user_id
            ];
        }
        
        $stmt->execute($params);
        $success = "✅ Admin updated successfully";
        
    } catch (PDOException $e) {
        $error = "😥 Error: " . $e->getMessage();
    }
}

// Handle archive user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'archive_user') {
    try {
        $user_id = $_POST['user_id'];
        
        // Fetch user
        $stmt = $pdo->prepare("SELECT * FROM btuser WHERE bt_user_id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Create archived_users if not exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS archived_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    original_id INT NOT NULL,
                    bt_first_name VARCHAR(50) NOT NULL,
                    bt_last_name VARCHAR(50) NOT NULL,
                    bt_email VARCHAR(100) NOT NULL,
                    bt_phone_number VARCHAR(20),
                    bt_password_hash VARCHAR(255) NOT NULL,
                    bt_privilege_id INT NOT NULL,
                    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Insert into archive
            $stmt = $pdo->prepare("
                INSERT INTO archived_users
                    (original_id, bt_first_name, bt_last_name, bt_email, bt_phone_number, bt_password_hash, bt_privilege_id)
                VALUES
                    (:original_id, :first_name, :last_name, :email, :phone, :password, :privilege_id)
            ");
            $stmt->execute([
                'original_id'  => $user['bt_user_id'],
                'first_name'   => $user['bt_first_name'],
                'last_name'    => $user['bt_last_name'],
                'email'        => $user['bt_email'],
                'phone'        => $user['bt_phone_number'],
                'password'     => $user['bt_password_hash'],
                'privilege_id' => $user['bt_privilege_id']
            ]);
            
            // Delete from btuser
            $stmt = $pdo->prepare("DELETE FROM btuser WHERE bt_user_id = :id");
            $stmt->execute(['id' => $user_id]);
            
            $success = "✅ Admin archived successfully";
        } else {
            $error = "❌ Admin not found";
        }
    } catch (PDOException $e) {
        $error = "😥 Error: " . $e->getMessage();
    }
}

// Handle restore user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'restore_user') {
    try {
        $archive_id = $_POST['archive_id'];
        
        // Fetch archived user
        $stmt = $pdo->prepare("SELECT * FROM archived_users WHERE id = :id");
        $stmt->execute(['id' => $archive_id]);
        $archived_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archived_user) {
            // Check if email already exists in btuser
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM btuser WHERE bt_email = :email");
            $stmt->execute(['email' => $archived_user['bt_email']]);
            if ($stmt->fetchColumn() > 0) {
                $error = "❌ Cannot restore admin. Email already exists.";
            } else {
                // Insert back to btuser
                $stmt = $pdo->prepare("
                    INSERT INTO btuser
                        (bt_first_name, bt_last_name, bt_email, bt_phone_number, bt_password_hash, bt_privilege_id)
                    VALUES
                        (:first_name, :last_name, :email, :phone, :password, :privilege_id)
                ");
                $stmt->execute([
                    'first_name'   => $archived_user['bt_first_name'],
                    'last_name'    => $archived_user['bt_last_name'],
                    'email'        => $archived_user['bt_email'],
                    'phone'        => $archived_user['bt_phone_number'],
                    'password'     => $archived_user['bt_password_hash'],
                    'privilege_id' => 1  // Always restore as Admin
                ]);
                
                // Delete from archive
                $stmt = $pdo->prepare("DELETE FROM archived_users WHERE id = :id");
                $stmt->execute(['id' => $archive_id]);
                
                $success = "✅ Admin restored successfully";
            }
        } else {
            $error = "❌ Archived admin not found";
        }
    } catch (PDOException $e) {
        $error = "😥 Error: " . $e->getMessage();
    }
}


// Handle permanently delete user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'permanently_delete') {
    try {
        $archive_id = $_POST['archive_id'];
        
        $stmt = $pdo->prepare("DELETE FROM archived_users WHERE id = :id");
        $stmt->execute(['id' => $archive_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "🗑️ Archived admin permanently deleted";
        } else {
            $error = "❌ Archived admin not found";
        }
    } catch (PDOException $e) {
        $error = "😥 Error: " . $e->getMessage();
    }
}

// Function to get all active admins
function getUsers($pdo) {
    $stmt = $pdo->query("
        SELECT * 
        FROM btuser 
        WHERE bt_privilege_id = 1 
        ORDER BY bt_user_id DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all archived admins
function getArchivedUsers($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * 
            FROM archived_users 
            WHERE bt_privilege_id = 1 
            ORDER BY archived_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}
$users = getUsers($pdo);
$archived_users = getArchivedUsers($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management</title>
    <link rel="stylesheet" href="../assets_css/admin.css">
    <style>
        /* Admin Management Specific Styles */
        .tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: 8px;
            padding: 5px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-radius: 6px;
            margin: 0 2px;
            transition: all 0.3s ease;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .tab.active {
            background: var(--highlight);
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .tab:hover:not(.active) {
            background: rgba(74, 107, 255, 0.1);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Styles */
        #add-user form {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        
        #add-user input {
            width: 100%;
            padding: 12px 15px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        #add-user input:focus {
            outline: none;
            border-color: var(--highlight);
            box-shadow: 0 0 0 2px rgba(74, 107, 255, 0.1);
        }
        
        #add-user button {
            background: var(--highlight);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s ease;
            margin-top: 10px;
        }
        
        #add-user button:hover {
            background: var(--highlight-dark);
        }
        
        /* Modal Enhancements */
        .modal-content {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 90%;
        }
        
        .modal-title {
            color: var(--highlight);
            margin-bottom: 20px;
            font-size: 1.5em;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--highlight);
            box-shadow: 0 0 0 2px rgba(74, 107, 255, 0.1);
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .modal-footer button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .modal-footer button[type="submit"] {
            background: var(--highlight);
            color: white;
        }
        
        .modal-footer button[type="submit"]:hover {
            background: var(--highlight-dark);
        }
        
        .modal-footer button[type="button"] {
            background: #95a5a6;
            color: white;
        }
        
        .modal-footer button[type="button"]:hover {
            background: #7f8c8d;
        }
        
        /* Button Styles */
        .history-button {
            background: var(--highlight);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background 0.3s ease;
            margin-right: 5px;
        }
        
        .history-button:hover {
            background: var(--highlight-dark);
        }
        
        .clear-archive-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .clear-archive-btn:hover {
            background: #c0392b;
        }
        
        /* Notification Styles */
        .error {
            background: #e74c3c;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(231, 76, 60, 0.3);
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(39, 174, 96, 0.3);
        }
        
        /* Table Enhancements */
        .customer-table {
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .customer-table th {
            background: var(--highlight);
            color: white;
            padding: 15px;
            font-weight: 600;
        }
        
        .customer-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .customer-table tr:hover {
            background: rgba(74, 107, 255, 0.05);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                margin: 2px 0;
                text-align: center;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .modal-footer {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
     <nav class="sidebar">
            <div class="logo">
        <h2>Admin Dashboard</h2>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="add_user.php" class="active">Admin Management</a></li>
        <li><a href="user_management.php">User Management</a></li>
        <li><a href="calendar.php">Calendar</a></li>
        <li><a href="Inventory.php">Inventory</a></li>
        <li><a href="admin_management.php">Edit</a></li>
        <li><a href="Index.php?logout=true">Logout</a></li>
    </ul>
</nav>

    <!-- Main Content -->
    <main class="main-content">
        <h1>Admin Management</h1>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="openTab('active-users')">Active Admins</div>
            <div class="tab" onclick="openTab('archived-users')">Archived Admins</div>
            <div class="tab" onclick="openTab('add-user')">Add New Admin</div>
        </div>

        <!-- Active Admins -->
        <div id="active-users" class="tab-content active">
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['bt_user_id']; ?></td>
                        <td><?= $user['bt_first_name'].' '.$user['bt_last_name']; ?></td>
                        <td><?= $user['bt_email']; ?></td>
                        <td><?= $user['bt_phone_number']; ?></td>
                        <td><?= date('M j, Y', strtotime($user['bt_created_at'])); ?></td>
                        <td>
                            <button class="history-button" onclick="editUser(
                                <?= $user['bt_user_id']; ?>,
                                '<?= $user['bt_first_name']; ?>',
                                '<?= $user['bt_last_name']; ?>',
                                '<?= $user['bt_email']; ?>',
                                '<?= $user['bt_phone_number']; ?>'
                            )">Edit</button>
                            <button class="clear-archive-btn" onclick="confirmArchive(<?= $user['bt_user_id']; ?>)">Archive</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" style="text-align:center;">No admins found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Archived Admins -->
        <div id="archived-users" class="tab-content">
            <table class="customer-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Archived Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($archived_users as $user): ?>
                    <tr>
                        <td><?= $user['id']; ?></td>
                        <td><?= $user['bt_first_name'].' '.$user['bt_last_name']; ?></td>
                        <td><?= $user['bt_email']; ?></td>
                        <td><?= $user['bt_phone_number']; ?></td>
                        <td><?= date('M j, Y', strtotime($user['archived_at'])); ?></td>
                        <td>
                            <button class="history-button" onclick="confirmRestore(<?= $user['id']; ?>)">Restore</button>
                            <button class="clear-archive-btn" onclick="confirmDelete(<?= $user['id']; ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($archived_users)): ?>
                    <tr><td colspan="6" style="text-align:center;">No archived admins found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Admin -->
        <div id="add-user" class="tab-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" class="history-button">Add Admin</button>
            </form>
        </div>
    </main>
</div>

<!-- ===================== -->
<!-- Edit Admin Modal -->
<!-- ===================== -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 class="modal-title">Edit Admin</h2>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" id="edit_user_id" name="user_id">

            <div class="form-group">
                <label for="edit_first_name">First Name</label>
                <input type="text" id="edit_first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="edit_last_name">Last Name</label>
                <input type="text" id="edit_last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone Number</label>
                <input type="text" id="edit_phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="edit_email">Email</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="edit_password">Password (leave blank to keep current)</label>
                <input type="password" id="edit_password" name="password">
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()">Cancel</button>
                <button type="submit">Update Admin</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== -->
<!-- Confirm Modal -->
<!-- ===================== -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeConfirmModal()">&times;</span>
        <h2 class="modal-title" id="confirmTitle">Confirm Action</h2>
        <p id="confirmMessage">Are you sure you want to proceed?</p>
        <form id="confirmForm" method="POST">
            <input type="hidden" id="confirm_action" name="action" value="">
            <input type="hidden" id="confirm_id" name="archive_id" value="">
            <div class="modal-footer">
                <button type="button" onclick="closeConfirmModal()">Cancel</button>
                <button type="submit" id="confirmButton">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
// Tab functionality
function openTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => tab.classList.remove('active'));
    
    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Show selected tab content and activate tab
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}

// Modal functions
function editUser(id, firstName, lastName, email, phone) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('editModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function confirmArchive(userId) {
    document.getElementById('confirmTitle').textContent = 'Archive Admin';
    document.getElementById('confirmMessage').textContent = 'Are you sure you want to archive this admin? They will be moved to archived admins.';
    document.getElementById('confirm_action').value = 'archive_user';
    document.getElementById('confirm_id').value = userId;
    document.getElementById('confirmButton').textContent = 'Archive';
    document.getElementById('confirmButton').style.backgroundColor = '#e74c3c';
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmRestore(archiveId) {
    document.getElementById('confirmTitle').textContent = 'Restore Admin';
    document.getElementById('confirmMessage').textContent = 'Are you sure you want to restore this admin?';
    document.getElementById('confirm_action').value = 'restore_user';
    document.getElementById('confirm_id').value = archiveId;
    document.getElementById('confirmButton').textContent = 'Restore';
    document.getElementById('confirmButton').style.backgroundColor = '#27ae60';
    document.getElementById('confirmModal').style.display = 'block';
}

function confirmDelete(archiveId) {
    document.getElementById('confirmTitle').textContent = 'Delete Admin';
    document.getElementById('confirmMessage').textContent = 'Are you sure you want to permanently delete this admin? This action cannot be undone.';
    document.getElementById('confirm_action').value = 'permanently_delete';
    document.getElementById('confirm_id').value = archiveId;
    document.getElementById('confirmButton').textContent = 'Delete';
    document.getElementById('confirmButton').style.backgroundColor = '#e74c3c';
    document.getElementById('confirmModal').style.display = 'block';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// Close modals when clicking outside
window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const confirmModal = document.getElementById('confirmModal');
    
    if (event.target === editModal) {
        editModal.style.display = 'none';
    }
    if (event.target === confirmModal) {
        confirmModal.style.display = 'none';
    }
}
</script>
</body>
</html>