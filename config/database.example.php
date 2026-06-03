<?php
// ============================================================
// config/database.php — TEMPLATE (Salin file ini ke database.php)
// Sesuaikan dengan konfigurasi server Anda
// ============================================================

define('DB_HOST', 'localhost');       // Host database
define('DB_USER', 'root');            // Username database
define('DB_PASS', '');                // Password database (ganti sesuai server)
define('DB_NAME', 'bup_db');          // Nama database
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function formatRupiah($num) {
    return 'Rp ' . number_format($num, 0, ',', '.');
}

function generateKodePesanan() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < 6; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return 'BUP-' . $randomString;
}

function db() {
    return getDB();
}
