<?php
session_start();
include("../../config/auth.php");
include("../../config/koneksi_mysql.php");

// Tangkap Parameter Tanggal
$tgl_mulai  = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Pemakaian Bahan - SI Resto</title>
    <style>
        /* PENGATURAN KERTAS A4 PORTRAIT DENGAN MARGIN LEGA */
        @page { 
            size: A4 portrait; 
            margin: 15mm; 
        }
        
        body { 
            font-family: 'Arial', sans-serif; 
            font-size: 10pt; 
            color: #000; 
            margin: 0; 
            padding: 0; 
        }
        
        /* KOP SURAT (HEADER RESTO) */
        .header-container { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .logo-box { width: 70px; flex-shrink: 0; }
        .logo-box img { width: 100%; height: auto; }
        .restaurant-info { flex-grow: 1; text-align: center; padding-right: 70px; }
        .restaurant-info h1 { margin: 0; font-size: 16pt; text-transform: uppercase; font-weight: bold; color: #000; }
        .restaurant-info p { margin: 2px 0; font-size: 8.5pt; color: #000; }

        .report-title { text-align: center; margin-bottom: 15px; }
        .report-title h2 { margin: 0; font-size: 12pt; text-decoration: underline; text-transform: uppercase; color: #000; }
        
        .info-table { width: 100%; margin-bottom: 15px; font-size: 9pt; }
        .info-table td { border: none !important; padding: 2px !important; }
        
        /* TABEL DATA RINCI - PERBAIKAN SEJAJAR (ROWSPAN) */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px;}
        
        /* TRIK ANTI-POTONG: Setiap 1 Produk dibungkus tbody */
        .item-group { page-break-inside: avoid; border-bottom: 2px solid #000; }
        
        .data-table th, .data-table td { 
            border: 1px solid #000; 
            padding: 8px 6px; 
            vertical-align: middle; 
            word-wrap: break-word; 
        }
        .data-table th { background-color: #f2f2f2; font-weight: bold; font-size: 8.5pt; text-align: center; }
        .data-table td { font-size: 8.5pt; }
        
        /* CLASS KHUSUS AGAR KOP SURAT BISA MASUK THEAD */
        th.kop-surat-area {
            border: none !important;
            background-color: transparent !important;
            padding: 0 !important;
            text-align: left;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #555; }
        
        /* CATATAN WASTE KHUSUS */
        .waste-row td {
            background-color: #fff8f8;
            color: #d33;
            font-style: italic;
        }

        /* KOLOM TANDA TANGAN */
        .ttd-area {
            width: 100%;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .ttd-area td { border: none !important; text-align: center; font-size: 10pt; }
        
        .footer-note { margin-top: 15px; font-size: 7.5pt; color: #555; text-align: right; font-style: italic; }

        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <table class="data-table">
        <thead>
            <tr>
                <th colspan="5" class="kop-surat-area">
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
                        <h2>LAPORAN PEMAKAIAN BAHAN BAKU & BSJ</h2>
                    </div>

                    <table class="info-table">
                        <tr>
                            <td width="15%"><b>Periode Laporan</b></td>
                            <td width="2%">:</td>
                            <td><?= date('d/m/Y', strtotime($tgl_mulai)) ?> s/d <?= date('d/m/Y', strtotime($tgl_selesai)) ?></td>
                            <td width="15%" class="text-right"><b>Tgl Cetak</b></td>
                            <td width="2%" class="text-center">:</td>
                            <td width="20%"><?= date('d/m/Y H:i') ?></td>
                        </tr>
                    </table>
                </th>
            </tr>
            <tr>
                <th width="5%">NO</th>
                <th width="23%">PRODUK (HASIL PRODUKSI)</th>
                <th width="37%">KOMPONEN BAHAN BAKU / BSJ</th>
                <th width="15%">KEBUTUHAN RESEP</th>
                <th width="20%">TOTAL PEMAKAIAN</th>
            </tr>
        </thead>
        
        <?php
        $sql_prod = "
            SELECT 'BSJ' as tipe_bom, p.id_bsj as id_induk, bsj.nama_bsj as nama_produk, 
                   SUM(p.qty_rencana) as total_proses, 
                   SUM(p.qty_realisasi) as total_hasil, 
                   SUM(p.qty_rencana - p.qty_realisasi) as total_waste,
                   s.nama_satuan as sat_produk
            FROM produksi p
            JOIN master_bahan_setengah_jadi bsj ON p.id_bsj = bsj.id_bsj
            JOIN master_satuan s ON bsj.id_satuan = s.id_satuan
            WHERE DATE(p.tgl_produksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai' AND p.status = 'Selesai'
            GROUP BY p.id_bsj
            UNION ALL
            SELECT 'MENU' as tipe_bom, d.id_menu as id_induk, m.nama_menu as nama_produk, 
                   SUM(d.qty_terjual) as total_proses, 
                   SUM(d.qty_terjual) as total_hasil, 
                   0 as total_waste,
                   s.nama_satuan as sat_produk
            FROM menu_terjual h
            JOIN detail_menu_terjual d ON h.id_jual = d.id_jual
            JOIN master_menu m ON d.id_menu = m.id_menu
            JOIN master_satuan s ON m.id_satuan = s.id_satuan
            WHERE h.tanggal_transaksi BETWEEN '$tgl_mulai' AND '$tgl_selesai'
            GROUP BY d.id_menu
        ";
        
        $q_produksi = mysqli_query($koneksi, $sql_prod);
        $no = 1;

        if($q_produksi && mysqli_num_rows($q_produksi) > 0) {
            while($prod = mysqli_fetch_assoc($q_produksi)) :
                $id_induk = $prod['id_induk'];
                $tipe_bom = $prod['tipe_bom'];
                $ada_waste = $prod['total_waste'] > 0;
                
                $ui_produksi = "<div class='text-muted mt-1'>Rencana: <b>".(float)$prod['total_proses']." {$prod['sat_produk']}</b></div>";
                $ui_produksi .= "<div class='text-muted'>Berhasil: <b>".(float)$prod['total_hasil']." {$prod['sat_produk']}</b></div>";

                $sql_bahan = "SELECT b.*, 
                              COALESCE(bb.nama_bb, bsj2.nama_bsj) as nama_bahan,
                              s_bom.nama_satuan as sat_bom,
                              k.nilai_konversi, k.satuan_kecil as id_satuan_kecil, s_besar.nama_satuan as sat_besar
                              FROM master_bom b
                              LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
                              LEFT JOIN master_bahan_setengah_jadi bsj2 ON b.id_bsj = bsj2.id_bsj
                              LEFT JOIN master_satuan s_bom ON b.id_satuan = s_bom.id_satuan
                              LEFT JOIN master_konversi k ON (b.id_bb = k.id_komponen AND k.tipe_bahan = 'BB') 
                                                          OR (b.id_bsj = k.id_komponen AND k.tipe_bahan = 'BSJ')
                              LEFT JOIN master_satuan s_besar ON k.satuan_besar = s_besar.id_satuan
                              WHERE b.id_induk = '$id_induk' AND b.tipe_bom = '$tipe_bom'";
                
                $q_bahan = mysqli_query($koneksi, $sql_bahan);
                
                // Kumpulkan bahan ke dalam array untuk menghitung ROWSPAN
                $bahan_list = [];
                while($row = mysqli_fetch_assoc($q_bahan)) {
                    $bahan_list[] = $row;
                }
                
                $jml_bahan = count($bahan_list);
                $rowspan = $jml_bahan > 0 ? $jml_bahan : 1;
        ?>
        <tbody class="item-group">
            <?php 
            if($jml_bahan > 0) {
                $first = true;
                foreach($bahan_list as $row) {
                    $qty_resep = (float)$row['qty'];
                    $target_hasil = (float)$row['target_hasil'];
                    if($target_hasil <= 0) $target_hasil = 1;
                    
                    $total_pakai = ($qty_resep / $target_hasil) * $prod['total_proses'];
                    
                    $teks_konv_total = "";
                    if (!empty($row['nilai_konversi']) && $row['id_satuan'] == $row['id_satuan_kecil']) {
                        $konv_total = $total_pakai / $row['nilai_konversi'];
                        $teks_konv_total = "<br><span class='text-muted' style='font-size: 7.5pt;'>(≈ " . (float)round($konv_total, 2) . " " . $row['sat_besar'] . ")</span>";
                    }
                    
                    $teks_target = ($target_hasil > 1) ? "<br><span class='text-muted' style='font-size: 7.5pt;'>per ".(float)$target_hasil." {$prod['sat_produk']}</span>" : "";
                    
                    echo "<tr>";
                    // Tampilkan NO dan PRODUK hanya di baris pertama saja (Di-Merge ke bawah)
                    if($first) {
                        echo "<td class='text-center' rowspan='{$rowspan}'>{$no}</td>";
                        echo "<td rowspan='{$rowspan}'>
                                <b>".strtoupper($prod['nama_produk'])." ({$tipe_bom})</b>
                                {$ui_produksi}
                              </td>";
                        $first = false;
                    }
                    
                    // Kolom bahan sejajar sempurna
                    echo "<td>- {$row['nama_bahan']}</td>";
                    echo "<td class='text-center'>{$qty_resep} {$row['sat_bom']} {$teks_target}</td>";
                    echo "<td class='text-center'><b>".(float)round($total_pakai, 2)." {$row['sat_bom']}</b> {$teks_konv_total}</td>";
                    echo "</tr>";
                }
                $no++;
            } else {
                // Jika belum ada resep BOM
                echo "<tr>";
                echo "<td class='text-center'>{$no}</td>";
                echo "<td><b>".strtoupper($prod['nama_produk'])." ({$tipe_bom})</b>{$ui_produksi}</td>";
                echo "<td class='text-muted'><i>Resep belum diatur</i></td>";
                echo "<td class='text-center'>-</td>";
                echo "<td class='text-center'>-</td>";
                echo "</tr>";
                $no++;
            }
            ?>
            
            <?php if($ada_waste): ?>
            <tr class="waste-row">
                <td colspan="5" style="padding: 5px 8px;">
                    <b>* INFO WASTE:</b> Terdapat produk yang terbuang/gagal sebanyak <b><?= (float)$prod['total_waste'] ?> <?= $prod['sat_produk'] ?></b> pada proses produksi ini.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
        <?php 
            endwhile; 
        } else {
            echo "<tbody><tr><td colspan='5' class='text-center fw-bold' style='padding: 30px;'>Tidak ada data pemakaian bahan pada periode ini.</td></tr></tbody>";
        }
        ?>
    </table>

    <table class="ttd-area">
        <tr>
            <td width="70%"></td>
            <td width="30%">
                Malang, <?= date('d M Y') ?><br>
                Mengetahui,<br><br><br><br><br>
                <b>( .................................... )</b><br>
                Owner / Manager
            </td>
        </tr>
    </table>
    
    <div class="footer-note no-print">
        Laporan dicetak oleh: <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Karyawan') ?> | Waktu cetak: <?= date('d/m/Y H:i:s') ?>
    </div>

    <script>
        // Tutup tab otomatis kalau user klik 'Cancel' saat ngeprint
        window.onafterprint = function() { window.close(); };
    </script>
</body>
</html>