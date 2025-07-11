<?php
session_start();
include '../db.php';

// 1. Validasi Request Awal
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: ../cart.php');
    exit;
}

// 2. Ambil dan Sanitasi Semua Data dari Form Checkout
$cart = $_SESSION['cart'];
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_email = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_SANITIZE_EMAIL);
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? 'Digital Product - No Address');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postal_code = trim($_POST['postal_code'] ?? '');
$payment_method = $_POST['payment_method'] ?? 'bank_transfer';
$shipping_method = $_POST['shipping_method'] ?? 'digital_delivery';
$notes = trim($_POST['notes'] ?? '');
$form_coupon_code = trim($_POST['coupon_code'] ?? '');

// 3. Validasi di Sisi Server
if (empty($customer_name) || empty($customer_email) || empty($customer_phone)) {
    $_SESSION['error'] = 'Please fill in all required customer information.';
    header('Location: index.php');
    exit;
}

$productIds = array_column($cart, 'product_id');
if (empty($productIds)) {
    $_SESSION['error'] = 'Your cart is empty.';
    header('Location: ../cart.php');
    exit;
}
$placeholders = str_repeat('?,', count($productIds) - 1) . '?';
$stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
$stmt->execute($productIds);
$products_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

$products = [];
foreach ($products_from_db as $product) {
    $products[$product['id']] = $product;
}

$server_subtotal = 0;
$isDigitalOnly = true;
foreach ($cart as $item) {
    $product = $products[$item['product_id']] ?? null;
    if (!$product || $product['stock'] < $item['quantity']) {
        $_SESSION['error'] = 'Stock for a product in your cart is not sufficient. Please review your cart.';
        header('Location: ../cart.php');
        exit;
    }
    if ($product['category'] === 'physical') {
        $isDigitalOnly = false;
    }
    $server_subtotal += $product['price'] * $item['quantity'];
}

$couponDiscount = 0;
if (!empty($form_coupon_code) && isset($_SESSION['applied_coupon']) && $_SESSION['applied_coupon']['code'] === $form_coupon_code) {
    $coupon = $_SESSION['applied_coupon'];
    if ($coupon['minimum_amount'] <= $server_subtotal) {
        if ($coupon['discount_type'] === 'percentage') {
            $couponDiscount = ($server_subtotal * $coupon['discount_value']) / 100;
        } else {
            $couponDiscount = $coupon['discount_value'];
        }
    }
}

$shippingOptions = ['reguler' => 15000, 'express' => 25000, 'same_day' => 35000];
$shippingCost = $isDigitalOnly ? 0 : ($shippingOptions[$shipping_method] ?? 0);

$finalTotal = $server_subtotal - $couponDiscount + $shippingCost;


// 4. Proses Pesanan ke Database dengan Transaction
try {
    $pdo->beginTransaction();

    // Buat Nomor Order Unik
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    // =================================================================
    // A. Masukkan data ke tabel 'orders' (DISESUAIKAN DENGAN STRUKTUR GAMBAR ANDA)
    // =================================================================
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            order_number, customer_name, customer_email, customer_phone, 
            customer_address, city, province, postal_code, 
            total_amount, payment_method, order_status, payment_status, shipping_method, shipping_cost, notes, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?, ?, NOW()
        )
    ");
    $stmt->execute([
        $orderNumber, $customer_name, $customer_email, $customer_phone,
        $customer_address, $city, $province, $postal_code,
        $finalTotal, $payment_method, $shipping_method, $shippingCost, $notes
    ]);
    $orderId = $pdo->lastInsertId();

    // B. Masukkan setiap item ke tabel 'order_items'
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, product_price, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($cart as $item) {
        $product = $products[$item['product_id']];
        $itemSubtotal = $product['price'] * $item['quantity'];
        $itemStmt->execute([
            $orderId, $item['product_id'], $product['name'], $item['quantity'], $product['price'], $itemSubtotal
        ]);
    }

    // =================================================================
    // C. (LOGIKA BARU) Masukkan data ke tabel 'payment_logs'
    // =================================================================
    $logStmt = $pdo->prepare("
        INSERT INTO payment_logs (order_id, payment_method, amount, status, created_at) 
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $logStmt->execute([$orderId, $payment_method, $finalTotal]);


    // D. Kurangi stok produk
    $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
    foreach ($cart as $item) {
        $stockStmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // E. Jika kupon digunakan, update jumlah penggunaannya
    if ($couponDiscount > 0 && isset($coupon)) {
        $couponStmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $couponStmt->execute([$coupon['id']]);
    }

    // Jika semua berhasil, commit transaksi
    $pdo->commit();

} catch (Exception $e) {
    // Jika ada kesalahan, batalkan semua perubahan
    $pdo->rollBack();
    $_SESSION['error'] = 'Failed to process your order. Please try again. Error: ' . $e->getMessage();
    header('Location: index.php');
    exit;
}

// 5. Pembersihan dan Redirect ke Halaman Sukses
$_SESSION['last_order_id'] = $orderId;
$_SESSION['last_order_number'] = $orderNumber;
unset($_SESSION['cart']);
unset($_SESSION['applied_coupon']);
header("Location: success.php");
exit;
?>