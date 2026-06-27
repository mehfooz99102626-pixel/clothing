<?php
/**
 * profile.php - User Account Profile and Order History Dashboard
 */
$page_title = 'My Account';
require_once __DIR__ . '/includes/header.php';

// Force authentication
require_login();

$user_id = get_logged_in_user_id();
$success_msg = '';
$error_msg = '';

// Handle details updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    verify_csrf_request();
    
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    if (empty($name)) {
        $error_msg = "Name cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $user_id]);
            
            // Update session data
            $_SESSION['user_name'] = $name;
            $success_msg = "Profile details updated successfully!";
        } catch (PDOException $e) {
            $error_msg = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Fetch user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Fetch orders history
    $orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $orders_stmt->execute([$user_id]);
    $orders = $orders_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$active_tab = sanitize($_GET['tab'] ?? 'orders');
?>

<div class="container section-padding">
    <div class="profile-layout">
        <!-- Sidebar Navigation -->
        <aside class="profile-nav-sidebar">
            <div class="profile-user-badge">
                <div class="profile-user-avatar">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>
                <h4><?php echo e($user['name']); ?></h4>
                <p style="font-size:0.8rem; color:var(--text-muted); margin-top:0.3rem;"><?php echo e($user['email']); ?></p>
            </div>
            
            <div class="profile-nav-list">
                <a href="profile.php?tab=orders" class="profile-nav-item <?php echo $active_tab === 'orders' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-bag"></i> My Orders
                </a>
                <a href="profile.php?tab=settings" class="profile-nav-item <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i> Profile Settings
                </a>
                <a href="wishlist.php" class="profile-nav-item">
                    <i class="fas fa-heart"></i> My Wishlist
                </a>
                <a href="logout.php" class="profile-nav-item" style="color: var(--error);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="profile-content-card">
            <?php if ($success_msg): ?>
                <div class="form-alert form-alert-success"><?php echo e($success_msg); ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="form-alert form-alert-danger"><?php echo e($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
                <div class="form-alert form-alert-success">Account created successfully! Welcome to VÈLO Fashion.</div>
            <?php endif; ?>
            
            <?php if ($active_tab === 'orders'): ?>
                <h3>My Order History</h3>
                <?php if (empty($orders)): ?>
                    <div style="text-align:center; padding: 3rem 0;">
                        <i class="fas fa-receipt" style="font-size:3rem; color:var(--border); margin-bottom:1rem;"></i>
                        <p style="color:var(--text-muted);">You haven't placed any orders yet.</p>
                        <a href="shop.php" class="btn btn-solid" style="margin-top:1.5rem;">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="orders-history-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight:600;"><?php echo e($order['order_number']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo format_price($order['total_amount']); ?></td>
                                        <td>
                                            <span style="font-size:0.8rem; text-transform:uppercase; font-weight:500;">
                                                <?php echo e($order['payment_method']); ?>
                                            </span>
                                            <span class="status-pill status-<?php echo $order['payment_status'] === 'completed' ? 'completed' : 'failed'; ?>" style="font-size:0.6rem; padding: 0.1rem 0.4rem; margin-left:4px;">
                                                <?php echo e($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-pill status-<?php echo e($order['order_status']); ?>">
                                                <?php echo e($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-tracking.php?order_num=<?php echo urlencode($order['order_number']); ?>" class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.75rem; letter-spacing:0.05em;">
                                                Track / Invoice
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($active_tab === 'settings'): ?>
                <h3>Profile Settings</h3>
                <form action="profile.php?tab=settings" method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="email">Email Address (Cannot change)</label>
                        <input type="email" id="email" class="form-control" value="<?php echo e($user['email']); ?>" disabled style="background-color: var(--surface); color: var(--text-muted); cursor: not-allowed;">
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo e($user['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>" placeholder="Enter phone number">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Default Shipping Address</label>
                        <textarea id="address" name="address" class="form-control" rows="4" placeholder="Enter full address for deliveries" style="resize:none;"><?php echo e($user['address']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-solid" style="margin-top:1rem;">Save Changes</button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
