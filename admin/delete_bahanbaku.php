<?php
// Mulai session
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Validasi parameter ID
if (!isset($_GET['id']) || $_GET['id'] === '' || !ctype_digit($_GET['id'])) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_bb = (int) $_GET['id'];

// Mulai transaksi untuk keamanan
mysqli_begin_transaction($koneksi);

try {
    // Siapkan query DELETE
    $sql = "DELETE FROM master_bahan_baku WHERE id_bb = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_bb);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus data. " . mysqli_stmt_error($stmt));
    }

    // Jika tidak ada baris berubah
    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data tidak ditemukan atau sudah dihapus.");
    }

    mysqli_stmt_close($stmt);

    // Commit transaksi
    mysqli_commit($koneksi);

    $msg = "Data bahan baku berhasil dihapus.";
    header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
    exit();

} catch (Throwable $e) {
    // Rollback jika terjadi error
    mysqli_rollback($koneksi);

    $msg = "Error: " . $e->getMessage();
    header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
