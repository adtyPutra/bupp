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
$action = $_GET['action'] ?? 'view';

// Helper: Format tanggal ke bahasa Indonesia
function tglIndo($tgl) {
    $bulan = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','May'=>'Mei',
              'Jun'=>'Jun','Jul'=>'Jul','Aug'=>'Agu','Sep'=>'Sep','Oct'=>'Okt',
              'Nov'=>'Nov','Dec'=>'Des'];
    $ts = strtotime($tgl); $eng = date('M', $ts);
    return str_replace($eng, $bulan[$eng] ?? $eng, date('d M Y', $ts));
}

// --- API UNTUK GRAFIK CHART.JS ---
if ($action === 'data') {
    header('Content-Type: application/json');
    $tahun_filter = (int)($_GET['tahun'] ?? date('Y'));

    $monthly = $db->prepare("SELECT MONTH(tanggal_pesan) as bln, COUNT(*) as total, 
                             SUM(CASE WHEN status_bayar = 'confirmed' THEN total_harga ELSE 0 END) as revenue 
                             FROM pesanan 
                             WHERE YEAR(tanggal_pesan) = ? 
                             GROUP BY bln");
    $monthly->execute([$tahun_filter]);
    $rows = $monthly->fetchAll(PDO::FETCH_ASSOC);

    $monthData = array_fill(1, 12, ['total' => 0, 'revenue' => 0]);
    foreach ($rows as $r) {
        $monthData[(int)$r['bln']] = ['total' => (int)$r['total'], 'revenue' => (int)$r['revenue']];
    }

    echo json_encode([
        'success' => true,
        'monthly' => array_values($monthData)
    ]);
    exit;
}

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// 1. AMBIL DATA UTAMA PESANAN
$stmt = $db->prepare("
    SELECT p.*, pel.nama AS nama_pelanggan, pel.no_wa, pel.alamat, mp.nama_metode AS metode_pengiriman
    FROM pesanan p 
    JOIN pelanggan pel ON p.pelanggan_id = pel.id 
    LEFT JOIN metode_pengiriman mp ON p.metode_pengiriman_id = mp.id
    WHERE MONTH(p.tanggal_pesan) = ? AND YEAR(p.tanggal_pesan) = ? 
    ORDER BY p.tanggal_pesan ASC
");
$stmt->execute([$bulan, $tahun]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. AMBIL DETAIL ITEM UNTUK SETIAP PESANAN
if (count($orders) > 0) {
    $order_ids = array_column($orders, 'id');
    $in = str_repeat('?,', count($order_ids) - 1) . '?';
    
    $details_stmt = $db->prepare("
        SELECT dp.pesanan_id, dp.merk_item, dp.ukuran, dp.warna, dp.jumlah, dp.harga_satuan, 
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

    foreach ($orders as &$pesanan) {
        $pesanan['items'] = $details_grouped[$pesanan['id']] ?? [];
    }
}

// --- FITUR UNDUH EXCEL (CSV) ---
if ($action === 'export_excel') {
    $filename = "Laporan_Pendapatan_BUP_{$bulan}_{$tahun}.csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    // Tambahkan BOM agar Excel mengenali karakter UTF-8
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Header Kolom Excel
    fputcsv($output, ['No', 'Kode Pesanan', 'Tanggal Pesan', 'Pelanggan', 'No WhatsApp', 'Alamat', 'Metode Pengiriman', 'Rincian Item Cucian', 'Total Tagihan', 'Metode Pembayaran', 'Status Pembayaran', 'Status Pengerjaan']);
    
    foreach ($orders as $i => $o) {
        // Gabungkan rincian item menjadi teks bersusun
        $rincian_arr = [];
        foreach($o['items'] as $item) {
            $base_str = $item['kategori'] . " - " . $item['nama_layanan'] . " (x" . $item['jumlah'] . ")";
            if ($item['kategori'] !== 'Extra Treatment') {
                $detailStr = [];
                if (!empty($item['merk_item'])) $detailStr[] = $item['merk_item'];
                if (!empty($item['ukuran'])) $detailStr[] = "Size " . $item['ukuran'];
                if (!empty($item['warna'])) $detailStr[] = $item['warna'];
                if (!empty($detailStr)) {
                    $base_str .= " [" . implode(' | ', $detailStr) . "]";
                }
            }
            $rincian_arr[] = $base_str;
        }
        $rincian_text = implode("\n", $rincian_arr);
        
        $alamat = !empty($o['alamat']) ? $o['alamat'] : 'Antar & Ambil di Toko';
        $metode_pengiriman = !empty($o['metode_pengiriman']) ? $o['metode_pengiriman'] : '-';
        $metode = strtoupper(str_replace('_', ' ', $o['metode_bayar']));
        $status_bayar = ($o['status_bayar'] == 'confirmed') ? 'LUNAS' : 'BELUM LUNAS';
        $status_pesanan = strtoupper($o['status_pesanan']);
        
        fputcsv($output, [
            $i + 1,
            $o['kode_pesanan'],
            date('d M Y', strtotime($o['tanggal_pesan'])),
            $o['nama_pelanggan'],
            $o['no_wa'],
            $alamat,
            $metode_pengiriman,
            $rincian_text,
            $o['total_harga'],
            $metode,
            $status_bayar,
            $status_pesanan
        ]);
    }
    fclose($output);
    exit;
}

// 3. QUERY UNTUK LAYANAN TERLARIS BULAN INI
$top_services_stmt = $db->prepare("
    SELECT l.jenis, l.kategori, SUM(dp.jumlah) as total_terjual
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.pesanan_id = p.id
    JOIN layanan l ON dp.layanan_id = l.id
    WHERE MONTH(p.tanggal_pesan) = ? AND YEAR(p.tanggal_pesan) = ? AND p.status_bayar = 'confirmed'
    GROUP BY l.id
    ORDER BY total_terjual DESC
    LIMIT 5
");
$top_services_stmt->execute([$bulan, $tahun]);
$top_services = $top_services_stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. HITUNG METRIK DASHBOARD
$total_revenue = 0;
$total_pesanan = count($orders);
$total_selesai = 0;

foreach ($orders as $o) {
    if ($o['status_bayar'] === 'confirmed') {
        $total_revenue += $o['total_harga'];
    }
    if (in_array(strtolower($o['status_pesanan']), ['selesai', 'diambil'])) {
        $total_selesai++;
    }
}

$total_proses = $total_pesanan - $total_selesai;
$bulan_nama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>
<!DOCTYPE html>
<html lang="id" class="page-reports">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan & Performa | BUP Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/main.css">
    
    <link rel="stylesheet" href="../../assets/css/admin.css?v=<?= time() ?>">
</head>
<body>

    <div class="admin-page">
        <?php if(file_exists('partials/sidebar.php')) include 'partials/sidebar.php'; ?>

        <div class="admin-content">
            <style>
        /* Penyesuaian Topbar Khusus Laporan di Mobile */
        @media (max-width: 768px) {
            .admin-content .topbar {
                padding-top: 8px !important; /* Tambah sedikit jarak atas agar lebih turun */
                padding-left: 0 !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 16px !important;
            }
            .page-title h1 {
                margin-top: 0 !important;
                margin-left: 0 !important;
                margin-bottom: 4px !important;
            }
            .page-title p {
                margin-left: 0 !important;
                font-size: 0.95rem !important;
            }
        }
        </style>
        <div class="topbar">
                <div class="page-title">
                    <h1>Laporan Pemesanan</h1>
                    <p>Ringkasan Pendapatan dan Rincian Pesanan</p>
                </div>
            </div>

            <div class="filter-section">
                <form class="filter-form" method="GET" action="">
                    <select name="bulan">
                        <?php for($m=1; $m<=12; ++$m): ?>
                            <option value="<?= sprintf("%02d", $m) ?>" <?= $bulan == sprintf("%02d", $m) ? 'selected' : '' ?>><?= $bulan_nama[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun">
                        <?php for($y=date('Y')-2; $y<=date('Y')+1; ++$y): ?>
                            <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-filter">Filter Data</button>
                </form>
                
                <a href="?action=export_excel&bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="btn-print">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                    Unduh File Excel
                </a>
            </div>

            <div class="dashboard-cards">
                <div class="card c-blue">
                    <div class="card-content">
                        <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <div class="value"><?= $total_pesanan ?></div>
                            <div class="label">Total Order</div>
                        </div>
                    </div>
                </div>

                <div class="card c-green">
                    <div class="card-content">
                        <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <div class="value"><?= $total_selesai ?></div>
                            <div class="label">Selesai</div>
                        </div>
                    </div>
                </div>

                <div class="card c-orange">
                    <div class="card-content">
                        <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <div class="value"><?= $total_proses ?></div>
                            <div class="label">Diproses</div>
                        </div>
                    </div>
                </div>

                <div class="card c-purple">
                    <div class="card-content">
                        <div style="background: rgba(255,255,255,0.25); border-radius: 14px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2" ry="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: center;">
                            <div class="value"><?= function_exists('rupiah') ? rupiah($total_revenue) : 'Rp ' . number_format($total_revenue, 0, ',', '.') ?></div>
                            <div class="label">Pendapatan</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="middle-grid">
                <div class="box-panel">
                    <h3 class="box-title">Tren Pendapatan Tahun <?= $tahun ?></h3>
                    <div class="chart-container">
                        <canvas id="grafikPendapatan"></canvas>
                    </div>
                </div>
                
                <div class="box-panel">
                    <h3 class="box-title">Layanan Paling Laris</h3>
                    <ul class="top-service-list">
                        <?php if(empty($top_services)): ?>
                            <li style="color: #64748b; font-style: italic; font-size: 0.9rem; justify-content:center;">Belum ada layanan di bulan ini.</li>
                        <?php else: ?>
                            <?php foreach($top_services as $ts): ?>
                            <li>
                                <div>
                                    <div class="ts-name"><?= htmlspecialchars($ts['jenis']) ?></div>
                                    <div class="ts-cat"><?= htmlspecialchars($ts['kategori']) ?></div>
                                </div>
                                <div class="ts-count"><?= $ts['total_terjual'] ?> Pcs</div>
                            </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="box-panel">
                <h3 class="box-title" style="margin-bottom: 24px;">Rincian Pesanan</h3>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th style="text-align: center; width:50px;">No</th>
                                <th style="text-align: left;">Tanggal Pesanan</th>
                                <th style="text-align: left;">Kode Pesanan</th>
                                <th style="text-align: left;">Nama Pelanggan</th>
                                <th style="text-align: left;">No WhatsApp</th>
                                <th style="text-align: center;">Rincian Item Cucian</th>
                                <th style="text-align: left;">Total Tagihan</th>
                                <th style="text-align: center;">Status Pembayaran</th>
                                <th style="text-align: center;">Status Kerja</th>
                                <th style="text-align: center;">Metode Pembayaran</th>
                                <th style="text-align: center;">Metode Pengiriman</th>
                                <th style="text-align: left;">Waktu Penjemputan</th>
                                <th style="text-align: center;">Alamat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($orders)): ?>
                                <tr><td colspan="12" style="text-align:center; padding:60px; color:#94a3b8; font-weight: 500;">Belum ada pesanan yang sesuai kriteria.</td></tr>
                            <?php endif; ?>

                            <?php foreach ($orders as $i => $o): 
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

                                $item_string = '<div style="display: flex; flex-direction: column; gap: 12px; padding: 4px 0;">';
                                foreach ($grouped_items as $item) {
                                    $item_string .= '<div>';
                                    if ($item['kategori'] !== 'Extra Treatment') {
                                        $detailStr = [];
                                        if (!empty($item['merk_item'])) $detailStr[] = htmlspecialchars($item['merk_item']);
                                        if (!empty($item['ukuran'])) $detailStr[] = "Size " . htmlspecialchars($item['ukuran']);
                                        if (!empty($item['warna'])) $detailStr[] = htmlspecialchars($item['warna']);
                                        $detailText = !empty($detailStr) ? implode(' | ', $detailStr) : '';

                                        $item_string .= '<div style="font-size: 0.85rem; font-weight: 700; color: #1e293b; line-height: 1.4;">' . htmlspecialchars($item['kategori']) . ' - ' . htmlspecialchars($item['nama_layanan']) . ' <span style="font-weight: 800; color: #475569;">(x' . (int)$item['jumlah'] . ')</span></div>';
                                        
                                        if ($detailText) {
                                            $item_string .= '<div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">' . $detailText . '</div>';
                                        }
                                    } else {
                                        $item_string .= '<div style="font-size: 0.85rem; font-weight: 600; color: #475569;">+ Extra: ' . htmlspecialchars($item['nama_layanan']) . ' (x' . (int)$item['jumlah'] . ')</div>';
                                    }

                                    if (!empty($item['extras'])) {
                                        $item_string .= '<div style="margin-top: 4px; padding-left: 8px; border-left: 2px solid #cbd5e1; display: flex; flex-direction: column; gap: 2px;">';
                                        foreach ($item['extras'] as $ext) {
                                            $item_string .= '<div style="font-size: 0.8rem; font-weight: 600; color: #64748b;">| Extra: ' . htmlspecialchars($ext['nama_layanan']) . '</div>';
                                        }
                                        $item_string .= '</div>';
                                    }
                                    $item_string .= '</div>';
                                }
                                if (empty($grouped_items)) {
                                    $item_string .= '<div style="color:#94a3b8; font-size:0.82rem; font-style:italic;">Tidak ada item</div>';
                                }
                                $item_string .= '</div>';
                            ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: #94a3b8;"><?= $i + 1 ?></td>
                                
                                <td style="white-space: nowrap;">
                                    <div style="font-size: 0.85rem; font-weight: 700; color: #475569;">
                                        <?= tglIndo(date('Y-m-d', strtotime($o['created_at']))) ?>
                                    </div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight: 600; margin-top: 2px;">
                                        <?= date('H:i', strtotime($o['created_at'])) ?> WIB
                                    </div>
                                </td>

                                <td style="white-space: nowrap;">
                                    <strong style="color:#0f172a; font-size: 0.95rem;"><?= htmlspecialchars($o['kode_pesanan']) ?></strong>
                                </td>
                                
                                <td style="white-space: nowrap;">
                                    <strong style="color:#0f172a; font-size: 0.95rem;"><?= htmlspecialchars($o['nama_pelanggan']) ?></strong>
                                </td>

                                <td style="white-space: nowrap; color: #475569; font-size: 0.85rem; font-weight: 600;">
                                    <?= htmlspecialchars($o['no_wa'] ?? '-') ?>
                                </td>

                                <td style="line-height: 1.4; font-size: 0.85rem; white-space: normal; min-width: 250px;">
                                    <?= $item_string ?>
                                </td>
                                
                                <td style="white-space: nowrap;">
                                    <strong style="font-weight: 800; color: #0f172a; font-size: 1rem;">
                                        <?= function_exists('rupiah') ? rupiah($o['total_harga']) : 'Rp ' . number_format($o['total_harga'], 0, ',', '.') ?>
                                    </strong>
                                </td>

                                <td style="white-space: nowrap; text-align: center;">
                                    <?php
                                        $sb = $o['status_bayar'];
                                        $isLunas = ($sb === 'confirmed' || $sb === 'cash');
                                    ?>
                                    <span style="font-size:0.85rem; font-weight:600; color:#0f172a;">
                                        <?= $isLunas ? 'Lunas' : 'Belum Lunas' ?>
                                    </span>
                                </td>

                                <td style="white-space: nowrap; text-align: center;">
                                    <span style="font-size:0.85rem; font-weight:600; color:#0f172a;">
                                        <?= ucwords(str_replace('_', ' ', htmlspecialchars($o['status_pesanan']))) ?>
                                    </span>
                                </td>

                                <td style="white-space: nowrap; text-align: center;">
                                    <div style="font-size: 0.85rem; font-weight: 600; color: #0f172a;">
                                        <?= strtoupper(str_replace('_', ' ', htmlspecialchars($o['metode_bayar']))) ?>
                                    </div>
                                </td>



                                <td style="white-space: nowrap;">
                                    <div style="font-size: 0.85rem; font-weight: 600; color: #475569;">
                                        <?= htmlspecialchars($o['metode_pengiriman'] ?? '-') ?>
                                    </div>
                                </td>

                                <td style="text-align: center; white-space: nowrap;">
                                    <?php if (!empty($o['waktu_penjemputan'])): ?>
                                        <div style="font-weight: 700; color: #0f172a; font-size: 0.85rem;">
                                            <?= tglIndo($o['tanggal_pesan']) ?>
                                        </div>
                                        <div style="color: #64748b; font-weight: 600; font-size: 0.8rem; margin-top: 2px;">
                                            <?= htmlspecialchars($o['waktu_penjemputan']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 0.85rem; font-weight: 500;">-</span>
                                    <?php endif; ?>
                                </td>

                                <td style="min-width: 200px; max-width: 250px; white-space: normal;">
                                    <?php if (!empty($o['alamat'])): ?>
                                        <div style="color: #475569; font-size: 0.85rem; font-weight: 500; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($o['alamat']) ?>">
                                            <?= htmlspecialchars($o['alamat']) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="color: #94a3b8; font-size: 0.85rem; font-style: italic; font-weight: 600;">
                                            Antar &amp; Ambil di Toko
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/js/admin.js"></script>
    <script>
        const urlAPI = '?action=data&tahun=<?= $tahun ?>';
        initChart(urlAPI);
    </script>

</body>
</html>


