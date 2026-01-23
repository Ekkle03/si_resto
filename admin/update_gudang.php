<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_gudang   = isset($_POST['id_gudang']) ? (int) $_POST['id_gudang'] : 0;
    $nama_gudang = isset($_POST['nama_gudang']) ? trim($_POST['nama_gudang']) : '';

    if ($id_gudang <= 0) {
        header("Location: master_gudang.php?msg=" . urlencode("ID gudang tidak valid."));
        exit();
    }

    if ($nama_gudang === '') {
        header("Location: master_gudang.php?msg=" . urlencode("Nama gudang tidak boleh kosong."));
        exit();
    }

    $sql  = "UPDATE master_gudang SET nama_gudang = ? WHERE id_gudang = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $nama_gudang, $id_gudang);

        if (mysqli_stmt_execute($stmt)) {
            $msg = "Data gudang berhasil diupdate.";
        } else {
            $msg = "Gagal mengupdate data: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $msg = "Kesalahan sistem: " . mysqli_error($koneksi);
    }

    mysqli_close($koneksi);
    header("Location: master_gudang.php?msg=" . urlencode($msg));
    exit();

} else {
    header("Location: master_gudang.php");
    exit();
}
?>