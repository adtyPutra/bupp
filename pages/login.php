<?php
// ============================================================
// pages/login.php — Login Pelanggan (formal, mirip admin login)
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/customer_auth.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$redirect = htmlspecialchars($_GET['redirect'] ?? '');
$mode     = $_GET['mode'] ?? 'login';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $mode === 'register' ? 'Daftar Akun' : 'Selamat Datang' ?> — BUP</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background-color: #f1f5f9;
    background-image:
        radial-gradient(at 0% 0%,   rgba(37,99,235,0.15)  0px, transparent 50%),
        radial-gradient(at 100% 0%,  rgba(96,165,250,0.15) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(37,99,235,0.10) 0px, transparent 50%),
        radial-gradient(at 0% 100%,  rgba(147,197,253,0.15) 0px, transparent 50%);
    background-attachment: fixed;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    color: #0f172a;
}

.login-container {
    width: 100%;
    max-width: 420px;
}

.card {
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.5);
    border-radius: 24px;
    padding: 40px 36px;
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.08);
}

/* Logo & Header */
.logo {
    text-align: center;
    margin-bottom: 32px;
}
.logo img {
    height: 52px;
    width: auto;
    object-fit: contain;
    margin-bottom: 16px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.logo h2 {
    font-size: 1.45rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 5px;
    letter-spacing: -0.5px;
}
.logo p {
    color: #64748b;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Alert */
.alert-box {
    display: none;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-box.hidden { display: none !important; }
.alert-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }

/* Form Group */
.form-group { margin-bottom: 20px; }

label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 8px;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 16px;
    color: #94a3b8;
    font-size: 0.95rem;
    pointer-events: none;
    transition: color 0.2s;
}

input[type="text"],
input[type="password"],
input[type="tel"] {
    width: 100%;
    padding: 13px 44px 13px 44px;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    color: #0f172a;
    font-size: 0.95rem;
    font-family: inherit;
    font-weight: 500;
    outline: none;
    transition: all 0.2s ease;
}
input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
}
input:focus + .input-icon { color: #2563eb; }
input::placeholder { color: #cbd5e1; font-weight: 400; }

.toggle-password {
    position: absolute;
    right: 16px;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.95rem;
    transition: color 0.2s;
}
.toggle-password:hover {
    color: #0f172a;
}

/* Button */
.btn-submit {
    width: 100%;
    padding: 14px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s ease;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-submit:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(37,99,235,0.3);
}
.btn-submit:active { transform: translateY(0); }
.btn-submit:disabled {
    background: #94a3b8;
    transform: none;
    cursor: not-allowed;
    box-shadow: none;
}

/* Footer link */
.auth-switch {
    text-align: center;
    margin-top: 22px;
    font-size: 0.875rem;
    color: #64748b;
}
.auth-switch a {
    color: #2563eb;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}
.auth-switch a:hover { text-decoration: underline; }

/* Back link */
.back-link {
    text-align: center;
    margin-top: 24px;
    border-top: 1px solid #e2e8f0;
    padding-top: 20px;
}
.back-link a {
    color: #64748b;
    font-size: 0.875rem;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: color 0.2s;
}
.back-link a:hover { color: #2563eb; }

/* Section toggle */
.form-section { display: none; }
.form-section.active { display: block; }

@media (max-width: 480px) {
    .card { padding: 32px 24px; border-radius: 20px; }
    .logo img { height: 44px; }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="card">

        <!-- Logo -->
        <div class="logo">
            <img src="<?= BASE_URL ?>/assets/img/logo-bup.png" alt="Logo BUP">
            <h2 id="pageTitle"><?= $mode === 'register' ? 'Buat Akun Baru' : 'Selamat Datang' ?></h2>
            <p id="pageSubtitle"><?= $mode === 'register' ? 'Isi data di bawah untuk mendaftar' : 'Silakan masuk untuk melanjutkan' ?></p>
        </div>

        <!-- Alert -->
        <div id="alertBox" class="alert-box hidden"></div>

        <!-- ── FORM LOGIN ── -->
        <div id="sectionLogin" class="form-section <?= $mode !== 'register' ? 'active' : '' ?>">
            <form onsubmit="submitLogin(event)">
                <div class="form-group">
                    <label for="loginUsername">Username</label>
                    <div class="input-group">
                        <input type="text" id="loginUsername" placeholder="Masukkan username Anda" required autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Password</label>
                    <div class="input-group">
                        <input type="password" id="loginPassword" placeholder="••••••••••••" required autocomplete="current-password">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('loginPassword', this)"></i>
                    </div>
                    <div style="text-align:right; margin-top:8px;">
                        <a href="lupa-password.php" style="font-size:0.8rem; color:#2563eb; font-weight:600; text-decoration:none;">Lupa Password?</a>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnLogin">Login</button>

            </form>
            <div class="auth-switch">
                Belum punya akun? <a onclick="switchMode('register')">Daftar Sekarang</a>
            </div>
        </div>

        <!-- ── FORM REGISTER ── -->
        <div id="sectionRegister" class="form-section <?= $mode === 'register' ? 'active' : '' ?>">
            <form onsubmit="submitRegister(event)">
                <div class="form-group">
                    <label for="regNama">Nama Lengkap</label>
                    <div class="input-group">
                        <input type="text" id="regNama" placeholder="Nama lengkap Anda" required autocomplete="name">
                        <i class="fas fa-id-card input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="regWa">Nomor WhatsApp</label>
                    <div class="input-group">
                        <input type="tel" id="regWa" placeholder="08xx-xxxx-xxxx" required autocomplete="tel">
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="regUsername">Username</label>
                    <div class="input-group">
                        <input type="text" id="regUsername" placeholder="Buat username unik (huruf & angka)" required autocomplete="username">
                        <i class="fas fa-user input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="regPassword">Password</label>
                    <div class="input-group">
                        <input type="password" id="regPassword" placeholder="Minimal 6 karakter" required autocomplete="new-password">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('regPassword', this)"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="regConfirm">Konfirmasi Password</label>
                    <div class="input-group">
                        <input type="password" id="regConfirm" placeholder="Ulangi password" required autocomplete="new-password">
                        <i class="fas fa-lock input-icon"></i>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('regConfirm', this)"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnRegister">Daftar</button>
            </form>
            <div class="auth-switch">
                Sudah punya akun? <a onclick="switchMode('login')">Login di sini</a>
            </div>
        </div>

        <!-- Back -->
        <div class="back-link">
            <a href="<?= BASE_URL ?>/index.php">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Website Utama
            </a>
        </div>

    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const REDIRECT  = '<?= $redirect ?>';

function switchMode(mode) {
    document.getElementById('sectionLogin').classList.toggle('active',    mode === 'login');
    document.getElementById('sectionRegister').classList.toggle('active', mode === 'register');
    document.getElementById('pageTitle').textContent    = mode === 'register' ? 'Buat Akun Baru'                    : 'Selamat Datang';
    document.getElementById('pageSubtitle').textContent = mode === 'register' ? 'Isi data di bawah untuk mendaftar' : 'Silakan masuk untuk melanjutkan';
    hideAlert();
    history.replaceState(null, '', '?mode=' + mode + (REDIRECT ? '&redirect=' + encodeURIComponent(REDIRECT) : ''));
}

function showAlert(msg, type = 'error') {
    const el = document.getElementById('alertBox');
    el.className = 'alert-box ' + (type === 'success' ? 'alert-success' : 'alert-error');
    el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
}
function hideAlert() {
    document.getElementById('alertBox').className = 'alert-box hidden';
}

function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

async function submitLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLogin');
    btn.disabled = true; btn.textContent = 'Memproses...';
    hideAlert();

    const fd = new FormData();
    fd.append('action', 'login');
    fd.append('username', document.getElementById('loginUsername').value);
    fd.append('password', document.getElementById('loginPassword').value);

    try {
        const res  = await fetch(BASE_URL + '/api/customer_auth.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert('Login berhasil! Mengalihkan...', 'success');
            setTimeout(() => {
                window.location.href = REDIRECT || BASE_URL + '/pages/customer/home.php';
            }, 700);
        } else {
            showAlert(data.message || 'Username atau password salah.');
            btn.disabled = false; btn.textContent = 'Login';
        }
    } catch {
        showAlert('Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false; btn.textContent = 'Login';
    }
}

async function submitRegister(e) {
    e.preventDefault();
    const btn = document.getElementById('btnRegister');
    btn.disabled = true; btn.textContent = 'Memproses...';
    hideAlert();

    const fd = new FormData();
    fd.append('action', 'register');
    fd.append('nama',             document.getElementById('regNama').value);
    fd.append('no_wa',            document.getElementById('regWa').value);
    fd.append('username',         document.getElementById('regUsername').value);
    fd.append('password',         document.getElementById('regPassword').value);
    fd.append('confirm_password', document.getElementById('regConfirm').value);

    try {
        const res  = await fetch(BASE_URL + '/api/customer_auth.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            showAlert('Akun berhasil dibuat! Silakan login.', 'success');
            document.getElementById('regNama').value = '';
            document.getElementById('regWa').value = '';
            document.getElementById('regUsername').value = '';
            document.getElementById('regPassword').value = '';
            document.getElementById('regConfirm').value = '';
            setTimeout(() => {
                switchMode('login');
                btn.disabled = false; btn.textContent = 'Daftar';
            }, 1500);
        } else {
            showAlert(data.message || 'Pendaftaran gagal.');
            btn.disabled = false; btn.textContent = 'Daftar';
        }
    } catch {
        showAlert('Terjadi kesalahan. Silakan coba lagi.');
        btn.disabled = false; btn.textContent = 'Daftar';
    }
}
</script>
</body>
</html>
