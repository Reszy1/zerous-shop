<?php
include 'auth.php'; // Keamanan admin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $review_id = $_POST['review_id'];
    
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->execute([$review_id]);

    $_SESSION['message'] = "Feedback has been deleted successfully.";
}

header('Location: reviews.php');
exit;
?>