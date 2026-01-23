<?php
// Selalu mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Pastikan request via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: master_bahanbaku.php");
    exit();
}

// Ambil & bersihkan input
$nama_bb     = isset($_POST['nama_bb']) ? trim($_POST['nama_bb']) : '';
$id_satuan   = isset($_POST['id_satuan']) ? (int)$_POST['id_satuan'] : 0;
$id_kategori = isset($_POST['id_kategori']) ? (int)$_POST['id_kategori'] : 0;
$tipe_bahan  = isset($_POST['tipe_bahan']) ? trim($_POST['tipe_bahan']) : '';

// Validasi dasar
if ($nama_bb === '' || $id_satuan <= 0 || $id_kategori <= 0 || $tipe_bahan === '') {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Semua field wajib diisi."));
    exit();
}

// Validasi tipe_bahan
if (!in_array($tipe_bahan, ['basah', 'kering'], true)) {
    header("Location: master_bahanbaku.php?msg=" . urlencode("Error: Tipe bahan tidak valid."));
    exit();
}

// ====== Generate kode otomatis BB-001, BB-002, dst ======
// Gunakan COALESCE untuk memastikan jika tabel kosong tetap mulai dari 1
$query_max = mysqli_query($koneksi, "SELECT COALESCE(MAX(id_bb), 0) AS max_id FROM master_bahan_baku");
$data_max  = mysqli_fetch_assoc($query_max);
$next_id   = $data_max['max_id'] + 1;

// Format → BB-001 (str_pad akan memastikan 3 digit)
$kode_bb = "BB-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

// ====== INSERT data (prepared statement) ======
$sql = "INSERT INTO master_bahan_baku (kode_bb, nama_bb, id_satuan, id_kategori, tipe_bahan) VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($koneksi, $sql);

if ($stmt) {
    // ss i i s  →  kode (s), nama (s), id_satuan (i), id_kategori (i), tipe (s)
    mysqli_stmt_bind_param($stmt, "ssiis",
        $kode_bb,
        $nama_bb,
        $id_satuan,
        $id_kategori,
        $tipe_bahan
    );

    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data bahan baku berhasil ditambahkan dengan kode " . $kode_bb;
    } else {
        // Cek jika error disebabkan nama bahan duplikat (jika ada unique constraint)
        if (mysqli_errno($koneksi) == 1062) {
            $msg = "Error: Nama atau Kode bahan baku sudah ada.";
        } else {
            $msg = "Error: Gagal menambahkan data. " . mysqli_stmt_error($stmt);
        }
    }

    mysqli_stmt_close($stmt);
} else {
    $msg = "Error: Gagal menyiapkan query.";
}

// Tutup koneksi
mysqli_close($koneksi);

// Redirect dengan pesan
header("Location: master_bahanbaku.php?msg=" . urlencode($msg));
exit();