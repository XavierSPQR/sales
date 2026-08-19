<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST']);
    require_roles(['stock_clerk', 'manager', 'admin']);
    $input = json_input();
    require_fields($input, ['name', 'price']);

    $code = $input['product_code'] ?? generate_code('P');
    $stmt = get_pdo()->prepare('
        INSERT INTO products
            (product_code, name, category, sub_category, description, unit, price, wholesale_price, cost_price, current_stock, reorder_level, supplier_id, image_path, is_available, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $code,
        $input['name'],
        $input['category'] ?? null,
        $input['sub_category'] ?? null,
        $input['description'] ?? null,
        $input['unit'] ?? 'kg',
        (float)$input['price'],
        isset($input['wholesale_price']) ? (float)$input['wholesale_price'] : null,
        (float)($input['cost_price'] ?? 0),
        (float)($input['current_stock'] ?? 0),
        (float)($input['reorder_level'] ?? 10),
        $input['supplier_id'] ?? null,
        $input['image_path'] ?? null,
        isset($input['is_available']) ? (int)(bool)$input['is_available'] : 1,
        $input['status'] ?? 'active',
    ]);

    respond(true, 'Product added', ['product_id' => (int)get_pdo()->lastInsertId(), 'product_code' => $code], 201);
});
?>
