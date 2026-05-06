<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../config/auth.php");
include("../config/koneksi_mysql.php");

$tgl_hari_ini = date('Y-m-d');

// ============================================================
// 1. KARTU STRATEGIS STAF & OPERASIONAL
// ============================================================

// A. Stok Kosong Cabang (Gudang Oprasional & Produksi -> ID 2 & 5)
$q_kosong_cabang = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM stok_bahan WHERE jumlah <= 0 AND id_gudang IN (2, 5)");
$tot_kosong = mysqli_fetch_assoc($q_kosong_cabang)['total'] ?? 0;

// B. Tugas Produksi Aktif (Rencana & Proses)
$q_tugas = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produksi WHERE status IN ('Rencana', 'Proses')");
$tot_tugas = mysqli_fetch_assoc($q_tugas)['total'] ?? 0;

// C. Menu Terjual Hari Ini
$q_terjual = mysqli_query($koneksi, "
    SELECT SUM(d.qty_terjual) as total 
    FROM detail_menu_terjual d 
    JOIN menu_terjual h ON d.id_jual = h.id_jual 
    WHERE h.tanggal_transaksi = '$tgl_hari_ini'
");
$tot_terjual = mysqli_fetch_assoc($q_terjual)['total'] ?? 0;

// D. Total Waste Hari Ini
$q_waste = mysqli_query($koneksi, "
    SELECT SUM(d.qty_waste) as total 
    FROM detail_waste d 
    JOIN header_waste h ON d.id_header_waste = h.id_header_waste 
    WHERE h.tgl_waste = '$tgl_hari_ini'
");
$tot_waste = mysqli_fetch_assoc($q_waste)['total'] ?? 0;


// ============================================================
// 2. FUNGSI UNTUK AMBIL DATA STOK KOSONG PER GUDANG
// ============================================================
function getStokKosong($koneksi, $id_gudang) {
    $sql = "(SELECT 'BB' as tipe, bb.nama_bb as nama_item, sat.nama_satuan as satuan
             FROM stok_bahan sb
             JOIN master_bahan_baku bb ON sb.id_bb = bb.id_bb
             JOIN master_satuan sat ON bb.id_satuan = sat.id_satuan
             WHERE sb.id_gudang = $id_gudang AND sb.jumlah <= 0)
            UNION ALL
            (SELECT 'BSJ' as tipe, bsj.nama_bsj as nama_item, sat.nama_satuan as satuan
             FROM stok_bahan sb
             JOIN master_bahan_setengah_jadi bsj ON sb.id_bsj = bsj.id_bsj
             JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
             WHERE sb.id_gudang = $id_gudang AND sb.jumlah <= 0)";
    return mysqli_query($koneksi, $sql);
}

// Eksekusi fungsi untuk Gudang 2 (Oprasional) dan 5 (Produksi)
$q_kosong_opr = getStokKosong($koneksi, 2);
$q_kosong_prd = getStokKosong($koneksi, 5);

// ── Navbar Session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil']) ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) : 'assets/img/profil/default.png';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard Staf Produksi - SI Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: { families: [ "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons" ], urls: ["assets/css/fonts.min.css"] },
        });
    </script>

    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .card-stats { border: none; border-radius: 12px; transition: transform 0.25s ease; }
        .card-stats:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.12) !important; }
        .table-custom thead th { background: #f4f6fa; color: #555; text-transform: uppercase; font-size: 10.5px; letter-spacing: .8px; font-weight: 600; border-bottom: 2px solid #e4e8f0; }
        .card-accent-danger { border-left: 4px solid #f5365c !important; }
        .card-accent-warning { border-left: 4px solid #ff9e27 !important; }
        .card-accent-success { border-left: 4px solid #2dce89 !important; }
        .card-accent-dark { border-left: 4px solid #1a2035 !important; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="dashboard.php" class="logo"><img src="assets/img/logo/logo_resto.png" alt="Logo" class="navbar-brand" height="30" /></a>
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
                    <div class="page-header mb-4">
                        <h4 class="page-title">Dashboard Staff & Oprasional</h4>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round shadow-sm card-accent-danger">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon"><div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-boxes"></i></div></div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Bahan Kosong (0)</p>
                                                <h4 class="card-title text-danger"><?= $tot_kosong ?> Item</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round shadow-sm card-accent-warning">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon"><div class="icon-big text-center icon-warning bubble-shadow-small"><i class="fas fa-fire-alt"></i></div></div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Tugas Produksi</p>
                                                <h4 class="card-title text-warning"><?= $tot_tugas ?> Antrean</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round shadow-sm card-accent-success">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon"><div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-utensils"></i></div></div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Menu Terjual (Hari Ini)</p>
                                                <h4 class="card-title text-success"><?= (float)$tot_terjual ?> Porsi</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round shadow-sm card-accent-dark bg-dark text-white">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon"><div class="icon-big text-center text-white"><i class="fas fa-trash-alt"></i></div></div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category text-white">Total Waste (Hari Ini)</p>
                                                <h4 class="card-title text-white"><?= (float)$tot_waste ?> Item</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <div class="card card-round shadow-sm border-top border-warning border-3">
                                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
                                    <h4 class="card-title text-warning text-dark fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Daftar Tugas Produksi (Pending & Proses)</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-custom mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3">Waktu Request</th>
                                                    <th>Nama Produk (BSJ)</th>
                                                    <th class="text-center">Target Qty</th>
                                                    <th class="text-center">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql_tugas = "
                                                    SELECT p.*, bsj.nama_bsj, sat.nama_satuan 
                                                    FROM produksi p 
                                                    JOIN master_bahan_setengah_jadi bsj ON p.id_bsj = bsj.id_bsj 
                                                    JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
                                                    WHERE p.status IN ('Rencana', 'Proses') 
                                                    ORDER BY p.status DESC, p.tgl_produksi ASC
                                                ";
                                                $res_tugas = mysqli_query($koneksi, $sql_tugas);
                                                if(mysqli_num_rows($res_tugas) > 0):
                                                    while($t = mysqli_fetch_assoc($res_tugas)):
                                                        $is_rencana = ($t['status'] == 'Rencana');
                                                        $badge_class = $is_rencana ? 'badge-danger' : 'badge-warning text-dark';
                                                ?>
                                                <tr>
                                                    <td class="ps-3 text-muted small">
                                                        <?= date('d M Y', strtotime($t['tgl_produksi'])) ?><br>
                                                        <i class="fas fa-clock me-1"></i><?= date('H:i', strtotime($t['tgl_produksi'])) ?>
                                                    </td>
                                                    <td class="fw-bold text-dark fs-6"><?= htmlspecialchars($t['nama_bsj']) ?></td>
                                                    <td class="text-center fw-bold text-primary fs-5">
                                                        <?= (float)$t['qty_rencana'] ?> <small class="text-muted fw-normal fs-6"><?= htmlspecialchars($t['nama_satuan']) ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $badge_class ?> shadow-sm px-3 py-2" style="border-radius: 20px;">
                                                            <?= $is_rencana ? '<i class="fas fa-exclamation-circle me-1"></i> Tugas Baru' : '<i class="fas fa-fire-alt me-1"></i> Sedang Diproses' ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted">
                                                        <i class="fas fa-check-circle fa-2x text-success mb-2 d-block op-5"></i>
                                                        Tidak ada antrean tugas produksi saat ini.
                                                    </td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="card card-round shadow-sm border-top border-danger border-3">
                                <div class="card-header bg-white py-3 border-0">
                                    <h4 class="card-title text-danger fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stok Kosong (Gudang Oprasional)</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-custom mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3">Nama Bahan</th>
                                                    <th class="text-center">Satuan</th>
                                                    <th class="text-center">Sisa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(mysqli_num_rows($q_kosong_opr) > 0): while($opr = mysqli_fetch_assoc($q_kosong_opr)): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($opr['nama_item']) ?></td>
                                                    <td class="text-center text-muted small"><?= htmlspecialchars($opr['satuan']) ?></td>
                                                    <td class="text-center text-danger fw-bold">0</td>
                                                </tr>
                                                <?php endwhile; else: ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">Stok gudang oprasional terpantau aman.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card card-round shadow-sm border-top border-danger border-3">
                                <div class="card-header bg-white py-3 border-0">
                                    <h4 class="card-title text-danger fw-bold mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stok Kosong (Gudang Produksi)</h4>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-custom mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="ps-3">Nama Bahan / BSJ</th>
                                                    <th class="text-center">Satuan</th>
                                                    <th class="text-center">Sisa</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(mysqli_num_rows($q_kosong_prd) > 0): while($prd = mysqli_fetch_assoc($q_kosong_prd)): ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($prd['nama_item']) ?></td>
                                                    <td class="text-center text-muted small"><?= htmlspecialchars($prd['satuan']) ?></td>
                                                    <td class="text-center text-danger fw-bold">0</td>
                                                </tr>
                                                <?php endwhile; else: ?>
                                                <tr><td colspan="3" class="text-center py-4 text-muted">Stok gudang produksi terpantau aman.</td></tr>
                                                <?php endif; ?>
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
</div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script></body>
</html>