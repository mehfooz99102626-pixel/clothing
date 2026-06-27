<?php
/**
 * admin/orders.php - Orders Management logs and Status editor
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Force admin credentials
require_admin();

$success_msg = '';
$error_msg = '';

// Handle Status Updates POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order') {
    verify_csrf_request();
    
    $order_id = (int)($_POST['order_id'] ?? 0);
    $order_status = sanitize($_POST['order_status'] ?? 'pending');
    $payment_status = sanitize($_POST['payment_status'] ?? 'pending');
    
    if ($order_id <= 0) {
        $error_msg = "Invalid Order ID.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Update Order Statuses
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$order_status, $payment_status, $order_id]);
            
            // 2. Update Payment Log Status accordingly
            $pay_stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ?");
            $pay_stmt->execute([
                $payment_status === 'completed' ? 'Success' : ($payment_status === 'refunded' ? 'Refunded' : 'Pending'), 
                $order_id
            ]);
            
            $pdo->commit();
            $success_msg = "Order status updated successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_msg = "Failed to update order details: " . $e->getMessage();
        }
    }
}

// Capture filters
$filter_status = sanitize($_GET['status'] ?? '');

// Build Query
$query = "SELECT o.*, u.name as customer_name FROM orders o LEFT JOIN users u ON o.user_id = u.id";
$params = [];

if ($filter_status) {
    $query .= " WHERE o.order_status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}

// Fetch single order details if edit_id is set
$selected_order = null;
$selected_items = [];
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    try {
        $ord_stmt = $pdo->prepare("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $ord_stmt->execute([$edit_id]);
        $selected_order = $ord_stmt->fetch();
        
        if ($selected_order) {
            $items_stmt = $pdo->prepare("
                SELECT oi.*, p.name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$selected_order['id']]);
            $selected_items = $items_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $error_msg = "Error fetching order details: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            VÈLO<span>.</span> Admin
        </div>
        <nav class="admin-menu">
            <a href="dashboard.php" class="admin-menu-item">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="products.php" class="admin-menu-item">
                <i class="fas fa-tshirt"></i> Products CRUD
            </a>
            <a href="categories.php" class="admin-menu-item">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="orders.php" class="admin-menu-item active">
                <i class="fas fa-shopping-bag"></i> Orders Log
            </a>
            <a href="customers.php" class="admin-menu-item">
                <i class="fas fa-users"></i> Customers
            </a>
            <hr style="border:none; border-top: 1px solid #27272c; margin: 1.5rem 0;">
            <a href="../index.php" class="admin-menu-item" style="color: var(--accent);">
                <i class="fas fa-external-link-alt"></i> Visit Store
            </a>
            <a href="../logout.php" class="admin-menu-item" style="color: var(--error);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <div class="admin-topbar">
            <h2>Order Transaction Logs</h2>
            <div style="display:flex; gap:0.6rem;">
                <a href="orders.php" class="btn btn-outline <?php echo !$filter_status ? 'btn-solid' : ''; ?>" style="padding:0.4rem 1rem; font-size:0.75rem;">All</a>
                <a href="orders.php?status=pending" class="btn btn-outline <?php echo $filter_status === 'pending' ? 'btn-solid' : ''; ?>" style="padding:0.4rem 1rem; font-size:0.75rem;">Pending</a>
                <a href="orders.php?status=processing" class="btn btn-outline <?php echo $filter_status === 'processing' ? 'btn-solid' : ''; ?>" style="padding:0.4rem 1rem; font-size:0.75rem;">Processing</a>
                <a href="orders.php?status=shipped" class="btn btn-outline <?php echo $filter_status === 'shipped' ? 'btn-solid' : ''; ?>" style="padding:0.4rem 1rem; font-size:0.75rem;">Shipped</a>
                <a href="orders.php?status=delivered" class="btn btn-outline <?php echo $filter_status === 'delivered' ? 'btn-solid' : ''; ?>" style="padding:0.4rem 1rem; font-size:0.75rem;">Delivered</a>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="form-alert form-alert-success"><?php echo e($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="form-alert form-alert-danger"><?php echo e($error_msg); ?></div>
        <?php endif; ?>

        <!-- Split Layout if an order is selected for detail updates -->
        <div style="display:grid; grid-template-columns: <?php echo $selected_order ? '1.1fr 0.9fr' : '1fr'; ?>; gap: 2rem; margin-bottom: 3rem; align-items:start;">
            <!-- Order Lists Table -->
            <div class="checkout-section-card" style="margin-bottom:0;">
                <h3>Transactions Log (<?php echo count($orders); ?> orders)</h3>
                
                <div style="overflow-x:auto; margin-top:1.5rem;">
                    <table class="orders-history-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer Name</th>
                                <th>Grand Total</th>
                                <th>Method</th>
                                <th>Delivery Status</th>
                                <th>Payment Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:3rem; color:var(--text-muted);">No orders found matching status filter.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr style="<?php echo ($selected_order && $selected_order['id'] === $order['id']) ? 'background-color: var(--surface);' : ''; ?>">
                                        <td style="font-family:monospace; font-weight:600;"><?php echo e($order['order_number']); ?></td>
                                        <td><?php echo e($order['customer_name'] ?? $order['shipping_name']); ?></td>
                                        <td><?php echo format_price($order['total_amount']); ?></td>
                                        <td style="text-transform:uppercase; font-size:0.75rem; font-weight:600;"><?php echo e($order['payment_method']); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo e($order['order_status']); ?>" style="font-size:0.65rem;">
                                                <?php echo e($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-pill status-<?php echo $order['payment_status'] === 'completed' ? 'completed' : ($order['payment_status'] === 'refunded' ? 'failed' : 'pending'); ?>" style="font-size:0.65rem;">
                                                <?php echo e($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="orders.php?status=<?php echo urlencode($filter_status); ?>&edit_id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding:0.35rem 0.8rem; font-size:0.7rem; letter-spacing:0.02em;">Manage</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Detail View & Status Editor Panel -->
            <?php if ($selected_order): ?>
                <div class="checkout-section-card" style="margin-bottom:0; position:sticky; top:20px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding-bottom:0.8rem; margin-bottom:1.5rem;">
                        <h3 style="border-bottom:none; margin-bottom:0; padding-bottom:0;">Order Management</h3>
                        <a href="orders.php?status=<?php echo urlencode($filter_status); ?>" style="font-size:1.25rem; color:var(--text-muted); font-weight:700;">&times;</a>
                    </div>

                    <div style="font-size:0.85rem; line-height:1.6; display:flex; flex-direction:column; gap:1.2rem;">
                        <!-- Shipping Box Info -->
                        <div>
                            <h5 style="text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:0.4rem;">Customer Delivery Address</h5>
                            <strong><?php echo e($selected_order['shipping_name']); ?></strong>
                            <p style="color:var(--text-muted);">Phone: <?php echo e($selected_order['shipping_phone']); ?></p>
                            <p style="color:var(--text-muted);">Email: <?php echo e($selected_order['shipping_email']); ?></p>
                            <p style="color:var(--text-muted); margin-top:0.3rem; white-space:pre-line;"><?php echo e($selected_order['shipping_address']); ?></p>
                        </div>

                        <!-- Items Bought Box -->
                        <div>
                            <h5 style="text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:0.6rem;">Items Purchased</h5>
                            <table style="width:100%; border-collapse:collapse; font-size:0.8rem; text-align:left;">
                                <tbody>
                                    <?php foreach ($selected_items as $item): ?>
                                        <tr style="border-bottom:1px solid var(--border);">
                                            <td style="padding:0.6rem 0; font-weight:600;"><?php echo e($item['name']); ?></td>
                                            <td style="padding:0.6rem 0; text-align:center;"><?php echo e($item['size']); ?>/<?php echo e($item['color']); ?></td>
                                            <td style="padding:0.6rem 0; text-align:center;">x<?php echo $item['quantity']; ?></td>
                                            <td style="padding:0.6rem 0; text-align:right; font-weight:600;"><?php echo format_price($item['price'] * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Update Form -->
                        <div style="background-color:var(--surface); border:1px solid var(--border); padding:1.5rem; margin-top:0.5rem;">
                            <h5 style="text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em; color:var(--text-muted); margin-bottom:1rem;">Update Statuses</h5>
                            
                            <form action="orders.php?status=<?php echo urlencode($filter_status); ?>&edit_id=<?php echo $selected_order['id']; ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="update_order">
                                <input type="hidden" name="order_id" value="<?php echo $selected_order['id']; ?>">
                                
                                <div class="form-group" style="margin-bottom:1rem;">
                                    <label for="order_status" style="font-size:0.7rem;">Delivery Status</label>
                                    <select id="order_status" name="order_status" class="form-control" style="height:40px; font-size:0.85rem; padding: 0 0.8rem;">
                                        <option value="pending" <?php echo $selected_order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $selected_order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $selected_order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $selected_order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $selected_order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom:1.5rem;">
                                    <label for="payment_status" style="font-size:0.7rem;">Payment status</label>
                                    <select id="payment_status" name="payment_status" class="form-control" style="height:40px; font-size:0.85rem; padding: 0 0.8rem;">
                                        <option value="pending" <?php echo $selected_order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="completed" <?php echo $selected_order['payment_status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="failed" <?php echo $selected_order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                        <option value="refunded" <?php echo $selected_order['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-solid" style="width:100%; padding:0.6rem; font-size:0.8rem; height:42px; letter-spacing:0.02em;">Apply Statuses</button>
                            </form>
                        </div>
                        
                        <div style="text-align:center; margin-top:1rem;">
                            <!-- Link to print invoice -->
                            <a href="../order-confirmation.php?order_num=<?php echo urlencode($selected_order['order_number']); ?>" target="_blank" class="btn btn-outline" style="width:100%; font-size:0.75rem; padding: 0.6rem; display:inline-flex; align-items:center; justify-content:center; height:42px;"><i class="fas fa-print" style="margin-right:8px;"></i>Print Customer Invoice</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
