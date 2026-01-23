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
$id_bsj      = isset($_POST['id_bsj']) ? trim($_POST['id_bsj']) : '';
$nama_bsj    = isset($_POST['nama_bsj']) ? trim($_POST['nama_bsj']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? trim($_POST['id_satuan']) : '';
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';
$tahap       = isset($_POST['tahap']) ? trim($_POST['tahap']) : '';

// Validasi
if ($id_bsj === '' || !ctype_digit($id_bsj)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: ID bahan setengah jadi tidak valid."));
    exit();
}
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

$allowed_tahap = ['bsj1', 'bsj2'];
if (!in_array($tahap, $allowed_tahap, true)) {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Tahap tidak valid."));
    exit();
}

$id_bsj      = (int)$id_bsj;
$id_satuan   = (int)$id_satuan;
$id_kategori = (int)$id_kategori;

// Siapkan query UPDATE
$sql = "UPDATE master_bahan_setengah_jadi
        SET nama_bsj = ?, id_satuan = ?, id_kategori = ?, tahap = ?
        WHERE id_bsj = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        "siisi",     // s: nama_bsj, i: id_satuan, i: id_kategori, s: tahap, i: id_bsj
        $nama_bsj,
        $id_satuan,
        $id_kategori,
        $tahap,
        $id_bsj
    );

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Data bahan setengah jadi berhasil diupdate.";
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
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();
