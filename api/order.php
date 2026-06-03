<?php
// Sembunyikan pesan error default PHP agar format JSON tidak rusak
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('generateKodePesanan')) {
    function generateKodePesanan() {
        // Hanya menggunakan huruf dan angka, tanpa karakter ambigu seperti O dan 0 jika memungkinkan
        // Namun untuk kesederhanaan, kita gunakan A-Z dan 0-9 standar
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 6; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Format menjadi: BUP-XXXXXX
        return 'BUP-' . $randomString;
    }
}

if (!function_exists('uploadBuktiTransfer')) {
    function uploadBuktiTransfer($fileArray) {
        if (!is_array($fileArray) || !isset($fileArray['name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $targetDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : __DIR__ . '/../uploads/bukti_bayar/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = basename($fileArray['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = time() . '_' . uniqid() . '.' . $fileType;
        $targetFilePath = $targetDir . $newFileName;
        $allowedTypes = ['jpg', 'jpeg', 'png'];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($fileArray['tmp_name'], $targetFilePath)) return $newFileName;
        }
        return false;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    // ==============================================================
    // PROSES MENYIMPAN PESANAN (BISA BANYAK ITEM)
    // ==============================================================
    if ($method === 'POST' && $action === 'create') {
        $nama             = trim($_POST['nama'] ?? '');
        $no_wa            = trim($_POST['no_wa'] ?? '');
        $alamat           = trim($_POST['alamat'] ?? '');
        
        // Data Multi Item (Array)
        $layanan_id_arr   = $_POST['layanan_id'] ?? [];
        $sepatu_arr       = $_POST['sepatu'] ?? [];
        $ukuran_arr       = $_POST['ukuran'] ?? [];
        $warna_arr        = $_POST['warna'] ?? [];
        $jumlah_arr       = $_POST['jumlah'] ?? [];
        $extra_layanan_id_arr = $_POST['extra_layanan_id'] ?? [];
        
        $metode_pengiriman_id = (int)($_POST['metode_pengiriman'] ?? 1); 
        
        // PERBAIKAN: Pastikan empty string ("") menjadi NULL
        $waktu_penjemputan    = !empty($_POST['waktu_penjemputan']) ? trim($_POST['waktu_penjemputan']) : null; 
        
        // Jika diantar ke toko (ID 1), pastikan jadwal penjemputan dikosongkan
        if ($metode_pengiriman_id === 1) {
            $waktu_penjemputan = null;
        }

        $metode_bayar     = $_POST['metode_bayar'] ?? 'transfer_bca';
        $tanggal_pesan    = $_POST['tanggal_pesan'] ?? date('Y-m-d');
        $catatan_umum     = trim($_POST['catatan'] ?? '');

        if (!$nama || !$no_wa || empty($layanan_id_arr) || empty($sepatu_arr)) {
            jsonResponse(['success' => false, 'message' => 'Lengkapi semua field yang wajib diisi'], 400);
        }

        $db = getDB();

        // Cek Biaya Pengiriman
        $stmtPengiriman = $db->prepare("SELECT * FROM metode_pengiriman WHERE id = ?");
        $stmtPengiriman->execute([$metode_pengiriman_id]);
        $pengiriman = $stmtPengiriman->fetch();
        // $biaya_pengiriman = 0; // Sudah tidak dipakai

        // Hapus pengecekan biaya_pengiriman
        // if ($biaya_pengiriman > 0 && empty($alamat)) {
        //    jsonResponse(['success' => false, 'message' => 'Alamat wajib diisi untuk layanan pengantaran'], 400);
        // }

        // Kalkulasi Total Harga Semua Item yang Ada di Array
        $total_harga_layanan = 0;
        $itemsToInsert = [];

        foreach ($layanan_id_arr as $index => $l_id) {
            $l_id = (int)$l_id;
            $jml = max(1, (int)($jumlah_arr[$index] ?? 1));
            $merk = trim($sepatu_arr[$index] ?? '');
            $uk = trim($ukuran_arr[$index] ?? '');
            $wrn = trim($warna_arr[$index] ?? '');

            $stmtLayanan = $db->prepare("SELECT harga FROM layanan WHERE id = ? AND aktif = 1");
            $stmtLayanan->execute([$l_id]);
            $layanan = $stmtLayanan->fetch();

            if ($layanan && !empty($merk)) {
                $harga_satuan = (int)$layanan['harga'];
                $total_harga_layanan += ($harga_satuan * $jml);
                
                // Simpan data di memori sementara untuk dimasukkan ke database nanti
                $itemsToInsert[] = [
                    'layanan_id' => $l_id,
                    'merk_item' => $merk,
                    'ukuran' => $uk,
                    'warna' => $wrn,
                    'jumlah' => $jml,
                    'harga_satuan' => $harga_satuan
                ];
            }
        }

        // Extra Treatment items
        foreach ($extra_layanan_id_arr as $index => $et_id) {
            $et_id = (int)$et_id;
            if (!$et_id) continue;
            $jml = max(1, (int)($jumlah_arr[$index] ?? 1));
            $merk = trim($sepatu_arr[$index] ?? '-');
            $uk = trim($ukuran_arr[$index] ?? '');
            $wrn = trim($warna_arr[$index] ?? '');

            $stmtET = $db->prepare("SELECT harga FROM layanan WHERE id = ? AND aktif = 1");
            $stmtET->execute([$et_id]);
            $etLayanan = $stmtET->fetch();

            if ($etLayanan) {
                $harga_et = (int)$etLayanan['harga'];
                $total_harga_layanan += ($harga_et * $jml);
                $itemsToInsert[] = [
                    'layanan_id'  => $et_id,
                    'merk_item'   => $merk,
                    'ukuran'      => $uk,
                    'warna'       => $wrn,
                    'jumlah'      => $jml,
                    'harga_satuan'=> $harga_et
                ];
            }
        }

        if (empty($itemsToInsert)) {
            jsonResponse(['success' => false, 'message' => 'Layanan tidak valid atau merk sepatu kosong'], 400);
        }

        $ongkir_dinamis = isset($_POST['ongkir_dinamis']) ? (int)$_POST['ongkir_dinamis'] : 0;
        $tambah_ongkir = ($ongkir_dinamis === -1) ? 0 : $ongkir_dinamis;
        $total_harga  = $total_harga_layanan + $tambah_ongkir;
        
        $kode_pesanan = generateKodePesanan();

        // Handle file upload Bukti Bayar
        $bukti_bayar = null;
        $status_bayar = 'pending'; // Semua pesanan baru mulai pending, admin yg konfirmasi (termasuk tunai)
        if ($metode_bayar !== 'tunai' && isset($_FILES['bukti_bayar'])) {
            $uploadedFileName = uploadBuktiTransfer($_FILES['bukti_bayar']);
            if ($uploadedFileName) {
                $bukti_bayar = $uploadedFileName;
            } else {
                jsonResponse(['success' => false, 'message' => 'Gagal mengupload foto bukti bayar atau format tidak diizinkan.'], 400);
            }
        }

        // Jalankan Database Transaction
        $db->beginTransaction();

        try {
            $stmtPelanggan = $db->prepare("SELECT id FROM pelanggan WHERE no_wa = ?");
            $stmtPelanggan->execute([$no_wa]);
            $pelanggan = $stmtPelanggan->fetch();

            if ($pelanggan) {
                $pelanggan_id = $pelanggan['id'];
                // Hanya update alamat jika ada nilai baru — jangan hapus alamat lama
                // (mencegah data alamat hilang saat customer memesan dengan Ambil di Toko)
                if (!empty($alamat)) {
                    $db->prepare("UPDATE pelanggan SET nama = ?, alamat = ? WHERE id = ?")
                       ->execute([$nama, $alamat, $pelanggan_id]);
                } else {
                    $db->prepare("UPDATE pelanggan SET nama = ? WHERE id = ?")
                       ->execute([$nama, $pelanggan_id]);
                }
            } else {
                $db->prepare("INSERT INTO pelanggan (nama, no_wa, alamat) VALUES (?, ?, ?)")
                   ->execute([$nama, $no_wa, $alamat ?: null]);
                $pelanggan_id = $db->lastInsertId();
            }

            // Insert Pesanan Utama dengan tambahan waktu_penjemputan dan ongkir
            $stmtPesanan = $db->prepare("INSERT INTO pesanan 
                (kode_pesanan, pelanggan_id, tanggal_pesan, metode_pengiriman_id, waktu_penjemputan, metode_bayar, total_harga, ongkir, status_pesanan, status_bayar, bukti_bayar) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'diterima', ?, ?)");
            $stmtPesanan->execute([
                $kode_pesanan, $pelanggan_id, $tanggal_pesan, $metode_pengiriman_id, $waktu_penjemputan,
                $metode_bayar, $total_harga, $ongkir_dinamis, $status_bayar, $bukti_bayar
            ]);
            $pesanan_id = $db->lastInsertId();

            // Insert ke tabel detail_pesanan BANYAK KALI (Sesuai Jumlah Item)
            $stmtDetail = $db->prepare("INSERT INTO detail_pesanan 
                (pesanan_id, layanan_id, merk_item, ukuran, warna, jumlah, harga_satuan, catatan) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            foreach ($itemsToInsert as $index => $item) {
                // Catatan umum disisipkan pada sepatu pertama saja
                $note = ($index === 0) ? $catatan_umum : null;
                
                $stmtDetail->execute([
                    $pesanan_id, 
                    $item['layanan_id'], 
                    $item['merk_item'], 
                    $item['ukuran'],
                    $item['warna'],
                    $item['jumlah'], 
                    $item['harga_satuan'], 
                    $note
                ]);
            }

            $db->commit();

            jsonResponse([
                'success' => true,
                'kode_pesanan' => $kode_pesanan,
                'message' => 'Pesanan berhasil dibuat'
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['success' => false, 'message' => 'Gagal menyimpan data ke database: ' . $e->getMessage()], 500);
        }
        exit;
    }

    // ==============================================================
    // PROSES PENGECEKAN STATUS (METHOD GET) 
    // ==============================================================
    if ($method === 'GET' && $action === 'status') {
        $kode = strtoupper(trim($_GET['kode'] ?? ''));
        if (!$kode) {
            jsonResponse(['success' => false, 'message' => 'Masukkan kode pesanan'], 400);
        }

        $db = getDB();
        
        // 1. Ambil Data Pesanan Utama beserta waktu_penjemputan dan ongkir
        $stmt = $db->prepare("
            SELECT 
                p.id, p.kode_pesanan, p.total_harga, p.ongkir, p.tanggal_pesan, p.status_pesanan, p.waktu_penjemputan,
                p.metode_bayar, p.status_bayar, p.bukti_bayar, p.created_at, p.updated_at, p.catatan_pembatalan,
                pel.nama AS nama_pelanggan, pel.no_wa, pel.alamat,
                mp.nama_metode
            FROM pesanan p
            LEFT JOIN pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN metode_pengiriman mp ON p.metode_pengiriman_id = mp.id
            WHERE p.kode_pesanan = ?
            LIMIT 1
        ");
        $stmt->execute([$kode]);
        $pesanan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pesanan) {
            jsonResponse(['success' => false, 'message' => 'Pesanan tidak ditemukan']);
        }

        // 2. Ambil SEMUA Detail Sepatu (Bisa banyak baris)
        $stmtItems = $db->prepare("
            SELECT 
                dp.merk_item AS merek, 
                dp.jumlah AS qty, 
                l.kategori, 
                l.jenis AS nama_layanan,
                dp.ukuran AS ukuran,
                dp.warna AS warna,
                dp.catatan AS catatan,
                dp.harga_satuan AS harga_satuan
            FROM detail_pesanan dp
            LEFT JOIN layanan l ON dp.layanan_id = l.id
            WHERE dp.pesanan_id = ?
        ");
        $stmtItems->execute([$pesanan['id']]);
        
        // fetchAll() merangkum semua baris menjadi array
        $pesanan['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Bersihkan ID pesanan dari response JSON karena frontend tidak membutuhkannya
        unset($pesanan['id']);

        // Jika bukan metode jemput (tidak ada waktu_penjemputan), kosongkan alamat
        // agar struk tidak menampilkan alamat dari tabel pelanggan yang tidak relevan
        if (empty($pesanan['waktu_penjemputan'])) {
            $pesanan['alamat'] = null;
        }
        // Jika metode jemput tapi alamat kosong di DB, tandai dengan string khusus
        // agar frontend tetap bisa menampilkan baris Alamat (meski isinya '-')
        // Tidak perlu override di sini — biarkan alamat apa adanya dari DB

        // 3. Kirim response ke frontend
        jsonResponse(['success' => true, 'data' => $pesanan]);
        exit;
    }

    jsonResponse(['success' => false, 'message' => 'Aksi API tidak valid (Gunakan GET status atau POST create)'], 404);

} catch (Throwable $e) {
    jsonResponse([
        'success' => false, 
        'message' => 'Sistem API Error: ' . $e->getMessage()
    ], 500);
}
