<?php
require_once "../includes/allData.php";



if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['sales_id'], $_POST['ref_no'], $_POST['userUpdate'], $_POST['amount_paid'], $_POST['totalAmmount'])) {
    $id = filter_input(INPUT_POST, 'sales_id', FILTER_SANITIZE_NUMBER_INT);
    $refno = $_POST['ref_no'];
    $userUpdatedId = filter_input(INPUT_POST, 'userUpdate', FILTER_SANITIZE_NUMBER_INT);
    $AmmountPaid = filter_input(INPUT_POST, 'amount_paid', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $totalAmmount = filter_input(INPUT_POST, 'totalAmmount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $employee = new AllData($pdo);

    if ($AmmountPaid == $totalAmmount) {
        $employee->updateSaleStatusAndAmountPaid($id, 2, $refno, $userUpdatedId, $AmmountPaid); // Status 2 for complete
    } else if ($AmmountPaid > $totalAmmount) {
        $employee->updateSaleStatusAndAmountPaid($id, 1, $refno, $userUpdatedId, $AmmountPaid); // Status 1 for pending
    } else {
        $employee->updateSaleStatusAndAmountPaid($id, 1, $refno, $userUpdatedId, $AmmountPaid); // Status 1 for pending
    }
    // No update if $AmmountPaid exceeds $totalAmmount

    // Redirect back to the admin page
    header("Location: ../PHP/admin.php");
    exit();
} 