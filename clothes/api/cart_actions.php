<?php
/**
 * cart_actions.php - REST API/AJAX Handler for Shopping Cart CRUD
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$response = ['success' => false, 'message' => 'Invalid Request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!check_csrf($csrf_token)) {
        $response['message'] = "Security Exception: Invalid CSRF Token.";
        echo json_encode($response);
        exit;
    }
    
    $action = sanitize($_POST['action'] ?? '');
    $product_id = (int)($_POST['product_id'] ?? 0);
    $size = sanitize($_POST['size'] ?? 'M');
    $color = sanitize($_POST['color'] ?? 'Black');
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0) {
        $response['message'] = "Product identifier is invalid.";
        echo json_encode($response);
        exit;
    }
    
    // Fetch product stock and details from DB
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
    } catch (PDOException $e) {
        $response['message'] = "Database error: " . $e->getMessage();
        echo json_encode($response);
        exit;
    }
    
    if (!$product) {
        $response['message'] = "This product does not exist in our system.";
        echo json_encode($response);
        exit;
    }
    
    // Generate a unique cart item key based on product ID, size, and color
    $cart_key = $product_id . '-' . $size . '-' . $color;
    
    switch ($action) {
        case 'add':
            // Check stock level
            $current_qty_in_cart = isset($_SESSION['cart'][$cart_key]) ? $_SESSION['cart'][$cart_key]['quantity'] : 0;
            $requested_total = $current_qty_in_cart + $quantity;
            
            if ($product['stock'] < $requested_total) {
                $response['message'] = "Insufficient stock. Only " . $product['stock'] . " items available. You already have " . $current_qty_in_cart . " in your cart.";
            } else {
                $_SESSION['cart'][$cart_key] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'price' => (float)$product['price'],
                    'discount_price' => $product['discount_price'] !== null ? (float)$product['discount_price'] : null,
                    'image' => $product['image'],
                    'size' => $size,
                    'color' => $color,
                    'quantity' => $requested_total
                ];
                
                $response['success'] = true;
                $response['message'] = "Added " . e($product['name']) . " (" . e($size) . "/" . e($color) . ") to cart!";
                $response['cart_count'] = get_cart_count();
            }
            break;
            
        case 'update':
            if ($quantity <= 0) {
                // If quantity is 0 or less, remove item
                unset($_SESSION['cart'][$cart_key]);
                $response['success'] = true;
                $response['message'] = "Item removed from cart.";
            } else {
                // Validate stock limit
                if ($product['stock'] < $quantity) {
                    $response['message'] = "Only " . $product['stock'] . " items available in stock.";
                } else {
                    $_SESSION['cart'][$cart_key]['quantity'] = $quantity;
                    $response['success'] = true;
                    $response['message'] = "Cart updated successfully.";
                }
            }
            $response['cart_count'] = get_cart_count();
            $response['subtotal'] = get_cart_subtotal();
            break;
            
        case 'remove':
            if (isset($_SESSION['cart'][$cart_key])) {
                unset($_SESSION['cart'][$cart_key]);
                $response['success'] = true;
                $response['message'] = "Item removed from cart.";
            } else {
                $response['message'] = "Item not found in cart.";
            }
            $response['cart_count'] = get_cart_count();
            $response['subtotal'] = get_cart_subtotal();
            break;
            
        default:
            $response['message'] = "Unknown cart action requested.";
            break;
    }
}

echo json_encode($response);
exit;
