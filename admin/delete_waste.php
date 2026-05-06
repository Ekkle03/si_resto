<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_header = mysqli_real_escape_string($koneksi, $_GET['id']);

    // Hapus data dari tabel header_waste
    // Berkat ON DELETE CASCADE di database, detail yang terkait juga otomatis terhapus
    $sql = "DELETE FROM header_waste WHERE id_header_waste = '$id_header'";
    
    if (mysqli_query($koneksi, $sql)) {
        $_SESSION['flash_msg'] = "Transaksi waste dibatalkan. Nota berhasil dihapus.";
    } else {
        $_SESSION['flash_msg'] = "Gagal membatalkan transaksi: " . mysqli_error($koneksi);
    }
}

// Kembalikan user ke halaman utama waste
header("Location: waste.php");
exit();
?>