<?php
require_once __DIR__ . '/db_connect.php';
endpoint_guard(function (): void {
    require_method(['POST']);
    $user = require_auth();
    $input = json_input();
    require_fields($input, ['receiver_id', 'subject', 'message']);

    $stmt = get_pdo()->prepare('SELECT user_id, role, full_name FROM users WHERE user_id = ? AND status = "active"');
    $stmt->execute([(int)$input['receiver_id']]);
    $receiver = $stmt->fetch();
    if (!$receiver) {
        fail('Receiver not found', 404);
    }

    get_pdo()->prepare('
        INSERT INTO user_messages (sender_id, receiver_id, sender_role, receiver_role, sender_name, receiver_name, subject, message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([
        $user['user_id'],
        $receiver['user_id'],
        $user['role'],
        $receiver['role'],
        $user['full_name'],
        $receiver['full_name'],
        $input['subject'],
        $input['message'],
    ]);
    create_notification((int)$receiver['user_id'], $receiver['role'], 'info', 'New message', (string)$input['subject']);
    respond(true, 'Message sent', ['message_id' => (int)get_pdo()->lastInsertId()], 201);
});
?>
