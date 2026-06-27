<?php
/**
 * checkout.php - Checkout Form & Order Placement
 */
$page_title = 'Secure Checkout';
require_once __DIR__ . '/includes/header.php';

// Force authentication
require_login();

$user_id = get_logged_in_user_id();
$cart_items = $_SESSION['cart'] ?? [];

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('shop.php');
}

// Fetch user defaults
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$subtotal = get_cart_subtotal();

// Recalculate discount
$discount = 0.00;
$coupon_code = null;
if (isset($_SESSION['coupon'])) {
    $cp = $_SESSION['coupon'];
    if ($subtotal >= $cp['min_cart_amount']) {
        $coupon_code = $cp['code'];
        if ($cp['type'] === 'percentage') {
            $discount = ($subtotal * $cp['value']) / 100;
        } else {
            $discount = $cp['value'];
        }
        if ($discount > $subtotal) $discount = $subtotal;
    }
}
$grand_total = $subtotal - $discount;

$error_msg = '';

// Handle direct COD/Simulated order placement post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    verify_csrf_request();
    
    $shipping_name = sanitize($_POST['shipping_name'] ?? '');
    $shipping_email = filter_var(trim($_POST['shipping_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $shipping_phone = sanitize($_POST['shipping_phone'] ?? '');
    $shipping_address = sanitize($_POST['shipping_address'] ?? '');
    $payment_method = sanitize($_POST['payment_method'] ?? 'cod');
    
    // Form Validation
    if (empty($shipping_name) || empty($shipping_email) || empty($shipping_phone) || empty($shipping_address)) {
        $error_msg = "Please complete all shipping address fields.";
    } elseif (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please provide a valid shipping email address.";
    } else {
        // Start Order Placement Process
        try {
            $pdo->beginTransaction();
            
            // 1. Verify stock limits before inserting anything
            foreach ($cart_items as $key => $item) {
                $p_stmt = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
                $p_stmt->execute([$item['product_id']]);
                $prod = $p_stmt->fetch();
                
                if (!$prod || $prod['stock'] < $item['quantity']) {
                    throw new Exception("Stock validation failed for '" . ($prod['name'] ?? 'Unknown Item') . "'. Only " . ($prod['stock'] ?? 0) . " items left in stock.");
                }
            }
            
            // 2. Generate unique order number
            $order_number = 'VELO-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('ymdHi');
            
            // 3. Set payment and order status
            $payment_status = 'pending';
            $order_status = 'pending';
            
            // Instant approvals for COD / Direct mocks
            if ($payment_method === 'cod') {
                $payment_status = 'pending';
                $order_status = 'processing';
            } elseif (in_array($payment_method, ['card', 'upi', 'netbanking'])) {
                // Instantly approve simulated local card/upi
                $payment_status = 'completed';
                $order_status = 'processing';
            }
            
            // 4. Insert Order
            $ins_order = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, discount_amount, coupon_code, shipping_name, shipping_email, shipping_phone, shipping_address, payment_method, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins_order->execute([
                $user_id,
                $order_number,
                $grand_total,
                $discount,
                $coupon_code,
                $shipping_name,
                $shipping_email,
                $shipping_phone,
                $shipping_address,
                $payment_method,
                $payment_status,
                $order_status
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // 5. Insert Items & Update Stocks
            $ins_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, size, color, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
            $upd_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            
            foreach ($cart_items as $item) {
                $item_price = $item['discount_price'] !== null ? $item['discount_price'] : $item['price'];
                $ins_item->execute([
                    $order_id,
                    $item['product_id'],
                    $item['size'],
                    $item['color'],
                    $item['quantity'],
                    $item_price
                ]);
                
                $upd_stock->execute([$item['quantity'], $item['product_id']]);
            }
            
            // 6. Insert Payment Log
            $transaction_id = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
            if ($payment_method === 'cod') {
                $transaction_id = 'COD-COLLECT';
            }
            
            $ins_pay = $pdo->prepare("INSERT INTO payments (order_id, transaction_id, payment_method, amount, status) VALUES (?, ?, ?, ?, ?)");
            $ins_pay->execute([
                $order_id,
                $transaction_id,
                $payment_method,
                $grand_total,
                $payment_status === 'completed' ? 'Success' : 'Pending'
            ]);
            
            $pdo->commit();
            
            // Clear cart & coupons
            unset($_SESSION['cart']);
            unset($_SESSION['coupon']);
            
            // Redirect to Success
            redirect("order-confirmation.php?order_num=" . urlencode($order_number));
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Order placement failed: " . $e->getMessage();
        }
    }
}
?>

<!-- Gateway Simulation Overlay Modals -->
<div class="modal-overlay" id="gateway-simulation-modal" style="align-items:center; justify-content:center;">
    <div class="modal-card" style="max-width: 500px; padding: 3rem 2.5rem; text-align:center;">
        <span class="modal-close-btn" onclick="document.getElementById('gateway-simulation-modal').style.display='none';">&times;</span>
        <div id="razorpay-simulation-content" style="display:none;">
            <div style="color:#3395e2; font-size:2.8rem; margin-bottom:1rem;"><i class="fas fa-wallet"></i></div>
            <h3 style="font-size:1.6rem; margin-bottom:0.5rem; font-family:var(--font-sans); color:#111111;">Razorpay Secure Payment</h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Simulating payment checkout popup overlay.</p>
        </div>
        <div id="paypal-simulation-content" style="display:none;">
            <div style="color:#003087; font-size:2.8rem; margin-bottom:1rem;"><i class="fab fa-paypal"></i></div>
            <h3 style="font-size:1.6rem; margin-bottom:0.5rem; font-family:var(--font-sans); color:#111111;">PayPal Log-In Portal</h3>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Simulating sandbox login redirect portal.</p>
        </div>
        
        <div style="font-weight: 600; margin-bottom: 2rem;">
            Order Amount: <span style="color:var(--accent); font-size:1.2rem;"><?php echo format_price($grand_total); ?></span>
        </div>
        
        <div style="display:flex; flex-direction:column; gap:1rem;">
            <button type="button" class="btn btn-solid" id="simulate-success-btn" style="background-color:var(--success); color:#ffffff;">Simulate Success</button>
            <button type="button" class="btn btn-outline" id="simulate-fail-btn" style="background-color:var(--error); border-color:var(--error); color:#ffffff;">Simulate Failure</button>
        </div>
    </div>
</div>

<div class="container section-padding" style="padding-top: 3rem;">
    <!-- Breadcrumbs -->
    <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 3rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; <a href="cart.php">Cart</a> &nbsp;/&nbsp; <span style="color: var(--text);">Checkout</span>
    </div>

    <?php if ($error_msg): ?>
        <div class="form-alert form-alert-danger" style="max-width:900px; margin:0 auto 2rem;"><?php echo e($error_msg); ?></div>
    <?php endif; ?>

    <form id="checkout-form" action="checkout.php" method="POST">
        <?php echo csrf_field(); ?>
        <!-- Action field to identify standard COD submit -->
        <input type="hidden" name="action" id="checkout-form-action" value="place_order">

        <div class="checkout-layout">
            <!-- Left Side: Shipping & Payments -->
            <div>
                <!-- Shipping Box -->
                <div class="checkout-section-card">
                    <h3>Shipping Address</h3>
                    <div style="margin-top:1.5rem;">
                        <div class="form-group">
                            <label for="shipping_name">Full Name</label>
                            <input type="text" id="shipping_name" name="shipping_name" class="form-control" value="<?php echo e($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_email">Email Address</label>
                            <input type="email" id="shipping_email" name="shipping_email" class="form-control" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="shipping_phone">Phone Number</label>
                            <input type="text" id="shipping_phone" name="shipping_phone" class="form-control" value="<?php echo e($user['phone']); ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="shipping_address">Delivery Address</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" rows="4" style="resize:none;" required><?php echo e($user['address']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Selection -->
                <div class="checkout-section-card">
                    <h3>Payment Methods</h3>
                    <div style="margin-top:1.5rem;">
                        <div class="payment-options-grid">
                            <!-- COD -->
                            <label class="payment-method-card selected" id="label-cod">
                                <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentSelection('cod')">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Cash on Delivery</span>
                            </label>
                            <!-- Razorpay -->
                            <label class="payment-method-card" id="label-razorpay">
                                <input type="radio" name="payment_method" value="razorpay" onchange="togglePaymentSelection('razorpay')">
                                <i class="fas fa-wallet"></i>
                                <span>Razorpay Gateway</span>
                            </label>
                            <!-- PayPal -->
                            <label class="payment-method-card" id="label-paypal">
                                <input type="radio" name="payment_method" value="paypal" onchange="togglePaymentSelection('paypal')">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal Portal</span>
                            </label>
                            <!-- Card Mock -->
                            <label class="payment-method-card" id="label-card">
                                <input type="radio" name="payment_method" value="card" onchange="togglePaymentSelection('card')">
                                <i class="far fa-credit-card"></i>
                                <span>Credit/Debit Card</span>
                            </label>
                            <!-- UPI Mock -->
                            <label class="payment-method-card" id="label-upi">
                                <input type="radio" name="payment_method" value="upi" onchange="togglePaymentSelection('upi')">
                                <i class="fas fa-mobile-alt"></i>
                                <span>UPI Payments</span>
                            </label>
                            <!-- Netbanking -->
                            <label class="payment-method-card" id="label-netbanking">
                                <input type="radio" name="payment_method" value="netbanking" onchange="togglePaymentSelection('netbanking')">
                                <i class="fas fa-university"></i>
                                <span>Net Banking</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary -->
            <div>
                <div class="checkout-section-card" style="position:sticky; top: 120px;">
                    <h3>Order Summary</h3>
                    
                    <div style="margin-top:1.5rem;">
                        <table style="width:100%; border-collapse:collapse; font-size:0.9rem; text-align:left; margin-bottom:1.5rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border); color:var(--text-muted); font-size:0.75rem; text-transform:uppercase;">
                                    <th style="padding:0.5rem 0;">Product</th>
                                    <th style="padding:0.5rem 0; text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <?php 
                                        $price = $item['discount_price'] !== null ? $item['discount_price'] : $item['price'];
                                        $total = $price * $item['quantity'];
                                    ?>
                                    <tr style="border-bottom: 1px solid var(--border);">
                                        <td style="padding:1rem 0;">
                                            <span style="font-weight:600;"><?php echo e($item['name']); ?></span>
                                            <span style="font-size:0.75rem; display:block; color:var(--text-muted); margin-top:0.15rem;">
                                                Size: <?php echo e($item['size']); ?> | Color: <?php echo e($item['color']); ?> | Qty: <?php echo $item['quantity']; ?>
                                            </span>
                                        </td>
                                        <td style="padding:1rem 0; text-align:right; font-weight:500;">
                                            <?php echo format_price($total); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="display:flex; flex-direction:column; gap:0.8rem; font-size:0.9rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem; margin-bottom:1.5rem;">
                            <div style="display:flex; justify-content:between; width:100%;">
                                <span style="flex:1;">Subtotal:</span>
                                <span style="font-weight:600;"><?php echo format_price($subtotal); ?></span>
                            </div>
                            <?php if ($discount > 0): ?>
                                <div style="display:flex; justify-content:between; width:100%; color:var(--success);">
                                    <span style="flex:1;">Discount Applied:</span>
                                    <span style="font-weight:600;">-<?php echo format_price($discount); ?></span>
                                </div>
                            <?php endif; ?>
                            <div style="display:flex; justify-content:between; width:100%;">
                                <span style="flex:1;">Shipping Charges:</span>
                                <span style="color:var(--success); font-weight:600;">FREE</span>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:between; font-size:1.25rem; font-weight:700; margin-bottom:2rem;">
                            <span style="flex:1;">Grand Total:</span>
                            <span style="color:var(--accent);"><?php echo format_price($grand_total); ?></span>
                        </div>

                        <button type="submit" class="btn btn-solid" style="width:100%; height:52px; display:inline-flex; align-items:center; justify-content:center;">Place Order</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Include checkout controller scripts -->
<script src="<?php echo SITE_URL; ?>/assets/js/checkout.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
