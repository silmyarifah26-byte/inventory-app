<?php
// public/products.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login();
$user = current_user();

$errors = [];
$success = '';

// Handling Tambah / Edit Produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $category_id = trim($_POST['category_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $minimum_stock = trim($_POST['minimum_stock'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $id = $_POST['id'] ?? null;

    if (empty($name) || empty($category_id) || empty($sku) || $stock === '' || $price === '') {
        $errors[] = "Semua kolom yang wajib bertanda bintang (*) harus diisi!";
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, sku, stock, minimum_stock, price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $name, $sku, $stock, $minimum_stock ?: 5, $price]);
                $success = "Produk berhasil ditambahkan!";
            } elseif ($action === 'update' && $id) {
                $stmt = $pdo->prepare("UPDATE products SET category_id = ?, name = ?, sku = ?, stock = ?, minimum_stock = ?, price = ? WHERE id = ?");
                $stmt->execute([$category_id, $name, $sku, $stock, $minimum_stock ?: 5, $price, $id]);
                $success = "Produk berhasil diperbarui!";
            }
        } catch (PDOException $e) {
            $errors[] = "Gagal menyimpan produk (Kemungkinan SKU sudah terdaftar): " . $e->getMessage();
        }
    }
}

// Handling Hapus Produk
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: products.php?status=deleted");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Gagal menghapus produk.";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $success = "Produk berhasil dihapus!";
}

// Ambil data untuk form edit jika ada query string ?edit=ID
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Ambil daftar kategori untuk pilihan dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Ambil daftar seluruh produk beserta nama kategorinya menggunakan JOIN
$products = $pdo->query("
    SELECT products.*, categories.name AS category_name 
    FROM products 
    LEFT JOIN categories ON products.category_id = categories.id 
    ORDER BY products.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Produk - Inventory System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .btn-action { text-decoration: none; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; color: white; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-delete { background: #dc3545; }
        .badge-warning { background: #fff3cd; color: #856404; padding: 3px 6px; border-radius: 4px; font-weight: bold; }
        .top-nav { margin-bottom: 20px; }
        .top-nav a { text-decoration: none; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <h2>Inventory System - Produk</h2>
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
        <!-- Form Tambah / Edit Produk -->
        <div class="card">
            <h3><?= $editData ? 'Edit Produk' : 'Tambah Produk Baru' ?></h3>
            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="category_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($editData && $editData['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= escape($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nama Produk *</label>
                    <input type="text" name="name" value="<?= escape($editData['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>SKU (Kode Barang) *</label>
                    <input type="text" name="sku" value="<?= escape($editData['sku'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Stok Awal *</label>
                    <input type="number" name="stock" value="<?= escape($editData['stock'] ?? '') ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Batas Minimum Stok (Peringatan)</label>
                    <input type="number" name="minimum_stock" value="<?= escape($editData['minimum_stock'] ?? '5') ?>" min="0">
                </div>

                <div class="form-group">
                    <label>Harga Satuan (Rp) *</label>
                    <input type="number" name="price" value="<?= escape($editData['price'] ?? '') ?>" min="0" step="100" required>
                </div>

                <button type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah Produk' ?></button>
                <?php if ($editData): ?>
                    <a href="products.php" style="margin-left: 10px; text-decoration: none; font-size: 0.9rem;">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Daftar Produk -->
        <div class="card" style="overflow-x: auto;">
            <h3>Daftar Produk Inventaris</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Harga</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" style="text-align:center;">Belum ada data produk.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $index => $prod): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><code><?= escape($prod['sku']) ?></code></td>
                                <td><b><?= escape($prod['name']) ?></b></td>
                                <td><?= escape($prod['category_name'] ?? 'Tanpa Kategori') ?></td>
                                <td>
                                    <?= escape($prod['stock']) ?>
                                    <?php if ($prod['stock'] <= $prod['minimum_stock']): ?>
                                        <br><span class="badge-warning">Stok Rendah!</span>
                                    <?php endif; ?>
                                </td>
                                <td>Rp <?= number_format($prod['price'], 0, ',', '.') ?></td>
                                <td>
                                    <a href="products.php?edit=<?= $prod['id'] ?>" class="btn-action btn-edit">Edit</a>
                                    <a href="products.php?delete=<?= $prod['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus produk ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>