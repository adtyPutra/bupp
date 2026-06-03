<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
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
$error_msg = ""; 

// Helper: Format tanggal ke bahasa Indonesia
function tglIndo($tgl) {
    $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei',
              'Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt',
              'Nov'=>'Nov','Dec'=>'Des'];
    $ts = strtotime($tgl); $eng = date('M', $ts);
    return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
}
function tglIndoFull($ts = null) {
    $ts = $ts ?: time();
    $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',
              6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',
              11=>'November',12=>'Desember'];
    return date('j', $ts) . ' ' . ($bulan[(int)date('n',$ts)]) . ' ' . date('Y', $ts);
}

// Proses Simpan Pesanan Baru
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah_pesanan') {
    try {
        $db->beginTransaction();

        // 1. Simpan Data Pelanggan Baru
        $nama = $_POST['nama'] ?? '';
        $no_wa = $_POST['no_wa'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $catatan_umum = trim($_POST['catatan'] ?? '');

        if (empty($nama) || empty($no_wa)) {
            throw new Exception("Nama dan Nomor WhatsApp wajib diisi.");
        }

        $stmtPelanggan = $db->prepare("INSERT INTO pelanggan (nama, no_wa, alamat) VALUES (?, ?, ?)");
        $stmtPelanggan->execute([$nama, $no_wa, $alamat]);
        $pelanggan_id = $db->lastInsertId();

        // 2. Hitung Total Harga (Ongkir + Total Layanan) di Backend
        $metode_pengiriman_id = $_POST['metode_pengiriman'] ?? null;
        $waktu_penjemputan    = trim($_POST['waktu_penjemputan'] ?? '');
        if (!$metode_pengiriman_id) {
            throw new Exception("Metode pengiriman belum dipilih.");
        }

        $ongkir = isset($_POST['ongkir_manual']) ? (int)$_POST['ongkir_manual'] : 0;
        $total_harga = $ongkir;
        $items_to_insert = [];

        if (empty($_POST['kategori']) || !is_array($_POST['kategori'])) {
            throw new Exception("Pesanan minimal harus memiliki 1 item cucian.");
        }

        foreach ($_POST['kategori'] as $index => $kategori) {
            $layanan_id = $_POST['layanan_id'][$index] ?? null;
            $merk = trim($_POST['merk_item'][$index] ?? '');
            $ukuran = trim($_POST['ukuran'][$index] ?? '');
            $warna = trim($_POST['warna'][$index] ?? '');
            
            if (empty($merk)) { $merk = "-"; }
            $jumlah = isset($_POST['jumlah'][$index]) ? (int)$_POST['jumlah'][$index] : 1;

            if (empty($layanan_id)) {
                throw new Exception("Terdapat item yang layanannya belum dipilih.");
            }

            $stmtLayanan = $db->prepare("SELECT harga FROM layanan WHERE id = ?");
            $stmtLayanan->execute([$layanan_id]);
            $harga_satuan = $stmtLayanan->fetchColumn();

            if ($harga_satuan === false) {
                 throw new Exception("Layanan tidak valid ditemukan pada item.");
            }

            $total_harga += ($harga_satuan * $jumlah);

            $items_to_insert[] = [
                'layanan_id' => $layanan_id,
                'merk_item'  => $merk,
                'ukuran'     => $ukuran ?: null,
                'warna'      => $warna ?: null,
                'jumlah'     => $jumlah,
                'harga_satuan' => $harga_satuan
            ];

            // Extra Treatment for this item
            $et_id = $_POST['extra_layanan_id'][$index] ?? null;
            if (!empty($et_id)) {
                $stmtET = $db->prepare("SELECT harga FROM layanan WHERE id = ?");
                $stmtET->execute([$et_id]);
                $harga_et = $stmtET->fetchColumn();
                if ($harga_et !== false) {
                    $total_harga += ($harga_et * $jumlah);
                    $items_to_insert[] = [
                        'layanan_id'   => $et_id,
                        'merk_item'    => $merk,
                        'ukuran'       => $ukuran ?: null,
                        'warna'        => $warna ?: null,
                        'jumlah'       => $jumlah,
                        'harga_satuan' => $harga_et
                    ];
                }
            }
        }

        // 3. Simpan ke Tabel Pesanan Utama (Tanpa kolom catatan)
        $metode_bayar = $_POST['metode_bayar'];
        $status_bayar = $_POST['status_bayar'];
        $status_pesanan = $_POST['status_pesanan'];
        
        $kode_pesanan = generateKodePesanan();

        $stmtPesanan = $db->prepare("
            INSERT INTO pesanan 
            (kode_pesanan, pelanggan_id, metode_pengiriman_id, total_harga, ongkir, metode_bayar, status_bayar, status_pesanan, waktu_penjemputan, tanggal_pesan) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $tanggal_jemput = !empty($_POST['tanggal_jemput']) ? $_POST['tanggal_jemput'] : date('Y-m-d');

        $stmtPesanan->execute([
            $kode_pesanan, $pelanggan_id, $metode_pengiriman_id, $total_harga, $ongkir,
            $metode_bayar, $status_bayar, $status_pesanan,
            ($waktu_penjemputan ?: null),
            $tanggal_jemput
        ]);
        $pesanan_id = $db->lastInsertId();

        // 4. Simpan ke Tabel Detail Pesanan (Memasukkan catatan per item)
        $stmtDetail = $db->prepare("INSERT INTO detail_pesanan (pesanan_id, layanan_id, merk_item, ukuran, warna, jumlah, harga_satuan, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($items_to_insert as $index => $item) {
            $note = ($index === 0) ? $catatan_umum : null;
            $stmtDetail->execute([
                $pesanan_id, 
                $item['layanan_id'], 
                $item['merk_item'], 
                $item['ukuran'],
                $item['warna'],
                $item['jumlah'], 
                $item['harga_satuan'],
                $note 
            ]);
        }

        $db->commit();
        // Mengalihkan ke halaman ini sendiri dengan parameter success_id
        header('Location: ?success_id=' . $pesanan_id);
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error_msg = $e->getMessage();
    }
}

// Mengambil detail pesanan jika ada success_id
$orderInfo = null;
$orderItems = [];
if (isset($_GET['success_id'])) {
    $stmtOrder = $db->prepare("
        SELECT p.*, pel.nama AS pelanggan_nama, pel.no_wa, pel.alamat AS pelanggan_alamat, mp.nama_metode AS pengiriman_nama
        FROM pesanan p
        JOIN pelanggan pel ON p.pelanggan_id = pel.id
        LEFT JOIN metode_pengiriman mp ON p.metode_pengiriman_id = mp.id
        WHERE p.id = ?
    ");
    $stmtOrder->execute([$_GET['success_id']]);
    $orderInfo = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if ($orderInfo) {
        $stmtDetails = $db->prepare("
            SELECT dp.*, l.kategori, l.jenis 
            FROM detail_pesanan dp
            JOIN layanan l ON dp.layanan_id = l.id
            WHERE dp.pesanan_id = ?
        ");
        $stmtDetails->execute([$_GET['success_id']]);
        $orderItems = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);

        $catatan_umum = '-';
        if (!empty($orderItems) && !empty($orderItems[0]['catatan'])) {
            $catatan_umum = $orderItems[0]['catatan'];
        }
    }
}

// BUG FIX: Filter WHERE aktif = 1 dicabut agar data layanan pasti muncul
$layanan = $db->query("SELECT * FROM layanan ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$metode_pengiriman_db = $db->query("SELECT * FROM metode_pengiriman ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tambah Pesanan Baru | BUP Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>
<div class="admin-page page-pesanan-edit">
  <?php if(file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>
  <div class="admin-content">
    <?php if (isset($_GET['success_id']) && $orderInfo): ?>
        <?php $status_bayar_check = strtolower(trim($orderInfo['status_bayar'] ?? 'pending')); ?>
        <div style="max-width: 650px; margin: 0 auto; margin-bottom: 24px;">
            <div style="text-align:center; padding:40px; background:#fff; border-radius:24px; border:1px solid #e2e8f0; margin-bottom: 20px; box-shadow:0 10px 30px rgba(0,0,0,0.03);" class="no-print">
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <div style="background:#dcfce7; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                </div>
                <h2 style="font-family:'Plus Jakarta Sans', sans-serif; font-size:clamp(0.9rem, 5vw, 2rem); font-weight:800; color:#0f172a; margin-bottom:12px; letter-spacing:-0.5px; white-space: nowrap;">Pesanan Berhasil Dibuat!</h2>
                <p style="color:#64748b; margin-bottom:30px; font-size:clamp(0.8rem, 3vw, 1.05rem); line-height:1.6; max-width:600px; margin-left:auto; margin-right:auto;">Pesanan telah berhasil dimasukkan ke sistem. Silakan simpan atau cetak bukti pemesanan di bawah ini jika diperlukan.</p>
                <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                    <button type="button" style="background:#fff; border:1px solid #cbd5e1; border-radius:12px; font-weight:700; color:#334155; display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; cursor:pointer; font-family: 'Plus Jakarta Sans', sans-serif;" onclick="window.print()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        <?= (isset($status_bayar_check) && ($status_bayar_check == 'confirmed' || $status_bayar_check == 'lunas')) ? 'Cetak Bukti Pembayaran' : 'Cetak Bukti Pemesanan' ?>
                    </button>
                    <a href="orders.php" style="background:#3b82f6; color:#fff; text-decoration:none; display: inline-flex; align-items: center; justify-content:center; border-radius:12px; font-weight: 700; padding: 14px 28px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25); font-family: 'Plus Jakarta Sans', sans-serif;">
                        Kembali ke Pesanan
                    </a>
                </div>
            </div>

            <div class="receipt-wrapper" style="position: relative; overflow: hidden;">
                <?php 
                if ($status_bayar_check == 'confirmed' || $status_bayar_check == 'lunas'): 
                ?>
                <!-- Watermark LUNAS -->
                <div id="watermarkLunas" style="
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-35deg);
                    font-size: clamp(2.5rem, 12vw, 6rem);
                    font-weight: 900;
                    color: #16a34a;
                    letter-spacing: 0.15em;
                    user-select: none;
                    pointer-events: none;
                    white-space: nowrap;
                    z-index: 0;
                    font-family: 'Plus Jakarta Sans', sans-serif;
                    border: 6px solid #16a34a !important;
                    padding: 8px 24px;
                    border-radius: 12px;
                    color: #16a34a !important;
                    opacity: 0.25;
                ">LUNAS</div>
                <?php endif; ?>
                
                <div style="position: relative; z-index: 1;">
                <div class="receipt-header">
                    <h3>BUP &ndash; Build Up Play</h3>
                    <p>Jasa Laundry Sepatu</p>
                    <p>Telp/WA: 0812 1181 1577</p>
                </div>
                
                <div class="receipt-title">
                    <?= (isset($status_bayar_check) && ($status_bayar_check == 'confirmed' || $status_bayar_check == 'lunas')) ? 'BUKTI PEMBAYARAN' : 'BUKTI PEMESANAN' ?>
                </div>

                <div class="receipt-grid">
                    <div class="receipt-row">
                        <span class="receipt-label">Kode Pesanan:</span>
                        <span class="receipt-value"><?= htmlspecialchars($orderInfo['kode_pesanan']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Tanggal Pesan:</span>
                        <span class="receipt-value"><?= tglIndo($orderInfo['tanggal_pesan']) . ' ' . date('H:i', strtotime($orderInfo['created_at'])) ?> WIB</span>
                    </div>
                    <div class="receipt-row print-only-row">
                        <span class="receipt-label">Tanggal Cetak:</span>
                        <span class="receipt-value"><?= tglIndoFull() . ' ' . date('H:i') ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Nama Pelanggan:</span>
                        <span class="receipt-value"><?= htmlspecialchars($orderInfo['pelanggan_nama']) ?></span>
                    </div>
                    <div class="receipt-row">
                        <span class="receipt-label">Nomor WhatsApp:</span>
                        <span class="receipt-value"><?= htmlspecialchars($orderInfo['no_wa']) ?></span>
                    </div>
                    
                    <div style="margin-top: 16px; margin-bottom: 8px; font-weight: 800; font-size: 0.85rem; color: #475569;">DETAIL PEMBAYARAN</div>
                    <div class="receipt-row" style="border-bottom: none; padding-bottom: 4px; padding-top: 4px;">
                        <span class="receipt-label">Metode:</span>
                        <span class="receipt-value">
                            <?= $orderInfo['metode_bayar'] == 'tunai' ? 'Tunai (Bayar Ditempat)' : 'Transfer BCA' ?>
                        </span>
                    </div>
                    <div class="receipt-row" style="border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px; padding-top: 4px;">
                        <span class="receipt-label">Status:</span>
                        <span class="receipt-value">
                            <?php 
                                $status_pesanan_val = strtolower(trim($orderInfo['status_pesanan'] ?? ''));
                                $status_bayar_check2 = strtolower(trim($orderInfo['status_bayar'] ?? 'pending'));
                                if ($status_pesanan_val === 'batal') {
                                    echo '<span style="color:#ef4444 !important; font-weight:800;">BATAL</span>';
                                } elseif ($status_bayar_check2 == 'confirmed' || $status_bayar_check2 == 'lunas') {
                                    echo '<span style="color:#10b981 !important; font-weight:800;">LUNAS</span>';
                                } else {
                                    echo '<span style="color:#ea580c !important; font-weight:800;">BELUM LUNAS</span>';
                                }
                            ?>
                        </span>
                    </div>

                    <div style="margin-top:12px; border-top: 2px solid #e2e8f0;">
                    <?php 
                    $grouped_items = [];
                    foreach ($orderItems as $item) {
                        if ($item['kategori'] !== 'Extra Treatment') {
                            $item['extras'] = [];
                            $grouped_items[] = $item;
                        } else {
                            if (!empty($grouped_items)) {
                                $grouped_items[count($grouped_items) - 1]['extras'][] = $item;
                            }
                        }
                    }
                    foreach ($grouped_items as $idx => $c): 
                        $detailsArr = [];
                        if(!empty($c['merk_item']) && $c['merk_item'] !== '-') $detailsArr[] = $c['merk_item'];
                        if(!empty($c['ukuran']) && $c['ukuran'] !== '-') $detailsArr[] = "Size " . $c['ukuran'];
                        if(!empty($c['warna']) && $c['warna'] !== '-') $detailsArr[] = $c['warna'];
                        $detailStr = !empty($detailsArr) ? " (" . implode(' | ', $detailsArr) . ")" : "";
                    ?>
                        <div style="padding: 12px 0; border-bottom: 1px dashed #cbd5e1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span style="font-weight: 900; font-size: 0.95rem; color: #0f172a;">Item #<?= $idx + 1 ?></span>
                                <span style="font-weight: 900; font-size: 0.85rem; color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">x<?= htmlspecialchars($c['jumlah']) ?></span>
                            </div>
                            
                            <div style="display: flex; align-items: flex-start; margin-bottom: 6px;">
                                <span class="receipt-label" style="color: #64748b;">Barang:</span>
                                <div class="receipt-value" style="color: #1e293b;">
                                    <?= htmlspecialchars($c['kategori']) ?>
                                    <?php if (!empty($detailStr)): ?>
                                        <br><span style="font-size: 0.85em; font-weight: 600; color: #64748b;"><?= htmlspecialchars($detailStr) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; margin-bottom: 6px;">
                                <span class="receipt-label" style="color: #64748b;">Layanan:</span>
                                <span class="receipt-value" style="color: #1e293b;"><?= htmlspecialchars($c['jenis']) ?></span>
                            </div>
                            
                            <div style="display: flex;">
                                <span class="receipt-label" style="color: #64748b;">Harga:</span>
                                <span class="receipt-value" style="color: #0f172a;">Rp <?= number_format($c['harga_satuan'] * $c['jumlah'], 0, ',', '.') ?></span>
                            </div>

                            <?php if(!empty($c['extras'])): foreach($c['extras'] as $ext): ?>
                            <div style="display: flex; margin-top: 6px; margin-bottom: 2px;">
                                <span class="receipt-label" style="color: #0284c7;">↳ Extra:</span>
                                <span class="receipt-value" style="color: #0369a1;"><?= htmlspecialchars($ext['jenis']) ?></span>
                            </div>
                            <div style="display: flex;">
                                <span class="receipt-label" style="color: #0284c7;">↳ Harga:</span>
                                <span class="receipt-value" style="color: #0369a1;">Rp <?= number_format($ext['harga_satuan'] * $ext['jumlah'], 0, ',', '.') ?></span>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>

                    <?php if(!empty(trim($catatan_umum)) && trim($catatan_umum) !== '-'): ?>
                    <div class="receipt-row">
                        <span class="receipt-label">Catatan Umum:</span>
                        <span class="receipt-value"><?= htmlspecialchars($catatan_umum) ?></span>
                    </div>
                    <?php endif; ?>

                    <div style="position: relative; z-index: 1;">
                        <div style="margin-top: 20px; margin-bottom: 8px; font-weight: 800; font-size: 0.85rem; color: #475569;">DETAIL PENGIRIMAN</div>
                        <div class="receipt-row" style="border-bottom: none !important; padding-bottom: 4px; padding-top: 4px;">
                            <span class="receipt-label">Metode:</span>
                            <span class="receipt-value"><?= htmlspecialchars($orderInfo['pengiriman_nama']) ?></span>
                        </div>
                        <?php if(!empty($orderInfo['waktu_penjemputan'])): ?>
                        <div class="receipt-row" style="border-bottom: none !important; padding-bottom: 12px; padding-top: 4px;">
                            <span class="receipt-label">Jadwal Jemput:</span>
                            <span class="receipt-value"><?= tglIndo($orderInfo['tanggal_pesan']) ?> <span class="desktop-only">|</span> <span class="mobile-break"><?= htmlspecialchars($orderInfo['waktu_penjemputan']) ?></span></span>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($orderInfo['pelanggan_alamat'])): ?>
                        <div class="receipt-row receipt-row-alamat" style="border-bottom: none !important; padding-bottom: 4px; padding-top: 4px; flex-direction: column !important; align-items: flex-start !important; gap: 4px;">
                            <span class="receipt-label" style="width: auto !important;">Alamat:</span>
                            <span class="receipt-value" style="text-align: left !important; width: 100% !important; font-weight: 500 !important; color: #475569 !important;"><?= nl2br(htmlspecialchars($orderInfo['pelanggan_alamat'])) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(isset($orderInfo['ongkir'])): ?>
                        <div class="receipt-row" style="border-bottom: none !important; padding-bottom: 12px; padding-top: 4px;">
                            <span class="receipt-label">Ongkos Kirim:</span>
                            <?php if ($orderInfo['ongkir'] == -1): ?>
                                <span class="receipt-value" style="color:#ef4444;">Diinfokan via WA</span>
                            <?php else: ?>
                                <span class="receipt-value">Rp <?= number_format($orderInfo['ongkir'], 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="receipt-total">
                    <span class="label">TOTAL HARGA:</span>
                    <span class="value">Rp <?= number_format($orderInfo['total_harga'], 0, ',', '.') ?></span>
                </div>
                </div> <!-- End z-index 1 -->
            </div>
        </div>
        <?php else: ?>
        <div class="admin-topbar">
            <a href="orders.php" class="btn-back" style="order:-1;">Kembali</a>
            <h1 style="order:0;">Tambah Pesanan Manual</h1>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-error">⚠️ Gagal Menyimpan: <?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="orderForm">
            <input type="hidden" name="action" value="tambah_pesanan">
            <input type="hidden" name="ongkir_dinamis" id="input_ongkir_dinamis" value="0">

            <div class="admin-card">
                <h2 class="section-title" style="display:flex; align-items:center; gap:12px; color:#0f172a; border-bottom:2px dashed #e2e8f0; padding-bottom:16px; margin-bottom:24px; font-size:1.25rem;">
                    <div style="background:#e0f2fe; color:#0ea5e9; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem;">1</div>
                    Data Pelanggan
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span>*</span></label>
                        <input type="text" name="nama" class="form-control" required placeholder="Masukkan nama pelanggan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nomor WhatsApp <span>*</span></label>
                        <input type="tel" name="no_wa" class="form-control" required placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div class="form-grid full">
                    <div class="form-group">
                        <label class="form-label">Metode Pengiriman <span>*</span></label>
                        <select name="metode_pengiriman" class="form-control" required onchange="updatePrice(); toggleAlamat(this);">
                            <option value="">-- Pilih Metode Pengiriman --</option>
                            <?php foreach ($metode_pengiriman_db as $mp): ?>
                                <option value="<?= $mp['id'] ?>" data-biaya="<?= $mp['biaya'] ?? ($mp['id'] == 2 ? 15000 : 0) ?>"><?= htmlspecialchars($mp['nama_metode']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="alamatGroup" style="display:none; gap:16px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); background:#f8fafc; padding:16px; border:1.5px solid #cbd5e1; border-radius:12px; margin-top: 16px;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label">Alamat Lengkap <span>*</span></label>
                            <textarea name="alamat" id="inputAlamat" class="form-control" style="min-height:80px;" placeholder="Masukkan alamat untuk penjemputan/pengantaran..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tanggal Penjemputan <span>*</span></label>
                            <input type="date" name="tanggal_jemput" id="inputTanggalJemput" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Waktu Penjemputan <span>*</span></label>
                            <select name="waktu_penjemputan" id="inputWaktuJemput" class="form-control">
                                <option value="">-- Pilih Waktu --</option>
                                <option value="09.00 - 13.00">09.00 - 13.00</option>
                                <option value="13.00 - 17.00">13.00 - 17.00</option>
                                <option value="17.00 - 20.00">17.00 - 20.00</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ongkos Kirim (Manual) <span>*</span></label>
                            <div style="display:flex; align-items:stretch; background:#fff; border:1px solid #cbd5e1; border-radius:12px; overflow:hidden; height: 100%;">
                                <span style="background:#f1f5f9; padding:0 16px; display:flex; align-items:center; color:#475569; font-weight:700; font-size:0.95rem; border-right:1px solid #cbd5e1;">Rp</span>
                                <input type="number" name="ongkir_manual" id="inputOngkirManual" class="form-control" style="border:none; border-radius:0; box-shadow:none; flex:1;" placeholder="0" value="0" min="0" onchange="updatePrice()" onkeyup="updatePrice()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h2 class="section-title" style="display:flex; align-items:center; gap:12px; color:#0f172a; border-bottom:2px dashed #e2e8f0; padding-bottom:16px; margin-bottom:24px; font-size:1.25rem;">
                    <div style="background:#e0f2fe; color:#0ea5e9; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem;">2</div>
                    Rincian Item Cucian
                </h2>
                <div id="dynamicItemsContainer">
                    <div class="item-row">
                        <div class="item-header">
                            <h4 class="item-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                                Item #1
                            </h4>
                        </div>
                        <div class="form-grid">
                            <div class="form-group fg-kategori">
                                <label class="form-label">Jenis Sepatu/Kategori <span>*</span></label>
                                <select name="kategori[]" class="form-control kat-select" required onchange="updateLayananDynamic(this)">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Sneakers">Sneakers</option>
                                    <option value="Boots Shoes">Boots Shoes</option>
                                    <option value="Outdoor Shoes">Outdoor Shoes</option>
                                    <option value="Leather Shoes">Leather Shoes</option>
                                    <option value="Women Shoes">Women Shoes</option>
                                    <option value="Bag">Bag</option>
                                    <option value="Wallet">Wallet</option>
                                    <option value="Sandals">Sandals</option>
                                    <option value="Hat">Hat</option>
                                    <option value="Repaint">Repaint</option>
                                    <option value="Unyellowing">Unyellowing</option>
                                </select>
                            </div>
                            <div class="form-group fg-merek-ukuran">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Merek<span>*</span></label>
                                        <input type="text" name="merk_item[]" class="form-control merk-input" required placeholder="cth: Nike" style="width: 100%;">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Ukuran <span>*</span></label>
                                        <input type="text" name="ukuran[]" class="form-control ukuran-input" required placeholder="cth: 42" style="width: 100%;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group fg-layanan">
                                <label class="form-label">Jenis Layanan <span>*</span></label>
                                <select name="layanan_id[]" class="form-control lay-select" required onchange="updatePrice(); checkExtraTreatmentPrompt(this.closest('.item-row'));">
                                    <option value="">-- Pilih Kategori Dulu --</option>
                                </select>
                            </div>
                            <div class="form-group fg-jumlah-warna">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label jum-label">Jumlah Item <span>*</span></label>
                                        <input type="number" name="jumlah[]" class="form-control jum-input" required min="1" value="1" onchange="updatePrice()">
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label">Warna <span>*</span></label>
                                        <input type="text" name="warna[]" class="form-control warna-input" required placeholder="cth: Hitam" style="width: 100%;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group fg-extra" style="grid-column: 1 / -1; margin-top: -8px;">
                                <div class="extra-treatment-box" style="display:none; background:linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1.5px solid #7dd3fc; border-radius:10px; padding:14px;">
                                    <p style="margin:0 0 10px 0; font-size:0.9rem; font-weight:700; color:#0369a1;">Ingin menambahkan Extra Treatment?</p>
                                    <div style="display:flex; gap:8px;">
                                        <button type="button" class="btn-et-ya" onclick="pilihExtraTreatment(this,true)" style="flex:1; padding:8px 12px; background:#0ea5e9; color:white; border:none; border-radius:7px; font-size:0.85rem; font-weight:700; cursor:pointer; transition:0.2s;">Ya, Tambahkan</button>
                                        <button type="button" class="btn-et-tidak" onclick="pilihExtraTreatment(this,false)" style="flex:1; padding:8px 12px; background:#e2e8f0; color:#475569; border:none; border-radius:7px; font-size:0.85rem; font-weight:700; cursor:pointer; transition:0.2s;">Tidak</button>
                                    </div>
                                    <div class="et-select-box" style="display:none; margin-top:14px;">
                                        <label style="font-size:0.85rem; font-weight:600; color:#0369a1; display:block; margin-bottom:6px;">Jenis Extra Treatment <span style="color:red;">*</span></label>
                                        <select name="extra_layanan_id[]" class="form-control et-select" onchange="updatePrice()" style="width:100%;">
                                            <option value="">-- Pilih Extra Treatment --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-tambah" onclick="tambahItem()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Tambah Item Lainnya
                </button>
                <div class="price-box">
                    <div>
                        <div class="price-label">Estimasi Total Harga</div>
                    </div>
                    <div class="price-value" id="priceEst">Pilih layanan terlebih dahulu</div>
                </div>
            </div>

            <div class="admin-card">
                <h2 class="section-title" style="display:flex; align-items:center; gap:12px; color:#0f172a; border-bottom:2px dashed #e2e8f0; padding-bottom:16px; margin-bottom:24px; font-size:1.25rem;">
                    <div style="background:#e0f2fe; color:#0ea5e9; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:1.1rem;">3</div>
                    Pembayaran & Status
                </h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Metode Pembayaran <span>*</span></label>
                        <select name="metode_bayar" class="form-control" required>
                            <option value="tunai">Tunai (Kasir)</option>
                            <option value="transfer_bca">Transfer BCA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Pembayaran <span>*</span></label>
                        <select name="status_bayar" class="form-control" required>
                            <option value="pending">Belum Lunas</option>
                            <option value="confirmed">Lunas</option>
                            <option value="batal">Batal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Pengerjaan Awal <span>*</span></label>
                        <select name="status_pesanan" class="form-control" required>
                            <option value="diterima">Diterima</option>
                            <option value="diproses">Langsung Diproses</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid full" style="margin-top: 20px;">
                    <div class="form-group">
                        <label class="form-label">Catatan Tambahan (Opsional)</label>
                        <textarea name="catatan" class="form-control" placeholder="Opsional, misal instruksi khusus..." style="min-height: 80px;"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn-submit">Simpan & Buat Pesanan</button>
            </div>
        </form>
        <?php endif; ?>
  </div>
</div>
<?php if (!isset($_GET['success_id'])): ?>
<script>
const DATA_LAYANAN = [
    <?php foreach ($layanan as $l): ?>
    {
        id: "<?= $l['id'] ?>",
        kategori: <?= json_encode($l['kategori']) ?>,
        nama: <?= json_encode($l['jenis']) ?>, 
        harga: <?= (int)$l['harga'] ?>
    },
    <?php endforeach; ?>
];
</script>
<?php endif; ?>
<script src="../../assets/js/admin.js?v=<?= time() ?>"></script>
</body>
</html>





