<?php
// produksi.php - Halaman utama untuk manajemen produksi (Versi Sederhana)
session_start();
include("../config/koneksi_mysql.php");

// Ambil data riwayat produksi sesuai database yang ada
$daftar_produksi = [];
$sql = "
    SELECT 
        p.id_produksi,
        p.tanggal_dibuat,
        item.nama_item AS nama_produk
    FROM produksi p
    JOIN master_item item ON p.id_item = item.id_item
    ORDER BY p.tanggal_dibuat DESC
";
$q = mysqli_query($koneksi, $sql);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $daftar_produksi[] = $r;
    }
} else {
    die("Error executing query: " . mysqli_error($koneksi));
}

// Ambil daftar item yang bisa diproduksi (bahan setengah jadi & menu)
$item_produksi_list = [];
$q_item_produksi = mysqli_query($koneksi, "SELECT id_item, nama_item FROM master_item WHERE jenis_item IN ('bahan setengah jadi', 'menu') ORDER BY nama_item");
if ($q_item_produksi) while ($r = mysqli_fetch_assoc($q_item_produksi)) $item_produksi_list[] = $r;

// Hapus pesan flash jika ada
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
    <title>Transaksi Produksi - Sistem Resto</title>
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
            <!-- (Header content goes here) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Transaksi Produksi</h3>
                     <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Transaksi</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Produksi</a></li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title">Daftar Jadwal Produksi</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addProduksiModal">
                                        <i class="fa fa-plus"></i> Buat Produksi
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan): ?>
                                <div class="alert alert-success" role="alert"><?= htmlspecialchars($pesan) ?></div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table id="tabel-produksi" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width:10%">ID Produksi</th>
                                                <th>Item yang Diproduksi</th>
                                                <th style="width:20%">Tanggal Dibuat</th>
                                                <th class="text-center" style="width:15%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($daftar_produksi as $p): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($p['id_produksi']) ?></td>
                                                <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                                                <td><?= date('d F Y', strtotime($p['tanggal_dibuat'])) ?></td>
                                                <td class="text-center">
                                                    <a href="detail_produksi.php?id=<?= $p['id_produksi'] ?>" class="btn btn-info btn-sm" title="Lihat Detail">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                     <a href="produksi_delete.php?id=<?= $p['id_produksi'] ?>" class="btn btn-danger btn-sm" title="Hapus" onclick="return confirm('Yakin ingin menghapus jadwal produksi ini?')">
                                                        <i class="fa fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal untuk Buat Jadwal Produksi Baru -->
<div class="modal fade" id="addProduksiModal" tabindex="-1" aria-labelledby="addProduksiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_produksi.php"> <!-- Nanti buat file prosesnya -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addProduksiModalLabel">Buat Produksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="id_item">Item yang akan Diproduksi</label>
                        <select name="id_item" id="id_item" class="form-select" required>
                            <option value="">-- Pilih Item --</option>
                            <?php foreach ($item_produksi_list as $item): ?>
                            <option value="<?= $item['id_item'] ?>"><?= htmlspecialchars($item['nama_item']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Item dengan jenis "bahan setengah jadi" dan "menu" akan muncul di sini.</small>
                    </div>
                    <div class="form-group">
                        <label for="jumlah_target">Jumlah Produksi</label>
                        <input type="number" step="0.01" class="form-control" name="jumlah_target" id="jumlah_target" placeholder="Masukkan jumlah target produksi" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_dibuat">Tanggal Mulai Produksi</label>
                        <input type="date" class="form-control" name="tanggal_dibuat" id="tanggal_dibuat" required>
                        <small class="form-text text-muted">Ini akan menjadi pengingat jadwal produksi.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabel-produksi').DataTable();

        // Set tanggal mulai default ke hari ini
        document.getElementById('tanggal_dibuat').valueAsDate = new Date();
    });
</script>
</body>
</html>

