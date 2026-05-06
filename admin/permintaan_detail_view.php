<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (!isset($_GET['id'])) {
    header("Location: permintaan_bahan.php");
    exit;
}

$id_h = (int)$_GET['id'];
// Update query untuk mengambil status (pastikan kolom status sudah ada di DB)
$header = mysqli_query($koneksi, "SELECT * FROM header_request WHERE id_header_req = '$id_h'");
$h = mysqli_fetch_assoc($header);

if (!$h) { die("Data tidak ditemukan."); }

// Persiapan Variabel Navbar & Role
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role_nama = htmlspecialchars($_SESSION['nama_role']    ?? '');
$id_role  = $_SESSION['id_role'] ?? 0; // Ambil ID Role dari session
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';

// Penentuan hak akses Approve (Admin: 1, Owner: 2, Purchasing: 3)
$boleh_approve = in_array($id_role, [1, 2, 3]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>BOM - Sistem Resto</title>
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
                <div class="page-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
                    <div>
                        <h3 class="fw-bold mb-1">Detail Permintaan: <span class="text-primary"><?= $h['kode_request'] ?></span></h3>
                        <p class="text-muted small">Kelola persetujuan pengeluaran bahan baku ke operasional.</p>
                    </div>
                    
                    <div class="d-flex align-items-center mt-2 mt-md-0">
                        <?php if ($h['status'] == 'Selesai'): ?>
                            <span class="badge badge-success px-4 py-2 me-2 shadow-sm"><i class="fa fa-check-circle me-1"></i> TERKONFIRMASI</span>
                        <?php elseif ($boleh_approve && $h['status'] == 'Pending'): ?>
                            <button type="button" class="btn btn-success btn-round fw-bold shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalApprove">
                                <i class="fa fa-check-circle me-1"></i> Approve & Keluarkan Barang
                            </button>
                        <?php endif; ?>

                        <a href="permintaan_bahan.php" class="btn btn-outline-primary btn-round btn-sm">
                            <i class="fa fa-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0 mb-4">
                            <div class="card-body py-4">
                                <div class="row">
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <p class="mb-0 text-muted small">Tanggal Request</p>
                                        <span class="fw-bold h5 text-dark"><?= date('d F Y', strtotime($h['tgl_request'])) ?></span>
                                    </div>
                                    <div class="col-md-8">
                                        <p class="mb-0 text-muted small">Keterangan / Tujuan</p>
                                        <span class="fw-bold h5 text-dark"><?= htmlspecialchars($h['keterangan'] ?: '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <div class="card-title fw-bold text-primary"><i class="fa fa-list-ul me-2"></i> Item Bahan / Olahan</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 text-center">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="text-start ps-4 py-3">NAMA BAHAN</th>
                                                <th class="py-3">QTY REQUEST</th>
                                                <th class="py-3">SATUAN</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-dark">
                                            <?php 
                                            $sql_det = "SELECT r.*, bb.nama_bb, bsj.nama_bsj, s.nama_satuan as sat_bb, s2.nama_satuan as sat_bsj 
                                                       FROM request_bahan r 
                                                       LEFT JOIN master_bahan_baku bb ON r.id_bb = bb.id_bb 
                                                       LEFT JOIN master_satuan s ON bb.id_satuan = s.id_satuan
                                                       LEFT JOIN master_bahan_setengah_jadi bsj ON r.id_bsj = bsj.id_bsj
                                                       LEFT JOIN master_satuan s2 ON bsj.id_satuan = s2.id_satuan
                                                       WHERE r.id_header_req = '$id_h'";
                                            $query_det = mysqli_query($koneksi, $sql_det);
                                            
                                            while($d = mysqli_fetch_assoc($query_det)):
                                                $nama_item = $d['nama_bb'] ?? $d['nama_bsj'];
                                                $satuan_item = $d['sat_bb'] ?? $d['sat_bsj'];
                                            ?>
                                            <tr>
                                                <td class="text-start ps-4 fw-bold"><?= htmlspecialchars($nama_item) ?></td>
                                                <td><span class="badge bg-primary px-3"><?= (float)$d['qty_request'] ?></span></td>
                                                <td><?= $satuan_item ?></td>
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

<div class="modal fade" id="modalApprove" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-success">Konfirmasi Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah bahan baku sudah diserahkan secara fisik ke bagian operasional?</p>
                <div class="alert alert-warning border-0 small shadow-sm">
                    <i class="fa fa-exclamation-triangle me-2"></i> 
                    Stok di <strong>Gudang Utama</strong> akan otomatis berkurang!
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-link text-dark" data-bs-dismiss="modal">Batal</button>
                <a href="proses_approve_request.php?id=<?= $id_h ?>" class="btn btn-success btn-round px-4 shadow-sm">Ya, Sudah Diserahkan</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>  ← ini
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
</body>
</html>