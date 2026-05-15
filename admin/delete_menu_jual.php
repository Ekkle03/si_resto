<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_GET['id'])) {
    $id_jual = (int)$_GET['id'];

    // Ambil kode transaksi
    $q_trx = mysqli_query($koneksi, "SELECT kode_transaksi FROM menu_terjual WHERE id_jual = '$id_jual'");
    $d_trx = mysqli_fetch_assoc($q_trx);
    $kode_trx = $d_trx ? $d_trx['kode_transaksi'] : "Unknown";

    // 1. KEMBALIKAN STOK MENU (BOM RESEP)
    $q_detail = mysqli_query($koneksi, "SELECT id_menu, qty_terjual FROM detail_menu_terjual WHERE id_jual = '$id_jual'");
    while ($row = mysqli_fetch_assoc($q_detail)) {
        $id_menu = $row['id_menu'];
        $qty_batal = $row['qty_terjual'];
        restoreStokBOM($id_menu, $qty_batal, $kode_trx, $koneksi);
    }

    // 2. KEMBALIKAN STOK BAHAN EKSTRA (KRESEK / KOTAK MAKAN)
    $sql_bb_log = "SELECT id_bb, qty_keluar FROM log_stok 
                   WHERE jenis_mutasi = 'Penjualan' 
                   AND keterangan LIKE 'Pemakaian Ekstra%' 
                   AND keterangan LIKE '%$kode_trx%'";
    $query_bb_log = mysqli_query($koneksi, $sql_bb_log);

    while ($bb = mysqli_fetch_assoc($query_bb_log)) {
        $id_bb = $bb['id_bb'];
        $qty_batal_bb = $bb['qty_keluar'];
        $id_gudang_ops = 2;
        $ket_batal = "Batal Jual (Ekstra) - Ref: $kode_trx";

        // Tambah lagi stok ke Gudang Oprasional
        mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $qty_batal_bb WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");

        $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
        $sisa_akhir = mysqli_fetch_assoc($q_sisa)['jumlah'] ?? 0;

        // Catat kembalinya stok kresek
        mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_masuk, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                VALUES ('$id_bb', '$qty_batal_bb', '$sisa_akhir', 'Batal Jual', '$id_gudang_ops', '$ket_batal')");
    }

    // 3. BARU HAPUS HEADER (Detail akan terhapus otomatis karena CASCADE)
    $delete = mysqli_query($koneksi, "DELETE FROM menu_terjual WHERE id_jual = '$id_jual'");

    if ($delete) {
        header("Location: menu_terjual.php?msg=Sukses menghapus riwayat penjualan dan mengembalikan seluruh stok (Termasuk Bahan Ekstra).");
    } else {
        header("Location: menu_terjual.php?msg=Gagal menghapus riwayat penjualan.");
    }
} else {
    header("Location: menu_terjual.php");
}

/**
 * Fungsi Mengembalikan Stok BOM
 */
function restoreStokBOM($id_menu, $qty_laku, $kode_trx, $koneksi) {
    $id_gudang_ops = 2; // Target Gudang Operasional
    
    $sql_bom = "SELECT * FROM master_bom WHERE id_induk = '$id_menu' AND tipe_bom = 'MENU'";
    $query_bom = mysqli_query($koneksi, $sql_bom);

    while ($bom = mysqli_fetch_assoc($query_bom)) {
        $jumlah_awal = (float)$bom['qty'] * $qty_laku;
        $unit_di_bom = (int)$bom['id_satuan'];
        $ket_batal = "Batal Jual (Refund Stok) - Ref: $kode_trx";

        if (!empty($bom['id_bb'])) {
            $id_bb = $bom['id_bb'];
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi 
                                              WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $jumlah_kembali = $jumlah_awal;
            if ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) {
                $jumlah_kembali = $jumlah_awal * floatval($d_konv['nilai_konversi']);
            }

            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $jumlah_kembali 
                                    WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
            
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
            $sisa_akhir = mysqli_fetch_assoc($q_sisa)['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_masuk, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bb', '$jumlah_kembali', '$sisa_akhir', 'Batal Jual', '$id_gudang_ops', '$ket_batal')");

        } elseif (!empty($bom['id_bsj'])) {
            $id_bsj = $bom['id_bsj'];
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_kecil FROM master_konversi 
                                              WHERE id_komponen = '$id_bsj' AND tipe_bahan = 'BSJ' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $jumlah_kembali = $jumlah_awal;
            if ($d_konv && $unit_di_bom != (int)$d_konv['satuan_kecil']) {
                $jumlah_kembali = $jumlah_awal * floatval($d_konv['nilai_konversi']);
            }

            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $jumlah_kembali 
                                    WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            $sisa_akhir = mysqli_fetch_assoc($q_sisa)['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, qty_masuk, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bsj', '$jumlah_kembali', '$sisa_akhir', 'Batal Jual', '$id_gudang_ops', '$ket_batal')");
        }
    }
}
?>