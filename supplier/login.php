<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username']) || !isset($data['password']) || !isset($data['role'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$username = $data['username'];
$password = $data['password'];
$role = $data['role'];

$usersFile = __DIR__ . '/../data/users.json';

if (!file_exists($usersFile)) {
    echo json_encode(['success' => false, 'message' => 'No registered users found. Please register first.']);
    exit;
}

$usersData = json_decode(file_get_contents($usersFile), true) ?: [];

$foundUser = null;
foreach ($usersData as $user) {
    if (strtolower($user['username']) === strtolower($username) && $user['role'] === $role) {
        $foundUser = $user;
        break;
    }
}

if (!$foundUser) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Verify password
if (!password_verify($password, $foundUser['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

// Check account status
if ($foundUser['account_status'] === 'pending') {
    echo json_encode([
        'success' => false, 
        'message' => 'Your account is pending approval. You will receive an email when approved.',
        'account_status' => 'pending'
    ]);
    exit;
}

if ($foundUser['account_status'] === 'rejected') {
    $reason = $foundUser['rejection_reason'] ?? 'No reason provided';
    echo json_encode([
        'success' => false,
        'message' => 'Your registration was rejected. Reason: ' . $reason,
        'account_status' => 'rejected'
    ]);
    exit;
}

// Successful login
$redirectMap = [
    'customer' => 'customer.html',
    'supplier' => 'supllierdashboard.html',
    'wholesaler' => 'wholeseller.html'
];

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'user' => [
        'id' => $foundUser['id'],
        'username' => $foundUser['username'],
        'email' => $foundUser['email'],
        'full_name' => $foundUser['full_name'],
        'role' => $foundUser['role'],
        'account_status' => $foundUser['account_status']
    ],
    'redirect_page' => $redirectMap[$role] ?? 'index.html'
]);
?>