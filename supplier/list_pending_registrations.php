<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$pendingFile = __DIR__ . '/../data/pending_registrations.json';

if (!file_exists($pendingFile)) {
    echo json_encode(['success' => true, 'data' => ['requests' => []]]);
    exit;
}

$pendingData = json_decode(file_get_contents($pendingFile), true) ?: [];
$pending = array_filter($pendingData, function($entry) {
    return $entry['status'] === 'Pending';
});

echo json_encode([
    'success' => true,
    'data' => [
        'requests' => array_values($pending)
    ]
]);
?>