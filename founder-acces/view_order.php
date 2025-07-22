<?php
$page_title = "Invoice Details";
include 'admin_header.php';

// Validasi ID dari URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Invalid Order ID.</p>";
    include 'admin_footer.php';
    exit;
}
$order_id = $_GET['id'];

// 1. Ambil data pesanan utama
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    echo "<p>Order not found.</p>";
    include 'admin_footer.php';
    exit;
}

// 2. Ambil item-item yang dipesan
$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);
$order_items = $items->fetchAll();

// 3. Ambil riwayat pembayaran
$logs = $pdo->prepare("SELECT * FROM payment_logs WHERE order_id = ? ORDER BY created_at DESC");
$logs->execute([$order_id]);
$payment_logs = $logs->fetchAll();

// Hitung subtotal dari order_items
$subtotal = 0;
foreach($order_items as $item) {
    $subtotal += $item['subtotal'];
}
?>

<div class="content-header">
    <div>
        <h1>Invoice Details</h1>
        <p>View the details of the invoice #<?= htmlspecialchars($order['order_number']) ?></p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary"><i class="fa-solid fa-download"></i> Download PDF</button>
    </div>
</div>

<div class="invoice-grid">
    <div class="invoice-main">
        <div class="card">
            <h3>Order Information</h3>
            <div class="info-grid">
                <div><label>ID</label><span><?= htmlspecialchars($order['order_number']) ?></span></div>
                <div><label>Status</label><span class="status-badge status-<?= $order['payment_status'] ?>"><?= ucfirst($order['payment_status']) ?></span></div>
                <div><label>Payment Method</label><span><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span></div>
                <div><label>Subtotal</label><span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span></div>
                <div><label>Shipping Cost</label><span>Rp <?= number_format($order['shipping_cost'], 0, ',', '.') ?></span></div>
                <div><label>Total Price</label><span class="total-price">Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></span></div>
                <div><label>Created At</label><span><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></span></div>
                <div><label>Completed At</label><span><?= $order['order_status'] == 'delivered' ? date('d M Y, H:i', strtotime($order['updated_at'])) : 'N/A' ?></span></div>
            </div>
        </div>

        <div class="card">
            <h3>Items</h3>
            <table class="item-table">
                <?php foreach($order_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?> (x<?= $item['quantity'] ?>)</td>
                    <td>Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="card">
            <h3>Payment History</h3>
            <?php foreach($payment_logs as $log): ?>
                <div class="payment-history-item">
                    <span class="status-badge status-<?= $log['status'] ?>"><?= ucfirst($log['status']) ?></span>
                    <span>Rp <?= number_format($log['amount'], 0, ',', '.') ?></span>
                    <span><?= date('d M Y, H:i', strtotime($log['created_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="invoice-side">
        <div class="card">
            <h3>Customer Information</h3>
            <div class="info-grid">
                <div><label>E-mail Address</label><a href="mailto:<?= htmlspecialchars($order['customer_email']) ?>" class="link"><?= htmlspecialchars($order['customer_email']) ?></a></div>
                <div><label>IP Address</label><span><?= htmlspecialchars($order['ip_address'] ?? 'N/A') ?></span></div>
                <div><label>Country</label><span>Indonesia</span></div> <div><label>User Agent</label><span class="user-agent"><?= htmlspecialchars($order['user_agent'] ?? 'N/A') ?></span></div>
            </div>
        </div>
        <div class="card">
            <h3>Invoice Note</h3>
            <p class="note"><?= !empty($order['notes']) ? htmlspecialchars($order['notes']) : 'No notes for this order.' ?></p>
        </div>
    </div>
</div>


<?php include 'admin_footer.php'; ?>