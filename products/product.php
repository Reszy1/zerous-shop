<?php
session_start();
if (!isset($_SESSION['email'])) {
    $_SESSION['login_notice'] = "Silakan login terlebih dahulu untuk mengakses halaman ini.";
    header('Location: ../user-login/dashboard.php');
    exit;
}
include '../db.php';

// Cek apakah ada ID produk
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$productId = (int)$_GET['id'];

// Handle POST request untuk add to cart
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
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'quantity' => $quantity
        ];
    }

    if (isset($_POST['buy_now'])) {
        header('Location: ../cart.php');
    } else {
        header('Location: product.php?id=' . $productId . '&added=1');
    }
    exit;
}

// Ambil data produk dari database (filter by category/search jika ada)
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';

$query = "SELECT * FROM products WHERE id = :id";

$stmt = $pdo->prepare($query);
$stmt->bindValue(':id', $productId, PDO::PARAM_INT);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    header('Location: ../index.php');
    exit;
}

// Ambil review untuk produk ini
$reviewStmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC LIMIT 5");
$reviewStmt->execute([$productId]);
$reviews = $reviewStmt->fetchAll();

// Hitung rata-rata rating
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
</head>
<body>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<!-- Success Message -->
<?php if (isset($_GET['added']) && $_GET['added'] == 1): ?>
<div id="success-message" class="success-notification">
    <p>✅ Product added to cart successfully!</p>
</div>
<?php endif; ?>

<!-- ====== HEADER ====== -->
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
// Include search bar with proper context
$_SESSION['current_page'] = 'product';
include '../search-bar.php'; 
?>

<style>

/* Success notification styles */
.success-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.product-detail-container {
    display: flex;
    justify-content: center;
    padding: 1.5rem;
}

.product-detail {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    background: #1e293b;
    padding: 1rem;
    border-radius: 1rem;
    max-width: 1000px;
    width: 100%;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
}

.product-image-section, .product-info-section {
    flex: 1 1 45%;
    min-width: 280px;
}

.product-main-image {
    width: 100%;
    border-radius: 0.75rem;
    border: 2px solid #334155;
}

.breadcrumb {
    font-size: 0.9rem;
    color: #94a3b8;
    margin-bottom: 1rem;
}

.breadcrumb a {
    color: #3b82f6;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.product-title {
    font-size: 1.5rem;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #f1f5f9;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.stars {
    display: flex;
    gap: 0.1rem;
}

.star-filled {
    color: #facc15;
}

.star-empty {
    color: #64748b;
}

.rating-text {
    font-size: 0.9rem;
    color: #94a3b8;
}

.product-price {
    background: #0f172a;
    padding: 1rem;
    border-radius: 1rem;
    margin-bottom: 1rem;
}

.current-price {
    font-size: 1.5rem;
    font-weight: bold;
    color: #facc15;
}

.stock-info {
    font-size: 0.9rem;
    color: #a1a1aa;
    margin-top: 0.5rem;
}

.product-description {
    background: #0f172a;
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    line-height: 1.6;
    font-size: 0.9rem;
    color: #e2e8f0;
}

.quantity-section {
    margin-bottom: 1rem;
}

.quantity-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background-color: #1e293b;
    border: 1px solid #334155;
    border-radius: 0.5rem;
    padding: 0.3rem 0.5rem;
    width: fit-content;
}

.quantity-box button {
    background-color: #334155;
    color: #f1f5f9;
    border: none;
    padding: 0.3rem 0.6rem;
    font-size: 1rem;
    border-radius: 0.3rem;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.quantity-box button:hover {
    background-color: #475569;
}

.quantity-box input {
    width: 50px;
    padding: 0.3rem;
    border-radius: 0.3rem;
    border: none;
    text-align: center;
    background: transparent;
    color: #f1f5f9;
    font-size: 1rem;
    -moz-appearance: textfield;
}

.quantity-box input::-webkit-outer-spin-button,
.quantity-box input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.product-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.btn {
    background: #3b82f6;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn:hover {
    background: #2563eb;
}

.btn-buy {
    background: #4f46e5;
}

.btn-buy:hover {
    background: #4338ca;
}

.btn:disabled {
    background: #64748b;
    cursor: not-allowed;
}

.meta-info {
    font-size: 0.85rem;
    color: #94a3b8;
    margin-bottom: 2rem;
}

.reviews-section {
    width: 100%;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #334155;
}

.reviews-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #f1f5f9;
    margin-bottom: 1rem;
}

.review-item {
    background: #0f172a;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.review-rating {
    color: #facc15;
}

.review-date {
    font-size: 0.8rem;
    color: #94a3b8;
}

.review-comment {
    color: #e2e8f0;
    line-height: 1.5;
}

.no-reviews {
    text-align: center;
    color: #94a3b8;
    font-style: italic;
}

footer {
    position: relative;
    bottom: 0;
    width: 100%;
    background: rgba(18, 18, 36, 0.9);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(88, 88, 255, 0.2);
    margin-top: auto;
    padding: 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.6);
    transition: all 0.3s ease;
}

footer:hover {
    color: rgba(255, 255, 255, 0.8);
    border-top-color: rgba(88, 88, 255, 0.4);
}

/* Pastikan body menggunakan flexbox untuk sticky footer */
body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Main content area */
.main-content {
    flex: 1;
}

/* Responsive footer */
@media (max-width: 768px) {
    footer {
        font-size: 0.75rem;
        padding: 0.75rem;
    }
}
</style>

<!-- ====== PRODUCT DETAIL ====== -->
<main class="product-detail-container">
    <div class="product-detail">
        <div class="product-image-section">
            <img src="../assets/<?= htmlspecialchars($product['image']) ?>" 
                 alt="<?= htmlspecialchars($product['name']) ?>" 
                 class="product-main-image">
        </div>
        
        <div class="product-info-section">
            <div class="breadcrumb">
                <a href="../index.php">Products</a> > <?= htmlspecialchars($product['name']) ?>
            </div>
            
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <p class="meta-info">Category Product : <?= htmlspecialchars($product['category']) ?></p>
            
            <div class="product-rating">
                <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?= $i <= $avgRating ? 'star-filled' : 'star-empty' ?>">★</span>
                    <?php endfor; ?>
                </div>
                <span class="rating-text"><?= $avgRating ?>/5 (<?= $totalReviews ?> reviews)</span>
            </div>
            
            <div class="product-price">
                <span class="current-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                <div class="stock-info">
                    <?= $product['stock'] > 0 ? "In Stock ({$product['stock']} available)" : "<span style='color:#ef4444;'>Out of Stock</span>" ?>
                </div>
            </div>
            
            <div class="product-description">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                
                <div class="quantity-section">
                    <div class="quantity-box">
                        <button type="button" onclick="decrementQty(this)">−</button>
                        <input type="number" name="quantity" min="1" max="<?= $product['stock'] ?>" value="1">
                        <button type="button" onclick="incrementQty(this)">+</button>
                    </div>
                </div>
                
                <div class="product-actions">
                    <?php if ($product['stock'] > 0): ?>
                        <button type="submit" class="btn">Add to Cart</button>
                        <button type="submit" name="buy_now" value="1" class="btn btn-buy">Buy Now</button>
                    <?php else: ?>
                        <button type="button" class="btn" disabled>Out of Stock</button>
                    <?php endif; ?>
                </div>
            </form>
            
            <div class="meta-info">
                🛒 Based on <?= $totalReviews ?> customer reviews
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 class="reviews-title">Customer Reviews</h2>
            
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= $review['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <div class="review-date">
                                <?= date('M j, Y', strtotime($review['created_at'])) ?>
                            </div>
                        </div>
                        <div class="review-comment">
                            <?= nl2br(htmlspecialchars($review['review'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-reviews">
                    No reviews yet. Be the first to review this product!
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../contact-widget.php'; ?>

<footer>
    <div style="max-width: 1200px; margin: 0 auto;">
        &copy; <?php echo date('Y'); ?> Zerous Shop. All rights reserved.
        <br>
        <small style="opacity: 0.7; font-size: 0.75rem;">
            Secure Login System | Last updated: <?php echo date('M Y'); ?>
        </small>
    </div>
</footer>

<!-- ====== LOADING SCREEN SCRIPT ====== -->
<script>
window.addEventListener('load', () => {
    const loader = document.getElementById('loading-screen');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 400);
});

// Auto-hide success message
document.addEventListener('DOMContentLoaded', function() {
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 300);
        }, 3000); // Hide after 3 seconds
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

// Override cart button untuk path yang benar dari subfolder
document.addEventListener('DOMContentLoaded', function() {
    const cartBtn = document.getElementById('cartBtn');
    if (cartBtn) {
        cartBtn.onclick = function() {
            window.location.href = '../cart.php';
        };
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const footer = document.querySelector('footer');
        
    // Add smooth fade-in animation
    footer.style.opacity = '0';
    footer.style.transform = 'translateY(20px)';
        
    setTimeout(() => {
        footer.style.transition = 'all 0.5s ease';
        footer.style.opacity = '1';
        footer.style.transform = 'translateY(0)';
    }, 500);
});
</script>

</body>
</html>