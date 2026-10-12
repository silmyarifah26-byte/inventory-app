 <?php
// public/login.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/escape.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        // Menggunakan prepared statement untuk keamanan dari SQL Injection
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        // Verifikasi password hash
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // Mencegah session fixation
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'username' => $user['username'],
                'role'  => $user['role']
            ];
            
            // Redirect ke dashboard setelah sukses login
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Inventory Management System</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 320px; }
        .login-card h2 { margin-bottom: 20px; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #666; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 10px; background: #007bff; border: none; color: white; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .error { color: #dc3545; font-size: 0.85rem; margin-bottom: 15px; text-align: center; }
        .hint { font-size: 0.75rem; color: #888; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login Inventory</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?= escape($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Masuk</button>
        </form>
        <div class="hint">
            <strong>Akun Uji Coba:</strong><br>
            - Admin: <code>admin_pusat</code> / <code>password123</code><br>
            - Staff: <code>staff_gudang</code> / <code>password123</code>
        </div>
    </div>
</body>
</html>