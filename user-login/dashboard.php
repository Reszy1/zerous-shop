<?php
ini_set('session.gc_maxlifetime', 3600); // 1 jam
session_set_cookie_params(3600);
session_start();
include __DIR__ . '/../db.php';

// Cek jika pengguna belum login
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

// Ambil email dari session
$email = $_SESSION['email'];

// Ambil data user
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmtUser->execute([$email]);
$user = $stmtUser->fetch();

if (!$user) {
    $_SESSION['login_error'] = "User tidak ditemukan. Silakan periksa kembali atau hubungi admin.";
    unset($_SESSION['email']);
    header('Location: ../user-login/login.php');
    exit;
}

// Ambil statistik order
$stmtStats = $pdo->prepare("
    SELECT COUNT(*) AS total_orders, IFNULL(SUM(total_amount), 0) AS total_spent
    FROM orders
    WHERE customer_email = ? AND order_status = 'delivered'
");
$stmtStats->execute([$user['email']]);
$stats = $stmtStats->fetch();

// Ambil order terbaru
$stmtLatest = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC LIMIT 1");
$stmtLatest->execute([$user['email']]);
$latestOrder = $stmtLatest->fetch();

$completedOrders = $stats['total_orders'];
$totalSpent = $stats['total_spent'];
$customerSince = date('M d, Y', strtotime($user['created_at']));
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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

    /* === MAIN LAYOUT & SIDEBAR (Sama seperti orders.php) === */
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

    /* === MAIN CONTENT === */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 30px;
    }
    .welcome-header h2 { font-size: 1.8rem; font-weight: 700; color: #fff; margin: 0 0 5px 0; }
    .welcome-header p { color: #94a3b8; margin: 0; }

<<<<<<< HEAD
    /* --- PERUBAHAN DI SINI --- */
    .stats-grid { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); /* Diubah untuk memastikan 3 kolom */
        gap: 25px; 
    }
    .stat-card {
        background-color: #1e293b;
        border-radius: 12px;
        padding: 20px; 
        display: flex;
        align-items: center;
        gap: 15px; 
        border: 1px solid #334155;
    }
    .stat-card i { font-size: 1.2rem; padding: 12px; border-radius: 8px; } 
    .icon-orders { color: #60a5fa; background-color: rgba(96, 165, 250, 0.1); }
    .icon-spent { color: #4ade80; background-color: rgba(74, 222, 128, 0.1); }
    .icon-since { color: #facc15; background-color: rgba(251, 191, 36, 0.1); }
    .stat-info p { margin: 0 0 5px 0; color: #94a3b8; font-size: 0.85rem; font-weight: 500; } 
    .stat-info span { font-size: 1.4rem; font-weight: 600; color: #fff; } 

    /* Kartu Pesanan Terakhir */
=======
    /* PENYESUAIAN: Ukuran Kartu Statistik disamakan */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; }
    .stat-card {
        background-color: #1e293b;
        border-radius: 12px;
        padding: 20px; /* Padding disesuaikan */
        display: flex;
        align-items: center;
        gap: 15px; /* Jarak ikon dan teks disesuaikan */
        border: 1px solid #334155;
    }
    .stat-card i { font-size: 1.2rem; padding: 12px; border-radius: 8px; } /* Ukuran ikon dan padding diperkecil */
    .icon-orders { color: #60a5fa; background-color: rgba(96, 165, 250, 0.1); }
    .icon-spent { color: #4ade80; background-color: rgba(74, 222, 128, 0.1); }
    .icon-since { color: #facc15; background-color: rgba(251, 191, 36, 0.1); }
    .stat-info p { margin: 0 0 5px 0; color: #94a3b8; font-size: 0.85rem; font-weight: 500; } /* Ukuran font disesuaikan */
    .stat-info span { font-size: 1.4rem; font-weight: 600; color: #fff; } /* Ukuran font disesuaikan */

    /* PENYESUAIAN: Kartu Pesanan Terakhir disamakan */
>>>>>>> ae843433873d7c7e4a2788bb3695a960f8f4d323
    .latest-order-card {
        background-color: #1e293b;
        border-radius: 12px;
        padding: 25px;
        border: 1px solid #334155;
    }
    .latest-order-card h3 { font-size: 1.2rem; color: #fff; margin: 0 0 20px 0; border-bottom: 1px solid #334155; padding-bottom: 15px;}
    .order-details { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
    .order-id { font-family: 'Courier New', monospace; color: #94a3b8; }
    .order-date { color: #94a3b8; font-size: 0.9rem; }
    .order-total { font-size: 1.1rem; font-weight: 600; color: #fff; }
    .no-orders-msg { color: #94a3b8; margin: 0; padding: 20px 0; text-align: center;}

<<<<<<< HEAD
    /* Tombol */
=======
    /* PENYESUAIAN: Tombol disamakan dengan orders.php */
>>>>>>> ae843433873d7c7e4a2788bb3695a960f8f4d323
    .btn-primary {
        background: #3b82f6; color: white; padding: 8px 16px;
        border: none; border-radius: 6px; cursor: pointer;
        font-weight: 500; text-decoration: none; font-size: 13px;
    }
    .btn-primary:hover { background: #2563eb; }

<<<<<<< HEAD
    /* Responsive untuk grid statistik */
    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: 1fr; /* Kembali ke 1 kolom di tablet */
        }
    }

=======
>>>>>>> ae843433873d7c7e4a2788bb3695a960f8f4d323
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
        <a href="#" class="nav-btn active">My Account</a>
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
            <a href="#" class="nav-link active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
            <a href="orders.php" class="nav-link"><i class="fa-solid fa-receipt"></i> My Orders</a>
            <a href="logout.php" class="nav-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="main-content">
        <div class="welcome-header">
            <h2>Dashboard Overview</h2>
            <p>Here's a summary of your activity at Zerous Shop.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fa-solid fa-box-check icon-orders"></i>
                <div class="stat-info">
                    <p>Completed Orders</p>
                    <span><?= $completedOrders ?></span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-wallet icon-spent"></i>
                <div class="stat-info">
                    <p>Total Spent</p>
                    <span>Rp <?= number_format($totalSpent, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="stat-card">
                <i class="fa-solid fa-calendar-alt icon-since"></i>
                <div class="stat-info">
                    <p>Customer Since</p>
                    <span><?= $customerSince ?></span>
                </div>
            </div>
        </div>

        <div class="latest-order-card">
            <h3>Your Latest Order</h3>
            <?php if ($latestOrder): ?>
                <div class="order-details">
                    <div class="order-id">
                        Order #<?= htmlspecialchars($latestOrder['order_number']) ?>
                    </div>
                    <div class="order-date">
                        <?= date('F j, Y, g:i a', strtotime($latestOrder['created_at'])) ?>
                    </div>
                    <div class="order-total">
                        Rp <?= number_format($latestOrder['total_amount'], 0, ',', '.') ?>
                    </div>
                    <a href="../checkout/track_order.php?order=<?= htmlspecialchars($latestOrder['order_number']) ?>" class="btn btn-primary">
                        View Details
                    </a>
                </div>
            <?php else: ?>
                <p class="no-orders-msg">You have no orders yet. Let's go shopping!</p>
                <a href="../index.php" class="btn btn-primary">Browse Products</a>
            <?php endif; ?>
            </div>
    </div>
</main>

<?php include '../contact-widget.php'; ?>
    <footer style="margin-top:auto;">
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