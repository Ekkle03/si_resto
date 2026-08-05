<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Set Zona Waktu agar akurat
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari modal waste.php
    $id_gudang   = mysqli_real_escape_string($koneksi, $_POST['id_gudang']);
    $tgl_waste   = mysqli_real_escape_string($koneksi, $_POST['tgl_waste']);
    
    // Tangkap data user yang sedang login
    $id_karyawan = $_SESSION['id_karyawan'] ?? 1; 
    $user_role   = strtolower($_SESSION['nama_role'] ?? '');

    // --- TAMBAHAN VALIDASI ---
    $tgl_sekarang = date('Y-m-d');

    // 1. Validasi Input Kosong
    if (empty($id_gudang) || empty($tgl_waste)) {
        header("Location: waste.php?status=error&msg=Gagal! Semua data wajib diisi.");
        exit();
    }

    // 2. Validasi Tanggal (Tidak boleh melebihi hari ini)
    if ($tgl_waste > $tgl_sekarang) {
        header("Location: waste.php?status=error&msg=Gagal! Tanggal tidak boleh melebihi hari ini.");
        exit();
    }

    // 3. Validasi Keamanan (Staf DILARANG nembak data ke Gudang Utama)
    if ($user_role === 'staf' && $id_gudang == '1') {
        header("Location: waste.php?status=error&msg=Akses Ditolak! Staf tidak bisa menginput waste di Gudang Utama.");
        exit();
    }
    // -------------------------

    // --- LOGIKA MAKER-CHECKER (STATUS VALIDASI) ---
    // Jika yang input Purchasing, Admin, atau Owner -> Langsung Disetujui
    // Jika selain itu (Staf) -> Pending (Menunggu Validasi Purchasing)
    $status_awal = (in_array($user_role, ['admin', 'purchasing', 'owner'])) ? 'Disetujui' : 'Pending';

    // 1. Generate Kode Waste Simpel (Contoh: WST-001)
    $q_max = mysqli_query($koneksi, "SELECT MAX(id_header_waste) as max_id FROM header_waste");
    $d_max = mysqli_fetch_assoc($q_max);
    $next_id = ($d_max['max_id'] ?? 0) + 1;
    $kode_waste = "WST-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    // 2. Simpan ke Tabel header_waste (Dengan Sisipan status_validasi)
    $sql_header = "INSERT INTO header_waste (kode_waste, tgl_waste, id_gudang, id_karyawan, status_validasi) 
                   VALUES ('$kode_waste', '$tgl_waste', '$id_gudang', '$id_karyawan', '$status_awal')";
    
    if (mysqli_query($koneksi, $sql_header)) {
        // Ambil ID header yang baru saja tersimpan
        $id_baru = mysqli_insert_id($koneksi);
        
        // 3. Redirect ke halaman input_detail_waste.php sambil membawa ID headernya
        header("Location: input_detail_waste.php?id=$id_baru");
        exit();
    } else {
        // Jika gagal insert header
        header("Location: waste.php?status=error&msg=Gagal menyimpan data header.");
        exit();
    }
} else {
    // Jika akses langsung tanpa POST
    header("Location: waste.php");
    exit();
}
?>