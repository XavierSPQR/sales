<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['stock_clerk', 'manager', 'admin', 'account_clerk']);
    $stmt = get_pdo()->query('SELECT * FROM grns ORDER BY created_at DESC');
    respond(true, 'GRNs loaded', ['grns' => $stmt->fetchAll()]);
});
?>
