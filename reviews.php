<?php
session_start();
include 'db.php';

// Get filter parameter
$filterProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : null;

// Build query with optional filter
$query = "
    SELECT r.*, p.name AS product_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id
";
$params = [];

if ($filterProductId) {
    $query .= " WHERE r.product_id = :product_id";
    $params['product_id'] = $filterProductId;
}
$query .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reviews - Zerous Shop</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div id="loading-screen">
    <div class="spinner"></div>
    <p>Loading...</p>
</div>

<header>
    <div class="logo-title">
        <img src="assets/logo.webp" alt="Logo" class="logo">
        <h1>Zerous Shop</h1>
    </div>
    <nav class="nav-grid">
        <a href="index.php" class="nav-btn">Products</a>
        <a href="reviews.php" class="nav-btn active">Reviews</a>
        <a href="index.php#news" class="nav-btn">News</a>
        <a href="faq.php" class="nav-btn">FAQ</a>
        <a href="user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>


<style>
body {
    color: white;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}

.content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.filter-review-section {
    background: #1e293b;
    padding: 1.5rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.filter-review-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #f1f5f9;
    margin-bottom: 1rem;
    text-align: center;
}

.filter-form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.filter-label {
    font-size: 1rem;
    color: #94a3b8;
    font-weight: 500;
}

.filter-select {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #334155;
    background-color: #0f172a;
    color: #f1f5f9;
    font-size: 1rem;
    min-width: 250px;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
}

.filter-select option {
    background-color: #0f172a;
    color: #f1f5f9;
}

.reviews-section {
    display: grid;
    gap: 1.5rem;
}

.review-card {
    background: linear-gradient(145deg, #1e293b, #334155);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.review-header strong {
    color: #f1f5f9;
    font-size: 1.1rem;
}

.stars {
    display: flex;
    gap: 0.1rem;
}

.star-filled {
    color: #facc15;
}

.star-empty {
    color: #64748b;
}

.review-date {
    font-size: 0.9rem;
    color: #94a3b8;
    font-weight: 400;
}

.review-product {
    font-size: 0.9rem;
    color: #3b82f6;
    font-style: italic;
    margin-bottom: 0.75rem;
    padding: 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 0.5rem;
    border-left: 3px solid #3b82f6;
}

.review-text {
    color: #e2e8f0;
    line-height: 1.6;
    font-size: 1rem;
    margin: 0;
}
.admin-reply {
    margin-top: 1rem;
    padding: 1rem;
    background-color: rgba(59, 130, 246, 0.1); /* Latar belakang biru transparan */
    border-left: 4px solid #3b82f6; /* Garis aksen biru di kiri */
    border-radius: 0 8px 8px 0;
}
.admin-reply strong {
    color:rgb(173, 190, 209);
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.5rem;
}
.admin-reply p {
    margin: 0;
    color: #cbd5e1;
    font-size: 0.95rem;
    line-height: 1.6;
}

.no-reviews {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
    font-size: 1.1rem;
    font-style: italic;
    background: #1e293b;
    border-radius: 1rem;
    border: 2px dashed #334155;
}

/* Responsive */
@media (max-width: 768px) {
    .content {
        padding: 1rem;
    }
    
    .filter-form {
        align-items: stretch;
    }
    
    .filter-select {
        min-width: auto;
        width: 100%;
    }
    
    .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .review-header .stars {
        align-self: flex-end;
    }
}

@media (max-width: 480px) {
    .review-card {
        padding: 1rem;
    }
    
    .filter-review-section {
        padding: 1rem;
    }
}
        footer {
        position: relative;
        bottom: 0;
        width: 100%;
        background: rgba(18, 18, 36, 0.9);
        backdrop-filter: blur(10px);
        border-top: 1px solid rgba(88, 88, 255, 0.2);
        margin-top: auto;
        padding: 1rem;
        text-align: center;
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        transition: all 0.3s ease;
        }

        footer:hover {
            color: rgba(255, 255, 255, 0.8);
            border-top-color: rgba(88, 88, 255, 0.4);
        }

        /* Responsive footer */
        @media (max-width: 768px) {
            footer {
                font-size: 0.75rem;
                padding: 0.75rem;
            }
        }
</style>

<main class="content">

    <section class="filter-review-section" id="review-filter">
        <h2>Customer Reviews</h2>
        <form method="GET" class="filter-form">
            <label for="product_id" class="filter-label">Filter by Product:</label>
            <select name="product_id" id="product_id" onchange="this.form.submit()" class="filter-select">
                <option value="">All Products</option>
                <?php
                $productStmt = $pdo->query("SELECT id, name FROM products ORDER BY name");
                while ($prod = $productStmt->fetch()):
                    $selected = $filterProductId == $prod['id'] ? 'selected' : '';
                ?>
                    <option value="<?= $prod['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($prod['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>
    </section>

    <section class="reviews-section" id="reviews">
        <?php if (count($reviews) > 0): ?>
            <?php foreach ($reviews as $review):
                $name = htmlspecialchars($review['user']);
                $rating = (int)$review['rating'];
                $date = date('M j, Y', strtotime($review['created_at']));
                $text = nl2br(htmlspecialchars($review['review']));
                $productName = htmlspecialchars($review['product_name']);
            ?>
            <div class="review-card">
                <div class="review-header">
                    <strong><?= $name ?></strong>
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?= $i <= $rating ? 'star-filled' : 'star-empty' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="review-date"><?= $date ?></span>
                </div>
                <div class="review-product">📦 Product: <?= $productName ?></div>
                <p class="review-text"><?= $text ?></p>

                <?php if (!empty($review['admin_reply'])): ?>
                    <div class="admin-reply">
                        <strong>Zerous Reply:</strong>
                        <p><?= nl2br(htmlspecialchars($review['admin_reply'])) ?></p>
                    </div>
                <?php endif; ?>
                </div> <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-reviews">
                <?= $filterProductId ? 'No reviews found for this product.' : 'No reviews available yet. Be the first to leave a review!' ?>
            </div>
        <?php endif; ?>
    </section>

</main>

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
<script>
window.addEventListener('load', () => {
    const loader = document.getElementById('loading-screen');
    loader.style.opacity = '0';
    setTimeout(() => {
        loader.style.display = 'none';
    }, 400);
});

// Enhanced filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    
    // Add loading state when changing filter
    productSelect.addEventListener('change', function() {
        if (this.value !== '') {
            // Add loading state
            const reviewsSection = document.getElementById('reviews');
            reviewsSection.style.opacity = '0.5';
            reviewsSection.style.pointerEvents = 'none';
        }
    });
            document.addEventListener('DOMContentLoaded', function() {
            const footer = document.querySelector('footer');
            
            // Add smooth fade-in animation
            footer.style.opacity = '0';
            footer.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                footer.style.transition = 'all 0.5s ease';
                footer.style.opacity = '1';
                footer.style.transform = 'translateY(0)';
            }, 500);
            
            // Add current time update (optional)
            function updateTime() {
                const now = new Date();
                const timeElement = footer.querySelector('.current-time');
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('id-ID');
                }
            }
            
            // Uncomment if you want to show current time
            // setInterval(updateTime, 1000);
        });
    // Smooth scroll to reviews after filter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('product_id')) {
        setTimeout(() => {
            document.getElementById('reviews').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }, 100);
    }
});
</script>

</body>
</html>