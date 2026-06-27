<?php
/**
 * shop.php - Clothing Product Catalog with Filters and Sorting
 */
$page_title = 'Shop Fashion';
require_once __DIR__ . '/includes/header.php';

// Capture filters and sorting inputs
$cat_filter = sanitize($_GET['category'] ?? '');
$search_filter = sanitize($_GET['search'] ?? '');
$min_price = filter_var($_GET['min_price'] ?? '', FILTER_VALIDATE_FLOAT);
$max_price = filter_var($_GET['max_price'] ?? '', FILTER_VALIDATE_FLOAT);
$size_filter = sanitize($_GET['size'] ?? '');
$color_filter = sanitize($_GET['color'] ?? '');
$sort_order = sanitize($_GET['sort'] ?? 'default');

// Build SQL dynamic query
$query = "SELECT p.*, c.name as category_name, c.slug as category_slug 
          FROM products p 
          JOIN categories c ON p.category_id = c.id";

$where_clauses = [];
$params = [];

if ($cat_filter) {
    $where_clauses[] = "c.slug = :category";
    $params['category'] = $cat_filter;
}

if ($search_filter) {
    $where_clauses[] = "(p.name LIKE :search OR p.description LIKE :search)";
    $params['search'] = '%' . $search_filter . '%';
}

if ($min_price !== false && $min_price !== '') {
    $where_clauses[] = "COALESCE(p.discount_price, p.price) >= :min_price";
    $params['min_price'] = $min_price;
}

if ($max_price !== false && $max_price !== '') {
    $where_clauses[] = "COALESCE(p.discount_price, p.price) <= :max_price";
    $params['max_price'] = $max_price;
}

if ($size_filter) {
    $where_clauses[] = "FIND_IN_SET(:size, p.sizes) > 0";
    $params['size'] = $size_filter;
}

if ($color_filter) {
    $where_clauses[] = "p.colors LIKE :color";
    $params['color'] = '%' . $color_filter . '%';
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Apply Sorting
switch ($sort_order) {
    case 'price_asc':
        $query .= " ORDER BY COALESCE(p.discount_price, p.price) ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY COALESCE(p.discount_price, p.price) DESC";
        break;
    case 'newest':
        $query .= " ORDER BY p.created_at DESC";
        break;
    default:
        $query .= " ORDER BY p.id ASC";
        break;
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Fetch all categories for sidebar widget
    $cats_stmt = $pdo->query("SELECT * FROM categories");
    $categories = $cats_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<div class="container section-padding" style="padding-top: 3rem;">
    <!-- Breadcrumbs -->
    <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 2rem;">
        <a href="index.php">Home</a> &nbsp;/&nbsp; <span style="color: var(--text);">Shop Catalog</span>
    </div>

    <div class="shop-layout">
        <!-- Sidebar Filters widget -->
        <aside class="shop-sidebar">
            <form action="shop.php" method="GET" id="filter-form">
                <?php if ($search_filter): ?>
                    <input type="hidden" name="search" value="<?php echo e($search_filter); ?>">
                <?php endif; ?>
                
                <!-- Category Widget -->
                <div class="sidebar-widget">
                    <h4>Categories</h4>
                    <div class="filter-list">
                        <label>
                            <input type="radio" name="category" value="" <?php echo !$cat_filter ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <span>All Collections</span>
                        </label>
                        <?php foreach ($categories as $cat): ?>
                            <label>
                                <input type="radio" name="category" value="<?php echo e($cat['slug']); ?>" <?php echo $cat_filter === $cat['slug'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span><?php echo e($cat['name']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range Widget -->
                <div class="sidebar-widget">
                    <h4>Price Bounds</h4>
                    <div class="price-range-inputs" style="margin-bottom: 1rem;">
                        <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price !== false ? e($min_price) : ''; ?>" min="0">
                        <span>—</span>
                        <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price !== false ? e($max_price) : ''; ?>" min="0">
                    </div>
                    <button type="submit" class="btn btn-solid" style="width:100%; padding:0.6rem 1rem; font-size:0.75rem; letter-spacing:0.05em;">Apply Bounds</button>
                </div>

                <!-- Sizes Widget -->
                <div class="sidebar-widget">
                    <h4>Filter Size</h4>
                    <div class="size-selector-chips" style="flex-wrap: wrap;">
                        <?php foreach (['S', 'M', 'L', 'XL'] as $sz): ?>
                            <label>
                                <input type="radio" name="size" value="<?php echo $sz; ?>" <?php echo $size_filter === $sz ? 'checked' : ''; ?> onchange="this.form.submit()" class="size-chip-input">
                                <span class="size-chip" style="width:38px; height:38px; font-size:0.8rem;"><?php echo $sz; ?></span>
                            </label>
                        <?php endforeach; ?>
                        <?php if ($size_filter): ?>
                            <a href="shop.php?category=<?php echo urlencode($cat_filter); ?>&search=<?php echo urlencode($search_filter); ?>" style="font-size:0.75rem; color:var(--error); margin-top:0.5rem; display:block;">Clear Size</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Colors Widget -->
                <div class="sidebar-widget">
                    <h4>Filter Color</h4>
                    <div class="filter-list">
                        <select name="color" onchange="this.form.submit()" class="sort-select" style="width:100%;">
                            <option value="">Select Color</option>
                            <?php foreach (['Black', 'White', 'Blue', 'Navy', 'Gray', 'Yellow', 'Emerald', 'Beige'] as $col): ?>
                                <option value="<?php echo $col; ?>" <?php echo $color_filter === $col ? 'selected' : ''; ?>><?php echo $col; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <a href="shop.php" class="btn btn-outline" style="width:100%; text-align:center; padding: 0.6rem 1rem; font-size:0.75rem; letter-spacing:0.05em;">Clear All Filters</a>
            </form>
        </aside>

        <!-- Main Product Area -->
        <main>
            <!-- Control bar -->
            <div class="shop-control-bar">
                <div>
                    Showing <strong style="color:var(--text);"><?php echo count($products); ?></strong> items 
                    <?php if ($search_filter): ?>
                        matching "<?php echo e($search_filter); ?>"
                    <?php endif; ?>
                    <?php if ($cat_filter): ?>
                        in <?php echo e(ucfirst($cat_filter)); ?>
                    <?php endif; ?>
                </div>
                
                <div class="shop-control-right">
                    <label for="sort-select" style="font-size: 0.85rem; color: var(--text-muted);">Sort By:</label>
                    <select id="sort-select" class="sort-select" onchange="location = this.value;">
                        <option value="shop.php?category=<?php echo urlencode($cat_filter); ?>&search=<?php echo urlencode($search_filter); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&size=<?php echo urlencode($size_filter); ?>&color=<?php echo urlencode($color_filter); ?>&sort=default" <?php echo $sort_order === 'default' ? 'selected' : ''; ?>>Default Order</option>
                        <option value="shop.php?category=<?php echo urlencode($cat_filter); ?>&search=<?php echo urlencode($search_filter); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&size=<?php echo urlencode($size_filter); ?>&color=<?php echo urlencode($color_filter); ?>&sort=price_asc" <?php echo $sort_order === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="shop.php?category=<?php echo urlencode($cat_filter); ?>&search=<?php echo urlencode($search_filter); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&size=<?php echo urlencode($size_filter); ?>&color=<?php echo urlencode($color_filter); ?>&sort=price_desc" <?php echo $sort_order === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="shop.php?category=<?php echo urlencode($cat_filter); ?>&search=<?php echo urlencode($search_filter); ?>&min_price=<?php echo urlencode($min_price); ?>&max_price=<?php echo urlencode($max_price); ?>&size=<?php echo urlencode($size_filter); ?>&color=<?php echo urlencode($color_filter); ?>&sort=newest" <?php echo $sort_order === 'newest' ? 'selected' : ''; ?>>New Arrivals</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid layout -->
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 5rem 0;">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--border); margin-bottom: 1.5rem;"></i>
                    <h4>No Products Found</h4>
                    <p style="color:var(--text-muted); margin-top: 0.5rem;">Try adjusting your search query or filter checkboxes.</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-card-img-wrapper">
                                <?php if ($product['discount_price'] !== null): ?>
                                    <?php $pct = round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                                    <div class="badge-discount">-<?php echo $pct; ?>%</div>
                                <?php endif; ?>
                                
                                <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                
                                <!-- Hover Actions -->
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
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
