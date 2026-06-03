
/* === admin-customers.js === */

function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function formatDate(dateStr, createdAt) {
    if (!dateStr) return '-';
    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const d = new Date(dateStr);
    const tgl = String(d.getDate()).padStart(2, '0') + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    if (createdAt) {
        const c = new Date(createdAt);
        const jam = String(c.getHours()).padStart(2, '0') + ':' + String(c.getMinutes()).padStart(2, '0');
        return tgl + ' ' + jam + ' WIB';
    }
    return tgl;
}

function formatMetode(metode) {
    return metode ? metode.replace('_', ' ').toUpperCase() : '-';
}

function bukaRiwayat(nama, pelanggan_id) {
    document.getElementById('r-namaPelanggan').innerText = nama;
    document.getElementById('modalRiwayat').style.display = 'flex';

    const tbody = document.getElementById('r-tabelBody');
    tbody.innerHTML = ''; // Bersihkan tabel

    // Filter pesanan khusus untuk pelanggan yang diklik
    const userOrders = allOrders.filter(order => order.pelanggan_id == pelanggan_id);

    if (userOrders.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Belum ada riwayat transaksi</td></tr>`;
        return;
    }

    // Membangun baris tabel sesuai data asli
    userOrders.forEach(order => {
        // Cek jika status lunas
        const isLunas = order.status_bayar === 'confirmed' || order.status_bayar === 'cash';

        const statusHtml = isLunas
            ? '<span class="status-badge status-lunas">LUNAS</span>'
            : '<span class="status-badge status-pending">PENDING</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="white-space: nowrap;">${formatDate(order.tanggal_pesan, order.created_at)}</td>
            <td><strong style="color: #0f172a;">${order.kode_pesanan}</strong></td>
            <td style="color: #64748b; font-size: 0.8rem; font-weight: 700;">${formatMetode(order.metode_bayar)}</td>
            <td style="white-space: nowrap;"><strong style="color: #0f172a;">${formatRupiah(order.total_harga)}</strong></td>
            <td>${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}


function formatRupiah(angka) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(angka);
}

function formatDate(dateStr, createdAt) {
    if (!dateStr) return '-';
    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    // Ganti spasi dengan T agar aman di Safari/iOS
    const safeDateStr = dateStr.replace(' ', 'T');
    const d = new Date(safeDateStr);
    
    // Jika masih invalid date (fallback)
    if (isNaN(d.getTime())) return dateStr;

    const tgl = String(d.getDate()).padStart(2, '0') + ' ' + bulan[d.getMonth()] + ' ' + d.getFullYear();
    if (createdAt) {
        const safeCreated = createdAt.replace(' ', 'T');
        const c = new Date(safeCreated);
        if (!isNaN(c.getTime())) {
            const jam = String(c.getHours()).padStart(2, '0') + ':' + String(c.getMinutes()).padStart(2, '0');
            return tgl + ' ' + jam + ' WIB';
        }
    }
    return tgl;
}

function formatMetode(metode) {
    return metode ? metode.replace('_', ' ').toUpperCase() : '-';
}

function bukaRiwayat(nama, pelanggan_id) {
    document.getElementById('r-namaPelanggan').innerText = nama;
    document.getElementById('modalRiwayat').style.display = 'flex';

    const tbody = document.getElementById('r-tabelBody');
    tbody.innerHTML = ''; // Bersihkan tabel

    // Filter pesanan khusus untuk pelanggan yang diklik
    const userOrders = allOrders.filter(order => order.pelanggan_id == pelanggan_id);

    if (userOrders.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 40px;">Belum ada riwayat transaksi</td></tr>`;
        return;
    }

    // Membangun baris tabel sesuai data asli
    userOrders.forEach(order => {
        // Cek jika status lunas
        const isLunas = order.status_bayar === 'confirmed' || order.status_bayar === 'cash' || order.status_bayar === 'lunas';

        const statusHtml = isLunas
            ? '<span class="status-badge status-lunas">LUNAS</span>'
            : '<span class="status-badge status-pending">PENDING</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="white-space: nowrap;">${formatDate(order.tanggal_pesan, order.created_at)}</td>
            <td style="white-space: nowrap;"><strong style="color: #0f172a;">${order.kode_pesanan}</strong></td>
            <td style="color: #64748b; font-size: 0.8rem; font-weight: 700; white-space: nowrap;">${formatMetode(order.metode_bayar)}</td>
            <td style="white-space: nowrap;"><strong style="color: #0f172a;">${formatRupiah(order.total_harga)}</strong></td>
            <td>${statusHtml}</td>
        `;
        tbody.appendChild(tr);
    });
}

function tutupRiwayat() {
    document.getElementById('modalRiwayat').style.display = 'none';
}

// Tutup modal jika klik area luar modal
window.onclick = function (event) {
    if (event.target.className == 'modal-overlay') {
        tutupRiwayat();
    }
}

/* === admin-orders.js === */

function toggleCatatanBatal(status) {
    const group = document.getElementById('group_catatan_batal');
    const input = document.getElementById('e_catatan_pembatalan');
    if (!group || !input) return;
    if (status === 'batal') {
        group.style.display = 'block';
        input.required = true;
    } else {
        group.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function bukaModalEdit(data) {
    document.getElementById('e_id').value = data.id;
    document.getElementById('e_pelanggan_id').value = data.pelanggan_id;

    document.getElementById('e_kode').innerText = "Pesanan #" + data.kode;
    document.getElementById('e_nama_display').innerText = data.nama;
    document.getElementById('e_jml_item').innerHTML = data.items.length + " Item Cucian";
    document.getElementById('e_harga').innerText = data.harga;

    document.getElementById('e_input_nama').value = data.nama;
    document.getElementById('e_input_nowa').value = data.nowa;
    document.getElementById('e_input_alamat').value = data.alamat_asli;

    // Logika menyembunyikan status pengiriman yang tidak relevan & Tampil/Sembunyi Tanggal Jemput
    const mMetode = (data.metode_pengiriman || '').toLowerCase();
    const isAntarSendiri = mMetode.includes('antar') && !mMetode.includes('jemput');
    
    const wjGroupEdit = document.getElementById('e_group_jemput');
    const alamatInputGroup = document.getElementById('e_input_alamat') ? document.getElementById('e_input_alamat').closest('.form-group') : null;
    
    if (wjGroupEdit) {
        if (!isAntarSendiri) {
            wjGroupEdit.style.display = 'block';
            if (alamatInputGroup) alamatInputGroup.style.display = 'block';
            document.getElementById('e_input_tanggal_jemput').value = data.tanggal_pesan_raw;
            document.getElementById('e_input_waktu').value = data.waktu_penjemputan;
        } else {
            wjGroupEdit.style.display = 'none';
            if (alamatInputGroup) alamatInputGroup.style.display = 'none';
            document.getElementById('e_input_tanggal_jemput').value = '';
            document.getElementById('e_input_waktu').value = '';
            document.getElementById('e_input_alamat').value = ''; // pastikan alamat kosong
        }
    } else {
        const wjInput = document.getElementById('e_input_waktu');
        if(wjInput) wjInput.value = data.waktu_penjemputan;
    }
    const optSiapDiambil = document.querySelector('#e_status option[value="siap_diambil"]');
    const optDiantarKurir = document.querySelector('#e_status option[value="diantar_kurir"]');
    if (optSiapDiambil && optDiantarKurir) {
        if (isAntarSendiri) {
            optSiapDiambil.hidden = false;
            optSiapDiambil.disabled = false;
            optDiantarKurir.hidden = true;
            optDiantarKurir.disabled = true;
        } else {
            optSiapDiambil.hidden = true;
            optSiapDiambil.disabled = true;
            optDiantarKurir.hidden = false;
            optDiantarKurir.disabled = false;
        }
    }

    document.getElementById('e_status').value = data.status.toLowerCase();
    // Set Status Bayar Dropdown
    document.getElementById('e_status_bayar').value = (data.status_bayar || 'pending').toLowerCase();

    toggleCatatanBatal(data.status_bayar ? data.status_bayar.toLowerCase() : 'pending');
    const catatanEl = document.getElementById('e_catatan_pembatalan');
    if (catatanEl) catatanEl.value = data.catatan_pembatalan || '';

    // Render Edit Items (Merek, Ukuran, Warna)
    const itemsContainer = document.getElementById('e_items_container');
    if (itemsContainer) {
        itemsContainer.innerHTML = '';
        data.items.forEach((item, index) => {
            // Abaikan Extra Treatment karena biasanya tidak punya merek/ukuran terpisah
            if (item.kategori !== 'Extra Treatment') {
                const itemDiv = document.createElement('div');
                itemDiv.style.background = '#f8fafc';
                itemDiv.style.padding = '12px';
                itemDiv.style.borderRadius = '8px';
                itemDiv.style.border = '1px solid #e2e8f0';

                const title = document.createElement('div');
                title.style.fontWeight = '700';
                title.style.fontSize = '0.85rem';
                title.style.marginBottom = '8px';
                title.style.color = '#3b82f6';
                title.innerText = item.kategori + ' - ' + item.nama_layanan;

                const grid = document.createElement('div');
                grid.style.display = 'grid';
                grid.style.gridTemplateColumns = '1fr 1fr 1fr';
                grid.style.gap = '8px';

                grid.innerHTML = `
                    <input type="hidden" name="detail_id[]" value="${item.detail_id}">
                    <div>
                        <label style="font-size:0.75rem; color:#64748b; font-weight:600;">Merek</label>
                        <input type="text" name="detail_merk[]" class="form-control" style="font-size:0.85rem; padding:6px; height:32px;" value="${item.merk_item !== '-' ? (item.merk_item || '') : ''}" placeholder="-">
                    </div>
                    <div>
                        <label style="font-size:0.75rem; color:#64748b; font-weight:600;">Ukuran</label>
                        <input type="text" name="detail_ukuran[]" class="form-control" style="font-size:0.85rem; padding:6px; height:32px;" value="${item.ukuran !== '-' ? (item.ukuran || '') : ''}" placeholder="-">
                    </div>
                    <div>
                        <label style="font-size:0.75rem; color:#64748b; font-weight:600;">Warna</label>
                        <input type="text" name="detail_warna[]" class="form-control" style="font-size:0.85rem; padding:6px; height:32px;" value="${item.warna !== '-' ? (item.warna || '') : ''}" placeholder="-">
                    </div>
                `;

                itemDiv.appendChild(title);
                itemDiv.appendChild(grid);
                itemsContainer.appendChild(itemDiv);
            }
        });
        
        if (itemsContainer.innerHTML === '') {
            itemsContainer.innerHTML = '<div style="font-size:0.85rem; color:#94a3b8; font-style:italic;">Tidak ada item yang bisa diedit.</div>';
        }
    }

    document.getElementById('modalEdit').classList.add('show');
}

function bukaModalDetail(data) {
    document.getElementById('d_kode').innerText = data.kode;
    document.getElementById('d_tanggal').innerText = data.tanggal;
    document.getElementById('d_waktu').innerHTML = (data.waktu && data.waktu !== '') ? `(${data.waktu})` : '';
    document.getElementById('d_nama').innerText = data.nama;
    document.getElementById('d_nowa').innerText = data.nowa;

    // Tampilkan alamat & waktu jemput HANYA jika metode butuh penjemputan
    const alamatGroup = document.getElementById('d_alamat').closest('.form-group');
    const wjGroup = document.getElementById('d_waktu_jemput_group');
    const wjEl = document.getElementById('d_waktu_jemput');
    const adaAlamat = data.alamat_asli && data.alamat_asli.trim() !== '';
    const adaWaktu = data.waktu_penjemputan && data.waktu_penjemputan.trim() !== '';

    if (adaAlamat && adaWaktu) {
        // Metode jemput — tampilkan alamat dan waktu
        document.getElementById('d_alamat').value = data.alamat_asli;
        alamatGroup.style.display = 'block';
        wjEl.innerHTML = data.tanggal_jemput ?
            `${data.tanggal_jemput} | ${data.waktu_penjemputan}`
            : data.waktu_penjemputan;
        wjGroup.style.display = 'block';
    } else {
        // Metode antar / ambil di toko — sembunyikan
        document.getElementById('d_alamat').value = '';
        alamatGroup.style.display = 'none';
        wjGroup.style.display = 'none';
    }

    document.getElementById('d_pengiriman').innerText = data.metode_pengiriman;

    let listHtml = '';
    let semuaCatatan = []; // Array penampung catatan

    let itemsCards = [];
    data.items.forEach(function (item) {
        if (item.kategori === 'Extra Treatment') {
            if (itemsCards.length > 0) {
                itemsCards[itemsCards.length - 1].extras.push({
                    layanan: item.nama_layanan,
                    qty: item.jumlah
                });
            }
        } else {
            itemsCards.push({
                kategori: item.kategori,
                layanan: item.nama_layanan,
                merek: item.merk_item,
                qty: item.jumlah,
                ukuran: item.ukuran,
                warna: item.warna,
                catatan: item.catatan_item,
                extras: []
            });
        }
    });

    itemsCards.forEach(function (c) {
        listHtml += `<div style="background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6; padding: 18px; border-radius: 14px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: flex; flex-direction: column; margin-bottom: 16px;">`;

        listHtml += `  <div style="margin-bottom: 12px;">
                               <span style="background: #f1f5f9; color: #475569; padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid #e2e8f0;">
                                   ${c.kategori}
                               </span>
                           </div>`;

        let detailsArr = [];
        if (c.merek && c.merek !== '-') detailsArr.push(c.merek);
        if (c.ukuran && c.ukuran !== '-') detailsArr.push('Size ' + c.ukuran);
        if (c.warna && c.warna !== '-') detailsArr.push(c.warna);
        let detailStr = detailsArr.length > 0 ? detailsArr.join(' | ') : '';

        let extrasHtml = '';
        if (c.extras.length > 0) {
            extrasHtml += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0; display: flex; flex-direction: column; gap: 4px;">`;
            c.extras.forEach(ext => {
                extrasHtml += `
                         <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                              <span style="color: #0284c7; font-weight: 700;">↳ Extra:</span>
                              <span style="color: #0369a1; font-weight: 800; text-align: right;">${ext.layanan}</span>
                         </div>
                    `;
            });
            extrasHtml += `</div>`;
        }

        listHtml += `  <div style="flex: 1;">
                               <div style="color: #0f172a; font-weight: 800; font-size: 1rem; margin-bottom: 2px; display: flex; justify-content: space-between;">
                                    <span>${c.layanan}</span>
                                    <span style="color: #3b82f6; background: #eff6ff; padding: 2px 8px; border-radius: 6px; font-size: 0.85rem;">x${c.qty}</span>
                               </div>
                               ${detailStr ? `<div style="color: #64748b; font-weight: 600; font-size: 0.85rem; margin-bottom: 4px;">${detailStr}</div>` : ''}
                               ${extrasHtml}
                           </div>`;

        // Kumpulkan catatan jika ada
        if (c.catatan && c.catatan.trim() !== '') {
            semuaCatatan.push(c.catatan.trim());
        }

        listHtml += `</div>`;
    });

    if (data.items.length === 0) {
        listHtml = `<div style="grid-column: 1 / -1; padding: 24px; text-align: center; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; font-weight: 600;">Tidak ada rincian item tercatat</div>`;
    }

    document.getElementById('d_layanan_list').innerHTML = listHtml;

    // Logika Tampilkan Catatan Tambahan
    let catatanArea = document.getElementById('d_catatan_area');
    if (semuaCatatan.length > 0) {
        // Hilangkan duplikat jika catatan sama ditulis berulang-ulang
        let unikCatatan = [...new Set(semuaCatatan)];
        document.getElementById('d_catatan').innerHTML = unikCatatan.join('<br><br>');
        catatanArea.style.display = 'block';
    } else {
        catatanArea.style.display = 'none';
    }

    document.getElementById('d_status').innerText = (data.status_label ? data.status_label.toUpperCase() : data.status.toUpperCase());
    document.getElementById('d_metode').innerText = data.metode_str;
    document.getElementById('d_total').innerText = data.harga;

    let stBayarEl = document.getElementById('d_status_bayar');
    if (data.status === 'batal') {
        stBayarEl.innerHTML = 'BATAL';
        stBayarEl.style.color = '#ef4444';
        stBayarEl.style.background = '#fef2f2';
        stBayarEl.style.borderColor = '#fecaca';
    } else if (data.status_bayar === 'pending') {
        stBayarEl.innerHTML = 'BELUM LUNAS';
        stBayarEl.style.color = '#dc2626';
        stBayarEl.style.background = '#fee2e2';
        stBayarEl.style.borderColor = '#fca5a5';
    } else {
        stBayarEl.innerHTML = 'LUNAS';
        stBayarEl.style.color = '#15803d';
        stBayarEl.style.background = '#dcfce7';
        stBayarEl.style.borderColor = '#bbf7d0';
    }

    let alasanGroup = document.getElementById('d_alasan_batal_group');
    let alasanEl = document.getElementById('d_alasan_batal');
    if (alasanGroup && alasanEl) {
        if (data.status_bayar === 'batal' && data.catatan_pembatalan) {
            alasanEl.innerText = data.catatan_pembatalan;
            alasanGroup.style.display = 'block';
        } else {
            alasanGroup.style.display = 'none';
            alasanEl.innerText = '';
        }
    }

    document.getElementById('modalDetail').classList.add('show');
}

function tutupModal(id) { document.getElementById(id).classList.remove('show'); }
window.onclick = function (e) { if (e.target.classList.contains('modal-overlay')) e.target.classList.remove('show'); }

/* === admin-pembayaran.js === */

function bukaModalKonfirmasi(btn) {
    const id = btn.getAttribute('data-id');

    document.getElementById('k-kode').innerText = 'KODE: ' + btn.getAttribute('data-kode');
    document.getElementById('k-nama').innerText = btn.getAttribute('data-nama');
    document.getElementById('k-metode').innerText = btn.getAttribute('data-metode');
    document.getElementById('k-total').innerText = btn.getAttribute('data-total');

    const ongkirVal = btn.getAttribute('data-ongkir');
    const ongkirBox = document.getElementById('k-ongkir-box');
    if (ongkirBox) {
        if (ongkirVal && ongkirVal !== 'hide') {
            document.getElementById('k-ongkir').innerText = ongkirVal;
            ongkirBox.style.display = 'flex';
        } else {
            ongkirBox.style.display = 'none';
        }
    }

    // Setting Action Database
    let targetStatus = document.getElementById('k-target-status');
    if (targetStatus) {
        targetStatus.value = btn.getAttribute('data-target-status');
    }

    // Setting Teks Tombol Berdasarkan Tunai/Transfer
    const btnSubmit = document.getElementById('btn-modal-submit');
    btnSubmit.innerText = btn.getAttribute('data-pesan-modal');

    // Ubah warna tombol kalau itu tombol "Tandai Lunas"
    if (btn.getAttribute('data-pesan-modal').includes('Uang Diterima') || btn.getAttribute('data-pesan-modal').includes('Tandai Pembayaran Lunas')) {
        btnSubmit.style.background = '#3b82f6';
    } else {
        btnSubmit.style.background = '#10b981';
    }

    // Tarik HTML Rincian dari template hidden
    document.getElementById('k-layanan').innerHTML = document.getElementById('detail-' + id).innerHTML;
    document.getElementById('k-id').value = id;

    document.getElementById('modalKonfirmasi').style.display = 'flex';
}

function bukaModalBatal(id, kode, nama, metode, total) {
    document.getElementById('b-kode').innerText = 'KODE: ' + kode;
    document.getElementById('b-id').value = id;

    document.getElementById('modalBatal').style.display = 'flex';
}

function tutupModalKonfirmasi() { document.getElementById('modalKonfirmasi').style.display = 'none'; }
function tutupModalBatal() { document.getElementById('modalBatal').style.display = 'none'; }
function lihatBukti(urlGambar) {
    document.getElementById('gambar-bukti').src = urlGambar;
    document.getElementById('modalBukti').style.display = 'flex';
}
function tutupModalBukti() { document.getElementById('modalBukti').style.display = 'none'; }

window.onclick = function (event) {
    if (event.target.className == 'modal-overlay') {
        tutupModalKonfirmasi();
        tutupModalBatal();
        tutupModalBukti();
    }
}

/* === admin-pesanan-edit.js === */

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((row, index) => {
        const title = row.querySelector('.item-title');
        if (title) title.textContent = `Item #${index + 1}`;
    });
}

function tambahItem() {
    const container = document.getElementById('dynamicItemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';

    row.innerHTML = `
        <div class="item-header">
            <h4 class="item-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                Item #X
            </h4>
            <button type="button" class="btn-hapus-item" onclick="hapusItem(this)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                Hapus Item
            </button>
        </div>
        <div class="form-grid">
            <div class="form-group fg-kategori">
                <label class="form-label">Kategori <span>*</span></label>
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
                        <label class="form-label">Merek <span>*</span></label>
                        <input type="text" name="merk_item[]" class="form-control merk-input" required placeholder="cth: Nike" style="width: 100%;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Ukuran <span>*</span></label>
                        <input type="text" name="ukuran[]" class="form-control ukuran-input" required placeholder="cth: 42" style="width: 100%;">
                    </div>
                </div>
            </div>
            <div class="form-group fg-layanan">
                <label class="form-label">Layanan <span>*</span></label>
                <select name="layanan_id[]" class="form-control lay-select" required onchange="updatePrice(); checkExtraTreatmentPrompt(this.closest('.item-row'));">
                    <option value="">-- Pilih Kategori Dulu --</option>
                </select>
            </div>
            <div class="form-group fg-jumlah-warna">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label jum-label">Jumlah <span>*</span></label>
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

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-row');
    items.forEach((row, index) => {
        const title = row.querySelector('.item-title');
        if (title) {
            title.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                Item #` + (index + 1) + `
            `;
        }
    });
}

function updateLayananDynamic(selEl) {
    const kategoriPilihan = selEl.value;
    const row = selEl.closest('.item-row');
    const layananSelect = row.querySelector('.lay-select');

    layananSelect.innerHTML = '<option value="">-- Pilih Layanan --</option>';

    if (kategoriPilihan === "") {
        updatePrice();
        return;
    }

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

function toggleAlamat(selectEl) {
    const group = document.getElementById('alamatGroup');
    const input = document.getElementById('inputAlamat');
    const inputWaktu = document.getElementById('inputWaktuJemput');

    if (selectEl.value && selectEl.value != "1") {
        group.style.display = 'grid';
        input.required = true;
        if (inputWaktu) inputWaktu.required = true;
    } else {
        group.style.display = 'none';
        input.required = false;
        input.value = '';
        if (inputWaktu) { inputWaktu.required = false; inputWaktu.value = ''; }
    }
}

function updatePrice() {
    let totalLayanan = 0;
    const layananSelects = document.querySelectorAll('.lay-select');
    const jumlahInputs = document.querySelectorAll('.jum-input');

    layananSelects.forEach(function (sel, index) {
        if (sel.selectedIndex > 0) {
            const opt = sel.options[sel.selectedIndex];
            const jml = parseInt(jumlahInputs[index].value) || 1;
            if (opt && opt.getAttribute('data-price')) {
                totalLayanan += parseInt(opt.getAttribute('data-price')) * jml;
            }
        }

        const row = sel.closest('.item-row');
        if (row) {
            const katSel = row.querySelector('.kat-select');
            const ukuranInput = row.querySelector('.ukuran-input');
            if (katSel && ukuranInput) {
                const kat = katSel.value;
                const layananText = sel.selectedIndex > 0 ? sel.options[sel.selectedIndex].textContent : "";
                const noSizeKategori = ['Bag', 'Wallet', 'Sandals', 'Hat'];
                const hideSize = noSizeKategori.includes(kat) || (kat === 'Repaint' && layananText.includes('Hat'));

                const ukuranContainer = ukuranInput.closest('.form-group');
                if (hideSize) {
                    if (ukuranContainer) ukuranContainer.style.display = 'none';
                    ukuranInput.required = false;
                    ukuranInput.value = '';
                } else {
                    if (ukuranContainer) ukuranContainer.style.display = '';
                    ukuranInput.required = true;
                }

                const jumLabel = row.querySelector('.jum-label');
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
                totalLayanan += parseInt(etOpt.getAttribute('data-price')) * jml;
            }
        }
    });

    const priceEl = document.getElementById('priceEst');
    const ongkirEl = document.getElementById('ongkirText');
    let ongkir = 0;

    const selectPengiriman = document.querySelector('select[name="metode_pengiriman"]');
    if (selectPengiriman && selectPengiriman.selectedIndex > 0) {
        const optPengiriman = selectPengiriman.options[selectPengiriman.selectedIndex];
        
        if (optPengiriman.value === "2") {
            const manualOngkir = document.getElementById('inputOngkirManual');
            if (manualOngkir) {
                ongkir = parseInt(manualOngkir.value) || 0;
            }
        } else {
            ongkir = parseInt(optPengiriman.getAttribute('data-biaya')) || 0;
        }
    }

    const total = totalLayanan + ongkir;

    if (ongkirEl) {
        ongkirEl.textContent = 'Bebas Ongkir (Gratis)';
        ongkirEl.style.color = '#10b981';
    }

    if (total > 0) {
        priceEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
    } else {
        priceEl.textContent = 'Pilih layanan terlebih dahulu';
    }
}



/* === admin-reports.js === */

function initChart(urlAPI) {
    fetch(urlAPI)
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                const labelsBulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const dataPendapatan = res.monthly.map(item => item.revenue);

                const ctxPendapatan = document.getElementById('grafikPendapatan').getContext('2d');

                let gradient = ctxPendapatan.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
                gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

                new Chart(ctxPendapatan, {
                    type: 'line',
                    data: {
                        labels: labelsBulan,
                        datasets: [{
                            label: 'Pendapatan (Rp)',
                            data: dataPendapatan,
                            borderColor: '#3b82f6',
                            backgroundColor: gradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#3b82f6',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        let label = context.dataset.label || '';
                                        if (label) { label += ': '; }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { borderDash: [4, 4], color: '#e2e8f0' },
                                ticks: {
                                    callback: function (value) {
                                        if (value >= 1000000) return 'Rp ' + (value / 1000000) + ' Jt';
                                        if (value >= 1000) return 'Rp ' + (value / 1000) + ' Rb';
                                        return value;
                                    }
                                }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Error fetching data:', error));
}

/* === sidebar.php === */
function toggleAdminSidebar() {
    document.getElementById('adminSidebar').classList.toggle('show');
    document.getElementById('adminOverlay').classList.toggle('show');
    document.getElementById('adminHamburger').classList.toggle('active');
}
function closeAdminSidebar() {
    document.getElementById('adminSidebar').classList.remove('show');
    document.getElementById('adminOverlay').classList.remove('show');
    document.getElementById('adminHamburger').classList.remove('active');
}

