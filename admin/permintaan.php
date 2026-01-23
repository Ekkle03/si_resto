<?php
// permintaan.php - Menampilkan riwayat permintaan barang internal
session_start();
include("../config/koneksi_mysql.php");

// Ambil data riwayat permintaan
$riwayat = [];
$sql = "
    SELECT 
        p.id_permintaan, 
        p.tanggal_permintaan, 
        p.keterangan,
        k.nama_lengkap AS nama_peminta,
        (SELECT COUNT(*) FROM detail_permintaaan dp WHERE dp.id_permintaan = p.id_permintaan) AS jumlah_jenis_item
    FROM permintaan_barang p
    JOIN master_karyawan k ON p.id_karyawan = k.id_karyawan
    ORDER BY p.tanggal_permintaan DESC
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
    <title>Permintaan Barang - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-3">Transaksi Permintaan Barang</h3>
                     <ul class="breadcrumbs">
                        <li class="nav-home"><a href="#"><i class="icon-home"></i></a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Transaksi</a></li>
                        <li class="separator"><i class="icon-arrow-right"></i></li>
                        <li class="nav-item"><a href="#">Permintaan Barang</a></li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <h4 class="card-title">Riwayat Permintaan</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addPermintaanModal">
                                        <i class="fa fa-plus"></i> Buat Permintaan Baru
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan): ?>
                                <div class="alert alert-info" role="alert"><?= htmlspecialchars($pesan) ?></div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table id="tabel-permintaan" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Tanggal</th>
                                                <th>Peminta</th>
                                                <th>Keterangan</th>
                                                <th class="text-center">Jumlah Item</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($riwayat as $r): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($r['id_permintaan']) ?></td>
                                                <td><?= date('d-m-Y H:i', strtotime($r['tanggal_permintaan'])) ?></td>
                                                <td><?= htmlspecialchars($r['nama_peminta']) ?></td>
                                                <td><?= htmlspecialchars($r['keterangan']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($r['jumlah_jenis_item']) ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <a href="detail_permintaan.php?id=<?= $r['id_permintaan'] ?>" data-bs-toggle="tooltip" title="Lihat Detail" class="btn btn-info btn-sm">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" data-bs-toggle="tooltip" title="Hapus" class="btn btn-danger btn-sm btn-delete" data-id_permintaan="<?= $r['id_permintaan'] ?>">
                                                            <i class="fa fa-trash"></i>
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

<!-- Modal untuk Buat Permintaan Baru -->
<div class="modal fade" id="addPermintaanModal" tabindex="-1" aria-labelledby="addPermintaanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_permintaan.php">
                <input type="hidden" name="action" value="buat_header">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPermintaanModalLabel">Buat Permintaan Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="tanggal_permintaan">Tanggal Permintaan</label>
                        <input type="datetime-local" class="form-control" name="tanggal_permintaan" id="tanggal_permintaan" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="keterangan">Keterangan (Opsional)</label>
                        <textarea class="form-control" name="keterangan" id="keterangan" rows="3" placeholder="Contoh: Stok untuk akhir pekan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Lanjut ke Input Detail</button>
                </div>
            </form>
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
                <p>Apakah Anda yakin ingin menghapus data permintaan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
            </div>
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
        $('#tabel-permintaan').DataTable();
        
        // Inisialisasi Tooltip
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Event listener untuk tombol Delete
        $(document).on('click', '.btn-delete', function() {
            const permintaanId = $(this).data('id_permintaan');
            const deleteLink = document.getElementById('confirmDeleteLink');
            deleteLink.href = 'delete_permintaan.php?id=' + permintaanId;
            const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            deleteModal.show();
        });
    });
</script>
</body>
</html>

