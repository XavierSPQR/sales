<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['POST']);
    $user = require_roles(['account_clerk', 'manager', 'admin']);
    $input = json_input();
    require_fields($input, ['amount', 'payment_method']);
    $type = $input['payment_type'] ?? 'customer';

    if ($type === 'supplier') {
        require_fields($input, ['supplier_id']);
        $stmt = get_pdo()->prepare('
            INSERT INTO supplier_payments (grn_id, purchase_order_id, supplier_id, invoice_number, amount, payment_method, reference_no, status, due_date, payment_date, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $input['grn_id'] ?? null,
            $input['purchase_order_id'] ?? null,
            $input['supplier_id'],
            $input['invoice_number'] ?? null,
            (float)$input['amount'],
            $input['payment_method'],
            $input['reference_no'] ?? null,
            $input['status'] ?? 'paid',
            $input['due_date'] ?? null,
            $input['payment_date'] ?? date('Y-m-d'),
            $input['notes'] ?? null,
            $user['user_id'],
        ]);
    } else {
        $stmt = get_pdo()->prepare('
            INSERT INTO payments (order_id, customer_id, wholesaler_id, payment_type, amount, payment_method, reference_no, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $input['order_id'] ?? null,
            $input['customer_id'] ?? null,
            $input['wholesaler_id'] ?? null,
            $type,
            (float)$input['amount'],
            $input['payment_method'],
            $input['reference_no'] ?? null,
            $input['status'] ?? 'paid',
            $input['notes'] ?? null,
        ]);
        if (!empty($input['order_id'])) {
            get_pdo()->prepare('UPDATE orders SET payment_status = ? WHERE order_id = ?')
                ->execute([$input['status'] ?? 'paid', (int)$input['order_id']]);
        }
    }

    respond(true, 'Payment processed', ['payment_id' => (int)get_pdo()->lastInsertId()], 201);
});
?>
