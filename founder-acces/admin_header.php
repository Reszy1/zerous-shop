<?php include 'auth.php'; // Keamanan tetap di sini ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'Admin Panel' ?> - Zerous Shop</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.webp" alt="Logo" class="logo">
            <h1>Zerous Shop</h1>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link <?= ($page_title === 'Dashboard') ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>
            <a href="products.php" class="nav-link <?= ($page_title === 'Manage Products') ? 'active' : '' ?>">
                <i class="fa-solid fa-box-archive"></i> Products
            </a>
            
            <a href="orders.php" class="nav-link <?= ($page_title === 'Manage Invoices') ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Orders
            </a>
            <a href="customers.php" class="nav-link <?= ($page_title == 'Customers') ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Customers
            </a>
            <a href="reviews.php" class="nav-link <?= ($page_title == 'Feedbacks') ? 'active' : '' ?>">
                <i class="fa-solid fa-star"></i> Feedbacks
            </a>
            
        </nav>
        <div class="sidebar-footer">
            <a href="../user-login/logout.php" class="nav-link logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </aside>

    <main class="admin-main-content">