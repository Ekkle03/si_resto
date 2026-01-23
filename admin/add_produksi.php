<?php
// add_produksi.php - Memproses dan menyimpan jadwal produksi baru dari modal
session_start();
include("../config/koneksi_mysql.php");

// Pastikan file ini diakses melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil dan bersihkan data dari form
    $id_item = isset($_POST['id_item']) ? (int)$_POST['id_item'] : 0;
    
    $jumlah_target = isset($_POST['jumlah_target']) ? (float)$_POST['jumlah_target'] : 0; 
    
    $tanggal_dibuat = isset($_POST['tanggal_dibuat']) ? $_POST['tanggal_dibuat'] : date('Y-m-d');

    // Validasi dasar, pastikan semua data yang dibutuhkan ada
    if ($id_item > 0 && $jumlah_target > 0) {
        
        // Siapkan perintah SQL untuk memasukkan data
        $stmt = mysqli_prepare($koneksi, 
            "INSERT INTO produksi (id_item, jumlah_akhir, tanggal_dibuat) VALUES (?, ?, ?)"
        );
        
        // Kita menggunakan $jumlah_target untuk diisi ke kolom jumlah_akhir
        mysqli_stmt_bind_param($stmt, "ids", $id_item, $jumlah_target, $tanggal_dibuat);
        
        // Eksekusi perintah
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, beri pesan sukses
            $_SESSION['flash_msg'] = "Jadwal produksi baru berhasil dibuat!";
        } else {
            // Jika gagal, beri pesan error
            $_SESSION['flash_msg'] = "Error: Gagal menyimpan jadwal produksi. " . mysqli_error($koneksi);
        }
        // Tutup statement
        mysqli_stmt_close($stmt);

    } else {
        $_SESSION['flash_msg'] = "Error: Data yang diinput tidak valid. Pastikan semua kolom terisi.";
    }
} else {
    // Jika file diakses langsung tanpa POST, beri pesan error
    $_SESSION['flash_msg'] = "Error: Akses tidak sah.";
}

// Setelah selesai, selalu kembalikan pengguna ke halaman utama produksi
header("Location: produksi.php");
exit;
?>

