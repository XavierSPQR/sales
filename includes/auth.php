<?php
// includes/auth.php
// Authentication and authorization helper functions.
// Uses the Database singleton class for all DB operations.

require_once __DIR__ . '/../classes/Database.php';

// Get the database connection from the singleton
$pdo = Database::getInstance()->getConnection();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Redirect to appropriate dashboard based on role
        if (hasRole('manager')) {
            header('Location: ../manager-dashboard.php');
        } elseif (hasRole('stock_clerk')) {
            header('Location: ../stock-dashboard.php');
        } elseif (hasRole('account_clerk')) {
            header('Location: ../account-dashboard.php');
        } else {
            header('Location: ../index.php');
        }
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $pdo = Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getRoleDisplayName($role) {
    $roles = [
        'manager' => '👑 Manager',
        'stock_clerk' => '📦 Stock Clerk',
        'account_clerk' => '💰 Account Clerk'
    ];
    return $roles[$role] ?? $role;
}
?>
