<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
    <?php include '/../includes/components/head.php'; ?>
<body>
    <?php include '/../includes/components/header.php'; ?>
    <?php include '/../pages/home.php'; ?>
</body>
</html>