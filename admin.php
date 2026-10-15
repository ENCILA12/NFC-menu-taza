<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$json_file = __DIR__ . '/menu.json';
$menu_items = json_decode(file_get_contents($json_file), true);

$config_file = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($config_file), true) ?: ['mode' => 'static'];

$gallery_file = __DIR__ . '/gallery.json';
$gallery_items = json_decode(file_get_contents($gallery_file), true) ?: [];

$message = '';

// Handle Mode Toggle
if (isset($_POST['update_mode'])) {
    $config['mode'] = $_POST['menu_mode'];
    file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
    $message = "Menu mode updated successfully.";
}

// Handle Delete Product
if (isset($_GET['delete_product'])) {
    $id_to_delete = (int)$_GET['delete_product'];
    $menu_items = array_filter($menu_items, function($item) use ($id_to_delete) {
        return $item['id'] !== $id_to_delete;
    });
    $menu_items = array_values($menu_items);
    file_put_contents($json_file, json_encode($menu_items, JSON_PRETTY_PRINT));
    $message = "Product deleted successfully.";
}

// Handle Delete Gallery Image
if (isset($_GET['delete_gallery'])) {
    $index = (int)$_GET['delete_gallery'];
    if (isset($gallery_items[$index])) {
        // Optional: unlink file here if you want to physically delete it
        unset($gallery_items[$index]);
        $gallery_items = array_values($gallery_items);
        file_put_contents($gallery_file, json_encode($gallery_items, JSON_PRETTY_PRINT));
        $message = "Gallery image deleted.";
    }
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $tags = array_filter(array_map('trim', explode(',', $_POST['tags'])));
    
    $imagePath = 'assets/images/default.png';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_prod_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/assets/images/' . $fileName)) {
            $imagePath = 'assets/images/' . $fileName;
        }
    }
    $newId = count($menu_items) > 0 ? max(array_column($menu_items, 'id')) + 1 : 1;
    $menu_items[] = ['id' => $newId, 'name' => $name, 'category' => $category, 'price' => $price, 'description' => $description, 'image' => $imagePath, 'tags' => $tags, 'is_available' => true];
    file_put_contents($json_file, json_encode($menu_items, JSON_PRETTY_PRINT));
    $message = "Product added successfully.";
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = (int)$_POST['edit_id'];
    foreach ($menu_items as &$item) {
        if ($item['id'] === $id) {
            $item['name'] = $_POST['edit_name'];
            $item['category'] = $_POST['edit_category'];
            $item['price'] = $_POST['edit_price'];
            $item['description'] = $_POST['edit_description'];
            $item['tags'] = array_filter(array_map('trim', explode(',', $_POST['edit_tags'])));
            $item['is_available'] = isset($_POST['edit_is_available']) ? true : false;
            
            if (isset($_FILES['edit_image']) && $_FILES['edit_image']['error'] === UPLOAD_ERR_OK) {
                $fileName = time() . '_prod_' . basename($_FILES['edit_image']['name']);
                if (move_uploaded_file($_FILES['edit_image']['tmp_name'], __DIR__ . '/assets/images/' . $fileName)) {
                    $item['image'] = 'assets/images/' . $fileName;
                }
            }
            break;
        }
    }
    file_put_contents($json_file, json_encode($menu_items, JSON_PRETTY_PRINT));
    $message = "Product updated successfully.";
}

// Handle Upload Gallery Menu Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery'])) {
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_gal_' . basename($_FILES['gallery_image']['name']);
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], __DIR__ . '/assets/images/' . $fileName)) {
            $gallery_items[] = 'assets/images/' . $fileName;
            file_put_contents($gallery_file, json_encode($gallery_items, JSON_PRETTY_PRINT));
            $message = "Menu page uploaded successfully.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Taza Brew</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary-color: #8C5F4D; --bg-color: #FAFAFA; --text-main: #33241D; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg-color); color: var(--text-main); margin: 0; }
        .header { background: var(--primary-color); color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; margin: 0; }
        .btn { padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; border: none; font-size: 0.9rem; transition: 0.3s; }
        .btn-light { background: white; color: var(--primary-color); }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-danger { background: #d9534f; color: white; }
        .container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; display: grid; gap: 2rem; }
        .card { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .card h2 { margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 0.5rem; margin-bottom: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .img-preview { width: 60px; height: auto; border-radius: 8px; }
        .alert { padding: 1rem; background: #d4edda; color: #155724; border-radius: 8px; margin-bottom: 1rem; }
        .flex-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media(max-width: 768px) { .flex-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Taza Brew - Admin Panel</h1>
        <div style="display:flex; gap:1rem;">
            <a href="index.php" class="btn btn-light" target="_blank"><i class="ph ph-eye"></i> View Menu</a>
            <a href="logout.php" class="btn btn-light"><i class="ph ph-sign-out"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if($message): ?><div class="alert"><?= $message ?></div><?php endif; ?>

        <!-- Active Mode Settings -->
        <div class="card">
            <h2>⚙️ Menu Display Settings</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Choose how customers will see your menu:</label>
                    <select name="menu_mode">
                        <option value="static" <?= $config['mode'] === 'static' ? 'selected' : '' ?>>Mode A: Static Image Gallery (Original Menu Pictures)</option>
                        <option value="interactive" <?= $config['mode'] === 'interactive' ? 'selected' : '' ?>>Mode B: Interactive Grid (Individual Configured Products)</option>
                    </select>
                </div>
                <button type="submit" name="update_mode" class="btn btn-primary">Save Settings</button>
            </form>
        </div>

        <div class="flex-row">
            <!-- Mode A Manager -->
            <div class="card">
                <h2>📸 Mode A: Full Menu Pictures</h2>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 1.5rem;">
                    <div class="form-group">
                        <label>Upload Physical Menu Page</label>
                        <input type="file" name="gallery_image" accept="image/*" required>
                    </div>
                    <button type="submit" name="upload_gallery" class="btn btn-primary">Upload Page</button>
                </form>
                
                <table>
                    <thead><tr><th>Preview</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($gallery_items as $index => $img): ?>
                        <tr>
                            <td><img src="<?= $img ?>" class="img-preview" alt="menu page"></td>
                            <td><a href="?delete_gallery=<?= $index ?>" class="btn btn-danger"><i class="ph ph-trash"></i></a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($gallery_items) === 0): ?><tr><td colspan="2">No images uploaded.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mode B Manager -->
            <div class="card">
                <h2>☕ Mode B: Individual Products</h2>
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 1.5rem;">
                    <div class="form-group"><input type="text" name="name" required placeholder="Product Name"></div>
                    <div class="form-group"><input type="text" name="category" required placeholder="Category"></div>
                    <div class="form-group"><input type="text" name="price" required placeholder="Price"></div>
                    <div class="form-group"><input type="file" name="image" accept="image/*" required></div>
                    <div class="form-group"><input type="text" name="tags" placeholder="Tags (comma separated)"></div>
                    <div class="form-group"><textarea name="description" rows="2" required placeholder="Description..."></textarea></div>
                    <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                </form>

                <?php 
                $grouped_menu = [];
                foreach ($menu_items as $item) {
                    $cat = $item['category'];
                    if (!isset($grouped_menu[$cat])) {
                        $grouped_menu[$cat] = [];
                    }
                    $grouped_menu[$cat][] = $item;
                }
                ?>
                
                <?php foreach($grouped_menu as $cat_name => $items_in_cat): ?>
                <h3 style="margin-top: 1.5rem; color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 0.5rem;"><?= htmlspecialchars($cat_name) ?></h3>
                <table style="font-size:0.9rem;">
                    <thead><tr><th>Img</th><th>Name</th><th>Status</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php foreach($items_in_cat as $item): ?>
                        <tr>
                            <td><img src="<?= $item['image'] ?>" class="img-preview" style="width:40px;"></td>
                            <td>
                                <?= htmlspecialchars($item['name']) ?><br>
                                <small style="color:#777;"><?= htmlspecialchars($item['category']) ?></small>
                            </td>
                            <td>
                                <?= (isset($item['is_available']) && $item['is_available'] === false) ? '<span style="color:red;font-size:0.8rem;">Not Avail</span>' : '<span style="color:green;font-size:0.8rem;">Avail</span>' ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary" style="padding:5px 8px;" onclick='openEditModal(<?= json_encode($item) ?>)'><i class="ph ph-pencil-simple"></i></button>
                                <a href="?delete_product=<?= $item['id'] ?>" class="btn btn-danger" style="padding:5px 8px;" onclick="return confirm('Are you sure you want to delete this product?');"><i class="ph ph-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:100; align-items:center; justify-content:center;">
        <div style="background:white; width:90%; max-width:500px; padding:20px; border-radius:15px; max-height:85vh; overflow-y:auto; position: relative;">
            <h2 style="margin-top:0; color:var(--primary-color);">Edit Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="edit_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="edit_category" id="edit_category" required>
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="text" name="edit_price" id="edit_price" required>
                </div>
                <div class="form-group">
                    <label>Change Image (Optional)</label>
                    <input type="file" name="edit_image" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Tags (comma separated)</label>
                    <input type="text" name="edit_tags" id="edit_tags">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="edit_description" id="edit_description" rows="2" required></textarea>
                </div>
                <div class="form-group">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" name="edit_is_available" id="edit_is_available" value="1" style="width:auto;"> 
                        Product is Available
                    </label>
                </div>
                <div style="display:flex; gap:10px; margin-top:1.5rem;">
                    <button type="submit" name="edit_product" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-light" onclick="document.getElementById('editModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_name').value = item.name;
            document.getElementById('edit_category').value = item.category;
            document.getElementById('edit_price').value = item.price;
            document.getElementById('edit_tags').value = (item.tags || []).join(', ');
            document.getElementById('edit_description').value = item.description;
            document.getElementById('edit_is_available').checked = (item.is_available !== false);
            
            var modal = document.getElementById('editModal');
            modal.style.display = 'flex';
        }
    </script>
</body>
</html>
