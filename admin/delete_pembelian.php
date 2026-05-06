<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Cek apakah ada ID yang dikirim melalui URL
if (isset($_GET['id'])) {
    $id_pembelian = (int)$_GET['id'];

    // 1. Ambil kode pembelian dulu untuk keperluan pesan notifikasi
    $q_info = mysqli_query($koneksi, "SELECT kode_pembelian FROM pembelian WHERE id_pembelian = '$id_pembelian'");
    $d_info = mysqli_fetch_assoc($q_info);
    $kode = $d_info['kode_pembelian'] ?? 'Tidak Diketahui';

    // 2. Jalankan query hapus
    // Karena foreign key sudah ON DELETE CASCADE, detail_pembelian otomatis ikut terhapus
    $delete = mysqli_query($koneksi, "DELETE FROM pembelian WHERE id_pembelian = '$id_pembelian'");

    if ($delete) {
        $_SESSION['flash_msg'] = "Data Pembelian <b>$kode</b> berhasil dihapus.";
    } else {
        $_SESSION['flash_msg'] = "Gagal menghapus data: " . mysqli_error($koneksi);
    }
} else {
    $_SESSION['flash_msg'] = "ID Pembelian tidak ditemukan.";
}

// Kembalikan ke halaman daftar pembelian
header("Location: pembelian.php");
exit();
?>