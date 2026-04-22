<?php
session_start();
include("../config/koneksi_mysql.php");

if (isset($_GET['id'])) {
    $id_jual = (int)$_GET['id'];

    // Ambil kode transaksi untuk log (opsional tapi bagus)
    $q_trx = mysqli_query($koneksi, "SELECT kode_transaksi FROM menu_terjual WHERE id_jual = '$id_jual'");
    $d_trx = mysqli_fetch_assoc($q_trx);
    $kode_trx = $d_trx ? $d_trx['kode_transaksi'] : "Unknown";

    // 1. AMBIL DULU DATA MENU APA SAJA YANG PERNAH TERJUAL DI TRX INI
    $q_detail = mysqli_query($koneksi, "SELECT id_menu, qty_terjual FROM detail_menu_terjual WHERE id_jual = '$id_jual'");

    while ($row = mysqli_fetch_assoc($q_detail)) {
        $id_menu = $row['id_menu'];
        $qty_batal = $row['qty_terjual'];

        // 2. KEMBALIKAN STOK HANYA KE GUDANG OPERASIONAL
        restoreStokBOM($id_menu, $qty_batal, $kode_trx, $koneksi);
    }

    // 3. BARU HAPUS HEADER (Detail akan terhapus otomatis karena CASCADE)
    $sql_delete = "DELETE FROM menu_terjual WHERE id_jual = '$id_jual'";
    
    if (mysqli_query($koneksi, $sql_delete)) {
        header("Location: menu_terjual.php?msg=Riwayat dihapus & stok berhasil dikembalikan ke Gudang Operasional.");
    } else {
        header("Location: menu_terjual.php?msg=Gagal menghapus data.");
    }
}

/**
 * FUNGSI RESTORE STOK (Tambah Kembali Stok yang Pernah Dipotong)
 */
function restoreStokBOM($id_menu, $qty_batal, $kode_trx, $koneksi) {
    $id_gudang_ops = 2; // HARUS DIKUNCI KE GUDANG 2
    $keterangan = "Batal Jual - Ref: $kode_trx";

    $sql_bom = "SELECT * FROM master_bom WHERE id_induk = '$id_menu' AND tipe_bom = 'MENU'";
    $query_bom = mysqli_query($koneksi, $sql_bom);

    while ($bom = mysqli_fetch_assoc($query_bom)) {
        $jumlah_awal = $bom['qty'] * $qty_batal;
        $unit_di_bom = (int)$bom['id_satuan'];

        // KEMBALIKAN BB
        if (!empty($bom['id_bb'])) {
            $id_bb = $bom['id_bb'];
            
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_besar FROM master_konversi 
                                              WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $jumlah_kembali = $jumlah_awal;
            if ($d_konv && (int)$d_konv['satuan_besar'] == $unit_di_bom) {
                $jumlah_kembali = $jumlah_awal * floatval($d_konv['nilai_konversi']);
            }

            // Tambah kembali stok di Gudang Operasional
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $jumlah_kembali 
                                    WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");

            // Catat ke Log agar historinya jelas
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = '$id_bb' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bb, qty_masuk, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bb', '$jumlah_kembali', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
        } 
        // KEMBALIKAN BSJ
        elseif (!empty($bom['id_bsj'])) {
            $id_bsj = $bom['id_bsj'];
            
            $q_konv = mysqli_query($koneksi, "SELECT nilai_konversi, satuan_besar FROM master_konversi 
                                              WHERE id_komponen = '$id_bsj' AND tipe_bahan = 'BSJ' LIMIT 1");
            $d_konv = mysqli_fetch_assoc($q_konv);
            
            $jumlah_kembali = $jumlah_awal;
            if ($d_konv && (int)$d_konv['satuan_besar'] == $unit_di_bom) {
                $jumlah_kembali = $jumlah_awal * floatval($d_konv['nilai_konversi']);
            }

            // Tambah kembali stok di Gudang Operasional
            mysqli_query($koneksi, "UPDATE stok_bahan SET jumlah = jumlah + $jumlah_kembali 
                                    WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            
            // Catat ke Log agar historinya jelas
            $q_sisa = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = '$id_bsj' AND id_gudang = '$id_gudang_ops'");
            $d_sisa = mysqli_fetch_assoc($q_sisa);
            $sisa_akhir = $d_sisa['jumlah'] ?? 0;

            mysqli_query($koneksi, "INSERT INTO log_stok (id_bsj, qty_masuk, sisa_stok, jenis_mutasi, id_gudang, keterangan) 
                                    VALUES ('$id_bsj', '$jumlah_kembali', '$sisa_akhir', 'Penjualan', '$id_gudang_ops', '$keterangan')");
                                    
            // LOGIKA REKURSIF DIHAPUS. Ayam matang biarlah matang.
        }
    }
}
?>