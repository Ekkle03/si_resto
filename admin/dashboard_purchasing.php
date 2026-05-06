<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// ============================================================
// 0. HANDLE AKSI NOTIFIKASI PRODUKSI (Sudah dinonaktifkan/disembunyikan dari UI)
// ============================================================
if (isset($_GET['action']) && $_GET['action'] == 'notif_produksi') {
    $id_bsj_notif = (int)$_GET['id_bsj'];
    $qty_notif    = (float)$_GET['qty']; 
    $_SESSION['flash_msg'] = "Berhasil! Peringatan kebutuhan stok telah dikirim ke staf produksi.";
    header("Location: dashboard_purchasing.php");
    exit();
}

// ============================================================
// 1. KARTU STRATEGIS (ANGKA SUDAH SINKRON)
// ============================================================

// A. PO Belum Diterima (Outstanding)
$q_outstanding = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pembelian p LEFT JOIN penerimaan pn ON p.id_pembelian = pn.id_pembelian WHERE pn.id_penerimaan IS NULL");
$total_outstanding = mysqli_fetch_assoc($q_outstanding)['total'] ?? 0;

// B. Masalah Kiriman (QTY KURANG + BARANG RUSAK) - Ngitung per ITEM
$sql_item_kurang = "
    SELECT COUNT(*) as total 
    FROM detail_penerimaan dtn 
    JOIN penerimaan pn ON dtn.id_penerimaan = pn.id_penerimaan
    JOIN detail_pembelian dp ON pn.id_pembelian = dp.id_pembelian AND dtn.id_bb = dp.id_bb
    WHERE dtn.qty_terima < dp.qty_beli
";
$count_kurang = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_item_kurang))['total'] ?? 0;

$sql_item_rusak = "SELECT COUNT(*) as total FROM detail_waste WHERE sumber = 'Penerimaan'";
$count_rusak = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_item_rusak))['total'] ?? 0;

$total_masalah = $count_kurang + $count_rusak;

// C. Stok Kosong (Di Semua Gudang)
$total_kosong = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM stok_bahan WHERE jumlah <= 0"))['total'] ?? 0;

// D. Bahan Terboros (30 hari terakhir)
$sql_boros = "SELECT COALESCE(bb.nama_bb, bsj.nama_bsj) as nama FROM request_bahan rb JOIN header_request h ON rb.id_header_req = h.id_header_req LEFT JOIN master_bahan_baku bb ON rb.id_bb = bb.id_bb LEFT JOIN master_bahan_setengah_jadi bsj ON rb.id_bsj = bsj.id_bsj WHERE h.tgl_request >= DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY rb.id_bb, rb.id_bsj ORDER BY SUM(rb.qty_request) DESC LIMIT 1";
$bahan_boros = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_boros))['nama'] ?? '-';

// ============================================================
// 2. LOGIKA SMART INSIGHT: REKOMENDASI PEMBELIAN
// ============================================================
// Cek Gudang Utama (Asumsi ID = 1)
$sql_rek_utama = "
    (SELECT 'BB' as tipe, bb.id_bb as id_item, bb.nama_bb as nama_item, sb.jumlah as stok_sekarang, bb.stok_minimal as batas_minimal, g.nama_gudang, sat.nama_satuan as satuan, 0 as is_pending_prod
    FROM stok_bahan sb JOIN master_bahan_baku bb ON sb.id_bb = bb.id_bb JOIN master_gudang g ON sb.id_gudang = g.id_gudang JOIN master_satuan sat ON bb.id_satuan = sat.id_satuan
    WHERE sb.id_gudang = 1 AND sb.jumlah <= bb.stok_minimal)
    UNION ALL
    (SELECT 'BSJ' as tipe, bsj.id_bsj as id_item, bsj.nama_bsj as nama_item, sb.jumlah as stok_sekarang, bsj.stok_minimal_bsj as batas_minimal, g.nama_gudang, sat.nama_satuan as satuan,
            (SELECT COUNT(*) FROM produksi p WHERE p.id_bsj = bsj.id_bsj AND p.status IN ('Rencana', 'Proses')) as is_pending_prod
    FROM stok_bahan sb JOIN master_bahan_setengah_jadi bsj ON sb.id_bsj = bsj.id_bsj JOIN master_gudang g ON sb.id_gudang = g.id_gudang JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
    WHERE sb.id_gudang = 1 AND sb.jumlah <= bsj.stok_minimal_bsj)
    ORDER BY stok_sekarang ASC
";
$res_rekomendasi = mysqli_query($koneksi, $sql_rek_utama);

if (mysqli_num_rows($res_rekomendasi) > 0) {
    $info_rek = "Fokus stok <b>Gudang Utama</b>: perlu segera dipantau untuk di-restock.";
    $alert_color = "alert-warning";
} else {
    // Gudang Utama Aman, Cek Gudang Lain yang stoknya 0
    $sql_rek_lain = "
        (SELECT 'BB' as tipe, bb.id_bb as id_item, bb.nama_bb as nama_item, sb.jumlah as stok_sekarang, bb.stok_minimal as batas_minimal, g.nama_gudang, sat.nama_satuan as satuan, 0 as is_pending_prod
        FROM stok_bahan sb JOIN master_bahan_baku bb ON sb.id_bb = bb.id_bb JOIN master_gudang g ON sb.id_gudang = g.id_gudang JOIN master_satuan sat ON bb.id_satuan = sat.id_satuan
        WHERE sb.id_gudang != 1 AND sb.jumlah <= 0)
        UNION ALL
        (SELECT 'BSJ' as tipe, bsj.id_bsj as id_item, bsj.nama_bsj as nama_item, sb.jumlah as stok_sekarang, bsj.stok_minimal_bsj as batas_minimal, g.nama_gudang, sat.nama_satuan as satuan,
                (SELECT COUNT(*) FROM produksi p WHERE p.id_bsj = bsj.id_bsj AND p.status IN ('Rencana', 'Proses')) as is_pending_prod
        FROM stok_bahan sb JOIN master_bahan_setengah_jadi bsj ON sb.id_bsj = bsj.id_bsj JOIN master_gudang g ON sb.id_gudang = g.id_gudang JOIN master_satuan sat ON bsj.id_satuan = sat.id_satuan
        WHERE sb.id_gudang != 1 AND sb.jumlah <= 0)
        ORDER BY stok_sekarang ASC
    ";
    $res_rekomendasi = mysqli_query($koneksi, $sql_rek_lain);
    $info_rek = "Gudang Utama aman. Warning stok <b>KOSONG</b> di gudang operasional.";
    $alert_color = "alert-danger";
}

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
    <title>Dashboard</title>
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
                    <h4 class="page-title">Purchasing Dashboard</h4>
                </div>

                <div class="row">
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round shadow-sm card-accent-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-primary bubble-shadow-small"><i class="fas fa-file-invoice"></i></div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">PO Pending</p>
                                            <h4 class="card-title"><?= $total_outstanding ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round shadow-sm card-accent-danger">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-danger bubble-shadow-small"><i class="fas fa-exclamation-circle"></i></div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Masalah Kiriman</p>
                                            <h4 class="card-title text-danger"><?= $total_masalah ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round bg-dark text-white shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center text-warning"><i class="fas fa-boxes"></i></div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category text-white">Stok Kosong</p>
                                            <h4 class="card-title text-warning"><?= $total_kosong ?></h4>
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
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-success bubble-shadow-small"><i class="fas fa-chart-line"></i></div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Terboros (30hr)</p>
                                            <h4 class="card-title" style="font-size:13px;"><?= htmlspecialchars($bahan_boros) ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-top border-primary border-3">
                            <div class="card-header d-flex justify-content-between align-items-center bg-white py-3 border-0">
                                <h4 class="card-title text-danger fw-bold"><i class="fas fa-lightbulb me-2 text-warning"></i>Rekomendasi Pemenuhan Stok</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="alert <?= $alert_color ?> border-0 rounded-0 m-0 py-2">
                                    <i class="fas fa-info-circle me-1"></i> <?= $info_rek ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Nama Bahan / BSJ</th>
                                                <th>Gudang Tujuan</th>
                                                <th class="text-center">Stok Sekarang</th>
                                                <th class="text-center">Batas Minimal</th>
                                                <th class="text-center">Status</th>
                                                <!-- KOLOM TINDAKAN DIHAPUS -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if(mysqli_num_rows($res_rekomendasi) > 0): 
                                                while($r = mysqli_fetch_assoc($res_rekomendasi)): 
                                                    $stok = (float)round($r['stok_sekarang'], 2);
                                                    $min  = (float)round($r['batas_minimal'], 2);
                                                    $is_habis = ($stok <= 0);
                                            ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($r['nama_item']) ?></td>
                                                <td><span class="badge badge-info badge-masalah"><?= htmlspecialchars($r['nama_gudang']) ?></span></td>
                                                <td class="text-center fw-bold <?= $is_habis ? 'text-danger' : 'text-warning' ?>">
                                                    <?= $stok ?> <small class="text-muted fw-normal"><?= htmlspecialchars($r['satuan']) ?></small>
                                                </td>
                                                <td class="text-center text-muted fw-semibold"><?= $min ?></td>
                                                <td class="text-center">
                                                    <?php if($is_habis): ?>
                                                        <span class="badge badge-danger badge-status shadow-sm">Habis Total</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning badge-status text-dark shadow-sm">Kritis</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i class="fas fa-check-circle fa-2x text-success mb-2 d-block"></i>
                                                    Stok seluruh gudang terpantau sangat aman.
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
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-top border-primary border-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-3">
                                <h4 class="card-title section-title text-primary mb-0">
                                    <i class="fas fa-truck-loading me-2"></i>Permintaan Kurang Kirim Antar Gudang
                                </h4>
                                <a href="pembelian.php" class="btn btn-primary btn-sm btn-round shadow-sm px-3">
                                    Buka Riwayat PO
                                </a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-custom mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">#</th>
                                                <th>Nama Bahan / BSJ</th>
                                                <th>Gudang Peminta</th>
                                                <th class="text-center">Kekurangan</th>
                                                <th class="text-center">Aksi / Tindakan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $sql_list = "
                                            SELECT rb.*, 
                                                   COALESCE(bb.nama_bb, bsj.nama_bsj) as nama, 
                                                   g.nama_gudang, 
                                                   COALESCE(s_bb.nama_satuan, s_bsj.nama_satuan) as sat,
                                                   CASE WHEN rb.id_bsj IS NOT NULL THEN (SELECT COUNT(*) FROM produksi p WHERE p.id_bsj = rb.id_bsj AND p.status IN ('Rencana', 'Proses')) ELSE 0 END as is_pending_prod
                                            FROM request_bahan rb 
                                            JOIN header_request h ON rb.id_header_req = h.id_header_req
                                            JOIN master_gudang g ON h.id_gudang_tujuan = g.id_gudang
                                            LEFT JOIN master_bahan_baku bb ON rb.id_bb = bb.id_bb 
                                            LEFT JOIN master_satuan s_bb ON bb.id_satuan = s_bb.id_satuan
                                            LEFT JOIN master_bahan_setengah_jadi bsj ON rb.id_bsj = bsj.id_bsj 
                                            LEFT JOIN master_satuan s_bsj ON bsj.id_satuan = s_bsj.id_satuan
                                            WHERE rb.qty_sisa > 0 
                                            ORDER BY h.tgl_request DESC 
                                        ";
                                        $res = mysqli_query($koneksi, $sql_list);
                                        if (mysqli_num_rows($res) > 0):
                                            $no = 1;
                                            while ($l = mysqli_fetch_assoc($res)):
                                                $id_item = $l['id_bb'] ? $l['id_bb'] : $l['id_bsj'];
                                                $tipe = $l['id_bb'] ? 'BB' : 'BSJ';
                                        ?>
                                        <tr>
                                            <td class="ps-3 text-muted"><?= $no++ ?></td>
                                            <td class="fw-semibold text-dark"><?= htmlspecialchars($l['nama']) ?></td>
                                            <td><span class="badge badge-info badge-masalah"><?= htmlspecialchars($l['nama_gudang']) ?></span></td>
                                            <td class="text-center text-danger fw-bold"><?= (float)$l['qty_sisa'] ?> <small class="text-muted fw-normal"><?= htmlspecialchars($l['sat']) ?></small></td>
                                            <td class="text-center">
                                                <?php if($tipe == 'BB'): ?>
                                                    <a href="pembelian.php?id_item=<?= $id_item ?>&qty=<?= (float)$l['qty_sisa'] ?>" class="btn btn-primary btn-sm btn-round shadow-sm px-3">
                                                        <i class="fas fa-cart-plus me-1"></i> Beli
                                                    </a>
                                                <?php else: ?>
                                                    <?php if($l['is_pending_prod'] > 0): ?>
                                                        <button class="btn btn-secondary btn-sm btn-round px-3" disabled>
                                                            <i class="fas fa-clock me-1"></i> Sedang Diproses
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-warning btn-sm btn-round shadow-sm px-3 fw-bold text-dark" onclick="notifProduksi('<?= $id_item ?>', '<?= htmlspecialchars($l['nama']) ?>', '<?= (float)$l['qty_sisa'] ?>')">
                                                            <i class="fas fa-bell me-1"></i> Kirim Peringatan
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                                Tidak ada kekurangan bahan untuk cabang saat ini.
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

            </div></div></div></div>
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Tangkap Flash Message PHP dan tampilkan via SweetAlert
    <?php if(isset($_SESSION['flash_msg'])): ?>
        Swal.fire({
            title: 'Terkirim!',
            text: '<?= $_SESSION['flash_msg'] ?>',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>

    // Fungsi notifikasi untuk tombol di tabel bawah (Permintaan Kurang Kirim)
    function notifProduksi(idBsj, namaBahan, qtyTarget) {
        Swal.fire({
            title: 'Kirim Peringatan?',
            html: "Ingatkan staf bahwa stok <b>" + namaBahan + "</b> sedang menipis/kurang dan butuh segera diproduksi?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ff9e27', 
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Ya, Kirim Peringatan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Tembak GET request agar diproses oleh PHP
                window.location.href = "dashboard_purchasing.php?action=notif_produksi&id_bsj=" + idBsj + "&qty=" + qtyTarget;
            }
        });
    }
</script>
</body>
</html>