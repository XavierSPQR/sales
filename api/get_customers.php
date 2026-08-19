<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['admin', 'manager', 'account_clerk']);
    $stmt = get_pdo()->query('SELECT c.*, u.username, u.status AS user_status FROM customers c LEFT JOIN users u ON u.user_id = c.user_id ORDER BY c.created_at DESC');
    respond(true, 'Customers loaded', ['customers' => $stmt->fetchAll()]);
});
?>
