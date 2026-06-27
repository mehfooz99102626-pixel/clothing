<?php
/**
 * wishlist_actions.php - REST API/AJAX Handler for Customer Wishlist toggles
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid Request'];

if (!is_logged_in()) {
    $response['message'] = "Please log in to manage your wishlist.";
    $response['redirect'] = SITE_URL . '/login.php';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!check_csrf($csrf_token)) {
        $response['message'] = "Security Exception: Invalid CSRF Token.";
        echo json_encode($response);
        exit;
    }
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    $user_id = get_logged_in_user_id();
    
    if ($product_id <= 0) {
        $response['message'] = "Product ID is invalid.";
        echo json_encode($response);
        exit;
    }
    
    try {
        // Check if product exists in DB first
        $p_check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $p_check->execute([$product_id]);
        if (!$p_check->fetch()) {
            $response['message'] = "Product does not exist.";
            echo json_encode($response);
            exit;
        }
        
        // Check if it's already in wishlist
        $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Remove
            $del_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $del_stmt->execute([$user_id, $product_id]);
            $response['success'] = true;
            $response['status'] = 'removed';
            $response['message'] = "Product removed from your wishlist.";
        } else {
            // Add
            $add_stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $add_stmt->execute([$user_id, $product_id]);
            $response['success'] = true;
            $response['status'] = 'added';
            $response['message'] = "Product saved to your wishlist!";
        }
        
        // Fetch new wishlist count
        $response['wishlist_count'] = get_wishlist_count($pdo);
        
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
    }
}

echo json_encode($response);
exit;
