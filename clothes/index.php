<?php
/**
 * index.php - VÈLO E-Commerce Store Homepage
 */
$page_title = 'Premium Fashion Boutique';
require_once __DIR__ . '/includes/header.php';

// Handle Newsletter Subscription (simulated or session stored)
$newsletter_coupon = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'newsletter_subscribe') {
    verify_csrf_request();
    $email = filter_var(trim($_POST['newsletter_email'] ?? ''), FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Success! Alert client with promo code
        $newsletter_coupon = "WELCOME15";
    }
}

// Fetch featured, new arrivals, and best seller products
try {
    // 1. Featured Products
    $feat_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 LIMIT 4");
    $featured_products = $feat_stmt->fetchAll();
    
    // 2. New Arrivals
    $new_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_new_arrival = 1 LIMIT 4");
    $new_products = $new_stmt->fetchAll();
    
    // 3. Best Sellers
    $best_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_best_seller = 1 LIMIT 4");
    $best_products = $best_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>

<!-- Newsletter Promo Alert Modal -->
<?php if ($newsletter_coupon): ?>
    <div class="modal-overlay" id="newsletter-promo-modal" style="display:flex;">
        <div class="modal-card" style="max-width: 500px; text-align:center; padding: 3rem 2rem;">
            <span class="modal-close-btn" onclick="this.parentNode.parentNode.style.display='none';">&times;</span>
            <i class="fas fa-envelope-open-text" style="font-size:3rem; color:var(--accent); margin-bottom:1.5rem;"></i>
            <h3 style="font-size:1.8rem; margin-bottom:0.5rem;">Thank You For Joining!</h3>
            <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;">You have successfully subscribed to VÈLO updates. Here is your welcome discount coupon:</p>
            <div style="background-color:var(--surface); border:1px dashed var(--accent); padding:1rem; font-family:monospace; font-size:1.3rem; font-weight:700; color:var(--accent); letter-spacing:0.1em; display:inline-block; margin-bottom:2rem;">
                <?php echo e($newsletter_coupon); ?>
            </div>
            <p style="font-size:0.75rem; color:var(--text-muted);">Apply this code at checkout to receive 15% off your first order!</p>
        </div>
    </div>
<?php endif; ?>

<!-- Hero Slider Section -->
<section class="hero-slider">
    <div class="container hero-slide">
        <div class="hero-content">
            <span class="subtitle">Season Clearance Sale</span>
            <h1>The Art of Dressing Well</h1>
            <p>Explore our premium seasonal collection crafted with sustainable linen, luxury wool, and organic denim blends designed for absolute comfort.</p>
            <div style="display:flex; gap:1.2rem;">
                <a href="shop.php" class="btn btn-solid">Shop The Catalog</a>
                <a href="shop.php?category=women" class="btn btn-outline">Explore Couture</a>
            </div>
        </div>
        <div class="hero-image">
            <!-- First generated premium image -->
            <img src="assets/images/denim_jacket.jpg" alt="Premium Denim Collection">
        </div>
    </div>
</section>

<!-- Category Boxes Section -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <h2>Curated Collections</h2>
            <p>Explore our custom designed ranges tailored specifically for your lifestyle.</p>
        </div>
        
        <div class="category-grid">
            <!-- Men -->
            <a href="shop.php?category=men" class="category-card">
                <img src="assets/images/slim_suit.jpg" alt="Men's Collection">
                <div class="category-card-overlay">
                    <h3>Men's Collection</h3>
                    <span>Browse Tailored Suits & Tees</span>
                </div>
            </a>
            
            <!-- Women -->
            <a href="shop.php?category=women" class="category-card">
                <img src="assets/images/evening_gown.jpg" alt="Women's Couture">
                <div class="category-card-overlay">
                    <h3>Women's Couture</h3>
                    <span>Browse Elegant Silk Dresses</span>
                </div>
            </a>
            
            <!-- Kids -->
            <a href="shop.php?category=kids" class="category-card">
                <img src="assets/images/kids_hoodie.jpg" alt="Kids Clothes">
                <div class="category-card-overlay">
                    <h3>Kids Playful Wear</h3>
                    <span>Browse Hoodies & Overalls</span>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="section-padding" style="background-color: var(--surface);">
    <div class="container">
        <div class="section-header">
            <h2>Featured Pieces</h2>
            <p>Our top recommended picks for this week’s styling.</p>
        </div>
        
        <div class="product-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-card-img-wrapper">
                        <?php if ($product['discount_price'] !== null): ?>
                            <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                            <div class="badge-discount">-<?php echo $pct; ?>%</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                        
                        <!-- Hover Overlay Actions -->
                        <div class="product-card-actions">
                            <button type="button" class="product-action-btn quick-view-btn" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo e($product['name']); ?>"
                                    data-price="<?php echo $product['price']; ?>"
                                    data-description="<?php echo e($product['description']); ?>"
                                    data-image="<?php echo e($product['image']); ?>"
                                    data-sizes="<?php echo e($product['sizes']); ?>"
                                    data-colors="<?php echo e($product['colors']); ?>"
                                    title="Quick View">
                                <i class="far fa-eye"></i>
                            </button>
                            
                            <button type="button" class="product-action-btn wishlist-toggle-btn <?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'active' : ''; ?>" data-product-id="<?php echo $product['id']; ?>" title="Add to Wishlist">
                                <i class="<?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-card-info">
                        <div class="product-card-cat"><?php echo e($product['category_name']); ?></div>
                        <h4 class="product-card-title"><a href="product.php?slug=<?php echo e($product['slug']); ?>"><?php echo e($product['name']); ?></a></h4>
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
    </div>
</section>

<!-- New Arrivals & Best Sellers Split Show -->
<section class="section-padding">
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:3.5rem;">
            <div>
                <h2 style="font-size:2.2rem; font-family:var(--font-serif);">New Drops</h2>
                <p style="color:var(--text-muted); font-size:0.95rem; margin-top:0.3rem;">Fresh additions freshly unpacked in our boutique store.</p>
            </div>
            <a href="shop.php?sort=newest" style="font-size:0.85rem; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; color:var(--accent); border-bottom: 1px solid var(--accent); padding-bottom:3px;">View All Drops</a>
        </div>
        
        <div class="product-grid" style="margin-bottom: 6rem;">
            <?php foreach ($new_products as $product): ?>
                <div class="product-card">
                    <div class="product-card-img-wrapper">
                        <div class="badge-tag">NEW</div>
                        <?php if ($product['discount_price'] !== null): ?>
                            <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                            <div class="badge-discount">-<?php echo $pct; ?>%</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                        
                        <!-- Hover Overlay Actions -->
                        <div class="product-card-actions">
                            <button type="button" class="product-action-btn quick-view-btn" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo e($product['name']); ?>"
                                    data-price="<?php echo $product['price']; ?>"
                                    data-description="<?php echo e($product['description']); ?>"
                                    data-image="<?php echo e($product['image']); ?>"
                                    data-sizes="<?php echo e($product['sizes']); ?>"
                                    data-colors="<?php echo e($product['colors']); ?>"
                                    title="Quick View">
                                <i class="far fa-eye"></i>
                            </button>
                            
                            <button type="button" class="product-action-btn wishlist-toggle-btn <?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'active' : ''; ?>" data-product-id="<?php echo $product['id']; ?>" title="Add to Wishlist">
                                <i class="<?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-card-info">
                        <div class="product-card-cat"><?php echo e($product['category_name']); ?></div>
                        <h4 class="product-card-title"><a href="product.php?slug=<?php echo e($product['slug']); ?>"><?php echo e($product['name']); ?></a></h4>
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

        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:3.5rem;">
            <div>
                <h2 style="font-size:2.2rem; font-family:var(--font-serif);">Best Sellers</h2>
                <p style="color:var(--text-muted); font-size:0.95rem; margin-top:0.3rem;">The most loved and purchased designs in our store.</p>
            </div>
            <a href="shop.php" style="font-size:0.85rem; font-weight:600; text-transform:uppercase; letter-spacing:0.1em; color:var(--accent); border-bottom: 1px solid var(--accent); padding-bottom:3px;">View All Classics</a>
        </div>
        
        <div class="product-grid">
            <?php foreach ($best_products as $product): ?>
                <div class="product-card">
                    <div class="product-card-img-wrapper">
                        <?php if ($product['discount_price'] !== null): ?>
                            <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                            <div class="badge-discount">-<?php echo $pct; ?>%</div>
                        <?php endif; ?>
                        
                        <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                        
                        <!-- Hover Overlay Actions -->
                        <div class="product-card-actions">
                            <button type="button" class="product-action-btn quick-view-btn" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-name="<?php echo e($product['name']); ?>"
                                    data-price="<?php echo $product['price']; ?>"
                                    data-description="<?php echo e($product['description']); ?>"
                                    data-image="<?php echo e($product['image']); ?>"
                                    data-sizes="<?php echo e($product['sizes']); ?>"
                                    data-colors="<?php echo e($product['colors']); ?>"
                                    title="Quick View">
                                <i class="far fa-eye"></i>
                            </button>
                            
                            <button type="button" class="product-action-btn wishlist-toggle-btn <?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'active' : ''; ?>" data-product-id="<?php echo $product['id']; ?>" title="Add to Wishlist">
                                <i class="<?php echo (is_logged_in() && $pdo->query("SELECT 1 FROM wishlist WHERE user_id = ".get_logged_in_user_id()." AND product_id = ".$product['id'])->fetch()) ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </div>
                    </div>
                    <div class="product-card-info">
                        <div class="product-card-cat"><?php echo e($product['category_name']); ?></div>
                        <h4 class="product-card-title"><a href="product.php?slug=<?php echo e($product['slug']); ?>"><?php echo e($product['name']); ?></a></h4>
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
    </div>
</section>

<!-- Review Section -->
<section class="section-padding reviews-section">
    <div class="container">
        <div class="section-header">
            <h2>Customer Journal</h2>
            <p>Read what our global clientele writes about their VÈLO experience.</p>
        </div>
        
        <div class="reviews-grid">
            <div class="review-card">
                <div class="stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="review-text">"The Classic Denim Jacket is exactly what I was searching for. Material feels extremely heavyweight and substantial, and the fit is tailored. Truly premium brand."</p>
                <div class="reviewer-name">Marcus Aurelius</div>
            </div>
            
            <div class="review-card">
                <div class="stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="far fa-star"></i>
                </div>
                <p class="review-text">"Wore the Silk Gown to a charity dinner and got a dozen compliments. Exquisite flow. I had to get the hem tailored slightly as it runs a bit long, but it is gorgeous."</p>
                <div class="reviewer-name">Eleanor Vance</div>
            </div>
            
            <div class="review-card">
                <div class="stars">
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="review-text">"Fast shipping to the UK, items are packed in beautiful dust bags. The kid overalls denim set is sturdy and survived multiple wash cycles already. Highly recommend."</p>
                <div class="reviewer-name">Sarah Jenkins</div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="section-padding newsletter-section">
    <div class="container">
        <h2>Join VÈLO Society</h2>
        <p>Subscribe to receive early notifications on limited product collections, sales previews, and custom wardrobe newsletters.</p>
        <form action="index.php" method="POST" class="newsletter-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="newsletter_subscribe">
            <input type="email" name="newsletter_email" placeholder="Enter your email address" required style="border:none; outline:none; background:none; color:#ffffff;">
            <button type="submit">Subscribe</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/header.php'; // In includes, footer is required later ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
