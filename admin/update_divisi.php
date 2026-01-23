<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request method adalah POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form dengan pengecekan dasar
    $id_divisi   = isset($_POST['id_divisi']) ? (int) $_POST['id_divisi'] : 0;
    $nama_divisi = isset($_POST['nama_divisi']) ? trim($_POST['nama_divisi']) : '';

    // Validasi ID dan nama
    if ($id_divisi <= 0) {
        $pesan_error = "ID divisi tidak valid.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    if ($nama_divisi === '') {
        $pesan_error = "Nama divisi tidak boleh kosong.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // Siapkan query UPDATE menggunakan prepared statement
    $sql  = "UPDATE master_divisi SET nama_divisi = ? WHERE id_divisi = ?";
    $stmt = mysqli_prepare($koneksi, $sql);

    if (!$stmt) {
        $pesan_error = "Terjadi kesalahan pada sistem: " . mysqli_error($koneksi);
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    // 'si' berarti: string, integer
    mysqli_stmt_bind_param($stmt, "si", $nama_divisi, $id_divisi);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        $pesan_sukses = "Data divisi berhasil diupdate.";
        header("Location: master_divisi.php?msg=" . urlencode($pesan_sukses));
        exit();
    } else {
        $pesan_error = "Gagal mengupdate data: " . mysqli_stmt_error($stmt);
        header("Location: master_divisi.php?msg=" . urlencode($pesan_error));
        exit();
    }

    mysqli_stmt_close($stmt);

} else {
    // Jika halaman diakses langsung (bukan via POST), redirect kembali
    header("Location: master_divisi.php");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>
