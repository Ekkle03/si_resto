<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// ==========================================
// 1. QUERY UNTUK KARTU REKAP (Executive Summary)
// ==========================================
$tgl_hari_ini = date('Y-m-d');

// A. Total Permintaan Bahan PENDING (Urusan Operasional)
$q_req = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM header_request WHERE status = 'Pending'");
$total_req_pending = mysqli_fetch_assoc($q_req)['total'] ?? 0;

// B. Total Produksi Hari Ini (Real-time dapur)
$q_prod = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM produksi WHERE DATE(tgl_produksi) = '$tgl_hari_ini'");
$total_prod_hari_ini = mysqli_fetch_assoc($q_prod)['total'] ?? 0;

// C. Total Item Waste Hari Ini (Efisiensi)
$q_waste = mysqli_query($koneksi, "SELECT SUM(d.qty_waste) as total_waste FROM detail_waste d JOIN header_waste h ON d.id_header_waste = h.id_header_waste WHERE h.tgl_waste = '$tgl_hari_ini'");
$total_waste = mysqli_fetch_assoc($q_waste)['total_waste'] ?? 0;

// D. Total Item Stok Kritis (Peringatan Stok)
$sql_kritis = "
    SELECT COUNT(*) as total FROM (
        SELECT sb.jumlah FROM stok_bahan sb JOIN master_bahan_baku bb ON sb.id_bb = bb.id_bb WHERE sb.jumlah <= bb.stok_minimal
        UNION ALL
        SELECT sb.jumlah FROM stok_bahan sb JOIN master_bahan_setengah_jadi bsj ON sb.id_bsj = bsj.id_bsj WHERE sb.jumlah <= bsj.stok_minimal_bsj
    ) as stok_kritis
";
$q_kritis = mysqli_query($koneksi, $sql_kritis);
$total_kritis = mysqli_fetch_assoc($q_kritis)['total'] ?? 0;

// ==========================================
// 2. QUERY UNTUK GRAFIK TREN PENJUALAN (H-7 s/d H-1)
// ==========================================
$label_hari = [];
$data_penjualan = [];
for ($i = 7; $i >= 1; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label_hari[] = date('d M', strtotime($tgl));
    
    // Menghitung jumlah struk penjualan per hari
    $q_grafik = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu_terjual WHERE tanggal_transaksi = '$tgl'");
    $data_penjualan[] = mysqli_fetch_assoc($q_grafik)['total'] ?? 0;
}

// ── Navbar Data ──────────────────────────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil']) ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard</title>
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
                            <img src="assets/img/logo/logo_resto.png" alt="Logo" class="navbar-brand" height="30" />
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
                    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
                        <div>
                            <h3 class="fw-bold mb-3">Dashboard Owner</h3>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-clipboard-list"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Permintaan Pending</p>
                                                <h4 class="card-title"><?= $total_req_pending ?> Request</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-blender"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Produksi Hari Ini</p>
                                                <h4 class="card-title"><?= $total_prod_hari_ini ?> Batch</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-danger bubble-shadow-small">
                                                <i class="fas fa-trash-alt"></i>
                                            </div>
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
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ms-3 ms-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Stok Kritis</p>
                                                <h4 class="card-title text-danger"><?= $total_kritis ?> Item</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card card-round">
                                <div class="card-header">
                                    <div class="card-title"><i class="fas fa-chart-line text-primary me-2"></i> Tren Penjualan (H-7 s/d Kemarin)</div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="min-height: 350px">
                                        <canvas id="salesChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card card-round shadow-sm">
                                <div class="card-header">
                                    <div class="card-title fs-5 fw-bold"><i class="fas fa-crown text-warning me-2"></i> Menu Terlaris</div>
                                </div>
                                <div class="card-body p-0">
                                    <ul class="list-group list-group-flush">
                                        <?php
                                        // QUERY SUPER CANGGIH SESUAI DATABASE: 
                                        // Menggabungkan tabel detail_menu_terjual untuk mengambil qty_terjual
                                        $sql_top_menu = "
                                            SELECT m.nama_menu, k.nama_kategori, SUM(d.qty_terjual) as total_qty 
                                            FROM detail_menu_terjual d 
                                            JOIN master_menu m ON d.id_menu = m.id_menu 
                                            JOIN master_kategori k ON m.id_kategori = k.id_kategori 
                                            GROUP BY m.id_menu 
                                            ORDER BY total_qty DESC 
                                            LIMIT 5
                                        ";
                                        $q_top_menu = mysqli_query($koneksi, $sql_top_menu);
                                        
                                        // Cek apakah query berhasil dijalankan dan ada datanya
                                        if($q_top_menu && mysqli_num_rows($q_top_menu) > 0) {
                                            while($menu = mysqli_fetch_assoc($q_top_menu)):
                                        ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                                <div>
                                                    <h6 class="mb-1 fw-bold"><?= htmlspecialchars($menu['nama_menu']) ?></h6>
                                                    <small class="text-muted"><i class="fas fa-tag me-1"></i> <?= htmlspecialchars($menu['nama_kategori']) ?></small>
                                                </div>
                                                <span class="badge badge-warning fw-bold text-dark"><?= (float)$menu['total_qty'] ?> Porsi</span>
                                            </li>
                                        <?php 
                                            endwhile; 
                                        } else { 
                                        ?>
                                            <li class='list-group-item text-center text-muted fst-italic py-4'>Belum ada data penjualan ter-import.</li>
                                        <?php } ?>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            var labels = <?php echo json_encode($label_hari); ?>;
            var dataSales = <?php echo json_encode($data_penjualan); ?>;
            var ctx = document.getElementById('salesChart').getContext('2d');
            var salesChart = new Chart(ctx, {
                type: 'line', 
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Struk Terjual',
                        data: dataSales,
                        borderColor: '#1d7af3',
                        backgroundColor: 'rgba(29, 122, 243, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#1d7af3',
                        pointRadius: 4,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    },
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
</body>
</html>