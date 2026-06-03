<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$kode = strtoupper(trim($_GET['kode'] ?? ''));
if (!$kode) {
    jsonResponse(['success' => false, 'message' => 'Masukkan kode pesanan']);
}

$db = getDB();
$stmt = $db->prepare("SELECT p.*, l.kategori, l.nama as nama_layanan, l.kode as kode_layanan, l.harga as harga_satuan FROM pesanan p JOIN layanan l ON p.layanan_id = l.id WHERE p.kode_pesanan = ?");
$stmt->execute([$kode]);
$pesanan = $stmt->fetch();

if (!$pesanan) {
    jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
}

jsonResponse(['success' => true, 'data' => $pesanan]);