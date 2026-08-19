<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST', 'PUT', 'PATCH']);
    $user = require_roles(['manager', 'admin']);
    $input = json_input();
    require_fields($input, ['request_id', 'status']);

    $status = ucfirst(strtolower((string)$input['status']));
    if (!in_array($status, ['Approved', 'Rejected'], true)) {
        fail('Status must be Approved or Rejected', 422);
    }

    $pdo = get_pdo();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM supplier_registration_requests WHERE request_id = ? FOR UPDATE');
    $stmt->execute([(int)$input['request_id']]);
    $request = $stmt->fetch();
    if (!$request) {
        $pdo->rollBack();
        fail('Supplier request not found', 404);
    }

    $pdo->prepare('UPDATE supplier_registration_requests SET status = ?, rejection_reason = ?, reviewed_at = NOW(), reviewed_by = ? WHERE request_id = ?')
        ->execute([$status, $input['rejection_reason'] ?? null, $user['user_id'], $request['request_id']]);

    $supplierId = null;
    $newUserId = null;
    if ($status === 'Approved') {
        $username = trim((string)($request['username'] ?? ''));
        if ($username === '') {
            $username = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $request['company_name'])) . '_' . $request['request_id'];
        }

        $exists = $pdo->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
        $exists->execute([$username, $request['email']]);
        if ($exists->fetch()) {
            $pdo->rollBack();
            fail('A user with this supplier username or email already exists', 409);
        }

        $pdo->prepare('
            INSERT INTO users (username, password, role, full_name, email, phone, address, city, status, redirect_page)
            VALUES (?, ?, "supplier", ?, ?, ?, ?, ?, "active", "supllierdashboard.html")
        ')->execute([
            $username,
            $request['password'],
            $request['company_name'],
            $request['email'],
            $request['phone'],
            $request['address'],
            $request['city'],
        ]);
        $newUserId = (int)$pdo->lastInsertId();

        $supplierCode = 'SUP' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        $pdo->prepare('
            INSERT INTO suppliers (user_id, supplier_code, name, contact, email, phone, address, city, postal_code, business_type, materials, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active")
        ')->execute([
            $newUserId,
            $supplierCode,
            $request['company_name'],
            $request['contact_person'],
            $request['email'],
            $request['phone'],
            $request['address'],
            $request['city'],
            $request['postal_code'] ?? null,
            $request['business_type'],
            $request['materials'],
        ]);
        $supplierId = (int)$pdo->lastInsertId();
    }

    $pdo->commit();
    respond(true, 'Supplier request ' . strtolower($status), [
        'supplier_id' => $supplierId,
        'user_id' => $newUserId,
    ]);
});
?>
