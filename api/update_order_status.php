<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST', 'PUT', 'PATCH']);
    $user = require_roles(['manager', 'stock_clerk', 'account_clerk', 'admin']);
    $input = json_input();
    require_fields($input, ['order_id']);

    $allowed = [
        'manager_approval' => ['manager', 'admin'],
        'delivery_status' => ['stock_clerk', 'manager', 'admin'],
        'payment_status' => ['account_clerk', 'manager', 'admin'],
        'tracking_number' => ['stock_clerk', 'manager', 'admin'],
    ];
    $sets = [];
    $params = [];

    foreach ($allowed as $field => $roles) {
        if (array_key_exists($field, $input)) {
            if (!in_array($user['role'], $roles, true)) {
                fail("Your role cannot update {$field}", 403);
            }
            $sets[] = "{$field} = ?";
            $params[] = $input[$field];
        }
    }

    if (!$sets) {
        fail('No status fields supplied', 422);
    }

    $params[] = (int)$input['order_id'];
    $stmt = get_pdo()->prepare('UPDATE orders SET ' . implode(', ', $sets) . ' WHERE order_id = ?');
    $stmt->execute($params);

    respond(true, 'Order status updated');
});
?>
