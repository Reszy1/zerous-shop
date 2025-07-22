<?php
include 'auth.php'; // Keamanan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    
    // Ambil nama gambar untuk dihapus dari folder
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $image_name = $stmt->fetchColumn();

    // Hapus dari database
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);

    // Hapus file gambar
    if ($image_name && file_exists('../assets/' . $image_name)) {
        unlink('../assets/' . $image_name);
    }

    $_SESSION['success'] = "Product deleted successfully!";
}

header('Location: products.php');
exit;
?>