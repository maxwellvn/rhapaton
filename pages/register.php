<?php
// Harden session cookies for the form session
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

// Security headers for the form page
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Also set CSRF cookie for double-submit fallback (not HttpOnly by design)
$is_https = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
setcookie('csrf_token', $_SESSION['csrf_token'], 0, '/', '', $is_https, false);

$page_title = 'Register - Rhapaton';

// Include header
include_once '../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-bold text-primary">
                Create your account
            </h2>
            <p class="mt-2 text-center text-sm text-accent">
                Join Rhapaton today
            </p>
        </div>
        
        <form class="mt-8 space-y-6" action="#" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-primary">
                        Username
                    </label>
                    <input id="username" 
                           name="username" 
                           type="text" 
                           required 
                           class="mt-1 block w-full px-3 py-2 border border-border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-primary">
                        Email address
                    </label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           class="mt-1 block w-full px-3 py-2 border border-border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-primary">
                        Password
                    </label>
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           class="mt-1 block w-full px-3 py-2 border border-border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-primary">
                        Confirm Password
                    </label>
                    <input id="confirm_password" 
                           name="confirm_password" 
                           type="password" 
                           required 
                           class="mt-1 block w-full px-3 py-2 border border-border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                </div>
            </div>
            
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-colors duration-200">
                    Register
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-sm text-accent">
                    Already have an account? 
                    <a href="login" class="font-medium text-primary hover:text-secondary">
                        Sign in
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<?php
// Include footer
include_once '../includes/footer.php';
?> 
