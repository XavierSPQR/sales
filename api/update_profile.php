<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['POST', 'PUT', 'PATCH']);
    $user = require_auth();
    $input = json_input();

    $fields = ['full_name', 'email', 'phone', 'address', 'city', 'profile_image'];
    $sets = [];
    $params = [];
    foreach ($fields as $field) {
        if (array_key_exists($field, $input)) {
            $sets[] = "{$field} = ?";
            $params[] = $input[$field];
        }
    }
    if (!$sets) {
        fail('No profile fields supplied', 422);
    }
    $params[] = $user['user_id'];
    get_pdo()->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = ?')->execute($params);

    respond(true, 'Profile updated');
});
?>
