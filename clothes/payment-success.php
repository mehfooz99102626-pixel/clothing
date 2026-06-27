<?php
/**
 * payment-success.php - Intermediate payment success handler
 * Forwards customers securely to their invoice and receipt page.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$order_num = sanitize($_GET['order_num'] ?? '');

if (!empty($order_num)) {
    // Redirect to the finalized order confirmation / invoice receipt page
    redirect(SITE_URL . "/order-confirmation.php?order_num=" . urlencode($order_num));
} else {
    // Fallback directly to customer profile dashboard
    redirect(SITE_URL . "/profile.php");
}
exit;
