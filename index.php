<?php
// ============================================================
// index.php — Halaman Utama BUP
// ============================================================
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/customer_auth.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/customer/home.php');
    exit;
}

$db = db();


$setting = [
    'nama_usaha' => 'BUP',
    'no_wa'      => '6281211811577'
];

$wa_num       = preg_replace('/[^0-9]/', '', $setting['no_wa']);
$customer     = getLoggedInCustomer();
$isLoggedIn   = isCustomerLoggedIn();
$namaInisial  = $isLoggedIn ? strtoupper(substr($customer['nama'], 0, 2)) : '';
$namaDepan    = $isLoggedIn ? htmlspecialchars(explode(' ', $customer['nama'])[0]) : '';
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
<link rel="stylesheet" href="assets/css/customer.css?v=<?= time(); ?>">
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
      <?php if ($isLoggedIn): ?>
        <a href="#services">Layanan</a>
        <a href="pages/customer/my-orders.php">Riwayat Pesanan</a>
      <?php else: ?>
        <a href="#about">Tentang</a>
        <a href="#services">Layanan</a>
        <a href="#gallery">Galeri</a>
        <a href="#contact">Kontak</a>
        <a href="pages/status.php">Status Pesanan</a>
      <?php endif; ?>
    </div>
    <div class="nav-right">
      <?php if ($isLoggedIn): ?>
        <div class="nav-dropdown-wrap" id="userDropWrap">
          <button class="nav-user-btn" onclick="toggleUserDrop()">
            <span class="avatar"><?= $namaInisial ?></span>
            <span><?= $namaDepan ?></span>
            <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
          </button>
          <div class="nav-dropdown" id="userDropdown">
            <div class="nav-dropdown-user">
              <div class="dd-name"><?= htmlspecialchars($customer['nama']) ?></div>
              <div class="dd-email">@<?= htmlspecialchars($customer['username']) ?></div>
            </div>

            <a href="pages/customer/my-orders.php">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              Riwayat Pesanan
            </a>
            <a href="pages/customer/profile.php">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              Profil Saya
            </a>
            <div class="dd-divider"></div>
            <button class="dd-logout" onclick="window.location.href='api/customer_auth.php?action=logout'">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
              Logout
            </button>
          </div>
        </div>
      <?php else: ?>
        <a href="pages/login.php" class="btn btn-outline" style="border: 1px solid #1f2937; color: #1f2937; font-weight: 600; padding: 8px 18px; border-radius: 50px; text-decoration: none;">Login</a>
        <a href="pages/login.php?mode=register" class="btn btn-dark" style="padding: 8px 18px; border-radius: 50px; background-color: #1f2937; color: white; border: none; font-weight: 600; text-decoration: none;">Daftar</a>
      <?php endif; ?>
    </div>
    <div class="hamburger" id="hamburger" onclick="toggleMenu()"><span></span><span></span><span></span></div>
  </div>
</nav>

<div class="mob-menu" id="mobMenu">
  <span class="mob-close" onclick="toggleMenu()">✕</span>
  <?php if ($isLoggedIn): ?>
    <a href="pages/customer/home.php" onclick="toggleMenu()">Beranda</a>
    <a href="#services" onclick="toggleMenu()">Layanan</a>
    <a href="pages/customer/my-orders.php" onclick="toggleMenu()">Riwayat Pesanan</a>
    <a href="pages/customer/profile.php" onclick="toggleMenu()">Profil Saya</a>
    <a href="api/customer_auth.php?action=logout" style="color:#ef4444;">Logout</a>
    <a href="pages/order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan Sekarang</a>
  <?php else: ?>
    <a href="#home" onclick="toggleMenu()">Beranda</a>
    <a href="#about" onclick="toggleMenu()">Tentang</a>
    <a href="#services" onclick="toggleMenu()">Layanan</a>
    <a href="#gallery" onclick="toggleMenu()">Galeri</a>
    <a href="#contact" onclick="toggleMenu()">Kontak</a>
    <a href="pages/status.php" onclick="toggleMenu()">Status Pesanan</a>
    <a href="pages/login.php" class="btn btn-outline" style="margin-top:14px;justify-content:center; border: 1px solid #1f2937; color: #1f2937; border-radius: 50px;">Login</a>
    <a href="pages/login.php?mode=register" class="btn btn-dark" style="margin-top:8px;justify-content:center; border-radius: 50px;">Daftar</a>
  <?php endif; ?>
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
          <a href="pages/login.php" class="btn btn-dark">Pesan Sekarang</a>
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
    <?php $items = ['Sneakers Cleaning','Boots Cleaning', 'Unyellowing Treatment','Repaint','Pick Up & Delivery'];
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
    <div class="gal-top fade-up" style="margin-bottom: 24px;">
      <div>
        <h2 class="sec-title">Layanan Kami</h2>
        <p class="sec-sub">Perawatan sepatu profesional dengan hasil maksimal dan pengerjaan cepat.</p>
      </div>
      <div class="gal-btns">
        <button class="gal-btn" onclick="document.getElementById('svcScroll').scrollBy({left: -300, behavior: 'smooth'})"><i class="fas fa-chevron-left"></i></button>
        <button class="gal-btn" onclick="document.getElementById('svcScroll').scrollBy({left: 300, behavior: 'smooth'})"><i class="fas fa-chevron-right"></i></button>
      </div>
    </div>

    <div class="svc-grid">
  <?php
  require_once __DIR__ . '/includes/service_helper.php';
  $stmt = $db->query("SELECT * FROM layanan WHERE aktif = 1 ORDER BY kategori ASC, harga ASC");
  $layananList = $stmt->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
  <div id="svcScroll" class="hide-scrollbar" style="display: flex; overflow-x: auto; scroll-snap-type: x mandatory; gap: 20px; padding-bottom: 24px; scroll-behavior: smooth;">
    <?php 
      $excluded = ['bag', 'wallet', 'sandals', 'hat'];
      foreach ($layananList as $s): 
        $catStr = strtolower(trim($s['kategori']));
        if (in_array($catStr, $excluded)) continue;
        if ($catStr === 'repaint' && (stripos($s['jenis'], 'hat') !== false || stripos($s['jenis'], 'topi') !== false)) continue;
        $details = getDeskripsi($s['jenis'], $s['kategori']);
    ?>
      <div class="card fade-up" style="flex: 0 0 280px; scroll-snap-align: start;">
        <div class="card-img" style="background-image: url('<?= $details['img']; ?>');">
        </div>
        <div class="card-body">
          <div class="card-cat"><?= htmlspecialchars(strtoupper($s['kategori'])); ?></div>
          <h4 class="card-title"><?= htmlspecialchars($s['jenis']); ?></h4>
          <p class="card-desc"><?= htmlspecialchars($details['desc']); ?></p>
          <div class="card-footer">
              <?php if ($isLoggedIn): ?>
                  <a href="pages/order.php?layanan_id=<?= $s['id'] ?>" class="card-btn">Pesan Sekarang</a>
              <?php else: ?>
                  <a href="pages/login.php" class="card-btn">Pesan Sekarang</a>
              <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
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
      <a href="pages/login.php" class="btn btn-blue">Pesan Sekarang</a>
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
          <a href="#services">Unyellowing Treatment</a>
          <a href="#services">Repaint</a>
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
<script>
function toggleUserDrop() {
    const wrap = document.getElementById('userDropWrap');
    const dd   = document.getElementById('userDropdown');
    if (!wrap) return;
    wrap.classList.toggle('open');
    dd.classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('userDropWrap');
    if (wrap && !wrap.contains(e.target)) {
        wrap.classList.remove('open');
        const dd = document.getElementById('userDropdown');
        if (dd) dd.classList.remove('show');
    }
});
</script>
</body>
</html>