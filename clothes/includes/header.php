<?php
/**
 * header.php - Global Header Layout template
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Capture search query if present
$search_query = sanitize($_GET['search'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME . ' - Premium Clothing E-Store'; ?></title>
    <!-- SEO Meta Tags -->
    <meta name="description" content="Discover premium apparel and the latest fashion trends at VÈLO Fashion. Discover custom shirts, elegant evening gowns, stylish denim jackets, and cute kids wear.">
    <meta name="csrf-token" content="<?php echo e($_SESSION['csrf_token'] ?? ''); ?>">
    <!-- Google Fonts & FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style System -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="nav-wrapper">
            <!-- Hamburger menu for mobile devices -->
            <div class="burger-menu" id="burger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <!-- Brand Logo -->
            <a href="<?php echo SITE_URL; ?>/index.php" class="logo">
                VÈLO<span>.</span>
            </a>

            <!-- Navigation Links -->
            <nav class="nav-links" id="nav-links">
                <a href="<?php echo SITE_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="<?php echo SITE_URL; ?>/shop.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'shop.php' ? 'active' : ''; ?>">Shop</a>
                <a href="<?php echo SITE_URL; ?>/shop.php?category=men" class="<?php echo (isset($_GET['category']) && $_GET['category'] === 'men') ? 'active' : ''; ?>">Men</a>
                <a href="<?php echo SITE_URL; ?>/shop.php?category=women" class="<?php echo (isset($_GET['category']) && $_GET['category'] === 'women') ? 'active' : ''; ?>">Women</a>
                <a href="<?php echo SITE_URL; ?>/shop.php?category=kids" class="<?php echo (isset($_GET['category']) && $_GET['category'] === 'kids') ? 'active' : ''; ?>">Kids</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'active' : ''; ?>">Contact</a>
            </nav>

            <!-- Search Bar Form -->
            <form action="<?php echo SITE_URL; ?>/shop.php" method="GET" style="display: flex; border-bottom: 1px solid var(--border); max-width: 200px; margin: 0 1rem; align-items:center;">
                <input type="text" name="search" placeholder="Search clothes..." value="<?php echo e($search_query); ?>" style="font-size:0.85rem; padding: 0.3rem 0; width:100%; border:none; outline:none; background:none;">
                <button type="submit" style="cursor:pointer; font-size:0.9rem; padding: 0.3rem;"><i class="fas fa-search"></i></button>
            </form>

            <!-- Header Action Icons -->
            <div class="header-actions">
                <!-- Theme switch -->
                <div class="theme-switch" id="theme-toggler" title="Toggle Light/Dark Theme">
                    <div class="theme-switch-slider"></div>
                </div>

                <!-- User account / Admin Dashboard access -->
                <?php if (is_logged_in()): ?>
                    <div class="header-icon-btn" style="position: relative;" onclick="document.getElementById('user-dropdown').classList.toggle('show');">
                        <i class="far fa-user" style="cursor:pointer;"></i>
                        <!-- Dropdown list -->
                        <div id="user-dropdown" style="display:none; position: absolute; top: 100%; right: 0; background-color: var(--surface-card); border: 1px solid var(--border); min-width: 160px; box-shadow: var(--shadow); z-index: 101; padding: 0.5rem 0;">
                            <a href="<?php echo SITE_URL; ?>/profile.php" style="display:block; padding:0.6rem 1.2rem; font-size:0.85rem;"><i class="fas fa-id-card" style="margin-right: 8px;"></i>My Account</a>
                            <?php if (is_admin()): ?>
                                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" style="display:block; padding:0.6rem 1.2rem; font-size:0.85rem; color: var(--accent);"><i class="fas fa-cogs" style="margin-right: 8px;"></i>Admin Panel</a>
                            <?php endif; ?>
                            <hr style="border:none; border-top: 1px solid var(--border); margin:0.4rem 0;">
                            <a href="<?php echo SITE_URL; ?>/logout.php" style="display:block; padding:0.6rem 1.2rem; font-size:0.85rem; color: var(--error);"><i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="header-icon-btn" title="Login / Register"><i class="far fa-user"></i></a>
                <?php endif; ?>

                <!-- Wishlist link -->
                <a href="<?php echo SITE_URL; ?>/wishlist.php" class="header-icon-btn" title="Wishlist">
                    <i class="far fa-heart"></i>
                    <?php $wl_count = get_wishlist_count($pdo); ?>
                    <span class="badge-count wishlist-badge" style="<?php echo $wl_count > 0 ? '' : 'display:none;'; ?>">
                        <?php echo $wl_count; ?>
                    </span>
                </a>

                <!-- Cart icon link -->
                <a href="<?php echo SITE_URL; ?>/cart.php" class="header-icon-btn" title="Shopping Cart">
                    <i class="fas fa-shopping-bag"></i>
                    <?php $cart_count = get_cart_count(); ?>
                    <span class="badge-count cart-badge" style="<?php echo $cart_count > 0 ? '' : 'display:none;'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>
            </div>
        </div>
    </div>
</header>

<style>
/* Header Dropdown Simple Show Toggle Styling */
#user-dropdown.show {
    display: block !important;
}
#user-dropdown a:hover {
    background-color: var(--surface);
    color: var(--accent);
}
</style>
