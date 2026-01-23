<?php
// Mulai session di awal
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Validasi ID dari URL
if (!isset($_GET['id']) || $_GET['id'] === '' || !ctype_digit($_GET['id'])) {
    header("Location: master_satuan.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_satuan = (int) $_GET['id'];

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {

    // Query DELETE
    $sql  = "DELETE FROM master_satuan WHERE id_satuan = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement DELETE.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_satuan);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus data satuan. " . mysqli_stmt_error($stmt));
    }

    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data tidak ditemukan atau sudah dihapus.");
    }

    mysqli_stmt_close($stmt);

    // Commit
    mysqli_commit($koneksi);

    header("Location: master_satuan.php?msg=" . urlencode("Data satuan berhasil dihapus."));
    exit();

} catch (Throwable $e) {

    // Rollback jika ada error
    mysqli_rollback($koneksi);

    $msg = "Error: " . $e->getMessage();
    header("Location: master_satuan.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi (opsional setelah exit)
mysqli_close($koneksi);
