<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['request_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$requestId = $data['request_id'];
$action = $data['action'];
$reason = $data['reason'] ?? '';

$pendingFile = __DIR__ . '/../data/pending_registrations.json';
$usersFile = __DIR__ . '/../data/users.json';

if (!file_exists($pendingFile)) {
    echo json_encode(['success' => false, 'message' => 'No pending registrations found']);
    exit;
}

$pendingData = json_decode(file_get_contents($pendingFile), true) ?: [];
$usersData = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];

$found = false;
$userEntry = null;

// Find and update pending registration
foreach ($pendingData as $key => $entry) {
    if ($entry['request_id'] == $requestId) {
        $pendingData[$key]['status'] = ($action === 'approve') ? 'Approved' : 'Rejected';
        $pendingData[$key]['processed_at'] = date('Y-m-d H:i:s');
        $pendingData[$key]['reason'] = $reason;
        $found = true;
        $userEntry = $entry;
        break;
    }
}

if (!$found) {
    echo json_encode(['success' => false, 'message' => 'Registration request not found']);
    exit;
}

// Update user status in users.json
foreach ($usersData as &$user) {
    if ($user['username'] === $userEntry['username']) {
        if ($action === 'approve') {
            $user['account_status'] = 'active';
        } else {
            $user['account_status'] = 'rejected';
            $user['rejection_reason'] = $reason;
        }
        break;
    }
}

file_put_contents($pendingFile, json_encode($pendingData, JSON_PRETTY_PRINT));
file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT));

// Send email notification (simulated)
$emailSubject = ($action === 'approve') 
    ? '✅ Supplier Registration Approved - Pearl Land Commodities' 
    : '❌ Supplier Registration Update - Pearl Land Commodities';

$emailBody = ($action === 'approve')
    ? "Dear {$userEntry['full_name']},\n\n"
    . "We are pleased to inform you that your supplier registration with Pearl Land Commodities has been approved!\n\n"
    . "You can now log in to your account using your username and password:\n"
    . "Username: {$userEntry['username']}\n"
    . "Login URL: https://yourdomain.com/index.html\n\n"
    . "Welcome to our supplier network!\n\n"
    . "Best regards,\n"
    . "Pearl Land Commodities Team"
    : "Dear {$userEntry['full_name']},\n\n"
    . "Thank you for your interest in becoming a supplier with Pearl Land Commodities.\n\n"
    . "We regret to inform you that your registration request has not been approved at this time.\n"
    . ($reason ? "Reason: $reason\n\n" : "")
    . "We appreciate your interest and encourage you to apply again in the future.\n\n"
    . "Best regards,\n"
    . "Pearl Land Commodities Team";

// Simulate sending email - in production use PHPMailer or mail()
// mail($userEntry['email'], $emailSubject, $emailBody);

echo json_encode([
    'success' => true,
    'message' => ($action === 'approve') 
        ? 'Registration approved! Email notification sent to the supplier.' 
        : 'Registration rejected. Email notification sent to the supplier.'
]);
?>