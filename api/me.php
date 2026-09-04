<?php
require_once __DIR__ . '/db_connect.php';

endpoint_guard(function (): void {
    require_method(['GET']);
    $user = current_user();

    if (!$user) {
        respond(false, 'Not logged in', ['user' => null]);
    }

    respond(true, 'User details', [
        'user' => public_user($user)
    ]);
});
?>
