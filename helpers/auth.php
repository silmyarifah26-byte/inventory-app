 <?php
// helpers/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /inventory-app/public/login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['user']['role'] !== $role) {
        http_response_code(403);
        exit('Akses ditolak: Anda tidak memiliki hak akses untuk halaman ini.');
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}