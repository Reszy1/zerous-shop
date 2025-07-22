<?php include 'db.php'; ?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zerous Shop</title>
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
        <a href="#" class="nav-btn active">Products</a>
        <a href="reviews.php" class="nav-btn">Reviews</a>
        <a href="#news" class="nav-btn">News</a>
        <a href="faq.php" class="nav-btn">FAQ</a>
        <a href="user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>

<?php include 'search-bar.php'; ?>

<style>
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

    /* Pastikan body menggunakan flexbox untuk sticky footer */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Main content area */
    .main-content {
        flex: 1;
    }

    /* Responsive footer */
    @media (max-width: 768px) {
        footer {
            font-size: 0.75rem;
            padding: 0.75rem;
        }
    }
</style>

<section class="product-section">
    <div class="product-list">
        <?php
        // --- START: MODIFIED DATABASE QUERY ---
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];

        // Add search filter
        if (!empty($_GET['search'])) {
            $sql .= " AND name LIKE :search";
            $params[':search'] = '%' . trim($_GET['search']) . '%';
        }

        // Add category filter
        if (!empty($_GET['category']) && in_array($_GET['category'], ['digital', 'physical'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $_GET['category'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        // --- END: MODIFIED DATABASE QUERY ---

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()):
                $name = htmlspecialchars($row['name']);
                $image = 'assets/' . htmlspecialchars($row['image']);
                $price = number_format($row['price'], 2);
                $stock = (int)$row['stock'];
             // Define badge variable
        ?>
        <a class="product-card block h-full bg-card border border-white/5 rounded-lg text-t-primary group"
           href="products/product.php?id=<?= $row['id'] ?>">
            <div class="h-full group transition-colors duration-150 ease-in-out hover:bg-black/10 rounded-lg relative">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-black/0 to-black/50 rounded-lg"></div>
                <div class="relative overflow-hidden p-2 mb-4 border-b border-white/5">
                    <img src="<?= $image ?>" alt="<?= $name ?>" class="rounded-lg aspect-video w-full object-scale-down" loading="lazy">
                    <?php if (!empty($badge)): ?>
                    <div class="badges absolute top-2 right-2 flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-2 bg-accent-500 text-t-accent text-xs font-semibold px-2 py-1 rounded-lg" style="background-color: #ff0000;">
                            <?= $badge ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="info px-2 pb-2">
                    <h3><?= $name ?></h3>
                    <p class="stock <?= $stock > 0 ? 'in' : 'out' ?>">
                        <?= $stock > 0 ? $stock . ' in stock' : 'Out of Stock' ?>
                    </p>
                    <p class="price">Rp <?= $price ?></p>
                </div>
            </div>
        </a>
        <?php 
            endwhile;
        } else {
            // Message when no products are found
            echo '<p style="color: white; text-align: center; width: 100%; grid-column: 1 / -1;">No products found matching your criteria.</p>';
        }
        ?>
    </div>
</section>


<?php include 'news.php'; ?>
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
</script>

</body>
</html>