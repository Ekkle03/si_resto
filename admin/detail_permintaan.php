<?php
// detail_permintaan.php - Menampilkan rincian dari satu transaksi permintaan
session_start();
include("../config/koneksi_mysql.php");

// Validasi ID Permintaan dari URL
$id_permintaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_permintaan <= 0) {
    header("Location: permintaan.php");
    exit;
}

// Ambil data header permintaan
$header = null;
$q_header = mysqli_query($koneksi, "
    SELECT p.*, k.nama_lengkap, asal.nama_gudang AS gudang_asal, tujuan.nama_gudang AS gudang_tujuan
    FROM permintaan_barang p
    JOIN master_karyawan k ON p.id_karyawan = k.id_karyawan
    JOIN master_gudang asal ON p.id_gudang_asal = asal.id_gudang
    JOIN master_gudang tujuan ON p.id_gudang_tujuan = tujuan.id_gudang
    WHERE p.id_permintaan = $id_permintaan
");
if ($q_header) $header = mysqli_fetch_assoc($q_header);
if (!$header) {
    die("Data permintaan tidak ditemukan.");
}

// Ambil data detail item
$detail_items = [];
$q_detail = mysqli_query($koneksi, "
    SELECT dp.id_detail_permintaan, i.nama_item, s.nama_satuan, dp.jumlah_permintaan
    FROM detail_permintaaan dp
    JOIN master_item i ON dp.id_item = i.id_item
    LEFT JOIN master_satuan s ON i.id_satuan = s.id_satuan
    WHERE dp.id_permintaan = $id_permintaan
    ORDER BY i.nama_item
");
if ($q_detail) while($r = mysqli_fetch_assoc($q_detail)) $detail_items[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Permintaan - Sistem Resto</title>
    <!-- (Head content sama seperti file sebelumnya) -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header"><!-- (Header content) --></div>
        <div class="container">
            <div class="page-inner">
                 <div class="page-header">
                    <h3 class="fw-bold mb-3">Detail Permintaan Barang</h3>
                </div>
                <div class="row">
                    <div class="col-md-12">
                         <div class="card">
                            <div class="card-header"><div class="card-title">Informasi Permintaan</div></div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-3">ID Permintaan</dt><dd class="col-sm-9">: <?= htmlspecialchars($header['id_permintaan']) ?></dd>
                                    <dt class="col-sm-3">Tanggal</dt><dd class="col-sm-9">: <?= date('d F Y H:i', strtotime($header['tanggal_permintaan'])) ?></dd>
                                    <dt class="col-sm-3">Peminta</dt><dd class="col-sm-9">: <?= htmlspecialchars($header['nama_lengkap']) ?></dd>
                                    <dt class="col-sm-3">Dari Gudang</dt><dd class="col-sm-9">: <?= htmlspecialchars($header['gudang_asal']) ?></dd>
                                    <dt class="col-sm-3">Ke Gudang</dt><dd class="col-sm-9">: <?= htmlspecialchars($header['gudang_tujuan']) ?></dd>
                                    <dt class="col-sm-3">Keterangan</dt><dd class="col-sm-9">: <?= htmlspecialchars($header['keterangan'] ?: '-') ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="card">
                             <div class="card-header"><div class="card-title">Rincian Item yang Diminta</div></div>
                             <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead><tr><th>No</th><th>Item</th><th class="text-end">Jumlah</th></tr></thead>
                                        <tbody>
                                            <?php if(empty($detail_items)): ?>
                                            <tr><td colspan="3" class="text-center text-muted">Belum ada item ditambahkan.</td></tr>
                                            <?php else: $no = 1; foreach($detail_items as $item): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++ ?></td>
                                                <td><?= htmlspecialchars($item['nama_item']) ?></td>
                                                <td class="text-end"><?= rtrim(rtrim(number_format($item['jumlah_permintaan'], 2, ',', '.'),'0'),',') ?> <?= htmlspecialchars($item['nama_satuan']) ?></td>
                                            </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="permintaan.php" class="btn btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Kembali ke Riwayat
                                    </a>
                                </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/js/core/jquery-3.7.1.min.js"></script><script src="assets/js/core/bootstrap.min.js"></script><script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
