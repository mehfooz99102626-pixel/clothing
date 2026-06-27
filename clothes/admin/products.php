<?php
/**
 * admin/products.php - Product CRUD Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Force admin credentials
require_admin();

$success_msg = '';
$error_msg = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_request();
    
    $action = sanitize($_POST['action'] ?? '');
    
    // Add Product
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = filter_var($_POST['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);
        $discount_price = $_POST['discount_price'] !== '' ? filter_var($_POST['discount_price'], FILTER_VALIDATE_FLOAT) : null;
        $category_id = (int)($_POST['category_id'] ?? 0);
        $sizes = sanitize($_POST['sizes'] ?? 'S,M,L,XL');
        $colors = sanitize($_POST['colors'] ?? 'Black,White');
        $stock = (int)($_POST['stock'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;
        $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
        
        // Image Upload
        $image_path = 'assets/images/denim_jacket.jpg'; // default placeholder
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = sanitize($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $dest_path = __DIR__ . '/../assets/images/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_path = 'assets/images/' . $new_file_name;
                }
            } else {
                $error_msg = "Invalid file type. Allowed formats: JPG, JPEG, PNG, WEBP.";
            }
        }
        
        if (empty($name) || empty($slug) || $price <= 0 || $category_id <= 0) {
            $error_msg = "Name, slug, positive price, and category are required.";
        } elseif (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, price, discount_price, image, sizes, colors, stock, is_featured, is_new_arrival, is_best_seller) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $slug, $description, $price, $discount_price, $image_path, $sizes, $colors, $stock, $is_featured, $is_new_arrival, $is_best_seller]);
                $success_msg = "Product created successfully!";
            } catch (PDOException $e) {
                $error_msg = "Failed to insert product: " . $e->getMessage();
            }
        }
    }
    
    // Edit Product
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $price = filter_var($_POST['price'] ?? 0.00, FILTER_VALIDATE_FLOAT);
        $discount_price = $_POST['discount_price'] !== '' ? filter_var($_POST['discount_price'], FILTER_VALIDATE_FLOAT) : null;
        $category_id = (int)($_POST['category_id'] ?? 0);
        $sizes = sanitize($_POST['sizes'] ?? 'S,M,L,XL');
        $colors = sanitize($_POST['colors'] ?? 'Black,White');
        $stock = (int)($_POST['stock'] ?? 0);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0;
        $is_best_seller = isset($_POST['is_best_seller']) ? 1 : 0;
        
        // Image Upload
        $image_path = sanitize($_POST['existing_image'] ?? '');
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_name = sanitize($_FILES['image']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($file_ext, $allowed_exts)) {
                $new_file_name = 'prod_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
                $dest_path = __DIR__ . '/../assets/images/' . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $dest_path)) {
                    $image_path = 'assets/images/' . $new_file_name;
                }
            } else {
                $error_msg = "Invalid file type. Allowed formats: JPG, JPEG, PNG, WEBP.";
            }
        }
        
        if ($id <= 0 || empty($name) || empty($slug) || $price <= 0 || $category_id <= 0) {
            $error_msg = "Invalid inputs. Ensure name, slug, price, and category are completed.";
        } elseif (empty($error_msg)) {
            try {
                $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, discount_price = ?, image = ?, sizes = ?, colors = ?, stock = ?, is_featured = ?, is_new_arrival = ?, is_best_seller = ? WHERE id = ?");
                $stmt->execute([$category_id, $name, $slug, $description, $price, $discount_price, $image_path, $sizes, $colors, $stock, $is_featured, $is_new_arrival, $is_best_seller, $id]);
                $success_msg = "Product updated successfully!";
            } catch (PDOException $e) {
                $error_msg = "Failed to update product: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Product (via GET for simple dashboard operations, secure it with CSRF token link)
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $token = $_GET['csrf_token'] ?? '';
    
    if (!check_csrf($token)) {
        $error_msg = "Security Exception: CSRF token validation failed.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = "Product deleted successfully.";
        } catch (PDOException $e) {
            $error_msg = "Failed to delete product: " . $e->getMessage();
        }
    }
}

// Fetch Categories for dropdowns
try {
    $cats_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $cats_stmt->fetchAll();
    
    // Fetch Products List
    $prods_stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
    $products = $prods_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database fetch failed: " . $e->getMessage());
}

// Fetch Edit product values if requested
$edit_product = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    foreach ($products as $p) {
        if ($p['id'] === $edit_id) {
            $edit_product = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products | <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-logo">
            VÈLO<span>.</span> Admin
        </div>
        <nav class="admin-menu">
            <a href="dashboard.php" class="admin-menu-item">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="products.php" class="admin-menu-item active">
                <i class="fas fa-tshirt"></i> Products CRUD
            </a>
            <a href="categories.php" class="admin-menu-item">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="orders.php" class="admin-menu-item">
                <i class="fas fa-shopping-bag"></i> Orders Log
            </a>
            <a href="customers.php" class="admin-menu-item">
                <i class="fas fa-users"></i> Customers
            </a>
            <hr style="border:none; border-top: 1px solid #27272c; margin: 1.5rem 0;">
            <a href="../index.php" class="admin-menu-item" style="color: var(--accent);">
                <i class="fas fa-external-link-alt"></i> Visit Store
            </a>
            <a href="../logout.php" class="admin-menu-item" style="color: var(--error);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- Main -->
    <main class="admin-main">
        <div class="admin-topbar">
            <h2>Product Catalog Manager</h2>
            <a href="products.php" class="btn btn-solid" style="padding: 0.6rem 1.2rem; font-size: 0.75rem;"><i class="fas fa-plus" style="margin-right:6px;"></i>Add New Product</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="form-alert form-alert-success"><?php echo e($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="form-alert form-alert-danger"><?php echo e($error_msg); ?></div>
        <?php endif; ?>

        <!-- Split CRUD panels: Editor Form (top/side) & List (bottom) -->
        <div style="display:grid; grid-template-columns: 1fr; gap: 2rem; margin-bottom: 3rem;">
            <!-- Form Card -->
            <div class="checkout-section-card" style="margin-bottom:0;">
                <h3><?php echo $edit_product ? 'Edit Product: ' . e($edit_product['name']) : 'Add Fashion Apparel Product'; ?></h3>
                
                <form action="products.php" method="POST" enctype="multipart/form-data" style="margin-top:1.5rem;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                        <input type="hidden" name="existing_image" value="<?php echo e($edit_product['image']); ?>">
                    <?php endif; ?>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                        <div class="form-group">
                            <label for="name">Product Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $edit_product ? e($edit_product['name']) : ''; ?>" placeholder="e.g. Classic Trench Coat" required>
                        </div>
                        <div class="form-group">
                            <label for="slug">Product Slug (URL friendly)</label>
                            <input type="text" id="slug" name="slug" class="form-control" value="<?php echo $edit_product ? e($edit_product['slug']) : ''; ?>" placeholder="e.g. classic-trench-coat" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Product Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Enter materials, fit type, pockets etc..." style="resize:none;"><?php echo $edit_product ? e($edit_product['description']) : ''; ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem;">
                        <div class="form-group">
                            <label for="price">Retail Price ($)</label>
                            <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?php echo $edit_product ? e($edit_product['price']) : ''; ?>" placeholder="0.00" required>
                        </div>
                        <div class="form-group">
                            <label for="discount_price">Discount Price ($) (Optional)</label>
                            <input type="number" step="0.01" id="discount_price" name="discount_price" class="form-control" value="<?php echo $edit_product && $edit_product['discount_price'] !== null ? e($edit_product['discount_price']) : ''; ?>" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="category_id">Category Collection</label>
                            <select id="category_id" name="category_id" class="form-control" required style="height:50px;">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($edit_product && $edit_product['category_id'] === $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.5rem;">
                        <div class="form-group">
                            <label for="sizes">Sizes (Comma separated)</label>
                            <input type="text" id="sizes" name="sizes" class="form-control" value="<?php echo $edit_product ? e($edit_product['sizes']) : 'S,M,L,XL'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="colors">Colors (Comma separated)</label>
                            <input type="text" id="colors" name="colors" class="form-control" value="<?php echo $edit_product ? e($edit_product['colors']) : 'Black,White,Navy,Gray'; ?>">
                        </div>
                        <div class="form-group">
                            <label for="stock">Inventory Stock</label>
                            <input type="number" id="stock" name="stock" class="form-control" value="<?php echo $edit_product ? e($edit_product['stock']) : '25'; ?>" required>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1.5fr 1fr; gap:1.5rem; align-items:center;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label for="image">Product Image File</label>
                            <input type="file" id="image" name="image" class="form-control" style="padding-top:10px;">
                            <?php if ($edit_product): ?>
                                <span style="font-size:0.75rem; color:var(--text-muted); display:block; margin-top:0.3rem;">Current Image: <?php echo e($edit_product['image']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Checkboxes tags -->
                        <div style="display:flex; justify-content:space-between; margin-top:20px;">
                            <label style="cursor:pointer; display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:600;">
                                <input type="checkbox" name="is_featured" value="1" <?php echo ($edit_product && $edit_product['is_featured']) ? 'checked' : ''; ?>>
                                <span>FEATURED</span>
                            </label>
                            <label style="cursor:pointer; display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:600;">
                                <input type="checkbox" name="is_new_arrival" value="1" <?php echo ($edit_product && $edit_product['is_new_arrival']) ? 'checked' : ''; ?>>
                                <span>NEW IN</span>
                            </label>
                            <label style="cursor:pointer; display:flex; align-items:center; gap:0.4rem; font-size:0.8rem; font-weight:600;">
                                <input type="checkbox" name="is_best_seller" value="1" <?php echo ($edit_product && $edit_product['is_best_seller']) ? 'checked' : ''; ?>>
                                <span>BEST SELLER</span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top:2.5rem; display:flex; gap:1.2rem;">
                        <button type="submit" class="btn btn-solid" style="padding:0.8rem 3rem; font-size:0.85rem;"><?php echo $edit_product ? 'Save Changes' : 'Publish Product'; ?></button>
                        <?php if ($edit_product): ?>
                            <a href="products.php" class="btn btn-outline" style="padding:0.8rem 3rem; font-size:0.85rem;">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Products Grid Table -->
            <div class="checkout-section-card" style="margin-bottom:0;">
                <h3>Product Stock Inventory</h3>
                <div style="overflow-x:auto; margin-top:1.5rem;">
                    <table class="orders-history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Tags</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?php echo $p['id']; ?></td>
                                    <td>
                                        <img src="../<?php echo e($p['image']); ?>" alt="product thumbnail" style="width:44px; height:54px; object-fit:cover; border:1px solid var(--border);">
                                    </td>
                                    <td style="font-weight:600;"><?php echo e($p['name']); ?></td>
                                    <td><?php echo e($p['category_name']); ?></td>
                                    <td>
                                        <?php if ($p['discount_price'] !== null): ?>
                                            <span style="color:var(--accent); font-weight:600;"><?php echo format_price($p['discount_price']); ?></span>
                                            <span style="text-decoration:line-through; font-size:0.75rem; color:var(--text-muted); margin-left:4px;"><?php echo format_price($p['price']); ?></span>
                                        <?php else: ?>
                                            <span><?php echo format_price($p['price']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight:600; color:<?php echo $p['stock'] > 10 ? 'var(--success)' : ($p['stock'] > 0 ? 'var(--accent)' : 'var(--error)'); ?>;">
                                            <?php echo $p['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex; flex-direction:column; gap:0.2rem; font-size:0.6rem;">
                                            <?php if ($p['is_featured']): ?><span style="background-color:rgba(184, 146, 85, 0.1); color:var(--accent); padding:2px 4px; font-weight:600; text-align:center;">FEAT</span><?php endif; ?>
                                            <?php if ($p['is_new_arrival']): ?><span style="background-color:rgba(0,120,240,0.1); color:#0078f0; padding:2px 4px; font-weight:600; text-align:center;">NEW</span><?php endif; ?>
                                            <?php if ($p['is_best_seller']): ?><span style="background-color:rgba(27,133,53,0.1); color:var(--success); padding:2px 4px; font-weight:600; text-align:center;">BEST</span><?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <a href="products.php?edit_id=<?php echo $p['id']; ?>" class="btn btn-outline" style="padding:0.3rem 0.8rem; font-size:0.7rem; letter-spacing:0.02em;"><i class="fas fa-edit"></i></a>
                                            <!-- Delete secure link -->
                                            <a href="products.php?delete_id=<?php echo $p['id']; ?>&csrf_token=<?php echo urlencode($_SESSION['csrf_token']); ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="btn btn-outline" style="padding:0.3rem 0.8rem; font-size:0.7rem; color:var(--error); border-color:var(--border);"><i class="fas fa-trash-alt"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Simple dynamic slug helper
const nameInput = document.getElementById('name');
const slugInput = document.getElementById('slug');
if (nameInput && slugInput && !slugInput.value) {
    nameInput.addEventListener('input', () => {
        slugInput.value = nameInput.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    });
}
</script>

</body>
</html>
