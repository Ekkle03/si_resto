<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

if (!isset($_GET['id'])) {
    header("Location: permintaan_bahan.php");
    exit;
}

$id_header = (int)$_GET['id'];
// Ambil data header untuk menampilkan Kode Request
$header = mysqli_query($koneksi, "SELECT * FROM header_request WHERE id_header_req = '$id_header'");
$h = mysqli_fetch_assoc($header);

if (!$h) {
    die("Nota permintaan tidak ditemukan.");
}

$pesan = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

// Persiapan Variabel Navbar
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
    <title>Permintaan Bahan</title>
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
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-1">Input Item Permintaan</h3>
                        <p class="text-muted mb-0">
                            Nota: <b><?= $h['kode_request'] ?></b> | 
                            Tgl: <b><?= date('d/m/Y', strtotime($h['tgl_request'])) ?></b>
                        </p>
                    </div>
                    <div class="d-flex">
                        <?php if (isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0): ?>
                        <a href="simpan_item_permintaan.php?aksi=final&id=<?= $id_header ?>" class="btn btn-success fw-bold px-4 shadow-sm">
                            <i class="fas fa-save me-1"></i> SIMPAN 
                        </a>
                        <?php endif; ?>
                        
                        <button type="button" onclick="confirmBatal(<?= $id_header ?>)" class="btn btn-danger fw-bold ms-1 shadow-sm">
                            BATAL
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white border-bottom py-3">
                                <h4 class="card-title text-primary fw-bold"><i class="fa fa-plus-circle me-2"></i> Tambah Item Bahan</h4>
                            </div>
                            <div class="card-body">
                                <?php if ($pesan): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close"><?= $pesan ?></div>
                                <?php endif; ?>
                                
                                <form action="simpan_item_permintaan.php" method="POST" class="row align-items-end">
                                    <input type="hidden" name="id_header" value="<?= $id_header ?>">
                                    
                                    <div class="form-group col-md-7">
                                        <label class="fw-bold text-dark">Cari Nama Bahan (BB atau BSJ)</label>
                                        <select name="id_gabungan" class="form-select border-primary select2-bahan" required>
                                            <option value="">-- Ketik Nama Bahan --</option>
                                            <optgroup label="Bahan Baku (BB)">
                                                <?php 
                                                $q_bb = mysqli_query($koneksi, "SELECT id_bb, nama_bb FROM master_bahan_baku ORDER BY nama_bb ASC");
                                                while($b = mysqli_fetch_assoc($q_bb)) echo "<option value='BB|".$b['id_bb']."'>".$b['nama_bb']."</option>";
                                                ?>
                                            </optgroup>
                                            <optgroup label="Bahan Setengah Jadi (BSJ)">
                                                <?php 
                                                // Filter: Kecualikan kategori Olahan Dasar (ID: 19)
                                                $q_bsj = mysqli_query($koneksi, "SELECT id_bsj, nama_bsj FROM master_bahan_setengah_jadi WHERE id_kategori != 19 ORDER BY nama_bsj ASC");
                                                while($s = mysqli_fetch_assoc($q_bsj)) echo "<option value='BSJ|".$s['id_bsj']."'>".$s['nama_bsj']."</option>";
                                                ?>
                                            </optgroup>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label class="fw-bold">Jumlah (Qty)</label>
                                        <input type="number" step="0.01" name="qty" class="form-control border-primary" placeholder="0.00" required>
                                    </div>

                                    <div class="form-group col-md-2">
                                        <button type="submit" class="btn btn-primary w-100 btn-round">
                                            <i class="fa fa-plus me-1"></i> Tambah
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card card-round mt-4 shadow-sm border-0">
                            <div class="card-header bg-white border-bottom">
                                <div class="card-title fw-bold small text-uppercase text-muted">Daftar Bahan Sementara</div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr class="small fw-bold">
                                                <th class="ps-4">NAMA BAHAN</th>
                                                <th class="text-center">QTY</th>
                                                <th class="text-center">SATUAN</th>
                                                <th class="text-center">AKSI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            if (!isset($_SESSION['keranjang']) || count($_SESSION['keranjang']) == 0): ?>
                                                <tr><td colspan="4" class="p-5 text-center text-muted italic">Belum ada bahan yang dipilih.</td></tr>
                                            <?php else: 
                                                foreach ($_SESSION['keranjang'] as $key => $item):
                                                    if ($item['tipe'] == 'BB') {
                                                        $q = mysqli_query($koneksi, "SELECT b.nama_bb as nama, s.nama_satuan FROM master_bahan_baku b JOIN master_satuan s ON b.id_satuan = s.id_satuan WHERE id_bb = ".$item['id_item']);
                                                    } else {
                                                        $q = mysqli_query($koneksi, "SELECT b.nama_bsj as nama, s.nama_satuan FROM master_bahan_setengah_jadi b JOIN master_satuan s ON b.id_satuan = s.id_satuan WHERE id_bsj = ".$item['id_item']);
                                                    }
                                                    $d = mysqli_fetch_assoc($q);
                                            ?>
                                            <tr class="align-middle">
                                                <td class="ps-4">
                                                    <span class="fw-bold text-dark"><?= $d['nama'] ?></span><br>
                                                    <small class="text-muted">Tipe: <?= $item['tipe'] ?></small>
                                                </td>
                                                <td class="text-center"><span class="badge bg-primary px-3"><?= (float)$item['qty'] ?></span></td>
                                                <td class="text-center"><?= $d['nama_satuan'] ?></td>
                                                <td class="text-center">
                                                    <a href="simpan_item_permintaan.php?aksi=hapus_item&key=<?= $key ?>&id=<?= $id_header ?>" 
                                                       class="btn btn-link btn-danger btn-sm" title="Hapus">
                                                        <i class="fa fa-trash-alt fa-lg"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="mb-5"></div> </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/select2/select2.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('.select2-bahan').select2({
            theme: "bootstrap",
            width: '100%',
            placeholder: "-- Ketik Nama Bahan --"
        });

        window.setTimeout(function() {
            $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 3000);
    });

    // Fungsi Konfirmasi Batal (Panggil delete_permintaan.php)
    function confirmBatal(id) {
        Swal.fire({
            title: 'Batalkan Permintaan?',
            text: "Data yang sudah dibuat akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Lanjut Input'
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke file delete_permintaan.php untuk menghapus jejak nota
                window.location.href = "delete_permintaan.php?id=" + id;
            }
        })
    }
</script>
</body>
</html>