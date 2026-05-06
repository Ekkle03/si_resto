<?php
// Mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Mengambil data divisi dari database
$result = mysqli_query($koneksi, "SELECT * FROM master_divisi ORDER BY id_divisi ASC");

// ── Navbar: siapkan variabel session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Divisi</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons"
                ],
                urls: ["assets/css/fonts.min.css"],
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
                <!-- ── NAVBAR DIPERBAIKI ──────────────────────────────────────── -->
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="<?= $foto ?>"
                                         alt="Foto Profil"
                                         class="avatar-img rounded-circle"
                                         onerror="this.src='assets/img/profil/default.png'" />
                                </div>
                                <span class="profile-username">
                                    <span class="op-7">Selamat Datang,</span>
                                    <span class="fw-bold"><?= $nama ?></span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <div class="dropdown-user-scroll scrollbar-outer">
                                    <li>
                                        <div class="user-box">
                                            <div class="avatar-lg">
                                                <img src="<?= $foto ?>"
                                                     alt="Foto Profil"
                                                     class="avatar-img rounded"
                                                     onerror="this.src='assets/img/profil/default.png'" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?= $nama ?></h4>
                                                <p class="text-muted">@<?= $username ?></p>
                                                <?php if (!empty($role)): ?>
                                                    <span class="badge bg-secondary mb-2"><?= $role ?></span>
                                                <?php endif; ?>
                                                <br>
                                                <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="../logout.php">Logout</a>
                                    </li>
                                </div>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- ── END NAVBAR ─────────────────────────────────────────────── -->
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
                                    <h4 class="card-title">Data Master Divisi</h4>
                                    <?php if (can_edit()): ?>
                                    <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addDivisiModal">
                                        <i class="fa fa-plus"></i> Tambah Data
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['msg'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_GET['msg']) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 8%;" class="text-center">No</th>
                                                    <th style="width: 20%;" class="text-center">Kode Divisi</th>
                                                    <th class="text-center">Nama Divisi</th>
                                                    <?php if (can_edit()): ?>
                                                    <th style="width: 20%;" class="text-center">Action</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($result)): 
                                                ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($row['kode_divisi']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_divisi']) ?></td>
                                                    
                                                    <?php if (can_edit()): ?>
                                                    <td class="text-center">
                                                        <div class="form-button-action">
                                                            <button 
                                                                type="button" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Edit Data" 
                                                                class="btn btn-primary btn-sm btn-update"
                                                                data-id_divisi='<?= htmlspecialchars($row['id_divisi']) ?>'
                                                                data-kode_divisi='<?= htmlspecialchars($row['kode_divisi']) ?>'
                                                                data-nama_divisi='<?= htmlspecialchars($row['nama_divisi']) ?>'>
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button 
                                                                type="button" 
                                                                data-bs-toggle="tooltip" 
                                                                title="Hapus Data" 
                                                                class="btn btn-danger btn-sm btn-delete"
                                                                data-id_divisi='<?= htmlspecialchars($row['id_divisi']) ?>'>
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <?php endif; ?>

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

    <!-- Modal Tambah Divisi -->
    <div class="modal fade" id="addDivisiModal" tabindex="-1" aria-labelledby="addDivisiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_divisi.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDivisiModalLabel">Tambah Data Divisi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_divisi" class="form-label">Nama Divisi</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="nama_divisi" 
                                name="nama_divisi" 
                                placeholder="Masukkan nama divisi" 
                                required />
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

    <!-- Modal Update Divisi -->
    <div class="modal fade" id="updateDivisiModal" tabindex="-1" aria-labelledby="updateDivisiModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="update_divisi.php">
                    <input type="hidden" name="id_divisi" id="update_id_divisi" />
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateDivisiModalLabel">Update Data Divisi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="update_nama_divisi" class="form-label">Nama Divisi</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="update_nama_divisi" 
                                name="nama_divisi" 
                                placeholder="Ubah nama divisi" 
                                required />
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

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus divisi ini?</p>
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
        $(document).ready(function() {
            $('#basic-datatables').DataTable();

            // Auto hide alert & redirect
            if ($('.alert-success').length) {
                setTimeout(function() {
                    $('.alert-success').fadeOut('slow', function() {
                        window.location.href = 'master_divisi.php';
                    });
                }, 3000);
            }
        });

        // Event listener untuk tombol Update
        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function() {
                const idDivisi   = this.dataset.id_divisi;
                const namaDivisi = this.dataset.nama_divisi;

                document.getElementById('update_id_divisi').value   = idDivisi;
                document.getElementById('update_nama_divisi').value = namaDivisi;

                const updateModal = new bootstrap.Modal(document.getElementById('updateDivisiModal'));
                updateModal.show();
            });
        });

        // Event listener untuk tombol Delete
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const divisiId   = this.dataset.id_divisi;
                const deleteLink = document.getElementById('confirmDeleteLink');
                deleteLink.href  = 'delete_divisi.php?id=' + divisiId;

                const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>
