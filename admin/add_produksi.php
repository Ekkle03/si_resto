<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Set Zona Waktu lokal biar jamnya akurat
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Tangkap kode produksi dari form
    $kode_produksi = mysqli_real_escape_string($koneksi, $_POST['kode_produksi']);
    
    $id_bsj = (int)$_POST['id_bsj'];
    // 1. Menangkap inputan jumlah BOM dari form
    $qty_bom_input = floatval($_POST['qty_bom_input']);
    
    // GABUNGIN TANGGAL DARI FORM DENGAN JAM SAAT INI (Biar urutan tabel akurat)
    $tgl_input = $_POST['tgl_produksi'] . ' ' . date('H:i:s'); 
    
    // Pastikan input valid
    if ($id_bsj <= 0 || $qty_bom_input <= 0) {
        $_SESSION['flash_msg'] = "Gagal: Produk dan jumlah resep harus diisi dengan benar.";
        header("Location: produksi.php");
        exit();
    }

    // 2. Ambil target_hasil dari Master BOM untuk kalkulasi
    // Kita harus tahu 1 resep produk ini menghasilkan berapa unit/porsi
    $query_target = mysqli_query($koneksi, "SELECT target_hasil FROM master_bom 
                                            WHERE id_induk = '$id_bsj' 
                                            AND tipe_bom = 'BSJ' LIMIT 1");
    $data_bom = mysqli_fetch_assoc($query_target);
    
    // Jika resep tidak ditemukan, default ke 1 (keamanan sistem)
    $target_per_resep = ($data_bom) ? floatval($data_bom['target_hasil']) : 1;

    // 3. RUMUS UTAMA: Jumlah BOM x Target Hasil = Total Porsi Rencana
    $qty_rencana_total = $qty_bom_input * $target_per_resep;

    // 4. Simpan ke tabel produksi dengan status 'Rencana' DAN masukkan kode_produksi
    $sql = "INSERT INTO produksi (kode_produksi, id_bsj, qty_rencana, qty_realisasi, tgl_produksi, status) 
            VALUES (?, ?, ?, 0, ?, 'Rencana')";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    
    // Bind parameter: s (string kode), i (int id_bsj), d (double/float qty), s (string tanggal)
    mysqli_stmt_bind_param($stmt, "sids", $kode_produksi, $id_bsj, $qty_rencana_total, $tgl_input);
    
    if (mysqli_stmt_execute($stmt)) {
        // Ambil nama produk untuk pesan sukses
        $q_name = mysqli_query($koneksi, "SELECT nama_bsj FROM master_bahan_setengah_jadi WHERE id_bsj = '$id_bsj'");
        $nama_produk = mysqli_fetch_assoc($q_name)['nama_bsj'];
        
        $_SESSION['flash_msg'] = "Berhasil: Rencana produksi $nama_produk dijadwalkan (Total: " . (float)$qty_rencana_total . ").";
    } else {
        $_SESSION['flash_msg'] = "Sistem Error: " . mysqli_error($koneksi);
    }
    
    mysqli_stmt_close($stmt);
    header("Location: produksi.php");
    exit();
}
?>