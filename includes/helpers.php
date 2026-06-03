<?php
// 1. Amankan session_start() agar tidak error jika session sudah berjalan di file lain
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Bungkus fungsi dengan function_exists untuk MENCEGAH error "Cannot redeclare"
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            // Pastikan BASE_URL sudah didefinisikan di config, jika belum fallback ke path relatif
            $base = defined('BASE_URL') ? BASE_URL : '';
            header('Location: ' . $base . '/pages/admin/login.php');
            exit;
        }
    }
}

if (!function_exists('loginAdmin')) {
    function loginAdmin($username, $password) {
        $db = db();

        $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Menggunakan perbandingan teks biasa sesuai request Anda
        if ($user && $password === $user['password']) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_nama'] = $user['nama']; // Mengambil 'nama' dari database, bukan username
            return true;
        }

        return false;
    }
}

if (!function_exists('logoutAdmin')) {
    function logoutAdmin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();

        header('Location: login.php');
        exit;
    }
}

if (!function_exists('uploadBuktiBayar')) {
    function uploadBuktiBayar($fileArray) {
        // Tentukan folder tujuan (pastikan folder uploads/bukti_bayar/ sudah dibuat)
        $targetDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../uploads/bukti_bayar/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = basename($fileArray['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Bikin nama file unik biar gak tertimpa
        $newFileName = time() . '_' . uniqid() . '.' . $fileType;
        $targetFilePath = $targetDir . $newFileName;

        // Cek ekstensi (hanya gambar)
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($fileArray['tmp_name'], $targetFilePath)) {
                return $newFileName; // Sukses
            }
        }
        
        return false; // Gagal
    }
}