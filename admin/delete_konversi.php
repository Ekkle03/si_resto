<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Pastikan ada parameter ID yang dikirim lewat URL
if (isset($_GET['id'])) {
    $id_konversi = $_GET['id'];

    // Query untuk menghapus data konversi
    $query = "DELETE FROM master_konversi WHERE id_konversi = '$id_konversi'";

    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, balikkan ke halaman utama dengan pesan sukses
        header("Location: konversi_satuan.php?msg=Rumus konversi berhasil dihapus!");
    } else {
        // Jika gagal karena relasi database (misal data sedang dipakai di transaksi)
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    // Jika tidak ada ID, langsung balikkan saja
    header("Location: konversi_satuan.php");
}
?>