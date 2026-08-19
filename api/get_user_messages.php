<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['GET']);
    $user = require_auth();
    $box = $_GET['box'] ?? 'inbox';
    $column = $box === 'sent' ? 'sender_id' : 'receiver_id';
    $stmt = get_pdo()->prepare("SELECT * FROM user_messages WHERE {$column} = ? AND status <> 'deleted' ORDER BY created_at DESC LIMIT 100");
    $stmt->execute([$user['user_id']]);
    respond(true, 'Messages loaded', ['messages' => $stmt->fetchAll()]);
});
?>
