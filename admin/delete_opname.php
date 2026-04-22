<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Proteksi Halaman: Pastikan hanya user yang login yang bisa akses
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Tangkap dan Amankan ID
// Menggunakan mysqli_real_escape_string untuk mencegah SQL Injection
$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (!empty($id)) {
    // 3. Cek apakah data memang ada di database sebelum dihapus
    $cek_data = mysqli_query($koneksi, "SELECT kode_opname FROM header_opname WHERE id_header_opname = '$id'");
    
    if (mysqli_num_rows($cek_data) > 0) {
        $data = mysqli_fetch_assoc($cek_data);
        $kode = $data['kode_opname'];

        // 4. Proses Hapus
        // Database kamu sudah pakai ON DELETE CASCADE, jadi detail_opname otomatis ludes
        $sql = "DELETE FROM header_opname WHERE id_header_opname = '$id'";
        
        if (mysqli_query($koneksi, $sql)) {
            // Berhasil: Balik dengan pesan spesifik
            header("Location: stok_opname.php?status=success&msg=" . urlencode("Transaksi $kode telah berhasil dihapus."));
        } else {
            // Gagal: Berikan info error SQL
            header("Location: stok_opname.php?status=error&msg=" . urlencode("Gagal menghapus database: " . mysqli_error($koneksi)));
        }
    } else {
        // Data tidak ditemukan
        header("Location: stok_opname.php?status=error&msg=" . urlencode("Data tidak ditemukan atau sudah dihapus sebelumnya."));
    }
} else {
    // ID Kosong
    header("Location: stok_opname.php");
}
exit();
?>