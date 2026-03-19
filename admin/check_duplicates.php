<?php
// Check for duplicate email and KingsChat username registrations

require_once __DIR__ . '/../includes/storage.php';

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');

// Check if this is a duplicate check request
if (!isset($_POST['action']) || $_POST['action'] !== 'check_duplicates') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$kingschat_username = trim($_POST['kingschat_username'] ?? '');

$errors = [];
$duplicates = registration_storage_duplicate_status($email, $kingschat_username);

if (!empty($duplicates['email'])) {
    $errors[] = 'Email address already registered';
}

if (!empty($duplicates['kingschat'])) {
    $errors[] = 'KingsChat username already registered';
}

// Return result
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' and ', $errors) . '. Please use different contact information or contact support at <a href="https://kingschat.online/user/kingsblast" target="_blank" style="color: #D4AF37; text-decoration: underline;">KingsChat: @kingsblast</a>.'
    ]);
} else {
    echo json_encode(['success' => true]);
}
exit;
?>
