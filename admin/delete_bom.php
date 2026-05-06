<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Pastikan parameter id dan tipe ada di URL
if (isset($_GET['id']) && isset($_GET['tipe'])) {
    
    // 1. Tangkap data dari URL
    $id_induk = (int)$_GET['id'];
    $tipe_bom = mysqli_real_escape_string($koneksi, $_GET['tipe']);

    // 2. Query Hapus (Hanya menghapus isi Resep/BOM di tabel master_bom)
    $query_delete = "DELETE FROM master_bom 
                     WHERE id_induk = '$id_induk' 
                     AND tipe_bom = '$tipe_bom'";

    if (mysqli_query($koneksi, $query_delete)) {
        // Jika sukses, buat pesan sukses
        $msg = "Seluruh bahan resep berhasil dihapus.";
    } else {
        // Jika gagal, tampilkan errornya
        $msg = "Gagal menghapus resep: " . mysqli_error($koneksi);
    }

} else {
    $msg = "Gagal: Parameter tidak ditemukan.";
}

// 3. REVISI: Kembali ke Master BOM (Bukan Master Menu/BSJ)
header("Location: master_bom.php?msg=" . urlencode($msg));
exit();