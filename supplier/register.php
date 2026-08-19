<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

// Required fields
$required = ['company_name', 'full_name', 'email', 'phone', 'address', 'city', 'postal_code', 'username', 'password', 'role'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => 'Missing required field: ' . $field]);
        exit;
    }
}

// Validate username (must start with ex-)
if (!preg_match('/^ex-[a-zA-Z0-9_]{1,}$/', $data['username'])) {
    echo json_encode(['success' => false, 'message' => 'Username must start with "ex-" (e.g., ex-kamals)']);
    exit;
}

// Validate password
if (strlen($data['password']) < 6 || !preg_match('/[0-9]/', $data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters and contain a number']);
    exit;
}

// Validate email
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate phone
if (!preg_match('/^[0-9]{10}$/', $data['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Phone must be exactly 10 digits']);
    exit;
}

// Simulate saving to database
// In production, save to database with status = 'pending'
// For demo, we'll store in a JSON file

$pendingFile = __DIR__ . '/../data/pending_registrations.json';
$pendingData = [];

if (file_exists($pendingFile)) {
    $pendingData = json_decode(file_get_contents($pendingFile), true) ?: [];
}

// Check if username already exists
foreach ($pendingData as $entry) {
    if (strtolower($entry['username']) === strtolower($data['username'])) {
        echo json_encode(['success' => false, 'message' => 'Username already exists. Please choose another.']);
        exit;
    }
}

// Create pending registration entry
$entry = [
    'request_id' => count($pendingData) + 1,
    'request_code' => 'REQ-' . str_pad(count($pendingData) + 1, 6, '0', STR_PAD_LEFT),
    'company_name' => $data['company_name'],
    'full_name' => $data['full_name'],
    'email' => $data['email'],
    'phone' => $data['phone'],
    'address' => $data['address'],
    'city' => $data['city'],
    'postal_code' => $data['postal_code'],
    'business_type' => $data['business_type'] ?? '',
    'materials' => $data['materials'] ?? '',
    'username' => $data['username'],
    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
    'role' => $data['role'],
    'status' => 'Pending',
    'created_at' => date('Y-m-d H:i:s')
];

$pendingData[] = $entry;

// Save to file
if (!is_dir(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data', 0777, true);
}

file_put_contents($pendingFile, json_encode($pendingData, JSON_PRETTY_PRINT));

// Also add to registered users with pending status
$usersFile = __DIR__ . '/../data/users.json';
$usersData = [];

if (file_exists($usersFile)) {
    $usersData = json_decode(file_get_contents($usersFile), true) ?: [];
}

$userEntry = [
    'id' => count($usersData) + 1,
    'username' => $data['username'],
    'password' => password_hash($data['password'], PASSWORD_DEFAULT),
    'email' => $data['email'],
    'full_name' => $data['full_name'],
    'phone' => $data['phone'],
    'address' => $data['address'],
    'city' => $data['city'],
    'postal_code' => $data['postal_code'],
    'role' => $data['role'],
    'company_name' => $data['company_name'],
    'business_type' => $data['business_type'] ?? '',
    'materials' => $data['materials'] ?? '',
    'account_status' => 'pending',
    'created_at' => date('Y-m-d H:i:s')
];

$usersData[] = $userEntry;
file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT));

echo json_encode([
    'success' => true,
    'message' => 'Registration submitted successfully. Awaiting admin approval.',
    'request_id' => $entry['request_id']
]);
?>