<?php
/**
 * product.php - Product Details Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Capture product details
$slug = sanitize($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);

try {
    if ($slug) {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.slug = ?");
        $stmt->execute([$slug]);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
    }
    
    $product = $stmt->fetch();
    
    if (!$product) {
        $page_title = 'Product Not Found';
        require_once __DIR__ . '/includes/header.php';
        echo '<div class="container section-padding" style="text-align:center;">';
        echo '<i class="fas fa-exclamation-triangle" style="font-size:3rem; color:var(--error); margin-bottom:1.5rem;"></i>';
        echo '<h2>Product Not Found</h2>';
        echo '<p style="color:var(--text-muted); margin-top:0.5rem;">The clothing item you are looking for does not exist or has been removed.</p>';
        echo '<a href="shop.php" class="btn btn-solid" style="margin-top:2rem;">Return to Shop</a>';
        echo '</div>';
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }
    
    // Set page title for header
    $page_title = $product['name'];
    require_once __DIR__ . '/includes/header.php';
    
    // Process Review Submission
    $review_error = '';
    $review_success = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
        verify_csrf_request();
        
        if (!is_logged_in()) {
            $review_error = "You must be logged in to submit a review.";
        } else {
            $rating = (int)($_POST['rating'] ?? 0);
            $review_text = sanitize($_POST['review_text'] ?? '');
            $user_id = get_logged_in_user_id();
            
            if ($rating < 1 || $rating > 5) {
                $review_error = "Please provide a rating between 1 and 5 stars.";
            } elseif (empty($review_text)) {
                $review_error = "Review message cannot be empty.";
            } else {
                // Check if user already reviewed this product
                $check_stmt = $pdo->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
                $check_stmt->execute([$product['id'], $user_id]);
                if ($check_stmt->fetch()) {
                    $review_error = "You have already reviewed this product.";
                } else {
                    $ins_review = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
                    $ins_review->execute([$product['id'], $user_id, $rating, $review_text]);
                    $review_success = "Your review has been published successfully!";
                }
            }
        }
    }
    
    // Fetch product reviews
    $rev_stmt = $pdo->prepare("SELECT r.*, u.name as user_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $rev_stmt->execute([$product['id']]);
    $reviews = $rev_stmt->fetchAll();
    
    // Calculate average rating
    $avg_rating = 0.00;
    if (!empty($reviews)) {
        $total_stars = 0;
        foreach ($reviews as $rev) {
            $total_stars += $rev['rating'];
        }
        $avg_rating = round($total_stars / count($reviews), 1);
    }
    
    // Fetch related products (same category, up to 4, excluding current product)
    $rel_stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? LIMIT 4");
    $rel_stmt->execute([$product['category_id'], $product['id']]);
    $related_products = $rel_stmt->fetchAll();
    
    // Parse Sizes and Colors
    $product_sizes = array_map('trim', explode(',', $product['sizes'] ?? 'S,M,L,XL'));
    $product_colors = array_map('trim', explode(',', $product['colors'] ?? 'Black,White'));
    
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>

<div class="container section-padding" style="padding-top: 3rem;">
    <!-- Breadcrumbs -->
    <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 3rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; <a href="shop.php">Shop</a> &nbsp;/&nbsp; <a href="shop.php?category=<?php echo urlencode($product['category_slug'] ?? ''); ?>"><?php echo e($product['category_name']); ?></a> &nbsp;/&nbsp; <span style="color: var(--text);"><?php echo e($product['name']); ?></span>
    </div>

    <!-- Product Details Layout -->
    <div class="product-details-layout">
        
        <!-- Gallery Images -->
        <div class="product-gallery">
            <div class="gallery-main-img">
                <img id="main-gallery-image" src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
            </div>
            <div class="gallery-thumbs">
                <div class="gallery-thumb-item active">
                    <img src="<?php echo e($product['image']); ?>" alt="Product angle 1">
                </div>
                <!-- Mock secondary angles using the same image/colored overlays for visual demo -->
                <div class="gallery-thumb-item">
                    <img src="<?php echo e($product['image']); ?>" alt="Product angle 2" style="filter: brightness(0.9);">
                </div>
                <div class="gallery-thumb-item">
                    <img src="<?php echo e($product['image']); ?>" alt="Product angle 3" style="filter: contrast(1.1);">
                </div>
                <div class="gallery-thumb-item">
                    <img src="<?php echo e($product['image']); ?>" alt="Product angle 4" style="filter: brightness(0.95) contrast(0.95);">
                </div>
            </div>
        </div>
        
        <!-- Product Details info panel -->
        <div class="product-info-panel">
            <span style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.15em; color:var(--accent); font-weight:600; display:block; margin-bottom:0.5rem;"><?php echo e($product['category_name']); ?> Collection</span>
            <h1><?php echo e($product['name']); ?></h1>
            
            <div class="product-rating-summary">
                <div class="stars">
                    <?php 
                    $full_stars = floor($avg_rating);
                    for ($i = 1; $i <= 5; $i++) {
                        if ($i <= $full_stars) {
                            echo '<i class="fas fa-star"></i>';
                        } elseif ($i - 0.5 <= $avg_rating) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        } else {
                            echo '<i class="far fa-star"></i>';
                        }
                    }
                    ?>
                </div>
                <span class="reviews-count">(<?php echo count($reviews); ?> Customer Reviews)</span>
            </div>
            
            <div class="product-details-price">
                <?php if ($product['discount_price'] !== null): ?>
                    <span class="price-original" style="font-size:1.3rem; margin-right:8px;"><?php echo format_price($product['price']); ?></span>
                    <span class="price-discount"><?php echo format_price($product['discount_price']); ?></span>
                <?php else: ?>
                    <span><?php echo format_price($product['price']); ?></span>
                <?php endif; ?>
            </div>
            
            <p class="product-details-desc"><?php echo e($product['description']); ?></p>
            
            <!-- Add to Cart Form -->
            <form action="api/cart_actions.php" method="POST" class="ajax-add-to-cart-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <!-- Sizes Selector -->
                <div class="product-variant-selector">
                    <span class="variant-label">Select Size</span>
                    <div class="size-selector-chips">
                        <?php foreach ($product_sizes as $idx => $size): ?>
                            <label>
                                <input type="radio" name="size" value="<?php echo e($size); ?>" <?php echo $idx === 0 ? 'checked' : ''; ?> class="size-chip-input">
                                <span class="size-chip"><?php echo e($size); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Colors Selector -->
                <div class="product-variant-selector">
                    <span class="variant-label">Select Color</span>
                    <div style="display:flex; gap:1rem; align-items:center;">
                        <?php foreach ($product_colors as $idx => $color): ?>
                            <label style="display:flex; align-items:center; gap:0.4rem; cursor:pointer;">
                                <input type="radio" name="color" value="<?php echo e($color); ?>" <?php echo $idx === 0 ? 'checked' : ''; ?> style="accent-color: var(--accent);">
                                <span style="font-size:0.9rem;"><?php echo e($color); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stock indicators -->
                <div style="margin-bottom: 2rem; font-size: 0.9rem;">
                    <?php if ($product['stock'] > 10): ?>
                        <span style="color:var(--success); font-weight:600;"><i class="fas fa-check-circle" style="margin-right:6px;"></i>In Stock (<?php echo $product['stock']; ?> items remaining)</span>
                    <?php elseif ($product['stock'] > 0): ?>
                        <span style="color:var(--accent); font-weight:600;"><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>Low Stock (Only <?php echo $product['stock']; ?> items left!)</span>
                    <?php else: ?>
                        <span style="color:var(--error); font-weight:600;"><i class="fas fa-times-circle" style="margin-right:6px;"></i>Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <!-- Quantity & Add Buttons -->
                <div class="qty-buy-row">
                    <div class="quantity-adjuster">
                        <button type="button" onclick="const input = this.parentNode.querySelector('input'); if(input.value > 1) input.value = parseInt(input.value)-1;">-</button>
                        <input type="number" name="quantity" value="1" min="1" max="10">
                        <button type="button" onclick="const input = this.parentNode.querySelector('input'); input.value = parseInt(input.value)+1;">+</button>
                    </div>
                    
                    <?php if ($product['stock'] > 0): ?>
                        <button type="submit" class="btn btn-solid" style="height:52px; padding:0 3rem;">Add to Cart</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-solid" style="height:52px; padding:0 3rem; background-color: var(--border); color:var(--text-muted); cursor:not-allowed;" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="product-meta-details">
                <span><strong>SKU:</strong> VELO-<?php echo sprintf('%04d', $product['id']); ?></span>
                <span><strong>Category:</strong> <?php echo e($product['category_name']); ?></span>
                <span><strong>Tagging:</strong> Organic Cotton, Sustainable Styling, Premium Comfort</span>
            </div>
        </div>
    </div>
    
    <!-- Tab panels for description vs reviews -->
    <div class="details-tabs">
        <div class="tabs-nav">
            <span class="tab-btn active" data-tab="details">Details & Material</span>
            <span class="tab-btn" data-tab="reviews">Reviews (<?php echo count($reviews); ?>)</span>
        </div>
        
        <div class="tab-content-pane active" id="tab-details">
            <h4 style="margin-bottom:1rem;">Premium Material Composition</h4>
            <p style="color:var(--text-muted); margin-bottom: 1.5rem;">Crafted from the finest selected raw materials in a carbon-neutral boutique loom facility. This piece offers high structural integrity, natural skin respiration, and comfortable long-wear cycles.</p>
            <ul style="padding-left:1.5rem; list-style:disc; color:var(--text-muted); display:flex; flex-direction:column; gap:0.6rem;">
                <li>80% Organic Natural Fibers, 20% Technical Resilience Yarn</li>
                <li>Heavyweight tailoring, double stitching lining profiles</li>
                <li>Hypoallergenic structure suitable for direct skin layering</li>
                <li>Eco-friendly low chemical dye coloring process</li>
            </ul>
        </div>
        
        <div class="tab-content-pane" id="tab-reviews">
            <div class="checkout-layout">
                <!-- Review List -->
                <div>
                    <h4 style="margin-bottom:1.5rem;">Customer Feedback</h4>
                    <?php if (empty($reviews)): ?>
                        <p style="color:var(--text-muted); font-style:italic;">No reviews posted yet for this clothing piece. Be the first to share your thoughts!</p>
                    <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:1.5rem;">
                            <?php foreach ($reviews as $rev): ?>
                                <div class="review-card" style="padding:1.5rem; border:1px solid var(--border); box-shadow:none;">
                                    <div class="stars" style="margin-bottom:0.5rem;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?php echo $i <= $rev['rating'] ? 'fas' : 'far'; ?> fa-star" style="font-size:0.8rem;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="review-text" style="font-size:0.85rem; margin-bottom:0.8rem;">"<?php echo e($rev['review_text']); ?>"</p>
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:var(--text-muted);">
                                        <strong><?php echo e($rev['user_name']); ?></strong>
                                        <span><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Review Submission form -->
                <div class="checkout-section-card" style="padding:2rem; margin-bottom:0; height:fit-content;">
                    <h4>Write a Review</h4>
                    
                    <?php if ($review_success): ?>
                        <div class="form-alert form-alert-success" style="margin-top:1rem;"><?php echo e($review_success); ?></div>
                    <?php endif; ?>
                    <?php if ($review_error): ?>
                        <div class="form-alert form-alert-danger" style="margin-top:1rem;"><?php echo e($review_error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (is_logged_in()): ?>
                        <form action="product.php?id=<?php echo $product['id']; ?>" method="POST" style="margin-top:1.5rem;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="submit_review">
                            <input type="hidden" name="rating" id="review-rating-input" value="5">
                            
                            <div class="form-group" style="margin-bottom: 1.2rem;">
                                <label>Your Rating</label>
                                <div class="stars-select" style="font-size:1.4rem; color:var(--accent); cursor:pointer; display:flex; gap:0.4rem;">
                                    <i class="fas fa-star" data-value="1"></i>
                                    <i class="fas fa-star" data-value="2"></i>
                                    <i class="fas fa-star" data-value="3"></i>
                                    <i class="fas fa-star" data-value="4"></i>
                                    <i class="fas fa-star" data-value="5"></i>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 1.2rem;">
                                <label for="review_text">Review Message</label>
                                <textarea name="review_text" id="review_text" rows="4" class="form-control" placeholder="Share your experience wearing this product..." required style="resize:none; font-size:0.85rem;"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-solid" style="width:100%; padding:0.7rem; font-size:0.8rem; letter-spacing:0.05em;">Submit Feedback</button>
                        </form>
                    <?php else: ?>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-top:1.5rem;">You must be <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" style="color:var(--accent); font-weight:600;">logged in</a> to post reviews.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
        <div style="margin-top:6rem;">
            <h3 style="font-size:1.8rem; font-family:var(--font-serif); margin-bottom:2.5rem; text-align:center;">You May Also Style With</h3>
            <div class="product-grid">
                <?php foreach ($related_products as $rel): ?>
                    <div class="product-card">
                        <div class="product-card-img-wrapper">
                            <?php if ($rel['discount_price'] !== null): ?>
                                <?php $pct = round((($rel['price'] - $rel['discount_price']) / $rel['price']) * 100); ?>
                                <div class="badge-discount">-<?php echo $pct; ?>%</div>
                            <?php endif; ?>
                            
                            <img src="<?php echo e($rel['image']); ?>" alt="<?php echo e($rel['name']); ?>">
                            
                            <!-- Hover Actions -->
                            <div class="product-card-actions">
                                <button type="button" class="product-action-btn quick-view-btn" 
                                        data-product-id="<?php echo $rel['id']; ?>"
                                        data-name="<?php echo e($rel['name']); ?>"
                                        data-price="<?php echo $rel['price']; ?>"
                                        data-description="<?php echo e($rel['description']); ?>"
                                        data-image="<?php echo e($rel['image']); ?>"
                                        data-sizes="<?php echo e($rel['sizes']); ?>"
                                        data-colors="<?php echo e($rel['colors']); ?>"
                                        title="Quick View">
                                    <i class="far fa-eye"></i>
                                </button>
                                
                                <button type="button" class="product-action-btn wishlist-toggle-btn <?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$rel['id'])->fetch()) ? 'active' : ''; ?>" data-product-id="<?php echo $rel['id']; ?>" title="Add to Wishlist">
                                    <i class="<?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$rel['id'])->fetch()) ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-card-info">
                            <div class="product-card-cat"><?php echo e($rel['category_name']); ?></div>
                            <h4 class="product-card-title"><a href="product.php?slug=<?php echo e($rel['slug']); ?>"><?php echo e($rel['name']); ?></a></h4>
                            <div class="product-card-price">
                                <?php if ($rel['discount_price'] !== null): ?>
                                    <span class="price-original"><?php echo format_price($rel['price']); ?></span>
                                    <span class="price-discount"><?php echo format_price($rel['discount_price']); ?></span>
                                <?php else: ?>
                                    <span><?php echo format_price($rel['price']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
