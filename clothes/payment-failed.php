<?php
/**
 * payment-failed.php - Payment Gateway Error Screen
 */
$page_title = 'Payment Failed';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding" style="text-align: center; padding-top: 5rem;">
    <div style="max-width: 600px; margin: 0 auto; background-color: var(--surface-card); border: 1px solid var(--border); padding: 4rem 3rem; box-shadow: var(--shadow);">
        <i class="fas fa-times-circle" style="font-size: 4rem; color: var(--error); margin-bottom: 1.5rem;"></i>
        <h2>Transaction Failed</h2>
        <p style="color: var(--text-muted); margin-top: 0.5rem; line-height: 1.6;">
            We were unable to process your payment transaction. This may be due to insufficient card funds, bank authentication timeouts, or network interruptions.
        </p>
        
        <div style="background-color: var(--surface); border-left: 4px solid var(--error); padding: 1rem 1.5rem; font-size: 0.85rem; color: var(--text-muted); text-align: left; margin: 2rem 0; line-height: 1.5;">
            <strong>Possible steps to resolve this:</strong>
            <ul style="padding-left: 1.25rem; margin-top: 0.5rem; list-style: disc;">
                <li>Verify your credit card credentials and CVV code.</li>
                <li>Ensure Net Banking or UPI OTP authentication is completed within the timeout limit.</li>
                <li>Try selecting **Cash on Delivery (COD)** or another card method at the checkout screen.</li>
            </ul>
        </div>
        
        <div style="display:flex; justify-content:center; gap:1.2rem; margin-top: 2rem;">
            <a href="checkout.php" class="btn btn-solid">Return to Checkout</a>
            <a href="cart.php" class="btn btn-outline">Review Shopping Bag</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
