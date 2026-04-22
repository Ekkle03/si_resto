<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_bsj = (int)$_POST['id_bsj'];
    // 1. Pastikan inputan jumlah BOM ditangkap sebagai float agar bisa 0.5 resep dsb
    $qty_bom_input = floatval($_POST['qty_bom_input']);
    
    // --- PERBAIKAN: TANGKAP KODE & TANGGAL DARI FORM ---
    $kode_produksi = mysqli_real_escape_string($koneksi, $_POST['kode_produksi']);
    
    // Ambil tanggal dari form, lalu gabungkan dengan jam saat ini biar format DateTime valid
    $tgl_input = $_POST['tgl_produksi']; 
    $waktu_sekarang = date('H:i:s');
    $tgl_produksi = $tgl_input . ' ' . $waktu_sekarang; 
    // ----------------------------------------------------

    if ($id_bsj <= 0 || $qty_bom_input <= 0) {
        $_SESSION['flash_msg'] = "Gagal: Produk dan jumlah resep harus diisi dengan benar.";
        header("Location: produksi_langsung.php");
        exit();
    }

    // 2. Ambil target_hasil dari Master BOM (Sekarang sudah mendukung DECIMAL dari DB)
    $query_target = mysqli_query($koneksi, "SELECT target_hasil FROM master_bom 
                                            WHERE id_induk = '$id_bsj' 
                                            AND tipe_bom = 'BSJ' LIMIT 1");
    $data_bom = mysqli_fetch_assoc($query_target);
    
    // Gunakan floatval agar jika targetnya 1.5 tidak dibulatkan jadi 1
    $target_hasil = ($data_bom) ? floatval($data_bom['target_hasil']) : 1;

    // 3. RUMUS: Jumlah BOM x Target Hasil = Total Hasil Rencana
    $qty_rencana_hasil = $qty_bom_input * $target_hasil;

    // 4. Ambil nama satuan untuk keperluan notifikasi pesan
    $q_satuan = mysqli_query($koneksi, "SELECT s.nama_satuan FROM master_bahan_setengah_jadi bsj 
                                       JOIN master_satuan s ON bsj.id_satuan = s.id_satuan 
                                       WHERE bsj.id_bsj = '$id_bsj'");
    $d_satuan = mysqli_fetch_assoc($q_satuan);
    $nama_satuan = $d_satuan['nama_satuan'] ?? 'Unit';

    // 5. Simpan ke database menggunakan Prepared Statement (DITAMBAH KODE PRODUKSI)
    $sql = "INSERT INTO produksi (kode_produksi, id_bsj, qty_rencana, qty_realisasi, tgl_produksi, status) 
            VALUES (?, ?, ?, 0, ?, 'Rencana')";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    
    // "s" (string untuk kode_produksi), "i" (integer untuk id_bsj), "d" (double untuk qty), "s" (string untuk tgl_produksi)
    mysqli_stmt_bind_param($stmt, "sids", $kode_produksi, $id_bsj, $qty_rencana_hasil, $tgl_produksi);
    
    if (mysqli_stmt_execute($stmt)) {
        // Notifikasi dibuat lebih dinamis mengikuti satuan produknya
        $_SESSION['flash_msg'] = "Rencana produksi <b>$kode_produksi</b> berhasil disimpan (Total: " . (float)$qty_rencana_hasil . " $nama_satuan).";
    } else {
        $_SESSION['flash_msg'] = "Sistem Error: " . mysqli_error($koneksi);
    }
    
    mysqli_stmt_close($stmt);
    header("Location: produksi_langsung.php");
    exit();
}
?>