<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
// require_once __DIR__ . '/../../includes/auth.php'; // Aktifkan jika ada
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
    $ts = strtotime($tgl);
    $eng = date('M', $ts);
    return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
}

// --- PROSES AKSI POST (Konfirmasi & Hapus) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $id_pesanan = $_POST['id_pesanan'];

    // 1. Aksi Konfirmasi Pembayaran (Tunai & Transfer)
    if ($_POST['action'] == 'konfirmasi_bayar') {
        // Ambil data pesanan saat ini
        $stmtCek = $db->prepare("SELECT status_bayar, total_harga FROM pesanan WHERE id = ?");
        $stmtCek->execute([$id_pesanan]);
        $pesanan = $stmtCek->fetch(PDO::FETCH_ASSOC);

        if ($pesanan && $pesanan['status_bayar'] == 'pending') {
            // Langsung set status_bayar = confirmed (berlaku untuk tunai & transfer)
            $update = $db->prepare("UPDATE pesanan SET status_bayar = 'confirmed' WHERE id = ?");
            $update->execute([$id_pesanan]);
        }
        
        header('Location: pembayaran.php?msg=confirmed');
        exit;
    }


    // 2. Aksi Tolak Bayar (Batal)
    if ($_POST['action'] == 'batal') {
        $catatan = $_POST['catatan_pembatalan'] ?? null;
        $update = $db->prepare("UPDATE pesanan SET status_pesanan = 'batal', status_bayar = 'batal', catatan_pembatalan = ? WHERE id = ?");
        $update->execute([$catatan, $id_pesanan]);
        
        header('Location: pembayaran.php?msg=batal');
        exit;
    }

    // 3. Aksi Hapus
    if ($_POST['action'] == 'hapus') {
        $deleteDetail = $db->prepare("DELETE FROM detail_pesanan WHERE pesanan_id = ?");
        $deleteDetail->execute([$id_pesanan]);

        $delete = $db->prepare("DELETE FROM pesanan WHERE id = ?");
        $delete->execute([$id_pesanan]);
        
        header('Location: pembayaran.php?msg=deleted');
        exit;
    }
}

// --- FILTER & PENCARIAN ---
$q = $_GET['q'] ?? '';
$filter_bayar = $_GET['status_bayar'] ?? 'semua';

$where_clauses = [];
$params = [];

if ($q !== '') {
    $where_clauses[] = "(p.kode_pesanan LIKE ? OR c.nama LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($filter_bayar == 'pending') {
    $where_clauses[] = "p.status_bayar = 'pending' AND (p.status_pesanan != 'batal' OR p.status_pesanan IS NULL)";
} elseif ($filter_bayar == 'confirmed') {
    $where_clauses[] = "p.status_bayar = 'confirmed'";
} elseif ($filter_bayar == 'batal') {
    $where_clauses[] = "p.status_pesanan = 'batal'";
}

$where_sql = '';
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// --- QUERY UTAMA ---
$stmt = $db->prepare("
    SELECT 
        p.*, 
        c.nama AS nama_pelanggan,
        c.no_wa,
        (SELECT SUM(jumlah) FROM detail_pesanan WHERE pesanan_id = p.id) AS total_item
    FROM pesanan p
    JOIN pelanggan c ON p.pelanggan_id = c.id
    $where_sql
    ORDER BY 
        CASE WHEN p.status_bayar = 'pending' THEN 1 ELSE 2 END,
        p.created_at DESC
");
$stmt->execute($params);
$data_pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pesananIds = array_column($data_pembayaran, 'id');
$pesananDetailsGrouped = [];
if (!empty($pesananIds)) {
    $inQuery = implode(',', array_fill(0, count($pesananIds), '?'));
    $stmtDet = $db->prepare("SELECT dp.pesanan_id, dp.jumlah, dp.harga_satuan, dp.merk_item, dp.ukuran, dp.warna, l.kategori, l.jenis 
                             FROM detail_pesanan dp 
                             JOIN layanan l ON dp.layanan_id = l.id 
                             WHERE dp.pesanan_id IN ($inQuery)");
    $stmtDet->execute($pesananIds);
    $allDetails = $stmtDet->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allDetails as $det) {
        $pesananDetailsGrouped[$det['pesanan_id']][] = $det;
    }
}

foreach ($data_pembayaran as &$o) {
    $details = $pesananDetailsGrouped[$o['id']] ?? [];
    $groupedItems = [];
    foreach ($details as $det) {
        if ($det['kategori'] == 'Extra Treatment') {
            if (count($groupedItems) > 0) {
                $groupedItems[count($groupedItems) - 1]['extras'][] = $det;
            }
        } else {
            $det['extras'] = [];
            $groupedItems[] = $det;
        }
    }
    
    $html = '';
    foreach ($groupedItems as $gi) {
        $html .= '<div style="padding: 6px 0; border-bottom: 1px solid #f1f5f9; line-height: 1.4;">';
        $html .= '<div class="rincian-item-row" style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:4px;">';
        $html .= '<strong class="rincian-item-name" style="color:#0f172a; font-size: 0.95rem; word-break: break-word; flex:1;">• ' . $gi['jumlah'] . 'x ' . htmlspecialchars($gi['kategori']) . '</strong>';
        $html .= '<span class="rincian-item-price desktop-price" style="font-weight:800; color:#1d4ed8; font-size:0.9rem; white-space:nowrap; margin-left: 12px;">Rp ' . number_format($gi['harga_satuan'], 0, ',', '.') . '</span>';
        $html .= '</div>';
        $html .= '<span style="color:#64748b; font-size: 0.8rem; margin-left: 12px;">(Layanan: ' . htmlspecialchars($gi['jenis']) . ')</span><br>';
        
        $merkLine = [];
        if (!empty($gi['merk_item'])) $merkLine[] = htmlspecialchars($gi['merk_item']);
        if (!empty($gi['ukuran'])) $merkLine[] = 'Size ' . htmlspecialchars($gi['ukuran']);
        if (!empty($gi['warna'])) $merkLine[] = htmlspecialchars($gi['warna']);
        
        if (!empty($merkLine)) {
            $html .= '<span style="color:#64748b; font-size: 0.8rem; margin-left: 12px; display:block; word-break: break-word;">' . implode(' | ', $merkLine) . '</span>';
        }
        
        if (!empty($gi['extras'])) {
            $html .= '<div style="margin-top:4px; padding-left:12px;">';
            foreach ($gi['extras'] as $ex) {
                $html .= '<div style="font-size: 0.75rem; font-weight: 600; color: #0284c7; display:flex; justify-content:space-between; align-items:flex-start; width:100%; gap:8px; margin-bottom:4px;">';
                $html .= '<div style="display:flex; align-items:flex-start; gap:6px; line-height:1.4;">';
                $html .= '<span style="font-weight: 800; opacity: 0.7;">|</span>';
                $html .= '<span>Extra: ' . htmlspecialchars($ex['jenis']) . '</span>';
                $html .= '</div>';
                $html .= '<span style="color:#0284c7; white-space:nowrap; flex-shrink:0;">Rp ' . number_format($ex['harga_satuan'], 0, ',', '.') . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        
        $html .= '<span class="mobile-price" style="font-weight:800; color:#1d4ed8;">Rp ' . number_format($gi['harga_satuan'], 0, ',', '.') . '</span>';
        $html .= '</div>';
    }
    $o['detail_layanan'] = $html;
}
unset($o);
?>

<!DOCTYPE html>
<html lang="id" class="page-pembayaran">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kelola Pembayaran | BUP Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css">

<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">

<body>
<div class="admin-page">
    <?php if(file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>

    <div class="admin-content">
        <div class="admin-topbar">
            <h1>Kelola Pembayaran</h1>
        </div>

        <form method="GET" class="filter-container">
            <input type="text" name="q" class="filter-input" placeholder="Cari nama pelanggan atau kode pesanan..." value="<?= htmlspecialchars($q) ?>">
            <select name="status_bayar" class="filter-select">
                <option value="semua" <?= $filter_bayar == 'semua' ? 'selected' : '' ?>>Semua Status</option>
                <option value="pending" <?= $filter_bayar == 'pending' ? 'selected' : '' ?>>Pending (Belum Lunas)</option>
                <option value="confirmed" <?= $filter_bayar == 'confirmed' ? 'selected' : '' ?>>Lunas</option>
                <option value="batal" <?= $filter_bayar == 'batal' ? 'selected' : '' ?>>Dibatalkan</option>
            </select>
            <button type="submit" class="filter-btn">Filter Pencarian</button>
        </form>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="text-align: center; width: 50px;">No</th>
                  <th style="text-align: left;">Tanggal Pesanan</th>
                  <th>Nama Pelanggan</th>
                  <th style="text-align: center;">No. WhatsApp</th>
                  <th style="text-align: center;">Total Tagihan</th>
                  <th style="text-align: center;">Metode Pembayaran</th>
                  <th style="text-align: center;">Bukti</th>
                  <th style="text-align: center;">Status</th>
                  <th style="text-align: center;">Aksi</th>
                </tr>
              </thead>
              <tbody>
              <?php if(count($data_pembayaran) == 0): ?>
                <tr>
                  <td colspan="10" style="text-align:center; padding: 48px; color: #94a3b8; font-weight: 600; font-size: 0.95rem;">Tidak ada data pesanan ditemukan</td>
                </tr>
              <?php endif; ?>

              <?php $no = 1; foreach ($data_pembayaran as $o): 
                  $metode_bersih = strtolower(trim(str_replace('_', ' ', $o['metode_bayar'])));
                  $is_tunai = in_array($metode_bersih, ['tunai', 'cash']);
                  $is_pending = ($o['status_bayar'] == 'pending');
                  
                  // Cek apakah admin sudah klik "Konfirmasi" di tahap awal
                  $is_tunai_tahap1_selesai = ($o['bukti_bayar'] === 'tunai_confirmed');
              ?>
                <tr>
                  <td style="text-align: center; color: #94a3b8; font-weight: 700;"><?= $no++ ?></td>
                  <td style="white-space: nowrap; text-align: left;">
                      <div style="font-size: 0.75rem; font-weight: 700; color: #475569;"><?= tglIndo($o['tanggal_pesan']) ?></div>
                      <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 600; margin-top: 2px;"><?= date('H:i', strtotime($o['created_at'])) ?> WIB</div>
                  </td>
                  <td style="font-weight: 600; color: #334155; font-size: 0.85rem; white-space: nowrap;">
                      <?= htmlspecialchars($o['nama_pelanggan']) ?>
                      <div style="font-size: 0.65rem; font-weight: 800; color: #3b82f6; margin-top: 3px;"><?= htmlspecialchars($o['kode_pesanan']) ?></div>
                  </td>
                  <td style="color: #475569; font-size: 0.75rem; font-weight: 600; white-space: nowrap; text-align: center;">
                      <?= htmlspecialchars($o['no_wa'] ?? '-') ?>
                  </td>
                  <td style="font-weight: 800; color: #0f172a; white-space: nowrap; font-size: 0.85rem; text-align: center;">
                      Rp <?= number_format($o['total_harga'], 0, ',', '.') ?>
                  </td>
                  <td style="text-align: center;" class="text-method">
                      <?= strtoupper($metode_bersih) ?>
                  </td>
                  
                  <td style="text-align: center;">
                    <?php 
                    // Menampilkan bukti bayar JIKA BUKAN flag rahasia kita
                    if (!empty($o['bukti_bayar']) && $o['bukti_bayar'] !== 'tunai_confirmed'): ?>
                        <img src="../../uploads/bukti_bayar/<?= htmlspecialchars($o['bukti_bayar']) ?>" alt="Bukti" class="img-thumbnail" onclick="lihatBukti('../../uploads/bukti_bayar/<?= htmlspecialchars($o['bukti_bayar']) ?>')">
                    <?php else: ?>
                        <span style="font-size: 0.7rem; color: #cbd5e1; font-style: italic; font-weight: 500;">Tidak Ada</span>
                    <?php endif; ?>
                  </td>
                  
                  <td style="text-align: center;">
                    <?php if($o['status_pesanan'] == 'batal'): ?>
                        <span class="badge-status status-batal">Batal</span>
                    <?php elseif($is_pending): ?>
                        <span class="badge-status status-pending">Pending</span>
                    <?php else: ?>
                        <span class="badge-status status-lunas">Lunas</span>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <div class="action-group" style="display: flex; gap: 8px; justify-content: center;">
                        
                        <?php if($is_pending && $o['status_pesanan'] !== 'batal'): ?>
                            <?php
                                // Tunai: 1 tombol langsung "Tandai Lunas" (biru)
                                // Transfer: tombol "Konfirmasi Transfer" (hijau)
                                if ($is_tunai) {
                                    $btn_class   = "btn-lunasi";
                                    $btn_text    = "Tandai Lunas";
                                    $pesan_modal = "Tandai Pembayaran Lunas";
                                } else {
                                    $btn_class   = "btn-konfirmasi";
                                    $btn_text    = "Konfirmasi";
                                    $pesan_modal = "Konfirmasi Bukti Pembayaran Selesai";
                                }
                            ?>

                            <button type="button" class="btn-action <?= $btn_class ?>"
                            data-id="<?= $o['id'] ?>"
                            data-kode="<?= htmlspecialchars($o['kode_pesanan']) ?>"
                            data-nama="<?= htmlspecialchars($o['nama_pelanggan']) ?>"
                            data-metode="<?= strtoupper($metode_bersih) ?>"
                            data-total="Rp <?= number_format($o['total_harga'], 0, ',', '.') ?>"
                            data-ongkir="<?= (!empty($o['waktu_penjemputan'])) ? (($o['ongkir'] == -1) ? 'Diinfokan via WA' : ($o['ongkir'] > 0 ? 'Rp ' . number_format($o['ongkir'], 0, ',', '.') : 'Rp 0')) : 'hide' ?>"
                            data-target-status="confirmed"
                            data-pesan-modal="<?= $pesan_modal ?>"
                            onclick="bukaModalKonfirmasi(this)">
                            <?= $btn_text ?>
                            </button>

                            <template id="detail-<?= $o['id'] ?>">
                                <?= $o['detail_layanan'] ?: '<span style="color:#ef4444; font-size: 0.85rem;">Tidak ada item tercatat</span>' ?>
                            </template>
                        <?php elseif ($o['status_pesanan'] == 'batal'): ?>
                            <span style="color: #ef4444; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; height: 34px; letter-spacing: 0.5px;">DIBATALKAN</span>
                        <?php else: ?>
                            <span style="color: #10b981; font-weight: 800; font-size: 0.75rem; display: flex; align-items: center; height: 34px; letter-spacing: 0.5px;">SELESAI</span>
                        <?php endif; ?>
                        
                        <div style="margin: 0;">
                            <?php if (!$is_tunai && $o['status_pesanan'] !== 'batal' && $is_pending): ?>
                                <button type="button" class="btn-action btn-hapus" onclick="bukaModalBatal('<?= $o['id'] ?>', '<?= htmlspecialchars($o['kode_pesanan']) ?>', '<?= htmlspecialchars($o['nama_pelanggan']) ?>', '<?= strtoupper($metode_bersih) ?>', 'Rp <?= number_format($o['total_harga'], 0, ',', '.') ?>')">Batal</button>
                            <?php else: ?>
                                <form action="" method="POST" style="margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pembayaran ini?')">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id_pesanan" value="<?= $o['id'] ?>">
                                    <button type="submit" class="btn-action btn-hapus">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
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

<style>
/* Tampilkan harga mobile disembunyikan secara default (untuk desktop) */
.mobile-price { display: none; }

@media (max-width: 768px) {
    .modal-content {
        padding: 20px 16px !important; /* Perkecil padding bawaan agar ruang lebih luas */
        width: 92% !important;
        max-width: 100% !important;
    }
    #k-kode, #b-kode {
        font-size: 1.15rem !important; /* Perkecil judul KODE */
    }
    #k-total {
        font-size: 1.25rem !important; /* Perkecil total tagihan */
    }
    .info-box-mobile-left {
        text-align: left !important; /* Paksa rata kiri di mobile */
    }
    /* Desain List Item Khusus Mobile */
    .desktop-price {
        display: none !important; /* Sembunyikan harga atas di mobile */
    }
    .mobile-price {
        display: block !important; /* Munculkan harga bawah di mobile */
        margin-left: 12px !important; /* Sejajarkan dengan rincian di bawahnya */
        margin-top: 6px !important;
        font-size: 0.9rem !important;
    }
    .mobile-price::before {
        content: "Harga: ";
        font-weight: 600;
        color: #64748b;
        font-size: 0.85rem;
    }
}
</style>
<div id="modalKonfirmasi" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="k-kode" style="word-break: break-all;">KODE: -</h2>
        </div>
        <div class="border-dashed"></div>
        
        <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0;">
                <div class="info-box" style="margin: 0; flex: 1; min-width: 130px; word-break: break-word;">
                    <label>Pelanggan</label>
                    <span id="k-nama">-</span>
                </div>
                <div class="info-box info-box-mobile-left" style="margin: 0; text-align: center; flex: 1; min-width: 130px;">
                    <label>Metode Pembayaran</label>
                    <span id="k-metode" style="color: #3b82f6;">-</span>
                </div>
            </div>
            
            <div class="info-box" style="margin: 0;">
                <label>Rincian Cucian (Layanan)</label>
                <div id="k-layanan" style="background: #fff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 8px;">
                    -
                </div>
            </div>
        </div>

        <div id="k-ongkir-box" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; padding: 10px 16px; margin-bottom: 12px; border: 1px dashed #cbd5e1; border-radius: 8px; background: #f8fafc; display: none;">
            <span style="color: #64748b; font-weight: 700; font-size: 0.85rem;">Ongkos Kirim</span>
            <strong id="k-ongkir" style="color: #0f172a; font-size: 0.95rem;">-</strong>
        </div>

        <div style="background: #eff6ff; padding: 16px; border-radius: 12px; text-align: center; border: 1px solid #bfdbfe; margin-bottom: 16px;">
            <div style="color: #2563eb; font-weight: 800; font-size: 0.7rem; letter-spacing: 0.5px; margin-bottom: 4px;">TOTAL TAGIHAN</div>
            <div id="k-total" style="color: #1d4ed8; font-size: 1.6rem; font-weight: 800;">-</div>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="konfirmasi_bayar">
            <input type="hidden" name="id_pesanan" id="k-id">
            <input type="hidden" name="target_status" id="k-target-status" value="confirmed">
            
            <button type="submit" id="btn-modal-submit" class="btn-action btn-konfirmasi" style="width: 100%; height: auto; min-height: 44px; padding: 12px; border-radius: 10px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; white-space: normal; line-height: 1.4;">
                Konfirmasi Pembayaran
            </button>
        </form>
        <button class="btn-close" onclick="tutupModalKonfirmasi()">Tutup</button>
    </div>
</div>

<div id="modalBatal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="b-kode" style="word-break: break-all;">KODE: -</h2>
        </div>
        <div class="border-dashed"></div>

        <form action="" method="POST">
            <input type="hidden" name="action" value="batal">
            <input type="hidden" name="id_pesanan" id="b-id">
            
            <div style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; margin-bottom: 20px; text-align: left; box-shadow: inset 0 2px 4px rgba(239,68,68,0.05);">
                <label style="display: flex; align-items: center; gap: 6px; color: #dc2626; font-size: 0.75rem; font-weight: 800; margin-bottom: 10px; letter-spacing: 0.5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    ALASAN PEMBATALAN
                </label>
                <textarea name="catatan_pembatalan" class="form-control" rows="3" placeholder="Tuliskan detail masalah (cth: Bukti transfer buram / Nominal tidak sesuai)..." required style="width: 100%; font-size: 0.9rem; border: 1px solid #fca5a5; background: #ffffff; border-radius: 8px; padding: 12px; color: #7f1d1d; resize: vertical; box-shadow: 0 1px 2px rgba(0,0,0,0.02); transition: all 0.2s ease;"></textarea>
            </div>
            
            <button type="submit" class="btn-action" style="width: 100%; height: auto; min-height: 44px; padding: 12px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; background: #ef4444; color: #ffffff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); white-space: normal; line-height: 1.4;">
                Konfirmasi Pembatalan
            </button>
        </form>
        <button class="btn-close" onclick="tutupModalBatal()">Tutup</button>
    </div>
</div>

<div id="modalBukti" class="modal-overlay">
    <div class="modal-content" style="text-align: center; padding: 20px;">
        <div class="modal-header" style="border: none; padding: 0; margin-bottom: 16px;">
            <h2 style="font-size: 1.05rem; color: #475569;">BUKTI TRANSFER</h2>
        </div>
        <img id="gambar-bukti" src="" alt="Bukti" style="width: 100%; max-height: 60vh; border-radius: 12px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0;">
        <button class="btn-close" onclick="tutupModalBukti()">Kembali</button>
    </div>
</div>

<script src="../../assets/js/admin.js?v=<?= time() ?>"></script>

</body>
</html>

