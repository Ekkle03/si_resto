<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan parameter 'id' ada
if (isset($_GET['id'])) {

    // Paksa ID jadi integer (lebih aman)
    $id_divisi = (int) $_GET['id'];

    if ($id_divisi <= 0) {
        $pesan_error = "ID divisi tidak valid.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // Query DELETE dengan prepared statement
    $sql  = "DELETE FROM master_divisi WHERE id_divisi = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        $pesan_error = "Terjadi kesalahan pada sistem: " . mysqli_error($koneksi);
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $id_divisi);

    if (mysqli_stmt_execute($stmt)) {

        // Cek apakah ada baris yang terhapus
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $pesan_sukses = "Data divisi berhasil dihapus.";
            header("Location: master_divisi.php?msg=" . urlencode($pesan_sukses));
            exit();
        } else {
            // ID tidak ditemukan
            $pesan_error = "Data tidak ditemukan.";
            header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
            exit();
        }

    } else {
        // Biasanya error karena foreign key (divisi dipakai di data karyawan)
        $pesan_error = "Gagal menghapus data, divisi masih digunakan di tabel lain.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    mysqli_stmt_close($stmt);

} else {
    // Jika tidak ada ID sama sekali
    header("Location: master_divisi.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>
