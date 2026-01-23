<?php
session_start();
include("../config/koneksi_mysql.php");

// Hanya boleh POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_kategori.php");
    exit();
}

// 1. Ambil input Nama Kategori
$nama_kategori = isset($_POST['nama_kategori']) ? trim($_POST['nama_kategori']) : '';

// 2. Ambil input parent_id (Ini yang paling penting!)
// Jika tidak ada di post, otomatis jadi 0 (Kategori Utama)
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

if ($nama_kategori === '') {
    header("Location: master_kategori.php?msg=" . urlencode("Error: Nama kategori tidak boleh kosong."));
    exit();
}

// =========== Generate kode kategori otomatis ===========
$sql_max = "SELECT MAX(id_kategori) AS max_id FROM master_kategori";
$res = mysqli_query($koneksi, $sql_max);
$row = mysqli_fetch_assoc($res);

$next_id = (int)$row['max_id'] + 1;
$kode_kategori = "KAT-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);
// =======================================================

// 3. PERBAIKAN INSERT: Tambahkan kolom parent_id dan satu placeholder (?) lagi
$sql = "INSERT INTO master_kategori (kode_kategori, nama_kategori, parent_id) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if (!$stmt) {
    $msg = "Error: Gagal menyiapkan statement.";
} else {
    // 4. BIND PARAM: Tambahkan "i" (integer) untuk parent_id di akhir
    mysqli_stmt_bind_param($stmt, "ssi", $kode_kategori, $nama_kategori, $parent_id);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data kategori berhasil ditambahkan.";
    } else {
        $msg = "Error: Gagal menambahkan data. " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
}

header("Location: master_kategori.php?msg=" . urlencode($msg));
exit();
?>