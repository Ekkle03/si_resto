<?php
session_start();
include("../config/koneksi_mysql.php");

// Query utama untuk menampilkan data konversi dengan JOIN ke master_satuan
$query = "SELECT mk.*, 
          sb.nama_satuan AS satuan_besar_nama, 
          sk.nama_satuan AS satuan_kecil_nama
          FROM master_konversi mk
          JOIN master_satuan sb ON mk.satuan_besar = sb.id_satuan
          JOIN master_satuan sk ON mk.satuan_kecil = sk.id_satuan
          ORDER BY mk.id_konversi DESC";
$result = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Konversi Satuan - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-3">Master Data</h3>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center">
                                <h4 class="card-title">Data Master Konversi Satuan</h4>
                                <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addKonversiModal">
                                    <i class="fa fa-plus"></i> Tambah Konversi
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered text-center">
                                        <thead>
                                            <tr>
                                                <th style="width: 5%;">No</th>
                                                <th>Nama Bahan</th>
                                                <th>Tipe</th>
                                                <th>Rumus Konversi</th>
                                                <th>Nilai</th>
                                                <th style="width: 15%;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php 
                                        $no = 1;
                                        while ($row = mysqli_fetch_assoc($result)): 
                                            $id_target = $row['id_komponen'];
                                            if($row['tipe_bahan'] == 'BB') {
                                                $q_nama = mysqli_query($koneksi, "SELECT b.nama_bb as nama FROM master_bahan_baku b WHERE b.id_bb = '$id_target'");
                                            } else {
                                                $q_nama = mysqli_query($koneksi, "SELECT b.nama_bsj as nama FROM master_bahan_setengah_jadi b WHERE b.id_bsj = '$id_target'");
                                            }
                                            $d_nama = mysqli_fetch_assoc($q_nama);
                                        ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td class="text-start"><?= htmlspecialchars($d_nama['nama'] ?? 'Data Terhapus') ?></td>
                                                <td><span class="badge badge-info"><?= $row['tipe_bahan'] ?></span></td>
                                                <td>1 <b><?= $row['satuan_besar_nama'] ?></b> = ... <b><?= $row['satuan_kecil_nama'] ?></b></td>
                                                <td class="fw-bold"><?= number_format($row['nilai_konversi']) ?></td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-primary btn-sm btn-update" 
                                                                data-id_konversi="<?= $row['id_konversi'] ?>"
                                                                data-nama="<?= htmlspecialchars($d_nama['nama'] ?? '') ?>"
                                                                data-pilihan="<?= $row['tipe_bahan'].'-'.$row['id_komponen'] ?>"
                                                                data-nilai="<?= $row['nilai_konversi'] ?>"
                                                                data-besar="<?= $row['satuan_besar'] ?>"
                                                                data-kecil="<?= $row['satuan_kecil'] ?>">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger btn-sm btn-delete" data-id_konversi='<?= $row['id_konversi'] ?>'>
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

<div class="modal fade" id="addKonversiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_konversi.php">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Rumus Konversi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Pilih Item (Bahan)</label>
                            <select name="pilihan_item" id="pilihan_item_add" class="form-control" required>
                                <option value="">-- Pilih Item --</option>
                                <optgroup label="Bahan Baku">
                                    <?php
                                    $bb = mysqli_query($koneksi, "SELECT b.id_bb, b.nama_bb, s.nama_satuan FROM master_bahan_baku b JOIN master_satuan s ON b.id_satuan = s.id_satuan ORDER BY b.nama_bb ASC");
                                    while($r = mysqli_fetch_assoc($bb)) {
                                        echo "<option value='BB-{$r['id_bb']}' data-sat_ori='{$r['nama_satuan']}'>[BB] {$r['nama_bb']}</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Bahan Setengah Jadi">
                                    <?php
                                    $bsj = mysqli_query($koneksi, "SELECT b.id_bsj, b.nama_bsj, s.nama_satuan FROM master_bahan_setengah_jadi b JOIN master_satuan s ON b.id_satuan = s.id_satuan ORDER BY b.nama_bsj ASC");
                                    while($r = mysqli_fetch_assoc($bsj)) {
                                        echo "<option value='BSJ-{$r['id_bsj']}' data-sat_ori='{$r['nama_satuan']}'>[BSJ] {$r['nama_bsj']}</option>";
                                    }
                                    ?>
                                </optgroup>
                            </select>
                            <div id="hint_satuan_add" class="hint-text mt-1"></div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan Besar</label>
                            <select name="satuan_besar" id="satuan_besar_add" class="form-control" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php
                                $sat = mysqli_query($koneksi, "SELECT * FROM master_satuan ORDER BY nama_satuan ASC");
                                while($s = mysqli_fetch_assoc($sat)) echo "<option value='{$s['id_satuan']}'>{$s['nama_satuan']}</option>";
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nilai Konversi</label>
                            <input type="number" step="0.01" name="nilai_konversi" class="form-control" placeholder="Contoh: 1000" required />
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan Kecil</label>
                            <select name="satuan_kecil" id="satuan_kecil_add" class="form-control" required>
                                <option value="">-- Pilih Satuan --</option>
                                <?php
                                mysqli_data_seek($sat, 0);
                                while($s = mysqli_fetch_assoc($sat)) echo "<option value='{$s['id_satuan']}'>{$s['nama_satuan']}</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Rumus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="updateKonversiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="update_konversi.php">
                <input type="hidden" name="id_konversi" id="update_id_konversi">
                <div class="modal-header">
                    <h5 class="modal-title">Update Rumus Konversi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Item (Komponen)</label>
                            <input type="text" id="update_nama_item_display" class="form-control" readonly style="background-color: #f4f4f4;">
                            <input type="hidden" name="pilihan_item" id="update_pilihan_item">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan Besar</label>
                            <select name="satuan_besar" id="update_satuan_besar" class="form-control" required>
                                <?php
                                mysqli_data_seek($sat, 0);
                                while($s = mysqli_fetch_assoc($sat)) echo "<option value='{$s['id_satuan']}'>{$s['nama_satuan']}</option>";
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nilai Konversi</label>
                            <input type="number" step="0.01" name="nilai_konversi" id="update_nilai_konversi" class="form-control" required />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Satuan Kecil</label>
                            <select name="satuan_kecil" id="update_satuan_kecil" class="form-control" required>
                                <?php
                                mysqli_data_seek($sat, 0);
                                while($s = mysqli_fetch_assoc($sat)) echo "<option value='{$s['id_satuan']}'>{$s['nama_satuan']}</option>";
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update Rumus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Hapus Konversi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Rumus konversi ini akan dihapus. Lanjutkan?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
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
        $('#basic-datatables').DataTable();

        if ($('.alert-success').length) {
            setTimeout(function() {
                $('.alert-success').fadeOut('slow', function() {
                    window.location.href = 'konversi_satuan.php';
                });
            }, 3000);
        }

        // Handler Hint Satuan Master
        $('#pilihan_item_add').change(function() {
            const selected = $(this).find(':selected');
            const satOri = selected.data('sat_ori');
            if(satOri) {
                $('#hint_satuan_add').html("ℹ️ Satuan Stok di Master: <b>" + satOri + "</b>");
            } else {
                $('#hint_satuan_add').html("");
            }
        });
    });

    // Handler Update
    document.querySelectorAll('.btn-update').forEach(button => {
        button.addEventListener('click', function() {
            const d = this.dataset;
            document.getElementById('update_id_konversi').value = d.id_konversi;
            document.getElementById('update_nama_item_display').value = d.nama;
            document.getElementById('update_pilihan_item').value = d.pilihan;
            document.getElementById('update_nilai_konversi').value = d.nilai;
            document.getElementById('update_satuan_besar').value = d.besar;
            document.getElementById('update_satuan_kecil').value = d.kecil;

            new bootstrap.Modal(document.getElementById('updateKonversiModal')).show();
        });
    });

    // Handler Delete
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.dataset.id_konversi;
            document.getElementById('confirmDeleteLink').href = 'delete_konversi.php?id=' + id;
            new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
        });
    });
</script>
</body>
</html>