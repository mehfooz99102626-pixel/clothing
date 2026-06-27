<?php
/**
 * admin/customers.php - Customers account registry logs
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Force admin credentials
require_admin();

$success_msg = '';
$error_msg = '';

// Handle Delete Customer Account
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $token = $_GET['csrf_token'] ?? '';
    
    if (!check_csrf($token)) {
        $error_msg = "Security Exception: CSRF token validation failed.";
    } else {
        // Guard: Prevent deleting logged-in admin user
        if ($del_id === get_logged_in_user_id()) {
            $error_msg = "Cannot delete currently active logged in administrator account.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$del_id]);
                $success_msg = "User account deleted successfully.";
            } catch (PDOException $e) {
                $error_msg = "Failed to delete user account: " . $e->getMessage();
            }
        }
    }
}

// Fetch all registered customers and their order counts
try {
    $stmt = $pdo->query("
        SELECT u.*, COUNT(o.id) as order_count 
        FROM users u 
        LEFT JOIN orders o ON u.id = o.user_id 
        GROUP BY u.id 
        ORDER BY u.role DESC, u.created_at DESC
    ");
    $accounts = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registry | <?php echo SITE_NAME; ?></title>
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
            <a href="orders.php" class="admin-menu-item">
                <i class="fas fa-shopping-bag"></i> Orders Log
            </a>
            <a href="customers.php" class="admin-menu-item active">
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
            <h2>Customer Accounts Registry</h2>
            <div style="font-size:0.9rem; color:var(--text-muted); font-weight:500;">
                Active Members: <strong style="color:var(--text);"><?php echo count($accounts); ?></strong>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="form-alert form-alert-success"><?php echo e($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="form-alert form-alert-danger"><?php echo e($error_msg); ?></div>
        <?php endif; ?>

        <!-- Customer Logs Table -->
        <div class="checkout-section-card" style="margin-bottom:0;">
            <h3>Accounts Index</h3>
            
            <div style="overflow-x:auto; margin-top:1.5rem;">
                <table class="orders-history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Name</th>
                            <th>Email Address</th>
                            <th>Contact Phone</th>
                            <th>Associated Orders</th>
                            <th>Date Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td><?php echo $acc['id']; ?></td>
                                <td>
                                    <span class="status-pill <?php echo $acc['role'] === 'admin' ? 'status-completed' : 'status-processing'; ?>" style="font-size:0.6rem; font-weight:600;">
                                        <?php echo e($acc['role']); ?>
                                    </span>
                                </td>
                                <td style="font-weight:600;"><?php echo e($acc['name']); ?></td>
                                <td><code><?php echo e($acc['email']); ?></code></td>
                                <td><?php echo e($acc['phone'] ?: 'N/A'); ?></td>
                                <td style="text-align:center; font-weight:600;"><?php echo $acc['order_count']; ?></td>
                                <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($acc['created_at'])); ?></td>
                                <td>
                                    <?php if ($acc['id'] !== get_logged_in_user_id()): ?>
                                        <a href="customers.php?delete_id=<?php echo $acc['id']; ?>&csrf_token=<?php echo urlencode($_SESSION['csrf_token']); ?>" onclick="return confirm('Are you sure you want to delete this customer account? Associated orders may be affected.');" class="btn btn-outline" style="padding:0.3rem 0.8rem; font-size:0.7rem; color:var(--error); border-color:var(--border);"><i class="fas fa-user-times"></i> Delete</a>
                                    <?php else: ?>
                                        <span style="font-size:0.75rem; color:var(--text-muted); font-style:italic;">Active Session</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

</body>
</html>
