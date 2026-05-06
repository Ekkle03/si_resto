<?php
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_POST['id_bsj']) && isset($_POST['qty_bom'])) {
    $id_bsj = (int)$_POST['id_bsj'];
    $qty_bom = floatval($_POST['qty_bom']);
    
    // TANGKAP ID GUDANG (Default 5 untuk Produksi Berjenjang)
    $id_gudang_cek = isset($_POST['id_gudang']) ? (int)$_POST['id_gudang'] : 5;
    $errors = [];

    // Ambil nama gudang untuk pesan notifikasi
    $q_nama_gudang = mysqli_query($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang = '$id_gudang_cek'");
    $d_nama_gudang = mysqli_fetch_assoc($q_nama_gudang);
    $nama_gudang_teks = $d_nama_gudang ? $d_nama_gudang['nama_gudang'] : "Gudang";

    // 1. Query ambil detail bahan resep yang akan diproduksi
    $sql_bom = "SELECT b.id_bb, b.id_bsj as id_bsj_bahan, b.qty as qty_per_resep, 
                       b.id_satuan as satuan_bom, s_bom.nama_satuan as nama_satuan_bom,
                       bb.nama_bb, bsj.nama_bsj as nama_bsj_bahan,
                       bb.id_satuan as satuan_stok_bb, bsj.id_satuan as satuan_stok_bsj
                FROM master_bom b
                LEFT JOIN master_satuan s_bom ON b.id_satuan = s_bom.id_satuan
                LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
                LEFT JOIN master_bahan_setengah_jadi bsj ON b.id_bsj = bsj.id_bsj
                WHERE b.id_induk = '$id_bsj' AND b.tipe_bom = 'BSJ'";
    
    $query_bom = mysqli_query($koneksi, $sql_bom);

    while ($row = mysqli_fetch_assoc($query_bom)) {
        $qty_pakai_resep = $qty_bom * floatval($row['qty_per_resep']); // Kebutuhan dalam satuan resep (contoh: Gram)
        $id_item = !empty($row['id_bb']) ? $row['id_bb'] : $row['id_bsj_bahan'];
        $tipe_item = !empty($row['id_bb']) ? 'BB' : 'BSJ';
        $satuan_stok_id = !empty($row['id_bb']) ? $row['satuan_stok_bb'] : $row['satuan_stok_bsj'];
        $nama_item = $row['nama_bb'] ?? $row['nama_bsj_bahan'];

        // --- A. LOGIKA KONVERSI KEBUTUHAN SAAT INI ---
        $qty_butuh_dlm_stok = $qty_pakai_resep; // Default sama jika tidak ada konversi
        $nilai_konversi = 1;

        // Jika satuan di resep (Gram) beda dengan satuan fisik gudang (Bungkus)
        if ($row['satuan_bom'] != $satuan_stok_id) {
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi 
                                              WHERE id_komponen = '$id_item' 
                                              AND tipe_bahan = '$tipe_item'
                                              AND satuan_besar = '".$row['satuan_bom']."'
                                              AND satuan_kecil = '$satuan_stok_id'");
            $d_konv = mysqli_fetch_assoc($q_konv);
            if ($d_konv) {
                $nilai_konversi = floatval($d_konv['nilai_konversi']);
                $qty_butuh_dlm_stok = $qty_pakai_resep / $nilai_konversi; // Konversi kebutuhan resep ke satuan fisik gudang
            }
        }

        // --- B. CEK STOK FISIK DINAMIS (Berdasarkan Parameter $id_gudang_cek) ---
        $filter_item = ($tipe_item == 'BB') ? "id_bb = '$id_item'" : "id_bsj = '$id_item'";
        $sql_stok = "SELECT SUM(jumlah) as total_stok 
                     FROM stok_bahan 
                     WHERE $filter_item 
                     AND id_gudang = '$id_gudang_cek'"; 
        
        $q_stok = mysqli_query($koneksi, $sql_stok);
        $d_stok = mysqli_fetch_assoc($q_stok);
        $stok_fisik = ($d_stok['total_stok']) ? floatval($d_stok['total_stok']) : 0;

        // --- C. HITUNG RENCANA MENGGANTUNG (ATP LOGIC) ---
        // Cari semua produksi status 'Rencana' yang menggunakan bahan baku ini
        $sql_gantung = "SELECT p.qty_rencana, mb.target_hasil, mb.qty, mb.id_satuan as satuan_bom
                        FROM produksi p
                        JOIN master_bom mb ON p.id_bsj = mb.id_induk AND mb.tipe_bom = 'BSJ'
                        WHERE p.status = 'Rencana' AND " . ($tipe_item == 'BB' ? "mb.id_bb = '$id_item'" : "mb.id_bsj = '$id_item'");
        $q_gantung = mysqli_query($koneksi, $sql_gantung);
        
        $total_gantung_stok = 0;
        while ($g = mysqli_fetch_assoc($q_gantung)) {
            $kebutuhan_resep_gantung = (floatval($g['qty_rencana']) / floatval($g['target_hasil'])) * floatval($g['qty']);
            $kebutuhan_stok_gantung = $kebutuhan_resep_gantung;

            // Konversikan juga rencana menggantung ke satuan fisik gudang jika beda
            if ($g['satuan_bom'] != $satuan_stok_id) {
                $q_konv_g = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi 
                                                    WHERE id_komponen = '$id_item' 
                                                    AND tipe_bahan = '$tipe_item'
                                                    AND satuan_besar = '".$g['satuan_bom']."'
                                                    AND satuan_kecil = '$satuan_stok_id'");
                if ($d_konv_g = mysqli_fetch_assoc($q_konv_g)) {
                    $kebutuhan_stok_gantung = $kebutuhan_resep_gantung / floatval($d_konv_g['nilai_konversi']);
                }
            }
            $total_gantung_stok += $kebutuhan_stok_gantung; // Total yg dibooking
        }

        // --- D. KALKULASI SISA STOK TERSEDIA (AVAILABLE TO PROMISE) ---
        // Stok yang benar-benar bisa dipakai = Fisik - Booking Rencana Lain
        $stok_tersedia = $stok_fisik - $total_gantung_stok;

        if ($stok_tersedia < $qty_butuh_dlm_stok) {
            // Jika kurang, kita beritahu user dalam satuan RESEP
            $kurang_stok = $qty_butuh_dlm_stok - $stok_tersedia;
            $kurang_resep = $kurang_stok * $nilai_konversi; // Balikin ke Gram/Buah/dll
            
            // Siapin pesan error informatif
            $pesan = "Stok <b>$nama_item</b> di $nama_gudang_teks kurang " . (float)round($kurang_resep, 2) . " " . $row['nama_satuan_bom'] . ". ";
            
            if ($total_gantung_stok > 0) {
                $pesan .= "<br><small class='text-danger'>(Sebagian stok sebesar <b>".round($total_gantung_stok * $nilai_konversi, 2)." ".$row['nama_satuan_bom']."</b> saat ini sedang dialokasikan untuk Rencana Produksi lain yang belum diselesaikan.)</small>";
            } else {
                $pesan .= "<br><small class='text-danger'>(Cek Gudang Utama dan buat Request Barang)</small>";
            }

            $errors[] = $pesan;
        }
    }

    // Bersihkan buffer JSON agar murni
    ob_clean();
    if (count($errors) > 0) {
        echo json_encode(['status' => 'error', 'message' => implode("<br><br>", $errors)]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Bahan tersedia di '.$nama_gudang_teks.' dan siap dieksekusi.']);
    }
    exit;
}
?>