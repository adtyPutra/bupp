<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!isLoggedIn()) { header('Location: login.php'); exit; }
$db = db();
$q  = trim($_GET['q'] ?? '');

// Helper: Format tanggal ke bahasa Indonesia
function tglIndo($tgl) {
    $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei',
              'Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt',
              'Nov'=>'Nov','Dec'=>'Des'];
    $ts = strtotime($tgl); $eng = date('M', $ts);
    return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
}

// 1. Query Utama untuk Data Pelanggan (Tanpa alamat)
$sql = "SELECT p.pelanggan_id, c.nama, c.no_wa,
               COUNT(p.id) AS total_order,
               SUM(p.total_harga) AS total_belanja,
               MAX(p.tanggal_pesan) AS last_order
        FROM pesanan p
        JOIN pelanggan c ON p.pelanggan_id = c.id";

$params = [];
if ($q) {
    $sql .= " WHERE c.nama LIKE ? OR c.no_wa LIKE ?";
    $params = ["%$q%", "%$q%"];
}

$sql .= " GROUP BY p.pelanggan_id, c.nama, c.no_wa ORDER BY last_order DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// 2. Query untuk Riwayat Transaksi Asli (Real Data)
$orders_sql = "SELECT pelanggan_id, kode_pesanan, total_harga, status_bayar, metode_bayar, tanggal_pesan, created_at
               FROM pesanan ORDER BY created_at DESC";
$all_orders = $db->query($orders_sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id" class="page-customers">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pelanggan | BUP Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css">

<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-page">
  <?php if(file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>
  
  <div class="admin-content">
    
    <div class="admin-topbar">
        <h1>Daftar Pelanggan</h1>
    </div>

    <div style="width: 100%;">
        <div class="stat-mini-card" style="margin-bottom: 16px;">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="stat-info">
                <span class="label">Total Pelanggan</span>
                <span class="value"><?= count($customers) ?> Orang</span>
            </div>
        </div>
    </div>
    
    <form method="GET" style="width: 100%;">
        <div class="filter-container">
            <input type="text" class="filter-input" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama pelanggan atau nomor WhatsApp...">
            <button type="submit" class="filter-btn">Cari Data</button>
        </div>
    </form>

    <!-- Tabel Utama Bersih (Tanpa Alamat & Ikon, Warna Polos) -->
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
                <th style="text-align: center; width: 60px;">No</th>
                <th>Nama Pelanggan</th>
                <th style="text-align: center;">Nomor WhatsApp</th>
                <th style="text-align: center;">Total Order</th>
                <th style="text-align: center;">Total Belanja</th>
                <th style="text-align: center;">Terakhir Order</th>
                <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($customers as $i => $c): ?>
            <tr>
              <td style="text-align: center; color: #94a3b8; font-weight: 700;"><?= $i + 1 ?></td>
              
              <td style="white-space: nowrap;">
                  <strong style="color:#0f172a; font-size: 0.95rem;"><?= htmlspecialchars($c['nama']) ?></strong>
              </td>
              
              <td style="text-align: center;">
                  <?= htmlspecialchars($c['no_wa']) ?>
              </td>
              
              <!-- Total Order: Tanpa warna, teks tebal biasa -->
              <td style="text-align: center;">
                  <strong style="color: #0f172a; font-size: 0.95rem;"><?= $c['total_order'] ?>x</strong>
              </td>
              
              <td style="text-align: center;">
                  <strong style="color: #0f172a; white-space: nowrap;">Rp <?= number_format($c['total_belanja'], 0, ',', '.') ?></strong>
              </td>

              <td style="text-align: center;">
                  <?= $c['last_order'] ? tglIndo($c['last_order']) : '-' ?>
              </td>

              <td style="text-align: center;">
                  <button type="button" class="btn-action-riwayat" onclick="bukaRiwayat('<?= htmlspecialchars(addslashes($c['nama'])) ?>', <?= $c['pelanggan_id'] ?>)">
                      Lihat Riwayat
                  </button>
              </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($customers)): ?>
            <tr>
                <td colspan="7" style="text-align:center; padding:80px 20px; color:#94a3b8;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 16px; display: block; margin-left: auto; margin-right: auto;"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                    <span style="font-size: 1.1rem; font-weight: 600; color: #64748b;">Belum Ada Data Pelanggan</span>
                </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
  </div>
</div>

<!-- Modal Riwayat Transaksi -->
<div id="modalRiwayat" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="header-title-wrap">
                <!-- Ikon dengan Gradasi Premium -->
                <div class="header-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="header-text">
                    <span class="subtitle">Riwayat Transaksi</span>
                    <h2 id="r-namaPelanggan">Nama Pelanggan</h2>
                </div>
            </div>
            <button class="btn-close-icon" onclick="tutupRiwayat()">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        
        <div class="riwayat-container">
            <!-- Tabel Modal Polos Tanpa Ikon -->
            <table class="riwayat-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kode Pesanan</th>
                        <th>Metode</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="r-tabelBody">
                    <!-- Isi akan di-inject lewat JavaScript berdasarkan Real Data -->
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 24px; text-align: right;">
            <button class="btn-action-riwayat" style="background: #fff; color: #475569; border: 1px solid #cbd5e1; padding: 12px 28px;" onclick="tutupRiwayat()">Tutup</button>
        </div>
    </div>
</div>

<!-- Mengoper data PHP ke JavaScript -->
<script>
// Data riwayat transaksi asli dari database
const allOrders = <?= json_encode($all_orders) ?>;

</script>
<script src="../../assets/js/admin.js"></script>
</body>
</html>

