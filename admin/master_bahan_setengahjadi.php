<?php
// Mulai session
session_start();

// Koneksi database
include("../config/koneksi_mysql.php");

// Ambil data bahan setengah jadi (join ke satuan & kategori)
$sql = "SELECT bsj.*, s.nama_satuan, k.nama_kategori
        FROM master_bahan_setengah_jadi bsj
        INNER JOIN master_satuan s ON bsj.id_satuan = s.id_satuan
        INNER JOIN master_kategori k ON bsj.id_kategori = k.id_kategori
        ORDER BY bsj.id_bsj ASC";
$result = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Bahan Setengah Jadi - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [ "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons" ],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .btn-outline-primary-thicker { border-width: 2px !important; font-weight: 500 !important; }
    </style>
</head>
<body>
<div class="wrapper">
    <?php include 'sidebar.php'; ?>

    <div class="main-panel">
        <div class="main-header">
            <div class="main-header-logo">
                <div class="logo-header" data-background-color="dark">
                    <a href="dashboard.php" class="logo">
                        <img src="assets/img/logo/logo_resto.png" alt="Logo Resto" class="navbar-brand" height="30" />
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                        <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                    </div>
                    <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                </div>
            </div>
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                                </div>
                                <span class="profile-username">
                                    <span class="op-7">Selamat Datang,</span>
                                    <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg">
                                                <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></h4>
                                                <p class="text-muted"><?= htmlspecialchars($_SESSION['username'] ?? 'guest') ?></p>
                                                <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="#">Pengaturan Akun</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="../logout.php">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Master Data</h3>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4 class="card-title">Data Master Bahan Setengah Jadi</h4>
                                <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto"
                                        data-bs-toggle="modal" data-bs-target="#addBsjModal">
                                    <i class="fa fa-plus"></i> Tambah Data
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover align-middle">
                                        <thead>
                                        <tr>
                                            <th style="width: 6%;" class="text-center">NO</th>
                                            <th style="width: 16%; text-align:center;">KODE BAHAN</th>
                                            <th>Nama Bahan</th>
                                            <th style="width: 15%; text-align:center;">Satuan</th>
                                            <th style="width: 18%; text-align:center;">Kategori</th>
                                            <th style="width: 10%; text-align:center;">Tahap</th>
                                            <th style="width: 14%;" class="text-center">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++; ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['kode_bsj']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_bsj']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                <td class="text-center">
                                                    <?= $row['tahap'] === 'bsj2' ? 'BSJ 2' : 'BSJ 1' ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <button type="button"
                                                                class="btn btn-primary btn-sm btn-update"
                                                                data-bs-toggle="tooltip"
                                                                title="Edit Data"
                                                                data-id_bsj="<?= htmlspecialchars($row['id_bsj']) ?>"
                                                                data-nama_bsj="<?= htmlspecialchars($row['nama_bsj']) ?>"
                                                                data-id_satuan="<?= htmlspecialchars($row['id_satuan']) ?>"
                                                                data-id_kategori="<?= htmlspecialchars($row['id_kategori']) ?>"
                                                                data-tahap="<?= htmlspecialchars($row['tahap']) ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-danger btn-sm btn-delete"
                                                                data-bs-toggle="tooltip"
                                                                title="Hapus Data"
                                                                data-id_bsj="<?= htmlspecialchars($row['id_bsj']) ?>">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div><!-- card -->
                    </div>
                </div><!-- row -->
            </div>
        </div>
    </div>
</div>

<!-- ================== Modals ================== -->

<!-- Tambah -->
<div class="modal fade" id="addBsjModal" tabindex="-1" aria-labelledby="addBsjModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_bahan_setengahjadi.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBsjModalLabel">Tambah Data Bahan Setengah Jadi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Nama Bahan Setengah Jadi</label>
                        <input type="text"
                               class="form-control"
                               name="nama_bsj"
                               placeholder="Masukkan nama bahan"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="id_satuan" required>
                            <option value="" disabled selected>-- Pilih Satuan --</option>
                            <?php
                            $satuan = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                            while ($s = mysqli_fetch_assoc($satuan)): ?>
                                <option value="<?= htmlspecialchars($s['id_satuan']) ?>">
                                    <?= htmlspecialchars($s['nama_satuan']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="id_kategori" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php
                            $kategori = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori ORDER BY nama_kategori ASC");
                            while ($k = mysqli_fetch_assoc($kategori)): ?>
                                <option value="<?= htmlspecialchars($k['id_kategori']) ?>">
                                    <?= htmlspecialchars($k['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tahap</label>
                        <select class="form-select" name="tahap" required>
                            <option value="" disabled selected>-- Pilih Tahap --</option>
                            <option value="bsj1">BSJ 1</option>
                            <option value="bsj2">BSJ 2</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update -->
<div class="modal fade" id="updateBsjModal" tabindex="-1" aria-labelledby="updateBsjModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="update_bahan_setengahjadi.php">
                <input type="hidden" name="id_bsj" id="update_id_bsj">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateBsjModalLabel">Update Data Bahan Setengah Jadi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Nama Bahan Setengah Jadi</label>
                        <input type="text"
                               class="form-control"
                               name="nama_bsj"
                               id="update_nama_bsj"
                               required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="id_satuan" id="update_id_satuan" required>
                            <option value="" disabled>-- Pilih Satuan --</option>
                            <?php
                            $satuan2 = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                            while ($s2 = mysqli_fetch_assoc($satuan2)): ?>
                                <option value="<?= htmlspecialchars($s2['id_satuan']) ?>">
                                    <?= htmlspecialchars($s2['nama_satuan']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="id_kategori" id="update_id_kategori" required>
                            <option value="" disabled>-- Pilih Kategori --</option>
                            <?php
                            $kategori2 = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori ORDER BY nama_kategori ASC");
                            while ($k2 = mysqli_fetch_assoc($kategori2)): ?>
                                <option value="<?= htmlspecialchars($k2['id_kategori']) ?>">
                                    <?= htmlspecialchars($k2['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tahap</label>
                        <select class="form-select" name="tahap" id="update_tahap" required>
                            <option value="bsj1">BSJ 1</option>
                            <option value="bsj2">BSJ 2</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hapus -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus bahan setengah jadi ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
    $(document).ready(function () {
        $('#basic-datatables').DataTable();

        // Auto-hide alert + redirect
        if ($('.alert-success').length) {
            setTimeout(function () {
                $('.alert-success').fadeOut('slow', function () {
                    window.location.href = 'master_bahan_setengahjadi.php';
                });
            }, 3000);
        }
    });

    // Tombol Update -> isi modal
    document.querySelectorAll('.btn-update').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const ds = this.dataset;

            document.getElementById('update_id_bsj').value      = ds.id_bsj || '';
            document.getElementById('update_nama_bsj').value    = ds.nama_bsj || '';
            document.getElementById('update_id_satuan').value   = ds.id_satuan || '';
            document.getElementById('update_id_kategori').value = ds.id_kategori || '';
            document.getElementById('update_tahap').value       = ds.tahap || 'bsj1';

            const modal = new bootstrap.Modal(document.getElementById('updateBsjModal'));
            modal.show();
        });
    });

    // Tombol Delete -> isi link konfirmasi
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id_bsj;
            const link = document.getElementById('confirmDeleteLink');
            link.href = 'delete_bahan_setengahjadi.php?id=' + id;

            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            modal.show();
        });
    });
</script>
</body>
</html>
