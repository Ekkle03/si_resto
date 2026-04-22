<?php
session_start();
include("../config/koneksi_mysql.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_header   = $_POST['id_header'];
    $id_gudang   = $_POST['id_gudang'];
    $kode_opname = $_POST['kode_opname'];

    // Mulai Transaksi agar data aman
    mysqli_begin_transaction($koneksi);

    try {
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                // Ambil data dari form
                $stok_fisik_besar  = (float)$item['stok_fisik'];
                $stok_sistem_besar = (float)$item['stok_sistem'];
                
                // Pastikan konversi minimal 1 jika tidak ada data konversi
                $konversi = (float)($item['konversi'] ?: 1);
                
                // Konversi balik ke Satuan Terkecil untuk simpan ke Database
                $stok_fisik_kecil  = $stok_fisik_besar * $konversi;
                $stok_sistem_kecil = $stok_sistem_besar * $konversi;
                $selisih_kecil     = $stok_fisik_kecil - $stok_sistem_kecil;

                // Pecah ID (Contoh: BB-5)
                $raw_id = explode('-', $item['id_raw']);
                $tipe   = $raw_id[0];
                $id_itm = $raw_id[1];
                $col    = ($tipe == 'BB') ? 'id_bb' : 'id_bsj';

                // 1. Simpan ke Tabel detail_opname
                $sql_detail = "INSERT INTO detail_opname (id_header_opname, $col, stok_sistem, stok_fisik, selisih) 
                               VALUES ('$id_header', '$id_itm', '$stok_sistem_kecil', '$stok_fisik_kecil', '$selisih_kecil')";
                if (!mysqli_query($koneksi, $sql_detail)) throw new Exception(mysqli_error($koneksi));

                // 2. Update stok_bahan (Menyesuaikan dengan jumlah fisik terbaru)
                $sql_update_stok = "UPDATE stok_bahan SET jumlah = '$stok_fisik_kecil' 
                                    WHERE $col = '$id_itm' AND id_gudang = '$id_gudang'";
                if (!mysqli_query($koneksi, $sql_update_stok)) throw new Exception(mysqli_error($koneksi));

                // 3. Catat ke Log Stok
                $qty_masuk  = ($selisih_kecil > 0) ? $selisih_kecil : 0;
                $qty_keluar = ($selisih_kecil < 0) ? abs($selisih_kecil) : 0;
                $keterangan_log = "Stok Opname: $kode_opname";

                $sql_log = "INSERT INTO log_stok ($col, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                            VALUES ('$id_itm', '$qty_masuk', '$qty_keluar', 'Stok Opname', '$id_gudang', '$stok_fisik_kecil', '$keterangan_log')";
                if (!mysqli_query($koneksi, $sql_log)) throw new Exception(mysqli_error($koneksi));
            }
        }

        // Jika semua OK, simpan permanen
        mysqli_commit($koneksi);
        
        header("Location: stok_opname.php?status=success&msg=" . urlencode("Stok Opname $kode_opname Berhasil Disimpan & Stok Diperbarui!"));
        exit();

    } catch (Exception $e) {
        // Jika ada yang gagal, batalkan semua perubahan
        mysqli_rollback($koneksi);
        header("Location: stok_opname.php?status=error&msg=" . urlencode("Gagal simpan data: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: stok_opname.php");
    exit();
}
?>s