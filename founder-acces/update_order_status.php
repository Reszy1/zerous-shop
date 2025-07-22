<?php
include 'auth.php'; // Keamanan admin
require '../user-login/vendor/autoload.php'; // Panggil autoloader Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: orders.php');
    exit;
}

// 1. Ambil data dari form
$order_id = $_POST['order_id'];
$new_payment_status = $_POST['payment_status'];
$new_order_status = $_POST['order_status'];

// Informasi untuk email
$order_number = $_POST['order_number'];
$customer_email = $_POST['customer_email'];

// 2. Update status di database
try {
    $stmt = $pdo->prepare("UPDATE orders SET payment_status = ?, order_status = ? WHERE id = ?");
    $stmt->execute([$new_payment_status, $new_order_status, $order_id]);
} catch(PDOException $e) {
    $_SESSION['message'] = "Error updating order: " . $e->getMessage();
    header('Location: orders.php');
    exit;
}

// 3. Kirim notifikasi email ke pelanggan
$mail = new PHPMailer(true);
$email_subject = "Update for Your Order #" . $order_number;
$email_body = "Hi,<br><br>There is an update on your order <b>#" . $order_number . "</b>.<br><br>New Order Status: <b>" . ucfirst($new_order_status) . "</b><br>New Payment Status: <b>" . ucfirst($new_payment_status) . "</b><br><br>You can track your order progress on our website.<br><br>Thank you,<br>Zerous Shop";

try {
    // Pengaturan Server SMTP (GANTI DENGAN PENGATURAN ANDA)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'zeronestore.shop@gmail.com';
    $mail->Password   = 'qrgsmukxspsauqjq'; // Gunakan App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Penerima
    $mail->setFrom('no-reply@zerousshop.com', 'Zerous Shop');
    $mail->addAddress($customer_email);

    // Konten
    $mail->isHTML(true);
    $mail->Subject = $email_subject;
    $mail->Body    = $email_body;

    $mail->send();
    $_SESSION['message'] = "Order #$order_number updated successfully and notification sent to customer.";

} catch (Exception $e) {
    // Jika email gagal terkirim, update tetap berhasil, tapi beri notifikasi error email
    $_SESSION['message'] = "Order #$order_number updated, but failed to send email notification. Mailer Error: {$mail->ErrorInfo}";
}

// 4. Kembali ke halaman orders
header('Location: orders.php');
exit;
?>