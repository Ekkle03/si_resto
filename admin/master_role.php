<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Mengambil data role dari database
$result = mysqli_query($koneksi, "SELECT * FROM master_role ORDER BY id_role ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Role - Sistem Resto</title>
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
                                                <div class="avatar-lg"><img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" /></div>
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
                                    <h4 class="card-title">Data Master Role</h4>
                                    <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addRoleModal">
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
                                        <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%;" class="text-center">ID Role</th>
                                                    <th class="text-center">Nama Role</th>
                                                    <th style="width: 20%;" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td class="text-center"><?= htmlspecialchars($row['id_role']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_role']) ?></td>
                                                    <td class="text-center">
                                                        <div class="form-button-action">
                                                            <button type="button" data-bs-toggle="tooltip" title="Edit Data" class="btn btn-primary btn-sm btn-update" data-id_role='<?= htmlspecialchars($row['id_role']) ?>' data-nama_role='<?= htmlspecialchars($row['nama_role']) ?>'>
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button type="button" data-bs-toggle="tooltip" title="Hapus Data" class="btn btn-danger btn-sm btn-delete" data-id_role='<?= htmlspecialchars($row['id_role']) ?>'>
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

    <!-- Modals (Tambah, Update, Hapus) -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_role.php">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoleModalLabel">Tambah Data Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nama_role" class="form-label">Nama Role</label>
                            <input type="text" class="form-control" id="nama_role" name="nama_role" placeholder="Masukkan nama role" required />
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

    <div class="modal fade" id="updateRoleModal" tabindex="-1" aria-labelledby="updateRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="update_role.php">
                    <input type="hidden" name="id_role" id="update_id_role" />
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateRoleModalLabel">Update Data Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="update_nama_role" class="form-label">Nama Role</label>
                            <input type="text" class="form-control" id="update_nama_role" name="nama_role" placeholder="Ubah nama role" required />
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

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus role ini?</p>
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

            // Auto-hide alert & redirect ke halaman ini lagi
            if ($('.alert-success').length) {
                setTimeout(function() {
                    $('.alert-success').fadeOut('slow', function() {
                        window.location.href = 'master_role.php';
                    });
                }, 3000);
            }
        });

        // Event listener untuk tombol Update
        document.querySelectorAll('.btn-update').forEach(button => {
            button.addEventListener('click', function() {
                const idRole = this.dataset.id_role;
                const namaRole = this.dataset.nama_role;
                document.getElementById('update_id_role').value = idRole;
                document.getElementById('update_nama_role').value = namaRole;
                const updateModal = new bootstrap.Modal(document.getElementById('updateRoleModal'));
                updateModal.show();
            });
        });

        // Event listener untuk tombol Delete
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const roleId = this.dataset.id_role;
                const deleteLink = document.getElementById('confirmDeleteLink');
                deleteLink.href = 'delete_role.php?id=' + roleId;
                const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
                deleteModal.show();
            });
        });
    </script>
</body>
</html>
