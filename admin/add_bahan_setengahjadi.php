<?php
// Mulai session
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Hanya boleh lewat POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahan_setengahjadi.php");
    exit();
}

// Ambil & bersihkan input
$nama_bsj    = isset($_POST['nama_bsj']) ? trim($_POST['nama_bsj']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? trim($_POST['id_satuan']) : '';
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';
$tahap       = isset($_POST['tahap']) ? trim($_POST['tahap']) : '';

// Validasi dasar
if ($nama_bsj === '') {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Nama bahan setengah jadi tidak boleh kosong."));
    exit();
}
if ($id_satuan === '' || !ctype_digit($id_satuan)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Satuan tidak valid."));
    exit();
}
if ($id_kategori === '' || !ctype_digit($id_kategori)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Kategori tidak valid."));
    exit();
}

// Validasi tahap
$allowed_tahap = ['bsj1', 'bsj2'];
if (!in_array($tahap, $allowed_tahap, true)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Tahap tidak valid."));
    exit();
}

$id_satuan   = (int)$id_satuan;
$id_kategori = (int)$id_kategori;

// ===== Generate kode_bsj berikutnya (BSJ-001, BSJ-002, dst) =====
$sql_kode = "SELECT MAX(kode_bsj) AS max_kode 
             FROM master_bahan_setengah_jadi 
             WHERE kode_bsj LIKE 'BSJ-%'";
$res_kode = mysqli_query($koneksi, $sql_kode);

$next_num = 1;
if ($res_kode && mysqli_num_rows($res_kode) > 0) {
    $row_kode = mysqli_fetch_assoc($res_kode);
    if (!empty($row_kode['max_kode'])) {
        // ambil 3 digit terakhir setelah 'BSJ-'
        $last_num = (int)substr($row_kode['max_kode'], 4);
        $next_num = $last_num + 1;
    }
}
$kode_bsj = 'BSJ-' . str_pad((string)$next_num, 3, '0', STR_PAD_LEFT);

// ===== INSERT data dengan kode_bsj yang sudah jadi =====
$sql = "INSERT INTO master_bahan_setengah_jadi (kode_bsj, nama_bsj, id_satuan, id_kategori, tahap)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        "ssiis",           // s: kode, s: nama, i: id_satuan, i: id_kategori, s: tahap
        $kode_bsj,
        $nama_bsj,
        $id_satuan,
        $id_kategori,
        $tahap
    );

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data bahan setengah jadi berhasil ditambahkan dengan kode $kode_bsj.";
    } else {
        $msg = "Error: Gagal menambahkan data. " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan statement.";
}

// Tutup koneksi
mysqli_close($koneksi);

// Redirect
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();
