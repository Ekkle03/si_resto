<?php
session_start();
include("../config/auth.php");
include("../config/koneksi_mysql.php");

// Query Header: Ambil data permintaan dan nama gudang tujuan
$sql_header = "SELECT h.*, g.nama_gudang as tujuan 
               FROM header_request h 
               LEFT JOIN master_gudang g ON h.id_gudang_tujuan = g.id_gudang 
               ORDER BY h.id_header_req DESC";
$query_header = mysqli_query($koneksi, $sql_header);

$pesan = $_SESSION['flash_msg'] ?? '';
unset($_SESSION['flash_msg']);

// Navbar session variables
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
    <title>Permintaan Bahan - Sistem Resto</title>
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
                    <h3 class="fw-bold mb-0">Permintaan Bahan</h3>
                    <?php if (strtolower($role) != 'purchasing'): ?>
                    <div>
                        <button class="btn btn-primary btn-round fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahRequest">
                            <i class="fa fa-plus me-1"></i> Buat Permintaan
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-round shadow-sm border-0">
                            <div class="card-body">
                                <?php if ($pesan): ?>
                                    <div class="alert alert-info border-0 shadow-sm auto-close">
                                        <i class="fa fa-check-circle me-1"></i> <?= $pesan ?>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="display table table-striped table-hover table-bordered" style="width: 100%;">
                                        <thead class="bg-light text-center">
                                            <tr class="small text-uppercase fw-bold">
                                                <th style="width: 50px;">No</th>
                                                <th>Kode</th>
                                                <th>Tanggal</th>
                                                <th>Tujuan</th>
                                                <th>Status</th>
                                                <th style="width: 120px;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $no = 1; 
                                            while($row = mysqli_fetch_assoc($query_header)): 
                                                $status = $row['status'];
                                                if ($status == 'Selesai') {
                                                    $badgeClass = 'bg-success';
                                                } elseif ($status == 'Sebagian') {
                                                    $badgeClass = 'bg-info';
                                                } else {
                                                    $badgeClass = 'bg-warning text-dark';
                                                }
                                            ?>
                                            <tr>
                                                <td class="text-center text-muted"><?= $no++ ?></td>
                                                <td class="text-center fw-bold text-dark"><?= $row['kode_request'] ?></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($row['tgl_request'])) ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['tujuan'] ?? '-') ?></td>
                                                <td class="text-center"><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
                                                <td class="text-center">
                                                    <div class="form-button-action justify-content-center">
                                                        <?php if (strtolower($role) == 'purchasing' && ($status == 'Pending' || $status == 'Sebagian')): ?>
                                                            <button type="button" class="btn btn-link btn-success p-1 btn-konfirmasi" 
                                                                    data-id="<?= $row['id_header_req'] ?>" 
                                                                    data-kode="<?= $row['kode_request'] ?>"
                                                                    title="Konfirmasi Penyerahan">
                                                                <i class="fa fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="permintaan_detail_view.php?id=<?= $row['id_header_req'] ?>" class="btn btn-link btn-primary p-1" title="Lihat Detail">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($status == 'Pending'): ?>
                                                        <button type="button" class="btn btn-link btn-danger p-1 btn-delete-nota" data-id="<?= $row['id_header_req'] ?>" data-kode="<?= $row['kode_request'] ?>" title="Hapus">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
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

<div class="modal fade" id="modalTambahRequest" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog shadow" role="document">
        <div class="modal-content" style="border-radius: 15px;">
            <form action="add_permintaan.php" method="POST">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold text-primary">Buat Permintaan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="fw-bold">Tanggal Permintaan</label>
                        <input type="date" name="tgl_request" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">Minta Ke Gudang</label>
                        <select name="id_gudang_tujuan" class="form-select" required>
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            <?php 
                            $g_query = mysqli_query($koneksi, "SELECT * FROM master_gudang WHERE id_gudang != 1"); 
                            while($g = mysqli_fetch_assoc($g_query)) {
                                echo "<option value='".$g['id_gudang']."'>".$g['nama_gudang']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="fw-bold">Keterangan Tambahan</label>
                        <textarea name="keterangan" class="form-control" placeholder="Contoh: Stok operasional harian" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary btn-round" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-round px-4">Lanjut Isi Bahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        $('#basic-datatables').DataTable({
            "order": [],
            "columnDefs": [
                { "orderable": false, "targets": [5] }
            ]
        });

        // TRIGGER AUTO-PILOT PENYERAHAN
        $('.btn-konfirmasi').click(function() {
            let id = $(this).data('id');
            let kode = $(this).data('kode');
            
            Swal.fire({
                title: 'Proses Penyerahan ' + kode + '?',
                text: 'Sistem akan otomatis menyerahkan bahan sesuai dengan stok yang tersedia di Gudang Utama saat ini. Lanjutkan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Serahkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Memproses Penyerahan...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                    $.ajax({
                        url: 'proses_auto_penyerahan.php', // Panggil Robot Eksekutor
                        type: 'POST',
                        data: { id: id },
                        dataType: 'json',
                        success: function(res) {
                            if (res.status === 'success') {
                                // Tampilkan laporan setelah berhasil
                                Swal.fire({
                                    title: res.title,
                                    html: res.html,
                                    icon: res.icon
                                }).then(() => { location.reload(); }); 
                            } else {
                                Swal.fire('Error', res.msg, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal memproses data. Cek file proses_auto_penyerahan.php', 'error');
                        }
                    });
                }
            });
        });

        $('.btn-delete-nota').click(function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let kode = $(this).data('kode');
            
            Swal.fire({
                title: 'Hapus Nota ' + kode + '?',
                text: "Data yang dihapus tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "delete_permintaan.php?id=" + id;
                }
            })
        });

        window.setTimeout(function() {
            $(".auto-close").fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); });
        }, 3000);
    });
</script>
</body>
</html>