<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Cari ID Induk untuk kategori 'Menu' (Pastikan nama di database sesuai)
$cek_parent = mysqli_query($koneksi, "SELECT id_kategori FROM master_kategori WHERE nama_kategori = 'Menu' LIMIT 1");
$data_parent = mysqli_fetch_assoc($cek_parent);
$id_induk_menu = $data_parent['id_kategori'] ?? 0;

// 2. Query JOIN untuk List Tabel
$sql = "SELECT m.*, s.nama_satuan, k.nama_kategori 
        FROM master_menu m
        LEFT JOIN master_satuan s ON m.id_satuan = s.id_satuan
        LEFT JOIN master_kategori k ON m.id_kategori = k.id_kategori
        ORDER BY m.id_menu ASC";
$result = mysqli_query($koneksi, $sql);

// 3. Ambil data SUB-KATEGORI (Makanan & Minuman) untuk Dropdown Modal
// Hanya mengambil kategori yang punya parent_id ke kategori 'Menu'
$kategori_list = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori WHERE parent_id = '$id_induk_menu' ORDER BY nama_kategori ASC");
$satuan_list = mysqli_query($koneksi, "SELECT * FROM master_satuan ORDER BY nama_satuan ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Divisi - Sistem Resto</title>
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
                                    <h4 class="card-title">Data Master Menu</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addMenuModal">
                                        <i class="fa fa-plus"></i> Tambah Menu
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($_GET['msg'])): ?>
                                        <div id="alert-notif" class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_GET['msg']) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table id="basic-datatables" class="table table-striped table-bordered table-hover">
                                            <thead class="bg-light text-center">
                                                <tr>
                                                    <th style="width: 5%;">NO</th>
                                                    <th>KODE</th>
                                                    <th>NAMA MENU</th>
                                                    <th>KATEGORI</th>
                                                    <th>SATUAN</th>
                                                    <th style="width: 10%;">ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                while ($row = mysqli_fetch_assoc($result)): 
                                                    $kode_tampil = "MNU-" . str_pad($row['id_menu'], 3, '0', STR_PAD_LEFT);
                                                ?>
                                                <tr class="text-center">
                                                    <td><?= $no++; ?></td>
                                                    <td class="fw-bold"><?= $kode_tampil ?></td>
                                                    <td class="text-start"><?= htmlspecialchars($row['nama_menu']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                    <td><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <button type="button" class="btn btn-primary btn-sm btn-update me-1"
                                                                data-id_menu="<?= $row['id_menu'] ?>"
                                                                data-id_satuan="<?= $row['id_satuan'] ?>"
                                                                data-id_kategori="<?= $row['id_kategori'] ?>"
                                                                data-nama_menu="<?= htmlspecialchars($row['nama_menu']) ?>">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                                data-id_menu="<?= $row['id_menu'] ?>">
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

    <div class="modal fade" id="addMenuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="add_menu.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Menu</label>
                            <input type="text" class="form-control" name="nama_menu" placeholder="Misal: Nasi Goreng" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori Menu</label>
                            <select class="form-select" name="id_kategori" required>
                                <option value="" disabled selected>-- Pilih Ketegori --</option>
                                <?php mysqli_data_seek($kategori_list, 0); while($k = mysqli_fetch_assoc($kategori_list)): ?>
                                    <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Satuan</label>
                            <select class="form-select" name="id_satuan" required>
                                <option value="" disabled selected>-- Pilih Satuan --</option>
                                <?php mysqli_data_seek($satuan_list, 0); while($s = mysqli_fetch_assoc($satuan_list)): ?>
                                    <option value="<?= $s['id_satuan'] ?>"><?= $s['nama_satuan'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <hr>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="langsung_bom" value="1" id="menu_bom">
                            <label class="form-check-label fw-bold" for="menu_bom">
                                Langsung buat BOM setelah simpan?
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnSubmitMenu" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="updateMenuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="update_menu.php">
                    <input type="hidden" name="id_menu" id="update_id_menu" />
                    <div class="modal-header">
                        <h5 class="modal-title">Update Data Menu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Menu</label>
                            <input type="text" class="form-control" name="nama_menu" id="update_nama_menu" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kategori Menu</label>
                            <select class="form-select" name="id_kategori" id="update_id_kategori" required>
                                <?php mysqli_data_seek($kategori_list, 0); while($k = mysqli_fetch_assoc($kategori_list)): ?>
                                    <option value="<?= $k['id_kategori'] ?>"><?= $k['nama_kategori'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Satuan</label>
                            <select class="form-select" name="id_satuan" id="update_id_satuan" required>
                                <?php mysqli_data_seek($satuan_list, 0); while($s = mysqli_fetch_assoc($satuan_list)): ?>
                                    <option value="<?= $s['id_satuan'] ?>"><?= $s['nama_satuan'] ?></option>
                                <?php endwhile; ?>
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

    <div class="modal fade" id="confirmDeleteMenuModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus menu ini? <br>
                    <small class="text-muted">Seluruh data resep (BOM) terkait akan ikut terhapus secara otomatis.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" id="confirmDeleteMenuLink" class="btn btn-danger">Hapus Permanen</a>
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
            $('#basic-datatables').DataTable();

            if ($('#alert-notif').length > 0) {
                setTimeout(function() {
                    $('#alert-notif').fadeOut('slow', function() {
                        window.history.replaceState({}, document.title, "master_menu.php");
                    });
                }, 3000);
            }

            $(document).on('click', '.btn-update', function() {
                $('#update_id_menu').val($(this).data('id_menu'));
                $('#update_nama_menu').val($(this).data('nama_menu'));
                $('#update_id_kategori').val($(this).data('id_kategori'));
                $('#update_id_satuan').val($(this).data('id_satuan'));
                $('#updateMenuModal').modal('show');
            });

            $(document).on('click', '.btn-delete', function() {
                var id = $(this).data('id_menu');
                $('#confirmDeleteMenuLink').attr('href', 'delete_menu.php?id=' + id);
                $('#confirmDeleteMenuModal').modal('show');
            });

            $('#menu_bom').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#btnSubmitMenu').text('Simpan & Buat BOM').addClass('btn-warning');
                } else {
                    $('#btnSubmitMenu').text('Simpan').removeClass('btn-warning');
                }
            });
        });
    </script>
</body>
</html>