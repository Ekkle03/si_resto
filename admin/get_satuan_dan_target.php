<?php
include("../config/koneksi_mysql.php");

// Pastikan ada ID BSJ yang dikirim via POST
if (isset($_POST['id_bsj'])) {
    $id_bsj = (int)$_POST['id_bsj'];

    // Query untuk mengambil Nama Satuan dari master_satuan
    // Dan mengambil Target Hasil dari master_bom
    $sql = "SELECT s.nama_satuan, b.target_hasil 
            FROM master_bahan_setengah_jadi bsj
            JOIN master_satuan s ON bsj.id_satuan = s.id_satuan
            LEFT JOIN master_bom b ON bsj.id_bsj = b.id_induk AND b.tipe_bom = 'BSJ'
            WHERE bsj.id_bsj = '$id_bsj'
            LIMIT 1";
            
    $query = mysqli_query($koneksi, $sql);
    $data = mysqli_fetch_assoc($query);

    // Perbaikan: Gunakan floatval() agar angka desimal seperti 1.5 tetap terbaca
    $response = [
        'satuan' => $data['nama_satuan'] ?? 'Unit',
        'target' => floatval($data['target_hasil'] ?? 1) 
    ];

    echo json_encode($response);
}
?>