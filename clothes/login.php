<?php
/**
 * login.php - User Login Page
 */
$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect(SITE_URL . '/index.php');
}

$errors = [];
$email = '';
$redirect_url = sanitize($_GET['redirect'] ?? SITE_URL . '/profile.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    verify_csrf_request();
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $redirect_url = sanitize($_POST['redirect'] ?? SITE_URL . '/profile.php');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set Sessions
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Regenerate session ID
                session_regenerate_id(true);
                
                // If it is an admin, they might prefer the dashboard
                if ($user['role'] === 'admin' && $redirect_url === SITE_URL . '/profile.php') {
                    $redirect_url = SITE_URL . '/admin/dashboard.php';
                }
                
                redirect($redirect_url);
            } else {
                $errors[] = "Invalid email or password.";
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
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to your VÈLO account to access orders, checkout faster, and save your favorites.</p>
            
            <?php if (!empty($errors)): ?>
                <div class="form-alert form-alert-danger">
                    <ul style="padding-left: 1.25rem; list-style: disc;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
                <div class="form-alert form-alert-danger">
                    Please log in as an administrator to access the Admin Panel.
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="auth-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="redirect" value="<?php echo e($redirect_url); ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?php echo e($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.6rem;">
                        <label for="password" style="margin-bottom:0;">Password</label>
                        <a href="forgot-password.php" style="font-size:0.75rem; color: var(--text-muted); font-weight:500;">Forgot Password?</a>
                    </div>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                
                <button type="submit" class="btn btn-solid" style="width: 100%; margin-top: 1rem;">Sign In</button>
            </form>
            
            <div class="form-footer-link">
                <span>New to VÈLO? <a href="register.php" style="color: var(--accent); font-weight:600;">Create an Account</a></span>
            </div>
        </div>
        
        <!-- Image Side -->
        <div class="auth-image-side">
            <img src="<?php echo SITE_URL; ?>/assets/images/denim_jacket.jpg" alt="Premium fashion styling">
            <div style="position: absolute; bottom: 40px; left: 40px; right: 40px; color: #ffffff; text-shadow: 0 2px 10px rgba(0,0,0,0.5); z-index: 2;">
                <p style="font-family: var(--font-serif); font-size: 1.8rem; font-style: italic; font-weight:600; margin-bottom: 0.5rem;">"Style is a way to say who you are without having to speak."</p>
                <span style="font-size:0.8rem; letter-spacing:0.1em; text-transform:uppercase;">— Rachel Zoe</span>
            </div>
            <div style="position: absolute; inset:0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.2) 100%); z-index:1;"></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
