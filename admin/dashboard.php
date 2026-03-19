<?php
// Strengthen session cookie settings before starting session
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

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('X-XSS-Protection: 1; mode=block');

// Load admin auth configuration
$ADMIN_PASSWORD_HASH = null;
$auth_config = __DIR__ . '/../secure_data/admin_auth.php';
if (is_file($auth_config)) {
    require $auth_config; // should set $ADMIN_PASSWORD_HASH
}

// Generate CSRF token for admin login
if (empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

// Handle logout
if (isset($_GET['logout'])) {
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
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php')), '/');
    if ($scriptDir === '') $scriptDir = '/';
    header('Location: ' . $origin . $scriptDir . '/dashboard.php');
    exit;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Handle login form
    if (isset($_POST['admin_password'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['admin_csrf_token'] ?? '', $_POST['csrf_token'])) {
            $login_error = 'Invalid security token';
        } else {
            if (!empty($ADMIN_PASSWORD_HASH) && password_verify($_POST['admin_password'], $ADMIN_PASSWORD_HASH)) {
                $_SESSION['admin_logged_in'] = true;
                session_regenerate_id(true);
                // Rotate CSRF token after successful login
                $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $login_error = empty($ADMIN_PASSWORD_HASH)
                    ? 'Admin password not configured. Please set $ADMIN_PASSWORD_HASH.'
                    : 'Invalid password';
            }
        }
    }

    // Show login form if not logged in
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Admin Login - Rhapathon</title>
            <link rel="icon" type="image/png" href="../assets/videos/images/logo-rhapathon.png">
            <link rel="apple-touch-icon" href="../assets/videos/images/logo-rhapathon.png">
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                primary: '#000080',
                                accent: '#D4AF37',
                                light: '#F5F5F5',
                                border: '#e2e8f0',
                                white: '#FFFFFF'
                            },
                            boxShadow: { 'inner-lg': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)' }
                        }
                    }
                }
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

// Load registration data
$registrations = registration_storage_all();

// Sort registrations by timestamp (newest first)
usort($registrations, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Load KingsChat outbox statuses to reflect confirmation message state
$kc_status_by_reg = outbox_storage_latest_status_by_registration();

// Compute base prefix for links (handles subdirectory deployments)
$__script = $_SERVER['SCRIPT_NAME'] ?? '/admin/dashboard.php';
$__adminDir = rtrim(str_replace('\\', '/', dirname($__script)), '/');
$__baseRaw = rtrim(str_replace('\\', '/', dirname($__adminDir)), '/');
$BASE_PREFIX = ($__baseRaw === '' || $__baseRaw === '/') ? '' : $__baseRaw;

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="rhapathon_registrations_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID', 'Timestamp', 'Title', 'First Name', 'Last Name', 'Email', 'Phone',
        'KingsChat', 'Church/Network Info', 'Selected Days', 'Sessions', 'Onsite Participation'
    ]);
    
        // CSV data
        foreach ($registrations as $reg) {
            // Consolidate church/network information
            $churchNetworkInfo = '';
            $network = $reg['church_info']['network'] ?? '';
            $zone = $reg['church_info']['zone'] ?? '';
            $group = $reg['church_info']['group'] ?? '';
            $church = $reg['church_info']['church'] ?? '';
            $manualNetwork = $reg['church_info']['manual_network'] ?? '';

            if (!empty($network)) {
                $churchNetworkInfo = 'Network: ' . $network;
                if (!empty($manualNetwork)) {
                    $churchNetworkInfo .= ' (' . $manualNetwork . ')';
                }
            } elseif (!empty($zone) && !empty($group) && !empty($church)) {
                $churchNetworkInfo = $zone . ' - ' . $group . ' - ' . $church;
            } else {
                $churchNetworkInfo = 'Not specified';
            }

            // Consolidate session information
            $sessionInfo = '';
            $sessions = $reg['event_info']['sessions'] ?? [];
            if (!empty($sessions)) {
                $sessionParts = [];
                foreach ($sessions as $day => $daySessions) {
                    if (!empty($daySessions)) {
                        $sessionParts[] = ucfirst($day) . ': ' . implode('/', $daySessions);
                    }
                }
                $sessionInfo = implode('; ', $sessionParts);
            }

            fputcsv($output, [
                $reg['id'],
                $reg['timestamp'],
                $reg['personal_info']['title'],
                $reg['personal_info']['first_name'],
                $reg['personal_info']['last_name'],
                $reg['personal_info']['email'],
                $reg['personal_info']['phone'],
                $reg['personal_info']['kingschat_username'],
                $churchNetworkInfo,
                implode(', ', $reg['event_info']['selected_days']),
                $sessionInfo,
                $reg['event_info']['onsite_participation']
            ]);
        }
    
    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rhapathon Registrations</title>
    <link rel="icon" type="image/png" href="../assets/videos/images/logo-rhapathon.png">
    <link rel="apple-touch-icon" href="../assets/videos/images/logo-rhapathon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
      /* Page Loader Styles (prefixed) */
      #pageLoaderOverlay { transition: opacity .3s ease; }
      .loader-container {
        --uib-size: 35px;
        --uib-color: black;
        --uib-speed: .9s;
        --uib-stroke: 3.5px;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: var(--uib-size);
        width: var(--uib-size);
      }
      .loader-line {
        position: absolute;
        top: calc(50% - var(--uib-stroke) / 2);
        left: 0;
        height: var(--uib-stroke);
        width: 100%;
        border-radius: calc(var(--uib-stroke) / 2);
        background-color: var(--uib-color);
        animation: loader-rotate var(--uib-speed) ease-in-out infinite;
        transition: background-color 0.3s ease;
      }
      .loader-line:nth-child(1) { animation-delay: calc(var(--uib-speed) * -0.375); }
      .loader-line:nth-child(2) { animation-delay: calc(var(--uib-speed) * -0.375); opacity: 0.8; }
      .loader-line:nth-child(3) { animation-delay: calc(var(--uib-speed) * -0.3); opacity: 0.6; }
      .loader-line:nth-child(4) { animation-delay: calc(var(--uib-speed) * -0.225); opacity: 0.4; }
      .loader-line:nth-child(5) { animation-delay: calc(var(--uib-speed) * -0.15); opacity: 0.2; }
      .loader-line:nth-child(6) { animation-delay: calc(var(--uib-speed) * -0.075); opacity: 0.1; }
      @keyframes loader-rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(180deg); } }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#000080',
                        accent: '#D4AF37',
                        light: '#F5F5F5',
                        border: '#e2e8f0',
                        cta: '#FF0000'
                    },
                    boxShadow: { 'inner-lg': 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)' }
                }
            }
        }
    </script>
</head>
<body class="bg-light min-h-screen">
    <!-- Page Loader Overlay -->
    <div id="pageLoaderOverlay" class="fixed inset-0 z-[999999] flex items-center justify-center bg-white">
      <div class="loader-container">
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
        <div class="loader-line"></div>
      </div>
    </div>
    <meta name="admin-csrf" content="<?php echo htmlspecialchars($_SESSION['admin_csrf_token']); ?>">

    <!-- Top Nav -->
    <nav class="sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-border">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <img src="../assets/videos/images/logo-rhapathon.png" alt="Rhapathon" class="h-9 w-auto object-contain">
            <div class="border-l pl-3">
              <div class="text-sm text-gray-500">Admin</div>
              <div class="text-lg font-semibold text-primary">Dashboard</div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="<?php echo htmlspecialchars($BASE_PREFIX . '/admin/notifications.php'); ?>" class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-md border border-border text-primary hover:bg-gray-50">
              <i class="fa-solid fa-bell"></i><span>Notifications</span>
            </a>
            <a href="?export=csv" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-accent text-white hover:bg-yellow-600">
              <i class="fa-solid fa-file-export"></i><span class="hidden sm:inline">Export CSV</span>
            </a>
            <a href="?logout=1" class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-red-600 text-white hover:bg-red-700">
              <i class="fa-solid fa-right-from-bracket"></i><span class="hidden sm:inline">Logout</span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Layout -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
      <!-- Sidebar -->
      <aside class="lg:col-span-3 space-y-6">
        <div class="bg-white rounded-xl shadow-md border border-border p-4">
          <nav class="space-y-1">
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/5 text-primary font-medium" href="#">
              <i class="fa-solid fa-gauge"></i>
              <span>Overview</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-700" href="<?php echo htmlspecialchars($BASE_PREFIX . '/admin/notifications.php'); ?>">
              <i class="fa-solid fa-bell"></i>
              <span>Notifications</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 text-gray-700" href="?export=csv">
              <i class="fa-solid fa-file-arrow-down"></i>
              <span>Export CSV</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 text-red-600" href="?logout=1">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Logout</span>
            </a>
          </nav>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-border p-4">
          <h3 class="text-sm font-semibold text-primary mb-3">Quick Stats</h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Total</span>
              <span class="font-semibold text-primary"><?php echo count($registrations); ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Onsite</span>
              <span class="font-semibold text-green-600"><?php echo count(array_filter($registrations, function($reg){return ($reg['event_info']['onsite_participation'] ?? '') === 'yes';})); ?></span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-gray-600">Latest</span>
              <span class="font-medium text-gray-800"><?php echo !empty($registrations) ? date('M j, Y g:i A', strtotime($registrations[0]['timestamp'])) : '—'; ?></span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Content -->
      <section class="lg:col-span-9 space-y-6">
        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white rounded-xl shadow-md p-5 border border-border">
            <div class="flex items-center gap-4">
              <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center"><i class="fa-solid fa-users"></i></div>
              <div>
                <div class="text-sm text-gray-600">Total Registrations</div>
                <div class="text-3xl font-bold text-primary/90 leading-tight"><?php echo count($registrations); ?></div>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-md p-5 border border-border">
            <div class="flex items-center gap-4">
              <div class="h-10 w-10 rounded-full bg-green-100 text-green-700 flex items-center justify-center"><i class="fa-solid fa-person-walking"></i></div>
              <div>
                <div class="text-sm text-gray-600">Onsite Participants</div>
                <div class="text-3xl font-bold text-green-600 leading-tight"><?php echo count(array_filter($registrations, function($reg){return ($reg['event_info']['onsite_participation'] ?? '') === 'yes';})); ?></div>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-xl shadow-md p-5 border border-border">
            <div class="flex items-center gap-4">
              <div class="h-10 w-10 rounded-full bg-accent/10 text-accent flex items-center justify-center"><i class="fa-solid fa-clock"></i></div>
              <div>
                <div class="text-sm text-gray-600">Latest Registration</div>
                <div class="text-sm font-medium text-gray-800"><?php echo !empty($registrations) ? date('M j, Y g:i A', strtotime($registrations[0]['timestamp'])) : 'None'; ?></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-primary">Recent Registrations</h2>
          <div class="relative w-64">
            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input id="searchInput" type="text" class="w-full pl-10 pr-3 py-2 border border-border rounded-lg focus:outline-none focus:ring-2 focus:ring-accent" placeholder="Search name, email, phone">
          </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-md border border-border overflow-hidden">
          <div class="overflow-x-auto max-h-[65vh]">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-light sticky top-0 z-[1]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Church/Network</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Selected Days</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Sessions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Participation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Registered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-primary uppercase tracking-wider">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="tableBody" class="bg-white divide-y divide-border">
                        <?php foreach ($registrations as $reg): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">
                                    <?php echo htmlspecialchars($reg['personal_info']['title'] . ' ' . 
                                                                 $reg['personal_info']['first_name'] . ' ' . 
                                                                 $reg['personal_info']['last_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: <?php echo htmlspecialchars(substr($reg['id'], -8)); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($reg['personal_info']['email']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($reg['personal_info']['phone']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $network = $reg['church_info']['network'] ?? '';
                                    $zone = $reg['church_info']['zone'] ?? '';
                                    $group = $reg['church_info']['group'] ?? '';
                                    $church = $reg['church_info']['church'] ?? '';

                                    if (!empty($network)) {
                                        echo '<div class="text-sm text-gray-900">Network: ' . htmlspecialchars($network) . '</div>';
                                        if (!empty($reg['church_info']['manual_network'])) {
                                            echo '<div class="text-sm text-gray-500">(' . htmlspecialchars($reg['church_info']['manual_network']) . ')</div>';
                                        }
                                    } elseif (!empty($zone) && !empty($group) && !empty($church)) {
                                        echo '<div class="text-sm text-gray-900">' . htmlspecialchars($zone) . '</div>';
                                        echo '<div class="text-sm text-gray-500">' . htmlspecialchars($group . ' - ' . $church) . '</div>';
                                    } else {
                                        echo '<div class="text-sm text-gray-500">Not specified</div>';
                                    }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars(implode(', ', array_map('ucfirst', $reg['event_info']['selected_days']))); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php
                                    $sessions = $reg['event_info']['sessions'] ?? [];
                                    if (!empty($sessions)) {
                                        $sessionParts = [];
                                        foreach ($sessions as $day => $daySessions) {
                                            if (!empty($daySessions)) {
                                                $sessionParts[] = htmlspecialchars(ucfirst($day) . ': ' . implode('/', array_map('ucfirst', $daySessions)));
                                            }
                                        }
                                        echo implode('<br>', $sessionParts);
                                    } else {
                                        echo '<span class="text-gray-500">—</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php echo ($reg['event_info']['onsite_participation'] ?? '') === 'yes' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ($reg['event_info']['onsite_participation'] ?? '') === 'yes' ? 'Onsite' : 'Online'; ?>
                                </span>
                                <?php if (($reg['event_info']['onsite_participation'] ?? '') === 'no'): ?>
                                <?php $online = $reg['event_info']['online_participation'] ?? ''; ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ml-1 <?php echo $online === 'yes' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    Online: <?php echo $online === 'yes' ? 'Yes' : ($online === 'no' ? 'No' : '—'); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M j, Y g:i A', strtotime($reg['timestamp'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php $rowId = 'details_' . htmlspecialchars(preg_replace('/[^a-zA-Z0-9_\-]/', '', substr($reg['id'], -12))); ?>
                                <button 
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 border border-border rounded-md text-primary hover:bg-gray-50 transition-colors text-sm"
                                    data-target="<?php echo $rowId; ?>"
                                    onclick="toggleDetails(this)">
                                    <i class="fa-solid fa-chevron-down"></i>
                                    <span>View</span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                    $rid = $reg['id'];
                                    $kcUser = trim($reg['personal_info']['kingschat_username'] ?? '');
                                    $kcStatus = $kc_status_by_reg[$rid] ?? 'none';
                                ?>
                                <?php if ($kcUser === ''): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">No KingsChat</span>
                                <?php else: ?>
                                    <?php if ($kcStatus === 'sent'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmation Sent</span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Not Sent</span>
                                        <button type="button"
                                                class="ml-2 inline-flex items-center gap-1.5 text-primary hover:text-[#00006b]"
                                                data-reg-id="<?php echo htmlspecialchars($rid); ?>"
                                                onclick="resendConfirmation(this)">
                                            <i class="fa-solid fa-paper-plane"></i>
                                            <span>Send Confirmation</span>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="mx-2 text-gray-300">|</span>
                                <button 
                                    type="button"
                                    class="inline-flex items-center gap-1.5 text-red-600 hover:text-red-700"
                                    data-reg-id="<?php echo htmlspecialchars($reg['id']); ?>"
                                    onclick="deleteRegistration(this)">
                                    <i class="fa-regular fa-trash-can"></i>
                                    <span>Delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr id="<?php echo $rowId; ?>" class="hidden bg-gray-50">
                            <td colspan="8" class="px-6 py-4">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div class="bg-white border border-border rounded-lg p-4">
                                        <h4 class="font-semibold text-primary mb-2">Personal Info</h4>
                                        <p><span class="font-medium">Title:</span> <?php echo htmlspecialchars($reg['personal_info']['title']); ?></p>
                                        <p><span class="font-medium">First Name:</span> <?php echo htmlspecialchars($reg['personal_info']['first_name']); ?></p>
                                        <p><span class="font-medium">Last Name:</span> <?php echo htmlspecialchars($reg['personal_info']['last_name']); ?></p>
                                        <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($reg['personal_info']['email']); ?></p>
                                        <p><span class="font-medium">Phone:</span> <?php echo htmlspecialchars($reg['personal_info']['phone']); ?></p>
                                        <p><span class="font-medium">KingsChat:</span> <?php echo htmlspecialchars($reg['personal_info']['kingschat_username'] ?? ''); ?></p>
                                    </div>
                                    <div class="bg-white border border-border rounded-lg p-4">
                                        <h4 class="font-semibold text-primary mb-2">Church/Network Info</h4>
                                        <?php
                                            $network = $reg['church_info']['network'] ?? '';
                                            $manualNetwork = $reg['church_info']['manual_network'] ?? '';
                                            $zone = $reg['church_info']['zone'] ?? '';
                                            $group = $reg['church_info']['group'] ?? '';
                                            $church = $reg['church_info']['church'] ?? '';

                                            if (!empty($network)) {
                                                echo '<p><span class="font-medium">Network:</span> ' . htmlspecialchars($network) . '</p>';
                                                if (!empty($manualNetwork)) {
                                                    echo '<p><span class="font-medium">Custom Network:</span> ' . htmlspecialchars($manualNetwork) . '</p>';
                                                }
                                            } elseif (!empty($zone) && !empty($group) && !empty($church)) {
                                                echo '<p><span class="font-medium">Zone:</span> ' . htmlspecialchars($zone) . '</p>';
                                                echo '<p><span class="font-medium">Group:</span> ' . htmlspecialchars($group) . '</p>';
                                                echo '<p><span class="font-medium">Church:</span> ' . htmlspecialchars($church) . '</p>';
                                            } else {
                                                echo '<p><span class="font-medium">Information:</span> Not provided</p>';
                                            }
                                        ?>
                                    </div>
                                    <div class="bg-white border border-border rounded-lg p-4">
                                        <h4 class="font-semibold text-primary mb-2">Event</h4>
                                        <?php
                                          $daysSel = $reg['event_info']['selected_days'] ?? [];
                                          $daysSel = is_array($daysSel) ? $daysSel : [];
                                          $onsite = strtolower(trim($reg['event_info']['onsite_participation'] ?? '')) === 'yes';
                                          $online = $reg['event_info']['online_participation'] ?? '';
                                        ?>
                                        <div class="mb-3">
                                            <div class="font-medium text-gray-700 mb-1">Selected Days</div>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach ($daysSel as $d): $label = ucfirst((string)$d); ?>
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-primary/5 text-primary text-xs border border-primary/20"><?php echo htmlspecialchars($label); ?></span>
                                                <?php endforeach; if (empty($daysSel)): ?>
                                                    <span class="text-gray-500 text-sm">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="font-medium text-gray-700 mb-1">Selected Sessions</div>
                                            <div class="space-y-1">
                                                <?php
                                                $sessions = $reg['event_info']['sessions'] ?? [];
                                                $hasSessions = false;
                                                foreach ($sessions as $day => $daySessions):
                                                    if (!empty($daySessions)):
                                                        $hasSessions = true;
                                                        $sessionLabels = array_map('ucfirst', $daySessions);
                                                ?>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-sm font-medium text-gray-600"><?php echo ucfirst($day); ?>:</span>
                                                        <div class="flex gap-1">
                                                            <?php foreach ($sessionLabels as $session): ?>
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-accent/10 text-accent text-xs"><?php echo htmlspecialchars($session); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php
                                                    endif;
                                                endforeach;
                                                if (!$hasSessions):
                                                ?>
                                                    <span class="text-gray-500 text-sm">No sessions selected</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mb-1">
                                            <div class="font-medium text-gray-700 mb-1">Participation</div>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs <?php echo $onsite ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-blue-100 text-blue-700 border border-blue-200'; ?>"><?php echo $onsite ? 'Onsite' : 'Online'; ?></span>
                                            <?php if (!$onsite): ?>
                                                <span class="ml-2 text-gray-600 text-sm">Online Participation: <?php echo htmlspecialchars($online === '' ? '—' : $online); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mb-1">
                                            <div class="font-medium text-gray-700 mb-1">Language</div>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-purple-100 text-purple-700 text-xs border border-purple-200"><?php echo htmlspecialchars(strtoupper($reg['language_preference'] ?? 'EN')); ?></span>
                                        </div>
                                    </div>
                                    <?php $feedback = $reg['additional_info']['feedback'] ?? ''; if (strlen(trim($feedback)) > 0): ?>
                                    <div class="bg-white border border-border rounded-lg p-4">
                                        <h4 class="font-semibold text-primary mb-2">Feedback</h4>
                                        <p class="whitespace-pre-wrap break-words text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($feedback)); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">
                                No registrations found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
            </table>
          </div>
        </div>
      </section>
    </main>

    <script>
    // Fade out loader when page fully loaded
    window.addEventListener('load', function() {
        const overlay = document.getElementById('pageLoaderOverlay');
        if (overlay) { overlay.style.opacity = '0'; setTimeout(() => overlay.remove(), 320); }
        // Hook up table filtering
        const input = document.getElementById('searchInput');
        if (input) {
            input.addEventListener('input', function() {
                const q = this.value.trim().toLowerCase();
                const rows = document.querySelectorAll('#tableBody > tr');
                let visible = 0;
                rows.forEach((tr) => {
                    // Skip detail rows
                    if (tr.id && tr.id.startsWith('details_')) return;
                    const text = tr.textContent.toLowerCase();
                    const show = q === '' || text.indexOf(q) !== -1;
                    tr.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
            });
        }
    });
    function toggleDetails(button) {
        const targetId = button.getAttribute('data-target');
        const row = document.getElementById(targetId);
        if (!row) return;
        const isHidden = row.classList.contains('hidden');
        const label = button.querySelector('span');
        const icon = button.querySelector('i');
        if (isHidden) {
            row.classList.remove('hidden');
            if (label) label.textContent = 'Close';
            if (icon) { icon.classList.remove('fa-chevron-down'); icon.classList.add('fa-chevron-up'); }
        } else {
            row.classList.add('hidden');
            if (label) label.textContent = 'View';
            if (icon) { icon.classList.remove('fa-chevron-up'); icon.classList.add('fa-chevron-down'); }
        }
    }

    function deleteRegistration(button) {
        const id = button.getAttribute('data-reg-id');
        const csrf = document.querySelector('meta[name="admin-csrf"]').getAttribute('content');
        if (!id || !csrf) return;
        Swal.fire({
            title: 'Delete registration?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
        }).then(result => {
            if (!result.isConfirmed) return;
            // Build base path to handle subdirectory deployments (e.g., /rhapaton/admin)
            const adminBase = (function () {
                const p = window.location.pathname;
                const idx = p.indexOf('/admin');
                return idx >= 0 ? p.slice(0, idx + 6) : '/admin';
            })();
            const url = adminBase.replace(/\/$/, '') + '/delete_registration.php';
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrf)
            })
            .then(async r => {
                const text = await r.text();
                try { return JSON.parse(text); }
                catch (e) {
                    throw new Error(`Request failed (${r.status}). Response: ${text.substring(0,180)}`);
                }
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: 'The registration has been removed.',
                        confirmButtonColor: '#000080'
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: data.message || 'Unknown error',
                        confirmButtonColor: '#000080'
                    });
                }
            })
            .catch(err => {
                console.error('Delete request error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network error',
                    text: 'There was a problem deleting the registration. ' + (err.message || ''),
                    confirmButtonColor: '#000080'
                });
            });
        });
    }

    async function resendConfirmation(button) {
        const id = button.getAttribute('data-reg-id');
        const csrf = document.querySelector('meta[name="admin-csrf"]').getAttribute('content');
        if (!id || !csrf) return;
        // Build base path to handle subdirectory deployments (e.g., /rhapaton/admin)
        const adminBase = (function () {
            const p = window.location.pathname;
            const idx = p.indexOf('/admin');
            return idx >= 0 ? p.slice(0, idx + 6) : '/admin';
        })();
        const url = adminBase.replace(/\/$/, '') + '/resend_confirmation.php';
        button.disabled = true;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id) + '&csrf_token=' + encodeURIComponent(csrf)
            });
            const text = await res.text();
            let data;
            try { data = JSON.parse(text); }
            catch (e) {
                throw new Error(`Request failed (${res.status}). Response: ${text.substring(0,180)}`);
            }
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sent', text: 'Confirmation delivered.', confirmButtonColor: '#000080' })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Unknown error', confirmButtonColor: '#000080' });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Network error', text: String(e), confirmButtonColor: '#000080' });
        } finally {
            button.disabled = false;
        }
    }
    </script>
</body>
</html> 
