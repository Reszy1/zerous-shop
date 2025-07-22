<?php
session_start();
include 'db.php';

// Pastikan pengguna sudah login
if (!isset($_SESSION['email'])) {
    header('Location: user-login/login.php');
    exit;
}
$customer_email = $_SESSION['email'];

// Validasi order_id dari URL
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    die("Invalid Order ID.");
}
$order_id = $_GET['order_id'];

// Ambil detail pesanan untuk memastikan pesanan ini milik pengguna yang login
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_email = ? AND order_status = 'delivered'");
$stmt->execute([$order_id, $customer_email]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or you don't have permission to review this order.");
}

// Ambil item produk dari pesanan ini
$stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $rating = $_POST['rating'];
    $review_text = trim($_POST['review']);

    // Masukkan review ke database, sesuaikan dengan struktur tabel 'reviews' Anda
    $insertStmt = $pdo->prepare("
        INSERT INTO reviews (product_id, user, order_id, rating, review, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    // Menggunakan nama customer dari data order sebagai 'user'
    $insertStmt->execute([$product_id, $order['customer_name'], $order_id, $rating, $review_text]);
    
    // Arahkan ke halaman My Orders setelah submit
    header('Location: user-login/orders.php?review_success=1');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<style>
        body {
        background-color: #0f172a;
        color: #e2e8f0;
        display: flex;
        flex-direction: column;
        min-height: 100vh;}
</style>



<head>
    <meta charset="UTF-8">
    <title>Leave a Review</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* CSS untuk Halaman Review */
        body { background-color: #0f172a; color: #e2e8f0; font-family: 'Inter', sans-serif; }
        .review-container { max-width: 700px; margin: 40px auto; padding: 2rem; background: #1e293b; border-radius: 12px; border: 1px solid #334155;}
        .review-container h1 { color: #fff; text-align: center; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; color: #94a3b8; font-weight: 500; margin-bottom: 0.5rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #334155; background-color: #0f172a; color: #e2e8f0; border-radius: 6px; }
        .rating-stars { display: flex; flex-direction: row-reverse; justify-content: flex-end; }
        .rating-stars input { display: none; }
        .rating-stars label { font-size: 2rem; color: #475569; cursor: pointer; transition: color 0.2s; }
        .rating-stars input:checked ~ label, .rating-stars label:hover, .rating-stars label:hover ~ label { color: #facc15; }
        .btn-submit { background: #3b82f6; color: white; padding: 12px; width: 100%; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <head>
        <meta charset="UTF-8">
        <title>Zerous Shop</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>

    <header>
        <div class="logo-title">
            <img src="assets/logo.webp" alt="Logo" class="logo">
            <h1>Zerous Shop</h1>
        </div>

        <nav class="nav-grid">
            <a href="#" class="nav-btn active">Products</a>
            <a href="reviews.php" class="nav-btn">Reviews</a>
            <a href="#news" class="nav-btn">News</a>
            <a href="faq.php" class="nav-btn">FAQ</a>
            <a href="user-login/dashboard.php" class="nav-btn">My Account</a>
        </nav>
    </header>
    
    <div class="review-container">
        <h1>Leave a Review for Order #<?= htmlspecialchars($order['order_number']) ?></h1>
        <form method="POST">
            <div class="form-group">
                <label for="product_id">Select Product to Review:</label>
                <select name="product_id" id="product_id" class="form-control" required>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= $item['product_id'] ?>"><?= htmlspecialchars($item['product_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Your Rating:</label>
                <div class="rating-stars">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                </div>
            </div>

            <div class="form-group">
                <label for="review">Your Feedback:</label>
                <textarea name="review" id="review" rows="5" class="form-control" placeholder="Tell us what you think..."></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Submit Review</button>
        </form>
    </div>

    <?php include 'contact-widget.php'; ?>

    <footer>
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