<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['admin', 'manager', 'account_clerk']);
    $stmt = get_pdo()->query('SELECT w.*, u.username, u.status AS user_status FROM wholesalers w LEFT JOIN users u ON u.user_id = w.user_id ORDER BY w.created_at DESC');
    respond(true, 'Wholesalers loaded', ['wholesalers' => $stmt->fetchAll()]);
});
?>
