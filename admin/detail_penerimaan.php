<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// 1. Tangkap ID Penerimaan dari URL
$id_penerimaan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_penerimaan <= 0) {
    header("Location: penerimaan.php");
    exit();
}

// 2. Ambil data Header Penerimaan & Nama Kode Pembelian asalnya
$sql_header = "SELECT p.*, pb.kode_pembelian 
               FROM penerimaan p 
               LEFT JOIN pembelian pb ON p.id_pembelian = pb.id_pembelian 
               WHERE p.id_penerimaan = '$id_penerimaan'";
$query_header = mysqli_query($koneksi, $sql_header);
$header = mysqli_fetch_assoc($query_header);

if (!$header) {
    echo "Data tidak ditemukan!";
    exit();
}

// Query Pintar: Mengambil satuan asli dan mengecek apakah ada satuan konversi (satuan besar)
$sql_detail = "SELECT 
                    d.*, 
                    b.nama_bb, 
                    b.kode_bb, 
                    s_kecil.nama_satuan AS satuan_asli,
                    mk.nilai_konversi,
                    s_besar.nama_satuan AS satuan_konversi
               FROM detail_penerimaan d
               JOIN master_bahan_baku b ON d.id_bb = b.id_bb
               JOIN master_satuan s_kecil ON b.id_satuan = s_kecil.id_satuan
               LEFT JOIN master_konversi mk ON b.id_bb = mk.id_komponen AND mk.tipe_bahan = 'BB'
               LEFT JOIN master_satuan s_besar ON mk.satuan_besar = s_besar.id_satuan
               WHERE d.id_penerimaan = '$id_penerimaan'
               ORDER BY b.nama_bb ASC";
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
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Penerimaan Bahan</title>
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

    <style>
        .info-label { font-size: 11px; text-transform: uppercase; color: #8d9498; font-weight: 700; margin-bottom: 3px; }
        .info-value { font-size: 14px; font-weight: 600; color: #2a2f5b; }
        .badge-ref { font-size: 12px; padding: 5px 10px; }
        .table-detail th { background: #f8f9fa; font-size: 11px; font-weight: 700; color: #8d9498; text-transform: uppercase; }
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
            <!-- ── NAVBAR ──────────────────────────────────────── -->
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
        </div>
        
        <div class="container">
            <div class="page-inner">
                <div class="page-header mb-0 mt-3 pb-3 border-bottom">
                    <h3 class="fw-bold mb-0 text-dark">Detail Penerimaan Bahan</h3>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        
                        <!-- CARD 1: KHUSUS INFO HEADER (Dipisah dari Tabel) -->
                        <div class="card card-round shadow-sm border-0 mb-4">
                            <div class="card-header bg-white d-flex align-items-center py-3">
                                <div class="card-title mb-0">
                                    <span class="text-muted" style="font-weight: 400; font-size: 14px;">KODE RCV:</span> 
                                    <span class="text-dark fw-bold" style="font-size: 16px;"><?= $header['kode_penerimaan'] ?></span>
                                </div>
                                <a href="penerimaan.php" class="btn btn-warning btn-round fw-bold btn-sm ms-auto shadow-sm">
                                    <i class="fa fa-arrow-left me-1"></i> Kembali
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row mx-0">
                                    <div class="col-md-3 border-end">
                                        <div class="info-label">Tanggal Terima</div>
                                        <div class="info-value"><?= date('d/m/Y', strtotime($header['tgl_terima'])) ?></div>
                                    </div>
                                    <div class="col-md-3 border-end ps-4">
                                        <div class="info-label">Referensi PB</div>
                                        <div class="info-value">
                                            <span class="badge badge-info badge-ref shadow-sm"><?= $header['kode_pembelian'] ?? '-' ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 ps-4">
                                        <div class="info-label">Keterangan</div>
                                        <div class="info-value"><?= !empty($header['keterangan']) ? $header['keterangan'] : '-' ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CARD 2: KHUSUS TABEL -->
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="table-penerimaan-detail" class="display table table-hover table-bordered table-detail mb-0" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 5%;">NO</th>
                                                <th class="text-center" style="width: 15%;">KODE BAHAN</th>
                                                <th class="text-start" style="width: 30%;">NAMA BAHAN BAKU</th>
                                                <th class="text-center text-success" style="width: 20%;">QTY DITERIMA</th>
                                                <th class="text-center text-danger" style="width: 20%;">QTY WASTE</th>
                                                <th class="text-center" style="width: 10%;">SATUAN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1;
                                            while ($d = mysqli_fetch_assoc($query_detail)): 
                                                if (!empty($d['nilai_konversi']) && $d['nilai_konversi'] > 0) {
                                                    $qty_final = $d['qty_terima'] / $d['nilai_konversi'];
                                                    $satuan_final = $d['satuan_konversi'];
                                                    $faktor_bagi = $d['nilai_konversi'];
                                                } else {
                                                    $qty_final = $d['qty_terima'];
                                                    $satuan_final = $d['satuan_asli'];
                                                    $faktor_bagi = 1;
                                                }

                                                // CEK SILANG KE TABEL WASTE
                                                $id_bb_cek = $d['id_bb'];
                                                $q_waste = mysqli_query($koneksi, "SELECT SUM(qty_waste) as total_rusak 
                                                                                   FROM detail_waste 
                                                                                   WHERE sumber = 'Penerimaan' 
                                                                                   AND id_referensi_sumber = '$id_penerimaan' 
                                                                                   AND id_bb = '$id_bb_cek'");
                                                $d_waste = mysqli_fetch_assoc($q_waste);
                                                $qty_rusak_mentah = $d_waste['total_rusak'] ?? 0;
                                                
                                                // Konversi juga qty rusak agar satuannya sama
                                                $qty_rusak_final = $qty_rusak_mentah / $faktor_bagi;
                                            ?>
                                            <tr>
                                                <td class="text-center text-muted align-middle"><?= $no++ ?></td>
                                                <td class="text-center fw-bold text-dark align-middle"><?= $d['kode_bb'] ?></td>
                                                <td class="align-middle"><?= $d['nama_bb'] ?></td>
                                                <td class="text-center fw-bold text-success align-middle" style="font-size: 15px;">
                                                    <?= (float)$qty_final ?>
                                                </td>
                                                <td class="text-center fw-bold text-danger align-middle">
                                                    <?= $qty_rusak_final > 0 ? (float)$qty_rusak_final : '-' ?>
                                                </td>
                                                <td class="text-center text-muted align-middle">
                                                    <?= $satuan_final ?>
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

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <!-- Tambahan Script DataTables -->
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>

    <!-- Script Aktivasi DataTables -->
    <script>
        $(document).ready(function() {
            $('#table-penerimaan-detail').DataTable({
                "pageLength": 10,
                "bLengthChange": true,
                "bFilter": true,
                "bInfo": true,
                "order": [] // Disable urut otomatis
            });
        });
    </script>
</body>
</html>