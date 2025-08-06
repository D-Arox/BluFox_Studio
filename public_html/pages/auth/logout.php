<?php
require_once __DIR__ . '/../../classes/MainClass.php';
require_once __DIR__ . '/../../classes/RobloxOAuth.php';

$robloxOAuth = new RobloxOAuth();
$robloxOAuth->logout();

header('Location: /?logged_out=1');
exit;
?>