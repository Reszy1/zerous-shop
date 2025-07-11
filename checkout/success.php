<?php
session_start();
include '../db.php';

// 1. Validasi Akses Halaman
// Pastikan pengguna baru saja menyelesaikan order dan memiliki session ID
if (!isset($_SESSION['last_order_id'])) {
    // Jika tidak ada, arahkan ke halaman utama
    header('Location: ../index.php');
    exit;
}

$orderId = $_SESSION['last_order_id'];
$order = null;
$orderItems = [];

// 2. Ambil Detail Pesanan dari Database
try {
    // Ambil data pesanan utama
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Jika pesanan tidak ditemukan, hentikan
    if (!$order) {
        unset($_SESSION['last_order_id']);
        header('Location: ../index.php');
        exit;
    }

    // Ambil item-item dalam pesanan
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$orderId]);
    $orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Jika ada error database, arahkan ke halaman utama
    die("Error fetching order details: " . $e->getMessage());
}

// 3. Hapus Session setelah data diambil agar halaman ini hanya bisa dilihat sekali
unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_number']);

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 40px;
            min-height: 100vh;
        }

        .success-container {
            max-width: 700px;
            width: 100%;
            text-align: center;
            padding: 20px;
        }

        .success-box {
            background: #1e293b;
            padding: 40px;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            line-height: 1;
            margin-bottom: 20px;
            animation: pop-in 0.5s ease-out;
        }

        @keyframes pop-in {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-title {
            font-size: 2rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 10px;
        }

        .success-message {
            color: #94a3b8;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .order-number-box {
            background: rgba(15, 23, 42, 0.8);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: #60a5fa;
            border: 1px dashed #334155;
        }

        .order-summary-card {
            background: #0f172a;
            border-radius: 8px;
            padding: 25px;
            text-align: left;
            margin-bottom: 30px;
        }
        
        .summary-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #cbd5e1;
            padding-bottom: 10px;
            border-bottom: 1px solid #334155;
            margin: -5px 0 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
        }

        .detail-label {
            color: #94a3b8;
        }

        .detail-value {
            color: #f1f5f9;
            font-weight: 500;
            text-align: right;
        }

        .item-list {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #334155;
        }
        
        .item-row {
             display: flex;
             justify-content: space-between;
             align-items: center;
             padding: 10px 0;
             border-bottom: 1px solid #334155;
        }
        .item-row:last-child { border: none; }
        .item-name { color: #cbd5e1; }
        .item-qty { color: #94a3b8; font-size: 0.9rem; }
        .item-price { font-weight: 500; }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background-color: #334155;
            color: #cbd5e1;
        }
        .btn-secondary:hover {
            background-color: #475569;
            transform: translateY(-2px);
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

    <div class="success-container">
        <?php if ($order): ?>
        <div class="success-box">
            <div class="success-icon">✅</div>
            <h2 class="success-title">Thank You for Your Order!</h2>
            <p class="success-message">Your order has been placed successfully. A confirmation has been sent to your email address.</p>
            
            <p style="color: #94a3b8;">Your Order Number:</p>
            <div class="order-number-box"><?= htmlspecialchars($order['order_number']) ?></div>

            <div class="order-summary-card">
                <h3 class="summary-title">Order Summary</h3>
                <div class="detail-row">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value"><?= formatStatus($order['payment_method']) ?></span>
                </div>
                
                <?php if ($order['shipping_method'] !== 'digital_delivery'): ?>
                <div class="detail-row">
                    <span class="detail-label">Shipping Method:</span>
                    <span class="detail-value"><?= formatStatus($order['shipping_method']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Shipping Address:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['customer_address']) ?></span>
                </div>
                <?php else: ?>
                <div class="detail-row">
                    <span class="detail-label">Delivery:</span>
                    <span class="detail-value">Delivered to <?= htmlspecialchars($order['customer_email']) ?></span>
                </div>
                <?php endif; ?>

                <div class="item-list">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="item-row">
                           <div>
                                <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div class="item-qty">Quantity: <?= $item['quantity'] ?></div>
                           </div>
                           <div class="item-price">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="action-buttons">
                <a href="track_order.php?order=<?= htmlspecialchars($order['order_number']) ?>" class="btn btn-primary">Track Your Order</a>
                <a href="../index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
            <?php if ($order && $order['order_status'] === 'delivered'): ?>
                <div class="feedback-prompt">
                    <p>Your order is complete. Help others by leaving a review!</p>
                    <a href="../give_review.php?order_id=<?= $order['id'] ?>" class="btn btn-primary" style="background-color: #facc15; color: #1e293b;">
                        <i class="fa-solid fa-star"></i> Leave a Review
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="success-box">
            <h2 class="success-title">Error</h2>
            <p class="success-message">Could not find your order details. Please check your order history in your dashboard.</p>
            <div class="action-buttons">
                 <a href="../index.php" class="btn btn-primary">Go to Homepage</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

</body>
</html>