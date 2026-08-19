<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['admin', 'manager', 'stock_clerk', 'account_clerk']);
    $stmt = get_pdo()->query('SELECT s.*, u.username, u.status AS user_status FROM suppliers s LEFT JOIN users u ON u.user_id = s.user_id ORDER BY s.created_at DESC');
    respond(true, 'Suppliers loaded', ['suppliers' => $stmt->fetchAll()]);
});
?>
