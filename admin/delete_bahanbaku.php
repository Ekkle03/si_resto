<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (!isset($_GET['id']) || $_GET['id'] === '' || !ctype_digit($_GET['id'])) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_bb = (int) $_GET['id'];

mysqli_begin_transaction($koneksi);

try {
    // 1. CEK: Apakah Bahan Baku ini dipakai di Master BOM?
    // Sesuai struktur tabelmu, kita cek di kolom id_bb
    $sql_cek_bom = "SELECT id_bom FROM master_bom WHERE id_bb = ?";
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek_bom);
    mysqli_stmt_bind_param($stmt_cek, "i", $id_bb);
    mysqli_stmt_execute($stmt_cek);
    mysqli_stmt_store_result($stmt_cek);
    
    if (mysqli_stmt_num_rows($stmt_cek) > 0) {
        throw new Exception("Bahan Baku tidak bisa dihapus karena sudah terdaftar dalam resep. Hapus dulu data resepnya!");
    }
    mysqli_stmt_close($stmt_cek);

    // 2. CEK: Apakah Bahan Baku ini sudah punya histori stok (pembelian)?
    // (Opsional, tapi bagus buat skripsi kalau ada tabel transaksi_pembelian/kartu_stok)

    // 3. Eksekusi DELETE
    $sql = "DELETE FROM master_bahan_baku WHERE id_bb = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        throw new Exception("Gagal menyiapkan statement.");
    }

    mysqli_stmt_bind_param($stmt, "i", $id_bb);

    if (!mysqli_stmt_execute($stmt)) {
        // Jika error di sini biasanya karena relasi database (Foreign Key Constraint)
        throw new Exception("Gagal menghapus! Data ini kemungkinan masih terhubung dengan data transaksi lain.");
    }

    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data tidak ditemukan.");
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($koneksi);

    $msg = "Data bahan baku berhasil dihapus.";
    header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
    exit();

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    $msg = $e->getMessage();
    header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
    exit();
}

mysqli_close($koneksi);