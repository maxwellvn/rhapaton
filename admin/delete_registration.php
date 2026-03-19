<?php
// Harden session cookies before starting session
$is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params(0, '/', '', $is_https, true);
}
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

function json_response($ok, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    json_response(false, 'Method not allowed');
}

// Require admin session
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    json_response(false, 'Unauthorized');
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $csrf)) {
    http_response_code(400);
    json_response(false, 'Invalid security token');
}

// Validate id
$id = $_POST['id'] ?? '';
if ($id === '') {
    http_response_code(400);
    json_response(false, 'Missing registration ID');
}

$storage_file = __DIR__ . '/../secure_data/registrations.json';
if (!file_exists($storage_file)) {
    json_response(false, 'Storage file not found');
}

$json = file_get_contents($storage_file);
$data = json_decode($json, true);
if (!is_array($data)) {
    json_response(false, 'Invalid storage format');
}

$original_count = count($data);
$filtered = array_values(array_filter($data, function ($reg) use ($id) {
    return isset($reg['id']) && $reg['id'] !== $id;
}));

if (count($filtered) === $original_count) {
    json_response(false, 'Registration not found');
}

// Atomic write
$temp = $storage_file . '.tmp';
$encoded = json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($encoded === false) {
    json_response(false, 'Failed to encode data: ' . json_last_error_msg());
}

if (file_put_contents($temp, $encoded, LOCK_EX) === false) {
    $err = error_get_last();
    json_response(false, 'Unable to write temp file: ' . ($err['message'] ?? 'unknown'));
}

if (!@rename($temp, $storage_file)) {
    @unlink($temp);
    $err = error_get_last();
    json_response(false, 'Unable to finalize delete: ' . ($err['message'] ?? 'unknown'));
}

@chmod($storage_file, 0644);

json_response(true, 'Registration deleted');
?>
