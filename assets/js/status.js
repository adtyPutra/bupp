const ICONS = {
    check: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`,

    // Ikon untuk Box Info Atas
    user_grad: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
    cal_grad: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>`,
    truck_grad: `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="15" height="13" x="1" y="6" rx="2" ry="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="16.5" cy="18.5" r="2.5"/></svg>`,

    // Ikon Timeline
    diterima: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M9 14l2 2 4-4"></path></svg>`,
    dicuci: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16l-1.5 11.5A2 2 0 0 1 16.5 22h-9A2 2 0 0 1 5.5 20.5L4 9z"/><path d="M8 9V6a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/><circle cx="12" cy="14" r="2"/><circle cx="9" cy="16" r="1.5"/><circle cx="15" cy="16" r="1.5"/></svg>`,
    dikeringkan: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.59 4.59A2 2 0 1 1 11 8H2m10.59 11.41A2 2 0 1 0 14 16H2m15.73-8.27A2.5 2.5 0 1 1 19.5 12H2"/></svg>`,
    finishing: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>`,
    siap_diambil: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2v0a2.5 2.5 0 0 1-2.5-2.5v0a2.5 2.5 0 0 0-5 0v0a2.5 2.5 0 0 1-5 0v0a2.5 2.5 0 0 0-5 0v0a2 2 0 0 1-2-2V7"/></svg>`,
    diantar_kurir: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="15" height="13" x="1" y="6" rx="2" ry="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="16.5" cy="18.5" r="2.5"/></svg>`,
    selesai: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`
};

// Global default TAHAPAN for reference (now dynamically built in checkStatus)
const DEFAULT_TAHAPAN = [
    { k: 'diterima', l: 'Diterima', icon: 'diterima', d: 'Pesanan masuk dan menunggu konfirmasi pembayaran admin.' },
    { k: 'dicuci', l: 'Dicuci', icon: 'dicuci', d: 'Pesanan sedang dalam proses pencucian.' },
    { k: 'dikeringkan', l: 'Dikeringkan', icon: 'dikeringkan', d: 'Pesanan sedang dalam proses pengeringan.' },
    { k: 'finishing', l: 'Finishing', icon: 'finishing', d: 'Pesanan sedang dalam tahap pengecekan akhir dan persiapan pengemasan.' }
];

async function checkStatus() {
    const inputField = document.getElementById('statusInput');
    const id = inputField.value.trim().toUpperCase();
    const resCard = document.getElementById('statusResult');

    if (!id) { inputField.focus(); return; }

    // Update URL agar bisa di-refresh tanpa hilang
    const newUrl = new URL(window.location);
    newUrl.searchParams.set('kode', id);
    window.history.replaceState({}, '', newUrl);
    resCard.classList.add('show');
    resCard.innerHTML = '<div style="padding:40px;text-align:center;color:#64748b;font-weight:600;">Mencari data pesanan...</div>';

    try {
        const r = await fetch('../api/order.php?action=status&kode=' + encodeURIComponent(id));
        const json = await r.json();

        if (!json.success) {
            resCard.innerHTML = `
                <div style="padding:40px;text-align:center;">
                    <div style="color:#ef4444;font-size:1.2rem;font-weight:800;margin-bottom:8px;">Pesanan Tidak Ditemukan</div>
                    <div style="color:#64748b;font-size:0.95rem;">Pesanan Anda dengan kode <strong>${id}</strong> tidak ditemukan. Pastikan kode pesanan sudah benar.</div>
                </div>`;
            const btnPrint = document.getElementById('btnPrint');
            if (btnPrint) btnPrint.style.display = 'none';
            return;
        }

        const o = json.data;

        // --- LOGIKA MENUNGGU PEMBAYARAN & DESKRIPSI DINAMIS ---
        const isWaitingPayment = (o.metode_bayar !== 'tunai' && o.metode_bayar !== 'cash') && (o.status_bayar !== 'confirmed' && o.status_bayar !== 'lunas');

        const mMetode = (o.nama_metode || o.metode_pengiriman || '').toLowerCase();
        const isAntarSendiri = mMetode.includes('antar') && !mMetode.includes('jemput');

        let dynamicTahapan = JSON.parse(JSON.stringify(DEFAULT_TAHAPAN));

        if (isAntarSendiri) {
            dynamicTahapan[0].d = 'Pesanan tercatat. Silakan antar barang Anda ke toko kami untuk mulai diproses.';
            dynamicTahapan.push({ k: 'siap_diambil', l: 'Siap Diambil', icon: 'siap_diambil', d: 'Pengerjaan selesai! Pesanan Anda siap untuk diambil di toko kami.' });
            dynamicTahapan.push({ k: 'selesai', l: 'Selesai', icon: 'selesai', d: 'Pesanan selesai dan telah diambil. Terima kasih telah menggunakan layanan BUP Laundry.' });
        } else {
            dynamicTahapan[0].d = 'Pesanan tercatat. Kurir kami akan segera melakukan penjemputan ke Alamat Anda.';
            dynamicTahapan.push({ k: 'diantar_kurir', l: 'Diantar Kurir', icon: 'diantar_kurir', d: 'Pengerjaan selesai! Pesanan Anda sedang dalam perjalanan diantar oleh kurir kami.' });
            dynamicTahapan.push({ k: 'selesai', l: 'Selesai', icon: 'selesai', d: 'Pesanan selesai dan telah diterima. Terima kasih telah menggunakan layanan BUP Laundry.' });
        }

        const curIdx = dynamicTahapan.findIndex(s => s.k === o.status_pesanan);

        // Format waktu update terakhir
        let updatedAtFormatted = '';
        if (o.updated_at) {
            const d = new Date(o.updated_at.replace(' ', 'T'));
            if (!isNaN(d)) {
                updatedAtFormatted = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
                    + ' · ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
        }

        // --- RENDER TIMELINE ---
        const stepsHtml = dynamicTahapan.map((s, i) => {
            const isDone = i < curIdx || (i === curIdx && s.k === 'selesai');
            const isActive = i === curIdx && s.k !== 'selesai';
            const tlClass = isDone ? 'done' : (isActive ? 'active' : 'pending');
            const iconHtml = isDone ? ICONS.check : ICONS[s.icon];
            const statusBadge = isDone ? 'SELESAI' : (isActive ? 'SEDANG DIKERJAKAN' : 'MENUNGGU');

            // Tampilkan timestamp hanya pada step aktif (current) dan step selesai terakhir
            const showTs = (isActive || (isDone && s.k === 'selesai')) && updatedAtFormatted;
            const tsHtml = showTs
                ? `<div class="tl-timestamp">${updatedAtFormatted}</div>`
                : '';

            return `
                <div class="tl-step ${tlClass}">
                    <div class="tl-circle">${iconHtml}</div>
                    <div class="tl-content">
                        <div class="tl-title">${s.l}</div>
                        <div class="tl-status">${statusBadge}</div>
                        ${tsHtml}
                        <div class="tl-desc">${s.d}</div>
                    </div>
                </div>
            `;
        }).join('');

        // --- SIAPKAN DATA UNTUK STRUK (PRINT) ---
        document.getElementById('rKode').textContent = o.kode_pesanan;
        const now = new Date();
        const tgl = now.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        const jam = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        document.getElementById('rTglCetak').textContent = `${tgl} ${jam}`;
        document.getElementById('rNama').textContent = o.nama_pelanggan || o.nama || '-';
        document.getElementById('rWa').textContent = o.no_wa || o.no_whatsapp || '-';
        let tglPesanFormatted = '-';
        if (o.created_at) {
            const d = new Date(o.created_at.replace(' ', 'T'));
            tglPesanFormatted = !isNaN(d) ? d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) : o.created_at;
        }
        document.getElementById('rTglPesan').textContent = tglPesanFormatted;
        let catatanUmum = '-';
        if (o.catatan) { catatanUmum = o.catatan; }
        else if (o.items && o.items.length > 0) {
            const itemWithNote = o.items.find(item => item.catatan && item.catatan.trim() !== '');
            if (itemWithNote) catatanUmum = itemWithNote.catatan;
        }
        document.getElementById('rCatatan').textContent = catatanUmum;
        const rowCatatan = document.getElementById('rowCatatan');
        if (rowCatatan) {
            if (catatanUmum && catatanUmum.trim() !== '' && catatanUmum !== '-') {
                rowCatatan.classList.remove('hidden-row');
            } else {
                rowCatatan.classList.add('hidden-row');
            }
        }
        document.getElementById('rPengiriman').textContent = o.nama_metode || o.metode_pengiriman || 'Reguler';

        // Tampilkan baris alamat & waktu jemput HANYA jika metode butuh penjemputan
        // Indikator utama: waktu_penjemputan — kalau ada, berarti metode jemput
        const rowAlamat = document.getElementById('rowAlamat');
        const rowWaktuJemput = document.getElementById('rowWaktuJemput');
        const adaWaktuJemput = o.waktu_penjemputan && !o.waktu_penjemputan.startsWith('0000-00-00') && o.waktu_penjemputan.trim() !== '';

        if (adaWaktuJemput) {
            // Metode jemput — tampilkan baris alamat dan waktu
            document.getElementById('rAlamat').textContent = (o.alamat && o.alamat.trim() !== '') ? o.alamat : '-';
            rowAlamat.classList.remove('hidden-row');
            const tglJemputFormatted = (o.tanggal_pesan && !o.tanggal_pesan.startsWith('0000-00-00')) ? new Date(o.tanggal_pesan).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' }) : '';
            document.getElementById('rWaktuJemput').innerHTML = tglJemputFormatted ? tglJemputFormatted + ' <span class="desktop-only">|</span> <span class="mobile-break">' + o.waktu_penjemputan + '</span>' : o.waktu_penjemputan;
            rowWaktuJemput.classList.remove('hidden-row');
        } else {
            // Metode antar ke pelanggan atau ambil di toko — sembunyikan keduanya
            document.getElementById('rAlamat').textContent = '-';
            rowAlamat.classList.add('hidden-row');
            document.getElementById('rWaktuJemput').textContent = '-';
            rowWaktuJemput.classList.add('hidden-row');
        }
        document.getElementById('rMetodeBayar').textContent = (o.metode_bayar === 'tunai' || o.metode_bayar === 'cash') ? 'Tunai (Bayar Ditempat)' : 'Transfer BCA';

        const rOngkirRow = document.getElementById('rOngkirRow');
        const ongkir = parseInt(o.ongkir) || 0;
        if (rOngkirRow) {
            if (o.waktu_penjemputan && o.waktu_penjemputan.trim() !== '') {
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

        let rcptStatusBayar;
        const rTitleEl = document.getElementById('rTitle');
        if (o.status_bayar === 'confirmed' || o.status_bayar === 'lunas') {
            rcptStatusBayar = '<span style="color:#10b981 !important; font-weight:800;">LUNAS</span>';
            if (rTitleEl) rTitleEl.textContent = 'BUKTI PEMBAYARAN';
        } else {
            if (rTitleEl) rTitleEl.textContent = 'BUKTI PEMESANAN';
            if (o.metode_bayar === 'tunai' || o.metode_bayar === 'cash') {
                rcptStatusBayar = '<span style="color:#ea580c !important; font-weight:800;">BELUM LUNAS</span>';
            } else {
                rcptStatusBayar = '<span style="color:#f59e0b !important; font-weight:800;">MENUNGGU KONFIRMASI</span>';
            }
        }
        document.getElementById('rStatusBayar').innerHTML = rcptStatusBayar;
        document.getElementById('rTotal').textContent = 'Rp ' + parseInt(o.total_harga || o.total_bayar || 0).toLocaleString('id-ID');

        // --- RENDER DESAIN KARTU MULTI-ITEM ---
        let itemsHtml = '';
        const arrayItems = o.items || o.detail || o.detail_pesanan;

        let rcptItemsHtml = '';
        let realItemIndex = 1;
        let itemsCards = [];
        if (arrayItems && Array.isArray(arrayItems) && arrayItems.length > 0) {
            arrayItems.forEach((item, index) => {
                const kategori = item.kategori || item.nama_kategori || 'KATEGORI';
                const layanan = item.nama_layanan || item.layanan || item.jenis_layanan || 'Layanan Standar';
                const merek = item.merek || item.nama_merek || item.merk_item || '-';
                const qty = item.qty || item.jumlah || 1;
                const ukuran = item.ukuran || '-';
                const warna = item.warna || '-';
                const harga = parseInt(item.harga_satuan || 0);

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

            // Build UI for On-Screen Cards (Clean & Modern)
            itemsCards.forEach((c, idx) => {
                let extrasHtml = '';
                if (c.extras.length > 0) {
                    extrasHtml += `<div style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display:flex; flex-direction:column; gap:6px;">`;
                    c.extras.forEach(ext => {
                        extrasHtml += `
                            <div style="display:flex; align-items:center; gap:6px; font-size: 0.9rem; font-weight: 700; color: #0284c7;">
                                | Extra: ${ext.layanan}
                            </div>
                        `;
                    });
                    extrasHtml += `</div>`;
                }

                let detailStrArr = [];
                if (c.merek && c.merek !== '-') detailStrArr.push(c.merek);
                if (c.ukuran && c.ukuran !== '-') detailStrArr.push(`Size ${c.ukuran}`);
                if (c.warna && c.warna !== '-') detailStrArr.push(c.warna);

                itemsHtml += `
                    <div style="background: #fff; border: 1px solid var(--border-color); border-left: 4px solid #3b82f6; border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 2px;">
                            <div style="font-size:1.05rem; font-weight:800; color:var(--text-dark);">${c.kategori} - ${c.layanan}</div>
                            <div style="background: var(--primary-light); color: var(--primary); font-size: 0.85rem; font-weight: 800; padding: 4px 10px; border-radius: 6px;">x${c.qty}</div>
                        </div>
                        ${detailStrArr.length > 0 ? `
                        <div style="font-size: 0.85rem; font-weight: 600; color: #64748b;">
                            ${detailStrArr.join(' | ')}
                        </div>
                        ` : ''}
                        ${extrasHtml}
                    </div>
                `;
            });

            // Build UI for Print Receipt (Struk)
            rcptItemsHtml += `<div style="margin-top:12px; border-top: 2px solid #e2e8f0;">`;
            itemsCards.forEach((c, idx) => {
                let ukTxt = c.ukuran && c.ukuran !== '-' ? `Size ${c.ukuran}` : '';
                let detailsArr = [];
                if (c.merek && c.merek !== '-') detailsArr.push(c.merek);
                if (ukTxt) detailsArr.push(ukTxt);
                if (c.warna && c.warna !== '-') detailsArr.push(c.warna);
                let detailStr = detailsArr.length > 0 ? ` (${detailsArr.join(' | ')})` : '';

                const subtotalMain = (c.harga || 0) * c.jumlah;

                rcptItemsHtml += `
                <div style="padding: 12px 0; border-bottom: 1px dashed #cbd5e1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-weight: 900; font-size: clamp(0.75rem, 3.5vw, 0.95rem); color: #0f172a;">Item #${idx + 1}</span>
                        <span style="font-weight: 900; font-size: clamp(0.65rem, 3vw, 0.85rem); color: #0f172a; background: #f1f5f9; padding: 2px 8px; border-radius: 4px;">x${c.jumlah}</span>
                    </div>
                    
                    <div style="display: flex; align-items: flex-start; margin-bottom: 6px;">
                        <span style="color: #64748b; font-weight: 500; width: 175px; flex-shrink: 0; font-size: 0.85rem;">Barang:</span>
                        <div style="text-align: right; font-weight: 800; color: #000; flex: 1; font-size: 0.85rem;">
                            ${c.kategori}
                            ${detailStr ? `<br><span style="font-size: 0.85em; font-weight: 600; color: #64748b;">${detailStr}</span>` : ''}
                        </div>
                    </div>
                    
                    <div style="display: flex; margin-bottom: 6px;">
                        <span style="color: #64748b; font-weight: 500; width: 175px; flex-shrink: 0; font-size: 0.85rem;">Layanan:</span>
                        <span style="font-weight: 800; text-align: right; color: #000; flex: 1; font-size: 0.85rem;">${c.jenis}</span>
                    </div>

                    <div style="display: flex;">
                        <span style="color: #64748b; font-weight: 500; width: 175px; flex-shrink: 0; font-size: 0.85rem;">Harga:</span>
                        <span style="font-weight: 800; text-align: right; color: #000; flex: 1; font-size: 0.85rem;">Rp ${subtotalMain.toLocaleString('id-ID')}</span>
                    </div>
                `;

                if (c.extras && c.extras.length > 0) {
                    c.extras.forEach(ext => {
                        const subtotalExtra = ext.harga_satuan * ext.jumlah;
                        rcptItemsHtml += `
                        <div style="display: flex; margin-top: 6px; margin-bottom: 2px;">
                            <span style="color: #0284c7; font-weight: 500; width: 175px; flex-shrink: 0; font-size: 0.85rem;">↳ Extra:</span>
                            <span style="color: #0369a1; font-weight: 800; text-align: right; flex: 1; font-size: 0.85rem;">${ext.jenis}</span>
                        </div>
                        <div style="display: flex;">
                            <span style="color: #0284c7; font-weight: 500; width: 175px; flex-shrink: 0; font-size: 0.85rem;">↳ Harga:</span>
                            <span style="color: #0369a1; font-weight: 800; text-align: right; flex: 1; font-size: 0.85rem;">Rp ${subtotalExtra.toLocaleString('id-ID')}</span>
                        </div>
                        `;
                    });
                }
                rcptItemsHtml += `</div>`;
            });
            rcptItemsHtml += `</div>`;

        } else {
            // Fallback for single legacy item format
            let kategori = o.kategori || o.nama_kategori || 'KATEGORI';
            let layanan = o.nama_layanan || o.jenis || o.layanan || 'Layanan Standar';
            let merek = o.merek || '-';
            let ukuran = o.ukuran || '-';

            let detailStrArr = [];
            if (merek !== '-') detailStrArr.push(merek);
            if (ukuran !== '-') detailStrArr.push(`Size ${ukuran}`);

            itemsHtml = `
                <div style="background: #fff; border: 1px solid var(--border-color); border-radius: 12px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 6px;">
                        <div style="font-size:1.05rem; font-weight:800; color:var(--text-dark);">${kategori} - ${layanan}</div>
                        <div style="background: var(--primary-light); color: var(--primary); font-size: 0.85rem; font-weight: 800; padding: 4px 10px; border-radius: 6px;">x1</div>
                    </div>
                    ${detailStrArr.length > 0 ? `
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        ${detailStrArr.map(d => `<span style="font-size: 0.8rem; background: #f8fafc; border: 1px solid #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 6px; font-weight: 700;">${d}</span>`).join('')}
                    </div>
                    ` : ''}
                </div>
            `;
            let detailStr = detailStrArr.length > 0 ? `(${detailStrArr.join(' | ')})` : '';
            rcptItemsHtml = `
                <div style="margin-top:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="font-size:0.9rem; font-weight:700; color:#0f172a;">${kategori} - ${layanan} <span style="font-weight:800; color:#475569;">(x1)</span></div>
                    </div>
                    ${detailStr ? `<div style="font-size:0.8rem; color:#64748b;">${detailStr}</div>` : ''}
                </div>
            `;
        }
        document.getElementById('rItemList').innerHTML = rcptItemsHtml;

        const namaLengkap = o.nama_pelanggan || o.nama || 'Pelanggan BUP';
        const namaMetode = o.nama_metode || o.metode_pengiriman || 'Reguler';
        const totalHarga = parseInt(o.total_harga || o.total_bayar || 0).toLocaleString('id-ID');

        let statusBayarText = '';
        if (o.status_pesanan === 'batal') {
            statusBayarText = '<span class="ig-val-lg" style="color: #ef4444; white-space: nowrap;">Batal</span>';
        } else if (o.status_bayar === 'confirmed' || o.status_bayar === 'lunas') {
            statusBayarText = '<span class="ig-val-lg" style="color: #15803d; white-space: nowrap;">Lunas</span>';
        } else {
            if (o.metode_bayar === 'tunai') {
                statusBayarText = '<span class="ig-val-lg" style="color: #c2410c; white-space: nowrap; display: inline-block; font-size: clamp(0.75rem, 3.5vw, 1.05rem);">Belum Lunas</span>';
            } else {
                statusBayarText = '<span class="ig-val-lg" style="color: #b45309; white-space: nowrap; display: inline-block; font-size: clamp(0.75rem, 3.5vw, 1.05rem);">Menunggu Konfirmasi</span>';
            }
        }

        let finalTimelineHtml = '';
        if (o.status_pesanan === 'batal') {
            const alasanHtml = o.catatan_pembatalan ? `
                <div style="background: #fff; padding: 16px; border-radius: 8px; margin: 0 auto 20px auto; max-width: 500px; text-align: left; border-left: 4px solid #ef4444; border: 1px solid #fecaca; box-shadow: 0 2px 4px rgba(239,68,68,0.1);">
                    <div style="color: #991b1b; font-weight: 800; font-size: 0.85rem; margin-bottom: 4px;">ALASAN PEMBATALAN:</div>
                    <div style="color: #450a0a; font-size: 0.95rem; font-weight: 600;">${o.catatan_pembatalan}</div>
                </div>
            ` : '';

            finalTimelineHtml = `
                <div class="timeline-sec" style="border-top:1px solid var(--border-color);">
                    <div style="background:#fef2f2; border: 1.5px solid #fca5a5; border-radius:16px; padding: clamp(16px, 4vw, 24px); text-align:center;">
                        <div style="display:inline-flex; justify-content:center; align-items:center; width:56px; height:56px; background:#fee2e2; border-radius:50%; margin-bottom:14px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </div>
                        <h4 style="margin:0 0 8px 0; color:#991b1b; font-size: clamp(1rem, 4.5vw, 1.25rem); font-weight:800; line-height: 1.4;">PESANAN DIBATALKAN</h4>
                       <p style="margin:0 0 18px 0; color:#7f1d1d; font-size: clamp(0.8rem, 3.5vw, 0.9rem); line-height: 1.6;">
                            Mohon maaf, pesanan Anda dengan kode <strong>${o.kode_pesanan}</strong> tidak dapat diproses. Silakan hubungi admin melalui WhatsApp untuk informasi lebih lanjut atau pemesanan ulang.
                        </p>
                        ${alasanHtml}
                        <a href="https://wa.me/6281211811577?text=Halo%20BUP,%20saya%20ingin%20bertanya%20mengenai%20pesanan%20saya%20yang%20dibatalkan%20dengan%20kode%20${o.kode_pesanan}" target="_blank" style="display:inline-flex; align-items:center; gap:8px; background:#ef4444; color:#fff; padding:12px 24px; border-radius:10px; font-weight:700; font-size: clamp(0.85rem, 3.5vw, 0.95rem); text-decoration:none; box-shadow:0 4px 12px rgba(239,68,68,0.2); transition:all 0.2s;">
                            💬 Hubungi WhatsApp
                        </a>
                    </div>
                </div>
            `;
        } else {
            finalTimelineHtml = `
                <div class="timeline-sec" style="margin-top: 16px;">
                    <div class="timeline-wrap">
                        ${stepsHtml}
                    </div>
                </div>
            `;
        }

        resCard.innerHTML = `
            <div class="order-info-header">
                <div class="info-grid-top">
                    <div>
                        <div class="ig-label">Kode Pesanan</div>
                        <div class="ig-val-lg">${o.kode_pesanan}</div>
                    </div>
                    <div style="text-align: center;">
                        <div class="ig-label">Total Harga</div>
                        <div class="ig-val-lg text-primary" style="display:inline-block;">Rp ${totalHarga}</div>
                    </div>
                    <div style="text-align: center;">
                        <div class="ig-label">Status Pembayaran</div>
                        <div style="margin-top: 6px;">${statusBayarText}</div>
                    </div>
                </div>
                
                <div class="info-list-wrapper">
                    <div class="il-item il-item-full">
                        <div class="il-icon grad-blue">${ICONS.user_grad}</div>
                        <div class="il-text">
                            <div class="il-label">Nama Pelanggan</div>
                            <div class="il-val">${namaLengkap}</div>
                        </div>
                    </div>
                    <div class="il-item">
                        <div class="il-icon grad-orange">${ICONS.cal_grad}</div>
                        <div class="il-text">
                          <div class="il-label">Tanggal Order</div>
                          <div class="il-val">${o.created_at ? new Date(o.created_at.replace(' ', 'T')).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'}</div>
                        </div>
                    </div>
                    <div class="il-item">
                        <div class="il-icon grad-green">${ICONS.truck_grad}</div>
                        <div class="il-text">
                            <div class="il-label">Metode Pengiriman</div>
                            <div class="il-val">${namaMetode}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="order-items-sec">
                <div class="rincian-title">RINCIAN ITEM CUCIAN</div>
                <div class="rincian-grid">
                    ${itemsHtml}
                </div>
            </div>

            ${finalTimelineHtml}
        `;

    } catch (e) {
        resCard.innerHTML = '<div style="padding:40px;text-align:center;color:#ef4444;font-weight:700;">Pesanan Anda tidak ditemukan. Pastikan kode pesanan sudah benar.</div>';
        console.error(e);
    }
}

document.getElementById('statusInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') checkStatus();
});

// Auto load jika ada parameter kode di URL
document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const kode = params.get('kode');
    if (kode) {
        const inputField = document.getElementById('statusInput');
        if (inputField) {
            inputField.value = kode;
            checkStatus();
        }
    }
});

async function handleUploadBukti(e, kode) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitBukti');
    const form = document.getElementById('formUploadBukti');
    
    btn.disabled = true;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunggah...';

    try {
        const formData = new FormData(form);
        formData.append('kode_pesanan', kode);

        const res = await fetch('../api/upload_bukti.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            alert('Bukti pembayaran berhasil diunggah!');
            window.location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan saat mengunggah.');
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    } catch(err) {
        alert('Terjadi kesalahan jaringan.');
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    }
}



















