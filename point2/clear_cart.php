<?php
session_start();

// Clear only the cart-related session variables, not the entire session
unset($_SESSION['cart']);
unset($_SESSION['total']);

// Clear localStorage and redirect to index.php
echo "<script>
    localStorage.removeItem('savedCart');
    window.location.href = 'index.php';
</script>";
exit;
?>