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

$kode_trx = $header['kode_transaksi'];

// 3. Ambil data Detail (Khusus MENU-MENU yang laku)
$sql_detail = "SELECT d.qty_terjual, m.nama_menu, m.kode_menu 
               FROM detail_menu_terjual d
               JOIN master_menu m ON d.id_menu = m.id_menu
               WHERE d.id_jual = '$id_jual'";
$query_detail = mysqli_query($koneksi, $sql_detail);

// 4. JURUS RAHASIA: Ambil data PACKAGING / EKSTRA (Kresek dkk) dari jejak Log Stok
$sql_bb_ekstra = "SELECT l.qty_keluar as qty_terjual, b.nama_bb as nama_menu, b.kode_bb as kode_menu 
                  FROM log_stok l 
                  JOIN master_bahan_baku b ON l.id_bb = b.id_bb 
                  WHERE l.jenis_mutasi = 'Penjualan' 
                  AND l.keterangan LIKE 'Pemakaian Ekstra%' 
                  AND l.keterangan LIKE '%$kode_trx%'";
$query_bb_ekstra = mysqli_query($koneksi, $sql_bb_ekstra);

// ── Navbar: siapkan variabel session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Penjualan - <?= $kode_trx ?></title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

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
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
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
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm">
                                    <img src="<?= $foto ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.src='assets/img/profil/default.png'" />
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
                                                <img src="<?= $foto ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.src='assets/img/profil/default.png'" />
                                            </div>
                                            <div class="u-text">
                                                <h4><?= $nama ?></h4>
                                                <p class="text-muted">@<?= $username ?></p>
                                                <?php if (!empty($role)): ?><span class="badge bg-secondary mb-2"><?= $role ?></span><?php endif; ?>
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
            </div>

           <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Detail Penjualan</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-round shadow-sm">
                                <div class="card-header d-flex align-items-center">
                                    <div class="card-title">
                                        <span class="text-muted" style="font-weight: normal;">KODE:</span> 
                                        <span class="text-dark fw-bold"><?= $kode_trx ?></span>
                                    </div>
                                    <a href="menu_terjual.php" class="btn btn-warning btn-round btn-sm ms-auto shadow-sm">
                                        <i class="fa fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4 bg-light rounded p-3 mx-0">
                                        <div class="col-md-4">
                                            <div class="info-label fw-bold text-muted small">TANGGAL TRANSAKSI</div>
                                            <div class="info-value fw-bold text-dark"><?= date('d/m/Y', strtotime($header['tanggal_transaksi'])) ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-label fw-bold text-muted small">WAKTU IMPORT</div>
                                            <div class="info-value fw-bold text-dark"><?= date('d/m/Y H:i', strtotime($header['tanggal_upload'])) ?></div>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <div class="info-label fw-bold text-muted small">TOTAL ITEM DARI CSV</div>
                                            <div class="info-value fw-bold text-primary fs-5"><?= $header['total_item'] ?> Item</div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover table-bordered">
                                            <thead>
                                                <tr class="bg-primary text-white">
                                                    <th class="text-center" style="width: 50px;">NO</th>
                                                    <th style="width: 150px;">KODE ITEM</th>
                                                    <th>NAMA ITEM (MENU & BAHAN EKSTRA)</th>
                                                    <th class="text-center" style="width: 150px;">QTY TERJUAL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $no = 1;
                                                $total_porsi = 0; 
                                                
                                                // 1. Tampilkan Menu Utama
                                                while ($d = mysqli_fetch_assoc($query_detail)): 
                                                    $total_porsi += $d['qty_terjual'];
                                                ?>
                                                <tr>
                                                    <td class="text-center text-muted"><?= $no++ ?></td>
                                                    <td class="text-center"><span class="badge badge-primary"><?= $d['kode_menu'] ?></span></td>
                                                    <td class="fw-bold text-dark"><?= $d['nama_menu'] ?></td>
                                                    <td class="text-center fw-bold text-success fs-6">
                                                        <?= number_format($d['qty_terjual']) ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>

                                                // 2. Tampilkan Bahan Ekstra (Kresek/Kotak)
                                                <?php 
                                                while ($bb = mysqli_fetch_assoc($query_bb_ekstra)): 
                                                    $total_porsi += $bb['qty_terjual'];
                                                ?>
                                                <tr class="bg-light">
                                                    <td class="text-center text-muted"><?= $no++ ?></td>
                                                    <td class="text-center"><span class="badge badge-info"><?= $bb['kode_menu'] ?></span></td>
                                                    <td class="text-muted"><i class="fa fa-box me-1"></i> <?= $bb['nama_menu'] ?> </td>
                                                    <td class="text-center fw-bold text-success fs-6">
                                                        <?= number_format($bb['qty_terjual']) ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                            <tfoot class="bg-light">
                                                <tr>
                                                    <td colspan="3" class="text-end fw-bold text-dark">TOTAL ITEM KESELURUHAN:</td>
                                                    <td class="text-center fw-extrabold text-primary fs-5"><?= $total_porsi ?></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer py-2">
                                    <small class="text-muted" style="font-style: italic; font-size: 11px;">
                                        * Data ditarik otomatis dari laporan POS harian. Bahan ekstra/packaging berhasil dimunculkan.
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
</body>
</html>