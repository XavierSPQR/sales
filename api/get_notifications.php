<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    $user = require_auth();
    $stmt = get_pdo()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
    $stmt->execute([$user['user_id']]);
    respond(true, 'Notifications loaded', ['notifications' => $stmt->fetchAll()]);
});
?>
