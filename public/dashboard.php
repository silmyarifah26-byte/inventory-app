<?php
// public/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login();
$user = current_user();

// Hitung statistik untuk widget dashboard
try {
    // Total Kategori
    $stmtCat = $pdo->query("SELECT COUNT(*) FROM categories");
    $totalCategories = $stmtCat->fetchColumn();

    // Total Produk & Total Nilai Aset
    $stmtProd = $pdo->query("SELECT COUNT(*), SUM(stock * price), SUM(stock) FROM products");
    $prodData = $stmtProd->fetch(PDO::FETCH_NUM);
    $totalProducts = $prodData[0] ?? 0;
    $totalAssetValue = $prodData[1] ?? 0;
    $totalStockQty = $prodData[2] ?? 0;

    // Produk dengan stok menipis (<= minimum_stock)
    $stmtLow = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= minimum_stock");
    $lowStockCount = $stmtLow->fetchColumn();
} catch (PDOException $e) {
    $totalCategories = 0;
    $totalProducts = 0;
    $totalAssetValue = 0;
    $totalStockQty = 0;
    $lowStockCount = 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Inventory System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .welcome-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 25px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #007bff; }
        .stat-card.green { border-left-color: #28a745; }
        .stat-card.orange { border-left-color: #ffc107; }
        .stat-card.red { border-left-color: #dc3545; }
        .stat-title { font-size: 0.9rem; color: #6c757d; font-weight: bold; text-transform: uppercase; }
        .stat-value { font-size: 1.8rem; font-weight: bold; margin-top: 5px; color: #343a40; }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .btn { padding: 10px 20px; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
        .btn-green { background: #28a745; }
        .btn-green:hover { background: #218838; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .btn-red { background: #dc3545; }
        .btn-red:hover { background: #c82333; }
    </style>
</head>
<body>

    <header>
        <h2>Inventory Management System</h2>
        <div>Halo, <b><?= escape($user['username']) ?></b> (<i><?= escape($user['role']) ?></i>)</div>
    </header>

    <div class="container">
        
        <div class="welcome-card">
            <h2>Selamat Datang di Panel Utama, Baginda ratu!</h2>
            <p>Sistem inventaris siap digunakan untuk mengelola kategori barang, stok produk, serta memantau persediaan gudang secara real-time.</p>
            
            <div class="nav-links">
                <a href="categories.php" class="btn btn-green">Kelola Kategori</a>
                <a href="products.php" class="btn btn-info">Kelola Produk</a>
                <a href="logout.php" class="btn btn-red" onclick="return confirm('Keluar dari sistem?')">Logout</a>
                <a href="stock_movement.php" class="btn" style="background: #ffc107; color: #000;">Transaksi Stok</a></div>
            </div>

        <!-- Widget Statistik Ringkasan -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Kategori</div>
                <div class="stat-value"><?= number_format($totalCategories) ?></div>
            </div>

            <div class="stat-card green">
                <div class="stat-title">Total Jenis Produk</div>
                <div class="stat-value"><?= number_format($totalProducts) ?></div>
            </div>

            <div class="stat-card orange">
                <div class="stat-title">Total Item Barang</div>
                <div class="stat-value"><?= number_format($totalStockQty) ?> unit</div>
            </div>

            <div class="stat-card red">
                <div class="stat-title">Stok Menipis</div>
                <div class="stat-value"><?= number_format($lowStockCount) ?> item</div>
            </div>
        </div>

    </div>

</body>
</html>