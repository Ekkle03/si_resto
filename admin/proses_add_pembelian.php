<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (isset($_POST['btn_simpan'])) {
    $kode_pembelian = mysqli_real_escape_string($koneksi, $_POST['kode_pembelian']);
    $tgl_pembelian  = mysqli_real_escape_string($koneksi, $_POST['tgl_pembelian']);
    $keterangan     = mysqli_real_escape_string($koneksi, $_POST['keterangan']);

    // 1. Simpan ke Tabel Header (pembelian)
    $sql_header = "INSERT INTO pembelian (kode_pembelian, tgl_pembelian, keterangan) 
                   VALUES ('$kode_pembelian', '$tgl_pembelian', '$keterangan')";
    
    if (mysqli_query($koneksi, $sql_header)) {
        // Ambil ID baru saja dimasukkan
        $id_pembelian = mysqli_insert_id($koneksi);

        // 2. Ambil data array dari form detail
        $id_bb_arr    = $_POST['id_bb'];
        $qty_beli_arr = $_POST['qty_beli'];

        $berhasil = 0;
        foreach ($id_bb_arr as $key => $id_bb) {
            $id_bb = mysqli_real_escape_string($koneksi, $id_bb);
            $qty_input = mysqli_real_escape_string($koneksi, $qty_beli_arr[$key]);

            // Validasi: hanya simpan jika ID Bahan dan QTY ada isinya
            if (!empty($id_bb) && !empty($qty_input)) {
                
                // --- AWAL LOGIKA KONVERSI PINTAR ---
                // Cek apakah bahan ini punya konversi (misal: Beras punya Karung)
                $sql_konv = "SELECT nilai_konversi FROM master_konversi 
                             WHERE id_komponen = '$id_bb' AND tipe_bahan = 'BB' LIMIT 1";
                $query_konv = mysqli_query($koneksi, $sql_konv);
                $data_konv = mysqli_fetch_assoc($query_konv);

                if ($data_konv && $data_konv['nilai_konversi'] > 0) {
                    // JIKA ADA KONVERSI: Misal input 1 Karung * 25 = 25 Kg
                    $qty_final = $qty_input * $data_konv['nilai_konversi'];
                } else {
                    // JIKA TIDAK ADA: Pakai angka inputan asli
                    $qty_final = $qty_input;
                }
                // --- AKHIR LOGIKA KONVERSI PINTAR ---

                $sql_detail = "INSERT INTO detail_pembelian (id_pembelian, id_bb, qty_beli) 
                               VALUES ('$id_pembelian', '$id_bb', '$qty_final')";
                
                mysqli_query($koneksi, $sql_detail);
                $berhasil++;
            }
        }

        if ($berhasil > 0) {
            header("Location: pembelian.php?status=success&msg=Rencana belanja berhasil disimpan!");
        } else {
            // Jika header masuk tapi detail kosong, hapus headernya lagi biar gak sampah
            mysqli_query($koneksi, "DELETE FROM pembelian WHERE id_pembelian = '$id_pembelian'");
            header("Location: add_pembelian.php?msg=Gagal: Detail bahan tidak boleh kosong.");
        }
    } else {
        echo "Error Header: " . mysqli_error($koneksi);
    }
} else {
    header("Location: pembelian.php");
}
?>