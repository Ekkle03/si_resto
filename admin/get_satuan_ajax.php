<?php
include("../config/koneksi_mysql.php");

if (isset($_POST['pilihan'])) {
    $pecah = explode('-', $_POST['pilihan']);
    
    if (count($pecah) < 2) {
        echo json_encode(['id' => '', 'nama' => '-']);
        exit();
    }

    $tipe = $pecah[0]; // BB atau BSJ
    $id   = (int)$pecah[1];

    // 1. CEK KHUSUS: Jika dia adalah BSJ, kita cek dia Tahap berapa?
    $tahap_bsj = '';
    if ($tipe == 'BSJ') {
        $q_tahap = mysqli_query($koneksi, "SELECT tahap FROM master_bahan_setengah_jadi WHERE id_bsj = '$id'");
        $d_tahap = mysqli_fetch_assoc($q_tahap);
        $tahap_bsj = $d_tahap['tahap'] ?? '';
    }

    /**
     * LOGIKA BARU:
     * - Jika BSJ Tahap 1 ('bsj1'), cari 'satuan_besar' di master_konversi.
     * - Jika BB atau BSJ Tahap 2, cari 'satuan_kecil' (logika lama).
     */
    
    if ($tipe == 'BSJ' && $tahap_bsj == 'bsj1') {
        // --- LOGIKA KHUSUS BSJ 1: Ambil Satuan Terbesar (Contoh: Sachet) ---
        $sql_konv = "SELECT sk.id_satuan, sk.nama_satuan 
                     FROM master_konversi mk
                     JOIN master_satuan sk ON mk.satuan_besar = sk.id_satuan
                     WHERE mk.id_komponen = ? AND mk.tipe_bahan = 'BSJ'
                     LIMIT 1";
    } else {
        // --- LOGIKA STANDAR (BB / BSJ 2): Ambil Satuan Terkecil (Contoh: Gram) ---
        $sql_konv = "SELECT sk.id_satuan, sk.nama_satuan 
                     FROM master_konversi mk
                     JOIN master_satuan sk ON mk.satuan_kecil = sk.id_satuan
                     WHERE mk.id_komponen = ? AND mk.tipe_bahan = ?
                     LIMIT 1";
    }
    
    $stmt_k = mysqli_prepare($koneksi, $sql_konv);
    if ($tipe == 'BSJ' && $tahap_bsj == 'bsj1') {
        mysqli_stmt_bind_param($stmt_k, "i", $id);
    } else {
        mysqli_stmt_bind_param($stmt_k, "is", $id, $tipe);
    }
    
    mysqli_stmt_execute($stmt_k);
    $res_konv = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_k));

    if ($res_konv) {
        echo json_encode([
            'id'   => $res_konv['id_satuan'],
            'nama' => $res_konv['nama_satuan']
        ]);
    } else {
        // Fallback: Jika tidak ada data di tabel konversi, ambil satuan default dari master
        if ($tipe == 'BB') {
            $sql_m = "SELECT s.id_satuan, s.nama_satuan FROM master_bahan_baku b 
                      JOIN master_satuan s ON b.id_satuan = s.id_satuan WHERE b.id_bb = ?";
        } else {
            $sql_m = "SELECT s.id_satuan, s.nama_satuan FROM master_bahan_setengah_jadi b 
                      JOIN master_satuan s ON b.id_satuan = s.id_satuan WHERE b.id_bsj = ?";
        }
        
        $stmt_m = mysqli_prepare($koneksi, $sql_m);
        mysqli_stmt_bind_param($stmt_m, "i", $id);
        mysqli_stmt_execute($stmt_m);
        $res_m = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_m));
        
        echo json_encode([
            'id'   => $res_m['id_satuan'] ?? '',
            'nama' => $res_m['nama_satuan'] ?? 'Satuan Tidak Ditemukan'
        ]);
    }
} else {
    echo json_encode(['id' => '', 'nama' => 'Invalid Request']);
}
?>