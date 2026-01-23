<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahanbaku.php");
    exit();
}

// Ambil & bersihkan input
$id_bb       = isset($_POST['id_bb']) ? trim($_POST['id_bb']) : '';
$nama_bb     = isset($_POST['nama_bb']) ? trim($_POST['nama_bb']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? trim($_POST['id_satuan']) : '';
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';
$tipe_bahan  = isset($_POST['tipe_bahan']) ? trim($_POST['tipe_bahan']) : '';

// Validasi dasar
if ($id_bb === '' || $nama_bb === '' || $id_satuan === '' || $id_kategori === '') {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Field wajib diisi."));
    exit();
}

$id_bb       = (int)$id_bb;
$id_satuan   = (int)$id_satuan;
$id_kategori = (int)$id_kategori;

// Query UPDATE (Kode BB tidak diupdate agar konsisten)
$sql = "UPDATE master_bahan_baku 
        SET nama_bb = ?, id_satuan = ?, id_kategori = ?, tipe_bahan = ?
        WHERE id_bb = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "siisi", $nama_bb, $id_satuan, $id_kategori, $tipe_bahan, $id_bb);

    if (mysqli_stmt_execute($stmt)) {
        // REVISI LOGIKA: Walaupun affected_rows 0 (data tidak ada yg diubah), 
        // kita tetap beri pesan sukses agar user tidak bingung.
        $msg = "Data bahan baku berhasil diperbarui.";
    } else {
        $msg = "Error: Gagal mengupdate data. " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan query.";
}

mysqli_close($koneksi);
header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
exit();