<?php
// Bulk sender for KingsChat notifications to registrants

$__rb = function_exists('random_bytes');
if (!$__rb && function_exists('openssl_random_pseudo_bytes')) {
    function random_bytes($length) { return openssl_random_pseudo_bytes($length); }
}
$__rb = null;
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

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-cache');
header('Content-Type: application/json');

// Require admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['type' => 'error', 'error' => 'Unauthorized']);
    exit;
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $csrf)) {
    echo json_encode(['type' => 'error', 'error' => 'Invalid CSRF token']);
    exit;
}

// Check KingsChat session
require_once __DIR__ . '/../kingschat/helpers.php';
if (!kc_is_authenticated()) {
    echo json_encode(['type' => 'error', 'error' => 'KingsChat not authenticated. Login via /kingschat/ or configure KC_REMOTE_BASE.']);
    exit;
}

// Load token refresh functions
@require_once __DIR__ . '/../kingschat/token_refresh.php';

// Read message
$message = trim($_POST['message'] ?? '');
if ($message === '') {
    echo json_encode(['type' => 'error', 'error' => 'Message cannot be empty']);
    exit;
}

// Load registrations
$registrations = registration_storage_all();

$targets = [];
foreach ($registrations as $reg) {
    $username = trim($reg['personal_info']['kingschat_username'] ?? '');
    if ($username !== '') {
        $title = trim($reg['personal_info']['title'] ?? '');
        $first = trim($reg['personal_info']['first_name'] ?? '');
        $last = trim($reg['personal_info']['last_name'] ?? '');
        $nameCore = trim($first . ' ' . $last);
        $name = trim(($title !== '' ? $title . ' ' : '') . $nameCore);
        $targets[] = [
            'username' => $username,
            'name' => $name,
        ];
    }
}

// Check if specific recipients are selected
$selectedRecipients = $_POST['recipients'] ?? [];
if (!empty($selectedRecipients) && is_array($selectedRecipients)) {
    // Filter targets to only include selected recipients
    $selectedUsernames = array_map('trim', $selectedRecipients);
    $targets = array_filter($targets, function($target) use ($selectedUsernames) {
        $targetUsername = trim($target['username']);

        // Check for exact match first
        if (in_array($targetUsername, $selectedUsernames)) {
            return true;
        }

        // Also check without @ prefix for compatibility
        $targetUsernameClean = ltrim($targetUsername, '@');
        foreach ($selectedUsernames as $selected) {
            $selectedClean = ltrim(trim($selected), '@');
            if ($targetUsernameClean === $selectedClean) {
                return true;
            }
        }

        return false;
    });
}

$total = count($targets);
if ($total === 0) {
    $message = !empty($selectedRecipients)
        ? 'No valid recipients found among selected users. Please check that the selected users have valid KingsChat usernames.'
        : 'No registrants with KingsChat usernames found in the database.';
    echo json_encode(['type' => 'done', 'success' => 0, 'failed' => 0, 'detail' => $message]);
    exit;
}

// Log the filtering results for debugging
if (!empty($selectedRecipients)) {
    $selectedCount = count($selectedRecipients);
    $filteredCount = count($targets);
    error_log("Notification filtering: Selected $selectedCount recipients, found $filteredCount valid targets");
}

// Streaming helper
function stream_event(array $data) {
    echo json_encode($data) . "\n";
    if (function_exists('ob_flush')) @ob_flush();
    flush();
}

$sent = 0; $failed = 0; $i = 0;
foreach ($targets as $t) {
    $i++;
    $username = $t['username'];
    $name = $t['name'];
    $personalized = str_replace(['{name}', '{NAME}', '{{name}}'], [$name, strtoupper($name), $name], $message);

    // Refresh token for every message or if it's close to expiring
    if (function_exists('ensureValidToken')) {
        // Refresh token if it's about to expire within 2 minutes, or every 10 messages
        if (!@ensureValidToken(120) || ($i % 10 === 1 && $i > 1)) {
            // Log the refresh attempt
            error_log("Token refresh triggered for message $i to $username");
            $tokenRefreshed = @ensureValidToken(300); // Refresh if expires within 5 minutes
            if (!$tokenRefreshed) {
                $failed++;
                outbox_storage_append([
                    'timestamp' => date('Y-m-d H:i:s'),
                    'username' => $username,
                    'name' => $name,
                    'message' => $personalized,
                    'status' => 'token_refresh_failed'
                ]);
                stream_event(['type' => 'progress', 'sent' => $sent, 'total' => $total, 'message' => "Token refresh failed for @$username - skipping"]);
                continue;
            } else {
                // Small delay after token refresh to be respectful to the API
                usleep(50000); // 50ms delay
            }
        }
    }

    // Lookup user ID
    $user_id = kc_lookup_user_id($username);
    if (!$user_id) {
        $failed++;
        outbox_storage_append([
            'timestamp' => date('Y-m-d H:i:s'),
            'username' => $username,
            'name' => $name,
            'message' => $personalized,
            'status' => 'lookup_failed'
        ]);
        stream_event(['type' => 'progress', 'sent' => $sent, 'total' => $total, 'message' => "Lookup failed for @$username"]);
        continue;
    }

    // Send message
    $res = kc_send_text_message($user_id, $personalized);
    if ($res['ok']) {
        $sent++;
        outbox_storage_append([
            'timestamp' => date('Y-m-d H:i:s'),
            'username' => $username,
            'name' => $name,
            'message' => $personalized,
            'status' => 'sent'
        ]);
        stream_event(['type' => 'progress', 'sent' => $sent, 'total' => $total, 'message' => "Sent to @$username"]);
    } else {
        $failed++;
        $code = $res['status'] ?? 0;
        outbox_storage_append([
            'timestamp' => date('Y-m-d H:i:s'),
            'username' => $username,
            'name' => $name,
            'message' => $personalized,
            'status' => 'failed',
            'error' => $res['error'] ?? ('HTTP ' . $code)
        ]);
        stream_event(['type' => 'progress', 'sent' => $sent, 'total' => $total, 'message' => "Failed @$username (" . ($res['error'] ?? ('HTTP ' . $code)) . ")"]);
    }

    // Be polite to API
    usleep(150000); // 150ms
}

// Provide detailed completion message
$completionMessage = 'Broadcast finished';
if (!empty($selectedRecipients)) {
    $originalCount = count($selectedRecipients);
    $completionMessage .= ": Processed $total of $originalCount selected recipients";
}

stream_event(['type' => 'done', 'success' => $sent, 'failed' => $failed, 'detail' => $completionMessage]);
exit;

?>
