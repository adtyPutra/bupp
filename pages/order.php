<?php
// ============================================================
// pages/order.php — Form Pemesanan & Cetak Bukti (Multiple Items)
// ============================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_auth.php';

$db = db();

$layanan = $db->query("SELECT * FROM layanan WHERE aktif = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$metode_pengiriman_db = $db->query("SELECT * FROM metode_pengiriman ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Auto-fill jika pelanggan sudah login
$customerLoggedIn = isCustomerLoggedIn();
$customerData     = getLoggedInCustomer();
$prefillNama      = $customerLoggedIn ? htmlspecialchars($customerData['nama']) : '';
$prefillWa        = $customerLoggedIn ? htmlspecialchars($customerData['no_wa']) : '';

$isLoggedIn = $customerLoggedIn;
$namaInisial = '';
$namaDepan = '';
if ($isLoggedIn) {
    $namaInisial = strtoupper(substr($customerData['nama'], 0, 2));
    $namaDepan = htmlspecialchars(explode(' ', trim($customerData['nama']))[0]);
}
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
        /* Styling Google Maps Places Autocomplete dropdown */
        .pac-container {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            border-radius: 0 0 12px 12px !important;
            border: 1px solid #e2e8f0 !important;
            border-top: none !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
            z-index: 99999 !important;
        }
        .pac-item {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            padding: 10px 14px !important;
            font-size: 0.875rem !important;
            cursor: pointer !important;
            border-top: 1px solid #f1f5f9 !important;
            line-height: 1.4 !important;
        }
        .pac-item:hover { background: #f0f9ff !important; }
        .pac-item-query { font-weight: 600 !important; color: #0f172a !important; }
        .pac-matched { font-weight: 800 !important; color: #3b82f6 !important; }
        .pac-secondary-text { color: #64748b !important; font-size: 0.75rem !important; }
        .pac-item .pac-icon {
            filter: invert(36%) sepia(85%) saturate(2462%) hue-rotate(338deg) brightness(101%) contrast(101%) !important;
        }
        .pac-logo::after { display: none !important; } /* Sembunyikan logo Google di bawah dropdown */
    </style>
</head>

<div class="order-page">
    <div class="container order-wrap">

        <style>
            .order-page { padding-top: 32px; }
            .order-wrap { max-width: 980px !important; }
            .checkout-container { display: flex; flex-direction: column; gap: 32px; }
            @media(min-width: 992px) { .checkout-container { flex-direction: row; align-items: flex-start; } }
            .checkout-left { flex: 1; width: 100%; }
            .checkout-right { width: 100%; background: #fff; border: 1px solid #e2e8f0; border-top: 4px solid var(--blue); border-radius: 12px; padding: clamp(16px, 4vw, 24px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            @media(min-width: 992px) { .checkout-right { width: 350px; position: sticky; top: 32px; } }
            
            .step-item { flex: 1; text-align: center; padding: 12px 8px; background: #fff; border-radius: 12px; border: 2px solid #e2e8f0; color: #94a3b8; font-weight: 700; font-size: clamp(0.7rem, 2.5vw, 0.9rem); transition: 0.3s; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
            .step-item svg { margin-bottom: 4px; opacity: 0.5; transition: 0.3s; }
            .step-item.active { border-color: var(--blue); background: #eff6ff; color: var(--blue); box-shadow: 0 4px 12px rgba(59,130,246,0.1); }
            .step-item.active svg { opacity: 1; stroke: var(--blue); }
            .step-item.done { border-color: #10b981; background: #f0fdf4; color: #10b981; cursor: pointer; }
            .step-item.done svg { opacity: 1; stroke: #10b981; }
            
            .order-card-box { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
            .shipping-method-card { border: 1.5px solid var(--blue); border-radius: 8px; padding: 14px 10px; cursor: pointer; transition: 0.3s; display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 8px; background: #fff; color: var(--blue); text-transform: none; }
            .shipping-method-card input { position: absolute; opacity: 0; width: 0; height: 0; }
            .shipping-method-card .icon-wrap { display: flex; align-items: center; justify-content: center; color: inherit; flex-shrink: 0; }
            .shipping-method-card .icon-wrap svg { width: 20px; height: auto; max-height: 20px; fill: currentColor; }
            .shipping-method-card .title { font-weight: 700; font-size: clamp(0.75rem, 2.5vw, 0.9rem); color: inherit; transition: 0.3s; text-transform: none !important; text-align: center; white-space: nowrap; }
            .shipping-method-card:hover { border-color: #94a3b8; background: #f8fafc; }
            .shipping-method-card.selected { border-color: var(--blue); background: var(--blue); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.25); }
        </style>

        <form id="orderForm" class="checkout-container" novalidate enctype="multipart/form-data">
            <div class="checkout-left">
                        
                        <!-- ================= CARD 1: PESANAN ================= -->
                        <div id="checkout-step-1" class="order-card-box">
                            <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">1</div> Data Pemesan</div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Nama Lengkap <span>*</span></label>
                                    <input type="text" class="form-input" name="nama" id="oNama" placeholder="Masukkan nama lengkap" value="<?= $prefillNama ?>" required <?= $customerLoggedIn ? 'readonly style="background:#f8fafc;"' : '' ?> oninput="updateCheckoutSummary()">
                                </div>
                                <div class="form-group">
                                    <label>Nomor WhatsApp <span>*</span></label>
                                    <input type="tel" class="form-input" name="no_wa" id="oWa" placeholder="08xx-xxxx-xxxx" value="<?= $prefillWa ?>" required <?= $customerLoggedIn ? 'readonly style="background:#f8fafc;"' : '' ?> oninput="updateCheckoutSummary()">
                                </div>
                            </div>

                            <div class="order-section-title" style="color:var(--blue); margin-top:24px;"><div class="num" style="background:var(--blue);">2</div> Detail Cucian</div>
                            
                            <div id="dynamicItemsContainer"></div>

                            <button type="button" class="btn-tambah" onclick="tambahItem()">
                                <span style="font-size:1.1rem; margin-right:4px;">+</span> Tambah Item Cucian Lain
                            </button>

                            <div class="form-group full" style="margin-top: 24px;">
                                <label>Catatan Tambahan (Opsional)</label>
                                <textarea class="form-input" name="catatan" id="oCatatan" placeholder="Contoh: Tolong sepatu Vans putih disikat bagian talinya saja, noda kuning hati-hati." rows="3" style="width: 100%; border-radius: 10px;"></textarea>
                            </div>

                        </div>

                        <!-- ================= CARD 2: PENGIRIMAN ================= -->
                        <div id="checkout-step-2" class="order-card-box" style="display:none;">
                            <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">3</div> Pengiriman & Waktu</div>
                            
                            <div class="form-group full">
                                <label style="font-size: 1rem; color: #0f172a; display: flex; align-items: center; text-transform: none; font-weight: 700;">
                                    Pilih Metode Pengiriman
                                </label>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 8px;">
                                    <?php foreach ($metode_pengiriman_db as $idx => $mp): ?>
                                        <?php
                                            $needsPickup = (stripos($mp['nama_metode'], 'jemput') !== false) ? '1' : '0';
                                            $isStore = (stripos($mp['nama_metode'], 'toko') !== false);
                                        ?>
                                        <label class="shipping-method-card" onclick="document.querySelectorAll('.shipping-method-card').forEach(c=>c.classList.remove('selected')); this.classList.add('selected');">
                                            <input type="radio" name="metode_pengiriman" value="<?= $mp['id'] ?>" data-biaya="<?= $mp['biaya'] ?? 0 ?>" data-perlu-jemput="<?= $needsPickup ?>" class="radio-pengiriman" required>
                                            <div class="icon-wrap">
                                                <?php if($isStore): ?>
                                                    <svg viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>
                                                <?php else: ?>
                                                    <svg viewBox="0 0 640 512"><path d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
                                                <?php endif; ?>
                                            </div>
                                            <?php 
                                                $nama_m = ucwords(strtolower($mp['nama_metode']));
                                                $nama_m = str_replace([' Dan ', ' Di ', ' Ke '], [' dan ', ' di ', ' ke '], $nama_m);
                                            ?>
                                            <span class="title"><?= htmlspecialchars($nama_m) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <input type="hidden" name="ongkir_dinamis" id="input_ongkir_dinamis" value="0">
                            
                            <div id="infoOngkirBox" style="display: none; background: #fff; border: 1px solid #e2e8f0; border-top: 4px solid var(--blue, #3b82f6); padding: 24px; border-radius: 12px; margin-top: 24px; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);">
                                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                                    <svg viewBox="0 0 640 512" style="fill: var(--blue); width: 22px; height: 22px;"><path d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
                                    Informasi Pengiriman
                                </h3>
                                <div style="margin-bottom: 16px; position: relative;">
                                    <label style="font-size: 0.8rem; font-weight: 700; color: #64748b; display: block; margin-bottom: 8px; letter-spacing: 0.5px;">🔍 Cari Nama Jalan / Wilayah di Peta</label>
                                    <input type="text" id="searchAlamat" class="form-input" placeholder="Contoh: Jalan Salak Raya, Bekasi..." style="width: 100%; margin-bottom: 0;" autocomplete="off">
                                    <div id="searchSuggestBox" style="display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-top:none; border-radius:0 0 10px 10px; box-shadow:0 8px 24px rgba(0,0,0,0.12); z-index:9999; max-height:260px; overflow-y:auto;"></div>
                                </div>
                                
                                <div id="maps_info_text" style="font-size: clamp(0.7rem, 3vw, 0.85rem); color: #475569; line-height: 1.6; margin-bottom: 12px; background: #fffbeb; padding: 12px 14px; border-radius: 8px; border: 1px solid #fde68a;">
                                    <span style="display: flex; align-items: flex-start; gap: 8px;">
                                        <svg style="flex-shrink:0;margin-top:2px" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                        <span><strong style="color:#92400e;">Cara pakai:</strong> Ketik nama jalan utama (cth: <em>Jalan Salak Raya</em>), pilih lokasi paling dekat dari saran, lalu <strong>geser pin merah</strong> ke titik yang lebih tepat. Alamat lengkap dengan RT/RW bisa ditulis di kolom bawah setelah klik Konfirmasi.</span>
                                    </span>
                                </div>
                                <div id="map" style="height: 300px; width: 100%; border-radius: 12px; display: block; border: 2px solid #e2e8f0; box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.1); margin-bottom: 16px; overflow: hidden;"></div>
                                
                                <button type="button" id="btnKonfirmasiLokasi" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:600; font-size:clamp(0.85rem, 4vw, 0.95rem); padding:12px 16px; width: 100%; border-radius: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                    <span style="white-space: nowrap;">Konfirmasi Lokasi</span>
                                </button>
                            </div>

                            <div id="alamatGroup" class="delivery-wrapper" style="display:none; margin-top:24px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:12px; padding:20px;">
                                <div style="margin-bottom: 20px;">
                                    <label style="font-size:0.9rem; font-weight:700; color:#475569; margin-bottom:8px; display:block; text-transform:none !important;">Alamat Penjemputan <span style="color:#ef4444;">*</span></label>
                                    <textarea id="oAlamat" name="alamat" class="form-input" rows="3" placeholder="Tuliskan alamat lengkap..." oninput="updateCheckoutSummary()" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-family: inherit; font-size: 0.9rem;"></textarea>
                                    <input type="hidden" id="oLat" name="lokasi_lat">
                                    <input type="hidden" id="oLng" name="lokasi_lng">
                                </div>
                                <div style="display:flex; flex-wrap:wrap; gap:20px;">
                                    <div style="flex: 1 1 150px;">
                                        <label style="font-size:0.9rem; font-weight:700; color:#475569; margin-bottom:8px; display:block; text-transform:none !important;">Tanggal Penjemputan <span style="color:#ef4444;">*</span></label>
                                        <input type="date" id="oTanggal" name="tanggal_pesan" class="form-input" onchange="updateCheckoutSummary()" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 0.9rem;">
                                    </div>
                                    <div style="flex: 1 1 150px;">
                                        <label style="font-size:0.9rem; font-weight:700; color:#475569; margin-bottom:8px; display:block; text-transform:none !important;">Waktu Penjemputan <span style="color:#ef4444;">*</span></label>
                                        <select id="oWaktuJemput" name="waktu_penjemputan" class="form-select" onchange="updateCheckoutSummary()" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-family: inherit; font-size: 0.9rem;">
                                            <option value="">-- Pilih Waktu --</option>
                                            <option value="09.00 - 13.00">09.00 - 13.00</option>
                                            <option value="13.00 - 17.00">13.00 - 17.00</option>
                                            <option value="17.00 - 20.00">17.00 - 20.00</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="infoJadwalHabis" style="display:none; margin-top:16px; background:#fff7ed; border:1px solid #fed7aa; border-left:4px solid #f97316; border-radius:10px; padding:10px 14px; font-size:clamp(0.7rem, 3vw, 0.82rem); color:#c2410c; font-weight:600; line-height:1.6; align-items:flex-start; gap:8px;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                    <span>Semua jadwal hari ini sudah tidak tersedia. Silakan pilih <strong>tanggal besok</strong> untuk melanjutkan pemesanan.</span>
                                </div>
                            </div>

                            <div id="infoAmbilToko" style="display:none; margin-top:24px; background:#f8fafc; border:2px dashed #cbd5e1; border-radius:12px; padding:24px; text-align:center;">
                                <div style="display:flex; justify-content:center; margin-bottom: 12px; color: var(--blue);">
                                    <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>
                                </div>
                                <h4 style="margin:0 0 8px 0; font-size:1.1rem; color:#334155;">Lokasi Toko Kami</h4>
                                <p style="margin:0; font-size:0.9rem; color:#64748b; line-height:1.6; text-wrap: balance;">
                                    Anda memilih untuk mengantar dan mengambil pesanan secara langsung di toko kami.<br>
                                    Silakan kunjungi kami di:<br>
                                    <strong style="color:#334155; font-size: 0.85rem; display:inline-block; margin-top:12px; margin-bottom: 4px; line-height: 1.5; padding: 0 8px;">Terentang Elok 2 No.11 Blok G5, RT.11/RW.9, Penggilingan, Kec. Cakung, Kota Jakarta Timur</strong><br>
                                    <span style="display:inline-block; margin-top:4px;">Jam Buka: 09.00 - 20.00</span>
                                </p>
                            </div>


                        </div>

                        <!-- ================= CARD 3: PEMBAYARAN ================= -->
                        <div id="checkout-step-3" class="order-card-box" style="display:none;">
                            <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">4</div> Metode Pembayaran</div>
                            
                            <?php
                            $pay_opts = [
                                ['transfer_bca', '<img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" style="height: 28px; width: auto;">', 'Transfer BCA', '5891029111'],
                                ['tunai', '<svg width="32" height="32" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2" fill="#dcfce7" stroke="#16a34a" stroke-width="1.5"/><circle cx="12" cy="12" r="3" fill="#22c55e" stroke="#16a34a" stroke-width="1.5"/><path d="M6 9h.01M18 9h.01M6 15h.01M18 15h.01" stroke="#16a34a" stroke-width="2" stroke-linecap="round"/></svg>', 'Tunai', 'Bayar di tempat']
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

                            <!-- Informasi Tambahan Dinamis -->
                            <div id="paymentInfoBox" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; margin-top: 20px;">
                                <div style="display: flex; gap: 12px; align-items: flex-start;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                    <div>
                                        <h4 id="paymentInfoTitle" style="margin: 0 0 4px 0; font-size: 0.9rem; font-weight: 700; color: #1e293b;">Informasi Pembayaran</h4>
                                        <p id="paymentInfoDesc" style="margin: 0; font-size: 0.8rem; color: #475569; line-height: 1.5; text-wrap: pretty;">-</p>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="metode_bayar" id="paymentInput">

                        </div>

                        <!-- ================= CARD 4: KONFIRMASI PESANAN ================= -->
                        <div id="checkout-step-4" class="order-card-box" style="display:none;">
                            <div class="order-section-title" style="color:var(--blue);"><div class="num" style="background:var(--blue);">5</div> Konfirmasi Pesanan</div>
                            
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: clamp(14px, 3.5vw, 20px);">
                                <h4 style="margin: 0 0 16px 0; font-size: 1rem; font-weight: 800; color: #1e293b; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px;">Cek Kembali Data Anda</h4>
                                
                                <div style="display: flex; flex-direction: column; gap: 14px; font-size: clamp(0.8rem, 3.5vw, 0.9rem); color: #475569; padding-bottom: 16px; border-bottom: 1px dashed #cbd5e1; margin-bottom: 16px;">
                                    <div style="display: grid; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">Nama:</span>
                                        <span id="konfNama" style="color: #0f172a; font-weight: 700;">-</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">No. WA:</span>
                                        <span id="konfWa" style="color: #0f172a; font-weight: 700;">-</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">Pengiriman:</span>
                                        <span id="konfPengiriman" style="color: #0f172a; font-weight: 700;">-</span>
                                    </div>
                                    <div id="konfAlamatGroup" style="display: none; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">Alamat Jemput:</span>
                                        <span id="konfAlamat" style="color: #0f172a; font-weight: 700;">-</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">Pembayaran:</span>
                                        <span id="konfBayar" style="color: #0f172a; font-weight: 700;">-</span>
                                    </div>
                                    <div id="konfCatatanGroup" style="display: none; grid-template-columns: clamp(96px, 26vw, 130px) 1fr; gap: clamp(8px, 2vw, 12px);">
                                        <span style="font-weight: 600;">Catatan:</span>
                                        <span id="konfCatatan" style="color: #0f172a; font-weight: 600; font-style: italic;">-</span>
                                    </div>
                                </div>

                                <h4 style="margin: 0 0 12px 0; font-size: 0.95rem; font-weight: 800; color: #1e293b;">Rincian Layanan</h4>
                                <div id="konfItemsHtml" style="margin-bottom: 16px;"></div>

                                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: #475569; padding-top: 16px; border-top: 1px dashed #cbd5e1;">
                                    <div style="display: flex; justify-content: space-between;">
                                        <span>Subtotal Layanan</span>
                                        <span id="konfSubtotal" style="font-weight: 700; color: #0f172a; white-space: nowrap; margin-left: 8px;">Rp 0</span>
                                    </div>
                                    <div id="konfOngkirRow" style="display: flex; justify-content: space-between;">
                                        <span>Biaya Pengiriman</span>
                                        <span id="konfOngkir" style="font-weight: 700; color: #0f172a; white-space: nowrap; margin-left: 8px;">Rp 0</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 8px;">
                                        <span style="font-size: 1rem; font-weight: 800; color: #0f172a; white-space: nowrap;">Total Tagihan</span>
                                        <span id="konfTotal" style="font-size: clamp(1.15rem, 5vw, 1.25rem); font-weight: 800; color: var(--blue); white-space: nowrap; text-align: right;">Rp 0</span>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 20px; background: #eff6ff; border: 1px dashed #93c5fd; padding: clamp(12px, 3.5vw, 16px); border-radius: 10px; font-size: 0.85rem; color: #1e3a8a; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px; color: #3b82f6;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                    <div style="line-height: 1.5; font-weight: 500; text-wrap: pretty;">
                                        Pastikan semua data di atas sudah benar. Pesanan yang sudah dikonfirmasi tidak dapat diubah kembali.
                                    </div>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <button type="button" id="btnKonfirmasiAkhir" class="btn" style="background:#10b981; color:#fff; border:none; font-weight:800; padding:14px; width: 100%; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:8px; font-size:1.05rem; box-shadow:0 8px 20px rgba(16,185,129,0.3);" onclick="prosesPesanan()">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                        Konfirmasi Pesanan
                                    </button>
                                    <button type="button" class="btn btn-outline" style="font-weight:700; width: 100%; display:flex; justify-content:center; padding:12px; border-radius:10px;" onclick="goToStep(3)">
                                        Kembali untuk Ubah Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End checkout-left -->

                    <!-- ================= CHECKOUT RIGHT (STICKY SUMMARY) ================= -->
                    <div class="checkout-right" id="checkoutRight">
                        <h3 style="margin: 0 0 20px 0; font-size: 1.2rem; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">Ringkasan Pesanan</h3>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.85rem; color: #475569;">
                            <span>Total Item Cucian</span>
                            <span id="sumItems" style="font-weight: 700; color: #0f172a;">0 Item</span>
                        </div>
                        
                        <div id="daftarLayananSummary" style="margin-bottom: 16px;"></div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.85rem; color: #475569;">
                            <span>Subtotal Layanan</span>
                            <span id="sumSubtotal" style="font-weight: 700; color: #0f172a; white-space: nowrap; margin-left: 8px;">Rp 0</span>
                        </div>
                        
                        <div id="rowBiayaPengiriman" style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 0.85rem; color: #475569;">
                            <span>Biaya Pengiriman</span>
                            <span id="sumOngkir" style="font-weight: 700; color: #0f172a; white-space: nowrap; margin-left: 8px;">Rp 0</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 16px; border-top: 1px dashed #cbd5e1; margin-bottom: 24px; flex-wrap: wrap; gap: 8px;">
                            <span style="font-size: 1rem; font-weight: 800; color: #0f172a; white-space: nowrap;">Total Tagihan</span>
                            <span id="sumTotal" style="font-size: clamp(1.2rem, 5.5vw, 1.5rem); font-weight: 800; color: var(--blue); white-space: nowrap; text-align: right;">Rp 0</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <button type="button" id="btnLanjutUtama" class="btn" style="background:var(--blue); color:#fff; border:none; font-weight:800; padding:14px; width: 100%; border-radius:10px; display:flex; align-items:center; justify-content:center; gap:8px; font-size:1.05rem;" onclick="handleLanjut()">
                                Lanjut ke Pengiriman
                            </button>
                            <button type="button" id="btnKembaliUtama" class="btn btn-outline" style="font-weight:700; width: 100%; display:flex; justify-content:center; padding:12px; border-radius:10px;" onclick="handleKembali()">
                                Kembali
                            </button>
                        </div>
                    </div>

                </form>
                </div>
            </div>
        </div>


<script>
const DATA_LAYANAN = [
    <?php 
    $excluded = ['bag', 'wallet', 'sandals', 'hat'];
    foreach ($layanan as $l): 
        $catStr = strtolower(trim($l['kategori']));
        if (in_array($catStr, $excluded)) continue;
        if ($catStr === 'repaint' && (stripos($l['jenis'], 'hat') !== false || stripos($l['jenis'], 'topi') !== false)) continue;
    ?>
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