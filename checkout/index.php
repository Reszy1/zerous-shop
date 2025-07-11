<?php
session_start();
include '../db.php';

// Redirect jika cart kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../cart.php');
    exit;
}

// Function untuk mendapatkan detail produk dan tipe cart
function getCartDetails($pdo, $cart) {
    $cartItems = [];
    $totalPrice = 0;
    $stockIssues = [];
    $isDigitalOnly = true; 

    if (!empty($cart)) {
        $productIds = array_column($cart, 'product_id');
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($productIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cart as $cartItem) {
            foreach ($products as $product) {
                if ($product['id'] == $cartItem['product_id']) {
                    if ($product['category'] === 'physical') {
                        $isDigitalOnly = false;
                    }
                    $itemTotal = $product['price'] * $cartItem['quantity'];
                    if ($cartItem['quantity'] > $product['stock']) {
                        $stockIssues[] = ['product_name' => $product['name'], 'requested' => $cartItem['quantity'], 'available' => $product['stock']];
                    }
                    $cartItems[] = ['product' => $product, 'quantity' => $cartItem['quantity'], 'total' => $itemTotal];
                    $totalPrice += $itemTotal;
                    break;
                }
            }
        }
    }
    
    return ['items' => $cartItems, 'total' => $totalPrice, 'stock_issues' => $stockIssues, 'is_digital_only' => $isDigitalOnly];
}

// Function untuk validasi kupon
function validateCoupon($pdo, $couponCode, $totalAmount) {
    $stmt = $pdo->prepare("
        SELECT * FROM coupons 
        WHERE code = ? AND is_active = 1 
        AND (valid_from IS NULL OR valid_from <= CURDATE()) 
        AND (valid_until IS NULL OR valid_until >= CURDATE())
        AND (max_usage IS NULL OR used_count < max_usage)
        AND minimum_amount <= ?
    ");
    $stmt->execute([$couponCode, $totalAmount]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$cartDetails = getCartDetails($pdo, $_SESSION['cart']);
$cartItems = $cartDetails['items'];
$subtotal = $cartDetails['total'];
$stockIssues = $cartDetails['stock_issues'];
$isDigitalOnly = $cartDetails['is_digital_only'];

if (!empty($stockIssues)) {
    header('Location: ../cart.php?error=' . urlencode('Please resolve stock issues before checkout'));
    exit;
}

// Handle coupon validation
$couponDiscount = 0;
$couponError = '';
$appliedCoupon = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $couponCode = trim($_POST['coupon_code']);
    
    if (!empty($couponCode)) {
        $coupon = validateCoupon($pdo, $couponCode, $subtotal);
        
        if ($coupon) {
            $appliedCoupon = $coupon;
            if ($coupon['discount_type'] === 'percentage') {
                $couponDiscount = ($subtotal * $coupon['discount_value']) / 100;
            } else {
                $couponDiscount = $coupon['discount_value'];
            }
            $_SESSION['applied_coupon'] = $coupon;
        } else {
            $couponError = 'Invalid or expired coupon code';
        }
    }
} elseif (isset($_SESSION['applied_coupon'])) {
    $appliedCoupon = $_SESSION['applied_coupon'];
    if ($appliedCoupon['discount_type'] === 'percentage') {
        $couponDiscount = ($subtotal * $appliedCoupon['discount_value']) / 100;
    } else {
        $couponDiscount = $appliedCoupon['discount_value'];
    }
}

// Remove coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
    $appliedCoupon = null;
    $couponDiscount = 0;
}

// Shipping options
$shippingOptions = [
    'reguler' => ['name' => 'Reguler (5-7 hari)', 'cost' => 15000],
    'express' => ['name' => 'Express (2-3 hari)', 'cost' => 25000],
    'same_day' => ['name' => 'Same Day (Jakarta only)', 'cost' => 35000]
];

$selectedShipping = $_POST['shipping_method'] ?? 'reguler';
$shippingCost = $isDigitalOnly ? 0 : ($shippingOptions[$selectedShipping]['cost'] ?? 0);

$finalTotal = $subtotal - $couponDiscount + $shippingCost;

function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
    
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 40px;
            align-items: flex-start;
        }
        
        .checkout-form, .order-summary {
            background: #1e293b;
            padding: 30px;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .order-summary {
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .form-section h2, .form-section h3, .order-summary h3 {
            color: #f1f5f9;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #3b82f6;
            font-weight: 600;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; font-size: 0.9rem; }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #334155;
            border-radius: 8px;
            font-size: 1rem;
            background-color: #0f172a;
            color: #f1f5f9;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .shipping-options, .payment-methods { display: flex; flex-direction: column; gap: 15px; }
        
        .shipping-option, .payment-method {
            display: flex; /* Changed to flex for better alignment */
            position: relative;
            padding: 15px;
            border: 1px solid #334155;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            align-items: center; /* Vertically align items */
        }
        
        .shipping-option:hover, .payment-method:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }
        
        .shipping-option input[type="radio"], .payment-method input[type="radio"] {
            display: none;
        }
        
        .radio-custom {
            flex-shrink: 0;
            height: 20px;
            width: 20px;
            background-color: #0f172a;
            border: 2px solid #334155;
            border-radius: 50%;
            transition: all 0.2s;
            margin-right: 15px;
        }

        .radio-custom::after {
            content: '';
            position: absolute;
            display: none;
            top: 50%;
            left: 25px; /* Adjust based on padding and element size */
            transform: translate(-50%, -50%);
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: white;
        }
        
        input[type="radio"]:checked + .radio-custom {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        
        input[type="radio"]:checked + .radio-custom + div {
             /* Styles for text next to selected radio */
        }
        
        .payment-method.selected {
             border-color: #3b82f6;
             background: rgba(59, 130, 246, 0.1);
        }

        .summary-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #334155; }
        .summary-item:last-child { border-bottom: none; }
        .item-image { width: 60px; height: 50px; margin-right: 15px; flex-shrink: 0;}
        .item-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 6px; }
        .item-details { flex: 1; }
        .item-name { color: #f1f5f9; }
        .item-qty-price { font-size: 0.9rem; color: #94a3b8; }
        .item-total { color: #f1f5f9; font-weight: 500; text-align: right; }

        .coupon-section { margin: 20px 0; padding: 20px; background: rgba(15, 23, 42, 0.7); border-radius: 8px; }
        .coupon-input-group { display: flex; gap: 10px; margin-bottom: 10px; }
        .coupon-input { flex: 1; padding: 10px; border: 1px solid #334155; border-radius: 6px; background-color: #0f172a; color: #f1f5f9; }
        .coupon-btn { padding: 10px 20px; background: #4e5dff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .coupon-success { color: #4ade80; font-size: 0.9rem; }
        .coupon-error { color: #f87171; font-size: 0.9rem; }

        .summary-totals { margin-top: 20px; padding-top: 20px; border-top: 1px solid #334155; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 12px; color: #94a3b8; }
        .total-row span:last-child { color: #f1f5f9; font-weight: 500; }
        .total-row.final { font-size: 1.25rem; font-weight: bold; color: #f1f5f9; padding-top: 10px; margin-top: 10px; border-top: 1px solid #334155; }
        .total-row.final span:last-child { color: #3b82f6; }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .checkout-btn:hover { background: #218838; transform: translateY(-2px); }

        .digital-product {
            background-color: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .digital-product h3 { border: none; margin: 0 0 10px 0; color: #60a5fa; }
        .digital-product .info { font-size: 0.9rem; color: #94a3b8; line-height: 1.5; }
        
        @media (max-width: 992px) {
            .checkout-container { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

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

<div class="checkout-container">
    <div class="checkout-form">
        <h2>Checkout</h2>

        <?php if ($isDigitalOnly): ?>
        <div class="digital-product">
            <h3>Digital Product Order</h3>
            <p class="info">You are purchasing digital products. The items will be delivered to your email address. No physical shipping is required.</p>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="process_order.php" id="checkoutForm">
            <div class="form-section">
                <h3>Customer Information</h3>
                <div class="form-group">
                    <label for="customer_name">Full Name *</label>
                    <input type="text" id="customer_name" name="customer_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_email">Email *</label>
                        <input type="email" id="customer_email" name="customer_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Phone *</label>
                        <input type="tel" id="customer_phone" name="customer_phone" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <?php if (!$isDigitalOnly): ?>
            <div class="form-section">
                <h3>Shipping Address</h3>
                <div class="form-group">
                    <label for="customer_address">Address *</label>
                    <textarea id="customer_address" name="customer_address" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal Code *</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" id="postal_code" name="postal_code" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="province">Province *</label>
                    <select id="province" name="province" class="form-control" required>
                        <option value="">Select Province</option>
                        <option value="DKI Jakarta">DKI Jakarta</option>
                        <option value="Jawa Barat">Jawa Barat</option>
                        <option value="Jawa Tengah">Jawa Tengah</option>
                        <option value="Jawa Timur">Jawa Timur</option>
                        <option value="Banten">Banten</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Shipping Method</h3>
                <div class="shipping-options">
                    <?php foreach ($shippingOptions as $key => $option): ?>
                    <label class="shipping-option">
                        <input type="radio" name="shipping_method" value="<?= $key ?>" <?= $selectedShipping === $key ? 'checked' : '' ?> onchange="updateShipping()">
                        <span class="radio-custom"></span>
                        <div>
                            <div><?= $option['name'] ?></div>
                            <div style="color: #94a3b8; font-size: 14px;">Rp <?= number_format($option['cost'], 0, ',', '.') ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-section">
                <h3>Payment Method</h3>
                <div class="payment-methods">
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="bank_transfer" required>
                        <span class="radio-custom"></span>
                        <div>
                            <div>Bank Transfer</div>
                            <small style="color: #94a3b8;">Transfer to our bank account</small>
                        </div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="e_wallet" required>
                        <span class="radio-custom"></span>
                        <div>
                            <div>E-Wallet</div>
                            <small style="color: #94a3b8;">OVO, GoPay, DANA</small>
                        </div>
                    </label>
                    <label class="payment-method">
                        <input type="radio" name="payment_method" value="cod" required>
                        <span class="radio-custom"></span>
                        <div>
                            <div>Cash on Delivery</div>
                            <small style="color: #94a3b8;">Pay when delivered</small>
                        </div>
                    </label>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Order Notes (Optional)</h3>
                <div class="form-group">
                    <textarea name="notes" class="form-control" rows="3" placeholder="Special instructions for your order..."></textarea>
                </div>
            </div>
            
            <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
            <input type="hidden" name="coupon_discount" value="<?= $couponDiscount ?>">
            <input type="hidden" name="shipping_cost" value="<?= $shippingCost ?>" id="shipping_cost_input">
            <input type="hidden" name="final_total" value="<?= $finalTotal ?>" id="final_total_input">
            <?php if ($appliedCoupon): ?>
            <input type="hidden" name="coupon_code" value="<?= $appliedCoupon['code'] ?>">
            <?php endif; ?>
        </form>
    </div>
    
    <div class="order-summary">
        <h3>Order Summary</h3>
        
        <?php foreach ($cartItems as $item): ?>
        <div class="summary-item">
            <div class="item-image"><img src="../assets/<?= htmlspecialchars($item['product']['image']) ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>"></div>
            <div class="item-details">
                <div class="item-name"><?= htmlspecialchars($item['product']['name']) ?></div>
                <div class="item-qty-price"><?= $item['quantity'] ?> × Rp <?= number_format($item['product']['price'], 0, ',', '.') ?></div>
            </div>
            <div class="item-total">Rp <?= number_format($item['total'], 0, ',', '.') ?></div>
        </div>
        <?php endforeach; ?>
        
        <div class="coupon-section">
            <?php if (!$appliedCoupon): ?>
            <form method="POST">
                <div class="coupon-input-group">
                    <input type="text" name="coupon_code" class="coupon-input" placeholder="Enter coupon code">
                    <button type="submit" name="apply_coupon" class="coupon-btn">Apply</button>
                </div>
                <?php if ($couponError): ?><div class="coupon-error"><?= htmlspecialchars($couponError) ?></div><?php endif; ?>
            </form>
            <?php else: ?>
            <div class="coupon-success">
                ✅ Coupon "<?= $appliedCoupon['code'] ?>" applied 
                (-<?= $appliedCoupon['discount_type'] === 'percentage' ? $appliedCoupon['discount_value'] . '%' : 'Rp ' . number_format($appliedCoupon['discount_value'], 0, ',', '.') ?>)
                <form method="POST" style="display: inline;"><button type="submit" name="remove_coupon" style="background:none;border:none;color:#f87171;cursor:pointer;margin-left:10px;font-weight:bold;">Remove</button></form>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="summary-totals">
            <div class="total-row"><span>Subtotal:</span><span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span></div>
            <?php if ($couponDiscount > 0): ?>
            <div class="total-row" style="color: #4ade80;"><span>Discount:</span><span>-Rp <?= number_format($couponDiscount, 0, ',', '.') ?></span></div>
            <?php endif; ?>
            <?php if (!$isDigitalOnly): ?>
            <div class="total-row"><span>Shipping:</span><span id="shipping_cost_display">Rp <?= number_format($shippingCost, 0, ',', '.') ?></span></div>
            <?php endif; ?>
            <div class="total-row final"><span>Total:</span><span id="final_total_display">Rp <?= number_format($finalTotal, 0, ',', '.') ?></span></div>
        </div>
        
        <button type="submit" form="checkoutForm" class="checkout-btn">Place Order - Rp <?= number_format($finalTotal, 0, ',', '.') ?></button>
    </div>
</div>

<script>
const shippingOptions = <?= json_encode($shippingOptions) ?>;
const subtotal = <?= $subtotal ?>;
const couponDiscount = <?= $couponDiscount ?>;
const isDigitalOnly = <?= json_encode($isDigitalOnly) ?>;

function updateShipping() {
    if (isDigitalOnly) return;
    const selectedMethod = document.querySelector('input[name="shipping_method"]:checked').value;
    const shippingCost = shippingOptions[selectedMethod].cost;
    const finalTotal = subtotal - couponDiscount + shippingCost;
    
    document.getElementById('shipping_cost_display').textContent = 'Rp ' + shippingCost.toLocaleString('id-ID');
    document.getElementById('final_total_display').textContent = 'Rp ' + finalTotal.toLocaleString('id-ID');
    document.getElementById('shipping_cost_input').value = shippingCost;
    document.getElementById('final_total_input').value = finalTotal;
    document.querySelector('.checkout-btn').textContent = 'Place Order - Rp ' + finalTotal.toLocaleString('id-ID');
}

document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-method').forEach(method => {
            method.classList.remove('selected');
        });
        this.closest('.payment-method').classList.add('selected');
    });
});

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    let isValid = true;
    this.querySelectorAll('[required]').forEach(field => {
        // Only check visible fields for validity
        if (field.offsetParent !== null && !field.value.trim()) {
            field.style.borderColor = '#f87171';
            isValid = false;
        } else {
            field.style.borderColor = '#334155';
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields');
    }
});
</script>

</body>
</html>