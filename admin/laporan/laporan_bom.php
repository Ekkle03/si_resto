<?php
session_start();
include("../../config/auth.php");
include("../../config/koneksi_mysql.php");

// 1. Tangkap Parameter Filter
$tgl_mulai  = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-d');
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : date('Y-m-d');

// 2. Variabel Session Navbar
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? 'user');
$foto_user = !empty($_SESSION['foto_profil'])
            ? '../assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : '../assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Laporan Pemakaian Bahan</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="../assets/img/logo/logo_resto.png" type="image/x-icon" />

    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: ["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"],
                urls: ["../assets/css/fonts.min.css"],
            },
        });
    </script>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../assets/css/plugins.min.css" />
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css" />

    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #ebedf2 !important; vertical-align: middle; }
        .text-qty { font-weight: 800; color: #1572e8; font-size: 14px;}
        
        .inner-row { padding: 8px 15px; display: flex; align-items: center; min-height: 65px; }
        .inner-row-center { padding: 8px 15px; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 65px; }
        
        .td-produk { background-color: #f8f9fa; border-right: 2px solid #ebedf2 !important; }
        
        @media print {
            .sidebar, .main-header, .filter-card, .btn-print, .navbar, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate { display: none !important; }
            .main-panel { width: 100% !important; transition: none !important; margin-left: 0 !important; }
            .page-inner { padding: 0 !important; }
            .card { border: none !important; box-shadow: none !important;}
            .bg-light { background-color: #f1f1f1 !important; -webkit-print-color-adjust: exact; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #000 !important; padding: 5px !important; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="<?= $foto_user ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.src='../assets/img/profil/default.png'" />
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
                                                    <img src="<?= $foto_user ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.src='../assets/img/profil/default.png'" />
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
                                            <a class="dropdown-item" href="../../logout.php">Logout</a>
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
                        <h3 class="fw-bold mb-3 text-uppercase">Laporan Pemakaian Bahan</h3>
                    </div>

                    <div class="card card-round shadow-sm mb-4 border-0 filter-card">
                        <div class="card-body">
                            <form method="GET">
                                <div class="row align-items-end">
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label fw-bold">Dari Tanggal</label>
                                        <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
                                    </div>
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label fw-bold">Sampai Tanggal</label>
                                        <input type="date" name="tgl_selesai" class="form-control" value="<?= $tgl_selesai ?>">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button type="submit" class="btn btn-secondary w-100 fw-bold">CARI</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-round shadow-sm border-0">
                        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
                            <div>
                                <div class="card-title fw-bold text-primary mb-1">RINCIAN PEMAKAIAN BAHAN</div>
                                <div class="text-muted" style="font-size:12px;">Periode: <?= date('d M Y', strtotime($tgl_mulai)) ?> s/d <?= date('d M Y', strtotime($tgl_selesai)) ?></div>
                            </div>
                            <a href="cetak_bom.php?tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>" target="_blank" class="btn btn-outline-danger btn-sm px-3 fw-bold btn-print">
                                <i class="fa fa-print me-1"></i> CETAK LAPORAN
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-bom" class="table table-hover table-bordered" style="width: 100%;">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th width="5%">NO</th>
                                            <th width="25%">PRODUK (HASIL PRODUKSI)</th>
                                            <th width="35%">KOMPONEN BAHAN BAKU / BSJ</th>
                                            <th width="15%">KEBUTUHAN BOM</th>
                                            <th width="20%">TOTAL PEMAKAIAN</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql_prod = "
                                            SELECT 'BSJ' as tipe_bom, p.id_bsj as id_induk, bsj.nama_bsj as nama_produk, 
                                                   SUM(p.qty_rencana) as total_proses, 
                                                   SUM(p.qty_realisasi) as total_hasil, 
                                                   SUM(p.qty_rencana - p.qty_realisasi) as total_waste,
                                                   s.nama_satuan as sat_produk
                                            FROM produksi p
                                            JOIN master_bahan_setengah_jadi bsj ON p.id_bsj = bsj.id_bsj
                                            JOIN master_satuan s ON bsj.id_satuan = s.id_satuan
                                            WHERE DATE(p.tgl_produksi) BETWEEN '$tgl_mulai' AND '$tgl_selesai' AND p.status = 'Selesai'
                                            GROUP BY p.id_bsj
                                            
                                            UNION ALL
                                            
                                            SELECT 'MENU' as tipe_bom, d.id_menu as id_induk, m.nama_menu as nama_produk, 
                                                   SUM(d.qty_terjual) as total_proses, 
                                                   SUM(d.qty_terjual) as total_hasil, 
                                                   0 as total_waste,
                                                   s.nama_satuan as sat_produk
                                            FROM menu_terjual h
                                            JOIN detail_menu_terjual d ON h.id_jual = d.id_jual
                                            JOIN master_menu m ON d.id_menu = m.id_menu
                                            JOIN master_satuan s ON m.id_satuan = s.id_satuan
                                            WHERE h.tanggal_transaksi BETWEEN '$tgl_mulai' AND '$tgl_selesai'
                                            GROUP BY d.id_menu
                                        ";
                                        
                                        $q_produksi = mysqli_query($koneksi, $sql_prod);
                                        $no = 1;

                                        if($q_produksi && mysqli_num_rows($q_produksi) > 0) {
                                            while($prod = mysqli_fetch_assoc($q_produksi)) :
                                                $id_induk = $prod['id_induk'];
                                                $tipe_bom = $prod['tipe_bom'];
                                                $badge_tipe = ($tipe_bom == 'MENU') ? 'badge-success' : 'badge-warning text-dark';
                                                
                                                // Rincian UI Total Produksi vs Waste
                                                $ui_produksi = "<small class='text-muted d-block mt-1'>Rencana (Diproses): <b class='text-dark'>".(float)$prod['total_proses']." {$prod['sat_produk']}</b></small>";

                                                if($prod['total_waste'] > 0) {
                                                    $ui_produksi .= "<small class='text-success d-block'>Berhasil: <b class='text-dark'>".(float)$prod['total_hasil']." {$prod['sat_produk']}</b></small>";
                                                    $ui_produksi .= "<small class='text-danger d-block'>Waste: <b class='text-dark'>".(float)$prod['total_waste']." {$prod['sat_produk']}</b></small>";
                                                } else {
                                                    // Kalau berhasil 100% (Tanpa Waste)
                                                    $ui_produksi .= "<small class='text-success d-block'>Berhasil: <b class='text-dark'>".(float)$prod['total_hasil']." {$prod['sat_produk']}</b></small>";
                                                }

                                                $sql_bahan = "SELECT b.*, 
                                                              COALESCE(bb.nama_bb, bsj2.nama_bsj) as nama_bahan,
                                                              s_bom.nama_satuan as sat_bom,
                                                              k.nilai_konversi, k.satuan_kecil as id_satuan_kecil, s_besar.nama_satuan as sat_besar
                                                              FROM master_bom b
                                                              LEFT JOIN master_bahan_baku bb ON b.id_bb = bb.id_bb
                                                              LEFT JOIN master_bahan_setengah_jadi bsj2 ON b.id_bsj = bsj2.id_bsj
                                                              LEFT JOIN master_satuan s_bom ON b.id_satuan = s_bom.id_satuan
                                                              LEFT JOIN master_konversi k ON (b.id_bb = k.id_komponen AND k.tipe_bahan = 'BB') 
                                                                                          OR (b.id_bsj = k.id_komponen AND k.tipe_bahan = 'BSJ')
                                                              LEFT JOIN master_satuan s_besar ON k.satuan_besar = s_besar.id_satuan
                                                              WHERE b.id_induk = '$id_induk' AND b.tipe_bom = '$tipe_bom'";
                                                
                                                $q_bahan = mysqli_query($koneksi, $sql_bahan);
                                                
                                                $html_bahan = ""; $html_kebutuhan = ""; $html_total = "";
                                                $count_bahan = mysqli_num_rows($q_bahan);
                                                
                                                if($count_bahan > 0) {
                                                    $i = 0;
                                                    while($row = mysqli_fetch_assoc($q_bahan)) {
                                                        $i++;
                                                        $border_bottom = ($i < $count_bahan) ? "border-bottom: 1px solid #ebedf2;" : "";
                                                        
                                                        // Hitung dari total_proses (bukan hasil jadi)
                                                        $qty_resep = (float)$row['qty'];
                                                        $target_hasil = (float)$row['target_hasil'];
                                                        if($target_hasil <= 0) $target_hasil = 1;
                                                        
                                                        $total_pakai = ($qty_resep / $target_hasil) * $prod['total_proses'];
                                                        
                                                        $teks_konv_total = "";
                                                        if (!empty($row['nilai_konversi']) && $row['id_satuan'] == $row['id_satuan_kecil']) {
                                                            $konv_total = $total_pakai / $row['nilai_konversi'];
                                                            $teks_konv_total = "<span class='text-muted mt-1' style='font-size:11px;'>(≈ " . (float)round($konv_total, 2) . " " . $row['sat_besar'] . ")</span>";
                                                        }

                                                        $is_bb = !empty($row['id_bb']);
                                                        $jenis_badge = $is_bb ? "<span class='badge badge-secondary ms-2' style='font-size:9px;'>Bahan Baku</span>" : "<span class='badge badge-info ms-2' style='font-size:9px;'>BSJ</span>";
                                                        
                                                        $teks_target = ($target_hasil > 1) ? "<span class='text-muted mt-1' style='font-size:11px;'>per ".(float)$target_hasil." {$prod['sat_produk']}</span>" : "";
                                                        
                                                        $html_bahan .= "<div class='inner-row w-100' style='{$border_bottom}'>
                                                                            <i class='fas fa-caret-right text-muted me-2'></i> {$row['nama_bahan']} $jenis_badge
                                                                        </div>";
                                                                        
                                                        $html_kebutuhan .= "<div class='inner-row-center w-100' style='{$border_bottom}'>
                                                                                <span>{$qty_resep} {$row['sat_bom']}</span>
                                                                                $teks_target
                                                                            </div>";
                                                                            
                                                        $html_total .= "<div class='inner-row-center w-100' style='{$border_bottom}'>
                                                                            <span class='text-qty'>".(float)round($total_pakai, 2)." {$row['sat_bom']}</span>
                                                                            $teks_konv_total
                                                                        </div>";
                                                    }
                                                } else {
                                                    $html_bahan = "<div class='inner-row text-muted fst-italic'>BOM belum diatur</div>";
                                                    $html_kebutuhan = "<div class='inner-row-center text-muted'>-</div>";
                                                    $html_total = "<div class='inner-row-center text-muted'>-</div>";
                                                }
                                        ?>
                                        <tr>
                                            <td class="text-center align-middle"><?= $no++ ?></td>
                                            <td class="align-middle td-produk">
                                                <div class="fw-bold text-dark mb-1"><?= strtoupper($prod['nama_produk']) ?> <span class="badge <?= $badge_tipe ?> ms-1" style="font-size:9px;"><?= $tipe_bom ?></span></div>
                                                <?= $ui_produksi ?>
                                            </td>
                                            <td class="p-0 align-top bg-white"><?= $html_bahan ?></td>
                                            <td class="p-0 align-top bg-white"><?= $html_kebutuhan ?></td>
                                            <td class="p-0 align-top bg-white"><?= $html_total ?></td>
                                        </tr>
                                        <?php 
                                            endwhile; 
                                        } 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../assets/js/kaiadmin.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // DataTables menggunakan bahasa Inggris default bawaannya (Sesuai dengan template yang lain)
            $('#table-bom').DataTable({ 
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]]
            });
        });
    </script>
</body>
</html>