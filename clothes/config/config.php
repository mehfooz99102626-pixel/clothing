<?php
/**
 * config.php - Main Application Configuration and Security Headers
 * Initializes standard session variables, defines paths, and sets configuration constants.
 */

// Enable secure sessions
if (session_status() === PHP_SESSION_NONE) {
    // Help block cookie harvesting via client side scripts (XSS mitigation)
    ini_set('session.cookie_httponly', 1);
    // If running on HTTPS, uncomment the line below:
    // ini_set('session.cookie_secure', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Global Site Configurations
define('SITE_NAME', 'VÈLO FASHION');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$dir = str_replace('\\', '/', dirname($script_name));
// Handle directory trimming for subfolders when config is loaded from config/ or includes/ or admin/
$dir = preg_replace('/(\/(config|includes|admin|api))$/i', '', $dir);
if ($dir === '/') {
    $dir = '';
}
define('SITE_URL', $protocol . $host . $dir);
define('CURRENCY_SYMBOL', '$');

// Mock payment gateway credentials
define('RAZORPAY_KEY_ID', 'rzp_test_vEl0fAsh10nM0ckId');
define('PAYPAL_CLIENT_ID', 'AeM0ckPaYpAlClIeNtId_12345_67890');

// Initialize CSRF protection token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize shopping cart session if it does not exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Initialize wishlist session if user is not logged in (to allow guest browsing, optional - database preferred)
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}
