<?php
// Reuse the same PDO connection from Dashboard.php
$stmt = $pdo->query("
    SELECT * 
    FROM btuser 
    WHERE bt_privilege_id = 1 
    ORDER BY bt_user_id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="table-header">
    <h3>Admin List</h3>
        <a href="add_user.php" class="nav-btn">View</a>
</div>

<table class="customer-table">
    <thead>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Created</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['bt_user_id']); ?></td>
            <td><?= htmlspecialchars($user['bt_first_name'].' '.$user['bt_last_name']); ?></td>
            <td><?= htmlspecialchars($user['bt_email']); ?></td>
            <td><?= htmlspecialchars($user['bt_phone_number']); ?></td>
            <td><?= date('M j, Y', strtotime($user['bt_created_at'])); ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($users)): ?>
        <tr><td colspan="5" style="text-align:center;">No admins found</td></tr>
    <?php endif; ?>
    </tbody>
</table>
