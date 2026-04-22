<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_konversi    = $_POST['id_konversi'];
    $pilihan_item   = $_POST['pilihan_item']; 
    $satuan_besar   = $_POST['satuan_besar'];
    $satuan_kecil   = $_POST['satuan_kecil'];
    $nilai_konversi = $_POST['nilai_konversi'];

    // Pecah lagi "BB-1" jadi tipe=BB dan id=1
    $pecah       = explode('-', $pilihan_item);
    $tipe_bahan  = $pecah[0];
    $id_komponen = $pecah[1];

    $query = "UPDATE master_konversi SET 
              id_komponen = '$id_komponen', 
              tipe_bahan = '$tipe_bahan', 
              satuan_besar = '$satuan_besar', 
              satuan_kecil = '$satuan_kecil', 
              nilai_konversi = '$nilai_konversi' 
              WHERE id_konversi = '$id_konversi'";

    if (mysqli_query($koneksi, $query)) {
        header("Location: konversi_satuan.php?msg=Rumus konversi berhasil diperbarui!");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>