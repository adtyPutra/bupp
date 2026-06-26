<?php
// ============================================================
// includes/customer_auth.php — Autentikasi Pelanggan
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isCustomerLoggedIn(): bool {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

function getLoggedInCustomer(): ?array {
    if (!isCustomerLoggedIn()) return null;
    return [
        'id'       => $_SESSION['customer_id'],
        'nama'     => $_SESSION['customer_nama'],
        'username' => $_SESSION['customer_username'],
        'no_wa'    => $_SESSION['customer_no_wa'],
    ];
}

function requireCustomerLogin(): void {
    if (!isCustomerLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . '/pages/auth/login.php' . ($redirect ? '?redirect=' . $redirect : ''));
        exit;
    }
}

function loginCustomer(string $username, string $password): array {
    $db   = db();
    $stmt = $db->prepare("SELECT * FROM pelanggan WHERE username = ? AND password IS NOT NULL LIMIT 1");
    $stmt->execute([trim($username)]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['customer_id']       = $customer['id'];
        $_SESSION['customer_nama']     = $customer['nama'];
        $_SESSION['customer_username'] = $customer['username'];
        $_SESSION['customer_no_wa']    = $customer['no_wa'];
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Username atau password salah.'];
}

function logoutCustomer(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_nama'],
        $_SESSION['customer_username'],
        $_SESSION['customer_no_wa']
    );
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

function registerCustomer(string $nama, string $no_wa, string $username, string $password): array {
    $db = db();

    // Cek apakah username sudah terdaftar
    $stmt = $db->prepare("SELECT id FROM pelanggan WHERE username = ? LIMIT 1");
    $stmt->execute([trim($username)]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username sudah digunakan. Pilih username lain.'];
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO pelanggan (nama, no_wa, username, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([trim($nama), trim($no_wa), trim($username), $hashed]);

    $newId = $db->lastInsertId();

    return ['success' => true];
}
