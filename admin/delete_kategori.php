<?php
// Mulai session
session_start();
include("../config/auth.php");
// Koneksi DB
include("../config/koneksi_mysql.php");

// Validasi parameter ID
if (!isset($_GET['id']) || $_GET['id'] === '') {
    header("Location: master_kategori.php?msg=" . urlencode("Error: ID kategori tidak valid."));
    exit();
}

$id_kategori = (int) $_GET['id'];

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // 1. CEK: Apakah kategori ini punya "Anak" (Sub-Kategori)?
    $sql_cek_anak = "SELECT id_kategori FROM master_kategori WHERE parent_id = ?";
    $stmt_anak = mysqli_prepare($koneksi, $sql_cek_anak);
    mysqli_stmt_bind_param($stmt_anak, "i", $id_kategori);
    mysqli_stmt_execute($stmt_anak);
    mysqli_stmt_store_result($stmt_anak);
    
    if (mysqli_stmt_num_rows($stmt_anak) > 0) {
        throw new Exception("Kategori ini tidak bisa dihapus karena masih memiliki Sub-Kategori. Hapus dulu sub-kategorinya!");
    }
    mysqli_stmt_close($stmt_anak);

    // 2. Query DELETE
    $sql  = "DELETE FROM master_kategori WHERE id_kategori = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan query penghapusan.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_kategori);

    // Jika gagal eksekusi, kemungkinan besar karena Foreign Key (masih dipakai di master_menu / master_bb)
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus! Kategori ini kemungkinan masih digunakan oleh data Bahan Baku atau Menu.");
    }

    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data kategori tidak ditemukan.");
    }

    mysqli_stmt_close($stmt);

    // Commit transaksi
    mysqli_commit($koneksi);
    header("Location: master_kategori.php?msg=" . urlencode("Data kategori berhasil dihapus."));
    exit();

} catch (Throwable $e) {
    // Rollback jika gagal
    mysqli_rollback($koneksi);
    $msg = $e->getMessage();
    header("Location: master_kategori.php?msg=" . urlencode($msg));
exit();
}

mysqli_close($koneksi);

