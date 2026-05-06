<?php
// Mulai session di awal
session_start();
include("../config/auth.php");
// Hubungkan ke database
include("../config/koneksi_mysql.php");

// QUERY PERBAIKAN: Mengurutkan agar Sub-Kategori nempel di bawah Induknya
$query = "SELECT * FROM master_kategori 
          ORDER BY (CASE WHEN parent_id = 0 THEN id_kategori ELSE parent_id END) ASC, 
          parent_id ASC";
$result = mysqli_query($koneksi, $query);

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
    <title>Master Kategori</title>
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
                                    <h4 class="card-title">Data Master Kategori</h4>
                                    <div class="ms-auto">
                                        <?php if (can_edit()): ?>
                                        <button class="btn btn-primary btn-round me-2" data-bs-toggle="modal" data-bs-target="#addKategoriModal">
                                            <i class="fa fa-plus"></i> Kategori Utama
                                        </button>
                                        <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round" data-bs-toggle="modal" data-bs-target="#addSubKategoriModal">
                                            <i class="fas fa-level-down-alt"></i> Sub-Kategori
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['msg'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_GET['msg']) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 8%;" class="text-center">No</th>
                                                    <th style="width: 20%;" class="text-center">Kode Kategori</th>
                                                    <th class="text-center">Nama Kategori</th>
                                                    <?php if (can_edit()): ?>
                                                    <th style="width: 20%;" class="text-center">Action</th>
                                                    <?php endif; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($result)): 
                                                    $is_sub = ($row['parent_id'] != 0);
                                                ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++; ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($row['kode_kategori']) ?></td>
                                                    <td class="<?= $is_sub ? 'indent-sub' : 'fw-bold' ?>">
                                                        <?php if($is_sub): ?>
                                                            <i class="fas fa-angle-right me-2 text-muted"></i>
                                                        <?php endif; ?>
                                                        <?= htmlspecialchars($row['nama_kategori']) ?>
                                                    </td>
                                                    
                                                    <?php if (can_edit()): ?>
                                                    <td class="text-center">
                                                        <div class="form-button-action">
                                                            <button type="button" class="btn btn-primary btn-sm btn-update"
                                                                data-id_kategori='<?= $row['id_kategori'] ?>'
                                                                data-nama_kategori='<?= htmlspecialchars($row['nama_kategori']) ?>'>
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                                data-id_kategori='<?= $row['id_kategori'] ?>'>
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

    <div class="modal fade" id="addKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_kategori.php">
                <input type="hidden" name="parent_id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Kategori Utama</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori Utama</label>
                        <input type="text" class="form-control" name="nama_kategori" placeholder="Masukkan nama kategori" required />
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

<div class="modal fade" id="addSubKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_kategori.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Sub-Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Kategori Utama (Induk)</label>
                        <select class="form-select" name="parent_id" required>
                            <option value="">-- Pilih Induk --</option>
                            <?php 
                            $res_induk = mysqli_query($koneksi, "SELECT * FROM master_kategori WHERE parent_id = 0 ORDER BY nama_kategori ASC");
                            while($i = mysqli_fetch_assoc($res_induk)) {
                                echo "<option value='".$i['id_kategori']."'>".$i['nama_kategori']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Sub-Kategori</label>
                        <input type="text" class="form-control" name="nama_kategori" placeholder="Masukkan nama sub-kategori" required />
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

<div class="modal fade" id="updateKategoriModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="update_kategori.php">
                <input type="hidden" name="id_kategori" id="update_id_kategori" />
                <div class="modal-header">
                    <h5 class="modal-title">Update Data Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="update_nama_kategori" class="form-label">Nama Kategori</label>
                        <input type="text" class="form-control" id="update_nama_kategori" name="nama_kategori" required />
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus kategori ini?</p>
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
        // 1. Inisialisasi DataTable
        $('#basic-datatables').DataTable({ "ordering": false });

        // 2. LOGIKA AUTO-HIDE ALERT & REDIRECT (Ini yang kamu mau)
        if ($('.alert').length) {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    // Menghilangkan parameter ?msg= di URL tanpa reload halaman
                    window.history.replaceState({}, document.title, "master_kategori.php");
                });
            }, 3000); // Alert hilang dalam 3 detik
        }

        // 3. Handler tombol Update
        $(document).on('click', '.btn-update', function() {
            var id = $(this).data('id_kategori');
            var nama = $(this).data('nama_kategori');
            $('#update_id_kategori').val(id);
            $('#update_nama_kategori').val(nama);
            $('#updateKategoriModal').modal('show');
        });

        // 4. Handler tombol Hapus
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id_kategori');
            $('#confirmDeleteLink').attr('href', 'delete_kategori.php?id=' + id);
            $('#confirmDeleteModal').modal('show');
        });
    });
</script>
</body>
</html>