<?php
// public/stock_movement.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login();
$user = current_user();

$errors = [];
$success = '';

// Proses Transaksi Barang Masuk / Keluar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? '';
    $type = $_POST['type'] ?? ''; // 'IN' atau 'OUT'
    $quantity = intval($_POST['quantity'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');

    if (empty($product_id) || empty($type) || $quantity <= 0) {
        $errors[] = "Pilih produk, jenis transaksi, dan masukkan jumlah kuantitas yang valid!";
    } else {
        try {
            // Mulai Database Transaction untuk menjaga konsistensi stok dan riwayat
            $pdo->beginTransaction();

            // 1. Cek stok produk saat ini
            $stmtCheck = $pdo->prepare("SELECT stock, name FROM products WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$product_id]);
            $product = $stmtCheck->fetch();

            if (!$product) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $current_stock = intval($product['stock']);

            // Jika transaksi Keluar (OUT), pastikan stok mencukupi
            if ($type === 'OUT' && $current_stock < $quantity) {
                throw new Exception("Stok tidak mencukupi! Stok saat ini untuk '{$product['name']}' hanya tersisa {$current_stock} unit.");
            }

            // 2. Hitung stok baru
            $new_stock = ($type === 'IN') ? ($current_stock + $quantity) : ($current_stock - $quantity);

            // 3. Update stok di tabel products
            $stmtUpdate = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmtUpdate->execute([$new_stock, $product_id]);

            // 4. Catat ke tabel stock_movements
            $stmtInsert = $pdo->prepare("INSERT INTO stock_movements (product_id, type, quantity, reference) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$product_id, $type, $quantity, $reference]);

            // Commit transaksi jika semua berhasil
            $pdo->commit();
            $success = "Transaksi stok berhasil dicatat! Stok produk '{$product['name']}' kini menjadi {$new_stock} unit.";
        } catch (Exception $e) {
            // Rollback jika terjadi error
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Gagal memproses transaksi: " . $e->getMessage();
        }
    }
}

// Ambil daftar produk untuk dropdown pilihan
$products = $pdo->query("SELECT id, name, sku, stock FROM products ORDER BY name ASC")->fetchAll();

// Ambil riwayat transaksi stok terbaru (bergabung dengan tabel products)
$movements = $pdo->query("
    SELECT stock_movements.*, products.name AS product_name, products.sku 
    FROM stock_movements 
    JOIN products ON stock_movements.product_id = products.id 
    ORDER BY stock_movements.id DESC 
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Transaksi Stok - Inventory System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 15px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; }
        button:hover { background: #138496; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 0.75rem; font-weight: bold; color: white; }
        .badge-in { background: #28a745; }
        .badge-out { background: #dc3545; }
        .top-nav { margin-bottom: 20px; }
    </style>
</head>
<body>

    <header>
        <h2>Inventory System - Transaksi Stok</h2>
        <div>Halo, <b><?= escape($user['username']) ?></b> | <a href="dashboard.php" style="color:#fff;">Dashboard</a></div>
    </header>

    <div style="max-width: 1200px; margin: 20px auto 0; padding: 0 20px;">
        <div class="top-nav"><a href="dashboard.php">&larr; Kembali ke Dashboard</a></div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p style="margin:2px 0;"><?= escape($e) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= escape($success) ?></div>
        <?php endif; ?>
    </div>

    <div class="container">
        <!-- Form Input Transaksi Stok Masuk/Keluar -->
        <div class="card">
            <h3>Catat Barang Masuk / Keluar</h3>
            <form method="POST" action="stock_movement.php">
                <div class="form-group">
                    <label>Pilih Produk *</label>
                    <select name="product_id" required>
                        <option value="">-- Pilih Produk --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>">
                                <?= escape($p['name']) ?> (Stok: <?= $p['stock'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Jenis Transaksi *</label>
                    <select name="type" required>
                        <option value="IN">Barang Masuk (IN)</option>
                        <option value="OUT">Barang Keluar (OUT)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Kuantitas / Jumlah *</label>
                    <input type="number" name="quantity" min="1" value="1" required>
                </div>

                <div class="form-group">
                    <label>Keterangan / No. Referensi (Opsional)</label>
                    <input type="text" name="reference" placeholder="Contoh: Restock dari supplier / Penjualan kasir">
                </div>

                <button type="submit">Simpan Transaksi</button>
            </form>
        </div>

        <!-- Tabel Riwayat Transaksi -->
        <div class="card" style="overflow-x: auto;">
            <h3>Riwayat Transaksi Stok Terbaru</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Waktu</th>
                        <th>Produk</th>
                        <th>Tipe</th>
                        <th>Jumlah</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="6" style="text-align:center;">Belum ada riwayat transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($movements as $index => $m): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><small><?= escape($m['created_at']) ?></small></td>
                                <td><b><?= escape($m['product_name']) ?></b><br><small style="color:#666;"><?= escape($m['sku']) ?></small></td>
                                <td>
                                    <?php if ($m['type'] === 'IN'): ?>
                                        <span class="badge badge-in">MASUK (+<?= $m['quantity'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge badge-out">KELUAR (-<?= $m['quantity'] ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td><b><?= escape($m['quantity']) ?></b></td>
                                <td><?= escape($m['reference'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>