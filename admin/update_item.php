<?php
// Mulai session di awal
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_item.php");
    exit();
}

// Ambil & bersihkan input
$id_item     = isset($_POST['id_item'])     ? trim($_POST['id_item']) : '';
$nama_item   = isset($_POST['nama_item'])   ? trim($_POST['nama_item']) : '';
$jenis_item  = isset($_POST['jenis_item'])  ? strtoupper(trim($_POST['jenis_item'])) : '';
$harga_beli  = isset($_POST['harga_beli'])  ? trim($_POST['harga_beli']) : '';
$id_satuan   = isset($_POST['id_satuan'])   ? trim($_POST['id_satuan']) : '';
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';

// ===== Validasi dasar =====
if ($id_item === '' || !ctype_digit($id_item) || (int)$id_item <= 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: ID item tidak valid."));
    exit();
}
if ($nama_item === '') {
    header("Location: master_item.php?msg=" . urlencode("Error: Nama item wajib diisi."));
    exit();
}
if (!in_array($jenis_item, ['RAW','PREP'], true)) {
    header("Location: master_item.php?msg=" . urlencode("Error: Jenis item tidak valid (RAW/PREP)."));
    exit();
}
if ($harga_beli === '' || !is_numeric($harga_beli) || (float)$harga_beli < 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: Harga beli tidak valid."));
    exit();
}
if ($id_satuan === '' || !ctype_digit($id_satuan) || (int)$id_satuan <= 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: Satuan tidak valid."));
    exit();
}
if ($id_kategori === '' || !ctype_digit($id_kategori) || (int)$id_kategori <= 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: Kategori tidak valid."));
    exit();
}

$id_item     = (int)$id_item;
$id_satuan   = (int)$id_satuan;
$id_kategori = (int)$id_kategori;

// Normalisasi harga ke float + format desimal MySQL
$harga_num = (float)$harga_beli; // DECIMAL(15,2) aman di-bind sebagai double

// (Opsional) Cek FK ada
$cek1 = mysqli_query($koneksi, "SELECT 1 FROM master_satuan   WHERE id_satuan   = $id_satuan   LIMIT 1");
$cek2 = mysqli_query($koneksi, "SELECT 1 FROM master_kategori WHERE id_kategori = $id_kategori LIMIT 1");
if (!$cek1 || mysqli_num_rows($cek1) === 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: Satuan tidak ditemukan."));
    exit();
}
if (!$cek2 || mysqli_num_rows($cek2) === 0) {
    header("Location: master_item.php?msg=" . urlencode("Error: Kategori tidak ditemukan."));
    exit();
}

// Siapkan query UPDATE
$sql = "UPDATE master_item
        SET nama_item = ?, jenis_item = ?, harga_beli = ?, id_satuan = ?, id_kategori = ?
        WHERE id_item = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // ssdiii: string, string, double, int, int, int
    mysqli_stmt_bind_param($stmt, "ssdiii",
        $nama_item,
        $jenis_item,
        $harga_num,
        $id_satuan,
        $id_kategori,
        $id_item
    );

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Data item berhasil diupdate.";
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

// Tutup koneksi & redirect
mysqli_close($koneksi);
header("Location: master_item.php?msg=" . urlencode($msg));
exit();
