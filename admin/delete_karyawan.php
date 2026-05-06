<?php
// Mulai session
session_start();
include("../config/auth.php");
// Koneksi DB
include("../config/koneksi_mysql.php");

// ===== Validasi ID =====
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}
$id_karyawan = (int) $_GET['id'];

// ===== Folder foto =====
$upload_dir_fs = __DIR__ . '/assets/img/profil';
if (!is_dir($upload_dir_fs)) {
    // fallback jika folder hilang
    @mkdir($upload_dir_fs, 0755, true);
}

// ===== Ambil foto profil sebelum delete =====
$sql_f  = "SELECT foto_profil FROM master_karyawan WHERE id_karyawan = ?";
$stmt_f = mysqli_prepare($koneksi, $sql_f);

if (!$stmt_f) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Query ambil foto gagal."));
    exit();
}

mysqli_stmt_bind_param($stmt_f, "i", $id_karyawan);
mysqli_stmt_execute($stmt_f);
mysqli_stmt_bind_result($stmt_f, $foto_profil);
$found = mysqli_stmt_fetch($stmt_f);
mysqli_stmt_close($stmt_f);

if (!$found) {
    header("Location: master_karyawan.php?msg=" . urlencode("Error: Data karyawan tidak ditemukan."));
    exit();
}

// ===== DELETE dalam transaksi =====
mysqli_begin_transaction($koneksi);

try {
    $sql  = "DELETE FROM master_karyawan WHERE id_karyawan = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) throw new Exception("Gagal menyiapkan query hapus.");

    mysqli_stmt_bind_param($stmt, "i", $id_karyawan);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus data karyawan. Mungkin masih dipakai tabel lain.");
    }

    if (mysqli_stmt_affected_rows($stmt) <= 0) {
        throw new Exception("Data tidak ditemukan atau sudah terhapus.");
    }

    mysqli_stmt_close($stmt);
    mysqli_commit($koneksi);

    // ===== Hapus foto jika bukan default =====
    $foto = basename($foto_profil ?: 'default.png');
    if ($foto !== 'default.png') {
        $path = $upload_dir_fs . '/' . $foto;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    header("Location: master_karyawan.php?msg=" . urlencode("Data karyawan berhasil dihapus."));
    exit();

} catch (Throwable $e) {

    mysqli_rollback($koneksi);
    $msg = "Error: " . $e->getMessage();

    header("Location: master_karyawan.php?msg=" . urlencode($msg));
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
