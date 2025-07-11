<?php 
$page_title = "Manage Products"; // Judul halaman dinamis
include 'admin_header.php'; // Menggunakan header baru

// Ambil semua produk dari database
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <div>
        <h1>Products</h1>
        <p>Manage your product inventory</p>
    </div>
    <div class="header-actions">
        <a href="manage_product.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Create Product</a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="table-container">
    <div class="table-toolbar">
        <button class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="Quick Search by Name...">
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th><input type="checkbox"></th>
                <th>ID</th>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Visibility</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><input type="checkbox"></td>
                <td><span class="text-muted"><?= $product['id'] ?></span></td>
                <td>
                    <div class="product-info">
                        <img src="../assets/<?= htmlspecialchars($product['image'] ?: 'placeholder.png') ?>" alt="Image" class="product-thumb">
                        <span><?= htmlspecialchars($product['name']) ?></span>
                    </div>
                </td>
                <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                <td><?= $product['stock'] > 0 ? $product['stock'] : '<span class="status-badge status-out-of-stock">Out of Stock</span>' ?></td>
                <td><span class="status-badge status-public">Public</span></td>
                <td class="actions">
                    <a href="manage_product.php?id=<?= $product['id'] ?>" class="action-btn" title="Edit"><i class="fa-solid fa-pencil"></i></a>
                    <form action="delete_product.php" method="POST" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <button type="submit" class="action-btn delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    <a href="../products/product.php?id=<?= $product['id'] ?>" target="_blank" class="action-btn" title="View in Shop"><i class="fa-solid fa-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
     <div class="table-footer">
        Showing <?= count($products) ?> results
    </div>
</div>

<?php include 'admin_footer.php'; // Menggunakan footer baru ?>