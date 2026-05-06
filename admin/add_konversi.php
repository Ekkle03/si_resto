<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $pilihan_item   = $_POST['pilihan_item']; 
    $satuan_besar   = $_POST['satuan_besar'];
    $satuan_kecil   = $_POST['satuan_kecil'];
    $nilai_konversi = $_POST['nilai_konversi'];

    // Pecah pilihan_item untuk mendapatkan tipe dan id asli
    $pecah       = explode('-', $pilihan_item);
    $tipe_bahan  = $pecah[0]; // BB atau BSJ
    $id_komponen = $pecah[1]; // ID asli dari tabelnya

    // Query insert ke tabel master_konversi
    $query = "INSERT INTO master_konversi (id_komponen, tipe_bahan, satuan_besar, satuan_kecil, nilai_konversi) 
              VALUES ('$id_komponen', '$tipe_bahan', '$satuan_besar', '$satuan_kecil', '$nilai_konversi')";

    if (mysqli_query($koneksi, $query)) {
        header("Location: konversi_satuan.php?msg=Rumus konversi berhasil disimpan!");
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>