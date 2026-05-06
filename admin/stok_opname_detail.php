<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

$nama = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$foto = !empty($_SESSION['foto_profil']) ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) : 'assets/img/profil/default.png';

$id_header = $_GET['id'] ?? '';
if (empty($id_header)) { header("Location: stok_opname.php"); exit(); }

$q_h = mysqli_query($koneksi, "SELECT h.*, g.nama_gudang 
                               FROM header_opname h 
                               JOIN master_gudang g ON h.id_gudang = g.id_gudang 
                               WHERE h.id_header_opname = '$id_header'");
$d_h = mysqli_fetch_assoc($q_h);
if (!$d_h) { header("Location: stok_opname.php"); exit(); }

// =========================================================================
// LOGIKA GUDANG CERDAS UNTUK DETAIL TAMPILAN
// =========================================================================
$is_gudang_utama = ($d_h['id_gudang'] == 1);

if ($is_gudang_utama) {
    $satuan_pilih = "COALESCE(sat_b.nama_satuan, sat_default.nama_satuan)";
    $konversi_val = "COALESCE(k.nilai_konversi, 1)";
} else {
    $satuan_pilih = "COALESCE(sat_k.nama_satuan, sat_default.nama_satuan)";
    $konversi_val = "1";
}

$sql_detail = "(SELECT 'BB' as tipe, b.kode_bb as kode, b.nama_bb as nama_item, 
                       $satuan_pilih as satuan, 
                       d.stok_sistem / $konversi_val as sistem, 
                       d.stok_fisik / $konversi_val as fisik, 
                       d.selisih / $konversi_val as selisih
                FROM detail_opname d
                JOIN master_bahan_baku b ON d.id_bb = b.id_bb
                JOIN master_satuan sat_default ON b.id_satuan = sat_default.id_satuan
                LEFT JOIN master_konversi k ON b.id_bb = k.id_komponen AND k.tipe_bahan = 'BB'
                LEFT JOIN master_satuan sat_b ON k.satuan_besar = sat_b.id_satuan
                LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan
                WHERE d.id_header_opname = '$id_header')
               UNION
               (SELECT 'BSJ' as tipe, b.kode_bsj as kode, b.nama_bsj as nama_item, 
                       $satuan_pilih as satuan, 
                       d.stok_sistem / $konversi_val as sistem, 
                       d.stok_fisik / $konversi_val as fisik, 
                       d.selisih / $konversi_val as selisih
                FROM detail_opname d
                JOIN master_bahan_setengah_jadi b ON d.id_bsj = b.id_bsj
                JOIN master_satuan sat_default ON b.id_satuan = sat_default.id_satuan
                LEFT JOIN master_konversi k ON b.id_bsj = k.id_komponen AND k.tipe_bahan = 'BSJ'
                LEFT JOIN master_satuan sat_b ON k.satuan_besar = sat_b.id_satuan
                LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan
                WHERE d.id_header_opname = '$id_header')";
$q_detail = mysqli_query($koneksi, $sql_detail);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Opname - SI Resto</title>
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
            <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                <div class="container-fluid">
                    <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                        <li class="nav-item topbar-user dropdown hidden-caret">
                            <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                <div class="avatar-sm"><img src="<?= $foto ?>" class="avatar-img rounded-circle" /></div>
                                <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= $nama ?></span></span>
                            </a>
                            <ul class="dropdown-menu dropdown-user animated fadeIn">
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

        <div class="container">
            <div class="page-inner">
                <div class="page-header">
                    <h3 class="fw-bold mb-3">Detail Hasil Stok Opname</h3>
                </div>

                <div class="card card-round shadow-sm border-0">
                    <div class="card-header bg-white border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title fw-bold mb-0">KODE OPN: <span class="text-primary"><?= $d_h['kode_opname'] ?></span></h4>
                            <a href="stok_opname.php" class="btn btn-warning btn-round btn-sm shadow-sm text-dark fw-bold">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4 p-3 bg-light rounded shadow-none border">
                            <div class="col-md-3">
                                <label class="text-muted small mb-0">Tanggal Pelaksanaan</label>
                                <h6 class="fw-bold mb-0"><?= date('d M Y', strtotime($d_h['tgl_opname'])) ?></h6>
                            </div>
                            <div class="col-md-3 border-start">
                                <label class="text-muted small mb-0">Lokasi Gudang</label>
                                <h6 class="fw-bold mb-0 text-info"><?= $d_h['nama_gudang'] ?></h6>
                            </div>
                            <div class="col-md-6 border-start">
                                <label class="text-muted small mb-0">Keterangan</label>
                                <h6 class="fw-bold mb-0 text-muted fst-italic"><?= htmlspecialchars($d_h['keterangan'] ?: '-') ?></h6>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle">
                                <thead class="bg-light">
                                    <tr class="text-center text-uppercase small fw-bold">
                                        <th style="width: 50px;">NO</th>
                                        <th>KODE BAHAN</th>
                                        <th>NAMA BAHAN</th>
                                        <th>STOK SISTEM</th>
                                        <th>STOK FISIK</th>
                                        <th>SELISIH</th>
                                        <th>SATUAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no=1; while($row = mysqli_fetch_assoc($q_detail)): 
                                        // Filter koma nol nol
                                        $sistem  = (float)round($row['sistem'], 2);
                                        $fisik   = (float)round($row['fisik'], 2);
                                        $selisih = (float)round($row['selisih'], 2);
                                    ?>
                                    <tr>
                                        <td class="text-center text-muted small"><?= $no++ ?></td>
                                        <td class="text-center fw-bold"><?= $row['kode'] ?></td>
                                        <td>
                                            <span class="fw-bold text-dark"><?= $row['nama_item'] ?></span><br>
                                            <small class="text-muted text-uppercase">Tipe: <?= $row['tipe'] ?></small>
                                        </td>
                                        <td class="text-center text-muted fw-bold"><?= $sistem ?></td>
                                        <td class="text-center text-success fw-bold"><?= $fisik ?></td>
                                        <td class="text-center fw-bold">
                                            <?php if($selisih > 0): ?>
                                                <span class="text-success">+<?= $selisih ?></span>
                                            <?php elseif($selisih < 0): ?>
                                                <span class="text-danger"><?= $selisih ?></span>
                                            <?php else: ?>
                                                <span class="text-dark">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center text-muted small"><?= htmlspecialchars($row['satuan']) ?></td>
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
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</body>
</html>