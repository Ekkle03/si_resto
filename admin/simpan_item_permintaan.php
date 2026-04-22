<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. TAMBAH KE KERANJANG (SESSION)
if (isset($_POST['qty'])) {
    $id_header = (int)$_POST['id_header'];
    $qty = floatval($_POST['qty']);
    $gabungan = explode('|', $_POST['id_gabungan']); 
    $tipe = $gabungan[0];
    $id_item = (int)$gabungan[1];

    if (!isset($_SESSION['keranjang'])) { $_SESSION['keranjang'] = []; }

    $found = false;
    foreach ($_SESSION['keranjang'] as $key => $item) {
        if ($item['id_item'] == $id_item && $item['tipe'] == $tipe) {
            $_SESSION['keranjang'][$key]['qty'] += $qty;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['keranjang'][] = ['id_item' => $id_item, 'tipe' => $tipe, 'qty' => $qty];
    }

    header("Location: input_detail_permintaan.php?id=$id_header");
    exit;
}

// 2. HAPUS SATU ITEM DARI KERANJANG
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus_item') {
    $key = $_GET['key'];
    $id_h = (int)$_GET['id'];
    
    if (isset($_SESSION['keranjang'][$key])) {
        unset($_SESSION['keranjang'][$key]);
        $_SESSION['keranjang'] = array_values($_SESSION['keranjang']);
    }
    
    header("Location: input_detail_permintaan.php?id=$id_h");
    exit;
}

// 3. SIMPAN DRAFT DARI SESSION KE DATABASE
if (isset($_GET['aksi']) && $_GET['aksi'] == 'final') {
    $id_h = (int)$_GET['id'];

    if (isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0) {
        
        mysqli_begin_transaction($koneksi);
        
        try {
            foreach ($_SESSION['keranjang'] as $item) {
                $id_i  = $item['id_item'];
                $q_req = floatval($item['qty']); // Qty dalam satuan besar sesuai input
                $tipe  = $item['tipe'];
                
                // PERBAIKAN: Menambahkan qty_dikirim (0) dan qty_sisa (sama dengan q_req)
                if ($tipe == 'BB') {
                    $sql_req = "INSERT INTO request_bahan (id_header_req, id_bb, id_bsj, qty_request, qty_dikirim, qty_sisa) 
                                VALUES ('$id_h', '$id_i', NULL, '$q_req', 0, '$q_req')";
                } else {
                    $sql_req = "INSERT INTO request_bahan (id_header_req, id_bb, id_bsj, qty_request, qty_dikirim, qty_sisa) 
                                VALUES ('$id_h', NULL, '$id_i', '$q_req', 0, '$q_req')";
                }
                mysqli_query($koneksi, $sql_req);
            }
            
            // Set status header menjadi Pending agar bisa diproses oleh Gudang Utama
            mysqli_query($koneksi, "UPDATE header_request SET status = 'Pending' WHERE id_header_req = '$id_h'");
            
            mysqli_commit($koneksi);
            unset($_SESSION['keranjang']);
            
            $_SESSION['flash_msg'] = "Permintaan berhasil dibuat! Menunggu konfirmasi penyerahan dari Gudang Utama.";
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['flash_msg'] = "Gagal menyimpan permintaan: " . $e->getMessage();
        }
    }
    
    header("Location: permintaan_bahan.php");
    exit;
}
?>