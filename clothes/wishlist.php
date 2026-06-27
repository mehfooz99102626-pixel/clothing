<?php
/**
 * wishlist.php - User Wishlist Page
 */
$page_title = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';

// Force authentication
require_login();

$user_id = get_logged_in_user_id();
$error = '';
$success = '';

// Handle Move to Cart or Remove Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf_request();
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if ($_POST['action'] === 'remove') {
        try {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $success = "Product removed from your wishlist.";
        } catch (PDOException $e) {
            $error = "Failed to remove item: " . $e->getMessage();
        }
    }
}

// Fetch wishlist items
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.id 
        JOIN categories c ON p.category_id = c.id 
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $wishlist_items = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container section-padding">
    <div class="section-header">
        <h2>My Wishlist</h2>
        <p>Your curated list of premium fashion pieces you have saved for later.</p>
    </div>

    <?php if ($success): ?>
        <div class="form-alert form-alert-success" style="max-width: 600px; margin: 0 auto 2rem;"><?php echo e($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="form-alert form-alert-danger" style="max-width: 600px; margin: 0 auto 2rem;"><?php echo e($error); ?></div>
    <?php endif; ?>

    <?php if (empty($wishlist_items)): ?>
        <div style="text-align: center; padding: 4rem 0;">
            <i class="far fa-heart" style="font-size: 4rem; color: var(--border); margin-bottom: 1.5rem; display: block;"></i>
            <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">Your wishlist is currently empty.</p>
            <a href="shop.php" class="btn btn-solid">Explore Shop</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($wishlist_items as $product): ?>
                <div class="product-card">
                    <div class="product-card-img-wrapper">
                        <!-- Badges -->
                        <?php if ($product['discount_price'] !== null): ?>
                            <?php 
                                $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); 
                            ?>
                            <div class="badge-discount">-<?php echo $pct; ?>%</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                        
                        <!-- Hover Actions -->
                        <div class="product-card-actions">
                            <form action="api/cart_actions.php" method="POST" class="ajax-add-to-cart-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="product-action-btn" title="Add to Cart">
                                    <i class="fas fa-shopping-bag"></i>
                                </button>
                            </form>
                            
                            <!-- Remove from wishlist -->
                            <form action="wishlist.php" method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="product-action-btn" title="Remove" style="color: var(--error);">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="product-card-info">
                        <div class="product-card-cat"><?php echo e($product['category_name']); ?></div>
                        <h4 class="product-card-title">
                            <a href="product.php?slug=<?php echo e($product['slug']); ?>"><?php echo e($product['name']); ?></a>
                        </h4>
                        <div class="product-card-price">
                            <?php if ($product['discount_price'] !== null): ?>
                                <span class="price-original"><?php echo format_price($product['price']); ?></span>
                                <span class="price-discount"><?php echo format_price($product['discount_price']); ?></span>
                            <?php else: ?>
                                <span><?php echo format_price($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
