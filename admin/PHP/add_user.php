<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: Index.php");
    exit();
}

// Database Configuration
$host = 'localhost';
$dbname = 'capstone';
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

// Add this after your database connection code
function getUserCustomColors($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT bg_color, font_family FROM user_customization WHERE user_id = ?");
    $stmt->execute([$userId]);
    $colors = $stmt->fetch(PDO::FETCH_ASSOC);
    return $colors ?: ['bg_color' => '#e8f4f8', 'font_family' => 'Arial']; // Default colors
}

// Get user's custom colors
$userColors = getUserCustomColors($pdo, $_SESSION['user_id']);

// Handle form submission for adding new employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    try {
        // Collect form data
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Use secure password hashing
        $role = 'employee'; // Force role to be employee

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already exists";
        } else {
            // Insert new employee
            $stmt = $pdo->prepare("INSERT INTO admin_users (first_name, last_name, email, password, role) 
                       VALUES (:first_name, :last_name, :email, :password, :role)");

            $stmt->execute([
                'first_name' => $first_name,
                'last_name' => $last_name,  
                'email' => $email,
                'password' => $password,
                'role' => $role
            ]);

            $success = "Employee added successfully";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle edit user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit_user') {
    try {
        $user_id = $_POST['user_id'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $role = 'employee'; // Force role to be employee
        
        // Check if password should be updated
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET first_name = :first_name, last_name = :last_name, 
                                   email = :email, password = :password, role = :role WHERE id = :id");
            $params = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'id' => $user_id
            ];
        } else {
            $stmt = $pdo->prepare("UPDATE admin_users SET first_name = :first_name, last_name = :last_name, 
                                   email = :email, role = :role WHERE id = :id");
            $params = [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'role' => $role,
                'id' => $user_id
            ];
        }
        
        $stmt->execute($params);
        $success = "Employee updated successfully";
        
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle archive user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'archive_user') {
    try {
        $user_id = $_POST['user_id'];
        
        // First, retrieve user information to be stored in archived_users table
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if archived_users table exists, if not create it
            $pdo->exec("CREATE TABLE IF NOT EXISTS archived_users (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                original_id INT(11) NOT NULL,
                email VARCHAR(100) NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                profile_picture VARCHAR(255) DEFAULT NULL,
                role ENUM('admin','employee') NOT NULL,
                archived_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
                account_type VARCHAR(20) NOT NULL DEFAULT 'admin_user'
            )");
            
            // Insert into archived_users table
            $stmt = $pdo->prepare("INSERT INTO archived_users (original_id, email, first_name, last_name, profile_picture, role, account_type) 
                VALUES (:original_id, :email, :first_name, :last_name, :profile_picture, :role, 'admin_user')");
                
            $stmt->execute([
                'original_id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'profile_picture' => $user['profile_picture'],
                'role' => $user['role']
            ]);
            
            // Delete from admin_users table
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            
            $success = "Employee archived successfully";
        } else {
            $error = "Employee not found";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle restore user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'restore_user') {
    try {
        $archive_id = $_POST['archive_id'];
        
        // First, retrieve archived user information
        $stmt = $pdo->prepare("SELECT * FROM archived_users WHERE id = :id AND account_type = 'admin_user'");
        $stmt->execute(['id' => $archive_id]);
        $archived_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($archived_user) {
            // Check if email already exists in active users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE email = :email");
            $stmt->execute(['email' => $archived_user['email']]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Cannot restore employee. Email already exists in active employees.";
            } else {
                // Insert back into admin_users table with role forced to employee
                $stmt = $pdo->prepare("INSERT INTO admin_users (email, password, first_name, last_name, profile_picture, role) 
                    VALUES (:email, :password, :first_name, :last_name, :profile_picture, 'employee')");
                $stmt->execute([
                    'email' => $archived_user['email'],
                    'password' => 'reset_required',
                    'first_name' => $archived_user['first_name'],
                    'last_name' => $archived_user['last_name'],
                    'profile_picture' => $archived_user['profile_picture']
                ]);
                
                // Delete from archived_users table
                $stmt = $pdo->prepare("DELETE FROM archived_users WHERE id = :id");
                $stmt->execute(['id' => $archive_id]);
                
                $success = "Employee restored successfully. Employee will need to reset password.";
            }
        } else {
            $error = "Archived employee not found";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle permanently delete user action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'permanently_delete') {
    try {
        $archive_id = $_POST['archive_id'];
        
        // Delete from archived_users table
        $stmt = $pdo->prepare("DELETE FROM archived_users WHERE id = :id AND account_type = 'admin_user'");
        $stmt->execute(['id' => $archive_id]);
        
        if ($stmt->rowCount() > 0) {
            $success = "Employee permanently deleted";
        } else {
            $error = "Employee not found";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Function to get all employees
function getUsers($pdo) {
    $stmt = $pdo->query("SELECT * FROM admin_users WHERE role = 'employee' ORDER BY id DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all archived employees
function getArchivedUsers($pdo) {
    // Check if archived_users table exists
    try {
        $stmt = $pdo->query("SELECT * FROM archived_users WHERE account_type = 'admin_user' AND role = 'employee' ORDER BY archived_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Get users if database connection successful
$users = ($pdo) ? getUsers($pdo) : [];
$archived_users = ($pdo) ? getArchivedUsers($pdo) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Management</title>
    <link rel="stylesheet" href="assets_css/add_user.css">
    <style>
        body{
            font-family: <?php echo htmlspecialchars($userColors['font_family']); ?>, sans-serif;
        }
    </style>
</head>
<body>
    <a href="dashboard.php" class="back-button">←</a>
    
    <div class="container">
        <h1>Employee Management</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab('active-users')">Active Employees</div>
            <div class="tab" onclick="openTab('archived-users')">Archived Employees</div>
            <div class="tab" onclick="openTab('add-user')">Add New Employee</div>
        </div>
        
        <!-- Active Users Tab -->
        <div id="active-users" class="tab-content">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="action-btn edit-btn" onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['first_name']; ?>', '<?php echo $user['last_name']; ?>', '<?php echo $user['email']; ?>')">Edit</button>
                                <button class="action-btn archive-btn" onclick="confirmArchive(<?php echo $user['id']; ?>)">Archive</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No employees found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Archived Users Tab -->
        <div id="archived-users" class="tab-content" style="display: none;">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Archived Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['archived_at'])); ?></td>
                            <td>
                                <button class="action-btn restore-btn" onclick="confirmRestore(<?php echo $user['id']; ?>)">Restore</button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $user['id']; ?>)">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($archived_users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No archived employees found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add User Tab -->
        <div id="add-user" class="tab-content" style="display: none;">
            <div class="add-user-container">
                <h2>Add New Employee</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group">
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="form-group">
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <!-- Remove role selector since all new users will be employees -->
                    <input type="hidden" name="role" value="employee">
                    <div class="form-group">
                        <button type="submit">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 class="modal-title">Edit Employee</h2>
            
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
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_password">Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password">
                </div>
                
                <!-- Remove role selector from edit form -->
                <input type="hidden" name="role" value="employee">
                
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" style="background-color: #7f8c8d;">Cancel</button>
                    <button type="submit" style="background-color: #2c3e50;">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeConfirmModal()">&times;</span>
            <h2 class="modal-title" id="confirmTitle">Confirm Action</h2>
            
            <p id="confirmMessage">Are you sure you want to proceed?</p>
            
            <form id="confirmForm" method="POST">
                <input type="hidden" id="confirm_action" name="action" value="">
                <input type="hidden" id="confirm_id" name="user_id" value="">
                
                <div class="modal-footer">
                    <button type="button" onclick="closeConfirmModal()" style="background-color: #7f8c8d;">Cancel</button>
                    <button type="submit" id="confirmButton" style="background-color: #e74c3c;">Confirm</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="asset_js/add_user.js"></script>
</body>
</html>