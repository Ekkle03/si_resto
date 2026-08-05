<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Query ambil data riwayat waste, join ke gudang dan karyawan sesuai database terbaru
$sql = "SELECT h.*, g.nama_gudang, k.nama_lengkap 
        FROM header_waste h
        JOIN master_gudang g ON h.id_gudang = g.id_gudang
        JOIN master_karyawan k ON h.id_karyawan = k.id_karyawan
        ORDER BY h.tgl_waste DESC, h.id_header_waste DESC";
$q_riwayat = mysqli_query($koneksi, $sql);

// Ambil data gudang untuk pilihan di Modal
$q_gudang = mysqli_query($koneksi, "SELECT * FROM master_gudang");

// Variabel Navbar menggunakan session
$nama = htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Guest');
$username = htmlspecialchars($_SESSION['username'] ?? 'guest');
$role = htmlspecialchars($_SESSION['nama_role'] ?? '');
$foto = !empty($_SESSION['foto_profil']) 
        ? 'assets/img/profil/' . htmlspecialchars($_SESSION['foto_profil']) 
        : 'assets/img/profil/default.png';

// --- LOGIKA BATAS TANGGAL WASTE ---
$tgl_maksimal = date('Y-m-d'); // Maksimal hari ini (tidak bisa input masa depan)
// ----------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Waste</title>
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
                    <h3 class="fw-bold mb-0">Waste Barang</h3>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-header d-flex align-items-center bg-white">
                                <h4 class="card-title fw-bold" style="font-size: 15px !important;">Riwayat Waste Barang</h4>
                                <button class="btn btn-success btn-round btn-sm ms-auto shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambahWaste">
                                    <i class="fa fa-plus me-1"></i> Tambah Waste
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['flash_msg'])): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close">
                                        <i class="fa fa-info-circle me-1"></i> <?= $_SESSION['flash_msg'] ?>
                                    </div>
                                    <?php unset($_SESSION['flash_msg']); ?>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-center">
                                            <tr>
                                                <th style="width: 50px;">NO</th>
                                                <th>KODE WASTE</th>
                                                <th>GUDANG</th>
                                                <th>TGL LAPOR</th>
                                                <th>KARYAWAN</th>
                                                <th>STATUS</th> <th style="width: 120px;">ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no=1; while($row = mysqli_fetch_assoc($q_riwayat)): 
                                                // Default fallback untuk data lama
                                                $status_val = $row['status_validasi'] ?? 'Disetujui';
                                            ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="text-dark fw-bold text-center"><?= $row['kode_waste'] ?></td>
                                                <td class="text-center"><?= $row['nama_gudang'] ?></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_waste'])) ?></td>
                                                <td class="text-center"><?= $row['nama_lengkap'] ?></td>
                                                <td class="text-center">
                                                    <?php if($status_val == 'Pending'): ?>
                                                        <span class="badge bg-warning text-dark shadow-sm"><i class="fa fa-clock"></i> Pending</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success shadow-sm"><i class="fa fa-check"></i> Disetujui</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-button-action justify-content-center">
                                                        <?php if($status_val == 'Pending' && in_array(strtolower($role), ['admin', 'purchasing', 'owner'])): ?>
                                                            <button type="button" class="btn btn-link btn-success p-1" onclick="validasiWaste(<?= $row['id_header_waste'] ?>, '<?= $row['kode_waste'] ?>')" title="Validasi & Potong Stok">
                                                                <i class="fa fa-check-double"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <a href="waste_detail.php?id=<?= $row['id_header_waste'] ?>" class="btn btn-link btn-primary p-1" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-link btn-danger p-1" onclick="confirmDelete(<?= $row['id_header_waste'] ?>, '<?= $row['kode_waste'] ?>')" title="Hapus">
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

<div class="modal fade" id="modalTambahWaste" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog shadow" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold text-primary">Buat Laporan Waste Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="add_waste.php" method="POST">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="fw-bold">Pilih Lokasi Gudang</label>
                        <select name="id_gudang" class="form-select" required>
                            <option value="">-- Pilih Gudang --</option>
                            <?php 
                            mysqli_data_seek($q_gudang, 0); 
                            while($g = mysqli_fetch_assoc($q_gudang)): 
                                // LOGIKA FILTER GUDANG UTAMA UNTUK STAF
                                if (strtolower($role) === 'staf' && $g['id_gudang'] == '1') {
                                    continue; // Skip Gudang Utama
                                }
                            ?>
                                <option value="<?= $g['id_gudang'] ?>"><?= $g['nama_gudang'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">Tanggal Kejadian</label>
                        <input type="date" name="tgl_waste" class="form-control fw-bold text-primary bg-light" value="<?= $tgl_maksimal ?>" readonly required>
                        <small class="text-muted d-block mt-1">* Tanggal kejadian otomatis tercatat hari ini.</small>
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
<script src="assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('#basic-datatables').DataTable({
        "order": [],
        "columnDefs": [
            { "orderable": false, "targets": [6] }
        ]
    });

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

        window.history.replaceState({}, document.title, window.location.pathname);
    } else if (status === 'error') {
        $.notify({
            icon: 'fa fa-times-circle',
            title: 'Gagal!',
            message: msg ? msg : 'Terjadi kesalahan',
        },{
            type: 'danger',
            placement: { from: "top", align: "right" },
            time: 1000, delay: 3000, 
        });

        window.history.replaceState({}, document.title, window.location.pathname);
    }

    window.setTimeout(function() {
        $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
    }, 3000);
});

function confirmDelete(id, kode) {
    Swal.fire({
        title: 'Hapus Nota ' + kode + '?',
        text: "Data transaksi dan foto bukti akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "delete_waste.php?id=" + id;
        }
    });
}

function validasiWaste(id, kode) {
    Swal.fire({
        title: 'Validasi Waste ' + kode + '?',
        text: "Setelah divalidasi, stok barang di gudang akan otomatis terpotong!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fa fa-check"></i> Ya, Setujui & Potong Stok!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "validasi_waste.php?id=" + id;
        }
    });
}
</script>
</body>
</html>