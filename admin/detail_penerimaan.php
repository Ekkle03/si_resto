<?php
session_start();
include("../config/koneksi_mysql.php");

// Validasi ID Penerimaan dari URL
$id_penerimaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_penerimaan <= 0) {
    $_SESSION['flash_msg'] = "Error: ID Penerimaan tidak valid.";
    header("Location: penerimaan.php");
    exit;
}

// Ambil data header penerimaan
$header = null;
$stmt_header = mysqli_prepare($koneksi, "SELECT tanggal_penerimaan, keterangan FROM penerimaan_barang WHERE id_penerimaan = ?");
mysqli_stmt_bind_param($stmt_header, "i", $id_penerimaan);
mysqli_stmt_execute($stmt_header);
$result_header = mysqli_stmt_get_result($stmt_header);
if ($result_header) $header = mysqli_fetch_assoc($result_header);
mysqli_stmt_close($stmt_header);

// Jika data tidak ditemukan, kembalikan ke halaman riwayat
if (!$header) {
    $_SESSION['flash_msg'] = "Error: Data Penerimaan tidak ditemukan.";
    header("Location: penerimaan.php");
    exit;
}

// Ambil data detail item
$detail_items = [];
$sql_detail = "
    SELECT 
        pd.jumlah_penerimaan,
        i.nama_item,
        s.nama_satuan
    FROM penerimaan_detail pd
    JOIN master_item i ON pd.id_item = i.id_item
    LEFT JOIN master_satuan s ON i.id_satuan = s.id_satuan
    WHERE pd.id_penerimaan = ?
    ORDER BY i.nama_item
";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_penerimaan);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
if ($result_detail) {
    while ($r = mysqli_fetch_assoc($result_detail)) {
        $detail_items[] = $r;
    }
}
mysqli_stmt_close($stmt_detail);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Penerimaan Barang - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: { "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], "urls": ["assets/css/fonts.min.css"], },
        });
    </script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header">
            <!-- (Header content) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Detail Penerimaan Barang</h3>
                    <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="penerimaan.php">Penerimaan Barang</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a>Detail</a></li>
                    </ul>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <!-- CARD 1: INFORMASI TRANSAKSI -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Informasi Transaksi</div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">ID Penerimaan</dt>
                                            <dd class="col-sm-8">: <?= htmlspecialchars($id_penerimaan) ?></dd>
                                            
                                            <dt class="col-sm-4">Tanggal</dt>
                                            <dd class="col-sm-8">: <?= date('d F Y', strtotime($header['tanggal_penerimaan'])) ?></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                         <dl class="row">
                                            <dt class="col-sm-4">Keterangan</dt>
                                            <dd class="col-sm-8">: <?= htmlspecialchars($header['keterangan'] ?: '-') ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2: DAFTAR ITEM DITERIMA -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Rincian Barang yang Diterima</div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">No</th>
                                                <th>Nama Item</th>
                                                <th class="text-end" style="width: 20%;">Jumlah Diterima</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($detail_items)): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada item dalam transaksi ini.</td></tr>
                                            <?php else: 
                                                $no = 1;
                                                foreach($detail_items as $item): 
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td><?= htmlspecialchars($item['nama_item']) ?></td>
                                                    <td class="text-end"><?= rtrim(rtrim(number_format($item['jumlah_penerimaan'], 2, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['nama_satuan']) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <hr>
                                <div class="text-end mt-3">
                                    <a href="penerimaan.php" class="btn btn-secondary">
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
<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>
