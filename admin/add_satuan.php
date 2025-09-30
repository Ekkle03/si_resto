<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_satuan.php");
    exit();
}

// Ambil & bersihkan input
$nama_satuan = isset($_POST['nama_satuan']) ? trim($_POST['nama_satuan']) : '';

// Validasi
if ($nama_satuan === '') {
    header("Location: master_satuan.php?msg=" . urlencode("Error: Nama satuan tidak boleh kosong."));
    exit();
}

// Query INSERT
$sql = "INSERT INTO master_satuan (nama_satuan) VALUES (?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $nama_satuan);
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data satuan berhasil ditambahkan.";
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
header("Location: master_satuan.php?msg=" . urlencode($msg));
exit();
