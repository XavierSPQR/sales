<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST']);
    $input = json_input();
    require_fields($input, ['username', 'password']);

    $stmt = get_pdo()->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([trim((string)$input['username'])]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        fail('Invalid username or inactive account', 401);
    }

    $password = (string)$input['password'];
    $stored = (string)$user['password'];
    $valid = password_verify($password, $stored) || hash_equals($stored, $password);

    if (!$valid) {
        fail('Invalid username or password', 401);
    }

    if (!password_get_info($stored)['algo']) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = get_pdo()->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $update->execute([$hash, $user['user_id']]);
    }

    get_pdo()->prepare('UPDATE users SET last_login = NOW() WHERE user_id = ?')->execute([$user['user_id']]);
    update_user_session($user);

    $redirects = [
        'admin' => 'admin-dashboard.php',
        'manager' => 'manager-dashboard.php',
        'stock_clerk' => 'stock-dashboard.php',
        'account_clerk' => 'account-dashboard.php',
        'customer' => 'customer.html',
        'wholesaler' => 'wholeseller.html',
        'supplier' => 'supllierdashboard.html',
    ];

    respond(true, 'Login successful', [
        'user' => public_user($user),
        'redirect_page' => $redirects[$user['role']] ?? ($user['redirect_page'] ?: null),
    ]);
});
?>
