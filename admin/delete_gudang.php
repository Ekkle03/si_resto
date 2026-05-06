<?php
// Mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan parameter 'id' ada
if (isset($_GET['id'])) {

    // Paksa ID jadi integer agar aman dari SQL Injection
    $id_gudang = (int) $_GET['id'];

    if ($id_gudang <= 0) {
        $pesan_error = "ID gudang tidak valid.";
        header("Location: master_gudang.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // Query DELETE dengan prepared statement
    $sql  = "DELETE FROM master_gudang WHERE id_gudang = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        $pesan_error = "Terjadi kesalahan pada sistem: " . mysqli_error($koneksi);
        header("Location: master_gudang.php?msg=" . urlencode($pesan_error));
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $id_gudang);

    if (mysqli_stmt_execute($stmt)) {

        // Cek apakah ada baris yang benar-benar terhapus
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $pesan_sukses = "Data gudang berhasil dihapus.";
            header("Location: master_gudang.php?msg=" . urlencode($pesan_sukses));
            exit();
        } else {
            // ID tidak ditemukan di database
            $pesan_error = "Data tidak ditemukan.";
            header("Location: master_gudang.php?msg=" . urlencode($pesan_error));
            exit();
        }

    } else {
        // Error ini biasanya terjadi karena Constraint Foreign Key (Gudang sudah dipakai di transaksi/stok)
        $pesan_error = "Gagal menghapus data, gudang masih digunakan di tabel lain.";
        header("Location: master_gudang.php?msg=" . urlencode($pesan_error));
        exit();
    }

    mysqli_stmt_close($stmt);

} else {
    // Jika file diakses tanpa parameter ID
    header("Location: master_gudang.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>