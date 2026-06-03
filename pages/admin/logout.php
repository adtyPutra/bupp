<?php
// Load file auth.php agar fungsinya terbaca
require_once __DIR__ . '/../../includes/auth.php';

// Panggil fungsi logout yang sudah kamu buat di auth.php
// Pastikan namanya persis (Case Sensitive)
logoutAdmin();