<?php
declare(strict_types=1);

session_start();

$allowedRoles = ['account_clerk', 'stock_clerk', 'manager', 'admin'];
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', $allowedRoles, true)) {
    header('Location: index.html');
    exit;
}

$html = file_get_contents(__DIR__ . '/linkeddashboard.html');
if ($html === false) {
    http_response_code(500);
    echo 'Linked dashboard template not found.';
    exit;
}

$name = htmlspecialchars($_SESSION['full_name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role = strtoupper(str_replace('_', ' ', $_SESSION['role'] ?? 'user'));
$html = str_replace('<div><strong id="userName">Account Clerk</strong></div>', '<div><strong id="userName">' . $name . '</strong></div>', $html);
$html = str_replace('<div class="role-badge" id="userRole">ACCOUNT CLERK</div>', '<div class="role-badge" id="userRole">' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '</div>', $html);

echo $html;
?>
