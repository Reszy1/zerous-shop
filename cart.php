<?php 
session_start();
include 'db.php'; 

// --- Logika PHP tidak diubah ---
function getProductStock($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return $result ? $result['stock'] : 0;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if (isset($_POST['product_id'])) {
            $productId = (int)$_POST['product_id'];
        }

        switch ($action) {
            case 'update':
                $quantity = (int)$_POST['quantity'];
                $availableStock = getProductStock($pdo, $productId);
                if ($quantity <= 0) {
                    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
                        return $item['product_id'] != $productId;
                    });
                    $success_message = "Item removed from cart.";
                } else {
                    if ($quantity > $availableStock) {
                        $error_message = "Cannot update quantity. Only {$availableStock} items available.";
                        break;
                    }
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['product_id'] == $productId) {
                            $item['quantity'] = $quantity;
                            $success_message = "Cart updated successfully!";
                            break;
                        }
                    }
                }
                break;
            case 'remove':
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
                    return $item['product_id'] != $productId;
                });
                $success_message = "Item removed from cart.";
                break;
            case 'clear':
                $_SESSION['cart'] = [];
                $success_message = "Cart cleared successfully!";
                break;
        }
        
        $redirectUrl = 'cart.php';
        if ($error_message) {
            $_SESSION['flash_message'] = ['type' => 'error', 'text' => $error_message];
        } elseif ($success_message) {
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => $success_message];
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

if (isset($_SESSION['flash_message'])) {
    if ($_SESSION['flash_message']['type'] === 'error') {
        $error_message = $_SESSION['flash_message']['text'];
    } else {
        $success_message = $_SESSION['flash_message']['text'];
    }
    unset($_SESSION['flash_message']);
}

$cartItems = [];
$totalPrice = 0;
$stockIssues = []; 

if (!empty($_SESSION['cart'])) {
    $productIds = array_column($_SESSION['cart'], 'product_id');
    if (!empty($productIds)) {
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($_SESSION['cart'] as $cartIndex => $cartItem) {
            foreach ($products as $product) {
                if ($product['id'] == $cartItem['product_id']) {
                    $itemTotal = $product['price'] * $cartItem['quantity'];
                    $stockIssue = false;
                    if ($cartItem['quantity'] > $product['stock']) {
                        $stockIssues[] = ['product_name' => $product['name'], 'requested' => $cartItem['quantity'], 'available' => $product['stock']];
                        $stockIssue = true;
                    }
                    $cartItems[] = ['product' => $product, 'quantity' => $cartItem['quantity'], 'total' => $itemTotal, 'stock_issue' => $stockIssue];
                    $totalPrice += $itemTotal;
                    break;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Zerous Shop</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<div id="notification-container"></div>

<header>
    <div class="logo-title">
        <img src="assets/logo.webp" alt="Logo" class="logo">
        <h1>Zerous Shop</h1>
    </div>
    <nav class="nav-grid">
        <a href="index.php" class="nav-btn">Products</a>
        <a href="reviews.php" class="nav-btn">Reviews</a>
        <a href="index.php#news" class="nav-btn">News</a>
        <a href="faq.php" class="nav-btn">FAQ</a>
        <a href="user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>

<style>
/* === PENYESUAIAN UI UNTUK HALAMAN KERANJANG === */
body {
    background-color: #0f172a;
    color: #e2e8f0;
    font-family: 'Inter', sans-serif;
    display: flex; flex-direction: column; min-height: 100vh;
}
.cart-container { max-width: 1000px; margin: 40px auto; padding: 0 20px; flex-grow: 1; }
.cart-title {
    font-size: 2.5rem; font-weight: 700; color: #f1f5f9; text-align: center;
    margin-bottom: 30px; border-bottom: 1px solid #334155; padding-bottom: 15px;
}
/* Notifikasi */
#notification-container { position: fixed; top: 20px; right: 20px; z-index: 1000; }
.notification {
    padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    margin-bottom: 10px; display: flex; align-items: center; gap: 10px;
    animation: slideIn 0.3s ease-out, slideOut 0.3s ease-out 4s forwards;
}
.notification.success { background: #28a745; color: white; }
.notification.error { background: #dc3545; color: white; }
@keyframes slideIn { from { transform: translateX(110%); } to { transform: translateX(0); } }
@keyframes slideOut { from { transform: translateX(0); } to { transform: translateX(110%); } }

/* Keranjang Kosong */
.empty-cart {
    text-align: center; padding: 60px 20px; background-color: #1e293b;
    border-radius: 12px; border: 1px dashed #334155;
}
.empty-cart i { font-size: 4rem; color: #3b82f6; margin-bottom: 20px; }
.empty-cart p { font-size: 1.2rem; color: #94a3b8; margin-bottom: 25px; }
/* Tombol di Halaman ini */
.btn {
    display: inline-block; padding: 12px 24px; text-decoration: none;
    border-radius: 8px; font-weight: 600; transition: all 0.2s ease;
    border: none; cursor: pointer; text-align: center;
}
.btn-primary { background-color: #3b82f6; color: white; }
.btn-primary:hover { background-color: #2563eb; transform: translateY(-2px); }
.btn-secondary { background-color: #334155; color: #e2e8f0; }
.btn-secondary:hover { background-color: #475569; }
.btn-danger { background-color: #ef4444; color: white; }
.btn-danger:hover { background-color: #dc2626; }
.btn-success { background-color: #22c55e; color: white; }
.btn-success:hover { background-color: #16a34a; }
.btn:disabled { background-color: #475569; cursor: not-allowed; opacity: 0.6; }

/* Konten Keranjang */
.cart-content { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
.cart-items { display: flex; flex-direction: column; gap: 20px; }
.cart-item {
    display: grid; grid-template-columns: 80px 1fr auto auto; gap: 20px; align-items: center;
    padding: 20px; background-color: #1e293b; border-radius: 12px;
    border: 1px solid #334155; transition: all 0.2s ease;
}
.cart-item:hover { border-color: #3b82f6; }
.cart-item.stock-issue { border-color: #facc15; background-color: rgba(251, 191, 36, 0.05); }

.item-image img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; }
.item-details h3 { margin: 0; color: #f1f5f9; font-size: 1.1rem; }
.item-price { color: #60a5fa; font-weight: 600; margin: 5px 0; }
.stock-info { font-size: 0.85rem; color: #94a3b8; margin: 2px 0 0 0; }
.stock-warning-text { color: #facc15; font-weight: 600; }

.quantity-form { display: flex; align-items: center; gap: 8px; }
.qty-btn { width: 30px; height: 30px; border: 1px solid #475569; background-color: #334155; color: #e2e8f0; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.2s; }
.qty-btn:hover { background-color: #475569; }
.qty-input { width: 50px; text-align: center; border: 1px solid #475569; background: #1e293b; color: #e2e8f0; border-radius: 6px; padding: 5px; }
.item-total p { font-weight: 600; font-size: 1.1rem; color: #f1f5f9; margin: 0; }
.remove-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #ef4444; padding: 5px; transition: all 0.2s; }
.remove-btn:hover { color: #f87171; transform: scale(1.1); }

/* Ringkasan */
.cart-summary {
    padding: 25px; background-color: #1e293b; border-radius: 12px;
    border: 1px solid #334155; position: sticky; top: 20px; align-self: start;
}
.cart-summary h3 { font-size: 1.5rem; margin: 0 0 20px 0; padding-bottom: 15px; border-bottom: 1px solid #334155; }
.summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
.summary-row span { color: #94a3b8; }
.summary-row strong { color: #f1f5f9; font-weight: 600; }
.summary-total { font-size: 1.5rem; font-weight: bold; color: #22c55e; }
.cart-actions { display: flex; flex-direction: column; gap: 10px; margin-top: 25px; border-top: 1px solid #334155; padding-top: 20px; }

/* === PERUBAHAN DI SINI === */
.cart-actions .btn { 
    width: 100%; /* Membuat semua tombol memiliki lebar penuh */
    box-sizing: border-box; /* Memastikan padding tidak menambah lebar */
}

footer { margin-top: auto; }
/* Responsive */
@media (max-width: 800px) {
    .cart-content { grid-template-columns: 1fr; }
    .cart-summary { position: static; }
}
@media (max-width: 500px) {
    .cart-item { grid-template-columns: 1fr; text-align: center; }
    .item-image { margin: 0 auto; }
}
</style>

<main class="cart-container">
    <h2 class="cart-title">Your Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <p>Your cart feels a little empty. Let's fill it up!</p>
            <a href="index.php" class="btn btn-primary">Browse Products</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item <?= $item['stock_issue'] ? 'stock-issue' : '' ?>">
                        <div class="item-image">
                            <img src="assets/<?= htmlspecialchars($item['product']['image']) ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>">
                        </div>
                        <div class="item-details">
                            <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                            <p class="item-price">Rp <?= number_format($item['product']['price'], 0, ',', '.') ?></p>
                            <?php if ($item['stock_issue']): ?>
                                <p class="stock-info stock-warning-text">⚠️ Requested <?= $item['quantity'] ?>, only <?= $item['product']['stock'] ?> available</p>
                            <?php endif; ?>
                        </div>
                        <div class="item-quantity">
                            <form method="POST" class="quantity-form" onsubmit="showLoading()">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                <button type="button" class="qty-btn" onclick="this.form.quantity.value--; this.form.submit();">-</button>
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="0" max="<?= $item['product']['stock'] ?>" class="qty-input" onchange="this.form.submit()">
                                <button type="button" class="qty-btn" onclick="this.form.quantity.value++; this.form.submit();">+</button>
                            </form>
                        </div>
                        <div class="item-total">
                            <p>Rp <?= number_format($item['total'], 0, ',', '.') ?></p>
                        </div>
                        <div class="item-remove">
                            <form method="POST" onsubmit="return confirm('Remove this item?') && showLoading()">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                <button type="submit" class="remove-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <aside class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong>Rp <?= number_format($totalPrice, 0, ',', '.') ?></strong>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <strong>FREE</strong>
                </div>
                <div class="summary-row" style="font-size: 1.2rem; border-top: 1px solid #334155; padding-top: 15px;">
                    <span>Total</span>
                    <strong class="summary-total">Rp <?= number_format($totalPrice, 0, ',', '.') ?></strong>
                </div>
                <div class="cart-actions">
                    <?php if (empty($stockIssues)): ?>
                        <a href="checkout/" class="btn btn-success"><i class="fas fa-shield-alt"></i> Checkout Now</a>
                    <?php else: ?>
                        <button class="btn" disabled title="Please resolve stock issues before checkout">Checkout (Stock Issues)</button>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                    <form method="POST" onsubmit="return confirm('Are you sure?') && showLoading()" style="width:100%;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times-circle"></i> Clear Cart
                        </button>
                    </form>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</main>

<?php include 'contact-widget.php'; ?>
<footer>
    <div style="max-width: 1200px; margin: 0 auto;">
        &copy; <?= date('Y'); ?> Zerous Shop. All rights reserved.
    </div>
</footer>

<script>
function showNotification(message, type = 'success') {
    const container = document.getElementById('notification-container');
    const notif = document.createElement('div');
    notif.className = `notification ${type}`;
    notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'times-circle'}"></i> <p>${message}</p>`;
    container.appendChild(notif);
    setTimeout(() => { notif.remove(); }, 4300);
}

function showLoading() {
    document.getElementById('loading-screen').style.opacity = '1';
    document.getElementById('loading-screen').style.display = 'flex';
}

window.addEventListener('load', () => {
    document.getElementById('loading-screen').style.opacity = '0';
    setTimeout(() => {
        document.getElementById('loading-screen').style.display = 'none';
    }, 400);
});

// Tampilkan notifikasi dari PHP
<?php if ($success_message): ?>
    showNotification('<?= htmlspecialchars($success_message) ?>', 'success');
<?php endif; ?>
<?php if ($error_message): ?>
    showNotification('<?= htmlspecialchars($error_message) ?>', 'error');
<?php endif; ?>

</script>

</body>
</html>