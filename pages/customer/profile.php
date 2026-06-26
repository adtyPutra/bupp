<?php
// ============================================================
// pages/customer/profile.php — Edit Profil Pelanggan
// ============================================================
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/customer_auth.php';

requireCustomerLogin();
$customer    = getLoggedInCustomer();
$db          = db();
$namaInisial = strtoupper(substr($customer['nama'], 0, 2));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil Saya | BUP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="../../assets/css/customer.css?v=<?= time() ?>">
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="navbar">
  <div class="container">
    <a href="home.php" class="logo-wrap">
      <img src="../../assets/img/logo_bup.png" alt="Logo BUP">
    </a>
    <div class="nav-links" id="navLinks">
      <a href="home.php">Beranda</a>
      <a href="../layanan.php">Layanan</a>
      <a href="my-orders.php">Riwayat Pesanan</a>
    </div>
    <div class="nav-right" style="display:flex; align-items:center; gap:10px;">
      <a href="../order.php" class="btn btn-dark hide-mob" style="padding: 8px 18px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; text-decoration: none;">Pesan</a>
      <div class="nav-dropdown-wrap" id="userDropWrap">
        <button class="nav-user-btn" onclick="toggleDropdown()">
          <span class="avatar"><?= $namaInisial ?></span>
          <span><?= htmlspecialchars(explode(' ', $customer['nama'])[0]) ?></span>
          <svg class="chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="nav-dropdown" id="userDropdown">
          <div class="nav-dropdown-user">
              <div class="dd-name"><?= htmlspecialchars($customer['nama']) ?></div>
              <div class="dd-email">@<?= htmlspecialchars($customer['username']) ?></div>
          </div>

          <a href="profile.php" style="color:var(--blue);">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profil Saya
          </a>
          <div class="dd-divider"></div>
          <button class="dd-logout" onclick="window.location.href='../../api/customer_auth.php?action=logout'">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Logout
          </button>
        </div>
      </div>
    </div>
    <div class="hamburger" id="hamburger" onclick="toggleMobMenu()"><span></span><span></span><span></span></div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mob-menu" id="mobMenu">
  <span class="mob-close" onclick="toggleMobMenu()">✕</span>
  <a href="home.php" onclick="toggleMobMenu()">Beranda</a>
  <a href="../layanan.php" onclick="toggleMobMenu()">Layanan</a>
  <a href="my-orders.php" onclick="toggleMobMenu()">Riwayat Pesanan</a>
  <a href="profile.php" onclick="toggleMobMenu()">Profil Saya</a>
  <a href="../../api/customer_auth.php?action=logout" style="color:#ef4444;">Logout</a>
  <a href="../order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan Sekarang</a>
</div>

<div class="customer-page">
  <div class="customer-wrap-sm">

    <div class="customer-header">
      <h1>Profil Saya</h1>
      <p>Kelola data diri dan keamanan akun kamu</p>
    </div>

    <!-- Alert -->
    <div id="profileAlert" style="display:none; padding:12px 16px; border-radius:10px; font-size:0.85rem; font-weight:600; margin-bottom:20px;"></div>

    <!-- Data Diri -->
    <div class="profile-card">
      <div class="profile-card-header">
        <h3>Data Diri</h3>
      </div>
      <div class="profile-card-body">
        <form class="profile-form" id="formProfil" onsubmit="simpanProfil(event)">
          <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" class="form-input" id="pNama" value="<?= htmlspecialchars($customer['nama']) ?>" required>
          </div>
          <div class="form-group">
            <label>Nomor WhatsApp</label>
            <input type="tel" class="form-input" id="pWa" value="<?= htmlspecialchars($customer['no_wa']) ?>" required>
          </div>
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-input" value="<?= htmlspecialchars($customer['username']) ?>" disabled
                   style="background:#f8fafc; color:#94a3b8; cursor:not-allowed;">
            <small style="font-size:0.75rem; color:#94a3b8; margin-top:4px;">Username tidak dapat diubah</small>
          </div>
          <button type="submit" class="profile-submit" id="btnSimpan">Simpan Perubahan</button>
        </form>
      </div>
    </div>

    <!-- Ganti Password -->
    <div class="profile-card">
      <div class="profile-card-header">
        <h3>Ganti Password</h3>
      </div>
      <div class="profile-card-body">
        <form class="profile-form" id="formPassword" onsubmit="gantiPassword(event)">
          <div class="form-group">
            <label>Password Lama</label>
            <input type="password" class="form-input" id="pOldPass" placeholder="Masukkan password lama" required>
          </div>
          <div class="form-group">
            <label>Password Baru</label>
            <input type="password" class="form-input" id="pNewPass" placeholder="Minimal 6 karakter" required>
          </div>
          <div class="form-group">
            <label>Konfirmasi Password Baru</label>
            <input type="password" class="form-input" id="pConfirm" placeholder="Ulangi password baru" required>
          </div>
          <button type="submit" class="profile-submit" id="btnGantiPass">Ganti Password</button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../../includes/customer_footer.php'; ?>

<script>
const BASE_URL = '<?= BASE_URL ?>';

function showAlert(msg, type = 'error') {
    const el = document.getElementById('profileAlert');
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
    el.style.color       = type === 'success' ? '#166534' : '#991b1b';
    el.style.border      = type === 'success' ? '1px solid #86efac' : '1px solid #fca5a5';
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

async function simpanProfil(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSimpan');
    btn.disabled = true; btn.textContent = 'Menyimpan...';

    const fd = new FormData();
    fd.append('action', 'update_profile');
    fd.append('nama', document.getElementById('pNama').value);
    fd.append('no_wa', document.getElementById('pWa').value);

    const res  = await fetch(BASE_URL + '/api/customer_profile.php', { method: 'POST', body: fd });
    const data = await res.json();
    showAlert(data.message || (data.success ? 'Berhasil disimpan.' : 'Gagal menyimpan.'), data.success ? 'success' : 'error');
    btn.disabled = false; btn.textContent = 'Simpan Perubahan';
}

async function gantiPassword(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGantiPass');
    if (document.getElementById('pNewPass').value !== document.getElementById('pConfirm').value) {
        showAlert('Konfirmasi password tidak cocok.');
        return;
    }
    btn.disabled = true; btn.textContent = 'Memproses...';

    const fd = new FormData();
    fd.append('action', 'change_password');
    fd.append('old_password', document.getElementById('pOldPass').value);
    fd.append('new_password', document.getElementById('pNewPass').value);

    const res  = await fetch(BASE_URL + '/api/customer_profile.php', { method: 'POST', body: fd });
    const data = await res.json();
    showAlert(data.message || (data.success ? 'Password berhasil diubah.' : 'Gagal mengubah password.'), data.success ? 'success' : 'error');
    if (data.success) document.getElementById('formPassword').reset();
    btn.disabled = false; btn.textContent = 'Ganti Password';
}

function toggleDropdown() {
    const wrap = document.getElementById('userDropWrap');
    const dd   = document.getElementById('userDropdown');
    wrap.classList.toggle('open');
    dd.classList.toggle('show');
}
document.addEventListener('click', e => {
    const wrap = document.getElementById('userDropWrap');
    if (!wrap.contains(e.target)) {
        wrap.classList.remove('open');
        document.getElementById('userDropdown').classList.remove('show');
    }
});
function toggleMobMenu() {
    document.getElementById('mobMenu').classList.toggle('open');
}
window.addEventListener('scroll', () => {
    document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 10);
});
</script>
</body>
</html>
