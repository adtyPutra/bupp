<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';

// Cek apakah BASE_URL sudah didefinisikan, jika belum beri nilai default untuk testing
if (!defined('BASE_URL')) {
    define('BASE_URL', '/'); // Sesuaikan dengan struktur folder Anda
}

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginAdmin($username, $password)) {
        header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
        exit;
    }

    $error = 'Username atau password salah!';
}
?>

<!DOCTYPE html>
<html lang="id" class="page-login">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — BUP</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>

<div class="login-container">
    <div class="card">
        <div class="logo">
            <img src="<?= BASE_URL ?>/assets/img/logo-bup.png" alt="Logo Build Up Play">
            <h2>Login Admin</h2>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <input type="text" id="username" name="username" placeholder="Masukkan username Anda" required autocomplete="username" value="<?= htmlspecialchars($username ?? '') ?>">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn">
                Login
            </button>
        </form>

        <div class="back">
            <a href="<?= BASE_URL ?>/">
                <i class="fas fa-arrow-left"></i>
                Kembali ke Website Utama
            </a>
        </div>
    </div>
</div>

</body>
</html>

