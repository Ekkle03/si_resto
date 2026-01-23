<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_menu.php");
    exit();
}

// Ambil & bersihkan input
$nama_menu   = isset($_POST['nama_menu']) ? trim($_POST['nama_menu']) : '';
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;

// Validasi sederhana
if ($nama_menu === '' || $id_kategori === 0 || $id_satuan === 0) {
    header("Location: master_menu.php?msg=" . urlencode("Error: Semua field harus diisi."));
    exit();
}

// ===== Generate kode_menu otomatis (MNU-001, MNU-002, dst) =====
$prefix = 'MNU-';
$nextNumber = 1;

// Ambil kode terbesar yang sudah ada
$sqlMax  = "SELECT kode_menu FROM master_menu 
            WHERE kode_menu LIKE ? 
            ORDER BY kode_menu DESC 
            LIMIT 1";
$stmtMax = mysqli_prepare($koneksi, $sqlMax);

if ($stmtMax) {
    $like = $prefix . '%';
    mysqli_stmt_bind_param($stmtMax, "s", $like);
    mysqli_stmt_execute($stmtMax);
    mysqli_stmt_bind_result($stmtMax, $lastKode);

    if (mysqli_stmt_fetch($stmtMax) && $lastKode) {
        // Ambil angka di belakang prefix, contoh MNU-005 → 5
        $numPart = preg_replace('/[^0-9]/', '', $lastKode);
        if ($numPart !== '') {
            $nextNumber = (int)$numPart + 1;
        }
    }
    mysqli_stmt_close($stmtMax);
}

// Format ke 3 digit: 1 → 001, 12 → 012, dst
$kode_menu = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

// ===== Insert data =====
// Sesuai struktur: kode_menu, id_satuan, id_kategori, nama_menu
$sql  = "INSERT INTO master_menu (kode_menu, id_satuan, id_kategori, nama_menu) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // 'siis' -> string (kode), integer (satuan), integer (kategori), string (nama)
    mysqli_stmt_bind_param($stmt, "siis", $kode_menu, $id_satuan, $id_kategori, $nama_menu);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data menu berhasil ditambahkan dengan kode: $kode_menu";
    } else {
        $msg = "Error: Gagal menambahkan data. " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan statement.";
}

// Tutup koneksi
mysqli_close($koneksi);

// Redirect dengan pesan
header("Location: master_menu.php?msg=" . urlencode($msg));
exit();