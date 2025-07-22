<?php
session_start();
include '../db.php';

$order = null;
$orderItems = [];
$trackingHistory = [];
$error_message = '';

// Handle form submission untuk tracking
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['order'])) {
    $orderNumber = $_POST['order_number'] ?? $_GET['order'] ?? '';
    $email = $_POST['email'] ?? '';
    
    if (!empty($orderNumber)) {
        // Cari order berdasarkan order number
        $query = "SELECT * FROM orders WHERE order_number = ?";
        $params = [$orderNumber];
        
        // Jika email diisi, tambahkan ke kondisi pencarian untuk keamanan
        if (!empty($email)) {
            $query .= " AND customer_email = ?";
            $params[] = $email;
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Ambil order items
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['id']]);
            $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ambil tracking history
            try {
                $stmt = $pdo->prepare("SELECT * FROM shipping_tracking WHERE order_id = ? ORDER BY updated_at DESC");
                $stmt->execute([$order['id']]);
                $trackingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                // Jika tabel shipping_tracking belum ada, buat data dummy
                $trackingHistory = generateDummyTracking($order);
            }
        } else {
            $error_message = "Order not found. Please check your order number" . (!empty($email) ? " and email address." : ".");
        }
    }
}

function generateDummyTracking($order) {
    $tracking = [];
    $orderDate = new DateTime($order['created_at']);
    $tracking[] = ['status' => 'Order Placed', 'description' => 'Your order has been received.', 'updated_at' => $order['created_at'], 'location' => 'Warehouse'];

    if ($order['order_status'] === 'processing' || $order['order_status'] === 'shipped' || $order['order_status'] === 'delivered') {
        $processingDate = clone $orderDate; $processingDate->add(new DateInterval('PT1H'));
        $tracking[] = ['status' => 'Processing', 'description' => 'Your order is being prepared for shipment.', 'updated_at' => $processingDate->format('Y-m-d H:i:s'), 'location' => 'Warehouse'];
    }
    if ($order['order_status'] === 'shipped' || $order['order_status'] === 'delivered') {
        $shippedDate = clone $orderDate; $shippedDate->add(new DateInterval('P1D'));
        $tracking[] = ['status' => 'Shipped', 'description' => 'Your order has been shipped and is on its way.', 'updated_at' => $shippedDate->format('Y-m-d H:i:s'), 'location' => 'Distribution Center'];
    }
    if ($order['order_status'] === 'delivered') {
        $deliveredDate = clone $orderDate; $deliveredDate->add(new DateInterval('P3D'));
        $tracking[] = ['status' => 'Delivered', 'description' => 'Your order has been successfully delivered.', 'updated_at' => $deliveredDate->format('Y-m-d H:i:s'), 'location' => $order['city']];
    }
    return array_reverse($tracking);
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'status-pending';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        case 'delivered': return 'status-delivered';
        case 'cancelled': return 'status-cancelled';
        default: return 'status-pending';
    }
}

function getPaymentStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'payment-pending';
        case 'paid': return 'payment-paid';
        case 'failed': return 'payment-failed';
        case 'cancelled': return 'payment-cancelled';
        default: return 'payment-pending';
    }
}

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            background-color: #0f172a;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .track-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        
        /* Card Styling */
        .track-form, .order-info, .order-items, .tracking-timeline, .help-section {
            background: #1e293b;
            padding: 30px;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }
        
        h2, h3 {
            color: #f1f5f9;
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #3b82f6;
            font-weight: 600;
        }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 500; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #334155; border-radius: 8px; font-size: 1rem; background-color: #0f172a; color: #f1f5f9; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        .track-btn { width: 100%; padding: 12px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
        .track-btn:hover { background: #2563eb; }

        .error-message { background: rgba(248, 113, 113, 0.1); border-left: 4px solid #f87171; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        
        /* Order Info Header */
        .order-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 20px; }
        .order-number { font-size: 1.5rem; font-weight: bold; color: #60a5fa; font-family: 'Courier New', monospace; line-height: 1; }
        .order-status { display: flex; gap: 10px; flex-wrap: wrap; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; border: 1px solid transparent; }
        
        /* Status Badges */
        .status-pending, .payment-pending { background: rgba(251, 191, 36, 0.1); color: #facc15; border-color: rgba(251, 191, 36, 0.5); }
        .status-processing { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border-color: rgba(59, 130, 246, 0.5); }
        .status-shipped, .status-delivered, .payment-paid { background: rgba(74, 222, 128, 0.1); color: #4ade80; border-color: rgba(74, 222, 128, 0.5); }
        .status-cancelled, .payment-failed { background: rgba(248, 113, 113, 0.1); color: #f87171; border-color: rgba(248, 113, 113, 0.5); }
        
        .order-details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .detail-item { display: flex; flex-direction: column; gap: 5px; }
        .detail-label { font-weight: 600; color: #94a3b8; font-size: 14px; }
        .detail-value { color: #e2e8f0; }

        /* Order Items */
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #334155; }
        .item-row:last-child { border-bottom: none; }
        .item-name { font-weight: 600; color: #f1f5f9; }
        .item-details { font-size: 14px; color: #94a3b8; }
        .item-total { font-weight: 600; color: #f1f5f9; }

        /* Tracking Timeline */
        .timeline { position: relative; padding-left: 25px; border-left: 2px solid #334155; }
        .timeline-item { position: relative; margin-bottom: 30px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -37px; top: 5px; width: 14px; height: 14px;
            border-radius: 50%; background: #334155; border: 3px solid #1e293b;
        }
        .timeline-item.completed::before { background: #3b82f6; }
        .timeline-status { font-weight: 600; color: #f1f5f9; margin-bottom: 5px; }
        .timeline-description { color: #94a3b8; margin-bottom: 8px; }
        .timeline-meta { display: flex; gap: 15px; font-size: 12px; color: #64748b; }
        
        /* Action buttons */
        .action-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 30px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-secondary:hover { background: #475569; }

        .help-section p { color: #94a3b8; margin-bottom: 15px; }
        .contact-item { display: flex; align-items: center; gap: 8px; color: #60a5fa; text-decoration: none; }
        .contact-item:hover { color: #3b82f6; }
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
        <a href="../cart.php" class="nav-btn">Cart</a>
        <a href="../index.php#news" class="nav-btn">News</a>
        <a href="../reviews.php" class="nav-btn">Reviews</a>
        <a href="../user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>

<div class="track-container">
    <div class="track-form">
        <h2>🔍 Track Your Order</h2>
        <form method="POST">
            <div class="form-group">
                <label for="order_number">Order Number</label>
                <input type="text" id="order_number" name="order_number" class="form-control" 
                       placeholder="Enter your order number" required
                       value="<?= htmlspecialchars($_POST['order_number'] ?? $_GET['order'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email Address (Optional)</label>
                <input type="email" id="email" name="email" class="form-control" 
                       placeholder="Enter your email for additional security"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <button type="submit" class="track-btn">Track Order</button>
        </form>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="error-message">
        <?= htmlspecialchars($error_message) ?>
    </div>
    <?php endif; ?>

    <?php if ($order): ?>
    <div class="order-info">
        <div class="order-header">
            <div>
                <div class="order-number"><?= htmlspecialchars($order['order_number']) ?></div>
            </div>
            <div class="order-status">
                <span class="status-badge <?= getStatusBadgeClass($order['order_status']) ?>">
                    Order: <?= formatStatus($order['order_status']) ?>
                </span>
                <span class="status-badge <?= getPaymentStatusBadgeClass($order['payment_status']) ?>">
                    Payment: <?= formatStatus($order['payment_status']) ?>
                </span>
            </div>
        </div>
        
        <div class="order-details-grid">
            <div class="detail-item">
                <span class="detail-label">Order Date</span>
                <span class="detail-value"><?= date('F j, Y, g:i A', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Customer</span>
                <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Shipping Method</span>
                <span class="detail-value"><?= formatStatus($order['shipping_method']) ?></span>
            </div>
        </div>
        
        <?php if($order['shipping_method'] !== 'digital_delivery'): ?>
        <div class="detail-item" style="margin-top: 20px;">
            <span class="detail-label">Shipping Address</span>
            <span class="detail-value">
                <?= htmlspecialchars($order['customer_address']) ?><br>
                <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['province']) ?> <?= htmlspecialchars($order['postal_code']) ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($orderItems)): ?>
    <div class="order-items">
        <h3>📦 Order Items (Total: Rp <?= number_format($order['total_amount'], 0, ',', '.') ?>)</h3>
        <?php foreach ($orderItems as $item): ?>
        <div class="item-row">
            <div class="item-info">
                <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                <div class="item-details">
                    <?= $item['quantity'] ?> × Rp <?= number_format($item['product_price'], 0, ',', '.') ?>
                </div>
            </div>
            <div class="item-total">
                Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="tracking-timeline">
        <h3>📍 Tracking History</h3>
        <div class="timeline">
            <?php if (!empty($trackingHistory)): ?>
                <?php foreach ($trackingHistory as $index => $tracking): ?>
                <div class="timeline-item <?= $index < count($trackingHistory) -1 ? 'completed' : '' ?>">
                    <div class="timeline-status"><?= htmlspecialchars($tracking['status']) ?></div>
                    <div class="timeline-description"><?= htmlspecialchars($tracking['description']) ?></div>
                    <div class="timeline-meta">
                        <span>📅 <?= date('M j, Y, g:i A', strtotime($tracking['updated_at'])) ?></span>
                        <?php if (!empty($tracking['location'])): ?>
                        <span>📍 <?= htmlspecialchars($tracking['location']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="timeline-item">
                    <div class="timeline-status">No Tracking History</div>
                    <div class="timeline-description">Tracking details will appear here once the order is processed.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="action-buttons">
        <a href="../index.php" class="btn btn-primary">Continue Shopping</a>
        <button onclick="window.print()" class="btn btn-secondary">Print Details</button>
    </div>
    <?php endif; ?>
    
    <?php if ($order && $order['order_status'] === 'delivered'): ?>
    <div class="feedback-section">
        <h3>Order Completed!</h3>
        <p>Thank you for your purchase. We would love to hear your feedback about the products you received.</p>
        <div class="action-buttons">
            <a href="../give_review.php?order_id=<?= $order['id'] ?>" class="btn btn-primary">
                <i class="fa-solid fa-star"></i> Leave a Review
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!$order): ?>
document.getElementById('order_number').focus();
<?php endif; ?>
</script>

</body>
</html>