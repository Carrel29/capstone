<?php
session_start();

require_once "../includes/dbh.inc.php";
require_once "../includes/allData.php";
require_once "../includes/userData.php";

$data = new AllData($pdo);

$users = $data->getAllUser();
$totalBooking = $data->getTotalBooking();
$totalSales = $data->getTotalSales();
$getAllUserAndSales = $data->getAllUserAndSales();
$getCompletedSalesStatus = $data->getAllUserAndSalesCompleted();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../CSS/style.css" />
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Comfortaa:wght@400;700&family=M+PLUS+Rounded+1c:wght@400;700&display=swap"
    rel="stylesheet">
  <script src="../JS/verify-modal.js"></script>

  <title>Home</title>
</head>

<body>
  <header>
    <h1 class="company-name">BTONE</h1>
    <nav class="nav-bar">
      <ul>
        <li><a href="admin.php"><img src="../Img/Login/boy.png" alt="" class="img-round"> <?php echo $fullname ?> </a>
        </li>
        <li class="dropdown">
          <a href="#"><img src="../Img/menu.png" alt="" class="img-round"></a>
          <ul class="dropdown-content">
            <li><a href="../includes/logout.php">Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>
  <main>
    <section class="combo-display-flex-space-between my-30 admin-content-data">
      <div class="card col-6 mx-20 w-100">
        <h4>TOTAL OF BOOKING</h4>
        <div class="content">
          <img src="../Img/admin/book-open-cover.png" alt="">
          <h1><?php echo $totalBooking; ?></h1>
        </div>
      </div>
      <div class="card col-6 mx-20 w-100">
        <h4>TOTAL OF SALES</h4>
        <div class="content">
          <img src="../Img/admin/wallet.png" alt="">
          <h1><?php echo $totalSales; ?></h1>
        </div>
      </div>
    </section>
    <section class="combo-display-flex">
      <div class="card mx-20 w-100 list-of-booking">
        <div class="header-content">
          <h1>All User</h1>
        </div>
        <div class="content table-container">
          <table>
            <thead>
              <th>ID</th>
              <th>Customer Name</th>
              <th>Email</th>
              <th>Phone No.</th>
              <th>Role</th>
              <th>Status</th>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td><?= htmlspecialchars($user['bt_user_id']) ?></td>
                  <td><?= htmlspecialchars($user['bt_first_name']) . ' ' . htmlspecialchars($user['bt_last_name']) ?>
                  </td>
                  <td><?= htmlspecialchars($user['bt_email']) ?></td>
                  <td><?= htmlspecialchars($user['bt_phone_number']) ?></td>
                  <td>
                    <?= ($user['bt_privilege_id'] == 1) ? 'Admin' : 'Customer'; ?>
                  </td>
                  <td>
                    <?= ($user['bt_is_active'] == 1) ? 'Active' : 'Inactive'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <section class="combo-display-flex">
      <div class="card mx-20 w-100 list-of-booking">
        <div class="header-content">
          <h1>Sales Approver</h1>
        </div>
        <div class="content table-container">
          <table>
            <thead>
              <th>Ref No.</th>
              <th>Customer Name</th>
              <th>Email</th>
              <th>Total Amount</th>
              <th>Total Amound Paid</th>
              <th>Status</th>
              <th>Updated By</th>
              <th>Status Update</th>
            </thead>
            <tbody>
              <?php foreach ($getAllUserAndSales as $userAndSale): ?>
                <tr>
                  <td><?= htmlspecialchars($userAndSale['refNo']) ?></td>
                  <td><?= htmlspecialchars($userAndSale['fullname']) . ' ' . htmlspecialchars($user['bt_last_name']) ?>
                  </td>
                  <td><?= htmlspecialchars($userAndSale['email']) ?></td>
                  <td><?= number_format((float) $userAndSale['TotalAmount'], 2, '.', ',') ?></td>
                  <td><?= number_format((float) ($userAndSale['AmountPaid'] ?? 0), 2, '.', ',') ?></td>
                  <td>
                    <?= ($userAndSale['Status'] == 1) ? 'Pending' : 'Complete'; ?>
                  </td>
                  <td>
                    <?php
                    $updatedByUser = $data->getUserById($userAndSale['updatedBy']);
                    echo htmlspecialchars($updatedByUser['bt_first_name'] . ' ' . $updatedByUser['bt_last_name']);
                    ?>
                  </td>
                  <td>
                    <button class="btn btn-secondary open-verify-modal"
                      data-id="<?= htmlspecialchars($userAndSale['sales_id']) ?>"
                      data-ref="<?= htmlspecialchars($userAndSale['refNo']) ?>"
                      data-total="<?= htmlspecialchars($userAndSale['TotalAmount']) ?>"
                      data-paid="<?= htmlspecialchars($userAndSale['AmountPaid'] ?? 0) ?>">Verify</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <section class="combo-display-flex">
      <div class="card mx-20 w-100 list-of-booking">
        <div class="header-content">
          <h1>Completed Sale Status</h1>
        </div>
        <div class="content table-container">
          <table>
            <thead>
              <th>Ref No.</th>
              <th>Customer Name</th>
              <th>Email</th>
              <th>Total Amount</th>
              <th>Total Amound Paid</th>
              <th>Status</th>
            </thead>
            <tbody>
              <?php foreach ($getCompletedSalesStatus as $userAndSale): ?>
                <tr>
                  <td><?= htmlspecialchars($userAndSale['refNo']) ?></td>
                  <td><?= htmlspecialchars($userAndSale['fullname']) . ' ' . htmlspecialchars($user['bt_last_name']) ?>
                  </td>
                  <td><?= htmlspecialchars($userAndSale['email']) ?></td>
                  <td><?= number_format((float) $userAndSale['TotalAmount'], 2, '.', ',') ?></td>
                  <td><?= number_format((float) ($userAndSale['AmountPaid'] ?? 0), 2, '.', ',') ?></td>
                  <td>
                    <?= ($userAndSale['Status'] == 1) ? 'Pending' : 'Complete'; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>


  <div class="pop-up-modal d-none">
    <div class="modal card">
      <div class="header-modal">
        <h2>Verify</h2>
        <span class="close-modal">&times;</span>
      </div>
      <div class="modal-content">
        <form action="../includes/handlestatus.php" method="POST">
          <input type="hidden" name="sales_id" id="sales_id" value="">
          <input type="hidden" name="ref_no" id="ref_no" value="">
          <input type="hidden" name="totalAmmount" id="totalAmmount" value="">
          <input type="hidden" name="userUpdate" id="userUpdate" value="<?php echo htmlspecialchars($user_id); ?>">
          <p class="data-refno my-10"></p>
          <p class="data-pending-ammount my-10"></p>
          <div class="form-group">
            <label for="amount_paid">Update Total Amount Paid:</label>
            <input type="number" name="amount_paid" id="amount_paid" class="form-control my-10" step="0.01" min="0"
              placeholder="Enter amount paid">
          </div>
          <small class="form-text text-muted"><i>Please ensure that this update is correct before
              submitting.</i></small>
          <div class="btn-group-card gap-20">
            <input type="submit" class="btn btn-view-now" value="Update">
          </div>
        </form>
      </div>
    </div>
  </div>
  <script src="../JS/nav.js"></script>

</body>

</html>