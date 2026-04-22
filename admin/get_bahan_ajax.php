<?php
include("../config/koneksi_mysql.php");

$q = mysqli_real_escape_string($koneksi, $_GET['q'] ?? '');
$id_gudang = mysqli_real_escape_string($koneksi, $_GET['id_gudang'] ?? '');

$results = [];

if (!empty($id_gudang)) {
    // 1. Cari Bahan Baku (BB) + Satuan Besar, Kecil, dan Nilai Konversi
    $sql_bb = "SELECT bb.id_bb, bb.nama_bb, s.jumlah, sat_b.nama_satuan as sat_besar, 
               mk.nilai_konversi, sat_k.nama_satuan as sat_kecil
               FROM master_bahan_baku bb
               JOIN stok_bahan s ON bb.id_bb = s.id_bb
               JOIN master_satuan sat_b ON bb.id_satuan = sat_b.id_satuan
               LEFT JOIN master_konversi mk ON bb.id_bb = mk.id_komponen AND mk.tipe_bahan = 'BB'
               LEFT JOIN master_satuan sat_k ON mk.satuan_kecil = sat_k.id_satuan
               WHERE s.id_gudang = '$id_gudang' AND bb.nama_bb LIKE '%$q%'";
    
    $res_bb = mysqli_query($koneksi, $sql_bb);
    while($row = mysqli_fetch_assoc($res_bb)){
        $results[] = [
            'id'    => 'BB-' . $row['id_bb'],
            'text'  => $row['nama_bb'],
            'stok'  => (float)$row['jumlah'],
            'konv'  => (float)($row['nilai_konversi'] ?? 1), // Default 1 jika tidak ada konversi
            'sat_b' => $row['sat_besar'],
            'sat_k' => $row['sat_kecil'] ?? $row['sat_besar']
        ];
    }

    // 2. Cari BSJ + Satuan (BSJ biasanya dianggap 1:1 kecuali ada tabel konversinya juga)
    $sql_bsj = "SELECT bsj.id_bsj, bsj.nama_bsj, s.jumlah, sat.nama_satuan as sat_besar
                FROM master_bahan_setengah_jadi bsj
                JOIN stok_bahan s ON bsj.id_bsj = s.id_bsj
                JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
                WHERE s.id_gudang = '$id_gudang' AND bsj.nama_bsj LIKE '%$q%'";
    
    $res_bsj = mysqli_query($koneksi, $sql_bsj);
    while($row = mysqli_fetch_assoc($res_bsj)){
        $results[] = [
            'id'    => 'BSJ-' . $row['id_bsj'],
            'text'  => $row['nama_bsj'],
            'stok'  => (float)$row['jumlah'],
            'konv'  => 1,
            'sat_b' => $row['sat_besar'],
            'sat_k' => $row['sat_besar']
        ];
    }
}
echo json_encode($results);
?>