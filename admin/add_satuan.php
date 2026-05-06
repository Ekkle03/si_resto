<?php
// Selalu mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_satuan.php");
    exit();
}

// Ambil & bersihkan input
$nama_satuan = isset($_POST['nama_satuan']) ? trim($_POST['nama_satuan']) : '';

// Validasi
if ($nama_satuan === '') {
    header("Location: master_satuan.php?msg=" . urlencode("Error: Nama satuan tidak boleh kosong."));
    exit();
}

// ===== Generate kode_satuan otomatis (SAT-001, SAT-002, dst) =====
$prefix = 'SAT-';
$nextNumber = 1;

// Ambil kode terbesar yang sudah ada
$sqlMax  = "SELECT kode_satuan FROM master_satuan 
            WHERE kode_satuan LIKE ? 
            ORDER BY kode_satuan DESC 
            LIMIT 1";
$stmtMax = mysqli_prepare($koneksi, $sqlMax);

if ($stmtMax) {
    $like = $prefix . '%';
    mysqli_stmt_bind_param($stmtMax, "s", $like);
    mysqli_stmt_execute($stmtMax);
    mysqli_stmt_bind_result($stmtMax, $lastKode);

    if (mysqli_stmt_fetch($stmtMax) && $lastKode) {
        // Ambil angka di belakang prefix, contoh SAT-015 → 15
        $numPart = preg_replace('/[^0-9]/', '', $lastKode);
        if ($numPart !== '') {
            $nextNumber = (int)$numPart + 1;
        }
    }
    mysqli_stmt_close($stmtMax);
}

// Format ke 3 digit: 1 → 001, 12 → 012, dst
$kode_satuan = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

// ===== Insert data =====
$sql  = "INSERT INTO master_satuan (kode_satuan, nama_satuan) VALUES (?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $kode_satuan, $nama_satuan);

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data satuan berhasil ditambahkan dengan kode: $kode_satuan";
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
header("Location: master_satuan.php?msg=" . urlencode($msg));
exit();
