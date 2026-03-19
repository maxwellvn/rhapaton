<?php
// Resend KingsChat confirmation message for a specific registration

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

// Enable robust JSON error handling to avoid blank 500s
ob_start();
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
});
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level()) { ob_end_clean(); }
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $err['message']]);
    } else {
        // flush any buffered output
        if (ob_get_level()) { @ob_end_flush(); }
    }
});

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/json');

// Require admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['admin_csrf_token'] ?? '', $csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$id = trim($_POST['id'] ?? '');
if ($id === '') {
    echo json_encode(['success' => false, 'message' => 'Missing registration id']);
    exit;
}

// Load data
$target = registration_storage_find_by_id($id);
if (!$target) {
    echo json_encode(['success' => false, 'message' => 'Registration not found']);
    exit;
}

$kcUsername = trim($target['personal_info']['kingschat_username'] ?? '');
if ($kcUsername === '') {
    echo json_encode(['success' => false, 'message' => 'No KingsChat username on registration']);
    exit;
}

require_once __DIR__ . '/../kingschat/helpers.php';
@require_once __DIR__ . '/../kingschat/token_refresh.php';

// Try to restore token from config if needed
if (!kc_remote_enabled() && !kc_is_authenticated()) {
    $cfg = __DIR__ . '/../secure_data/kc_config.json';
    if (is_file($cfg)) {
        $cfgJson = json_decode(@file_get_contents($cfg), true);
        if (is_array($cfgJson) && !empty($cfgJson['access_token'])) {
            $exp = (int)($cfgJson['expires_at'] ?? 0);
            if ($exp === 0 || $exp > time()) {
                $_SESSION['kc_access_token'] = $cfgJson['access_token'];
                if (!empty($cfgJson['refresh_token'])) {
                    $_SESSION['kc_refresh_token'] = $cfgJson['refresh_token'];
                }
            }
        }
    }
}

if (!(kc_remote_enabled() || kc_is_authenticated())) {
    echo json_encode(['success' => false, 'message' => 'KingsChat not authenticated on server']);
    exit;
}

// Refresh token immediately before sending message to ensure it's fresh
if (function_exists('ensureValidToken')) {
    $tokenRefreshed = @ensureValidToken(120); // Refresh if expires within 2 minutes
    if (!$tokenRefreshed) {
        echo json_encode(['success' => false, 'message' => 'Failed to refresh KingsChat token']);
        exit;
    }
}

// Ensure cURL is available if not in remote mode
if (!kc_remote_enabled() && !function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'PHP cURL extension is missing. Enable it on the server.']);
    exit;
}

$userId = kc_lookup_user_id($kcUsername);
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'KingsChat username lookup failed']);
    exit;
}

$title = trim($target['personal_info']['title'] ?? '');
$first = trim($target['personal_info']['first_name'] ?? '');
$last  = trim($target['personal_info']['last_name'] ?? '');
$nameCore = trim($first . ' ' . $last);
$name = trim(($title !== '' ? ($title . ' ') : '') . $nameCore);
$regDate = date('M j, Y', strtotime($target['timestamp'] ?? 'now'));
    // Include translation function
    require_once __DIR__ . '/submit_registration.php';

    $userLanguage = $target['language_preference'] ?? 'en';
    $msg = getTranslatedKingsChatMessage($name, $regDate, $userLanguage);

    // Fallback message in case of issues
    if (empty($msg)) {
        $msg = "Dear {$name}\n\n" .
               "Thank you for registering for **Rhapathon 2026** with **Pastor Chris**, taking place from Monday 4th to Friday 8th May 2026. We are delighted to have you on board.\n\n" .
               "Join us to **sponsor Rhapsody of Realities** as we prepare for this upcoming program.\n\n" .
               "🔥 **Be a Rhapsody Wonder Today**!!\n" .
               "Sponsor at least **one copy** in each of the **8,123 languages** and **over 4,000 dialects**, and be part of taking God's Word to the nations. Every copy you sponsor is a seed of salvation, healing, and transformation.\n\n" .
               "👉 You can give daily through **your zone**\n" .
               "or alternatively using this link: https://give.rhapsodyofrealities.org/ref/official\n\n" .
               "Together, we are changing the world and preparing the nations for the return of our Lord Jesus Christ!\n\n" .
               "**Rhapathon 2026 Team**";
    }

$res = kc_send_text_message($userId, $msg);
$ok = is_array($res) ? ($res['ok'] ?? false) : false;

$entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'origin' => 'manual_resend',
    'registration_id' => $target['id'],
    'username' => ltrim($kcUsername, '@'),
    'name' => $name,
    'message' => $msg,
    'status' => $ok ? 'sent' : 'failed',
];
if (!$ok) { $entry['error'] = $res['error'] ?? ('HTTP ' . ($res['status'] ?? 0)); }
outbox_storage_append($entry);

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Confirmation sent']);
} else {
    $code = $res['status'] ?? 0;
    echo json_encode(['success' => false, 'message' => ($entry['error'] ?? 'Failed to send'), 'code' => $code]);
}
exit;
?>
