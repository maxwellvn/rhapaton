<?php

require_once __DIR__ . '/../includes/google_translate.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON payload']);
    exit;
}

$texts = $payload['texts'] ?? [];
$target = (string) ($payload['target'] ?? 'en');
$source = isset($payload['source']) ? (string) $payload['source'] : 'en';

if (!is_array($texts)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'texts must be an array']);
    exit;
}

$texts = array_values(array_slice(array_map(static fn ($text) => (string) $text, $texts), 0, 250));

if ($target === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'target is required']);
    exit;
}

if ($target === 'en') {
    echo json_encode(['ok' => true, 'texts' => $texts]);
    exit;
}

$result = google_translate_many($texts, $target, $source !== '' ? $source : null);
if (!$result['ok']) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $result['error'] ?? 'Translation failed',
    ]);
    exit;
}

echo json_encode([
    'ok' => true,
    'texts' => $result['texts'],
]);
