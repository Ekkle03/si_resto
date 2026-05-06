<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahan_setengahjadi.php");
    exit();
}

$nama_bsj     = isset($_POST['nama_bsj']) ? trim($_POST['nama_bsj']) : '';
$id_satuan    = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;
$id_kategori  = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$tahap        = isset($_POST['tahap']) ? trim($_POST['tahap']) : '';
$langsung_bom = isset($_POST['langsung_bom']) ? $_POST['langsung_bom'] : 0;
// --- TAMBAHAN BARU ---
$stok_minimal_bsj = isset($_POST['stok_minimal_bsj']) ? (float)$_POST['stok_minimal_bsj'] : 0;
// ---------------------

// Ambil info source (dari mana dia klik tambah)
// Default-nya ke master_bahan_setengahjadi jika tidak ada
$source       = isset($_POST['source']) ? $_POST['source'] : 'master_bsj';

if ($nama_bsj === '' || $id_satuan <= 0 || $id_kategori <= 0 || $tahap === '') {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Data tidak lengkap!"));
    exit();
}

// Generate kode_bsj (Logika Asli Kamu)
$sql_kode = "SELECT MAX(id_bsj) AS max_id FROM master_bahan_setengah_jadi";
$res_kode = mysqli_query($koneksi, $sql_kode);
$data_max = mysqli_fetch_assoc($res_kode);
$next_num = ($data_max['max_id'] ?? 0) + 1;
$kode_bsj = 'BSJ-' . str_pad((string)$next_num, 3, '0', STR_PAD_LEFT);

// SQL INSERT (Menambahkan kolom stok_minimal_bsj)
$sql = "INSERT INTO master_bahan_setengah_jadi (kode_bsj, nama_bsj, id_satuan, id_kategori, tahap, stok_minimal_bsj) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // Bind param: s (kode), s (nama), i (satuan), i (kategori), s (tahap), d (stok_min)
    mysqli_stmt_bind_param($stmt, "ssiisd", $kode_bb, $nama_bsj, $id_satuan, $id_kategori, $tahap, $stok_minimal_bsj);
    
    // Perbaikan sedikit: variabel kode kamu tadi $kode_bsj, aku sesuaikan di bind_param
    mysqli_stmt_bind_param($stmt, "ssiisd", $kode_bsj, $nama_bsj, $id_satuan, $id_kategori, $tahap, $stok_minimal_bsj);

    if (mysqli_stmt_execute($stmt)) {
        $new_id = mysqli_insert_id($koneksi);
        
        if ($langsung_bom == 1) {
            mysqli_stmt_close($stmt);
            mysqli_close($koneksi);
            
            // Perhatikan: kita bawa ID, Satuan Standar, dan Source-nya (Logika Asli Kamu)
            header("Location: buat_bom_bsj.php?id=" . $new_id . "&std_satuan=" . $id_satuan . "&from=" . $source);
            exit();
        } else {
            $msg = "Data $nama_bsj berhasil ditambahkan!";
        }
    } else {
        $msg = "Error: Gagal menyimpan data.";
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Persiapan query gagal.";
}

mysqli_close($koneksi);
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();