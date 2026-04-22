<?php
session_start();
include("../config/koneksi_mysql.php");

// Set Zona Waktu agar jam log akurat
date_default_timezone_set('Asia/Jakarta');

$id_h = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id_h == 0) { 
    echo json_encode(['status' => 'error', 'msg' => 'ID tidak valid.']); 
    exit; 
}

// Ambil data header dan nama gudang tujuan untuk keperluan Log
$q_h = mysqli_query($koneksi, "SELECT h.kode_request, h.id_gudang_tujuan, g.nama_gudang 
                               FROM header_request h 
                               LEFT JOIN master_gudang g ON h.id_gudang_tujuan = g.id_gudang 
                               WHERE h.id_header_req = '$id_h'");
$d_h = mysqli_fetch_assoc($q_h);
$kode_req = $d_h['kode_request'];
$id_g_tujuan = $d_h['id_gudang_tujuan'];
$nama_g_tujuan = $d_h['nama_gudang'] ?? 'Gudang Tujuan';

mysqli_begin_transaction($koneksi);

try {
    // Ambil item yang sisa hutangnya lebih dari 0
    $q_items = mysqli_query($koneksi, "SELECT r.*, bb.nama_bb, bsj.nama_bsj, s.nama_satuan as sat_bb, s2.nama_satuan as sat_bsj 
                                       FROM request_bahan r 
                                       LEFT JOIN master_bahan_baku bb ON r.id_bb = bb.id_bb 
                                       LEFT JOIN master_satuan s ON bb.id_satuan = s.id_satuan
                                       LEFT JOIN master_bahan_setengah_jadi bsj ON r.id_bsj = bsj.id_bsj
                                       LEFT JOIN master_satuan s2 ON bsj.id_satuan = s2.id_satuan
                                       WHERE r.id_header_req = '$id_h' AND r.qty_sisa > 0");
    
    $pesan_detail = "<div class='text-start' style='font-size:14px;'>";
    $ada_item_diproses = false;

    while ($item = mysqli_fetch_assoc($q_items)) {
        $ada_item_diproses = true;
        $tipe = !empty($item['id_bb']) ? 'BB' : 'BSJ';
        $id_item = ($tipe == 'BB') ? $item['id_bb'] : $item['id_bsj'];
        $col = ($tipe == 'BB') ? 'id_bb' : 'id_bsj';
        $nama_item = $item['nama_bb'] ?? $item['nama_bsj'];
        $satuan = $item['sat_bb'] ?? $item['sat_bsj'];

        // 1. Cek Stok Aktual Gudang Utama (id_gudang = 1)
        $q_stok = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_item' AND id_gudang = 1 FOR UPDATE");
        $stok_db = mysqli_fetch_assoc($q_stok)['jumlah'] ?? 0;

        // 2. Konversi ke Satuan Besar
        $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi WHERE id_komponen = '$id_item' AND tipe_bahan = '$tipe'");
        $konv = mysqli_fetch_assoc($q_konv)['nilai_konversi'] ?? 1;
        $stok_satuan_besar = $stok_db / $konv;
        
        $sisa_hutang = round((float)$item['qty_sisa'], 2);

        // 3. LOGIKA AUTO-PILOT: Tarik stok maksimal sejumlah sisa hutang
        $qty_kirim_besar = round(min($sisa_hutang, $stok_satuan_besar), 2);

        if ($qty_kirim_besar > 0) {
            $qty_terkecil = $qty_kirim_besar * $konv;

            // A. UPDATE GUDANG UTAMA & CATAT LOG KELUAR
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah - $qty_terkecil WHERE $col = '$id_item' AND id_gudang = 1");
            
            $q_sisa_utama = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_item' AND id_gudang = 1");
            $sisa_stok_utama = mysqli_fetch_assoc($q_sisa_utama)['jumlah'];
            
            mysqli_query($koneksi, "INSERT INTO log_stok ($col, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                                    VALUES ('$id_item', '$qty_terkecil', 'Operasional', 1, '$sisa_stok_utama', 'Kirim ke $kode_req ($nama_g_tujuan)')");
            
            // B. UPDATE GUDANG TUJUAN & CATAT LOG MASUK
            $cek_dest = mysqli_query($koneksi, "SELECT id_stok FROM stok_bahan WHERE $col = '$id_item' AND id_gudang = '$id_g_tujuan'");
            if (mysqli_num_rows($cek_dest) > 0) {
                mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $qty_terkecil WHERE $col = '$id_item' AND id_gudang = '$id_g_tujuan'");
            } else {
                mysqli_query($koneksi, "INSERT INTO stok_bahan (id_gudang, $col, jumlah) VALUES ('$id_g_tujuan', '$id_item', '$qty_terkecil')");
            }
            
            $q_sisa_tujuan = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_item' AND id_gudang = '$id_g_tujuan'");
            $sisa_stok_tujuan = mysqli_fetch_assoc($q_sisa_tujuan)['jumlah'];
            
            mysqli_query($koneksi, "INSERT INTO log_stok ($col, qty_masuk, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                                    VALUES ('$id_item', '$qty_terkecil', 'Operasional', '$id_g_tujuan', '$sisa_stok_tujuan', 'Terima dari Gudang Utama ($kode_req)')");

            // C. UPDATE DETAIL NOTA
            $id_req_detail = $item['id_request'];
            mysqli_query($koneksi, "UPDATE request_bahan SET qty_dikirim = qty_dikirim + $qty_kirim_besar, qty_sisa = qty_sisa - $qty_kirim_besar WHERE id_request = '$id_req_detail'");
        }

        // CATAT TEKS LAPORAN UNTUK NOTIFIKASI USER
        if (round($stok_satuan_besar, 2) < $sisa_hutang) {
            $kurangnya = round($sisa_hutang - $qty_kirim_besar, 2);
            $pesan_detail .= "• <b>$nama_item:</b> Diserahkan $qty_kirim_besar <span class='text-danger fw-bold'>(Kurang $kurangnya $satuan)</span><br>";
        } else if ($qty_kirim_besar > 0) {
            $pesan_detail .= "• <b>$nama_item:</b> <span class='text-success'>Terpenuhi sepenuhnya ($qty_kirim_besar $satuan)</span><br>";
        }
    }
    $pesan_detail .= "</div>";

    // 4. LOGIKA PENENTUAN STATUS HEADER NOTA
    $q_cek = mysqli_query($koneksi, "SELECT SUM(qty_sisa) as total_sisa, SUM(qty_dikirim) as total_dikirim FROM request_bahan WHERE id_header_req = '$id_h'");
    $cek_data = mysqli_fetch_assoc($q_cek);
    $total_sisa = floatval($cek_data['total_sisa']);
    $total_dikirim = floatval($cek_data['total_dikirim']);

    // Mencegah bug nota lama yang qty_sisa-nya 0 secara tidak wajar
    if (!$ada_item_diproses && $total_sisa <= 0 && $total_dikirim <= 0) {
        throw new Exception("Data nota tidak valid. Silakan buat nota permintaan baru.");
    }

    if ($total_sisa <= 0) {
        // Kasus: Semua barang sudah dikirim lunas
        mysqli_query($koneksi, "UPDATE header_request SET status = 'Selesai' WHERE id_header_req = '$id_h'");
        $title = "Penyerahan Lunas!";
        $icon = "success";
        $header_pesan = "<p class='text-muted'>Seluruh bahan berhasil diserahkan.</p>";
    } else if ($total_dikirim > 0) {
        // Kasus: Sudah ada yang dikirim, tapi stok gudang kurang (Sebagian)
        mysqli_query($koneksi, "UPDATE header_request SET status = 'Sebagian' WHERE id_header_req = '$id_h'");
        $title = "Dikirim Sebagian";
        $icon = "info";
        $header_pesan = "<p class='mb-2 fw-bold text-dark'>Bahan telah diserahkan sebagian sesuai ketersediaan stok di Gudang Utama.</p>";
    } else {
        // Kasus: Belum ada satupun yang bisa dikirim (Stok Kosong Total)
        mysqli_query($koneksi, "UPDATE header_request SET status = 'Pending' WHERE id_header_req = '$id_h'");
        $title = "Stok Habis!";
        $icon = "error";
        $header_pesan = "<p class='text-danger fw-bold mb-2'>Tidak ada bahan yang bisa diserahkan karena stok Gudang Utama kosong!</p>";
    }

    mysqli_commit($koneksi); 
    
    echo json_encode([
        'status' => 'success',
        'title' => $title,
        'icon' => $icon,
        'html' => $header_pesan . ($ada_item_diproses ? $pesan_detail : '')
    ]);

} catch (Exception $e) {
    mysqli_rollback($koneksi); 
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>