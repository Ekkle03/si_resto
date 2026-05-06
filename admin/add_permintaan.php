<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl = $_POST['tgl_request'];
    $id_g_tujuan = $_POST['id_gudang_tujuan']; 
    $ket_input = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    
    // --- LOGIKA PENOMORAN SIMPEL (REQ-001, REQ-002, dst) ---
    // Cari nota terakhir yang kodenya pendek (maksimal 8 karakter untuk REQ-999) 
    // agar tidak bertabrakan dengan format tanggal yang lama.
    $q_last = mysqli_query($koneksi, "SELECT kode_request FROM header_request 
                                      WHERE kode_request LIKE 'REQ-%' 
                                      AND LENGTH(kode_request) <= 8 
                                      ORDER BY id_header_req DESC LIMIT 1");
    
    if (mysqli_num_rows($q_last) > 0) {
        $d_last = mysqli_fetch_assoc($q_last);
        // Ambil angka setelah "REQ-" (karakter ke-4 dan seterusnya) lalu ubah jadi integer
        $last_nomor = (int) substr($d_last['kode_request'], 4);
        $next_nomor = $last_nomor + 1;
    } else {
        // Jika belum ada format baru sama sekali, mulai dari 1
        $next_nomor = 1;
    }
    
    // Format angka menjadi 3 digit (contoh: 1 jadi 001, 10 jadi 010)
    $kode_req = "REQ-" . str_pad($next_nomor, 3, '0', STR_PAD_LEFT);
    // ------------------------------------------------------

    // Simpan ke database dengan status 'Pending' sesuai alur konfirmasi dua langkah
    $sql = "INSERT INTO header_request (kode_request, tgl_request, id_gudang_tujuan, keterangan, status) 
            VALUES ('$kode_req', '$tgl', '$id_g_tujuan', '$ket_input', 'Pending')";
    
    if (mysqli_query($koneksi, $sql)) {
        $id_header = mysqli_insert_id($koneksi);
        
        // Bersihkan keranjang session biar tidak ada data sisa dari transaksi sebelumnya
        unset($_SESSION['keranjang']);
        
        header("Location: input_detail_permintaan.php?id=$id_header");
        exit();
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>