<?php
session_start();
include("../../config/auth.php");
include("../../config/koneksi_mysql.php");

// 1. Parameter Filter
$tgl_awal  = $_GET['tgl_awal'] ?? date('Y-m-01');
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
$id_gudang = $_GET['id_gudang'] ?? '';

// 2. Info Gudang
$nama_gudang = "Semua Gudang";
if (!empty($id_gudang)) {
    $q_gudang = mysqli_query($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang = '$id_gudang'");
    $d_gudang = mysqli_fetch_assoc($q_gudang);
    $nama_gudang = $d_gudang['nama_gudang'] ?? 'Gudang';
}

// Fungsi Pembersih Kode agar laporan rapi (PRD/RCV dibuang)
function formatAlasan($teks) {
    $teks = preg_replace('/PRD\d+/', '', $teks);
    $teks = preg_replace('/RCV\d+/', '', $teks);
    return htmlspecialchars(trim(str_replace(':', '', $teks)));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Waste - SI Resto</title>
    <style>
        /* PENGATURAN KERTAS A4 PORTRAIT */
        @page { size: A4 portrait; margin: 10mm; }
        
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 10pt; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }
        
        /* Kop Surat */
        .header-container { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo-box { width: 70px; flex-shrink: 0; }
        .logo-box img { width: 100%; height: auto; }
        .restaurant-info { flex-grow: 1; text-align: center; padding-right: 70px; }
        .restaurant-info h1 { margin: 0; font-size: 16pt; text-transform: uppercase; font-weight: bold; }
        .restaurant-info p { margin: 2px 0; font-size: 8.5pt; }

        .report-title { text-align: center; margin-bottom: 15px; }
        .report-title h2 { margin: 0; font-size: 12pt; text-decoration: underline; text-transform: uppercase; }
        
        .info-table { width: 100%; margin-bottom: 10px; font-size: 9pt; }
        
        /* Tabel Data Rinci */
        .data-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .data-table th, .data-table td { 
            border: 1px solid #000; 
            padding: 6px 4px; 
            vertical-align: middle; 
            word-wrap: break-word; 
        }
        .data-table th { background-color: #f2f2f2; font-weight: bold; font-size: 8.5pt; }
        .data-table td { font-size: 8.5pt; }
        
        /* PERBAIKAN UKURAN FOTO: Dibuat lebih besar (85px) agar detail terlihat */
        .img-bukti { 
            width: 85px; 
            height: 85px; 
            object-fit: cover; 
            border: 1px solid #ccc; 
            border-radius: 3px; 
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #d33; font-weight: bold; }

        .footer-note { margin-top: 15px; font-size: 7.5pt; color: #555; text-align: right; font-style: italic; }

        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header-container">
        <div class="logo-box">
            <img src="../assets/img/logo/logo_resto.png" alt="Logo" onerror="this.style.display='none'">
        </div>
        <div class="restaurant-info">
            <h1>AYAM GORENG KABAYAN</h1>
            <p>Jl. Lumajang No.12, Gading Kasri, Kec. Klojen, Kota Malang, Jawa Timur 65115</p>
            <p>Telp: +6281957166000 | Email: info@ayamkabayan.com</p>
        </div>
    </div>

    <div class="report-title">
        <h2>LAPORAN REKAPITULASI WASTE BARANG</h2>
    </div>

    <table class="info-table">
        <tr>
            <td width="12%"><b>Gudang</b></td>
            <td width="2%">:</td>
            <td><?= $nama_gudang ?></td>
            <td width="15%" class="text-right"><b>Periode</b></td>
            <td width="2%" class="text-center">:</td>
            <td width="25%"><?= date('d/m/Y', strtotime($tgl_awal)) ?> s/d <?= date('d/m/Y', strtotime($tgl_akhir)) ?></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="30px">NO</th>
                <th width="75px">TANGGAL</th>
                <th>NAMA BARANG</th>
                <th width="45px">QTY</th>
                <th width="65px">SATUAN</th>
                <th width="75px">SUMBER</th>
                <th width="160px">ALASAN</th>
                <th width="95px">BUKTI FOTO</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $where = "h.tgl_waste BETWEEN '$tgl_awal' AND '$tgl_akhir'";
            if (!empty($id_gudang)) { $where .= " AND h.id_gudang = '$id_gudang'"; }

            $sql = "SELECT h.tgl_waste, h.id_gudang, d.qty_waste, d.alasan, d.sumber, d.foto_bukti,
                           bb.id_bb, bsj.id_bsj,
                           COALESCE(bb.nama_bb, bsj.nama_bsj) as nama_item,
                           COALESCE(s1.nama_satuan, s2.nama_satuan) as sat_master,
                           COALESCE(sk_bb.nama_satuan, sk_bsj.nama_satuan) as sat_kecil,
                           COALESCE(k_bb.nilai_konversi, k_bsj.nilai_konversi, 1) as nilai_konversi
                    FROM detail_waste d
                    JOIN header_waste h ON d.id_header_waste = h.id_header_waste
                    LEFT JOIN master_bahan_baku bb ON d.id_bb = bb.id_bb
                    LEFT JOIN master_satuan s1 ON bb.id_satuan = s1.id_satuan
                    LEFT JOIN master_konversi k_bb ON bb.id_bb = k_bb.id_komponen AND k_bb.tipe_bahan = 'BB'
                    LEFT JOIN master_satuan sk_bb ON k_bb.satuan_kecil = sk_bb.id_satuan
                    LEFT JOIN master_bahan_setengah_jadi bsj ON d.id_bsj = bsj.id_bsj
                    LEFT JOIN master_satuan s2 ON bsj.id_satuan = s2.id_satuan
                    LEFT JOIN master_konversi k_bsj ON bsj.id_bsj = k_bsj.id_komponen AND k_bsj.tipe_bahan = 'BSJ'
                    LEFT JOIN master_satuan sk_bsj ON k_bsj.satuan_kecil = sk_bsj.id_satuan
                    WHERE $where ORDER BY h.tgl_waste ASC";

            $q_run = mysqli_query($koneksi, $sql);
            $no = 1;

            if(mysqli_num_rows($q_run) > 0){
                while($row = mysqli_fetch_assoc($q_run)): 
                    // Logika Konversi Satuan Cerdas
                    $qty_db = (float)$row['qty_waste'];
                    $n_konv = (float)$row['nilai_konversi'];
                    if ($n_konv <= 0) $n_konv = 1;

                    if (!empty($row['id_bb'])) {
                        if ($row['sumber'] == 'Penerimaan' || $row['id_gudang'] == '1') {
                            $qty_final = $qty_db / $n_konv;
                            $sat_final = $row['sat_master'];
                        } else {
                            $qty_final = $qty_db;
                            $sat_final = !empty($row['sat_kecil']) ? $row['sat_kecil'] : $row['sat_master'];
                        }
                    } else {
                        $qty_final = $qty_db;
                        $sat_final = $row['sat_master'];
                    }

                    // Path Foto naik 2 tingkat ke root (si_resto/assets/img/waste/)
                    $img_path = '../../assets/img/waste/' . $row['foto_bukti'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_waste'])) ?></td>
                <td class="fw-bold"><?= $row['nama_item'] ?></td>
                <td class="text-center text-danger"><?= (float)round($qty_final, 3) ?></td>
                <td class="text-center"><?= $sat_final ?></td>
                <td class="text-center"><?= $row['sumber'] ?></td>
                <td><?= formatAlasan($row['alasan']) ?></td>
                <td class="text-center">
                    <?php if(!empty($row['foto_bukti'])): ?>
                        <img src="<?= $img_path ?>" class="img-bukti" onerror="this.outerHTML='<small style=\'font-size:7pt\'>[N/A]</small>'">
                    <?php else: ?>
                        <small style="color:#999; font-size:7pt">N/A</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; } else { ?>
                <tr><td colspan="8" class="text-center py-3">Tidak ada data transaksi waste.</td></tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="footer-note">
        Laporan dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap']) ?> | Waktu cetak: <?= date('d/m/Y H:i:s') ?>
    </div>

    <script>
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>