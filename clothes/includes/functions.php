<?php
/**
 * functions.php - Global security and helper utilities.
 */

// Helper to escape output for XSS prevention
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Sanitize user inputs for safe processing
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return trim(strip_tags($data));
}

// Generate hidden CSRF input field for forms
function csrf_field() {
    $token = $_SESSION['csrf_token'] ?? '';
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

// Verify CSRF token
function check_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Verify CSRF from POST/GET requests and terminate if invalid
function verify_csrf_request() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!check_csrf($token)) {
        http_response_code(403);
        die("Security Exception: CSRF token validation failed.");
    }
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit;
}

// Auth checks
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_logged_in_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check admin clearance, redirect if failed
function require_admin() {
    if (!is_admin()) {
        redirect(SITE_URL . '/login.php?error=unauthorized');
    }
}

// Check standard user clearance, redirect if failed
function require_login() {
    if (!is_logged_in()) {
        redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

// Format currency
function format_price($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

// Get Cart Item Count
function get_cart_count() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += ($item['quantity'] ?? 0);
        }
    }
    return $count;
}

// Get Cart Subtotal
function get_cart_subtotal() {
    $subtotal = 0.00;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['discount_price']) && $item['discount_price'] !== null ? $item['discount_price'] : $item['price'];
            $subtotal += $price * $item['quantity'];
        }
    }
    return $subtotal;
}

// Get Wishlist Count for Logged-In User
function get_wishlist_count($pdo) {
    if (!is_logged_in()) {
        return 0;
    }
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([get_logged_in_user_id()]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
