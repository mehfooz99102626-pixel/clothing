<?php
/**
 * cart.php - Shopping Cart Details Page
 */
$page_title = 'Shopping Cart';
require_once __DIR__ . '/includes/header.php';

$coupon_error = '';
$coupon_success = '';

// Handle Coupon application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_coupon') {
    verify_csrf_request();
    
    $code = sanitize($_POST['coupon_code'] ?? '');
    
    if (empty($code)) {
        $coupon_error = "Please enter a coupon code.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expiry_date >= CURDATE()");
            $stmt->execute([$code]);
            $coupon = $stmt->fetch();
            
            if ($coupon) {
                $subtotal = get_cart_subtotal();
                
                if ($subtotal < $coupon['min_cart_amount']) {
                    $coupon_error = "Min order total to use this coupon is " . format_price($coupon['min_cart_amount']);
                } else {
                    $_SESSION['coupon'] = [
                        'code' => $coupon['code'],
                        'type' => $coupon['type'],
                        'value' => (float)$coupon['value'],
                        'min_cart_amount' => (float)$coupon['min_cart_amount']
                    ];
                    $coupon_success = "Coupon '" . e($code) . "' applied successfully!";
                }
            } else {
                $coupon_error = "Invalid or expired coupon code.";
                unset($_SESSION['coupon']); // Clear existing
            }
        } catch (PDOException $e) {
            $coupon_error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle Clear Coupon
if (isset($_GET['clear_coupon']) && $_GET['clear_coupon'] === '1') {
    unset($_SESSION['coupon']);
    redirect('cart.php');
}

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = get_cart_subtotal();

// Calculate Discount
$discount = 0.00;
if (isset($_SESSION['coupon'])) {
    $cp = $_SESSION['coupon'];
    
    // Validate if min amount is still satisfied (in case quantity was reduced)
    if ($subtotal >= $cp['min_cart_amount']) {
        if ($cp['type'] === 'percentage') {
            $discount = ($subtotal * $cp['value']) / 100;
        } else {
            $discount = $cp['value'];
        }
        // Ensure discount is not greater than subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }
    } else {
        // Remove coupon as condition is no longer met
        unset($_SESSION['coupon']);
        $coupon_error = "Coupon removed: Minimum order requirements not met.";
    }
}

$grand_total = $subtotal - $discount;
?>

<div class="container section-padding" style="padding-top: 3rem;">
    <div class="section-header">
        <h2>Your Shopping Bag</h2>
        <p>Review items in your cart and apply any promotional codes before checking out.</p>
    </div>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 4rem 0;">
            <i class="fas fa-shopping-bag" style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem; display: block;"></i>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">Your shopping cart is empty.</p>
            <a href="shop.php" class="btn btn-solid">Continue Shopping</a>
        </div>
    <?php else: ?>
        <!-- Cart Table -->
        <div class="cart-table-wrapper">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $key => $item): ?>
                        <?php 
                            $price = $item['discount_price'] !== null ? $item['discount_price'] : $item['price'];
                            $item_total = $price * $item['quantity'];
                        ?>
                        <tr id="cart-row-<?php echo e($key); ?>">
                            <td>
                                <div class="cart-product-item">
                                    <img src="<?php echo e($item['image']); ?>" class="cart-product-img" alt="<?php echo e($item['name']); ?>">
                                    <div class="cart-product-info">
                                        <h5><a href="product.php?slug=<?php echo e($item['slug']); ?>"><?php echo e($item['name']); ?></a></h5>
                                        <p style="margin-top: 0.2rem;">
                                            <span><strong>Size:</strong> <?php echo e($item['size']); ?></span> | 
                                            <span><strong>Color:</strong> <?php echo e($item['color']); ?></span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($item['discount_price'] !== null): ?>
                                    <span class="price-discount"><?php echo format_price($item['discount_price']); ?></span>
                                    <span class="price-original" style="font-size:0.8rem; display:block;"><?php echo format_price($item['price']); ?></span>
                                <?php else: ?>
                                    <span><?php echo format_price($item['price']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="quantity-adjuster" style="height: 40px;">
                                    <!-- AJAX triggers -->
                                    <button type="button" class="btn-qty-change" data-key="<?php echo e($key); ?>" data-action="minus" style="width:30px;">-</button>
                                    <input type="number" id="qty-<?php echo e($key); ?>" value="<?php echo $item['quantity']; ?>" min="1" max="10" readonly style="width:35px; font-size:0.85rem;">
                                    <button type="button" class="btn-qty-change" data-key="<?php echo e($key); ?>" data-action="plus" style="width:30px;">+</button>
                                </div>
                            </td>
                            <td class="cart-row-total" id="row-total-<?php echo e($key); ?>" style="font-weight: 600;">
                                <?php echo format_price($item_total); ?>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-remove-item btn-cart-remove" data-key="<?php echo e($key); ?>">
                                    <i class="far fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary & Coupon area -->
        <div class="cart-summary-layout">
            <!-- Coupon Form -->
            <div class="coupon-section">
                <h4>Promo / Coupon Codes</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top:0.4rem;">Got a promotional coupon code? Enter it below to redeem your discounts.</p>
                
                <?php if ($coupon_success): ?>
                    <div class="form-alert form-alert-success" style="margin: 1rem 0;"><?php echo e($coupon_success); ?></div>
                <?php endif; ?>
                <?php if ($coupon_error): ?>
                    <div class="form-alert form-alert-danger" style="margin: 1rem 0;"><?php echo e($coupon_error); ?></div>
                <?php endif; ?>
                
                <form action="cart.php" method="POST" class="coupon-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="apply_coupon">
                    <input type="text" name="coupon_code" placeholder="e.g. SAVE10" value="<?php echo isset($_SESSION['coupon']) ? e($_SESSION['coupon']['code']) : ''; ?>" <?php echo isset($_SESSION['coupon']) ? 'disabled style="background-color: var(--border);"' : ''; ?> required>
                    
                    <?php if (isset($_SESSION['coupon'])): ?>
                        <a href="cart.php?clear_coupon=1" class="btn btn-outline" style="background-color:var(--error); color:#ffffff; border-color:var(--error); padding: 0.8rem 1.5rem;">Remove</a>
                    <?php else: ?>
                        <button type="submit" class="btn btn-solid">Redeem</button>
                    <?php endif; ?>
                </form>

                <div style="margin-top:1.5rem; font-size:0.75rem; color:var(--text-muted);">
                    <p style="margin-bottom:0.3rem;">💡 <strong>Available Coupon Codes for Testing:</strong></p>
                    <ul style="padding-left:1rem; list-style:disc;">
                        <li><strong>WELCOME15</strong>: 15% off any order value</li>
                        <li><strong>SAVE10</strong>: 10% off orders above $50.00</li>
                        <li><strong>FLAT50</strong>: Flat $50.00 off orders above $200.00</li>
                    </ul>
                </div>
            </div>

            <!-- Totals Box -->
            <div class="cart-totals-box">
                <h3>Cart Totals</h3>
                
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span id="cart-summary-subtotal" style="font-weight: 500;"><?php echo format_price($subtotal); ?></span>
                </div>
                
                <?php if (isset($_SESSION['coupon'])): ?>
                    <div class="total-row" style="color: var(--success); font-weight:500;">
                        <span>Discount (Code: <?php echo e($_SESSION['coupon']['code']); ?>):</span>
                        <span id="cart-summary-discount">-<?php echo format_price($discount); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="total-row">
                    <span>Shipping Charges:</span>
                    <span style="color:var(--success); font-weight: 500;">FREE DELIVERY</span>
                </div>
                
                <div class="total-row grand-total">
                    <span>Grand Total:</span>
                    <span id="cart-summary-grandtotal"><?php echo format_price($grand_total); ?></span>
                </div>
                
                <a href="checkout.php" class="btn btn-solid" style="width: 100%; text-align:center; margin-top:1.5rem; height:52px; display:inline-flex; align-items:center; justify-content:center;">Proceed to Checkout</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Adjust Quantities dynamically via AJAX
    document.querySelectorAll('.btn-qty-change').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const key = button.getAttribute('data-key');
            const direction = button.getAttribute('data-action');
            const input = document.getElementById(`qty-${key}`);
            let val = parseInt(input.value);
            
            if (direction === 'plus') {
                val += 1;
            } else {
                if (val > 1) val -= 1;
                else return;
            }
            
            // Extract product id, size, and color from the key (format: id-size-color)
            const parts = key.split('-');
            const productId = parts[0];
            const size = parts[1];
            const color = parts[2];
            
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('product_id', productId);
            formData.append('size', size);
            formData.append('color', color);
            formData.append('quantity', val);
            formData.append('csrf_token', csrfToken);
            
            fetch('api/cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = val;
                    // Reload page to recompute full discounts easily
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to update cart');
                }
            });
        });
    });

    // Remove Cart Item dynamically
    document.querySelectorAll('.btn-cart-remove').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const key = button.getAttribute('data-key');
            const parts = key.split('-');
            const productId = parts[0];
            const size = parts[1];
            const color = parts[2];
            
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('product_id', productId);
            formData.append('size', size);
            formData.append('color', color);
            formData.append('csrf_token', csrfToken);
            
            fetch('api/cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`cart-row-${key}`).remove();
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to remove product');
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
