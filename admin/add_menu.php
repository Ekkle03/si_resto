<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_menu.php");
    exit();
}

// Ambil input
$nama_menu   = isset($_POST['nama_menu']) ? trim($_POST['nama_menu']) : '';
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;
$langsung_bom = isset($_POST['langsung_bom']) ? $_POST['langsung_bom'] : '';

// Validasi
if ($nama_menu === '' || $id_kategori === 0 || $id_satuan === 0) {
    header("Location: master_menu.php?msg=" . urlencode("Error: Semua field harus diisi."));
    exit();
}

// 1. GENERATE KODE MENU OTOMATIS (MNU-001, dst)
$prefix = 'MNU-';
$nextNumber = 1;

$sqlMax = "SELECT kode_menu FROM master_menu WHERE kode_menu LIKE ? ORDER BY kode_menu DESC LIMIT 1";
$stmtMax = mysqli_prepare($koneksi, $sqlMax);

if ($stmtMax) {
    $like = $prefix . '%';
    mysqli_stmt_bind_param($stmtMax, "s", $like);
    mysqli_stmt_execute($stmtMax);
    mysqli_stmt_bind_result($stmtMax, $lastKode);

    if (mysqli_stmt_fetch($stmtMax) && $lastKode) {
        $numPart = preg_replace('/[^0-9]/', '', $lastKode);
        if ($numPart !== '') {
            $nextNumber = (int)$numPart + 1;
        }
    }
    mysqli_stmt_close($stmtMax);
}
$kode_menu = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

// 2. INSERT DATA
$sql  = "INSERT INTO master_menu (kode_menu, id_satuan, id_kategori, nama_menu) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "siis", $kode_menu, $id_satuan, $id_kategori, $nama_menu);

    if (mysqli_stmt_execute($stmt)) {
        // Ambil ID yang baru saja masuk untuk keperluan redirect ke Resep
        $new_id = mysqli_insert_id($koneksi);
        
        // --- LOGIKA REDIRECT ---
        if ($langsung_bom == '1') {
            // Jika checkbox dicentang, langsung ke halaman buat resep
            header("Location: buat_bom_menu.php?id=$new_id");
            exit();
        } else {
            $msg = "Menu $nama_menu ($kode_menu) berhasil ditambahkan!";
        }
    } else {
        $msg = "Error: Gagal simpan. " . mysqli_stmt_error($stmt);
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal siapkan statement.";
}

mysqli_close($koneksi);
header("Location: master_menu.php?msg=" . urlencode($msg));
exit();