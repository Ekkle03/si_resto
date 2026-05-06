<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari modal stok_opname.php
    $id_gudang  = mysqli_real_escape_string($koneksi, $_POST['id_gudang']);
    $tgl_opname = mysqli_real_escape_string($koneksi, $_POST['tgl_opname']);
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    // 1. Generate Kode Opname (Contoh: OPN-001)
    $q_max = mysqli_query($koneksi, "SELECT MAX(id_header_opname) as max_id FROM header_opname");
    $d_max = mysqli_fetch_assoc($q_max);
    $next_id = ($d_max['max_id'] ?? 0) + 1;
    $kode_opname = "OPN-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // 2. Simpan ke Tabel header_opname (Hanya kolom yang ada di SQL-mu)
    $sql_header = "INSERT INTO header_opname (kode_opname, tgl_opname, id_gudang, keterangan) 
                   VALUES ('$kode_opname', '$tgl_opname', '$id_gudang', '$keterangan')";
    
    if (mysqli_query($koneksi, $sql_header)) {
        // Ambil ID yang baru saja dibuat
        $id_baru = mysqli_insert_id($koneksi);
        
        // 3. Redirect ke halaman input stok_opname_input.php
        header("Location: stok_opname_input.php?id_header=" . $id_baru);
        exit();
    } else {
        // Jika gagal, kasih tahu errornya apa
        echo "<script>
                alert('Gagal simpan header: " . mysqli_error($koneksi) . "');
                window.location.href='stok_opname.php';
              </script>";
    }
} else {
    header("Location: stok_opname.php");
    exit();
}
?>