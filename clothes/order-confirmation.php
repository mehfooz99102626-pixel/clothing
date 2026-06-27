<?php
/**
 * order-confirmation.php - Order Confirmation Receipt
 */
$page_title = 'Order Confirmation';
require_once __DIR__ . '/includes/header.php';

// Force authentication
require_login();

$order_number = sanitize($_GET['order_num'] ?? '');
$user_id = get_logged_in_user_id();

try {
    // Fetch Order details
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
    $stmt->execute([$order_number, $user_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        redirect('index.php');
    }
    
    // Fetch Order Items
    $items_stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image, p.slug 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$order['id']]);
    $order_items = $items_stmt->fetchAll();
    
    // Fetch Payment Transaction
    $pay_stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
    $pay_stmt->execute([$order['id']]);
    $payment = $pay_stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container section-padding" style="padding-top: 3rem;">
    <!-- Print friendly styling layout wraps -->
    <div style="text-align: center; margin-bottom: 4rem;" class="no-print">
        <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1.5rem;"></i>
        <h2>Thank You For Your Order!</h2>
        <p style="color:var(--text-muted); margin-top: 0.5rem;">Your order has been placed successfully. A receipt and confirmation has been dispatched to your email.</p>
        
        <div style="margin-top: 2rem; display:flex; justify-content:center; gap:1.2rem;">
            <a href="order-tracking.php?order_num=<?php echo urlencode($order['order_number']); ?>" class="btn btn-solid">Track Order</a>
            <button onclick="window.print();" class="btn btn-outline"><i class="fas fa-print" style="margin-right: 8px;"></i> Print Invoice</button>
            <a href="shop.php" class="btn btn-outline">Continue Shopping</a>
        </div>
    </div>

    <!-- Invoice Card Layout -->
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div>
                <h1 style="font-family: var(--font-serif); font-size:2.2rem; font-weight:700; letter-spacing:0.05em; color:var(--text);">VÈLO<span>.</span></h1>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.3rem;">100 Fashion Avenue, New York, NY</p>
            </div>
            <div style="text-align: right;">
                <h3 style="font-family: var(--font-sans); text-transform:uppercase; font-size:1.1rem; letter-spacing:0.08em; color:var(--accent);">Invoice Receipt</h3>
                <p style="font-size:0.85rem; font-weight:600; font-family:monospace; margin-top:0.3rem;"><?php echo e($order['order_number']); ?></p>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">Date: <?php echo date('F d, Y H:i', strtotime($order['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Details grid -->
        <div class="invoice-details-grid">
            <div>
                <h5 style="font-family: var(--font-sans); text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:0.75rem;">Billed & Shipped To:</h5>
                <strong style="color:var(--text); font-size:1.05rem;"><?php echo e($order['shipping_name']); ?></strong>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">Phone: <?php echo e($order['shipping_phone']); ?></p>
                <p style="font-size:0.85rem; color:var(--text-muted);">Email: <?php echo e($order['shipping_email']); ?></p>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.5rem; white-space: pre-line; line-height: 1.5;"><?php echo e($order['shipping_address']); ?></p>
            </div>
            <div style="text-align: right;">
                <h5 style="font-family: var(--font-sans); text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:0.75rem;">Payment Method:</h5>
                <strong style="text-transform:uppercase; font-size:0.95rem; color:var(--text);"><?php echo e($order['payment_method']); ?></strong>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">Payment Status: 
                    <span class="status-pill status-<?php echo $order['payment_status'] === 'completed' ? 'completed' : 'pending'; ?>" style="font-size:0.65rem; padding: 0.15rem 0.5rem;">
                        <?php echo e($order['payment_status']); ?>
                    </span>
                </p>
                <?php if ($payment && $payment['transaction_id']): ?>
                    <p style="font-size:0.85rem; color:var(--text-muted); font-family:monospace; margin-top:0.5rem;">Txn ID: <?php echo e($payment['transaction_id']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Table Items -->
        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem; margin-bottom:3rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase;">
                    <th style="padding:0.75rem 0;">Clothing Item</th>
                    <th style="padding:0.75rem 0; text-align:center;">Size</th>
                    <th style="padding:0.75rem 0; text-align:center;">Color</th>
                    <th style="padding:0.75rem 0; text-align:center;">Qty</th>
                    <th style="padding:0.75rem 0; text-align:right;">Price</th>
                    <th style="padding:0.75rem 0; text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                    <?php 
                        $total = $item['price'] * $item['quantity'];
                    ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding:1.25rem 0; font-weight:600;"><?php echo e($item['name']); ?></td>
                        <td style="padding:1.25rem 0; text-align:center;"><?php echo e($item['size']); ?></td>
                        <td style="padding:1.25rem 0; text-align:center;"><?php echo e($item['color']); ?></td>
                        <td style="padding:1.25rem 0; text-align:center;"><?php echo $item['quantity']; ?></td>
                        <td style="padding:1.25rem 0; text-align:right;"><?php echo format_price($item['price']); ?></td>
                        <td style="padding:1.25rem 0; text-align:right; font-weight:500;"><?php echo format_price($total); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totals summary block -->
        <div style="display:flex; justify-content:flex-end;">
            <div style="width: 280px; display:flex; flex-direction:column; gap:0.8rem; font-size:0.9rem;">
                <?php 
                    $order_subtotal = $order['total_amount'] + $order['discount_amount'];
                ?>
                <div style="display:flex; justify-content:between;">
                    <span style="flex:1;">Subtotal:</span>
                    <span style="font-weight:500;"><?php echo format_price($order_subtotal); ?></span>
                </div>
                <?php if ($order['discount_amount'] > 0): ?>
                    <div style="display:flex; justify-content:between; color:var(--success);">
                        <span style="flex:1;">Promo Discount (<?php echo e($order['coupon_code']); ?>):</span>
                        <span style="font-weight:500;">-<?php echo format_price($order['discount_amount']); ?></span>
                    </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:between;">
                    <span style="flex:1;">Delivery Fees:</span>
                    <span style="color:var(--success); font-weight:500;">FREE</span>
                </div>
                <div style="display:flex; justify-content:between; font-size:1.15rem; font-weight:700; border-top:1px solid var(--border); padding-top:0.8rem; margin-top:0.4rem;">
                    <span style="flex:1;">Grand Total:</span>
                    <span style="color:var(--accent);"><?php echo format_price($order['total_amount']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Print stylesheet integration */
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
    .site-header, .site-footer, .no-print, #theme-toggler {
        display: none !important;
    }
    .invoice-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
