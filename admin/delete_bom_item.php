<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Pastikan parameter ID tersedia
if (!isset($_GET['id']) || !isset($_GET['back_id']) || !isset($_GET['back_tipe'])) {
    header("Location: master_bom.php?msg=Parameter hapus tidak lengkap");
    exit();
}

$id_bom = (int)$_GET['id'];
$back_id = (int)$_GET['back_id'];
$back_tipe = mysqli_real_escape_string($koneksi, $_GET['back_tipe']);

// 2. Proses Hapus Data di Tabel master_bom
$query_hapus = mysqli_query($koneksi, "DELETE FROM master_bom WHERE id_bom = '$id_bom'");

if ($query_hapus) {
    // 3. Cek apakah setelah dihapus masih ada sisa bahan untuk produk tersebut
    $cek_sisa = mysqli_query($koneksi, "SELECT id_bom FROM master_bom WHERE id_induk = '$back_id' AND tipe_bom = '$back_tipe'");
    
    if (mysqli_num_rows($cek_sisa) > 0) {
        // Jika masih ada sisa bahan, balik ke halaman Update
        header("Location: update_bom.php?id=$back_id&tipe=$back_tipe&msg=Bahan berhasil dihapus");
    } else {
        // Jika bahan habis total (kosong), lebih baik balik ke Master BOM
        header("Location: master_bom.php?msg=Resep dikosongkan karena tidak ada bahan tersisa");
    }
} else {
    // Jika gagal hapus
    header("Location: update_bom.php?id=$back_id&tipe=$back_tipe&msg=Gagal menghapus bahan");
}
exit();
?>