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
require_once __DIR__ . '/../includes/storage.php';

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

if (!registration_storage_find_by_id($id)) {
    json_response(false, 'Registration not found');
}

if (!registration_storage_delete($id)) {
    json_response(false, 'Unable to delete registration');
}

json_response(true, 'Registration deleted');
?>
