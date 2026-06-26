<?php
// ============================================================
// api/customer_profile.php — Update Profil & Password Pelanggan
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_auth.php';

header('Content-Type: application/json');

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Tidak terautentikasi.']);
    exit;
}

$customer = getLoggedInCustomer();
$db       = db();
$action   = $_POST['action'] ?? '';

// ── Update Profil ──────────────────────────────────────────
if ($action === 'update_profile') {
    $nama  = trim($_POST['nama'] ?? '');
    $no_wa = trim($_POST['no_wa'] ?? '');

    if (!$nama || !$no_wa) {
        echo json_encode(['success' => false, 'message' => 'Nama dan nomor WA wajib diisi.']);
        exit;
    }

    $stmt = $db->prepare("UPDATE pelanggan SET nama = ?, no_wa = ? WHERE id = ?");
    $stmt->execute([$nama, $no_wa, $customer['id']]);

    // Update session
    $_SESSION['customer_nama']  = $nama;
    $_SESSION['customer_no_wa'] = $no_wa;

    echo json_encode(['success' => true, 'message' => 'Profil berhasil diperbarui.']);
    exit;
}

// ── Ganti Password ─────────────────────────────────────────
if ($action === 'change_password') {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';

    if (!$oldPass || !$newPass) {
        echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
        exit;
    }

    if (strlen($newPass) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
        exit;
    }

    // Ambil password saat ini
    $stmt = $db->prepare("SELECT password FROM pelanggan WHERE id = ? LIMIT 1");
    $stmt->execute([$customer['id']]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($oldPass, $row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Password lama tidak sesuai.']);
        exit;
    }

    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
    $stmt   = $db->prepare("UPDATE pelanggan SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $customer['id']]);

    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak dikenal.']);
