<?php
include 'db.php';

// Ambil data news dari database dengan PDO
try {
    $stmt = $pdo->query("SELECT title, content, created_at FROM news ORDER BY created_at DESC");
    $news = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "<p class='text-red-500'>Failed to fetch news: " . htmlspecialchars($e->getMessage()) . "</p>";
    $news = [];
}
?>

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

<section id="news" class="news-section">
    <h2>Latest News</h2>

    <?php if (!empty($news)): ?>
        <?php foreach ($news as $index => $row): ?>
            <?php
            $content = htmlspecialchars($row['content']);
            $shortContent = mb_substr($content, 0, 200); // Potong jadi 200 karakter
            $isLong = mb_strlen($content) > 200;
            ?>
            <div class="news-card">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <div class="date"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                <p id="short-<?= $index ?>">
                    <?= nl2br($shortContent) ?>
                    <?php if ($isLong): ?>
                        ... <a href="javascript:void(0);" onclick="showFull(<?= $index ?>)">Lihat Selengkapnya</a>
                    <?php endif; ?>
                </p>
                <?php if ($isLong): ?>
                    <p id="full-<?= $index ?>" style="display:none;">
                        <?= nl2br($content) ?> <br>
                        <a href="javascript:void(0);" onclick="hideFull(<?= $index ?>)">Sembunyikan</a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No news available.</p>
    <?php endif; ?>
</section>

<script>
function showFull(index) {
    document.getElementById('short-' + index).style.display = 'none';
    document.getElementById('full-' + index).style.display = 'block';
}
function hideFull(index) {
    document.getElementById('short-' + index).style.display = 'block';
    document.getElementById('full-' + index).style.display = 'none';
}
</script>
