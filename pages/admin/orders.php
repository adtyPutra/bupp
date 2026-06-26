<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/auth.php'; // Aktifkan jika file auth ada
require_once __DIR__ . '/../../includes/helpers.php';

// Fallback session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id']) && function_exists('isLoggedIn') && !isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = db();

// Helper: Format tanggal ke bahasa Indonesia
function tglIndo($tgl) {
    $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei',
              'Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt',
              'Nov'=>'Nov','Dec'=>'Des'];
    $ts  = strtotime($tgl);
    $eng = date('M', $ts);
    return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
}

// Proses Hapus 
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $db->prepare("DELETE FROM detail_pesanan WHERE pesanan_id = ?")->execute([$_GET['id']]);
    $db->prepare("DELETE FROM pesanan WHERE id = ?")->execute([$_GET['id']]);
    header('Location: ?msg=deleted'); 
    exit;
}

// Proses Update Pesanan (Status Pengerjaan, Status Bayar & Data Pelanggan)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_pesanan') {
    $edit_id = $_POST['edit_id'];
    $pelanggan_id = $_POST['pelanggan_id'];
    
    $nama = $_POST['nama'];
    $no_wa = $_POST['no_wa'];
    $alamat = $_POST['alamat'];
    $waktu_penjemputan = $_POST['waktu_penjemputan'];
    $tanggal_jemput = $_POST['tanggal_jemput'] ?? null;
    $status_pesanan = $_POST['status_pesanan'];
    $status_bayar = $_POST['status_bayar']; // Tambahan Status Bayar
    $catatan_pembatalan = $_POST['catatan_pembatalan'] ?? null;

    // Jika status bayar dipilih Batal, maka status pesanan juga ikut batal
    if ($status_bayar === 'batal') {
        $status_pesanan = 'batal';
    } else {
        $catatan_pembatalan = null;
    }

    // Update tabel pesanan
    if ($tanggal_jemput) {
        $updatePesanan = $db->prepare("UPDATE pesanan SET status_pesanan = ?, status_bayar = ?, waktu_penjemputan = ?, tanggal_pesan = ?, catatan_pembatalan = ? WHERE id = ?");
        $updatePesanan->execute([$status_pesanan, $status_bayar, $waktu_penjemputan, $tanggal_jemput, $catatan_pembatalan, $edit_id]);
    } else {
        $updatePesanan = $db->prepare("UPDATE pesanan SET status_pesanan = ?, status_bayar = ?, waktu_penjemputan = ?, catatan_pembatalan = ? WHERE id = ?");
        $updatePesanan->execute([$status_pesanan, $status_bayar, $waktu_penjemputan, $catatan_pembatalan, $edit_id]);
    }

    // Update tabel pelanggan
    $updatePelanggan = $db->prepare("UPDATE pelanggan SET nama = ?, no_wa = ?, alamat = ? WHERE id = ?");
    $updatePelanggan->execute([$nama, $no_wa, $alamat, $pelanggan_id]);

    // Update detail pesanan (Merek, Ukuran, Warna)
    if (isset($_POST['detail_id']) && is_array($_POST['detail_id'])) {
        $stmtDetail = $db->prepare("UPDATE detail_pesanan SET merk_item = ?, ukuran = ?, warna = ? WHERE id = ?");
        foreach ($_POST['detail_id'] as $index => $detail_id) {
            $merk = $_POST['detail_merk'][$index] ?? '';
            $ukuran = $_POST['detail_ukuran'][$index] ?? '';
            $warna = $_POST['detail_warna'][$index] ?? '';
            $stmtDetail->execute([$merk, $ukuran, $warna, $detail_id]);
        }
    }

    header('Location: ?msg=updated');
    exit;
}



// Fitur Pencarian & Filter
$q = $_GET['q'] ?? '';
$status_filter = $_GET['status'] ?? 'semua';

$where_clauses = [];
$params = [];

if ($q !== '') {
    $where_clauses[] = "(p.kode_pesanan LIKE ? OR pel.nama LIKE ? OR pel.no_wa LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($status_filter !== 'semua') {
    $where_clauses[] = "p.status_pesanan = ?";
    $params[] = $status_filter;
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// 1. AMBIL DATA UTAMA PESANAN
$stmt = $db->prepare("
    SELECT 
        p.id, p.pelanggan_id, p.kode_pesanan, p.tanggal_pesan, p.total_harga, p.status_pesanan, 
        p.metode_bayar, p.status_bayar, p.bukti_bayar, p.catatan_pembatalan,
        p.waktu_penjemputan, p.created_at,
        pel.nama, pel.no_wa, pel.alamat,
        mp.nama_metode AS metode_pengiriman
    FROM pesanan p
    LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.id
    LEFT JOIN metode_pengiriman mp ON p.metode_pengiriman_id = mp.id
    $where_sql
    ORDER BY p.created_at DESC, p.id DESC
");
$stmt->execute($params);
$semua_pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. AMBIL DETAIL ITEM & CATATAN PER ITEM
if (count($semua_pesanan) > 0) {
    $order_ids = array_column($semua_pesanan, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    
    $details_stmt = $db->prepare("
        SELECT dp.id AS detail_id, dp.pesanan_id, dp.merk_item, dp.ukuran, dp.warna, dp.jumlah, dp.catatan AS catatan_item, 
               l.kategori, l.jenis AS nama_layanan
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

    foreach ($semua_pesanan as &$pesanan) {
        $pesanan['items'] = $details_grouped[$pesanan['id']] ?? [];
    }
}

$list_filter = ['Semua', 'Diterima', 'Dicuci', 'Dikeringkan', 'Finishing', 'Siap Diambil', 'Diantar Kurir', 'Selesai'];
?>
<!DOCTYPE html>
<html lang="id" class="page-orders">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pesanan | BUP Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css">
<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>

<body>
<div class="admin-page">
  <?php if(file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-topbar">
      <h1>Kelola Pesanan</h1>
    </div>

    <div class="admin-card">
      <div class="control-bar">
          <div class="control-top-row">
              <form method="GET" class="search-form" style="max-width: 700px;">
                  <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Cari nama, atau kode pesanan..." class="search-input">
                  <?php if($status_filter != 'semua'): ?>
                      <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter ?? '') ?>">
                  <?php endif; ?>
                  <button type="submit" class="btn-search">Cari</button>
                  <a href="pesanan_edit.php" class="btn-add-order"> 
                      + Tambah Pesanan
                  </a>
              </form>
          </div>

          <div class="control-bottom-row">
              <div class="filter-pills">
                  <?php foreach($list_filter as $f): 
                      $val = strtolower(str_replace(' ', '_', $f));
                      $isActive = ($status_filter == $val) ? 'active' : '';
                  ?>
                      <a href="?status=<?= $val ?><?= $q ? '&q='.urlencode($q) : '' ?>" class="pill <?= $isActive ?>"><?= htmlspecialchars($f) ?></a>
                  <?php endforeach; ?>
              </div>

              <select class="filter-select-mobile" onchange="window.location.href=this.value">
                  <?php foreach($list_filter as $f): 
                      $val = strtolower(str_replace(' ', '_', $f));
                      $isActive = ($status_filter == $val) ? 'selected' : '';
                      $url = "?status=" . $val . ($q ? '&q='.urlencode($q) : '');
                  ?>
                      <option value="<?= $url ?>" <?= $isActive ?>><?= $f == 'Semua' ? 'Semua Status' : htmlspecialchars($f) ?></option>
                  <?php endforeach; ?>
              </select>
          </div>
      </div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="text-align: center; width:50px;">No</th>
              <th style="text-align: left;">Kode Pesanan</th>
              <th style="text-align: left;">Tanggal Pesanan</th>
              <th style="text-align: left;">Nama Pelanggan</th>
              <th style="text-align: left;">Rincian Item Cucian</th>
              <th style="text-align: left;">Metode Pengiriman</th>
              <th style="text-align: center;">Status Kerja</th>
              <th style="text-align: center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if(count($semua_pesanan) == 0): ?>
            <tr>
                <td colspan="12" style="text-align: center; padding: 60px; color: #64748b; font-weight: 500;">
                    Belum ada pesanan yang sesuai kriteria.
                </td>
            </tr>
          <?php endif; ?>

          <?php $no = 1; foreach ($semua_pesanan as $o): ?>
            <tr>
              <td style="text-align: center; font-weight: 700; color: #94a3b8;"><?= $no++ ?></td>
              
              <td class="td-nowrap">
                  <strong style="color:#0f172a; font-size: 0.75rem;"><?= htmlspecialchars($o['kode_pesanan'] ?? '') ?></strong>
              </td>

              <td class="td-nowrap">
                  <div style="font-size: 0.7rem; font-weight: 700; color: #475569;">
                      <?= tglIndo(date('Y-m-d', strtotime($o['created_at']))) ?>
                  </div>
                  <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 600; margin-top: 2px;">
                      <?= date('H:i', strtotime($o['created_at'])) ?> WIB
                  </div>
              </td>
              
              <td class="td-nowrap">
                  <strong style="color:#0f172a; font-size: 0.75rem;"><?= htmlspecialchars($o['nama'] ?? '') ?></strong>
              </td>

              <td style="min-width: 250px; vertical-align: top;">
                  <div style="display: flex; flex-direction: column; gap: 12px; padding: 4px 0;">
                  <?php 
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
                              <div style="font-size: 0.7rem; font-weight: 700; color: #1e293b; line-height: 1.4;">
                                  <?= htmlspecialchars($item['kategori']) ?> - <?= htmlspecialchars($item['nama_layanan']) ?> <span style="font-weight: 800; color: #475569;">(x<?= (int)$item['jumlah'] ?>)</span>
                              </div>
                              <?php if ($detailText): ?>
                                  <div style="font-size: 0.65rem; color: #64748b; margin-top: 2px;">
                                      <?= $detailText ?>
                                  </div>
                              <?php endif; ?>
                          <?php else: ?>
                              <div style="font-size: 0.7rem; font-weight: 600; color: #475569;">+ Extra: <?= htmlspecialchars($item['nama_layanan']) ?> (x<?= (int)$item['jumlah'] ?>)</div>
                          <?php endif; ?>

                          <?php if (!empty($item['extras'])): ?>
                              <div style="margin-top: 4px; padding-left: 8px; border-left: 2px solid #cbd5e1; display: flex; flex-direction: column; gap: 2px;">
                                  <?php foreach ($item['extras'] as $ext): ?>
                                      <div style="font-size: 0.65rem; font-weight: 600; color: #64748b;">
                                          | Extra: <?= htmlspecialchars($ext['nama_layanan']) ?>
                                      </div>
                                  <?php endforeach; ?>
                              </div>
                          <?php endif; ?>
                      </div>
                  <?php endforeach; ?>
                  <?php if(empty($o['items'])): ?>
                      <div style="color:#94a3b8; font-size:0.7rem; font-style:italic;">Tidak ada item</div>
                  <?php endif; ?>
                  </div>
              </td>
              
              <td>
                  <?php $metode_kirim = str_replace('_', ' ', $o['metode_pengiriman'] ?? 'Toko'); ?>
                  <div style="font-weight:700; font-size:0.7rem; color:#475569; white-space: nowrap;"><?= htmlspecialchars($metode_kirim) ?></div>
              </td>
              
              <td style="text-align: center;">
                <?php
                  $st = strtolower($o['status_pesanan']);
                  $badge = 'bg-diterima';
                  if($st=='finishing') { $badge='bg-finishing'; }
                  elseif($st=='siap_diambil') { $badge='bg-finishing'; }
                  elseif($st=='diantar_kurir') { $badge='bg-finishing'; }
                  elseif($st=='selesai') { $badge='bg-selesai'; }
                  elseif($st=='batal') { $badge='bg-batal'; }
                  elseif($st=='dicuci'||$st=='dikeringkan') { $badge='bg-dicuci'; }
                  $st_label = ucwords(str_replace('_', ' ', $st));
                ?>
                <span class="badge <?= $badge ?>"><?= htmlspecialchars($st_label) ?></span>
              </td>
              
              <td style="text-align: center;">
                <div class="act-btns">
                  <?php
                    $data_json = htmlspecialchars(json_encode([
                        'id'                => $o['id'],
                        'pelanggan_id'      => $o['pelanggan_id'],
                        'kode'              => $o['kode_pesanan'],
                        'nama'              => $o['nama'],
                        'nowa'              => $o['no_wa'],
                        'alamat'            => !empty($o['alamat']) ? $o['alamat'] : 'Antar & Ambil di Toko',
                        'alamat_asli'       => $o['alamat'] ?? '',
                        'waktu_penjemputan' => $o['waktu_penjemputan'] ?? '',
                        'harga'             => function_exists('rupiah') ? rupiah((int)$o['total_harga']) : 'Rp ' . number_format((int)$o['total_harga'], 0, ',', '.'),
                        'metode_str'        => str_replace('_', ' ', $o['metode_bayar']),
                        'status_label'      => $st_label,
                        'status'            => $o['status_pesanan'],
                        'tanggal'           => tglIndo(date('Y-m-d', strtotime($o['created_at']))),
                        'tanggal_jemput'    => tglIndo($o['tanggal_pesan']),
                        'tanggal_pesan_raw' => $o['tanggal_pesan'],
                        'waktu'             => date('H:i', strtotime($o['created_at'])),
                        'metode_pengiriman' => str_replace('_', ' ', $o['metode_pengiriman'] ?? 'Toko'),
                        'status_bayar'      => $o['status_bayar'],
                        'catatan_pembatalan'=> $o['catatan_pembatalan'] ?? '',
                        'bukti'             => $o['bukti_bayar'] ?? '',
                        'items'             => $o['items']
                    ]), ENT_QUOTES, 'UTF-8');
                  ?>
                  
                  <button type="button" class="act-btn btn-edit" onclick="bukaModalEdit(<?= $data_json ?>)" title="Update Status & Edit Data">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                  </button>
                  <button type="button" class="act-btn btn-detail" onclick="bukaModalDetail(<?= $data_json ?>)" title="Lihat Detail Rincian">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                  <a href="?action=delete&id=<?= $o['id'] ?>" class="act-btn btn-del" onclick="return confirm('Peringatan: Yakin ingin menghapus pesanan <?= htmlspecialchars($o['kode_pesanan'] ?? '') ?>? Semua riwayat item cucian ini akan hilang selamanya.')" title="Hapus">
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
</tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<div id="modalEdit" class="modal-overlay modal-edit">
  <div class="modal-content">
    <button class="btn-close" onclick="tutupModal('modalEdit')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    <h2 class="edit-title">Edit & Update Status</h2>

    <div class="summary-box">
        <div class="sum-header">
            <div>
                <div class="sum-name" id="e_kode"></div>
                <div style="font-size: 0.85rem; color: #64748b; margin-top:4px; font-weight: 600;" id="e_nama_display"></div>
            </div>
            <div class="sum-cat" id="e_jml_item"></div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; border-top: 1px dashed #cbd5e1; padding-top: 16px;">
            <div>
                <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 4px; font-weight: 800; text-transform: uppercase;">Total Tagihan</div>
                <div class="sum-price" id="e_harga"></div>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="action" value="update_pesanan">
        <input type="hidden" name="edit_id" id="e_id">
        <input type="hidden" name="pelanggan_id" id="e_pelanggan_id">
        
        <div class="form-group">
            <label class="form-label">Nama Pelanggan</label>
            <input type="text" name="nama" id="e_input_nama" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">No. WhatsApp</label>
            <input type="text" name="no_wa" id="e_input_nowa" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Alamat / Detail Pengiriman</label>
            <textarea name="alamat" id="e_input_alamat" class="form-control" rows="2" placeholder="Biarkan kosong jika layanan antar-ambil di toko"></textarea>
        </div>
        
        <div id="e_group_jemput" style="display: none;">
            <div class="form-group">
                <label class="form-label">Tanggal Penjemputan</label>
                <input type="date" name="tanggal_jemput" id="e_input_tanggal_jemput" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Waktu Penjemputan</label>
                <select name="waktu_penjemputan" id="e_input_waktu" class="form-control">
                    <option value="">-- Pilih Waktu --</option>
                    <option value="09.00 - 13.00">09.00 - 13.00</option>
                    <option value="13.00 - 17.00">13.00 - 17.00</option>
                    <option value="17.00 - 20.00">17.00 - 20.00</option>
                </select>
            </div>
        </div>

        <div class="form-group" style="margin-top: 24px; border-top: 2px dashed #e2e8f0; padding-top: 20px;">
            <label class="form-label" style="color: #15803d;">Update Status Pembayaran</label>
            <select name="status_bayar" id="e_status_bayar" class="form-control" style="border-color: #bbf7d0; background: #f0fdf4;" onchange="toggleCatatanBatal(this.value)">
                <option value="pending">Belum Lunas (Pending)</option>
                <option value="confirmed">Lunas</option>
                <option value="batal">Batal</option>
            </select>
        </div>

        <div class="form-group" id="group_catatan_batal" style="display: none; margin-top: 16px;">
            <label class="form-label" style="color: #dc2626;">Alasan Pembatalan</label>
            <textarea name="catatan_pembatalan" id="e_catatan_pembatalan" class="form-control" rows="2" placeholder="Masukkan alasan kenapa dibatalkan (misal: Bukti transfer palsu)"></textarea>
        </div>

        <div class="form-group" style="margin-top: 16px; margin-bottom: 32px;">
            <label class="form-label" style="color: var(--primary);">Update Status Pengerjaan</label>
            <select name="status_pesanan" id="e_status" class="form-control" style="text-transform: capitalize; border-color: #bfdbfe; background: #eff6ff;">
                <option value="diterima">Diterima</option>
                <option value="dicuci">Dicuci</option>
                <option value="dikeringkan">Dikeringkan</option>
                <option value="finishing">Finishing</option>
                <option value="siap_diambil">Siap Diambil</option>
                <option value="diantar_kurir">Diantar Kurir</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>
        
        <div style="margin-top: 24px; border-top: 2px dashed #e2e8f0; padding-top: 20px;">
            <h3 style="font-size: 1rem; color: #0f172a; margin-bottom: 12px;">Edit Rincian Item</h3>
            <div id="e_items_container" style="display: flex; flex-direction: column; gap: 12px;">
                <!-- Dinamis lewat JS -->
            </div>
        </div>
        
        <div class="modal-footer-btns">
            <button type="button" class="btn-cancel-modal" onclick="tutupModal('modalEdit')">Batal</button>
            <button type="submit" class="btn-save-modal">Simpan Perubahan</button>
        </div>
    </form>
  </div>
</div>

<div id="modalDetail" class="modal-overlay modal-detail">
  <div class="modal-content">
    <button class="btn-close" onclick="tutupModal('modalDetail')">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
    
    <div style="margin-bottom:28px; border-bottom: 2px dashed #e2e8f0; padding-bottom: 24px;">
        <h2 class="edit-title" style="margin-bottom:8px; font-size: clamp(1.2rem, 5vw, 1.6rem); color: #0f172a; word-break: break-all;">
            <span class="label" style="display: block; font-size: 0.85rem; font-weight: 800; color: #64748b; margin-bottom: 4px;">KODE PESANAN:</span> 
            <span class="value mobile-break" id="d_kode"></span>
        </h2>
        <div style="font-size:0.95rem; color:#64748b; font-weight:600;">
            <span class="label" style="display: block; font-size: 0.85rem; margin-bottom: 4px;">Tanggal Pesanan:</span> 
            <span class="value mobile-break" style="color: #0f172a;"><span id="d_tanggal"></span> <span id="d_waktu"></span></span>
        </div>
    </div>

    <div class="form-grid">
        <div>
            <h3 class="section-title">Informasi Pelanggan</h3>
            <div class="form-group"><label class="form-label">Nama Lengkap</label><div class="form-control-readonly" style="height: 52px;" id="d_nama"></div></div>
            <div class="form-group"><label class="form-label">No. WhatsApp</label><div class="form-control-readonly" style="height: 52px;" id="d_nowa"></div></div>
            <div class="form-group"><label class="form-label">Metode Pengiriman</label><div class="form-control-readonly" style="height: 52px; " id="d_pengiriman"></div></div>
            
            <div class="form-group"><label class="form-label">Alamat / Detail Pengiriman</label>
                <textarea class="form-control-readonly" id="d_alamat" style="height: 152px;" readonly></textarea>
            </div>
            
            <div class="form-group" id="d_waktu_jemput_group" style="display:none;">
                <label class="form-label">Waktu Penjemputan</label>
                <div class="form-control-readonly" id="d_waktu_jemput" style="height:52px; font-weight:700; color:#0f172a;"></div>
            </div>

        </div>

        <div>
            <h3 class="section-title">Informasi Tagihan & Status</h3>
            <div class="form-group"><label class="form-label">Total Tagihan</label><div class="form-control-readonly" id="d_total" style="height: 52px; color:#1d4ed8; font-size: 1.2rem; font-weight: 800; background: #eff6ff; border-color: #bfdbfe;"></div></div>
            
            <div class="form-grid" style="gap:16px; margin-bottom:20px;">
                <div class="form-group" style="margin-bottom:0;"><label class="form-label">Metode Pembayaran</label><div class="form-control-readonly" style="height: 52px;" id="d_metode" style="text-transform: uppercase;"></div></div>
                <div class="form-group" style="margin-bottom:0;"><label class="form-label">Status Pembayaran</label><div class="form-control-readonly mobile-left-align" style="height: 52px; justify-content:center;" id="d_status_bayar"></div></div>
            </div>

            <div class="form-group" id="d_alasan_batal_group" style="display: none; margin-bottom: 20px;">
                <label class="form-label" style="color: #dc2626;">Alasan Pembatalan</label>
                <div class="form-control-readonly" style="height: auto; min-height: 52px; background: #fef2f2; border-color: #fca5a5; color: #b91c1c;" id="d_alasan_batal"></div>
            </div>
            
            <div class="form-group"><label class="form-label">Status Pengerjaan Saat Ini</label><div class="form-control-readonly" style="height: 52px;" id="d_status" style="color:#4f46e5;text-transform:capitalize; font-weight: 800; background: #eef2ff; border-color: #c7d2fe;"></div></div>
        </div>
    </div>

    <h3 class="section-title" style="margin-top:16px;">Rincian Item Cucian</h3>
    <div class="form-grid full" style="margin-bottom: 24px;">
        <div class="form-group" style="margin-bottom: 0;">
            <div id="d_layanan_list" style="height: auto; min-height: 80px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
                </div>
        </div>
    </div>

    <div id="d_catatan_area" style="display: none; margin-bottom: 24px;">
        <h3 class="section-title">Catatan Tambahan</h3>
        <div class="form-grid full" style="margin-bottom: 0;">
            <div class="form-group" style="margin-bottom: 0;">
                <div class="form-control-readonly" id="d_catatan" style="height: auto; min-height: 60px; padding: 16px; background: #fff; align-items: flex-start; font-weight: 600; color: #334155; line-height: 1.6;"></div>
            </div>
        </div>
    </div>



  </div>
</div>

<script src="../../assets/js/admin.js?v=<?= time() ?>"></script>
</body>
</html>







