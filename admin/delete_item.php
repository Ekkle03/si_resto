<?php
// Mulai session di awal
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Pastikan ada parameter id yang valid
if (!isset($_GET['id']) || $_GET['id'] === '' || !ctype_digit($_GET['id'])) {
    header("Location: master_item.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_item = (int) $_GET['id'];

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // (Opsional) Cek keberadaan data dulu
    $cek = mysqli_prepare($koneksi, "SELECT 1 FROM master_item WHERE id_item = ? LIMIT 1");
    if (!$cek) throw new Exception("Gagal menyiapkan pengecekan data.");
    mysqli_stmt_bind_param($cek, "i", $id_item);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);
    if (mysqli_stmt_num_rows($cek) === 0) {
        mysqli_stmt_close($cek);
        throw new Exception("Data item tidak ditemukan.");
    }
    mysqli_stmt_close($cek);

    // Hapus data item
    $sql  = "DELETE FROM master_item WHERE id_item = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) throw new Exception("Gagal menyiapkan statement delete.");

    mysqli_stmt_bind_param($stmt, "i", $id_item);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus data. " . mysqli_stmt_error($stmt));
    }
    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        mysqli_stmt_close($stmt);
        throw new Exception("Data tidak ditemukan atau sudah dihapus.");
    }
    mysqli_stmt_close($stmt);

    // Commit jika sukses
    mysqli_commit($koneksi);

    $msg = "Data item berhasil dihapus.";
    header("Location: master_item.php?msg=" . urlencode($msg));
    exit();

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    $msg = "Error: " . $e->getMessage();

    // Jika ada constraint (FK) yang menahan (misal dipakai di resep/stock), pesan lebih ramah:
    if (strpos($msg, 'foreign key') !== false || strpos(strtolower($msg), 'constraint') !== false) {
        $msg = "Error: Item masih digunakan pada data lain, tidak dapat dihapus.";
    }

    header("Location: master_item.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
