<?php
// public/categories.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

require_login();
$user = current_user();

$errors = [];
$success = '';

// Proses Tambah / Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id = $_POST['id'] ?? null;

    if (empty($name)) {
        $errors[] = "Nama kategori wajib diisi!";
    } else {
        try {
            if ($action === 'create') {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = "Kategori berhasil ditambahkan!";
            } elseif ($action === 'update' && $id) {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$name, $description, $id]);
                $success = "Kategori berhasil diperbarui!";
            }
        } catch (PDOException $e) {
            $errors[] = "Gagal menyimpan kategori (kemungkinan nama sudah terdaftar): " . $e->getMessage();
        }
    }
}

// Proses Hapus Kategori
if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: categories.php?status=deleted");
        exit;
    } catch (PDOException $e) {
        $errors[] = "Gagal menghapus kategori (mungkin masih digunakan oleh produk).";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $success = "Kategori berhasil dihapus!";
}

// Ambil data untuk form edit jika ada query string ?edit=ID
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}

// Ambil daftar seluruh kategori
$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kategori - Inventory System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        header { background: #343a40; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 20px; display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 4px; font-weight: bold; font-size: 0.9rem; }
        input[type="text"], textarea { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 15px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .alert { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
        .btn-action { text-decoration: none; padding: 4px 8px; border-radius: 3px; font-size: 0.8rem; color: white; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-delete { background: #dc3545; }
        .top-nav { margin-bottom: 20px; }
        .top-nav a { text-decoration: none; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>

    <header>
        <h2>Inventory System - Kategori</h2>
        <div>Halo, <b><?= escape($user['username']) ?></b> | <a href="dashboard.php" style="color:#fff;">Dashboard</a></div>
    </header>

    <div style="max-width: 1100px; margin: 20px auto 0; padding: 0 20px;">
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
        <!-- Form Tambah / Edit Kategori -->
        <div class="card">
            <h3><?= $editData ? 'Edit Kategori' : 'Tambah Kategori Baru' ?></h3>
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Nama Kategori *</label>
                    <input type="text" name="name" value="<?= escape($editData['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" rows="3"><?= escape($editData['description'] ?? '') ?></textarea>
                </div>

                <button type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
                <?php if ($editData): ?>
                    <a href="categories.php" style="margin-left: 10px; text-decoration: none; font-size: 0.9rem;">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel Daftar Kategori -->
        <div class="card" style="overflow-x: auto;">
            <h3>Daftar Kategori</h3>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="4" style="text-align:center;">Belum ada data kategori.</td></tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><b><?= escape($cat['name']) ?></b></td>
                                <td><?= escape($cat['description'] ?? '-') ?></td>
                                <td>
                                    <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn-action btn-edit">Edit</a>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
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