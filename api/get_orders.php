<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['GET']);
    $user = require_auth();
    $params = [];
    $where = '1=1';

    if (in_array($user['role'], ['customer', 'wholesaler'], true)) {
        $party = find_party_for_user((int)$user['user_id'], $user['role']);
        if (!$party) {
            respond(true, 'No orders found', ['orders' => []]);
        }
        $where = $user['role'] === 'customer' ? 'o.customer_id = ?' : 'o.wholesaler_id = ?';
        $params[] = $party['id'];
    }

    if (!empty($_GET['status'])) {
        $where .= ' AND o.delivery_status = ?';
        $params[] = $_GET['status'];
    }

    $stmt = get_pdo()->prepare("
        SELECT o.*, c.name AS customer_name, w.company_name AS wholesaler_name
        FROM orders o
        LEFT JOIN customers c ON c.customer_id = o.customer_id
        LEFT JOIN wholesalers w ON w.wholesaler_id = o.wholesaler_id
        WHERE {$where}
        ORDER BY o.order_date DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $itemStmt = get_pdo()->prepare('SELECT * FROM order_items WHERE order_id = ?');
    foreach ($orders as &$order) {
        $itemStmt->execute([$order['order_id']]);
        $order['items'] = $itemStmt->fetchAll();
    }

    respond(true, 'Orders loaded', ['orders' => $orders]);
});
?>
