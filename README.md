# BUP — Build Up Play
## Aplikasi Manajemen Laundry Sepatu

---

## 📁 Struktur Folder

```
bupp/
├── index.php                      ← Halaman utama (landing page)
├── .htaccess                      ← Konfigurasi Apache
│
├── config/
│   ├── app.php                    ← Konfigurasi umum & konstanta
│   ├── database.php               ← Koneksi database PDO (buat dari database.example.php)
│   └── database.example.php       ← Template konfigurasi database
│
├── includes/
│   ├── auth.php                   ← Autentikasi admin (login/logout)
│   └── helpers.php                ← Fungsi pembantu (rupiah, upload, dll)
│
├── pages/
│   ├── order.php                  ← Form pemesanan (multi-item, peta, ongkir dinamis)
│   ├── status.php                 ← Cek status pesanan
│   ├── partials/
│   │   └── navbar.php             ← Komponen navbar (reusable)
│   └── admin/
│       ├── login.php              ← Login admin
│       ├── logout.php             ← Logout admin
│       ├── dashboard.php          ← Dashboard & statistik
│       ├── orders.php             ← Manajemen daftar pesanan
│       ├── pesanan_edit.php       ← Detail & edit pesanan
│       ├── pembayaran.php         ← Manajemen pembayaran & verifikasi bukti
│       ├── customers.php          ← Data pelanggan
│       ├── reports.php            ← Laporan & analitik
│       └── partials/
│           └── sidebar.php        ← Sidebar admin (reusable)
│
├── api/
│   ├── order.php                  ← POST: Submit pesanan baru / GET: cek status
│   ├── status.php                 ← GET: Cek status pesanan by kode
│   └── upload_bukti.php           ← POST: Upload bukti pembayaran
│
├── assets/
│   ├── css/
│   │   ├── main.css               ← Stylesheet halaman utama & landing
│   │   ├── order.css              ← Stylesheet halaman pemesanan
│   │   ├── status.css             ← Stylesheet halaman status pesanan
│   │   └── admin.css              ← Stylesheet admin panel
│   ├── js/
│   │   ├── main.js                ← JavaScript halaman utama
│   │   ├── order.js               ← JavaScript form order (maps, ongkir, multi-item)
│   │   ├── status.js              ← JavaScript halaman status
│   │   └── admin.js               ← JavaScript admin panel
│   ├── img/                       ← Gambar statis (logo, produk, layanan)
│   ├── video/
│   │   └── promo-bup.mp4          ← Video promosi homepage
│   └── uploads/
│       ├── bukti_bayar/           ← Upload bukti transfer pelanggan
│       └── galeri/                ← Foto galeri
│
├── uploads/
│   └── bukti_bayar/               ← Alternatif folder upload bukti bayar
│
└── sql/
    └── bup_db (7).sql             ← Schema & data awal database (versi terbaru)
```

---

## 🚀 Langkah Instalasi

### 1. Persyaratan Server
- PHP 8.0+ (dengan ekstensi: PDO, PDO_MySQL, GD, fileinfo)
- MySQL 5.7+ atau MariaDB 10.3+
- Apache dengan mod_rewrite aktif
- Web server lokal: XAMPP, Laragon, atau WAMP

---

### 2. Copy File ke Server

Salin seluruh folder `bupp/` ke dalam direktori web server:
- **XAMPP/WAMP**: `C:/xampp/htdocs/bupp/`
- **Laragon**: `C:/laragon/www/bupp/`
- **Linux**: `/var/www/html/bupp/`

---

### 3. Buat Database

1. Buka **phpMyAdmin** (biasanya di `http://localhost/phpmyadmin`)
2. Klik **"New"** → buat database baru bernama `bup_db`
3. Pilih **Collation**: `utf8mb4_unicode_ci`
4. Klik tab **Import**
5. Import file `sql/bup_db (7).sql` (versi terbaru)

Atau jalankan via terminal:
```bash
mysql -u root -p bup_db < "sql/bup_db (7).sql"
```

---

### 4. Konfigurasi Database

Salin file template lalu sesuaikan:
```bash
# Windows
copy config\database.example.php config\database.php

# Linux/Mac
cp config/database.example.php config/database.php
```

Buka `config/database.php` dan sesuaikan:
```php
define('DB_HOST', 'localhost');  // Host database
define('DB_NAME', 'bup_db');     // Nama database
define('DB_USER', 'root');       // Username MySQL kamu
define('DB_PASS', '');           // Password MySQL kamu (kosong jika XAMPP default)
```

---

### 5. Konfigurasi URL Aplikasi

Buka `config/app.php` dan pastikan BASE_URL sesuai:
```php
define('BASE_URL', '/bupp');  // Sesuaikan dengan nama folder di htdocs
```

---

### 6. Folder Upload

Pastikan folder-folder berikut **ada dan bisa ditulis (writable)**:

```
bupp/assets/uploads/bukti_bayar/
bupp/assets/uploads/galeri/
bupp/uploads/bukti_bayar/
```

Di Linux/Mac:
```bash
chmod -R 755 assets/uploads/
chmod -R 755 uploads/
```

---

### 7. Akses Website

- **Website utama**: `http://localhost/bupp/`
- **Form pesan**: `http://localhost/bupp/pages/order.php`
- **Cek status**: `http://localhost/bupp/pages/status.php`
- **Admin login**: `http://localhost/bupp/pages/admin/login.php`

---

### 8. Login Admin

- **Username**: `admin`
- **Password**: `admin123`

> ⚠️ **WAJIB ganti password** setelah pertama kali login!

---

## 🗺️ Fitur Google Maps

Halaman pemesanan menggunakan **Google Maps API** untuk:
- Menampilkan peta interaktif dengan pin yang bisa digeser
- Autocomplete pencarian alamat
- Kalkulasi jarak & ongkos kirim dinamis via OSRM

Konfigurasi API Key ada di `pages/order.php`:
```html
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places&callback=initGoogleMaps">
```

> Ganti `YOUR_API_KEY` dengan Google Maps API Key milik kamu jika deploy ke server baru.

---

## 💰 Sistem Ongkos Kirim Dinamis

| Jarak dari Toko | Ongkos Kirim |
|---|---|
| ≤ 4 km | Gratis (Rp 0) |
| 4 – 10 km | Rp 15.000 |
| 10 – 25 km | Rp 25.000 |
| > 25 km | Diinfokan via WhatsApp |

---

## 🛡️ Tips Keamanan Production

1. **Jangan push** `config/database.php` ke repository (sudah di-ignore oleh `.gitignore`)
2. **Ganti password admin** segera setelah instalasi
3. Hapus atau batasi akses ke folder `sql/` setelah import
4. Gunakan HTTPS (SSL certificate)
5. Pastikan folder `uploads/` tidak bisa mengeksekusi PHP:
   ```
   # Tambahkan ke uploads/.htaccess
   php_flag engine off
   ```

---

## ❓ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Halaman blank | Cek PHP error log, aktifkan `display_errors` sementara |
| Database error | Periksa kredensial di `config/database.php` |
| Upload gagal | Pastikan folder `uploads/` ada dan writable |
| Admin tidak bisa login | Pastikan tabel `admin` ada dan password bcrypt valid |
| CSS/JS tidak muncul | Periksa `BASE_URL` di `config/app.php` |
| Maps tidak muncul | Cek Google Maps API Key, pastikan billing aktif |
| Maps blank setelah refresh | Ini known issue — map lazy-init, coba pilih ulang metode pengiriman |

---

## 📞 Kontak

**BUP — Build Up Play**
Jasa Laundry Sepatu

📍 Terentang Elok 2 No.11, Cakung, Jakarta Timur
📱 WhatsApp: 0812-1181-1577
📸 Instagram: @buil.dupplay
