<?php
// Atur judul halaman sebelum memanggil header
$page_title = "Dashboard";
include 'admin_header.php';

// === 1. MENGAMBIL DATA UNTUK STATS CARD (HARI INI) ===
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');

// Revenue (hanya dari pesanan yang sudah 'paid')
$stmt_revenue = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid' AND created_at BETWEEN ? AND ?");
$stmt_revenue->execute([$today_start, $today_end]);
$revenue_today = $stmt_revenue->fetchColumn() ?: 0;

// Pesanan Baru
$stmt_orders = $pdo->prepare("SELECT COUNT(id) FROM orders WHERE created_at BETWEEN ? AND ?");
$stmt_orders->execute([$today_start, $today_end]);
$new_orders_today = $stmt_orders->fetchColumn();

// Pelanggan Baru (menggunakan tabel 'users' Anda)
$stmt_customers = $pdo->prepare("SELECT COUNT(id) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?");
$stmt_customers->execute([$today_start, $today_end]);
$new_customers_today = $stmt_customers->fetchColumn();


// === 2. MENGAMBIL DATA UNTUK TABEL & PANEL SAMPING ===

// 5 Pesanan Selesai Terbaru
$latest_orders = $pdo->query("
    SELECT order_number, total_amount, payment_method, customer_email, created_at 
    FROM orders 
    WHERE order_status = 'delivered' 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 3 Pengumuman Terbaru
$announcements = $pdo->query("
    SELECT title, created_at 
    FROM news 
    ORDER BY created_at DESC 
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);


// === 3. PERSIAPAN DATA UNTUK GRAFIK (24 JAM TERAKHIR) ===
$chart_data_query = $pdo->query("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(id) as order_count,
        SUM(total_amount) as revenue_sum
    FROM orders
    WHERE created_at >= NOW() - INTERVAL 1 DAY
    GROUP BY HOUR(created_at)
    ORDER BY hour ASC
");
$chart_raw_data = $chart_data_query->fetchAll(PDO::FETCH_ASSOC);

$chart_labels = [];
$chart_order_data = [];
$chart_revenue_data = [];

for ($i = 0; $i < 24; $i++) {
    $chart_labels[] = sprintf('%02d:00', $i);
    $chart_order_data[$i] = 0;
    $chart_revenue_data[$i] = 0;
}

foreach ($chart_raw_data as $data) {
    $hour = (int)$data['hour'];
    $chart_order_data[$hour] = (int)$data['order_count'];
    $chart_revenue_data[$hour] = (float)$data['revenue_sum'];
}
?>

<div class="content-header">
    <div>
        <h1>Dashboard</h1>
        <p>Discover the latest updates and insights regarding your store today.</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
       
        <div class="stat-info">
            <p>Revenue (Today)</p>
            <span>Rp <?= number_format($revenue_today, 0, ',', '.') ?></span>
        </div>
    </div>
    <div class="stat-card">
    
        <div class="stat-info">
            <p>New Orders (Today)</p>
            <span><?= $new_orders_today ?></span>
        </div>
    </div>
    <div class="stat-card">
       
        <div class="stat-info">
            <p>New Customers (Today)</p>
            <span><?= $new_customers_today ?></span>
        </div>
    </div>
</div>

<div class="dashboard-main-grid">
    <div class="card chart-container">
        <h3>Revenue & Orders (Last 24h)</h3>
        <div style="height: 350px;">
            <canvas id="revenueOrdersChart"></canvas>
        </div>
    </div>

    <div class="side-panels">
        <div class="card">
            <h3>Announcements</h3>
            <ul class="announcement-list">
                <?php foreach($announcements as $item): ?>
                    <li>
                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                        <small><?= date('d M Y', strtotime($item['created_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('revenueOrdersChart').getContext('2d');
    
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const orderData = <?= json_encode(array_values($chart_order_data)) ?>;
    const revenueData = <?= json_encode(array_values($chart_revenue_data)) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'yRevenue',
            }, {
                label: 'Orders',
                data: orderData,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'yOrders',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                yRevenue: {
                    type: 'linear', position: 'left',
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { 
                        color: '#94a3b8',
                        callback: function(value) { return 'Rp' + (value/1000) + 'k'; }
                    }
                },
                yOrders: {
                    type: 'linear', position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#94a3b8', stepSize: 1 }
                }
            },
            plugins: {
                legend: { labels: { color: '#cbd5e1' } },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#fff',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        }
    });
});
</script>

<?php include 'admin_footer.php'; ?>