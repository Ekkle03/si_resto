<?php
session_start();
include("../config/koneksi_mysql.php");

// Validasi ID Produksi dari URL
$id_produksi = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_produksi <= 0) {
    $_SESSION['flash_msg'] = "Error: ID Produksi tidak valid.";
    header("Location: produksi.php");
    exit;
}

// Ambil data header produksi
$produksi_header = null;
$sql_header = "
    SELECT 
        p.id_produksi,
        p.tanggal_dibuat,
        p.jumlah_akhir AS jumlah_target, -- Mengambil jumlah_target yang disimpan di kolom jumlah_akhir
        item.id_item,
        item.nama_item AS nama_produk,
        satuan.nama_satuan
    FROM produksi p
    JOIN master_item item ON p.id_item = item.id_item
    LEFT JOIN master_satuan satuan ON item.id_satuan = satuan.id_satuan
    WHERE p.id_produksi = ?
";
$stmt_header = mysqli_prepare($koneksi, $sql_header);
mysqli_stmt_bind_param($stmt_header, "i", $id_produksi);
mysqli_stmt_execute($stmt_header);
$result_header = mysqli_stmt_get_result($stmt_header);
if ($result_header) $produksi_header = mysqli_fetch_assoc($result_header);
mysqli_stmt_close($stmt_header);

// Jika data tidak ditemukan, kembalikan ke halaman riwayat
if (!$produksi_header) {
    $_SESSION['flash_msg'] = "Error: Data Produksi tidak ditemukan.";
    header("Location: produksi.php");
    exit;
}

// Ambil data detail resep (BOM)
$bom_detail = [];
$id_item_produksi = $produksi_header['id_item'];
$sql_detail = "
    SELECT 
        bom.qty,
        komponen.nama_item AS nama_komponen,
        satuan.nama_satuan
    FROM tabel_bom bom
    JOIN master_item komponen ON bom.komponen_id = komponen.id_item
    LEFT JOIN master_satuan satuan ON komponen.id_satuan = satuan.id_satuan
    WHERE bom.produk_id = ?
    ORDER BY komponen.nama_item
";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "i", $id_item_produksi);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
if ($result_detail) {
    while ($r = mysqli_fetch_assoc($result_detail)) {
        $bom_detail[] = $r;
    }
}
mysqli_stmt_close($stmt_detail);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Produksi - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-3">Detail Produksi</h3>
                    <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="produksi.php">Produksi</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a>Detail</a></li>
                    </ul>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <!-- CARD 1: INFORMASI PRODUKSI -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Informasi Jadwal Produksi</div>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-3">ID Produksi</dt>
                                    <dd class="col-sm-9">: <?= htmlspecialchars($produksi_header['id_produksi']) ?></dd>
                                    
                                    <dt class="col-sm-3">Tanggal Dibuat</dt>
                                    <dd class="col-sm-9">: <?= date('d F Y', strtotime($produksi_header['tanggal_dibuat'])) ?></dd>
                                    
                                    <dt class="col-sm-3">Produk yang Dibuat</dt>
                                    <dd class="col-sm-9">: <?= htmlspecialchars($produksi_header['nama_produk']) ?></dd>

                                    <dt class="col-sm-3">Jumlah Target</dt>
                                    <dd class="col-sm-9">: <?= rtrim(rtrim(number_format($produksi_header['jumlah_target'], 2, ',', '.'),'0'),',') ?> <?= htmlspecialchars($produksi_header['nama_satuan']) ?></dd>
                                </dl>
                            </div>
                        </div>

                        <!-- CARD 2: RINCIAN RESEP (BOM) -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">Rincian Resep (Bill of Material)</div>
                            </div>
                            <div class="card-body">
                                <p>Berikut adalah bahan-bahan yang dibutuhkan untuk memproduksi <strong>1 unit <?= htmlspecialchars($produksi_header['nama_produk']) ?></strong>.</p>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">No</th>
                                                <th>Nama Komponen</th>
                                                <th class="text-end" style="width: 20%;">Jumlah Dibutuhkan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($bom_detail)): ?>
                                                <tr><td colspan="3" class="text-center text-muted py-4">Resep untuk item ini belum diatur di Master BOM.</td></tr>
                                            <?php else: 
                                                $no = 1;
                                                foreach($bom_detail as $item): 
                                            ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td><?= htmlspecialchars($item['nama_komponen']) ?></td>
                                                    <td class="text-end"><?= rtrim(rtrim(number_format($item['qty'], 3, ',', '.'), '0'), ',') ?> <?= htmlspecialchars($item['nama_satuan']) ?></td>
                                                </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 3: FORM VERIFIKASI (UNTUK PURCHASING) -->
                        <div class="card">
                             <div class="card-header">
                                <div class="card-title">Verifikasi Hasil Produksi</div>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="update_produksi.php" onsubmit="return confirm('Anda yakin ingin menyelesaikan produksi ini? Stok akan diperbarui dan tidak bisa diubah lagi.')">
                                    <input type="hidden" name="id_produksi" value="<?= $id_produksi ?>">
                                    <div class="form-group">
                                        <label for="jumlah_akhir">Jumlah Hasil Aktual</label>
                                        <input type="number" step="0.01" class="form-control" name="jumlah_akhir" id="jumlah_akhir" placeholder="Masukkan jumlah hasil hitungan fisik" required>
                                        <small class="form-text text-muted">Isi dengan jumlah produk jadi yang diterima dari bagian produksi.</small>
                                    </div>
                                    <div class="text-end">
                                        <a href="produksi.php" class="btn btn-secondary">Kembali</a>
                                        <button type="submit" class="btn btn-success">Selesaikan & Catat Stok</button>
                                    </div>
                                </form>
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
