<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari modal waste.php
    $id_gudang   = mysqli_real_escape_string($koneksi, $_POST['id_gudang']);
    $tgl_waste   = mysqli_real_escape_string($koneksi, $_POST['tgl_waste']);
    
    // Ambil ID Karyawan dari session (pastikan sudah diset saat login)
    $id_karyawan = $_SESSION['id_karyawan'] ?? 1; 

    // 1. Generate Kode Waste Simpel (Contoh: WST-001)
    $q_max = mysqli_query($koneksi, "SELECT MAX(id_header_waste) as max_id FROM header_waste");
    $d_max = mysqli_fetch_assoc($q_max);
    $next_id = ($d_max['max_id'] ?? 0) + 1;
    $kode_waste = "WST-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // 2. Simpan ke Tabel header_waste
    $sql_header = "INSERT INTO header_waste (kode_waste, tgl_waste, id_gudang, id_karyawan) 
                   VALUES ('$kode_waste', '$tgl_waste', '$id_gudang', '$id_karyawan')";
    
    if (mysqli_query($koneksi, $sql_header)) {
        // Ambil ID header yang baru saja tersimpan
        $id_baru = mysqli_insert_id($koneksi);
        
        // 3. Redirect ke halaman input_detail_waste.php sambil membawa ID headernya
        header("Location: input_detail_waste.php?id=" . $id_baru);
        exit();
    } else {
        // Jika gagal, tampilkan pesan error atau kembalikan ke halaman awal
        echo "<script>
                alert('Gagal menyimpan header transaksi: " . mysqli_error($koneksi) . "');
                window.location.href='waste.php';
              </script>";
    }
} else {
    // Jika diakses tanpa POST, kembalikan ke halaman utama waste
    header("Location: waste.php");
    exit();
}
?>