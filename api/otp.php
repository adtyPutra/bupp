<?php
// ============================================================
// api/otp.php — API OTP untuk Lupa Password via Fonnte
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db     = db();

// Fungsi internal untuk mengirim WhatsApp via Fonnte
function kirimWA(string $nomorTujuan, string $pesan): bool {
    if (!defined('FONNTE_TOKEN') || empty(FONNTE_TOKEN)) return false;
    
    // Format nomor ke 62
    $nomor = ltrim($nomorTujuan, '0');
    if (substr($nomor, 0, 2) !== '62') {
        $nomor = '62' . $nomor;
    }

    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . FONNTE_TOKEN
        ],
        CURLOPT_POSTFIELDS     => [
            'target'  => $nomor,
            'message' => $pesan
        ]
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return false;
    
    $res = json_decode($response, true);
    return isset($res['status']) && $res['status'] === true;
}

// ── 1. Minta OTP ───────────────────────────────────────────
if ($action === 'request_otp') {
    $username = trim($_POST['username'] ?? '');

    if (!$username) {
        echo json_encode(['success' => false, 'message' => 'Username wajib diisi.']);
        exit;
    }

    $stmt = $db->prepare("SELECT id, nama, no_wa FROM pelanggan WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan.']);
        exit;
    }

    if (empty($user['no_wa'])) {
        echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp tidak terdaftar untuk akun ini.']);
        exit;
    }

    // Generate OTP 6 digit
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    
    // Expired dalam 5 menit
    $expired = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Update DB
    $stmtUpdate = $db->prepare("UPDATE pelanggan SET otp_code = ?, otp_expired_at = ? WHERE id = ?");
    $stmtUpdate->execute([$otp, $expired, $user['id']]);

    // Kirim WA
    $pesan = "Halo *{$user['nama']}*,\n\nKode OTP untuk reset password BUP kamu adalah: *{$otp}*\n\nKode ini berlaku selama 5 menit. Mohon tidak membagikan kode ini kepada siapapun.";
    
    $dikirim = kirimWA($user['no_wa'], $pesan);

    if ($dikirim) {
        // Sensor nomor WA untuk ditampilkan di UI
        $waLen = strlen($user['no_wa']);
        $waSensor = substr($user['no_wa'], 0, 4) . str_repeat('*', $waLen - 6) . substr($user['no_wa'], -2);
        
        echo json_encode([
            'success' => true, 
            'message' => 'OTP berhasil dikirim ke WhatsApp.',
            'wa_sensor' => $waSensor
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengirim OTP ke WhatsApp. Coba lagi nanti.']);
    }
    exit;
}

// ── 2. Verifikasi OTP ──────────────────────────────────────
if ($action === 'verify_otp') {
    $username = trim($_POST['username'] ?? '');
    $otp      = trim($_POST['otp'] ?? '');

    if (!$username || !$otp) {
        echo json_encode(['success' => false, 'message' => 'Username dan OTP wajib diisi.']);
        exit;
    }

    $stmt = $db->prepare("SELECT otp_code, otp_expired_at FROM pelanggan WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !$user['otp_code']) {
        echo json_encode(['success' => false, 'message' => 'Sesi OTP tidak valid atau tidak ditemukan.']);
        exit;
    }

    if ($user['otp_code'] !== $otp) {
        echo json_encode(['success' => false, 'message' => 'Kode OTP salah.']);
        exit;
    }

    if (strtotime($user['otp_expired_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'OTP valid.']);
    exit;
}

// ── 3. Reset Password ──────────────────────────────────────
if ($action === 'reset_password') {
    $username = trim($_POST['username'] ?? '');
    $otp      = trim($_POST['otp'] ?? '');
    $newPass  = $_POST['new_password'] ?? '';

    if (!$username || !$otp || !$newPass) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
        exit;
    }

    if (strlen($newPass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
        exit;
    }

    // Verifikasi ulang OTP untuk keamanan sebelum update
    $stmt = $db->prepare("SELECT id, otp_code, otp_expired_at FROM pelanggan WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || $user['otp_code'] !== $otp || strtotime($user['otp_expired_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Sesi OTP tidak valid atau kedaluwarsa.']);
        exit;
    }

    // Update password dan bersihkan OTP
    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
    $stmtUpdate = $db->prepare("UPDATE pelanggan SET password = ?, otp_code = NULL, otp_expired_at = NULL WHERE id = ?");
    $stmtUpdate->execute([$hashed, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password berhasil direset. Silakan login dengan password baru.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
