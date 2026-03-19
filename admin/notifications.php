<?php
// Admin notifications page: send KingsChat messages to registrants

// Session + secure headers
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
require_once __DIR__ . '/../kingschat/helpers.php';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-XSS-Protection: 1; mode=block');

// Admin auth config
$ADMIN_PASSWORD_HASH = null;
$auth_config = __DIR__ . '/../secure_data/admin_auth.php';
if (is_file($auth_config)) {
    require $auth_config; // defines $ADMIN_PASSWORD_HASH
}

// CSRF token
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Logout handler
if (isset($_GET['logout'])) {
    // First logout from KingsChat if authenticated
    if (kc_is_authenticated()) {
        // Clear KingsChat session variables
        unset($_SESSION['kc_access_token']);
        unset($_SESSION['kc_refresh_token']);
        unset($_SESSION['kc_token_expires_at']);

        // Clear KingsChat config file token
        $kc_config_file = __DIR__ . '/../secure_data/kc_config.json';
        if (file_exists($kc_config_file)) {
            $config = json_decode(file_get_contents($kc_config_file), true);
            if (is_array($config)) {
                // Remove tokens but keep other config
                unset($config['access_token']);
                unset($config['expires_at']);
                // Keep refresh_token and sender_user_id for future use
                file_put_contents($kc_config_file, json_encode($config, JSON_PRETTY_PRINT));
            }
        }
    }

    // Clear admin session
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();

    // Build absolute URL with correct subdirectory and proxy headers
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ($is_https ? 'https' : 'http');
    $port = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
        $fp = (string)$_SERVER['HTTP_X_FORWARDED_PORT'];
        if (($proto === 'http' && $fp !== '80') || ($proto === 'https' && $fp !== '443')) {
            $port = ':' . $fp;
        }
    }
    $origin = $proto . '://' . $host . $port;
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/notifications.php')), '/');
    if ($scriptDir === '') $scriptDir = '/';
    header('Location: ' . $origin . $scriptDir . '/notifications.php');
    exit;
}

// Handle login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if (isset($_POST['admin_password'])) {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
            $login_error = 'Invalid security token';
        } else if (!empty($ADMIN_PASSWORD_HASH) && password_verify($_POST['admin_password'], $ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in'] = true;
            session_regenerate_id(true);
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $login_error = empty($ADMIN_PASSWORD_HASH)
                ? 'Admin password not configured. Please set $ADMIN_PASSWORD_HASH.'
                : 'Invalid password';
        }
        // If login successful, redirect to avoid form resubmission (PRG pattern)
        if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            $target = $_SERVER['REQUEST_URI'] ?? '/admin/notifications.php';
            // Strip query string to ensure a clean GET
            $target = strtok($target, '?');
            header('Location: ' . $target, true, 303);
            exit;
        }
    }

    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Notifications</title>
            <link rel="icon" type="image/png" href="../assets/videos/images/logo-rhapathon.png">
            <link rel="apple-touch-icon" href="../assets/videos/images/logo-rhapathon.png">
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
            <script>
              tailwind.config = { theme: { extend: { colors: { primary: '#000080', accent: '#D4AF37', light: '#F5F5F5', border: '#e2e8f0', white: '#FFFFFF' }, boxShadow: { 'inner-lg': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)' } } } };
            </script>
        </head>
        <body class="min-h-screen bg-gradient-to-br from-white to-light flex items-center justify-center p-4">
            <div class="w-full max-w-md">
                <div class="bg-white/90 backdrop-blur-sm border border-border rounded-2xl shadow-xl overflow-hidden">
                    <div class="px-6 pt-8 pb-2 text-center">
                        <img src="../assets/videos/images/logo-rhapathon.png" alt="Rhapathon" class="mx-auto h-14 w-auto object-contain">
                        <h1 class="mt-4 text-2xl font-bold text-primary">Admin Portal</h1>
                        <p class="mt-1 text-sm text-gray-600">Sign in to continue</p>
                    </div>
                    <div class="px-6 pb-8 pt-4">
                        <?php if (isset($login_error)): ?>
                            <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700">
                                <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-600"></i>
                                <div><?php echo htmlspecialchars($login_error); ?></div>
                            </div>
                        <?php endif; ?>
                        <form method="POST" autocomplete="off" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
                            <div>
                                <label for="admin_password" class="block text-sm font-medium text-primary mb-1">Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>
                                    <input type="password" id="admin_password" name="admin_password" required class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent shadow-inner-lg">
                                </div>
                            </div>
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 bg-primary hover:bg-[#00006b] text-white font-medium py-2.5 px-4 rounded-lg transition-all">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Sign In
                            </button>
                        </form>
                    </div>
                </div>
                <p class="text-center text-xs text-gray-500 mt-4">© <?php echo date('Y'); ?> Rhapathon Admin</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Load registrations
$data_file = __DIR__ . '/../secure_data/registrations.json';
$registrations = [];
if (is_file($data_file)) {
    $json = file_get_contents($data_file);
    $registrations = json_decode($json, true) ?: [];
}

// Extract entries with KingsChat usernames and additional info for filtering
$targets = [];
$zones = [];
foreach ($registrations as $reg) {
    $u = trim($reg['personal_info']['kingschat_username'] ?? '');
    if ($u !== '') {
        // Normalize username - ensure it starts with @
        if (!str_starts_with($u, '@')) {
            $u = '@' . $u;
        }
        $title = trim($reg['personal_info']['title'] ?? '');
        $first = trim($reg['personal_info']['first_name'] ?? '');
        $last = trim($reg['personal_info']['last_name'] ?? '');
        $nameCore = trim($first . ' ' . $last);
        $name = trim(($title !== '' ? $title . ' ' : '') . $nameCore);
        $zone = trim($reg['church_info']['zone'] ?? '');
        $onsite = strtolower(trim($reg['event_info']['onsite_participation'] ?? '')) === 'yes';

        $targets[] = [
            'username' => $u, // Now normalized with @ prefix
            'name' => $name,
            'zone' => $zone,
            'onsite' => $onsite,
            'registration_id' => $reg['id'] ?? '',
        ];

        // Collect unique zones for filtering
        if ($zone !== '' && !in_array($zone, $zones)) {
            $zones[] = $zone;
        }
    }
}
sort($zones);

$kc_logged_in = kc_is_authenticated();

// Compute base prefix for links (handles subdirectory deployments)
$__script = $_SERVER['SCRIPT_NAME'] ?? '/admin/notifications.php';
$__adminDir = rtrim(str_replace('\\', '/', dirname($__script)), '/');
$__baseRaw = rtrim(str_replace('\\', '/', dirname($__adminDir)), '/');
$BASE_PREFIX = ($__baseRaw === '' || $__baseRaw === '/') ? '' : $__baseRaw;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - Rhapathon</title>
    <link rel="icon" type="image/png" href="../assets/videos/images/logo-rhapathon.png">
    <link rel="apple-touch-icon" href="../assets/videos/images/logo-rhapathon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <script>
      tailwind.config = { theme: { extend: { colors: { primary: '#000080', accent: '#D4AF37', light: '#F5F5F5', border: '#e2e8f0' }, boxShadow: { 'inner-lg': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)' } } } };
    </script>
    <meta name="admin-csrf" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">
</head>
<body class="bg-light min-h-screen">
    <!-- Top Nav -->
    <nav class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-border">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img src="../assets/videos/images/logo-rhapathon.png" alt="Rhapathon" class="h-9 w-auto object-contain">
            <div class="border-l pl-3">
              <div class="text-sm text-gray-500">Admin</div>
              <div class="text-lg font-semibold text-primary">Notifications</div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="<?php echo htmlspecialchars($BASE_PREFIX . '/admin/dashboard.php'); ?>" class="inline-flex items-center gap-2 px-3 py-2 rounded-md border border-border text-primary hover:bg-gray-50">
              <i class="fa-solid fa-gauge"></i><span class="hidden sm:inline">Dashboard</span>
            </a>
            <a href="?logout=1" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">
              <i class="fa-solid fa-right-from-bracket"></i><span class="hidden sm:inline">Logout</span>
            </a>
          </div>
        </div>
      </div>
    </nav>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 bg-white rounded-xl shadow-md border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-primary">Compose Message</h2>
                    <div class="text-sm text-gray-500">Recipients: <?php echo count($targets); ?></div>
                </div>
                <?php if (!$kc_logged_in): ?>
                    <?php
                        $script = $_SERVER['SCRIPT_NAME'] ?? '/admin/notifications.php';
                        $adminDir = rtrim(str_replace('\\', '/', dirname($script)), '/'); // /{base}/admin
                        $basePathRaw = rtrim(str_replace('\\', '/', dirname($adminDir)), '/'); // '' or '/{base}'
                        $basePrefix = ($basePathRaw === '' || $basePathRaw === '/') ? '' : $basePathRaw;
                        $returnTo = $basePrefix . '/admin/notifications.php';
                        $kcLogin = $basePrefix . '/kingschat/?return_to=' . rawurlencode($returnTo);
                    ?>
                    <div class="mb-4 p-3 rounded border border-yellow-300 bg-yellow-50 text-yellow-800 flex items-start gap-2">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <span>KingsChat not connected. Please <a class="underline" href="<?php echo htmlspecialchars($kcLogin); ?>" target="_blank" rel="noopener">login to KingsChat</a> first, then return here.</span>
                    </div>
                <?php endif; ?>
                <form id="notifyForm" class="space-y-4">
                    <div class="flex items-start gap-3">
                      <label class="block text-sm font-medium text-primary pt-2" for="message">Message</label>
                      <div class="flex-1">
                        <textarea id="message" name="message" rows="8" required class="w-full border border-border rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-accent shadow-inner-lg" placeholder="Hi {name}, your custom message...">Dear {name},

Thank you for registering for 𝐑𝐡𝐚𝐩𝐚𝐭𝐡𝐨𝐧 𝐰𝐢𝐭𝐡 𝐏𝐚𝐬𝐭𝐨𝐫 𝐂𝐡𝐫𝐢𝐬 — 𝟮𝟬𝟮𝟲 𝐄𝐝𝐢𝐭𝐢𝐨𝐧, holding from 𝐌𝐨𝐧𝐝𝐚𝐲 𝟰𝐭𝐡 𝐭𝐨 𝐅𝐫𝐢𝐝𝐚𝐲 𝟴𝐭𝐡 𝐌𝐚𝐲, 𝟮𝟬𝟮𝟲. We are delighted to have you on board.</textarea>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-600">
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded border border-border"><i class="fa-solid fa-tag"></i> {name}</span>
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded border border-border"><i class="fa-solid fa-tag"></i> {username}</span>
                          <span class="inline-flex items-center gap-1 px-2 py-1 rounded border border-border"><i class="fa-solid fa-tag"></i> {zone}</span>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center justify-end mt-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-white hover:bg-[#00006b] disabled:opacity-50" <?php echo $kc_logged_in ? '' : 'disabled';?>>
                          <i class="fa-solid fa-paper-plane"></i>
                          <span>Send to All</span>
                        </button>
                    </div>
                </form>
                <div id="progressBox" class="hidden mt-4 text-sm">
                    <div class="mb-2 flex items-center gap-2"><span id="progressText" class="font-medium text-primary"></span></div>
                    <div class="w-full bg-gray-200 rounded-full h-2"><div id="progressBar" class="bg-accent h-2 rounded-full" style="width:0%"></div></div>
                    <div id="progressLog" class="mt-3 max-h-48 overflow-auto border border-border rounded p-2 text-gray-700 bg-gray-50"></div>
                </div>
            </div>

            <div class="lg:col-span-4 bg-white rounded-xl shadow-md border border-border p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-primary">Recipients</h2>
                    <span class="text-sm text-gray-600"><?php echo count($targets); ?> total</span>
                </div>

                <!-- Bulk Selection Options -->
                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-medium text-primary mb-2">Bulk Selection</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="selectAllBtn" class="px-3 py-1 text-xs bg-primary text-white rounded hover:bg-[#00006b]">Select All</button>
                        <button type="button" id="selectNoneBtn" class="px-3 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600">Select None</button>
                        <button type="button" id="selectOnsiteBtn" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">Onsite Only</button>
                        <button type="button" id="selectOnlineBtn" class="px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">Online Only</button>
                    </div>
                    <?php if (!empty($zones)): ?>
                    <div class="mt-2">
                        <select id="zoneFilter" class="text-xs border border-border rounded px-2 py-1">
                            <option value="">All Zones</option>
                            <?php foreach ($zones as $zone): ?>
                            <option value="<?php echo htmlspecialchars($zone); ?>"><?php echo htmlspecialchars($zone); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="selectZoneBtn" class="ml-2 px-3 py-1 text-xs bg-purple-600 text-white rounded hover:bg-purple-700">Select Zone</button>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($targets)): ?>
                    <div class="text-gray-600">No KingsChat usernames found in registrations.</div>
                <?php else: ?>
                    <form id="recipientForm" class="space-y-2">
                        <div class="max-h-[50vh] overflow-auto border border-border rounded p-2">
                            <?php foreach ($targets as $index => $t): ?>
                                <label class="flex items-center gap-2 py-2 hover:bg-gray-50 px-2 rounded cursor-pointer">
                                    <input type="checkbox" name="recipients[]" value="<?php echo htmlspecialchars($t['username']); ?>"
                                           class="recipient-checkbox w-4 h-4 text-primary border-border rounded focus:ring-primary"
                                           data-zone="<?php echo htmlspecialchars($t['zone']); ?>"
                                           data-onsite="<?php echo $t['onsite'] ? 'true' : 'false'; ?>"
                                           data-name="<?php echo htmlspecialchars($t['name']); ?>">
                                    <div class="flex-1">
                                        <span class="font-medium text-sm"><?php echo htmlspecialchars($t['name']); ?></span>
                                        <span class="text-gray-600 text-sm">(@<?php echo htmlspecialchars(ltrim($t['username'], '@')); ?>)</span>
                                        <div class="text-xs text-gray-500">
                                            <?php echo htmlspecialchars($t['zone']); ?> • <?php echo $t['onsite'] ? 'Onsite' : 'Online'; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="pt-2 border-t border-border">
                            <div class="text-sm text-gray-600 mb-2">
                                <span id="selectedCount">0</span> selected
                            </div>
                            <button type="button" id="sendSelectedBtn"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-accent text-white hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                    <?php echo $kc_logged_in ? '' : 'disabled';?>>
                                <i class="fa-solid fa-paper-plane"></i>
                                <span>Send to Selected</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
const form = document.getElementById('notifyForm');
const recipientForm = document.getElementById('recipientForm');
const csrf = document.querySelector('meta[name="admin-csrf"]').getAttribute('content');
const progressBox = document.getElementById('progressBox');
const progressText = document.getElementById('progressText');
const progressBar = document.getElementById('progressBar');
const progressLog = document.getElementById('progressLog');

function logLine(text) {
  const p = document.createElement('div');
  p.textContent = text;
  progressLog.appendChild(p);
  progressLog.scrollTop = progressLog.scrollHeight;
}

function updateSelectedCount() {
  const checkboxes = document.querySelectorAll('.recipient-checkbox:checked');
  const selectedCount = document.getElementById('selectedCount');
  const sendSelectedBtn = document.getElementById('sendSelectedBtn');

  selectedCount.textContent = checkboxes.length;
  sendSelectedBtn.disabled = checkboxes.length === 0;
}

// Bulk selection handlers
document.getElementById('selectAllBtn')?.addEventListener('click', () => {
  document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = true);
  updateSelectedCount();
});

document.getElementById('selectNoneBtn')?.addEventListener('click', () => {
  document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
  updateSelectedCount();
});

document.getElementById('selectOnsiteBtn')?.addEventListener('click', () => {
  document.querySelectorAll('.recipient-checkbox').forEach(cb => {
    cb.checked = cb.dataset.onsite === 'true';
  });
  updateSelectedCount();
});

document.getElementById('selectOnlineBtn')?.addEventListener('click', () => {
  document.querySelectorAll('.recipient-checkbox').forEach(cb => {
    cb.checked = cb.dataset.onsite === 'false';
  });
  updateSelectedCount();
});

document.getElementById('selectZoneBtn')?.addEventListener('click', () => {
  const zoneFilter = document.getElementById('zoneFilter');
  const selectedZone = zoneFilter.value;
  if (selectedZone) {
    document.querySelectorAll('.recipient-checkbox').forEach(cb => {
      cb.checked = cb.dataset.zone === selectedZone;
    });
    updateSelectedCount();
  }
});

// Update count when checkboxes change
document.addEventListener('change', (e) => {
  if (e.target.classList.contains('recipient-checkbox')) {
    updateSelectedCount();
  }
});

// Initialize count
updateSelectedCount();

async function sendNotifications(recipients, isSendAll = false) {
  const progressBox = document.getElementById('progressBox');
  const progressText = document.getElementById('progressText');
  const progressBar = document.getElementById('progressBar');
  const progressLog = document.getElementById('progressLog');

  // Disable buttons
  const sendAllBtn = form?.querySelector('button[type="submit"]');
  const sendSelectedBtn = document.getElementById('sendSelectedBtn');
  if (sendAllBtn) sendAllBtn.disabled = true;
  if (sendSelectedBtn) sendSelectedBtn.disabled = true;

  progressBox.classList.remove('hidden');
  progressLog.innerHTML = '';
  progressText.textContent = 'Starting...';
  progressBar.style.width = '0%';

  const fd = new FormData();
  fd.append('message', document.getElementById('message').value);
  fd.append('csrf_token', csrf);

  if (!isSendAll && recipients && recipients.length > 0) {
    recipients.forEach(recipient => fd.append('recipients[]', recipient));
  }

  try {
    // Build admin base path to support subdirectory deployments
    const p = window.location.pathname;
    const idx = p.indexOf('/admin');
    const adminBase = (idx >= 0 ? p.slice(0, idx + 6) : '/admin').replace(/\/$/, '');
    const url = adminBase + '/send_notifications.php';
    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-CSRF-Token': csrf } });
    const reader = res.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';
    while (true) {
      const {done, value} = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, {stream: true});
      const parts = buffer.split('\n');
      buffer = parts.pop();
      for (const line of parts) {
        if (!line) continue;
        try {
          const evt = JSON.parse(line);
          if (evt.type === 'progress') {
            progressText.textContent = `${evt.sent}/${evt.total} sent`;
            progressBar.style.width = `${Math.round((evt.sent/evt.total)*100)}%`;
            if (evt.message) logLine(evt.message);
          } else if (evt.type === 'done') {
            progressText.textContent = `Done: ${evt.success} sent, ${evt.failed} failed`;
            progressBar.style.width = '100%';
            if (evt.detail) logLine(evt.detail);
          } else if (evt.type === 'error') {
            logLine('Error: ' + (evt.error || 'unknown'));
          }
        } catch {}
      }
    }
    Swal.fire({icon:'success', title:'Completed', text: progressText.textContent, confirmButtonColor:'#000080'});
  } catch (err) {
    console.error(err);
    Swal.fire({icon:'error', title:'Failed', text: String(err), confirmButtonColor:'#000080'});
  } finally {
    // Re-enable buttons
    if (sendAllBtn) sendAllBtn.disabled = false;
    if (sendSelectedBtn) sendSelectedBtn.disabled = false;
  }
}

// Send to all handler
form?.addEventListener('submit', async (e) => {
  e.preventDefault();
  await sendNotifications(null, true);
});

// Send to selected handler
document.getElementById('sendSelectedBtn')?.addEventListener('click', async () => {
  const selectedRecipients = Array.from(document.querySelectorAll('.recipient-checkbox:checked')).map(cb => cb.value);
  if (selectedRecipients.length === 0) {
    Swal.fire({icon:'warning', title:'No Recipients Selected', text:'Please select at least one recipient.', confirmButtonColor:'#000080'});
    return;
  }
  await sendNotifications(selectedRecipients, false);
});
</script>
</body>
</html>
