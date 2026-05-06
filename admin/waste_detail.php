<?php
session_start(); 
include("../config/auth.php");
include("../config/koneksi_mysql.php");

$id_header = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');

// 1. Ambil data Header Waste
$sql_h = "SELECT h.*, g.nama_gudang, k.nama_lengkap 
          FROM header_waste h
          JOIN master_gudang g ON h.id_gudang = g.id_gudang
          JOIN master_karyawan k ON h.id_karyawan = k.id_karyawan
          WHERE h.id_header_waste = '$id_header'";
$q_h = mysqli_query($koneksi, $sql_h);
$d_h = mysqli_fetch_assoc($q_h);

if (!$d_h) {
    header("Location: waste.php");
    exit();
}

// 2. Ambil data Detail Waste
$sql_d = "SELECT d.*, 
          COALESCE(bb.nama_bb, bsj.nama_bsj) as nama_barang,
          COALESCE(s_bb.nama_satuan, s_bsj.nama_satuan) as sat_besar,
          mk.nilai_konversi,
          sk.nama_satuan as sat_kecil
          FROM detail_waste d
          LEFT JOIN master_bahan_baku bb ON d.id_bb = bb.id_bb
          LEFT JOIN master_satuan s_bb ON bb.id_satuan = s_bb.id_satuan
          LEFT JOIN master_bahan_setengah_jadi bsj ON d.id_bsj = bsj.id_bsj
          LEFT JOIN master_satuan s_bsj ON bsj.id_satuan = s_bsj.id_satuan
          LEFT JOIN master_konversi mk ON (d.id_bb = mk.id_komponen AND mk.tipe_bahan = 'BB')
          LEFT JOIN master_satuan sk ON mk.satuan_kecil = sk.id_satuan
          WHERE d.id_header_waste = '$id_header'";
$res_d = mysqli_query($koneksi, $sql_d);

// ── Navbar: siapkan variabel session ─────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';
// ─────────────────────────────────────────────────────────────────────────────
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Detail Waste - SI Resto</title>
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
        /* Memaksa halaman bisa di-scroll */
        html, body { overflow-y: auto !important; height: auto !important; }
        .main-panel { height: auto !important; min-height: 100vh; overflow-y: visible !important; }
        
        .label-info { font-size: 11px; color: #8d9498; text-transform: uppercase; font-weight: 700; }
        .val-info { font-size: 14px; color: #1a2035; font-weight: 600; }
        
        /* Memaksa gambar bukti tetap kecil */
        .img-waste-thumb { 
            width: 70px !important; 
            height: 70px !important; 
            object-fit: cover !important; 
            border-radius: 8px; 
            cursor: pointer; 
            border: 2px solid #ebedef;
            transition: 0.3s;
        }
        .img-waste-thumb:hover { border-color: #1572e8; transform: scale(1.1); }
    </style>
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
                <div class="page-header d-flex justify-content-between align-items-center">
                    <h3 class="fw-bold">Detail Riwayat: <span class="text-primary"><?= $d_h['kode_waste'] ?></span></h3>
                    <a href="waste.php" class="btn btn-sm btn-black btn-border btn-round"><i class="fa fa-undo me-1"></i> Kembali</a>
                </div>

                <div class="card card-round shadow-sm border-0 mb-4">
                    <div class="card-body py-3">
                        <div class="row text-center">
                            <div class="col-md-3 border-end">
                                <div class="label-info">Gudang Asal</div>
                                <div class="val-info text-uppercase"><?= $d_h['nama_gudang'] ?></div>
                            </div>
                            <div class="col-md-3 border-end">
                                <div class="label-info">Tanggal Transaksi</div>
                                <div class="val-info"><?= date('d M Y', strtotime($d_h['tgl_waste'])) ?></div>
                            </div>
                            <div class="col-md-3 border-end">
                                <div class="label-info">Karyawan</div>
                                <div class="val-info"><?= $d_h['nama_lengkap'] ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="label-info">Waktu Input</div>
                                <div class="val-info"><?= date('H:i', strtotime($d_h['tgl_input'])) ?> WIB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-round shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="text-center">
                                    <th style="width: 5%">NO</th>
                                    <th>NAMA BARANG</th>
                                    <th>ALASAN</th>
                                    <th>JUMLAH</th>
                                    <th>FOTO BUKTI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($res_d)): 
                                    $konv = (float)($row['nilai_konversi'] ?? 1);
                                    if ($d_h['id_gudang'] == "1") {
                                        $qty_tampil = $row['qty_waste'] / $konv;
                                        $satuan = $row['sat_besar'];
                                    } else {
                                        $qty_tampil = $row['qty_waste'];
                                        $satuan = $row['sat_kecil'] ?? $row['sat_besar'];
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no ?></td>
                                    <td><span class="fw-bold text-dark"><?= $row['nama_barang'] ?></span></td>
                                    <td><span class="badge bg-danger text-white"><?= $row['alasan'] ?></span></td>
                                    <td class="text-center">
                                        <span class="fw-bold text-primary" style="font-size: 15px;"><?= round($qty_tampil, 2) ?></span> 
                                        <span class="text-muted small"><?= $satuan ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php if(!empty($row['foto_bukti'])): ?>
                                            <img src="../assets/img/waste/<?= $row['foto_bukti'] ?>" class="img-waste-thumb shadow-sm" data-bs-toggle="modal" data-bs-target="#modalFoto<?= $no ?>">
                                            
                                            <div class="modal fade" id="modalFoto<?= $no ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 bg-transparent text-center">
                                                        <div class="modal-body p-0">
                                                            <img src="../assets/img/waste/<?= $row['foto_bukti'] ?>" class="img-fluid rounded shadow-lg">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php $no++; endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
</body>
</html>