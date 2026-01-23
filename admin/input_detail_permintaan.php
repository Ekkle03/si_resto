<?php
session_start();
include("../config/koneksi_mysql.php");

$id_permintaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_permintaan <= 0) {
    header("Location: permintaan.php");
    exit;
}

// Ambil data header permintaan
$header = null;
$q_header = mysqli_query($koneksi, "SELECT * FROM permintaan_barang WHERE id_permintaan = $id_permintaan");
if ($q_header) $header = mysqli_fetch_assoc($q_header);
if (!$header) {
    die("Permintaan tidak ditemukan.");
}

// Ambil detail item yang sudah ditambahkan
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

// Ambil item kandidat untuk dropdown
$item_kandidat = [];
$q_item = mysqli_query($koneksi, "SELECT i.id_item, i.nama_item, s.nama_satuan FROM master_item i LEFT JOIN master_satuan s ON i.id_satuan = s.id_satuan WHERE i.jenis_item IN ('bahan baku', 'bahan setengah jadi') ORDER BY i.nama_item");
if ($q_item) while ($r = mysqli_fetch_assoc($q_item)) $item_kandidat[] = $r;

$pesan = '';
if (isset($_SESSION['flash_msg'])) {
    $pesan = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Input Detail Permintaan - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-3">Input Detail Permintaan</h3>
                </div>
                <div class="row">
                    <div class="col-md-12">
                         <div class="card">
                            <div class="card-header"><div class="card-title">Informasi Permintaan</div></div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-2">ID Permintaan</dt><dd class="col-sm-10">: <?= htmlspecialchars($header['id_permintaan']) ?></dd>
                                    <dt class="col-sm-2">Tanggal</dt><dd class="col-sm-10">: <?= date('d F Y H:i', strtotime($header['tanggal_permintaan'])) ?></dd>
                                    <dt class="col-sm-2">Keterangan</dt><dd class="col-sm-10">: <?= htmlspecialchars($header['keterangan'] ?: '-') ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="card">
                             <div class="card-header"><div class="card-title">Tambah & Daftar Item</div></div>
                             <div class="card-body">
                                <form method="POST" action="add_permintaan.php" class="mb-4 border-bottom pb-4">
                                    <input type="hidden" name="action" value="tambah_item">
                                    <input type="hidden" name="id_permintaan" value="<?= $id_permintaan ?>">
                                    <div class="row align-items-end">
                                        <div class="col-md-6"><div class="form-group"><label>Pilih Item</label><select class="form-select" name="id_item" required><option value="">-- Pilih --</option><?php foreach($item_kandidat as $item): ?><option value="<?= $item['id_item'] ?>"><?= htmlspecialchars($item['nama_item']) ?> (<?= htmlspecialchars($item['nama_satuan']) ?>)</option><?php endforeach; ?></select></div></div>
                                        <div class="col-md-4"><div class="form-group"><label>Jumlah</label><input type="number" step="0.01" name="jumlah_permintaan" class="form-control" required></div></div>
                                        <div class="col-md-2"><div class="form-group"><button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah</button></div></div>
                                    </div>
                                </form>

                                <?php if ($pesan): ?><div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div><?php endif; ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead><tr><th>Item</th><th class="text-end">Jumlah</th><th class="text-center">Aksi</th></tr></thead>
                                        <tbody>
                                            <?php if(empty($detail_items)): ?>
                                            <tr><td colspan="3" class="text-center text-muted">Belum ada item ditambahkan.</td></tr>
                                            <?php else: foreach($detail_items as $item): ?>
                                            <tr><td><?= htmlspecialchars($item['nama_item']) ?></td><td class="text-end"><?= rtrim(rtrim(number_format($item['jumlah_permintaan'], 2, ',', '.'),'0'),',') ?> <?= htmlspecialchars($item['nama_satuan']) ?></td><td class="text-center"><a href="add_permintaan.php?action=hapus_item&id=<?= $item['id_detail_permintaan'] ?>&id_permintaan=<?= $id_permintaan ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin?')"><i class="fa fa-trash"></i></a></td></tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <a href="permintaan.php" class="btn btn-success btn-round"><i class="fa fa-check"></i> Selesai & Kembali</a>
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
