<?php
include 'auth.php'; // Keamanan admin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    $admin_reply = trim($_POST['admin_reply']);

    $stmt = $pdo->prepare("UPDATE reviews SET admin_reply = ? WHERE id = ?");
    $stmt->execute([$admin_reply, $review_id]);

    $_SESSION['message'] = "Reply has been submitted successfully.";
}

header('Location: reviews.php');
exit;
?>