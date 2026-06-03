<?php
// ============================================================
// pages/status.php — Cek Status Pesanan
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan | BUP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/status.css?v=<?= time() ?>">

</head>
<body>

<?php if (file_exists(__DIR__ . '/partials/navbar.php')) include __DIR__ . '/partials/navbar.php'; ?>

<div class="status-container">
    <div class="status-header">
        <h2>Cek Status Pesanan</h2>
        <p>Masukkan Kode Pesanan (contoh: BUP-X7K9K1) untuk melihat status terbaru.</p>
    </div>

    <div class="search-box">
        <input type="text" id="statusInput" class="search-input" placeholder="Masukkan Kode Pesanan..." autocomplete="off">
        <button class="search-btn" onclick="checkStatus()">Cek Status</button>
    </div>
    <div class="search-hint">Kode pesanan bisa ditemukan di Bukti Pemesanan atau Struk Transaksi.</div>

    <div id="statusResult" class="result-card"></div>

    <div class="btn-back-wrap" id="actionButtons" style="display: flex; justify-content: center; gap: 16px; margin-top: 32px; flex-wrap: wrap;">
        <a href="../index.php" class="btn-back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Beranda
        </a>
    </div>
</div>

<div class="receipt-box" id="printableReceipt" style="display: none; max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif; color: #000; position: relative; overflow: hidden;">

    <!-- Watermark LUNAS -->
    <div id="watermarkLunas" style="
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-35deg);
        font-size: 6rem;
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

    <div style="position: relative; z-index: 1;">
    <div class="receipt-header" style="text-align: center; margin-bottom: 20px;">
        <h2 style="font-weight:800; margin:0 0 5px 0; color:#000; font-size: 1.25rem;">BUP - Build Up Play</h2>
        <p style="color:#000; font-size:0.9rem; margin: 0;">Jasa Laundry Sepatu</p>
        <p style="color:#000; font-size:0.85rem; margin: 5px 0 0 0;">Telp/WA: 0812 1181 1577</p>
    </div>
    
    <div style="border-top: 1px dashed #cbd5e1; margin-bottom: 20px;"></div>
    
    <h3 id="rTitle" style="text-align:center; margin-bottom:20px; font-size:1.05rem; color:#000; font-weight:800;">BUKTI PEMBAYARAN</h3>
    
    <div class="receipt-row-old"><span class="label">Kode Pesanan:</span><span class="value" id="rKode">-</span></div>
    <div class="receipt-row-old"><span class="label">Tanggal Pesan:</span><span class="value" id="rTglPesan">-</span></div>
    <div class="receipt-row-old print-only-row"><span class="label">Tanggal Cetak:</span><span class="value" id="rTglCetak">-</span></div>
    <div class="receipt-row-old"><span class="label">Nama Pelanggan:</span><span class="value" id="rNama">-</span></div>
    <div class="receipt-row-old"><span class="label">Nomor WhatsApp:</span><span class="value" id="rWa">-</span></div>
    
    <div style="margin-top: 16px; margin-bottom: 8px; font-weight: 800; font-size: 0.85rem; color: #475569;">DETAIL PEMBAYARAN</div>
    <div class="receipt-row-old" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 4px !important;"><span class="label">Metode:</span><span class="value" id="rMetodeBayar">-</span></div>
    <div class="receipt-row-old" style="border-bottom: 1px dashed #e2e8f0 !important; padding-bottom: 12px !important; padding-top: 4px !important;"><span class="label">Status:</span><span class="value" id="rStatusBayar">-</span></div>

    <div id="rItemList"></div>

    <div class="receipt-row-old" id="rowCatatan"><span class="label">Catatan Umum:</span><span class="value" id="rCatatan">-</span></div>
    
    <div style="margin-top: 20px; margin-bottom: 8px; font-weight: 800; font-size: 0.85rem; color: #475569;">DETAIL PENGIRIMAN</div>
    <div class="receipt-row-old" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 4px !important;"><span class="label">Metode:</span><span class="value" id="rPengiriman">-</span></div>
    <div class="receipt-row-old hidden-row" id="rowWaktuJemput" style="border-bottom: 1px dashed #e2e8f0 !important; padding-bottom: 12px !important; padding-top: 4px !important;"><span class="label">Jadwal Jemput:</span><span class="value" id="rWaktuJemput">-</span></div>
    <div class="receipt-row-old hidden-row" id="rowAlamat" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 12px !important; flex-direction: column !important; align-items: flex-start !important; gap: 2px;"><span class="label" style="width: auto !important;">Alamat:</span><span class="value" id="rAlamat" style="font-size: clamp(0.65rem, 2.5vw, 0.75rem) !important; font-weight: 600 !important; line-height: 1.4 !important; text-align: left !important; width: 100% !important;">-</span></div>
    
    <div class="receipt-row-old hidden-row" id="rOngkirRow" style="border-bottom: none !important;"><span class="label">Ongkos Kirim:</span><span class="value" id="rOngkir">-</span></div>
    
    <div style="margin-top:16px; padding: 12px 0; border-top: 2px dashed #000; border-bottom: 2px dashed #000; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: nowrap;">
        <span class="label" style="color:#000; font-weight:900; font-size: clamp(0.85rem, 3.5vw, 1rem); white-space: nowrap;">TOTAL HARGA:</span>
        <span class="value" id="rTotal" style="color:#000; font-weight:900; font-size: clamp(0.95rem, 4.5vw, 1.2rem); white-space: nowrap; text-align: right;">Rp 0</span>
    </div>
    
    <div style="border-top: 1px dashed #cbd5e1; margin-top: 20px;"></div>
    </div><!-- end z-index wrapper -->
</div>

<script src="../assets/js/status.js?v=<?= time() ?>"></script>

<script src="../assets/js/main.js"></script>
</body>
</html>


