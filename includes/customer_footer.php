<?php
// includes/customer_footer.php
?>
<footer class="footer" style="margin-top: auto; padding: 50px 0 30px 0;">
  <div class="container">
    <div class="footer-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
      
      <!-- Kolom 1: Logo & Deskripsi -->
      <div>
        <a href="<?= BASE_URL ?>/index.php" class="logo-wrap footer-logo-wrap" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; text-decoration: none;">
          <img src="<?= BASE_URL ?>/assets/img/logo_bup.png" alt="Logo Build Up Play" style="height: 36px; filter: brightness(0) invert(1);"> 
          <span style="color: #ffffff; font-size: 1.3rem; font-weight: 800; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.5px;">BUP Laundry</span>
        </a>
        <p class="footer-desc" style="font-size: 0.95rem; line-height: 1.6; color: #cbd5e1; margin-bottom: 15px; font-family: 'Plus Jakarta Sans', sans-serif;">Jasa perawatan sepatu premium yang praktis dan higienis.</p>
        <div class="socials">
          <a href="https://wa.me/6281211811577" target="_blank" class="soc-btn" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
          </a>
        </div>
      </div>

      <!-- Kolom 2: Navigasi Mini -->
      <div class="footer-col">
        <h5 style="color: #ffffff; font-size: 0.95rem; margin-bottom: 15px; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: 1px;">NAVIGASI</h5>
        <div class="footer-links" style="display: flex; flex-direction: column; gap: 10px;">
          <a href="<?= BASE_URL ?>/index.php" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: color 0.3s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">Beranda</a>
          <a href="<?= BASE_URL ?>/pages/layanan.php" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: color 0.3s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">Layanan</a>
          <a href="<?= BASE_URL ?>/pages/status.php" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: color 0.3s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='#94a3b8'">Status Pesanan</a>
        </div>
      </div>

      <!-- Kolom 3: Kontak Mini -->
      <div class="footer-col">
        <h5 style="color: #ffffff; font-size: 0.95rem; margin-bottom: 15px; font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: 1px;">HUBUNGI KAMI</h5>
        <div class="fci" style="display: flex; gap: 12px; margin-bottom: 12px; color: #cbd5e1; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif;">
          <span><i class="fas fa-map-marker-alt"></i></span>
          <p style="margin: 0; line-height: 1.5;">Terentang Elok 2 Blok G5, Cakung, Jakarta Timur 13940</p>
        </div>
        <div class="fci" style="display: flex; gap: 12px; color: #cbd5e1; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif;">
          <span><i class="fab fa-whatsapp"></i></span>
          <p style="margin: 0;"><a href="https://wa.me/6281211811577" target="_blank" style="color: inherit; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='inherit'">+62 812-1181-1577</a></p>
        </div>
      </div>

    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bot" style="margin-top: 40px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center;">
      <p style="margin: 0; color: #64748b; font-size: 0.85rem; font-family: 'Plus Jakarta Sans', sans-serif;">© <?= date('Y') ?> BUP Laundry Sepatu. Seluruh Hak Cipta Dilindungi.</p>
    </div>
  </div>
</footer>

<!-- Subtle Background Accents -->
<div style="position: fixed; bottom: -20%; right: -10%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(59,130,246,0.04) 0%, transparent 60%); border-radius: 50%; z-index: -2; pointer-events: none;"></div>
<div style="position: fixed; top: -10%; left: -10%; width: 500px; height: 500px; background: radial-gradient(circle, rgba(16,185,129,0.03) 0%, transparent 60%); border-radius: 50%; z-index: -2; pointer-events: none;"></div>

<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<?php if (!isset($isLoggedIn) || $isLoggedIn): ?>
<!-- Bottom Navigation (Mobile Only) -->
<nav class="bottom-nav">
    <div class="bottom-nav-inner">
        <a href="<?= BASE_URL ?>/pages/customer/home.php" class="<?= ($currentPage == 'home.php') ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/layanan.php" class="<?= ($currentPage == 'layanan.php') ? 'active' : '' ?>">
            <i class="fas fa-tshirt"></i>
            <span>Pesan</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/customer/my-orders.php" class="<?= ($currentPage == 'my-orders.php') ? 'active' : '' ?>">
            <i class="fas fa-receipt"></i>
            <span>Pesanan</span>
        </a>
        <a href="<?= BASE_URL ?>/pages/customer/profile.php" class="<?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </div>
</nav>
<?php endif; ?>
