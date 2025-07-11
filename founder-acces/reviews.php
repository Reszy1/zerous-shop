<?php
$page_title = "Feedbacks";
include 'admin_header.php';

// Fungsi untuk membuat bintang rating
function generate_stars($rating) {
    $stars_html = '<div class="star-rating">';
    for ($i = 1; $i <= 5; $i++) {
        $class = ($i <= $rating) ? 'filled' : 'empty';
        $stars_html .= "<i class='fa-solid fa-star star {$class}'></i>";
    }
    $stars_html .= '</div>';
    return $stars_html;
}

// Ambil semua ulasan dari database, diurutkan dari yang terbaru
$reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="content-header">
    <div>
        <h1>Feedbacks</h1>
        <p>Browse and manage your feedbacks.</p>
    </div>
    <div class="header-actions">
        <div class="search-bar">
            <i class="fa-solid fa-search"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>
</div>

<?php if (isset($_SESSION['message'])): ?>
<div class="alert alert-success"><?= $_SESSION['message']; unset($_SESSION['message']); ?></div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Feedback</th>
                <th>Rating</th>
                <th>Reply</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reviews)): ?>
                <tr><td colspan="7" style="text-align: center; color: #94a3b8;">No feedbacks yet.</td></tr>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <tr>
                    <td><span class="text-muted"><?= $review['id'] ?></span></td>
                    <td class="feedback-text"><?= htmlspecialchars($review['review']) ?></td>
                    <td><?= generate_stars($review['rating']) ?></td>
                    <td><?= !empty($review['admin_reply']) ? '✓ Replied' : '-' ?></td>
                    <td><?= date('n/j/Y, g:i A', strtotime($review['created_at'])) ?></td>
                    <td><?= date('n/j/Y, g:i A', strtotime($review['updated_at'])) ?></td>
                    <td class="actions">
                        <button type="button" class="action-btn-text reply reply-btn"
                                data-id="<?= $review['id'] ?>"
                                data-text="<?= htmlspecialchars($review['review']) ?>"
                                data-rating="<?= $review['rating'] ?>">
                            <i class="fa-regular fa-comment-dots"></i> Reply
                        </button>
                        <form action="delete_review.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this feedback?');" style="display: inline;">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <button type="submit" class="action-btn-text appeal" style="background:none; border:none; padding:0; cursor:pointer;">Delete</button>
                        </form>
                        <?php if ($review['order_id']): ?>
                        <a href="view_order.php?id=<?= $review['order_id'] ?>" class="action-btn-text">View Invoice</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div id="replyModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <form action="reply_to_review.php" method="POST" id="replyForm">
            <div class="modal-header">
                <h2>Replying to Feedback</h2>
                <button type="button" class="close-btn" onclick="closeReplyModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="original-feedback">
                    <label>Feedback</label>
                    <div id="originalFeedbackContent" class="feedback-display">
                        </div>
                </div>
                
                <input type="hidden" name="review_id" id="modalReviewId">
                <div class="form-group">
                    <label for="adminReplyText">Your Reply</label>
                    <textarea name="admin_reply" id="adminReplyText" rows="4" class="form-control" placeholder="Write your professional reply here..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
            </div>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>

<script>
    const modal = document.getElementById('replyModal');
    const modalReviewId = document.getElementById('modalReviewId');
    const originalFeedbackContent = document.getElementById('originalFeedbackContent');
    const adminReplyText = document.getElementById('adminReplyText');

    // Fungsi untuk membuat HTML bintang di sisi browser
    function generateStarsJS(rating) {
        let starsHTML = '';
        for (let i = 1; i <= 5; i++) {
            const classToAdd = i <= rating ? 'filled' : 'empty';
            starsHTML += `<i class='fa-solid fa-star star ${classToAdd}'></i>`;
        }
        return starsHTML;
    }

    // Fungsi untuk membuka modal
    function openReplyModal(id, text, rating) {
        modalReviewId.value = id;
        originalFeedbackContent.innerHTML = `<p>${text}</p><div class="star-rating">${generateStarsJS(rating)}</div>`;
        adminReplyText.value = ''; // Kosongkan textarea
        modal.style.display = 'flex';
        adminReplyText.focus(); // Langsung fokus ke textarea
    }

    // Fungsi untuk menutup modal
    function closeReplyModal() {
        modal.style.display = 'none';
    }

    // Event listener untuk semua tombol reply
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('reply-btn')) {
            const id = e.target.dataset.id;
            const text = e.target.dataset.text;
            const rating = e.target.dataset.rating;
            openReplyModal(id, text, rating);
        }
    });

    // Klik di luar area konten modal untuk menutupnya
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeReplyModal();
        }
    });
</script>