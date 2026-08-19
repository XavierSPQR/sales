<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['POST']);
    $user = require_roles(['stock_clerk', 'manager', 'admin']);
    $input = json_input();
    require_fields($input, ['supplier_id', 'material', 'quantity', 'unit_price']);

    $quantity = (float)$input['quantity'];
    $unitPrice = (float)$input['unit_price'];
    $total = $quantity * $unitPrice;
    $poNumber = $input['po_number'] ?? generate_code('PO');

    $stmt = get_pdo()->prepare('
        INSERT INTO purchase_orders (po_number, supplier_id, supplier_name, sample_id, material, quantity, unit_price, total_amount, delivery_date, payment_terms, status, manager_approved, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $poNumber,
        $input['supplier_id'],
        $input['supplier_name'] ?? null,
        $input['sample_id'] ?? null,
        $input['material'],
        $quantity,
        $unitPrice,
        $total,
        $input['delivery_date'] ?? null,
        $input['payment_terms'] ?? null,
        $input['status'] ?? 'Pending',
        !empty($input['manager_approved']) ? 1 : 0,
        $user['user_id'],
    ]);
    $poId = (int)get_pdo()->lastInsertId();

    get_pdo()->prepare('INSERT INTO purchase_order_items (purchase_order_id, material, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)')
        ->execute([$poId, $input['material'], $quantity, $unitPrice, $total]);

    respond(true, 'Purchase order created', ['purchase_order_id' => $poId, 'po_number' => $poNumber], 201);
});
?>
