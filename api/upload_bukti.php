<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$kode_pesanan = strtoupper(trim($_POST['kode_pesanan'] ?? ''));
if (!$kode_pesanan) {
    jsonResponse(['success' => false, 'message' => 'Kode pesanan diperlukan'], 400);
}

if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'File bukti pembayaran tidak ditemukan'], 400);
}

$upload = uploadBuktiBayar($_FILES['bukti_bayar']);
if (!$upload['success']) {
    jsonResponse(['success' => false, 'message' => $upload['message']], 400);
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM pesanan WHERE kode_pesanan = ?");
$stmt->execute([$kode_pesanan]);
$pesanan = $stmt->fetch();
if (!$pesanan) {
    jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan'], 404);
}

// Delete old file if exists
if ($pesanan['bukti_bayar'] && file_exists(UPLOAD_DIR . $pesanan['bukti_bayar'])) {
    unlink(UPLOAD_DIR . $pesanan['bukti_bayar']);
}

$stmt2 = $db->prepare("UPDATE pesanan SET bukti_bayar = ?, status_bayar = 'pending', updated_at = NOW() WHERE kode_pesanan = ?");
$stmt2->execute([$upload['filename'], $kode_pesanan]);

jsonResponse([
    'success' => true,
    'message' => 'Bukti pembayaran berhasil diupload',
    'file_url' => UPLOAD_URL . $upload['filename']
]);