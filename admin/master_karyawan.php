<?php
// Mulai session di awal
session_start();

// Hubungkan ke database
include("../config/koneksi_mysql.php");

// Ambil data karyawan + nama divisi + nama role (LEFT JOIN agar role boleh null)
$sql = "SELECT k.*, d.nama_divisi, r.nama_role FROM master_karyawan k 
        INNER JOIN master_divisi d ON k.id_divisi = d.id_divisi
        LEFT JOIN master_role r ON k.id_role = r.id_role
        ORDER BY k.id_karyawan ASC";
$result = mysqli_query($koneksi, $sql);

// Ambil data divisi & role untuk dropdown (modal tambah)
$divisi = mysqli_query($koneksi, "SELECT id_divisi, nama_divisi FROM master_divisi ORDER BY nama_divisi ASC");
$roles  = mysqli_query($koneksi, "SELECT id_role, nama_role FROM master_role ORDER BY nama_role ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Master Karyawan - Sistem Resto</title>
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
        .avatar-list { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid #eee; }
        .text-truncate-2 { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .form-hint { font-size: .8rem; color: #6c757d; }
        .input-icon-right { position: relative; }
        .input-icon-right .toggle-eye { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; }
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
                                            <div class="avatar-lg"><img src="assets/img/profile.jpg" alt="image profile" class="avatar-img rounded" /></div>
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
                                <h4 class="card-title">Data Master Karyawan</h4>
                                <button class="btn btn-outline-primary btn-outline-primary-thicker btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#addKaryawanModal">
                                    <i class="fa fa-plus"></i> Tambah Data
                                </button>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_GET['msg'])): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($_GET['msg']) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="table-responsive">
                                    <table id="basic-datatables" class="table table-striped table-bordered table-hover align-middle">
                                        <thead>
                                        <tr>
                                            <th style="width: 6%;" class="text-center">ID</th>
                                            <th style="width: 10%;" class="text-center">Foto</th>
                                            <th>Username</th>
                                            <th>Nama</th>
                                            <th>Telepon</th>
                                            <th>Divisi</th>
                                            <th>Role</th>
                                            <th style="width: 14%;" class="text-center">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <?php
                                            $foto = trim((string)($row['foto_profil'] ?? ''));
                                            if ($foto === '' || $foto === '0') $foto = 'default.png';
                                            $foto_fs  = __DIR__ . '/assets/img/profil/' . basename($foto);
                                            $foto_web = 'assets/img/profil/' . basename($foto);
                                            if (!file_exists($foto_fs)) {
                                                $foto_web = 'assets/img/profil/default.png';
                                            }
                                            ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars($row['id_karyawan']) ?></td>
                                                <td class="text-center">
                                                    <img src="<?= htmlspecialchars($foto_web) ?>" alt="foto" class="avatar-list">
                                                </td>
                                                <td><?= htmlspecialchars($row['username'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                                <td><?= htmlspecialchars($row['telepon'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($row['nama_divisi']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_role'] ?? '-') ?></td>
                                                <td class="text-center">
                                                    <div class="form-button-action">
                                                        <button type="button" data-bs-toggle="tooltip" title="Edit Data" class="btn btn-primary btn-sm btn-update"
                                                            data-id_karyawan='<?= htmlspecialchars($row['id_karyawan']) ?>'
                                                            data-username='<?= htmlspecialchars($row['username']) ?>'
                                                            data-nama_lengkap='<?= htmlspecialchars($row['nama_lengkap']) ?>'
                                                            data-telepon='<?= htmlspecialchars($row['telepon']) ?>'
                                                            data-alamat='<?= htmlspecialchars($row['alamat']) ?>'
                                                            data-id_divisi='<?= htmlspecialchars($row['id_divisi']) ?>'
                                                            data-id_role='<?= htmlspecialchars($row['id_role']) ?>'
                                                            data-foto_profil='<?= htmlspecialchars($row['foto_profil'] ?: 'default.png') ?>'>
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" data-bs-toggle="tooltip" title="Hapus Data" class="btn btn-danger btn-sm btn-delete" data-id_karyawan='<?= htmlspecialchars($row['id_karyawan']) ?>'>
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

<!-- ================== Modals ================== -->
<!-- Add -->
<div class="modal fade" id="addKaryawanModal" tabindex="-1" aria-labelledby="addKaryawanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="add_karyawan.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addKaryawanModalLabel">Tambah Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username (otomatis)</label>
                            <input type="text" class="form-control" value="Username" disabled />
                            <div class="form-hint">Username dibuat otomatis saat disimpan.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" placeholder="Nama Lengkap" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Divisi</label>
                            <select class="form-select" name="id_divisi" required>
                                <option value="" disabled selected>Pilih Divisi</option>
                                <?php while($d = mysqli_fetch_assoc($divisi)): ?>
                                    <option value="<?= htmlspecialchars($d['id_divisi']) ?>"><?= htmlspecialchars($d['nama_divisi']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="id_role">
                                <option value="" selected>Tanpa Role</option>
                                <?php while($r = mysqli_fetch_assoc($roles)): ?>
                                    <option value="<?= htmlspecialchars($r['id_role']) ?>"><?= htmlspecialchars($r['nama_role']) ?></option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-hint">Role opsional, bisa diset nanti.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="telepon" placeholder="08xxxxxxxxxx" />
                        </div>
                        <div class="col-md-6 input-icon-right">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" id="add_password" placeholder="Password" required />
                            <span class="toggle-eye" data-target="#add_password"><i class="fa fa-eye"></i></span>
                            <div class="form-hint">Masukkan Password</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2" placeholder="Alamat"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Foto Profil (opsional)</label>
                            <input type="file" class="form-control" name="foto_profil" accept="image/*" />
                            <div class="form-hint">File akan disimpan ke <code>admin/assets/img/profil</code>. Jika kosong akan gunakan <code>default.png</code>.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update -->
<div class="modal fade" id="updateKaryawanModal" tabindex="-1" aria-labelledby="updateKaryawanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="update_karyawan.php" enctype="multipart/form-data">
                <input type="hidden" name="id_karyawan" id="update_id_karyawan" />
                <input type="hidden" name="old_foto_profil" id="update_old_foto_profil" />
                <div class="modal-header">
                    <h5 class="modal-title" id="updateKaryawanModalLabel">Update Data Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" id="update_username" readonly disabled />
                            <div class="form-hint">Tidak dapat diubah (dibuat otomatis).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_lengkap" id="update_nama_lengkap" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Divisi</label>
                            <select class="form-select" name="id_divisi" id="update_id_divisi" required>
                                <?php
                                $divisi2 = mysqli_query($koneksi, "SELECT id_divisi, nama_divisi FROM master_divisi ORDER BY nama_divisi ASC");
                                while($d2 = mysqli_fetch_assoc($divisi2)){
                                    echo '<option value="'.htmlspecialchars($d2['id_divisi']).'">'.htmlspecialchars($d2['nama_divisi'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="id_role" id="update_id_role">
                                <option value="">Tanpa Role</option>
                                <?php
                                $roles2 = mysqli_query($koneksi, "SELECT id_role, nama_role FROM master_role ORDER BY nama_role ASC");
                                while($r2 = mysqli_fetch_assoc($roles2)){
                                    echo '<option value="'.htmlspecialchars($r2['id_role']).'">'.htmlspecialchars($r2['nama_role'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="telepon" id="update_telepon" />
                        </div>
                        <div class="col-md-6 input-icon-right">
                            <label class="form-label">Password Baru (opsional)</label>
                            <input type="password" class="form-control" name="password" id="update_password" placeholder="Kosongkan jika tidak mengganti" />
                            <span class="toggle-eye" data-target="#update_password"><i class="fa fa-eye"></i></span>
                            <div class="form-hint">Ganti Password</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" id="update_alamat" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Foto Profil (opsional)</label>
                            <input type="file" class="form-control" name="foto_profil" accept="image/*" />
                            <div class="form-hint">Kosongkan jika tidak ingin mengganti. File berada di <code>admin/assets/img/profil</code>.</div>
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <img id="preview_update_foto" src="assets/img/profil/default.png" class="avatar-list" alt="preview" />
                                <span class="text-muted" id="preview_update_foto_name">default.png</span>
                            </div>
                        </div>
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

<!-- Delete -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus karyawan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">Hapus</a>
            </div>
        </div>
    </div>
</div>

<!-- Core JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/plugin/datatables/datatables.min.js"></script>
<script src="assets/js/plugin/sweetalert/sweetalert.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
$(document).ready(function(){
    $('#basic-datatables').DataTable();

    // Auto-hide alert & redirect
    if ($('.alert-success').length) {
        setTimeout(function(){
            $('.alert-success').fadeOut('slow', function(){
                window.location.href = 'master_karyawan.php';
            });
        }, 3000);
    }
});

// Toggle eye password
function toggleEye(el){
    const targetSel = el.getAttribute('data-target');
    const input = document.querySelector(targetSel);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        el.innerHTML = '<i class="fa fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        el.innerHTML = '<i class="fa fa-eye"></i>';
    }
}

document.querySelectorAll('.toggle-eye').forEach(icon => {
    icon.addEventListener('click', function(){ toggleEye(this); });
});

// === UPDATE button fill ===
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', function(){
        const ds = this.dataset;
        document.getElementById('update_id_karyawan').value = ds.id_karyawan;
        document.getElementById('update_username').value   = ds.username || '';
        document.getElementById('update_nama_lengkap').value = ds.nama_lengkap || '';
        document.getElementById('update_telepon').value    = ds.telepon || '';
        document.getElementById('update_alamat').value     = ds.alamat || '';
        document.getElementById('update_id_divisi').value  = ds.id_divisi || '';
        document.getElementById('update_id_role').value    = ds.id_role || '';
        document.getElementById('update_old_foto_profil').value = ds.foto_profil || 'default.png';
        document.getElementById('preview_update_foto').src = 'assets/img/profil/' + (ds.foto_profil || 'default.png');
        document.getElementById('preview_update_foto_name').innerText = ds.foto_profil || 'default.png';

        const modal = new bootstrap.Modal(document.getElementById('updateKaryawanModal'));
        modal.show();
    });
});

// === DELETE button fill ===
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function(){
        const id = this.dataset.id_karyawan;
        const link = document.getElementById('confirmDeleteLink');
        link.href = 'delete_karyawan.php?id=' + id;
        const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        modal.show();
    });
});
</script>
</body>
</html>
