<?php
/**
 * order-tracking.php - Interactive Order Tracking Page
 */
$page_title = 'Track Order';
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
        // Look up by POST if using a tracking search box
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_num'])) {
            $order_number = sanitize($_POST['order_num']);
            $stmt->execute([$order_number, $user_id]);
            $order = $stmt->fetch();
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container section-padding" style="padding-top: 3rem;">
    <!-- Breadcrumbs -->
    <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 3rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; <a href="profile.php">Account</a> &nbsp;/&nbsp; <span style="color: var(--text);">Track Order</span>
    </div>

    <!-- Tracking Lookup Box -->
    <?php if (!$order): ?>
        <div class="auth-split-screen" style="max-width: 600px; margin: 0 auto; min-height: auto;">
            <div class="auth-form-side" style="padding: 4rem 3rem;">
                <h2>Track Your Order</h2>
                <p class="subtitle" style="margin-bottom: 2rem;">Enter your order invoice number (e.g. VELO-XXXX-XXXX) to retrieve current parcel tracking records.</p>
                
                <form action="order-tracking.php" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="order_num">Invoice Order #</label>
                        <input type="text" id="order_num" name="order_num" class="form-control" placeholder="e.g. VELO-ABCD-123456" required>
                    </div>
                    <button type="submit" class="btn btn-solid" style="width: 100%; margin-top: 1rem;">Track Status</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- Active Order Timeline Progress -->
        <div class="checkout-section-card" style="max-width:900px; margin: 0 auto;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:1.5rem; margin-bottom:2.5rem;">
                <div>
                    <h3 style="border-bottom:none; margin-bottom:0; padding-bottom:0;">Order Tracking</h3>
                    <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.25rem;">Invoice: <strong style="font-family:monospace;"><?php echo e($order['order_number']); ?></strong></p>
                </div>
                <div style="text-align:right;">
                    <span class="status-pill status-<?php echo e($order['order_status']); ?>" style="font-size:0.85rem; padding:0.4rem 1rem;">
                        <?php echo e($order['order_status']); ?>
                    </span>
                    <p style="font-size:0.75rem; color:var(--text-muted); margin-top:0.4rem;">Placed on: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                </div>
            </div>

            <?php if ($order['order_status'] === 'cancelled'): ?>
                <div class="form-alert form-alert-danger" style="text-align:center; padding: 2rem;">
                    <i class="fas fa-times-circle" style="font-size: 2.5rem; margin-bottom: 0.5rem; display:block;"></i>
                    <h4>This Order Has Been Cancelled</h4>
                    <p style="font-size:0.85rem; margin-top:0.3rem;">If you have any questions or require refunds, please contact VÈLO customer care.</p>
                </div>
            <?php else: ?>
                <!-- Interactive horizontal milestone timeline -->
                <div class="timeline-container" style="position:relative; display:flex; justify-content:space-between; margin: 3rem 0; padding:0 2rem;">
                    <!-- Line connector background -->
                    <div style="position:absolute; top: 22px; left: 4rem; right: 4rem; height: 3px; background-color: var(--border); z-index: 1;"></div>
                    
                    <!-- Line connector progress -->
                    <?php 
                        $pct = 0;
                        if ($order['order_status'] === 'processing') $pct = 33;
                        elseif ($order['order_status'] === 'shipped') $pct = 66;
                        elseif ($order['order_status'] === 'delivered') $pct = 100;
                    ?>
                    <div style="position:absolute; top: 22px; left: 4rem; width: calc(<?php echo $pct; ?>% - 4rem); height: 3px; background-color: var(--accent); z-index: 2; transition: all 1s ease;"></div>

                    <!-- Milestone 1: Placed -->
                    <div class="milestone-node" style="position:relative; z-index: 3; text-align:center; display:flex; flex-direction:column; align-items:center; width:80px;">
                        <div class="node-circle active" style="width:44px; height:44px; border-radius:50%; background-color:var(--accent); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow:0 0 0 6px var(--background);">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; margin-top:0.8rem; display:block;">Placed</span>
                    </div>

                    <!-- Milestone 2: Processing -->
                    <?php $node2 = in_array($order['order_status'], ['processing', 'shipped', 'delivered']); ?>
                    <div class="milestone-node" style="position:relative; z-index: 3; text-align:center; display:flex; flex-direction:column; align-items:center; width:80px;">
                        <div class="node-circle <?php echo $node2 ? 'active' : ''; ?>" style="width:44px; height:44px; border-radius:50%; background-color:<?php echo $node2 ? 'var(--accent)' : 'var(--border)'; ?>; color:<?php echo $node2 ? '#ffffff' : 'var(--text-muted)'; ?>; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow:0 0 0 6px var(--background); transition: var(--transition);">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; margin-top:0.8rem; display:block; color:<?php echo $node2 ? 'inherit' : 'var(--text-muted)'; ?>;">Processing</span>
                    </div>

                    <!-- Milestone 3: Shipped -->
                    <?php $node3 = in_array($order['order_status'], ['shipped', 'delivered']); ?>
                    <div class="milestone-node" style="position:relative; z-index: 3; text-align:center; display:flex; flex-direction:column; align-items:center; width:80px;">
                        <div class="node-circle <?php echo $node3 ? 'active' : ''; ?>" style="width:44px; height:44px; border-radius:50%; background-color:<?php echo $node3 ? 'var(--accent)' : 'var(--border)'; ?>; color:<?php echo $node3 ? '#ffffff' : 'var(--text-muted)'; ?>; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow:0 0 0 6px var(--background); transition: var(--transition);">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; margin-top:0.8rem; display:block; color:<?php echo $node3 ? 'inherit' : 'var(--text-muted)'; ?>;">Shipped</span>
                    </div>

                    <!-- Milestone 4: Delivered -->
                    <?php $node4 = ($order['order_status'] === 'delivered'); ?>
                    <div class="milestone-node" style="position:relative; z-index: 3; text-align:center; display:flex; flex-direction:column; align-items:center; width:80px;">
                        <div class="node-circle <?php echo $node4 ? 'active' : ''; ?>" style="width:44px; height:44px; border-radius:50%; background-color:<?php echo $node4 ? 'var(--success)' : 'var(--border)'; ?>; color:<?php echo $node4 ? '#ffffff' : 'var(--text-muted)'; ?>; display:flex; align-items:center; justify-content:center; font-size:1.1rem; box-shadow:0 0 0 6px var(--background); transition: var(--transition);">
                            <i class="fas fa-home"></i>
                        </div>
                        <span style="font-size:0.75rem; font-weight:600; text-transform:uppercase; margin-top:0.8rem; display:block; color:<?php echo $node4 ? 'var(--success)' : 'var(--text-muted)'; ?>;">Delivered</span>
                    </div>
                </div>

                <div style="background-color:var(--surface); border:1px solid var(--border); padding: 1.5rem; font-size: 0.9rem; line-height: 1.7; margin-bottom: 2rem;">
                    <?php if ($order['order_status'] === 'pending'): ?>
                        📌 <strong>Tracking Note:</strong> Your order has been registered and is awaiting staff catalog confirmation. Stock validation completed successfully.
                    <?php elseif ($order['order_status'] === 'processing'): ?>
                        📦 <strong>Tracking Note:</strong> Our warehouse team is currently picking, folding, and packaging your fashion apparel. Label generation is in progress.
                    <?php elseif ($order['order_status'] === 'shipped'): ?>
                        🚚 <strong>Tracking Note:</strong> Your parcel has been dispatched from VÈLO HQ and is in transit with our logistics carrier. Estimated delivery: <strong>2 - 3 business days</strong>.
                    <?php elseif ($order['order_status'] === 'delivered'): ?>
                        🎉 <strong>Tracking Note:</strong> Package delivered successfully! Thank you for styling with VÈLO. We would love to hear your feedback on the product review panel!
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Delivery Summary -->
            <div style="border-top:1px solid var(--border); padding-top:2rem; display:grid; grid-template-columns:1fr 1fr; gap:2.5rem; font-size:0.9rem;">
                <div>
                    <h5 style="text-transform:uppercase; color:var(--text-muted); font-size:0.75rem; letter-spacing:0.05em; margin-bottom:0.5rem;">Shipping Address:</h5>
                    <strong><?php echo e($order['shipping_name']); ?></strong>
                    <p style="color:var(--text-muted); margin-top:0.2rem;"><?php echo e($order['shipping_phone']); ?></p>
                    <p style="color:var(--text-muted); margin-top:0.25rem; white-space:pre-line;"><?php echo e($order['shipping_address']); ?></p>
                </div>
                <div>
                    <h5 style="text-transform:uppercase; color:var(--text-muted); font-size:0.75rem; letter-spacing:0.05em; margin-bottom:0.5rem;">Billing Breakdown:</h5>
                    <div style="display:flex; flex-direction:column; gap:0.4rem; color:var(--text-muted);">
                        <div style="display:flex; justify-content:between; width:100%;">
                            <span style="flex:1;">Grand Order Total:</span>
                            <span style="font-weight:600; color:var(--text);"><?php echo format_price($order['total_amount']); ?></span>
                        </div>
                        <div style="display:flex; justify-content:between; width:100%;">
                            <span style="flex:1;">Payment Method:</span>
                            <span style="font-weight:600; text-transform:uppercase; color:var(--text);"><?php echo e($order['payment_method']); ?></span>
                        </div>
                        <div style="display:flex; justify-content:between; width:100%;">
                            <span style="flex:1;">Billing Status:</span>
                            <span class="status-pill status-<?php echo $order['payment_status'] === 'completed' ? 'completed' : 'pending'; ?>" style="font-size:0.6rem; padding: 0.1rem 0.4rem; display:inline-block; font-weight:600;">
                                <?php echo e($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top:3rem; text-align:center;">
                <a href="order-confirmation.php?order_num=<?php echo urlencode($order['order_number']); ?>" class="btn btn-outline" style="font-size:0.8rem; padding:0.6rem 1.8rem;"><i class="fas fa-file-invoice" style="margin-right:8px;"></i>View Invoice Receipt</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
