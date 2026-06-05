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
                    <option value="Bag">Bag</option>
                    <option value="Wallet">Wallet</option>
                    <option value="Sandals">Sandals</option>
                    <option value="Hat">Hat</option>
                    <option value="Repaint">Repaint</option>
                    <option value="Unyellowing">Unyellowing</option>
                </select>
            </div>
            <div class="form-group fg-merk">
                <div class="form-row-split">
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label>Merek <span>*</span></label>
                        <input type="text" class="form-input merk-input" name="sepatu[]" placeholder="Merek (cth: Nike)" required style="width: 100%;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 0;">
                        <label>Ukuran <span>*</span></label>
                        <input type="text" class="form-input ukuran-input" name="ukuran[]" placeholder="Size (cth: 42)" required style="width: 100%;">
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
                        <input type="text" class="form-input warna-input" name="warna[]" placeholder="cth: Hitam" required style="width: 100%;">
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
            <span style="display: block; word-break: break-word;">Estimasi Pengerjaan: <strong class="item-estimasi-text">3 - 5 Hari Kerja</strong></span>
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
    document.querySelectorAll('.shipping-card').forEach(c => c.classList.remove('selected'));
    radioEl.closest('.shipping-card').classList.add('selected');

    const group = document.getElementById('alamatGroup');
    const inputAlamat = document.getElementById('oAlamat');
    const inputWaktu = document.getElementById('oWaktuJemput');
    const infoOngkir = document.getElementById('infoOngkirBox'); // Panggil kotak informasi
    const labelTanggal = document.getElementById('labelTanggalOrder');

    if (radioEl.getAttribute('data-perlu-jemput') === '1') {
        group.classList.add('active');
        inputAlamat.required = true;
        inputWaktu.required = true;
        if (labelTanggal) labelTanggal.innerHTML = 'Tanggal Penjemputan <span>*</span>';

        if (infoOngkir) {
            infoOngkir.style.display = 'block';
            
            // Jangan kosongkan alamat jika saat ini sedang restore state (ada isinya tapi kita baru render)
            // Tapi jika user klik manual, biasanya isinya kosong/beda, kita bisa biarkan saja.
            // Lebih baik kita hapus saja baris inputAlamat.value = '' karena jika mereka mau ubah, mereka tinggal search lagi.

            // Lazy init: buat map pertama kali saat section visible
            if (!mapCreated) {
                createMap(); 
            } else {
                // Map sudah ada, cukup resize
                if (map) {
                    setTimeout(() => {
                        google.maps.event.trigger(map, 'resize');
                        const savedLat = sessionStorage.getItem('bupp_order_lat');
                        const savedLng = sessionStorage.getItem('bupp_order_lng');
                        if (savedLat && savedLng) {
                            map.setCenter({ lat: parseFloat(savedLat), lng: parseFloat(savedLng) });
                        }
                    }, 50);
                }
                // Hanya lacak ulang jika bukan dari state restore
                if (!inputAlamat.value) {
                    if (typeof autoGetLocation === 'function') autoGetLocation();
                }
            }
        }
    } else {
        group.classList.remove('active');
        inputAlamat.required = false;
        inputWaktu.required = false;
        inputAlamat.value = '';
        inputWaktu.value = '';
        if (infoOngkir) infoOngkir.style.display = 'none'; // Sembunyikan saat antar ke toko
        if (labelTanggal) labelTanggal.innerHTML = 'Tanggal Pemesanan <span>*</span>';
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
        if (kategoriPilihan === 'Bag') {
            merkInput.placeholder = 'cth: Gucci';
        } else if (kategoriPilihan === 'Wallet') {
            merkInput.placeholder = 'cth: Polo';
        } else if (kategoriPilihan === 'Sandals') {
            merkInput.placeholder = 'cth: Eiger';
        } else if (kategoriPilihan === 'Hat') {
            merkInput.placeholder = 'cth: Adidas';
        } else {
            merkInput.placeholder = 'cth: Nike Air Force 1';
        }
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

                const noSizeKategori = ['Bag', 'Wallet', 'Sandals', 'Hat'];
                const hideSize = noSizeKategori.includes(kat) || (kat === 'Repaint' && layananText.includes('Hat'));

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
                        jumLabel.innerHTML = 'Jumlah (Pasang) <span>*</span>';
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
        priceEl.textContent = 'Rp ' + totalSemua.toLocaleString('id-ID');
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
        priceEl.textContent = 'Pilih layanan terlebih dahulu';
        if (warningEl) {
            warningEl.style.display = 'none';
        }
    }
}

function selectPay(el, val) {
    document.querySelectorAll('.payment-card').forEach(x => x.classList.remove('selected'));
    if (el) el.classList.add('selected');
    document.getElementById('paymentInput').value = val;
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

function reviewOrder(isRestoring = false) {
    if (!isRestoring) {
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
            const isNoSize = ['Bag', 'Wallet', 'Sandals', 'Hat'].includes(kat) || (kat === 'Repaint' && layText.includes('Hat'));
            if (!isNoSize && (!uChecks[i] || !uChecks[i].value.trim())) return alert(`Item #${i + 1}: Ukuran sepatu wajib diisi!`);
            if (!wChecks[i] || !wChecks[i].value.trim()) return alert(`Item #${i + 1}: Warna wajib diisi!`);
        }

        const mt = document.querySelector('input[name="metode_pengiriman"]:checked');
        if (!mt) return alert('Pilih metode pengiriman');

        const alamatGroup = document.getElementById('alamatGroup');
        if (alamatGroup && alamatGroup.classList.contains('active')) {
            if (!document.getElementById('oAlamat').value.trim()) return alert('Alamat wajib diisi!');
            if (!document.getElementById('oWaktuJemput').value) return alert('Waktu penjemputan wajib dipilih!');
        }

        if (!document.getElementById('paymentInput').value) return alert('Pilih metode pembayaran');

        saveState();
    }

    const kategoriSelects = document.querySelectorAll('.kat-select');
    const merkInputs = document.querySelectorAll('.merk-input');
    const ukuranInputs = document.querySelectorAll('.ukuran-input');
    const layananSelects = document.querySelectorAll('.lay-select');
    const jumlahInputs = document.querySelectorAll('.jum-input');

    let totalLayanan = 0;
    let daftarLayananHtml = '';
    let isLongProcess = false;

    for (let i = 0; i < kategoriSelects.length; i++) {
        const katName = kategoriSelects[i].value ? kategoriSelects[i].value.toLowerCase() : '';
        const selIndex = layananSelects[i].selectedIndex;
        const opt = selIndex >= 0 ? layananSelects[i].options[selIndex] : null;
        const layName = opt ? opt.text.toLowerCase() : '';

        if (katName.includes('repaint') || katName.includes('unyellowing') || layName.includes('repaint') || layName.includes('unyellowing')) {
            isLongProcess = true;
        }
        const jml = parseInt(jumlahInputs[i].value) || 1;
        let itemLayananPrice = 0;
        if (opt && opt.getAttribute('data-price')) {
            itemLayananPrice = parseInt(opt.getAttribute('data-price')) * jml;
            totalLayanan += itemLayananPrice;
        }

        let etAddonHtml = '';
        const row = layananSelects[i].closest('.item-row');
        const etSel = row ? row.querySelector('.et-select') : null;
        if (etSel && etSel.value && etSel.selectedIndex > 0) {
            const etOpt = etSel.options[etSel.selectedIndex];
            const etName = etOpt.text.toLowerCase();
            if (etName.includes('repaint') || etName.includes('unyellowing')) {
                isLongProcess = true;
            }
            const etPrice = parseInt(etOpt.getAttribute('data-price')) * jml;
            totalLayanan += etPrice;
            etAddonHtml = `
                <div style="font-size: clamp(0.75rem, 3vw, 1.15rem); font-weight: 600; color: #475569; margin-top: 4px; display:flex; justify-content:space-between; align-items: flex-start; gap:8px;">
                    <span style="flex:1; word-break: break-word;">| Extra: ${etOpt.text.split(' (Rp')[0]}</span>
                    <span style="color:#0f172a; font-weight: 700; flex-shrink: 0; min-width: 80px; text-align: right;">Rp ${etPrice.toLocaleString('id-ID')}</span>
                </div>
            `;
        }

        const sizeText = ukuranInputs[i] && ukuranInputs[i].value ? `Size ${ukuranInputs[i].value}` : '';
        const warnaInput = row.querySelector('.warna-input');
        const warnaText = warnaInput && warnaInput.value ? warnaInput.value : '';
        const merkText = merkInputs[i].value;
        const detailsArr = [];
        if (merkText && merkText !== '-') detailsArr.push(merkText);
        if (sizeText) detailsArr.push(sizeText);
        if (warnaText) detailsArr.push(warnaText);
        const detailStr = detailsArr.join(' | ');
        const detailHtml = detailStr ? `<div style="font-size: clamp(0.65rem, 3vw, 1rem); color: #64748b; margin-top: 2px; line-height: 1.4;">${detailStr}</div>` : '';

        daftarLayananHtml += `
        <div style="margin-bottom:14px;">
            <div style="display:flex; justify-content:space-between; align-items: flex-start; gap:8px;">
                <div style="font-size: clamp(0.75rem, 3vw, 1.15rem); font-weight: 700; color: #1e293b; flex:1; padding-right: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${kategoriSelects[i].value} - ${opt.text.split(' (Rp')[0]} <span style="font-weight: 800; color: var(--blue);">(x${jml})</span></div>
                <div style="font-size: clamp(0.75rem, 3vw, 1.15rem); font-weight: 700; color: #0f172a; text-align:right; white-space:nowrap; flex-shrink:0;">Rp ${itemLayananPrice.toLocaleString('id-ID')}</div>
            </div>
            ${detailHtml}
            ${etAddonHtml}
        </div>`;
    }

    const metodeCek = document.querySelector('input[name="metode_pengiriman"]:checked');
    let biayaKirimSum = 0;
    if (metodeCek) {
        if (metodeCek.getAttribute('data-perlu-jemput') === '1' && typeof ongkirDinamis !== 'undefined') {
            biayaKirimSum = (ongkirDinamis === -1) ? 0 : ongkirDinamis;
        } else {
            biayaKirimSum = parseInt(metodeCek.getAttribute('data-biaya')) || 0;
        }
    }
    const total = totalLayanan + biayaKirimSum;

    document.getElementById('sumNama').textContent = document.getElementById('oNama').value;
    document.getElementById('sumItemCount').textContent = kategoriSelects.length;
    document.getElementById('sumLayananList').innerHTML = daftarLayananHtml;

    const estimasiEl = document.getElementById('sumEstimasi');
    if (estimasiEl) {
        estimasiEl.textContent = isLongProcess ? '7 - 10 Hari Kerja' : '3 - 5 Hari Kerja';
    }

    // Tampilkan ongkos kirim di baris tersendiri
    const sumOngkirRow = document.getElementById('sumOngkirRow');
    const sumOngkirValue = document.getElementById('sumOngkirValue');
    if (sumOngkirRow && sumOngkirValue) {
        if (metodeCek && metodeCek.getAttribute('data-perlu-jemput') === '1') {
            if (typeof ongkirDinamis !== 'undefined' && ongkirDinamis === -1) {
                sumOngkirValue.textContent = 'Diinfokan via WA';
            } else {
                sumOngkirValue.textContent = 'Rp ' + biayaKirimSum.toLocaleString('id-ID');
            }
            sumOngkirRow.style.display = 'flex';
        } else {
            // Sembunyikan baris ongkir jika metode tidak butuh jemput/antar
            sumOngkirRow.style.display = 'none';
        }
    }

    document.getElementById('sumTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');

    const pay = document.getElementById('paymentInput').value;
    const upArea = document.getElementById('uploadArea');
    const bcaArea = document.getElementById('rekBcaArea');
    const subtitle = document.getElementById('paySubtitle');
    const btnKirim = document.getElementById('btnSubmitFinal');

    if (pay === 'tunai') {
        document.getElementById('sumMetode').textContent = 'Tunai (Bayar Ditempat)';
        upArea.style.display = 'none';
        bcaArea.style.display = 'none';
        subtitle.textContent = 'Pembayaran akan dilakukan secara tunai. Pastikan uang Anda pas.';
        btnKirim.textContent = 'Konfirmasi Pembayaran';
        document.getElementById('timerWarning').style.display = 'none';
        if (timerInterval) clearInterval(timerInterval);
    } else {
        document.getElementById('sumMetode').textContent = 'Transfer BCA';
        upArea.style.display = 'block';
        bcaArea.style.display = 'block';
        subtitle.textContent = 'Lakukan pembayaran ke rekening berikut, kemudian upload bukti pembayaran.';
        btnKirim.textContent = 'Kirim Bukti & Konfirmasi Pembayaran';
        let deadline = localStorage.getItem('paymentDeadline');
        if (!deadline) {
            deadline = new Date().getTime() + (30 * 60 * 1000);
            localStorage.setItem('paymentDeadline', deadline);
        }
        startCountdown(parseInt(deadline));
    }

    // Tampilkan Modal Konfirmasi
    const payVal = document.getElementById('paymentInput').value;
    const payText = (payVal === 'tunai' || payVal === 'cash') ? 'Tunai (Bayar Ditempat)' : 'Transfer BCA';
    const catatan = document.getElementById('oCatatan').value.trim() || '-';

    const inputDate = document.getElementById('oTanggal').value;
    let formattedDate = inputDate;
    if (inputDate) {
        const d = new Date(inputDate);
        if (!isNaN(d)) {
            formattedDate = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }
    } else {
        const today = new Date();
        formattedDate = today.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }
    const currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    let modalHtml = `
        <div style="margin-bottom:16px; padding:16px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
            <div style="margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Informasi Pemesan</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; overflow-wrap: break-word;">${document.getElementById('oNama').value}</strong>
                <span style="color:#475569; font-size:clamp(0.75rem, 3vw, 1.15rem); display:block; margin-top:2px;">${document.getElementById('oWa').value}</span>
            </div>
            <div>
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Tanggal Pesan</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; overflow-wrap: break-word;">${formattedDate}, ${currentTime}</strong>
            </div>
        </div>
    `;

    const metodeEl = document.querySelector('input[name="metode_pengiriman"]:checked');
    const namaMetode = metodeEl ? metodeEl.nextElementSibling.textContent : '-';
    
    modalHtml += `
        <div style="margin-bottom:16px; padding:16px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
            <div style="${metodeEl && metodeEl.getAttribute('data-perlu-jemput') === '1' ? 'margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;' : ''}">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Metode Pengiriman</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; overflow-wrap: break-word;">${namaMetode}</strong>
            </div>
    `;

    if (metodeEl && metodeEl.getAttribute('data-perlu-jemput') === '1') {
        let tglJemput = document.getElementById('oTanggal').value;
        if (tglJemput) {
            const parts = tglJemput.split('-');
            if (parts.length === 3) tglJemput = `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        let wktGabung = tglJemput ? (tglJemput + ' | ' + document.getElementById('oWaktuJemput').value) : document.getElementById('oWaktuJemput').value;
        modalHtml += `
            <div style="margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Jadwal Jemput</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; overflow-wrap: break-word;">${wktGabung}</strong>
            </div>
            <div>
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Alamat Lengkap</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; line-height:1.4; overflow-wrap: break-word;">${document.getElementById('oAlamat').value}</strong>
            </div>
        `;
    }

    modalHtml += `</div>`; // Tutup grup pengiriman

    modalHtml += `
        <div style="margin-bottom:16px; padding:16px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 1px 2px rgba(0,0,0,0.02);">
            <div style="${catatan && catatan !== '-' ? 'margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;' : ''}">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Metode Pembayaran</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.2rem); display:block; overflow-wrap: break-word;">${payText}</strong>
            </div>
            ${(catatan && catatan !== '-') ? `
            <div>
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.5px;">Catatan Tambahan</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.15rem); display:block; font-style:italic; line-height:1.4; overflow-wrap: break-word;">${catatan}</strong>
            </div>` : ''}
        </div>
    `;

    modalHtml += `
        <div style="padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;">
            <div style="margin-bottom:12px;">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Rincian Pesanan:</span>
                <div style="padding-left:14px; border-left:3px solid var(--blue); font-size:clamp(0.75rem, 3vw, 1.15rem); overflow-wrap: break-word;">${daftarLayananHtml}</div>
            </div>
            ${(metodeEl && metodeEl.getAttribute('data-perlu-jemput') === '1') ? `
            <div style="margin-bottom:12px; border-bottom:1px solid #e2e8f0; padding-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                <span style="color:#64748b; font-weight:500; font-size:clamp(0.7rem, 3vw, 1.1rem);">Ongkos Kirim</span>
                <strong style="color:#0f172a; font-size:clamp(0.75rem, 3vw, 1.15rem);">${(typeof ongkirDinamis !== 'undefined' && ongkirDinamis === -1) ? 'Diinfokan via WA' : (biayaKirimSum > 0 ? 'Rp ' + biayaKirimSum.toLocaleString('id-ID') : 'Rp 0')}</strong>
            </div>` : ''}
            <div style="margin-top:12px; text-align:right;">
                <span style="color:#64748b; font-weight:600; font-size:clamp(0.6rem, 2.5vw, 1rem); display:block; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Total Tagihan</span>
                <strong style="color:var(--blue); font-size:clamp(1.2rem, 5vw, 2.2rem); display:block; line-height: 1; letter-spacing:-0.5px;">Rp ${total.toLocaleString('id-ID')}</strong>
            </div>
        </div>
    `;

    document.getElementById('modalSumContent').innerHTML = modalHtml;
    document.getElementById('modalKonfirmasi').style.display = 'flex';
}

function tutupModalKonfirmasi() {
    document.getElementById('modalKonfirmasi').style.display = 'none';
}

function lanjutKePembayaran() {
    document.getElementById('modalKonfirmasi').style.display = 'none';

    localStorage.setItem('bup_step', '2');
    isPaymentStep = true;

    document.getElementById('btnBackHome').style.display = 'none';
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'block';
    window.scrollTo(0, 0);
}

function kembaliKeForm() {
    const konfirmasi = confirm("Apakah Anda yakin ingin kembali ke halaman form?");
    if (!konfirmasi) {
        return;
    }

    localStorage.removeItem('paymentDeadline');
    if (timerInterval) clearInterval(timerInterval);

    clearOrderState();
    isPaymentStep = false;

    // Reset form fields
    document.getElementById('orderForm').reset();
    document.getElementById('dynamicItemsContainer').innerHTML = '';
    tambahItem(); // Tambah 1 item default
    document.getElementById('oAlamat').value = '';
    document.getElementById('oWaktuJemput').value = '';
    document.querySelectorAll('.shipping-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('alamatGroup').classList.remove('active');

    // Reset peta
    if (typeof map !== 'undefined' && map && typeof directionsRenderer !== 'undefined' && directionsRenderer) {
        directionsRenderer.setDirections({ routes: [] });
    }
    const infoOngkir = document.getElementById('infoOngkirBox');
    if (infoOngkir) infoOngkir.style.display = 'none';
    const infoText = document.getElementById('maps_info_text');
    if (infoText) {
        infoText.innerHTML = `
            <span style="display: flex; align-items: center; gap: 6px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                <em>Geser pin pada peta untuk menentukan lokasi, atau ketik manual pada kolom alamat di bawah.</em>
            </span>
        `;
    }

    updatePrice();

    document.getElementById('step2').style.display = 'none';
    document.getElementById('btnBackHome').style.display = 'inline-flex';
    document.getElementById('step1').style.display = 'block';
}

async function prosesPesanan() {
    const pay = document.getElementById('paymentInput').value;
    const fileInput = document.getElementById('oBukti');

    if (pay !== 'tunai') {
        if (fileInput.files.length === 0) return alert('Harap upload foto bukti transfer terlebih dahulu!');
        if (fileInput.files[0].size > 5 * 1024 * 1024) return alert('Ukuran foto terlalu besar! Maksimal 5MB.');
    }

    const form = document.getElementById('orderForm');
    const btn = document.getElementById('btnSubmitFinal');

    btn.disabled = true;
    const textLama = btn.textContent;
    btn.textContent = 'Mengirim Data...';

    document.querySelectorAll('.lay-select').forEach(sel => sel.disabled = false);

    const formData = new FormData(form);

    try {
        const res = await fetch('../api/order.php?action=create', { method: 'POST', body: formData });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Backend Error Response:", text);
            btn.disabled = false;
            btn.textContent = textLama;
            return alert("Gagal memproses pesanan ke server. Cek log console.");
        }

        if (data.success) {
            clearOrderState();
            isPaymentStep = false;
            isPaymentStep = false;

            document.getElementById('btnBackHome').style.display = 'none';
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'none';
            document.getElementById('mainOrderCard').style.display = 'none';
            document.getElementById('step3').style.display = 'none';

            window.history.replaceState(null, '', '?success=' + data.kode_pesanan);

            const resStatus = await fetch(`../api/order.php?action=status&kode=${data.kode_pesanan}&_t=${new Date().getTime()}`, { cache: 'no-store' });
            const statusJson = await resStatus.json();
            if (statusJson.success && statusJson.data) {
                siapkanStruk(statusJson.data);
            }
            document.getElementById('step3').style.display = 'block';
            window.scrollTo(0, 0);

        } else {
            alert(data.message || 'Terjadi kesalahan sistem di database');
            btn.disabled = false;
            btn.textContent = textLama;
        }
    } catch (e) {
        console.error(e);
        btn.disabled = false;
        btn.textContent = textLama;
        alert("Terjadi kesalahan jaringan.");
    }
}

// === Google Maps API Logic ===
let ongkirDinamis = 0;
const STORE_LAT = -6.199487902393529;
const STORE_LNG = 106.9400659797928;
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
    mapCreated = true;

    const oAlamat = document.getElementById('oAlamat');
    const searchAlamat = document.getElementById('searchAlamat');
    if (!oAlamat) return;

    let initLat = STORE_LAT;
    let initLng = STORE_LNG;
    let hasSavedLocation = false;

    const oLatInput = document.getElementById('oLat');
    const oLngInput = document.getElementById('oLng');

    // Coba ambil dari sessionStorage dulu
    const sessionLat = sessionStorage.getItem('bupp_order_lat');
    const sessionLng = sessionStorage.getItem('bupp_order_lng');

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

    // Pastikan container terlihat sebelum init (cegah 0x0 size)
    requestAnimationFrame(() => {
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
    });
}

function setupMapControls(oAlamat, searchAlamat) {
    // Tombol Konfirmasi Lokasi
    const btnKonfirmasi = document.getElementById('btnKonfirmasiLokasi');
    if (btnKonfirmasi) {
        btnKonfirmasi.addEventListener('click', function() {
            if (searchAlamat && searchAlamat.value.trim() !== '') {
                oAlamat.value = searchAlamat.value;
                if (draggableMarker) {
                    const pos = draggableMarker.getPosition();
                    sessionStorage.setItem('bupp_order_lat', pos.lat());
                    sessionStorage.setItem('bupp_order_lng', pos.lng());
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

    // ── Custom Search + Rekomendasi Google Autocomplete ──
    if (searchAlamat) {
        const suggestBox = document.getElementById('searchSuggestBox');
        let debounceTimer;
        let autocompleteService = null;

        // Load places library sekali saja, lalu simpan instance
        async function getAutocompleteService() {
            if (autocompleteService) return autocompleteService;
            await google.maps.importLibrary('places');
            autocompleteService = new google.maps.places.AutocompleteService();
            return autocompleteService;
        }

        async function geocodeAndMove(query) {
            if (!query || query.trim().length < 3) return;
            const geocoder = new google.maps.Geocoder();
            try {
                const result = await geocoder.geocode(
                    { address: query + ', Indonesia', componentRestrictions: { country: 'ID' } }
                );
                if (result.results && result.results[0]) {
                    const loc = result.results[0].geometry.location;
                    searchAlamat.value = result.results[0].formatted_address;
                    moveMapAndPin(loc, result.results[0].geometry.viewport);
                    const lat = typeof loc.lat === 'function' ? loc.lat() : loc.lat;
                    const lng = typeof loc.lng === 'function' ? loc.lng() : loc.lng;
                    hitungJarak({ lat, lng });
                }
            } catch (e) {
                console.warn('Geocode failed:', e);
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

        function showSuggestions(predictions) {
            if (!suggestBox) return;
            if (!predictions || predictions.length === 0) { hideSuggestions(); return; }
            suggestBox.innerHTML = '';
            predictions.forEach((pred) => {
                const mainText = pred.structured_formatting?.main_text || pred.description;
                const secText  = pred.structured_formatting?.secondary_text || '';

                const item = document.createElement('div');
                item.style.cssText = 'display:flex;align-items:flex-start;gap:10px;padding:12px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background 0.15s;font-family:inherit;';
                item.innerHTML = `
                    <svg style="flex-shrink:0;margin-top:2px;color:#ef4444;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    <div>
                        <div style="font-size:0.875rem;font-weight:600;color:#0f172a;line-height:1.3;">${mainText}</div>
                        <div style="font-size:0.75rem;color:#64748b;margin-top:2px;line-height:1.3;">${secText}</div>
                    </div>`;
                item.addEventListener('mouseenter', () => item.style.background = '#f0f9ff');
                item.addEventListener('mouseleave', () => item.style.background = '');
                item.addEventListener('mousedown', async (e) => {
                    e.preventDefault();
                    hideSuggestions();
                    searchAlamat.value = mainText;
                    const infoText = document.getElementById('maps_info_text');
                    if (infoText) infoText.innerHTML = '<span style="color:#3b82f6;font-size:0.85rem;">⏳ Memuat lokasi...</span>';
                    // Gunakan Google Geocoder untuk resolve koordinat (lebih stabil dari PlacesService)
                    await geocodeAndMove(pred.description || mainText + (secText ? ', ' + secText : ''));
                });
                suggestBox.appendChild(item);
            });
            suggestBox.style.display = 'block';
        }

        function hideSuggestions() {
            if (suggestBox) suggestBox.style.display = 'none';
        }

        searchAlamat.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 3) { hideSuggestions(); return; }
            debounceTimer = setTimeout(async () => {
                try {
                    const svc = await getAutocompleteService();
                    svc.getPlacePredictions(
                        {
                            input: val,
                            componentRestrictions: { country: 'id' },
                            location: new google.maps.LatLng(STORE_LAT, STORE_LNG),
                            radius: 50000,
                            language: 'id'
                        },
                        function(predictions, status) {
                            if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
                                showSuggestions(predictions.slice(0, 6));
                            } else {
                                hideSuggestions();
                            }
                        }
                    );
                } catch (err) {
                    console.warn('Google Autocomplete failed:', err);
                    hideSuggestions();
                }
            }, 300);
        });

        searchAlamat.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(debounceTimer);
                hideSuggestions();
                const val = this.value.trim();
                if (val.length >= 3) geocodeAndMove(val);
            }
            if (e.key === 'Escape') hideSuggestions();
        });

        document.addEventListener('click', function(e) {
            if (e.target !== searchAlamat && !suggestBox?.contains(e.target)) hideSuggestions();
        });

        searchAlamat.addEventListener('focus', function() {
            if (this.value.trim().length >= 3 && suggestBox?.children.length > 0) {
                suggestBox.style.display = 'block';
            }
        });
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
    sessionStorage.setItem('bupp_order_lat', lat2);
    sessionStorage.setItem('bupp_order_lng', lng2);

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
                if (btnKonfirmasi) btnKonfirmasi.style.display = 'flex';
                if (searchAlamatInput && searchAlamatInput.parentElement) searchAlamatInput.parentElement.style.display = 'block';
                infoText.innerHTML = `
                    <div style="color: #0f172a; background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center; font-size:clamp(0.8rem, 3vw, 0.95rem); line-height: 1.5;">
                        Mohon maaf, lokasi Anda melampaui batas maksimal (Maks. 25 KM). Namun jangan khawatir, pesanan Anda <b>tetap dapat kami proses</b>.<br><br>Silakan lanjutkan pemesanan, dan tim kami akan menghubungi Anda melalui <b>WhatsApp</b> untuk penyesuaian biaya kirim.
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

        if (localStorage.getItem('bup_step') === '2') {
            reviewOrder(true);
            lanjutKePembayaran();
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


