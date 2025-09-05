<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// 1. Pastikan request method adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Cek jika data yang dibutuhkan ada dan tidak kosong
    if (empty($_POST['id_divisi']) || empty($_POST['nama_divisi'])) {
        // Redirect dengan pesan error jika data tidak lengkap
        header("Location: master_divisi.php?msg=Error:%20Data%20tidak%20lengkap.");
        exit();
    }

    // 3. Ambil data dari form
    $id_divisi = $_POST['id_divisi'];
    $nama_divisi = trim($_POST['nama_divisi']); // Gunakan trim untuk menghapus spasi ekstra

    // 4. Siapkan query UPDATE menggunakan prepared statement
    $sql = "UPDATE master_divisi SET nama_divisi = ? WHERE id_divisi = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt) {
        // 5. Bind parameter ke statement
        // "si" berarti parameter pertama adalah String (nama_divisi), kedua adalah Integer (id_divisi)
        mysqli_stmt_bind_param($stmt, "si", $nama_divisi, $id_divisi);

        // 6. Eksekusi statement
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, redirect dengan pesan sukses
            header("Location: master_divisi.php?msg=Data%20berhasil%20diupdate");
            exit();
        } else {
            // Jika eksekusi gagal
            header("Location: master_divisi.php?msg=Error:%20Gagal%20mengupdate%20data.");
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
    // 7. Jika halaman diakses langsung (bukan via POST), redirect kembali
    header("Location: master_divisi.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>
