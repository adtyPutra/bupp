<?php
// ============================================================
// api/customer_auth.php — API Autentikasi Pelanggan
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_auth.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Login ──────────────────────────────────────────────────
if ($action === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Username dan password wajib diisi.']);
        exit;
    }

    echo json_encode(loginCustomer($username, $password));
    exit;
}

// ── Register ───────────────────────────────────────────────
if ($action === 'register') {
    $nama     = trim($_POST['nama'] ?? '');
    $no_wa    = trim($_POST['no_wa'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$nama || !$no_wa || !$username || !$password) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
        exit;
    }

    if (strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Username minimal 3 karakter.']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'Username hanya boleh huruf, angka, dan underscore.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
        exit;
    }

    if ($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok.']);
        exit;
    }

    echo json_encode(registerCustomer($nama, $no_wa, $username, $password));
    exit;
}

// ── Logout ─────────────────────────────────────────────────
if ($action === 'logout') {
    logoutCustomer();
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
