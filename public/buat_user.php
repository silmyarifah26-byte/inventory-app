<?php
// public/buat_user.php
require_once __DIR__ . '/../config/database.php';

$password_polos = 'password123';
$hash_baru = password_hash($password_polos, PASSWORD_DEFAULT);

try {
    // Hapus data lama di tabel users dulu (matikan sementara FK jika perlu, atau langsung kosongkan)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $pdo->exec("TRUNCATE TABLE users;");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Masukkan ulang admin
    $stmt1 = $pdo->prepare("INSERT INTO users (id, username, password_hash, role) VALUES (1, 'admin_pusat', ?, 'admin')");
    $stmt1->execute([$hash_baru]);

    // Masukkan ulang staff
    $stmt2 = $pdo->prepare("INSERT INTO users (id, username, password_hash, role) VALUES (2, 'staff_gudang', ?, 'staff')");
    $stmt2->execute([$hash_baru]);

    echo "<h3 style='color: green;'>Berhasil! Akun telah diatur ulang dengan password baru.</h3>";
    echo "<p>Silakan login dengan password: <b>password123</b></p>";
    echo "<br><a href='login.php' style='padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Menuju Halaman Login</a>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>Terjadi Kesalahan Database:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}