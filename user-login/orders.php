<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

include __DIR__ . '/../db.php';

$email = $_SESSION['email'] ?? '';

$stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmtUser->execute([$email]);
$user = $stmtUser->fetch();

if (!$user) {
    $_SESSION['login_error'] = "User tidak ditemukan. Silakan login ulang.";
    unset($_SESSION['email']);
    header('Location: ../user-login/login.php');
    exit;
}

// Query untuk mengambil data pesanan sudah benar
$stmtOrders = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC");
$stmtOrders->execute([$user['email']]);
$orders = $stmtOrders->fetchAll();

// Fungsi helper untuk status
function getStatusClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'delivered': return 'status-completed';
        case 'pending': return 'status-pending';
        case 'cancelled': return 'status-cancelled';
        case 'processing': return 'status-processing';
        case 'shipped': return 'status-shipped';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
        <style>
        /* === FONT & BODY === */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            background-color: #0f172a;
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* === MAIN LAYOUT & SIDEBAR (Sama seperti dashboard.php) === */
        .dashboard-container { display: flex; max-width: 1200px; margin: 40px auto; gap: 30px; flex-grow: 1; }
        .sidebar { background-color: #1e293b; padding: 20px; width: 250px; border-radius: 12px; align-self: flex-start; border: 1px solid #334155; }
        .sidebar-user { display: flex; align-items: center; gap: 15px; padding-bottom: 20px; border-bottom: 1px solid #334155; margin-bottom: 15px; }
        .user-avatar { width: 40px; height: 40px; background-color: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .user-info strong { display: block; color: #fff; font-weight: 600; }
        .user-info span { font-size: 0.8rem; color: #94a3b8; }
        .sidebar-nav a.nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 10px; margin-bottom: 4px; color: #94a3b8; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.2s ease; }
        .sidebar-nav a.nav-link:hover { background-color: #334155; color: #fff; }
        .sidebar-nav a.nav-link.active { background-color: #3b82f6; color: #fff; }
        .sidebar-nav a.nav-link i { width: 20px; text-align: center; }
        .sidebar-nav a.logout-btn { color: #f87171; margin-top: 20px; }
        .sidebar-nav a.logout-btn:hover { background-color: rgba(248, 113, 113, 0.1); }

        /* === MAIN CONTENT (orders.php) === */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        .welcome-header h2 { font-size: 1.8rem; font-weight: 700; color: #fff; margin: 0 0 5px 0; }
        .welcome-header p { color: #94a3b8; margin: 0; }
        .table-container { background-color: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 16px 20px; text-align: left; border-bottom: 1px solid #334155; }
        thead tr { background-color: #2c3a52; }
        th { color: #94a3b8; text-transform: uppercase; font-size: 12px; font-weight: 600; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #25334a; }
        .no-orders-msg { text-align: center; color: #94a3b8; padding: 40px; }

        /* Status Badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-paid, .status-delivered, .status-completed { background-color: rgba(74, 222, 128, 0.1); color: #4ade80; }
        .status-pending { background-color: rgba(251, 191, 36, 0.1); color: #facc15; }
        .status-processing, .status-shipped { background-color: rgba(96, 165, 250, 0.1); color: #60a5fa; }
        .status-cancelled, .status-failed { background-color: rgba(248, 113, 113, 0.1); color: #f87171; }

        /* Link & Tombol */
        .order-id-link { color: #60a5fa; text-decoration: none; font-weight: 500; }
        .order-id-link:hover { text-decoration: underline; }
        .btn-sm { padding: 8px 16px; font-size: 13px; font-weight: 500; }
        .btn-primary {
            background: #3b82f6; color: white; border: none; border-radius: 6px;
            cursor: pointer; text-decoration: none; display: inline-block;
        }
        .btn-primary:hover { background: #2563eb; }

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
        <a href="dashboard.php" class="nav-btn active">My Account</a>
    </nav>
</header>

<main class="dashboard-container">
    
    <aside class="sidebar">
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-info">
                <strong>Welcome Back!</strong>
                <span><?= htmlspecialchars($email) ?></span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link active"><i class="fa-solid fa-receipt"></i> My Orders</a>
            <a href="logout.php" class="nav-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <div class="welcome-header">
            <h2>Order History</h2>
            <p>Here is a list of all your past and current orders.</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th></th> </tr>
                </thead>
                <tbody>
                <?php if (count($orders) === 0): ?>
                    <tr>
                        <td colspan="6" class="no-orders-msg">You have no orders yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a href="../checkout/track_order.php?order=<?= htmlspecialchars($order['order_number']) ?>" class="order-id-link">
                                <?= htmlspecialchars($order['order_number']) ?>
                            </a>
                        </td>
                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        <td><strong>Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></strong></td>
                        <td>
                            <span class="status-badge <?= getStatusClass($order['payment_status']) ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= getStatusClass($order['order_status']) ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="../checkout/track_order.php?order=<?= htmlspecialchars($order['order_number']) ?>" class="btn btn-primary btn-sm">
                                View Details
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</body>

<?php include '../contact-widget.php'; ?>
    <footer style="margin-top: auto;">
        <div style="max-width: 1200px; margin: 0 auto;">
            &copy; <?php echo date('Y'); ?> Zerous Shop. All rights reserved.
            <br>
            <small style="opacity: 0.7; font-size: 0.75rem;">
                Secure Login System | Last updated: <?php echo date('M Y'); ?>
            </small>
        </div>
    </footer>

</body>
</html>