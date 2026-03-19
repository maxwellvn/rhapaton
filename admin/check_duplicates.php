<?php
// Check for duplicate email and KingsChat username registrations

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');

// Check if this is a duplicate check request
if (!isset($_POST['action']) || $_POST['action'] !== 'check_duplicates') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$kingschat_username = trim($_POST['kingschat_username'] ?? '');

// Load existing registrations
$data_file = __DIR__ . '/../secure_data/registrations.json';
$existing_registrations = [];
if (is_file($data_file)) {
    $json = file_get_contents($data_file);
    $existing_registrations = json_decode($json, true) ?: [];
}

$errors = [];

// Check for duplicate email
if (!empty($email)) {
    $submitted_email = strtolower($email);
    foreach ($existing_registrations as $existing) {
        $existing_email = strtolower(trim($existing['personal_info']['email'] ?? ''));
        if ($existing_email === $submitted_email) {
            $errors[] = "Email address already registered";
            break;
        }
    }
}

// Check for duplicate KingsChat username
if (!empty($kingschat_username)) {
    // Normalize KingsChat username
    if (!str_starts_with($kingschat_username, '@')) {
        $kingschat_username = '@' . $kingschat_username;
    }

    $submitted_kingschat_clean = ltrim(strtolower($kingschat_username), '@');
    foreach ($existing_registrations as $existing) {
        $existing_kingschat = strtolower(trim($existing['personal_info']['kingschat_username'] ?? ''));
        $existing_kingschat_clean = ltrim($existing_kingschat, '@');

        if (!empty($existing_kingschat_clean) && $existing_kingschat_clean === $submitted_kingschat_clean) {
            $errors[] = "KingsChat username already registered";
            break;
        }
    }
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
