<?php
if (!function_exists('getDeskripsi')) {
function getDeskripsi(string $jenis, string $kategori): array {
    $j = strtolower($jenis);
    $k = strtolower($kategori);
    
    $base = defined('BASE_URL') ? BASE_URL : '/bupp';
    $baseImg = $base . '/assets/img/layanan/';
    
    $imgFile = 'Sneakers deep clean.png'; // default fallback
    
    if ($k == 'sneakers') {
        if (strpos($j, 'deep') !== false) $imgFile = 'Sneakers deep clean.png';
        elseif (strpos($j, 'standard') !== false) $imgFile = 'Sneakers standar clean.png';
        elseif (strpos($j, 'kids') !== false) $imgFile = 'Sneakers kids.png';
    } elseif ($k == 'boots shoes') {
        if (strpos($j, 'deep') !== false) $imgFile = 'Boots Deep Clean.png';
        elseif (strpos($j, 'standard') !== false) $imgFile = 'Boots Standard Clean.png';
        elseif (strpos($j, 'kids') !== false) $imgFile = 'Boots Kids.png';
    } elseif ($k == 'outdoor shoes') {
        if (strpos($j, 'deep') !== false) $imgFile = 'Outdoor Shoes Deep Clean.png';
        elseif (strpos($j, 'standard') !== false) $imgFile = 'Outdoor Shoes Standard Clean.png';
        elseif (strpos($j, 'kids') !== false) $imgFile = 'Outdoor Shoes Kids.png';
    } elseif ($k == 'leather shoes') {
        if (strpos($j, 'deep') !== false) $imgFile = 'Leather Shoes Deep Clean.png';
        elseif (strpos($j, 'standard') !== false) $imgFile = 'Leather Shoes Standard Clean.png';
    } elseif ($k == 'women shoes') {
        if (strpos($j, 'heels') !== false) $imgFile = 'Heels.png';
        elseif (strpos($j, 'flat') !== false) $imgFile = 'flat shoes.png';
    } elseif ($k == 'repaint') {
        if (strpos($j, 'suede') !== false) $imgFile = 'Repaint Suede Material.png';
        elseif (strpos($j, 'canvas') !== false) $imgFile = 'Repaint Canvas Material.png';
    } elseif ($k == 'unyellowing') {
        if (strpos($j, 'upper') !== false) $imgFile = 'Upper Unyellowing.png';
        elseif (strpos($j, 'midsole') !== false) $imgFile = 'Midsole Unyellowing.png';
    } elseif ($k == 'extra treatment') {
        if (strpos($j, 'deep') !== false) $imgFile = 'Extra Treatment-deep clean.png';
        elseif (strpos($j, 'recolour') !== false) $imgFile = 'Recolour.png';
    }

    $img = $baseImg . $imgFile;

    // Default description
    $desc = "Perawatan khusus agar barang kesayangan Anda kembali bersih dan nyaman dipakai.";

    // Deskripsi spesifik berdasarkan Kategori & Jenis
    if ($k == 'sneakers') {
        if (strpos($j, 'deep') !== false) {
            $desc = "Membersihkan bagian alas, bagian dalam, bagian atas, bagian sisi, dan tali sneakers secara menyeluruh.";
        } elseif (strpos($j, 'standard') !== false) {
            $desc = "Membersihkan bagian atas, bagian sisi, dan tali sneakers secara menyeluruh.";
        } elseif (strpos($j, 'kids') !== false) {
            $desc = "Membersihkan bagian alas, bagian dalam, bagian atas, bagian sisi, dan tali sneakers anak secara menyeluruh.";
        }
    } elseif ($k == 'boots shoes') {
        if (strpos($j, 'deep') !== false) {
            $desc = "Pembersihan menyeluruh pada bagian luar, bagian dalam, bagian bawah, dan tali boots untuk membantu mengangkat debu, noda, dan kotoran hingga ke area yang sulit dijangkau.";
        } elseif (strpos($j, 'standard') !== false) {
            $desc = "Pembersihan pada bagian luar dan tali boots untuk membantu mengangkat debu, noda, dan kotoran yang menempel serta menjaga tampilan boots tetap bersih dan rapi.";
        } elseif (strpos($j, 'kids') !== false) {
            $desc = "Pembersihan menyeluruh pada boots anak untuk membantu menjaga kebersihan dan kenyamanan saat digunakan.";
        }
    } elseif ($k == 'outdoor shoes') {
        if (strpos($j, 'deep') !== false) {
            $desc = "Membersihkan bagian alas, bagian dalam, bagian atas, bagian sisi, dan tali sepatu outdoor secara menyeluruh.";
        } elseif (strpos($j, 'standard') !== false) {
            $desc = "Membersihkan bagian atas, bagian sisi, dan tali sepatu outdoor secara menyeluruh.";
        } elseif (strpos($j, 'kids') !== false) {
            $desc = "Membersihkan bagian alas, bagian dalam, bagian atas, bagian sisi, dan tali sepatu outdoor anak secara menyeluruh.";
        }
    } elseif ($k == 'leather shoes') {
        if (strpos($j, 'deep') !== false) {
            $desc = "Membersihkan seluruh bagian sepatu kulit secara menyeluruh dengan perawatan khusus agar material tetap terjaga.";
        } elseif (strpos($j, 'standard') !== false) {
            $desc = "Membersihkan bagian atas dan sisi sepatu kulit secara menyeluruh dengan perawatan khusus agar material tetap terjaga.";
        }
    } elseif ($k == 'women shoes') {
        if (strpos($j, 'heels') !== false) {
            $desc = "Membersihkan hak dan bagian atas sepatu secara menyeluruh agar bebas dari noda.";
        } elseif (strpos($j, 'flat shoes') !== false) {
            $desc = "Membersihkan sepatu datar secara menyeluruh agar bebas dari kotoran dan noda.";
        }
    } elseif ($k == 'repaint') {
        if (strpos($j, 'canvas') !== false) {
            $desc = "Mewarnai ulang sepatu berbahan kanvas agar warnanya rata dan kembali terlihat seperti baru.";
        } elseif (strpos($j, 'suede') !== false) {
            $desc = "Mewarnai ulang sepatu berbahan suede secara hati-hati agar warnanya rata dan kembali terlihat seperti baru.";
        } else {
            $desc = "Mewarnai ulang sepatu agar warnanya rata dan kembali terlihat seperti baru.";
        }
    } elseif ($k == 'unyellowing') {
        if (strpos($j, 'midsole') !== false) {
            $desc = "Menghilangkan noda kuning di bagian tengah sol (midsole) sepatu agar warnanya kembali putih dan bersih.";
        } else {
            $desc = "Mengembalikan warna asli pada bagian atas sepatu dengan menghilangkan noda kuning menggunakan perawatan khusus.";
        }
    } elseif ($k == 'extra treatment') {
        if (strpos($j, 'deep clean') !== false) {
            $desc = "Layanan cuci tambahan yang dapat dipilih pada pesanan Repaint atau Unyellowing. Sepatu akan dibersihkan secara menyeluruh pada bagian luar dan dalam untuk membantu mengangkat debu, noda, dan kotoran yang menempel.";
        } elseif (strpos($j, 'recolour') !== false) {
            $desc = "Layanan pewarnaan ulang yang dapat dipilih pada pesanan Repaint untuk membantu mengembalikan warna sepatu yang pudar agar terlihat lebih segar dan merata.";
        } else {
            $desc = "Layanan tambahan yang bisa dipilih saat memesan layanan utama.";
        }
    }
    // Penentuan Estimasi Waktu
    $estimasi = "3-5 Hari Kerja"; // Default sesuai price list (3-5 Day Work)
    if ($k == 'repaint' || $k == 'unyellowing' || $k == 'extra treatment') {
        $estimasi = "7-10 Hari Kerja";
    }

    return ['desc' => $desc, 'img' => $img, 'estimasi' => $estimasi];
}
}
?>