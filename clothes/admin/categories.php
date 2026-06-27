<?php
/**
 * admin/categories.php - Category Management Panel
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Force admin credentials
require_admin();

$success_msg = '';
$error_msg = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_request();
    
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if (empty($name) || empty($slug)) {
            $error_msg = "Category name and slug are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
                $stmt->execute([$name, $slug, $description]);
                $success_msg = "Category added successfully!";
            } catch (PDOException $e) {
                $error_msg = "Failed to add category (slug must be unique): " . $e->getMessage();
            }
        }
    }
    
    elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        if ($id <= 0 || empty($name) || empty($slug)) {
            $error_msg = "Invalid inputs. Completing name and slug is required.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $id]);
                $success_msg = "Category updated successfully!";
            } catch (PDOException $e) {
                $error_msg = "Failed to update category: " . $e->getMessage();
            }
        }
    }
}

// Handle GET Delete
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $token = $_GET['csrf_token'] ?? '';
    
    if (!check_csrf($token)) {
        $error_msg = "Security Exception: CSRF token validation failed.";
    } else {
        try {
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$del_id]);
            $success_msg = "Category deleted successfully.";
        } catch (PDOException $e) {
            $error_msg = "Failed to delete category (it may contain products): " . $e->getMessage();
        }
    }
}

// Fetch categories list
try {
    $cats_stmt = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.id ASC");
    $categories = $cats_stmt->fetchAll();
} catch (PDOException $e) {
    die("Database fetch failed: " . $e->getMessage());
}

// Find edit item values
$edit_category = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    foreach ($categories as $c) {
        if ($c['id'] === $edit_id) {
            $edit_category = $c;
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
    <title>Manage Categories | <?php echo SITE_NAME; ?></title>
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
            <a href="products.php" class="admin-menu-item">
                <i class="fas fa-tshirt"></i> Products CRUD
            </a>
            <a href="categories.php" class="admin-menu-item active">
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
            <h2>Collection Categories</h2>
            <a href="categories.php" class="btn btn-solid" style="padding: 0.6rem 1.2rem; font-size: 0.75rem;"><i class="fas fa-plus" style="margin-right:6px;"></i>Add New Category</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="form-alert form-alert-success"><?php echo e($success_msg); ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="form-alert form-alert-danger"><?php echo e($error_msg); ?></div>
        <?php endif; ?>

        <!-- Form and Table Grid -->
        <div style="display:grid; grid-template-columns: 1fr; gap: 2.5rem;">
            <!-- Form Card -->
            <div class="checkout-section-card" style="margin-bottom:0;">
                <h3><?php echo $edit_category ? 'Edit Category: ' . e($edit_category['name']) : 'Create Category Collection'; ?></h3>
                
                <form action="categories.php" method="POST" style="margin-top:1.5rem;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php endif; ?>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                        <div class="form-group">
                            <label for="name">Category Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo $edit_category ? e($edit_category['name']) : ''; ?>" placeholder="e.g. Outerwear" required>
                        </div>
                        <div class="form-group">
                            <label for="slug">Category Slug (URL friendly)</label>
                            <input type="text" id="slug" name="slug" class="form-control" value="<?php echo $edit_category ? e($edit_category['slug']) : ''; ?>" placeholder="e.g. outerwear" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Collection Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Describe the season details, styling focus..." style="resize:none;"><?php echo $edit_category ? e($edit_category['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display:flex; gap:1rem;">
                        <button type="submit" class="btn btn-solid" style="padding:0.75rem 2.5rem; font-size:0.8rem;"><?php echo $edit_category ? 'Save Changes' : 'Create Category'; ?></button>
                        <?php if ($edit_category): ?>
                            <a href="categories.php" class="btn btn-outline" style="padding:0.75rem 2.5rem; font-size:0.8rem;">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Categories Table -->
            <div class="checkout-section-card" style="margin-bottom:0;">
                <h3>Active Collections</h3>
                
                <div style="overflow-x:auto; margin-top:1.5rem;">
                    <table class="orders-history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Associated Products</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo $cat['id']; ?></td>
                                    <td style="font-weight:600;"><?php echo e($cat['name']); ?></td>
                                    <td><code><?php echo e($cat['slug']); ?></code></td>
                                    <td style="color:var(--text-muted); font-size:0.85rem; max-width: 300px;"><?php echo e($cat['description']); ?></td>
                                    <td style="font-weight:600; text-align:center;"><?php echo $cat['product_count']; ?></td>
                                    <td>
                                        <div style="display:flex; gap:0.5rem;">
                                            <a href="categories.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-outline" style="padding:0.3rem 0.8rem; font-size:0.7rem;"><i class="fas fa-edit"></i></a>
                                            <a href="categories.php?delete_id=<?php echo $cat['id']; ?>&csrf_token=<?php echo urlencode($_SESSION['csrf_token']); ?>" onclick="return confirm('Are you sure you want to delete this category? Associated products may be affected.');" class="btn btn-outline" style="padding:0.3rem 0.8rem; font-size:0.7rem; color:var(--error); border-color:var(--border);"><i class="fas fa-trash-alt"></i></a>
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
// Slugs helper
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
