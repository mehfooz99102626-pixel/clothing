<?php
/**
 * contact.php - Contact Page
 */
$page_title = 'Contact Support';
require_once __DIR__ . '/includes/header.php';

$success = '';
$error = '';
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_request();
    
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // In real systems, write email script here or store message in database.
        $success = "Thank you for reaching out, " . e($name) . "! Your message has been received. Our support team will respond within 24 hours.";
        
        // Reset form variables
        $name = $email = $subject = $message = '';
    }
}
?>

<div class="container section-padding">
    <div class="section-header">
        <h2>Contact Us</h2>
        <p>Have questions about sizes, collections, or custom tailoring? We are here to help.</p>
    </div>

    <div class="checkout-layout" style="margin-bottom: 5rem;">
        <!-- Contact Form Side -->
        <div class="checkout-section-card" style="margin-bottom: 0;">
            <h3>Send Us a Message</h3>
            
            <?php if ($success): ?>
                <div class="form-alert form-alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="form-alert form-alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <form action="contact.php" method="POST">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" value="<?php echo e($name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="john@example.com" value="<?php echo e($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" class="form-control" placeholder="How can we help you?" value="<?php echo e($subject); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" class="form-control" rows="6" placeholder="Write your message here..." style="resize:none;" required><?php echo e($message); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-solid" style="width: 100%;">Send Message</button>
            </form>
        </div>
        
        <!-- Info Side -->
        <div style="display:flex; flex-direction:column; gap:2.5rem;">
            <!-- Customer Support Cards -->
            <div class="checkout-section-card" style="margin-bottom: 0; padding: 2.5rem;">
                <h3>Customer Support</h3>
                <div style="margin-top: 1.5rem; display:flex; flex-direction:column; gap:1.5rem;">
                    <div style="display:flex; gap:1rem; align-items:flex-start;">
                        <div style="color:var(--accent); font-size:1.4rem; padding-top:2px;"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h5 style="font-family:var(--font-sans); font-size:0.95rem; font-weight:600; margin-bottom:0.2rem;">Our Headquarters</h5>
                            <p style="font-size:0.85rem; color:var(--text-muted);">100 Fashion Avenue, Penthouse B, New York, NY 10001</p>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:1rem; align-items:flex-start;">
                        <div style="color:var(--accent); font-size:1.4rem; padding-top:2px;"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <h5 style="font-family:var(--font-sans); font-size:0.95rem; font-weight:600; margin-bottom:0.2rem;">Call Customer Care</h5>
                            <p style="font-size:0.85rem; color:var(--text-muted); font-weight:500;">+1 (800) 555-VELO (8am - 8pm EST)</p>
                        </div>
                    </div>
                    
                    <div style="display:flex; gap:1rem; align-items:flex-start;">
                        <div style="color:var(--accent); font-size:1.4rem; padding-top:2px;"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h5 style="font-family:var(--font-sans); font-size:0.95rem; font-weight:600; margin-bottom:0.2rem;">Email Support</h5>
                            <p style="font-size:0.85rem; color:var(--text-muted); font-weight:500;">concierge@velofashion.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQ Summary Box -->
            <div class="checkout-section-card" style="margin-bottom: 0; padding: 2.5rem;" id="support">
                <h3>Quick Details</h3>
                <div style="margin-top: 1.5rem; display:flex; flex-direction:column; gap:1rem; font-size:0.85rem; color:var(--text-muted);">
                    <p><strong>Returns:</strong> 30-day worry-free returns and exchanges on all unworn items with tags attached.</p>
                    <p><strong>Shipping:</strong> Worldwide complimentary standard delivery on orders exceeding $150. Express overnight shipping available.</p>
                    <p><strong>Sizing:</strong> True-to-size cut fitting. Please inspect size selections on product detail panels before purchasing.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Google Maps Mockup Frame -->
    <h3>Our Location</h3>
    <div class="maps-mock-container" style="margin-top: 1.5rem;">
        <div class="maps-mock-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h4 style="margin-bottom: 0.5rem;">VÈLO Flagship Boutique Manhattan</h4>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">100 Fashion Avenue, New York, NY</p>
            <a href="https://maps.google.com" target="_blank" rel="noreferrer" class="btn btn-outline" style="padding: 0.5rem 1.5rem; font-size: 0.8rem;">Open in Maps</a>
        </div>
        <!-- Stylized Map lines layout mock overlay -->
        <div style="position: absolute; inset:0; opacity: 0.08; background-image: radial-gradient(var(--text) 1px, transparent 1px), radial-gradient(var(--text) 1px, var(--background) 1px); background-size: 20px 20px; background-position: 0 0, 10px 10px; z-index: 1;"></div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
