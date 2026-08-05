<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_header = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 1. Ambil data kode_waste dan id_gudang
    $q_header = mysqli_query($koneksi, "SELECT kode_waste, id_gudang FROM header_waste WHERE id_header_waste = '$id_header'");
    $d_header = mysqli_fetch_assoc($q_header);

    if ($d_header) {
        $kode_waste = $d_header['kode_waste'];
        $id_gudang  = $d_header['id_gudang'];

        // 2. Ambil detail item yang akan dibatalkan
        $q_detail = mysqli_query($koneksi, "SELECT id_bb, id_bsj, qty_waste FROM detail_waste WHERE id_header_waste = '$id_header'");

        mysqli_begin_transaction($koneksi);

        try {
            while ($row = mysqli_fetch_assoc($q_detail)) {
                $qty = $row['qty_waste'];
                $id_bb = !empty($row['id_bb']) ? $row['id_bb'] : 'NULL';
                $id_bsj = !empty($row['id_bsj']) ? $row['id_bsj'] : 'NULL';

                // A. Update stok di tabel stok_bahan (Tambah Kembali)
                if ($id_bb !== 'NULL') {
                    $sql_stok = "UPDATE stok_bahan SET jumlah = jumlah + $qty WHERE id_bb = $id_bb AND id_gudang = '$id_gudang'";
                } else {
                    $sql_stok = "UPDATE stok_bahan SET jumlah = jumlah + $qty WHERE id_bsj = $id_bsj AND id_gudang = '$id_gudang'";
                }
                mysqli_query($koneksi, $sql_stok);

                // B. Ambil sisa stok terbaru untuk dicatat di log
                if ($id_bb !== 'NULL') {
                    $q_curr = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bb = $id_bb AND id_gudang = '$id_gudang'");
                } else {
                    $q_curr = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_bsj = $id_bsj AND id_gudang = '$id_gudang'");
                }
                $d_curr = mysqli_fetch_assoc($q_curr);
                $sisa_sekarang = $d_curr['jumlah'];

                // C. CATAT DI LOG STOK (Sebagai barang masuk karena pembatalan)
                // Menggunakan jenis_mutasi 'Waste' tapi masuk ke kolom qty_masuk
                $ket_log = "Pembatalan Waste: $kode_waste";
                $sql_log = "INSERT INTO log_stok (id_bb, id_bsj, qty_masuk, qty_keluar, jenis_mutasi, id_gudang, sisa_stok, keterangan) 
                            VALUES ($id_bb, $id_bsj, $qty, 0, 'Waste', '$id_gudang', '$sisa_sekarang', '$ket_log')";
                mysqli_query($koneksi, $sql_log);
            }

            // 3. Hapus header (detail_waste akan ikut terhapus karena CASCADE)
            $sql_delete = "DELETE FROM header_waste WHERE id_header_waste = '$id_header'";
            mysqli_query($koneksi, $sql_delete);

            mysqli_commit($koneksi);
            $_SESSION['flash_msg'] = "Transaksi $kode_waste dibatalkan. Jejak pengembalian stok tercatat di log.";

        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['flash_msg'] = "Gagal membatalkan transaksi: " . $e->getMessage();
        }
    }
}

header("Location: waste.php");
exit();
?>