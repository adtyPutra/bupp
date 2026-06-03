<?php
$curr = basename($_SERVER['PHP_SELF'], '.php');
$nav = [
  ['dashboard','Dashboard'], ['orders','Pesanan'], ['pembayaran','Pembayaran'],  ['customers','Pelanggan'], 
  ['reports','Laporan']
];
?>

<!-- Mobile Topbar — Visible Only on Mobile -->
<div class="admin-mobile-topbar" id="adminMobileTopbar">
    <button class="admin-hamburger" id="adminHamburger" onclick="toggleAdminSidebar()" aria-label="Buka Menu">
        <span></span><span></span><span></span>
    </button>
    <span class="admin-mobile-title">Admin Panel</span>
</div>

<!-- Dark Overlay — Mobile Only -->
<div class="admin-overlay" id="adminOverlay" onclick="closeAdminSidebar()"></div>

<div class="admin-sidebar" id="adminSidebar">
  <!-- Close Button — Mobile Only, inside sidebar -->
  <button class="admin-sidebar-close" id="adminSidebarClose" onclick="closeAdminSidebar()" aria-label="Tutup Menu">&times;</button>

  <div class="admin-logo">
    <img src="../../assets/img/logo_bup.png" alt="Admin BUP" style="max-width: 110px; height: auto; display: block; filter: brightness(0) invert(1);">
  </div>

  <nav class="admin-nav">
    <?php foreach ($nav as [$p, $l]): ?>
      <a href="<?= $p ?>.php" class="admin-nav-item <?= $curr === $p ? 'active' : '' ?>" onclick="closeAdminSidebar()"><?= $l ?></a>
    <?php endforeach; ?>

    <hr style="border:none; height:1px; background:rgba(255,255,255,.08); margin:14px 0">
    <a href="logout.php" class="admin-nav-item" style="color:#f87171" onclick="closeAdminSidebar()">Logout</a>
  </nav>
</div>
