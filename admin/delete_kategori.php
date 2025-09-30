<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan ada parameter id yang valid
if (!isset($_GET['id']) || $_GET['id'] === '' || !ctype_digit($_GET['id'])) {
    header("Location: master_kategori.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_kategori = (int) $_GET['id'];

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // Siapkan query DELETE
    $sql = "DELETE FROM master_kategori WHERE id_kategori = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_kategori);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus data. " . mysqli_stmt_error($stmt));
    }

    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data tidak ditemukan atau sudah dihapus.");
    }

    mysqli_stmt_close($stmt);

    // Commit jika sukses
    mysqli_commit($koneksi);

    $msg = "Data kategori berhasil dihapus.";
    header("Location: master_kategori.php?msg=" . urlencode($msg));
    exit();

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    $msg = "Error: " . $e->getMessage();
    header("Location: master_kategori.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
