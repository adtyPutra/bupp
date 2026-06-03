<?php
// ============================================================
// index.php — Halaman Utama BUP
// ============================================================

// Panggil file database saja. Pastikan path ini sesuai dengan lokasi file koneksimu
require_once __DIR__ . '/config/database.php';

// Ambil koneksi database
$db = db();

// Set manual array $setting sebagai pengganti tabel pengaturan yang tidak ada
$setting = [
    'nama_usaha' => 'BUP',
    'no_wa'      => '6281211811577'
];

$wa_num = preg_replace('/[^0-9]/', '', $setting['no_wa']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($setting['nama_usaha'] ?? 'BUP') ?> | Layanan Cuci Sepatu Premium</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Montserrat:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css?v=<?= time(); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div id="scrollBar"></div>

<nav class="navbar" id="navbar">
  <div class="container">
    <a href="index.php" class="logo-wrap">
      <img src="assets/img/logo_bup.png" alt="Logo Build Up Play">
    </a>
    <div class="nav-links" id="navLinks">
      <a href="#home">Beranda</a>
      <a href="#about">Tentang</a>
      <a href="#services">Layanan</a>
      <a href="#gallery">Galeri</a>
      <a href="#contact">Kontak</a>
      <a href="pages/status.php">Status Pesanan</a>
    </div>
    <div class="nav-right">
      <a href="pages/order.php" class="btn btn-dark">Pesan Sekarang</a>
    </div>
    <div class="hamburger" id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="mob-menu" id="mobMenu">
  <span class="mob-close" onclick="toggleMenu()">✕</span>
  <a href="#home" onclick="toggleMenu()">Beranda</a>
  <a href="#about" onclick="toggleMenu()">Tentang</a>
  <a href="#services" onclick="toggleMenu()">Layanan</a>
  <a href="#gallery" onclick="toggleMenu()">Galeri</a>
  <a href="#contact" onclick="toggleMenu()">Kontak</a>
  <a href="pages/status.php">Status Pesanan</a>
  <a href="pages/order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan Sekarang</a>
</div>

<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-blob"></div>
  <div class="container">
    <div class="hero-grid">
      
      <div class="hero-content">
        <h1 class="hero-title">Layanan Cuci<br>Sepatu <em>Premium</em><br>Terbaik</h1>
        <p class="hero-sub">Buat sepatu kamu kembali bersih, segar, dan seperti baru. Perawatan profesional untuk setiap jenis sepatu dari sneakers hingga formal shoes.</p>
        
        <div class="hero-actions">
          <a href="pages/order.php" class="btn btn-dark">Pesan Sekarang</a>
          <a href="pages/status.php" class="btn btn-outline">Cek Status</a>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-img-wrap">
          <img src="assets/img/sepatu.jpg" alt="Sepatu premium bersih">
        </div>
      </div>

    </div>
  </div>
</section>

<div class="marquee">
  <div class="mq-track">
    <?php $items = ['Sneakers Cleaning','Boots Cleaning', 'Bag Cleaning', 'Unyellowing Treatment','Repaint','Pick Up & Delivery','WALLET Cleaning'];
    $all = array_merge($items, $items);
    foreach ($all as $item): ?>
      <span class="mq-item"><span class="dot">●</span><?= htmlspecialchars($item) ?></span>
    <?php endforeach; ?>
  </div>
</div>

<section class="about" id="about">
  <div class="container">
    <div class="about-grid">

      <div class="about-visual fade-up">
        <div class="about-main-img">
          <video id="promoVideo" loop muted playsinline onclick="togglePlay()">
            <source src="assets/video/promo-bup.mp4" type="video/mp4">
          </video>
          <button class="play-btn" id="playBtn" onclick="togglePlay()">
            <!-- Icon Play SVG -->
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
          </button>
        </div>

       

        <div class="about-badge">
          <div class="n">1+</div>
          <div class="t">Tahun<br>Melayani</div>
        </div>
      </div>

      <div class="fade-up">
        <h2 class="sec-title">Tentang Kami</h2>
        
        <!-- Mengubah class sec-sub menjadi about-desc agar tidak memanjang -->
        <p class="about-desc">
          BUP (Build Up Play) adalah layanan profesional yang fokus pada perawatan sepatu 
          dengan hasil bersih maksimal. Usaha ini didirikan pada 1 Maret 2025 dengan tujuan 
          memberikan solusi perawatan sepatu yang praktis, aman, dan berkualitas. 
          Kami percaya setiap sepatu memiliki cerita, dan merawatnya dengan benar dapat membuatnya 
          tetap terlihat baru lebih lama.
        </p>

        <div class="highlights-grid">
          <div class="hl-card">
            <div class="hl-icon hl-blue">
              <!-- Icon Leaf SVG -->
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
            </div>
            <h4>Bahan Aman & Ramah</h4>
            <p>Eco-friendly, aman untuk semua jenis material sepatu</p>
          </div>
          <div class="hl-card">
            <div class="hl-icon hl-green">
              <!-- Icon Check SVG -->
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h4>Hasil Bersih Maksimal</h4>
            <p>Sepatu kembali bersih, rapi, dan terawat seperti baru</p>
          </div>
          <div class="hl-card">
            <div class="hl-icon hl-orange">
              <!-- Icon Bolt/Petir SVG -->
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            </div>
            <h4>Proses Cepat</h4>
            <p>Proses cepat tanpa mengurangi kualitas hasil</p>
          </div>
          <div class="hl-card">
            <div class="hl-icon hl-purple">
              <!-- Icon Profesional/Shield SVG -->
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </div>
            <h4>Tenaga Profesional</h4>
            <p>Dikerjakan oleh tenaga profesional berpengalaman</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>
<section class="services" id="services">
  <div class="container">
    <div class="svc-header fade-up">
      <h2 class="sec-title">Layanan Kami</h2>
      <p class="sec-sub">Perawatan sepatu profesional dengan hasil maksimal dan pengerjaan cepat.</p>
    </div>

    <div class="svc-grid">
  <?php
  $svc_display = [
    [
      'name' => 'Sneakers Cleaning', 
      'desc' => 'Pembersihan menyeluruh untuk sneakers kesayanganmu. Kami hilangkan noda membandel agar sepatu kembali bersih, wangi, dan nyaman dipakai beraktivitas.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)' 
    ],
    [
      'name' => 'Boots Cleaning', 
      'desc' => 'Perawatan ekstra untuk sepatu boots. Membersihkan kotoran berat sekaligus menjaga ketahanan material agar boots kamu tetap tangguh di segala medan.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)' 
    ],
    [
      'name' => 'Leather Care', 
      'desc' => 'Treatment khusus material kulit. Kami bersihkan dan berikan pelembap khusus agar sepatu kulitmu lentur, mengkilap, dan terhindar dari retak.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)' 
    ],
    [
      'name' => 'Unyellowing', 
      'desc' => 'Solusi ampuh untuk sol sepatu yang menguning. Proses pemutihan khusus untuk mengembalikan warna asli sol agar terlihat seperti baru dibeli.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)' 
    ],
    [
      'name' => 'Repaint Service', 
      'desc' => 'Warna sepatu pudar atau kusam? Layanan cat ulang kami siap mengembalikan kecerahan warna aslinya atau ubah warna custom sesuai gayamu.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)'
    ],
    [
      'name' => 'Pick Up & Delivery', 
      'desc' => 'Tidak perlu repot keluar rumah. Kurir kami siap menjemput sepatu kotormu dan mengantarkannya kembali dalam keadaan bersih dan rapi.', 
      'grad' => 'linear-gradient(135deg, #ffffff, #f0f0f0)'
    ],
  ];
  
  foreach ($svc_display as $s): ?>
    <div class="svc-card fade-up">
      <div class="svc-card-inner" style="background: <?= $s['grad']; ?>;">
        
        <h3 class="svc-name"><?= htmlspecialchars($s['name']); ?></h3>
        <p class="svc-desc"><?= htmlspecialchars($s['desc']); ?></p>
        
        <a href="pages/order.php" class="svc-link">Pesan Sekarang</a>
        
      </div>
    </div>
  <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="gallery" id="gallery">
  <div class="container">
    <div class="gal-top">
      <div class="fade-up">
        <h2 class="sec-title">Hasil Pengerjaan Kami</h2>
        <p class="sec-sub">Hasil nyata perawatan sepatu pelanggan kami.</p>
      </div>
      <div class="gal-btns fade-up">
        <button class="gal-btn" onclick="galPrev()"><i class="fas fa-chevron-left"></i></button>
        <button class="gal-btn" onclick="galNext()"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>
    
    <div class="gal-outer fade-up">
      <div class="gal-track" id="galTrack">
        
        <div class="gal-slide">
          <img src="assets/img/Sepatu1.png" alt="Gallery 1">
        </div>

        <div class="gal-slide">
          <img src="assets/img/sepatu2.png" alt="Gallery 2">
        </div>

        <div class="gal-slide">
          <img src="assets/img/sepatu3.png" alt="Gallery 3">
        </div>

        <div class="gal-slide">
          <img src="assets/img/sepatu4.png" alt="Gallery 4">
        </div>

        <div class="gal-slide">
          <img src="assets/img/sepatu5.png" alt="Gallery 5">
        </div>

        <div class="gal-slide">
          <img src="assets/img/sepatu6.png" alt="Gallery 6">
        </div>

      </div>
    </div>
  </div>
</section>

<section class="process" id="process">
  <div class="container">
    <div class="proc-header fade-up">
      <h2 class="sec-title">Proses 4 Langkah</h2>
      <p class="sec-sub">Mendapatkan sepatu yang bersih tidak pernah semudah ini.</p>
    </div>
    <div class="proc-grid">
      <?php 
      $steps = [
        ['01','fas fa-box','Antar / Jemput','Antarkan sepatu ke toko kami atau jadwalkan layanan penjemputan.'],
        ['02','fas fa-search','Pemeriksaan','Tim kami melakukan pemeriksaan kondisi sepatu untuk menentukan penanganan yang tepat.'],
        ['03','fas fa-broom','Proses Cleaning','Sepatu dibersihkan oleh tenaga ahli menggunakan bahan profesional yang aman.'],
        ['04','fas fa-truck','Siap Digunakan','Sepatu siap diambil atau diantar kembali dalam kondisi bersih dan rapi.']
      ];

      foreach($steps as [$num,$ico,$name,$desc]): ?>
      <div class="proc-step fade-up">
        <div class="proc-num"><?= $num ?></div>
        <div class="proc-ico"><i class="<?= $ico ?>"></i></div>
        <div class="proc-name"><?= $name ?></div>
        <p class="proc-desc"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="contact" id="contact">
  <div class="container">
    <div class="contact-grid">

      <div>
        <h2 class="sec-title">Hubungi Kami</h2>
        <div class="ci-items">
          
          <div class="ci-item">
            <div class="ci-ico"><i class="fas fa-map-marker-alt"></i></div>
            <div>
              <h4>Alamat</h4>
              <p>Terentang Elok 2 No.11 Blok G5, RT.11/RW.9, Penggilingan, Kec. Cakung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13940</p>
            </div>
          </div>
          
          <div class="ci-item">
            <div class="ci-ico"><i class="fab fa-whatsapp"></i></div>
            <div>
              <h4>WhatsApp</h4>
              <p><a href="https://wa.me/6281211811577" target="_blank" style="color: inherit; text-decoration: none;">+62 812-1181-1577</a></p>
            </div>
          </div>
          
          <div class="ci-item">
            <div class="ci-ico"><i class="fas fa-clock"></i></div>
            <div>
              <h4>Jam Operasional</h4>
              <p>Buka Setiap Hari: 09.00 - 20.00</p>
            </div>
          </div>
          
        </div>
      </div>

      <div class="map-wrap fade-up">
      <iframe 
  src="https://maps.google.com/maps?q=Build+up+play+cleaning+shoes,+Terentang+Elok+2+Blok+G5,+Penggilingan,+Cakung&t=&z=16&ie=UTF8&iwloc=&output=embed"
  width="100%" 
  height="400" 
  style="border:0;"
  allowfullscreen 
  loading="lazy" 
  title="Lokasi Toko BUP">
</iframe>
      </div>

    </div>
  </div>
</section>

<div class="cta">
  <div class="container">
    <h2>Siap Membuat Sepatu Kamu Seperti Baru?</h2>
    <p>Pesan sekarang dan rasakan layanan cuci sepatu berkualitas cepat, aman, dan profesional</p>
    <div class="actions">
      <a href="pages/order.php" class="btn btn-blue">Pesan Sekarang</a>
      <a href="pages/status.php" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.2)">Cek Status Pesanan</a>
    </div>
  </div>
</div>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <a href="index.php" class="logo-wrap footer-logo-wrap" style="margin-bottom: 15px;">
          <img src="assets/img/logo_bup.png" alt="Logo Build Up Play" style="height: 40px; filter: brightness(0) invert(1);"> 
        </a>
        <p class="footer-desc">Jasa cuci sepatu berkualitas di Jakarta Timur.</p>
        <div class="socials">
          <a href="https://wa.me/6281211811577" target="_blank" class="soc-btn" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Navigasi</h5>
        <div class="footer-links">
          <a href="#home">Beranda</a>
          <a href="#about">Tentang</a>
          <a href="#services">Layanan</a>
          <a href="#gallery">Galeri</a>
          <a href="#contact">Kontak</a>
          <a href="pages/status.php">Status Pesanan</a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Layanan</h5>
        <div class="footer-links">
          <a href="#services">Sneakers Cleaning</a>
          <a href="#services">Boots Cleaning</a>
          <a href="#services">Bag Cleaning</a>
          <a href="#services">Unyellowing Treatment</a>
          <a href="#services">Repaint</a>
          <a href="#services">Wallet Cleaning</a>
          <a href="#services">Pick Up & Delivery</a>
        </div>
      </div>
      <div class="footer-col">
        <h5>Kontak</h5>
        
        <div class="fci">
          <span><i class="fas fa-map-marker-alt"></i></span>
          <p>Terentang Elok 2 No.11 Blok G5, RT.11/RW.9, Penggilingan, Kec. Cakung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13940</p>
        </div>
        
        <div class="fci">
          <span><i class="fab fa-whatsapp"></i></span>
          <p><a href="https://wa.me/6281211811577" target="_blank" style="color: inherit; text-decoration: none;">+62 812-1181-1577</a></p>
        </div>
        
        <div class="fci">
          <span><i class="fas fa-clock"></i></span>
          <p>Buka: 09.00 - 20.00</p>
        </div>
      </div>
    </div>
    <div class="footer-bot">
      <p>© <?= date('Y') ?> BUP — Build Up Play. Hak cipta dilindungi.</p>
    </div>
  </div>
</footer>

<script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>