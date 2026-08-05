<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil ID Header Waste
    $id_header = mysqli_real_escape_string($koneksi, $_POST['id_header_waste']);
    
    // --- PERBAIKAN: Ambil status_validasi sekalian dari header ---
    $q_h = mysqli_query($koneksi, "SELECT id_gudang, kode_waste, status_validasi FROM header_waste WHERE id_header_waste = '$id_header'");
    $d_h = mysqli_fetch_assoc($q_h);
    $id_gudang  = $d_h['id_gudang'];
    $kode_waste = $d_h['kode_waste'];
    $status_val = $d_h['status_validasi']; // Nilainya 'Pending' atau 'Disetujui'

    // Siapkan folder upload foto bukti
    $target_dir = "../assets/img/waste/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

    foreach ($_POST['items'] as $index => $item) {
        $raw_id    = $item['id_item']; // Contoh: BB-5
        $qty_input = (float)$item['qty']; 
        $alasan    = mysqli_real_escape_string($koneksi, $item['alasan']);
        $ket       = mysqli_real_escape_string($koneksi, $item['keterangan_item']);

        // Pecah ID untuk tahu tipe bahannya
        $explode    = explode('-', $raw_id);
        $tipe       = $explode[0]; // BB atau BSJ
        $real_id    = $explode[1];
        $col_target = ($tipe == 'BB') ? 'id_bb' : 'id_bsj';

        // --- LOGIKA PERKALIAN KONVERSI (KE SATUAN TERKECIL) ---
        $qty_final = $qty_input; 
        if ($id_gudang == "1") {
            $sql_konv = "SELECT nilai_konversi FROM master_konversi 
                         WHERE id_komponen = '$real_id' AND tipe_bahan = '$tipe'";
            $q_konv = mysqli_query($koneksi, $sql_konv);
            if (mysqli_num_rows($q_konv) > 0) {
                $d_konv = mysqli_fetch_assoc($q_konv);
                $n_konv = (float)$d_konv['nilai_konversi'];
                $qty_final = $qty_input * $n_konv; // Konversi ke satuan kecil
            }
        }

        // 1. Proses Upload Foto Bukti
        $foto_db = "";
        $file_key = "foto_" . $index;
        if (!empty($_FILES[$file_key]['name'])) {
            $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
            $foto_db = "WST_" . time() . "_" . $index . "." . $ext;
            move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_dir . $foto_db);
        }

        // 2. Simpan ke detail_waste (SELALU DIJALANKAN, baik Pending maupun Disetujui)
        $sql_detail = "INSERT INTO detail_waste (id_header_waste, $col_target, qty_waste, alasan, foto_bukti, sumber) 
                       VALUES ('$id_header', '$real_id', '$qty_final', '$alasan', '$foto_db', 'Manual')";
        mysqli_query($koneksi, $sql_detail);

        // --- LOGIKA MAKER-CHECKER (POTONG STOK JIKA DISETUJUI SAJA) ---
        if ($status_val === 'Disetujui') {
            
            // 3. Potong Stok di kartu stok bahan
            $sql_update_stok = "UPDATE stok_bahan SET jumlah = jumlah - $qty_final 
                                WHERE $col_target = '$real_id' AND id_gudang = '$id_gudang'";
            mysqli_query($koneksi, $sql_update_stok);

            // 4. Ambil sisa stok terbaru untuk log
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col_target = '$real_id' AND id_gudang = '$id_gudang'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_sekarang = $d_sisa['jumlah'] ?? 0;

            // --- MERAPIKAN KETERANGAN LOG ---
            $alasan_tampil = ($alasan === 'Lainnya') ? $ket : $alasan;
            $keterangan_log = "Waste: " . $kode_waste . " (" . $alasan_tampil . ")";

            // 5. Masukkan ke log_stok dengan kolom yang spesifik (BB atau BSJ)
            if ($tipe == 'BB') {
                $sql_log = "INSERT INTO log_stok (id_bb, id_bsj, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan, tgl_log) 
                            VALUES ('$real_id', NULL, 0, '$qty_final', 'Waste', '$id_gudang', '$sisa_sekarang', '$keterangan_log', NOW())";
            } else {
                $sql_log = "INSERT INTO log_stok (id_bb, id_bsj, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan, tgl_log) 
                            VALUES (NULL, '$real_id', 0, '$qty_final', 'Waste', '$id_gudang', '$sisa_sekarang', '$keterangan_log', NOW())";
            }
            mysqli_query($koneksi, $sql_log);
        }
        // ----------------------------------------------------------------
    }

    // --- PERBAIKAN: Notifikasi dinamis berdasarkan status ---
    $msg = ($status_val === 'Disetujui') 
            ? "Waste $kode_waste Berhasil Disimpan & Stok Dipotong." 
            : "Laporan $kode_waste Berhasil Dikirim, Menunggu Validasi Purchasing.";
            
    header("Location: waste.php?status=success&msg=" . urlencode($msg));
    exit();
} else {
    header("Location: waste.php");
    exit();
}
?>