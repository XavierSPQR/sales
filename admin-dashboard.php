<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.html');
    exit;
}

$html = file_get_contents(__DIR__ . '/admin-dashboard-embedded.html');
if ($html === false) {
    http_response_code(500);
    echo 'Admin dashboard template not found.';
    exit;
}

$html = str_replace('Admin User', htmlspecialchars($_SESSION['full_name'] ?? 'Admin User', ENT_QUOTES, 'UTF-8'), $html);
$html = str_replace("window.location.href = 'index.html'", "window.location.href = 'logout.php'", $html);

echo $html;
?>
