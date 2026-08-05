<?php
session_start();
include("../config/koneksi_mysql.php");

if (isset($_POST['simpan_permanen'])) {
    $id_induk     = (int)$_POST['id_induk'];
    $tipe_bom     = $_POST['tipe_bom']; 
    $source       = isset($_POST['source_from']) ? $_POST['source_from'] : 'master_bsj';
    
    // REVISI: Tangkap Target Hasil (Yield)
    $target_hasil = isset($_POST['target_hasil']) ? (int)$_POST['target_hasil'] : 1;

    if (!isset($_SESSION['keranjang_bom'][$id_induk]) || empty($_SESSION['keranjang_bom'][$id_induk])) {
        $url_asal = ($tipe_bom == 'MENU') ? "buat_bom_menu.php" : "buat_bom_bsj.php";
        header("Location: $url_asal?id=$id_induk&from=$source&msg=" . urlencode("Error: Keranjang kosong!"));
        exit();
    }

    $keranjang = $_SESSION['keranjang_bom'][$id_induk];

    // 1. GENERATE KODE BOM
    $q_max = mysqli_query($koneksi, "SELECT MAX(id_bom) as max_id FROM master_bom");
    $d_max = mysqli_fetch_assoc($q_max);
    $next_id = ($d_max['max_id'] ?? 0) + 1;
    $kode_bom = "RCP-" . $tipe_bom . "-" . str_pad($next_id, 3, '0', STR_PAD_LEFT);

    mysqli_begin_transaction($koneksi);

    try {
        // 2. HAPUS RESEP LAMA
        $sql_clean = "DELETE FROM master_bom WHERE id_induk = ? AND tipe_bom = ?";
        $stmt_clean = mysqli_prepare($koneksi, $sql_clean);
        mysqli_stmt_bind_param($stmt_clean, "is", $id_induk, $tipe_bom);
        mysqli_stmt_execute($stmt_clean);
        mysqli_stmt_close($stmt_clean);

        // 3. PROSES INSERT (TAMBAHKAN KOLOM target_hasil)
        $sql_ins = "INSERT INTO master_bom (kode_bom, tipe_bom, id_induk, id_bb, id_bsj, qty, id_satuan, target_hasil) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_ins = mysqli_prepare($koneksi, $sql_ins);

        foreach ($keranjang as $item) {
            $id_item   = $item['id_item'];
            $tipe_item = $item['tipe_item'];
            $qty_final = $item['qty']; 
            $id_satuan = $item['id_satuan'];

            $id_bb_val = ($tipe_item == 'BB') ? $id_item : null;
            $id_bsj_val = ($tipe_item == 'BSJ') ? $id_item : null;

            // Bind param disesuaikan: ditambahkan "i" di akhir untuk target_hasil (INT)
            mysqli_stmt_bind_param($stmt_ins, "ssiiidii", 
                $kode_bom, $tipe_bom, $id_induk, $id_bb_val, $id_bsj_val, $qty_final, $id_satuan, $target_hasil
            );

            if (!mysqli_stmt_execute($stmt_ins)) {
                throw new Exception("Gagal menyimpan bahan: " . $item['nama_bahan']);
            }
        }

        mysqli_stmt_close($stmt_ins);
        mysqli_commit($koneksi);

        unset($_SESSION['keranjang_bom'][$id_induk]);

        // 4. REDIRECT BERHASIL
        if ($source == 'master_menu') {
            $redirect = "master_menu.php";
        } elseif ($source == 'master_bom') {
            $redirect = "master_bom.php";
        } else {
            $redirect = "master_bahan_setengahjadi.php";
        }

        header("Location: $redirect?msg=" . urlencode("Berhasil: Resep $kode_bom disimpan dengan Target Hasil $target_hasil."));
        exit();

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $url_error = ($tipe_bom == 'MENU') ? "buat_bom_menu.php" : "buat_bom_bsj.php";
        header("Location: $url_error?id=$id_induk&from=$source&msg=" . urlencode("Gagal: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
}