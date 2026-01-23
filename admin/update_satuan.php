<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_satuan.php");
    exit();
}

// Ambil & bersihkan input
$id_satuan   = isset($_POST['id_satuan']) ? trim($_POST['id_satuan']) : '';
$nama_satuan = isset($_POST['nama_satuan']) ? trim($_POST['nama_satuan']) : '';

// Validasi dasar ID
if ($id_satuan === '' || !ctype_digit($id_satuan)) {
    header("Location: master_satuan.php?msg=" . urlencode("Error: ID satuan tidak valid."));
    exit();
}
$id = (int) $id_satuan;
if ($id <= 0) {
    header("Location: master_satuan.php?msg=" . urlencode("Error: ID satuan tidak valid."));
    exit();
}

// Validasi nama
if ($nama_satuan === '') {
    header("Location: master_satuan.php?msg=" . urlencode("Error: Nama satuan tidak boleh kosong."));
    exit();
}

// Siapkan query UPDATE (hanya ubah nama_satuan, kode_satuan tetap)
$sql  = "UPDATE master_satuan SET nama_satuan = ? WHERE id_satuan = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    $msg = "Error: Gagal menyiapkan statement.";
    mysqli_close($koneksi);
    header("Location: master_satuan.php?msg=" . urlencode($msg));
    exit();
}

// Bind parameter dan eksekusi
mysqli_stmt_bind_param($stmt, "si", $nama_satuan, $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $msg = "Data satuan berhasil diupdate.";
    } else {
        // Tidak ada baris berubah (misal nama sama persis)
        $msg = "Tidak ada perubahan data pada satuan.";
    }
} else {
    $msg = "Error: Gagal mengupdate data. " . mysqli_stmt_error($stmt);
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);

// Redirect dengan pesan
header("Location: master_satuan.php?msg=" . urlencode($msg));
exit();
