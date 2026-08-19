<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['POST']);
    $user = require_roles(['stock_clerk', 'manager', 'admin']);
    $input = json_input();
    require_fields($input, ['received_quantity']);

    $stmt = get_pdo()->prepare('
        INSERT INTO grns (grn_number, purchase_order_id, po_number, supplier_id, supplier_name, received_date, received_quantity, inspected_by, inspector_id, remarks, amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $grnNumber = $input['grn_number'] ?? generate_code('GRN');
    $stmt->execute([
        $grnNumber,
        $input['purchase_order_id'] ?? null,
        $input['po_number'] ?? null,
        $input['supplier_id'] ?? null,
        $input['supplier_name'] ?? null,
        $input['received_date'] ?? date('Y-m-d'),
        (float)$input['received_quantity'],
        $input['inspected_by'] ?? $user['full_name'],
        $user['user_id'],
        $input['remarks'] ?? null,
        (float)($input['amount'] ?? 0),
        $input['status'] ?? 'Pending',
    ]);

    if (!empty($input['purchase_order_id'])) {
        get_pdo()->prepare('UPDATE purchase_orders SET status = "Received" WHERE purchase_order_id = ?')->execute([(int)$input['purchase_order_id']]);
    }

    respond(true, 'GRN created', ['grn_id' => (int)get_pdo()->lastInsertId(), 'grn_number' => $grnNumber], 201);
});
?>
