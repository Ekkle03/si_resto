<?php
session_start();
include("../../config/auth.php");
include("../../config/koneksi_mysql.php");

// 1. Ambil Parameter dari URL
$tipe      = $_GET['tipe'] ?? 'BB';
$id_item   = $_GET['id_item'] ?? '';
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$id_gudang = $_GET['id_gudang'] ?? '1';

if (empty($id_item)) {
    die("<script>alert('Pilih bahan terlebih dahulu!'); window.close();</script>");
}

// 2. Ambil Informasi Bahan & Gudang
$prefix = ($tipe == 'BB' ? 'bb' : 'bsj');
$tabel  = ($tipe == 'BB' ? 'master_bahan_baku' : 'master_bahan_setengah_jadi');

$q_info = mysqli_query($koneksi, "SELECT b.nama_$prefix as nama, s.nama_satuan 
                                  FROM $tabel b 
                                  JOIN master_satuan s ON b.id_satuan = s.id_satuan 
                                  WHERE b.id_$prefix = '$id_item'");
$d_info = mysqli_fetch_assoc($q_info);

$q_gudang = mysqli_query($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang = '$id_gudang'");
$d_gudang = mysqli_fetch_assoc($q_gudang);

$q_stok = mysqli_query($koneksi, "SELECT jumlah FROM stok_bahan WHERE id_$prefix = '$id_item' AND id_gudang = '$id_gudang'");
$d_stok = mysqli_fetch_assoc($q_stok);

$nama_bahan = $d_info['nama'] ?? '-';
$satuan     = $d_info['nama_satuan'] ?? '-';
$nama_gudang = $d_gudang['nama_gudang'] ?? 'Gudang Utama';
$stok_akhir = (float)($d_stok['jumlah'] ?? 0);

// =========================================================================
// FUNGSI UNTUK MERUBAH KODE SISTEM JADI BAHASA MANUSIA
// =========================================================================
function ubahBahasaManusia($teks) {
    // 1. Bersihkan kode PRD (Produksi)
    $teks = preg_replace('/Hasil PRD\d+ \((.*?)\)/', 'Hasil Produksi ($1)', $teks);
    $teks = preg_replace('/Waste PRD\d+ \((.*?)\)/', 'Waste / Sisa Produksi ($1)', $teks);
    
    // 2. Bersihkan kode REQ (Permintaan/Distribusi Gudang)
    $teks = preg_replace('/Kirim ke REQ-\d+ \((.*?)\)/', 'Distribusi ke $1', $teks);
    $teks = preg_replace('/Terima dari Gudang Utama \(REQ-\d+\)/', 'Terima dari Gudang Utama', $teks);
    
    // 3. Bersihkan kode JUL (Penjualan)
    $teks = preg_replace('/Jual (.*?) - Ref: JUL-.*/', 'Penjualan ($1)', $teks);
    
    return htmlspecialchars($teks);
}
// =========================================================================
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok - <?= $nama_bahan ?></title>
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

        .info-table { width: 100%; margin-bottom: 20px; border: 1px solid #000; border-collapse: collapse; }
        .info-table td { padding: 10px; border: 1px solid #000; }
        .bg-gray { background-color: #f2f2f2; font-weight: bold; width: 20%; }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { border: 1px solid #000; padding: 10px; background-color: #f2f2f2; text-transform: uppercase; font-size: 9pt; }
        .data-table td { border: 1px solid #000; padding: 8px 5px; font-size: 9pt; }
        .text-center { text-align: center; }
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
        <h2>Kartu Stok Material</h2>
        <p>Periode: <strong><?= date('d/m/Y', strtotime($tgl_awal)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($tgl_akhir)) ?></strong></p>
    </div>

    <table class="info-table">
        <tr>
            <td class="bg-gray">Nama Material</td>
            <td><?= $nama_bahan ?></td>
            <td class="bg-gray">Gudang</td>
            <td><?= $nama_gudang ?></td>
        </tr>
        <tr>
            <td class="bg-gray">Satuan</td>
            <td><?= $satuan ?></td>
            <td class="bg-gray">Stok Akhir</td>
            <td class="fw-bold"><?= (float)$stok_akhir ?> <?= $satuan ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Tanggal</th>
                <th>Uraian / Keterangan</th>
                <th width="12%">Masuk (+)</th>
                <th width="12%">Keluar (-)</th>
                <th width="15%">Saldo Sisa</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_log = "SELECT * FROM log_stok 
                        WHERE id_$prefix = '$id_item' AND id_gudang = '$id_gudang' 
                        AND DATE(tgl_log) BETWEEN '$tgl_awal' AND '$tgl_akhir' 
                        ORDER BY tgl_log ASC";
            $q_log = mysqli_query($koneksi, $sql_log);
            
            if(mysqli_num_rows($q_log) > 0){
                $no = 1;
                while($l = mysqli_fetch_assoc($q_log)): 
                    // Panggil fungsi penerjemah di sini
                    $keterangan_bersih = ubahBahasaManusia($l['keterangan']);
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($l['tgl_log'])) ?></td>
                    <td><?= $keterangan_bersih ?></td>
                    <td class="text-center"><?= (float)$l['qty_masuk'] > 0 ? (float)$l['qty_masuk'] : '-' ?></td>
                    <td class="text-center"><?= (float)$l['qty_keluar'] > 0 ? (float)$l['qty_keluar'] : '-' ?></td>
                    <td class="text-center fw-bold"><?= (float)$l['sisa_stok'] ?></td>
                </tr>
            <?php endwhile; } else { ?>
                <tr><td colspan="6" class="text-center">Tidak ada riwayat transaksi pada periode ini.</td></tr>
            <?php } ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; font-size: 8pt; color: #666; font-style: italic;">
        Laporan dicetak otomatis oleh sistem pada: <?= date('d/m/Y H:i:s') ?>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };

        window.onafterprint = function() {
            window.close();
            window.location.href = '../stok_bahan.php?tipe=<?= $tipe ?>&id_item=<?= $id_item ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_gudang=<?= $id_gudang ?>';
        };
    </script>

</body>
</html>