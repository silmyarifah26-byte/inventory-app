<?php
// public/dashboard.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

// Pastikan user sudah login
require_login();
$user = current_user();

// Mengambil ringkasan data untuk widget dashboard (Sesuai Slide 29)
// 1. Total Produk
$stmtTotalProducts = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = $stmtTotalProducts->fetchColumn();

// 2. Total Nilai Inventory (SUM(stock * price))
$stmtInventoryValue = $pdo->query("SELECT SUM(stock * price) FROM products");
$inventoryValue = $stmtInventoryValue->fetchColumn() ?: 0;

// 3. Produk Stok Rendah (stock <= minimum_stock)
$stmtLowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= minimum_stock");
$lowStockCount = $stmtLowStock->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Inventory Management System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        header .user-info { font-size: 0.9rem; }
        header a.logout { color: #ff6b6b; text-decoration: none; margin-left: 15px; font-weight: bold; }
        header a.logout:hover { text-decoration: underline; }
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .welcome-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .widgets { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .widget { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #007bff; }
        .widget.warning { border-left-color: #ffc107; }
        .widget h3 { margin: 0 0 10px 0; font-size: 1rem; color: #666; }
        .widget .number { font-size: 1.8rem; font-weight: bold; color: #333; }
        .nav-links { margin-top: 30px; display: flex; gap: 15px; }
        .nav-links a { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 0.9rem; }
        .nav-links a:hover { background: #0056b3; }
    </style>
</head>
<body>

    <header>
        <h2>Inventory System</h2>
        <div class="user-info">
            Halo, <b><?= escape($user['username']) ?></b> (Role: <em><?= escape($user['role']) ?></em>)
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-card">
            <h3>Selamat Datang di Dashboard</h3>
            <p>Sistem Manajemen Inventaris siap digunakan untuk memantau stok, kategori, dan transaksi barang masuk/keluar.</p>
        </div>

        <div class="widgets">
            <div class="widget">
                <h3>Total Produk</h3>
                <div class="number"><?= number_format($totalProducts) ?></div>
            </div>
            <div class="widget">
                <h3>Total Nilai Inventory</h3>
                <div class="number">Rp <?= number_format($inventoryValue, 0, ',', '.') ?></div>
            </div>
            <div class="widget warning">
                <h3>Produk Stok Rendah</h3>
                <div class="number"><?= number_format($lowStockCount) ?></div>
            </div>
        </div>

        <div class="nav-links">
            <a href="login.php">Kembali ke Login</a>
            <!-- Menu lain akan kita buat bertahap -->
        </div>
    </div>

</body>
</html>