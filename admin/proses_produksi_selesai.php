<?php
session_start();
include("../config/koneksi_mysql.php");

// Set Zona Waktu agar jam log dan inputan akurat
date_default_timezone_set('Asia/Jakarta'); 

if (isset($_POST['id_produksi'])) {
    $id_produksi = (int)$_POST['id_produksi'];
    $qty_realisasi = floatval($_POST['qty_realisasi']); 
    $qty_waste = isset($_POST['qty_waste']) ? floatval($_POST['qty_waste']) : 0; 
    
    $tgl_selesai = date('Y-m-d H:i:s');

    // 1. AMBIL DATA PRODUKSI & KONVERSI PRODUK JADI
    $q_prd_head = mysqli_query($koneksi, "SELECT p.id_bsj, p.kode_produksi, b.nama_bsj 
                                          FROM produksi p 
                                          JOIN master_bahan_setengah_jadi b ON p.id_bsj = b.id_bsj 
                                          WHERE p.id_produksi = '$id_produksi'");
    $d_prd_head = mysqli_fetch_assoc($q_prd_head);
    $id_bsj_utama = $d_prd_head['id_bsj'];
    $kode_produksi = $d_prd_head['kode_produksi'];
    $nama_produk_jadi = $d_prd_head['nama_bsj'];

    // Ambil nilai konversi produk HASIL JADI (BSJ Tahap 2 biasanya)
    $q_konv_jadi = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi 
                                           WHERE id_komponen = '$id_bsj_utama' AND tipe_bahan = 'BSJ' LIMIT 1");
    $d_konv_jadi = mysqli_fetch_assoc($q_konv_jadi);
    $nilai_konv_jadi = ($d_konv_jadi) ? floatval($d_konv_jadi['nilai_konversi']) : 1;

    // Hitung angka untuk Database (Satuan Kecil)
    $qty_total_produksi_besar = $qty_realisasi + $qty_waste; 
    $qty_masuk_db = $qty_total_produksi_besar * $nilai_konv_jadi; 
    $qty_waste_db = $qty_waste * $nilai_konv_jadi; 

    mysqli_begin_transaction($koneksi);

    try {
        // --- 2. POTONG BAHAN BAKU / BSJ BAHAN DI GUDANG PRODUKSI (ID 5) ---
        $q_bom = mysqli_query($koneksi, "SELECT * FROM master_bom WHERE id_induk = '$id_bsj_utama' AND tipe_bom = 'BSJ'");
        if (mysqli_num_rows($q_bom) == 0) throw new Exception("Resep tidak ditemukan.");

        while ($row = mysqli_fetch_assoc($q_bom)) {
            // Hitung kebutuhan berdasarkan porsi/sachet yang dibuat
            $rasio = $qty_total_produksi_besar / floatval($row['target_hasil']);
            $qty_pakai_resep = $rasio * floatval($row['qty']); 
            
            $col = !empty($row['id_bb']) ? 'id_bb' : 'id_bsj';
            $id_i = $row[$col];
            $tipe_bahan_i = !empty($row['id_bb']) ? 'BB' : 'BSJ';
            $unit_di_bom = (int)$row['id_satuan'];

            // --- LOGIKA PERBAIKAN: Cek Konversi Bahan Pembentuk ---
            $q_konv_item = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_besar FROM master_konversi 
                                                   WHERE id_komponen = '$id_i' AND tipe_bahan = '$tipe_bahan_i' LIMIT 1");
            $d_konv_item = mysqli_fetch_assoc($q_konv_item);
            
            // JIKA satuan di BOM adalah SATUAN BESAR, maka kalikan dengan nilai konversi.
            $qty_akhir_potong = $qty_pakai_resep;
            if ($d_konv_item && (int)$d_konv_item['satuan_besar'] == $unit_di_bom) {
                $qty_akhir_potong = $qty_pakai_resep * floatval($d_konv_item['nilai_konversi']);
            }

            // Update Stok Gudang Produksi (ID 5) dengan nilai yang sudah dikonversi (Satuan Kecil)
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah - $qty_akhir_potong WHERE $col = '$id_i' AND id_gudang = 5");
            
            // Ambil sisa stok terbaru untuk log
            $q_sisa_prod = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_i' AND id_gudang = 5");
            $sisa_p = mysqli_fetch_assoc($q_sisa_prod)['jumlah'] ?? 0;

            // Catat Log Penggunaan Bahan 
            mysqli_query($koneksi, "INSERT INTO log_stok ($col, id_gudang, qty_keluar, jenis_mutasi, sisa_stok, keterangan, tgl_log) 
                                    VALUES ('$id_i', 5, '$qty_akhir_potong', 'Produksi', '$sisa_p', 'Bahan $kode_produksi ($nama_produk_jadi)', '$tgl_selesai')");
        }

        // --- 3. TAMBAH TOTAL HASIL KE GUDANG UTAMA (ID 1) ---
        $cek_stok = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj_utama' AND id_gudang = 1 FOR UPDATE");
        if (mysqli_num_rows($cek_stok) > 0) {
            $old_qty = mysqli_fetch_assoc($cek_stok)['jumlah'];
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $qty_masuk_db WHERE id_bsj = '$id_bsj_utama' AND id_gudang = 1");
            $sisa_akhir = $old_qty + $qty_masuk_db;
        } else {
            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_bsj, id_gudang, jumlah) VALUES ('$id_bsj_utama', 1, '$qty_masuk_db')");
            $sisa_akhir = $qty_masuk_db;
        }

        // Log Masuk (Hasil Total Produksi)
        mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, id_gudang, qty_masuk, jenis_mutasi, sisa_stok, keterangan, tgl_log) 
                                VALUES ('$id_bsj_utama', 1, '$qty_masuk_db', 'Produksi', '$sisa_akhir', 'Hasil $kode_produksi ($nama_produk_jadi)', '$tgl_selesai')");

        // --- 4. JIKA ADA WASTE (TERMASUK UPLOAD FOTO) ---
        if ($qty_waste > 0) {
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah - $qty_waste_db WHERE id_bsj = '$id_bsj_utama' AND id_gudang = 1");
            
            $q_sisa_w = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj_utama' AND id_gudang = 1");
            $saldo_w = mysqli_fetch_assoc($q_sisa_w)['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, id_gudang, qty_keluar, jenis_mutasi, sisa_stok, keterangan, tgl_log) 
                                    VALUES ('$id_bsj_utama', 1, '$qty_waste_db', 'Waste', '$saldo_w', 'Waste $kode_produksi ($nama_produk_jadi)', '$tgl_selesai')");

            // --- BLOK UPLOAD FOTO BUKTI WASTE ---
            $foto_waste_name = "NULL"; // Default jika tidak upload foto
            if (isset($_FILES['foto_waste']) && $_FILES['foto_waste']['error'] === 0) {
                $file_name = $_FILES['foto_waste']['name'];
                $file_tmp  = $_FILES['foto_waste']['tmp_name'];
                $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = array('jpg', 'jpeg', 'png');

                if (in_array($file_ext, $allowed_ext)) {
                    // Buat nama file unik
                    $new_name = "waste_" . time() . "_" . uniqid() . "." . $file_ext;
                    $upload_path = "../assets/img/waste/" . $new_name; 

                    // Pindahkan file dari temp ke folder tujuan
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $foto_waste_name = "'$new_name'"; // Tambah petik satu agar siap di-insert ke SQL
                    }
                }
            }
            // --- AKHIR BLOK UPLOAD FOTO ---

            $id_pelapor = $_SESSION['id_karyawan'] ?? 1;
            $q_max_w = mysqli_query($koneksi, "SELECT MAX(id_header_waste) as max_id FROM header_waste");
            $next_id = (mysqli_fetch_assoc($q_max_w)['max_id'] ?? 0) + 1;
            $kode_w_baru = "WST-" . str_pad($next_id, 3, "0", STR_PAD_LEFT);

            mysqli_query($koneksi, "INSERT INTO header_waste (kode_waste, tgl_waste, id_gudang, id_karyawan) 
                                    VALUES ('$kode_w_baru', '".date('Y-m-d')."', 1, '$id_pelapor')");
            $id_h_waste = mysqli_insert_id($koneksi);

            // Masukkan data detail waste BESERTA nama fotonya
            mysqli_query($koneksi, "INSERT INTO detail_waste (id_header_waste, id_bsj, qty_waste, alasan, foto_bukti, sumber, id_referensi_sumber) 
                                    VALUES ('$id_h_waste', '$id_bsj_utama', '$qty_waste', 'Gagal Produksi $kode_produksi', $foto_waste_name, 'Produksi', '$id_produksi')");
        }

        // 5. UPDATE STATUS TABEL PRODUKSI
        mysqli_query($koneksi, "UPDATE produksi SET qty_realisasi = '$qty_realisasi', tgl_produksi = '$tgl_selesai', status = 'Selesai' WHERE id_produksi = '$id_produksi'");

        mysqli_commit($koneksi);
        $_SESSION['flash_msg'] = "Berhasil! Produksi $nama_produk_jadi selesai. Stok diperbarui.";

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['flash_msg'] = "Gagal: " . $e->getMessage();
    }

    header("Location: produksi.php");
    exit();
}
?>