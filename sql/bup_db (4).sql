-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Bulan Mei 2026 pada 02.56
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bup_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `created_at`) VALUES
(1, 'admin', 'admin123', 'Administrator BUP', '2026-05-04 06:27:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `layanan_id` int(11) NOT NULL,
  `merk_item` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `harga_satuan` int(11) NOT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `layanan_id`, `merk_item`, `jumlah`, `harga_satuan`, `catatan`) VALUES
(31, 26, 1, 'Nike', 1, 50000, ''),
(32, 27, 31, 'Nike', 1, 75000, ''),
(33, 27, 12, 'Nike', 1, 45000, NULL),
(34, 28, 1, 'Nike', 1, 50000, ''),
(35, 29, 1, 'apa', 1, 50000, ''),
(36, 30, 1, '-', 1, 50000, 'Yang bagus ya bang'),
(38, 32, 10, 'Nike', 1, 60000, ''),
(40, 34, 1, 'apa', 1, 50000, ''),
(41, 35, 1, 'Nike', 1, 50000, ''),
(42, 35, 1, 'Nike', 1, 50000, NULL),
(43, 36, 1, 'Nike', 1, 50000, ''),
(45, 38, 2, 'Nike', 1, 35000, ''),
(46, 39, 1, 'Nike', 1, 50000, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id` int(11) NOT NULL,
  `kode` varchar(10) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `jenis` varchar(100) NOT NULL,
  `harga` int(11) NOT NULL DEFAULT 0,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id`, `kode`, `kategori`, `jenis`, `harga`, `aktif`, `created_at`) VALUES
(1, 'SN-DC', 'Sneakers', 'Deep Clean', 50000, 1, '2026-04-10 10:41:57'),
(2, 'SN-SC', 'Sneakers', 'Standard Clean', 35000, 1, '2026-04-10 10:41:57'),
(3, 'SN-KD', 'Sneakers', 'Kids (max size 32)', 25000, 1, '2026-04-10 10:41:57'),
(4, 'BT-DC', 'Boots Shoes', 'Deep Clean', 65000, 1, '2026-04-10 10:41:57'),
(5, 'BT-SC', 'Boots Shoes', 'Standard Clean', 50000, 1, '2026-04-10 10:41:57'),
(6, 'BT-KD', 'Boots Shoes', 'Kids (max size 32)', 35000, 1, '2026-04-10 10:41:57'),
(7, 'OD-DC', 'Outdoor Shoes', 'Deep Clean', 70000, 1, '2026-04-10 10:41:57'),
(8, 'OD-SC', 'Outdoor Shoes', 'Standard Clean', 55000, 1, '2026-04-10 10:41:57'),
(9, 'OD-KD', 'Outdoor Shoes', 'Kids (max size 32)', 35000, 1, '2026-04-10 10:41:57'),
(10, 'LT-DC', 'Leather Shoes', 'Deep Clean', 60000, 1, '2026-04-10 10:41:57'),
(11, 'LT-SC', 'Leather Shoes', 'Standard Clean', 40000, 1, '2026-04-10 10:41:57'),
(12, 'WM-HL', 'Women Shoes', 'Heels', 45000, 1, '2026-04-10 10:41:57'),
(13, 'WM-FL', 'Women Shoes', 'Flat Shoes', 30000, 1, '2026-04-10 10:41:57'),
(14, 'BG-LG', 'Bag', 'Large', 80000, 1, '2026-04-10 10:41:57'),
(15, 'BG-MD', 'Bag', 'Medium', 70000, 1, '2026-04-10 10:41:57'),
(16, 'BG-SM', 'Bag', 'Small', 60000, 1, '2026-04-10 10:41:57'),
(17, 'BG-CR', 'Bag', 'Carrier (>30ltr)', 115000, 1, '2026-04-10 10:41:57'),
(18, 'BG-LL', 'Bag', 'Leather Large', 95000, 1, '2026-04-10 10:41:57'),
(19, 'BG-LM', 'Bag', 'Leather Medium', 80000, 1, '2026-04-10 10:41:57'),
(20, 'BG-LS', 'Bag', 'Leather Small', 70000, 1, '2026-04-10 10:41:57'),
(21, 'WL-LT', 'Wallet', 'Leather', 35000, 1, '2026-04-10 10:41:57'),
(22, 'WL-NL', 'Wallet', 'Non Leather', 25000, 1, '2026-04-10 10:41:57'),
(23, 'SD-MD', 'Sandals', 'Medium', 40000, 1, '2026-04-10 10:41:57'),
(24, 'SD-SM', 'Sandals', 'Small', 30000, 1, '2026-04-10 10:41:57'),
(25, 'HT-LG', 'Hat', 'Large', 45000, 1, '2026-04-10 10:41:57'),
(26, 'HT-MD', 'Hat', 'Medium', 35000, 1, '2026-04-10 10:41:57'),
(27, 'HT-SM', 'Hat', 'Small', 25000, 1, '2026-04-10 10:41:57'),
(28, 'RP-SD', 'Repaint', 'Suede Material', 135000, 1, '2026-04-10 10:41:57'),
(29, 'RP-CV', 'Repaint', 'Canvas Material', 120000, 1, '2026-04-10 10:41:57'),
(30, 'RP-HT', 'Repaint', 'Hat', 100000, 1, '2026-04-10 10:41:57'),
(31, 'UY-UP', 'Unyellowing', 'Upper', 75000, 1, '2026-04-10 10:41:57'),
(32, 'UY-MS', 'Unyellowing', 'Midsole', 70000, 1, '2026-04-10 10:41:57'),
(33, 'EX-DC', 'Extra Treatment', 'Deep Clean', 25000, 1, '2026-04-10 10:41:57'),
(34, 'EX-RC', 'Extra Treatment', 'Recolour', 35000, 1, '2026-04-10 10:41:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `metode_pengiriman`
--

CREATE TABLE `metode_pengiriman` (
  `id` int(11) NOT NULL,
  `nama_metode` varchar(50) NOT NULL,
  `biaya` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `metode_pengiriman`
--

INSERT INTO `metode_pengiriman` (`id`, `nama_metode`, `biaya`) VALUES
(1, 'Antar dan Ambil di Toko', 0),
(2, 'Antar dan Jemput di Rumah', 15000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pelanggan`
--

CREATE TABLE `pelanggan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_wa` varchar(20) NOT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pelanggan`
--

INSERT INTO `pelanggan` (`id`, `nama`, `no_wa`, `alamat`, `created_at`) VALUES
(1, 'Putra Aditya Hartanto', '085921136285', NULL, '2026-05-04 07:53:44'),
(2, 'Aditya Rayhanda Pramuji', '0876538276', NULL, '2026-05-04 12:08:16'),
(5, 'Aditya Rayhanda Pramuji', '09726715757', NULL, '2026-05-05 03:01:22'),
(7, 'ujang Zun Zhe', '0989796212', NULL, '2026-05-05 04:33:35'),
(8, 'ujang Zun Zhe', '08592113686', '', '2026-05-05 04:41:36'),
(9, 'Aditya Rayhanda Pramuji', '0808070969', NULL, '2026-05-05 07:47:44'),
(11, 'Putra Aditya Hartanto ', '098771218', 'Jalan', '2026-05-05 07:52:01'),
(12, 'Putra Aditya Hartanto ', '99790796', 'Jalan Salak', '2026-05-05 08:01:16'),
(14, 'ujang Zun Zhe', '080796857463', '', '2026-05-07 13:55:11'),
(15, 'Putra', '08070796986', '', '2026-05-07 14:20:11'),
(16, 'Ryzen', '0986468652', 'Jalan Pisang Raya', '2026-05-08 01:39:58'),
(17, 'Putra Aditya Hartanto ', '097526757', '', '2026-05-08 02:03:00'),
(18, 'ujang Zun Zhe', '09786857', '', '2026-05-08 02:07:14'),
(19, 'Rafly Romeo', '0876575652', NULL, '2026-05-08 02:20:01'),
(20, 'Arif', '08976541515', '', '2026-05-08 04:04:48'),
(21, 'Putra Aditya Hartanto ', '0989796989', '', '2026-05-08 11:52:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `kode_pesanan` varchar(20) NOT NULL,
  `pelanggan_id` int(11) NOT NULL,
  `tanggal_pesan` date NOT NULL,
  `metode_pengiriman_id` int(11) NOT NULL,
  `waktu_penjemputan` enum('09.00 - 13.00','13.00 - 17.00','17.00 - 20.00') DEFAULT NULL,
  `biaya_pengiriman` int(11) NOT NULL DEFAULT 0,
  `metode_bayar` enum('transfer_bca','tunai') NOT NULL DEFAULT 'transfer_bca',
  `total_harga` int(11) NOT NULL DEFAULT 0,
  `status_pesanan` enum('diterima','diproses','dicuci','dikeringkan','finishing','selesai') NOT NULL DEFAULT 'diterima',
  `status_bayar` enum('pending','confirmed','cash') NOT NULL DEFAULT 'pending',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id`, `kode_pesanan`, `pelanggan_id`, `tanggal_pesan`, `metode_pengiriman_id`, `waktu_penjemputan`, `biaya_pengiriman`, `metode_bayar`, `total_harga`, `status_pesanan`, `status_bayar`, `bukti_bayar`, `created_at`, `updated_at`) VALUES
(26, 'BUP-0001', 1, '2026-05-08', 1, NULL, 0, 'transfer_bca', 50000, 'diproses', 'confirmed', '1778206542_69fd474e0c49f.png', '2026-05-08 02:15:42', '2026-05-08 03:58:22'),
(27, 'BUP-0002', 2, '2026-05-08', 1, NULL, 0, 'tunai', 120000, 'diterima', 'cash', NULL, '2026-05-08 02:17:06', '2026-05-08 02:17:06'),
(28, 'BUP-0003', 19, '2026-05-08', 1, NULL, 0, 'transfer_bca', 50000, 'diterima', 'confirmed', '1778206801_69fd48512bd2d.png', '2026-05-08 02:20:01', '2026-05-08 06:29:23'),
(29, 'BUP-0004', 7, '2026-05-08', 1, NULL, 0, 'tunai', 50000, 'diterima', 'cash', NULL, '2026-05-08 02:40:02', '2026-05-08 02:40:02'),
(30, 'BUP-0005', 9, '2026-05-08', 1, NULL, 0, 'tunai', 50000, 'diterima', 'cash', NULL, '2026-05-08 04:01:22', '2026-05-08 04:01:22'),
(32, 'BUP-0007', 2, '2026-05-08', 1, NULL, 0, 'transfer_bca', 60000, 'diterima', 'confirmed', '1778239197_69fdc6dd4c599.png', '2026-05-08 11:19:57', '2026-05-09 14:39:47'),
(34, 'BUP-0009', 1, '2026-05-09', 1, NULL, 0, 'tunai', 50000, 'diterima', 'cash', NULL, '2026-05-09 12:50:05', '2026-05-09 12:50:05'),
(35, 'BUP-0010', 1, '2026-05-09', 2, NULL, 15000, 'transfer_bca', 115000, 'diterima', 'confirmed', '1778332740_69ff3444b446c.png', '2026-05-09 13:19:00', '2026-05-09 13:29:50'),
(36, 'BUP-0011', 1, '2026-05-09', 1, NULL, 0, 'tunai', 50000, 'diterima', 'cash', NULL, '2026-05-09 13:28:20', '2026-05-09 13:28:20'),
(38, 'BUP-0012', 1, '2026-05-09', 1, NULL, 0, 'tunai', 35000, 'diterima', 'cash', NULL, '2026-05-09 14:38:42', '2026-05-09 14:38:42'),
(39, 'BUP-0013', 1, '2026-05-10', 1, NULL, 0, 'tunai', 50000, 'diterima', 'cash', NULL, '2026-05-10 12:19:15', '2026-05-10 12:19:15');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `layanan_id` (`layanan_id`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indeks untuk tabel `metode_pengiriman`
--
ALTER TABLE `metode_pengiriman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_wa` (`no_wa`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pesanan` (`kode_pesanan`),
  ADD KEY `pelanggan_id` (`pelanggan_id`),
  ADD KEY `metode_pengiriman_id` (`metode_pengiriman_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT untuk tabel `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `metode_pengiriman`
--
ALTER TABLE `metode_pengiriman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `pelanggan`
--
ALTER TABLE `pelanggan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`);

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`pelanggan_id`) REFERENCES `pelanggan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`metode_pengiriman_id`) REFERENCES `metode_pengiriman` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
