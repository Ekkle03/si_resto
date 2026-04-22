<?php
session_start();
include("../config/koneksi_mysql.php");

if (isset($_POST['btn_import'])) {
    $tgl_trx = $_POST['tgl_trx'];
    $tgl_format = date('Ymd', strtotime($tgl_trx));
    
    // 1. LOGIKA KODE UNIK
    $q_last = mysqli_query($koneksi, "SELECT kode_transaksi FROM menu_terjual WHERE tanggal_transaksi = '$tgl_trx' ORDER BY id_jual DESC LIMIT 1");
    $d_last = mysqli_fetch_assoc($q_last);
    
    if ($d_last) {
        $last_no = (int) substr($d_last['kode_transaksi'], -3);
        $next_no = $last_no + 1;
    } else {
        $next_no = 1;
    }
    $kode_trx = "JUL-" . $tgl_format . "-" . str_pad($next_no, 3, '0', STR_PAD_LEFT);
    
    $filename = $_FILES['file_csv']['tmp_name'];
    if (!$filename) {
        header("Location: menu_terjual.php?msg=Gagal! File tidak ditemukan.");
        exit();
    }
    
    // 2. Simpan Header Transaksi
    $sql_header = "INSERT INTO menu_terjual (kode_transaksi, tanggal_transaksi) VALUES ('$kode_trx', '$tgl_trx')";
    mysqli_query($koneksi, $sql_header);
    $id_jual = mysqli_insert_id($koneksi);

    $total_menu_terproses = 0;

    // --- UPGRADE: BACA PAKSA (FORCE READ) CSV ---
    $file_content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (count($file_content) > 0) {
        foreach ($file_content as $line) {
            // Deteksi koma atau titik koma otomatis
            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line, $separator);

            $nama_menu_csv = isset($data[0]) ? trim($data[0], " \"'") : "";
            $qty_laku = 0;

            // Cari angka QTY di kolom sisanya (mulai dari index 1 ke atas)
            for ($j = 1; $j < count($data); $j++) {
                $potensial_qty = floatval(str_replace(',', '.', trim($data[$j], " \"'")));
                if ($potensial_qty > 0) {
                    $qty_laku = $potensial_qty;
                    break; // Ketemu angkanya, langsung stop pencarian di baris ini
                }
            }

            // FILTER BLACKLIST
            $lower_nama = strtolower($nama_menu_csv);
            $is_metadata = (strpos($lower_nama, 'periode') !== false || 
                            strpos($lower_nama, 'filter') !== false || 
                            strpos($lower_nama, 'cabang') !== false || 
                            strpos($lower_nama, 'laporan') !== false || 
                            strpos($lower_nama, 'total') !== false || 
                            strpos($lower_nama, 'menu') !== false || 
                            strpos($lower_nama, 'item') !== false);

            if ($nama_menu_csv != "" && !$is_metadata && $qty_laku > 0) {
                $nama_bersih = mysqli_real_escape_string($koneksi, $nama_menu_csv);
                
                $q_menu = mysqli_query($koneksi, "SELECT id_menu, nama_menu FROM master_menu WHERE nama_menu = '$nama_bersih'");
                
                if (mysqli_num_rows($q_menu) > 0) {
                    $row_m = mysqli_fetch_assoc($q_menu);
                    $id_menu = $row_m['id_menu'];
                    $nama_menu_real = $row_m['nama_menu'];

                    mysqli_query($koneksi, "INSERT INTO detail_menu_terjual (id_jual, id_menu, qty_terjual) 
                                            VALUES ('$id_jual', '$id_menu', '$qty_laku')");
                    
                    // 3. EKSEKUSI POTONG STOK DI GUDANG OPRASIONAL (ID: 2)
                    potongStokBOM($id_menu, $nama_menu_real, $qty_laku, $kode_trx, $koneksi);
                    
                    $total_menu_terproses++;
                }
            }
        }
    }

    mysqli_query($koneksi, "UPDATE menu_terjual SET total_item = '$total_menu_terproses' WHERE id_jual = '$id_jual'");

    header("Location: menu_terjual.php?msg=Sukses! Berhasil memproses $total_menu_terproses menu.");
    exit();
}

/**
 * FUNGSI POTONG STOK OPRASIONAL DENGAN KONVERSI & UPSERT (ANTI-SILENT FAIL)
 */
function potongStokBOM($id_menu, $nama_menu, $qty_laku, $kode_trx, $koneksi) {
    $id_gudang_ops = 2; // Target Gudang Oprasional

    $sql_bom = "SELECT * FROM master_bom WHERE id_induk = '$id_menu' AND tipe_bom = 'MENU'";
    $query_bom = mysqli_query($koneksi, $sql_bom);

    while ($bom = mysqli_fetch_assoc($query_bom)) {
        $jumlah_pakai = (float)$bom['qty'] * $qty_laku;
        $unit_di_bom = (int)$bom['id_satuan'];
        $keterangan = "Jual $nama_menu ($qty_laku porsi) - Ref: $kode_trx";

        // CEK BAHAN BAKU (BB)
        if (!empty($bom['id_bb'])) {
            $id_bb = $bom['id_bb'];
            
            // Cek Konversi Satuan
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_besar FROM master_konversi 
                                              WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $qty_potong_final = $jumlah_pakai;
            if ($d_konv && (int)$d_konv['satuan_besar'] == $unit_di_bom) {
                $qty_potong_final = $jumlah_pakai * floatval($d_konv['nilai_konversi']);
            }

            // JURUS SAKTI: UPSERT (Insert if not exist, Update if exist)
            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bb, id_gudang, jumlah) 
                                    VALUES ('$id_bb', '$id_gudang_ops', -$qty_potong_final) 
                                    ON DUPLICATE KEY UPDATE jumlah = jumlah - $qty_potong_final");
            
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_keluar, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bb', '$qty_potong_final', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
            
            cekMinimalStokBB($id_bb, $koneksi);
        } 
        // CEK BAHAN SETENGAH JADI (BSJ)
        elseif (!empty($bom['id_bsj'])) {
            $id_bsj = $bom['id_bsj'];
            
            // Cek Konversi Satuan
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_besar FROM master_konversi 
                                              WHERE id_komponen = '$id_bsj' AND tipe_bahan = 'BSJ' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $qty_potong_final = $jumlah_pakai;
            if ($d_konv && (int)$d_konv['satuan_besar'] == $unit_di_bom) {
                $qty_potong_final = $jumlah_pakai * floatval($d_konv['nilai_konversi']);
            }

            // JURUS SAKTI: UPSERT (Insert if not exist, Update if exist)
            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bsj, id_gudang, jumlah) 
                                    VALUES ('$id_bsj', '$id_gudang_ops', -$qty_potong_final) 
                                    ON DUPLICATE KEY UPDATE jumlah = jumlah - $qty_potong_final");

            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, qty_keluar, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bsj', '$qty_potong_final', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
            
            cekMinimalStokBSJ($id_bsj, $koneksi);
        }
    }
}

function cekMinimalStokBB($id_bb, $koneksi) {
    $id_gudang_ops = 2;
    $q = mysqli_query($koneksi, "SELECT b.nama_bb, b.stok_minimal, s.jumlah 
                                 FROM master_bahan_baku b 
                                 LEFT JOIN stok_bahan s ON b.id_bb = s.id_bb AND s.id_gudang = '$id_gudang_ops'
                                 WHERE b.id_bb = '$id_bb'");
    $data = mysqli_fetch_assoc($q);

    if ($data && $data['jumlah'] <= $data['stok_minimal']) {
        $cek = mysqli_query($koneksi, "SELECT id_request FROM request_bahan WHERE id_bb = '$id_bb' AND DATE(tgl_request) = CURDATE()");
        if (mysqli_num_rows($cek) == 0) {
            $qty_saran = $data['stok_minimal'] * 2; 
            mysqli_query($koneksi, "INSERT INTO request_bahan (id_bb, qty_request) VALUES ('$id_bb', '$qty_saran')");
        }
    }
}

function cekMinimalStokBSJ($id_bsj, $koneksi) {
    $id_gudang_ops = 2;
    $q = mysqli_query($koneksi, "SELECT b.nama_bsj, b.stok_minimal_bsj, s.jumlah 
                                 FROM master_bahan_setengah_jadi b 
                                 LEFT JOIN stok_bahan s ON b.id_bsj = s.id_bsj AND s.id_gudang = '$id_gudang_ops'
                                 WHERE b.id_bsj = '$id_bsj'");
    $data = mysqli_fetch_assoc($q);
    
    if ($data && $data['jumlah'] <= $data['stok_minimal_bsj']) {
        $cek = mysqli_query($koneksi, "SELECT id_produksi FROM produksi WHERE id_bsj = '$id_bsj' AND status = 'Rencana'");
        if (mysqli_num_rows($cek) == 0) {
             mysqli_query($koneksi, "INSERT INTO produksi (id_bsj, qty_rencana, status) VALUES ('$id_bsj', 10, 'Rencana')");
        }
    }
}
?>