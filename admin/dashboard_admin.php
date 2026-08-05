<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Panggil Satpam
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Proteksi Lapis 2 Khusus Halaman Ini
$role = strtolower($_SESSION['nama_role'] ?? '');
if ($role !== 'admin') {
    echo "<script>alert('Akses Ditolak! Anda bukan Admin.'); window.location.href='dashboard.php';</script>";
    exit();
}

// ==========================================
// 1. QUERY UNTUK KARTU REKAP (Quick Stats)
// ==========================================
$tgl_hari_ini = date('Y-m-d');

// Total Menu
$q_menu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM master_menu");
$total_menu = mysqli_fetch_assoc($q_menu)['total'] ?? 0;

// Total Transaksi Hari Ini
$q_jual = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu_terjual WHERE tanggal_transaksi = '$tgl_hari_ini'");
$total_jual_hari_ini = mysqli_fetch_assoc($q_jual)['total'] ?? 0;

// Total Produksi Hari Ini
$q_prod = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produksi WHERE DATE(tgl_produksi) = '$tgl_hari_ini'");
$total_prod_hari_ini = mysqli_fetch_assoc($q_prod)['total'] ?? 0;

// Total Item Waste Hari Ini
$q_waste = mysqli_query($koneksi, "SELECT SUM(d.qty_waste) as total_waste FROM detail_waste d JOIN header_waste h ON d.id_header_waste = h.id_header_waste WHERE h.tgl_waste = '$tgl_hari_ini'");
$total_waste = mysqli_fetch_assoc($q_waste)['total_waste'] ?? 0;

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
    <title>Dashboard Admin - SI Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .table-custom thead th { background: #f4f6fa; color: #555; text-transform: uppercase; font-size: 10.5px; letter-spacing: .8px; font-weight: 600; border-bottom: 2px solid #e4e8f0; }
        .card-stats { border: none; border-radius: 12px; transition: transform 0.25s ease; }
        .card-stats:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <!-- ── NAVBAR ──────────────────────────────────────── -->
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Dashboard Administrator</h3>
                        </div>
                    </div>

                    <!-- KARTU REKAP -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round border-start border-primary border-4 shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-utensils"></i></div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Menu Master</p>
                                                <h4 class="card-title"><?= $total_menu ?> Item</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round border-start border-success border-4 shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-file-invoice-dollar"></i></div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Transaksi Hari Ini</p>
                                                <h4 class="card-title"><?= $total_jual_hari_ini ?> Struk</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round border-start border-secondary border-4 shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-secondary bubble-shadow-small"><i class="fas fa-blender"></i></div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Produksi Hari Ini</p>
                                                <h4 class="card-title"><?= $total_prod_hari_ini ?> Proses</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round border-start border-danger border-4 shadow-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-trash-alt"></i></div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Waste Hari Ini</p>
                                                <h4 class="card-title"><?= (float)$total_waste ?> Item</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- KOLOM KIRI: MONITOR STOK MENIPIS (FORMAT TABEL) -->
                        <div class="col-md-8">
                            <div class="card card-round shadow-sm border-top border-danger border-3">
                                <div class="card-header d-flex justify-content-between align-items-center py-3 bg-white border-0">
                                    <h4 class="card-title text-danger fw-bold mb-0"><i class="fas fa-exclamation-circle me-2"></i>Monitor Stok Menipis</h4>
                                    <span class="badge badge-danger shadow-sm px-3 py-1" style="border-radius: 20px;"><i class="fas fa-bell me-1"></i> Butuh Perhatian</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-custom mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <!-- PERBAIKAN: Header dipaksa style manual -->
                                                    <th style="min-width: 250px; text-align: left !important; padding-left: 20px !important;">Bahan / Item</th>
                                                    <th>Lokasi Gudang</th>
                                                    <th class="text-center">Sisa Stok</th>
                                                    <th class="text-center">Batas Minimal</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Query: Menggabungkan stok BB dan BSJ yang <= batas minimal KHUSUS di Gudang Utama (id_gudang = 1)
                                                $sql_radar = "
                                                    SELECT 'BB' as tipe, bb.nama_bb as nama_item, sb.jumlah as stok, bb.stok_minimal as batas, g.nama_gudang, sat.nama_satuan as satuan
                                                    FROM stok_bahan sb
                                                    JOIN master_bahan_baku bb ON sb.id_bb = bb.id_bb
                                                    JOIN master_gudang g ON sb.id_gudang = g.id_gudang
                                                    JOIN master_satuan sat ON bb.id_satuan = sat.id_satuan
                                                    WHERE sb.jumlah <= bb.stok_minimal AND sb.id_gudang = 1
                                                    
                                                    UNION ALL
                                                    
                                                    SELECT 'BSJ' as tipe, bsj.nama_bsj as nama_item, sb.jumlah as stok, bsj.stok_minimal_bsj as batas, g.nama_gudang, sat.nama_satuan as satuan
                                                    FROM stok_bahan sb
                                                    JOIN master_bahan_setengah_jadi bsj ON sb.id_bsj = bsj.id_bsj
                                                    JOIN master_gudang g ON sb.id_gudang = g.id_gudang
                                                    JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
                                                    WHERE sb.jumlah <= bsj.stok_minimal_bsj AND sb.id_gudang = 1
                                                    
                                                    ORDER BY stok ASC LIMIT 6
                                                ";
                                                $q_radar = mysqli_query($koneksi, $sql_radar);

                                                if (mysqli_num_rows($q_radar) > 0) {
                                                    while ($radar = mysqli_fetch_assoc($q_radar)):
                                                        $stok = (float)$radar['stok'];
                                                        $batas = (float)$radar['batas'];
                                                        $is_habis = ($stok <= 0);
                                                        
                                                        $badge_tipe = $radar['tipe'] == 'BB' ? 'badge-primary' : 'badge-info';
                                                        $status_bg = $is_habis ? 'badge-danger' : 'badge-warning text-dark';
                                                        $status_text = $is_habis ? 'Habis Total' : 'Kritis';
                                                        $icon_status = $is_habis ? 'fa-times-circle' : 'fa-exclamation-triangle';
                                                        $text_color = $is_habis ? 'text-danger' : 'text-warning';
                                                ?>
                                                <tr>
                                                    <!-- JURUS BETON: CSS Flex Murni tanpa class bawaan table -->
                                                    <td style="text-align: left !important; padding-left: 20px !important;">
                                                        <div style="display: flex; align-items: flex-start; justify-content: flex-start; text-align: left;">
                                                            <!-- Kotak khusus untuk Badge -->
                                                            <div style="width: 45px; min-width: 45px; margin-right: 12px; padding-top: 2px;">
                                                                <span class="badge <?= $badge_tipe ?> w-100 shadow-sm" style="font-size: 10px; padding: 5px 0; display: block; text-align: center;"><?= $radar['tipe'] ?></span>
                                                            </div>
                                                            <!-- Kotak khusus untuk Teks -->
                                                            <div class="fw-bold text-dark" style="text-align: left; line-height: 1.4; padding-top: 1px;">
                                                                <?= htmlspecialchars($radar['nama_item']) ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><i class="fas fa-map-marker-alt text-muted me-1"></i> <span class="fw-semibold text-secondary"><?= htmlspecialchars($radar['nama_gudang']) ?></span></td>
                                                    <td class="text-center fw-bold fs-5 <?= $text_color ?>"><?= $stok ?> <small class="fw-normal text-muted fs-6"><?= htmlspecialchars($radar['satuan']) ?></small></td>
                                                    <td class="text-center text-muted fw-bold"><?= $batas ?></td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $status_bg ?> px-3 py-2 shadow-sm" style="border-radius: 20px;">
                                                            <i class="fas <?= $icon_status ?> me-1"></i> <?= $status_text ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php 
                                                    endwhile;
                                                } else {
                                                ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <div class="icon-big text-success mb-2"><i class="fas fa-shield-alt fa-2x op-7"></i></div>
                                                        <h5 class="fw-bold text-dark mb-1">Stok Gudang Utama Aman!</h5>
                                                        <p class="text-muted small mb-0">Tidak ada bahan baku atau bahan setengah jadi yang menyentuh batas kritis di Gudang Utama.</p>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if(mysqli_num_rows($q_radar) > 0): ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- KANAN: MENU CEPAT & PENDING REQUEST -->
                        <div class="col-md-4">
                            <div class="card card-round shadow-sm bg-primary gradient-custom">
                                <div class="card-body">
                                    <h4 class="mb-3 text-white fw-bold"><i class="fas fa-bolt me-2"></i> Akses Cepat Admin</h4>
                                    <div class="d-grid gap-2">
                                        <a href="master_karyawan.php" class="btn btn-light btn-sm text-start fw-bold"><i class="fas fa-user-plus text-primary me-2"></i> Kelola Karyawan</a>
                                        <a href="master_menu.php" class="btn btn-light btn-sm text-start fw-bold"><i class="fas fa-utensils text-primary me-2"></i> Kelola Master Menu</a>
                                        <a href="laporan/laporan_bom.php" class="btn btn-light btn-sm text-start fw-bold"><i class="fas fa-chart-pie text-danger me-2"></i> Cek Laporan Resep</a>
                                        <a href="master_gudang.php" class="btn btn-light btn-sm text-start fw-bold"><i class="fas fa-boxes text-success me-2"></i> Master Gudang</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-round shadow-sm border-top border-info border-3">
                                <div class="card-header py-3">
                                    <div class="card-title text-info fw-bold"><i class="fas fa-clipboard-list me-2"></i> Permintaan Tertunda</div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php
                                        $q_req = mysqli_query($koneksi, "SELECT kode_request, tgl_request FROM header_request WHERE status = 'Pending' ORDER BY tgl_input DESC LIMIT 4");
                                        if(mysqli_num_rows($q_req) > 0) {
                                            while($req = mysqli_fetch_assoc($q_req)):
                                        ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-dark"><?= $req['kode_request'] ?></h6>
                                                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($req['tgl_request'])) ?></small>
                                                </div>
                                                <a href="permintaan_bahan.php" class="btn btn-icon btn-round btn-info btn-sm shadow-sm"><i class="fas fa-arrow-right"></i></a>
                                            </li>
                                        <?php 
                                            endwhile; 
                                        } else {
                                            echo "<li class='list-group-item text-center text-muted py-4'>Semua permintaan operasional sudah diproses.</li>";
                                        }
                                        ?>
                                    </ul>
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
    <script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>