<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Proteksi Halaman: Pastikan hanya yang login bisa hapus
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// 2. Tangkap dan Amankan ID
$id = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : '';

if (!empty($id)) {
    // 3. Ambil Kode Request dulu buat pesan notifikasi
    $cek_data = mysqli_query($koneksi, "SELECT kode_request FROM header_request WHERE id_header_req = '$id'");
    
    if (mysqli_num_rows($cek_data) > 0) {
        $data = mysqli_fetch_assoc($cek_data);
        $kode = $data['kode_request'];

        // 4. Proses Hapus
        // Kita hapus detail dulu baru header (untuk jaga-jaga jika FK tidak di-set CASCADE)
        mysqli_query($koneksi, "DELETE FROM request_bahan WHERE id_header_req = '$id'");
        $hapus = mysqli_query($koneksi, "DELETE FROM header_request WHERE id_header_req = '$id'");

        if ($hapus) {
            // Hapus juga keranjang session jika ada agar bersih
            unset($_SESSION['keranjang']);
            
            header("Location: permintaan_bahan.php?status=success&msg=" . urlencode("Permintaan $kode berhasil dihapus!"));
        } else {
            header("Location: permintaan_bahan.php?status=error&msg=" . urlencode("Gagal menghapus data: " . mysqli_error($koneksi)));
        }
    } else {
        header("Location: permintaan_bahan.php?status=error&msg=" . urlencode("Data tidak ditemukan!"));
    }
} else {
    header("Location: permintaan_bahan.php");
}
exit();
?>