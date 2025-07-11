<?php
// Deteksi apakah kita di subfolder atau root
$isInSubfolder = isset($_SESSION['current_page']) && $_SESSION['current_page'] === 'product';
$pathPrefix = $isInSubfolder ? '../' : '';

// Hitung jumlah total item di keranjang
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Ambil produk dari database jika diperlukan (hanya jika belum ada)
if (!isset($products)) {
    // Include db.php dengan path yang benar
    if (!isset($pdo)) {
        if ($isInSubfolder) {
            require_once '../db.php';
        } else {
            require_once 'db.php';
        }
    }
    
    $stmt = $pdo->query("SELECT * FROM products");
    $products = $stmt->fetchAll();
}
?>


<section class="search-bar">
<form method="GET" action="<?= $pathPrefix ?>index.php" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <input type="text" id="searchInput" name="search" placeholder="Search for products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

    <button type="submit" class="cart-btn" style="background-color: #1e90ff;">Search</button>

    <select name="category" class="currency-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <option value="digital" <?= (isset($_GET['category']) && $_GET['category'] === 'digital') ? 'selected' : '' ?>>Digital</option>
        <option value="physical" <?= (isset($_GET['category']) && $_GET['category'] === 'physical') ? 'selected' : '' ?>>Fisik</option>
    </select>

    <select class="currency-select" id="currencySelect">
        <option value="idr">IDR</option>
    </select>

    <button type="button" class="cart-btn" id="cartBtn">
        🛒 Cart <span class="cart-count">(<?= $cartCount ?>)</span>
    </button>
</form>

<?php if (isset($_GET['category']) && $_GET['category']): ?>
    <div class="product-category-label">
        <span class="badge <?= $_GET['category'] === 'digital' ? 'badge-digital' : 'badge-physical' ?>">
            <?= ucfirst(htmlspecialchars($_GET['category'])) ?>
        </span>
    </div>
<?php endif; ?>
</section>


<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button onclick="this.parentElement.style.display='none'">&times;</button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isInSubfolder = <?= json_encode($isInSubfolder) ?>;
    
    // NOTE: The client-side search functionality has been removed.
    // The filtering is now handled by the server when the form is submitted.

    // Currency logic
    const currencySelect = document.getElementById('currencySelect');
    const savedCurrency = localStorage.getItem('preferredCurrency');
    if (savedCurrency) {
        currencySelect.value = savedCurrency;
    }
    
    currencySelect.addEventListener('change', function () {
        localStorage.setItem('preferredCurrency', this.value);
        updatePricesDisplay(this.value);
    });

    // Cart button dengan path yang dinamis
    document.getElementById('cartBtn').addEventListener('click', function () {
        const cartPath = isInSubfolder ? '../cart.php' : 'cart.php';
        window.location.href = cartPath;
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        });
    }, 5000);
});

// Currency conversion function (placeholder)
function updatePricesDisplay(currency) {
    const priceElements = document.querySelectorAll('.price, .current-price');
    // Add your currency conversion logic here
    console.log('Converting prices to', currency);
}
</script>

<style>
/* Search bar wrapper */
.search-bar {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 20px;
    flex-wrap: wrap;
}

/* Input pencarian */
.search-bar input[type="text"] {
    padding: 10px;
    border-radius: 8px;
    border: none;
    width: 250px;
    font-size: 1rem;
    background-color: #f5f5f5;
    color: #333;
}

.search-bar input[type="text"]:focus {
    outline: 2px solid #4e5dff;
    background-color: white;
}

/* Dropdown mata uang */
.currency-select {
    padding: 10px;
    border-radius: 8px;
    border: none;
    background-color: #1c1c1e;
    color: white;
    font-weight: 500;
    cursor: pointer;
}

/* Tombol Cart */
.cart-btn {
    padding: 10px 20px;
    background-color: #4e5dff;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
    transition: background-color 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Hover effect */
.cart-btn:hover {
    background-color: #3c4bff;
}

/* Jumlah item keranjang */
.cart-count {
    font-weight: normal;
    font-size: 0.9rem;
    color: #e0e0e0;
    background-color: rgba(255, 255, 255, 0.2);
    padding: 2px 6px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}

/* Alert Messages */
.alert {
    max-width: 800px;
    margin: 10px auto;
    padding: 12px 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 500;
    transition: opacity 0.3s ease;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert button {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: inherit;
    padding: 0;
    margin-left: 10px;
}

.alert button:hover {
    opacity: 0.7;
}


.product-category-label {
    white-space: nowrap;
    align-self: center;
    font-size: 0.9rem;
}

.badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    margin-left: 5px;
    font-size: 0.85rem;
    white-space: nowrap;
    vertical-align: middle;
    display: inline-block;
    padding: 5px 10px;
    border-radius: 12px;
    font-weight: bold;
    margin-left: 5px;
}

.badge-digital {
    background-color: #4ade80;
    color: #065f46;
    border: 1px solid #16a34a;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    background-color: #4ade80;
    color: #065f46;
}

.badge-physical {
    background-color: #60a5fa;
    color: #1e3a8a;
    border: 1px solid #2563eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    background-color: #60a5fa;
    color: #1e3a8a;
}


/* Responsive */
@media (max-width: 600px) {
    .search-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .search-bar input[type="text"] {
        width: 100%;
        max-width: 300px;
    }
}
</style>