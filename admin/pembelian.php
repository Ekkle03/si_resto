<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Ambil data riwayat rencana belanja menggunakan kolom kode_pembelian
$sql = "SELECT * FROM pembelian ORDER BY tgl_pembelian DESC, id_pembelian DESC";
$query = mysqli_query($koneksi, $sql);

// Variabel Navbar menggunakan session
$nama = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username'] ?? 'guest');
$role = htmlspecialchars($_SESSION['nama_role'] ?? '');
$foto = !empty($_SESSION['foto_profil']) 
        ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) 
        : 'assets/img/profil/default.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Pembelian</title>
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
        .btn-outline-primary-thicker { border-width: 2px !important; font-weight: 500 !important; }
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
            </div>

        <div class="container">
            <div class="page-inner">
                
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Pembelian Bahan</h3>
                    <div>
                        <a href="add_pembelian.php" class="btn btn-primary btn-round fw-bold shadow-sm">
                            <i class="fa fa-plus me-1"></i> Tambah Pembelian
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white d-flex align-items-center py-3">
                                <h4 class="card-title fw-bold" style="font-size: 15px !important;">Daftar Rencana Pembelian</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['flash_msg'])): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close">
                                        <i class="fa fa-info-circle me-1"></i> <?= $_SESSION['flash_msg'] ?>
                                    </div>
                                    <?php unset($_SESSION['flash_msg']); ?>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="table-pembelian" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-center">
                                            <tr>
                                                <th style="width: 50px;">NO</th>
                                                <th>KODE</th>
                                                <th>TGL PEMBELIAN</th>
                                                <th>KETERANGAN</th>
                                                <th style="width: 100px;">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; while($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="text-dark fw-bold text-center"><?= $row['kode_pembelian'] ?></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_pembelian'])) ?></td>
                                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action justify-content-center">
                                                        <a href="detail_pembelian.php?id=<?= $row['id_pembelian'] ?>" class="btn btn-link btn-primary p-1" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-link btn-danger p-1" onclick="confirmDelete(<?= $row['id_pembelian'] ?>, '<?= $row['kode_pembelian'] ?>')" title="Hapus">
                                                            <i class="fa fa-trash"></i>
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

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#table-pembelian').DataTable({
            "order": [], // Biar default sorting gak ditimpa otomatis
            "columnDefs": [
                { "orderable": false, "targets": [4] } // Matikan sorting di kolom aksi
            ]
        });

        // Tangkap parameter sukses dari URL 
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status === 'success') {
            $.notify({
                icon: 'fa fa-check-circle',
                title: 'Berhasil!',
                message: msg ? msg : 'Data berhasil disimpan',
            },{
                type: 'success',
                placement: { from: "top", align: "right" },
                time: 1000, delay: 3000,
            });

            // Bersihkan parameter di URL biar notifnya hilang pas di-refresh
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Auto-close buat flash message PHP
        window.setTimeout(function() {
            $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 3000);
    });

    // SweetAlert Konfirmasi Delete
    function confirmDelete(id, kode) {
        Swal.fire({
            title: 'Hapus Rencana Belanja ' + kode + '?',
            text: "Data transaksi pembelian ini beserta detail barangnya akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_pembelian.php?id=' + id;
            }
        })
    }
</script>
</body>
</html>