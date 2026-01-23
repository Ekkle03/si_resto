<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahan_setengahjadi.php");
    exit();
}

// 1. Ambil & bersihkan input
$id_bsj      = isset($_POST['id_bsj']) ? (int)$_POST['id_bsj'] : 0;
$nama_bsj    = isset($_POST['nama_bsj']) ? trim($_POST['nama_bsj']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$tahap       = isset($_POST['tahap']) ? trim($_POST['tahap']) : '';

// 2. Validasi
if ($id_bsj <= 0 || $nama_bsj === '' || $id_satuan <= 0 || $id_kategori <= 0) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Data tidak valid atau tidak lengkap."));
    exit();
}

$allowed_tahap = ['bsj1', 'bsj2'];
if (!in_array($tahap, $allowed_tahap, true)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Tahap tidak valid."));
    exit();
}

// 3. Eksekusi Update
$sql = "UPDATE master_bahan_setengah_jadi 
        SET nama_bsj = ?, id_satuan = ?, id_kategori = ?, tahap = ? 
        WHERE id_bsj = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "siisi", $nama_bsj, $id_satuan, $id_kategori, $tahap, $id_bsj);

    if (mysqli_stmt_execute($stmt)) {
        // Menggunakan affected_rows bisa menjebak jika user tidak mengubah apa-apa tapi klik simpan
        // Jadi kita beri pesan sukses selama query berhasil dijalankan
        $msg = "Data $nama_bsj berhasil diperbarui.";
    } else {
        $msg = "Error: Gagal mengupdate data. " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan query.";
}

mysqli_close($koneksi);
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();