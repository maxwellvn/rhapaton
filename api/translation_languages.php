<?php

require_once __DIR__ . '/../includes/google_translate.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$target = $_GET['target'] ?? 'en';
$result = google_supported_languages((string) $target);

if (!$result['ok']) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $result['error'] ?? 'Unable to load languages',
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'languages' => $result['languages'],
    'fallback' => !empty($result['fallback']),
]);
