<?php
session_start();
// Logika PHP tidak diubah, hanya UI dan teks
if (!isset($_SESSION['email'])) {
    $_SESSION['login_notice'] = "Silakan login terlebih dahulu untuk mengakses halaman ini.";
    header('Location: ../user-login/dashboard.php');
    exit;
}
include '../db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$productId = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $quantity = (int)$_POST['quantity'];
    $productId = (int)$_POST['product_id'];
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = ['product_id' => $productId, 'quantity' => $quantity];
    }
    if (isset($_POST['buy_now'])) {
        header('Location: ../cart.php');
    } else {
        header('Location: product.php?id=' . $productId . '&added=1');
    }
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->bindValue(':id', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    header('Location: ../index.php');
    exit;
}

$reviewStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC LIMIT 5");
$reviewStmt->execute([$productId]);
$reviews = $reviewStmt->fetchAll();

$avgRatingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE product_id = ?");
$avgRatingStmt->execute([$productId]);
$ratingData = $avgRatingStmt->fetch();
$avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
$totalReviews = $ratingData['total_reviews'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
<div id="success-message" class="success-notification">
    <p>✅ Berhasil ditambahkan ke keranjang!</p>
</div>
<?php endif; ?>

<header>
    <div class="logo-title">
        <img src="../assets/logo.webp" alt="Logo" class="logo">
        <h1>Zerous Shop</h1>
    </div>
    <nav class="nav-grid">
        <a href="../index.php" class="nav-btn">Products</a>
        <a href="../reviews.php" class="nav-btn">Reviews</a>
        <a href="../index.php#news" class="nav-btn">News</a>
        <a href="../faq.php" class="nav-btn">FAQ</a>
        <a href="../user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>

<?php 
$_SESSION['current_page'] = 'product';
include '../search-bar.php'; 
?>

<style>
/* === UI BARU & PENYESUAIAN GAYA === */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
.main-content {
    flex: 1;
}

/* Notifikasi */
.success-notification {
    position: fixed; top: 20px; right: 20px; background: #28a745; color: white;
    padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    z-index: 1000; animation: slideIn 0.3s ease-out;
}
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

/* Kontainer utama */
.product-detail-container {
    display: flex; justify-content: center; padding: 2.5rem 1.5rem;
}
.product-detail {
    display: grid; grid-template-columns: 1fr 1.2fr; gap: 2.5rem;
    background: #1e293b; padding: 2rem; border-radius: 1rem; max-width: 1100px;
    width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.4);
}
/* Bagian Gambar */
.product-image-section { align-self: start; }
.product-main-image {
    width: 100%; border-radius: 0.75rem; border: 2px solid #334155;
}
/* Bagian Info */
.product-info-section { display: flex; flex-direction: column; gap: 1.25rem; }

.breadcrumb { font-size: 0.9rem; color: #94a3b8; margin-bottom: 0.5rem; }
.breadcrumb a { color: #3b82f6; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }

.product-category-tag {
    background-color: rgba(59, 130, 246, 0.2); color: #60a5fa;
    padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem;
    font-weight: 500; display: inline-block;
}
.product-title {
    font-size: 2.25rem; font-weight: 700; color: #f1f5f9; margin: 0;
}
.product-rating {
    display: flex; align-items: center; gap: 0.75rem;
}
.stars { display: flex; gap: 0.1rem; color: #facc15; }
.rating-text { font-size: 0.9rem; color: #94a3b8; }

/* Harga dan Stok */
.price-stock-wrapper {
    background: #0f172a; padding: 1.25rem; border-radius: 0.75rem;
    display: flex; justify-content: space-between; align-items: center;
}
.current-price { font-size: 1.75rem; font-weight: bold; color: #4ade80; }
.stock-info.in-stock { color: #a3e635; font-weight: 500; }
.stock-info.out-of-stock { color: #f87171; font-weight: 600; font-size: 1rem; }

/* Deskripsi */
.description-wrapper {
    background: #0f172a; padding: 1.25rem; border-radius: 0.75rem;
}
.description-wrapper h3 {
    margin: 0 0 0.75rem 0; font-size: 1rem; font-weight: 600;
    color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;
}
.product-description { line-height: 1.6; font-size: 0.95rem; color: #e2e8f0; }

/* Aksi (Jumlah & Tombol) */
.actions-wrapper {
    background: #0f172a; padding: 1.25rem; border-radius: 0.75rem;
}
.actions-wrapper h3 {
    margin: 0 0 1rem 0; font-size: 1rem; font-weight: 600;
    color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;
}
.quantity-box {
    display: flex; align-items: center; gap: 0.5rem; border: 1px solid #334155;
    border-radius: 0.5rem; padding: 0.3rem 0.5rem; width: fit-content; margin-bottom: 1.25rem;
}
.quantity-box button { background: #334155; color: #f1f5f9; border: none; padding: 0.3rem 0.75rem; font-size: 1rem; border-radius: 0.3rem; cursor: pointer; transition: background-color 0.2s; }
.quantity-box button:hover { background: #475569; }
.quantity-box input { width: 50px; border: none; text-align: center; background: transparent; color: #f1f5f9; font-size: 1rem; -moz-appearance: textfield; }
.quantity-box input::-webkit-outer-spin-button, .quantity-box input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.product-actions { display: flex; gap: 1rem; }
.btn {
    flex-grow: 1; background: #3b82f6; color: white; padding: 0.75rem 1.5rem;
    border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;
    transition: all 0.2s; text-decoration: none; text-align: center; font-size: 1rem;
    display: flex; align-items: center; justify-content: center; gap: 0.5rem;
}
.btn:hover { background: #2563eb; transform: translateY(-2px); }
.btn-buy { background: #4ade80; color: #14532d; }
.btn-buy:hover { background: #86efac; }
.btn:disabled { background: #64748b; cursor: not-allowed; }
.btn:disabled:hover { transform: none; }

/* Bagian Review */
.reviews-section {
    grid-column: 1 / -1; /* Membuat review mengambil lebar penuh */
    margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #334155;
}
.reviews-title {
    font-size: 1.5rem; font-weight: 600; color: #f1f5f9; margin-bottom: 1.5rem;
}
.review-item { background: #0f172a; padding: 1.25rem; border-radius: 0.75rem; margin-bottom: 1rem; }
.review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.review-user { display: flex; align-items: center; gap: 0.75rem; color: #f1f5f9; font-weight: 600; }
.review-user i { font-size: 1.25rem; color: #94a3b8; }
.review-date { font-size: 0.8rem; color: #94a3b8; }
.review-comment { color: #cbd5e1; line-height: 1.6; }
.no-reviews { text-align: center; color: #94a3b8; font-style: italic; padding: 2rem; background: #0f172a; border-radius: 0.75rem; }

/* Responsive */
@media (max-width: 900px) {
    .product-detail { grid-template-columns: 1fr; gap: 1.5rem; padding: 1rem; }
}

footer { margin-top: auto; }
</style>

<main class="product-detail-container">
    <div class="product-detail">
        <div class="product-image-section">
            <img src="../assets/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-main-image">
        </div>
        
        <div class="product-info-section">
            <div class="breadcrumb">
                <a href="../index.php">Products</a> > <?= htmlspecialchars($product['name']) ?>
            </div>
            
            <div>
                <span class="product-category-tag"><?= htmlspecialchars($product['category']) ?></span>
                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            </div>
            
            <div class="product-rating">
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i <= floor($avgRating) ? 'fa' : 'fa-regular' ?> fa-star"></span><?php endfor; ?>
                </div>
                <span class="rating-text"><?= $avgRating ?>/5 (Based on <?= $totalReviews ?> reviews)</span>
            </div>
            
            <div class="price-stock-wrapper">
                <span class="current-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                <div class="stock-info <?= $product['stock'] > 0 ? "in-stock" : "out-of-stock" ?>">
                    <?= $product['stock'] > 0 ? "{$product['stock']} In Stock" : "Stok Habis" ?>
                </div>
            </div>
            
            <div class="description-wrapper">
                <h3>Product Description</h3>
                <div class="product-description">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </div>
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <div class="actions-wrapper">
                    <h3>Choose Quantity</h3>
                    <div class="quantity-box">
                        <button type="button" onclick="decrementQty(this)">−</button>
                        <input type="number" name="quantity" min="1" max="<?= $product['stock'] > 0 ? $product['stock'] : '1' ?>" value="1" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                        <button type="button" onclick="incrementQty(this)">+</button>
                    </div>
                
                    <div class="product-actions">
                        <?php if ($product['stock'] > 0): ?>
                            <button type="submit" class="btn"><i class="fas fa-shopping-cart"></i> Add to Cart</button>
                            <button type="submit" name="buy_now" value="1" class="btn btn-buy"><i class="fas fa-bolt"></i> Buy Now</button>
                        <?php else: ?>
                            <button type="button" class="btn" disabled><i class="fas fa-times-circle"></i> Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="reviews-section">
            <h2 class="reviews-title"> (<?= $totalReviews ?> Reviews)</h2>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-user"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($review['user']) ?></div>
                            <div class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></div>
                        </div>
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?><span class="fa <?= $i <= $review['rating'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></span><?php endfor; ?>
                        </div>
                        <div class="review-comment" style="margin-top: 0.5rem;">
                            <?= nl2br(htmlspecialchars($review['review'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    Belum ada ulasan. Jadilah yang pertama memberikan ulasan untuk produk ini!
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../contact-widget.php'; ?>

<footer>
    <div style="max-width: 1200px; margin: 0 auto;">
        &copy; <?php echo date('Y'); ?> Zerous Shop. All rights reserved.
    </div>
</footer>

<script>
window.addEventListener('load', () => {
    document.getElementById('loading-screen').style.display = 'none';
});

document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.transition = 'opacity 0.5s ease';
            successMessage.style.opacity = '0';
            setTimeout(() => { successMessage.style.display = 'none'; }, 500);
        }, 3000);
    }
});

function incrementQty(btn) {
    const input = btn.parentElement.querySelector('input');
    const max = parseInt(input.max);
    let val = parseInt(input.value);
    if (val < max) input.value = val + 1;
}

function decrementQty(btn) {
    const input = btn.parentElement.querySelector('input');
    let val = parseInt(input.value);
    if (val > 1) input.value = val - 1;
}
</script>

</body>
</html>