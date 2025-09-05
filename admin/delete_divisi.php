<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// 1. Pastikan parameter 'id' ada di URL dan bukan string kosong
if (isset($_GET['id']) && !empty($_GET['id'])) {
    
    // 2. Ambil ID dari URL
    $id_divisi = $_GET['id'];

    // 3. Siapkan query DELETE menggunakan prepared statement
    // Ini mencegah SQL Injection
    $sql = "DELETE FROM master_divisi WHERE id_divisi = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 4. Bind parameter ID ke statement
        // "i" berarti parameter adalah Integer
        mysqli_stmt_bind_param($stmt, "i", $id_divisi);

        // 5. Eksekusi statement
        if (mysqli_stmt_execute($stmt)) {
            // 6. Cek apakah ada baris yang benar-benar terhapus
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                // Jika berhasil, redirect dengan pesan sukses
                header("Location: master_divisi.php?msg=Data%20berhasil%20dihapus");
                exit();
            } else {
                // Jika tidak ada baris yang terhapus (misal: ID tidak ditemukan)
                header("Location: master_divisi.php?msg=Error:%20Data%20tidak%20ditemukan.");
                exit();
            }
        } else {
            // Jika eksekusi gagal (misal: ada data karyawan yang terhubung ke divisi ini)
            header("Location: master_divisi.php?msg=Error:%20Gagal%20menghapus%20data,%20kemungkinan%20divisi%20masih%20digunakan.");
            exit();
        }

        // Tutup statement
        mysqli_stmt_close($stmt);

    } else {
        // Jika prepare statement gagal
        header("Location: master_divisi.php?msg=Error:%20Terjadi%20kesalahan%20pada%20sistem.");
        exit();
    }

} else {
    // 7. Jika tidak ada ID di URL, redirect kembali
    header("Location: master_divisi.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>
