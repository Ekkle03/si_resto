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
// --- TAMBAHAN BARU ---
$stok_minimal_bsj = isset($_POST['stok_minimal_bsj']) ? (float)$_POST['stok_minimal_bsj'] : 0;
// ---------------------

// Tambahkan penangkap source navigasi
$source      = isset($_POST['source']) ? $_POST['source'] : 'master_bsj';

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

// 3. Eksekusi Update (Menambahkan kolom stok_minimal_bsj)
$sql = "UPDATE master_bahan_setengah_jadi 
        SET nama_bsj = ?, id_satuan = ?, id_kategori = ?, tahap = ?, stok_minimal_bsj = ? 
        WHERE id_bsj = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // Bind param: s (nama), i (satuan), i (kategori), s (tahap), d (stok_min), i (id_bsj)
    mysqli_stmt_bind_param($stmt, "siisdi", $nama_bsj, $id_satuan, $id_kategori, $tahap, $stok_minimal_bsj, $id_bsj);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data $nama_bsj berhasil diperbarui.";
    } else {
        $msg = "Error: Gagal mengupdate data.";
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan query.";
}

// 4. Logika Redirect Berdasarkan Source
mysqli_close($koneksi);

if ($source === 'master_bom') {
    header("Location: master_bom.php?msg=" . urlencode($msg));
} else {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
}
exit();