<?php
/**
 * reset_admin.php - Database password helper setup
 * Run this file in your browser to align the database hashed passwords.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

echo "<h2>VÈLO Password Alignment Utility</h2>";

try {
    // 1. Admin Account Hashing (Admin123!)
    $admin_pass = 'Admin123!';
    $admin_hash = password_hash($admin_pass, PASSWORD_BCRYPT);
    $stmt1 = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@fashionstore.com'");
    $stmt1->execute([$admin_hash]);
    echo "✓ Admin password aligned to: <strong>" . htmlspecialchars($admin_pass) . "</strong><br>";

    // 2. Customer Account Hashing (User123!)
    $user_pass = 'User123!';
    $user_hash = password_hash($user_pass, PASSWORD_BCRYPT);
    $stmt2 = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'john@gmail.com'");
    $stmt2->execute([$user_hash]);
    echo "✓ Customer John Doe password aligned to: <strong>" . htmlspecialchars($user_pass) . "</strong><br><br>";

    echo "<p style='color:var(--success); font-weight:600;'>Success! The database credentials have been updated. You can delete this file now for security.</p>";
    echo "<a href='login.php'>Go to Sign-In Screen</a>";
} catch (PDOException $e) {
    echo "<p style='color:var(--error);'>Error connecting to database. Please make sure MySQL is running and the database 'clothing_store' is imported.</p>";
    echo "Error details: " . htmlspecialchars($e->getMessage());
}
