<?php
// Cek jika sesi belum aktif, baru jalankan session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika user belum login, tendang ke halaman login
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    header('Location: ../user-login/login.php');
    exit;
}

include __DIR__ . '/../db.php';

// Cek apakah user yang login adalah admin
$stmt = $pdo->prepare("SELECT role FROM users WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$user = $stmt->fetch();

// Jika user bukan admin atau tidak ditemukan, tendang ke halaman utama
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header('Location: ../index.php');
    exit;
}


// Jika semua aman, variabel $pdo dan session bisa digunakan di halaman admin.
?>