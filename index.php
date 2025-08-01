<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Correct path to config outside public_html
include_once __DIR__ . '/../config/config.php';

$page_title = "BluFox Studio - Professional Roblox Development";
$page_description = "BluFox Studio - Professional Roblox game development, scripting services, and the revolutionary Vantara Framework.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/components/head.php' ?>
</head>
<body>
    <?php include 'includes/components/header.php'; ?>
    <?php include 'pages/home.php'; ?>
    <?php include 'includes/components/privacy-inline.php'; ?>
</body>
</html>