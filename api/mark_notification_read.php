<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['POST', 'PUT', 'PATCH']);
    $user = require_auth();
    $input = json_input();
    require_fields($input, ['notification_id']);
    $stmt = get_pdo()->prepare('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?');
    $stmt->execute([(int)$input['notification_id'], $user['user_id']]);
    respond(true, 'Notification marked as read');
});
?>
