<?php
// Selalu mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_gudang.php");
    exit();
}

// Ambil & bersihkan input
$nama_gudang = isset($_POST['nama_gudang']) ? trim($_POST['nama_gudang']) : '';

// Validasi
if ($nama_gudang === '') {
    header("Location: master_gudang.php?msg=" . urlencode("Error: Nama gudang tidak boleh kosong."));
    exit();
}

// ===== Generate kode_gudang otomatis (GDN-001, GDN-002, dst) =====
$prefix = 'GDN-';
$nextNumber = 1;

// Ambil kode terbesar yang sudah ada
$sqlMax  = "SELECT kode_gudang FROM master_gudang 
            WHERE kode_gudang LIKE ? 
            ORDER BY kode_gudang DESC 
            LIMIT 1";
$stmtMax = mysqli_prepare($koneksi, $sqlMax);

if ($stmtMax) {
    $like = $prefix . '%';
    mysqli_stmt_bind_param($stmtMax, "s", $like);
    mysqli_stmt_execute($stmtMax);
    mysqli_stmt_bind_result($stmtMax, $lastKode);

    if (mysqli_stmt_fetch($stmtMax) && $lastKode) {
        // Ambil angka di belakang prefix, contoh GDN-015 → 15
        $numPart = preg_replace('/[^0-9]/', '', $lastKode);
        if ($numPart !== '') {
            $nextNumber = (int)$numPart + 1;
        }
    }
    mysqli_stmt_close($stmtMax);
}

// Format ke 3 digit: 1 → 001, 12 → 012, dst
$kode_gudang = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

// ===== Insert data =====
$sql  = "INSERT INTO master_gudang (kode_gudang, nama_gudang) VALUES (?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $kode_gudang, $nama_gudang);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data gudang berhasil ditambahkan ";
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
header("Location: master_gudang.php?msg=" . urlencode($msg));
exit();