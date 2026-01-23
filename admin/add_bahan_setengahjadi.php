<?php
session_start();
include("../config/koneksi_mysql.php");

// Hanya boleh lewat POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahan_setengahjadi.php");
    exit();
}

// 1. Ambil & bersihkan input
$nama_bsj     = isset($_POST['nama_bsj']) ? trim($_POST['nama_bsj']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$tahap       = isset($_POST['tahap']) ? trim($_POST['tahap']) : '';
$langsung_bom = isset($_POST['langsung_bom']) ? $_POST['langsung_bom'] : 0; // Checkbox dari modal

// 2. Validasi dasar
if ($nama_bsj === '' || $id_satuan <= 0 || $id_kategori <= 0 || $tahap === '') {
    header("Location: master_bahan_setengahjadi.php?msg=" . urlencode("Error: Data tidak lengkap!"));
    exit();
}

// 3. Generate kode_bsj otomatis (BSJ-001, dst)
$sql_kode = "SELECT MAX(id_bsj) AS max_id FROM master_bahan_setengah_jadi";
$res_kode = mysqli_query($koneksi, $sql_kode);
$data_max = mysqli_fetch_assoc($res_kode);
$next_num = ($data_max['max_id'] ?? 0) + 1;
$kode_bsj = 'BSJ-' . str_pad((string)$next_num, 3, '0', STR_PAD_LEFT);

// 4. INSERT data ke database
$sql = "INSERT INTO master_bahan_setengah_jadi (kode_bsj, nama_bsj, id_satuan, id_kategori, tahap) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssiis", $kode_bsj, $nama_bsj, $id_satuan, $id_kategori, $tahap);

    if (mysqli_stmt_execute($stmt)) {
        // Ambil ID yang baru saja dibuat (Primary Key)
        $new_id = mysqli_insert_id($koneksi);
        
        // Logika Redirect: Cek apakah user mau langsung buat BOM atau tidak
        if ($langsung_bom == 1) {
            mysqli_stmt_close($stmt);
            mysqli_close($koneksi);
            // Lempar ke halaman tampilan buat BOM dengan membawa ID BSJ baru
            header("Location: buat_bom_bsj.php?id=" . $new_id);
            exit();
        } else {
            $msg = "Data $nama_bsj berhasil ditambahkan!";
        }
    } else {
        $msg = "Error: Gagal menyimpan data ke database.";
    }
    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan statement query.";
}

// Tutup koneksi & balik ke Master BSJ
mysqli_close($koneksi);
header("Location: master_bahan_setengahjadi.php?msg=" . urlencode($msg));
exit();