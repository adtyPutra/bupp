<?php
// ============================================================
// pages/customer/my-orders.php — Riwayat Pesanan Pelanggan
// ============================================================
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/customer_auth.php';

requireCustomerLogin();
$customer = getLoggedInCustomer();
$db       = db();

// Ambil semua pesanan milik pelanggan ini
$stmt = $db->prepare("
    SELECT p.*
    FROM pesanan p
    WHERE p.pelanggan_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$customer['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'diterima'      => ['label' => 'Diterima',      'badge' => 'badge-diterima'],
    'dicuci'        => ['label' => 'Dicuci',         'badge' => 'badge-dicuci'],
    'dikeringkan'   => ['label' => 'Dikeringkan',   'badge' => 'badge-dikeringkan'],
    'finishing'     => ['label' => 'Finishing',      'badge' => 'badge-finishing'],
    'siap_diambil'  => ['label' => 'Siap Diambil',  'badge' => 'badge-siap_diambil'],
    'diantar_kurir' => ['label' => 'Diantar Kurir', 'badge' => 'badge-diantar_kurir'],
    'selesai'       => ['label' => 'Selesai',        'badge' => 'badge-selesai'],
    'batal'         => ['label' => 'Dibatalkan',     'badge' => 'badge-batal'],
];
$namaInisial = strtoupper(substr($customer['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Pesanan | BUP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../../assets/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="../../assets/css/status.css?v=<?= time() ?>">
<link rel="stylesheet" href="../../assets/css/customer.css?v=<?= time() ?>">
<style>
body, .customer-page { 
    background: #fdfbf7 !important; 
    color: #1e293b;
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
      <a href="home.php">Beranda</a>
      <a href="../layanan.php">Layanan</a>
      <a href="my-orders.php" class="active">Riwayat Pesanan</a>
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
  <a href="home.php" onclick="toggleMobMenu()">Beranda</a>
  <a href="../layanan.php" onclick="toggleMobMenu()">Layanan</a>
  <a href="my-orders.php" class="active" onclick="toggleMobMenu()">Riwayat Pesanan</a>
  <a href="profile.php" onclick="toggleMobMenu()">Profil Saya</a>
  <a href="../../api/customer_auth.php?action=logout" style="color:#ef4444;">Logout</a>
  <a href="../order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan</a>
</div>

<div class="customer-page">
  <div class="customer-wrap">

    <div class="customer-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:32px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;">
      <div>
          <h1 style="display:flex; align-items:center; gap:12px; margin-bottom:6px;"><i class="fas fa-clipboard-list" style="color:#1e293b;"></i> Riwayat Pesanan</h1>
          <p style="color:#64748b;">Pantau status pembayaran dan pengerjaan pesanan Anda di sini.</p>
      </div>
      <a href="../layanan.php" class="btn btn-outline" style="border:1px solid #cbd5e1; color:#334155; padding:10px 20px; border-radius:50px; text-decoration:none; display:flex; align-items:center; gap:8px; font-weight:700; background:white; box-shadow:0 2px 4px rgba(0,0,0,0.02);"><i class="fas fa-plus"></i> Pesan Lagi</a>
    </div>

    <?php if (isset($_GET['new_order']) && $_GET['new_order'] == '1'): ?>
    <div id="successNotification" style="background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; border-radius:12px; padding:16px 20px; margin-bottom:24px; display:flex; align-items:center; gap:14px; font-weight:600; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1); transition: opacity 0.5s ease-out;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; color:#10b981; background: #fff; border-radius: 50%; padding: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <div style="line-height: 1.4;">
            Terima kasih! Pesanan Anda berhasil dibuat. <br>
            <span style="font-size: 0.85rem; font-weight: 500; opacity: 0.85;">Silakan pantau status pengerjaan, atau unggah bukti transfer di bawah ini.</span>
        </div>
    </div>
    <script>
        setTimeout(function() {
            var notif = document.getElementById('successNotification');
            if (notif) {
                notif.style.opacity = '0';
                setTimeout(function() { notif.style.display = 'none'; }, 500); // Tunggu animasi selesai
                // Bersihkan parameter dari URL agar tidak muncul lagi jika di-refresh
                var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path:newUrl}, '', newUrl);
            }
        }, 5000); // Menghilang setelah 5 detik
    </script>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
      <div class="section-card">
          <div class="section-card-body">
              <div class="empty-state">
                <div class="empty-icon" style="font-size:3.5rem; color:#cbd5e1; margin-bottom:16px;"><i class="fas fa-box-open"></i></div>
                <h4 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-bottom:8px;">Belum Ada Pesanan</h4>
                <p style="color:#64748b;">Kamu belum pernah melakukan pesanan.</p>
                <a href="../layanan.php" class="btn btn-primary-formal" style="margin-top:16px; display:inline-block;">Pesan Sekarang</a>
              </div>
          </div>
      </div>
    <?php else: ?>
      <div style="display:flex; flex-direction:column; gap:24px;">
      <?php foreach ($orders as $order):
        $st  = $statusLabels[$order['status_pesanan']] ?? ['label' => strtoupper($order['status_pesanan']), 'badge' => 'badge-diterima'];
        $tgl = date('d M Y, H:i', strtotime($order['created_at']));
        
        $isBatal = ($order['status_pesanan'] === 'batal');
        $isLunas = ($order['status_bayar'] === 'confirmed');

        $bayarLabel = ''; $bayarBg = ''; $bayarColor = ''; $bayarIcon = '';
        if ($isBatal) {
            $bayarLabel = 'Dibatalkan';
            $bayarBg = '#fef2f2'; $bayarColor = '#b91c1c'; $bayarIcon = '<i class="fas fa-times-circle"></i>';
        } elseif ($isLunas) {
            $bayarLabel = 'Lunas';
            $bayarBg = '#dcfce7'; $bayarColor = '#166534'; $bayarIcon = '<i class="fas fa-check-circle"></i>';
        } else {
            if ($order['metode_bayar'] === 'transfer_bca') {
                if (!empty($order['bukti_bayar'])) {
                    $bayarLabel = 'Menunggu Konfirmasi';
                    $bayarBg = '#eff6ff'; $bayarColor = '#1d4ed8'; $bayarIcon = '';
                } else {
                    $bayarLabel = 'Belum Lunas';
                    $bayarBg = '#fef3c7'; $bayarColor = '#b45309'; $bayarIcon = '';
                }
            } else {
                $bayarLabel = 'Belum Lunas';
                $bayarBg = '#fef3c7'; $bayarColor = '#b45309'; $bayarIcon = '';
            }
        }
      ?>
      <div class="order-card-wrap" style="background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;">
          <!-- Top Row -->
          <div class="order-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px; gap:12px;">
              <div style="display: flex; align-items: center; gap: 16px; flex-wrap:wrap;">
                  <strong style="font-size: 1.15rem; color: #0f172a; letter-spacing:0.5px;"><?= htmlspecialchars($order['kode_pesanan']) ?></strong>
                  <span style="color: #64748b; font-size: 0.9rem; display:flex; align-items:center; gap:6px;"><i class="far fa-calendar-alt"></i> <?= $tgl ?></span>
              </div>
              <span class="badge <?= $st['badge'] ?>" style="font-size:0.75rem; padding:6px 14px; border-radius:50px; letter-spacing:0.5px;"><?= strtoupper($st['label']) ?></span>
          </div>

          <!-- Main Body -->
          <div class="order-card-body" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap:20px;">
              <div>
                  <div style="font-size: 0.75rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 4px; letter-spacing:0.5px;">Total Tagihan</div>
                  <div style="font-size: 1.7rem; font-weight: 800; color: #b91c1c; margin-bottom: 14px; letter-spacing:-0.5px;">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></div>
                  
                  <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #475569; flex-wrap:wrap;">
                      Status Pembayaran: 
                      <span style="background: <?= $bayarBg ?>; color: <?= $bayarColor ?>; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; display:flex; align-items:center; gap:4px;">
                          <?= $bayarIcon ?> <?= $bayarLabel ?>
                      </span>
                  </div>
              </div>
              
              <div class="order-card-method" style="display: flex; align-items: center; justify-content: flex-end; gap: 8px;">
                 <div style="font-size:0.85rem; color:#64748b; font-weight:500;">Metode Pembayaran:</div>
                 <div style="font-size:0.75rem; font-weight:800; color:#0f172a; background:#f8fafc; padding:6px 14px; border-radius:50px; border:1px solid #e2e8f0; display:inline-block; letter-spacing: 0.5px;">
                     <?php
                     $metodeRaw = $order['metode_bayar'] ?? 'CASH';
                     $metodeDisplay = ($metodeRaw === 'transfer_bca') ? 'TRANSFER' : (($metodeRaw === 'tunai' || $metodeRaw === 'CASH') ? 'TUNAI' : strtoupper($metodeRaw));
                     echo htmlspecialchars($metodeDisplay);
                     ?>
                 </div>
              </div>
          </div>

          <!-- Alert Box -->
          <?php if ($isBatal): ?>
          <div style="background: #fef2f2; border: 1px dashed #fecaca; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
              <div style="color: #dc2626; font-size: 1.75rem; margin-bottom: 8px;"><i class="fas fa-times-circle"></i></div>
              <div style="font-weight: 800; color: #0f172a; margin-bottom: 16px; font-size:1.1rem;">Pesanan Dibatalkan</div>
              <div style="background: #fff; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; text-align: center; max-width: 450px; margin: 0 auto; box-shadow: 0 1px 3px rgba(220, 38, 38, 0.05);">
                  <div style="font-weight: 800; color: #dc2626; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Alasan Pembatalan</div>
                  <div style="font-size: 0.95rem; color: #475569; font-weight: 600; line-height: 1.5;">
                      <?= nl2br(htmlspecialchars($order['catatan_pembatalan'] ?: 'Dibatalkan oleh Admin')) ?>
                  </div>
              </div>
              <div style="margin-top: 20px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                  <button type="button" onclick="openStatusModal('<?= htmlspecialchars($order['kode_pesanan']) ?>')" style="background: #3b82f6; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; color:white; cursor:pointer; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                      Lacak Pesanan
                  </button>
                  <a href="../invoice.php?kode=<?= urlencode($order['kode_pesanan']) ?>" class="btn btn-outline" style="background: white; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #cbd5e1; color:#334155; text-decoration:none;">
                      <i class="fas fa-file-invoice"></i> Detail Invoice
                  </a>
              </div>
          </div>
          <?php elseif ($isLunas): ?>
          <div style="background: #f0fdf4; border: 1px dashed #bbf7d0; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
              <div style="color: #16a34a; font-size: 1.75rem; margin-bottom: 8px;"><i class="fas fa-check-circle"></i></div>
              <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px; font-size:1.1rem;">Pembayaran Lunas</div>
              <div style="font-size: 0.9rem; color: #475569; max-width: 450px; margin: 0 auto; line-height: 1.5;">Terima kasih, pembayaran pesanan ini sudah kami terima. Pesanan Anda saat ini sedang dikerjakan.</div>
              <div style="margin-top: 20px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                  <button type="button" onclick="openStatusModal('<?= htmlspecialchars($order['kode_pesanan']) ?>')" style="background: #3b82f6; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; color:white; cursor:pointer; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                      Lacak Pesanan
                  </button>
                  <a href="../invoice.php?kode=<?= urlencode($order['kode_pesanan']) ?>" class="btn btn-outline" style="background: white; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #cbd5e1; color:#334155; text-decoration:none;">
                      <i class="fas fa-file-invoice"></i> Detail Invoice
                  </a>
              </div>
          </div>
          <?php elseif ($order['metode_bayar'] === 'transfer_bca'): ?>
              <?php if (!empty($order['bukti_bayar'])): ?>
              <div style="background: #eff6ff; border: 1px dashed #bfdbfe; border-radius: 12px; padding: 24px; text-align: center; margin-top: 20px;">
                  <div style="color: #3b82f6; font-size: 1.75rem; margin-bottom: 8px;"><i class="fas fa-hourglass-half"></i></div>
                  <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px; font-size:1.1rem;">Menunggu Konfirmasi Admin</div>
                  <div style="font-size: 0.9rem; color: #475569; max-width: 450px; margin: 0 auto; line-height: 1.5;">Anda sudah berhasil mengunggah bukti pembayaran. Silakan tunggu admin memverifikasinya.</div>
                  <div style="margin-top: 20px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                      <button type="button" onclick="openStatusModal('<?= htmlspecialchars($order['kode_pesanan']) ?>')" style="background: #3b82f6; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; color:white; cursor:pointer; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                          Lacak Pesanan
                      </button>
                      <a href="../invoice.php?kode=<?= urlencode($order['kode_pesanan']) ?>" class="btn btn-outline" style="background: white; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #cbd5e1; color:#334155; text-decoration:none;">
                          <i class="fas fa-file-invoice"></i> Detail Invoice
                      </a>
                  </div>
              </div>
              <?php else: ?>
              <div class="transfer-alert-box" style="background: #f8fafc; border-radius: 12px; padding: 24px; border: 1px solid #e2e8f0; display:flex; gap: 20px; align-items: flex-start;">
                  <div class="ta-icon" style="background: #fffbeb; color: #d97706; font-size: 1.5rem; width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 4px rgba(217,119,6,0.1);">
                      <i class="fas fa-wallet"></i>
                  </div>
                  <div class="ta-content" style="flex: 1; min-width:0;">
                      <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px; font-size:1.1rem; letter-spacing:-0.5px;">Menunggu Pembayaran Transfer</div>
                      <div style="font-size: 0.95rem; color: #475569; margin-bottom: 20px; line-height: 1.5;">
                          Silakan transfer tagihan sebesar <strong style="color: #b91c1c; font-weight:800; font-size:1.05rem;">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></strong> ke rekening di bawah ini, lalu unggah buktinya.
                      </div>
                      
                      <div class="bca-account-box" style="background: white; border: 1px solid #cbd5e1; border-radius: 12px; padding: 24px 16px; margin-bottom: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                          <div style="background:#f8fafc; padding:10px 20px; border-radius:8px; border:1px solid #e2e8f0; display: inline-block;">
                              <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" style="height: 22px; display: block; margin: 0 auto;">
                          </div>
                          <div>
                              <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a; letter-spacing:2px; margin-bottom: 4px;">5891029111</div>
                              <div style="font-size: 0.9rem; color: #64748b; font-weight:700;">a.n. ANNIDA NURUL ISLAMI</div>
                          </div>
                      </div>
                      
                      <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px; width: 100%;">
                          <button type="button" onclick="openUploadModal('<?= $order['kode_pesanan'] ?>')" class="btn-unggah-tf" style="background: #0f172a; color: white; border: none; padding: 14px 24px; border-radius: 50px; font-weight: 700; font-size: 1rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; box-shadow: 0 4px 12px rgba(15,23,42,0.2); transition: 0.2s; width: 100%;">
                              <i class="fas fa-upload"></i> Unggah Bukti Transfer
                          </button>
                          <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-top: 4px;">
                              <button type="button" onclick="openStatusModal('<?= htmlspecialchars($order['kode_pesanan']) ?>')" style="background: #3b82f6; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; color:white; font-size: 0.95rem; cursor:pointer; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                                  Lacak Pesanan
                              </button>
                              <a href="../invoice.php?kode=<?= urlencode($order['kode_pesanan']) ?>" class="btn btn-outline" style="background: white; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #cbd5e1; color:#334155; text-decoration:none; font-size: 0.95rem;">
                                  <i class="fas fa-file-invoice"></i> Detail Invoice
                              </a>
                          </div>
                      </div>
                  </div>
              </div>
              <?php endif; ?>
          <?php else: ?>
          <div style="background: white; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 24px 16px; margin-bottom: 24px; margin-top: 20px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
              <div style="background:#f8fafc; padding:12px 16px; border-radius:8px; border:1px solid #e2e8f0; display: inline-block; color: #10b981;">
                  <i class="fas fa-money-bill-wave" style="font-size: 1.5rem; display: block; margin: 0 auto;"></i>
              </div>
              
              <div>
                  <div style="font-size: 1.1rem; color: #0f172a; font-weight:800; margin-bottom: 6px; letter-spacing:0.5px;">Pembayaran Tunai</div>
                  <div style="font-size: 0.95rem; color: #475569; font-weight:500; line-height: 1.5; max-width: 320px; margin: 0 auto;">
                      Silakan lakukan pembayaran tunai secara langsung kepada kurir atau di kasir kami.
                  </div>
              </div>
              
              <div style="width: 100%; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 4px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                  <button type="button" onclick="openStatusModal('<?= htmlspecialchars($order['kode_pesanan']) ?>')" style="background: #3b82f6; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:none; color:white; font-size: 0.95rem; cursor:pointer; box-shadow: 0 2px 4px rgba(59,130,246,0.2);">
                      Lacak Pesanan
                  </button>
                  <a href="../invoice.php?kode=<?= urlencode($order['kode_pesanan']) ?>" class="btn btn-outline" style="background: white; padding: 10px 24px; border-radius: 50px; font-weight: 600; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #cbd5e1; color:#334155; text-decoration:none; font-size: 0.95rem;">
                      <i class="fas fa-file-invoice"></i> Detail Invoice
                  </a>
              </div>
          </div>
          <?php endif; ?>
      </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Modal Upload Bukti Pembayaran -->
<div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding: 20px;">
    <div style="background:white; border-radius:16px; padding:32px; width:100%; max-width:400px; box-shadow:0 10px 25px rgba(0,0,0,0.2); position:relative;">
        <button type="button" onclick="closeUploadModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:1.5rem; cursor:pointer; color:#64748b;">&times;</button>
        
        <h3 style="margin-top:0; margin-bottom:8px; color:#0f172a; font-weight:800;">Unggah Bukti Transfer</h3>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:24px;">Pastikan foto terlihat jelas dan menampilkan tanggal serta nominal transfer.</p>
        
        <form id="uploadForm" onsubmit="submitUploadForm(event)">
            <input type="hidden" id="uploadKodePesanan" name="kode_pesanan">
            
            <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 32px 20px; text-align: center; margin-bottom: 24px; position: relative; background: #f8fafc; cursor:pointer;" onclick="document.getElementById('buktiFile').click()">
                <div id="uploadPlaceholder">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 12px;"></i>
                    <div style="font-weight: 600; color: #334155;">Pilih Foto/Screenshot</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">Format: JPG, JPEG, PNG</div>
                </div>
                <img id="uploadPreview" style="display:none; max-width:100%; max-height:200px; margin:0 auto; border-radius:8px;" />
                <input type="file" id="buktiFile" name="bukti_bayar" accept="image/jpeg, image/png, image/jpg" style="display:none;" required onchange="previewUpload(this)">
            </div>
            
            <button type="submit" id="btnSubmitUpload" class="btn btn-primary-formal" style="width:100%; padding:14px; font-size:1rem; border-radius:10px;">
                Kirim Bukti Pembayaran
            </button>
        </form>
    </div>
</div>

<!-- Modal Status Timeline -->
<div id="statusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; padding: 20px;">
    <div style="background:white; border-radius:16px; padding:0; width:100%; max-width:500px; box-shadow:0 10px 25px rgba(0,0,0,0.2); position:relative; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden;">
        
        <!-- Header -->
        <div style="padding: 24px 24px 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin:0 0 4px 0; color:#0f172a; font-weight:800; font-size:1.25rem;">Timeline Status</h3>
                <div style="color:#64748b; font-size:0.9rem; font-weight: 500;">Pesanan: <strong id="statusModalKode" style="color: #0f172a;"></strong></div>
            </div>
            <button type="button" onclick="closeStatusModal()" style="background:none; border:none; font-size:1.75rem; cursor:pointer; color:#94a3b8; transition: 0.2s;">&times;</button>
        </div>
        
        <!-- Content -->
        <div id="statusModalContent" style="padding: 0; overflow-y: auto; flex: 1; background: #f8fafc;">
            <!-- Timeline will be injected here -->
            <div style="padding: 40px; text-align: center; color: #64748b;">Memuat data...</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/customer_footer.php'; ?>

<script>
function openUploadModal(kode) {
    document.getElementById('uploadKodePesanan').value = kode;
    document.getElementById('uploadModal').style.display = 'flex';
    document.body.style.overflow = 'hidden'; // prevent scrolling behind
}

function closeUploadModal() {
    document.getElementById('uploadModal').style.display = 'none';
    document.body.style.overflow = '';
    // reset form
    document.getElementById('uploadForm').reset();
    document.getElementById('uploadPreview').style.display = 'none';
    document.getElementById('uploadPlaceholder').style.display = 'block';
}

function previewUpload(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('uploadPlaceholder').style.display = 'none';
            document.getElementById('uploadPreview').src = e.target.result;
            document.getElementById('uploadPreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

async function submitUploadForm(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitUpload');
    const form = document.getElementById('uploadForm');
    
    btn.disabled = true;
    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunggah...';
    
    try {
        const formData = new FormData(form);
        const res = await fetch('../../api/upload_bukti.php', { method: 'POST', body: formData });
        const data = await res.json();
        
        if (data.success) {
            alert('Bukti pembayaran berhasil diunggah! Kami akan segera memverifikasinya.');
            window.location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan sistem.');
            btn.disabled = false;
            btn.innerHTML = oldText;
        }
    } catch(err) {
        alert('Terjadi kesalahan jaringan.');
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}

function toggleDropdown() {
    const wrap = document.getElementById('userDropWrap');
    const dd   = document.getElementById('userDropdown');
    wrap.classList.toggle('open');
    dd.classList.toggle('show');
}
document.addEventListener('click', e => {
    const wrap = document.getElementById('userDropWrap');
    if (!wrap.contains(e.target)) {
        wrap.classList.remove('open');
        document.getElementById('userDropdown').classList.remove('show');
    }
});
function toggleMobMenu() {
    document.getElementById('mobMenu').classList.toggle('open');
}
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
});

// Status Modal Logic
const ICONS = {
    check: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,
    diterima: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M9 14l2 2 4-4"></path></svg>`,
    dicuci: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16l-1.5 11.5A2 2 0 0 1 16.5 22h-9A2 2 0 0 1 5.5 20.5L4 9z"/><path d="M8 9V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/><circle cx="12" cy="14" r="2"/><circle cx="9" cy="16" r="1.5"/><circle cx="15" cy="16" r="1.5"/></svg>`,
    dikeringkan: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/></svg>`,
    finishing: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>`,
    siap_diambil: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.5 2.5 0 0 1-2.5-2.5v0a2.5 2.5 0 0 0-5 0v0a2.5 2.5 0 0 1-5 0v0a2.5 2.5 0 0 0-5 0v0a2 2 0 0 1-2-2V7"/></svg>`,
    diantar_kurir: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="15" height="13" x="1" y="6" rx="2" ry="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="16.5" cy="18.5" r="2.5"/></svg>`,
    selesai: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
};

const DEFAULT_TAHAPAN = [
    { k: 'diterima', l: 'Diterima', icon: 'diterima', d: 'Pesanan masuk dan menunggu konfirmasi pembayaran admin.' },
    { k: 'dicuci', l: 'Dicuci', icon: 'dicuci', d: 'Pesanan sedang dalam proses pencucian.' },
    { k: 'dikeringkan', l: 'Dikeringkan', icon: 'dikeringkan', d: 'Pesanan sedang dalam proses pengeringan.' },
    { k: 'finishing', l: 'Finishing', icon: 'finishing', d: 'Pesanan sedang dalam tahap pengecekan akhir dan persiapan pengemasan.' }
];

async function openStatusModal(kode) {
    document.getElementById('statusModalKode').innerText = kode;
    document.getElementById('statusModalContent').innerHTML = '<div style="padding:40px;text-align:center;color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
    document.getElementById('statusModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    try {
        const res = await fetch('../../api/order.php?action=status&kode=' + encodeURIComponent(kode));
        const json = await res.json();
        
        if(!json.success) {
            document.getElementById('statusModalContent').innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">Pesanan tidak ditemukan.</div>';
            return;
        }
        
        const o = json.data;
        const mMetode = (o.nama_metode || o.metode_pengiriman || '').toLowerCase();
        const isAntarSendiri = mMetode.includes('antar') && !mMetode.includes('jemput');
        
        let dynamicTahapan = JSON.parse(JSON.stringify(DEFAULT_TAHAPAN));
        if (isAntarSendiri) {
            dynamicTahapan[0].d = 'Pesanan tercatat. Silakan antar barang Anda ke toko kami untuk mulai diproses.';
            dynamicTahapan.push({ k: 'siap_diambil', l: 'Siap Diambil', icon: 'siap_diambil', d: 'Pengerjaan selesai! Pesanan Anda siap untuk diambil di toko kami.' });
            dynamicTahapan.push({ k: 'selesai', l: 'Selesai', icon: 'selesai', d: 'Pesanan selesai dan telah diambil. Terima kasih telah menggunakan layanan BUP Laundry.' });
        } else {
            dynamicTahapan[0].d = 'Pesanan tercatat. Kurir kami akan segera melakukan penjemputan ke Alamat Anda.';
            dynamicTahapan.push({ k: 'diantar_kurir', l: 'Diantar Kurir', icon: 'diantar_kurir', d: 'Pengerjaan selesai! Pesanan Anda sedang dalam perjalanan diantar oleh kurir kami.' });
            dynamicTahapan.push({ k: 'selesai', l: 'Selesai', icon: 'selesai', d: 'Pesanan selesai dan telah diterima. Terima kasih telah menggunakan layanan BUP Laundry.' });
        }
        
        let finalTimelineHtml = '';
        if (o.status_pesanan === 'batal') {
            finalTimelineHtml = `
                <div style="padding: 24px; text-align: center;">
                    <div style="color: #ef4444; font-size: 3rem; margin-bottom: 12px;"><i class="fas fa-times-circle"></i></div>
                    <div style="font-weight: 800; font-size: 1.25rem; color: #991b1b; margin-bottom: 8px;">PESANAN DIBATALKAN</div>
                    <div style="color: #7f1d1d; font-size: 0.95rem;">${o.catatan_pembatalan || 'Pesanan dibatalkan oleh admin.'}</div>
                </div>
            `;
        } else {
            const curIdx = dynamicTahapan.findIndex(s => s.k === o.status_pesanan);
            
            let updatedAtFormatted = '';
            if (o.updated_at) {
                const d = new Date(o.updated_at.replace(' ', 'T'));
                if (!isNaN(d)) {
                    updatedAtFormatted = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                        + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                }
            }
            
            const stepsHtml = dynamicTahapan.map((s, i) => {
                const isDone = i < curIdx || (i === curIdx && s.k === 'selesai');
                const isActive = i === curIdx && s.k !== 'selesai';
                const tlClass = isDone ? 'done' : (isActive ? 'active' : 'pending');
                const iconHtml = isDone ? ICONS.check : ICONS[s.icon];
                const statusBadge = isDone ? 'SELESAI' : (isActive ? 'SEDANG DIKERJAKAN' : 'MENUNGGU');
                const tsHtml = (isActive || (isDone && s.k === 'selesai')) && updatedAtFormatted
                    ? `<div class="tl-timestamp">${updatedAtFormatted}</div>` : '';
                
                return `
                    <div class="tl-step ${tlClass}">
                        <div class="tl-circle">${iconHtml}</div>
                        <div class="tl-content">
                            <div class="tl-title">${s.l}</div>
                            <div class="tl-status">${statusBadge}</div>
                            ${tsHtml}
                            <div class="tl-desc">${s.d}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            finalTimelineHtml = `
                <div class="timeline-sec" style="background: transparent; padding: 24px;">
                    <div class="timeline-wrap">
                        ${stepsHtml}
                    </div>
                </div>
            `;
        }
        
        document.getElementById('statusModalContent').innerHTML = finalTimelineHtml;
        
    } catch (e) {
        document.getElementById('statusModalContent').innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;">Terjadi kesalahan sistem.</div>';
    }
}

function closeStatusModal() {
    document.getElementById('statusModal').style.display = 'none';
    document.body.style.overflow = '';
}
</script>
</body>
</html>
