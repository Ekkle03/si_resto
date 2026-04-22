<?php
session_start();
// Naik 2 tingkat ke config karena file ini ada di dalam folder admin/laporan/
include("../../config/koneksi_mysql.php");

// 1. Tangkap Parameter Filter (Default: Bulan Ini)
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$id_gudang = isset($_GET['id_gudang']) ? $_GET['id_gudang'] : ''; 

// 2. Variabel Session Navbar
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$foto_user = !empty($_SESSION['foto_profil'])
            ? '../assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : '../assets/img/profil/default.png';

// FUNGSI PEMBERSIH KODE (Merubah PRD123 / RCV123 jadi bahasa manusia)
function bersihkanAlasan($teks) {
    $teks = preg_replace('/PRD\d+/', '', $teks);
    $teks = preg_replace('/RCV\d+/', '', $teks);
    $teks = trim(str_replace(':', '', $teks));
    return htmlspecialchars($teks);
}
// ── Navbar: siapkan variabel session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? '../assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : '../assets/img/profil/default.png';

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Laporan Waste - AYAM GORENG KABAYAN</title>
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
        .foto-waste { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd; transition: 0.3s; }
        .foto-waste:hover { transform: scale(1.1); }
        .badge-sumber { font-size: 10px; padding: 5px 10px; border-radius: 50px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
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
                                        <a class="dropdown-item" href="#">Pengaturan Akun</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="../../logout.php">Logout</a>
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
                        <h3 class="fw-bold mb-3 text-uppercase">Laporan Pencatatan Waste</h3>
                    </div>

                    <div class="card card-round shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <form method="GET" action="laporan_waste.php">
                                <div class="row align-items-end">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold">Pilih Gudang</label>
                                        <select name="id_gudang" class="form-select border-primary" onchange="this.form.submit()">
                                            <option value="">-- Semua Gudang --</option>
                                            <?php 
                                            $q_g = mysqli_query($koneksi, "SELECT * FROM master_gudang");
                                            while($g = mysqli_fetch_assoc($q_g)): ?>
                                                <option value="<?= $g['id_gudang'] ?>" <?= $id_gudang == $g['id_gudang'] ? 'selected' : '' ?>><?= $g['nama_gudang'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label fw-bold">Dari Tanggal</label>
                                        <input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label fw-bold">Sampai Tanggal</label>
                                        <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <button type="submit" class="btn btn-secondary w-100 fw-bold">CARI</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-round shadow-sm border-0">
                        <div class="card-header d-flex justify-content-between bg-white border-bottom py-3">
                            <div class="card-title fw-bold text-primary">RINCIAN KERUSAKAN BARANG</div>
                            <a href="cetak_rekap_waste.php?tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_gudang=<?= $id_gudang ?>" target="_blank" class="btn btn-outline-danger btn-sm px-3 fw-bold">
                                <i class="fa fa-print me-1"></i> CETAK LAPORAN
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="table-waste" class="table table-striped table-hover table-bordered" style="width: 100%;">
                                    <thead class="bg-light text-center">
                                        <tr>
                                            <th width="50">NO</th>
                                            <th width="120">TANGGAL</th>
                                            <th>NAMA BARANG</th>
                                            <th width="80">QTY</th>
                                            <th width="100">SATUAN</th>
                                            <th width="150">SUMBER</th>
                                            <th>ALASAN</th>
                                            <th width="80">FOTO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $where = "h.tgl_waste BETWEEN '$tgl_awal' AND '$tgl_akhir'";
                                        if (!empty($id_gudang)) { $where .= " AND h.id_gudang = '$id_gudang'"; }

                                        $sql = "SELECT h.tgl_waste, h.id_gudang, d.qty_waste, d.alasan, d.sumber, d.foto_bukti,
                                                       bb.id_bb, bsj.id_bsj,
                                                       COALESCE(bb.nama_bb, bsj.nama_bsj) as nama_item,
                                                       COALESCE(s1.nama_satuan, s2.nama_satuan) as sat_master,
                                                       COALESCE(sk_bb.nama_satuan, sk_bsj.nama_satuan) as sat_kecil,
                                                       COALESCE(k_bb.nilai_konversi, k_bsj.nilai_konversi, 1) as nilai_konversi
                                                FROM detail_waste d
                                                JOIN header_waste h ON d.id_header_waste = h.id_header_waste
                                                LEFT JOIN master_bahan_baku bb ON d.id_bb = bb.id_bb
                                                LEFT JOIN master_satuan s1 ON bb.id_satuan = s1.id_satuan
                                                LEFT JOIN master_konversi k_bb ON bb.id_bb = k_bb.id_komponen AND k_bb.tipe_bahan = 'BB'
                                                LEFT JOIN master_satuan sk_bb ON k_bb.satuan_kecil = sk_bb.id_satuan
                                                LEFT JOIN master_bahan_setengah_jadi bsj ON d.id_bsj = bsj.id_bsj
                                                LEFT JOIN master_satuan s2 ON bsj.id_satuan = s2.id_satuan
                                                LEFT JOIN master_konversi k_bsj ON bsj.id_bsj = k_bsj.id_komponen AND k_bsj.tipe_bahan = 'BSJ'
                                                LEFT JOIN master_satuan sk_bsj ON k_bsj.satuan_kecil = sk_bsj.id_satuan
                                                WHERE $where ORDER BY h.tgl_waste DESC";

                                        $q_run = mysqli_query($koneksi, $sql);
                                        $no = 1;

                                        while($row = mysqli_fetch_assoc($q_run)): 
                                            // --- LOGIKA SATUAN ---
                                            $qty_db = (float)$row['qty_waste'];
                                            $nilai_konv = (float)$row['nilai_konversi'];
                                            if ($nilai_konv <= 0) $nilai_konv = 1;
                                            
                                            if (!empty($row['id_bb'])) {
                                                if ($row['sumber'] == 'Penerimaan' || $row['id_gudang'] == '1') {
                                                    $qty_tampil = $qty_db / $nilai_konv;
                                                    $sat_tampil = $row['sat_master'];
                                                } else {
                                                    $qty_tampil = $qty_db;
                                                    $sat_tampil = !empty($row['sat_kecil']) ? $row['sat_kecil'] : $row['sat_master'];
                                                }
                                            } else {
                                                $qty_tampil = $qty_db;
                                                $sat_tampil = $row['sat_master'];
                                            }

                                            // --- LOGIKA FOTO (Path diperbaiki: naik 2 tingkat ke root) ---
                                            $file_foto = $row['foto_bukti'];
                                            $img_path = '../../assets/img/waste/' . $file_foto; 

                                            if(!empty($file_foto)) {
                                                $tampil_foto = '<a href="'.$img_path.'" target="_blank">
                                                                    <img src="'.$img_path.'" class="foto-waste" 
                                                                    onerror="this.parentElement.outerHTML=\'<span class=\\\'badge bg-danger\\\' style=\\\'font-size:10px;\\\'>File Hilang</span>\'">
                                                                </a>';
                                            } else {
                                                $tampil_foto = '<span class="text-muted small"><i>N/A</i></span>';
                                            }
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no++ ?></td>
                                            <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_waste'])) ?></td>
                                            <td class="fw-bold text-dark"><?= $row['nama_item'] ?></td>
                                            <td class="text-center fw-bold text-danger"><?= (float)round($qty_tampil, 3) ?></td>
                                            <td class="text-center"><?= $sat_tampil ?></td>
                                            <td class="text-center"><span class="badge badge-info badge-sumber"><?= $row['sumber'] ?></span></td>
                                            <td><?= bersihkanAlasan($row['alasan']) ?></td>
                                            <td class="text-center"><?= $tampil_foto ?></td>
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
    
    <script src="../assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="../assets/js/core/popper.min.js"></script>
    <script src="../assets/js/core/bootstrap.min.js"></script>
    <script src="../assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../assets/js/kaiadmin.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#table-waste').DataTable({ "order": [] });
        });
    </script>
</body>
</html>