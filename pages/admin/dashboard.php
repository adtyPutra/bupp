<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id']) && function_exists('isLoggedIn') && !isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = db();

// Helper: Format tanggal ke bahasa Indonesia
if (!function_exists('tglIndo')) {
    function tglIndo($tgl) {
        $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei',
                  'Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt',
                  'Nov'=>'Nov','Dec'=>'Des'];
        $ts = strtotime($tgl);
        $eng = date('M', $ts);
        return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
    }
}

// Data Statistik
$total_bulan = $db->query("SELECT COUNT(*) FROM pesanan WHERE MONTH(tanggal_pesan)=MONTH(NOW()) AND YEAR(tanggal_pesan)=YEAR(NOW())")->fetchColumn();
$total_selesai = $db->query("SELECT COUNT(*) FROM pesanan WHERE status_pesanan='selesai'")->fetchColumn();
$total_proses = $db->query("SELECT COUNT(*) FROM pesanan WHERE status_pesanan!='selesai'")->fetchColumn();
$pendapatan = $db->query("SELECT COALESCE(SUM(total_harga), 0) FROM pesanan WHERE status_bayar IN ('confirmed', 'cash', 'lunas') AND MONTH(tanggal_pesan) = MONTH(NOW()) AND YEAR(tanggal_pesan) = YEAR(NOW())")->fetchColumn();

// Data Pesanan Terbaru
$recent = $db->query("
    SELECT 
        p.id, p.kode_pesanan, p.tanggal_pesan, p.created_at, p.total_harga, p.status_pesanan, p.metode_bayar, p.status_bayar, p.bukti_bayar,
        pel.nama, pel.no_wa, pel.alamat,
        mp.nama_metode AS metode_pengiriman
    FROM pesanan p
    LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.id
    LEFT JOIN metode_pengiriman mp ON p.metode_pengiriman_id = mp.id
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Ambil Detail Pesanan
if (count($recent) > 0) {
    $order_ids = array_column($recent, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    
    $details_stmt = $db->prepare("
        SELECT dp.pesanan_id, dp.merk_item, dp.ukuran, dp.warna, dp.jumlah, dp.catatan, l.kategori, l.jenis AS nama_layanan
        FROM detail_pesanan dp
        LEFT JOIN layanan l ON dp.layanan_id = l.id
        WHERE dp.pesanan_id IN ($in)
    ");
    $details_stmt->execute($order_ids);
    $all_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);

    $details_grouped = [];
    foreach ($all_details as $d) {
        $details_grouped[$d['pesanan_id']][] = $d;
    }

    foreach ($recent as &$r) {
        $r['items'] = $details_grouped[$r['id']] ?? [];
    }
}

// Data Grafik Bar Bulanan
$bar = $db->query("
    SELECT MONTH(tanggal_pesan) AS bln, COUNT(*) AS total 
    FROM pesanan 
    WHERE YEAR(tanggal_pesan)=YEAR(NOW()) 
    GROUP BY bln
")->fetchAll(PDO::FETCH_KEY_PAIR);

$bar_data = [];
for ($m = 1; $m <= 12; $m++) {
    $bar_data[] = $bar[$m] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="id" class="page-dashboard">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | BUP Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css">

<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>

<body>
<div class="admin-page">

  <?php if (file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>

  <div class="admin-content">

    <style>
      /* Solusi jitu untuk mobile agar kebal cache: Tata Letak Topbar */
      @media (max-width: 768px) {
        .admin-topbar {
          padding-top: 8px !important;
          padding-left: 0 !important;
          flex-direction: column !important;
          align-items: flex-start !important;
        }
        .admin-topbar h1 {
          margin-top: 0 !important;
          margin-left: 0 !important;
          margin-bottom: 4px !important;
        }
        .admin-topbar .sub {
          margin-left: 0 !important; /* Paksa rata kiri murni */
          font-size: 0.98rem !important; /* Perbesar sedikit */
          font-weight: 500 !important;
          color: #475569 !important;
        }
      }
    </style>
    <div class="admin-topbar">
      <div>
        <h1>Dashboard</h1>
        <div class="sub">Selamat datang, <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></div>
      </div>
    </div>

    <div class="stat-cards modern">
      <div class="stat-card gradient-blue">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
          </div>
          <div style="display: flex; flex-direction: column; overflow: hidden;">
            <div class="sc-val"><?= $total_bulan ?></div>
            <div class="sc-lbl">Pesanan Bulan Ini</div>
          </div>
        </div>
      </div>

      <div class="stat-card gradient-green">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          </div>
          <div style="display: flex; flex-direction: column; overflow: hidden;">
            <div class="sc-val"><?= $total_selesai ?></div>
            <div class="sc-lbl">Selesai</div>
          </div>
        </div>
      </div>

      <div class="stat-card gradient-orange">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
          </div>
          <div style="display: flex; flex-direction: column; overflow: hidden;">
            <div class="sc-val"><?= $total_proses ?></div>
            <div class="sc-lbl">Diproses</div>
          </div>
        </div>
      </div>

      <div class="stat-card gradient-purple">
        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
          </div>
          <div style="display: flex; flex-direction: column; overflow: hidden;">
            <div class="sc-val" style="font-size: 1rem;">
                <?= function_exists('rupiah') ? rupiah((int)$pendapatan) : 'Rp ' . number_format((int)$pendapatan, 0, ',', '.') ?>
            </div>
            <div class="sc-lbl">Pendapatan</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="admin-grid2">
      <div class="admin-card" style="height: 100%; margin-bottom: 0;">
        <div class="ac-title">Pesanan Bulanan <?= date('Y') ?></div>
        <?php $max_v = max($bar_data) ?: 1; ?>
        <div class="chart-bar-wrap" style="height: 220px;">
          <?php foreach ($bar_data as $idx => $val): 
            $hp = ($val / $max_v) * 100;
          ?>
            <div class="chart-bar-col">
              <div class="chart-bar-val"><?= $val ?: '' ?></div>
              <div class="chart-bar-container">
                <div class="chart-bar" style="height:<?= $hp ?>%; <?= $val==0?'background:transparent':'' ?>"></div>
              </div>
              <div class="chart-bar-label"><?= ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'][$idx] ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="admin-card">
      <div class="ac-title">Pesanan Terbaru</div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 50px; text-align: center;">No</th>
              <th style="text-align: left;">Kode Pesanan</th>
              <th style="text-align: left;">Tanggal Pesanan</th>
              <th style="text-align: left;">Nama Pelanggan</th>
              <th style="text-align: left;">Rincian Item Cucian</th>
              <th style="text-align: left;">Total Tagihan</th>
              <th style="text-align: center;">Status Kerja</th>
            </tr>
          </thead>
          <tbody>
          <?php $no = 1; foreach ($recent as $o): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td style="white-space: nowrap;">
                <strong><?= htmlspecialchars($o['kode_pesanan']) ?></strong>
              </td>
              
              <td style="white-space: nowrap;">
                  <div style="font-size: 0.85rem; font-weight: 700; color: #475569;">
                    <?= tglIndo($o['tanggal_pesan']) ?>
                  </div>
                  <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; margin-top: 2px;">
                    <?= date('H:i', strtotime($o['created_at'])) ?> WIB
                  </div>
              </td>

              <td style="font-weight: 600; color: #334155;">
                <?= htmlspecialchars($o['nama']) ?>
              </td>
              
              <td style="vertical-align: top;">
                  <div style="display: flex; flex-direction: column; gap: 12px; padding: 4px 0;">
                  <?php if (count($o['items']) > 0): 
                      $grouped_items = [];
                      foreach ($o['items'] as $item) {
                          if ($item['kategori'] !== 'Extra Treatment') {
                              $item['extras'] = [];
                              $grouped_items[] = $item;
                          } else {
                              if (!empty($grouped_items)) {
                                  $grouped_items[count($grouped_items) - 1]['extras'][] = $item;
                              } else {
                                  $item['extras'] = [];
                                  $grouped_items[] = $item;
                              }
                          }
                      }
                      
                      foreach ($grouped_items as $item): ?>
                          <div>
                              <?php if ($item['kategori'] !== 'Extra Treatment'): 
                                  $detailStr = [];
                                  if (!empty($item['merk_item'])) $detailStr[] = htmlspecialchars($item['merk_item']);
                                  if (!empty($item['ukuran'])) $detailStr[] = "Size " . htmlspecialchars($item['ukuran']);
                                  if (!empty($item['warna'])) $detailStr[] = htmlspecialchars($item['warna']);
                                  $detailText = !empty($detailStr) ? implode(' | ', $detailStr) : '';
                              ?>
                                  <div style="font-size: 0.85rem; font-weight: 700; color: #1e293b; line-height: 1.4;">
                                      <?= htmlspecialchars($item['kategori']) ?> - <?= htmlspecialchars($item['nama_layanan']) ?> <span style="font-weight: 800; color: #475569;">(x<?= (int)$item['jumlah'] ?>)</span>
                                  </div>
                                  <?php if ($detailText): ?>
                                      <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                          <?= $detailText ?>
                                      </div>
                                  <?php endif; ?>
                              <?php else: ?>
                                  <div style="font-size: 0.85rem; font-weight: 600; color: #0284c7;">| Extra: <?= htmlspecialchars($item['nama_layanan']) ?></div>
                              <?php endif; ?>

                              <?php if (!empty($item['extras'])): ?>
                                  <div style="margin-top: 4px; padding-left: 8px; border-left: 2px solid #cbd5e1; display: flex; flex-direction: column; gap: 2px;">
                                      <?php foreach ($item['extras'] as $ext): ?>
                                          <div style="font-size: 0.8rem; font-weight: 600; color: #64748b;">
                                              | Extra: <?= htmlspecialchars($ext['nama_layanan']) ?>
                                          </div>
                                      <?php endforeach; ?>
                                  </div>
                              <?php endif; ?>
                          </div>
                      <?php endforeach; else: ?>
                      <div style="color:#94a3b8; font-size:0.82rem; font-style:italic;">Tidak ada item</div>
                  <?php endif; ?>
                  </div>
              </td>
              
              <td style="font-weight:800; color:#0f172a; white-space: nowrap; font-size: 0.95rem;">
                  <?= function_exists('rupiah') ? rupiah((int)$o['total_harga']) : 'Rp ' . number_format((int)$o['total_harga'], 0, ',', '.') ?>
              </td>
              
              <td style="text-align: center;">
                <?php
                  $st = strtolower($o['status_pesanan']);
                  $badge = 'bg-diterima';
                  if($st=='diproses') { $badge='bg-diproses'; }
                  elseif($st=='finishing') { $badge='bg-finishing'; }
                  elseif($st=='siap_diambil') { $badge='bg-finishing'; }
                  elseif($st=='diantar_kurir') { $badge='bg-finishing'; }
                  elseif($st=='selesai') { $badge='bg-selesai'; }
                  elseif($st=='batal') { $badge='bg-batal'; }
                  elseif($st=='dicuci'||$st=='dikeringkan') { $badge='bg-dicuci'; }
                  
                  $st_label = ucwords(str_replace('_', ' ', $st));
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($st_label) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<script src="../../assets/js/admin.js"></script>
</body>
</html>

