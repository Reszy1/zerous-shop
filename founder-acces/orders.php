<?php 
$page_title = "Manage Invoices";
include 'admin_header.php';

// Ambil semua data pesanan dari database, diurutkan dari yang terbaru
$orders = $pdo->query("
    SELECT 
        o.*,
        (SELECT oi.product_name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) as first_product_name
    FROM orders o 
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <div>
        <h1>Invoices</h1>
        <p>Browse and manage your invoices.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="Quick Search by ID...">
        </div>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Email</th>
                <th>Products</th>
                <th>Total</th>
                <th>Date</th>
                <th style="width: 40%;">Actions</th> </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>
                    <a href="view_order.php?id=<?= $order['id'] ?>" class="link">
                        <?= htmlspecialchars($order['order_number']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($order['customer_email']) ?></td>
                <td><?= htmlspecialchars($order['first_product_name']) ?: 'N/A' ?></td>
                <td>Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></td>
                <td><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></td>
                
                <td>
                    <form action="update_order_status.php" method="POST" class="status-form">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <input type="hidden" name="order_number" value="<?= $order['order_number'] ?>">
                        <input type="hidden" name="customer_email" value="<?= $order['customer_email'] ?>">

                        <select name="payment_status" class="status-select status-<?= $order['payment_status'] ?>">
                            <option value="pending" <?= $order['payment_status'] == 'pending' ? 'selected' : '' ?>>Payment: Pending</option>
                            <option value="paid" <?= $order['payment_status'] == 'paid' ? 'selected' : '' ?>>Payment: Paid</option>
                            <option value="failed" <?= $order['payment_status'] == 'failed' ? 'selected' : '' ?>>Payment: Failed</option>
                            <option value="cancelled" <?= $order['payment_status'] == 'cancelled' ? 'selected' : '' ?>>Payment: Cancelled</option>
                        </select>

                        <select name="order_status" class="status-select status-<?= $order['order_status'] ?>">
                            <option value="pending" <?= $order['order_status'] == 'pending' ? 'selected' : '' ?>>Order: Pending</option>
                            <option value="processing" <?= $order['order_status'] == 'processing' ? 'selected' : '' ?>>Order: Processing</option>
                            <option value="shipped" <?= $order['order_status'] == 'shipped' ? 'selected' : '' ?>>Order: Shipped</option>
                            <option value="delivered" <?= $order['order_status'] == 'delivered' ? 'selected' : '' ?>>Order: Delivered</option>
                            <option value="cancelled" <?= $order['order_status'] == 'cancelled' ? 'selected' : '' ?>>Order: Cancelled</option>
                        </select>

                        <button type="submit" class="btn update-btn">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>