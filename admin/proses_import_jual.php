<?php
session_start();
include("../config/koneksi_mysql.php");

if (isset($_POST['btn_import'])) {
    $tgl_trx = mysqli_real_escape_string($koneksi, $_POST['tgl_trx']);
    $tgl_format = date('Ymd', strtotime($tgl_trx));
    
    // =================================================================
    // 0. SATPAM LAPIS DUA: CEK TANGGAL DUPLIKAT
    // =================================================================
    $cek_tgl = mysqli_query($koneksi, "SELECT id_jual FROM menu_terjual WHERE tanggal_transaksi = '$tgl_trx'");
    if (mysqli_num_rows($cek_tgl) > 0) {
        header("Location: menu_terjual.php?msg=Gagal! Data penjualan untuk tanggal tersebut sudah ada.");
        exit();
    }

    // 1. LOGIKA KODE UNIK TRANSAKSI
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
    
    // 2. SIMPAN HEADER TRANSAKSI
    $sql_header = "INSERT INTO menu_terjual (kode_transaksi, tanggal_transaksi) VALUES ('$kode_trx', '$tgl_trx')";
    mysqli_query($koneksi, $sql_header);
    $id_jual = mysqli_insert_id($koneksi);

    $total_porsi_terjual = 0; // Buat nampung total keseluruhan QTY

    // --- BACA PAKSA (FORCE READ) CSV ---
    $file_content = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (count($file_content) > 0) {
        foreach ($file_content as $line) {
            $separator = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line, $separator);

            $nama_item_csv = isset($data[0]) ? trim($data[0], " \"'") : "";
            $qty_laku = 0;

            for ($j = 1; $j < count($data); $j++) {
                $potensial_qty = floatval(str_replace(',', '.', trim($data[$j], " \"'")));
                if ($potensial_qty > 0) {
                    $qty_laku = $potensial_qty;
                    break; 
                }
            }

            $lower_nama = strtolower($nama_item_csv);
            $is_metadata = (strpos($lower_nama, 'periode') !== false || 
                            strpos($lower_nama, 'filter') !== false || 
                            strpos($lower_nama, 'cabang') !== false || 
                            strpos($lower_nama, 'laporan') !== false || 
                            strpos($lower_nama, 'total') !== false || 
                            strpos($lower_nama, 'menu') !== false || 
                            strpos($lower_nama, 'item') !== false);

            if ($nama_item_csv != "" && !$is_metadata && $qty_laku > 0) {
                $nama_bersih = mysqli_real_escape_string($koneksi, $nama_item_csv);
                
                // SKENARIO A: Cek apakah item adalah MENU
                $q_menu = mysqli_query($koneksi, "SELECT id_menu, nama_menu FROM master_menu WHERE nama_menu = '$nama_bersih'");
                
                if (mysqli_num_rows($q_menu) > 0) {
                    $row_m = mysqli_fetch_assoc($q_menu);
                    $id_menu = $row_m['id_menu'];
                    $nama_menu_real = $row_m['nama_menu'];

                    mysqli_query($koneksi, "INSERT INTO detail_menu_terjual (id_jual, id_menu, qty_terjual) 
                                            VALUES ('$id_jual', '$id_menu', '$qty_laku')");
                    
                    potongStokBOM($id_menu, $nama_menu_real, $qty_laku, $kode_trx, $koneksi);
                    
                    // Tambahkan ke total QTY (Khusus Menu)
                    $total_porsi_terjual += $qty_laku; 
                } 
                else {
                    // SKENARIO B: Jika bukan Menu, cek apakah item BAHAN BAKU (Kotak Makan/Kresek)
                    $q_bb = mysqli_query($koneksi, "SELECT id_bb, nama_bb FROM master_bahan_baku WHERE nama_bb = '$nama_bersih'");
                    
                    if (mysqli_num_rows($q_bb) > 0) {
                        $row_bb = mysqli_fetch_assoc($q_bb);
                        $id_bb = $row_bb['id_bb'];
                        $nama_bb_real = $row_bb['nama_bb'];
                        
                        $id_gudang_ops = 2; // Target Gudang Oprasional
                        $keterangan = "Pemakaian Ekstra / Jual $nama_bb_real ($qty_laku) - Ref: $kode_trx";
                        
                        mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bb, id_gudang, jumlah) 
                                                VALUES ('$id_bb', '$id_gudang_ops', -$qty_laku) 
                                                ON DUPLICATE KEY UPDATE jumlah = jumlah - $qty_laku");
                        
                        $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
                        $d_sisa = mysqli_fetch_assoc($q_sisa);
                        $sisa_akhir = $d_sisa['jumlah'] ?? 0;

                        mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_keluar, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                                VALUES ('$id_bb', '$qty_laku', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
                        
                        // Tambahkan ke total QTY (Khusus Packaging/Extra)
                        $total_porsi_terjual += $qty_laku;
                    }
                }
            }
        }
    }

    // Update total (12 Item) ke header
    mysqli_query($koneksi, "UPDATE menu_terjual SET total_item = '$total_porsi_terjual' WHERE id_jual = '$id_jual'");

    header("Location: menu_terjual.php?msg=Sukses! Berhasil memproses total $total_porsi_terjual Item sesuai CSV.");
    exit();
}

/**
 * FUNGSI POTONG STOK BOM (UNTUK MENU)
 */
function potongStokBOM($id_menu, $nama_menu, $qty_laku, $kode_trx, $koneksi) {
    $id_gudang_ops = 2; 

    $sql_bom = "SELECT * FROM master_bom WHERE id_induk = '$id_menu' AND tipe_bom = 'MENU'";
    $query_bom = mysqli_query($koneksi, $sql_bom);

    while ($bom = mysqli_fetch_assoc($query_bom)) {
        $jumlah_pakai = (float)$bom['qty'] * $qty_laku;
        $unit_di_bom = (int)$bom['id_satuan'];
        $keterangan = "Jual $nama_menu ($qty_laku porsi) - Ref: $kode_trx";

        if (!empty($bom['id_bb'])) {
            $id_bb = $bom['id_bb'];
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi 
                                              WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $qty_potong_final = $jumlah_pakai;
            if ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) {
                $qty_potong_final = $jumlah_pakai * floatval($d_konv['nilai_konversi']);
            }

            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bb, id_gudang, jumlah) 
                                    VALUES ('$id_bb', '$id_gudang_ops', -$qty_potong_final) 
                                    ON DUPLICATE KEY UPDATE jumlah = jumlah - $qty_potong_final");
            
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_keluar, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bb', '$qty_potong_final', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
        } 
        elseif (!empty($bom['id_bsj'])) {
            $id_bsj = $bom['id_bsj'];
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi 
                                              WHERE id_komponen = '$id_bsj' AND tipe_bahan = 'BSJ' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $qty_potong_final = $jumlah_pakai;
            if ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) {
                $qty_potong_final = $jumlah_pakai * floatval($d_konv['nilai_konversi']);
            }

            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bsj, id_gudang, jumlah) 
                                    VALUES ('$id_bsj', '$id_gudang_ops', -$qty_potong_final) 
                                    ON DUPLICATE KEY UPDATE jumlah = jumlah - $qty_potong_final");

            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, qty_keluar, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bsj', '$qty_potong_final', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
        }
    }
}
?>