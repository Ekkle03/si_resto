<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_GET['id'])) {
    $id_produksi = (int)$_GET['id'];
    
    // Tangkap kode rahasia dari URL untuk tahu dia datang dari halaman mana
    // 1 = Produksi Berjenjang, 2 = Produksi Langsung
    $jenis = isset($_GET['jenis']) ? $_GET['jenis'] : '1'; 

    // Tentukan halaman kembalinya
    $halaman_kembali = ($jenis == '2') ? "produksi_langsung.php" : "produksi.php";

    // Validasi: Pastikan yang dihapus statusnya masih "Rencana"
    $cek_status = mysqli_query($koneksi, "SELECT status FROM produksi WHERE id_produksi = '$id_produksi'");
    $d_status = mysqli_fetch_assoc($cek_status);

    if ($d_status && $d_status['status'] == 'Rencana') {
        // Aman, langsung hajar DELETE
        $q_delete = mysqli_query($koneksi, "DELETE FROM produksi WHERE id_produksi = '$id_produksi'");

        if ($q_delete) {
            $_SESSION['flash_msg'] = "Sukses! Rencana produksi berhasil dibatalkan dan dihapus.";
        } else {
            $_SESSION['flash_msg'] = "Gagal menghapus rencana produksi: " . mysqli_error($koneksi);
        }
    } else {
        $_SESSION['flash_msg'] = "Gagal! Data tidak ditemukan atau produksi sudah terlanjur selesai diproses.";
    }

    // Arahkan kembali ke halaman yang tepat
    header("Location: " . $halaman_kembali);
    exit();
} else {
    // Kalau ada yang iseng akses tanpa ID, lempar ke dashboard
    header("Location: ../dashboard.php");
    exit();
}
?>