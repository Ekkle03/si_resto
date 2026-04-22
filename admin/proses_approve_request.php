<?php
session_start();
include("../config/koneksi_mysql.php");

if (!isset($_GET['id'])) {
    header("Location: permintaan_bahan.php");
    exit;
}

$id_h = (int)$_GET['id'];

mysqli_begin_transaction($koneksi);

try {
    // 1. Cek Header
    $cek_q = mysqli_query($koneksi, "SELECT status, kode_request FROM header_request WHERE id_header_req = '$id_h' FOR UPDATE");
    $h = mysqli_fetch_assoc($cek_q);

    if (!$h) throw new Exception("Nota tidak ditemukan.");
    if ($h['status'] == 'Selesai') throw new Exception("Nota ini sudah selesai diproses.");

    $kode_req = $h['kode_request'];

    // 2. Ambil Item Request
    $sql_items = "SELECT * FROM request_bahan WHERE id_header_req = '$id_h'";
    $query_items = mysqli_query($koneksi, $sql_items);

    while ($item = mysqli_fetch_assoc($query_items)) {
        $qty = $item['qty_request'];
        $id_bb = $item['id_bb'];
        $id_bsj = $item['id_bsj'];

        if (!empty($id_bb)) {
            $id_target = $id_bb;
            $kolom = "id_bb";
        } else {
            $id_target = $id_bsj;
            $kolom = "id_bsj";
        }

        // 3. Ambil Stok Terkini di Gudang Utama (ID: 1) untuk hitung sisa_stok
        $q_stok = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $kolom = '$id_target' AND id_gudang = 1 FOR UPDATE");
        
        if (mysqli_num_rows($q_stok) == 0) {
            // Jika belum ada barisnya, buatkan dulu
            mysqli_query($koneksi, "INSERT INTO stok_bahan (id_gudang, $kolom, jumlah) VALUES (1, '$id_target', 0)");
            $stok_sekarang = 0;
        } else {
            $d_stok = mysqli_fetch_assoc($q_stok);
            $stok_sekarang = $d_stok['jumlah'];
        }

        $sisa_baru = $stok_sekarang - $qty;

        // 4. Update tabel stok_bahan (Nama kolom: jumlah)
        mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = $sisa_baru WHERE $kolom = '$id_target' AND id_gudang = 1");

        // 5. Insert ke log_stok (qty_keluar dan sisa_stok harus diisi)
        $tgl = date('Y-m-d H:i:s');
        $ket = "Keluar untuk nota: " . $kode_req;
        
        $sql_log = "INSERT INTO log_stok (id_gudang, $kolom, qty_masuk, qty_keluar, jenis_mutasi, sisa_stok, keterangan, tgl_log) 
                    VALUES (1, '$id_target', 0, '$qty', 'Operasional', '$sisa_baru', '$ket', '$tgl')";
        
        if (!mysqli_query($koneksi, $sql_log)) {
            throw new Exception("Gagal simpan log: " . mysqli_error($koneksi));
        }
    }

    // 6. Update Status Header
    mysqli_query($koneksi, "UPDATE header_request SET status = 'Selesai' WHERE id_header_req = '$id_h'");

    mysqli_commit($koneksi);
    $_SESSION['flash_msg'] = "Berhasil! Nota $kode_req disetujui dan stok Gudang Utama terpotong.";
    header("Location: permintaan_bahan.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $_SESSION['flash_msg'] = "Error: " . $e->getMessage();
    header("Location: permintaan_detail_view.php?id=$id_h");
    exit;
}