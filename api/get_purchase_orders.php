<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    require_roles(['stock_clerk', 'manager', 'admin', 'account_clerk', 'supplier']);
    $user = current_user();
    $params = [];
    $where = '1=1';
    if ($user && $user['role'] === 'supplier') {
        $party = find_party_for_user((int)$user['user_id'], 'supplier');
        $where = 'po.supplier_id = ?';
        $params[] = $party ? $party['id'] : 0;
    }
    $stmt = get_pdo()->prepare("SELECT po.*, s.name AS supplier_display_name FROM purchase_orders po LEFT JOIN suppliers s ON s.supplier_id = po.supplier_id WHERE {$where} ORDER BY po.created_at DESC");
    $stmt->execute($params);
    respond(true, 'Purchase orders loaded', ['purchase_orders' => $stmt->fetchAll()]);
});
?>
