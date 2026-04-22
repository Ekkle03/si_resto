<?php
session_start();
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_menu.php");
    exit();
}

// Ambil input dari Modal Update
$id_menu     = isset($_POST['id_menu']) ? (int)$_POST['id_menu'] : 0;
$nama_menu   = isset($_POST['nama_menu']) ? trim($_POST['nama_menu']) : '';
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;

// Validasi sederhana
if ($id_menu === 0 || $nama_menu === '' || $id_kategori === 0 || $id_satuan === 0) {
    header("Location: master_menu.php?msg=" . urlencode("Error: Data update tidak lengkap."));
    exit();
}

// ===== Update Data =====
// Kita gunakan Prepared Statement supaya aman dari SQL Injection
$sql  = "UPDATE master_menu SET nama_menu = ?, id_kategori = ?, id_satuan = ? WHERE id_menu = ?";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // 'siii' -> string (nama), integer (kategori), integer (satuan), integer (id)
    mysqli_stmt_bind_param($stmt, "siii", $nama_menu, $id_kategori, $id_satuan, $id_menu);

    if (mysqli_stmt_execute($stmt)) {
        // Cek apakah ada baris yang benar-benar berubah
        if (mysqli_stmt_affected_rows($stmt) >= 0) {
            $msg = "Data menu '$nama_menu' berhasil diperbarui!";
        } else {
            $msg = "Tidak ada perubahan data.";
        }
    } else {
        $msg = "Error: Gagal memperbarui data. " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan statement update.";
}

// Tutup koneksi
mysqli_close($koneksi);

// Redirect balik dengan pesan sukses/error
header("Location: master_menu.php?msg=" . urlencode($msg));
exit();