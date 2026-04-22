<?php
// PASTIKAN: Tidak ada spasi atau baris kosong sebelum tag <?php ini!
session_start();
include("../config/koneksi_mysql.php");

// Set header agar browser tahu ini adalah data JSON murni
header('Content-Type: application/json');

if (isset($_POST['id_produksi'])) {
    $id_p = (int)$_POST['id_produksi'];
    
    // Siapkan wadah respons yang akan dikirim ke Javascript
    $response = [
        'status' => 'success',
        'hasil_jadi' => '-',
        'bahan' => []
    ];

    /**
     * LOGIKA QUERY:
     * 1. Ambil p.qty_realisasi dan s_hasil.nama_satuan untuk "Hasil Jadi".
     * 2. Ambil p.qty_rencana sebagai acuan agar rasio (Rencana / Target BOM) = 1.
     */
    $sql = "SELECT 
                b.qty, 
                b.target_hasil, 
                COALESCE(bb.nama_bb, bsj_b.nama_bsj) as nama_item,
                s.nama_satuan as satuan_resep, 
                s_hasil.nama_satuan as satuan_hasil,
                p.qty_rencana,
                p.qty_realisasi
            FROM produksi p
            INNER JOIN master_bahan_setengah_jadi bsj_induk ON p.id_bsj = bsj_induk.id_bsj
            INNER JOIN master_satuan s_hasil ON bsj_induk.id_satuan = s_hasil.id_satuan
            INNER JOIN master_bom b ON p.id_bsj = b.id_induk AND b.tipe_bom = 'BSJ'
            LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
            LEFT JOIN master_bahan_setengah_jadi bsj_b ON b.id_bsj = bsj_b.id_bsj
            LEFT JOIN master_satuan s ON b.id_satuan = s.id_satuan
            WHERE p.id_produksi = $id_p";

    $query = mysqli_query($koneksi, $sql);
    
    if ($query) {
        $is_first = true;
        
        while ($row = mysqli_fetch_assoc($query)) {
            // Ambil "Hasil Jadi" di perulangan pertama saja
            if ($is_first) {
                // Hapus desimal .00 dari angka (misal 14.00 jadi 14)
                $hasil_angka = floatval($row['qty_realisasi']);
                $satuan_hasil = $row['satuan_hasil'] ?? '';
                
                $response['hasil_jadi'] = "{$hasil_angka} {$satuan_hasil}"; 
                $is_first = false;
            }

            // Hitung Kebutuhan Bahan
            $qty_rencana = floatval($row['qty_rencana']); 
            $target_bom  = floatval($row['target_hasil']);
            $qty_resep   = floatval($row['qty']);

            // Rasio = Rencana / Target
            $rasio = ($target_bom > 0) ? ($qty_rencana / $target_bom) : 0;
            $pemakaian = $rasio * $qty_resep;

            // Masukkan data bahan ke dalam array 'bahan'
            array_push($response['bahan'], [
                'nama'   => $row['nama_item'] ?? 'Tanpa Nama',
                'qty'    => (float)round($pemakaian, 3),
                'satuan' => $row['satuan_resep'] ?? '-'
            ]);
        }
    } else {
        $response['status'] = 'error';
        $response['msg'] = mysqli_error($koneksi);
    }

    // Bersihkan *buffer* (jaga-jaga kalau ada spasi kosong di atas/bawah file PHP)
    ob_clean();
    echo json_encode($response);
    exit;
} else {
    echo json_encode(['status' => 'error', 'msg' => 'ID Produksi tidak dikirim.']);
    exit;
}
?>