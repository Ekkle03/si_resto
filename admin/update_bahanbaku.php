<?php
// Mulai session
session_start();

// Koneksi DB
include("../config/koneksi_mysql.php");

// Hanya boleh lewat POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahanbaku.php");
    exit();
}

// Ambil & bersihkan input
$id_bb       = isset($_POST['id_bb']) ? trim($_POST['id_bb']) : '';
$nama_bb     = isset($_POST['nama_bb']) ? trim($_POST['nama_bb']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? trim($_POST['id_satuan']) : '';
$id_kategori = isset($_POST['id_kategori']) ? trim($_POST['id_kategori']) : '';
$tipe_bahan  = isset($_POST['tipe_bahan']) ? trim($_POST['tipe_bahan']) : '';

// Validasi dasar
if ($id_bb === '' || !ctype_digit($id_bb)) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: ID bahan baku tidak valid."));
    exit();
}
if ($nama_bb === '') {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Nama bahan baku tidak boleh kosong."));
    exit();
}
if ($id_satuan === '' || !ctype_digit($id_satuan)) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Satuan tidak valid."));
    exit();
}
if ($id_kategori === '' || !ctype_digit($id_kategori)) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Kategori tidak valid."));
    exit();
}

// Validasi tipe_bahan (enum: basah/kering)
$allowed_tipe = ['basah', 'kering'];
if (!in_array($tipe_bahan, $allowed_tipe, true)) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Tipe bahan tidak valid."));
    exit();
}

$id_bb       = (int)$id_bb;
$id_satuan   = (int)$id_satuan;
$id_kategori = (int)$id_kategori;

// Siapkan query UPDATE
$sql = "UPDATE master_bahan_baku 
        SET nama_bb = ?, id_satuan = ?, id_kategori = ?, tipe_bahan = ?
        WHERE id_bb = ?";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param(
        $stmt,
        "siisi",      // s: nama_bb, i: id_satuan, i: id_kategori, s: tipe_bahan, i: id_bb
        $nama_bb,
        $id_satuan,
        $id_kategori,
        $tipe_bahan,
        $id_bb
    );

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $msg = "Data bahan baku berhasil diupdate.";
        } else {
            // Tidak ada baris berubah (mungkin datanya sama persis)
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
header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
exit();
