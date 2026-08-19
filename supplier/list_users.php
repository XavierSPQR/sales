<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$usersFile = __DIR__ . '/../data/users.json';

if (!file_exists($usersFile)) {
    echo json_encode(['success' => true, 'data' => ['customers' => [], 'wholesalers' => [], 'suppliers' => []]]);
    exit;
}

$usersData = json_decode(file_get_contents($usersFile), true) ?: [];

$customers = [];
$wholesalers = [];
$suppliers = [];

foreach ($usersData as $user) {
    if ($user['account_status'] !== 'active') continue;
    
    if ($user['role'] === 'customer') {
        $customers[] = $user;
    } elseif ($user['role'] === 'wholesaler') {
        $wholesalers[] = $user;
    } elseif ($user['role'] === 'supplier') {
        $suppliers[] = $user;
    }
}

echo json_encode([
    'success' => true,
    'data' => [
        'customers' => $customers,
        'wholesalers' => $wholesalers,
        'suppliers' => $suppliers
    ]
]);
?>