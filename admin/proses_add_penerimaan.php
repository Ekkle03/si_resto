<?php
session_start();
include("../config/koneksi_mysql.php");

$id_karyawan_login = $_SESSION['id_karyawan'] ?? 1; 

if (isset($_POST['kode_penerimaan'])) {
    $kode_penerimaan = mysqli_real_escape_string($koneksi, $_POST['kode_penerimaan']);
    $id_pembelian    = mysqli_real_escape_string($koneksi, $_POST['id_pembelian']);
    $tgl_terima      = mysqli_real_escape_string($koneksi, $_POST['tgl_terima']);
    $keterangan      = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // 1. Simpan Header Penerimaan
    $sql_header = "INSERT INTO penerimaan (kode_penerimaan, id_pembelian, tgl_terima, keterangan) 
                   VALUES ('$kode_penerimaan', '$id_pembelian', '$tgl_terima', '$keterangan')";
    
    if (mysqli_query($koneksi, $sql_header)) {
        $id_penerimaan = mysqli_insert_id($koneksi);

        $id_bb_arr      = $_POST['id_bb'];
        $qty_terima_arr = $_POST['qty_terima']; 
        $qty_rusak_arr  = $_POST['qty_rusak'] ?? []; 

        $id_header_waste_baru = null; 

        foreach ($id_bb_arr as $key => $id_bb) {
            $id_bb      = mysqli_real_escape_string($koneksi, $id_bb);
            $qty_input  = (float)$qty_terima_arr[$key];
            $qty_rusak_input = isset($qty_rusak_arr[$key]) ? (float)$qty_rusak_arr[$key] : 0;

            if (!empty($id_bb)) {
                
                $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
                $d_konv = mysqli_fetch_assoc($q_konv);
                $faktor = ($d_konv && $d_konv['nilai_konversi'] > 0) ? (float)$d_konv['nilai_konversi'] : 1;

                $qty_terima_final = $qty_input * $faktor;
                $qty_rusak_final  = $qty_rusak_input * $faktor;
                
                // Total Fisik = Apa yang diinput Diterima + Apa yang diinput Waste
                $qty_total_fisik  = $qty_terima_final + $qty_rusak_final; 

                if ($qty_total_fisik > 0) {
                    
                    // --- 1. PROSES MASUK SEMUA FISIK KE GUDANG (+Total) ---
                    mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bb, id_gudang, jumlah) VALUES ('$id_bb', 1, '$qty_total_fisik') ON DUPLICATE KEY UPDATE jumlah = jumlah + $qty_total_fisik");

                    $q_stok = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = 1");
                    $saldo_sementara = mysqli_fetch_assoc($q_stok)['jumlah'];

                    // Catat Log Masuk
                    $uraian_log = "Penerimaan PB: " . $kode_penerimaan;
                    mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, id_gudang, qty_masuk, qty_keluar, jenis_mutasi, sisa_stok, keterangan) VALUES ('$id_bb', 1, '$qty_total_fisik', 0, 'Penerimaan', '$saldo_sementara', '$uraian_log')");

                    // Simpan ke Detail Penerimaan
                    if ($qty_terima_final > 0) {
                        mysqli_query($koneksi, "INSERT INTO detail_penerimaan (id_penerimaan, id_bb, qty_terima) VALUES ('$id_penerimaan', '$id_bb', '$qty_terima_final')");
                    }

                    // --- 2. JIKA ADA WASTE, POTONG STOK & CATAT (-Waste) ---
                    if ($qty_rusak_final > 0) {
                        
                        mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah - $qty_rusak_final WHERE id_bb = '$id_bb' AND id_gudang = 1");

                        $q_stok_akhir = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = 1");
                        $saldo_setelah_waste = mysqli_fetch_assoc($q_stok_akhir)['jumlah'];

                        // Catat Log Keluar (Waste)
                        $uraian_log_waste = "Barang Cacat saat Terima PB: " . $kode_penerimaan;
                        mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, id_gudang, qty_masuk, qty_keluar, jenis_mutasi, sisa_stok, keterangan) VALUES ('$id_bb', 1, 0, '$qty_rusak_final', 'Waste', '$saldo_setelah_waste', '$uraian_log_waste')");

                        // --- REVISI KODE WASTE: STRICT NYARI WST-00X ---
                        if ($id_header_waste_baru === null) {
                            // Regex untuk ngambil angka HANYA dari format WST- diikuti 1 sampai 4 digit angka
                            $q_kw = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(kode_waste, 5) AS UNSIGNED)) as mk 
                                                            FROM header_waste 
                                                            WHERE kode_waste REGEXP '^WST-[0-9]{1,4}$'");
                            $d_kw = mysqli_fetch_assoc($q_kw);
                            $no_kw = ($d_kw['mk'] != null) ? (int)$d_kw['mk'] + 1 : 1;
                            $kode_waste_baru = "WST-" . sprintf("%03s", $no_kw);

                            $sql_hw = "INSERT INTO header_waste (kode_waste, tgl_waste, id_gudang, id_karyawan) VALUES ('$kode_waste_baru', '$tgl_terima', 1, '$id_karyawan_login')";
                            mysqli_query($koneksi, $sql_hw);
                            $id_header_waste_baru = mysqli_insert_id($koneksi);
                        }

                        // --- UPLOAD FOTO ---
                        $nama_foto_simpan = "NULL"; 
                        if (isset($_FILES['foto_waste']['name'][$key]) && $_FILES['foto_waste']['error'][$key] == 0) {
                            $file_tmp = $_FILES['foto_waste']['tmp_name'][$key];
                            $file_ext = pathinfo($_FILES['foto_waste']['name'][$key], PATHINFO_EXTENSION);
                            
                            $nama_foto = "WST_" . time() . "_" . $id_bb . "." . $file_ext;
                            $dir_upload = "../assets/img/waste/"; 
                            
                            if (!is_dir($dir_upload)) { mkdir($dir_upload, 0777, true); }
                            
                            if (move_uploaded_file($file_tmp, $dir_upload . $nama_foto)) {
                                $nama_foto_simpan = "'$nama_foto'"; 
                            }
                        }

                        // Insert ke Detail Waste
                        $alasan_waste = "Rusak/Cacat dari Penerimaan: " . $kode_penerimaan;
                        $sql_dw = "INSERT INTO detail_waste (id_header_waste, id_bb, qty_waste, alasan, sumber, id_referensi_sumber, foto_bukti) 
                                   VALUES ('$id_header_waste_baru', '$id_bb', '$qty_rusak_final', '$alasan_waste', 'Penerimaan', '$id_penerimaan', $nama_foto_simpan)";
                        mysqli_query($koneksi, $sql_dw);
                    }
                }
            }
        }

        header("Location: penerimaan.php?status=success&msg=Penerimaan sukses! Barang bagus dan waste telah terdata di log stok dengan akurat.");
        exit();
    } else {
        echo "Error Header: " . mysqli_error($koneksi);
    }
} else {
    header("Location: penerimaan.php");
}
?>