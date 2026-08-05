<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Query ambil data Request Bahan Baku (BB)
$sql_bb = "SELECT r.*, b.nama_bb, b.kode_bb, s.nama_satuan 
           FROM request_bahan r
           INNER JOIN master_bahan_baku b ON r.id_bb = b.id_bb
           JOIN master_satuan s ON b.id_satuan = s.id_satuan
           WHERE r.id_bb IS NOT NULL
           ORDER BY r.tgl_request DESC";
$query_bb = mysqli_query($koneksi, $sql_bb);

// 2. Query ambil data Request Bahan Setengah Jadi (BSJ)
$sql_bsj = "SELECT r.*, bj.nama_bsj, bj.kode_bsj, s.nama_satuan 
            FROM request_bahan r
            INNER JOIN master_bahan_setengah_jadi bj ON r.id_bsj = bj.id_bsj
            JOIN master_satuan s ON bj.id_satuan = s.id_satuan
            WHERE r.id_bsj IS NOT NULL
            ORDER BY r.tgl_request DESC";
$query_bsj = mysqli_query($koneksi, $sql_bsj);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Sistem Resto</title>
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
                        <h3 class="fw-bold mb-3">Daftar Kebutuhan (Request)</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <div class="card card-round shadow-sm">
                                <div class="card-header border-bottom-0">
                                    <h4 class="card-title" style="font-size: 15px !important;">Bahan Mentah (Harus Dibeli)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-bordered datatable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;">NO</th>
                                                    <th>KODE</th>
                                                    <th>NAMA BAHAN</th>
                                                    <th>QTY REQ</th>
                                                    <th>SATUAN</th>
                                                    <th>TANGGAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no=1; while($r = mysqli_fetch_assoc($query_bb)): ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td class="text-center fw-bold text-dark"><?= $r['kode_bb'] ?></td>
                                                    <td><?= $r['nama_bb'] ?></td>
                                                    <td class="text-center qty-urgent"><?= (float)$r['qty_request'] ?></td>
                                                    <td class="text-center"><?= $r['nama_satuan'] ?></td>
                                                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['tgl_request'])) ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card card-round shadow-sm">
                                <div class="card-header border-bottom-0">
                                    <h4 class="card-title" style="font-size: 15px !important;">Bahan Olahan (Harus Dimasak)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-bordered datatable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 50px;">NO</th>
                                                    <th>KODE</th>
                                                    <th>NAMA OLAHAN</th>
                                                    <th>QTY REQ</th>
                                                    <th>SATUAN</th>
                                                    <th>TANGGAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no=1; while($r = mysqli_fetch_assoc($query_bsj)): ?>
                                                <tr>
                                                    <td class="text-center"><?= $no++ ?></td>
                                                    <td class="text-center fw-bold text-dark"><?= $r['kode_bsj'] ?></td>
                                                    <td><?= $r['nama_bsj'] ?></td>
                                                    <td class="text-center qty-urgent"><?= (float)$r['qty_request'] ?></td>
                                                    <td class="text-center"><?= $r['nama_satuan'] ?></td>
                                                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['tgl_request'])) ?></td>
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
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({ "pageLength": 5 });
        });
    </script>
</body>
</html>