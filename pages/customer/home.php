<?php
// ============================================================
// pages/customer/home.php — Customer Dashboard (Web-App Style)
// ============================================================
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/customer_auth.php';

requireCustomerLogin();

$customer = getLoggedInCustomer();
$namaInisial = strtoupper(substr($customer['nama'], 0, 2));
$db = db();

// Get active orders (not selesai, not batal)
$stmt = $db->prepare("
    SELECT id, kode_pesanan, status_pesanan, tanggal_pesan, total_harga, created_at, status_bayar 
    FROM pesanan 
    WHERE pelanggan_id = ? 
      AND status_pesanan NOT IN ('selesai', 'batal')
    ORDER BY created_at DESC 
");
$stmt->execute([$customer['id']]);
$activeOrders = $stmt->fetchAll();

$statusLabels = [
    'diterima'      => ['label' => 'Diterima',      'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-clipboard-check'],
    'dicuci'        => ['label' => 'Sedang Dicuci', 'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-soap'],
    'dikeringkan'   => ['label' => 'Dikeringkan',   'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-wind'],
    'finishing'     => ['label' => 'Finishing',     'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-tshirt'],
    'siap_diambil'  => ['label' => 'Siap Diambil',  'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-box'],
    'diantar_kurir' => ['label' => 'Sedang Diantar','color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-truck'],
    'selesai'       => ['label' => 'Selesai',       'color' => '#10b981', 'bg' => '#d1fae5', 'icon' => 'fa-check-circle'],
    'batal'         => ['label' => 'Dibatalkan',    'color' => '#ef4444', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
];

// Count orders statistics
$stmtCounts = $db->prepare("
    SELECT 
        SUM(CASE WHEN status_pesanan NOT IN ('selesai', 'batal') THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN status_pesanan = 'selesai' THEN 1 ELSE 0 END) as selesai,
        COUNT(id) as total
    FROM pesanan 
    WHERE pelanggan_id = ?
");
$stmtCounts->execute([$customer['id']]);
$counts = $stmtCounts->fetch();
$totalAktif = $counts['aktif'] ?? 0;
$totalSelesai = $counts['selesai'] ?? 0;
$totalPesanan = $counts['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beranda | BUP Laundry</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="../../assets/css/customer.css?v=<?= time() ?>">
<style>
body, .customer-page {
    background: #fdfbf7 !important;
    color: #1e293b;
}
.dashboard-container {
    max-width: 800px;
    margin: 100px auto 40px;
    padding: 0 20px;
    padding-bottom: 80px;
}
/* Welcome Card */
.welcome-card {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    border-radius: 24px;
    padding: 32px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 20px 40px -15px rgba(37,99,235,0.4);
    margin-bottom: 32px;
    position: relative;
    overflow: hidden;
}
.welcome-card::after {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    filter: blur(20px);
}
.welcome-text h1 {
    font-size: clamp(1.4rem, 4vw, 1.8rem);
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: -0.5px;
}
.welcome-text p {
    font-size: clamp(0.9rem, 2.5vw, 1rem);
    color: #e0e7ff;
    font-weight: 500;
}
.welcome-stats-container {
    display: flex;
    gap: 12px;
}
.welcome-stats {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.15);
    padding: 10px 16px;
    border-radius: 16px;
    text-align: center;
    min-width: 70px;
}
.welcome-stats .stat-num {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
}
.welcome-stats .stat-label {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #e0e7ff;
}

/* Section Title */
.section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Active Order List Group */
.active-orders-wrapper {
    background: white;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    margin-bottom: 32px;
    overflow: hidden;
}
.active-order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 24px;
    text-decoration: none;
    color: inherit;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s ease;
}
.active-order-item:last-child {
    border-bottom: none;
}
.active-order-item:hover {
    background: #f8fafc;
}
.ao-left { display: flex; align-items: center; gap: 16px; }
.ao-icon {
    width: 56px; height: 56px;
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}
.ao-details h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}
.ao-details p {
    font-size: 0.85rem;
    color: #64748b;
    font-weight: 500;
}
.ao-right { text-align: right; }
.ao-right .btn-track {
    background: #f1f5f9;
    color: #0f172a;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.active-order-card:hover .btn-track {
    background: #3b82f6;
    color: white;
}

/* Empty State */
.empty-order {
    background: white;
    border-radius: 20px;
    padding: 32px;
    text-align: center;
    border: 2px dashed #cbd5e1;
    margin-bottom: 32px;
}
.empty-order i {
    font-size: 2.5rem;
    color: #94a3b8;
    margin-bottom: 12px;
}
.empty-order p {
    color: #64748b;
    font-weight: 500;
    font-size: 0.95rem;
}

/* Grid Actions */
.action-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}
.action-card {
    box-sizing: border-box;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 24px;
    padding: 32px 24px;
    text-decoration: none;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: all 0.2s ease;
}
.action-card:hover {
    background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
    border-color: #cbd5e1;
}
.action-icon {
    width: 64px; height: 64px;
    border-radius: 20px;
    background: #3b82f6;
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 20px;
    flex-shrink: 0;
}
.action-title {
    font-weight: 800;
    font-size: 1.15rem;
    margin-bottom: 8px;
}
.action-desc {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.5;
}

/* Primary Action Card */
.action-card.primary {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
    flex-direction: row;
    justify-content: space-between;
    padding: 32px;
    border: none;
    box-shadow: 0 15px 35px -10px rgba(15,23,42,0.4);
    transition: all 0.2s ease;
}
.action-card.primary:hover {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}
.action-card.primary .action-icon {
    background: rgba(255,255,255,0.1);
    color: white;
    width: 60px; height: 60px;
    margin-bottom: 0;
    border-radius: 50%;
}
.action-card.primary .action-title { color: white; }
.action-card.primary .action-desc { color: #94a3b8; }

.btn-pesan-skrg {
    background: white;
    color: #0f172a;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.95rem;
    display: flex; align-items: center; gap: 8px;
    white-space: nowrap;
}

@media (max-width: 600px) {
    .welcome-card { padding: 20px; flex-direction: column; align-items: flex-start; text-align: left; gap: 16px; }
    .welcome-stats-container { display: grid; grid-template-columns: repeat(3, 1fr); width: 100%; gap: 8px; }
    .welcome-stats { padding: 10px; min-width: 0; width: 100%; box-sizing: border-box; }
    .welcome-stats .stat-num { font-size: 1.2rem; }
    .welcome-stats .stat-label { font-size: 0.65rem; }
    .active-order-item { padding: 20px; flex-direction: column; align-items: flex-start; text-align: left; gap: 16px; }
    .ao-left { flex-direction: row; align-items: flex-start; text-align: left; gap: 16px; }
    .ao-right { width: 100%; text-align: left; }
    .ao-right .btn-track { display: flex; justify-content: center; width: 100%; padding: 12px 16px; }
    .action-card.primary { flex-direction: column; align-items: flex-start; text-align: left; gap: 20px; padding: 24px; }
    .action-card-inner { flex-direction: row !important; text-align: left !important; gap: 16px !important; }
    .btn-pesan-skrg { width: 100%; justify-content: center; }
    .action-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="navbar">
  <div class="container">
    <a href="home.php" class="logo-wrap">
      <img src="../../assets/img/logo_bup.png" alt="Logo BUP">
    </a>
    <div class="nav-links" id="navLinks">
      <a href="home.php" class="active">Beranda</a>
      <a href="../layanan.php">Layanan</a>
      <a href="my-orders.php">Riwayat Pesanan</a>
    </div>
    <div class="nav-right" style="display:flex; align-items:center; gap:10px;">
      <a href="../order.php" class="btn btn-dark hide-mob" style="padding: 8px 18px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; text-decoration: none;">Pesan</a>
      <div class="nav-dropdown-wrap" id="userDropWrap">
        <button class="nav-user-btn" onclick="toggleDropdown()">
          <span class="avatar"><?= $namaInisial ?></span>
          <span><?= htmlspecialchars(explode(' ', $customer['nama'])[0]) ?></span>
          <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="nav-dropdown" id="userDropdown">
          <div class="nav-dropdown-user">
              <div class="dd-name"><?= htmlspecialchars($customer['nama']) ?></div>
              <div class="dd-email">@<?= htmlspecialchars($customer['username']) ?></div>
          </div>
          
          <a href="profile.php">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
          </a>
          <div class="dd-divider"></div>
          <button class="dd-logout" onclick="window.location.href='../../api/customer_auth.php?action=logout'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Logout
          </button>
        </div>
      </div>
    </div>
    <div class="hamburger" id="hamburger" onclick="toggleMobMenu()"><span></span><span></span><span></span></div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mob-menu" id="mobMenu">
  <span class="mob-close" onclick="toggleMobMenu()">✕</span>
  <a href="home.php" class="active">Beranda</a>
  <a href="../layanan.php" onclick="toggleMobMenu()">Layanan</a>
  <a href="my-orders.php" onclick="toggleMobMenu()">Riwayat Pesanan</a>
  <a href="profile.php" onclick="toggleMobMenu()">Profil Saya</a>
  <a href="../../api/customer_auth.php?action=logout" style="color:#ef4444;">Logout</a>
  <a href="../order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan</a>
</div>

<div class="dashboard-container">

    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="welcome-text">
            <h1>Halo, <?= explode(' ', trim($customer['nama']))[0] ?>! <i class="fas fa-sparkles" style="color:#fbbf24;"></i></h1>
            <p>Selamat datang di Beranda BUP Laundry Anda.</p>
        </div>
        <div class="welcome-stats-container">
            <div class="welcome-stats">
                <div class="stat-num"><?= $totalAktif ?></div>
                <div class="stat-label">Diproses</div>
            </div>
            <div class="welcome-stats">
                <div class="stat-num"><?= $totalSelesai ?></div>
                <div class="stat-label">Selesai</div>
            </div>
            <div class="welcome-stats">
                <div class="stat-num"><?= $totalPesanan ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
    </div>

    <!-- Active Order -->
    <h2 class="section-title"><i class="fas fa-bell" style="color:#f59e0b;"></i> Cucian Sedang Diproses</h2>
    
    <?php if (count($activeOrders) > 0): ?>
        <div class="active-orders-wrapper">
            <?php foreach ($activeOrders as $order): ?>
                <?php 
                    $st = $statusLabels[$order['status_pesanan']] ?? $statusLabels['diterima']; 
                    $tglRaw = (!empty($order['tanggal_pesan']) && strpos($order['tanggal_pesan'], '0000-00-00') === false) ? $order['tanggal_pesan'] : $order['created_at'];
                    $tgl = date('d M Y', strtotime($tglRaw));
                    
                    $sb = $order['status_bayar'] ?? 'pending';
                    if ($sb === 'lunas') {
                        $sbLabel = 'Lunas';
                        $sbColor = '#10b981';
                    } elseif ($sb === 'gagal') {
                        $sbLabel = 'Gagal';
                        $sbColor = '#ef4444';
                    } else {
                        $sbLabel = 'Belum Lunas';
                        $sbColor = '#f59e0b';
                    }
                ?>
                <a href="my-orders.php" class="active-order-item">
                    <div class="ao-left">
                        <div class="ao-icon" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>;">
                            <i class="fas <?= $st['icon'] ?>"></i>
                        </div>
                        <div class="ao-details">
                            <h3>Order <?= htmlspecialchars($order['kode_pesanan']) ?></h3>
                            <p>Status Pengerjaan: <strong style="color:<?= $st['color'] ?>; display: inline-block;"><?= $st['label'] ?></strong></p>
                            <p>Status Pembayaran: <strong style="color:<?= $sbColor ?>; display: inline-block;"><?= $sbLabel ?></strong></p>
                            <p style="font-size:0.75rem; margin-top:4px;"><i class="far fa-calendar-alt"></i> <?= $tgl ?></p>
                        </div>
                    </div>
                    <div class="ao-right">
                        <div class="btn-track">Cek Status <i class="fas fa-chevron-right" style="font-size:0.7rem;"></i></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-order">
            <i class="fas fa-box-open"></i>
            <p>Belum ada cucian yang sedang diproses saat ini.</p>
        </div>
    <?php endif; ?>

    <!-- Quick Actions -->
    <h2 class="section-title" style="margin-top: 40px;"><i class="fas fa-bolt" style="color:#3b82f6;"></i> Menu Cepat</h2>
    <div class="action-grid">
        <a href="../order.php" class="action-card primary">
            <div class="action-card-inner">
                <div class="action-icon"><i class="fas fa-plus"></i></div>
                <div style="text-align:left;">
                    <div class="action-title">Pesan Layanan Baru</div>
                    <div class="action-desc">Buat pesanan laundry sepatu sekarang juga.</div>
                </div>
            </div>
            <div class="btn-pesan-skrg">Mulai Pesan <i class="fas fa-arrow-right"></i></div>
        </a>
        <a href="my-orders.php" class="action-card">
            <div class="action-card-inner">
                <div class="action-icon"><i class="fas fa-receipt"></i></div>
                <div style="text-align:left;">
                    <div class="action-title">Riwayat Pesanan</div>
                    <div class="action-desc">Lihat semua transaksi sebelumnya.</div>
                </div>
            </div>
        </a>
        <a href="profile.php" class="action-card">
            <div class="action-card-inner">
                <div class="action-icon"><i class="fas fa-user-cog"></i></div>
                <div style="text-align:left;">
                    <div class="action-title">Pengaturan Profil</div>
                    <div class="action-desc">Ubah password atau nama Anda.</div>
                </div>
            </div>
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/customer_footer.php'; ?>

<script>
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}
function toggleMobMenu() {
    document.getElementById('mobMenu').classList.toggle('show');
}
window.addEventListener('click', function(e) {
    const dd = document.getElementById('userDropdown');
    if (dd && dd.classList.contains('show') && !e.target.closest('.nav-dropdown-wrap')) {
        dd.classList.remove('show');
    }
});
</script>
</body>
</html>
