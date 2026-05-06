<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Pastikan ada ID yang dikirim lewat URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: master_menu.php?msg=" . urlencode("Error: ID tidak ditemukan."));
    exit();
}

$id = (int)$_GET['id'];

// 2. Mulai proses penghapusan
// Kita hapus dulu "anak-anaknya" (BOM) agar tidak melanggar relasi database
$query_bom = "DELETE FROM master_bom WHERE id_induk = '$id' AND tipe_bom = 'MENU'";
$hapus_bom = mysqli_query($koneksi, $query_bom);

if ($hapus_bom) {
    // 3. Setelah resep bersih, baru hapus "Induknya" (Menu)
    $query_menu = "DELETE FROM master_menu WHERE id_menu = '$id'";
    $hapus_menu = mysqli_query($koneksi, $query_menu);

    if ($hapus_menu) {
        $msg = "Menu dan data resep terkait berhasil dihapus permanen!";
    } else {
        $msg = "Error: Gagal menghapus menu. " . mysqli_error($koneksi);
    }
} else {
    $msg = "Error: Gagal membersihkan data resep. " . mysqli_error($koneksi);
}

// 4. Tutup koneksi dan balik ke halaman utama
mysqli_close($koneksi);
header("Location: master_menu.php?msg=" . urlencode($msg));
exit();