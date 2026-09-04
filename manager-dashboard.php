<?php
// manager-dashboard.php at root
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

chdir(__DIR__ . '/manager');
require_once __DIR__ . '/manager/manager-dashboard.php';
?>
