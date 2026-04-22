<?php
session_start();
include("../config/koneksi_mysql.php");

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}

$id_bsj = (int) $_GET['id'];
mysqli_begin_transaction($koneksi);

try {
    // 1. CEK RELASI (Apakah dia jadi BAHAN di BOM lain?)
    // Cek apakah BSJ ini digunakan sebagai komponen di resep Menu atau resep BSJ lain
    $sql_cek = "SELECT b.id_bom, m.nama_menu, bsj.nama_bsj as nama_induk_bsj 
                FROM master_bom b
                LEFT JOIN master_menu m ON b.id_induk = m.id_menu AND b.tipe_bom = 'MENU'
                LEFT JOIN master_bahan_setengah_jadi bsj ON b.id_induk = bsj.id_bsj AND b.tipe_bom = 'BSJ'
                WHERE b.id_bsj = ? LIMIT 1";
    
    $stmt_cek = mysqli_prepare($koneksi, $sql_cek);
    mysqli_stmt_bind_param($stmt_cek, "i", $id_bsj);
    mysqli_stmt_execute($stmt_cek);
    $res_cek = mysqli_stmt_get_result($stmt_cek);
    
    if ($row = mysqli_fetch_assoc($res_cek)) {
        // Biar lebih keren buat skripsi, kasih tahu dia dipakai di mana
        $dipakai_di = $row['nama_menu'] ?? $row['nama_induk_bsj'] ?? "Resep Lain";
        throw new Exception("Bahan ini sedang digunakan di resep [$dipakai_di]. Hapus resep tersebut terlebih dahulu!");
    }
    mysqli_stmt_close($stmt_cek);

    // 2. HAPUS DATA RESEP (BOM) MILIK BSJ INI
    // Menghapus baris-baris bahan penyusun BSJ ini
    $sql_del_bom = "DELETE FROM master_bom WHERE id_induk = ? AND tipe_bom = 'BSJ'";
    $stmt_bom = mysqli_prepare($koneksi, $sql_del_bom);
    mysqli_stmt_bind_param($stmt_bom, "i", $id_bsj);
    mysqli_stmt_execute($stmt_bom);
    mysqli_stmt_close($stmt_bom);

    // 3. HAPUS DATA UTAMA BSJ
    $sql_del_bsj = "DELETE FROM master_bahan_setengah_jadi WHERE id_bsj = ?";
    $stmt_bsj = mysqli_prepare($koneksi, $sql_del_bsj);
    mysqli_stmt_bind_param($stmt_bsj, "i", $id_bsj);
    mysqli_stmt_execute($stmt_bsj);

    if (mysqli_stmt_affected_rows($stmt_bsj) <= 0) {
        throw new Exception("Gagal menghapus! Data mungkin sudah tidak ada.");
    }

    mysqli_stmt_close($stmt_bsj);
    mysqli_commit($koneksi);
    $msg = "Berhasil: Data bahan setengah jadi dan resep terkait telah dihapus.";

} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    $msg = "Error: " . $e->getMessage();
}

mysqli_close($koneksi);
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();