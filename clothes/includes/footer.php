<?php
/**
 * footer.php - Global Footer Layout template
 */
?>

<!-- Quick View Modal structure (Global modal loaded on demand) -->
<div class="modal-overlay" id="quickview-modal">
    <div class="modal-card" style="max-width: 800px;">
        <span class="modal-close-btn">&times;</span>
        <div class="quickview-container">
            <div class="quickview-img" style="display:flex; align-items:center; justify-content:center; background-color: var(--surface);">
                <img src="" class="qv-img" alt="Product Image" style="max-height: 100%; object-fit: contain;">
            </div>
            <div class="quickview-info">
                <h3 class="qv-name" style="font-size: 1.8rem; margin-bottom: 0.5rem;">Product Title</h3>
                <div class="qv-price" style="font-size:1.3rem; font-weight:700; color: var(--accent); margin-bottom: 1.5rem;">$0.00</div>
                <p class="qv-desc" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem;">Product description goes here.</p>
                
                <form action="api/cart_actions.php" method="POST" class="ajax-add-to-cart-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="" class="qv-prod-id">
                    
                    <div class="product-variant-selector" style="margin-bottom: 1rem;">
                        <span class="variant-label">Size</span>
                        <div class="size-selector-chips qv-sizes-container">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                    
                    <div class="product-variant-selector" style="margin-bottom: 1.5rem;">
                        <span class="variant-label">Color</span>
                        <div class="qv-colors-container">
                            <!-- Populated dynamically via JS -->
                        </div>
                    </div>
                    
                    <div class="qty-buy-row" style="margin-bottom: 0;">
                        <div class="quantity-adjuster">
                            <button type="button" onclick="const input = this.parentNode.querySelector('input'); if(input.value > 1) input.value = parseInt(input.value)-1;">-</button>
                            <input type="number" name="quantity" value="1" min="1" max="10">
                            <button type="button" onclick="const input = this.parentNode.querySelector('input'); input.value = parseInt(input.value)+1;">+</button>
                        </div>
                        <button type="submit" class="btn btn-solid">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-column">
                <div class="footer-logo">
                    VÈLO<span>.</span>
                </div>
                <p style="margin-bottom: 1.5rem; line-height: 1.7; font-size: 0.85rem;">
                    VÈLO Fashion is a modern online store built for styling high-end boutique clothing. We design premium items with organic cotton, silk, and wool.
                </p>
                <div class="social-links">
                    <a href="https://instagram.com" class="social-icon" target="_blank" rel="noreferrer"><i class="fab fa-instagram"></i></a>
                    <a href="https://facebook.com" class="social-icon" target="_blank" rel="noreferrer"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://pinterest.com" class="social-icon" target="_blank" rel="noreferrer"><i class="fab fa-pinterest-p"></i></a>
                    <a href="https://twitter.com" class="social-icon" target="_blank" rel="noreferrer"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="footer-column">
                <h4>Collections</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/shop.php?category=men">Men's Wardrobe</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/shop.php?category=women">Women's Couture</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/shop.php?category=kids">Kids Collection</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/shop.php?new_arrivals=1">New Arrivals</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Support</h4>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/profile.php">Order Tracking</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php#support">Customer Service</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php#faq">FAQs & Sizing</a></li>
                </ul>
            </div>
            
            <div class="footer-column">
                <h4>Newsletter</h4>
                <p style="font-size:0.85rem; margin-bottom: 1.5rem;">Subscribe to get notified about special product drops and exclusive discounts.</p>
                <form action="<?php echo SITE_URL; ?>/index.php" method="POST" class="newsletter-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="newsletter_subscribe">
                    <input type="email" name="newsletter_email" placeholder="Your email address" required style="border:none; outline:none; background:none; color:#ffffff;">
                    <button type="submit">Join</button>
                </form>
            </div>
        </div>
        
        <div class="footer-bottom">
            <div>
                &copy; <?php echo date('Y'); ?> VÈLO FASHION. All rights reserved. Made for premium styling.
            </div>
            <div class="payment-methods" title="Accepted Payment Gateways">
                <i class="fab fa-cc-visa" style="margin-right: 8px;"></i>
                <i class="fab fa-cc-mastercard" style="margin-right: 8px;"></i>
                <i class="fab fa-cc-paypal" style="margin-right: 8px;"></i>
                <i class="fab fa-cc-stripe" style="margin-right: 8px;"></i>
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
</footer>

<!-- Core Script -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
