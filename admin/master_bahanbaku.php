<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. QUERY FILTER KATEGORI (Hanya ambil sub-kategori dari induk 'BAHAN BAKU')
$cek_induk = mysqli_query($koneksi, "SELECT id_kategori FROM master_kategori WHERE nama_kategori = 'BAHAN BAKU' LIMIT 1");
$data_induk = mysqli_fetch_assoc($cek_induk);
$id_induk_bb = $data_induk['id_kategori'] ?? 0;

// 2. AMBIL DATA LIST BAHAN BAKU (Termasuk stok_minimal)
$sql_list = "SELECT b.*, s.nama_satuan, k.nama_kategori
             FROM master_bahan_baku b
             INNER JOIN master_satuan s ON b.id_satuan = s.id_satuan
             INNER JOIN master_kategori k ON b.id_kategori = k.id_kategori
             ORDER BY b.id_bb ASC";
$result = mysqli_query($koneksi, $sql_list);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Bahan Baku - Sistem Resto</title>
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
                                <h4 class="card-title">Data Master Bahan Baku</h4>
                                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addBahanBakuModal">
                                    <i class="fa fa-plus"></i> Tambah Data
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center">No</th>
                                                <th class="text-center">Kode</th>
                                                <th>Nama Bahan</th>
                                                <th class="text-center">Satuan</th>
                                                <th class="text-center">Kategori</th>
                                                <th class="text-center">Min. Stok</th>
                                                <th class="text-center">Tipe</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="text-center"><?= $no++; ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['kode_bb']) ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($row['nama_bb']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                <td class="text-center fw-bold text-primary"><?= $row['stok_minimal'] ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= $row['tipe_bahan'] == 'basah' ? 'badge-info' : 'badge-warning' ?>">
                                                        <?= ucfirst($row['tipe_bahan']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-primary btn-sm btn-update me-1"
                                                                data-id_bb="<?= $row['id_bb'] ?>"
                                                                data-nama_bb="<?= htmlspecialchars($row['nama_bb']) ?>"
                                                                data-id_satuan="<?= $row['id_satuan'] ?>"
                                                                data-id_kategori="<?= $row['id_kategori'] ?>"
                                                                data-stok_minimal="<?= $row['stok_minimal'] ?>"
                                                                data-tipe_bahan="<?= $row['tipe_bahan'] ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                                data-id_bb="<?= $row['id_bb'] ?>">
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addBahanBakuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_bahanbaku.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Bahan Baku</label>
                        <input type="text" class="form-control" name="nama_bb" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="id_satuan" required>
                            <option value="" disabled selected>-- Pilih Satuan --</option>
                            <?php 
                            $satuan = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                            while ($s = mysqli_fetch_assoc($satuan)) echo "<option value='".$s['id_satuan']."'>".$s['nama_satuan']."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori (Hanya Bahan Baku)</label>
                        <select class="form-select" name="id_kategori" required>
                            <option value="" disabled selected>-- Pilih Kategori --</option>
                            <?php 
                            $kategori = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori WHERE parent_id = '$id_induk_bb' ORDER BY nama_kategori ASC");
                            while ($k = mysqli_fetch_assoc($kategori)) echo "<option value='".$k['id_kategori']."'>".$k['nama_kategori']."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Minimal (Batas Reorder)</label>
                        <input type="number" step="0.01" class="form-control" name="stok_minimal" value="0" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Bahan</label>
                        <select class="form-select" name="tipe_bahan" required>
                            <option value="kering">Kering</option>
                            <option value="basah">Basah</option>
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

<div class="modal fade" id="updateBahanBakuModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="update_bahanbaku.php">
                <input type="hidden" name="id_bb" id="update_id_bb">
                <div class="modal-header">
                    <h5 class="modal-title">Update Data Bahan Baku</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Bahan Baku</label>
                        <input type="text" class="form-control" name="nama_bb" id="update_nama_bb" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="id_satuan" id="update_id_satuan" required>
                            <?php 
                            $satuan2 = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                            while ($s2 = mysqli_fetch_assoc($satuan2)) echo "<option value='".$s2['id_satuan']."'>".$s2['nama_satuan']."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="id_kategori" id="update_id_kategori" required>
                            <?php 
                            $kategori2 = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori WHERE parent_id = '$id_induk_bb' ORDER BY nama_kategori ASC");
                            while ($k2 = mysqli_fetch_assoc($kategori2)) echo "<option value='".$k2['id_kategori']."'>".$k2['nama_kategori']."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Minimal (Batas Reorder)</label>
                        <input type="number" step="0.01" class="form-control" name="stok_minimal" id="update_stok_minimal" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Bahan</label>
                        <select class="form-select" name="tipe_bahan" id="update_tipe_bahan" required>
                            <option value="kering">Kering</option>
                            <option value="basah">Basah</option>
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

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus bahan baku ini?
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
    $(document).ready(function () {
        $('#basic-datatables').DataTable();

        if ($('.alert').length) {
            setTimeout(function () {
                $('.alert').fadeOut('slow', function () {
                    window.history.replaceState({}, document.title, "master_bahanbaku.php");
                });
            }, 3000);
        }

        $(document).on('click', '.btn-update', function() {
            $('#update_id_bb').val($(this).data('id_bb'));
            $('#update_nama_bb').val($(this).data('nama_bb'));
            $('#update_id_satuan').val($(this).data('id_satuan'));
            $('#update_id_kategori').val($(this).data('id_kategori'));
            $('#update_stok_minimal').val($(this).data('stok_minimal'));
            $('#update_tipe_bahan').val($(this).data('tipe_bahan'));
            $('#updateBahanBakuModal').modal('show');
        });

        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id_bb');
            $('#confirmDeleteLink').attr('href', 'delete_bahanbaku.php?id=' + id);
            $('#confirmDeleteModal').modal('show');
        });
    });
</script>
</body>
</html>