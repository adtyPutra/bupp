<?php
define('BASE_URL', '/bupp');
define('UPLOAD_DIR', __DIR__ . '/../uploads/bukti_bayar/');
define('UPLOAD_URL', BASE_URL . '/uploads/bukti_bayar/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('FONNTE_TOKEN', 'Cpw2gqPaT5x4Joy8Zps2');

// Array status pesanan tetap dipertahankan untuk kebutuhan UI/Badge
define('STATUS_PESANAN', [
    'diterima'    => ['label' => 'Diterima',     'class' => 'badge-received'],
    'dicuci'      => ['label' => 'Dicuci',       'class' => 'badge-washing'],
    'dikeringkan' => ['label' => 'Dikeringkan',  'class' => 'badge-drying'],
    'finishing'     => ['label' => 'Finishing',         'class' => 'badge-finishing'],
    'siap_diambil'  => ['label' => 'Siap Diambil',      'class' => 'badge-ready'],
    'diantar_kurir' => ['label' => 'Diantar Kurir',     'class' => 'badge-delivery'],
    'selesai'       => ['label' => 'Selesai',           'class' => 'badge-done'],
]);