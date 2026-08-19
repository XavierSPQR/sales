<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST', 'PUT', 'PATCH']);
    $user = require_roles(['stock_clerk', 'manager', 'admin']);
    $input = json_input();
    require_fields($input, ['product_id', 'quantity']);

    $pdo = get_pdo();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT product_id, current_stock FROM products WHERE product_id = ? FOR UPDATE');
    $stmt->execute([(int)$input['product_id']]);
    $product = $stmt->fetch();
    if (!$product) {
        $pdo->rollBack();
        fail('Product not found', 404);
    }

    $quantity = (float)$input['quantity'];
    $type = $input['movement_type'] ?? 'adjustment';
    $previous = (float)$product['current_stock'];
    $newStock = $type === 'in' ? $previous + $quantity : ($type === 'out' ? $previous - $quantity : $quantity);
    if ($newStock < 0) {
        $pdo->rollBack();
        fail('Stock cannot be negative', 422);
    }

    $pdo->prepare('UPDATE products SET current_stock = ? WHERE product_id = ?')->execute([$newStock, $product['product_id']]);
    $pdo->prepare('
        INSERT INTO stock_movements (product_id, movement_type, quantity, previous_stock, new_stock, reference_type, reference_id, reason, notes, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $product['product_id'],
        $type,
        $quantity,
        $previous,
        $newStock,
        $input['reference_type'] ?? 'manual',
        $input['reference_id'] ?? null,
        $input['reason'] ?? 'Stock updated',
        $input['notes'] ?? null,
        $user['user_id'],
    ]);

    $pdo->commit();
    respond(true, 'Stock updated', ['previous_stock' => $previous, 'new_stock' => $newStock]);
});
?>
