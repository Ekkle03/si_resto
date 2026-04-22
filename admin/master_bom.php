<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Query Data BOM yang sudah ada (TAMBAHKAN MAX(b.target_hasil))
$sql_ada = "SELECT 
                b.id_induk, b.tipe_bom,
                MAX(b.target_hasil) as target_hasil, 
                CASE WHEN b.tipe_bom = 'MENU' THEN m.nama_menu ELSE bsj.nama_bsj END AS nama_produk,
                COUNT(b.id_bom) as jumlah_bahan
            FROM master_bom b
            LEFT JOIN master_menu m ON b.id_induk = m.id_menu AND b.tipe_bom = 'MENU'
            LEFT JOIN master_bahan_setengah_jadi bsj ON b.id_induk = bsj.id_bsj AND b.tipe_bom = 'BSJ'
            GROUP BY b.id_induk, b.tipe_bom";
$query_ada = mysqli_query($koneksi, $sql_ada);

// 2. Query untuk List di Modal (Hanya yang BELUM punya resep)
$sql_belum_menu = "SELECT id_menu, nama_menu FROM master_menu 
                   WHERE id_menu NOT IN (SELECT id_induk FROM master_bom WHERE tipe_bom = 'MENU') ORDER BY nama_menu ASC";
$query_belum_menu = mysqli_query($koneksi, $sql_belum_menu);

$sql_belum_bsj = "SELECT id_bsj, nama_bsj FROM master_bahan_setengah_jadi 
                  WHERE id_bsj NOT IN (SELECT id_induk FROM master_bom WHERE tipe_bom = 'BSJ') ORDER BY nama_bsj ASC";
$query_belum_bsj = mysqli_query($koneksi, $sql_belum_bsj);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Divisi - Sistem Resto</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/logo/logo_resto.png" type="image/x-icon" />

    <!-- Fonts and icons -->
    <script src="assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: { families: ["Public Sans:300,400,500,600,700"] },
            custom: {
                families: [
                    "Font Awesome 5 Solid",
                    "Font Awesome 5 Regular",
                    "Font Awesome 5 Brands",
                    "simple-line-icons"
                ],
                urls: ["assets/css/fonts.min.css"],
            },
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />

    <style>
        .btn-outline-primary-thicker {
            border-width: 2px !important;
            font-weight: 500 !important;
        }
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
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Selamat Datang,</span>
                                        <span class="fw-bold"><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" />
                                                </div>
                                                <div class="u-text">
                                                    <h4><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest') ?></h4>
                                                    <p class="text-muted"><?= htmlspecialchars($_SESSION['username'] ?? 'guest') ?></p>
                                                    <a href="profile.php" class="btn btn-xs btn-secondary btn-sm">Lihat Profil</a>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Pengaturan Akun</a>
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
                    <div class="page-header">
                        <h3 class="fw-bold mb-3">Master Data</h3>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex align-items-center">
                                    <h4 class="card-title">Data Master BOM</h4>
                                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#modalTambahBom">
                                        <i class="fa fa-plus"></i> Tambah BOM
                                    </button>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($_GET['msg'])): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_GET['msg']) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table id="table-ada" class="table table-striped table-bordered table-hover">
                                            <thead class="text-center bg-light">
                                                <tr>
                                                    <th style="width: 5%">NO</th>
                                                    <th>NAMA PRODUK</th>
                                                    <th>TIPE</th>
                                                    <th>TARGET HASIL</th>
                                                    <th>JUMLAH BAHAN</th>
                                                    <th style="width: 15%">ACTION</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no=1; while($r = mysqli_fetch_assoc($query_ada)): ?>
                                                <tr class="text-center">
                                                    <td><?= $no++ ?></td>
                                                    <td class="text-start fw-bold"><?= htmlspecialchars($r['nama_produk'] ?? 'Data Terhapus') ?></td>
                                                    <td><?= $r['tipe_bom'] == 'MENU' ? 'Menu' : 'BSJ' ?></td>
                                                    <td class="fw-bold"><?= (float)$r['target_hasil'] ?></td>
                                                    <td><?= $r['jumlah_bahan'] ?> Item</td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <button class="btn btn-info btn-sm me-1 btn-detail" 
                                                                    data-id="<?= $r['id_induk'] ?>" 
                                                                    data-tipe="<?= $r['tipe_bom'] ?>" 
                                                                    data-nama="<?= htmlspecialchars($r['nama_produk']) ?>"
                                                                    title="Lihat Detail">
                                                                <i class="fa fa-eye"></i>
                                                            </button>

                                                            <a href="update_bom.php?id=<?= $r['id_induk'] ?>&tipe=<?= $r['tipe_bom'] ?>" 
                                                               class="btn btn-primary btn-sm me-1" title="Update Resep">
                                                                <i class="fa fa-edit"></i>
                                                            </a>

                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteTotal('<?= $r['id_induk'] ?>', '<?= $r['tipe_bom'] ?>')">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </div>
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
    </div>

    <div class="modal fade" id="modalDetailBom" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Rincian Bahan: <b id="detail-nama-produk"></b></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="isi-detail-bom">
                    <div class="text-center p-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTambahBom" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Produk (Belum Ada Resep)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-start">
                    <label class="fw-bold mb-2">-- MENU JUAL --</label>
                    <div class="list-group mb-3">
                        <?php if(mysqli_num_rows($query_belum_menu) > 0): ?>
                            <?php while($m = mysqli_fetch_assoc($query_belum_menu)): ?>
                                <a href="buat_bom_menu.php?id=<?= $m['id_menu'] ?>" class="list-group-item list-group-item-action">
                                    <i class="fa fa-utensils me-2 text-primary"></i> <?= htmlspecialchars($m['nama_menu']) ?>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-2 text-muted small italic text-center">Semua menu sudah punya resep.</div>
                        <?php endif; ?>
                    </div>

                    <label class="fw-bold mb-2">-- BAHAN SETENGAH JADI --</label>
                    <div class="list-group">
                        <?php if(mysqli_num_rows($query_belum_bsj) > 0): ?>
                            <?php while($b = mysqli_fetch_assoc($query_belum_bsj)): ?>
                                <a href="buat_bom_bsj.php?id=<?= $b['id_bsj'] ?>" class="list-group-item list-group-item-action">
                                    <i class="fa fa-box me-2 text-warning"></i> <?= htmlspecialchars($b['nama_bsj']) ?>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-2 text-muted small italic text-center">Semua BSJ sudah punya resep.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
<script>
        $(document).ready(function() {
            // 1. Inisialisasi DataTable
            $('#table-ada').DataTable();

            // 2. LOGIKA AUTO HIDE ALERT & CLEAN REDIRECT (Gaya Master Divisi)
            if ($('.alert').length) {
                setTimeout(function() {
                    $('.alert').fadeOut('slow', function() {
                        // Setelah hilang, pindah ke URL bersih tanpa ?msg=...
                        window.location.href = 'master_bom.php';
                    });
                }, 3000); // Muncul selama 3 detik
            }

            // 3. Script Detail Pop-up
            $(document).on('click', '.btn-detail', function() {
                var id = $(this).data('id');
                var tipe = $(this).data('tipe');
                var nama = $(this).data('nama');
                
                $('#detail-nama-produk').text(nama);
                $('#isi-detail-bom').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Memuat data...</div>');
                $('#modalDetailBom').modal('show');
                
                $.post('get_detail_bom.php', { id: id, tipe: tipe }, function(res) {
                    $('#isi-detail-bom').html(res);
                });
            });
        });

        function confirmDeleteTotal(id, tipe) {
            if (confirm('Apakah Anda yakin ingin menghapus seluruh resep untuk produk ini?')) {
                window.location.href = 'delete_bom.php?id=' + id + '&tipe=' + tipe;
            }
        }
    </script>
    </body>
</html>