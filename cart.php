<?php 
session_start();
include 'db.php'; 

// Function untuk mendapatkan stok produk
function getProductStock($pdo, $productId) {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return $result ? $result['stock'] : 0;
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$error_message = '';
$success_message = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Redirect sesuai kategori produk

    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $productId = (int)$_POST['product_id'];
        
        switch ($action) {
            case 'add':
                $quantity = (int)$_POST['quantity'];
                $availableStock = getProductStock($pdo, $productId);
                
                // Cek stok yang tersedia
                if ($availableStock <= 0) {
                    $error_message = "Product is out of stock!";
                    break;
                }
                
                // Cek apakah produk sudah ada di keranjang
                $found = false;
                $currentQtyInCart = 0;
                
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['product_id'] == $productId) {
                        $currentQtyInCart = $item['quantity'];
                        $found = true;
                        break;
                    }
                }
                
                $totalRequestedQty = $currentQtyInCart + $quantity;
                
                if ($totalRequestedQty > $availableStock) {
                    $error_message = "Cannot add {$quantity} items. Only " . ($availableStock - $currentQtyInCart) . " items available in stock.";
                    break;
                }
                
                // Update atau tambah item ke keranjang
                if ($found) {
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['product_id'] == $productId) {
                            $item['quantity'] = $totalRequestedQty;
                            break;
                        }
                    }
                } else {
                    $_SESSION['cart'][] = [
                        'product_id' => $productId,
                        'quantity' => $quantity
                    ];
                }
                
                $success_message = "Product added to cart successfully!";
                break;
                
            case 'update':
                $quantity = (int)$_POST['quantity'];
                $availableStock = getProductStock($pdo, $productId);
                
                if ($quantity <= 0) {
                    // Hapus item jika quantity 0 atau kurang
                    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($productId) {
                        return $item['product_id'] != $productId;
                    });
                    $success_message = "Item removed from cart.";
                } else {
                    // Validasi stok sebelum update
                    if ($quantity > $availableStock) {
                        $error_message = "Cannot set quantity to {$quantity}. Only {$availableStock} items available in stock.";
                        break;
                    }
                    
                    // Update quantity
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
        
        // Redirect untuk mencegah resubmission
        $redirectUrl = 'cart.php';
        if ($error_message) {
            $redirectUrl .= '?error=' . urlencode($error_message);
        } elseif ($success_message) {
            $redirectUrl .= '?success=' . urlencode($success_message);
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Ambil pesan dari URL parameters
if (isset($_GET['error'])) {
    $error_message = $_GET['error'];
}
if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

// Ambil detail produk untuk items di keranjang
$cartItems = [];
$totalPrice = 0;
$stockIssues = []; // Array untuk menyimpan masalah stok

if (!empty($_SESSION['cart'])) {
    $productIds = array_column($_SESSION['cart'], 'product_id');
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gabungkan data produk dengan quantity dari keranjang
    foreach ($_SESSION['cart'] as $cartIndex => $cartItem) {
        foreach ($products as $product) {
            if ($product['id'] == $cartItem['product_id']) {
                $itemTotal = $product['price'] * $cartItem['quantity'];
                
                // Cek apakah quantity di cart melebihi stok
                $stockIssue = false;
                if ($cartItem['quantity'] > $product['stock']) {
                    $stockIssues[] = [
                        'product_name' => $product['name'],
                        'requested' => $cartItem['quantity'],
                        'available' => $product['stock']
                    ];
                    $stockIssue = true;
                }
                
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $cartItem['quantity'],
                    'total' => $itemTotal,
                    'stock_issue' => $stockIssue,
                    'cart_index' => $cartIndex
                ];
                $totalPrice += $itemTotal;
                break;
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
</head>
<body>

<!-- Loading Screen -->
<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<!-- Messages -->
<?php if ($error_message): ?>
<div id="error-message" class="error-notification">
    <p>❌ <?= htmlspecialchars($error_message) ?></p>
</div>
<?php endif; ?>

<?php if ($success_message): ?>
<div id="success-message" class="success-notification">
    <p>✅ <?= htmlspecialchars($success_message) ?></p>
</div>
<?php endif; ?>

<!-- Stock Issues Warning -->
<?php if (!empty($stockIssues)): ?>
<div class="stock-warning">
    <h3>⚠️ Stock Issues Detected</h3>
    <?php foreach ($stockIssues as $issue): ?>
        <p><?= htmlspecialchars($issue['product_name']) ?>: Requested <?= $issue['requested'] ?>, but only <?= $issue['available'] ?> available in stock.</p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ====== HEADER ====== -->
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

<!-- ====== CART CONTENT ====== -->
<main class="cart-container">
    <h2 class="cart-title">Shopping Cart</h2>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Your cart is empty</p>
            <a href="index.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-content">
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item <?= $item['stock_issue'] ? 'stock-issue' : '' ?>">
                        <div class="item-image">
                            <img src="assets/<?= htmlspecialchars($item['product']['image']) ?>" 
                                 alt="<?= htmlspecialchars($item['product']['name']) ?>">
                        </div>
                        <div class="item-details">
                            <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                            <p class="item-price">Rp <?= number_format($item['product']['price'], 0, ',', '.') ?></p>
                            <p class="stock-info">
                                Stock: <?= $item['product']['stock'] ?> available
                                <?php if ($item['stock_issue']): ?>
                                    <span class="stock-warning-text">⚠️ Exceeds available stock</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="item-quantity">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                <button type="button" class="qty-btn" onclick="changeQuantity(this, -1)">-</button>
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                       min="0" max="<?= $item['product']['stock'] ?>" class="qty-input" 
                                       onchange="validateAndSubmit(this)" 
                                       data-max-stock="<?= $item['product']['stock'] ?>">
                                <button type="button" class="qty-btn" onclick="changeQuantity(this, 1)">+</button>
                            </form>
                        </div>
                        <div class="item-total">
                            <p>Rp <?= number_format($item['total'], 0, ',', '.') ?></p>
                        </div>
                        <div class="item-remove">
                            <form method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['product']['id'] ?>">
                                <button type="submit" class="remove-btn" onclick="return confirm('Remove this item from cart?')">🗑️</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Total:</span>
                    <span class="total-price">Rp <?= number_format($totalPrice, 0, ',', '.') ?></span>
                </div>
                <div class="cart-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="clear-cart-btn" onclick="return confirm('Are you sure you want to clear your cart?')">
                            Clear Cart
                        </button>
                    </form>
                    <a href="index.php" class="continue-shopping">Continue Shopping</a>
                    <?php if (empty($stockIssues)): ?>
                        <a href="checkout/" class="checkout-btn">Proceed to Checkout</a>
                    <?php else: ?>
                        <button class="checkout-btn" disabled title="Please resolve stock issues before checkout">Checkout (Stock Issues)</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'contact-widget.php'; ?>

<!-- ====== FOOTER ====== -->
<footer>
    <div style="max-width: 1200px; margin: 0 auto;">
        &copy; <?php echo date('Y'); ?> Zerous Shop. All rights reserved.
        <br>
        <small style="opacity: 0.7; font-size: 0.75rem;">
            Secure Shopping Cart | Last updated: <?php echo date('M Y'); ?>
        </small>
    </div>
</footer>

<!-- ====== SCRIPTS ====== -->
<script>
// Loading screen
window.addEventListener('load', () => {
    const loader = document.getElementById('loading-screen');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 400);
});

// Auto-hide messages
document.addEventListener('DOMContentLoaded', function() {
    // Hide success message
    const successMessage = document.getElementById('success-message');
    if (successMessage) {
        setTimeout(() => {
            successMessage.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 300);
        }, 4000);
    }
    
    // Hide error message
    const errorMessage = document.getElementById('error-message');
    if (errorMessage) {
        setTimeout(() => {
            errorMessage.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 300);
        }, 6000); // Error messages stay longer
    }
});

// Quantity change function with stock validation
function changeQuantity(button, change) {
    const form = button.closest('.quantity-form');
    const input = form.querySelector('.qty-input');
    const currentValue = parseInt(input.value);
    const maxStock = parseInt(input.getAttribute('data-max-stock'));
    const newValue = Math.max(0, Math.min(maxStock, currentValue + change));
    
    input.value = newValue;
    
    // Submit form if valid
    if (newValue !== currentValue) {
        form.submit();
    }
}

// Validate quantity and submit
function validateAndSubmit(input) {
    const maxStock = parseInt(input.getAttribute('data-max-stock'));
    const value = parseInt(input.value);
    
    if (value > maxStock) {
        alert(`Maximum quantity is ${maxStock} (available stock)`);
        input.value = maxStock;
    } else if (value < 0) {
        input.value = 0;
    }
    
    // Auto-submit after validation
    setTimeout(() => {
        input.form.submit();
    }, 500);
}

// Auto-submit on quantity change (with debounce and validation)
let quantityTimeout;
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('input', function() {
        clearTimeout(quantityTimeout);
        quantityTimeout = setTimeout(() => {
            validateAndSubmit(this);
        }, 1000); // Submit after 1 second of no input
    });
});

// Checkout button click handler with stock validation
document.addEventListener('DOMContentLoaded', function() {
    const checkoutBtn = document.querySelector('.checkout-btn:not([disabled])');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function(e) {
            // Additional client-side validation before going to checkout
            e.preventDefault();
            
            // Show loading state
            this.style.opacity = '0.7';
            this.innerHTML = 'Processing...';
            
            // Small delay for UX, then redirect
            setTimeout(() => {
                window.location.href = 'checkout/';
            }, 500);
        });
    }
});

// Footer animation
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

// Toast notification for successful cart actions
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <span class="toast-icon">${type === 'success' ? '✅' : '❌'}</span>
            <span class="toast-message">${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove after delay
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}
</script>

<style>
/* Cart Styles */
.cart-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

.cart-title {
    font-size: 2rem;
    color: #333;
    text-align: center;
    margin-bottom: 30px;
}

/* Message Styles */
.success-notification, .error-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    animation: slideIn 0.3s ease-out;
}

.success-notification {
    background: #28a745;
    color: white;
}

.error-notification {
    background: #dc3545;
    color: white;
}

.stock-warning {
    background: #fff3cd;
    color: #856404;
    padding: 15px;
    border-radius: 8px;
    margin: 20px auto;
    max-width: 1000px;
    border: 1px solid #ffeaa7;
}

.stock-warning h3 {
    margin-top: 0;
    color: #d68910;
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

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    color: #666;
}

.empty-cart p {
    font-size: 1.2rem;
    margin-bottom: 20px;
}

.continue-shopping {
    display: inline-block;
    padding: 12px 24px;
    background-color: #4e5dff;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 5px;
}

.continue-shopping:hover {
    background-color: #3c4bff;
    transform: translateY(-1px);
}

.cart-content {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.cart-item {
    display: grid;
    grid-template-columns: 80px 1fr auto auto auto;
    gap: 20px;
    align-items: center;
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.cart-item:hover {
    border-color: rgba(78, 93, 255, 0.3);
    box-shadow: 0 4px 12px rgba(78, 93, 255, 0.1);
}

.cart-item.stock-issue {
    border-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.1);
}

.item-image img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
}

.item-details h3 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.item-price {
    color: #4e5dff;
    font-weight: 600;
    margin: 5px 0;
}

.stock-info {
    font-size: 0.85rem;
    color: #666;
    margin: 2px 0 0 0;
}

.stock-warning-text {
    color: #d68910;
    font-weight: 600;
}

.quantity-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #ddd;
    background-color: #f5f5f5;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.2s ease;
}

.qty-btn:hover {
    background-color: #e0e0e0;
    transform: scale(1.05);
}

.qty-input {
    width: 60px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    transition: border-color 0.3s ease;
}

.qty-input:focus {
    border-color: #4e5dff;
    outline: none;
}

.qty-input:invalid {
    border-color: #dc3545;
}

.item-total p {
    font-weight: 600;
    color: #333;
    margin: 0;
}

.remove-btn {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #ff4444;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.remove-btn:hover {
    background-color: rgba(255, 68, 68, 0.1);
    transform: scale(1.1);
}

.cart-summary {
    padding: 20px;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.total-price {
    color: #4e5dff;
}

.cart-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
}

.clear-cart-btn, .checkout-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.clear-cart-btn {
    background-color: #ff4444;
    color: white;
}

.clear-cart-btn:hover {
    background-color: #ff3333;
    transform: translateY(-1px);
}

.checkout-btn {
    background-color: #28a745;
    color: white;
    position: relative;
    overflow: hidden;
}

.checkout-btn:hover {
    background-color: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.checkout-btn:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
    transform: none;
}

.checkout-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.checkout-btn:hover::before {
    left: 100%;
}

/* Toast Notifications */
.toast-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    transform: translateX(100%);
    opacity: 0;
    transition: all 0.3s ease;
}

.toast-notification.error {
    background: #dc3545;
}

.toast-notification.show {
    transform: translateX(0);
    opacity: 1;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 15px;
    }
    
    .cart-actions {
        flex-direction: column;
    }
    
    .quantity-form {
        justify-content: center;
    }
    
    .success-notification, .error-notification {
        position: relative;
        top: auto;
        right: auto;
        margin: 20px;
    }
    
    .toast-notification {
        position: fixed;
        bottom: 20px;
        left: 20px;
        right: 20px;
        transform: translateY(100%);
    }
    
    .toast-notification.show {
        transform: translateY(0);
    }
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

/* Loading enhancement */
#loading-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    transition: opacity 0.4s ease;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(78, 93, 255, 0.3);
    border-left: 4px solid #4e5dff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#loading-screen p {
    color: white;
    font-size: 1.1rem;
}
</style>

</body>
</html>