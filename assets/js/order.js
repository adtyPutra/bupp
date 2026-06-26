let timerInterval;
let isPaymentStep = false;

function clearOrderState() {
    localStorage.removeItem('bup_order_state');
    localStorage.removeItem('bup_step');
    localStorage.removeItem('paymentDeadline');
    sessionStorage.removeItem('bupp_order_lat');
    sessionStorage.removeItem('bupp_order_lng');
    ongkirDinamis = 0;
}

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((row, index) => {
        const title = row.querySelector('.item-title');
        if (title) {
            title.textContent = `Item #${index + 1}`;
        }
    });
}

function updateWaktuJemput() {
    const select = document.getElementById('oWaktuJemput');
    if (!select) return;

    const tanggalInput = document.getElementById('oTanggal').value;
    if (!tanggalInput) return;

    const selectedDate = new Date(tanggalInput);
    const now = new Date();

    const savedValue = select.value; // Simpan pilihan sebelumnya jika ada

    select.innerHTML = '<option value="">-- Pilih Waktu --</option>';

    const shifts = [
        { label: '09.00 - 13.00', cutoffHour: 12, cutoffMin: 40 }, // Tutup jam 12:40 (Buffer 20 menit)
        { label: '13.00 - 17.00', cutoffHour: 16, cutoffMin: 40 }, // Tutup jam 16:40
        { label: '17.00 - 20.00', cutoffHour: 19, cutoffMin: 40 }  // Tutup jam 19:40
    ];

    const isToday = selectedDate.getDate() === now.getDate() &&
        selectedDate.getMonth() === now.getMonth() &&
        selectedDate.getFullYear() === now.getFullYear();

    let hasAvailableShift = false;

    shifts.forEach(shift => {
        if (isToday) {
            // Tampilkan jika jam saat ini masih SEBELUM cutoff (Jam lebih kecil, ATAU jam sama tapi menit lebih kecil)
            if (now.getHours() < shift.cutoffHour || (now.getHours() === shift.cutoffHour && now.getMinutes() < shift.cutoffMin)) {
                const opt = document.createElement('option');
                opt.value = shift.label;
                opt.textContent = shift.label;
                select.appendChild(opt);
                hasAvailableShift = true;
            }
        } else {
            // Jika besok/lusa, semua jam terbuka
            const opt = document.createElement('option');
            opt.value = shift.label;
            opt.textContent = shift.label;
            select.appendChild(opt);
            hasAvailableShift = true;
        }
    });

    if (isToday && !hasAvailableShift) {
        select.innerHTML = '<option value="">-- Waktu habis hari ini, pilih hari besok --</option>';
        select.disabled = true;
        const infoEl = document.getElementById('infoJadwalHabis');
        if (infoEl) infoEl.style.display = 'flex';
    } else {
        select.disabled = false;
        const infoEl = document.getElementById('infoJadwalHabis');
        if (infoEl) infoEl.style.display = 'none';
        // Kembalikan pilihan user jika masih ada di daftar opsi yang baru
        if (savedValue) {
            const exists = Array.from(select.options).some(opt => opt.value === savedValue);
            if (exists) select.value = savedValue;
        }
    }
}

function tambahItem() {
    const container = document.getElementById('dynamicItemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';

    const itemCount = document.querySelectorAll('.item-row').length + 1;
    const btnHapus = itemCount > 1 ? `
        <button type="button" class="btn-hapus-item" onclick="hapusItem(this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> 
            Hapus
        </button>
    ` : '';

    row.innerHTML = `
        <div class="item-header">
            <h4 class="item-title">Item #${itemCount}</h4>
            ${btnHapus}
        </div>
        <div class="form-grid item-form-grid">
            <div class="form-group fg-kategori">
                <label>Jenis Sepatu / Kategori <span>*</span></label>
                <select class="form-select kat-select" name="kategori[]" onchange="updateLayananDynamic(this)" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="Sneakers">Sneakers</option>
                    <option value="Boots Shoes">Boots Shoes</option>
                    <option value="Outdoor Shoes">Outdoor Shoes</option>
                    <option value="Leather Shoes">Leather Shoes</option>
                    <option value="Women Shoes">Women Shoes</option>
                    <option value="Repaint">Repaint</option>
                    <option value="Unyellowing">Unyellowing</option>
                </select>
            </div>
            <div class="form-group fg-merk">
                <div class="form-row-split">
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label>Merek <span>*</span></label>
                        <input type="text" class="form-input merk-input" name="sepatu[]" placeholder="Merek (cth: Nike)" required style="width: 100%;" oninput="updateCheckoutSummary()">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label>Ukuran <span>*</span></label>
                        <input type="text" class="form-input ukuran-input" name="ukuran[]" placeholder="Size (cth: 42)" required style="width: 100%;" oninput="updateCheckoutSummary()">
                    </div>
                </div>
            </div>
            <div class="form-group fg-layanan">
                <label>Jenis Layanan <span>*</span></label>
                <select class="form-select lay-select" name="layanan_id[]" required onchange="updatePrice(); checkExtraTreatmentPrompt(this.closest('.item-row'));" disabled>
                    <option value="">-- Pilih Kategori Terlebih Dahulu --</option>
                </select>
            </div>
            <div class="form-group fg-jumlah">
                <div class="form-row-split">
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label class="label-jumlah">Jumlah Item <span>*</span></label>
                        <input type="number" class="form-input jum-input" name="jumlah[]" value="1" min="1" required onchange="updatePrice()">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label>Warna <span>*</span></label>
                        <input type="text" class="form-input warna-input" name="warna[]" placeholder="cth: Hitam" required style="width: 100%;" oninput="updateCheckoutSummary()">
                    </div>
                </div>
            </div>
            <div class="form-group fg-extra" style="grid-column: 1 / -1; margin-top: -8px;">
                <div class="extra-treatment-box" style="display:none; background:linear-gradient(135deg,#f0f9ff,#e0f2fe); border:1.5px solid #7dd3fc; border-radius:10px; padding:14px;">
                    <p style="margin:0 0 10px 0; font-size:clamp(0.75rem, 3.5vw, 0.9rem); font-weight:700; color:#0369a1;">Ingin menambahkan Extra Treatment?</p>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn-et-ya" onclick="pilihExtraTreatment(this,true)" style="flex:1; padding:8px 12px; background:#0ea5e9; color:white; border:none; border-radius:7px; font-size:clamp(0.7rem, 3vw, 0.85rem); font-weight:700; cursor:pointer; transition:0.2s;">Ya, Tambahkan</button>
                        <button type="button" class="btn-et-tidak" onclick="pilihExtraTreatment(this,false)" style="flex:1; padding:8px 12px; background:#e2e8f0; color:#475569; border:none; border-radius:7px; font-size:clamp(0.7rem, 3vw, 0.85rem); font-weight:700; cursor:pointer; transition:0.2s;">Tidak</button>
                    </div>
                    <div class="et-select-box" style="display:none; margin-top:14px;">
                        <label style="font-size:clamp(0.7rem, 3vw, 0.85rem); font-weight:600; color:#0369a1; display:block; margin-bottom:6px;">Jenis Extra Treatment <span style="color:red;">*</span></label>
                        <select name="extra_layanan_id[]" class="form-select et-select" onchange="updatePrice()" style="width:100%;">
                            <option value="">-- Pilih Extra Treatment --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="est-item-box" style="display:none; margin-top: 14px; background: #eff6ff; padding: 10px 12px; border-radius: 10px; border: 1px solid #bfdbfe; font-size: clamp(0.65rem, 2.8vw, 0.75rem); color: #1e3a8a; font-weight: 600; line-height: 1.4; text-align: center;">
            <span style="display: inline-block;">Estimasi Pengerjaan: <strong class="item-estimasi-text">3 - 5 Hari Kerja</strong></span>
        </div>
    `;
    container.appendChild(row);
    updateItemNumbers();
}

function hapusItem(btn) {
    const row = btn.closest('.item-row');
    row.remove();
    updateItemNumbers();
    updatePrice();
}

function togglePengirimanUI(radioEl) {
    document.querySelectorAll('.shipping-method-card').forEach(c => c.classList.remove('selected'));
    if (radioEl && radioEl.closest('.shipping-method-card')) {
        radioEl.closest('.shipping-method-card').classList.add('selected');
    }

    const group = document.getElementById('alamatGroup');
    const inputAlamat = document.getElementById('oAlamat');
    const inputWaktu = document.getElementById('oWaktuJemput');
    const inputTanggal = document.getElementById('oTanggal');
    const infoOngkir = document.getElementById('infoOngkirBox'); // Panggil kotak informasi
    const infoToko = document.getElementById('infoAmbilToko');

    if (radioEl.getAttribute('data-perlu-jemput') === '1') {
        group.classList.add('active');
        group.style.display = 'block';
        if (infoToko) infoToko.style.display = 'none';
        inputAlamat.required = true;
        inputWaktu.required = true;
        if (inputTanggal) inputTanggal.required = true;

        if (infoOngkir) {
            infoOngkir.style.display = 'block';

            // Lazy init: buat map pertama kali saat section visible
            if (!mapCreated) {
                createMap(); // createMap sudah handle delay 200ms sendiri
            } else {
                // Map sudah ada, perlu resize karena container sempat display:none
                if (map) {
                    setTimeout(() => {
                        google.maps.event.trigger(map, 'resize');
                        // Gunakan key yang benar: 'bup_lat' / 'bup_lng'
                        const savedLat = sessionStorage.getItem('bup_lat');
                        const savedLng = sessionStorage.getItem('bup_lng');
                        if (savedLat && savedLng) {
                            map.setCenter({ lat: parseFloat(savedLat), lng: parseFloat(savedLng) });
                        } else {
                            map.setCenter({ lat: STORE_LAT, lng: STORE_LNG });
                        }
                    }, 200); // 200ms agar browser selesai render container
                }
                // Hanya lacak ulang jika bukan dari state restore
                if (!inputAlamat.value) {
                    if (typeof autoGetLocation === 'function') autoGetLocation();
                }
            }
        }
    } else {
        group.classList.remove('active');
        group.style.display = 'none';
        inputAlamat.required = false;
        inputWaktu.required = false;
        if (inputTanggal) inputTanggal.required = false;
        inputAlamat.value = '';
        inputWaktu.value = '';
        if (inputTanggal) inputTanggal.value = '';

        // Sembunyikan informasi ongkir jika tidak perlu jemput
        if (infoOngkir) infoOngkir.style.display = 'none'; // Sembunyikan saat antar ke toko
        if (infoToko) infoToko.style.display = 'block'; // Tampilkan info toko
    }
    updatePrice();
}

function updateLayananDynamic(selEl) {
    const kategoriPilihan = selEl.value;
    const row = selEl.closest('.item-row');
    const layananSelect = row.querySelector('.lay-select');

    layananSelect.innerHTML = '<option value="">-- Pilih Layanan --</option>';

    const merkInput = row.querySelector('.merk-input');

    if (kategoriPilihan === "") {
        layananSelect.disabled = true;
        if (merkInput) merkInput.placeholder = 'Merek (cth: Nike)';
        updatePrice();
        return;
    }

    if (merkInput) {
        merkInput.placeholder = 'cth: Nike';
    }

    layananSelect.disabled = false;
    DATA_LAYANAN.forEach(function (item) {
        if (item.kategori === kategoriPilihan) {
            const optionBaru = document.createElement('option');
            optionBaru.value = item.id;
            optionBaru.setAttribute('data-price', item.harga);
            const hargaRupiah = new Intl.NumberFormat('id-ID').format(item.harga);
            optionBaru.textContent = item.nama + ' (Rp ' + hargaRupiah + ')';
            layananSelect.appendChild(optionBaru);
        }
    });

    // Ukuran visibility is now handled inside updatePrice()
    // Reset ET box when category changes
    const etBox = row.querySelector('.extra-treatment-box');
    if (etBox) {
        etBox.style.display = 'none';
        const etSelectBox = etBox.querySelector('.et-select-box');
        const etSelect = etBox.querySelector('.et-select');
        if (etSelectBox) etSelectBox.style.display = 'none';
        if (etSelect) { etSelect.innerHTML = '<option value="">-- Pilih Extra Treatment --</option>'; }
        const btnYa = etBox.querySelector('.btn-et-ya');
        const btnTidak = etBox.querySelector('.btn-et-tidak');
        if (btnYa) { btnYa.style.background = '#0ea5e9'; btnYa.style.boxShadow = ''; }
        if (btnTidak) { btnTidak.style.background = '#e2e8f0'; btnTidak.style.color = '#475569'; btnTidak.style.boxShadow = ''; }
    }

    updatePrice();
}

function checkExtraTreatmentPrompt(row) {
    if (!row) return;
    const katSel = row.querySelector('.kat-select');
    const laySel = row.querySelector('.lay-select');
    const etBox = row.querySelector('.extra-treatment-box');
    if (!etBox || !katSel || !laySel) return;

    const kat = katSel.value;
    const layText = laySel.selectedIndex > 0 ? laySel.options[laySel.selectedIndex].textContent : '';
    const etSelect = etBox.querySelector('.et-select');
    const etSelectBox = etBox.querySelector('.et-select-box');
    const btnYa = etBox.querySelector('.btn-et-ya');
    const btnTidak = etBox.querySelector('.btn-et-tidak');

    // Reset state
    etBox.style.display = 'none';
    if (etSelectBox) etSelectBox.style.display = 'none';
    if (etSelect) etSelect.innerHTML = '<option value="">-- Pilih Extra Treatment --</option>';
    if (btnYa) { btnYa.style.background = '#0ea5e9'; btnYa.style.boxShadow = ''; }
    if (btnTidak) { btnTidak.style.background = '#e2e8f0'; btnTidak.style.color = '#475569'; btnTidak.style.boxShadow = ''; }

    if (!laySel.value) return;

    let etOptions = [];
    if (kat === 'Repaint' && !layText.toLowerCase().includes('hat')) {
        etOptions = DATA_LAYANAN.filter(l => l.kategori === 'Extra Treatment');
    } else if (kat === 'Unyellowing') {
        etOptions = DATA_LAYANAN.filter(l => l.kategori === 'Extra Treatment' && l.nama.toLowerCase().includes('deep clean'));
    }

    if (etOptions.length > 0 && etSelect) {
        etOptions.forEach(opt => {
            const o = document.createElement('option');
            o.value = opt.id;
            o.setAttribute('data-price', opt.harga);
            const hargaRupiah = new Intl.NumberFormat('id-ID').format(opt.harga);
            o.textContent = opt.nama + ' (Rp ' + hargaRupiah + ')';
            etSelect.appendChild(o);
        });
        etBox.style.display = 'block';
    }
}

function pilihExtraTreatment(btn, ya) {
    const etBox = btn.closest('.extra-treatment-box');
    const etSelectBox = etBox.querySelector('.et-select-box');
    const etSelect = etBox.querySelector('.et-select');
    const btnYa = etBox.querySelector('.btn-et-ya');
    const btnTidak = etBox.querySelector('.btn-et-tidak');

    if (ya) {
        etSelectBox.style.display = 'block';
        if (btnYa) { btnYa.style.background = '#0284c7'; btnYa.style.boxShadow = '0 0 0 2px #bae6fd'; }
        if (btnTidak) { btnTidak.style.background = '#e2e8f0'; btnTidak.style.color = '#475569'; btnTidak.style.boxShadow = ''; }
    } else {
        etSelectBox.style.display = 'none';
        if (etSelect) etSelect.value = '';
        if (btnTidak) { btnTidak.style.background = '#64748b'; btnTidak.style.color = 'white'; btnTidak.style.boxShadow = '0 0 0 2px #cbd5e1'; }
        if (btnYa) { btnYa.style.background = '#0ea5e9'; btnYa.style.boxShadow = ''; }
    }
    updatePrice();
}

function updatePrice() {
    let totalLayanan = 0;

    const layananSelects = document.querySelectorAll('.lay-select');
    const jumlahInputs = document.querySelectorAll('.jum-input');

    layananSelects.forEach(function (sel, index) {
        if (!sel.disabled && sel.selectedIndex > 0) {
            const opt = sel.options[sel.selectedIndex];
            const jml = parseInt(jumlahInputs[index].value) || 1;
            if (opt && opt.getAttribute('data-price')) {
                totalLayanan += parseInt(opt.getAttribute('data-price')) * jml;
            }
        }

        let isLongProcessItem = false;
        const row = sel.closest('.item-row');
        if (row) {
            const katSel = row.querySelector('.kat-select');
            const ukuranInput = row.querySelector('.ukuran-input');
            if (katSel && ukuranInput) {
                const kat = katSel.value;
                const layananText = sel.selectedIndex > 0 ? sel.options[sel.selectedIndex].textContent : "";

                if (kat.toLowerCase().includes('repaint') || kat.toLowerCase().includes('unyellowing') || layananText.toLowerCase().includes('repaint') || layananText.toLowerCase().includes('unyellowing')) {
                    isLongProcessItem = true;
                }

                const noSizeKategori = [];
                const hideSize = noSizeKategori.includes(kat);

                const ukuranContainer = ukuranInput.closest('.form-group');
                if (hideSize) {
                    if (ukuranContainer) ukuranContainer.style.display = 'none';
                    ukuranInput.required = false;
                    ukuranInput.value = '';
                } else {
                    if (ukuranContainer) ukuranContainer.style.display = 'flex';
                    ukuranInput.required = true;
                }

                const jumLabel = row.querySelector('.label-jumlah');
                if (jumLabel) {
                    if (!hideSize) {
                        jumLabel.innerHTML = 'Jumlah <span>*</span>';
                    } else {
                        jumLabel.innerHTML = 'Jumlah Item <span>*</span>';
                    }
                }
            }
        }

        // Extra Treatment price
        const etSel = row ? row.querySelector('.et-select') : null;
        if (etSel && etSel.value && etSel.selectedIndex > 0) {
            const etOpt = etSel.options[etSel.selectedIndex];
            const jml = parseInt(jumlahInputs[index].value) || 1;
            if (etOpt && etOpt.getAttribute('data-price')) {
                const etText = etOpt.textContent.toLowerCase();
                if (etText.includes('repaint') || etText.includes('unyellowing')) {
                    isLongProcessItem = true;
                }
                totalLayanan += parseInt(etOpt.getAttribute('data-price')) * jml;
            }
        }

        if (row) {
            const katSel = row.querySelector('.kat-select');
            const estItemBox = row.querySelector('.est-item-box');
            const estItemText = row.querySelector('.item-estimasi-text');
            if (katSel && katSel.value !== "" && sel.value !== "") {
                if (estItemBox) estItemBox.style.display = 'flex';
                if (estItemText) estItemText.textContent = isLongProcessItem ? '7 - 10 Hari Kerja' : '3 - 5 Hari Kerja';
            } else {
                if (estItemBox) estItemBox.style.display = 'none';
            }
        }
    });

    const priceEl = document.getElementById('priceEst');

    const metode = document.querySelector('input[name="metode_pengiriman"]:checked');
    let perluJemput = false;
    let biayaKirim = 0;
    if (metode) {
        perluJemput = metode.getAttribute('data-perlu-jemput') === '1';
        if (perluJemput && typeof ongkirDinamis !== 'undefined') {
            biayaKirim = (ongkirDinamis === -1) ? 0 : ongkirDinamis;
        } else {
            biayaKirim = parseInt(metode.getAttribute('data-biaya')) || 0;
        }
    }

    const totalSemua = totalLayanan + biayaKirim;

    const warningEl = document.getElementById('priceOngkirWarning');
    if (totalLayanan > 0) {
        if (priceEl) priceEl.textContent = 'Rp ' + totalSemua.toLocaleString('id-ID');
        if (warningEl) {
            if (perluJemput) {
                if (typeof ongkirDinamis !== 'undefined' && ongkirDinamis === -1) {
                    warningEl.textContent = '*Belum termasuk ongkir (Akan diinfo via WA)';
                    warningEl.style.color = '#64748b';
                } else {
                    warningEl.textContent = '(Termasuk ongkir: Rp ' + biayaKirim.toLocaleString('id-ID') + ')';
                    warningEl.style.color = '#1e293b';
                }
                warningEl.style.display = 'block';
            } else {
                warningEl.style.display = 'none';
            }
        }
    } else {
        if (priceEl) priceEl.textContent = 'Pilih layanan terlebih dahulu';
        if (warningEl) {
            warningEl.style.display = 'none';
        }
    }

    if (typeof updateCheckoutSummary === 'function') {
        updateCheckoutSummary();
    }
}

function selectPay(el, val) {
    document.querySelectorAll('.payment-card').forEach(x => x.classList.remove('selected'));
    if (el) el.classList.add('selected');
    document.getElementById('paymentInput').value = val;

    const infoBox = document.getElementById('paymentInfoBox');
    const infoTitle = document.getElementById('paymentInfoTitle');
    const infoDesc = document.getElementById('paymentInfoDesc');

    if (infoBox && infoTitle && infoDesc) {
        infoBox.style.display = 'block';
        if (val === 'transfer_bca') {
            infoTitle.textContent = 'Transfer Bank BCA';
            infoDesc.innerHTML = 'Pembayaran dilakukan melalui transfer ke rekening BCA yang tersedia setelah pesanan dibuat. Bukti transfer wajib diunggah agar pesanan dapat diproses.';
        } else if (val === 'tunai') {
            infoTitle.textContent = 'Info Bayar di Tempat (Tunai)';
            infoDesc.innerHTML = 'Anda memilih bayar tunai. Silakan siapkan uang tunai sejumlah total tagihan pesanan. Pembayaran diserahkan kepada kurir atau admin toko kami.';
        }
    }
}

function previewBukti(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('uploadInstructions').style.display = 'none';
            document.getElementById('imgPreview').src = e.target.result;
            document.getElementById('imgPreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function startCountdown(deadline) {
    const timerEl = document.getElementById('countdownTimer');
    document.getElementById('timerWarning').style.display = 'flex';
    if (timerInterval) clearInterval(timerInterval);

    function updateTimer() {
        const now = new Date().getTime();
        const distance = deadline - now;
        if (distance < 0) {
            clearInterval(timerInterval);
            timerEl.innerHTML = "WAKTU HABIS";
            localStorage.removeItem('paymentDeadline');
            localStorage.removeItem('bup_order_state');
            localStorage.removeItem('bup_step');
            isPaymentStep = false;
            window.location.href = 'order.php';
            return;
        }
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        timerEl.innerHTML = String(hours).padStart(2, '0') + ":" + String(minutes).padStart(2, '0') + ":" + String(seconds).padStart(2, '0');
    }
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
}

function saveState() {
    const state = {
        nama: document.getElementById('oNama').value,
        wa: document.getElementById('oWa').value,
        items: [],
        metode_pengiriman: document.querySelector('input[name="metode_pengiriman"]:checked')?.value || '',
        alamat: document.getElementById('oAlamat').value,
        waktu_penjemputan: document.getElementById('oWaktuJemput').value,
        tanggal: document.getElementById('oTanggal').value,
        catatan: document.getElementById('oCatatan').value,
        metode_bayar: document.getElementById('paymentInput').value,
        ongkirDinamis: (typeof ongkirDinamis !== 'undefined') ? ongkirDinamis : 0
    };

    const kats = document.querySelectorAll('.kat-select');
    const merks = document.querySelectorAll('.merk-input');
    const ukurans = document.querySelectorAll('.ukuran-input');
    const warnas = document.querySelectorAll('.warna-input');
    const lays = document.querySelectorAll('.lay-select');
    const jums = document.querySelectorAll('.jum-input');
    const ets = document.querySelectorAll('.et-select');

    for (let i = 0; i < kats.length; i++) {
        state.items.push({
            kat: kats[i].value,
            merk: merks[i].value,
            ukuran: ukurans[i] ? ukurans[i].value : '',
            warna: warnas[i] ? warnas[i].value : '',
            lay: lays[i].value,
            jum: jums[i].value,
            et: ets[i] ? ets[i].value : ''
        });
    }
    localStorage.setItem('bup_order_state', JSON.stringify(state));
}

function goToStep(step) {
    if (step === 2) {
        if (!document.getElementById('oNama').value.trim()) return alert('Nama wajib diisi');
        if (!document.getElementById('oWa').value.trim()) return alert('Nomor WA wajib diisi');

        const katChecks = document.querySelectorAll('.kat-select');
        const layChecks = document.querySelectorAll('.lay-select');
        const mChecks = document.querySelectorAll('.merk-input');
        const uChecks = document.querySelectorAll('.ukuran-input');
        const wChecks = document.querySelectorAll('.warna-input');

        for (let i = 0; i < katChecks.length; i++) {
            if (!katChecks[i].value) return alert(`Item #${i + 1}: Pilih kategori sepatu!`);
            if (!layChecks[i].value) return alert(`Item #${i + 1}: Pilih jenis layanan!`);
            if (!mChecks[i].value.trim()) return alert(`Item #${i + 1}: Merek sepatu wajib diisi!`);

            const kat = katChecks[i].value;
            const layText = layChecks[i].selectedIndex > 0 ? layChecks[i].options[layChecks[i].selectedIndex].textContent : "";
            const isNoSize = false;
            if (!isNoSize && (!uChecks[i] || !uChecks[i].value.trim())) return alert(`Item #${i + 1}: Ukuran sepatu wajib diisi!`);
            if (!wChecks[i] || !wChecks[i].value.trim()) return alert(`Item #${i + 1}: Warna wajib diisi!`);
        }
    }

    if (step === 3) {
        const mt = document.querySelector('input[name="metode_pengiriman"]:checked');
        if (!mt) return alert('Pilih metode pengiriman');

        const alamatGroup = document.getElementById('alamatGroup');
        if (alamatGroup && alamatGroup.classList.contains('active')) {
            if (typeof ongkirDinamis !== 'undefined' && ongkirDinamis === -1) {
                return alert('Maaf, layanan Antar Jemput Rumah hanya melayani maksimal jarak 25 KM. Silakan ubah metode pengiriman ke "Antar & Ambil di Toko".');
            }
            if (!document.getElementById('oAlamat').value.trim()) return alert('Alamat wajib diisi (Pilih/Konfirmasi dari Peta)!');
            if (!document.getElementById('oWaktuJemput').value) return alert('Waktu penjemputan wajib dipilih!');
        }
    }

    document.querySelectorAll('[id^="checkout-step-"]').forEach(el => el.style.display = 'none');
    document.getElementById('checkout-step-' + step).style.display = 'block';

    document.querySelectorAll('.step-item').forEach((el, index) => {
        el.classList.remove('active', 'done');
        if (index + 1 < step) el.classList.add('done');
        else if (index + 1 === step) el.classList.add('active');
    });

    localStorage.setItem('bup_step', step.toString());
    if (typeof saveState === 'function') {
        saveState();
    }

    // UPDATE BUTTONS DI STICKY SIDEBAR
    const btnLanjut = document.getElementById('btnLanjutUtama');
    const btnKembali = document.getElementById('btnKembaliUtama');
    if (btnLanjut && btnKembali) {
        if (step === 1) {
            btnKembali.style.display = 'flex';
            btnKembali.onclick = function () { clearOrderState(); window.location.href = '../index.php'; };
            btnLanjut.style.background = 'var(--blue)';
            btnLanjut.style.boxShadow = 'none';
            btnLanjut.innerHTML = 'Lanjut ke Pengiriman';
            btnLanjut.onclick = function () { goToStep(2); };
        } else if (step === 2) {
            btnKembali.style.display = 'flex';
            btnKembali.onclick = function () { goToStep(1); };
            btnLanjut.style.background = 'var(--blue)';
            btnLanjut.style.boxShadow = 'none';
            btnLanjut.innerHTML = 'Lanjut ke Pembayaran';
            btnLanjut.onclick = function () { goToStep(3); };
        } else if (step === 3) {
            btnKembali.style.display = 'flex';
            btnKembali.onclick = function () { goToStep(2); };
            btnLanjut.style.background = 'var(--blue)';
            btnLanjut.style.boxShadow = 'none';
            btnLanjut.innerHTML = 'Buat Pesanan';
            btnLanjut.onclick = function () {
                const pay = document.getElementById('paymentInput').value;
                if (!pay) return alert('Silakan pilih metode pembayaran terlebih dahulu!');
                renderKonfirmasi();
                goToStep(4);
            };
        }
    }

    const checkoutRight = document.getElementById('checkoutRight');
    if (checkoutRight) {
        if (step === 4) {
            checkoutRight.style.display = 'none';
        } else {
            checkoutRight.style.display = 'block';
        }
    }

    window.scrollTo({ top: 0, behavior: 'smooth' });
    updateCheckoutSummary();
}

function handleLanjut() {
    const step = parseInt(localStorage.getItem('bup_step')) || 1;
    if (step === 1) goToStep(2);
    else if (step === 2) goToStep(3);
    else if (step === 3) {
        const pay = document.getElementById('paymentInput').value;
        if (!pay) return alert('Silakan pilih metode pembayaran terlebih dahulu!');
        renderKonfirmasi();
        goToStep(4);
    }
    else if (step === 4) prosesPesanan();
}

function handleKembali() {
    const step = parseInt(localStorage.getItem('bup_step')) || 1;
    if (step === 1) {
        clearOrderState();
        window.location.href = '../index.php';
    }
    else if (step === 2) goToStep(1);
    else if (step === 3) goToStep(2);
    else if (step === 4) goToStep(3);
}

function renderKonfirmasi() {
    const nama = document.getElementById('oNama').value || '-';
    const wa = document.getElementById('oWa').value || '-';

    document.getElementById('konfNama').textContent = nama;
    document.getElementById('konfWa').textContent = wa;

    const metodeKirim = document.querySelector('input[name="metode_pengiriman"]:checked');
    let pengirimanStr = '-';
    let perluJemput = false;
    if (metodeKirim) {
        pengirimanStr = metodeKirim.nextElementSibling ? metodeKirim.nextElementSibling.nextElementSibling.textContent : '-';
        perluJemput = metodeKirim.getAttribute('data-perlu-jemput') === '1';
    }
    document.getElementById('konfPengiriman').textContent = pengirimanStr;

    const konfAlamatGroup = document.getElementById('konfAlamatGroup');
    if (perluJemput) {
        konfAlamatGroup.style.display = 'grid';
        const alamat = document.getElementById('oAlamat').value;
        const tgl = document.getElementById('oTanggal').value;
        const wkt = document.getElementById('oWaktuJemput').value;
        document.getElementById('konfAlamat').innerHTML = `${alamat}<br><div style="color:#64748b; font-size:clamp(0.7rem, 3vw, 0.8rem); margin-top:4px; display:flex; flex-wrap:wrap; column-gap:6px;"><span style="white-space:nowrap;">${tgl}</span><span style="white-space:nowrap;">|</span><span style="white-space:nowrap;">${wkt}</span></div>`;
    } else {
        konfAlamatGroup.style.display = 'none';
    }

    const pay = document.getElementById('paymentInput').value;
    let payStr = '-';
    if (pay === 'transfer_bca') payStr = 'Transfer BCA';
    else if (pay === 'tunai') payStr = 'Tunai (Bayar di tempat)';
    document.getElementById('konfBayar').textContent = payStr;

    const catatanVal = document.getElementById('oCatatan').value.trim();
    if (catatanVal) {
        document.getElementById('konfCatatanGroup').style.display = 'grid';
        document.getElementById('konfCatatan').textContent = catatanVal;
    } else {
        document.getElementById('konfCatatanGroup').style.display = 'none';
    }

    // Mengambil nilai ringkasan dari sidebar kanan
    const ringkasanHtml = document.getElementById('daftarLayananSummary').innerHTML;
    document.getElementById('konfItemsHtml').innerHTML = ringkasanHtml || '<div style="color:#64748b; font-size:0.85rem;">Tidak ada layanan terpilih</div>';

    document.getElementById('konfSubtotal').textContent = document.getElementById('sumSubtotal').textContent;

    // Tampilkan ongkir jika perlu dijemput, jika tidak sembunyikan baris ongkir di konfirmasi
    if (perluJemput) {
        document.getElementById('konfOngkirRow').style.display = 'flex';
        document.getElementById('konfOngkir').textContent = document.getElementById('sumOngkir').textContent;
    } else {
        document.getElementById('konfOngkirRow').style.display = 'none';
    }

    document.getElementById('konfTotal').textContent = document.getElementById('sumTotal').textContent;
}

function updateCheckoutSummary() {
    const kategoriSelects = document.querySelectorAll('.kat-select');
    const layananSelects = document.querySelectorAll('.lay-select');
    const jumlahInputs = document.querySelectorAll('.jum-input');
    const merkInputs = document.querySelectorAll('.merk-input');
    const ukuranInputs = document.querySelectorAll('.ukuran-input');
    const warnaInputs = document.querySelectorAll('.warna-input');

    let totalLayanan = 0;
    let daftarLayananHtml = '';

    for (let i = 0; i < kategoriSelects.length; i++) {
        const selIndex = layananSelects[i].selectedIndex;
        const opt = selIndex >= 0 ? layananSelects[i].options[selIndex] : null;

        const jml = parseInt(jumlahInputs[i].value) || 1;
        let itemLayananPrice = 0;
        if (opt && opt.getAttribute('data-price')) {
            itemLayananPrice = parseInt(opt.getAttribute('data-price')) * jml;
            totalLayanan += itemLayananPrice;
        }

        const row = layananSelects[i].closest('.item-row');
        let etAddonHtml = '';
        const etSel = row ? row.querySelector('.et-select') : null;
        if (etSel && etSel.value && etSel.selectedIndex > 0) {
            const etOpt = etSel.options[etSel.selectedIndex];
            const etPrice = parseInt(etOpt.getAttribute('data-price')) * jml;
            totalLayanan += etPrice;
            etAddonHtml = `
                <div style="font-size: 0.7rem; color: #64748b; margin-top: 4px; display:flex; justify-content:space-between; align-items:flex-start; gap: 8px;">
                    <span style="flex:1; word-break: break-word;">+ Extra: ${etOpt.text.split(' (Rp')[0]}</span>
                    <span style="color:#0f172a; font-weight: 700; font-size: 0.75rem; flex-shrink: 0;">Rp ${etPrice.toLocaleString('id-ID')}</span>
                </div>
            `;
        }

        if (kategoriSelects[i].value && opt && opt.getAttribute('data-price')) {
            let merkVal = merkInputs[i] ? merkInputs[i].value.trim() : '';
            let ukVal = ukuranInputs[i] && ukuranInputs[i].value.trim() !== '' ? ' | Size ' + ukuranInputs[i].value.trim() : '';
            let wrnVal = warnaInputs[i] && warnaInputs[i].value.trim() !== '' ? ' | ' + warnaInputs[i].value.trim() : '';
            let detailHtml = '';
            if (merkVal || wrnVal) {
                detailHtml = `<div style="font-size: 0.7rem; color: #64748b; margin-top: 2px; word-break: break-word;">${merkVal}${ukVal}${wrnVal}</div>`;
            }

            daftarLayananHtml += `
            <div style="margin-bottom:12px; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0;">
                <div style="display:flex; justify-content:space-between; align-items: flex-start; gap:8px;">
                    <div style="flex:1; min-width: 0;">
                        <div style="font-size: 0.8rem; font-weight: 700; color: #1e293b; line-height: 1.3; word-break: break-word;">${kategoriSelects[i].value} - ${opt.text.split(' (Rp')[0]} <span style="font-weight: 800; color: var(--blue);">(x${jml})</span></div>
                        ${detailHtml}
                    </div>
                    <div style="font-size: 0.8rem; font-weight: 700; color: #0f172a; flex-shrink: 0;">Rp ${itemLayananPrice.toLocaleString('id-ID')}</div>
                </div>
                ${etAddonHtml}
            </div>`;
        }
    }

    if (document.getElementById('daftarLayananSummary')) {
        document.getElementById('daftarLayananSummary').innerHTML = daftarLayananHtml;
    }

    if (document.getElementById('inlineTotalStep1')) {
        document.getElementById('inlineTotalStep1').textContent = totalLayanan > 0 ? 'Rp ' + totalLayanan.toLocaleString('id-ID') : 'Pilih layanan terlebih dahulu';
    }

    const metodeCek = document.querySelector('input[name="metode_pengiriman"]:checked');
    let biayaKirimSum = 0;
    let perluJemput = false;
    let namaMetodeKirim = '-';
    if (metodeCek) {
        namaMetodeKirim = metodeCek.nextElementSibling ? metodeCek.nextElementSibling.nextElementSibling.textContent : 'Pengiriman';
        perluJemput = metodeCek.getAttribute('data-perlu-jemput') === '1';
        if (perluJemput && typeof ongkirDinamis !== 'undefined') {
            biayaKirimSum = (ongkirDinamis === -1) ? 0 : ongkirDinamis;
        } else {
            biayaKirimSum = parseInt(metodeCek.getAttribute('data-biaya')) || 0;
        }
    }

    const totalSemua = totalLayanan + biayaKirimSum;

    let itemCount = 0;
    for (let i = 0; i < kategoriSelects.length; i++) {
        const selIndex = layananSelects[i].selectedIndex;
        const opt = selIndex >= 0 ? layananSelects[i].options[selIndex] : null;
        const jml = parseInt(jumlahInputs[i].value) || 1;

        if (kategoriSelects[i].value && opt && opt.getAttribute('data-price')) {
            itemCount += jml;
        }
    }

    if (document.getElementById('sumItems')) {
        document.getElementById('sumItems').textContent = itemCount + ' Item';
    }
    if (document.getElementById('sumSubtotal')) {
        document.getElementById('sumSubtotal').textContent = 'Rp ' + totalLayanan.toLocaleString('id-ID');
    }
    if (document.getElementById('sumOngkir')) {
        let textOngkir = 'Rp ' + biayaKirimSum.toLocaleString('id-ID');
        if (perluJemput && typeof ongkirDinamis !== 'undefined' && ongkirDinamis === -1) {
            textOngkir = 'Menunggu Info WA';
        } else if (!metodeCek) {
            textOngkir = 'Rp 0';
        }
        document.getElementById('sumOngkir').textContent = textOngkir;
    }
    if (document.getElementById('sumTotal')) {
        document.getElementById('sumTotal').textContent = 'Rp ' + totalSemua.toLocaleString('id-ID');
    }

    const rowBiaya = document.getElementById('rowBiayaPengiriman');
    if (rowBiaya) {
        if (perluJemput) {
            rowBiaya.style.display = 'flex';
        } else {
            rowBiaya.style.display = 'none';
        }
    }
}

async function prosesPesanan() {
    const pay = document.getElementById('paymentInput').value;
    if (!pay) return alert('Silakan pilih metode pembayaran terlebih dahulu!');

    const form = document.getElementById('orderForm');
    const btn = document.getElementById('btnKonfirmasiAkhir') || document.getElementById('btnLanjutUtama');

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Memproses...';
    }

    document.querySelectorAll('.lay-select').forEach(sel => sel.disabled = false);

    const formData = new FormData(form);

    try {
        const res = await fetch('../api/order.php?action=create', { method: 'POST', body: formData });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert('Terjadi kesalahan format response dari server.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Konfirmasi Pesanan';
            }
            return;
        }

        if (data.success) {
            clearOrderState();
            isPaymentStep = false;
            window.location.href = `customer/my-orders.php?new_order=1`;
        } else {
            alert(data.message || 'Gagal menyimpan pesanan.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Konfirmasi Pesanan';
            }
        }
    } catch (e) {
        alert('Terjadi kesalahan jaringan.');
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Konfirmasi Pesanan';
        }
    }
}

// === Google Maps API Logic ===
let ongkirDinamis = 0;
const STORE_LAT = -6.199423905832486;
const STORE_LNG = 106.94010889513326;
let map;
let draggableMarker;
let mapsApiReady = false;  // Flag: API sudah dimuat
let mapCreated = false;    // Flag: map sudah dibuat (lazy)

// initGoogleMaps dipanggil oleh Google Maps API callback
// Tugasnya HANYA menandai API siap — map dibuat secara lazy
// saat section pertama kali ditampilkan (agar tidak blank)
function initGoogleMaps() {
    mapsApiReady = true;

    // Jika section sudah visible (misal restore state), langsung buat map
    const infoBox = document.getElementById('infoOngkirBox');
    if (infoBox && infoBox.style.display !== 'none') {
        createMap();
    }
}

// Buat map hanya saat container sudah visible (lazy init)
function createMap() {
    if (mapCreated || !mapsApiReady) return;
    // JANGAN set mapCreated = true di sini dulu — set setelah map benar-benar dibuat

    const oAlamat = document.getElementById('oAlamat');
    const searchAlamat = document.getElementById('searchAlamat');
    if (!oAlamat) return;

    let initLat = STORE_LAT;
    let initLng = STORE_LNG;
    let hasSavedLocation = false;

    const oLatInput = document.getElementById('oLat');
    const oLngInput = document.getElementById('oLng');

    // Coba ambil dari sessionStorage dulu
    const sessionLat = sessionStorage.getItem('bup_lat');
    const sessionLng = sessionStorage.getItem('bup_lng');

    if (sessionLat && sessionLng && !isNaN(parseFloat(sessionLat)) && !isNaN(parseFloat(sessionLng))) {
        initLat = parseFloat(sessionLat);
        initLng = parseFloat(sessionLng);
        hasSavedLocation = true;
    } else if (oLatInput && oLngInput && oLatInput.value && oLngInput.value &&
        !isNaN(parseFloat(oLatInput.value)) && !isNaN(parseFloat(oLngInput.value))) {
        initLat = parseFloat(oLatInput.value);
        initLng = parseFloat(oLngInput.value);
        hasSavedLocation = true;
    }
    const mapEl = document.getElementById('map');
    if (!mapEl) return;

    // Tunggu 200ms agar browser selesai render container (cegah 0x0 size)
    // requestAnimationFrame tidak cukup karena layout belum final saat baru display:block
    setTimeout(() => {
        // Cek ulang apakah container benar-benar visible & punya ukuran
        if (mapEl.offsetWidth === 0 || mapEl.offsetHeight === 0) {
            // Container masih 0x0, retry setelah 300ms lagi
            setTimeout(() => {
                if (!mapCreated) createMap();
            }, 300);
            return;
        }

        mapCreated = true; // Set di sini — setelah kita yakin container sudah punya ukuran

        map = new google.maps.Map(mapEl, {
            center: { lat: initLat, lng: initLng },
            zoom: 13,
            disableDefaultUI: true,
            zoomControl: true,
            gestureHandling: 'greedy'
        });

        // Marker pelanggan yang bisa digeser
        draggableMarker = new google.maps.Marker({
            map: map,
            draggable: true,
            position: { lat: initLat, lng: initLng },
            title: 'Geser pin ini ke lokasi Anda',
            animation: google.maps.Animation.DROP
        });

        // Force resize setelah map dibuat untuk pastikan tile terload penuh
        google.maps.event.trigger(map, 'resize');
        map.setCenter({ lat: initLat, lng: initLng });

        // Reverse geocode saat marker digeser atau peta diklik
        function updateLocationFromLatLng(newPos) {
            const lat = typeof newPos.lat === 'function' ? newPos.lat() : newPos.lat;
            const lng = typeof newPos.lng === 'function' ? newPos.lng() : newPos.lng;
            draggableMarker.setPosition({ lat, lng });
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    if (searchAlamat) searchAlamat.value = results[0].formatted_address;
                } else {
                    if (searchAlamat) searchAlamat.value = 'Lokasi Peta';
                }
            });
            hitungJarak({ lat, lng });
        }

        if (hasSavedLocation) {
            updateLocationFromLatLng({ lat: initLat, lng: initLng });
        }

        google.maps.event.addListener(draggableMarker, 'dragend', evt => {
            updateLocationFromLatLng(evt.latLng);
        });
        map.addListener('click', evt => updateLocationFromLatLng(evt.latLng));

        // Setelah map dibuat, setup fitur search & tombol konfirmasi
        setupMapControls(oAlamat, searchAlamat);

        // Jika ada lokasi GPS yang harus diambil, lakukan sekarang
        if (!hasSavedLocation && typeof autoGetLocation === 'function') {
            autoGetLocation();
        }
    }, 200);
}

function setupMapControls(oAlamat, searchAlamat) {
    // Tombol Konfirmasi Lokasi
    const btnKonfirmasi = document.getElementById('btnKonfirmasiLokasi');
    if (btnKonfirmasi) {
        btnKonfirmasi.addEventListener('click', function () {
            if (searchAlamat && searchAlamat.value.trim() !== '') {
                oAlamat.value = searchAlamat.value;
                if (draggableMarker) {
                    const pos = draggableMarker.getPosition();
                    sessionStorage.setItem('bup_lat', pos.lat());
                    sessionStorage.setItem('bup_lng', pos.lng());
                }
                oAlamat.scrollIntoView({ behavior: 'smooth', block: 'center' });
                oAlamat.style.transition = 'box-shadow 0.3s ease';
                oAlamat.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.5)';
                setTimeout(() => { oAlamat.style.boxShadow = 'none'; }, 1500);
            } else {
                alert('Silakan cari lokasi atau geser pin peta terlebih dahulu.');
            }
        });
    }

    // ── Smart Autocomplete: Google Places (primary) + Nominatim (auto-fallback) ──
    if (searchAlamat) {
        const suggestBox = document.getElementById('searchSuggestBox');
        let googlePlacesWorking = null; // null=belum tahu, true=jalan, false=tidak jalan
        let fallbackDebounce;
        let googleAutocomplete = null;

        // ── Nominatim Fallback (Jabodetabek) ──
        async function fetchNominatim(query) {
            try {
                const viewbox = '106.60,-5.90,107.20,-6.50';
                const base = `format=json&addressdetails=1&namedetails=1&limit=7&countrycodes=id&accept-language=id&dedupe=1`;
                let res = await fetch(
                    `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&${base}&viewbox=${viewbox}&bounded=1`,
                    { headers: { 'Accept-Language': 'id' } }
                );
                let data = await res.json();
                if (!data.length) {
                    res = await fetch(
                        `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query + ' Jakarta')}&${base}`,
                        { headers: { 'Accept-Language': 'id' } }
                    );
                    data = await res.json();
                }
                return data.map(item => {
                    const parts = item.display_name.split(', ');
                    const mainText = item.namedetails?.name || parts[0];
                    const secText = parts.slice(1, 5).filter(p => p && p !== 'Indonesia').join(', ');
                    return { mainText, secText, lat: parseFloat(item.lat), lng: parseFloat(item.lon) };
                });
            } catch { return []; }
        }

        function showCustomSuggestions(results) {
            if (!suggestBox || !results.length) { hideCustomSuggestions(); return; }
            suggestBox.innerHTML = '';
            results.forEach(r => {
                const item = document.createElement('div');
                item.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background 0.15s;font-family:inherit;';
                item.innerHTML = `
                    <svg style="flex-shrink:0;margin-top:2px;color:#ef4444;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    <div>
                        <div style="font-size:0.875rem;font-weight:600;color:#0f172a;line-height:1.3;">${r.mainText}</div>
                        <div style="font-size:0.75rem;color:#64748b;margin-top:2px;line-height:1.3;">${r.secText}</div>
                    </div>`;
                item.addEventListener('mouseenter', () => item.style.background = '#f0f9ff');
                item.addEventListener('mouseleave', () => item.style.background = '');
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    hideCustomSuggestions();
                    searchAlamat.value = r.mainText;
                    moveMapAndPin({ lat: r.lat, lng: r.lng }, null);
                    hitungJarak({ lat: r.lat, lng: r.lng });
                    const infoText = document.getElementById('maps_info_text');
                    if (infoText) infoText.innerHTML = `<span style="color:#10b981;font-size:0.85rem;">📍 Lokasi ditemukan. Geser pin merah untuk perjelas, lalu klik Konfirmasi.</span>`;
                    if (oAlamat && !oAlamat.value) { oAlamat.value = r.mainText + (r.secText ? ', ' + r.secText : ''); updateCheckoutSummary(); }
                });
                suggestBox.appendChild(item);
            });
            suggestBox.style.display = 'block';
        }

        function hideCustomSuggestions() {
            if (suggestBox) suggestBox.style.display = 'none';
        }

        // ── Google Places Autocomplete Widget ──
        try {
            googleAutocomplete = new google.maps.places.Autocomplete(searchAlamat, {
                componentRestrictions: { country: 'id' },
                fields: ['geometry', 'formatted_address', 'name'],
                bounds: new google.maps.LatLngBounds(
                    new google.maps.LatLng(STORE_LAT - 0.5, STORE_LNG - 0.5),
                    new google.maps.LatLng(STORE_LAT + 0.5, STORE_LNG + 0.5)
                ),
                strictBounds: false
            });

            googleAutocomplete.addListener('place_changed', function () {
                googlePlacesWorking = true; // konfirmasi Google Places berjalan
                hideCustomSuggestions();
                clearTimeout(fallbackDebounce);

                const place = googleAutocomplete.getPlace();
                const infoText = document.getElementById('maps_info_text');

                if (!place || !place.geometry || !place.geometry.location) {
                    // Tekan Enter tanpa pilih → geocode manual
                    const val = searchAlamat.value.trim();
                    if (val.length >= 3) {
                        if (infoText) infoText.innerHTML = '<span style="color:#3b82f6;font-size:0.85rem;">⏳ Mencari lokasi...</span>';
                        new google.maps.Geocoder().geocode(
                            { address: val + ', Indonesia', componentRestrictions: { country: 'ID' } },
                            (results, status) => {
                                if (status === 'OK' && results[0]) {
                                    const loc = results[0].geometry.location;
                                    moveMapAndPin(loc, results[0].geometry.viewport);
                                    hitungJarak({ lat: loc.lat(), lng: loc.lng() });
                                    if (infoText) infoText.innerHTML = `<span style="color:#10b981;font-size:0.85rem;">📍 Lokasi ditemukan. Geser pin merah untuk perjelas, lalu klik Konfirmasi.</span>`;
                                    if (oAlamat && !oAlamat.value) { oAlamat.value = results[0].formatted_address; updateCheckoutSummary(); }
                                } else {
                                    if (infoText) infoText.innerHTML = '<span style="color:#ef4444;font-size:0.85rem;">❌ Alamat tidak ditemukan. Coba ketik lebih spesifik.</span>';
                                }
                            }
                        );
                    }
                    return;
                }

                // Pilih dari dropdown Google — langsung dapat geometry
                const loc = place.geometry.location;
                moveMapAndPin(loc, place.geometry.viewport || null);
                hitungJarak({ lat: loc.lat(), lng: loc.lng() });
                if (infoText) infoText.innerHTML = `<span style="color:#10b981;font-size:0.85rem;">📍 Lokasi ditemukan. Geser pin merah untuk perjelas, lalu klik Konfirmasi.</span>`;
                if (oAlamat && !oAlamat.value) { oAlamat.value = place.formatted_address || searchAlamat.value; updateCheckoutSummary(); }
            });

            if (map) googleAutocomplete.bindTo('bounds', map);
        } catch(e) {
            console.warn('Google Places Autocomplete init failed:', e);
            googlePlacesWorking = false;
        }

        // ── Deteksi otomatis: jika Google Places tidak muncul → pakai Nominatim ──
        searchAlamat.addEventListener('input', function () {
            clearTimeout(fallbackDebounce);
            const val = this.value.trim();
            if (val.length < 3) { hideCustomSuggestions(); return; }

            fallbackDebounce = setTimeout(async () => {
                // Cek apakah .pac-container Google sudah tampil dengan item
                const pac = document.querySelector('.pac-container');
                const googleIsShowing = pac && pac.offsetHeight > 0 && pac.children.length > 0;

                if (googleIsShowing) {
                    googlePlacesWorking = true; // Google Places aktif!
                    hideCustomSuggestions();
                    return;
                }

                // Jika sebelumnya Google Places terbukti jalan, mungkin query ini sekedar lambat 
                // atau memang tidak ada hasil di Google. Kita tetap coba ambil dari Nominatim.
                if (googlePlacesWorking === null) googlePlacesWorking = false;

                // Ambil dari Nominatim
                const results = await fetchNominatim(val);
                
                // Cek sekali lagi: jangan-jangan Google baru saja muncul saat fetch berjalan
                const pacCheck2 = document.querySelector('.pac-container');
                if (pacCheck2 && pacCheck2.offsetHeight > 0 && pacCheck2.children.length > 0) {
                    googlePlacesWorking = true;
                    hideCustomSuggestions();
                    return;
                }

                showCustomSuggestions(results);
            }, 800); // tunggu 800ms agar Google Places punya cukup waktu
        });

        // Tutup custom suggest saat klik di luar
        document.addEventListener('click', e => {
            if (e.target !== searchAlamat && !suggestBox?.contains(e.target)) hideCustomSuggestions();
        });

        // Cegah form submit saat Enter
        searchAlamat.addEventListener('keydown', e => {
            if (e.key === 'Enter') e.preventDefault();
            if (e.key === 'Escape') hideCustomSuggestions();
        });
    }
}


function moveMapAndPin(loc, viewport) {
    const lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
    const lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
    if (map) {
        if (viewport) {
            map.fitBounds(viewport);
            google.maps.event.addListenerOnce(map, 'bounds_changed', () => {
                if (map.getZoom() > 17) map.setZoom(17);
                if (map.getZoom() < 14) map.setZoom(15);
            });
        } else {
            map.panTo({ lat, lng });
            map.setZoom(16);
        }
    }
    if (draggableMarker) {
        draggableMarker.setPosition({ lat, lng });
        draggableMarker.setAnimation(google.maps.Animation.BOUNCE);
        setTimeout(() => draggableMarker.setAnimation(null), 700);
    }
}

function hitungJarak(destinationLocation) {
    // Normalisasi: terima object {lat, lng} atau google.maps.LatLng
    const lat2 = typeof destinationLocation.lat === 'function' ? destinationLocation.lat() : destinationLocation.lat;
    const lng2 = typeof destinationLocation.lng === 'function' ? destinationLocation.lng() : destinationLocation.lng;

    // Simpan koordinat ke hidden input
    const oLatInput = document.getElementById('oLat');
    const oLngInput = document.getElementById('oLng');
    if (oLatInput && oLngInput) {
        oLatInput.value = lat2;
        oLngInput.value = lng2;
    }
    sessionStorage.setItem('bup_lat', lat2);
    sessionStorage.setItem('bup_lng', lng2);

    const lat1 = STORE_LAT;
    const lng1 = STORE_LNG;

    // Gunakan OSRM sebagai primary (Google Directions API tidak aktif)
    const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${lng1},${lat1};${lng2},${lat2}?overview=false`;

    fetch(osrmUrl)
        .then(res => res.json())
        .then(data => {
            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                const distanceInKm = data.routes[0].distance / 1000;
                const distanceText = distanceInKm.toFixed(1) + ' km';
                processDistance(distanceInKm, distanceText);
            } else {
                throw new Error('OSRM no route');
            }
        })
        .catch(() => {
            // Fallback Haversine jika OSRM tidak tersedia
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
            const straightDistance = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distanceInKm = straightDistance * 1.4; // estimasi jalan
            processDistance(distanceInKm, distanceInKm.toFixed(1) + ' km (Est)');
        });

    function processDistance(distanceInKm, distanceText) {
        if (distanceInKm > 25) {
            ongkirDinamis = -1;
        } else if (distanceInKm <= 4) {
            ongkirDinamis = 0;
        } else if (distanceInKm <= 10) {
            ongkirDinamis = 15000;
        } else {
            ongkirDinamis = 25000;
        }

        const infoText = document.getElementById('maps_info_text');
        const inputOngkirDinamis = document.getElementById('input_ongkir_dinamis');
        const mapEl = document.getElementById('map');
        const btnKonfirmasi = document.getElementById('btnKonfirmasiLokasi');
        const searchAlamatInput = document.getElementById('searchAlamat');

        if (infoText) {
            if (ongkirDinamis === -1) {
                if (mapEl) mapEl.style.display = 'none';
                if (btnKonfirmasi) btnKonfirmasi.style.display = 'none';
                if (searchAlamatInput && searchAlamatInput.parentElement) searchAlamatInput.parentElement.style.display = 'block';
                infoText.innerHTML = `
                    <div style="color: #991b1b; background: #fef2f2; padding: 16px; border-radius: 8px; border: 1px solid #fecaca; text-align: center; font-size:clamp(0.8rem, 3vw, 0.95rem); line-height: 1.5;">
                        <b>Jarak Terlalu Jauh (${distanceText})</b><br><br>
                        Mohon maaf, layanan antar-jemput hanya tersedia untuk jarak maksimal 25 KM dari toko kami.<br><br>
                        Silakan <b>ganti Metode Pengiriman</b> di atas menjadi <b>"Antar & Ambil di Toko"</b> untuk melanjutkan pesanan.
                    </div>
                `;
            } else {
                if (btnKonfirmasi) btnKonfirmasi.style.display = 'flex';
                if (searchAlamatInput && searchAlamatInput.parentElement) searchAlamatInput.parentElement.style.display = 'block';
                if (mapEl) {
                    mapEl.style.display = 'block';
                    if (draggableMarker) draggableMarker.setPosition({ lat: lat2, lng: lng2 });
                    map.setCenter({ lat: lat2, lng: lng2 });
                    map.setZoom(15);
                }
                const ongkirText = ongkirDinamis === 0 ? 'Rp 0' : 'Rp ' + ongkirDinamis.toLocaleString('id-ID');
                infoText.innerHTML = `
                    <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:8px;">
                        <span style="color:#475569; font-weight:600; font-size:clamp(0.7rem, 3vw, 0.85rem);">Jarak Terukur: <strong style="color:#0f172a;">${distanceText}</strong></span>
                        <span style="background:#1e293b; color:#ffffff; padding:6px 12px; border-radius:20px; font-weight:700; font-size:clamp(0.7rem, 3vw, 0.85rem); white-space:nowrap;">Ongkir: ${ongkirText}</span>
                    </div>
                `;
            }
        }

        if (inputOngkirDinamis) inputOngkirDinamis.value = ongkirDinamis;
        updatePrice();
    }
}

function autoGetLocation() {
    if (navigator.geolocation) {
        const infoText = document.getElementById('maps_info_text');
        if (infoText) {
            infoText.innerHTML = `
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="color:#64748b; font-size:0.85rem;">⏳ Sedang melacak alamat Anda...</span>
                </div>
            `;
        }

        const searchBox = document.getElementById('searchAlamat');
        if (searchBox) searchBox.value = "⏳ Sedang mencari lokasi GPS Anda...";

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                if (infoText) infoText.innerHTML = `
                    <span style="display: flex; align-items: center; gap: 6px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--blue); flex-shrink:0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <em>Lokasi GPS ditemukan. Pastikan pin sudah tepat — geser pin atau ketuk peta untuk sesuaikan. Lalu klik <strong>Konfirmasi Lokasi</strong> untuk mengisi alamat penjemputan.</em>
                    </span>
                `;

                // Reverse Geocoding dengan Geocoder API
                try {
                    const geocoder = new google.maps.Geocoder();
                    const result = await geocoder.geocode({ location: userLocation });
                    if (result.results && result.results[0]) {
                        if (searchBox) searchBox.value = result.results[0].formatted_address;
                    } else {
                        if (searchBox) searchBox.value = 'Lokasi GPS';
                    }
                } catch (e) {
                    if (searchBox) searchBox.value = 'Lokasi GPS';
                }

                if (draggableMarker) {
                    draggableMarker.setPosition(userLocation);
                }
                if (map) {
                    map.setCenter(userLocation);
                }
                hitungJarak(userLocation);
            },
            (error) => {
                if (infoText) infoText.innerHTML = `
                    <span style="display: flex; align-items: center; gap: 6px; color: #ef4444;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <em>Gagal melacak GPS. Silakan ketik alamat manual.</em>
                    </span>
                `;
                if (searchBox) searchBox.value = "";
                console.warn("Geolocation error:", error);
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }
}

function siapkanStruk(dataPesanan) {
    document.getElementById('rKode').textContent = dataPesanan.kode_pesanan;
    const now = new Date();
    const tgl = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    const jam = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    document.getElementById('rTglCetak').textContent = `${tgl} ${jam}`;
    document.getElementById('rNama').textContent = dataPesanan.nama_pelanggan || '-';
    document.getElementById('rWa').textContent = dataPesanan.no_wa || '-';

    let receiptItemsHtml = '';
    let itemsCards = [];
    if (dataPesanan.items && dataPesanan.items.length > 0) {
        dataPesanan.items.forEach((item, index) => {
            const kategori = item.kategori || item.nama_kategori || 'KATEGORI';
            const layanan = item.nama_layanan || item.layanan || item.jenis_layanan || 'Layanan Standar';
            const merek = item.merek || item.nama_merek || '-';
            const qty = item.qty || item.jumlah || 1;
            const ukuran = item.ukuran || '-';
            const warna = item.warna || '-';
            const harga = item.harga_satuan || 0;

            if (kategori === 'Extra Treatment') {
                if (itemsCards.length > 0) {
                    itemsCards[itemsCards.length - 1].extras.push({ layanan, qty, harga });
                }
            } else {
                itemsCards.push({
                    kategori,
                    layanan,
                    merek,
                    qty,
                    ukuran,
                    warna,
                    harga,
                    extras: []
                });
            }
        });

        receiptItemsHtml += `<div style="margin-top:12px; border-top: 2px solid #e2e8f0;">`;
        itemsCards.forEach((c, idx) => {
            let ukTxt = c.ukuran && c.ukuran !== '-' ? `Size ${c.ukuran}` : '';
            let wrnTxt = c.warna && c.warna !== '-' ? c.warna : '';
            let detailsArr = [];
            if (c.merek && c.merek !== '-') detailsArr.push(c.merek);
            if (ukTxt) detailsArr.push(ukTxt);
            if (wrnTxt) detailsArr.push(wrnTxt);
            let detailStr = detailsArr.length > 0 ? `(${detailsArr.join(' | ')})` : '';

            const subtotalMain = c.harga * c.qty;

            receiptItemsHtml += `
                <div style="padding: 12px 0; border-bottom: 1px dashed #cbd5e1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 900; font-size: clamp(0.75rem, 3.5vw, 0.95rem); color: #0f172a;">Item #${idx + 1}</span>
                        <span style="font-weight: 900; font-size: clamp(0.65rem, 3vw, 0.85rem); color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">x${c.qty}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px; gap: 8px;">
                        <span style="color: #64748b; font-weight: 500; flex-shrink: 0; font-size: 0.85rem; white-space: nowrap;">Barang:</span>
                        <div style="text-align: right; font-weight: 800; color: #000; flex: 1; font-size: 0.85rem; word-break: break-word;">
                            ${c.kategori}
                            ${detailStr ? `<br><span style="font-size: 0.85em; font-weight: 600; color: #64748b;">${detailStr}</span>` : ''}
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; gap: 8px;">
                        <span style="color: #64748b; font-weight: 500; flex-shrink: 0; font-size: 0.85rem; white-space: nowrap;">Layanan:</span>
                        <span style="font-weight: 800; text-align: right; color: #000; flex: 1; font-size: 0.85rem; word-break: break-word;">${c.layanan}</span>
                    </div>

                    <div style="display: flex; justify-content: space-between; gap: 8px;">
                        <span style="color: #64748b; font-weight: 500; flex-shrink: 0; font-size: 0.85rem; white-space: nowrap;">Harga:</span>
                        <span style="font-weight: 800; text-align: right; color: #000; flex: 1; font-size: 0.85rem;">Rp ${subtotalMain.toLocaleString('id-ID')}</span>
                    </div>
            `;

            if (c.extras.length > 0) {
                c.extras.forEach(ext => {
                    const subtotalExtra = ext.harga * ext.qty;
                    receiptItemsHtml += `
                        <div style="display: flex; justify-content: space-between; margin-top: 6px; margin-bottom: 2px; gap: 8px;">
                            <span style="color: #0284c7; font-weight: 500; flex-shrink: 0; font-size: 0.85rem; white-space: nowrap;">↳ Extra:</span>
                            <span style="color: #0369a1; font-weight: 800; text-align: right; flex: 1; font-size: 0.85rem; word-break: break-word;">${ext.layanan}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 8px;">
                            <span style="color: #0284c7; font-weight: 500; flex-shrink: 0; font-size: 0.85rem; white-space: nowrap;">↳ Harga:</span>
                            <span style="color: #0369a1; font-weight: 800; text-align: right; flex: 1; font-size: 0.85rem;">Rp ${subtotalExtra.toLocaleString('id-ID')}</span>
                        </div>
                    `;
                });
            }
            receiptItemsHtml += `</div>`;
        });
        receiptItemsHtml += `</div>`;
    }
    document.getElementById('rItemList').innerHTML = receiptItemsHtml;

    let tglPesanLengkap = '-';
    if (dataPesanan.created_at) {
        const d = new Date(dataPesanan.created_at.replace(' ', 'T'));
        tglPesanLengkap = !isNaN(d) ? d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : dataPesanan.created_at;
    } else if (dataPesanan.tanggal_pesan) {
        tglPesanLengkap = dataPesanan.tanggal_pesan;
    }
    document.getElementById('rTglPesan').textContent = tglPesanLengkap;

    let catatanUmum = '-';
    if (dataPesanan.items && dataPesanan.items.length > 0 && dataPesanan.items[0].catatan) {
        catatanUmum = dataPesanan.items[0].catatan;
    }
    const rCatatan = document.getElementById('rCatatan');
    const rowCatatan = document.getElementById('rowCatatan');
    if (rCatatan) rCatatan.textContent = catatanUmum;
    if (rowCatatan) {
        if (catatanUmum && catatanUmum.trim() !== '' && catatanUmum !== '-') {
            rowCatatan.classList.remove('hidden-row');
        } else {
            rowCatatan.classList.add('hidden-row');
        }
    }

    document.getElementById('rPengiriman').textContent = dataPesanan.nama_metode || '-';

    // Tampilkan baris alamat & waktu jemput HANYA jika metode butuh penjemputan
    const rowAlamat = document.getElementById('rowAlamat');
    const rowWaktu = document.getElementById('rowWaktuJemput');
    const adaAlamat = dataPesanan.alamat && dataPesanan.alamat.trim() !== '';
    const adaWaktuJemput = dataPesanan.waktu_penjemputan && dataPesanan.waktu_penjemputan.trim() !== '';

    if (adaWaktuJemput) {
        // Metode jemput — tampilkan baris alamat dan waktu
        document.getElementById('rAlamat').textContent = (dataPesanan.alamat && dataPesanan.alamat.trim() !== '') ? dataPesanan.alamat : '-';
        rowAlamat.classList.remove('hidden-row');

        let tglJemputFormatted = '';
        if (dataPesanan.tanggal_pesan && !dataPesanan.tanggal_pesan.startsWith('0000-00-00')) {
            const dJemput = new Date(dataPesanan.tanggal_pesan);
            tglJemputFormatted = !isNaN(dJemput) ? dJemput.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : dataPesanan.tanggal_pesan;
        }
        document.getElementById('rWaktuJemput').innerHTML = tglJemputFormatted ? tglJemputFormatted + ' <span class="desktop-only">|</span> <span class="mobile-break">' + dataPesanan.waktu_penjemputan + '</span>' : dataPesanan.waktu_penjemputan;
        rowWaktu.classList.remove('hidden-row');
    } else {
        // Metode antar ke pelanggan atau ambil di toko — sembunyikan alamat & waktu jemput
        document.getElementById('rAlamat').textContent = '-';
        rowAlamat.classList.add('hidden-row');
        document.getElementById('rWaktuJemput').textContent = '-';
        rowWaktu.classList.add('hidden-row');
    }

    let metodeBayarText = (dataPesanan.metode_bayar === 'tunai' || dataPesanan.metode_bayar === 'cash') ? 'Tunai (Bayar Ditempat)' : 'Transfer BCA';
    document.getElementById('rMetodeBayar').textContent = metodeBayarText;

    const statusB = (dataPesanan.status_bayar || '').toLowerCase().trim();
    let statusBayarHtml;

    if (dataPesanan.status_pesanan === 'batal') {
        statusBayarHtml = '<span style="color:#ef4444 !important; font-weight:800;">BATAL</span>';
    } else if (statusB === 'confirmed' || statusB === 'lunas') {
        statusBayarHtml = '<span style="color:#10b981 !important; font-weight:800;">LUNAS</span>';
    } else {
        if (dataPesanan.metode_bayar === 'tunai' || dataPesanan.metode_bayar === 'cash') {
            statusBayarHtml = '<span style="color:#ea580c !important; font-weight:800;">BELUM LUNAS</span>';
        } else {
            statusBayarHtml = '<span style="color:#f59e0b !important; font-weight:800;">MENUNGGU KONFIRMASI</span>';
        }
    }
    document.getElementById('rStatusBayar').innerHTML = statusBayarHtml;

    const ongkir = parseInt(dataPesanan.ongkir) || 0;
    const rOngkirRow = document.getElementById('rOngkirRow');
    if (rOngkirRow) {
        if (adaWaktuJemput) {
            rOngkirRow.classList.remove('hidden-row');
            if (ongkir === -1) {
                document.getElementById('rOngkir').textContent = 'Diinfokan via WA';
            } else if (ongkir > 0) {
                document.getElementById('rOngkir').textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
            } else {
                document.getElementById('rOngkir').textContent = 'Rp 0';
            }
        } else {
            rOngkirRow.classList.add('hidden-row');
        }
    }
    document.getElementById('rTotal').textContent = 'Rp ' + (dataPesanan.total_harga || 0).toLocaleString('id-ID');

    const infoKotak = document.getElementById('infoKotak');
    if (infoKotak) {
        infoKotak.style.display = 'block';
    }
}

document.addEventListener("DOMContentLoaded", function () {

    const urlParams = new URLSearchParams(window.location.search);

    // Jika user datang dari beranda (fresh=1), kosongkan data form sebelumnya
    if (urlParams.get('fresh') === '1') {
        if (localStorage.getItem('bup_step') !== '2') {
            localStorage.removeItem('bup_order_state');
            localStorage.removeItem('bup_step');
        }
        // Bersihkan parameter dari URL agar tidak kelihatan di address bar
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, '', cleanUrl);
    }

    const successKode = urlParams.get('success');


    if (successKode) {
        document.getElementById('btnBackHome').style.display = 'none';
        document.getElementById('step1').style.display = 'none';
        document.getElementById('step2').style.display = 'none';
        document.getElementById('mainOrderCard').style.display = 'none';

        fetch(`../api/order.php?action=status&kode=${successKode}&_t=${new Date().getTime()}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(statusJson => {
                if (statusJson.success && statusJson.data) {
                    siapkanStruk(statusJson.data);
                    document.getElementById('step3').style.display = 'block';
                } else {
                    window.location.href = 'order.php';
                }
            }).catch(err => {
                console.error(err);
                window.location.href = 'order.php';
            });
        return;
    }



    const radiosPengiriman = document.querySelectorAll('.radio-pengiriman');
    radiosPengiriman.forEach(radio => {
        radio.addEventListener('change', function () {
            togglePengirimanUI(this);
        });
    });

    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('input', saveState);
        orderForm.addEventListener('change', saveState);
    }

    const tglInput = document.getElementById('oTanggal');
    if (tglInput) {
        // Set minimum date to today so they can't order for yesterday
        const todayStr = new Date().toLocaleDateString('en-CA'); // format YYYY-MM-DD
        tglInput.setAttribute('min', todayStr);
        tglInput.addEventListener('change', updateWaktuJemput);
    }

    // Reset state if coming from a different page (like home page), but NOT on refresh
    const navEntries = performance.getEntriesByType('navigation');
    const isReload = navEntries.length > 0 && navEntries[0].type === 'reload';

    if (!isReload && document.referrer && !document.referrer.includes('order.php') && !document.referrer.includes('status.php')) {
        // Jangan bersihkan state jika user sedang berada di tahap pembayaran (step 2)
        if (localStorage.getItem('bup_step') !== '2') {
            clearOrderState();
        }
    }

    const stateStr = localStorage.getItem('bup_order_state');

    if (stateStr) {
        const state = JSON.parse(stateStr);

        document.getElementById('oNama').value = state.nama || '';
        document.getElementById('oWa').value = state.wa || '';
        document.getElementById('oAlamat').value = state.alamat || '';
        if (state.waktu_penjemputan) document.getElementById('oWaktuJemput').value = state.waktu_penjemputan;
        if (state.tanggal) document.getElementById('oTanggal').value = state.tanggal;
        document.getElementById('oCatatan').value = state.catatan || '';

        if (state.ongkirDinamis !== undefined) {
            ongkirDinamis = parseInt(state.ongkirDinamis) || 0;
            const inputOngkirDinamis = document.getElementById('input_ongkir_dinamis');
            if (inputOngkirDinamis) inputOngkirDinamis.value = ongkirDinamis;
        }

        if (state.metode_pengiriman) {
            const radio = document.querySelector(`input[name="metode_pengiriman"][value="${state.metode_pengiriman}"]`);
            if (radio) {
                radio.checked = true;
                togglePengirimanUI(radio);
            }
        }

        if (state.metode_bayar) {
            const card = document.querySelector(`.payment-card[data-val="${state.metode_bayar}"]`);
            if (card) selectPay(card, state.metode_bayar);
        }

        const container = document.getElementById('dynamicItemsContainer');
        container.innerHTML = '';

        state.items.forEach((item, index) => {
            tambahItem();
            const rows = document.querySelectorAll('.item-row');
            const currentRow = rows[rows.length - 1];

            const katSelect = currentRow.querySelector('.kat-select');
            katSelect.value = item.kat;
            updateLayananDynamic(katSelect);

            currentRow.querySelector('.merk-input').value = item.merk;
            if (currentRow.querySelector('.ukuran-input')) {
                currentRow.querySelector('.ukuran-input').value = item.ukuran || '';
            }
            if (currentRow.querySelector('.warna-input')) {
                currentRow.querySelector('.warna-input').value = item.warna || '';
            }
            currentRow.querySelector('.lay-select').value = item.lay;
            currentRow.querySelector('.jum-input').value = item.jum;

            checkExtraTreatmentPrompt(currentRow);
            if (item.et) {
                const etSel = currentRow.querySelector('.et-select');
                if (etSel) {
                    const btnYa = currentRow.querySelector('.btn-et-ya');
                    if (btnYa) pilihExtraTreatment(btnYa, true);
                    etSel.value = item.et;
                }
            }
        });

        if (localStorage.getItem('bup_step')) {
            const savedStep = parseInt(localStorage.getItem('bup_step')) || 1;
            if (savedStep > 1 && savedStep <= 4) {
                goToStep(savedStep);
                if (savedStep === 4) {
                    renderKonfirmasi();
                }
            }
        }

        updatePrice();
        updateWaktuJemput(); // Panggil fungsi saat me-restore data

    } else {
        document.getElementById('oTanggal').valueAsDate = new Date();
        updateWaktuJemput(); // Panggil fungsi saat pertama kali muat
        tambahItem();
    }

    const deadline = localStorage.getItem('paymentDeadline');
    if (deadline && new Date().getTime() > parseInt(deadline)) {
        localStorage.removeItem('paymentDeadline');
    }
});


