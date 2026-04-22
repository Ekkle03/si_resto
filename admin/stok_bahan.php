<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Parameter Filter
$tipe = isset($_GET['tipe']) ? $_GET['tipe'] : 'BB';
$id_item = isset($_GET['id_item']) ? $_GET['id_item'] : '';
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');
$id_gudang = isset($_GET['id_gudang']) ? $_GET['id_gudang'] : '1'; 

$view = ($id_item == "") ? 'summary' : 'detail';

// 2. Load Dropdown
if ($tipe == 'BB') {
    $q_dropdown = mysqli_query($koneksi, "SELECT id_bb as id, nama_bb as nama FROM master_bahan_baku ORDER BY nama_bb ASC");
} else {
    $q_dropdown = mysqli_query($koneksi, "SELECT id_bsj as id, nama_bsj as nama FROM master_bahan_setengah_jadi ORDER BY nama_bsj ASC");
}

$q_gudang_name = mysqli_query($koneksi, "SELECT nama_gudang FROM master_gudang WHERE id_gudang = '$id_gudang'");
$g_res = mysqli_fetch_assoc($q_gudang_name);
$nama_gudang_aktif = $g_res['nama_gudang'] ?? 'Gudang Utama';
$is_gudang_utama = ($id_gudang == '1'); 

// ── Navbar: siapkan variabel session ──────────────────────────────────────────
$nama     = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username']     ?? 'guest');
$role     = htmlspecialchars($_SESSION['nama_role']    ?? '');
$foto     = !empty($_SESSION['foto_profil'])
            ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil'])
            : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Kartu Stok</title>
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .table-bordered th, .table-bordered td { border: 1px solid #ebedf2 !important; }
        .text-dark-custom { color: #333 !important; }
        .select2-container--default .select2-selection--single { height: 40px !important; border: 1px solid #ebedf2 !important; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <div class="logo-header" data-background-color="dark">
                        <a href="dashboard.php" class="logo"><img src="assets/img/logo/logo_resto.png" alt="Logo Resto" class="navbar-brand" height="30" /></a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar"><i class="gg-menu-right"></i></button>
                            <button class="btn btn-toggle sidenav-toggler"><i class="gg-menu-left"></i></button>
                        </div>
                        <button class="topbar-toggler more"><i class="gg-more-vertical-alt"></i></button>
                    </div>
                </div>
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm"><img src="<?= $foto ?>" alt="Foto Profil" class="avatar-img rounded-circle" onerror="this.src='assets/img/profil/default.png'" /></div>
                                    <span class="profile-username"><span class="op-7">Selamat Datang,</span> <span class="fw-bold"><?= $nama ?></span></span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg"><img src="<?= $foto ?>" alt="Foto Profil" class="avatar-img rounded" onerror="this.src='assets/img/profil/default.png'" /></div>
                                                <div class="u-text"><h4><?= $nama ?></h4><p class="text-muted">@<?= $username ?></p><?php if (!empty($role)): ?><span class="badge bg-secondary mb-2"><?= $role ?></span><?php endif; ?><br><a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a></div>
                                            </div>
                                        </li>
                                        <li><div class="dropdown-divider"></div><a class="dropdown-item" href="#">Pengaturan Akun</a><div class="dropdown-divider"></div><a class="dropdown-item" href="../logout.php">Logout</a></li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header"><h3 class="fw-bold mb-3">Monitoring Stok (<?= $nama_gudang_aktif ?>)</h3></div>
                    <div class="mb-4">
                        <a href="stok_bahan.php?tipe=BB&id_gudang=<?= $id_gudang ?>" class="btn btn-round <?= $tipe == 'BB' ? 'btn-primary' : 'btn-outline-primary' ?>">Bahan Baku</a>
                        <a href="stok_bahan.php?tipe=BSJ&id_gudang=<?= $id_gudang ?>" class="btn btn-round <?= $tipe == 'BSJ' ? 'btn-primary' : 'btn-outline-primary' ?>">Bahan Setengah Jadi</a>
                    </div>

                    <div class="card card-round shadow-sm mb-4 border-0">
                        <div class="card-body">
                            <form method="GET" action="stok_bahan.php">
                                <input type="hidden" name="tipe" value="<?= $tipe ?>">
                                <div class="row align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Pilih Gudang</label>
                                        <select name="id_gudang" class="form-select border-primary" onchange="this.form.submit()">
                                            <?php 
                                            $q_g = mysqli_query($koneksi, "SELECT * FROM master_gudang");
                                            while($g = mysqli_fetch_assoc($q_g)): ?>
                                                <option value="<?= $g['id_gudang'] ?>" <?= $id_gudang == $g['id_gudang'] ? 'selected' : '' ?>><?= $g['nama_gudang'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Pilih Bahan</label>
                                        <select name="id_item" class="form-select select2-search">
                                            <option value=""></option> 
                                            <?php mysqli_data_seek($q_dropdown, 0); while($d = mysqli_fetch_assoc($q_dropdown)): ?>
                                                <option value="<?= $d['id'] ?>" <?= $id_item == $d['id'] ? 'selected' : '' ?>><?= $d['nama'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2"><label class="form-label fw-bold">Dari</label><input type="date" name="tgl_awal" class="form-control" value="<?= $tgl_awal ?>"></div>
                                    <div class="col-md-2"><label class="form-label fw-bold">Sampai</label><input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>"></div>
                                    <div class="col-md-2"><button type="submit" class="btn btn-secondary w-100">Cari</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($view == 'summary'): ?>
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header d-flex justify-content-between bg-white border-bottom py-3">
                                <div class="card-title fw-bold text-primary">Ringkasan Stok (<?= $nama_gudang_aktif ?>)</div>
                                <a href="laporan/cetak_rekap_stok.php?tipe=<?= $tipe ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_gudang=<?= $id_gudang ?>" 
                                target="_blank" 
                                class="btn btn-success btn-sm px-3 fw-bold">
                                    <i class="fa fa-print me-1"></i> Cetak Rekapitulasi
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="table-stok" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-dark text-center text-uppercase">
                                            <tr>
                                                <th style="width: 50px;">NO</th>
                                                <th>KODE</th>
                                                <th>NAMA BAHAN</th>
                                                <th>JUMLAH STOK</th>
                                                <th>SATUAN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no=1; 
                                            $prefix = ($tipe == 'BB' ? 'bb' : 'bsj'); 
                                            $table_m = ($tipe == 'BB' ? 'master_bahan_baku' : 'master_bahan_setengah_jadi'); 
                                            $col_id = "id_" . $prefix;
                                            
                                            $tahap_query = ($tipe == 'BSJ') ? ", b.tahap" : "";
                                            
                                            // TARIK NILAI KONVERSI JUGA
                                            $sql = "SELECT b.$col_id as id, b.kode_$prefix as kode, b.nama_$prefix as nama $tahap_query, 
                                                           COALESCE(s.jumlah, 0) as stok_db, 
                                                           sat_m.nama_satuan as sat_master, 
                                                           sat_k.nama_satuan as sat_kecil,
                                                           k.nilai_konversi
                                                    FROM $table_m b
                                                    LEFT JOIN (SELECT $col_id, jumlah FROM stok_bahan WHERE id_gudang = '$id_gudang') s ON b.$col_id = s.$col_id
                                                    LEFT JOIN master_satuan sat_m ON b.id_satuan = sat_m.id_satuan
                                                    LEFT JOIN master_konversi k ON b.$col_id = k.id_komponen AND k.tipe_bahan = '$tipe'
                                                    LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan";
                                            $q_run = mysqli_query($koneksi, $sql);
                                            while($row = mysqli_fetch_assoc($q_run)): 
                                                $stok_raw = (float)$row['stok_db']; 
                                                $nilai_konv = (float)$row['nilai_konversi'];
                                                if ($nilai_konv <= 0) $nilai_konv = 1; // Cegah error dibagi 0
                                                
                                                $is_bsj1 = ($tipe == 'BSJ' && isset($row['tahap']) && $row['tahap'] == 'bsj1');
                                                
                                                // PEMBAGIAN DIKEMBALIKAN KE SINI
                                                if ($is_gudang_utama || $is_bsj1) {
                                                    $satuan_tampil = $row['sat_master']; 
                                                    $stok_tampil = $stok_raw / $nilai_konv; 
                                                } else {
                                                    $satuan_tampil = !empty($row['sat_kecil']) ? $row['sat_kecil'] : $row['sat_master']; 
                                                    $stok_tampil = $stok_raw; 
                                                }
                                            ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="text-center fw-bold"><?= $row['kode'] ?></td>
                                                <td class="text-start ps-3 fw-bold text-dark"><?= $row['nama'] ?></td>
                                                <td class="text-center fw-bold text-primary"><?= (float)round($stok_tampil, 3) ?></td>
                                                <td class="text-center"><?= $satuan_tampil ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header d-flex justify-content-between bg-white border-bottom py-3">
                                <div class="card-title fw-bold text-primary">Log Stok Detail (<?= $nama_gudang_aktif ?>)</div>
                                <a href="laporan/cetak_stok.php?tipe=<?= $tipe ?>&id_item=<?= $id_item ?>&tgl_awal=<?= $tgl_awal ?>&tgl_akhir=<?= $tgl_akhir ?>&id_gudang=<?= $id_gudang ?>" 
                                    target="_blank" 
                                    class="btn btn-outline-danger btn-sm px-3">
                                        <i class="fa fa-print me-1"></i> Cetak Laporan
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="table-stok" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-dark text-center text-uppercase">
                                            <tr>
                                                <th style="width: 150px;">TANGGAL</th>
                                                <th>URAIAN</th>
                                                <th style="width: 100px;">MASUK (+)</th>
                                                <th style="width: 100px;">KELUAR (-)</th>
                                                <th style="width: 100px;">SISA STOK</th>
                                                <th style="width: 100px;">SATUAN</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $prefix_log = ($tipe == 'BB' ? 'bb' : 'bsj'); 
                                            $col_id_log = "id_" . $prefix_log; 
                                            $table_m_log = ($tipe == 'BB' ? 'master_bahan_baku' : 'master_bahan_setengah_jadi');
                                            
                                            $tahap_query_k = ($tipe == 'BSJ') ? ", b.tahap" : "";
                                            $sql_konv = "SELECT sat_m.nama_satuan as sat_master, sat_k.nama_satuan as sat_kecil, k.nilai_konversi $tahap_query_k
                                                         FROM $table_m_log b 
                                                         LEFT JOIN master_satuan sat_m ON b.id_satuan = sat_m.id_satuan 
                                                         LEFT JOIN master_konversi k ON b.$col_id_log = k.id_komponen AND k.tipe_bahan = '$tipe' 
                                                         LEFT JOIN master_satuan sat_k ON k.satuan_kecil = sat_k.id_satuan 
                                                         WHERE b.$col_id_log = '$id_item'";
                                            $q_konv = mysqli_query($koneksi, $sql_konv);
                                            $data_k = mysqli_fetch_assoc($q_konv);

                                            $is_bsj1_log = ($tipe == 'BSJ' && isset($data_k['tahap']) && $data_k['tahap'] == 'bsj1');
                                            
                                            $nilai_konv_log = (float)$data_k['nilai_konversi'];
                                            if ($nilai_konv_log <= 0) $nilai_konv_log = 1;

                                            if ($is_gudang_utama || $is_bsj1_log) {
                                                $sat_log = $data_k['sat_master'];
                                            } else {
                                                $sat_log = !empty($data_k['sat_kecil']) ? $data_k['sat_kecil'] : $data_k['sat_master'];
                                            }

                                            $sql_log = "SELECT l.* FROM log_stok l 
                                                        WHERE l.$col_id_log = '$id_item' AND l.id_gudang = '$id_gudang' 
                                                        AND DATE(l.tgl_log) BETWEEN '$tgl_awal' AND '$tgl_akhir' 
                                                        ORDER BY l.tgl_log DESC, l.id_log DESC";
                                            $q_log = mysqli_query($koneksi, $sql_log);
                                            
                                            while($log = mysqli_fetch_assoc($q_log)): 
                                                $in = (float)$log['qty_masuk']; 
                                                $out = (float)$log['qty_keluar']; 
                                                $sisa = (float)$log['sisa_stok']; 
                                                
                                                // PEMBAGIAN JUGA DILAKUKAN DI DETAIL
                                                if ($is_gudang_utama || $is_bsj1_log) {
                                                    $in = $in / $nilai_konv_log;
                                                    $out = $out / $nilai_konv_log;
                                                    $sisa = $sisa / $nilai_konv_log;
                                                }

                                                $uraian = htmlspecialchars($log['keterangan']);
                                            ?>
                                            <tr class="text-dark">
                                                <td class="text-center"><?= date('d/m/Y H:i', strtotime($log['tgl_log'])) ?></td>
                                                <td class="text-start ps-3 small text-dark"><?= $uraian ?></td>
                                                <td class="text-center text-success fw-bold"><?= $in > 0 ? '+'.(float)round($in, 3) : '-' ?></td>
                                                <td class="text-center text-danger fw-bold"><?= $out > 0 ? '-'.(float)round($out, 3) : '-' ?></td>
                                                <td class="text-center bg-light fw-bold"><?= (float)round($sisa, 3) ?></td>
                                                <td class="text-center"><?= $sat_log ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#table-stok').DataTable({
                "order": [] 
            });
            
            $('.select2-search').select2({ 
                theme: "default", 
                width: '100%', 
                placeholder: "-- Tampilkan Ringkasan Semua --", 
                allowClear: true 
            });
        });
    </script>
</body>
</html>