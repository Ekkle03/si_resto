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
if ($id_kategori === '' || !ctype_digit($id_kategori)) {
    header("Location: master_kategori.php?msg=" . urlencode("Error: ID tidak valid."));
    exit();
}
if ($nama_kategori === '') {
    header("Location: master_kategori.php?msg=" . urlencode("Error: Nama kategori tidak boleh kosong."));
    exit();
}

$id = (int)$id_kategori;

// Siapkan query UPDATE (prepared statement)
$sql = "UPDATE master_kategori SET nama_kategori = ? WHERE id_kategori = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $nama_kategori, $id);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Data kategori berhasil diupdate.";
        } else {
            $msg = "Tidak ada perubahan data.";
        }
    } else {
        $msg = "Error: Gagal mengupdate data. " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan statement.";
}

// Tutup koneksi
mysqli_close($koneksi);

// Redirect dengan pesan
header("Location: master_kategori.php?msg=" . urlencode($msg));
exit();
