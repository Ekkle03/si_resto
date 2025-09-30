<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_kategori.php");
    exit();
}

// Ambil & bersihkan input
$nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';

// Validasi
if ($nama_kategori === '') {
    header("Location: master_kategori.php?msg=" . urlencode("Error: Nama kategori tidak boleh kosong."));
    exit();
}

// Query INSERT (prepared statement)
$sql = "INSERT INTO master_kategori (nama_kategori) VALUES (?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $nama_kategori);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data kategori berhasil ditambahkan.";
    } else {
        $msg = "Error: Gagal menambahkan data. " . mysqli_stmt_error($stmt);
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
