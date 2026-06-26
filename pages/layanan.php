<?php
// ============================================================
// pages/layanan.php — Katalog Layanan (Sidebar Layout & Photos)
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_auth.php';

$isLoggedIn = isCustomerLoggedIn();
$customer = $isLoggedIn ? getLoggedInCustomer() : null;
$namaInisial = $customer ? strtoupper(substr($customer['nama'], 0, 2)) : '';

$db = db();
$stmt = $db->query("SELECT * FROM layanan WHERE aktif = 1 ORDER BY kategori ASC, harga ASC");
$layananList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unique categories
$categories = [];
$filteredLayanan = [];
foreach ($layananList as $l) {
    $filteredLayanan[] = $l;
    if (!in_array($l['kategori'], $categories)) {
        $categories[] = $l['kategori'];
    }
}

// Map deskripsi natural & gambar dari Unsplash
require_once __DIR__ . '/../includes/service_helper.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Katalog Layanan | BUP Laundry</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/main.css?v=<?= time() ?>">
<link rel="stylesheet" href="../assets/css/customer.css?v=<?= time() ?>">
<style>
body { 
    background: #fdfbf7; 
    color: #1e293b;
}

.catalog-wrapper {
    max-width: 1300px;
    margin: 120px auto 60px; /* clear fixed navbar */
    padding: 0 20px;
    display: flex;
    align-items: flex-start;
    gap: 30px;
}

/* Sidebar */
.sidebar {
    width: 260px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    flex-shrink: 0;
    position: sticky;
    top: 100px;
}
.sidebar-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.sidebar-header .icon-box {
    background: #3b82f6; 
    color: white;
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.sidebar-header h3 {
    font-size: 1.05rem;
    font-weight: 700;
    color: #0f172a;
}
.sidebar-menu {
    list-style: none;
    padding: 10px 0;
    max-height: calc(100vh - 180px);
    overflow-y: auto;
}
.sidebar-menu::-webkit-scrollbar { width: 4px; }
.sidebar-menu::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.sidebar-menu li {
    padding: 12px 20px;
    cursor: pointer;
    font-weight: 600;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
    font-size: 0.95rem;
}
.sidebar-menu li:hover {
    background: #eff6ff;
    color: #3b82f6;
}
.sidebar-menu li.active {
    color: #3b82f6;
    border-left: 4px solid #3b82f6;
    background: #eff6ff;
    padding-left: 16px;
}

/* Main Content */
.catalog-main {
    flex-grow: 1;
    min-width: 0;
}
.main-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}
.main-header h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
    white-space: nowrap;
}
.main-header h2::before {
    content: '';
    display: block;
    width: 4px;
    height: 24px;
    background: #3b82f6;
    border-radius: 4px;
}
.badge-count {
    background: white;
    border: 1px solid #e2e8f0;
    padding: 6px 14px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

/* Free Delivery Banner */
.delivery-banner {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 16px 24px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.2);
}
.delivery-title {
    font-size: 1.1rem;
    font-weight: 800;
}
.delivery-body {
    display: flex;
    align-items: center;
    gap: 16px;
}
.delivery-icon {
    font-size: 1.8rem;
    background: rgba(255,255,255,0.2);
    min-width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 50%;
}
.delivery-text {
    font-size: 0.95rem;
    line-height: 1.4;
}

/* Grid */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    border: 1px solid #f1f5f9;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}
.card-img {
    height: 200px;
    background-size: cover;
    background-position: center;
    background-color: #e2e8f0;
    position: relative;
}
.card-badge {
    position: absolute;
    top: 12px; right: 12px;
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(4px);
    color: white;
    border: 1px solid rgba(255,255,255,0.4);
    font-size: 0.7rem;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 50px;
    text-transform: uppercase;
}
.card-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.card-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 6px;
}
.card-cat {
    font-size: 0.75rem;
    color: #3b82f6;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 8px;
}
.card-desc {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 16px;
    flex-grow: 1;
}
.card-footer {
    margin-top: auto;
}
.card-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e3a8a;
    margin-bottom: 12px;
}
.card-btn {
    display: block;
    width: 100%;
    text-align: center;
    padding: 10px;
    background: #0f172a;
    border: 1px solid #0f172a;
    color: white;
    border-radius: 8px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s;
}
.card-btn:hover, .card-btn:active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    color: #94a3b8;
    display: none;
}

.cat-select-mob {
    display: none;
    padding: 12px 16px;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 600;
    color: #0f172a;
    background: #f8fafc;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1em;
}

@media (max-width: 900px) {
    .catalog-wrapper {
        flex-direction: column;
        margin-top: 100px;
    }
    .sidebar {
        width: 100%;
        position: static;
    }
    .sidebar-menu { display: none !important; }
    .cat-select-mob { display: block; margin: 0 20px 20px; width: calc(100% - 40px); }
    .sidebar-header { border-bottom: none; padding-bottom: 12px; }
}

@media (max-width: 480px) {
    .main-header h2 { font-size: 1.25rem; }
    .badge-count { padding: 4px 12px; font-size: 0.75rem; }
    .delivery-title { font-size: 1.05rem; }
    .delivery-text { font-size: 0.85rem; }
    .delivery-icon { min-width: 40px; height: 40px; font-size: 1.4rem; }
    .main-header { gap: 10px; }
}

.navbar .container {
    max-width: 1300px !important;
    padding: 0 20px !important;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar" id="navbar">
  <div class="container">
    <a href="<?= $isLoggedIn ? 'customer/home.php' : '../index.php' ?>" class="logo-wrap">
      <img src="../assets/img/logo_bup.png" alt="Logo BUP">
    </a>
    
    <?php if ($isLoggedIn): ?>
        <div class="nav-links" id="navLinks">
          <a href="customer/home.php">Beranda</a>
          <a href="layanan.php" class="active">Layanan</a>
          <a href="customer/my-orders.php">Riwayat Pesanan</a>
        </div>
        <div class="nav-right">
          <a href="order.php" class="btn btn-dark hide-mob" style="padding: 8px 18px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; text-decoration: none;">Pesan</a>
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
              <a href="customer/profile.php">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profil Saya
              </a>
              <div class="dd-divider"></div>
              <button class="dd-logout" onclick="window.location.href='../api/customer_auth.php?action=logout'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                Logout
              </button>
            </div>
          </div>
        </div>
    <?php else: ?>
        <div class="nav-links" id="navLinks">
          <a href="../index.php">Beranda</a>
          <a href="layanan.php" class="active">Layanan</a>
          <a href="status.php">Status Pesanan</a>
        </div>
        <div class="nav-right">
            <a href="login.php" class="btn btn-outline" style="border: 1px solid #1f2937; color: #1f2937; font-weight: 600; padding: 8px 18px; border-radius: 50px; text-decoration: none;">Login</a>
            <a href="login.php?mode=register" class="btn btn-dark" style="padding: 8px 18px; border-radius: 50px; background-color: #1f2937; color: white; border: none; font-weight: 600; text-decoration: none;">Daftar</a>
        </div>
    <?php endif; ?>
    
    <div class="hamburger" id="hamburger" onclick="toggleMobMenu()"><span></span><span></span><span></span></div>
  </div>
</nav>

<!-- Mobile Menu -->
<div class="mob-menu" id="mobMenu">
  <span class="mob-close" onclick="toggleMobMenu()">✕</span>
  <?php if ($isLoggedIn): ?>
      <a href="customer/home.php" onclick="toggleMobMenu()">Beranda</a>
      <a href="layanan.php" onclick="toggleMobMenu()">Layanan</a>
      <a href="customer/my-orders.php" onclick="toggleMobMenu()">Riwayat Pesanan</a>
      <a href="customer/profile.php" onclick="toggleMobMenu()">Profil Saya</a>
      <a href="../api/customer_auth.php?action=logout" style="color:#ef4444;">Logout</a>
      <a href="order.php" class="btn btn-dark" style="margin-top:14px;justify-content:center">Pesan</a>
  <?php else: ?>
      <a href="../index.php" onclick="toggleMobMenu()">Beranda</a>
      <a href="layanan.php" onclick="toggleMobMenu()">Layanan</a>
      <a href="status.php" onclick="toggleMobMenu()">Status Pesanan</a>
      <a href="login.php" class="btn btn-outline" style="margin-top:14px;justify-content:center; border: 1px solid #1f2937; color: #1f2937; border-radius: 50px;">Login</a>
      <a href="login.php?mode=register" class="btn btn-dark" style="margin-top:8px;justify-content:center; border-radius: 50px;">Daftar</a>
  <?php endif; ?>
</div>

<div class="catalog-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="icon-box"><i class="fas fa-filter"></i></div>
            <h3>Kategori</h3>
        </div>
        
        <!-- Mobile Dropdown -->
        <select class="cat-select-mob" id="catSelect">
            <option value="all">Semua Layanan</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Desktop Menu -->
        <ul class="sidebar-menu" id="catMenu">
            <li class="active" data-filter="all"><i class="fas fa-border-all"></i> Semua Layanan</li>
            <?php foreach ($categories as $cat): ?>
                <li data-filter="<?= htmlspecialchars($cat) ?>"><i class="fas fa-tag"></i> <?= htmlspecialchars($cat) ?></li>
            <?php endforeach; ?>
        </ul>
    </aside>

    <main class="catalog-main">
        <div class="delivery-banner">
            <strong class="delivery-title">Free Pick Up & Delivery!</strong>
            <div class="delivery-body">
                <div class="delivery-icon"><i class="fas fa-truck"></i></div>
                <div class="delivery-text">
                    Gratis ongkir untuk jarak penjemputan dan pengantaran kurang dari 4km.
                </div>
            </div>
        </div>

        <div class="main-header">
            <h2>Katalog Layanan</h2>
            <div class="badge-count"><span id="prodCount"><?= count($filteredLayanan) ?></span> Layanan</div>
        </div>

        <div class="grid" id="prodGrid">
            <?php foreach ($filteredLayanan as $item): ?>
                <?php $info = getDeskripsi($item['jenis'], $item['kategori']); ?>
                <div class="card" data-category="<?= htmlspecialchars($item['kategori']) ?>">
                    <div class="card-img" style="background-image: url('<?= $info['img'] ?>');">
                        <div class="card-badge" style="background: rgba(255, 255, 255, 0.95); color: #ea580c; border: 1px solid #fed7aa; text-transform: none; display: flex; align-items: center; gap: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); font-weight: 700;">
                            <i class="fas fa-stopwatch"></i> <?= $info['estimasi'] ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="card-cat"><?= htmlspecialchars($item['kategori']) ?></div>
                        <h4 class="card-title"><?= htmlspecialchars($item['jenis']) ?></h4>
                        <p class="card-desc"><?= $info['desc'] ?></p>
                        
                        <div class="card-footer">
                            <div class="card-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                            <a href="order.php?layanan=<?= $item['id'] ?>" class="card-btn">Pesan</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="empty-state" id="emptyState">
                <i class="fas fa-box-open" style="font-size:3rem; margin-bottom:16px; color:#cbd5e1;"></i>
                <p>Tidak ada layanan di kategori ini.</p>
            </div>
        </div>
    </main>
</div>

<script>
function toggleDropdown() {
    document.getElementById('userDropdown').classList.toggle('show');
}
function toggleMobMenu() {
    document.getElementById('mobMenu').classList.toggle('show');
}
window.addEventListener('click', function(e) {
    const dd = document.getElementById('userDropdown');
    if (dd && dd.classList.contains('show') && !e.target.closest('.nav-dropdown-wrap')) {
        dd.classList.remove('show');
    }
});

// Category Filter Logic
const catMenu = document.getElementById('catMenu');
const catItems = catMenu.querySelectorAll('li');
const cards = document.querySelectorAll('.card');
const countEl = document.getElementById('prodCount');
const emptyState = document.getElementById('emptyState');

const catSelect = document.getElementById('catSelect');

function filterCards(filter) {
    let count = 0;
    cards.forEach(card => {
        if (filter === 'all' || card.getAttribute('data-category') === filter) {
            card.style.display = 'flex';
            count++;
        } else {
            card.style.display = 'none';
        }
    });
    countEl.textContent = count;
    if (count === 0) {
        emptyState.style.display = 'block';
    } else {
        emptyState.style.display = 'none';
    }
}

catItems.forEach(item => {
    item.addEventListener('click', () => {
        catItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        const filter = item.getAttribute('data-filter');
        if(catSelect) catSelect.value = filter;
        filterCards(filter);
    });
});

if(catSelect) {
    catSelect.addEventListener('change', (e) => {
        const filter = e.target.value;
        catItems.forEach(i => i.classList.remove('active'));
        const activeLi = Array.from(catItems).find(i => i.getAttribute('data-filter') === filter);
        if(activeLi) activeLi.classList.add('active');
        filterCards(filter);
    });
}
</script>


<?php include '../includes/customer_footer.php'; ?>

</body>
</html>
