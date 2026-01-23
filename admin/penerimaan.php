<?php
// penerimaan.php - Menampilkan riwayat penerimaan barang
session_start();
include("../config/koneksi_mysql.php");

// Ambil data riwayat penerimaan
$riwayat = [];
$sql = "
    SELECT 
        p.id_penerimaan, 
        p.tanggal_penerimaan, 
        p.keterangan,
        (SELECT COUNT(*) FROM penerimaan_detail pd WHERE pd.id_penerimaan = p.id_penerimaan) AS jumlah_jenis_item
    FROM penerimaan_barang p
    ORDER BY p.tanggal_penerimaan DESC
";
$q = mysqli_query($koneksi, $sql);
if ($q) while ($r = mysqli_fetch_assoc($q)) $riwayat[] = $r;

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
    <title>Penerimaan Barang - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />
    
    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { "families": ["Public Sans:300,400,500,600,700"] },
            custom: {
                "families": ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                "urls": ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <style>
        .btn-outline-primary-thicker {
            border-width: 2px !important;
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-panel">
        <div class="main-header">
            <!-- (Header content goes here, e.g., navbar) -->
        </div>
        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Transaksi Penerimaan</h3>
                    <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Transaksi</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Penerimaan Barang</a></li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4 class="card-title">Riwayat Penerimaan Barang</h4>
                                <a href="add_penerimaan.php" class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto">
                                    <i class="fa fa-plus"></i> Tambah Penerimaan
                                </a>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan): ?>
                                <div class="alert alert-success" role="alert"><?= htmlspecialchars($pesan) ?></div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table id="tabel-penerimaan" class="table table-striped table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%">ID</th>
                                                <th>Tanggal</th>
                                                <th>Keterangan</th>
                                                <th class="text-center" style="width: 10%">Jumlah Item</th>
                                                <th class="text-center" style="width: 15%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($riwayat as $r): ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars($r['id_penerimaan']) ?></td>
                                                <td><?= date('d-m-Y H:i', strtotime($r['tanggal_penerimaan'])) ?></td>
                                                <td><?= htmlspecialchars($r['keterangan']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($r['jumlah_jenis_item']) ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <a href="detail_penerimaan.php?id=<?= $r['id_penerimaan'] ?>" data-bs-toggle="tooltip" title="Lihat Detail" class="btn btn-info btn-sm">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" data-bs-toggle="tooltip" title="Hapus Transaksi" class="btn btn-danger btn-sm btn-delete" data-id_penerimaan='<?= htmlspecialchars($r['id_penerimaan']) ?>'>
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
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

<!-- Modal Konfirmasi Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus data penerimaan ini? Stok barang yang sudah ditambahkan akan ikut terhapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<!--   Core JS Files   -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<!-- Datatables -->
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<!-- Kaiadmin JS -->
<script src="assets/js/kaiadmin.min.js"></script>
<script>
    $(document).ready(function() {
        $('#tabel-penerimaan').DataTable();
        
        // Inisialisasi Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Event listener untuk tombol Delete
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const penerimaanId = this.dataset.id_penerimaan;
                const deleteLink = document.getElementById('confirmDeleteLink');
                // Nanti buat file delete_penerimaan.php untuk menghapus header dan detail, serta mengembalikan stok.
                deleteLink.href = 'delete_penerimaan.php?id=' + penerimaanId; 
                const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                deleteModal.show();
            });
        });
    });
</script>
</body>
</html>

