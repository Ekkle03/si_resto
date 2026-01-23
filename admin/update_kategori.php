<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_kategori.php");
    exit();
}

// Ambil & bersihkan input
$id_kategori   = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';
$nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';

// Validasi dasar
if ($id_kategori === '' || $nama_kategori === '') {
    header("Location: master_kategori.php?msg=" . urlencode("Error: Data tidak lengkap."));
    exit();
}

$id = (int) $id_kategori;

// Siapkan query UPDATE
$sql  = "UPDATE master_kategori SET nama_kategori = ? WHERE id_kategori = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    $msg = "Error: Gagal menyiapkan statement.";
} else {
    mysqli_stmt_bind_param($stmt, "si", $nama_kategori, $id);

    if (mysqli_stmt_execute($stmt)) {
        // PERBAIKAN LOGIKA: 
        // Kita anggap berhasil selama query tidak error, 
        // meskipun tidak ada teks yang diubah (affected_rows = 0)
        $msg = "Data kategori berhasil diupdate.";
    } else {
        $msg = "Error: Gagal mengupdate data. " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
}

header("Location: master_kategori.php?msg=" . urlencode($msg));
exit();
?>