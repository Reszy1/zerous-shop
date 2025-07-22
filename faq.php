<?php
include 'db.php'; // koneksi database
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FAQ - Zerous Shop</title>
    <link rel="stylesheet" href="style.css"> <!-- Sesuaikan jika CSS di folder berbeda -->
</head>
<body>

<!-- ====== HEADER ====== -->
<header>
    <div class="logo-title">
        <img src="assets/logo.webp" alt="Logo" class="logo">
        <h1>Zerous Shop</h1>
    </div>

    <nav class="nav-grid">
        <a href="index.php" class="nav-btn">Products</a>
        <a href="reviews.php" class="nav-btn">Reviews</a>
        <a href="index.php #news" class="nav-btn">News</a>
        <a href="faq.php" class="nav-btn active">FAQ</a>
        <a href="user-login/dashboard.php" class="nav-btn">My Account</a>
    </nav>
</header>

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
        background-color: #0f172a; /* Warna dasar biru gelap */
        background-image: url('assets/bg-stars.webp'); /* Gambar latar belakang bintang */
        background-repeat: repeat;
        background-attachment: fixed; /* Latar belakang tetap diam saat di-scroll */
        color: #e2e8f0; /* Warna teks default yang terang */
        font-family: 'Inter', sans-serif; /* Menggunakan font yang lebih modern */
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

<main class="faq-container" style="max-width: 800px; margin: 50px auto; padding: 0 20px;">
    <h2 style="color: grey; font-size: 24px; margin-bottom: 20px;">Tentang Kami</h2>

    <div class="faq-item">
        <button class="faq-question">Berdirinya Zerous Shop kapan ?</button>
        <div class="faq-answer">
            <p>Kamu hanya perlu memilih produk Netflix yang tersedia, klik "Buy", lalu lakukan pembayaran menggunakan QRIS. Setelah itu, akun akan langsung dikirimkan ke ticket kamu.</p>
        </div>
    </div>

    <div class="faq-item">
        <button class="faq-question">Apakah Zerous Shop aman dan bergaransi ?</button>
        <div class="faq-answer">
            <p>Yes, kamu akan mendapatkan garansi sesuai durasi yang kamu beli.</p>
        </div>
    </div>

    <div class="faq-item">
        <button class="faq-question">Apakah Zerous Shop mempunyai bukti transaksi ?</button>
        <div class="faq-answer">
            <p>Yes, kamu akan mendapatkan garansi sesuai durasi yang kamu beli.</p>
        </div>
    </div>

    <div class="faq-item">
        <button class="faq-question">Paltform mana aja Zerous Shop melakukan transaksi  ?</button>
        <div class="faq-answer">
            <p>Yes, kamu akan mendapatkan garansi sesuai durasi yang kamu beli.</p>
        </div>
    </div>

    <h3 style="color: grey; font-size: 24px; margin-bottom: 20px;">Bagaimana cara transaksinya</h3>
    <div class="faq-item">
        <button class="faq-question">Cara Pertama #1 </button>
        <div class="faq-answer">
            <p>Kamu bisa buka ke menu Login or Register kemudian lakukan pendaftaran akun kalian pada website untuk mendapatkan keburuntungan dalam melakukan pembelian.</p>
        </div>
    </div>

    <div class="faq-item">
        <button class="faq-question">Apa yang harus saya lakukan jika terjadi masalah ?</button>
        <div class="faq-answer">
            <p>Kamu bisa buka ticket support melalui tombol "Support", lalu jelaskan masalahmu. Admin akan membantu kamu secepatnya.</p>
        </div>
    </div>
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
    document.querySelectorAll('.faq-question').forEach(button => {
        button.addEventListener('click', () => {
            const item = button.parentElement;
            item.classList.toggle('active');
        });
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
