<?php
$page_title = "Customers";
include 'admin_header.php';

// Fungsi untuk format "time ago"
function time_ago($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = ['y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Query untuk mengambil semua customer beserta data agregat dari orders
$customers = $pdo->query("
    SELECT
        u.id, u.email, u.created_at as user_created_at,
        u.balance, u.created_at as user_created_at,
        o.total_spent, o.order_count, o.last_order_date
    FROM
        users u
    LEFT JOIN (
        SELECT
            customer_email,
            SUM(total_amount) as total_spent,
            COUNT(id) as order_count,
            MAX(created_at) as last_order_date
        FROM orders
        GROUP BY customer_email
    ) AS o ON u.email = o.customer_email
    WHERE
        u.role = 'customer'
    ORDER BY
        u.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="content-header">
    <div>
        <h1>Customers</h1>
        <p>Browse and manage your customers.</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Filter</button>
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="Quick Search by E-mail...">
        </div>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>E-mail Address</th>
                
                <th>Total Spent</th>
                <th>Last Order</th>
                <th>Views</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
            <tr>
                <td><span class="text-muted"><?= $customer['id'] ?></span></td>
                <td><?= htmlspecialchars($customer['email']) ?></td>
                <td>
                    IDR <?= number_format($customer['total_spent'] ?? 0, 2) ?>
                    <small class="sub-text"><?= $customer['order_count'] ?? 0 ?> order(s)</small>
                </td>
                <td><?= $customer['last_order_date'] ? time_ago($customer['last_order_date']) : 'No orders' ?></td>
                <td class="actions">
                    <a href="orders.php?email=<?= urlencode($customer['email']) ?>" class="action-btn" title="View Details"><i class="fa-solid fa-eye"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>