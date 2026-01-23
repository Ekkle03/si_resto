<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Ambil ID Induk Kategori BSJ (Pastikan Nama Kategori di DB sama persis)
$cek_induk = mysqli_query($koneksi, "SELECT id_kategori FROM master_kategori WHERE nama_kategori = 'BAHAN ½ JADI' LIMIT 1");
$data_induk = mysqli_fetch_assoc($cek_induk);
$id_induk_bsj = $data_induk['id_kategori'] ?? 0;

// 2. Query List Data
$sql = "SELECT bsj.*, s.nama_satuan, k.nama_kategori
        FROM master_bahan_setengah_jadi bsj
        INNER JOIN master_satuan s ON bsj.id_satuan = s.id_satuan
        INNER JOIN master_kategori k ON bsj.id_kategori = k.id_kategori
        ORDER BY bsj.id_bsj ASC";
$result = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Bahan Setengah Jadi - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-3">Master Bahan Setengah Jadi (BSJ)</h3>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4 class="card-title">Data Master BSJ</h4>
                                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addBsjModal">
                                    <i class="fa fa-plus"></i> Tambah Data
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover align-middle">
                                        <thead class="text-center">
                                            <tr>
                                                <th>NO</th>
                                                <th>KODE</th>
                                                <th class="text-start">NAMA BAHAN</th>
                                                <th>SATUAN</th>
                                                <th>KATEGORI</th>
                                                <th>TAHAP</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr class="text-center">
                                                <td><?= $no++; ?></td>
                                                <td><?= htmlspecialchars($row['kode_bsj']) ?></td>
                                                <td class="text-start fw-bold"><?= htmlspecialchars($row['nama_bsj']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge <?= $row['tahap'] === 'bsj2' ? 'badge-t2' : 'badge-t1' ?>" 
                                                        style="color: #2a2f5b !important; font-weight: bold; font-family: inherit;">
                                                        <?= $row['tahap'] === 'bsj2' ? 'Tahap 2' : 'Tahap 1' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-primary btn-sm btn-update me-1"
                                                                data-id_bsj="<?= $row['id_bsj'] ?>"
                                                                data-nama_bsj="<?= htmlspecialchars($row['nama_bsj']) ?>"
                                                                data-id_satuan="<?= $row['id_satuan'] ?>"
                                                                data-id_kategori="<?= $row['id_kategori'] ?>"
                                                                data-tahap="<?= $row['tahap'] ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete"
                                                                data-id_bsj="<?= $row['id_bsj'] ?>">
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

<div class="modal fade" id="addBsjModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="add_bahan_setengahjadi.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Data Bahan Setengah Jadi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Bahan Setengah Jadi</label>
                    <input type="text" class="form-control" name="nama_bsj" placeholder="Misal: Ayam Ungkep" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Satuan</label>
                    <select class="form-select" name="id_satuan" required>
                        <option value="" disabled selected>-- Pilih Satuan --</option>
                        <?php
                        $satuan = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                        while ($s = mysqli_fetch_assoc($satuan)) echo "<option value='".$s['id_satuan']."'>".$s['nama_satuan']."</option>";
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-primary fw-bold">Kategori (Hanya Sub-BSJ)</label>
                    <select class="form-select" name="id_kategori" id="update_id_kategori" required>
                        <?php
                        // Samakan persis kodenya dengan yang di Modal Add kamu
                        $query_induk_up = mysqli_query($koneksi, "SELECT id_kategori FROM master_kategori WHERE nama_kategori = 'Bahan Setengah Jadi' LIMIT 1");
                        $data_induk_up = mysqli_fetch_assoc($query_induk_up);
                        $id_parent_bsj_up = $data_induk_up['id_kategori'] ?? 0;

                        $query_sub_up = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori WHERE parent_id = '$id_parent_bsj_up' ORDER BY nama_kategori ASC");
                        
                        while ($sub_up = mysqli_fetch_assoc($query_sub_up)): ?>
                            <option value="<?= $sub_up['id_kategori'] ?>">
                                <?= htmlspecialchars($sub_up['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tahap Produksi</label>
                    <select class="form-select" name="tahap" required>
                        <option value="bsj1">BSJ 1 (Olahan Dasar)</option>
                        <option value="bsj2">BSJ 2 (Olahan Lanjutan)</option>
                    </select>
                </div>
                <hr>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="langsung_bom" value="1" id="langsung_bom">
                    <label class="form-check-label fw-bold" for="langsung_bom">
                        Langsung buat resep (BOM) setelah simpan?
                    </label>
                </div>
            </div> <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitAdd" class="btn btn-primary">Simpan</button>
            </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updateBsjModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="update_bahan_setengahjadi.php">
                <input type="hidden" name="id_bsj" id="update_id_bsj">
                <div class="modal-header">
                    <h5 class="modal-title">Update Data BSJ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Bahan Setengah Jadi</label>
                        <input type="text" class="form-control" name="nama_bsj" id="update_nama_bsj" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Satuan</label>
                        <select class="form-select" name="id_satuan" id="update_id_satuan" required>
                            <?php
                            $satuan2 = mysqli_query($koneksi, "SELECT id_satuan, nama_satuan FROM master_satuan ORDER BY nama_satuan ASC");
                            while ($s2 = mysqli_fetch_assoc($satuan2)) echo "<option value='".$s2['id_satuan']."'>".$s2['nama_satuan']."</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="id_kategori" id="update_id_kategori" required>
                            <?php
                            // Cari ID induk Bahan Setengah Jadi
                            $q_induk = mysqli_query($koneksi, "SELECT id_kategori FROM master_kategori WHERE nama_kategori = 'Bahan Setengah Jadi' LIMIT 1");
                            $d_induk = mysqli_fetch_assoc($q_induk);
                            $parent_id = $d_induk['id_kategori'] ?? 0;

                            // Ambil hanya sub-kategori (anak) dari Bahan Setengah Jadi
                            $q_sub = mysqli_query($koneksi, "SELECT id_kategori, nama_kategori FROM master_kategori WHERE parent_id = '$parent_id' ORDER BY nama_kategori ASC");
                            
                            while ($sub = mysqli_fetch_assoc($q_sub)): ?>
                                <option value="<?= $sub['id_kategori'] ?>">
                                    <?= htmlspecialchars($sub['nama_kategori']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tahap Produksi</label>
                        <select class="form-select" name="tahap" id="update_tahap" required>
                            <option value="bsj1">BSJ 1 (Olahan Dasar)</option>
                            <option value="bsj2">BSJ 2 (Olahan Lanjutan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus bahan ini? Seluruh data resep (BOM) terkait akan ikut terhapus secara otomatis.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus Permanen</a>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
    $(document).ready(function () {
        $('#basic-datatables').DataTable();

        // 1. Auto-hide alert
        if ($('.alert').length) {
            setTimeout(function () {
                $('.alert').fadeOut('slow', function () {
                    window.history.replaceState({}, document.title, "master_bahan_setengahjadi.php");
                });
            }, 3000);
        }

        // 2. Update Data (Event Delegation)
        $(document).on('click', '.btn-update', function() {
            var ds = $(this).data();
            
            $('#update_id_bsj').val(ds.id_bsj);
            $('#update_nama_bsj').val(ds.nama_bsj);
            $('#update_id_satuan').val(ds.id_satuan);
            
            // Mengatur kategori: JS akan mencari value yang sama dengan ID di tabel
            // Karena PHP di atas sudah kita batasi hanya 'Bumbu', 
            // maka kategori 'Bahan Baku' tidak akan bisa terpilih di sini.
            $('#update_id_kategori').val(ds.id_kategori).trigger('change');
            
            $('#update_tahap').val(ds.tahap);
            $('#updateBsjModal').modal('show');
        });

        // 3. Delete Data
        $(document).on('click', '.btn-delete', function() {
            var id = $(this).data('id_bsj');
            $('#confirmDeleteLink').attr('href', 'delete_bahan_setengahjadi.php?id=' + id);
            $('#confirmDeleteModal').modal('show');
        });

        // 4. Logika Checkbox BOM di Modal Add
        $(document).on('change', '#langsung_bom', function() {
            var btn = $('#btnSubmitAdd');
            if ($(this).is(':checked')) {
                btn.text('Simpan & Buat BOM').removeClass('btn-primary').addClass('btn-warning');
            } else {
                btn.text('Simpan').removeClass('btn-warning').addClass('btn-primary');
            }
        });
    });
</script>
</body>
</html>