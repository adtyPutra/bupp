<?php
// ============================================================
// api/forgot_password.php — API Lupa Password (OTP Fonnte)
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$db = db();

if ($action === 'request_otp') {
    $no_wa = trim($_POST['no_wa'] ?? '');
    if (!$no_wa) {
        echo json_encode(['success' => false, 'message' => 'Nomor WA wajib diisi.']);
        exit;
    }

    // Clean number format
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);

    // Check if WA exists
    $stmt = $db->prepare("SELECT id, nama FROM pelanggan WHERE no_wa = ? LIMIT 1");
    $stmt->execute([$no_wa]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Nomor WA tidak terdaftar.']);
        exit;
    }

    // Generate OTP
    $otp = sprintf("%06d", mt_rand(100000, 999999));
    $expired = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $db->prepare("UPDATE pelanggan SET otp_code = ?, otp_expired_at = ? WHERE id = ?");
    $stmt->execute([$otp, $expired, $user['id']]);

    // Send OTP via Fonnte
    $msg = "Halo {$user['nama']},\n\n";
    $msg .= "Berikut adalah kode OTP untuk mereset password akun BUP Laundry Anda:\n\n";
    $msg .= "*{$otp}*\n\n";
    $msg .= "Kode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapapun.";

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array(
        'target' => $no_wa,
        'message' => $msg,
        'countryCode' => '62',
      ),
      CURLOPT_HTTPHEADER => array(
        'Authorization: ' . FONNTE_TOKEN
      ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengirim OTP.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'OTP berhasil dikirim ke WhatsApp.']);
    }
    exit;
}

if ($action === 'verify_otp') {
    $no_wa = trim($_POST['no_wa'] ?? '');
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
    $otp = trim($_POST['otp'] ?? '');

    $stmt = $db->prepare("SELECT id, otp_expired_at FROM pelanggan WHERE no_wa = ? AND otp_code = ? LIMIT 1");
    $stmt->execute([$no_wa, $otp]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'OTP salah atau nomor WA tidak valid.']);
        exit;
    }

    if (strtotime($user['otp_expired_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Kode OTP sudah kadaluarsa. Silakan request ulang.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'OTP valid.']);
    exit;
}

if ($action === 'reset_password') {
    $no_wa = trim($_POST['no_wa'] ?? '');
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
    $otp = trim($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']);
        exit;
    }

    // Verify OTP again just to be safe
    $stmt = $db->prepare("SELECT id, otp_expired_at FROM pelanggan WHERE no_wa = ? AND otp_code = ? LIMIT 1");
    $stmt->execute([$no_wa, $otp]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Ulangi proses.']);
        exit;
    }

    if (strtotime($user['otp_expired_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Kode OTP sudah kadaluarsa. Silakan request ulang.']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE pelanggan SET password = ?, otp_code = NULL, otp_expired_at = NULL WHERE id = ?");
    $stmt->execute([$hashed, $user['id']]);

    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah. Silakan login.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
