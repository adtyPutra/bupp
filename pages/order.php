<?php
// ============================================================
// pages/order.php — Form Pemesanan & Cetak Bukti (Multiple Items)
// ============================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$db = db();

$layanan = $db->query("SELECT * FROM layanan WHERE aktif = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$metode_pengiriman_db = $db->query("SELECT * FROM metode_pengiriman ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Layanan | BUP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../assets/css/order.css?v=<?= time() ?>">
    <style>
        /* Ubah warna icon pin di dropdown Google Maps Autocomplete jadi merah terang */
        .pac-item .pac-icon {
            filter: invert(36%) sepia(85%) saturate(2462%) hue-rotate(338deg) brightness(101%) contrast(101%) !important;
        }
    </style>
</head>
<body>

<div class="order-page">
    <div class="container order-wrap">
        <a href="../index.php" class="btn btn-outline no-print" style="margin-bottom:20px; font-weight:600; display: inline-flex; align-items: center; gap: 8px;" id="btnBackHome" onclick="clearOrderState()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali ke Beranda
        </a>

        <div class="order-card" id="mainOrderCard">
            <form id="orderForm" novalidate enctype="multipart/form-data">
                
                <div id="step1">
                    <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">1</div> Data Pemesan</div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nama Lengkap <span>*</span></label>
                            <input type="text" class="form-input" name="nama" id="oNama" placeholder="Masukkan nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor WhatsApp <span>*</span></label>
                            <input type="tel" class="form-input" name="no_wa" id="oWa" placeholder="08xx-xxxx-xxxx" required>
                        </div>
                    </div>

                    <div class="order-section-title" style="color:var(--blue); margin-top:24px;"><div class="num" style="background:var(--blue);">2</div> Detail Cucian</div>
                    
                    <div id="dynamicItemsContainer">
                        </div>

                    <button type="button" class="btn-tambah" onclick="tambahItem()">
                        <span style="font-size:1.1rem; margin-right:4px;">+</span> Tambah Item Cucian Lain
                    </button>

                    <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">3</div> Pengiriman & Waktu</div>
                    
                    <div class="form-group full">
                        <label>Metode Pengiriman <span>*</span></label>
                        <div class="option-grid">
                            <?php foreach ($metode_pengiriman_db as $idx => $mp): ?>
                                <?php
                                    $needsPickup = (stripos($mp['nama_metode'], 'jemput') !== false) ? '1' : '0';
                                ?>
                                <label class="bup-card-box shipping-card">
                                    <input type="radio" name="metode_pengiriman" value="<?= $mp['id'] ?>" data-biaya="<?= $mp['biaya'] ?? 0 ?>" data-perlu-jemput="<?= $needsPickup ?>" class="radio-pengiriman" required>
                                    <span class="shipping-title"><?= htmlspecialchars($mp['nama_metode']) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="ongkir_dinamis" id="input_ongkir_dinamis" value="0">
                    <div id="infoOngkirBox" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid var(--blue, #3b82f6); padding: 16px; border-radius: 10px; margin-top: 14px; margin-bottom: 14px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.1);">
                        <p style="margin: 0 0 10px 0; font-size: clamp(0.75rem, 3.5vw, 0.95rem); font-weight: 800; color: #1e3a8a; display: flex; align-items: center; gap: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3" fill="white" stroke="white"></circle></svg>
                            Estimasi Jarak & Ongkos Kirim
                        </p>
                        <div style="margin-bottom: 12px; position: relative;">
                            <label style="font-size: 0.85rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Cari Lokasi / Geser Pin Peta:</label>
                            <input type="text" id="searchAlamat" class="form-input" placeholder="Ketik nama jalan / perumahan..." style="width: 100%; margin-bottom: 0;" autocomplete="off">
                            <div id="searchSuggestBox" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; max-height:260px; overflow-y:auto;"></div>
                        </div>
                        
                        <div id="maps_info_text" style="font-size: clamp(0.7rem, 3vw, 0.85rem); color: #475569; line-height: 1.5; margin-bottom: 12px; background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <span style="display: flex; align-items: center; gap: 6px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                <em>Ketuk peta atau geser pin merah untuk set lokasi Anda.</em>
                            </span>
                        </div>
                        <div id="map" style="height: 280px; width: 100%; border-radius: 10px; display: block; border: 1px solid #cbd5e1; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05); margin-bottom: 12px;"></div>
                        
                        <button type="button" id="btnKonfirmasiLokasi" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:600; padding:10px 16px; width: 100%; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            Konfirmasi Lokasi Ini
                        </button>
                    </div>

                    <div id="alamatGroup" class="delivery-wrapper">
                        <div class="dw-alamat">
                            <label>Alamat Penjemputan <span>*</span></label>
                            <textarea class="form-input" name="alamat" id="oAlamat" placeholder="Tuliskan alamat lengkap..." rows="3"></textarea>
                            <input type="hidden" id="oLat" name="lokasi_lat">
                            <input type="hidden" id="oLng" name="lokasi_lng">

                        </div>
                        <div class="dw-waktu">
                            <label>Jadwal Jemput <span>*</span></label>
                            <select class="form-select" name="waktu_penjemputan" id="oWaktuJemput">
                                <option value="">-- Pilih Waktu --</option>
                                <option value="09.00 - 13.00">09.00 - 13.00</option>
                                <option value="13.00 - 17.00">13.00 - 17.00</option>
                                <option value="17.00 - 20.00">17.00 - 20.00</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top: 16px; align-items: start;">
                        <div class="form-group">
                            <label id="labelTanggalOrder">Tanggal Pemesanan <span>*</span></label>
                            <input type="date" class="form-input" name="tanggal_pesan" id="oTanggal" required style="width: 100%;">
                        </div>
                        <div class="form-group">
                            <label>Catatan Tambahan</label>
                            <textarea class="form-input" name="catatan" id="oCatatan" placeholder="Opsional, misal instruksi khusus..." rows="2" style="width: 100%;"></textarea>
                        </div>
                    </div>

                    <div class="price-est" style="border-radius: 12px; margin-top: 24px;">
                        <span class="pe-label">Estimasi Total Harga</span>
                        <div class="pe-price-box">
                            <span class="pe-price" id="priceEst">Pilih layanan terlebih dahulu</span>
                            <span id="priceOngkirWarning" style="display: none; font-size: clamp(0.65rem, 2.5vw, 0.75rem); color: #64748b; font-weight: 600; margin-top: 4px;">*Belum termasuk ongkir</span>
                        </div>
                    </div>



                    <div style="margin: 32px 0;"></div>

                    <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">4</div> Metode Pembayaran</div>

                    <?php
                    $pay_opts = [
                        ['transfer_bca', '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" style="height: 28px; width: auto;">', 'Transfer BCA', '5891029111'],
                        ['tunai', '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2" fill="#dcfce7" stroke="#16a34a" stroke-width="1.5"/><circle cx="12" cy="12" r="3" fill="#22c55e" stroke="#16a34a" stroke-width="1.5"/><path d="M6 9h.01M18 9h.01M6 15h.01M18 15h.01" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/></svg>', 'Tunai', 'Bayar di tempat']
                    ];
                    ?>

                    <div class="option-grid">
                        <?php foreach ($pay_opts as [$val, $ico, $name, $detail]): ?>
                            <div class="bup-card-box payment-card" data-val="<?= $val ?>" onclick="selectPay(this,'<?= $val ?>')">
                                <div class="pay-ico"><?= $ico ?></div>
                                <div class="pay-info-wrap">
                                    <div class="pay-name"><?= htmlspecialchars($name) ?></div>
                                    <div class="pay-detail"><?= htmlspecialchars($detail) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="metode_bayar" id="paymentInput">

                    <div class="form-actions" style="margin-top: 32px;">
                        <a href="../index.php" class="btn btn-outline" style="font-weight:700;" onclick="clearOrderState()">Batal</a>
                        <button type="button" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:700; padding:14px 28px;" onclick="reviewOrder()">Cek Ringkasan Pesanan</button>
                    </div>
                </div>

                <div id="step2" style="display:none;">
                    <button type="button" class="btn btn-outline" style="margin-bottom: 20px; font-weight:600; display: inline-flex; align-items: center; gap: 8px;" onclick="kembaliKeForm()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Kembali ke Form
                    </button>

                    <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">5</div> Detail Pembayaran</div>
                    <p style="color:#64748b; font-size:clamp(0.95rem, 3vw, 1.15rem); margin-bottom:24px;" id="paySubtitle">Lakukan pembayaran ke rekening berikut, kemudian upload bukti pembayaran.</p>

                    <div id="timerWarning" class="timer-alert" style="display:none;">
                        <div class="info">
                            <strong style="display:block; font-size:clamp(0.95rem, 4vw, 1.25rem); margin-bottom:4px; line-height: 1.2;">Batas Waktu Pembayaran</strong>
                            <span style="font-size:clamp(0.75rem, 3.5vw, 1.05rem); opacity:0.9; display:block; line-height: 1.4;">Harap transfer sebelum waktu habis.</span>
                        </div>
                        <div class="time" id="countdownTimer" style="font-size:clamp(1.4rem, 6vw, 2.2rem);">Menghitung...</div>
                    </div>

                    <div class="pay-summary-box" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin-bottom:30px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.02);">
                        <div class="pay-stack-mobile" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
                            <span style="color:#64748b; font-weight:500; font-size:clamp(0.75rem, 3.5vw, 1.1rem);">Metode Pembayaran</span>
                            <strong id="sumMetode" style="color:#0f172a; font-size:clamp(0.8rem, 3.5vw, 1.2rem); text-align:right; max-width:60%; overflow-wrap:break-word;">Transfer BCA</strong>
                        </div>
                        <div id="rekBcaArea">
                            <div class="pay-stack-mobile" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
                                <span style="color:#64748b; font-weight:500; font-size:clamp(0.75rem, 3.5vw, 1.1rem);">Nomor Rekening</span>
                                <strong style="color:#0f172a; font-size:clamp(0.8rem, 3.5vw, 1.2rem); text-align:right; max-width:60%; overflow-wrap:break-word;">5891029111</strong>
                            </div>
                            <div class="pay-stack-mobile" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
                                <span style="color:#64748b; font-weight:500; font-size:clamp(0.75rem, 3.5vw, 1.1rem);">Atas Nama</span>
                                <strong style="color:#0f172a; font-size:clamp(0.8rem, 3.5vw, 1.2rem); text-align:right; max-width:60%; overflow-wrap:break-word;">ANNIDA NURUL ISLAMI</strong>
                            </div>
                        </div>
                        <div class="pay-stack-mobile" style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px;">
                            <span style="color:#64748b; font-weight:500; font-size:clamp(0.75rem, 3.5vw, 1.1rem);">Nama Pelanggan</span>
                            <strong id="sumNama" style="color:#0f172a; font-size:clamp(0.8rem, 3.5vw, 1.2rem); text-align:right; max-width:60%; overflow-wrap:break-word;">-</strong>
                        </div>
                        <div style="display:flex; flex-direction:column; margin-bottom:16px; border-bottom:1px solid #e2e8f0; padding-bottom:16px;">
                            <span style="color:#64748b; font-weight:500; margin-bottom:10px; font-size:clamp(0.75rem, 3.5vw, 1.1rem);">Detail Cucian (<span id="sumItemCount">1</span> Item):</span>
                            <div id="sumLayananList" style="font-size:clamp(0.9rem, 3.5vw, 1.15rem); padding-left:14px; border-left:3px solid var(--blue); color:#0f172a;"></div>
                        </div>
                        <div id="sumOngkirRow" style="display:flex; justify-content:space-between; flex-wrap:nowrap; gap:8px; margin-bottom:14px; border-bottom:1px solid #e2e8f0; padding-bottom:14px; align-items:center;">
                            <span style="color:#64748b; font-weight:500; font-size:clamp(0.7rem, 3.5vw, 1.1rem); white-space:nowrap;">Ongkos Kirim</span>
                            <strong id="sumOngkirValue" style="color:#0f172a; font-size:clamp(0.7rem, 3vw, 1.2rem); text-align:right; white-space:nowrap;">Rp 0</strong>
                        </div>

                        <div class="pay-total-row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; padding-top:4px;">
                            <span style="color:#64748b; font-weight:500; font-size:clamp(0.85rem, 3.5vw, 1.2rem);">Total Tagihan</span>
                            <div class="pay-total-price-box" style="display: flex; flex-direction: column; align-items: flex-end;">
                                <strong style="color:var(--blue); font-size:clamp(1.3rem, 5vw, 2.2rem); letter-spacing:-0.5px; text-align:right;" id="sumTotal">Rp 0</strong>
                            </div>
                        </div>
                    </div>

                    <div id="uploadArea">
                        <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">6</div> Upload Bukti Pembayaran</div>
                        <div style="border:2px dashed #cbd5e1; padding:40px 20px; text-align:center; border-radius:16px; position:relative; background:#f8fafc; cursor:pointer; transition:all 0.2s;">
                            <input type="file" name="bukti_bayar" id="oBukti" accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer; z-index:50;" onchange="previewBukti(this)">
                            <div id="uploadInstructions">
                                <div style="margin-bottom:12px; display:flex; justify-content:center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                </div>
                                <p style="font-weight:700; color:#0f172a; margin-bottom:6px; font-size:clamp(0.85rem, 3.5vw, 1.3rem); text-wrap: balance;">Klik atau seret foto bukti transfer ke sini</p>
                                <p style="font-size:clamp(0.7rem, 3vw, 1.05rem); color:#64748b; text-wrap: balance;">Format JPG, PNG, maks 5MB</p>
                            </div>
                            <img id="imgPreview" src="" style="max-width:100%; max-height:250px; margin-top:15px; display:none; border-radius:12px; margin-left:auto; margin-right:auto; position:relative; z-index:10; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                        </div>
                    </div>

                    <div class="step2-actions" style="display:flex; justify-content:space-between; align-items:center; margin-top:32px; gap:12px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-outline" style="font-weight:700;" onclick="if(confirm('Yakin ingin membatalkan pesanan?')) { clearOrderState(); window.location.href='../index.php'; }">Batal</button>
                        <button type="button" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:700; padding:14px 28px;" onclick="prosesPesanan()" id="btnSubmitFinal">Kirim Bukti &amp; Konfirmasi</button>
                    </div>
                </div>
            </form>
        </div>



        <div id="step3" style="display:none;">
            <div style="text-align:center; padding:40px; background:#fff; border-radius:24px; border:1px solid #e2e8f0; margin-bottom: 20px; box-shadow:0 10px 30px rgba(0,0,0,0.03);" class="no-print">
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <div style="background:#dcfce7; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                </div>
                <h2 style="font-family:'Plus Jakarta Sans', sans-serif; font-size:clamp(0.9rem, 5vw, 2rem); font-weight:800; color:var(--dk); margin-bottom:12px; letter-spacing:-0.5px; white-space: nowrap;">Pesanan Berhasil Dibuat!</h2>
                <p style="color:var(--md); margin-bottom:30px; font-size:clamp(0.8rem, 3vw, 1.05rem); line-height:1.6; max-width:600px; margin-left:auto; margin-right:auto;">Terima kasih telah melakukan pemesanan. Silakan simpan atau cetak bukti pemesanan di bawah ini untuk memantau proses pesanan Anda.</p>
                <div style="display:flex; gap:16px; justify-content:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-outline" style="font-weight:700; display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px;" onclick="window.print()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Cetak Bukti
                    </button>
                    <a href="../pages/status.php" class="btn" style="background:var(--blue); color:#fff; text-decoration:none; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; padding: 14px 28px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);">
                        Cek Status Pesanan
                    </a>
                </div>
            </div>

            <div class="receipt-box" id="printableReceipt" style="max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; font-family: 'Plus Jakarta Sans', sans-serif; color: #000;">
                <div class="receipt-header" style="text-align: center; margin-bottom: 20px;">
                    <h2 style="font-weight:800; margin:0 0 5px 0; color:#000; font-size: 1.25rem;">BUP - Build Up Play</h2>
                    <p style="color:#000; font-size:0.9rem; margin: 0;">Jasa Laundry Sepatu</p>
                    <p style="color:#000; font-size:0.85rem; margin: 5px 0 0 0;">Telp/WA: 0812 1181 1577</p>
                </div>
                
                <div style="border-top: 1px dashed #cbd5e1; margin-bottom: 20px;"></div>
                
                <h3 style="text-align:center; margin-bottom:20px; font-size:1.05rem; color:#000; font-weight:800;">BUKTI PEMESANAN</h3>
                
                <div class="receipt-row-old"><span class="label">Kode Pesanan:</span><span class="value" id="rKode">-</span></div>
                <div class="receipt-row-old"><span class="label">Tanggal Pesan:</span><span class="value" id="rTglPesan">-</span></div>
                <div class="receipt-row-old print-only-row"><span class="label">Tanggal Cetak:</span><span class="value" id="rTglCetak">-</span></div>
                <div class="receipt-row-old"><span class="label">Nama Pelanggan:</span><span class="value" id="rNama">-</span></div>
                <div class="receipt-row-old"><span class="label">Nomor WhatsApp:</span><span class="value" id="rWa">-</span></div>
                
                <div style="margin-top: 16px; margin-bottom: 8px; font-weight: 800; font-size: 0.75rem; color: #475569;">DETAIL PEMBAYARAN</div>
                <div class="receipt-row-old" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 4px !important;"><span class="label">Metode:</span><span class="value" id="rMetodeBayar">-</span></div>
                <div class="receipt-row-old" style="border-bottom: 1px dashed #e2e8f0 !important; padding-bottom: 12px !important; padding-top: 4px !important;"><span class="label">Status:</span><span class="value" id="rStatusBayar">-</span></div>
                
                <div id="rItemList"></div>

                <div class="receipt-row-old" id="rowCatatan"><span class="label">Catatan Umum:</span><span class="value" id="rCatatan">-</span></div>
                
                <div style="margin-top: 20px; margin-bottom: 8px; font-weight: 800; font-size: 0.75rem; color: #475569;">DETAIL PENGIRIMAN</div>
                <div class="receipt-row-old" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 4px !important;"><span class="label">Metode:</span><span class="value" id="rPengiriman">-</span></div>
                <div class="receipt-row-old hidden-row" id="rowWaktuJemput" style="border-bottom: 1px dashed #e2e8f0 !important; padding-bottom: 12px !important; padding-top: 4px !important;"><span class="label">Jadwal Jemput:</span><span class="value" id="rWaktuJemput">-</span></div>
                <div class="receipt-row-old hidden-row" id="rowAlamat" style="border-bottom: none !important; padding-bottom: 4px !important; padding-top: 12px !important; flex-direction: column !important; align-items: flex-start !important; gap: 2px;"><span class="label" style="width: auto !important;">Alamat:</span><span class="value" id="rAlamat" style="font-size: clamp(0.65rem, 2.5vw, 0.75rem) !important; font-weight: 600 !important; line-height: 1.4 !important; text-align: left !important; width: 100% !important;">-</span></div>

                <div class="receipt-row-old hidden-row" id="rOngkirRow" style="border-bottom: none !important;"><span class="label">Ongkos Kirim:</span><span class="value" id="rOngkir">-</span></div>
                
                <div style="margin-top:16px; padding: 12px 0; border-top: 2px dashed #000; border-bottom: 2px dashed #000; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: nowrap;">
                    <span class="label" style="color:#000; font-weight:900; font-size: clamp(0.85rem, 3.5vw, 1rem); white-space: nowrap;">TOTAL HARGA:</span>
                    <span class="value" id="rTotal" style="color:#000; font-weight:900; font-size: clamp(0.95rem, 4.5vw, 1.2rem); white-space: nowrap; text-align: right;">Rp 0</span>
                </div>
                
                <div style="border-top: 1px dashed #cbd5e1; margin-top: 20px;"></div>

                <div id="infoKotak" class="no-print" style="display:none; margin-top:24px; background:#fffbeb; border:1px solid #fde68a; border-left:4px solid #f59e0b; padding:16px 16px 16px 12px; border-radius:10px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <p style="margin:0; font-size:clamp(0.8rem, 3.5vw, 0.9rem); font-weight:800; color:#b45309; text-transform:uppercase; letter-spacing:0.5px;">
                            Informasi Penting
                        </p>
                    </div>
                    <ul style="margin:0; padding-left:22px; font-size:clamp(0.65rem, 3vw, 0.8rem); color:#b45309; line-height:1.65; list-style-type: disc;">
                        <li style="margin-bottom: 12px; padding-left: 4px;">Simpan kode pesanan Anda dengan baik untuk melakukan pengecekan status pesanan melalui menu <strong>Status Pesanan</strong>.</li>
                        <li style="margin-bottom: 12px; padding-left: 4px;">Jika terdapat data atau informasi pesanan yang tidak sesuai, kami akan menghubungi langsung melalui WhatsApp atau nomor yang telah didaftarkan.</li>
                        <li style="margin-bottom: 0; padding-left: 4px;">Untuk informasi lebih lanjut, silakan hubungi admin melalui WhatsApp di <strong>0812-1181-1577</strong>.</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Pesanan -->
<div id="modalKonfirmasi" class="modal-overlay" style="display:none; align-items:center; justify-content:center; z-index:999; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); backdrop-filter:blur(4px);">
    <div class="modal-content" style="max-width:550px; width:92%; background:#fff; border-radius:20px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,0.15); animation: modalFadeIn 0.3s ease-out; display: flex; flex-direction: column; max-height: 90vh;">
        <div class="modal-header" style="background:#f8fafc; padding:clamp(16px, 3vw, 20px) clamp(16px, 4vw, 24px); border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:clamp(0.95rem, 4vw, 1.2rem); font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="clamp(16px, 4vw, 20px)" height="clamp(16px, 4vw, 20px)" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Konfirmasi Pesanan
            </h3>
            <button type="button" onclick="tutupModalKonfirmasi()" style="background:none; border:none; font-size:1.5rem; color:#94a3b8; cursor:pointer; line-height:1; transition:color 0.2s;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        <div class="modal-body" style="padding:clamp(16px, 4vw, 24px); overflow-y:auto; flex:1; min-height:0; background:#fff;">
            <div id="modalSumContent"></div>
        </div>
        <style>
            .modal-footer-btns { display: flex; gap: 12px; justify-content: flex-end; align-items: center; flex-wrap: wrap; }
            .modal-footer-btns button { flex: 1 1 auto; text-align: center; justify-content: center; }
            @media (max-width: 480px) {
                .modal-footer-btns { flex-direction: column-reverse; align-items: stretch; }
                .modal-footer-btns button { width: 100%; }
            }
        </style>
        <div class="modal-footer modal-footer-btns" style="padding:clamp(16px, 4vw, 20px) clamp(16px, 4vw, 24px); border-top:1px solid #e2e8f0; background:#f8fafc;">
            <button type="button" class="btn btn-outline" style="font-weight:700; padding:clamp(10px, 3vw, 12px) clamp(16px, 4vw, 24px);" onclick="tutupModalKonfirmasi()">Cek Kembali</button>
            <button type="button" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:700; padding:clamp(10px, 3vw, 12px) clamp(16px, 4vw, 24px); box-shadow:0 4px 12px rgba(59,130,246,0.3);" onclick="lanjutKePembayaran()">Konfirmasi & Lanjut Bayar</button>
        </div>
    </div>
</div>

<script>
const DATA_LAYANAN = [
    <?php foreach ($layanan as $l): ?>
    {
        id: "<?= $l['id'] ?>",
        kategori: <?= json_encode($l['kategori']) ?>,
        nama: <?= json_encode($l['jenis']) ?>, 
        harga: <?= $l['harga'] ?>
    },
    <?php endforeach; ?>
];

</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAnefpYvMaGbj0BXmjdbCbUcVeRjXhw94k&libraries=places&callback=initGoogleMaps"></script>
<script src="../assets/js/order.js?v=<?= time() ?>"></script>
<script src="../assets/js/main.js?v=<?= time() ?>"></script>
</body>
</html>