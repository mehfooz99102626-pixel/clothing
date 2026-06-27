<?php
/**
 * register.php - User Registration Page
 */
$page_title = 'Create Account';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$success_msg = '';

$name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    verify_csrf_request();
    
    // Sanitize input
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Form Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "An account with this email address already exists.";
            } else {
                // Insert User
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, 'user')");
                $insert_stmt->execute([$name, $email, $hashed_password, $phone, $address]);
                
                $new_user_id = $pdo->lastInsertId();
                
                // Set Sessions
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'user';
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                redirect(SITE_URL . '/profile.php?registered=1');
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="auth-split-screen">
        <!-- Form Side -->
        <div class="auth-form-side">
            <h2>Create Account</h2>
            <p class="subtitle">Join VÈLO Fashion and enjoy exclusive perks, order tracking, and custom recommendations.</p>
            
            <?php if (!empty($errors)): ?>
                <div class="form-alert form-alert-danger">
                    <ul style="padding-left: 1.25rem; list-style: disc;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" value="<?php echo e($name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?php echo e($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 9876543210" value="<?php echo e($phone); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Delivery Address (Optional)</label>
                    <textarea id="address" name="address" class="form-control" rows="3" placeholder="e.g. Apartment, Suite, Street Address, City" style="resize: none;"><?php echo e($address); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" class="btn btn-solid" style="width: 100%; margin-top: 1rem;">Register Account</button>
            </form>
            
            <div class="form-footer-link">
                <span>Already have an account? <a href="login.php" style="color: var(--accent); font-weight:600;">Sign In</a></span>
            </div>
        </div>
        
        <!-- Image Side -->
        <div class="auth-image-side">
            <img src="<?php echo SITE_URL; ?>/assets/images/evening_gown.jpg" alt="Premium fashion styling">
            <div style="position: absolute; bottom: 40px; left: 40px; right: 40px; color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.5); z-index: 2;">
                <p style="font-family: var(--font-serif); font-size: 1.8rem; font-style: italic; font-weight:600; margin-bottom: 0.5rem;">"Fashion is the armor to survive the reality of everyday life."</p>
                <span style="font-size:0.8rem; letter-spacing:0.1em; text-transform:uppercase;">— Bill Cunningham</span>
            </div>
            <div style="position: absolute; inset:0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%); z-index:1;"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
