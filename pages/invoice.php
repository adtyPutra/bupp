<?php
// pages/invoice.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$kode = $_GET['kode'] ?? '';
if (!$kode) {
    die("Kode pesanan tidak valid.");
}

$db = db();

$stmt = $db->prepare("
    SELECT 
        p.*, 
        pel.nama AS nama_pelanggan, pel.no_wa, pel.alamat
    FROM pesanan p
    LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.id
    WHERE p.kode_pesanan = ?
");
$stmt->execute([$kode]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

$stmtItems = $db->prepare("
    SELECT dp.*, CONCAT(l.kategori, ' - ', l.jenis) AS nama_layanan
    FROM detail_pesanan dp
    LEFT JOIN layanan l ON dp.layanan_id = l.id
    WHERE dp.pesanan_id = ?
");
$stmtItems->execute([$order['id']]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Logic for status
$isBatal = ($order['status_pesanan'] === 'batal');
$isLunas = ($order['status_bayar'] === 'confirmed');

$statusText = '';
$statusBadgeClass = '';

if ($isBatal) {
    $statusText = 'Dibatalkan';
    $statusBadgeClass = 'b-batal';
} elseif ($isLunas) {
    $statusText = 'Lunas';
    $statusBadgeClass = 'b-lunas';
} else {
    if ($order['metode_bayar'] === 'transfer_bca') {
        if (!empty($order['bukti_bayar'])) {
            $statusText = 'Menunggu Konfirmasi';
            $statusBadgeClass = 'b-menunggu';
        } else {
            $statusText = 'Belum Lunas';
            $statusBadgeClass = 'b-belum';
        }
    } else {
        $statusText = 'Belum Lunas';
        $statusBadgeClass = 'b-belum';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($order['kode_pesanan']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; margin: 0; padding: 40px 20px; color: #334155; }
        .invoice-wrapper { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
        
        .inv-header { background: #0f172a; color: #fff; padding: 30px 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .inv-logo { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 1.5rem; letter-spacing: -0.5px; }
        .inv-logo img { height: 40px; filter: brightness(0) invert(1); }
        .inv-title { text-align: right; }
        .inv-title h1 { margin: 0; font-size: 1.8rem; font-weight: 800; }
        .inv-title p { margin: 4px 0 0; color: #94a3b8; font-size: 0.95rem; }

        .inv-body { padding: 40px; }
        .inv-info-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 40px; margin-bottom: 40px; }
        .inv-info-col { flex: 1; min-width: 250px; }
        .info-label { font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
        
        .customer-card { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .customer-name { font-weight: 800; font-size: 1.1rem; color: #0f172a; margin-bottom: 4px; }
        .customer-detail { font-size: 0.9rem; margin-bottom: 4px; display: flex; align-items: flex-start; gap: 8px; }
        
        .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .detail-table th { text-align: left; padding: 8px 0; border-bottom: 2px dashed #cbd5e1; font-size: 0.8rem; color: #64748b; font-weight: 600; }
        .detail-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; font-weight: 500; }
        .detail-table td strong { color: #0f172a; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; }
        .b-batal { background: #fef2f2; color: #b91c1c; }
        .b-lunas { background: #dcfce7; color: #166534; }
        .b-menunggu { background: #eff6ff; color: #1d4ed8; }
        .b-belum { background: #fef3c7; color: #b45309; }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        .items-table th { background: #f8fafc; text-align: left; padding: 16px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 16px; border-bottom: 1px solid #f1f5f9; }
        .item-name { font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .item-meta { font-size: 0.8rem; color: #64748b; }
        
        .inv-footer { display: flex; justify-content: flex-end; }
        .total-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; width: 300px; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.95rem; }
        .total-row.grand { border-top: 2px dashed #cbd5e1; padding-top: 16px; margin-top: 16px; font-size: 1.25rem; font-weight: 800; color: #b91c1c; }

        .btn-print { display: inline-flex; align-items: center; gap: 8px; background: #0f172a; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 50px; font-weight: 600; transition: 0.2s; margin-top: 20px; }
        .btn-print:hover { background: #1e293b; }

        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { padding: 0; background: #fff; }
            .invoice-wrapper { box-shadow: none; border: 2px solid #cbd5e1; max-width: 100%; }
            .no-print { display: none !important; }
        }
        
        .btn-kembali { 
            background: white; color: #475569; border: 1.5px solid #cbd5e1; 
            padding: 10px 24px; border-radius: 50px; text-decoration: none; 
            font-weight: 700; display: inline-flex; align-items: center; gap: 10px; 
            font-size: 0.95rem; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            box-shadow: 0 2px 4px rgba(0,0,0,0.02); 
        }
        .btn-kembali:hover { 
            background: #0f172a; color: white; border-color: #0f172a; 
            box-shadow: 0 8px 15px rgba(15,23,42,0.15); transform: translateY(-2px); 
        }
        .btn-kembali i { transition: transform 0.3s ease; }
        .btn-kembali:hover i { transform: translateX(-4px); }

        .stempel-lunas-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-15deg);
            font-size: clamp(60px, 10vw, 120px);
            font-weight: 900;
            color: rgba(34, 197, 94, 0.06);
            border: clamp(5px, 1vw, 10px) solid rgba(34, 197, 94, 0.06);
            padding: 20px 40px;
            border-radius: 30px;
            pointer-events: none;
            z-index: 10;
            user-select: none;
        }
        .inv-content-wrap { position: relative; z-index: 1; }
    </style>
</head>
<body>

<div class="no-print" style="max-width: 900px; margin: 0 auto 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="javascript:history.back()" class="btn-kembali"><i class="fas fa-arrow-left"></i> Kembali</a>
    <a href="#" onclick="window.print(); return false;" class="btn-print" style="margin-top: 0; padding: 10px 20px; font-size: 0.9rem;"><i class="fas fa-print"></i> Cetak Invoice</a>
</div>

<div class="invoice-wrapper">
    <div class="inv-header">
        <div class="inv-logo">
            <img src="../assets/img/logo_bup.png" alt="BUP Laundry" onerror="this.src='../../assets/img/logo_bup.png'">
            BUP Laundry
        </div>
        <div class="inv-title">
            <h1>INVOICE</h1>
            <p><?= htmlspecialchars($order['kode_pesanan']) ?></p>
        </div>
    </div>

    <div class="inv-body" style="position: relative;">
        
        <?php if ($isLunas): ?>
        <div class="stempel-lunas-watermark">LUNAS</div>
        <?php endif; ?>

        <div class="inv-content-wrap">
            <?php if ($isBatal): ?>
            <div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin-bottom: 30px;">
                <div style="color: #b91c1c; font-weight: 800; font-size: 0.9rem; margin-bottom: 4px;"><i class="fas fa-exclamation-triangle"></i> PESANAN DIBATALKAN</div>
                <div style="color: #7f1d1d; font-size: 0.9rem;"><strong>Alasan:</strong> <?= htmlspecialchars($order['catatan_pembatalan'] ?: 'Dibatalkan oleh Admin') ?></div>
            </div>
            <?php endif; ?>

        <div class="inv-info-row">
            <div class="inv-info-col">
                <div class="info-label">Ditagihkan Kepada:</div>
                <div class="customer-card">
                    <div class="customer-name"><?= htmlspecialchars($order['nama_pelanggan']) ?></div>
                    <div class="customer-detail"><i class="fab fa-whatsapp" style="color:#22c55e; margin-top:3px;"></i> <?= htmlspecialchars($order['no_wa']) ?></div>
                    <?php if ($order['metode_pengiriman_id'] == 2 && !empty(trim($order['alamat']))): ?>
                    <div class="customer-detail"><i class="fas fa-map-marker-alt" style="color:#ef4444; margin-top:3px;"></i> <?= nl2br(htmlspecialchars($order['alamat'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="inv-info-col">
                <div class="info-label">Detail Transaksi:</div>
                <table class="detail-table">
                    <tr>
                        <td>Tanggal Pesan</td>
                        <td align="right"><strong><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Metode Bayar</td>
                        <td align="right"><strong><?= strtoupper(str_replace('_', ' ', $order['metode_bayar'])) ?></strong></td>
                    </tr>
                    <tr>
                        <td style="<?= $order['metode_bayar'] === 'transfer_bca' ? 'border-bottom: 1px solid #f1f5f9;' : 'border:none;' ?>">Status Pembayaran</td>
                        <td align="right" style="<?= $order['metode_bayar'] === 'transfer_bca' ? 'border-bottom: 1px solid #f1f5f9;' : 'border:none;' ?>"><span class="badge <?= $statusBadgeClass ?>"><?= $statusText ?></span></td>
                    </tr>
                    <?php if ($order['metode_bayar'] === 'transfer_bca'): ?>
                    <tr>
                        <td style="border-bottom: 1px solid #f1f5f9; color: #64748b;">Nomor Rekening</td>
                        <td align="right" style="border-bottom: 1px solid #f1f5f9;">
                            <strong>5891029111</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="border:none; color: #64748b;">Atas Nama</td>
                        <td align="right" style="border:none;">
                            <strong>ANNIDA NURUL ISLAMI</strong>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi Layanan</th>
                    <th style="text-align: right;">Harga</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal_items = 0;
                foreach ($items as $item): 
                    $sub = $item['harga_satuan'] * $item['jumlah'];
                    $subtotal_items += $sub;
                ?>
                <tr>
                    <td>
                        <div class="item-name"><?= htmlspecialchars($item['nama_layanan']) ?></div>
                        <div class="item-meta">
                            Merk: <?= htmlspecialchars($item['merk_item'] ?: '-') ?> 
                            <?php if ($item['ukuran']): ?> | Ukuran: <?= htmlspecialchars($item['ukuran']) ?><?php endif; ?>
                            <?php if ($item['warna']): ?> | Warna: <?= htmlspecialchars($item['warna']) ?><?php endif; ?>
                        </div>
                    </td>
                    <td align="right" style="color: #64748b;">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td align="center" style="font-weight: 600;"><?= $item['jumlah'] ?></td>
                    <td align="right" style="font-weight: 700; color: #0f172a;">Rp <?= number_format($sub, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="inv-footer">
            <div class="total-box">
                <div class="total-row">
                    <span style="color: #64748b;">Total Layanan</span>
                    <strong style="color: #0f172a;">Rp <?= number_format($subtotal_items, 0, ',', '.') ?></strong>
                </div>
                <div class="total-row">
                    <span style="color: #64748b;">Ongkos Kirim</span>
                    <strong style="color: #0f172a;">Rp <?= number_format($order['ongkir'], 0, ',', '.') ?></strong>
                </div>
                <div class="total-row grand">
                    <span>Total Tagihan</span>
                    <span>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                </div>
            </div>
        </div>

        </div> <!-- end inv-content-wrap -->

    </div>
</div>

<div style="margin-bottom: 60px;"></div>

</body>
</html>
