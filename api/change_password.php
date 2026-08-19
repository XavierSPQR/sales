<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST']);
    $user = require_auth();
    $input = json_input();
    require_fields($input, ['current_password', 'new_password']);

    $valid = password_verify((string)$input['current_password'], $user['password']) || hash_equals($user['password'], (string)$input['current_password']);
    if (!$valid) {
        fail('Current password is incorrect', 401);
    }
    if (strlen((string)$input['new_password']) < 6) {
        fail('New password must be at least 6 characters', 422);
    }

    get_pdo()->prepare('UPDATE users SET password = ? WHERE user_id = ?')
        ->execute([password_hash((string)$input['new_password'], PASSWORD_DEFAULT), $user['user_id']]);

    respond(true, 'Password changed');
});
?>
