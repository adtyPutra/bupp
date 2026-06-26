<?php
// ============================================================
// pages/lupa-password.php — Reset Password via WhatsApp
// ============================================================
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/customer_auth.php';

if (isCustomerLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lupa Password — BUP Laundry</title>
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
    padding: 13px 16px 13px 44px;
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
input[type="text"].otp-input {
    text-align: center;
    letter-spacing: 5px;
    font-size: 1.2rem;
    font-weight: 700;
}
input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
}
input:focus + .input-icon { color: #2563eb; }
input::placeholder { color: #cbd5e1; font-weight: 400; letter-spacing: normal; }

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
.form-section.active { display: block; animation: fadeIn 0.3s ease; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 480px) {
    .card { padding: 32px 24px; border-radius: 20px; }
    .logo img { height: 44px; }
}
</style>
</head>
<body>

<div class="login-container">
    <div class="card">

        <div class="logo">
            <img src="<?= BASE_URL ?>/assets/img/logo_bup.png" alt="Logo BUP">
            <h2 id="pageTitle">Lupa Password</h2>
            <p id="pageSubtitle">Masukkan nomor WhatsApp Anda yang terdaftar.</p>
        </div>

        <!-- Alert -->
        <div id="alertBox" class="alert-box hidden"></div>

        <!-- ── STEP 1: REQUEST OTP ── -->
        <div id="step1" class="form-section active">
            <form onsubmit="submitStep1(event)">
                <div class="form-group">
                    <label for="regWa">Nomor WhatsApp</label>
                    <div class="input-group">
                        <input type="tel" id="regWa" placeholder="Contoh: 08123456789" required>
                        <i class="fas fa-phone input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnStep1">Kirim Kode OTP</button>
            </form>
        </div>

        <!-- ── STEP 2: VERIFY OTP ── -->
        <div id="step2" class="form-section">
            <form onsubmit="submitStep2(event)">
                <div class="form-group">
                    <label for="otpCode">Kode OTP (6 Digit)</label>
                    <div class="input-group">
                        <input type="text" id="otpCode" class="otp-input" placeholder="••••••" maxlength="6" required autocomplete="off">
                        <i class="fas fa-key input-icon"></i>
                    </div>
                    <div style="text-align:center; margin-top:12px;">
                        <span style="font-size:0.8rem; color:#64748b;">Belum menerima kode? <a href="#" onclick="submitStep1(event, true)" style="color:#2563eb; text-decoration:none; font-weight:600;">Kirim ulang</a></span>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnStep2">Verifikasi OTP</button>
            </form>
        </div>

        <!-- ── STEP 3: RESET PASSWORD ── -->
        <div id="step3" class="form-section">
            <form onsubmit="submitStep3(event)">
                <div class="form-group">
                    <label for="newPassword">Password Baru</label>
                    <div class="input-group">
                        <input type="password" id="newPassword" placeholder="Minimal 6 karakter" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <input type="password" id="confirmPassword" placeholder="Ulangi password baru" required>
                        <i class="fas fa-lock input-icon"></i>
                    </div>
                </div>
                <button type="submit" class="btn-submit" id="btnStep3">Simpan Password Baru</button>
            </form>
        </div>

        <!-- Back -->
        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Login
            </a>
        </div>

    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let currentWa = '';
let currentOtp = '';

function setStep(step) {
    document.getElementById('step1').classList.remove('active');
    document.getElementById('step2').classList.remove('active');
    document.getElementById('step3').classList.remove('active');
    
    document.getElementById('step' + step).classList.add('active');
    hideAlert();

    if (step === 1) {
        document.getElementById('pageTitle').textContent = 'Lupa Password';
        document.getElementById('pageSubtitle').textContent = 'Masukkan nomor WhatsApp Anda yang terdaftar.';
    } else if (step === 2) {
        document.getElementById('pageTitle').textContent = 'Verifikasi OTP';
        document.getElementById('pageSubtitle').textContent = `Kami telah mengirimkan kode OTP ke ${currentWa}.`;
    } else if (step === 3) {
        document.getElementById('pageTitle').textContent = 'Buat Password Baru';
        document.getElementById('pageSubtitle').textContent = 'Silakan buat password baru Anda.';
    }
}

function showAlert(msg, type = 'error') {
    const el = document.getElementById('alertBox');
    el.className = 'alert-box ' + (type === 'success' ? 'alert-success' : 'alert-error');
    el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
}
function hideAlert() {
    document.getElementById('alertBox').className = 'alert-box hidden';
}

async function submitStep1(e, isResend = false) {
    if(e) e.preventDefault();
    const wa = document.getElementById('regWa').value.trim();
    if (!wa) {
        showAlert('Masukkan nomor WhatsApp terlebih dahulu.');
        return;
    }

    const btn = document.getElementById('btnStep1');
    btn.disabled = true; btn.textContent = 'Mengirim...';
    hideAlert();

    const fd = new FormData();
    fd.append('action', 'request_otp');
    fd.append('no_wa', wa);

    try {
        const res  = await fetch(BASE_URL + '/api/forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        btn.disabled = false; btn.textContent = 'Kirim Kode OTP';
        
        if (data.success) {
            currentWa = wa;
            setStep(2);
            if(isResend) showAlert('OTP berhasil dikirim ulang.', 'success');
        } else {
            showAlert(data.message || 'Gagal meminta OTP.');
        }
    } catch {
        showAlert('Terjadi kesalahan koneksi.');
        btn.disabled = false; btn.textContent = 'Kirim Kode OTP';
    }
}

async function submitStep2(e) {
    e.preventDefault();
    const otp = document.getElementById('otpCode').value.trim();
    if (otp.length < 6) {
        showAlert('Masukkan 6 digit kode OTP.');
        return;
    }

    const btn = document.getElementById('btnStep2');
    btn.disabled = true; btn.textContent = 'Memverifikasi...';
    hideAlert();

    const fd = new FormData();
    fd.append('action', 'verify_otp');
    fd.append('no_wa', currentWa);
    fd.append('otp', otp);

    try {
        const res  = await fetch(BASE_URL + '/api/forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        btn.disabled = false; btn.textContent = 'Verifikasi OTP';
        
        if (data.success) {
            currentOtp = otp;
            setStep(3);
        } else {
            showAlert(data.message || 'OTP salah.');
        }
    } catch {
        showAlert('Terjadi kesalahan koneksi.');
        btn.disabled = false; btn.textContent = 'Verifikasi OTP';
    }
}

async function submitStep3(e) {
    e.preventDefault();
    const pass = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmPassword').value;

    if (pass !== confirm) {
        showAlert('Konfirmasi password tidak cocok.');
        return;
    }
    if (pass.length < 6) {
        showAlert('Password minimal 6 karakter.');
        return;
    }

    const btn = document.getElementById('btnStep3');
    btn.disabled = true; btn.textContent = 'Menyimpan...';
    hideAlert();

    const fd = new FormData();
    fd.append('action', 'reset_password');
    fd.append('no_wa', currentWa);
    fd.append('otp', currentOtp);
    fd.append('password', pass);

    try {
        const res  = await fetch(BASE_URL + '/api/forgot_password.php', { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            showAlert('Password berhasil diubah! Mengalihkan ke login...', 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 1500);
        } else {
            showAlert(data.message || 'Gagal mengubah password.');
            btn.disabled = false; btn.textContent = 'Simpan Password Baru';
        }
    } catch {
        showAlert('Terjadi kesalahan koneksi.');
        btn.disabled = false; btn.textContent = 'Simpan Password Baru';
    }
}
</script>
</body>
</html>
