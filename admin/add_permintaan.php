<?php
session_start();
include("../config/koneksi_mysql.php");

// Ganti 'id_karyawan' dengan nama session-mu yang benar
$id_karyawan = $_SESSION['id_karyawan'] ?? 0; 
if ($id_karyawan == 0) {
    $_SESSION['flash_msg'] = "Error: Sesi Anda telah berakhir. Silakan login kembali.";
    header("Location: permintaan.php");
    exit;
}

// Pastikan file ini diakses melalui metode POST dan aksinya benar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'buat_header') {
    
    // Ambil dan bersihkan data dari form modal
    $tanggal = $_POST['tanggal_permintaan'];
    $keterangan = $_POST['keterangan'];
    
    $id_gudang_asal = 1; 
    $id_gudang_tujuan = 3; 

    // Siapkan perintah SQL untuk memasukkan data header permintaan
    $stmt = mysqli_prepare($koneksi, 
        "INSERT INTO permintaan_barang (tanggal_permintaan, keterangan, id_gudang_asal, id_gudang_tujuan, id_karyawan) 
         VALUES (?, ?, ?, ?, ?)"
    );
    
    // Ikat parameter ke perintah SQL
    mysqli_stmt_bind_param($stmt, "ssiii", $tanggal, $keterangan, $id_gudang_asal, $id_gudang_tujuan, $id_karyawan);
    
    // Eksekusi perintah
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, ambil ID dari permintaan yang baru saja dibuat
        $id_permintaan_baru = mysqli_insert_id($koneksi);
        
        // Tutup statement
        mysqli_stmt_close($stmt);

        // Arahkan pengguna ke halaman input detail dengan membawa ID baru
        header("Location: input_detail_permintaan.php?id=" . $id_permintaan_baru);
        exit;

    } else {
        // Jika gagal, beri pesan error dan kembalikan ke halaman utama
        $_SESSION['flash_msg'] = "Error: Gagal membuat header permintaan. " . mysqli_error($koneksi);
        header("Location: permintaan.php");
        exit;
    }

} else {
    // Jika file diakses langsung atau aksinya salah, beri pesan error
    $_SESSION['flash_msg'] = "Error: Akses tidak sah.";
    header("Location: permintaan.php");
    exit;
}
?>

