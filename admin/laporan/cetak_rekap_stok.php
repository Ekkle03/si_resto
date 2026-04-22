<?php
session_start();
// Naik 2 tingkat ke config
include("../../config/koneksi_mysql.php");

// 1. Ambil Parameter
$tipe      = $_GET['tipe'] ?? 'BB';
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$id_gudang = $_GET['id_gudang'] ?? '1';

// 2. Info Gudang & Tipe
$q_gudang = mysqli_query($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang = '$id_gudang'");
$d_gudang = mysqli_fetch_assoc($q_gudang);
$nama_gudang = $d_gudang['nama_gudang'] ?? 'Gudang Utama';
$is_gudang_utama = ($id_gudang == '1');

$nama_kategori = ($tipe == 'BB') ? 'Bahan Baku' : 'Bahan Setengah Jadi';
$prefix = ($tipe == 'BB') ? 'bb' : 'bsj';
$table_m = ($tipe == 'BB' ? 'master_bahan_baku' : 'master_bahan_setengah_jadi');
$col_id = "id_" . $prefix;
$tahap_query = ($tipe == 'BSJ') ? ", b.tahap" : "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Stok - <?= $nama_kategori ?></title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #000; margin: 0; padding: 0; }
        
        .header-container { display: flex; align-items: center; border-bottom: 3px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
        .logo-box { width: 100px; flex-shrink: 0; }
        .logo-box img { width: 100%; height: auto; }
        .restaurant-info { flex-grow: 1; text-align: center; padding-right: 100px; }
        .restaurant-info h1 { margin: 0; font-size: 20pt; text-transform: uppercase; font-weight: bold; }
        .restaurant-info p { margin: 3px 0; font-size: 10pt; }

        .report-title { text-align: center; margin-bottom: 25px; }
        .report-title h2 { margin: 0; font-size: 14pt; text-decoration: underline; text-transform: uppercase; }
        
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 5px; font-size: 11pt; }

        /* DESAIN TABEL MINIMALIS & PROFESIONAL */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #000; padding: 10px 5px; vertical-align: middle; }
        .data-table td { font-size: 10pt; }
        .data-table th { text-align: center; text-transform: uppercase; font-size: 10pt; background-color: #fafafa; }
        
        /* Pewarnaan Header Mirip Gambar Referensi */
        .th-muted { color: #888; font-weight: bold; }
        .th-dark { color: #000; font-weight: bold; }
        
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="header-container">
        <div class="logo-box">
            <img src="../assets/img/logo/logo_resto.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="restaurant-info">
            <h1>AYAM GORENG KABAYAN</h1>
            <p>Jl. Lumajang No.12, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115, Indonesia</p>
            <p>Telp: +6281957166000</p>
        </div>
    </div>

    <div class="report-title">
        <h2>LAPORAN REKAPITULASI STOK BARANG</h2>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%" class="fw-bold">Gudang</td>
            <td width="2%">:</td>
            <td><?= $nama_gudang ?></td>
        </tr>
        <tr>
            <td class="fw-bold">Kategori</td>
            <td>:</td>
            <td><?= $nama_kategori ?></td>
        </tr>
        <tr>
            <td class="fw-bold">Periode</td>
            <td>:</td>
            <td><?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" width="5%" class="th-muted">No</th>
                <th rowspan="2" width="15%" class="th-muted">Kode Barang</th>
                <th rowspan="2" class="th-muted">Nama Barang</th>
                <th colspan="2" class="th-muted">Pergerakan Barang</th>
                <th rowspan="2" width="12%" class="th-muted">Sisa Stok Akhir</th>
                <th rowspan="2" width="10%" class="th-muted">Satuan</th>
            </tr>
            <tr>
                <th class="th-dark" width="12%">Total Masuk</th>
                <th class="th-dark" width="12%">Total Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Subquery untuk mengambil total masuk dan keluar pada periode terpilih
            $sql = "SELECT b.$col_id as id, b.kode_$prefix as kode, b.nama_$prefix as nama $tahap_query,
                           COALESCE(s.jumlah, 0) as stok_db,
                           sat_m.nama_satuan as sat_master,
                           sat_k.nama_satuan as sat_kecil,
                           COALESCE(k.nilai_konversi, 1) as nilai_konversi,
                           (SELECT SUM(qty_masuk) FROM log_stok WHERE id_$prefix = b.$col_id AND id_gudang = '$id_gudang' AND DATE(tgl_log) BETWEEN '$tgl_awal' AND '$tgl_akhir') as total_in,
                           (SELECT SUM(qty_keluar) FROM log_stok WHERE id_$prefix = b.$col_id AND id_gudang = '$id_gudang' AND DATE(tgl_log) BETWEEN '$tgl_awal' AND '$tgl_akhir') as total_out
                    FROM $table_m b
                    LEFT JOIN stok_bahan s ON b.$col_id = s.$col_id AND s.id_gudang = '$id_gudang'
                    LEFT JOIN master_satuan sat_m ON b.id_satuan = sat_m.id_satuan
                    LEFT JOIN master_konversi k ON b.$col_id = k.id_komponen AND k.tipe_bahan = '$tipe'
                    LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan
                    ORDER BY b.nama_$prefix ASC";
            
            $q_run = mysqli_query($koneksi, $sql);
            $no = 1;
            
            if(mysqli_num_rows($q_run) > 0){
                while($row = mysqli_fetch_assoc($q_run)): 
                    $in = (float)$row['total_in'];
                    $out = (float)$row['total_out'];
                    $stok_akhir = (float)$row['stok_db'];
                    
                    $nilai_konv = (float)$row['nilai_konversi'];
                    if ($nilai_konv <= 0) $nilai_konv = 1;

                    $is_bsj1 = ($tipe == 'BSJ' && isset($row['tahap']) && $row['tahap'] == 'bsj1');

                    // Terapkan logika konversi satuan
                    if ($is_gudang_utama || $is_bsj1) {
                        $satuan_tampil = $row['sat_master'];
                        $in = $in / $nilai_konv;
                        $out = $out / $nilai_konv;
                        $stok_akhir = $stok_akhir / $nilai_konv;
                    } else {
                        $satuan_tampil = !empty($row['sat_kecil']) ? $row['sat_kecil'] : $row['sat_master'];
                    }
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center fw-bold"><?= $row['kode'] ?></td>
                <td class="text-left ps-2"><?= $row['nama'] ?></td>
                <td class="text-center"><?= $in > 0 ? (float)round($in, 3) : '-' ?></td>
                <td class="text-center"><?= $out > 0 ? (float)round($out, 3) : '-' ?></td>
                <td class="text-center fw-bold"><?= (float)round($stok_akhir, 3) ?></td>
                <td class="text-center"><?= $satuan_tampil ?></td>
            </tr>
            <?php endwhile; } else { ?>
                <tr><td colspan="7" class="text-center">Data kosong pada periode ini.</td></tr>
            <?php } ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 8pt; color: #666; font-style: italic; text-align: right;">
        Laporan dicetak otomatis pada: <?= date('d/m/Y H:i:s') ?>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 500);
        };
        window.onafterprint = function() {
            window.close();
            window.location.href = '../stok_bahan.php?tipe=<?= $tipe ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_gudang=<?= $id_gudang ?>';
        };
    </script>
</body>
</html>