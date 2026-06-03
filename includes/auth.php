<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/admin/login.php');
        exit;
    }
}

function loginAdmin($username, $password) {
    $db = db();

    $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // TANPA HASH
    if ($user && $password === $user['password']) {
        $_SESSION['admin_id'] = $user['id']; // ✅ FIX DI SINI
        $_SESSION['admin_nama'] = $user['username']; // optional biar tampil nama
        return true;
    }

    return false;
}

function logoutAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();

    // Langsung arahkan ke login.php tanpa pakai konstanta BASE_URL
    header('Location: login.php');
    exit;
}
