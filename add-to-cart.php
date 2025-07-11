<?php
session_start();
include 'db.php';

// Cek apakah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil data dari form
$productId = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validasi input
if ($productId <= 0 || $quantity <= 0) {
    $_SESSION['error'] = 'Invalid product or quantity';
    header('Location: index.php');
    exit;
}

// Cek apakah produk ada di database
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: index.php');
    exit;
}

// Cek stok
if ($product['stock'] < $quantity) {
    $_SESSION['error'] = 'Not enough stock available';
    header('Location: products/product.php?id=' . $productId);
    exit;
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Cek apakah produk sudah ada di keranjang
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $productId) {
        $newQuantity = $item['quantity'] + $quantity;
        
        // Cek apakah total quantity tidak melebihi stok
        if ($newQuantity <= $product['stock']) {
            $item['quantity'] = $newQuantity;
            $found = true;
            $_SESSION['success'] = 'Product quantity updated in cart';
        } else {
            $_SESSION['error'] = 'Cannot add more items. Stock limit reached.';
        }
        break;
    }
}

// Jika produk belum ada di keranjang, tambahkan baru
if (!$found && !isset($_SESSION['error'])) {
    $_SESSION['cart'][] = [
        'product_id' => $productId,
        'quantity' => $quantity
    ];
    $_SESSION['success'] = 'Product added to cart successfully';
}

// Redirect kembali ke halaman produk atau keranjang
$redirectTo = isset($_POST['redirect']) ? $_POST['redirect'] : 'products/product.php?id=' . $productId;
header('Location: ' . $redirectTo);
exit;
?>