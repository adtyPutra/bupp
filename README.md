# BUP — Build Up Play
## Panduan Instalasi & Penggunaan

---

## 📁 Struktur Folder

```
bup/
├── index.php                  ← Halaman utama (landing page)
├── .htaccess                  ← Konfigurasi Apache
│
├── config/
│   ├── app.php                ← Konfigurasi umum & konstanta
│   └── database.php           ← Koneksi database (PDO)
│
├── includes/
│   ├── auth.php               ← Autentikasi admin (login/logout)
│   └── helpers.php            ← Fungsi pembantu (rupiah, upload, dll)
│
├── pages/
│   ├── order.php              ← Form pemesanan
│   ├── status.php             ← Cek status pesanan
│   ├── daftar-harga.php       ← Daftar harga layanan
│   ├── partials/
│   │   └── navbar.php         ← Komponen navbar (reusable)
│   └── admin/
│       ├── login.php          ← Login admin
│       ├── dashboard.php      ← Dashboard utama
│       ├── orders.php         ← Manajemen pesanan
│       ├── services.php       ← Manajemen layanan
│       ├── customers.php      ← Data pelanggan
│       ├── gallery.php        ← Manajemen galeri foto
│       ├── reports.php        ← Laporan & analitik
│       ├── settings.php       ← Pengaturan sistem
│       ├── logout.php         ← Logout
│       └── partials/
│           └── sidebar.php    ← Sidebar admin (reusable)
│
├── api/
│   ├── order.php              ← POST: Submit pesanan baru
│   ├── status.php             ← GET: Cek status pesanan
│   └── upload_bukti.php       ← POST: Upload bukti pembayaran
│
├── assets/
│   ├── css/
│   │   ├── main.css           ← Stylesheet utama
│   │   └── admin.css          ← Stylesheet admin panel
│   ├── js/
│   │   ├── main.js            ← JavaScript shared
│   │   ├── order.js           ← JavaScript form order
│   │   └── admin.js           ← JavaScript admin panel
│   └── uploads/               ← Folder upload (auto-dibuat)
│       ├── bukti_bayar/       ← Bukti transfer pelanggan
│       └── galeri/            ← Foto galeri
│
└── sql/
    └── bup_database.sql       ← Schema & data awal database
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

Salin seluruh folder `bup/` ke dalam direktori web server:
- **XAMPP/WAMP**: `C:/xampp/htdocs/bup/`
- **Laragon**: `C:/laragon/www/bup/`
- **Linux**: `/var/www/html/bup/`

---

### 3. Buat Database

1. Buka **phpMyAdmin** (biasanya di `http://localhost/phpmyadmin`)
2. Klik **"New"** → buat database baru bernama `bup_db`
3. Pilih **Collation**: `utf8mb4_unicode_ci`
4. Klik tab **SQL** atau **Import**
5. Import file `sql/bup_database.sql`

Atau jalankan via terminal:
```bash
mysql -u root -p < sql/bup_database.sql
```

---

### 4. Konfigurasi Database

Buka file `config/database.php` dan sesuaikan:

```php
define('DB_HOST', 'localhost');  // Host database
define('DB_NAME', 'bup_db');     // Nama database
define('DB_USER', 'root');       // Username MySQL kamu
define('DB_PASS', '');           // Password MySQL kamu
```

---

### 5. Konfigurasi URL Aplikasi

Buka `config/app.php` dan sesuaikan URL:

```php
define('APP_URL', 'http://localhost/bup'); // Sesuaikan!
```

---

### 6. Buat Folder Upload

Buat folder ini jika belum ada, dan pastikan dapat ditulis (writable):

```
bup/assets/uploads/
bup/assets/uploads/bukti_bayar/
bup/assets/uploads/galeri/
```

Di Linux/Mac:
```bash
mkdir -p assets/uploads/bukti_bayar assets/uploads/galeri
chmod -R 755 assets/uploads/
```

---

### 7. Akses Website

- **Website utama**: `http://localhost/bup/`
- **Form pesan**: `http://localhost/bup/pages/order.php`
- **Cek status**: `http://localhost/bup/pages/status.php`
- **Daftar harga**: `http://localhost/bup/pages/daftar-harga.php`
- **Admin login**: `http://localhost/bup/pages/admin/login.php`

---

### 8. Login Admin Pertama Kali

- **Username**: `admin`
- **Password**: `admin123`

> ⚠️ **WAJIB ganti password** setelah pertama login di menu **Pengaturan**!

---

## 🔧 Konfigurasi Lanjutan

### Ganti Nomor WhatsApp
Edit di **Admin → Pengaturan** atau langsung di tabel `pengaturan`:
```sql
UPDATE pengaturan SET nilai='628xxxxxxxxxx' WHERE kunci='wa_number';
```

### Ganti Info Rekening
Edit di **Admin → Pengaturan** (BCA, BRI, GoPay, DANA).

### Mode Production
Di `config/app.php`, ubah:
```php
define('APP_DEBUG', false); // Matikan di production!
```

---

## 🛡️ Tips Keamanan Production

1. **Ganti password admin** segera setelah instalasi
2. Hapus atau rename file `sql/bup_database.sql` setelah import
3. Set `APP_DEBUG = false` di production
4. Gunakan HTTPS (SSL certificate)
5. Pastikan folder `assets/uploads/` tidak bisa dieksekusi PHP:
   ```
   # Tambahkan ke uploads/.htaccess
   php_flag engine off
   ```

---

## ❓ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| Halaman blank | Cek error PHP, aktifkan `APP_DEBUG = true` sementara |
| Database error | Periksa kredensial di `config/database.php` |
| Upload gagal | Pastikan folder `assets/uploads/` ada dan writable |
| Admin tidak bisa login | Pastikan tabel `admin` ada, password bcrypt valid |
| CSS tidak muncul | Periksa `APP_URL` di `config/app.php` |

---

## 📞 Support

Hubungi kami via WhatsApp atau Instagram jika ada pertanyaan.

**BUP — Build Up Play**  
📍 Terentang Elok 2 No.11, Cakung, Jakarta Timur  
📸 @buil.dupplay
