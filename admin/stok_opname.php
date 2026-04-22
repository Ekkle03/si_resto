<?php
session_start();
include("../config/koneksi_mysql.php");

// 1. Ambil data riwayat Stok Opname
$sql = "SELECT h.*, g.nama_gudang 
        FROM header_opname h
        JOIN master_gudang g ON h.id_gudang = g.id_gudang
        ORDER BY h.tgl_opname DESC, h.id_header_opname DESC";
$q_riwayat = mysqli_query($koneksi, $sql);

// 2. Ambil data gudang untuk pilihan di Modal
$q_gudang = mysqli_query($koneksi, "SELECT * FROM master_gudang");

// Navbar Session
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
    <title>Stok Opname</title>
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
                <div class="page-header d-flex justify-content-between align-items-center mb-4">
                    <h3 class="fw-bold mb-0">Stok Opname</h3>
                    <div>
                        <button class="btn btn-primary btn-round fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahOpname">
                            <i class="fa fa-plus me-1"></i> Tambah Opname
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header bg-white">
                                <h4 class="card-title" style="font-size: 15px !important;">Riwayat Stok Opname</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['flash_msg'])): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close">
                                        <i class="fa fa-info-circle me-1"></i> <?= $_SESSION['flash_msg'] ?>
                                    </div>
                                    <?php unset($_SESSION['flash_msg']); ?>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="display table table-striped table-hover table-bordered">
                                        <thead class="bg-light text-center">
                                            <tr>
                                                <th style="width: 50px;">No</th>
                                                <th>Kode Opname</th>
                                                <th>Gudang</th>
                                                <th>Tgl Opname</th>
                                                <th>Keterangan</th>
                                                <th style="width: 100px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; while($row = mysqli_fetch_assoc($q_riwayat)): ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="text-dark fw-bold text-center"><?= $row['kode_opname'] ?></td>
                                                <td class="text-center"><?= $row['nama_gudang'] ?></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_opname'])) ?></td>
                                                <td><?= htmlspecialchars($row['keterangan'] ?: '-') ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action justify-content-center">
                                                        <a href="stok_opname_detail.php?id=<?= $row['id_header_opname'] ?>" class="btn btn-link btn-primary p-1" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-link btn-danger p-1" onclick="confirmDelete(<?= $row['id_header_opname'] ?>, '<?= $row['kode_opname'] ?>')" title="Hapus">
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

<div class="modal fade" id="modalTambahOpname" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog shadow" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-primary">Buat Opname Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="add_opname.php" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group mb-3">
                                <label class="fw-bold">Pilih Lokasi Gudang</label>
                                <select name="id_gudang" class="form-select" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    <?php mysqli_data_seek($q_gudang, 0); while($g = mysqli_fetch_assoc($q_gudang)): ?>
                                        <option value="<?= $g['id_gudang'] ?>"><?= $g['nama_gudang'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label class="fw-bold">Tanggal Opname</label>
                                <input type="date" name="tgl_opname" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label class="fw-bold">Keterangan (Opsional)</label>
                                <textarea name="keterangan" class="form-control" rows="3" style="resize: none;" placeholder="Contoh: Opname rutin bulanan..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-round px-4">Lanjut Isi Detail</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

<script>
$(document).ready(function() {
    $('#basic-datatables').DataTable({
        "order": [], // Biar urutan default dari PHP tetap dipakai
        "columnDefs": [
            { "orderable": false, "targets": [5] } // Matikan sorting di kolom aksi
        ]
    });

    // Handle parameter sukses dari URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('status') === 'success') {
        $.notify({
            icon: 'fa fa-check-circle',
            title: 'Berhasil!',
            message: urlParams.get('msg') || 'Data berhasil disimpan',
        },{
            type: 'success',
            placement: { from: "top", align: "right" },
            time: 1000, delay: 3000,
        });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Auto-close buat flash message PHP (kalau ada)
    window.setTimeout(function() {
        $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
    }, 3000);
});

function confirmDelete(id, kode) {
    Swal.fire({
        title: 'Hapus Nota ' + kode + '?',
        text: "Data akan dihapus permanen dari sistem!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete_opname.php?id=" + id;
        }
    })
}
</script>
</body>
</html>