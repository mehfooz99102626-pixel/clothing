<?php
/**
 * forgot-password.php - Mocked password recovery flow
 */
$page_title = 'Recover Password';
require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_request();
    
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // In real application, generate unique token, store in DB with expiration, and mail it.
                $success = "A password reset link has been sent to " . e($email) . ". Please check your inbox (and spam folder) shortly.";
            } else {
                // Security practice: Don't explicitly reveal if email is not found,
                // but for mockup convenience we will verify.
                $error = "No account found associated with this email address.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="auth-split-screen" style="max-width: 900px; margin: 5rem auto; min-height: auto;">
        <!-- Form Side -->
        <div class="auth-form-side" style="padding: 4rem 3rem;">
            <h2>Recover Password</h2>
            <p class="subtitle" style="margin-bottom: 2rem;">Enter your registered email below, and we will send you a secure link to reset your account credentials.</p>
            
            <?php if (!empty($error)): ?>
                <div class="form-alert form-alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="form-alert form-alert-success"><?php echo e($success); ?></div>
            <?php endif; ?>
            
            <form action="forgot-password.php" method="POST">
                <?php echo csrf_field(); ?>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@example.com" value="<?php echo e($email); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-solid" style="width: 100%; margin-top: 1rem;">Send Reset Link</button>
            </form>
            
            <div class="form-footer-link" style="margin-top: 2rem;">
                <span>Remember password? <a href="login.php" style="color: var(--accent); font-weight:600;">Sign In</a></span>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
