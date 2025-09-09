<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// 1. Cek apakah form sudah disubmit dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Ambil data dari form dan bersihkan dari spasi ekstra
    $nama_role = trim($_POST['nama_role']);

    // 3. Validasi sederhana: pastikan nama role tidak kosong
    if (empty($nama_role)) {
        // Jika kosong, kembalikan ke halaman sebelumnya dengan pesan error
        $pesan_error = "Nama role tidak boleh kosong.";
        header("Location: master_role.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 4. Siapkan query INSERT menggunakan prepared statement (lebih aman)
    $sql = "INSERT INTO master_role (nama_role) VALUES (?)";
    
    // 5. Siapkan statement
    $stmt = mysqli_prepare($koneksi, $sql);

    if ($stmt === false) {
        $pesan_error = "Error: Gagal menyiapkan statement.";
        header("Location: master_role.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 6. Bind parameter ke statement ('s' = string)
    mysqli_stmt_bind_param($stmt, "s", $nama_role);

    // 7. Eksekusi statement dan cek hasilnya
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect kembali dengan pesan sukses
        $pesan_sukses = "Data role berhasil ditambahkan.";
        header("Location: master_role.php?msg=" . urlencode($pesan_sukses));
        exit();
    } else {
        // Jika gagal, redirect kembali dengan pesan error dari database
        $pesan_error = "Error: " . mysqli_stmt_error($stmt);
        header("Location: master_role.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 8. Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika halaman diakses tanpa metode POST, redirect ke halaman utama
    header("Location: master_role.php");
    exit();
}

// Tutup koneksi database
mysqli_close($koneksi);
?>
