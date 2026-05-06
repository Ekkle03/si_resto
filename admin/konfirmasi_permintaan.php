<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

$id_h = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_h > 0) {
    // Cek apakah nota valid dan status masih Pending
    $q_h = mysqli_query($koneksi, "SELECT kode_request, id_gudang_tujuan, status FROM header_request WHERE id_header_req = '$id_h'");
    $d_h = mysqli_fetch_assoc($q_h);

    if ($d_h && $d_h['status'] == 'Pending') {
        $id_g_tujuan = $d_h['id_gudang_tujuan'];
        $id_g_asal   = 1; // Default: Gudang Utama
        $kode_req    = $d_h['kode_request'];

        mysqli_begin_transaction($koneksi);

        try {
            // Tarik semua barang yang diminta di nota ini
            $q_items = mysqli_query($koneksi, "SELECT * FROM request_bahan WHERE id_header_req = '$id_h'");
            
            while ($item = mysqli_fetch_assoc($q_items)) {
                $qty_input = floatval($item['qty_request']);
                
                // Cek ini BB atau BSJ
                if (!empty($item['id_bb'])) {
                    $tipe = 'BB';
                    $id_i = $item['id_bb'];
                    $col  = 'id_bb';
                } else {
                    $tipe = 'BSJ';
                    $id_i = $item['id_bsj'];
                    $col  = 'id_bsj';
                }

                // ==========================================
                // AMBIL KONVERSI
                // ==========================================
                $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi FROM master_konversi WHERE id_komponen = '$id_i' AND tipe_bahan = '$tipe'");
                $d_konv = mysqli_fetch_assoc($q_konv);
                
                $nilai_konversi = ($d_konv) ? floatval($d_konv['nilai_konversi']) : 1;
                $qty_terkecil = $qty_input * $nilai_konversi; // Ubah ke satuan paling kecil

                // ==========================================
                // KURANGI GUDANG UTAMA
                // ==========================================
                mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah - $qty_terkecil WHERE $col = '$id_i' AND id_gudang = '$id_g_asal'");

                $q_sisa_asal = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_i' AND id_gudang = '$id_g_asal'");
                $d_sisa_asal = mysqli_fetch_assoc($q_sisa_asal);
                
                mysqli_query($koneksi, "INSERT INTO log_stok ($col, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                                        VALUES ('$id_i', 0, '$qty_terkecil', 'Operasional', '$id_g_asal', '".$d_sisa_asal['jumlah']."', 'Kirim ke $kode_req')");

                // ==========================================
                // TAMBAH GUDANG TUJUAN
                // ==========================================
                $cek_dest = mysqli_query($koneksi, "SELECT id_stok FROM stok_bahan WHERE $col = '$id_i' AND id_gudang = '$id_g_tujuan'");
                if (mysqli_num_rows($cek_dest) > 0) {
                    mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $qty_terkecil WHERE $col = '$id_i' AND id_gudang = '$id_g_tujuan'");
                } else {
                    mysqli_query($koneksi, "INSERT INTO stok_bahan (id_gudang, $col, jumlah) VALUES ('$id_g_tujuan', '$id_i', '$qty_terkecil')");
                }

                $q_sisa_tuju = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE $col = '$id_i' AND id_gudang = '$id_g_tujuan'");
                $d_sisa_tuju = mysqli_fetch_assoc($q_sisa_tuju);
                
                mysqli_query($koneksi, "INSERT INTO log_stok ($col, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                                        VALUES ('$id_i', '$qty_terkecil', 0, 'Operasional', '$id_g_tujuan', '".$d_sisa_tuju['jumlah']."', 'Terima dari $kode_req')");
            }

            // ==========================================
            // KUNCI NOTA JADI SELESAI
            // ==========================================
            mysqli_query($koneksi, "UPDATE header_request SET status = 'Selesai' WHERE id_header_req = '$id_h'");

            mysqli_commit($koneksi); 
            $_SESSION['flash_msg'] = "Nota $kode_req berhasil dikonfirmasi! Stok sudah berpindah.";
            
        } catch (Exception $e) {
            mysqli_rollback($koneksi); 
            $_SESSION['flash_msg'] = "Gagal memproses stok: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_msg'] = "Nota tidak ditemukan atau sudah selesai!";
    }
}

header("Location: permintaan_bahan.php");
exit;
?>