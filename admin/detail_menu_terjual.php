<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Tangkap ID Header dari URL
$id_jual = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_jual <= 0) {
    header("Location: menu_terjual.php");
    exit();
}

// 2. Ambil data Header (untuk judul & info tanggal)
$sql_header = "SELECT * FROM menu_terjual WHERE id_jual = '$id_jual'";
$query_header = mysqli_query($koneksi, $sql_header);
$header = mysqli_fetch_assoc($query_header);

if (!$header) {
    echo "Data tidak ditemukan!";
    exit();
}

// 3. Ambil data Detail (Menu-menu yang laku)
$sql_detail = "SELECT d.*, m.nama_menu, m.kode_menu 
               FROM detail_menu_terjual d
               JOIN master_menu m ON d.id_menu = m.id_menu
               WHERE d.id_jual = '$id_jual'";
$query_detail = mysqli_query($koneksi, $sql_detail);

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
    <title>Menu Terjual</title>
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
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>


        <div class="main-panel">
            <div class="main-header">
                <!-- Logo Header -->
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="dashboard.php" class="logo">
                            <img src="assets/img/logo/LOGO PT.jpg" alt="Logo PT" class="navbar-brand" height="30" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                            <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                        </div>
                        <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                    </div>
                </div>
                <!-- End Logo Header -->
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
                        <h3 class="fw-bold mb-3">Detail Penjualan</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round">
                                <div class="card-header d-flex align-items-center">
                                    <div class="card-title">
                                        <span class="text-muted" style="font-weight: normal;">KODE:</span> 
                                        <span class="text-dark fw-bold"><?= $header['kode_transaksi'] ?></span>
                                    </div>
                                    <a href="menu_terjual.php" class="btn btn-warning btn-round btn-sm ms-auto">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4 bg-light rounded p-3 mx-0">
                                        <div class="col-md-4">
                                            <div class="info-label">Tanggal Transaksi</div>
                                            <div class="info-value"><?= date('d/m/Y', strtotime($header['tanggal_transaksi'])) ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label">Waktu Import</div>
                                            <div class="info-value"><?= date('d/m/Y H:i', strtotime($header['tanggal_upload'])) ?></div>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <div class="info-label">Total Item</div>
                                            <div class="info-value text-primary"><?= $header['total_item'] ?> Menu</div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-bordered">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;">NO</th>
                                                    <th style="width: 150px;">KODE MENU</th>
                                                    <th>NAMA MENU</th>
                                                    <th style="width: 150px;">QTY TERJUAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                while ($d = mysqli_fetch_assoc($query_detail)): 
                                                ?>
                                                <tr>
                                                    <td class="text-center text-muted"><?= $no++ ?></td>
                                                    <td class="text-center fw-bold text-dark"><?= $d['kode_menu'] ?></td>
                                                    <td><?= $d['nama_menu'] ?></td>
                                                    <td class="text-center fw-bold text-success">
                                                        <?= number_format($d['qty_terjual']) ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer py-2">
                                    <small class="text-muted" style="font-style: italic; font-size: 11px;">
                                        * Data ini ditarik otomatis dari laporan POS harian.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>