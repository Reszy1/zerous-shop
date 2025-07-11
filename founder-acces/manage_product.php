<?php
include 'auth.php'; // Keamanan

$isEditing = false;
$product = [
    'id' => '', 'name' => '', 'category' => 'physical', 'price' => '', 
    'stock' => '', 'image' => '', 'description' => '', 'badge' => ''
];

// Logika untuk Mode Edit
if (isset($_GET['id'])) {
    $isEditing = true;
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        $_SESSION['error'] = "Product not found!";
        header('Location: products.php');
        exit;
    }
}

// Logika untuk Memproses Form (Add & Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = trim($_POST['description']);
    $badge = trim($_POST['badge']);
    $old_image = $_POST['old_image'];
    $image_name = $old_image;

    // Handle upload gambar
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/';
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name);
        if ($old_image && $old_image != $image_name) {
            @unlink($upload_dir . $old_image);
        }
    }

    if (!empty($id)) { // Update
        $sql = "UPDATE products SET name=?, category=?, price=?, stock=?, image=?, description=?, badge=? WHERE id=?";
        $params = [$name, $category, $price, $stock, $image_name, $description, $badge, $id];
        $_SESSION['success'] = "Product updated successfully!";
    } else { // Insert
        $sql = "INSERT INTO products (name, category, price, stock, image, description, badge) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$name, $category, $price, $stock, $image_name, $description, $badge];
        $_SESSION['success'] = "Product added successfully!";
    }
    
    $pdo->prepare($sql)->execute($params);
    header('Location: products.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $isEditing ? 'Edit' : 'Add' ?> Product - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<?php 
// Menggunakan header admin standar
$page_title = $isEditing ? 'Edit Product' : 'Add Product';
include 'admin_header.php'; 
?>

<div class="content-header">
    <div>
        <h1><?= $isEditing ? 'Edit Product' : 'Add New Product' ?></h1>
        <p><?= $isEditing ? 'Update the details of your product.' : 'Fill in the details to create a new product.' ?></p>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="form-grid-layout">
    <div class="main-details">
        <div class="form-section-card">
            <h3>Product Information</h3>
            <input type="hidden" name="id" value="<?= $product['id'] ?>">
            <input type="hidden" name="old_image" value="<?= $product['image'] ?>">

            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="8"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
        </div>

        <div class="form-section-card">
            <h3>Product Image</h3>
            <div class="image-upload-box">
                <input type="file" id="image" name="image" class="image-upload-input" accept="image/*">
                <label for="image" class="image-upload-label">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Click to upload or drag & drop</span>
                    <small>PNG, JPG, WEBP (Max 800x400px)</small>
                </label>
                <?php if ($isEditing && $product['image']): ?>
                    <img src="../assets/<?= htmlspecialchars($product['image']) ?>" alt="Current Image" class="image-preview">
                <?php endif; ?>
            </div>
             <small class="form-text">Leave blank to keep the current image when editing.</small>
        </div>
    </div>

    <div class="side-details">
        <div class="form-section-card">
            <h3>Pricing & Inventory</h3>
            <div class="form-group">
                <label for="price">Price (IDR) *</label>
                <input type="number" step="100" id="price" name="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock *</label>
                <input type="number" id="stock" name="stock" class="form-control" value="<?= htmlspecialchars($product['stock']) ?>" required>
            </div>
        </div>

        <div class="form-section-card">
            <h3>Details</h3>
            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="physical" <?= $product['category'] === 'physical' ? 'selected' : '' ?>>Physical</option>
                    <option value="digital" <?= $product['category'] === 'digital' ? 'selected' : '' ?>>Digital</option>
                </select>
            </div>
            <div class="form-group">
                <label for="badge">Badge (Optional)</label>
                <input type="text" id="badge" name="badge" class="form-control" value="<?= htmlspecialchars($product['badge']) ?>" placeholder="e.g., Best Seller">
            </div>
        </div>
    </div>

    <div class="form-actions-footer">
        <a href="products.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> <?= $isEditing ? 'Update' : 'Save' ?> Product</button>
    </div>
</form>

<?php 
// Menggunakan footer admin standar
include 'admin_footer.php'; 
?>

</body>
</html>