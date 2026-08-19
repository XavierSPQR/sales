<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['GET']);
    $status = $_GET['status'] ?? 'active';
    $category = $_GET['category'] ?? null;

    $sql = 'SELECT p.*, s.name AS supplier_name FROM products p LEFT JOIN suppliers s ON s.supplier_id = p.supplier_id WHERE 1=1';
    $params = [];
    if ($status !== 'all') {
        $sql .= ' AND p.status = ?';
        $params[] = $status;
    }
    if ($category) {
        $sql .= ' AND p.category = ?';
        $params[] = $category;
    }
    $sql .= ' ORDER BY p.name';

    $stmt = get_pdo()->prepare($sql);
    $stmt->execute($params);
    respond(true, 'Products loaded', ['products' => $stmt->fetchAll()]);
});
?>
